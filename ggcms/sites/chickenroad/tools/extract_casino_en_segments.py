#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extract ordered translatable text segments from casino_articles EN HTML."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402

DL = Path("/home/lenovo/Downloads/02/chickenroad-casinos")


def main() -> int:
    entity_id = int(sys.argv[1]) if len(sys.argv) > 1 else 0
    ids = [entity_id] if entity_id else [10, 11, 18, 24, 25, 26]
    for eid in ids:
        path = DL / f"seo-casino_articles-{eid}-full.json"
        if not path.is_file():
            print(f"casino#{eid}: missing export {path}")
            continue
        data = json.loads(path.read_text(encoding="utf-8"))
        en = next(x for x in data["locales"] if x["lang_id"] == 1)
        html = en.get("content") or ""
        segs = extract_segments(html)
        out = DL / f"casino-{eid}-en-segments.json"
        out.write_text(json.dumps(segs, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"casino#{eid}: {len(segs)} EN segments")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
