#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Inject distinct homepage images into pages#1 cluster (all locales)."""

from __future__ import annotations

import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEFAULT = ROOT / "site/files/reference/seo-pages-1-full.json"

FIGURES = {
    "multipliers": (
        "/assets/images/ice-fish-multipliers.webp",
        "Ice Fish payout multipliers from 2x to 10x",
    ),
    "demo": (
        "/assets/images/ice-fish-demo-interface.webp",
        "Ice Fish demo mode with recent wins on screen",
    ),
    "inout": (
        "/assets/images/ice-fish-inout.webp",
        "Ice Fish logo by InOut Games",
    ),
}

STEP_ALTS = {
    "ice-fish-step-1.webp": "Choose your fish target and bet in Ice Fish",
    "ice-fish-step-2.webp": "Follow the catch as the line goes deeper in Ice Fish",
    "ice-fish-step-3.webp": "Cash out your Ice Fish multiplier before the round ends",
}


def figure_html(src: str, alt: str) -> str:
    return (
        f'<figure class="section-media__figure"><img src="{src}" border="0" '
        f'alt="{alt}" /></figure>'
    )


def insert_after_paragraph(section: str, para_index: int, fig: str) -> str:
    if fig in section:
        return section
    matches = list(re.finditer(r"</p>", section))
    if len(matches) <= para_index:
        return section
    pos = matches[para_index].end()
    return section[:pos] + fig + section[pos:]


def patch_section(html: str, section_id: str, para_index: int, key: str) -> str:
    src, alt = FIGURES[key]
    fig = figure_html(src, alt)
    pattern = rf'(<section id="{re.escape(section_id)}"[\s\S]*?</section>)'

    def repl(match: re.Match[str]) -> str:
        return insert_after_paragraph(match.group(1), para_index, fig)

    return re.sub(pattern, repl, html, count=1)


def patch_game_specs(html: str) -> str:
    src, alt = FIGURES["inout"]
    fig = figure_html(src, alt)
    pattern = r'(<section id="game-specs"[\s\S]*?<div class="row mt-3">\s*<div class="col-12">\s*)<div style="width: 100%;" class="table-responsive">'
    if fig in html:
        return html
    return re.sub(
        pattern,
        rf"\1<div class=\"about_content\">\n{fig}\n</div>\n</div>\n</div>\n<div class=\"row mt-3\">\n<div class=\"col-12\">\n<div style=\"width: 100%;\" class=\"table-responsive\">",
        html,
        count=1,
    )


def fix_step_alts(html: str) -> str:
    for fname, alt in STEP_ALTS.items():
        html = re.sub(
            rf'(<img[^>]*src="/assets/images/{re.escape(fname)}"[^>]*alt=")[^"]*(")',
            rf"\1{alt}\2",
            html,
        )
    return html


def patch_content(html: str) -> str:
    html = patch_section(html, "features", 1, "multipliers")
    html = patch_section(html, "demo-vs-real", 0, "demo")
    html = patch_game_specs(html)
    html = fix_step_alts(html)
    return html


def main() -> None:
    path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT
    cluster = json.loads(path.read_text(encoding="utf-8"))
    for loc in cluster.get("locales", []):
        if isinstance(loc.get("content"), str):
            loc["content"] = patch_content(loc["content"])
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    path.write_text(json.dumps(cluster, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Patched {len(cluster.get('locales', []))} locales -> {path}")


if __name__ == "__main__":
    main()
