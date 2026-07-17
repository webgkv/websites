#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial casino_10/11.json sw/ln translation data."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "casino_sw_ln_data"
DL = Path("/home/lenovo/Downloads/02/chickenroad-casinos")

sys.path.insert(0, str(TOOLS))
from ln_quality_replacements import polish_ln  # noqa: E402
from casino_data_10 import LN10, SW10  # noqa: E402
from casino_data_11 import LN11, SW11  # noqa: E402


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


def build_casino_10() -> None:
    en = load_segs("casino-10-en-segments.json")
    fr = load_segs("casino-10-fr-segments.json")
    ln = [polish_ln(t) for t in LN10]
    assert len(en) == len(SW10) == 74
    assert len(fr) == len(ln) == 75
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye Kasino ya 1win: Cheza na Bonus",
        "Cheza Chicken Road kwenye 1win: bonus ya usajili, RTP, demo na vidokezo vya Cash Out kwa mchezo wa Chicken Road wa 1win.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na 1win Casino: Bina mpe Bonus",
        "Bina Chicken Road na 1win: bonus ya kohyola compte, RTP, demo mpe ba conseil ya Cash Out mpo na lisano ya Chicken Road ya 1win.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {"name": "1WIN - Chicken Road", "title": sw_title, "description": sw_desc},
            "ln": {"name": "1WIN - Chicken Road", "title": ln_title, "description": ln_desc},
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW10),
            "fr_ln": pairs_from_lists(fr, ln),
        },
    }
    (OUT / "casino_10.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_10.json: {len(SW10)} sw + {len(ln)} fr_ln")


def build_casino_11() -> None:
    en = load_segs("casino-11-en-segments.json")
    fr = load_segs("casino-11-fr-segments.json")
    ln = [polish_ln(t) for t in LN11]
    assert len(en) == len(SW11) == 81
    assert len(fr) == len(ln) == 80
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye Kasino ya 1xBet: Cheza na Bonus",
        "Cheza Chicken Road kwenye 1xBet: bonus ya kukaribisha, demo, RTP na strategy ya Cash Out kwa mchezo wa Chicken Road wa 1xBet.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na 1xBet Casino: Bina mpe Bonus",
        "Bina Chicken Road na 1xBet: bonus ya boyei, demo, RTP mpe strategy ya Cash Out mpo na lisano ya Chicken Road ya 1xBet.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {"name": "1xBet - Chicken Road", "title": sw_title, "description": sw_desc},
            "ln": {"name": "1xBet - Chicken Road", "title": ln_title, "description": ln_desc},
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW11),
            "fr_ln": pairs_from_lists(fr, ln),
        },
    }
    (OUT / "casino_11.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_11.json: {len(SW11)} sw + {len(ln)} fr_ln")


def main() -> int:
    build_casino_10()
    build_casino_11()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
