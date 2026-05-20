# HRMS Activity Tracker — Soniya ke liye Setup Guide

## 📋 Aapko ye 3 cheezein chahiye:

1. **Installer file**: `HRMS Activity Tracker Setup 1.0.0.exe` (76 MB)
2. **API URL**: `http://192.168.31.22/hrms/api`
3. **Aapka Token**: `5|XXXXXXXXXXXXXXXXX...` (jo aapne HRMS pe generate kiya)

---

## 🚀 Steps

### Step 1: Installer install karo

1. `HRMS Activity Tracker Setup 1.0.0.exe` pe **double-click**
2. Setup wizard kholega:
   - "Choose Install Location" — default theek hai → **Next**
   - **Install** click
   - Wait 30 second
   - **Finish** click
3. Desktop pe **HRMS Activity Tracker** shortcut banega

### Step 2: Agent launch karo

- Desktop icon pe **double-click**
- Purple gradient window khulegi titled **"HRMS Activity Tracker"**

### Step 3: Settings bharo

Window me 3 input fields:

| Field | Kya bharna hai |
|-------|----------------|
| **API URL** | `http://192.168.31.22/hrms/api` |
| **Sanctum Token** | Aapka token paste karo |
| **Device Name** | `Soniya Laptop` (ya kuch bhi) |

Defaults rehne do:
- Screenshot Interval: 5 (minutes)
- Activity Interval: 30 (seconds)

### Step 4: Buttons in order

1. **Save Settings** click → log strip me green text "Settings saved" aana chahiye
2. **Register / Re-register Device** click → log me "Device registered → id 2" jaise kuch
3. Yellow consent banner padho — **"I understand and consent"** click
4. **▶ Start Tracking** button click

### Step 5: Verify on agent

Status panel me dikhna chahiye:
- ✅ **Status: Tracking** (green)
- ✅ **API: Connected** (green)
- ✅ **Device ID: 2** (number kuch bhi ho)
- ✅ **Last Sync: just now**
- ✅ **Active App: chrome.exe** (jo aap use kar rahe ho)

---

## ❗ Troubleshooting

| Problem | Fix |
|---------|-----|
| **"API: Unreachable"** | Same Wi-Fi pe ho aap aur server? Browser me try: http://192.168.31.22/hrms/dashboard — agar khulta hai, system reachable hai |
| **"Registration failed: 401"** | Token galat copy hua. HRMS pe naya generate karo |
| **"Cannot read properties..."** error | Windows env me `ELECTRON_RUN_AS_NODE` set hai — admin se delete karwao |
| Window khulta nahi | Antivirus block kar raha ho — installer ko whitelist karo |

---

## 🔒 What Gets Tracked

Agent ke "I consent" button click karne pe yeh data HRMS server ko jata hai:

- **Active app name** (jaise "chrome.exe", "notepad.exe")
- **Active window title** (jaise "GitHub - Chrome")
- **Idle time** (kitne second se mouse/keyboard nahi chala)
- **Keyboard event count** (sirf count, actual keys NAHI)
- **Mouse event count** (sirf count, position NAHI)
- **Screenshots** har 5 minute me (full screen)

---

## 🛑 Tracking band karna

- Agent window me **■ Stop Tracking** button click
- Ya tray icon (right-bottom me clock ke paas) right-click → **Stop Tracking**
- Ya tray icon → **Quit** (poora app band)

Aapka data ruk jayega bhej na, but pehle ka data server pe rahega.
