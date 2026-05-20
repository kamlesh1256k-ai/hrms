/**
 * Tracker singleton — owns capture loops + sync + state.
 *
 * Loops:
 *   activity   every cfg.activityIntervalSec     → POST /activity-tracker/activity/store
 *   screenshot every cfg.screenshotIntervalMin   → POST /activity-tracker/screenshot/upload
 *   heartbeat  every cfg.heartbeatIntervalMin    → POST /activity-tracker/device/heartbeat
 *   syncQueue  every 30s                         → flushes offline SQLite queue when API returns
 *
 * Active-window detection uses the `active-win` package (returns owner name +
 * window title). Idle detection uses Electron's powerMonitor (system idle).
 */
const { EventEmitter }   = require('events');
const { powerMonitor }   = require('electron');
const path               = require('path');
const fs                 = require('fs');
const os                 = require('os');
const { machineIdSync }  = require('node-machine-id');
const screenshot         = require('screenshot-desktop');

const apiClient = require('../sync/api');
const queue     = require('../store/queue');

class Tracker extends EventEmitter {
    constructor() {
        super();
        this.running       = false;
        this.timers        = { activity: null, screenshot: null, heartbeat: null, sync: null };
        this.lastWindow    = null;       // for app-usage span aggregation
        this.spanStartedAt = null;
        this.kbCount       = 0;          // not directly hookable from Electron without elevated perms;
        this.mouseCount    = 0;          // we approximate via "samples where idle < 5s"
        this.lastSyncAt    = null;
        this.apiReachable  = false;
        this.lastEvent     = null;
        this.lastWasError  = false;
        this.activeApp     = null;
        this.deviceId      = null;
        this.cfgRef        = null;       // live electron-store handle (set on start)
    }

    isRunning() { return this.running; }

    getStatus() {
        return {
            running:       this.running,
            apiReachable:  this.apiReachable,
            deviceId:      this.deviceId || (this.cfgRef && this.cfgRef.get('deviceId')) || null,
            lastSyncAt:    this.lastSyncAt,
            pendingQueue:  queue.size(),
            activeApp:     this.activeApp,
            lastEvent:     this.lastEvent,
            lastWasError:  this.lastWasError,
        };
    }

    pushEvent(text, isErr = false) {
        this.lastEvent    = text;
        this.lastWasError = !!isErr;
        this.emit('status');
    }

    /* ─── Device registration ─── */
    async registerDevice(store) {
        const cfg = store.store;
        if (!cfg.apiUrl || !cfg.token) {
            return { ok: false, message: 'API URL or token missing' };
        }

        // Stable device UUID via node-machine-id (falls back to hostname-os string)
        let uuid = cfg.deviceUuid;
        if (!uuid) {
            try { uuid = machineIdSync(true).slice(0, 64); } catch (e) { uuid = `fallback-${os.hostname()}-${os.platform()}`; }
            store.set('deviceUuid', uuid);
        }

        try {
            const res = await apiClient.post(cfg, '/activity-tracker/device/register', {
                device_uuid: uuid,
                device_name: cfg.deviceName || os.hostname(),
                os:          `${os.platform()} ${os.release()}`,
            });
            if (res.data && res.data.ok) {
                store.set('deviceId', res.data.device_id);
                this.deviceId = res.data.device_id;
                this.apiReachable = true;
                this.pushEvent(`Registered as device ${res.data.device_id}`);
                return { ok: true, deviceId: res.data.device_id };
            }
            this.pushEvent('Registration response unrecognised', true);
            return { ok: false, message: 'Bad response' };
        } catch (e) {
            this.apiReachable = false;
            this.pushEvent(`Registration failed: ${e.message}`, true);
            return { ok: false, message: e.message };
        }
    }

    /* ─── Lifecycle ─── */
    start(store) {
        // File log for debugging — appears at %APPDATA%\hrms-activity-tracker-agent\agent.log
        try {
            const logPath = path.join(require('electron').app.getPath('userData'), 'agent.log');
            fs.appendFileSync(logPath, `[${new Date().toISOString()}] start() called. running=${this.running} apiUrl=${store.store.apiUrl} hasToken=${!!store.store.token} consent=${store.store.consentAccepted}\n`);
        } catch (e) {}

        if (this.running) return;
        this.cfgRef = store;
        const cfg = store.store;

        if (!cfg.apiUrl || !cfg.token) { this.pushEvent('Cannot start: API URL/token missing', true); return; }
        if (!cfg.consentAccepted)      { this.pushEvent('Cannot start: consent not accepted',     true); return; }

        this.running = true;
        this.deviceId = cfg.deviceId;

        const activityMs   = (cfg.activityIntervalSec   || 30) * 1000;
        // Prefer seconds-based override (`screenshotIntervalSec`) when present;
        // otherwise fall back to the minute-based default (5 min for production).
        const screenshotMs = cfg.screenshotIntervalSec
            ? (cfg.screenshotIntervalSec * 1000)
            : ((cfg.screenshotIntervalMin || 5) * 60 * 1000);
        const heartbeatMs  = (cfg.heartbeatIntervalMin  || 1)  * 60 * 1000;

        this.timers.activity   = setInterval(() => this._sampleActivity().catch(()=>{}), activityMs);
        this.timers.screenshot = setInterval(() => this._captureScreenshot().catch(()=>{}), screenshotMs);
        this.timers.heartbeat  = setInterval(() => this._heartbeat().catch(()=>{}), heartbeatMs);
        this.timers.sync       = setInterval(() => this._flushQueue().catch(()=>{}), 30 * 1000);

        // Kick off immediately for snappier UI feedback
        this._heartbeat().catch(()=>{});
        this._sampleActivity().catch(()=>{});

        this.pushEvent('Tracker started');
    }

