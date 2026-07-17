#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extract ordered translatable text segments from games EN HTML for sw/ln pair building."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

DL = Path("/home/lenovo/Downloads/02/chickenroad-games")


def extract_segments(html: str) -> list[str]:
    segs: list[str] = []
    # headings
    for m in re.finditer(r"<h([1-3])[^>]*>(.*?)</h\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if t and t not in segs:
            segs.append(t)
    # alt
    for m in re.finditer(r'alt="([^"]*)"', html, re.I):
        t = m.group(1).strip()
        if t and t not in segs:
            segs.append(t)
    # paragraphs and li - preserve longer blocks
    for m in re.finditer(r"<(p|li|td|th|summary|figcaption)[^>]*>(.*?)</\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if len(t) >= 8 and t not in segs:
            segs.append(t)
    # strong-only FAQ questions inline in p tags already captured
    return segs


def main() -> int:
    entity_id = int(sys.argv[1]) if len(sys.argv) > 1 else 0
    ids = [entity_id] if entity_id else list(range(1, 13))
    for eid in ids:
        path = DL / f"seo-games-{eid}-full.json"
        data = json.loads(path.read_text(encoding="utf-8"))
        en = next(x for x in data["locales"] if x["lang_id"] == 1)
        html = en.get("content") or ""
        segs = extract_segments(html)
        out = DL / f"games-{eid}-en-segments.json"
        out.write_text(json.dumps(segs, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"games#{eid}: {len(segs)} segments, plain {len(re.sub(r'<[^>]+>','',html).strip())} chars")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
