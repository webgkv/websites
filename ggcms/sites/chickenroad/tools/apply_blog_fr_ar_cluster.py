#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Tune blog fr/ar body length toward EN parity (Phase 2 editorial)."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from fr_ar_tune import apply_lang_tuning, build_locale, gentle_expand  # noqa: E402
from games_i18n_utils import plain_len, sanitize_en_html, tag_counts  # noqa: E402

LANG_IDS = {3: "fr", 11: "ar"}

# blog#4 fr needs expand; ar is borderline OK
BLOG_MODES = {
    1: {"fr": "trim", "ar": "rebuild_gentle"},
    2: {"fr": "none", "ar": "expand"},
    3: {"fr": "none", "ar": "expand"},
    4: {"fr": "expand", "ar": "trim"},
}

BLOG_TRIM = {
    1: 0.84,
}


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_html = sanitize_en_html(en.get("content") or "")
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)
    modes = BLOG_MODES.get(entity_id, {"fr": "trim", "ar": "expand"})
    trim_ratio = BLOG_TRIM.get(entity_id, 0.86)

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        mode = modes.get(lang, "auto")
        if mode == "rebuild":
            content = build_locale(en_html, loc.get("content") or "", lang, trim=False, min_plain=500)
        elif mode == "rebuild_gentle":
            content = build_locale(en_html, loc.get("content") or "", lang, trim=False, min_plain=500)
            if plain_len(content) / max(en_plain, 1) < 0.85:
                content = gentle_expand(en_html, content, lang)
        else:
            content = apply_lang_tuning(
                en_html,
                loc.get("content") or "",
                lang,
                mode=mode,
                trim_ratio=trim_ratio,
            )
        loc["content"] = content
        loc["status"] = "published"
        loc["source"] = "content_i18n"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"blog#{entity_id} {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_blog_fr_ar_cluster.py <cluster.json> [out.json]", file=sys.stderr)
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
