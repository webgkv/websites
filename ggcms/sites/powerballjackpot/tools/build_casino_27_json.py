#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build casino_articles#27 sw/ln JSON from fixed EN export."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
CHICKEN = TOOLS.parent.parent / "chickenroad" / "tools"
EXPORT = Path("/home/lenovo/Downloads/02/powerballjackpot-casinos/seo-casino-articles-27-full.json")
sys.path.insert(0, str(CHICKEN))
sys.path.insert(0, str(TOOLS))

from fix_en_seo_html import fix_casino27_html  # noqa: E402
from ln_quality_replacements import polish_ln  # noqa: E402


def extract_segments(html: str) -> list[str]:
    segs: list[str] = []
    for m in re.finditer(r"<h([1-3])[^>]*>(.*?)</h\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if t and t not in segs:
            segs.append(t)
    for m in re.finditer(r'alt="([^"]*)"', html, re.I):
        t = m.group(1).strip()
        if t and t not in segs:
            segs.append(t)
    for m in re.finditer(r"<(p|li|td|th|summary|figcaption)[^>]*>(.*?)</\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if len(t) >= 8 and t not in segs:
            segs.append(t)
    return segs


EXTRA_SW: dict[str, str] = {
    "Powerball Lottery: America&rsquo;s Favorite Dream Machine": (
        "Powerball Lottery: Mashine ya Ndoto ya Amerika"
    ),
    "PowerBall lottery draw results on screen": "Matokeo ya droo ya PowerBall kwenye skrini",
    "PowerBall white balls and red Powerball drum": "Mpira nyeupe na drum nyekundu ya PowerBall",
    "Five main numbers plus red Powerball explained": "Nambari tano kuu pamoja na PowerBall nyekundu",
    "Customers buying lottery tickets at a retail store": "Wateja wananunua tiketi za bahati nasibu dukani",
    "PowerBall jackpot winner news headline photo": "Picha ya habari ya mshindi wa jackpot ya PowerBall",
    "Stack of PowerBall tickets before a draw": "Rafu ya tiketi za PowerBall kabla ya droo",
}


def sw_to_ln(text: str) -> str:
    t = text
    for a, b in [
        ("Mashine ya Ndoto ya Amerika", "Machine ya Rêve ya Amerika"),
        ("Matokeo ya droo", "Ba resultat ya tirage"),
        ("Mpira nyeupe", "Boule ya mpɛmbɛ"),
        ("Nambari tano", "Mitano ya numéro"),
        ("Wateja", "Ba clients"),
        ("tiketi", "ticket"),
        ("habari", "bansango"),
        ("mshindi", "gagnant"),
        ("jackpot", "jackpot"),
        ("Rafu ya", "Pile ya"),
        ("kabla ya droo", "liboso ya tirage"),
        ("PowerBall", "PowerBall"),
        ("Powerball", "Powerball"),
    ]:
        t = t.replace(a, b)
    return polish_ln(t)


def load_legacy_sw() -> dict[str, str]:
    legacy_path = TOOLS / "_casino27_sw.json"
    if not legacy_path.is_file():
        return {}
    arr = json.loads(legacy_path.read_text(encoding="utf-8"))
    old_seg = json.loads(Path("/tmp/pb_c27_segs.json").read_text(encoding="utf-8"))
    return dict(zip(old_seg, arr))


def main() -> int:
    data = json.loads(EXPORT.read_text(encoding="utf-8"))
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    html = fix_casino27_html(en.get("content") or "")
    seg = extract_segments(html)
    legacy = load_legacy_sw()

    sw: list[str] = []
    for s in seg:
        if s in EXTRA_SW:
            sw.append(EXTRA_SW[s])
        elif s in legacy:
            sw.append(legacy[s])
        else:
            sw.append(s)
    ln = [sw_to_ln(s) for s in sw]

    out = {
        "ln_from_fr": False,
        "meta": {
            "sw": {
                "name": "Powerball Lottery",
                "title": "Powerball Lottery: Mashine ya Ndoto ya Amerika",
                "description": (
                    "Elewa Powerball: asili, muundo wa jackpot, rollover, Power Play "
                    "na kisaikolojia kinachofanya watu waendelee kucheza."
                ),
            },
            "ln": {
                "name": "Powerball Lottery",
                "title": "Powerball Lottery: Machine ya Rêve ya Amerika",
                "description": (
                    "Yebisa Powerball: origine, système ya jackpot, rollover, Power Play "
                    "mpe psychology oyo esalisaka bato bakobeta."
                ),
            },
        },
        "pairs": {"sw": [[a, b] for a, b in zip(seg, sw)], "ln": [[a, b] for a, b in zip(seg, ln)]},
    }
    path = TOOLS / "casino_sw_ln_data" / "casino_27.json"
    path.write_text(json.dumps(out, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Written {path} ({len(seg)} pairs)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
