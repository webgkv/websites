# -*- coding: utf-8 -*-
"""Extract structured body copy from ChickenRoaddownload.html."""

from __future__ import annotations

import re
from pathlib import Path

HTML_PATH = Path(__file__).resolve().parents[1] / "tmp/jason/Chicken Road - download/ChickenRoaddownload.html"


def _clean(text: str) -> str:
    text = re.sub(r"<[^>]+>", " ", text)
    text = (
        text.replace("&#39;", "'")
        .replace("&quot;", '"')
        .replace("&mdash;", "—")
        .replace("&rsquo;", "'")
        .replace("&nbsp;", " ")
        .replace("&rdquo;", '"')
        .replace("&ldquo;", '"')
    )
    return re.sub(r"\s+", " ", text).strip()


def _section_paras(html: str, start_pat: str, end_pat: str | None = None) -> list[str]:
    m = re.search(start_pat, html, re.S)
    if not m:
        return []
    chunk = html[m.end() :]
    if end_pat:
        em = re.search(end_pat, chunk)
        if em:
            chunk = chunk[: em.start()]
    out: list[str] = []
    for block in re.findall(r'<p class="c2"[^>]*>(.*?)</p>', chunk, re.S):
        t = _clean(block)
        if not t:
            continue
        if re.fullmatch(r"[A-Za-z /+.&\d]+", t) and len(t) < 40:
            continue
        out.append(t)
    return out


def _tables(html: str) -> list[list[list[str]]]:
    tables: list[list[list[str]]] = []
    for table in re.findall(r'<table class="c17">(.*?)</table>', html, re.S):
        rows: list[list[str]] = []
        for tr in re.findall(r"<tr[^>]*>(.*?)</tr>", table, re.S):
            cells = [_clean(c) for c in re.findall(r"<td[^>]*>(.*?)</td>", tr, re.S)]
            if any(cells):
                rows.append(cells)
        if rows:
            tables.append(rows)
    return tables


def extract_english(html: str | None = None) -> dict:
    html = html or HTML_PATH.read_text(encoding="utf-8")
    tables = _tables(html)

    page_m = re.search(
        r'<p class="c2"><span class="c9 c8">(.*?)</span></p>', html, re.S
    )
    page_h1 = _clean(page_m.group(1)) if page_m else ""

    intro_paras = _section_paras(
        html,
        r"Chicken Road app download: how to access the game on Android, iPhone and PC</span></p>",
        r"What Is the Chicken Road Application",
    )

    what_h2 = "What Is the Chicken Road Application?"
    what_first = _clean(
        re.search(
            r"What Is the Chicken Road Application\?<br></span><span class=\"c1\">(.*?)</span></p>",
            html,
            re.S,
        ).group(1)
    )
    what_rest = _section_paras(
        html,
        r"What Is the Chicken Road Application\?<br></span><span class=\"c1\">",
        r"<table class=\"c17\">",
    )
    what_paras = [what_first] + what_rest

    spec_rows = tables[0][1:]
    spec_table = [(r[0], r[1]) for r in spec_rows if len(r) == 2]

    req_h2 = "The Chicken Road APP System Requirements"
    req_table = [(r[0], r[1], r[2]) for r in tables[1][1:] if len(r) == 3]

    download_h2 = "How to Download the Chicken Road Game App"
    download_paras = _section_paras(
        html,
        r"How to Download the Chicken Road Game App</span></p>",
        r"App on Android, iOS, and PC",
    )

    diff_h2 = "App on Android, iOS, and PC: Main Differences"
    diff_table = [(r[0], r[1], r[2], r[3]) for r in tables[2][1:] if len(r) == 4]

    use_h2 = "How to Use the Chicken Road Game App"
    use_paras = _section_paras(
        html,
        r"How to Use the Chicken Road Game App</span></p>",
        r'<span class="c8 c19">FAQ</span>',
    )

    faq: list[tuple[str, str]] = []
    faq_chunk = html.split('<span class="c8 c19">FAQ</span>', 1)[-1]
    blocks = re.findall(r'<p class="c2"[^>]*>(.*?)</p>', faq_chunk, re.S)
    i = 0
    while i < len(blocks):
        q = _clean(blocks[i])
        if not q.endswith("?"):
            i += 1
            continue
        answers: list[str] = []
        j = i + 1
        while j < len(blocks):
            t = _clean(blocks[j])
            if t.endswith("?"):
                break
            if t:
                answers.append(t)
            j += 1
        if answers:
            faq.append((q, " ".join(answers)))
        i = j

    return {
        "page_h1": page_h1,
        "intro_paras": intro_paras,
        "what_h2": what_h2,
        "what_paras": what_paras,
        "spec_table": spec_table,
        "req_h2": req_h2,
        "req_table": req_table,
        "download_h2": download_h2,
        "download_paras": download_paras,
        "diff_h2": diff_h2,
        "diff_table": diff_table,
        "use_h2": use_h2,
        "use_paras": use_paras,
        "faq_h2": "FAQ",
        "faq": faq,
    }
