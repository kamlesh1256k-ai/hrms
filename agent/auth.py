"""First-run login. Exchanges email+password for a Sanctum bearer token."""
import getpass
import socket
import sys
from typing import Dict, Tuple

import requests

import config_manager
from logger import get_logger

log = get_logger("auth")


def login_interactive(api_url: str, verify_ssl: bool = True) -> Tuple[bool, Dict]:
    print(f"HRMS agent login — {api_url}")
    email = input("Email: ").strip()
    password = getpass.getpass("Password: ")
    device = socket.gethostname() or "windows-agent"

    try:
        resp = requests.post(
            f"{api_url.rstrip('/')}/agent/login",
            json={"email": email, "password": password, "device_name": device},
            timeout=20,
            verify=verify_ssl,
        )
    except requests.RequestException as exc:
        log.error("login request failed: %s", exc)
        print(f"Network error: {exc}")
        return False, {}

    if resp.status_code != 200:
        msg = ""
        try:
            msg = resp.json().get("message", resp.text)
        except ValueError:
            msg = resp.text
        print(f"Login failed ({resp.status_code}): {msg}")
        return False, {}

    payload = resp.json().get("data", {})
    token = payload.get("token")
    if not token:
        print("Login response missing token.")
        return False, {}

    return True, payload


def ensure_token(cfg: Dict) -> Dict:
    """Returns cfg with a valid token; runs the login wizard if missing."""
    if cfg.get("token"):
        return cfg

    print("No token found in config — starting login...")
    ok, data = login_interactive(cfg["api_url"], cfg.get("verify_ssl", True))
    if not ok:
        sys.exit(1)

    cfg["token"] = data["token"]
    if data.get("interval_seconds"):
        cfg["interval"] = int(data["interval_seconds"])
    if data.get("device_name"):
        cfg["device_name"] = data["device_name"]
    config_manager.save(cfg)
    print("Token saved (encrypted at rest).")
    return cfg
