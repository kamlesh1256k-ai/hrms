"""
Employee Monitor — Standalone Demo Agent (Windows)
---------------------------------------------------
Captures a screenshot every N seconds and logs active-window + idle time.
No server, no DB. Everything saved locally so you can verify it works.

Run:
    python demo_agent.py

Stop: Ctrl+C
"""

import ctypes
import time
import json
import sys
from ctypes import wintypes
from datetime import datetime
from pathlib import Path

import mss
from PIL import Image
import psutil
import win32gui
import win32process

# ---------------- CONFIG ----------------
SCREENSHOT_INTERVAL_SEC = 30          # change to 5 for fast testing
IDLE_THRESHOLD_SEC      = 60          # no input >60s ⇒ idle
MAX_WIDTH               = 1600        # downscale wide displays
WEBP_QUALITY            = 60
OUT_DIR                 = Path(__file__).parent / "captures"
SHOTS_DIR               = OUT_DIR / "screenshots"
LOG_FILE                = OUT_DIR / "activity.log"
# ----------------------------------------


# --- Idle-time detection via Win32 (no listener thread needed) ---
class LASTINPUTINFO(ctypes.Structure):
    _fields_ = [("cbSize", wintypes.UINT), ("dwTime", wintypes.DWORD)]

def seconds_since_last_input() -> int:
    info = LASTINPUTINFO()
    info.cbSize = ctypes.sizeof(LASTINPUTINFO)
    if not ctypes.windll.user32.GetLastInputInfo(ctypes.byref(info)):
        return 0
    millis = ctypes.windll.kernel32.GetTickCount() - info.dwTime
    return millis // 1000


def active_window() -> tuple[str, str]:
    try:
        hwnd = win32gui.GetForegroundWindow()
        _, pid = win32process.GetWindowThreadProcessId(hwnd)
        proc = psutil.Process(pid).name()
        title = win32gui.GetWindowText(hwnd)
        return proc, title
    except Exception:
        return "unknown", ""


def take_screenshot() -> dict:
    SHOTS_DIR.mkdir(parents=True, exist_ok=True)
    with mss.mss() as sct:
        raw = sct.grab(sct.monitors[0])           # all monitors
        img = Image.frombytes("RGB", raw.size, raw.rgb)

    if img.width > MAX_WIDTH:
        ratio = MAX_WIDTH / img.width
        img = img.resize((MAX_WIDTH, int(img.height * ratio)), Image.LANCZOS)

    ts = datetime.now()
    fname = f"{ts.strftime('%Y%m%d_%H%M%S')}.webp"
    fpath = SHOTS_DIR / fname
    img.save(fpath, "WEBP", quality=WEBP_QUALITY, method=6)
    return {
        "file": fname,
        "taken_at": ts.isoformat(timespec="seconds"),
        "width": img.width, "height": img.height,
        "size_kb": round(fpath.stat().st_size / 1024, 1),
    }


def log_line(payload: dict) -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    with LOG_FILE.open("a", encoding="utf-8") as f:
        f.write(json.dumps(payload, ensure_ascii=False) + "\n")


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    print("=" * 60)
    print(" Employee Monitor — Demo Agent")
    print("=" * 60)
    print(f" Screenshots : {SHOTS_DIR}")
    print(f" Activity log: {LOG_FILE}")
    print(f" Interval    : {SCREENSHOT_INTERVAL_SEC}s")
    print(" Press Ctrl+C to stop.")
    print("-" * 60)

    next_shot = 0.0
    next_status = 0.0

    try:
        while True:
            now = time.time()

            # 1) Screenshot every N seconds
            if now >= next_shot:
                meta = take_screenshot()
                meta["event"] = "screenshot"
                log_line(meta)
                print(f"[{meta['taken_at']}] SHOT  {meta['file']}  "
                      f"({meta['width']}x{meta['height']}, {meta['size_kb']} KB)")
                next_shot = now + SCREENSHOT_INTERVAL_SEC

            # 2) Status snapshot every 5s (active window + idle status)
            if now >= next_status:
                idle = seconds_since_last_input()
                state = "IDLE" if idle >= IDLE_THRESHOLD_SEC else "ACTIVE"
                proc, title = active_window()
                payload = {
                    "event": "status",
                    "ts": datetime.now().isoformat(timespec="seconds"),
                    "state": state,
                    "idle_seconds": idle,
                    "app": proc,
                    "title": title[:80],
                }
                log_line(payload)
                print(f"[{payload['ts']}] {state:6s} idle={idle:>4}s  "
                      f"app={proc}  win={title[:50]!r}")
                next_status = now + 5

            time.sleep(0.5)

    except KeyboardInterrupt:
        print("\nStopped. Files saved in:", OUT_DIR)
        sys.exit(0)


if __name__ == "__main__":
    main()
