"""HRMS Desktop Monitoring Agent — entrypoint.

Run:
    python main.py             # foreground (logs to console + logs/app.log)
    pythonw main.py            # silent background

CLI flags:
    --login        force re-login (overwrites stored token)
    --once         run a single tick then exit (useful for testing)
    --no-autostart skip Windows startup registration
"""
import argparse
import os
import signal
import socket
import sys
import threading
import time
from datetime import datetime, timezone

import auth
import config_manager
from activity import ActivityTracker
from autostart import install_autostart
from logger import get_logger
from screenshot import capture_screenshot
from tray import Tray
from uploader import Uploader
from window_tracker import get_active_window

log = get_logger("main")

_stop_event = threading.Event()


def _productivity_score(activity: dict, idle_threshold: int) -> int:
    """Crude 0–100 score: ratio of active to total wall time, plus a small
    bump for sustained input. Tune to taste."""
    active = activity.get("active_seconds", 0)
    idle = activity.get("idle_seconds", 0)
    total = active + idle
    if total <= 0:
        return 0
    base = (active / total) * 100.0
    inputs = activity.get("keystrokes", 0) + activity.get("mouse_events", 0)
    bonus = min(10.0, inputs / 50.0)
    return int(max(0, min(100, round(base + bonus))))


def _tick(uploader: Uploader, tracker: ActivityTracker, cfg: dict) -> None:
    win = get_active_window()
    activity = tracker.snapshot_and_reset()

    # Always replay queue first so retries get capacity before new work.
    uploader.flush_queue()

    payload = {
        **activity,
        "active_window":      win.get("title"),
        "active_app":         win.get("app"),
        "active_url":         win.get("url"),
        "productivity_score": _productivity_score(activity, cfg["idle_threshold"]),
        "hostname":           socket.gethostname(),
        "captured_at":        datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds"),
    }
    uploader.send_activity(payload)

    shot_path = capture_screenshot(
        max_width=int(cfg.get("screenshot_max_width", 1600)),
        quality=int(cfg.get("screenshot_quality", 60)),
    )
    if shot_path:
        ok = uploader.upload_screenshot(
            shot_path,
            active_window=win.get("title"),
            active_url=win.get("url"),
            captured_at=payload["captured_at"],
        )
        if ok and not cfg.get("keep_local_screenshots", False):
            try:
                os.unlink(shot_path)
            except OSError:
                pass


def _shutdown(_signum=None, _frame=None) -> None:
    log.info("shutdown signal received")
    _stop_event.set()


def main() -> int:
    parser = argparse.ArgumentParser(description="HRMS Desktop Monitoring Agent")
    parser.add_argument("--login", action="store_true", help="Force re-login")
    parser.add_argument("--once", action="store_true", help="Run one tick and exit")
    parser.add_argument("--no-autostart", action="store_true", help="Skip Windows autostart registration")
    args = parser.parse_args()

    cfg = config_manager.load()

    if args.login:
        cfg["token"] = ""
    cfg = auth.ensure_token(cfg)

    if not args.no_autostart and sys.platform == "win32":
        install_autostart()

    tracker = ActivityTracker(idle_threshold=int(cfg["idle_threshold"]))
    tracker.start()

    uploader = Uploader(
        api_url=cfg["api_url"],
        token=cfg["token"],
        verify_ssl=bool(cfg.get("verify_ssl", True)),
    )

    tray = Tray(on_quit=_shutdown)
    tray.start()

    signal.signal(signal.SIGINT, _shutdown)
    signal.signal(signal.SIGTERM, _shutdown)

    log.info("agent started — interval=%ss api=%s", cfg["interval"], cfg["api_url"])

    if args.once:
        _tick(uploader, tracker, cfg)
        tracker.stop()
        return 0

    interval = int(cfg["interval"])
    next_tick = time.time()
    while not _stop_event.is_set():
        now = time.time()
        if now >= next_tick:
            try:
                _tick(uploader, tracker, cfg)
            except Exception as exc:
                log.exception("tick failed: %s", exc)
            next_tick = now + interval
        # Wake every second so SIGINT shuts us down quickly.
        _stop_event.wait(1.0)

    log.info("agent stopping...")
    tracker.stop()
    tray.stop()
    return 0


if __name__ == "__main__":
    sys.exit(main())
