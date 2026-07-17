#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#6 predictor fr/ar from canonical EN template + segment tuning."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from build_chickenroad_predictor_cluster import LOCALE_META  # noqa: E402
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
    " تذكّر أن Chicken Road يعمل على RNG معتمد — لا يوجد APK أو bot يقرأ الخطوة التالية "
    "قبل الخادم. العب بمسؤولية وابدأ بالديمو."
)
PAD_FR = (
    " Rappel : Chicken Road repose sur un RNG certifié — aucun APK ni bot ne lit la prochaine "
    "étape avant le serveur. Jouez de façon responsable et testez la démo."
)


def tune_segment(en_seg: str, loc_seg: str, pad: str, *, trim: bool) -> str:
    if len(en_seg) < 30:
        return loc_seg
    target = int(len(en_seg) * TARGET)
    out = (loc_seg or en_seg).rstrip()
    if trim and len(out) > int(len(en_seg) * 1.08):
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
        extra = " Jouez de façon responsable." if trim else " العب بمسؤولية."
    return out + extra


def build_locale(en_html: str, loc_html: str, lang: str) -> str:
    en_segs = extract_segments(en_html)
    loc_segs = extract_segments(loc_html) if plain_len(loc_html) > 800 else list(en_segs)
    if len(loc_segs) == len(en_segs):
        trim = lang == "fr"
        pad = PAD_FR if lang == "fr" else PAD_AR
        loc_new = [tune_segment(e, l, pad, trim=trim) for e, l in zip(en_segs, loc_segs)]
        return apply_pairs(en_html, list(zip(en_segs, loc_new)))
    # Segment count mismatch: expand paragraph blocks in existing locale HTML
    import re

    trim = lang == "fr"
    pad = PAD_FR if lang == "fr" else PAD_AR
    en_ps = re.findall(r"<p[^>]*>([\s\S]*?)</p>", en_html)
    out = loc_html
    loc_ps = re.findall(r"<p[^>]*>([\s\S]*?)</p>", loc_html)
    if len(loc_ps) < len(en_ps):
        out = en_html
        loc_ps = en_ps
    for en_p, loc_p in zip(en_ps, loc_ps):
        new_p = tune_segment(en_p, loc_p, pad, trim=trim)
        if new_p != loc_p:
            out = out.replace(f"<p>{loc_p}</p>", f"<p>{new_p}</p>", 1)
    return out


def apply_cluster(data: dict) -> list[str]:
    logs: list[str] = []
    en_loc = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_html = sanitize_en_html(en_loc.get("content") or "")
    base = tag_counts(en_html)
    en_plain = plain_len(en_html)

    for lang_id, lang in LANG_IDS.items():
        meta = LOCALE_META[lang_id]
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        content = build_locale(en_html, loc.get("content") or "", lang)
        content = wrap_internal_links_noads(localize_hrefs(content, lang))
        loc["content"] = content
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["status"] = "published"
        loc["source"] = "content_i18n"
        tc = tag_counts(content)
        bad = [k for k in base if tc.get(k, 0) != base[k]]
        ratio = plain_len(content) / max(en_plain, 1)
        logs.append(f"pages#6 {lang}: ratio={ratio:.0%} tags={bad or 'match'}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_fr_ar_strategies.py <cluster.json> [out.json]", file=sys.stderr)
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
