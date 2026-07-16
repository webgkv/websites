#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply targeted Lingala polish replacements to ln HTML in pages_content_sw_ln._LOCALIZED."""

from __future__ import annotations

import re
from pathlib import Path

TARGET = Path(__file__).resolve().parent / "pages_content_sw_ln.py"

# longest first
LN_REPLACEMENTS: list[tuple[str, str]] = [
    ("Guide na biso ya Cash Out", "Mwongozo na biso ya Cash Out"),
    ("Guide na biso ya APK Chicken Road", "Mwongozo na biso ya APK Chicken Road"),
    ("sans inscription", "na compte te"),
    ("sans risque", "na riski te"),
    ("sans app store", "na app store te"),
    ("stratégie", "strategy"),
    ("Stratégie", "Strategy"),
    ("ba joueurs", "basali"),
    ("Ba joueurs", "Basali"),
    ("joueur", "mosali"),
    ("Joueur", "Mosali"),
    (" téléphone", " telefone"),
    ("téléphone", "telefone"),
    ("Télécharger", "Kozua"),
    ("télécharger", "kozua"),
    (" ba jeux ", " ba lisano "),
    ("ba jeux", "ba lisano"),
    (" jeu ", " lisano "),
    ("Jeu ", "Lisano "),
    ("confiance", "kondima"),
    ("ba règles", "mibeko"),
    ("Ba règles", "Mibeko"),
    ("expliqué", "elimbolami"),
    ("pétillant", "pépé"),
    ("comportement", "ndenge ya kosala"),
    ("expérience", "ndenge ya kosalela"),
    ("gestion ya", "kobatela"),
    ("timing ya", "tango ya"),
    ("approche éditoriale", "ndenge ya kosa"),
    ("ko-review", "kotala"),
    ("Politique ya confidentialité", "Bosembo ya bomani"),
    ("politique ya confidentialité", "bosembo ya bomani"),
    ("Jeu Responsable", "Kobeta na libateli"),
    ("jeu responsable", "kobeta na libateli"),
    ("Installer ", "Botia "),
    ("installer ", "botia "),
    ("Écran d'accueil", "ecran ya liboso"),
    ("écran d'accueil", "ecran ya liboso"),
    ("e vérifié", "etalá"),
    ("E Vérifié", "Etalá"),
    ("sécurité", "libateli"),
    ("Sécurité", "Libateli"),
]


def polish_html(html: str) -> str:
    for old, new in LN_REPLACEMENTS:
        html = html.replace(old, new)
    return html


def main() -> None:
    text = TARGET.read_text(encoding="utf-8")
    # Match 'ln': '...' or "ln": "..." multiline strings in _LOCALIZED - use exec approach
    ns: dict = {}
    exec(compile(text, str(TARGET), "exec"), ns)  # noqa: S102
    localized = ns["_LOCALIZED"]
    changed = 0
    for eid, langs in localized.items():
        if "ln" not in langs:
            continue
        old = langs["ln"]
        new = polish_html(old)
        if new != old:
            langs["ln"] = new
            changed += 1
    if not changed:
        print("No ln body changes")
        return
    # Rebuild _LOCALIZED block only - simpler: replace in file per entity
    out = text
    for eid, langs in localized.items():
        if "ln" not in langs:
            continue
        # find entity block ln value - fragile; rewrite whole file _LOCALIZED via repr
        pass
    # Write by replacing original ln strings
    for eid, langs in sorted(localized.items()):
        old_block = ns.get("_ORIG_LN", {})
    # Alternative: patch file using stored originals from first read
    orig_ns: dict = {}
    exec(compile(text, str(TARGET), "exec"), orig_ns)
    out = text
    for eid in sorted(localized.keys()):
        o = orig_ns["_LOCALIZED"][eid].get("ln", "")
        n = localized[eid].get("ln", "")
        if o and n != o and o in out:
            out = out.replace(o, n, 1)
            print(f"polished pages#{eid} ln body")
    TARGET.write_text(out, encoding="utf-8")
    print(f"Done: {changed} entities updated")


if __name__ == "__main__":
    main()
