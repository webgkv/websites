#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand casino_articles fr/ar toward EN parity on live EN HTML template."""

from __future__ import annotations

import importlib
import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from fr_ar_tune import apply_lang_tuning  # noqa: E402
from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    localize_hrefs,
    plain_len,
    sanitize_en_html,
    tag_counts,
    wrap_internal_links_noads,
)

LANG_IDS = {3: "fr", 11: "ar"}
CLUSTER_MODULES = {10: 10, 11: 11, 18: 18}
CASINO_MODES = {
    25: {"fr": "none", "ar": "expand"},
    26: {"fr": "trim", "ar": "none"},
}
CASINO_TRIM = {26: 0.91}
TARGET = 0.92
PAD_AR = (
    " تحقق من اللوبي والشروط المحلية قبل الإيداع، وابدأ بالعرض التجريبي إن وُجد."
)
PAD_FR = (
    " Vérifiez le lobby et les conditions locales avant un dépôt, et testez la démo si disponible."
)


def tune_segment(en_seg: str, loc_seg: str, pad: str, *, trim: bool, trim_ratio: float = 0.86) -> str:
    if len(en_seg) < 25:
        return loc_seg
    target = int(len(en_seg) * (trim_ratio if trim else TARGET))
    out = (loc_seg or en_seg).rstrip()
    if trim and len(out) > int(len(en_seg) * 1.02):
        hard = int(len(en_seg) * trim_ratio)
        while len(out) > hard and ". " in out:
            out = out.rsplit(". ", 1)[0] + "."
        if len(out) > int(len(en_seg) * 1.02):
            out = out[: max(hard, 40)].rsplit(" ", 1)[0] + "."
        return out
    if not out.endswith((".", "!", "?", "…")):
        out += "."
    while len(out) < target:
        out += pad
        if len(out) > int(len(en_seg) * 1.15):
            break
    return out


def rebuild_fr(entity_id: int) -> str | None:
    if entity_id not in CLUSTER_MODULES:
        return None
    mod = importlib.import_module(f"chickenroad_casino_articles_{entity_id}_locales")
    cluster = importlib.import_module(f"build_chickenroad_casino_articles_{entity_id}_cluster")
    body = mod.get_body("fr")
    return cluster.build_content(body)


def build_locale(en_html: str, loc_html: str, lang: str, entity_id: int = 0) -> str:
    en_segs = extract_segments(en_html)
    loc_segs = extract_segments(loc_html) if plain_len(loc_html) > 500 else list(en_segs)
    if len(loc_segs) != len(en_segs):
        n = min(len(en_segs), len(loc_segs))
        en_segs = en_segs[:n]
        loc_segs = loc_segs[:n]
    pad = PAD_FR if lang == "fr" else PAD_AR

    trim_ratio = 0.84 if entity_id in (18, 24) else 0.86

    def render(trim: bool) -> str:
        loc_new = [
            tune_segment(e, l, pad, trim=trim, trim_ratio=trim_ratio)
            for e, l in zip(en_segs, loc_segs)
        ]
        return apply_pairs(en_html, list(zip(en_segs, loc_new)))

    force_trim = entity_id in (18, 24)
    html = render(trim=force_trim)
    en_plain = max(plain_len(en_html), 1)
    if plain_len(html) / en_plain > 1.15:
        html = render(trim=True)
    return wrap_internal_links_noads(localize_hrefs(html, lang))


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_html = sanitize_en_html(en.get("content") or "")
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)
    modes = CASINO_MODES.get(entity_id, {})
    trim_ratio = CASINO_TRIM.get(entity_id, 0.88)

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        src_html = loc.get("content") or ""
        mode = modes.get(lang)
        if mode is not None:
            if mode == "none":
                content = wrap_internal_links_noads(localize_hrefs(src_html, lang))
            else:
                if lang == "fr" and entity_id in CLUSTER_MODULES:
                    rebuilt = rebuild_fr(entity_id)
                    if rebuilt:
                        src_html = rebuilt
                content = apply_lang_tuning(
                    en_html, src_html, lang, mode=mode, trim_ratio=trim_ratio
                )
        else:
            if lang == "fr" and entity_id in CLUSTER_MODULES:
                rebuilt = rebuild_fr(entity_id)
                if rebuilt:
                    src_html = rebuilt
            content = build_locale(en_html, src_html, lang, entity_id)
        loc["content"] = content
        loc["status"] = "published"
        loc["source"] = "content_i18n"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"casino_articles#{entity_id} {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_casino_fr_ar_cluster_expand.py <cluster.json> [out.json]", file=sys.stderr)
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
