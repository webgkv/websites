#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Generate games_{id}.json sw/ln data from EN exports (+ FR reference for ln polish).
Editorial baseline — run apply + manual QA on key pages after import.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from ln_quality_replacements import polish_ln  # noqa: E402

DL = Path("/home/lenovo/Downloads/02/chickenroad-games")
OUT = TOOLS / "games_sw_ln_data"

# Swahili phrase-level replacements (longest first) for game review copy
SW_PHRASES: list[tuple[str, str]] = [
    ("Chicken Road 2.0", "Chicken Road 2.0"),
    ("Chicken Road 2", "Chicken Road 2"),
    ("Chicken Road", "Chicken Road"),
    ("InOut Games", "InOut Games"),
    ("Provably Fair", "Provably Fair"),
    ("Free Spins", "Free Spins"),
    ("Free Runs", "Free Runs"),
    ("Cash Out", "Cash Out"),
    ("Sticky Wild", "Sticky Wild"),
    ("Hardcore", "Hardcore"),
    ("before you play for real money", "kabla ya kucheza kwa pesa halisi"),
    ("before playing with real balance", "kabla ya kucheza na salio halisi"),
    ("before playing for real money", "kabla ya kucheza kwa pesa halisi"),
    ("Demo mode is the best place to start", "Demo ndiyo mahali bora pa kuanza"),
    ("demo mode is available", "demo inapatikana"),
    ("Can I play", "Naweza kucheza"),
    ("Can I open", "Naweza kufungua"),
    ("Can I try", "Naweza kujaribu"),
    ("Does ", "Je, "),
    ("Is ", "Je, "),
    ("What is", "Ni nini"),
    ("What kind of", "Ni aina gani ya"),
    ("How much", "Kiasi gani"),
    ("Yes.", "Ndiyo."),
    ("Yes,", "Ndiyo,"),
    ("Yes ", "Ndiyo "),
    ("No.", "Hapana."),
    ("No,", "Hapana,"),
    ("players", "wachezaji"),
    ("player", "mchezaji"),
    ("game", "mchezo"),
    ("games", "michezo"),
    ("casino", "kasino"),
    ("demo", "demo"),
    ("mobile", "simu"),
    ("download", "pakua"),
    ("review", "mapitio"),
    ("rules", "sheria"),
    ("where to play", "mahali pa kucheza"),
    ("real money", "pesa halisi"),
    ("virtual credits", "mikopo ya kawaida"),
    ("difficulty levels", "viwango vya ugumu"),
    ("Difficulty", "Ugumu"),
    ("Beginners", "Wanaoanza"),
    ("Maximum Win", "Ushindi wa Juu"),
    ("Max win", "Ushindi wa juu"),
    ("RTP", "RTP"),
    ("FAQ", "FAQ"),
    ("Feature", "Kipengele"),
    ("Details", "Maelezo"),
    ("Best For", "Bora kwa"),
    ("Risk", "Hatari"),
    ("Easy", "Easy"),
    ("Medium", "Medium"),
    ("Hard", "Hard"),
]

LN_PHRASES: list[tuple[str, str]] = [
    ("Chicken Road 2.0", "Chicken Road 2.0"),
    ("Chicken Road 2", "Chicken Road 2"),
    ("Chicken Road", "Chicken Road"),
    ("InOut Games", "InOut Games"),
    ("Provably Fair", "Provably Fair"),
    ("Free Spins", "Free Spins"),
    ("Free Runs", "Free Runs"),
    ("Cash Out", "Cash Out"),
    ("Sticky Wild", "Sticky Wild"),
    ("Hardcore", "Hardcore"),
    ("players", "basali"),
    ("player", "mosali"),
    ("games", "ba lisano"),
    ("game", "lisano"),
    ("casino", "casino"),
    ("demo", "demo"),
    ("download", "kozua"),
    ("review", "tala"),
    ("rules", "mibeko"),
    ("real money", "mbongo ya solo"),
    ("virtual credits", "ba crédit ya virtual"),
    ("difficulty", "difficulty"),
    ("Beginners", "Basali ya sika"),
    ("Maximum Win", "Gain ya likolo"),
    ("FAQ", "FAQ"),
    ("Feature", "Eloko"),
    ("Details", "Ba detail"),
    ("Best For", "Malamu mpo na"),
    ("Risk", "Riski"),
    ("Easy", "Easy"),
    ("Medium", "Medium"),
    ("Hard", "Hard"),
]


def translate_phrases(text: str, pairs: list[tuple[str, str]]) -> str:
    out = text
    for en, loc in sorted(pairs, key=lambda x: len(x[0]), reverse=True):
        out = out.replace(en, loc)
    return out


def translate_sw(text: str) -> str:
    return translate_phrases(text, SW_PHRASES)


def translate_ln(text: str) -> str:
    t = translate_phrases(text, LN_PHRASES)
    return polish_ln(t)


def truncate_meta(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def build_meta(en_loc: dict) -> dict:
    name = en_loc.get("name") or ""
    title = en_loc.get("title") or name
    desc = en_loc.get("description") or ""
    sw_t, sw_d = truncate_meta(translate_sw(title), translate_sw(desc))
    ln_t, ln_d = truncate_meta(translate_ln(title), translate_ln(desc))
    return {
        "sw": {"name": translate_sw(name) if name != "Chicken Road" else name, "title": sw_t, "description": sw_d},
        "ln": {"name": translate_ln(name) if "Chicken" in name else name, "title": ln_t, "description": ln_d},
    }


def build_pairs(segments: list[str]) -> dict:
    sw_pairs: list[list[str]] = []
    ln_pairs: list[list[str]] = []
    for seg in segments:
        sw = translate_sw(seg)
        ln = translate_ln(seg)
        if sw != seg:
            sw_pairs.append([seg, sw])
        elif len(seg) > 3:
            sw_pairs.append([seg, sw])  # keep parity even if unchanged Latin
        if ln != seg:
            ln_pairs.append([seg, ln])
        elif len(seg) > 3:
            ln_pairs.append([seg, ln])
    return {"sw": sw_pairs, "ln": ln_pairs}


def generate(entity_id: int) -> None:
    path = DL / f"seo-games-{entity_id}-full.json"
    data = json.loads(path.read_text(encoding="utf-8"))
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    segs = extract_segments(en.get("content") or "")
    payload = {"meta": build_meta(en), "pairs": build_pairs(segs)}
    out = OUT / f"games_{entity_id}.json"
    out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"games#{entity_id}: {len(segs)} segments -> {out.name}")


def main() -> int:
    ids = [int(x) for x in sys.argv[1:]] if len(sys.argv) > 1 else list(range(1, 13))
    OUT.mkdir(parents=True, exist_ok=True)
    for eid in ids:
        if eid == 8 and (OUT / "games_8.json").exists():
            print("games#8: keep hand-authored games_8.json")
            continue
        generate(eid)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
