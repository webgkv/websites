#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Fix EN HTML SEO issues (single H1, img alt) for PowerBall casino/blog clusters."""

from __future__ import annotations

import re

CASINO27_ALTS: dict[str, str] = {
    "screenshot-2026-07-02-170423": "PowerBall lottery draw results on screen",
    "powerball-1.webp": "PowerBall white balls and red Powerball drum",
    "sc-powerball-blog-off-plat2-copy": "Five main numbers plus red Powerball explained",
    "635884716989935149-lotto-004": "Customers buying lottery tickets at a retail store",
    "13519262_071823-cc-ap-powerball": "PowerBall jackpot winner news headline photo",
    "635882999638391443-powerball-tickets": "Stack of PowerBall tickets before a draw",
}

BLOG1_ALTS: dict[str, str] = {
    "lottery_money.webp": "Lottery betting payouts and cash prizes illustration",
    "lotteryworld_lotteries_2-1.webp": "International lottery draws listed for online betting",
    "greece-kino.webp": "Greece Kino 20/80 lottery draw interface",
    "multi-multi.webp": "Poland Multi Multi lottery betting screen",
}


def _set_img_alt(tag: str, alt: str) -> str:
    esc = alt.replace('"', "&quot;")
    if re.search(r'\salt\s*=\s*["\']', tag, re.I):
        return re.sub(r'\salt\s*=\s*["\'][^"\']*["\']', f' alt="{esc}"', tag, count=1, flags=re.I)
    return tag.replace("<img ", f'<img alt="{esc}" ', 1)


def fix_img_alts(html: str, mapping: dict[str, str]) -> str:
    def repl(m: re.Match[str]) -> str:
        tag = m.group(0)
        for key, alt in mapping.items():
            if key in tag:
                return _set_img_alt(tag, alt)
        if re.search(r'\salt\s*=\s*["\']\s*["\']', tag, re.I):
            return _set_img_alt(tag, "Lottery illustration")
        return tag

    return re.sub(r"<img\b[^>]*>", repl, html, flags=re.I)


def fix_casino27_html(html: str) -> str:
    if not re.search(r"<h1\b", html, re.I):
        html = "<h1>Powerball Lottery: America&rsquo;s Favorite Dream Machine</h1>\r\n" + html
    return fix_img_alts(html, CASINO27_ALTS)


def fix_blog1_html(html: str) -> str:
    html = re.sub(
        r'<p><span style="font-size: 24pt;"><strong>Lottery Betting</strong></span></p>',
        "<h1>Lottery Betting</h1>",
        html,
        count=1,
        flags=re.I,
    )
    html = re.sub(r"<h1>\s*&nbsp;\s*</h1>\s*", "", html, flags=re.I)
    html = re.sub(r"<h1><strong>([^<]+)</strong></h1>", r"<h2>\1</h2>", html, flags=re.I)
    if not re.search(r"<h1\b", html, re.I):
        html = "<h1>Lottery Betting</h1>\r\n" + html
    return fix_img_alts(html, BLOG1_ALTS)


def fix_en_content(entity: str, entity_id: int, html: str) -> str:
    if entity == "casino_articles" and entity_id == 27:
        return fix_casino27_html(html)
    if entity == "blog" and entity_id == 1:
        return fix_blog1_html(html)
    return html
