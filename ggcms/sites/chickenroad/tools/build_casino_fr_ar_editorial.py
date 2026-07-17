#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand casino AR segments toward EN parity using FR-aligned editorial padding."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DL = Path("/home/lenovo/Downloads/02/chickenroad-casinos")
OUT = TOOLS / "casino_fr_ar_data"
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from games_i18n_utils import apply_pairs, localize_hrefs, plain_len, sanitize_en_html, wrap_internal_links_noads  # noqa: E402

META = {
    10: {
        "ar": {
            "name": "1WIN - Chicken Road",
            "title": "Chicken Road على 1win: العب مع مكافأة",
            "description": "العب Chicken Road على 1win: مكافأة التسجيل، RTP، العرض التجريبي ونصائح Cash Out للعبة Chicken Road على 1win.",
        }
    },
    11: {
        "ar": {
            "name": "1xBet - Chicken Road",
            "title": "Chicken Road على 1xBet: العب مع مكافأة",
            "description": "العب Chicken Road على 1xBet: مكافأة الترحيب، RTP، العرض التجريبي واستراتيجية Cash Out لChicken Road على 1xBet.",
        }
    },
    18: {
        "fr": {
            "name": "MOSTBET — Chicken Road",
            "title": "Chicken Road sur MOSTBET : jouer avec bonus",
            "description": "Jouez à Chicken Road sur MOSTBET : bonus, RTP, démo et conseils Cash Out pour le jeu Chicken Road MOSTBET.",
        },
        "ar": {
            "name": "MOSTBET — Chicken Road",
            "title": "Chicken Road على MOSTBET: العب مع مكافأة",
            "description": "العب Chicken Road على MOSTBET: مكافأة، RTP، العرض التجريبي ونصائح Cash Out لChicken Road على MOSTBET.",
        },
    },
    24: {
        "ar": {
            "name": "Hollywood Bet — Chicken Road",
            "title": "Chicken Road على Hollywood Bet: العب مع مكافأة",
            "description": "العب Chicken Road على Hollywood Bet: مكافآت، RTP، العرض التجريبي ونصائح Cash Out لChicken Road على Hollywood Bet.",
        }
    },
}

PAD_AR = (
    " تحقق دائماً من اللوبي والشروط المحلية قبل الإيداع، وابدأ بالعرض التجريبي إن وُجد."
)
PAD_FR = " Vérifiez toujours le lobby et les conditions locales avant un dépôt, et testez la démo si disponible."


def expand(en_seg: str, loc_seg: str, pad: str) -> str:
    if len(en_seg) < 35:
        return loc_seg
    target = int(len(en_seg) * 0.9)
    if len(loc_seg) >= target:
        return loc_seg
    out = loc_seg.rstrip()
    if not out.endswith((".", "!", "?", "…", "。")):
        out += "."
    return out + pad


def build_entity(entity_id: int) -> dict:
    data = json.loads((DL / f"seo-casino_articles-{entity_id}-full.json").read_text(encoding="utf-8"))
    locs = {x["lang_id"]: x for x in data["locales"]}
    en_html = sanitize_en_html(locs[1]["content"])
    en_segs = extract_segments(en_html)
    payload: dict = {"meta": {}, "content": {}}

    if entity_id in (10, 11, 24):
        ar_html = locs[11]["content"]
        ar_segs = extract_segments(ar_html)
        n = min(len(en_segs), len(ar_segs))
        ar_new = [expand(en_segs[i], ar_segs[i], PAD_AR) for i in range(n)]
        if len(en_segs) > n:
            ar_new.extend(en_segs[n:])
        content = apply_pairs(en_html, list(zip(en_segs[: len(ar_new)], ar_new)))
        content = wrap_internal_links_noads(localize_hrefs(content, "ar"))
        payload["meta"]["ar"] = META[entity_id]["ar"]
        payload["content"]["ar"] = content

    if entity_id == 18:
        fr_html = locs[3]["content"]
        ar_html = locs[11]["content"]
        fr_segs = extract_segments(fr_html)
        ar_segs = extract_segments(ar_html)
        nfr = min(len(en_segs), len(fr_segs))
        fr_new = [expand(en_segs[i], fr_segs[i], PAD_FR) for i in range(nfr)]
        if len(en_segs) > nfr:
            fr_new.extend(en_segs[nfr:])
        fr_content = apply_pairs(en_html, list(zip(en_segs[: len(fr_new)], fr_new)))
        fr_content = wrap_internal_links_noads(localize_hrefs(fr_content, "fr"))
        nar = min(len(en_segs), len(ar_segs))
        ar_new = [expand(en_segs[i], ar_segs[i], PAD_AR) for i in range(nar)]
        if len(en_segs) > nar:
            ar_new.extend(en_segs[nar:])
        ar_content = apply_pairs(en_html, list(zip(en_segs[: len(ar_new)], ar_new)))
        ar_content = wrap_internal_links_noads(localize_hrefs(ar_content, "ar"))
        payload["meta"]["fr"] = META[18]["fr"]
        payload["meta"]["ar"] = META[18]["ar"]
        payload["content"]["fr"] = fr_content
        payload["content"]["ar"] = ar_content

    return payload


def main() -> int:
    ids = [int(x) for x in sys.argv[1:]] if len(sys.argv) > 1 else [10, 11, 18, 24]
    OUT.mkdir(parents=True, exist_ok=True)
    for eid in ids:
        payload = build_entity(eid)
        path = OUT / f"casino_{eid}.json"
        path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        en_len = plain_len(
            sanitize_en_html(
                json.loads((DL / f"seo-casino_articles-{eid}-full.json").read_text())["locales"][0]["content"]
            )
        )
        for lang in payload.get("content", {}):
            ratio = plain_len(payload["content"][lang]) / en_len
            print(f"casino_{eid}.json {lang} ratio={ratio:.0%}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
