#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate EN->locale string tables from body patches + HTML builder."""

from __future__ import annotations

import importlib
import json
import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
ROOT = TOOLS.parent
sys.path.insert(0, str(TOOLS))

from chickenroad_download_v2_builder import build_download_content  # noqa: E402
from chickenroad_download_v2_en import _ol1, get_en_body  # noqa: E402
from chickenroad_download_v2_locales_all import _loc, get_ru_body  # noqa: E402

LIVE = ROOT / "tmp/jason/seo-pages-5-live-export.json"
OUT = ROOT / "tmp/jason/download-locale-tables.json"
PATCH_DIR = TOOLS / "download_locale_patches"


def section_pairs(en_html: str, loc_html: str) -> dict[str, str]:
    pairs: dict[str, str] = {}

    def secs(html: str) -> dict[str, str]:
        return {
            m.group(1): m.group(0)
            for m in re.finditer(r'<section id="([^"]+)"[^>]*>(.*?)</section>', html, re.S)
        }

    se, sl = secs(en_html), secs(loc_html)
    for k in se:
        if k not in sl:
            continue
        ae = [x.strip() for x in re.findall(r">([^<]{4,})<", se[k])]
        be = [x.strip() for x in re.findall(r">([^<]{4,})<", sl[k])]
        for a, b in zip(ae, be):
            if a != b:
                pairs[a] = b
    # h1 outside sections
    for a, b in zip(re.findall(r"<h1>([^<]+)</h1>", en_html), re.findall(r"<h1>([^<]+)</h1>", loc_html)):
        if a.strip() != b.strip():
            pairs[a.strip()] = b.strip()
    for a, b in zip(re.findall(r'alt="([^"]+)"', en_html), re.findall(r'alt="([^"]+)"', loc_html)):
        if a != b:
            pairs[a] = b
    return pairs


def body_for(code: str) -> dict:
    if code == "ru":
        return get_ru_body()
    mod = importlib.import_module(f"download_locale_patches.{code}")
    patch = getattr(mod, "PATCH")
    b = _loc(**patch)
    if "download_ol" in patch:
        b["download_ol"] = [_ol1] + patch["download_ol"]
    return b


def main() -> None:
    cluster = json.loads(LIVE.read_text(encoding="utf-8"))
    en_html = next(x["content"] for x in cluster["locales"] if x["lang_id"] == 1)
    tables: dict[str, dict[str, str]] = {}

    for path in sorted(PATCH_DIR.glob("*.py")):
        if path.name.startswith("_"):
            continue
        code = path.stem
        html = build_download_content(body_for(code), code)
        tables[code] = section_pairs(en_html, html)
        print(code, "pairs", len(tables[code]), "noads", html.count("noads"))

    OUT.write_text(json.dumps(tables, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("Wrote", OUT)


if __name__ == "__main__":
    main()
