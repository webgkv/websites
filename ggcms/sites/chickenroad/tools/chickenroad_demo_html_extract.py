# -*- coding: utf-8 -*-
"""Extract structured body copy from ChickenRoadDemo.html."""

from __future__ import annotations

import re
from pathlib import Path

HTML_PATH = Path(__file__).resolve().parents[1] / "tmp/jason/ChickenRoadDemo.html"


def _clean(text: str) -> str:
    text = re.sub(r"<[^>]+>", " ", text)
    text = (
        text.replace("&#39;", "'")
        .replace("&quot;", '"')
        .replace("&mdash;", "—")
        .replace("&rsquo;", "'")
        .replace("&nbsp;", " ")
    )
    text = re.sub(r"&#(\d+);", lambda m: chr(int(m.group(1))), text)
    return re.sub(r"\s+", " ", text).strip()


def _split_sections(html: str) -> list[tuple[str, str]]:
    chunks = re.split(r'<h2 class="c7"[^>]*><span class="c[58][^"]*">', html)[1:]
    sections: list[tuple[str, str]] = []
    for chunk in chunks:
        title_raw, body = chunk.split("</span></h2>", 1)
        title = re.sub(r"^\d+\.\s*", "", _clean(title_raw))
        sections.append((title, body))
    return sections


def _section_paras(body: str) -> list[str]:
    return [
        _clean(block)
        for block in re.findall(r'<p class="c[346][^"]*"[^>]*>(.*?)</p>', body, re.S)
        if _clean(block)
    ]


def extract_english(html: str | None = None) -> dict:
    html = html or HTML_PATH.read_text(encoding="utf-8")
    sections = _split_sections(html)

    intro_paras = [
        _clean(block)
        for block in re.findall(
            r'<p class="c6"[^>]*><span class="c1">(.*?)</span></p>', sections[0][1], re.S
        )
        if _clean(block)
    ]

    what_h2 = "What Is Chicken Road Demo Mode?"
    what_paras = _section_paras(sections[1][1])

    start_h2 = "How to Start Chicken Road Demo"
    start_steps = [
        _clean(block)
        for block in re.findall(
            r'<li class="c2[^"]*"[^>]*>(.*?)</li>', sections[2][1], re.S
        )
        if _clean(block)
    ]
    start_summary = _section_paras(sections[2][1])[0]

    vs_h2 = "Demo vs Real Money Play"
    vs_paras = _section_paras(sections[3][1])

    why_h2 = "Why Start with the Demo First?"
    why_paras = _section_paras(sections[4][1])
    why_bullets_intro = why_paras[2] if len(why_paras) > 2 else "The demo helps with a few things:"
    why_before = why_paras[:2]
    why_after = why_paras[3:] if len(why_paras) > 3 else []
    bullet_match = re.search(
        r'lst-kix_9uytq1wo1xsj-0 start">(.*?)</ul>', sections[4][1], re.S
    )
    why_bullets: list[str] = []
    if bullet_match:
        why_bullets = [
            _clean(block)
            for block in re.findall(
                r'<li class="c2[^"]*"[^>]*>(.*?)</li>', bullet_match.group(1), re.S
            )
            if _clean(block)
        ]

    mobile_h2 = "Chicken Road Demo on Mobile"
    mobile_paras = _section_paras(sections[5][1])

    download_h2 = "Can You Download Chicken Road Demo?"
    download_paras = _section_paras(sections[6][1])

    trouble_h2 = "If Chicken Road Demo Does Not Start"
    trouble_paras = _section_paras(sections[7][1])

    safety_h2 = "Demo Safety and Fair Play"
    safety_paras = _section_paras(sections[8][1])

    faq_h2 = "FAQ — Chicken Road Demo"
    faq: list[tuple[str, str]] = []
    for q_raw, a_raw in re.findall(
        r'<p class="c4"><span class="c9">(.*?)</span></p>\s*'
        r'<p class="c3[^"]*"[^>]*><span class="c1"></span></p>\s*'
        r'<p class="c4"><span class="c1">(.*?)</span></p>',
        sections[9][1],
        re.S,
    ):
        faq.append((_clean(q_raw), _clean(a_raw)))

    return {
        "intro_h2": "Chicken Road Demo — play free without risk",
        "intro_paras": intro_paras,
        "what_h2": what_h2,
        "what_paras": what_paras,
        "start_h2": start_h2,
        "start_steps": start_steps,
        "start_summary": start_summary,
        "vs_h2": vs_h2,
        "vs_paras": vs_paras,
        "why_h2": why_h2,
        "why_before": why_before,
        "why_bullets_intro": why_bullets_intro,
        "why_bullets": why_bullets,
        "why_after": why_after,
        "mobile_h2": mobile_h2,
        "mobile_paras": mobile_paras,
        "download_h2": download_h2,
        "download_paras": download_paras,
        "trouble_h2": trouble_h2,
        "trouble_paras": trouble_paras,
        "safety_h2": safety_h2,
        "safety_paras": safety_paras,
        "faq_h2": faq_h2,
        "faq": faq,
    }
