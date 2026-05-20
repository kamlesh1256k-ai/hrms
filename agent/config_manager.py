"""Loads and persists config.json. Optionally encrypts the bearer token at rest.

Token storage strategy:
  - On first save we generate a machine-bound key (stored next to the config
    as `.agent.key`) and encrypt the token with Fernet.
  - The plaintext token is never written to config.json. Instead we store
    `token_enc`, the base64 ciphertext.
  - If `cryptography` is unavailable we fall back to plaintext (with a warning
    in the log) so the agent still runs in dev environments.
"""
import json
import os
from typing import Any, Dict

from logger import get_logger

log = get_logger("config")

_HERE = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(_HERE, "config.json")
KEY_PATH = os.path.join(_HERE, ".agent.key")

try:
    from cryptography.fernet import Fernet
    _HAS_CRYPTO = True
except ImportError:
    _HAS_CRYPTO = False
    log.warning("cryptography not installed — token will be stored in plaintext.")


def _load_or_create_key() -> bytes:
    if os.path.exists(KEY_PATH):
        with open(KEY_PATH, "rb") as f:
            return f.read()
    key = Fernet.generate_key()
    with open(KEY_PATH, "wb") as f:
        f.write(key)
    try:
        os.chmod(KEY_PATH, 0o600)
    except OSError:
        pass
    return key


def _encrypt(value: str) -> str:
    if not _HAS_CRYPTO or not value:
        return value
    return Fernet(_load_or_create_key()).encrypt(value.encode()).decode()


def _decrypt(value: str) -> str:
    if not _HAS_CRYPTO or not value:
        return value
    try:
        return Fernet(_load_or_create_key()).decrypt(value.encode()).decode()
    except Exception as exc:
        log.error("token decrypt failed: %s", exc)
        return ""


def load() -> Dict[str, Any]:
    if not os.path.exists(CONFIG_PATH):
        raise FileNotFoundError(f"config.json missing at {CONFIG_PATH}")
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        cfg = json.load(f)

    if cfg.get("token_enc"):
        cfg["token"] = _decrypt(cfg["token_enc"])
    cfg.setdefault("api_url", "http://localhost/hrms/api")
    cfg.setdefault("interval", 300)
    cfg.setdefault("idle_threshold", 60)
    cfg.setdefault("screenshot_quality", 60)
    cfg.setdefault("screenshot_max_width", 1600)
    cfg.setdefault("verify_ssl", True)
    return cfg


def save_token(token: str) -> None:
    """Persist a freshly issued token without rewriting the rest of the file."""
    cfg: Dict[str, Any] = {}
    if os.path.exists(CONFIG_PATH):
        with open(CONFIG_PATH, "r", encoding="utf-8") as f:
            cfg = json.load(f)

    if _HAS_CRYPTO:
        cfg["token_enc"] = _encrypt(token)
        cfg.pop("token", None)
    else:
        cfg["token"] = token

    with open(CONFIG_PATH, "w", encoding="utf-8") as f:
        json.dump(cfg, f, indent=4)


def save(cfg: Dict[str, Any]) -> None:
    """Write the full config (used by login flow / first-run wizard)."""
    out = dict(cfg)
    token = out.pop("token", "")
    if token:
        if _HAS_CRYPTO:
            out["token_enc"] = _encrypt(token)
        else:
            out["token"] = token
    with open(CONFIG_PATH, "w", encoding="utf-8") as f:
        json.dump(out, f, indent=4)
