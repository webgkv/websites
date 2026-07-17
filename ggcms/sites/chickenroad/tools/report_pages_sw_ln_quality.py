#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Print translation quality summary for Chicken Road pages clusters (sw/ln)."""

from __future__ import annotations

import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from pages_hub_meta_sw_ln import HUB_META  # noqa: E402
from pages_content_sw_ln import PAGE_META, _LOCALIZED, get_content  # noqa: E402
from home_cluster_sw_ln_sections import PAGE_META as HOME_META, build_content  # noqa: E402
from apply_pages_sw_ln_cluster import is_hub_cluster  # noqa: E402

PAGE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35]
HUB_IDS = {2, 3, 7, 8, 35}

LN_FR = re.compile(
    r"\b(stratégie|télécharger|joueur|joueurs|contenu|politique|responsabilité|"
    r"compliqué|pourquoi|bouton|choix|décide|mécanique|contrôle|funny|sérieux|"
    r"Guide na biso|pétillant|comportement|expérience)\b",
    re.I,
)
SW_ISSUES = re.compile(r"\b(chorisk|hai zaidi)\b", re.I)


def plain(html: str) -> str:
    return re.sub(r"<[^>]+>", " ", html or "").strip()


def fr_ratio(text: str) -> float:
    p = plain(text).lower()
    fr = len(re.findall(r"\b(le|la|les|de|des|du|pour|avec|sans|une|un|est|sont)\b", p))
    words = max(1, len(re.findall(r"\b\w+\b", p)))
    return fr / words


def score_lang(text: str, lang: str) -> tuple[int, list[str]]:
    notes: list[str] = []
    if not text.strip():
        return (10 if lang else 0, ["hub/meta only"])
    hits = LN_FR.findall(text) if lang == "ln" else SW_ISSUES.findall(text)
    if hits:
        uniq = sorted(set(h.lower() for h in hits))[:6]
        notes.append("flags: " + ", ".join(uniq))
    ratio = fr_ratio(text) if lang == "ln" else 0.0
    if lang == "ln" and ratio > 0.06:
        notes.append(f"fr_ratio={ratio:.1%}")
    # heuristic score
    s = 9
    s -= min(3, len(set(h.lower() for h in hits)))
    if lang == "ln" and ratio > 0.08:
        s -= 2
    elif lang == "ln" and ratio > 0.05:
        s -= 1
    return max(4, s), notes


def cluster_text(entity_id: int, lang: str) -> str:
    if entity_id in HUB_IDS:
        return " ".join(HUB_META[entity_id][lang].values())
    if entity_id == 1:
        meta = HOME_META[lang]
        return " ".join(meta.values()) + " " + build_content(lang)
    body = get_content(entity_id, lang) or ""
    meta = PAGE_META.get(entity_id, {}).get(lang, {})
    return " ".join(meta.values()) + " " + body


def main() -> int:
    print("Chicken Road pages — sw/ln quality heuristic\n")
    print(f"{'ID':>3}  {'type':<6}  {'sw':>3}  {'ln':>3}  notes")
    print("-" * 72)
    sw_scores: list[int] = []
    ln_scores: list[int] = []
    for eid in PAGE_IDS:
        typ = "hub" if eid in HUB_IDS else "body"
        sw_t = cluster_text(eid, "sw")
        ln_t = cluster_text(eid, "ln")
        sw_s, sw_n = score_lang(sw_t if typ == "body" or eid not in HUB_IDS else HUB_META[eid]["sw"]["description"], "sw")
        ln_s, ln_n = score_lang(ln_t if typ == "body" or eid not in HUB_IDS else HUB_META[eid]["ln"]["description"], "ln")
        sw_scores.append(sw_s)
        ln_scores.append(ln_s)
        notes = "; ".join((sw_n + ln_n)[:3])
        print(f"{eid:>3}  {typ:<6}  {sw_s:>3}  {ln_s:>3}  {notes}")
    print("-" * 72)
    print(f"Avg sw={sum(sw_scores)/len(sw_scores):.1f}/10  ln={sum(ln_scores)/len(ln_scores):.1f}/10")
    print("\nScores are editorial heuristics, not native-speaker review.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
