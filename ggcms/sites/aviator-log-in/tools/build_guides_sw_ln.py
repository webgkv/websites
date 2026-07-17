#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate guides_sw_ln_data/guides_{1..5}.json from segment exports."""

from __future__ import annotations

import json
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DATA_DIR = TOOLS / "guides_sw_ln_data"
SEG_ROOT = Path.home() / "Downloads/02/aviator-guides"

# Import translation lists (generated alongside this file)
from guides_sw_ln_translations import (  # noqa: E402
    EXTRA_FR_LN,
    EXTRA_SW,
    LN,
    META,
    SW,
)


def build(entity_id: int) -> dict:
    en_segs = json.loads((SEG_ROOT / f"guides-{entity_id}-en-segments.json").read_text(encoding="utf-8"))
    fr_segs = json.loads((SEG_ROOT / f"guides-{entity_id}-fr-segments.json").read_text(encoding="utf-8"))
    sw_targets = SW[entity_id]
    ln_targets = LN[entity_id]
    if len(en_segs) != len(sw_targets):
        raise SystemExit(f"guides_{entity_id}: sw count {len(sw_targets)} != en {len(en_segs)}")
    if len(fr_segs) != len(ln_targets):
        raise SystemExit(f"guides_{entity_id}: ln count {len(ln_targets)} != fr {len(fr_segs)}")
    sw_pairs = [[a, b] for a, b in zip(en_segs, sw_targets, strict=True)]
    fr_ln_pairs = [[a, b] for a, b in zip(fr_segs, ln_targets, strict=True)]
    sw_pairs.extend(EXTRA_SW.get(entity_id, []))
    fr_ln_pairs.extend(EXTRA_FR_LN.get(entity_id, []))
    return {
        "ln_from_fr": True,
        "meta": META[entity_id],
        "pairs": {"sw": sw_pairs, "fr_ln": fr_ln_pairs},
    }


def main() -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    for eid in range(1, 6):
        payload = build(eid)
        out = DATA_DIR / f"guides_{eid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Wrote {out} sw={len(payload['pairs']['sw'])} fr_ln={len(payload['pairs']['fr_ln'])}")
        for lang in ("sw", "ln"):
            m = META[eid][lang]
            print(f"  {lang} title={len(m['title'])} desc={len(m['description'])}")


if __name__ == "__main__":
    main()
