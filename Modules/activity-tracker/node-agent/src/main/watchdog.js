/**
 * Watchdog — keeps the HRMS Activity Tracker agent alive.
 *
 * The main process spawns this as a detached child. The watchdog then polls
 * for the parent's electron.exe process; if it disappears (Task Manager kill,
 * crash, etc.), the watchdog re-launches it. The watchdog itself is a tiny
 * Node script with no UI, so it has minimal attack surface.
 *
 * Failure modes:
 *  - Admin user kills BOTH watchdog + agent at the same moment → tracking
 *    stops. The Windows boot auto-start picks it up next reboot. This is the
 *    best you can do without running as a service (which requires admin).
 */
const { spawn } = require('child_process');
const fs        = require('fs');
const path      = require('path');

const electronExe = process.argv[2];   // path to electron.exe
const appDir      = process.argv[3];   // app directory containing package.json
const logPath     = process.argv[4];   // where to write watchdog activity

if (!electronExe || !appDir) {
    process.exit(1);
}

function log(msg) {
    try { fs.appendFileSync(logPath, `[${new Date().toISOString()}] [watchdog] ${msg}\n`); } catch (e) {}
}

log('Watchdog started — pid ' + process.pid);

let respawnCount = 0;
const MAX_RESPAWNS_PER_MINUTE = 6;
let respawnsInWindow = [];

function spawnAgent() {
    const now = Date.now();
    respawnsInWindow = respawnsInWindow.filter(t => now - t < 60000);
    if (respawnsInWindow.length >= MAX_RESPAWNS_PER_MINUTE) {
        log(`Respawn rate exceeded (${MAX_RESPAWNS_PER_MINUTE}/min) — backing off 60s`);
        setTimeout(spawnAgent, 60000);
        return;
    }
    respawnsInWindow.push(now);
    respawnCount++;
    log(`Spawning agent (respawn #${respawnCount})`);

    const child = spawn(electronExe, [appDir, '--watchdog-respawn'], {
        detached: true,
        stdio: 'ignore',
        windowsHide: true,
        env: { ...process.env, ELECTRON_RUN_AS_NODE: undefined },
    });
    child.unref();
}

/**
 * Poll for the agent: list electron processes whose command-line includes
 * our app directory. If none found, respawn.
 */
function isAgentRunning(callback) {
    const wmic = spawn('wmic.exe', ['process', 'where',
        `name='electron.exe'`, 'get', 'CommandLine,ProcessId', '/format:csv'], { windowsHide: true });

    let stdout = '';
    wmic.stdout.on('data', (d) => { stdout += d.toString(); });
    wmic.on('close', () => {
        const found = stdout.split('\n').some(line =>
            line.toLowerCase().includes(appDir.toLowerCase())
        );
        callback(found);
    });
    wmic.on('error', () => callback(false));   // assume not running on error
}

function checkLoop() {
    isAgentRunning((running) => {
        if (!running) {
            log('Agent not detected — respawning');
            spawnAgent();
        }
        setTimeout(checkLoop, 15000);  // check every 15s
    });
}

// Allow the parent agent some grace time to fully start before we begin polling
setTimeout(checkLoop, 10000);
