#!/usr/bin/env python3
"""
Compare two face images using DeepFace.verify().
Print one JSON line to stdout for PHP: match, confidence (0-100), message, error, distance, threshold.
Usage: python deepface_verify.py <img1_abs> <img2_abs> [model_name] [detector_backend]
"""
from __future__ import annotations

import json
import sys


def main() -> None:
    if len(sys.argv) < 3:
        print(
            json.dumps(
                {
                    "error": True,
                    "match": False,
                    "confidence": 0,
                    "message": "Usage: deepface_verify.py <img1> <img2> [model] [detector]",
                }
            )
        )
        sys.exit(2)

    img1, img2 = sys.argv[1], sys.argv[2]
    model = sys.argv[3] if len(sys.argv) > 3 else "Facenet512"
    detector = sys.argv[4] if len(sys.argv) > 4 else "opencv"

    try:
        from deepface import DeepFace
    except ImportError:
        print(
            json.dumps(
                {
                    "error": True,
                    "match": False,
                    "confidence": 0,
                    "message": "deepface package not installed (pip install deepface)",
                }
            )
        )
        sys.exit(1)

    kwargs = {
        "img1_path": img1,
        "img2_path": img2,
        "model_name": model,
        "detector_backend": detector,
        "enforce_detection": False,
    }
    try:
        r = DeepFace.verify(**kwargs)
    except TypeError:
        kwargs.pop("enforce_detection", None)
        r = DeepFace.verify(**kwargs)
    except Exception as e:
        print(
            json.dumps(
                {
                    "error": True,
                    "match": False,
                    "confidence": 0,
                    "message": str(e),
                }
            )
        )
        sys.exit(1)

    verified = bool(r.get("verified", False))
    try:
        distance = float(r.get("distance", 1.0))
    except (TypeError, ValueError):
        distance = 1.0
    try:
        threshold = float(r.get("threshold", 0.4))
    except (TypeError, ValueError):
        threshold = 0.4

    if verified:
        margin = max(0.0, threshold - distance)
        confidence = 70.0 + min(30.0, (margin / max(threshold, 1e-9)) * 30.0)
    else:
        denom = max(threshold * 2, 1e-9)
        confidence = max(0.0, min(49.0, 40.0 * (1.0 - min(distance, threshold * 2) / denom)))

    out = {
        "error": False,
        "match": verified,
        "confidence": round(confidence, 2),
        "message": "DeepFace: face match" if verified else "DeepFace: face does not match",
        "distance": distance,
        "threshold": threshold,
        "model": r.get("model", model),
    }
    print(json.dumps(out))
    sys.exit(0)


if __name__ == "__main__":
    main()
