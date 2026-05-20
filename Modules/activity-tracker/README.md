# HRMS Activity Tracker

Laptop/Desktop activity-monitoring module for the HRMS — Laravel backend + Electron Node.js desktop agent.

> **Consent-based.** The agent always shows a visible window, system-tray icon, Start/Stop button, and requires explicit consent before tracking begins. This is **not** hidden spyware.

## Folder layout

```
modules/activity-tracker/
├── README.md                       ← this file
├── laravel/                        ← Laravel-side source (mirrors live app)
│   ├── migrations/                 ← 5 migrations (at_devices, at_activity_logs, at_screenshots, at_app_usage_logs, at_daily_summaries)
│   ├── models/                     ← AtDevice, AtActivityLog, AtScreenshot, AtAppUsageLog, AtDailySummary
│   ├── controllers/
│   │   ├── ActivityTrackerController.php           ← admin dashboard (web)
│   │   └── Api/ActivityTrackerApiController.php    ← REST API (Sanctum)
│   ├── routes/
│   │   ├── routes.php              ← web routes (loaded from routes/web.php)
│   │   └── api_routes.php          ← API routes (loaded from routes/api.php)
│   ├── views/                      ← 6 Blade views (overview, user activity, timeline, app usage, daily report, token UI)
│   └── seeders/                    ← ActivityTrackerPermissionsSeeder
└── node-agent/                     ← Electron desktop agent
    ├── package.json
    └── src/
        ├── main/main.js            ← Electron main process (window, tray, IPC)
        ├── preload/preload.js      ← Secure IPC bridge to renderer
        ├── renderer/index.html     ← UI (vanilla JS — no framework)
        ├── capture/index.js        ← Tracker singleton (loops + state)
        ├── sync/api.js             ← axios wrapper for Sanctum API
        └── store/queue.js          ← better-sqlite3 offline queue
```

The Laravel files are **also copied** to the live app paths (`app/Models/`, `app/Http/Controllers/`, `database/migrations/`, etc.). The `modules/activity-tracker/laravel/` folder is the canonical source — re-copy from there if anything diverges.

## Database tables

| Table                    | Purpose                                                   |
| ------------------------ | --------------------------------------------------------- |
| `at_devices`             | Devices registered by the agent (one row per machine)     |
| `at_activity_logs`       | 30-second activity samples (active app, idle, K/M counts) |
| `at_screenshots`         | Screenshot archive metadata (image stored on public disk) |
| `at_app_usage_logs`      | Aggregated app-usage spans (started_at → ended_at)        |
| `at_daily_summaries`     | Pre-aggregated per-user-per-device-per-day rollup         |

## API endpoints (all require Sanctum bearer token)

```
POST  /api/activity-tracker/device/register     { device_uuid, device_name, os }
POST  /api/activity-tracker/device/heartbeat    { device_uuid }
POST  /api/activity-tracker/activity/store      single sample, or { samples: [...] }
POST  /api/activity-tracker/screenshot/upload   multipart: image + metadata
POST  /api/activity-tracker/app-usage/store     single span, or { usages: [...] }
GET   /api/activity-tracker/dashboard/summary   read-side helper
```

## Dashboard URLs

```
/activity-tracker                    Overview (KPIs + recent screenshots + top apps + devices)
/activity-tracker/user-activity      Per-user activity, filterable by date range
/activity-tracker/timeline           Screenshot grid, filterable by user + date
/activity-tracker/app-usage          App-usage report (table with bar chart)
/activity-tracker/daily-report       Daily rollup (with CSV export)
/activity-tracker/token              Generate/revoke Sanctum tokens for the agent
```

## Permissions (Spatie)

| Permission                    | Roles                                  |
| ----------------------------- | -------------------------------------- |
| `manage-activity-tracker`     | super admin · company · hr             |
| `use-activity-tracker`        | super admin · company · hr · employee  |

Already seeded — re-run with: `php artisan db:seed --class=ActivityTrackerPermissionsSeeder`

---

## 🚀 Installation

### 1. Laravel side (already done)

