#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#5 download cluster fr/ar (and other patched locales) from v2 builder."""

from __future__ import annotations

import copy
import importlib
import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from chickenroad_download_v2_builder import build_download_content  # noqa: E402
from chickenroad_download_v2_en import _ol1, get_en_body  # noqa: E402
from chickenroad_download_v2_locales_all import _loc, get_ru_body  # noqa: E402

PATCH_DIR = TOOLS / "download_locale_patches"
REBUILD_LANG_IDS = {3, 11}  # fr, ar


def body_for(code: str) -> dict:
    if code == "en":
        b = get_en_body()
        b["download_ol"] = [_ol1] + list(b["download_ol"][1:])
        return b
    if code == "ru":
        return get_ru_body()
    mod = importlib.import_module(f"download_locale_patches.{code}")
    patch = getattr(mod, "PATCH")
    b = _loc(**patch)
    if "download_ol" in patch:
        b["download_ol"] = [_ol1] + patch["download_ol"]
    return b


def apply_cluster(data: dict) -> list[str]:
    en_html = next(x["content"] for x in data["locales"] if x["lang_id"] == 1)
    logs: list[str] = []
    for loc in data["locales"]:
        lid = int(loc["lang_id"])
        code = loc.get("lang_url") or "en"
        if lid not in REBUILD_LANG_IDS:
            continue
        try:
            content = build_download_content(body_for(code), code)
        except ModuleNotFoundError as exc:
            logs.append(f"pages#5 {code}: patch missing ({exc})")
            continue
        loc["content"] = content
        loc["source"] = "content_i18n"
        loc["status"] = "published"
        logs.append(f"pages#5 {code}: rebuilt len={len(content)}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_fr_ar_download.py <cluster.json> [out.json]", file=sys.stderr)
        return 1
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))
    if dst == src:
        stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        backup = src.with_name(f"{src.stem}.backup-{stamp}{src.suffix}")
        shutil.copy2(src, backup)
        print(f"Backup: {backup}")
    for line in apply_cluster(data):
        print(line)
    dst.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Written: {dst}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
