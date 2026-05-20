"""Adds/removes the agent from Windows startup (HKCU registry Run key).

Idempotent: install_autostart() can be called every launch — it only writes
when the existing value differs.
"""
import os
import sys
from typing import Optional

from logger import get_logger

log = get_logger("autostart")

_RUN_KEY = r"Software\Microsoft\Windows\CurrentVersion\Run"
_VALUE_NAME = "HRMSDesktopAgent"


def _winreg():
    if sys.platform != "win32":
        return None
    try:
        import winreg  # noqa: WPS433
        return winreg
    except ImportError:
        return None


def _agent_command() -> str:
    """Command that should run on login. When frozen we point at the .exe;
    otherwise we point at pythonw.exe + main.py so no console flashes up."""
    if getattr(sys, "frozen", False):
        return f'"{sys.executable}"'

    here = os.path.dirname(os.path.abspath(__file__))
    main_py = os.path.join(here, "main.py")
    pythonw = sys.executable.replace("python.exe", "pythonw.exe")
    if not os.path.exists(pythonw):
        pythonw = sys.executable
    return f'"{pythonw}" "{main_py}"'


def install_autostart() -> bool:
    winreg = _winreg()
    if winreg is None:
        return False
    cmd = _agent_command()
    try:
        with winreg.OpenKey(winreg.HKEY_CURRENT_USER, _RUN_KEY, 0, winreg.KEY_READ | winreg.KEY_WRITE) as key:
            existing: Optional[str] = None
            try:
                existing, _ = winreg.QueryValueEx(key, _VALUE_NAME)
            except FileNotFoundError:
                pass
            if existing != cmd:
                winreg.SetValueEx(key, _VALUE_NAME, 0, winreg.REG_SZ, cmd)
                log.info("autostart entry installed: %s", cmd)
            return True
    except OSError as exc:
        log.error("autostart install failed: %s", exc)
        return False


def remove_autostart() -> bool:
    winreg = _winreg()
    if winreg is None:
        return False
    try:
        with winreg.OpenKey(winreg.HKEY_CURRENT_USER, _RUN_KEY, 0, winreg.KEY_WRITE) as key:
            winreg.DeleteValue(key, _VALUE_NAME)
        log.info("autostart entry removed")
        return True
    except FileNotFoundError:
        return True
    except OSError as exc:
        log.error("autostart remove failed: %s", exc)
        return False
