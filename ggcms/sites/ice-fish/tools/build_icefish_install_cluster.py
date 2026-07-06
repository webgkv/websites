#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages install-apk / install-pwa clusters from Aviator export → Ice Fish."""

from __future__ import annotations

import json
import re
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
AVIATOR = Path("/home/lenovo/bin/aviator-log-in/site/files/reference")
OUT = ROOT / "site/files/reference"

REBRAND = [
    ("aviator-log-in.com", "ice-fish.run"),
    ("/files/aviator.apk", "/files/ice-fish.apk"),
    ('download="aviator.apk"', 'download="ice-fish.apk"'),
    ("Aviator Log In", "Ice Fish"),
    ("Aviator demo", "Ice Fish demo"),
    ("Aviator Demo", "Ice Fish demo"),
    ("Aviator Android APK", "Ice Fish Android APK"),
    ("Aviator APK", "Ice Fish APK"),
    ("the Aviator demo", "the Ice Fish demo"),
    ("The Aviator demo", "The Ice Fish demo"),
    ("installing Aviator", "installing Ice Fish"),
    ("Install Aviator", "Install Ice Fish"),
    ("open Aviator", "open Ice Fish"),
    ("Open Aviator", "Open Ice Fish"),
    ("Aviator from Google Play", "Ice Fish app from Google Play"),
    ("Aviator", "Ice Fish"),
]

APK_EN = {
    "title": "Install Ice Fish demo (Android APK)",
    "description": "Download the Ice Fish Android APK, allow installs from your browser or file app, then install and turn on notifications for bonus and promo updates.",
    "name": "Install Android APK",
}


def rebrand(s: str) -> str:
    if not s:
        return s
    for old, new in REBRAND:
        s = re.sub(re.escape(old), new, s, flags=re.IGNORECASE)
    return s


def patch_locale(loc: dict) -> dict:
    out = deepcopy(loc)
    for key in ("name", "title", "description", "content"):
        if key in out and out[key]:
            out[key] = rebrand(str(out[key]))
    if out.get("lang_url") == "en" and out.get("url") == "install-apk":
        out["title"] = APK_EN["title"]
        out["description"] = APK_EN["description"]
        out["name"] = APK_EN["name"]
    return out


def process_file(src_name: str, out_name: str, entity_id: int | None = None) -> None:
    src = AVIATOR / src_name
    if not src.is_file():
        print(f"skip missing {src}")
        return
    data = json.loads(src.read_text(encoding="utf-8"))
    data["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    if entity_id is not None:
        data["entity_id"] = entity_id
    locales = []
    for loc in data.get("locales", []):
        locales.append(patch_locale(loc))
    data["locales"] = locales
    out = OUT / out_name
    out.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"wrote {out} ({len(locales)} locales)")


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    process_file("seo-pages-34-full.json", "seo-pages-34-install-apk-full.json", 34)
    # install-pwa may live in another export; try common names
    for name in ("seo-pages-35-full.json", "seo-pages-33-full.json"):
        p = AVIATOR / name
        if p.is_file():
            sample = p.read_text(encoding="utf-8")[:2000]
            if "install-pwa" in sample or "ios-pwa" in sample:
                process_file(name, "seo-pages-install-pwa-full.json")
                break
    else:
        print("install-pwa export not found in aviator reference — use Admin import after DB row exists")


if __name__ == "__main__":
    main()
