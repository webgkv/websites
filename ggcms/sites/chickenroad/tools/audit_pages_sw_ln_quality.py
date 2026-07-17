#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Audit sw/ln translation quality heuristics for Chicken Road pages clusters."""

from __future__ import annotations

import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from pages_hub_meta_sw_ln import HUB_META  # noqa: E402
from pages_content_sw_ln import PAGE_META, _LOCALIZED  # noqa: E402
from home_cluster_sw_ln_sections import PAGE_META as HOME_META, _TRANS, build_content  # noqa: E402

# French/English calques that should not appear in Lingala body/meta (case-insensitive word)
LN_BAD = [
    r"\bpétillant\b",
    r"\bba joueurs\b",
    r"\bjoueur\b",
    r"\bstratégie\b",
    r"\btélécharger\b",
    r"\btéléphone\b",
    r"\bconfiance\b",
    r"\bba règles\b",
    r"\bcomportement\b",
    r"\bexpérience\b",
    r"\bresponsabilité\b",
    r"\bcontenu\b",
    r"\bPolitique\b",
    r"\bGuide na biso\b",
    r"\bcompliqué\b",
    r"\bécran d'accueil\b",
    r"\bÉcran d'accueil\b",
    r"\bnotifications\b",
    r"\bpermets\b",
    r"\bpromotions\b",
    r"\binstallation\b",
    r"\bfichier\b",
    r"\btiroir\b",
    r"\bapps\b",
    r"\bko-review\b",
]

# Swahili issues
SW_BAD = [
    r"\bchorisk\b",  # typo for hatari
    r"\bhai zaidi\b",  # awkward
    r"\bKasi\b",  # should be Kasino in hub
]

PAGE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35]
HUB_IDS = {2, 3, 7, 8, 35}
BODY_IDS = {1, 4, 5, 6, 26, 27, 28, 29, 33, 34}


def strip_html(s: str) -> str:
    return re.sub(r"<[^>]+>", " ", s or "")


def find_patterns(text: str, patterns: list[str]) -> list[str]:
    hits: list[str] = []
    for p in patterns:
        if re.search(p, text, re.I):
            hits.append(p)
    return hits


def audit_text(label: str, text: str, lang: str, issues: list[tuple[str, str, str]]) -> None:
    bad = LN_BAD if lang == "ln" else SW_BAD
    for pat in find_patterns(text, bad):
        issues.append((label, lang, pat))
    # Lingala: too many French function words in plain text
    if lang == "ln":
        plain = strip_html(text).lower()
        fr_markers = len(re.findall(r"\b(le|la|les|de|des|du|pour|avec|sans|une|un|est|sont|notre|votre)\b", plain))
        words = len(re.findall(r"\b\w+\b", plain))
        if words > 40 and fr_markers / words > 0.08:
            issues.append((label, lang, f"high_fr_ratio={fr_markers}/{words}"))


def main() -> int:
    issues: list[tuple[str, str, str]] = []

    for eid in sorted(HUB_META.keys()):
        for lang in ("sw", "ln"):
            meta = HUB_META[eid][lang]
            blob = " ".join(meta.values())
            audit_text(f"hub#{eid} meta", blob, lang, issues)

    for eid, langs in PAGE_META.items():
        for lang in ("sw", "ln"):
            if lang in langs:
                blob = " ".join(langs[lang].values())
                audit_text(f"pages#{eid} meta", blob, lang, issues)

    for eid, langs in _LOCALIZED.items():
        for lang in ("sw", "ln"):
            if lang in langs:
                audit_text(f"pages#{eid} body", langs[lang], lang, issues)

    for lang in ("sw", "ln"):
        meta = HOME_META[lang]
        audit_text("pages#1 meta", " ".join(meta.values()), lang, issues)
        audit_text("pages#1 body", build_content(lang), lang, issues)

    for pair in _TRANS.get("ln", []):
        if len(pair) >= 2:
            audit_text("pages#1 trans", pair[1], "ln", issues)
    for pair in _TRANS.get("sw", []):
        if len(pair) >= 2:
            audit_text("pages#1 trans", pair[1], "sw", issues)

    if not issues:
        print("No heuristic issues found in source files.")
        return 0

    print(f"Found {len(issues)} heuristic issue(s):\n")
    by_label: dict[str, list[tuple[str, str]]] = {}
    for label, lang, pat in issues:
        by_label.setdefault(label, []).append((lang, pat))
    for label in sorted(by_label.keys()):
        print(label)
        for lang, pat in by_label[label]:
            print(f"  [{lang}] {pat}")
    return 1


if __name__ == "__main__":
    raise SystemExit(main())
