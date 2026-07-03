#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Merge locale packs into string table and rebuild home_cluster_sections_i18n.py."""

from __future__ import annotations

import importlib.util
import json
import subprocess
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent

LINKS = {
    "__INOUT__": '<noads><a href="/en/games/inout-games/">InOut Games</a></noads>',
    "__GAMES__": '<noads><a href="/en/games/">Chicken Road games</a></noads>',
    "__CR__": '<noads><a href="/en/games/chicken-road/">Chicken Road</a></noads>',
    "__CR2__": '<noads><a href="/en/games/chicken-road2/">Chicken Road 2.0</a></noads>',
    "__CR_ORIG__": '<noads><a href="/en/games/chicken-road/">the original Chicken Road game</a></noads>',
    "__CR2_REV__": '<noads><a href="/en/games/chicken-road2/">Chicken Road 2.0 review</a></noads>',
    "__CASINOS__": '<noads><a href="/en/casinos/">Chicken Road casino</a></noads>',
    "__DEMO__": '<noads><a href="/demo/">Chicken Road demo</a></noads>',
    "__DEMO_GUIDE__": '<noads><a href="/demo/">Chicken Road demo guide</a></noads>',
    "__DEMO_MODE__": '<noads><a href="/demo/">demo mode</a></noads>',
    "__DEMO_USUALLY__": '<noads><a href="/demo/">usually available</a></noads>',
    "__DOWNLOAD__": '<noads><a href="/en/download/">Chicken Road download</a></noads>',
    "__DL_GUIDE__": '<noads><a href="/en/download/">download guide</a></noads>',
    "__DL_FULL__": '<noads><a href="/en/download/">Chicken Road download guide</a></noads>',
    "__STRATEGIES__": '<noads><a href="/en/guides/how-to-win/chicken-road-strategy-guide/">Chicken Road strategies</a></noads>',
    "__CASHOUT__": '<noads><a href="/en/guides/signals/chicken-road-cash-out-guide/">when to cash out</a></noads>',
    "__STRATEGY__": '<noads><a href="/en/guides/how-to-win/chicken-road-strategy-guide/">strategy guide</a></noads>',
}


def sub(text: str) -> str:
    for k, v in LINKS.items():
        text = text.replace(k, v)
    return text


def block_to_keys(block: dict) -> dict:
    a, w, f, s, b, d, t, sp, fq = (
        block["app"],
        block["works"],
        block["features"],
        block["steps"],
        block["batting"],
        block["demo"],
        block["tips"],
        block["specs"],
        block["faq"],
    )
    return {
        "chickenroad-app": {
            "h2": [a["h2"]],
            "p": [sub(x) for x in a["p"] + a["p2"]],
            "img_alt": [a["img_alt"]],
        },
        "game-works": {
            "h2": [w["h2"]],
            "p": [sub(x) for x in w["p"]],
            "img_alt": [w["img_alt"]],
        },
        "features": {
            "h2": [f["h2"]],
            "p": [sub(x) for x in f["p"]] + [f["hooks_title"], sub(f["p_footer"])],
            "li": f["hooks"],
            "th": [f["tbl"]["metric"], f["tbl"]["orig"], f["tbl"]["v2"]],
            "td": [
                f["tbl"]["rtp_lbl"],
                "98%",
                "95.5%",
                f["tbl"]["released_lbl"],
                f["tbl"]["rel_o"],
                f["tbl"]["rel_v"],
                f["tbl"]["theme_lbl"],
                f["tbl"]["theme_o"],
                f["tbl"]["theme_v"],
                f["tbl"]["max_lbl"],
                "~$10,000",
                "~$20,000",
                f["tbl"]["best_lbl"],
                f["tbl"]["best_o"],
                f["tbl"]["best_v"],
            ],
        },
        "demo-steps": {
            "h2": [s["h2"]],
            "p": [s["intro"], s["s1p"], s["s2p"], s["s3p"], f"<strong>{s['check']}</strong>"],
            "h3": [s["s1h"], s["s2h"], s["s3h"]],
            "li": [sub(x) for x in s["ol"]],
            "img_alt": [s["alt1"], s["alt2"], s["alt3"]],
        },
        "batting": {
            "h2": [b["h2"]],
            "p": [sub(x) for x in b["p"]],
            "btn": [b["btn"]],
            "img_alt": [b["img_alt"]],
        },
        "demo-vs-real": {"h2": [d["h2"]], "p": [sub(x) for x in d["p"]]},
        "tips": {"h2": [t["h2"]], "p": [sub(x) for x in t["p"]]},
        "game-specs": {
            "h2": [sp["h2"]],
            "th": [sp["th1"], sp["th2"]],
            "td": [v for pair in sp["rows"] for v in (pair[0], sub(pair[1]))],
        },
        "faq": {
            "h2": [fq["h2"]],
            "p": [sub(a) for _, a in fq["items"]],
            "summary": [q for q, _ in fq["items"]],
        },
    }


def align_pairs(en_block: dict, loc_block: dict) -> dict[str, str]:
    pairs: dict[str, str] = {}
    for field in en_block:
        en_list = en_block.get(field) or []
        loc_list = loc_block.get(field) or []
        if not isinstance(en_list, list):
            en_list = [en_list]
        if not isinstance(loc_list, list):
            loc_list = [loc_list]
        for en, loc in zip(en_list, loc_list):
            if en and loc:
                pairs[en] = loc
    return pairs


def load_packs() -> dict:
  packs: dict = {}
  for lang in ["de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]:
    path = TOOLS / "i18n_packs" / f"{lang}.py"
    if not path.exists():
      continue
    spec = importlib.util.spec_from_file_location(lang, path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    packs[lang] = mod.PACK
  return packs


def main() -> None:
    en_keys = json.loads(Path("/tmp/en_keys.json").read_text(encoding="utf-8"))
    table_path = TOOLS / "string_translation_table.json"
    table = json.loads(table_path.read_text(encoding="utf-8"))

    packs = load_packs()
    for lang, pack in packs.items():
        keys = block_to_keys(pack)
        for sid in en_keys:
            if sid in ("lead", "where-to-play") or sid not in keys:
                continue
            for en, loc in align_pairs(en_keys[sid], keys[sid]).items():
                table.setdefault(en, {})[lang] = loc

    table_path.write_text(json.dumps(table, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Merged {len(packs)} packs into string table")

    subprocess.check_call([sys.executable, str(TOOLS / "build_string_map.py")], cwd=TOOLS)
    subprocess.check_call([sys.executable, str(TOOLS / "apply_cluster_i18n.py")], cwd=TOOLS)

    subprocess.check_call(
        [
            sys.executable,
            "-c",
            """
from home_cluster_sections_i18n import SECTIONS_I18N
langs = ['fr','de','es','hi','pt','ru','ar','az','bn','it','nl','pl','vi','ua','ro']
sections = ['lead','chickenroad-app','game-works','features','demo-steps','batting','demo-vs-real','where-to-play','tips','game-specs','faq']
for lang in langs:
    assert lang in SECTIONS_I18N, lang
    for s in sections:
        assert SECTIONS_I18N[lang].get(s), f'{lang}/{s}'
print('OK', len(langs), 'langs')
""",
        ],
        cwd=TOOLS,
    )


if __name__ == "__main__":
    main()
