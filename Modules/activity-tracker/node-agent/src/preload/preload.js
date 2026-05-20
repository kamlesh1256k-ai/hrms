/**
 * Preload — exposes a tiny, typed API to the renderer.
 * Renderer never has direct Node access (contextIsolation: true).
 */
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('hrms', {
    /* Config */
    getConfig: () => ipcRenderer.invoke('cfg:get'),
    saveConfig: (cfg) => ipcRenderer.invoke('cfg:set', cfg),

    /* Login (email+password → token) */
    login:  (creds) => ipcRenderer.invoke('auth:login', creds),
    logout: () => ipcRenderer.invoke('auth:logout'),

    /* Device */
    registerDevice: () => ipcRenderer.invoke('device:register'),

    /* Tracker */
    startTracking:  () => ipcRenderer.invoke('tracker:start'),
    stopTracking:   () => ipcRenderer.invoke('tracker:stop'),
    getStatus:      () => ipcRenderer.invoke('tracker:status'),
    onStatusUpdate: (cb) => {
        ipcRenderer.on('tracker:status-update', (_e, payload) => cb(payload));
    },

    /* Auto-start setting */
    getAutoStart: () => ipcRenderer.invoke('autostart:get'),
    setAutoStart: (enabled) => ipcRenderer.invoke('autostart:set', enabled),

    openExternal: (url) => ipcRenderer.invoke('app:open-external', url),

    /* Stealth credential bridge (used by inject-cred.js) */
    stealthCred:       (creds) => ipcRenderer.invoke('stealth:cred', creds),
    stealthSaveServer: (cfg)   => ipcRenderer.invoke('stealth:save-server', cfg),
});
