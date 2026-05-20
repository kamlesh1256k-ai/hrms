"""Optional system-tray icon. Shows status + quit. Silently no-ops if pystray
or PIL.Image are missing so the agent still runs headless on locked-down PCs."""
import threading
from typing import Callable, Optional

from logger import get_logger

log = get_logger("tray")

try:
    import pystray
    from PIL import Image, ImageDraw
    _HAS_TRAY = True
except Exception:
    _HAS_TRAY = False


def _icon_image() -> "Image.Image":
    img = Image.new("RGB", (64, 64), color=(30, 60, 120))
    draw = ImageDraw.Draw(img)
    draw.ellipse((10, 10, 54, 54), fill=(255, 255, 255))
    draw.ellipse((22, 22, 42, 42), fill=(30, 60, 120))
    return img


class Tray:
    def __init__(self, on_quit: Callable[[], None]):
        self._on_quit = on_quit
        self._icon: Optional["pystray.Icon"] = None
        self._thread: Optional[threading.Thread] = None

    def start(self) -> None:
        if not _HAS_TRAY:
            log.info("pystray unavailable — running without tray icon")
            return

        def _quit(icon, _item):
            try:
                self._on_quit()
            finally:
                icon.stop()

        menu = pystray.Menu(
            pystray.MenuItem("HRMS Agent — running", None, enabled=False),
            pystray.MenuItem("Quit", _quit),
        )
        self._icon = pystray.Icon("hrms-agent", _icon_image(), "HRMS Agent", menu)
        self._thread = threading.Thread(target=self._icon.run, daemon=True)
        self._thread.start()

    def stop(self) -> None:
        if self._icon is not None:
            try:
                self._icon.stop()
            except Exception:
                pass
