/**
 * Standalone tracker — runs without Electron.
 * Logs in with email + password, gets token automatically.
 * Run: node run-tracker.js
 */
const axios  = require('axios');
const os     = require('os');
const fs     = require('fs');
const path   = require('path');
const { execSync } = require('child_process');
const FormData = require('form-data');

const configPath = path.join(os.homedir(), 'AppData', 'Roaming', 'hrms-desktop', 'hrms-tracker-config.json');
const raw = fs.readFileSync(configPath, 'utf8').replace(/^﻿/, '');
const cfg = JSON.parse(raw);

const API         = cfg.apiUrl;
const DEVICE_NAME = cfg.deviceName || os.hostname();
const DEVICE_UUID = require('crypto').createHash('md5').update(os.hostname() + os.platform()).digest('hex');

let TOKEN    = null;
let deviceId = null;
let stopApproved = false;

function getHeaders() {
    return {
        'Authorization': `Bearer ${TOKEN}`,
        'Accept':        'application/json',
        'Content-Type':  'application/json',
    };
}

// Login with email + password to get token
async function login() {
    const email    = cfg.userEmail;
    const password = cfg.password;

    if (!email || !password) {
        console.error('[Login] No email/password in config. Add "userEmail" and "password" to hrms-tracker-config.json');
        process.exit(1);
    }

    try {
        const res = await axios.post(`${API}/activity-tracker/login`, { email, password }, {
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            timeout: 10000,
        });
        TOKEN = res.data.token;
        console.log('[Login] Success! User:', res.data.user.name, '(' + res.data.user.email + ')');
        return true;
    } catch (e) {
        console.error('[Login] Failed:', e.response?.data || e.message);
        return false;
    }
}

// Get currently active window title using PowerShell
function getActiveWindow() {
    try {
        const title = execSync(
            `powershell -NoProfile -NonInteractive -Command "$p = Get-Process | Where-Object {$_.MainWindowTitle -ne ''} | Sort-Object CPU -Descending | Select-Object -First 1; if ($p) { $p.MainWindowTitle } else { 'Windows Desktop' }"`,
            { timeout: 3000, encoding: 'utf8' }
        ).trim();
        return title || 'Windows Desktop';
    } catch {
        return 'Windows Desktop';
    }
}

// Get foreground app name using PowerShell
function getForegroundApp() {
    try {
        const app = execSync(
            `powershell -NoProfile -NonInteractive -Command "$p = Get-Process | Where-Object {$_.MainWindowTitle -ne ''} | Sort-Object CPU -Descending | Select-Object -First 1; if ($p) { $p.ProcessName } else { 'Windows' }"`,
            { timeout: 3000, encoding: 'utf8' }
        ).trim();
        return app || 'Windows';
    } catch {
        return 'Windows';
    }
}

async function registerDevice() {
    try {
        const res = await axios.post(`${API}/activity-tracker/device/register`, {
            device_uuid: DEVICE_UUID,
            device_name: DEVICE_NAME,
            os: `${os.platform()} ${os.release()}`,
        }, { headers: getHeaders(), timeout: 10000 });
        console.log('[Device] Registered:', res.data.device_id);
        return res.data.device_id;
    } catch (e) {
        console.error('[Device] Register failed:', e.response?.data || e.message);
        return null;
    }
}

async function heartbeat() {
    try {
        await axios.post(`${API}/activity-tracker/device/heartbeat`, {
            device_id:   deviceId,
            device_uuid: DEVICE_UUID,
        }, { headers: getHeaders(), timeout: 8000 });
        console.log('[Heartbeat]', new Date().toLocaleTimeString(), '— online');
    } catch (e) {
        console.error('[Heartbeat] Failed:', e.response?.status, e.response?.data || e.message);
    }
}

async function storeActivity() {
    try {
        const now         = new Date().toISOString();
        const activeApp   = getForegroundApp();
        const windowTitle = getActiveWindow();

        await axios.post(`${API}/activity-tracker/activity/store`, {
            device_id:           deviceId,
            device_uuid:         DEVICE_UUID,
            captured_at:         now,
            idle_seconds:        0,
            active_seconds:      30,
            keyboard_count:      0,
            mouse_count:         0,
            active_app:          activeApp,
            active_window_title: windowTitle,
        }, { headers: getHeaders(), timeout: 8000 });
        console.log('[Activity] Stored:', new Date().toLocaleTimeString(), '|', activeApp, '|', windowTitle.substring(0, 40));
    } catch (e) {
        console.error('[Activity] Failed:', e.response?.status, e.response?.data || e.message);
    }
}

function takeScreenshot() {
    const tmpFile = path.join(os.tmpdir(), `shot_${Date.now()}.jpg`);
    const escapedPath = tmpFile.replace(/\\/g, '\\\\');
    const psScript = [
        "Add-Type -AssemblyName System.Windows.Forms,System.Drawing",
        "$s = [System.Windows.Forms.SystemInformation]::VirtualScreen",
        "$bmp = New-Object System.Drawing.Bitmap($s.Width, $s.Height)",
        "$g = [System.Drawing.Graphics]::FromImage($bmp)",
        "$g.CopyFromScreen($s.Left, $s.Top, 0, 0, $bmp.Size)",
        `$bmp.Save('${escapedPath}', [System.Drawing.Imaging.ImageFormat]::Jpeg)`,
        "$g.Dispose()",
        "$bmp.Dispose()",
    ].join('; ');

    try {
        execSync(`powershell -NoProfile -NonInteractive -Command "${psScript}"`, { timeout: 15000 });
        return tmpFile;
    } catch (e) {
        console.error('[Screenshot] Capture failed:', e.message.substring(0, 200));
        return null;
    }
}

