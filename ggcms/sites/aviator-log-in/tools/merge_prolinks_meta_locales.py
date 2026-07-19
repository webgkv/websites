#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Merge editorial locale meta (phase 2) into prolinks cluster JSON files."""

from __future__ import annotations

import argparse
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

CODE_TO_LANG_ID = {
    "hi": 7, "pt": 8, "ru": 9, "ar": 11, "az": 12, "bn": 13, "it": 14,
    "nl": 15, "pl": 16, "vi": 17, "ua": 18, "ro": 19, "sw": 20, "ln": 21,
}


def log(msg: str) -> None:
    print(msg, flush=True)


def merge_one(cluster_path: Path, locale_path: Path) -> int:
    cluster = json.loads(cluster_path.read_text(encoding="utf-8"))
    patch = json.loads(locale_path.read_text(encoding="utf-8"))
    locales = patch.get("locales") or {}
    n = 0
    for loc in cluster.get("locales", []):
        lang_id = int(loc.get("lang_id") or 0)
        code = next((c for c, lid in CODE_TO_LANG_ID.items() if lid == lang_id), None)
        if not code or code not in locales:
            continue
        meta = locales[code]
        if meta.get("title"):
            loc["title"] = meta["title"]
        if meta.get("description"):
            loc["description"] = meta["description"]
        if meta.get("name"):
            loc["name"] = meta["name"]
        n += 1
    cluster["exported_at"] = datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")
    cluster_path.write_text(json.dumps(cluster, ensure_ascii=False, indent=2), encoding="utf-8")
    return n


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--clusters", type=Path, required=True)
    ap.add_argument("--locales-dir", type=Path, required=True)
    args = ap.parse_args()

    total = 0
    for lp in sorted(args.locales_dir.glob("*.json")):
        m = re.match(r"^(pages|casino_articles|games)-(\d+)\.json$", lp.name)
        if not m:
            continue
        entity, eid = m.group(1), m.group(2)
        cp = args.clusters / f"seo-{entity}-{eid}-meta.json"
        if not cp.is_file():
            log(f"skip {lp.name}: no cluster {cp.name}")
            continue
        n = merge_one(cp, lp)
        log(f"merged {lp.name} -> {cp.name} ({n} locales)")
        total += n
    log(f"done merges={total}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
