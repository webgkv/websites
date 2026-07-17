#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Audit all Chicken Road SEO entities for sw/ln coverage on prod exports."""

from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

ENTITIES: dict[str, list[int]] = {
    "pages": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35],
    "guides": list(range(1, 9)),
    "games": list(range(1, 13)),
    "casino_articles": [10, 11, 18, 24, 25, 26],
    "blog": [1, 2, 3, 4],
    "authors": [1, 2],
}

DL_BASE = Path.home() / "Downloads/02"


def plain_len(text: str) -> int:
    return len(re.sub(r"<[^>]+>", "", text or "").strip())


def check_export(entity: str, entity_id: int) -> dict | None:
    dl_map = {
        "pages": "chickenroad-pages",
        "guides": "chickenroad-guides",
        "games": "chickenroad-games",
        "casino_articles": "chickenroad-casinos",
        "blog": "chickenroad-blog",
        "authors": "chickenroad-authors",
    }
    path = DL_BASE / dl_map[entity] / f"seo-{entity}-{entity_id}-full.json"
    if not path.is_file():
        return None
    data = json.loads(path.read_text(encoding="utf-8"))
    sw = ln = None
    for loc in data.get("locales", []):
        lid = int(loc.get("lang_id", 0))
        if lid == 20:
            sw = loc
        elif lid == 21:
            ln = loc
    return {
        "entity": entity,
        "id": entity_id,
        "sw_status": (sw or {}).get("status", "missing"),
        "ln_status": (ln or {}).get("status", "missing"),
        "sw_len": plain_len((sw or {}).get("content") or ""),
        "ln_len": plain_len((ln or {}).get("content") or ""),
        "sw_title": ((sw or {}).get("title") or "")[:50],
    }


def main() -> int:
    problems: list[str] = []
    ok = 0
    for entity, ids in ENTITIES.items():
        print(f"=== {entity} ===")
        for eid in ids:
            row = check_export(entity, eid)
            if not row:
                print(f"  #{eid} NO_LOCAL_EXPORT")
                problems.append(f"{entity}#{eid} no export")
                continue
            bad = (
                row["sw_status"] != "published"
                or row["ln_status"] != "published"
                or (row["sw_len"] < 1 and entity != "authors")
            )
            # authors: bio can be short; profile_only
            if entity == "authors":
                bad = row["sw_status"] != "published" or row["ln_status"] != "published" or row["sw_len"] < 20
            if bad:
                print(
                    f"  #{eid} FAIL sw={row['sw_status']}({row['sw_len']}) "
                    f"ln={row['ln_status']}({row['ln_len']})"
                )
                problems.append(f"{entity}#{eid}")
            else:
                print(f"  #{eid} OK sw={row['sw_len']} ln={row['ln_len']} | {row['sw_title']}")
                ok += 1
    print(f"\nTotal OK: {ok}, problems: {len(problems)}")
    if problems:
        print("Missing/failed:", ", ".join(problems))
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
