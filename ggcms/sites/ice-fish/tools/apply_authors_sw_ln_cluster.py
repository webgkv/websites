#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply sw/ln to Ice Fish authors cluster JSON."""

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
    eid = int(data.get("entity_id") or 0)
    logs: list[str] = []
    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            continue
        fields = get_locale(eid, lang)
        if not fields or not all((fields.get(k) or "").strip() for k in ("name", "title", "content")):
            logs.append(f"authors#{eid} {lang}: missing")
            continue
        loc.update(lang_url=lang, url=f"author-{eid}", name=fields["name"], title=fields["title"], description="", content=fields["content"], status="published")
        logs.append(f"authors#{eid} {lang}: bio={len(fields['content'])}")
    return logs


def main() -> int:
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))
    if dst == src:
        b = src.with_name(f"{src.stem}.backup-{datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')}{src.suffix}")
        shutil.copy2(src, b)
    for line in apply_cluster(data):
        print(line)
    dst.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
