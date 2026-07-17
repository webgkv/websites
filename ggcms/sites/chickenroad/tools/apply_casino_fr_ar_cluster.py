#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply French (3) and Arabic (11) to casino_articles cluster JSON from EN template."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from casino_fr_ar import get_content_override, get_meta, get_pairs  # noqa: E402
from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    localize_hrefs,
    plain_len,
    sanitize_en_html,
    tag_counts,
    wrap_internal_links_noads,
)

LANG_IDS = {3: "fr", 11: "ar"}
PLAIN_MIN = 20


def en_template(data: dict) -> str:
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    return sanitize_en_html(en.get("content") or "")


def build_content(data: dict, lang: str) -> str | None:
    entity_id = int(data.get("entity_id") or 0)
    override = get_content_override(entity_id, lang)
    if override:
        html = override
    else:
        pairs = get_pairs(entity_id, lang)
        if not pairs:
            return None
        html = apply_pairs(en_template(data), pairs)
    html = localize_hrefs(html, lang)
    return wrap_internal_links_noads(html)


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_san = sanitize_en_html(en.get("content") or "")
    if en_san != (en.get("content") or ""):
        en["content"] = en_san
        logs.append(f"casino_articles#{entity_id} en: sanitized template")
    base = tag_counts(en_san)

    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            logs.append(f"lang_id={lang_id}: no locale row")
            continue
        meta = get_meta(entity_id, lang)
        content = build_content(data, lang)
        if not meta or not content:
            logs.append(f"casino_articles#{entity_id} {lang}: translation missing")
            continue
        if plain_len(content) < PLAIN_MIN:
            logs.append(f"casino_articles#{entity_id} {lang}: body too short")
            continue
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        loc["lang_url"] = lang
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = content
        loc["status"] = "published"
        ratio = plain_len(content) / max(plain_len(en_san), 1)
        logs.append(
            f"casino_articles#{entity_id} {lang}: body {len(content.encode())}B "
            f"ratio={ratio:.0%} tags={bad or 'match'} "
            f"title={len(meta['title'])} desc={len(meta['description'])}"
        )
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_casino_fr_ar_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
