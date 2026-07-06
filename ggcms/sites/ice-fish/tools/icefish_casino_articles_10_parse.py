# -*- coding: utf-8 -*-
"""Parse casino_articles#10 1win Ice Fish Word HTML into structured body dicts."""

from __future__ import annotations

import re
from html import unescape


def clean_text(html_fragment: str) -> str:
    t = re.sub(r"<br\s*/?>", " ", html_fragment)
    t = re.sub(r"<[^>]+>", " ", t)
    t = unescape(t)
    return re.sub(r"\s+", " ", t).strip()


def paras_from_html(html: str) -> list[str]:
    out: list[str] = []
    for p in re.findall(r"<p[^>]*>(.*?)</p>", html, re.S | re.I):
        t = clean_text(p)
        if t:
            out.append(t)
    return out


def imgs_from_html(html: str) -> list[str]:
    return re.findall(r'src="([^"]+)"', html, re.I)


def split_h3_sections(content: str) -> list[tuple[str, str]]:
    parts = re.split(r"<h3><strong>(.*?)</strong></h3>", content, flags=re.S | re.I)
    sections: list[tuple[str, str]] = []
    if parts and parts[0].strip():
        sections.append(("", parts[0]))
    i = 1
    while i + 1 < len(parts):
        sections.append((clean_text(parts[i]), parts[i + 1]))
        i += 2
    return sections


def parse_faq(body: str) -> list[tuple[str, str]]:
    faq: list[tuple[str, str]] = []
    blocks = re.split(r"<p><strong>([^<]+\?)</strong></p>", body, flags=re.S | re.I)
    if len(blocks) < 3:
        return faq
    i = 1
    while i + 1 < len(blocks):
        q = clean_text(blocks[i])
        ans = " ".join(paras_from_html(blocks[i + 1]))
        if q and ans:
            faq.append((q, ans))
        i += 2
    return faq


def parse_content_to_body(content: str) -> dict:
    content = unescape(content)
    wrapper = re.search(r'<div class="casino-article-content">(.*)</div>\s*$', content, re.S | re.I)
    inner = wrapper.group(1) if wrapper else content

    sections = split_h3_sections(inner)
    by_title = {t: b for t, b in sections if t}

    intro_body = by_title.get("Ice Fish on 1win - What Is This Game?", "")
    intro_paras = paras_from_html(intro_body)
    intro_imgs = imgs_from_html(intro_body)

    where_body = by_title.get("Where to Find Ice Fish on 1win", "")
    where_paras = paras_from_html(where_body)
    where_imgs = imgs_from_html(where_body)
    short_path = ""
    short_outro: list[str] = []
    for i, p in enumerate(where_paras):
        if p.startswith("The short path looks like this:"):
            short_path = where_paras[i + 1] if i + 1 < len(where_paras) else ""
            short_outro = where_paras[i + 2 :]
            where_paras = where_paras[:i]
            break

    play_body = by_title.get("How to Play Ice Fish Alternatives on 1win", "")
    play_paras = paras_from_html(play_body)
    play_imgs = imgs_from_html(play_body)

    demo_body = by_title.get("Demo and Real-Money Play on 1win", "")
    demo_paras = paras_from_html(demo_body)
    demo_imgs = imgs_from_html(demo_body)

    mobile_body = by_title.get("Ice Fish on Mobile via 1win", "")
    mobile_paras = paras_from_html(mobile_body)
    mobile_imgs = imgs_from_html(mobile_body)

    bonus_paras = paras_from_html(by_title.get("Bonuses and Promos for Ice Fish on 1win", ""))
    strategy_body = by_title.get("Strategies and Mistakes to Avoid", "")
    strategy_paras = paras_from_html(strategy_body)
    strategy_imgs = imgs_from_html(strategy_body)

    safety_paras = paras_from_html(by_title.get("Safety and Risks", ""))
    final_paras = paras_from_html(by_title.get("Final Thoughts", ""))
    faq = parse_faq(by_title.get("FAQ", ""))

    all_imgs = imgs_from_html(inner)
    defaults = [
        "/files/media/2026/06/screenshot-2026-06-08-131035.png",
        "/files/media/2026/06/screenshot-2026-06-08-131134.png",
        "/files/media/2026/06/screenshot-2026-06-08-131153.png",
        "/files/media/2026/06/screenshot-2026-06-08-130854.png",
        "/files/media/2026/06/screenshot-2026-06-08-131322.png",
        "/files/media/2026/06/screenshot-2026-06-08-134835.png",
        "/files/media/2026/06/screenshot-2026-06-08-134940.png",
        "/files/media/2026/06/screenshot-2026-06-08-131333.png",
    ]
    img_keys = ["hero", "search1", "search2", "play1", "play2", "demo", "mobile", "strategy"]
    images = {}
    for i, key in enumerate(img_keys):
        images[key] = (all_imgs[i] if i < len(all_imgs) else defaults[i])

    return {
        "h1": "1WIN - Ice Fish",
        "h2_intro": "Ice Fish on 1win — What Is This Game?",
        "img_hero_alt": "Ice Fish style game overview on 1win",
        "intro_paras": intro_paras,
        "h2_about": "About 1win",
        "about_paras": paras_from_html(by_title.get("About 1win", "")),
        "h2_where": "Where to Find Ice Fish on 1win",
        "img_search_main_alt": "1win Casino lobby search for chicken-style games",
        "img_search1_alt": "Search for chicken-style games in 1win Casino",
        "img_search2_alt": "Chicken Route and similar games on 1win",
        "where_paras": where_paras,
        "short_path_title": "The short path looks like this:",
        "short_path": short_path,
        "short_path_outro": short_outro,
        "h2_play": "How to Play Ice Fish Alternatives on 1win",
        "img_play1_alt": "Ice Fish alternative gameplay on 1win",
        "img_play2_alt": "Multiplier and Cash Out on 1win fast game",
        "play_paras": play_paras,
        "h2_demo": "Demo and Real-Money Play on 1win",
        "img_demo_alt": "Demo mode for Ice Fish alternatives on 1win",
        "demo_paras": demo_paras,
        "h2_mobile": "Ice Fish on Mobile via 1win",
        "img_mobile_alt": "Ice Fish style game on 1win mobile",
        "mobile_paras": mobile_paras,
        "h2_bonuses": "Bonuses and Promos for Ice Fish on 1win",
        "bonus_paras": bonus_paras,
        "h2_strategy": "Strategies and Mistakes to Avoid",
        "img_strategy_alt": "Risk and multiplier decisions in Ice Fish alternatives",
        "strategy_paras": strategy_paras,
        "h2_safety": "Safety and Risks",
        "safety_paras": safety_paras,
        "h2_final": "Final Thoughts",
        "final_paras": final_paras,
        "h2_faq": "FAQ",
        "faq": faq,
        "images": images,
    }


def fix_en_body(body: dict) -> dict:
    out = dict(body)
    out["title"] = "Ice Fish on 1win: alternatives, demo and tips"
    out["description"] = (
        "Ice Fish is not on 1win yet — find Turbo Games alternatives, demo play, "
        "mobile tips, bonuses, safety notes and FAQ."
    )
    return out
