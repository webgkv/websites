#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build pages_1/4/5/6.json sw/ln editorial data for Ice Fish (ice-fish.run)."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "pages_sw_ln_data"
SEG = Path.home() / "Downloads/02/ice-fish/pages"
CHICKEN = TOOLS.parents[1] / "chickenroad" / "tools"
sys.path.insert(0, str(CHICKEN))
from ln_quality_replacements import polish_ln  # noqa: E402

META = {
    1: {
        "sw": {
            "name": "Nyumbani",
            "title": "Ice Fishing Mtandaoni: Jinsi Inavyofanya Kazi, Demo na Pesa Halisi",
            "description": "Jifunze jinsi Ice Fishing inavyofanya kazi, kwa nini demo ni muhimu, na jinsi ya kucheza kwa uwajibikaji kabla ya raundi za pesa halisi.",
        },
        "ln": {
            "name": "Liboso",
            "title": "Ice Fishing na Internet: Ndenge, Demo mpe Mbongo ya Solo",
            "description": "Yebisa ndenge Ice Fishing esalaka, mpo nini demo ezali important mpe ndenge ya kobeta na responsabilité liboso ya ba tour na mbongo ya solo.",
        },
    },
    4: {
        "sw": {
            "name": "Demo",
            "title": "Ice Fish Demo Bure | Jaribu Mchezo Mtandaoni",
            "description": "Cheza Ice Fish demo bure na salio la kawaida. Jifunze mwendo, jaribu demo na uone tofauti na mchezo wa pesa halisi.",
        },
        "ln": {
            "name": "Demo",
            "title": "Ice Fish Demo ya Ofele | Kobeta na Internet",
            "description": "Betta Ice Fish demo na solde virtuel. Yekola tempo, meka demo mpe bona ndenge ezali différent na mbongo ya solo.",
        },
    },
    5: {
        "sw": {
            "name": "Pakua",
            "title": "Pakua App ya Ice Fish: Android, iPhone na PC",
            "description": "Pakua demo ya Ice Fish kwa Android na iPhone, angalia mahitaji ya mfumo, na jifunze njia salama ya ufikiaji kabla ya pesa halisi.",
        },
        "ln": {
            "name": "Télécharger",
            "title": "Télécharger Ice Fish: Android, iPhone mpe PC",
            "description": "Télécharger demo Ice Fish mpo na Android mpe iPhone, tala ba prérequis mpe pona nzela ya malamu liboso ya mbongo ya solo.",
        },
    },
    6: {
        "sw": {
            "name": "Mikakati",
            "title": "Mikakati ya Ice Fish: Jinsi ya Kucheza kwa Akili",
            "description": "Mikakati ya Ice Fish: jaribu demo, msingi wa RTP, uangalifu wa mwendo, makosa ya kawaida na uchezaji wenye uwajibikaji bila hadithi za ushindi.",
        },
        "ln": {
            "name": "Ba Strategy",
            "title": "Ba Strategy ya Ice Fish: Kobeta na Lobo Malamu",
            "description": "Strategy ya Ice Fish: test na Demo, base ya RTP, tempo ya lisano, ba erreur ya courant mpe kobeta na responsabilité, sans ba mythe ya gain.",
        },
    },
}


def load_segs(page_id: int, lang: str) -> list[str]:
    return json.loads((SEG / f"pages-{page_id}-{lang}-segments.json").read_text(encoding="utf-8"))


def truncate_meta(meta: dict[str, dict[str, str]]) -> dict[str, dict[str, str]]:
    out: dict[str, dict[str, str]] = {}
    for lang, block in meta.items():
        title = block["title"]
        desc = block["description"]
        if len(title) > 70:
            title = title[:67].rstrip() + "..."
        if len(desc) > 160:
            desc = desc[:157].rstrip() + "..."
        out[lang] = {**block, "title": title, "description": desc}
    return out


def build_page(page_id: int, sw: list[str], ln: list[str]) -> dict:
    en = load_segs(page_id, "en")
    fr = load_segs(page_id, "fr")
    if len(en) != len(sw):
        raise SystemExit(f"pages_{page_id}: sw {len(sw)} != en {len(en)}")
    if len(fr) != len(ln):
        raise SystemExit(f"pages_{page_id}: ln {len(ln)} != fr {len(fr)}")
    ln = [polish_ln(x) for x in ln]
    return {
        "ln_from_fr": True,
        "meta": truncate_meta(META[page_id]),
        "pairs": {
            "sw": [[a, b] for a, b in zip(en, sw, strict=True)],
            "fr_ln": [[a, b] for a, b in zip(fr, ln, strict=True)],
        },
    }


# Import translation arrays from companion module (keeps this file readable).
from ice_fish_pages_sw_ln_translations import (  # noqa: E402
    P1_LN,
    P1_SW,
    P4_LN,
    P4_SW,
    P5_LN,
    P5_SW,
    P6_LN,
    P6_SW,
)


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    pages = {
        1: (P1_SW, P1_LN),
        4: (P4_SW, P4_LN),
        5: (P5_SW, P5_LN),
        6: (P6_SW, P6_LN),
    }
    for pid, (sw, ln) in pages.items():
        payload = build_page(pid, sw, ln)
        out = OUT / f"pages_{pid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Wrote {out} sw={len(sw)} fr_ln={len(ln)}")
        for lang in ("sw", "ln"):
            m = payload["meta"][lang]
            print(f"  {lang} title={len(m['title'])} desc={len(m['description'])}")


if __name__ == "__main__":
    main()
