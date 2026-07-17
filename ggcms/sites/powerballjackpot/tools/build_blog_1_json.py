#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build blog#1 sw/ln JSON from fixed EN export with editorial Swahili."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
CHICKEN = TOOLS.parent.parent / "chickenroad" / "tools"
EXPORT = Path("/home/lenovo/Downloads/02/powerballjackpot-blog/seo-blog-1-full.json")
sys.path.insert(0, str(CHICKEN))
sys.path.insert(0, str(TOOLS))

from fix_en_seo_html import fix_blog1_html  # noqa: E402
from ln_quality_replacements import polish_ln  # noqa: E402
from blog_1_sw_editorial import SW_EDITORIAL  # noqa: E402


def extract_segments(html: str) -> list[str]:
    segs: list[str] = []
    for m in re.finditer(r"<h([1-3])[^>]*>(.*?)</h\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if t and t not in segs:
            segs.append(t)
    for m in re.finditer(r'alt="([^"]*)"', html, re.I):
        t = m.group(1).strip()
        if t and t not in segs:
            segs.append(t)
    for m in re.finditer(r"<(p|li|td|th|summary|figcaption)[^>]*>(.*?)</\1>", html, re.I | re.S):
        t = re.sub(r"<[^>]+>", "", m.group(2)).strip()
        t = re.sub(r"\s+", " ", t)
        if len(t) >= 8 and t not in segs:
            segs.append(t)
    return segs


def sw_to_ln(text: str) -> str:
    t = text
    for a, b in [
        ("Kubeti Bahati Nasibu", "Kobeta Lottery"),
        ("Bahati nasibu", "Lottery"),
        ("bahati nasibu", "lottery"),
        ("tiketi", "ticket"),
        ("Tiketi", "Ticket"),
        ("droo", "tirage"),
        ("Droo", "Tirage"),
        ("mchezaji", "joueur"),
        ("wachezaji", "ba joueurs"),
        ("Mwongozo", "Mwongozo"),
        ("mwendeshaji", "opérateur"),
        ("Waendeshaji", "Ba opérateur"),
        ("zawadi", "prix"),
        ("Uwezekano", "Probabilité"),
        ("uwezekano", "probabilité"),
        ("Ndio", "Ee"),
        ("Hapana", "Te"),
        ("Maswali", "FAQ"),
    ]:
        t = t.replace(a, b)
    return polish_ln(t)


def main() -> int:
    data = json.loads(EXPORT.read_text(encoding="utf-8"))
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    html = fix_blog1_html(en.get("content") or "")
    seg = extract_segments(html)

    missing = [s for s in seg if s not in SW_EDITORIAL]
    if missing:
        print(f"Missing {len(missing)} editorial SW entries", file=sys.stderr)
        for s in missing[:5]:
            print(f"  - {s[:100]}", file=sys.stderr)
        return 1

    sw = [SW_EDITORIAL[s] for s in seg]
    ln = [sw_to_ln(s) for s in sw]

    out = {
        "ln_from_fr": False,
        "meta": {
            "sw": {
                "name": "Kubeti Bahati Nasibu",
                "title": "Kubeti Bahati Nasibu: Mwongozo wa Lotteries na Kubeti",
                "description": (
                    "Mwongozo wa kubeti bahati nasibu: PowerBall, Greece Kino, Poland Multi Multi, "
                    "mifano ya waendeshaji, hatari na jinsi betting inavyofanya kazi."
                ),
            },
            "ln": {
                "name": "Kobeta Lottery",
                "title": "Kobeta Lottery: Mwongozo ya Ba Lotteries mpe Kobeta",
                "description": (
                    "Mwongozo ya kobeta lottery: PowerBall, Greece Kino, Poland Multi Multi, "
                    "ba modèle ya ba opérateur, ba risque mpe ndenge kobeta esalelaka."
                ),
            },
        },
        "pairs": {"sw": [[a, b] for a, b in zip(seg, sw)], "ln": [[a, b] for a, b in zip(seg, ln)]},
    }
    path = TOOLS / "blog_sw_ln_data" / "blog_1.json"
    path.write_text(json.dumps(out, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Written {path} ({len(seg)} pairs)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
