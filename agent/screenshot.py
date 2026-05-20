"""Full-screen capture, resizing, and JPEG compression."""
import os
from datetime import datetime
from typing import Optional

import pyautogui
from PIL import Image

from logger import get_logger

log = get_logger("screenshot")

_HERE = os.path.dirname(os.path.abspath(__file__))
SHOTS_DIR = os.path.join(_HERE, "screenshots")
os.makedirs(SHOTS_DIR, exist_ok=True)


def capture_screenshot(max_width: int = 1600, quality: int = 60) -> Optional[str]:
    """Grab the screen, downscale, JPEG-compress, and return the file path.

    Returns None on capture failure (e.g. no display available).
    """
    try:
        img: Image.Image = pyautogui.screenshot()
    except Exception as exc:
        log.error("screenshot capture failed: %s", exc)
        return None

    if img.width > max_width:
        ratio = max_width / float(img.width)
        new_size = (max_width, int(img.height * ratio))
        img = img.resize(new_size, Image.LANCZOS)

    if img.mode != "RGB":
        img = img.convert("RGB")

    filename = datetime.now().strftime("%Y-%m-%d_%H-%M-%S") + ".jpg"
    path = os.path.join(SHOTS_DIR, filename)
    try:
        img.save(path, "JPEG", quality=int(quality), optimize=True)
    except Exception as exc:
        log.error("screenshot save failed: %s", exc)
        return None
    return path
