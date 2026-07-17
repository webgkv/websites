#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build casino_18..25.json sw/ln editorial data for Aviator casino articles."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DATA_DIR = TOOLS / "casino_sw_ln_data"
DL = Path("/home/lenovo/Downloads/02/aviator-casinos")

sys.path.insert(0, str(TOOLS))
sys.path.insert(0, str(TOOLS.parent.parent / "chickenroad" / "tools"))

from ln_quality_replacements import polish_ln  # noqa: E402
from casino_sw_ln_translations import LN, META, SW  # noqa: E402


def load_segs(entity_id: int, lang: str) -> list[str]:
    return json.loads((DL / f"casino-{entity_id}-{lang}-segments.json").read_text(encoding="utf-8"))


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    if len(keys) != len(vals):
        raise SystemExit(f"count mismatch: {len(keys)} keys vs {len(vals)} vals")
    return [[k, v] for k, v in zip(keys, vals, strict=True)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def build(entity_id: int) -> dict:
    en = load_segs(entity_id, "en")
    fr = load_segs(entity_id, "fr")
    sw_targets = SW[entity_id]
    ln_targets = LN[entity_id]
    if len(en) != len(sw_targets):
        raise SystemExit(f"casino_{entity_id}: sw {len(sw_targets)} != en {len(en)}")
    if len(fr) != len(ln_targets):
        raise SystemExit(f"casino_{entity_id}: ln {len(ln_targets)} != fr {len(fr)}")
    meta = dict(META[entity_id])
    for lang in ("sw", "ln"):
        t, d = truncate(meta[lang]["title"], meta[lang]["description"])
        meta[lang]["title"] = t
        meta[lang]["description"] = d
    return {
        "ln_from_fr": True,
        "meta": meta,
        "pairs": {
            "sw": pairs_from_lists(en, sw_targets),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, ln_targets)],
        },
    }


def main() -> int:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    for eid in range(18, 26):
        payload = build(eid)
        out = DATA_DIR / f"casino_{eid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(
            f"casino_{eid}.json: sw={len(payload['pairs']['sw'])} "
            f"fr_ln={len(payload['pairs']['fr_ln'])}"
        )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