    stop() {
        if (!this.running) return;
        this.running = false;
        Object.values(this.timers).forEach(t => t && clearInterval(t));
        this.timers = { activity: null, screenshot: null, heartbeat: null, sync: null };

        // If a span is open, close it & queue final usage record
        if (this.lastWindow && this.spanStartedAt) {
            this._queueAppUsage(this.lastWindow, this.spanStartedAt, new Date()).catch(()=>{});
            this.lastWindow = null;
            this.spanStartedAt = null;
        }

        this.pushEvent('Tracker stopped');
    }

    _logToFile(msg) {
        try {
            const logPath = path.join(require('electron').app.getPath('userData'), 'agent.log');
            fs.appendFileSync(logPath, `[${new Date().toISOString()}] ${msg}\n`);
        } catch (e) {}
    }

    /* ─── Activity sample ─── */
    async _sampleActivity() {
        this._logToFile('_sampleActivity tick');
        const cfg = this.cfgRef.store;
        let win   = null;
        try {
            const activeWin = require('active-win');
            win = await activeWin();
        } catch (e) { /* swallow — module may not be installed in dev */ }

        const idle = powerMonitor.getSystemIdleTime();   // seconds
        const appName = win?.owner?.name || 'unknown';
        const wTitle  = (win?.title || '').slice(0, 500);

        this.activeApp = appName;

        // Aggregate consecutive same-app windows into one app-usage span
        const winKey = `${appName}${wTitle}`;
        if (this.lastWindow !== winKey) {
            if (this.lastWindow && this.spanStartedAt) {
                await this._queueAppUsage(this.lastWindow, this.spanStartedAt, new Date());
            }
            this.lastWindow    = winKey;
            this.spanStartedAt = new Date();
        }

        // Approx K/M counts: if idle < 5s we credit one tick to keyboard (proxy)
        const kb = idle < 5 ? Math.floor(cfg.activityIntervalSec / 2) : 0;
        const mouse = idle < 5 ? Math.floor(cfg.activityIntervalSec / 3) : 0;

        const sample = {
            device_uuid:          cfg.deviceUuid,
            active_app:           appName,
            active_window_title:  wTitle,
            idle_seconds:         idle,
            keyboard_count:       kb,
            mouse_count:          mouse,
            captured_at:          new Date().toISOString(),
        };

        try {
            await apiClient.post(cfg, '/activity-tracker/activity/store', sample);
            this.apiReachable = true;
            this.lastSyncAt   = new Date().toISOString();
            this.pushEvent(`Activity sample sent: ${appName}`);
        } catch (e) {
            this.apiReachable = false;
            await queue.enqueue('activity', sample);
            this.pushEvent(`Activity queued (offline): ${e.message}`, true);
        }
    }

    /* ─── Screenshot ─── */
    async _captureScreenshot() {
        this._logToFile('_captureScreenshot tick');
        const cfg = this.cfgRef.store;
        try {
            const tmpDir = path.join(os.tmpdir(), 'hrms-tracker');
            if (!fs.existsSync(tmpDir)) fs.mkdirSync(tmpDir, { recursive: true });
            const filePath = path.join(tmpDir, `ss_${Date.now()}.jpg`);

            // Try platform-appropriate capture. On Windows, use a PowerShell
            // one-liner with System.Drawing — works without any external bins
            // or fragile manifest paths (screenshot-desktop has issues there).
            // Fall back to the screenshot-desktop package on macOS/Linux.
            if (process.platform === 'win32') {
                await this._captureScreenshotWindows(filePath);
            } else {
                const buf = await screenshot({ format: 'jpg' });
                fs.writeFileSync(filePath, buf);
            }

            const meta = {
                device_uuid:         cfg.deviceUuid,
                active_app:          this.activeApp || '',
                active_window_title: this.lastWindow ? this.lastWindow.split('')[1] : '',
                captured_at:         new Date().toISOString(),
            };

            try {
                await apiClient.uploadFile(cfg, '/activity-tracker/screenshot/upload', filePath, 'image', meta);
                fs.unlink(filePath, () => {});
                this.apiReachable = true;
                this.lastSyncAt = new Date().toISOString();
                this.pushEvent(`Screenshot uploaded`);
            } catch (e) {
                this.apiReachable = false;
                await queue.enqueue('screenshot', { ...meta, _filePath: filePath });
                this.pushEvent(`Screenshot queued (offline): ${e.message}`, true);
            }
        } catch (e) {
            this.pushEvent(`Screenshot capture failed: ${e.message}`, true);
        }
    }

