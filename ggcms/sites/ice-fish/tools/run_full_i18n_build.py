#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""One-shot: merge locale packs -> cluster_section_translations.json -> home_cluster_sections_i18n.py"""

from __future__ import annotations

import importlib.util
import json
import subprocess
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent


def _load_build_helpers():
    spec = importlib.util.spec_from_file_location("bd", TOOLS / "build_i18n_data.py")
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def _block_to_keys(fr, links):
    def sub(text):
        for k, v in links.items():
            text = text.replace(k, v)
        return text

    a, w, f, s, b, d, t, sp, fq = (
        fr["app"],
        fr["works"],
        fr["features"],
        fr["steps"],
        fr["batting"],
        fr["demo"],
        fr["tips"],
        fr["specs"],
        fr["faq"],
    )
    return {
        "ice-fish-app": {
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


LINKS = {
    "__INOUT__": '<noads><a href="/en/games/inout-games/">InOut Games</a></noads>',
    "__GAMES__": '<noads><a href="/en/games/">Ice Fish games</a></noads>',
    "__CR__": '<noads><a href="/en/games/ice-fish/">Ice Fish</a></noads>',
    "__CR2__": '<noads><a href="/en/games/ice-fish2/">Ice Fish 2.0</a></noads>',
    "__CR_ORIG__": '<noads><a href="/en/games/ice-fish/">the original Ice Fish game</a></noads>',
    "__CR2_REV__": '<noads><a href="/en/games/ice-fish2/">Ice Fish 2.0 review</a></noads>',
    "__CASINOS__": '<noads><a href="/en/casinos/">Ice Fish casino</a></noads>',
    "__DEMO__": '<noads><a href="/demo/">Ice Fish demo</a></noads>',
    "__DEMO_GUIDE__": '<noads><a href="/demo/">Ice Fish demo guide</a></noads>',
    "__DEMO_MODE__": '<noads><a href="/demo/">demo mode</a></noads>',
    "__DEMO_USUALLY__": '<noads><a href="/demo/">usually available</a></noads>',
    "__DOWNLOAD__": '<noads><a href="/en/download/">Ice Fish download</a></noads>',
    "__DL_GUIDE__": '<noads><a href="/en/download/">download guide</a></noads>',
    "__DL_FULL__": '<noads><a href="/en/download/">Ice Fish download guide</a></noads>',
    "__STRATEGIES__": '<noads><a href="/en/guides/how-to-win/ice-fish-strategy-guide/">Ice Fish strategies</a></noads>',
    "__CASHOUT__": '<noads><a href="/en/guides/signals/ice-fish-cash-out-guide/">when to cash out</a></noads>',
    "__STRATEGY__": '<noads><a href="/en/guides/how-to-win/ice-fish-strategy-guide/">strategy guide</a></noads>',
}


def main() -> None:
    bd = _load_build_helpers()
    packs_path = TOOLS / "locale_packs.json"
    if not packs_path.exists():
        raise SystemExit(f"Missing {packs_path}")
    extra = json.loads(packs_path.read_text(encoding="utf-8"))
    all_packs = {**bd.TRANSLATIONS, **extra}
    out = {lang: _block_to_keys(all_packs[lang], LINKS) for lang in extra}
    out["fr"] = _block_to_keys(bd.TRANSLATIONS["fr"], LINKS)
    cluster_path = TOOLS / "cluster_section_translations.json"
    cluster_path.write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {cluster_path} ({len(out)} locales)")
    subprocess.check_call([sys.executable, str(TOOLS / "apply_cluster_i18n.py")], cwd=TOOLS)
    subprocess.check_call(
        [
            sys.executable,
            "-c",
            """
from home_cluster_sections_i18n import SECTIONS_I18N
langs = ['fr','de','es','hi','pt','ru','ar','az','bn','it','nl','pl','vi','ua','ro']
sections = ['lead','ice-fish-app','game-works','features','demo-steps','batting','demo-vs-real','where-to-play','tips','game-specs','faq']
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
