#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial blog_1/2.json sw/ln translation data."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "blog_sw_ln_data"
DL = Path("/home/lenovo/Downloads/02/chickenroad-blog")

sys.path.insert(0, str(TOOLS))
from ln_quality_replacements import polish_ln  # noqa: E402
from blog_data_1 import LN1, SW1  # noqa: E402
from blog_data_2 import LN2, SW2  # noqa: E402


def load_segs(name: str) -> list[str]:
    return json.loads((DL / name).read_text(encoding="utf-8"))


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    assert len(keys) == len(vals), f"{len(keys)} vs {len(vals)}"
    return [[k, v] for k, v in zip(keys, vals)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def build_blog_1() -> None:
    en = load_segs("blog-1-en-segments.json")
    fr = load_segs("blog-1-fr-segments.json")
    ln = [polish_ln(t) for t in LN1]
    assert len(en) == len(SW1) == 69
    assert len(fr) == len(ln) == 93
    sw_title, sw_desc = truncate(
        "Historia ya Chicken Road: Kutoka Kuzinduliwa Hadi Leo",
        "Historia kamili ya Chicken Road: mwanzo wa mchezo, mageuzi hadi Chicken Road 2 na mabadiliko yaliyotokea.",
    )
    ln_title, ln_desc = truncate(
        "Lisolo ya Chicken Road: Kobanda Utangulá Tii Lelo",
        "Lisolo mobimba ya Chicken Road: ndenge lisano ebandaki, evolusyon na Chicken Road 2 mpe oyo ebongwanaki.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "Historia ya Chicken Road — mawazo, msukumo, dhana...",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "Lisolo ya Chicken Road — ba idée, inspiration...",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW1),
            "fr_ln": pairs_from_lists(fr, ln),
        },
    }
    (OUT / "blog_1.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"blog_1.json: {len(SW1)} sw + {len(ln)} fr_ln")


def build_blog_2() -> None:
    en = load_segs("blog-2-en-segments.json")
    fr = load_segs("blog-2-fr-segments.json")
    ln = [polish_ln(t) for t in LN2]
    assert len(en) == len(SW2) == 77
    assert len(fr) == len(ln) == 77
    sw_title, sw_desc = truncate(
        "Je, Chicken Road ni Halali au Ulaghai? {year}",
        "Je, Chicken Road ni halali? Leseni ya Curaçao, Provably Fair, RTP 98% — jinsi ya kutambua nakala bandia na kucheza salama.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road ezali na solo to arnaque? {year}",
        "Chicken Road ezali na solo? Licence Curaçao, Provably Fair, RTP 98% — koyeba ba faux, kobeta malamu mpe kopona casino ya solo.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "Je, Chicken Road ni Halali au Ulaghai? {year} Uamuzi wa Kweli",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "Chicken Road na solo to arnaque? Verdict ya solo {year}",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW2),
            "fr_ln": pairs_from_lists(fr, ln),
        },
    }
    (OUT / "blog_2.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"blog_2.json: {len(SW2)} sw + {len(ln)} fr_ln")


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    build_blog_1()
    build_blog_2()


if __name__ == "__main__":
    main()
