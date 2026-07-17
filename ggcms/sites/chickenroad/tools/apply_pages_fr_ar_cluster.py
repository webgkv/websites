#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Tune pages fr/ar body length toward EN parity (Phase 2 editorial)."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from build_chickenroad_demo_cluster import (  # noqa: E402
    LOCALE_META as DEMO_META,
    build_content as demo_build_content,
    locale_body as demo_locale_body,
)
from chickenroad_about_locales import get_content as about_get_content  # noqa: E402
from fr_ar_tune import RATIO_MIN, apply_lang_tuning, gentle_expand  # noqa: E402
from games_i18n_utils import plain_len, sanitize_en_html, tag_counts, wrap_internal_links_noads  # noqa: E402

LANG_IDS = {3: "fr", 11: "ar"}

PAGE_TRIM = {
    27: 0.82,
    28: 0.82,
}

ABOUT_META = {
    3: {
        "name": "À propos",
        "title": "À propos | Chicken Road",
        "description": "Présentation du site : guide indépendant sur Chicken Road, les casinos et le jeu responsable.",
    },
    11: {
        "name": "حولنا",
        "title": "حولنا | Chicken Road",
        "description": "دليل مستقل عن Chicken Road والكازينوهات واللعب المسؤول—لسنا مشغّل مقامرة.",
    },
}


def rebuild_demo(lang_id: int, lang: str) -> tuple[str, dict]:
    meta = DEMO_META[lang_id]
    body = demo_locale_body(lang)
    content = demo_build_content(body, meta["title"])
    return wrap_internal_links_noads(content.replace('href="/en/', f'href="/{lang}/')), {
        "name": meta["name"],
        "title": meta["title"],
        "description": meta["description"],
    }


def rebuild_about(lang_id: int, lang: str) -> tuple[str, dict]:
    content = wrap_internal_links_noads(about_get_content(lang))
    return content, ABOUT_META[lang_id]


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_html = sanitize_en_html(en.get("content") or "")
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)
    trim_ratio = PAGE_TRIM.get(entity_id, 0.88)

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        meta_patch: dict | None = None
        if entity_id == 4:
            content, meta_patch = rebuild_demo(lang_id, lang)
            if lang == "ar":
                content = gentle_expand(en_html, content, lang)
        elif entity_id == 26:
            content, meta_patch = rebuild_about(lang_id, lang)
            content = apply_lang_tuning(en_html, content, lang, mode="trim" if lang == "fr" else "none")
            if lang == "ar" and plain_len(content) / max(en_plain, 1) < RATIO_MIN:
                content = gentle_expand(en_html, content, lang)
        else:
            mode = "trim" if lang == "fr" else "expand"
            content = apply_lang_tuning(
                en_html,
                loc.get("content") or "",
                lang,
                mode=mode,
                trim_ratio=trim_ratio,
            )
        if meta_patch:
            loc["name"] = meta_patch["name"]
            loc["title"] = meta_patch["title"]
            loc["description"] = meta_patch["description"]
        loc["content"] = content
        loc["status"] = "published"
        loc["source"] = "content_i18n"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"pages#{entity_id} {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_fr_ar_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
