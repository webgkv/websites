#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply fr/ar hub meta to pages cluster JSON exports."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from pages_hub_meta_fr_ar import HUB_META  # noqa: E402

LANG_IDS = {3: "fr", 11: "ar"}


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    meta_map = HUB_META.get(entity_id)
    if not meta_map:
        return [f"pages#{entity_id}: no hub meta defined"]
    logs: list[str] = []
    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            logs.append(f"lang_id={lang_id}: no locale row")
            continue
        meta = meta_map.get(lang)
        if not meta:
            logs.append(f"pages#{entity_id} {lang}: meta missing")
            continue
        loc["lang_url"] = lang
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = loc.get("content") or ""
        loc["status"] = "published"
        logs.append(f"pages#{entity_id} {lang}: hub meta applied")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_fr_ar_hub.py <cluster.json> [out.json]", file=sys.stderr)
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