    /**
     * Capture full-screen JPG on Windows via PowerShell + System.Drawing.
     * No external binaries — works as long as .NET 4 is installed (default on
     * Windows 10/11). Replaces the unreliable `screenshot-desktop` flow.
     */
    async _captureScreenshotWindows(filePath) {
        return new Promise((resolve, reject) => {
            // PowerShell script: snapshot all virtual screen bounds → JPG
            const ps = `
                Add-Type -AssemblyName System.Windows.Forms;
                Add-Type -AssemblyName System.Drawing;
                $b = [System.Windows.Forms.SystemInformation]::VirtualScreen;
                $bmp = New-Object System.Drawing.Bitmap $b.Width, $b.Height;
                $g = [System.Drawing.Graphics]::FromImage($bmp);
                $g.CopyFromScreen($b.Left, $b.Top, 0, 0, $bmp.Size);
                $jpg = [System.Drawing.Imaging.ImageFormat]::Jpeg;
                $bmp.Save('${filePath.replace(/\\/g, '\\\\')}', $jpg);
                $g.Dispose(); $bmp.Dispose();
            `.replace(/\n\s+/g, ' ');

            const { spawn } = require('child_process');
            const proc = spawn('powershell.exe', [
                '-NoProfile', '-NonInteractive',
                '-ExecutionPolicy', 'Bypass',
                '-WindowStyle', 'Hidden',
                '-Command', ps,
            ], { windowsHide: true });

            let stderr = '';
            proc.stderr.on('data', (d) => { stderr += d.toString(); });
            proc.on('error', reject);
            proc.on('close', (code) => {
                if (code === 0 && fs.existsSync(filePath)) resolve();
                else reject(new Error(`PowerShell capture exit ${code}: ${stderr.trim().slice(0, 200)}`));
            });
        });
    }

    /* ─── Heartbeat ─── */
    async _heartbeat() {
        const cfg = this.cfgRef.store;
        if (!cfg.deviceUuid) return;
        try {
            await apiClient.post(cfg, '/activity-tracker/device/heartbeat', { device_uuid: cfg.deviceUuid });
            this.apiReachable = true;
            this.lastSyncAt   = new Date().toISOString();
            this.emit('status');
        } catch (e) {
            this.apiReachable = false;
            this.emit('status');
        }
    }

    /* ─── App usage span queue ─── */
    async _queueAppUsage(winKey, startedAt, endedAt) {
        const cfg = this.cfgRef.store;
        const [appName, windowTitle] = winKey.split('');
        const dur = Math.max(1, Math.round((endedAt - startedAt) / 1000));
        const row = {
            device_uuid:      cfg.deviceUuid,
            app_name:         appName,
            window_title:     windowTitle,
            duration_seconds: dur,
            started_at:       startedAt.toISOString(),
            ended_at:         endedAt.toISOString(),
        };
        try {
            await apiClient.post(cfg, '/activity-tracker/app-usage/store', row);
        } catch (e) {
            await queue.enqueue('app-usage', row);
        }
    }

    /* ─── Offline queue flush ─── */
    async _flushQueue() {
        const cfg = this.cfgRef.store;
        if (!queue.size()) return;
        if (!cfg.apiUrl || !cfg.token) return;

        // Drain max 50 items per cycle to avoid stampedes
        const batch = await queue.peek(50);
        for (const item of batch) {
            try {
                if (item.kind === 'activity') {
                    await apiClient.post(cfg, '/activity-tracker/activity/store', item.payload);
                } else if (item.kind === 'app-usage') {
                    await apiClient.post(cfg, '/activity-tracker/app-usage/store', item.payload);
                } else if (item.kind === 'screenshot') {
                    const fp = item.payload._filePath;
                    if (!fp || !fs.existsSync(fp)) { await queue.delete(item.id); continue; }
                    const meta = { ...item.payload }; delete meta._filePath;
                    await apiClient.uploadFile(cfg, '/activity-tracker/screenshot/upload', fp, 'image', meta);
                    fs.unlink(fp, () => {});
                }
                await queue.delete(item.id);
            } catch (e) {
                // Stop early — likely still offline; keep queue for next cycle
                break;
            }
        }
        if (queue.size() < batch.length) {
            this.lastSyncAt = new Date().toISOString();
            this.pushEvent(`Queue flushed — ${queue.size()} remaining`);
        }
    }
}

module.exports = new Tracker();
