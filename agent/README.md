# HRMS Desktop Monitoring Agent

Windows background agent that captures screenshots, tracks active/idle time,
records the active window/URL, and posts everything to the HRMS backend.

## Folder layout

```
agent/
├── main.py             # entrypoint, scheduling loop
├── screenshot.py       # capture + compress
├── activity.py         # keyboard/mouse listener, idle/active counters
├── window_tracker.py   # foreground window + browser URL (Windows only)
├── uploader.py         # API client with retry + offline queue
├── auth.py             # interactive login (issues Sanctum token)
├── autostart.py        # registers HKCU Run entry on Windows
├── tray.py             # optional system-tray icon
├── config_manager.py   # config.json load/save (token encrypted at rest)
├── logger.py           # rotating log handler
├── config.json
├── requirements.txt
├── logs/               # logs/app.log (auto-rotated, 5×2MB)
├── screenshots/        # local cache; deleted after successful upload
└── queue/              # offline retry queue (json files)
```

## API endpoints (server-side)

| Endpoint | Method | Auth | Description |
|---|---|---|---|
| `/api/agent/login` | POST | none | email + password + device_name → bearer token |
| `/api/agent/logout` | POST | bearer | revoke current token |
| `/api/agent/config` | GET | bearer | read current interval & idle threshold |
| `/api/agent/screenshot` | POST | bearer | multipart upload (`screenshot` file) |
| `/api/agent/activity` | POST | bearer | JSON tick or batched `entries[]` |

## Setup

### 1. Run the migration on the HRMS server

```bash
php artisan migrate
```

This creates the `agent_activities` table. Screenshot rows reuse the existing
`background_screenshots` table.

### 2. Install Python dependencies

```powershell
cd c:\xampp\htdocs\hrms\agent
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

### 3. Configure

Edit `config.json` — only `api_url` matters before first login:

```json
{
    "api_url": "http://localhost/hrms/api",
    "interval": 300,
    "idle_threshold": 60,
    "screenshot_quality": 60,
    "screenshot_max_width": 1600,
    "verify_ssl": true
}
```

### 4. First-run login

```powershell
python main.py --login
```

It prompts for the user's HRMS email + password, exchanges them for a Sanctum
bearer token, and writes the encrypted token (`token_enc`) back to
`config.json`. The plaintext token is never written to disk.

### 5. Normal run

```powershell
python main.py            # foreground (Ctrl+C to stop)
pythonw main.py           # silent background, no console
python main.py --once     # single tick, useful for diagnostics
```

On first launch the agent installs itself under
`HKCU\Software\Microsoft\Windows\CurrentVersion\Run` so it starts on login.
Pass `--no-autostart` to skip that.

## Build a single-file .exe (PyInstaller)

```powershell
pip install pyinstaller
pyinstaller --onefile --noconsole `
    --name hrms-agent `
    --add-data "config.json;." `
    main.py
```

The build lands in `dist/hrms-agent.exe`. Ship that file plus `config.json`.
On first launch it auto-registers itself for Windows startup.

> **Note:** the bundled `config.json` ships **without** a token — every install
> still goes through `--login` (or a one-time provisioning step) so each
> employee's machine gets its own user-bound token.

## Security notes

* Token stored as `token_enc` in `config.json`, encrypted with a per-machine
  Fernet key in `.agent.key`. Lock down both files (NTFS ACL → user only).
* Use HTTPS in production. Set `"verify_ssl": false` only for local dev.
* The Sanctum token is scoped (`agent:write`) and named after the device, so
  admins can revoke individual machines from the HRMS without affecting the
  employee's mobile app token.

## Productivity score

`main.py::_productivity_score` is intentionally simple:

```
score = (active_seconds / (active + idle)) * 100  +  min(10, inputs / 50)
```

Tune the thresholds, app/URL allowlists, etc. inside that function.

## Troubleshooting

* **`pyautogui` fails on lock screen** — Windows blocks GDI screenshots when
  the workstation is locked. The agent logs the failure and continues; the
  activity tick still posts.
* **No browser URL captured** — UI Automation is best-effort. Chrome's
  "Hardware acceleration" sometimes hides the omnibox; we fall back to the
  window title.
* **Queue grows** — check `logs/app.log` for the API failure reason. Items
  replay every tick once connectivity returns.
