#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Demo cluster (pages#4): single-column images everywhere except #how-to-start steps grid."""

from __future__ import annotations

import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_IN = ROOT / "site/files/reference/seo-pages-4-full.json"
DEFAULT_OUT = DEFAULT_IN

STEP_BOX = 'style="overflow: hidden; border-radius: 10px;"'
STEP_IMG = (
    'style="width: 100%; max-width: 100%; height: 190px; object-fit: cover; '
    'object-position: left center; display: block;"'
)

TWO_COL_ROW = re.compile(
    r'<div class="row mt-4 align-items-start g-4">\s*'
    r'<div class="col-xl-\d+[^>]*>\s*<div class="about_content">\s*([\s\S]*?)</div>\s*</div>\s*'
    r'<div class="col-xl-\d+[^>]*>\s*<div class="about_content section-media">\s*([\s\S]*?)</div>\s*</div>\s*'
    r'</div>',
    re.IGNORECASE,
)

FIGURE_RE = re.compile(
    r'<figure\s+class="section-media__figure"[^>]*>([\s\S]*?)</figure>',
    re.IGNORECASE,
)


def normalize_figure(inner: str) -> str:
    img = re.search(r"<img\b[^>]*>", inner, re.IGNORECASE)
    if not img:
        return f'<figure class="section-media__figure">{inner.strip()}</figure>'
    tag = img.group(0)
    tag = re.sub(r'\sstyle="[^"]*"', "", tag)
    tag = re.sub(r'\sstyle=\'[^\']*\'', "", tag)
    return f'<figure class="section-media__figure">{tag}</figure>'


def collapse_two_column_row(match: re.Match[str]) -> str:
    text = match.group(1)
    media = match.group(2).strip()
    fig_match = FIGURE_RE.search(media)
    figure = normalize_figure(fig_match.group(0) if fig_match else media)
    text = FIGURE_RE.sub("", text).strip()
    return (
        '<div class="row mt-4">\n<div class="col-12">\n<div class="about_content">\n'
        f"{text}\n{figure}\n"
        "</div>\n</div>\n</div>"
    )


def collapse_section_two_columns(section: str) -> str:
    if 'id="how-to-start"' in section:
        return section
    while True:
        updated, count = TWO_COL_ROW.subn(collapse_two_column_row, section, count=1)
        if count == 0:
            break
        section = updated
    return section


def strip_non_step_inline_styles(section: str) -> str:
    if 'id="how-to-start"' in section:
        return section
    section = re.sub(
        r'<figure\s+class="section-media__figure"\s+style="[^"]*"',
        '<figure class="section-media__figure"',
        section,
    )
    section = re.sub(
        r'(<figure class="section-media__figure">\s*<img)\s+style="[^"]*"',
        r"\1",
        section,
    )
    return section


def fix_how_to_start_steps(section: str) -> str:
    if '<div class="row mt-4">\n<div class="col-xl-9 col-lg-9 mx-auto">' not in section:
        section = re.sub(
            r'(</div>\s*</div>\s*)\n(<div class="col-xl-9 col-lg-9 mx-auto">)',
            r'\1\n<div class="row mt-4">\n\2',
            section,
            count=1,
        )
        section = re.sub(
            r'(<section id="how-to-start"[\s\S]*?<div class="row mt-4">\s*'
            r'<div class="col-xl-9 col-lg-9 mx-auto">\s*'
            r'<div class="row align-items-stretch g-4">[\s\S]*?</div>\s*</div>\s*)'
            r'(</div>\s*</section>)',
            r"\1</div>\n\2",
            section,
            count=1,
        )
    section = section.replace('<div class="steps_box">', f'<div {STEP_BOX} class="steps_box">')
    section = re.sub(
        r'(<div[^>]*class="steps_box">)\s*<img(?!\s+style=)',
        rf"\1<img {STEP_IMG}",
        section,
    )
    section = re.sub(
        r'<div class="row mt-4 align-items-center g-4">',
        '<div class="row align-items-stretch g-4">',
        section,
    )
    return section


def fix_content(html: str) -> str:
    parts = re.split(r"(<section\b[\s\S]*?</section>)", html)
    out: list[str] = []
    for part in parts:
        if part.startswith("<section"):
            if 'id="how-to-start"' in part:
                part = fix_how_to_start_steps(part)
            else:
                part = collapse_section_two_columns(part)
                part = strip_non_step_inline_styles(part)
        out.append(part)
    return "".join(out)


def main() -> None:
    src = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_IN
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_OUT
    with src.open(encoding="utf-8") as f:
        cluster = json.load(f)
    for loc in cluster.get("locales", []):
        if isinstance(loc.get("content"), str):
            loc["content"] = fix_content(loc["content"])
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(cluster, ensure_ascii=False, indent=4) + "\n"
    dst.parent.mkdir(parents=True, exist_ok=True)
    dst.write_text(payload, encoding="utf-8")
    print(f"Fixed {len(cluster.get('locales', []))} locales -> {dst}")


if __name__ == "__main__":
    main()
