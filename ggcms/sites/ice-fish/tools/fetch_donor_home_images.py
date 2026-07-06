#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Download Ice Fish homepage images from icefish.bet and write site webp assets."""

from __future__ import annotations

import io
import urllib.request
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "site/assets/images"
BASE = "https://icefish.bet/wp-content/uploads/2026/06"

# donor URL -> our asset filename
DOWNLOADS: dict[str, str] = {
    f"{BASE}/screen.webp": "ice-fish-app-desktop-mobile.webp",
    f"{BASE}/gif.gif": "ice-fish-gameplay.webp",
    f"{BASE}/Numbers-scaled.png": "ice-fish-multipliers.webp",
    f"{BASE}/fish-buttons.png": "ice-fish-step-1.webp",
    f"{BASE}/girl-scaled.png": "ice-fish-step-2.webp",
    f"{BASE}/win-red.png": "ice-fish-step-3.webp",
    f"{BASE}/screen.webp": "ice-fish-mobile.webp",  # overwritten below
    f"{BASE}/Last-wins.png": "ice-fish-demo-interface.webp",
    f"{BASE}/logo.png": "ice-fish-inout.webp",
}

# explicit order (screen used twice — mobile gets fish-buttons crop alternative)
JOBS: list[tuple[str, str]] = [
    (f"{BASE}/screen.webp", "ice-fish-app-desktop-mobile.webp"),
    (f"{BASE}/gif.gif", "ice-fish-gameplay.webp"),
    (f"{BASE}/Numbers-scaled.png", "ice-fish-multipliers.webp"),
    (f"{BASE}/fish-buttons.png", "ice-fish-step-1.webp"),
    (f"{BASE}/Not-win-light-anchor.png", "ice-fish-step-2.webp"),
    (f"{BASE}/win-red.png", "ice-fish-step-3.webp"),
    (f"{BASE}/screen.webp", "ice-fish-mobile.webp"),
    (f"{BASE}/Last-wins.png", "ice-fish-demo-interface.webp"),
    (f"{BASE}/logo.png", "ice-fish-inout.webp"),
    (f"{BASE}/screen.webp", "ice-fish-download-interface.webp"),
    (f"{BASE}/Crane-truck.png", "ice-fish-download-hero.webp"),
]


def fetch(url: str) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, timeout=60) as resp:
        return resp.read()


def to_webp(data: bytes, dst: Path, width: int = 1200) -> None:
    im = Image.open(io.BytesIO(data))
    if getattr(im, "is_animated", False):
        im.seek(0)
    if im.mode in ("RGBA", "P", "LA"):
        im = im.convert("RGBA")
        bg = Image.new("RGBA", im.size, (255, 255, 255, 255))
        im = Image.alpha_composite(bg, im).convert("RGB")
    else:
        im = im.convert("RGB")
    if im.width > width:
        h = max(1, round(im.height * width / im.width))
        im = im.resize((width, h), Image.Resampling.LANCZOS)
    dst.parent.mkdir(parents=True, exist_ok=True)
    im.save(dst, "WEBP", quality=86, method=6)
    print(f"  -> {dst.name} {im.size}")


def main() -> None:
    for url, name in JOBS:
        print(f"fetch {url}")
        data = fetch(url)
        to_webp(data, OUT / name, width=900)
    print("done")


if __name__ == "__main__":
    main()
