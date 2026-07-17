#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand/trim pages#1 home fr/ar toward EN parity on live EN HTML template."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    localize_hrefs,
    plain_len,
    sanitize_en_html,
    tag_counts,
    wrap_internal_links_noads,
)

LANG_IDS = {3: "fr", 11: "ar"}
TARGET = 0.92
PAD_AR = (
    " تذكّر أن Chicken Road لعبة حظ — حدّد رهاناً ثابتاً، جرّب الديمو أولاً، "
    "ولا تطارد الخسائر في جولة واحدة طويلة."
)
PAD_FR = (
    " Chicken Road reste un jeu de hasard : fixez une mise, testez la démo d'abord, "
    "et évitez de rattraper les pertes en une seule session."
)


def tune_segment(en_seg: str, loc_seg: str, pad: str, *, trim: bool) -> str:
    if len(en_seg) < 30:
        return loc_seg
    target = int(len(en_seg) * TARGET)
    out = loc_seg.rstrip()
    if trim and len(out) > int(len(en_seg) * 1.08):
        # Trim bloated FR toward EN length at sentence boundaries
        while len(out) > target and ". " in out:
            out = out.rsplit(". ", 1)[0] + "."
        if len(out) > int(len(en_seg) * 1.08):
            out = out[: max(target, 40)].rsplit(" ", 1)[0] + "."
        return out
    if len(out) >= target:
        return out
    if not out.endswith((".", "!", "?", "…")):
        out += "."
    extra = pad
    if len(out) + len(extra) > int(len(en_seg) * 1.12):
        extra = " العب بمسؤولية." if "مسؤ" not in pad else "."
    return out + extra


def build_locale(en_html: str, loc_html: str, lang: str) -> str:
    en_segs = extract_segments(en_html)
    loc_segs = extract_segments(loc_html)
    if len(en_segs) != len(loc_segs):
        n = min(len(en_segs), len(loc_segs))
        en_segs = en_segs[:n]
        loc_segs = loc_segs[:n]
    trim = lang == "fr"
    pad = PAD_FR if lang == "fr" else PAD_AR
    loc_new = [tune_segment(e, l, pad, trim=trim) for e, l in zip(en_segs, loc_segs)]
    html = apply_pairs(en_html, list(zip(en_segs, loc_new)))
    return wrap_internal_links_noads(localize_hrefs(html, lang))


def apply_cluster(data: dict) -> list[str]:
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_html = sanitize_en_html(en.get("content") or "")
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        content = build_locale(en_html, loc.get("content") or "", lang)
        loc["content"] = content
        loc["status"] = "published"
        loc["source"] = "content_i18n"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"pages#1 {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_fr_ar_home.py <cluster.json> [out.json]", file=sys.stderr)
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