async function uploadScreenshot() {
    const shotPath = takeScreenshot();
    if (!shotPath || !fs.existsSync(shotPath)) {
        console.error('[Screenshot] File not found after capture');
        return;
    }

    const activeApp   = getForegroundApp();
    const windowTitle = getActiveWindow();

    try {
        const fd = new FormData();
        fd.append('device_id',           String(deviceId));
        fd.append('device_uuid',         DEVICE_UUID);
        fd.append('captured_at',         new Date().toISOString());
        fd.append('active_app',          activeApp);
        fd.append('active_window_title', windowTitle);
        fd.append('image', fs.createReadStream(shotPath), {
            filename:    'screenshot.jpg',
            contentType: 'image/jpeg',
        });

        const res = await axios.post(`${API}/activity-tracker/screenshot/upload`, fd, {
            headers: {
                ...fd.getHeaders(),
                'Authorization': `Bearer ${TOKEN}`,
                'Accept':        'application/json',
            },
            timeout: 30000,
            maxBodyLength:   50 * 1024 * 1024,
            maxContentLength: 50 * 1024 * 1024,
        });
        console.log('[Screenshot] Uploaded:', new Date().toLocaleTimeString(), '| ID:', res.data.screenshot_id);
    } catch (e) {
        console.error('[Screenshot] Upload failed:', e.response?.status, JSON.stringify(e.response?.data) || e.message);
    } finally {
        try { fs.unlinkSync(shotPath); } catch (_) {}
    }
}

async function notifyTrackerStopped(reason) {
    try {
        await axios.post(`${API}/activity-tracker/stop-request`, {
            device_id:   deviceId,
            device_uuid: DEVICE_UUID,
            reason:      reason || 'Tracker was stopped',
        }, { headers: getHeaders(), timeout: 10000 });
    } catch (_) {}
}

async function requestStop(reason) {
    try {
        await axios.post(`${API}/activity-tracker/stop-request`, {
            device_id:   deviceId,
            device_uuid: DEVICE_UUID,
            reason:      reason || 'Employee requested stop',
        }, { headers: getHeaders(), timeout: 10000 });
        console.log('[StopRequest] Sent to admin. Tracking continues until approved.');
        return true;
    } catch (e) {
        console.error('[StopRequest] Failed to send:', e.response?.data || e.message);
        return false;
    }
}

async function checkStopApproval() {
    try {
        const res = await axios.get(`${API}/activity-tracker/stop-request/status`, {
            params:  { device_id: deviceId, device_uuid: DEVICE_UUID },
            headers: getHeaders(),
            timeout: 8000,
        });
        if (res.data.status === 'approved') {
            stopApproved = true;
            console.log('[StopRequest] Admin approved. Stopping tracker...');
            process.exit(0);
        }
    } catch (e) {
        // Silent — will retry next interval
    }
}

// Intercept Ctrl+C and window close — send stop request instead of quitting
function setupStopInterception() {
    process.on('SIGINT',  () => handleStopAttempt('SIGINT'));
    process.on('SIGTERM', () => handleStopAttempt('SIGTERM'));
    process.on('SIGBREAK', () => handleStopAttempt('SIGBREAK'));
}

async function handleStopAttempt(signal) {
    console.log(`\n[StopRequest] Stop attempt detected (${signal}).`);
    console.log('[StopRequest] Sending stop request to admin for approval...');
    await requestStop('Employee pressed Ctrl+C / tried to close tracker');
    console.log('[StopRequest] Tracking continues. Admin must approve before tracker stops.');
    // Do NOT exit — keep running
}

// Called by auto-restart wrapper on startup to notify admin tracker was restarted
async function notifyRestart() {
    try {
        // Small delay to ensure login completes first
        await new Promise(r => setTimeout(r, 3000));
        await axios.post(`${API}/activity-tracker/stop-request`, {
            device_id:   deviceId,
            device_uuid: DEVICE_UUID,
            reason:      'WARNING: Tracker was forcefully closed and auto-restarted',
        }, { headers: getHeaders(), timeout: 10000 });
        console.log('[AutoRestart] Admin notified that tracker was restarted.');
    } catch (_) {}
}

async function main() {
    console.log('=== Miraix Activity Tracker (Standalone) ===');
    console.log('API:', API);
    console.log('Device:', DEVICE_NAME);
    console.log('');

    // Step 1: Login with email + password
    const loggedIn = await login();
    if (!loggedIn) {
        console.error('Login failed. Check email and password in config.');
        process.exit(1);
    }

    // Step 2: Register device
    deviceId = await registerDevice();
    if (!deviceId) {
        console.error('Cannot register device.');
        process.exit(1);
    }

    // Intercept Ctrl+C — send stop request to admin instead of quitting
    setupStopInterception();

    // Heartbeat every 30 seconds
    await heartbeat();
    setInterval(heartbeat, 30 * 1000);

    // Activity every 30 seconds
    await storeActivity();
    setInterval(storeActivity, 30 * 1000);

    // Screenshot every 5 seconds
    await uploadScreenshot();
    setInterval(uploadScreenshot, 5 * 1000);

    // Poll for admin stop-approval every 30 seconds
    setInterval(checkStopApproval, 30 * 1000);

    // If auto-restarted by wrapper, notify admin
    if (process.env.AT_AUTO_RESTARTED === '1') {
        notifyRestart();
    }

    console.log('\nTracker running... (heartbeat: 30s | activity: 30s | screenshot: 5s)\n');
    console.log('NOTE: Press Ctrl+C to request stop — admin approval required to actually stop.\n');
}

main().catch(console.error);
