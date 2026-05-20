"""Detects the active foreground window, its owning app, and (best-effort) URL.

URL detection uses Windows UI Automation when available, which can read the
address bar of Chromium browsers without injecting into the process. If
`uiautomation` is missing or fails, we fall back to the bare window title.
"""
import os
import re
from typing import Dict, Optional

from logger import get_logger

log = get_logger("window")

try:
    import win32gui
    import win32process
    _HAS_WIN32 = True
except ImportError:
    _HAS_WIN32 = False
    log.warning("pywin32 unavailable — window tracking disabled")

try:
    import psutil
    _HAS_PSUTIL = True
except ImportError:
    _HAS_PSUTIL = False

try:
    import uiautomation as uia
    _HAS_UIA = True
except Exception:
    _HAS_UIA = False

_BROWSERS = {"chrome.exe", "msedge.exe", "brave.exe", "opera.exe", "vivaldi.exe", "firefox.exe"}


def _process_for_hwnd(hwnd: int) -> Optional[str]:
    if not (_HAS_WIN32 and _HAS_PSUTIL):
        return None
    try:
        _, pid = win32process.GetWindowThreadProcessId(hwnd)
        return os.path.basename(psutil.Process(pid).exe()).lower()
    except Exception:
        return None


def _extract_url_from_browser(hwnd: int) -> Optional[str]:
    """Best-effort URL grab via UI Automation. Returns None on any failure."""
    if not _HAS_UIA:
        return None
    try:
        ctrl = uia.ControlFromHandle(hwnd)
        if ctrl is None:
            return None
        edit = ctrl.EditControl(searchDepth=20)
        if edit.Exists(0.2, 0.05):
            value = edit.GetValuePattern().Value
            if value:
                value = value.strip()
                if not re.match(r"^https?://", value):
                    value = "https://" + value
                return value[:500]
    except Exception as exc:
        log.debug("UIA url extract failed: %s", exc)
    return None


def get_active_window() -> Dict[str, Optional[str]]:
    """Return {title, app, url} for the foreground window.

    `url` is populated only when the foreground app is a known browser AND
    UI Automation succeeded; otherwise it is None.
    """
    if not _HAS_WIN32:
        return {"title": None, "app": None, "url": None}

    try:
        hwnd = win32gui.GetForegroundWindow()
        title = win32gui.GetWindowText(hwnd) or None
    except Exception as exc:
        log.error("foreground window lookup failed: %s", exc)
        return {"title": None, "app": None, "url": None}

    app = _process_for_hwnd(hwnd)
    url = _extract_url_from_browser(hwnd) if app in _BROWSERS else None

    return {
        "title": (title[:500] if title else None),
        "app":   (app[:200] if app else None),
        "url":   url,
    }
