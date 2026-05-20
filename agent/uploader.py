"""Posts screenshots and activity ticks to the HRMS API with retry + offline queue.

Failed uploads are written as JSON files under ./queue and replayed on the
next tick. Screenshots are queued by storing the source path alongside the
metadata; only after a successful upload do we delete the local image.
"""
import json
import os
import time
import uuid
from datetime import datetime, timezone
from typing import Any, Dict, Optional

import requests

from logger import get_logger

log = get_logger("uploader")

_HERE = os.path.dirname(os.path.abspath(__file__))
QUEUE_DIR = os.path.join(_HERE, "queue")
os.makedirs(QUEUE_DIR, exist_ok=True)


class Uploader:
    def __init__(self, api_url: str, token: str, verify_ssl: bool = True, timeout: int = 30):
        self.api_url = api_url.rstrip("/")
        self.token = token
        self.verify_ssl = bool(verify_ssl)
        self.timeout = int(timeout)

        self.session = requests.Session()
        self.session.headers.update({
            "Authorization": f"Bearer {self.token}",
            "Accept": "application/json",
        })

    # ── public API ───────────────────────────────────────────
    def upload_screenshot(
        self,
        path: str,
        active_window: Optional[str] = None,
        active_url: Optional[str] = None,
        captured_at: Optional[str] = None,
    ) -> bool:
        if not path or not os.path.exists(path):
            return False
        try:
            with open(path, "rb") as f:
                files = {"screenshot": (os.path.basename(path), f, "image/jpeg")}
                data = {
                    "active_window": (active_window or "")[:500],
                    "active_url":    (active_url or "")[:500],
                    "captured_at":   captured_at or _iso_now(),
                }
                resp = self._post("/agent/screenshot", files=files, data=data)
            if resp is not None and resp.status_code in (200, 201):
                return True
            log.warning("screenshot upload non-2xx: %s", resp.status_code if resp else "no-response")
        except requests.RequestException as exc:
            log.warning("screenshot upload failed: %s", exc)

        self._queue_screenshot(path, active_window, active_url, captured_at)
        return False

    def send_activity(self, payload: Dict[str, Any]) -> bool:
        body = dict(payload)
        body.setdefault("captured_at", _iso_now())
        try:
            resp = self._post("/agent/activity", json_body=body)
            if resp is not None and resp.status_code in (200, 201):
                return True
            log.warning("activity post non-2xx: %s", resp.status_code if resp else "no-response")
        except requests.RequestException as exc:
            log.warning("activity post failed: %s", exc)
        self._queue_activity(body)
        return False

    def flush_queue(self) -> None:
        """Replay any queued items. Stops on the first failure to avoid hammering."""
        for name in sorted(os.listdir(QUEUE_DIR)):
            qpath = os.path.join(QUEUE_DIR, name)
            try:
                with open(qpath, "r", encoding="utf-8") as f:
                    item = json.load(f)
            except (OSError, ValueError):
                _safe_unlink(qpath)
                continue

            ok = False
            if item.get("kind") == "screenshot":
                shot = item.get("path")
                if shot and os.path.exists(shot):
                    ok = self._send_screenshot_no_queue(
                        shot,
                        item.get("active_window"),
                        item.get("active_url"),
                        item.get("captured_at"),
                    )
                    if ok:
                        _safe_unlink(shot)
                else:
                    ok = True  # source file gone — drop silently
            elif item.get("kind") == "activity":
                ok = self._send_activity_no_queue(item.get("payload") or {})

            if ok:
                _safe_unlink(qpath)
            else:
                log.info("queue replay paused; will retry next tick")
                return

    # ── internals ────────────────────────────────────────────
    def _post(
        self,
        path: str,
        json_body: Optional[Dict[str, Any]] = None,
        data: Optional[Dict[str, Any]] = None,
        files: Optional[Dict[str, Any]] = None,
        retries: int = 2,
    ) -> Optional[requests.Response]:
        url = f"{self.api_url}{path}"
        last_exc: Optional[Exception] = None
        for attempt in range(retries + 1):
            try:
                return self.session.post(
                    url,
                    json=json_body,
                    data=data,
                    files=files,
                    timeout=self.timeout,
                    verify=self.verify_ssl,
                )
            except requests.RequestException as exc:
                last_exc = exc
                if attempt < retries:
                    time.sleep(1.5 * (attempt + 1))
        if last_exc:
            raise last_exc
        return None

    def _send_screenshot_no_queue(
        self,
        path: str,
        active_window: Optional[str],
        active_url: Optional[str],
        captured_at: Optional[str],
    ) -> bool:
        try:
            with open(path, "rb") as f:
                files = {"screenshot": (os.path.basename(path), f, "image/jpeg")}
                data = {
                    "active_window": (active_window or "")[:500],
                    "active_url":    (active_url or "")[:500],
                    "captured_at":   captured_at or _iso_now(),
                }
                resp = self._post("/agent/screenshot", files=files, data=data)
            return resp is not None and resp.status_code in (200, 201)
        except Exception:
            return False

    def _send_activity_no_queue(self, payload: Dict[str, Any]) -> bool:
        try:
            resp = self._post("/agent/activity", json_body=payload)
            return resp is not None and resp.status_code in (200, 201)
        except Exception:
            return False

    def _queue_screenshot(
        self,
        path: str,
        active_window: Optional[str],
        active_url: Optional[str],
        captured_at: Optional[str],
    ) -> None:
        item = {
            "kind": "screenshot",
            "path": path,
            "active_window": active_window,
            "active_url": active_url,
            "captured_at": captured_at or _iso_now(),
        }
        _write_queue_item(item)

    def _queue_activity(self, payload: Dict[str, Any]) -> None:
        _write_queue_item({"kind": "activity", "payload": payload})


# ── helpers ─────────────────────────────────────────────────
def _iso_now() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")


def _write_queue_item(item: Dict[str, Any]) -> None:
    name = f"{int(time.time()*1000)}_{uuid.uuid4().hex[:8]}.json"
    with open(os.path.join(QUEUE_DIR, name), "w", encoding="utf-8") as f:
        json.dump(item, f)


def _safe_unlink(path: str) -> None:
    try:
        os.unlink(path)
    except OSError:
        pass
