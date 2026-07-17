#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial casino_1..9.json sw/ln translation data for Aviator Log In."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
CHICKEN = TOOLS.parents[1] / "chickenroad" / "tools"
DL = Path("/home/lenovo/Downloads/02/aviator-casinos")
OUT = TOOLS / "casino_sw_ln_data"

sys.path.insert(0, str(TOOLS))
sys.path.append(str(CHICKEN))

from ln_quality_replacements import polish_ln  # noqa: E402

TRANS = TOOLS / "casino_sw_ln_translations"


def load_segs(name: str) -> list[str]:
    return json.loads((DL / name).read_text(encoding="utf-8"))


def load_cfg(entity_id: int) -> dict:
    return json.loads((TRANS / f"casino_{entity_id}.json").read_text(encoding="utf-8"))


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    if len(keys) != len(vals):
        raise ValueError(f"segment count mismatch: {len(keys)} keys vs {len(vals)} vals")
    return [[k, v] for k, v in zip(keys, vals)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def build(entity_id: int) -> None:
    cfg = load_cfg(entity_id)
    en = load_segs(f"casino-{entity_id}-en-segments.json")
    fr = load_segs(f"casino-{entity_id}-fr-segments.json")
    sw = cfg["sw"]
    ln = cfg["ln"]
    assert len(en) == len(sw), f"casino#{entity_id} EN/SW: {len(en)} vs {len(sw)}"
    assert len(fr) == len(ln), f"casino#{entity_id} FR/LN: {len(fr)} vs {len(ln)}"
    meta = cfg["meta"]
    for lang in ("sw", "ln"):
        title, desc = truncate(meta[lang]["title"], meta[lang]["description"])
        meta[lang]["title"] = title
        meta[lang]["description"] = desc
    payload = {
        "ln_from_fr": True,
        "meta": meta,
        "pairs": {
            "sw": pairs_from_lists(en, sw),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, ln)],
        },
    }
    OUT.mkdir(parents=True, exist_ok=True)
    out_path = OUT / f"casino_{entity_id}.json"
    out_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"casino_{entity_id}.json: {len(en)} sw + {len(fr)} fr_ln")


def main() -> int:
    for eid in range(1, 10):
        build(eid)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
