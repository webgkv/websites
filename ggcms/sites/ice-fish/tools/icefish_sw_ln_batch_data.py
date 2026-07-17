#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Sw/LN editorial translation data for Ice Fish guides#9, games#13/#14, blog#5."""

from __future__ import annotations

import json
from pathlib import Path

GUIDES_9_META = {
    "sw": {
        "name": "Jinsi ya Kucheza Ice Fish na InOut Games",
        "title": "Jinsi ya Kucheza Ice Fish na InOut Games",
        "description": "Jinsi ya kucheza Ice Fish (InOut): msingi wa wheel, dau za anchor na fish, bonus, multipliers, demo na simu kwa wanaoanza.",
    },
    "ln": {
        "name": "Ndenge ya kobeta Ice Fish na InOut Games",
        "title": "Ndenge ya kobeta Ice Fish na InOut Games",
        "description": "Ndenge ya kobeta Ice Fish (InOut): wheel, paris anchor mpe fish, bonus, multipliers, demo mpe mobile mpo ba débutant.",
    },
}

GAMES_13_META = {
    "sw": {
        "name": "Ice Fish na InOut Games",
        "title": "Ice Fish na InOut Games: Roulette ya Arctic",
        "description": "Ice Fish na InOut Games: mchezo wa wheel wa papo hapo, mandhari ya Arctic, bonus za fish, RTP 95.5%, Provably Fair na hadi 5,000x.",
    },
    "ln": {
        "name": "Ice Fish na InOut Games",
        "title": "Ice Fish na InOut Games: Roulette ya Arctic",
        "description": "Ice Fish na InOut Games: lisano ya wheel instant, thème Arctic, bonus fish, RTP 95.5%, Provably Fair mpe kino ya 5,000x.",
    },
}

GAMES_14_META = {
    "sw": {
        "name": "Ice Fishing na Evolution Gaming",
        "title": "Ice Fishing na Evolution: Live Game Show ya Arctic",
        "description": "Ice Fishing na Evolution: live game show ya Arctic, wheel ya sehemu 53, bonus za uvuvi, RTP ya Leaf 97.10% na ushindi hadi 5,000x.",
    },
    "ln": {
        "name": "Ice Fishing na Evolution Gaming",
        "title": "Ice Fishing na Evolution: Live Game Show ya Arctic",
        "description": "Ice Fishing na Evolution: live game show Arctic, wheel ya biteni 53, bonus ya pêche, RTP Leaf 97.10% mpe gain ti 5,000x.",
    },
}

BLOG_5_META = {
    "sw": {
        "name": "Historia nyuma ya mafanikio ya Ice Fishing — mawazo...",
        "title": "Historia ya Ice Fishing: Kutoka Dream Catcher Hadi Leo",
        "description": "Jinsi Ice Fishing ilivyokuwa speed game show ya kwanza ya Evolution: Dream Catcher, Crazy Time, virtual wheel na maana yake kwa live casino.",
    },
    "ln": {
        "name": "Lisolo ya suksè ya Ice Fishing — ba idée...",
        "title": "Lisolo ya Ice Fishing: Kobanda Dream Catcher tii lelo",
        "description": "Ndenge Ice Fishing ebandi speed game show ya liboso ya Evolution: Dream Catcher, Crazy Time, virtual wheel mpe ntina na live casino.",
    },
}

GUIDES_9_EXTRA_SW = [["Yes.", "Ndiyo."]]
GUIDES_9_EXTRA_FR_LN = [["Oui.", "Ee."]]
BLOG_5_EXTRA_SW = [["Feature", "Kipengele"]]
BLOG_5_EXTRA_FR_LN: list[list[str]] = []

TRANSLATIONS_DIR = Path(__file__).resolve().parent / "translations"


def load_translations(name: str) -> dict[str, list[str]]:
    path = TRANSLATIONS_DIR / f"{name}.json"
    if not path.is_file():
        raise SystemExit(f"missing translations: {path}")
    data = json.loads(path.read_text(encoding="utf-8"))
    return {"sw": data["sw"], "ln": data["ln"]}
