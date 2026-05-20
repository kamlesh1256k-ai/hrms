"""Tracks keyboard/mouse activity in a background thread.

Exposes a thread-safe `ActivityTracker` that the main loop polls every
interval to read counters and active/idle seconds since the last poll.
"""
import threading
import time
from typing import Dict

from pynput import keyboard, mouse

from logger import get_logger

log = get_logger("activity")


class ActivityTracker:
    def __init__(self, idle_threshold: int = 60):
        self.idle_threshold = int(idle_threshold)

        self._lock = threading.Lock()
        self._last_event_at = time.time()
        self._keystrokes = 0
        self._mouse_events = 0

        self._active_seconds = 0.0
        self._idle_seconds = 0.0
        self._last_tick_at = time.time()

        self._kb_listener = keyboard.Listener(on_press=self._on_key, daemon=True)
        self._mouse_listener = mouse.Listener(
            on_move=self._on_mouse_move,
            on_click=self._on_mouse_click,
            on_scroll=self._on_mouse_scroll,
            daemon=True,
        )

        self._timer_thread = threading.Thread(target=self._tick_loop, daemon=True)
        self._stopping = threading.Event()

    # ── lifecycle ────────────────────────────────────────────
    def start(self) -> None:
        self._kb_listener.start()
        self._mouse_listener.start()
        self._timer_thread.start()
        log.info("activity tracker started")

    def stop(self) -> None:
        self._stopping.set()
        try:
            self._kb_listener.stop()
            self._mouse_listener.stop()
        except Exception:
            pass

    # ── input handlers ───────────────────────────────────────
    def _on_key(self, _key) -> None:
        with self._lock:
            self._keystrokes += 1
            self._last_event_at = time.time()

    def _on_mouse_move(self, _x, _y) -> None:
        with self._lock:
            self._mouse_events += 1
            self._last_event_at = time.time()

    def _on_mouse_click(self, _x, _y, _button, pressed) -> None:
        if pressed:
            with self._lock:
                self._mouse_events += 1
                self._last_event_at = time.time()

    def _on_mouse_scroll(self, _x, _y, _dx, _dy) -> None:
        with self._lock:
            self._mouse_events += 1
            self._last_event_at = time.time()

    # ── ticker ───────────────────────────────────────────────
    def _tick_loop(self) -> None:
        """Every second, classify the last second as active or idle."""
        while not self._stopping.is_set():
            time.sleep(1.0)
            now = time.time()
            with self._lock:
                elapsed = now - self._last_tick_at
                self._last_tick_at = now
                idle_for = now - self._last_event_at
                if idle_for >= self.idle_threshold:
                    self._idle_seconds += elapsed
                else:
                    self._active_seconds += elapsed

    # ── snapshot ─────────────────────────────────────────────
    def get_idle_time(self) -> float:
        """Seconds since the last keyboard/mouse event."""
        with self._lock:
            return time.time() - self._last_event_at

    def snapshot_and_reset(self) -> Dict[str, int]:
        """Atomically read and zero counters. Call once per upload tick."""
        with self._lock:
            data = {
                "active_seconds": int(round(self._active_seconds)),
                "idle_seconds":   int(round(self._idle_seconds)),
                "keystrokes":     self._keystrokes,
                "mouse_events":   self._mouse_events,
            }
            self._active_seconds = 0.0
            self._idle_seconds = 0.0
            self._keystrokes = 0
            self._mouse_events = 0
            return data
