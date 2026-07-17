#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build Ice Fish casino_articles sw/ln JSON from segment exports."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "casino_sw_ln_data"
SEG = Path("/home/lenovo/Downloads/02/ice-fish/casinos")
CHICKEN = TOOLS.parents[1] / "chickenroad" / "tools"

sys.path.insert(0, str(TOOLS))
sys.path.insert(0, str(CHICKEN))

from ln_quality_replacements import polish_ln  # noqa: E402
from casino_data_10 import LN10, SW10  # noqa: E402
from casino_data_11 import LN11, SW11  # noqa: E402
from icefish_casino_sw_ln_18 import LN18, SW18  # noqa: E402
from icefish_casino_sw_ln_24 import LN24, SW24  # noqa: E402
from icefish_casino_sw_ln_25 import LN25, SW25  # noqa: E402
from icefish_casino_sw_ln_26 import LN26, SW26  # noqa: E402


def load_segs(name: str) -> list[str]:
    return json.loads((SEG / name).read_text(encoding="utf-8"))


def adapt_cr(text: str) -> str:
    return text.replace("Chicken Road", "Ice Fish")


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    assert len(keys) == len(vals), f"{len(keys)} vs {len(vals)}"
    return [[k, v] for k, v in zip(keys, vals)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


META: dict[int, dict] = {
    10: {
        "sw": {
            "name": "1WIN - Ice Fish",
            "title": "Ice Fish kwenye Kasino ya 1win: Cheza na Bonus",
            "description": "Cheza Ice Fish kwenye 1win: bonus ya usajili, demo, mbadala na vidokezo vya Cash Out kwa mchezo wa Ice Fish wa 1win.",
        },
        "ln": {
            "name": "1WIN - Ice Fish",
            "title": "Ice Fish na 1win Casino: Bina mpe Bonus",
            "description": "Bina Ice Fish na 1win: bonus ya bienvenue, demo, ba alternative mpe ba conseil ya Cash Out mpo na lisano ya Ice Fish ya 1win.",
        },
    },
    11: {
        "sw": {
            "name": "1xBet - Ice Fish",
            "title": "Ice Fish kwenye Kasino ya 1xBet: Cheza na Bonus",
            "description": "Cheza Ice Fish kwenye 1xBet: bonus ya kukaribisha, demo, RTP na strategy ya Cash Out kwa mchezo wa Ice Fish wa 1xBet.",
        },
        "ln": {
            "name": "1xBet - Ice Fish",
            "title": "Ice Fish na 1xBet Casino: Bina mpe Bonus",
            "description": "Bina Ice Fish na 1xBet: bonus ya boyei, demo, RTP mpe strategy ya Cash Out mpo na lisano ya Ice Fish ya 1xBet.",
        },
    },
    18: {
        "sw": {
            "name": "MOSTBET — Ice Fish",
            "title": "Ice Fish kwenye MOSTBET: Cheza na Bonus",
            "description": "Cheza Ice Fish kwenye MOSTBET: bonus, RTP, demo, simu na mkakati wa Cash Out kwa mchezo wa Ice Fish.",
        },
        "ln": {
            "name": "MOSTBET — Ice Fish",
            "title": "Ice Fish na MOSTBET: Kobeta mpe Bonus",
            "description": "Bina Ice Fish na MOSTBET: bonus, RTP, demo, mobile mpe strategy ya Cash Out mpo na lisano ya Ice Fish.",
        },
    },
    24: {
        "sw": {
            "name": "Fan-Sport - Ice Fish & Ice Fishing",
            "title": "Ice Fish na Ice Fishing kwenye Fan-Sport: Mwongozo",
            "description": "Cheza Ice Fish na Ice Fishing kwenye Fan-Sport: tofauti kati ya InOut na Evolution, demo, simu na mwongozo wa kupata michezo.",
        },
        "ln": {
            "name": "Fan-Sport - Ice Fish & Ice Fishing",
            "title": "Ice Fish mpe Ice Fishing na Fan-Sport: Guide",
            "description": "Bina Ice Fish mpe Ice Fishing na Fan-Sport: différence InOut mpe Evolution, demo, mobile mpe guide mpo na kozua ba lisano.",
        },
    },
    25: {
        "sw": {
            "name": "Jack-Pot — Ice Fish",
            "title": "Ice Fish kwenye Jack-Pot Casino: Cheza na Bonus",
            "description": "Cheza Ice Fish kwenye Jack-Pot: InOut Games, demo, simu, tofauti na Ice Fishing na mwongozo wa kupata mchezo.",
        },
        "ln": {
            "name": "Jack-Pot — Ice Fish",
            "title": "Ice Fish na Jack-Pot Casino: Kobeta mpe Bonus",
            "description": "Bina Ice Fish na Jack-Pot: InOut Games, demo, mobile, différence na Ice Fishing mpe ndenge ya kozua lisano.",
        },
    },
    26: {
        "sw": {
            "name": "BC.Game - Ice Fish & Ice Fishing",
            "title": "Ice Fish na Ice Fishing kwenye BC.Game: Mwongozo",
            "description": "Cheza Ice Fish na Ice Fishing kwenye BC.Game: crypto bonus, demo, simu, InOut na Evolution kwenye kasino moja.",
        },
        "ln": {
            "name": "BC.Game - Ice Fish & Ice Fishing",
            "title": "Ice Fish mpe Ice Fishing na BC.Game: Guide",
            "description": "Bina Ice Fish mpe Ice Fishing na BC.Game: crypto bonus, demo, mobile, InOut mpe Evolution na casino moko.",
        },
    },
}


def build(entity_id: int, sw_vals: list[str], ln_vals: list[str]) -> dict:
    en = load_segs(f"casino-{entity_id}-en-segments.json")
    fr = load_segs(f"casino-{entity_id}-fr-segments.json")
    sw = [adapt_cr(t) if entity_id in (10, 11, 18) else t for t in sw_vals]
    ln = [polish_ln(adapt_cr(t) if entity_id in (10, 11, 18) else t) for t in ln_vals]
    assert len(en) == len(sw), f"casino_{entity_id} sw {len(sw)} != en {len(en)}"
    assert len(fr) == len(ln), f"casino_{entity_id} ln {len(ln)} != fr {len(fr)}"
    meta = dict(META[entity_id])
    for lang in ("sw", "ln"):
        title, desc = truncate(meta[lang]["title"], meta[lang]["description"])
        meta[lang]["title"] = title
        meta[lang]["description"] = desc
    return {
        "ln_from_fr": True,
        "meta": meta,
        "pairs": {
            "sw": pairs_from_lists(en, sw),
            "fr_ln": pairs_from_lists(fr, ln),
        },
    }


def main() -> int:
    OUT.mkdir(parents=True, exist_ok=True)
    specs = [
        (10, SW10, LN10),
        (11, SW11, LN11),
        (18, SW18, LN18),
        (24, SW24, LN24),
        (25, SW25, LN25),
        (26, SW26, LN26),
    ]
    for eid, sw, ln in specs:
        payload = build(eid, sw, ln)
        out = OUT / f"casino_{eid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Wrote {out.name} sw={len(payload['pairs']['sw'])} fr_ln={len(payload['pairs']['fr_ln'])}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
