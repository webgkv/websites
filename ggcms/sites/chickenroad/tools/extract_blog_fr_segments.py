#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extract ordered translatable text segments from blog FR HTML."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402

DL = Path("/home/lenovo/Downloads/02/chickenroad-blog")


def main() -> int:
    entity_id = int(sys.argv[1]) if len(sys.argv) > 1 else 0
    ids = [entity_id] if entity_id else [1, 2, 3, 4]
    for eid in ids:
        path = DL / f"seo-blog-{eid}-full.json"
        if not path.is_file():
            continue
        data = json.loads(path.read_text(encoding="utf-8"))
        fr = next((x for x in data["locales"] if x["lang_id"] == 3), None)
        html = (fr.get("content") or "") if fr else ""
        segs = extract_segments(html) if html else []
        out = DL / f"blog-{eid}-fr-segments.json"
        out.write_text(json.dumps(segs, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"blog#{eid}: {len(segs)} FR segments")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
