#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply Swahili (20) and Lingala (21) to an authors cluster JSON export."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from authors_sw_ln import get_locale  # noqa: E402

LANG_IDS = {20: "sw", 21: "ln"}


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    url_slug = f"author-{entity_id}"

    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            logs.append(f"lang_id={lang_id}: no locale row")
            continue
        fields = get_locale(entity_id, lang)
        if not fields:
            logs.append(f"authors#{entity_id} {lang}: translation missing")
            continue
        for key in ("name", "title", "content"):
            if not (fields.get(key) or "").strip():
                logs.append(f"authors#{entity_id} {lang}: empty {key}")
                break
        else:
            loc["lang_url"] = lang
            loc["url"] = url_slug
            loc["name"] = fields["name"]
            loc["title"] = fields["title"]
            loc["description"] = ""
            loc["content"] = fields["content"]
            loc["status"] = "published"
            logs.append(
                f"authors#{entity_id} {lang}: name={fields['name']!r} "
                f"title={len(fields['title'])} bio={len(fields['content'])}"
            )
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_authors_sw_ln_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
