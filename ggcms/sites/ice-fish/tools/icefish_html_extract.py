# -*- coding: utf-8 -*-
"""Extract structured body copy from ChickenRoadHome.html."""

from __future__ import annotations

import re
from pathlib import Path

HTML_PATH = Path("/home/lenovo/Downloads/tmp_report/ChickenRoadHome.html")


def _clean(text: str) -> str:
    text = (
        text.replace("&#39;", "'")
        .replace("&quot;", '"')
        .replace("&mdash;", "—")
        .replace("&ldquo;", '"')
        .replace("&rdquo;", '"')
    )
    return re.sub(r"\s+", " ", text).strip()


def _paras_after(h2_marker: str, html: str) -> list[str]:
    m = re.search(h2_marker, html, re.S)
    if not m:
        return []
    chunk = html[m.end() :]
    end = re.search(r'<h2 class="c1"', chunk)
    if end:
        chunk = chunk[: end.start()]
    ps = re.findall(
        r'<p class="c3"[^>]*><span class="c0">(.*?)</span></p>', chunk, re.S
    )
    return [_clean(p) for p in ps if _clean(p)]


def extract_english(html: str | None = None) -> dict:
    html = html or HTML_PATH.read_text(encoding="utf-8")
    hooks = [
        _clean(h)
        for h in re.findall(
            r'<li class="c3 c16 li-bullet-0"><span class="c0">(.*?)</span></li>', html
        )
    ]
    table: list[tuple[str, str]] = []
    for row in re.findall(r"<tr class=\"c5\">(.*?)</tr>", html, re.S):
        cells = re.findall(r'<span class="c4">(.*?)</span>', row, re.S)
        if len(cells) == 2:
            table.append((_clean(cells[0]), _clean(cells[1])))

    faq: list[tuple[str, str]] = []
    faq_block = html.split("Ice Fish FAQ", 1)[-1]
    blocks = re.findall(r"<p class=\"c6\"[^>]*>(.*?)</p>", faq_block, re.S)
    i = 0
    while i < len(blocks):
        block = blocks[i]
        qm = re.search(r'<span class="c9">(.*?)</span>', block, re.S)
        if qm:
            if i + 1 < len(blocks):
                am = re.search(r'<span class="c0">(.*?)</span>', blocks[i + 1], re.S)
                if am:
                    faq.append((_clean(qm.group(1)), _clean(am.group(1))))
                    i += 2
                    continue
        i += 1

    return {
        "what_paras": _paras_after(r"1\. What Is Ice Fish\?</span></h2>", html),
        "works_paras": _paras_after(r"2\. How Ice Fish Works</span></h2>", html),
        "why_paras": _paras_after(r"3\. Why Ice Fish Pulls Players In So Quickly\s*</span></h2>", html),
        "demo_paras": _paras_after(r"4\. Ice Fish Demo.*?Free\?\s*</span></h2>", html),
        "mobile_paras": _paras_after(r"5\. Ice Fish on Mobile</span></h2>", html),
        "tips_paras": _paras_after(r"6\. Strategies, Risks, and Responsible Play\s*</span></h2>", html),
        "hooks": hooks,
        "table": table,
        "faq": faq,
    }