```bash
# Migrations
php artisan migrate --path=database/migrations/2026_05_02_000001_create_at_devices_table.php
php artisan migrate --path=database/migrations/2026_05_02_000002_create_at_activity_logs_table.php
php artisan migrate --path=database/migrations/2026_05_02_000003_create_at_screenshots_table.php
php artisan migrate --path=database/migrations/2026_05_02_000004_create_at_app_usage_logs_table.php
php artisan migrate --path=database/migrations/2026_05_02_000005_create_at_daily_summaries_table.php

# Permissions
php artisan db:seed --class=ActivityTrackerPermissionsSeeder

# Storage symlink (for screenshot serving)
php artisan storage:link
```

### 2. Node.js Electron agent

Requires Node.js 18+ and Windows 10/11.

```bash
cd modules/activity-tracker/node-agent
npm install                          # installs electron, active-win, axios, better-sqlite3, etc.
npm start                            # launches the agent in dev mode
```

To package as a Windows installer:

```bash
npm run build                        # produces dist/HRMS Activity Tracker Setup x.y.z.exe
```

---

## 🧪 Testing flow

1. **Login as company/HR admin** → Sidebar → **Activity Tracker** → **Tokens**
2. Click **Generate Token**, give it a name (e.g. "Office Laptop"), copy the plaintext token shown once
3. **Run the agent**: `npm start` in `modules/activity-tracker/node-agent`
4. In agent UI:
   - **API URL**: `http://localhost/hrms/api`
   - **Token**: paste the token from step 2
   - **Device Name**: any label
   - Click **Save Settings** → **Register Device**
   - Read & accept the consent banner
   - Click **▶ Start Tracking**
5. Agent will:
   - Send activity samples every 30s
   - Capture & upload screenshots every 5 min
   - Send heartbeat every 1 min
6. Back in HRMS dashboard:
   - **Overview** → KPI cards update + recent screenshots grid populates
   - **Screenshot Timeline** → today's date → grid of screenshots
   - **User Activity** → select user + date range → activity samples + app usage bars
   - **Daily Report** → CSV export available

### Offline testing

1. Disconnect the network on the agent machine while tracking is on
2. Activity samples + screenshots queue locally in `userData/tracker-queue.db`
3. Agent UI shows **Pending Queue** count climbing
4. Reconnect — within 30s the queue drains and **Last Sync** updates

---

## 🔒 Security

- **Sanctum bearer tokens** (per-device) — revokable from the dashboard
- **Multipart upload** validated server-side (`mimes:jpg,jpeg,png,webp`, `max:5120` KB)
- **Tenant scoping** — all queries scoped to `created_by` (company), so HR of company A never sees company B's data
- **Idle screenshot path bucketing** — `storage/app/public/screenshots/Y/m/d/<random>.jpg`
- **Visible UI required** — agent has window + tray; `consentAccepted` must be true before any capture loop runs
- **Use HTTPS in production** — token leaks over plain HTTP would let anyone post fake samples for that device

## ⚙ Configuration (agent)

Stored at `%APPDATA%/hrms-activity-tracker-agent/hrms-tracker-config.json`:

```json
{
  "apiUrl":                "https://hrms.example.com/api",
  "token":                 "...",
  "deviceUuid":            "<machine-id>",
  "deviceName":            "Office Laptop",
  "screenshotIntervalMin": 5,
  "activityIntervalSec":   30,
  "heartbeatIntervalMin":  1,
  "consentAccepted":       true
}
```

Settings can be edited from the Electron UI or directly in this file.

## 🐛 Troubleshooting

| Symptom                                     | Likely cause / fix                                                                         |
| ------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Agent: "Registration failed: 401"           | Token invalid or revoked — generate a new one from the dashboard                           |
| Agent: "Cannot start: consent not accepted" | Click "I understand and consent" in the yellow banner                                      |
| Dashboard: screenshots show broken images   | Run `php artisan storage:link`                                                             |
| `active-win` errors on Windows              | First-run permission prompt — allow it (we use `active-win`'s default child-process mode)  |
| `better-sqlite3` build error                | Need a C++ build toolchain on the agent machine — install via `npm i -g windows-build-tools` |
| Queue keeps growing                         | API URL/token wrong, or HRMS server unreachable — check the agent's "API" status pill      |

---

## File counts

- **Laravel:** 5 migrations + 5 models + 2 controllers + 6 views + 2 route files + 1 seeder = **21 files**
- **Node agent:** 1 package.json + 6 JS files + 1 HTML file = **8 files**

Total module source: ~30 files, ~1500 LOC.
