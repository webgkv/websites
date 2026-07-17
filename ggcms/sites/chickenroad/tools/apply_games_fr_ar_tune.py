#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand games fr/ar toward EN parity; rebuild from EN template to fix tag drift."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from fr_ar_tune import apply_lang_tuning, build_locale, build_locale_capped, gentle_expand  # noqa: E402
from games_fr_ar import get_content_override, get_meta, get_pairs  # noqa: E402
from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    localize_hrefs,
    plain_len,
    sanitize_en_html,
    tag_counts,
    wrap_internal_links_noads,
)

LANG_IDS = {3: "fr", 11: "ar"}

GAMES_MODES = {
    3: {"fr": "rebuild", "ar": "rebuild_gentle"},
    6: {"fr": "rebuild", "ar": "expand"},
    7: {"fr": "rebuild", "ar": "expand"},
    12: {"fr": "trim", "ar": "rebuild_gentle"},
}


def en_template(data: dict) -> str:
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    return sanitize_en_html(en.get("content") or "")


def base_content(data: dict, lang: str) -> str | None:
    entity_id = int(data.get("entity_id") or 0)
    override = get_content_override(entity_id, lang)
    if override:
        return override
    pairs = get_pairs(entity_id, lang)
    if pairs:
        return apply_pairs(en_template(data), pairs)
    loc = next((x for x in data["locales"] if x.get("lang_url") == lang), None)
    return (loc.get("content") or "") if loc else None


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en_html = en_template(data)
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)
    modes = GAMES_MODES.get(entity_id, {"fr": "none", "ar": "expand"})

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        meta = get_meta(entity_id, lang)
        src = base_content(data, lang) or loc.get("content") or ""
        mode = modes.get(lang, "expand")
        if mode == "rebuild":
            if lang == "fr":
                content = build_locale(en_html, src, lang, trim=False, min_plain=80)
            else:
                content = gentle_expand(en_html, src, lang)
        elif mode == "rebuild_gentle":
            content = build_locale_capped(en_html, src, lang, min_plain=80)
            if plain_len(content) / max(en_plain, 1) < 0.85:
                content = gentle_expand(en_html, content, lang)
        elif mode == "gentle":
            content = gentle_expand(en_html, src, lang)
        elif mode == "none" and src:
            content = wrap_internal_links_noads(localize_hrefs(src, lang))
        else:
            content = apply_lang_tuning(en_html, src, lang, mode=mode, trim_ratio=0.88)
        if meta:
            loc["name"] = meta["name"]
            loc["title"] = meta["title"]
            loc["description"] = meta["description"]
        loc["lang_url"] = lang
        loc["content"] = content
        loc["status"] = "published"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"games#{entity_id} {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_games_fr_ar_tune.py <cluster.json> [out.json]", file=sys.stderr)
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
