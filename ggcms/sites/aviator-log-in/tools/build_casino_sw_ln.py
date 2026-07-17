#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate casino_sw_ln_data/casino_{10..17}.json from segment exports."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DATA_DIR = TOOLS / "casino_sw_ln_data"
SEG_ROOT = Path.home() / "Downloads/02/aviator-casinos"
CHICKEN = TOOLS.parents[1] / "chickenroad" / "tools"
sys.path.insert(0, str(CHICKEN))

from ln_quality_replacements import polish_ln  # noqa: E402

from casino_sw_ln_translations import LN, META, SW  # noqa: E402


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def build(entity_id: int) -> dict:
    en_segs = json.loads((SEG_ROOT / f"casino-{entity_id}-en-segments.json").read_text(encoding="utf-8"))
    fr_segs = json.loads((SEG_ROOT / f"casino-{entity_id}-fr-segments.json").read_text(encoding="utf-8"))
    sw_targets = SW[entity_id]
    ln_targets = [polish_ln(t) for t in LN[entity_id]]
    if len(en_segs) != len(sw_targets):
        raise SystemExit(f"casino_{entity_id}: sw count {len(sw_targets)} != en {len(en_segs)}")
    if len(fr_segs) != len(ln_targets):
        raise SystemExit(f"casino_{entity_id}: ln count {len(ln_targets)} != fr {len(fr_segs)}")
    meta = dict(META[entity_id])
    for lang in ("sw", "ln"):
        title, desc = truncate(meta[lang]["title"], meta[lang]["description"])
        meta[lang]["title"] = title
        meta[lang]["description"] = desc
    return {
        "ln_from_fr": True,
        "meta": meta,
        "pairs": {
            "sw": [[a, b] for a, b in zip(en_segs, sw_targets, strict=True)],
            "fr_ln": [[a, b] for a, b in zip(fr_segs, ln_targets, strict=True)],
        },
    }


def main() -> int:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    for eid in range(10, 18):
        payload = build(eid)
        out = DATA_DIR / f"casino_{eid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(
            f"Wrote {out.name} sw={len(payload['pairs']['sw'])} "
            f"fr_ln={len(payload['pairs']['fr_ln'])}"
        )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
