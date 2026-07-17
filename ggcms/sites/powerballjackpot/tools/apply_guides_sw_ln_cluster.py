#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply Swahili (lang_id 20) and Lingala (lang_id 21) to a guides cluster JSON."""

from __future__ import annotations

import json
import re
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from guides_sw_ln import get_content, get_meta  # noqa: E402

LANG_IDS = {20: "sw", 21: "ln"}


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []

    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if loc is None:
            logs.append(f"lang_id={lang_id}: no locale row")
            continue

        content = get_content(entity_id, lang)
        meta = get_meta(entity_id, lang)
        if not content or not meta:
            logs.append(f"guides#{entity_id} {lang}: translation missing")
            continue

        loc["lang_url"] = lang
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = content
        loc["status"] = "published"
        logs.append(
            f"guides#{entity_id} {lang}: body {len(content.encode())}B "
            f"title={len(meta['title'])} desc={len(meta['description'])}"
        )

    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_guides_sw_ln_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
