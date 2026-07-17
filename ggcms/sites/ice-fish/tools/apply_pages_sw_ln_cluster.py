#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply Swahili (20) and Lingala (21) to Ice Fish pages cluster JSON export."""

from __future__ import annotations

import json
import re
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
CHICKEN_TOOLS = Path(__file__).resolve().parents[1].parent / "chickenroad" / "tools"
sys.path.insert(0, str(TOOLS))
sys.path.insert(0, str(CHICKEN_TOOLS))

from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    localize_hrefs,
    plain_len,
    sanitize_en_html,
    tag_counts,
    wrap_internal_links_noads,
)
from pages_hub_meta_sw_ln import HUB_META  # noqa: E402
from pages_sw_ln import get_fr_ln_pairs, get_meta, get_pairs, is_hub_only, ln_from_fr  # noqa: E402

LANG_IDS = {20: "sw", 21: "ln"}
PLAIN_MIN = 20


def en_template(data: dict) -> str:
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    return sanitize_en_html(en.get("content") or "")


def fr_template(data: dict) -> str:
    fr = next((x for x in data["locales"] if x["lang_id"] == 3), None)
    return (fr.get("content") or "") if fr else ""


def is_hub_cluster(data: dict) -> bool:
    entity_id = int(data.get("entity_id") or 0)
    if entity_id in HUB_META or is_hub_only(entity_id):
        return True
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    return plain_len(en.get("content") or "") < PLAIN_MIN


def build_sw_content(data: dict) -> str | None:
    entity_id = int(data.get("entity_id") or 0)
    if is_hub_cluster(data):
        return ""
    pairs = get_pairs(entity_id, "sw")
    if not pairs:
        return None
    html = apply_pairs(en_template(data), pairs)
    html = localize_hrefs(html, "sw")
    return wrap_internal_links_noads(html)


def build_ln_content(data: dict) -> str | None:
    entity_id = int(data.get("entity_id") or 0)
    if is_hub_cluster(data):
        return ""
    if ln_from_fr(entity_id):
        pairs = get_fr_ln_pairs(entity_id)
        fr_html = fr_template(data)
        if not pairs or not fr_html:
            return None
        html = apply_pairs(fr_html, pairs)
    else:
        pairs = get_pairs(entity_id, "ln")
        if not pairs:
            return None
        html = apply_pairs(en_template(data), pairs)
    html = localize_hrefs(html, "ln")
    return wrap_internal_links_noads(html)


def resolve_meta(entity_id: int, lang: str) -> dict[str, str] | None:
    if entity_id in HUB_META:
        return HUB_META[entity_id][lang]
    meta = get_meta(entity_id, lang)
    return meta


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    hub = is_hub_cluster(data)
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_san = sanitize_en_html(en.get("content") or "")
    if en_san != (en.get("content") or ""):
        en["content"] = en_san
        logs.append(f"pages#{entity_id} en: sanitized template")
    base = tag_counts(en_san) if not hub else {}
    fr_san = fr_template(data)
    fr_base = tag_counts(fr_san) if fr_san else base

    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            logs.append(f"lang_id={lang_id}: no locale row")
            continue
        meta = resolve_meta(entity_id, lang)
        if not meta:
            logs.append(f"pages#{entity_id} {lang}: meta missing")
            continue
        content = build_sw_content(data) if lang == "sw" else build_ln_content(data)
        if content is None:
            logs.append(f"pages#{entity_id} {lang}: content missing")
            continue
        if not hub and plain_len(content) < PLAIN_MIN:
            logs.append(f"pages#{entity_id} {lang}: body too short")
            continue
        if not hub:
            tc = tag_counts(content)
            tag_base = fr_base if lang == "ln" and ln_from_fr(entity_id) else base
            bad = [k for k in tag_base if tc.get(k, 0) != tag_base[k]]
        else:
            bad = []
        loc["lang_url"] = lang
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = content
        loc["status"] = "published"
        logs.append(
            f"pages#{entity_id} {lang}: {'hub' if hub else plain_len(content)} "
            f"tags={bad or 'match'} title={len(meta['title'])} desc={len(meta['description'])}"
        )
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_sw_ln_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
