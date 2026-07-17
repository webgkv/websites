#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Helpers for games cluster sw/ln: sanitize EN HTML, localize links, apply segment pairs."""

from __future__ import annotations

import re
from pathlib import Path


def sanitize_en_html(html: str) -> str:
    """Fix known EN issues before using as translation template."""
    html = re.sub(r"<h1>\s*(?:&nbsp;|\u00a0|\s)*</h1>\s*", "", html, flags=re.I)
    # Fill empty alt from filename stem as fallback (validator requires alt)
    def alt_fix(m: re.Match[str]) -> str:
        tag = m.group(0)
        if re.search(r'\balt="[^"]+"', tag, re.I) and not re.search(r'\balt=""', tag, re.I):
            return tag
        src_m = re.search(r'src="([^"]+)"', tag, re.I)
        stem = "Chicken Road gameplay"
        if src_m:
            stem = Path(src_m.group(1)).stem.replace("-", " ").replace("_", " ")
        new_alt = stem.replace('"', "'")
        return re.sub(r'\balt=""', f'alt="{new_alt}"', tag, count=1)

    return re.sub(r"<img\b[^>]*>", alt_fix, html, flags=re.I)


def localize_hrefs(html: str, lang: str) -> str:
    if lang == "en":
        return html
    return re.sub(r'href="/en/', f'href="/{lang}/', html)


def wrap_internal_links_noads(html: str) -> str:
    def repl(m: re.Match[str]) -> str:
        tag = m.group(0)
        pre = html[max(0, m.start() - 80) : m.start()]
        if re.search(r"<noads>\s*$", pre, re.I):
            return tag
        href_m = re.search(r'href="([^"]+)"', tag, re.I)
        if not href_m:
            return tag
        href = href_m.group(1)
        if not href.startswith("/") or href.startswith("//"):
            return tag
        if href.startswith("/assets/") or href.startswith("/files/"):
            return tag
        return f"<noads>{tag}</noads>"

    parts: list[str] = []
    pos = 0
    for m in re.finditer(r"<a\b[^>]*>.*?</a>", html, re.I | re.S):
        parts.append(html[pos : m.start()])
        parts.append(repl(m))
        pos = m.end()
    parts.append(html[pos:])
    return "".join(parts)


def apply_pairs(html: str, pairs: list[tuple[str, str]]) -> str:
    out = html
    for en, loc in sorted(pairs, key=lambda x: len(x[0]), reverse=True):
        if en and loc and en in out:
            out = out.replace(en, loc)
    return out


def plain_len(html: str) -> int:
    return len(re.sub(r"<[^>]+>", "", html or "").strip())


def tag_counts(html: str) -> dict[str, int]:
    return {
        "h1": len(re.findall(r"<h1\b", html, re.I)),
        "h2": len(re.findall(r"<h2\b", html, re.I)),
        "h3": len(re.findall(r"<h3\b", html, re.I)),
        "p": len(re.findall(r"<p[> ]", html, re.I)),
        "li": len(re.findall(r"<li\b", html, re.I)),
        "img": len(re.findall(r"<img\b", html, re.I)),
        "table": len(re.findall(r"<table\b", html, re.I)),
    }
