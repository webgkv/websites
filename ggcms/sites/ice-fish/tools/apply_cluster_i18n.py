#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build home_cluster_sections_i18n.py from EN template + per-locale text maps."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from process_home_cluster_pages1 import WHERE_TO_PLAY  # noqa: E402

EN_SECTIONS = json.loads(Path("/tmp/en_sections.json").read_text(encoding="utf-8"))
EN_KEYS = json.loads(Path("/tmp/en_keys.json").read_text(encoding="utf-8"))
OUT = TOOLS / "home_cluster_sections_i18n.py"
TRANS_PATH = TOOLS / "cluster_section_translations.json"

LANGS = ["fr", "de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]

PAGE_TITLES = {
    "fr": "Ice Fish en ligne : fonctionnement, démo et argent réel",
    "de": "Ice Fish online: So funktioniert es, Demo und Echtgeld",
    "es": "Ice Fish online: cómo funciona, demo y dinero real",
    "hi": "Ice Fish ऑनलाइन: कैसे काम करता है, डेमो और असली पैसा",
    "pt": "Ice Fish online: como funciona, demo e dinheiro real",
    "ru": "Ice Fish онлайн: как работает, демо и реальные деньги",
    "ar": "Ice Fish أونلاين: كيف تعمل، الديمو والمال الحقيقي",
    "az": "Ice Fish onlayn: necə işləyir, demo və real pul",
    "bn": "Ice Fish অনলাইনে: কীভাবে কাজ করে, ডেমো ও আসল টাকা",
    "it": "Ice Fish online: come funziona, demo e soldi veri",
    "nl": "Ice Fish online: zo werkt het, demo en echt geld",
    "pl": "Ice Fish online: jak działa, demo i prawdziwe pieniądze",
    "vi": "Ice Fish trực tuyến: cách chơi, bản demo và tiền thật",
    "ua": "Ice Fish онлайн: як працює, демо та реальні гроші",
    "ro": "Ice Fish online: cum funcționează, demo și bani reali",
}


def _noads_links(html: str) -> str:
    return re.sub(
        r'<a href="([^"]+)">([^<]+)</a>',
        lambda m: (
            f'<noads><a href="{m.group(1)}">{m.group(2)}</a></noads>'
            if "<noads>" not in html[max(0, m.start() - 8) : m.start()]
            else m.group(0)
        ),
        html,
    )


def _link(lang: str, path: str, text: str) -> str:
    href = "/demo/" if path.startswith("/demo") else path.replace("/en/", f"/{lang}/", 1)
    return f'<noads><a href="{href}">{text}</a></noads>'


def _fix_paths(html: str, lang: str) -> str:
    html = html.replace('href="/en/', f'href="/{lang}/')
    return html


def _flat_pairs(en_block: dict, loc_block: dict) -> list[tuple[str, str]]:
    pairs: list[tuple[str, str]] = []
    for key in ("h1",):
        if key in en_block and key in loc_block:
            pairs.append((en_block[key], loc_block[key]))
    for key in ("h2", "p", "h3", "li", "th", "td", "summary", "btn", "img_alt"):
        en_list = en_block.get(key, [])
        loc_list = loc_block.get(key, [])
        if not isinstance(en_list, list):
            en_list = [en_list]
        if not isinstance(loc_list, list):
            loc_list = [loc_list]
        for en, loc in zip(en_list, loc_list):
            if en and loc and en != loc:
                pairs.append((en, loc))
    return pairs


def localize_html(en_html: str, lang: str, pairs: list[tuple[str, str]]) -> str:
    html = en_html
    for en, loc in sorted(pairs, key=lambda x: -len(x[0])):
        html = html.replace(en, loc)
    html = _fix_paths(html, lang)
    # ensure internal links wrapped (where-to-play handled separately)
    html = re.sub(
        r'(?<!<noads>)<a href="(/(?!/)[^"]+)">',
        r'<noads><a href="\1">',
        html,
    )
    html = html.replace("</a></noads></noads>", "</a></noads>")
    html = re.sub(r"<noads><noads>", "<noads>", html)
    html = re.sub(r"</noads>(\s*)</noads>", r"</noads>\1", html)
    # fix double noads from re-sub
    while "<noads><noads>" in html:
        html = html.replace("<noads><noads>", "<noads>")
    return html


def build_where_to_play(lang: str) -> str:
    block = WHERE_TO_PLAY.get(lang) or WHERE_TO_PLAY["en"]
    p_style = 'style="font-size: 20px; line-height: 1.6;"'
    return (
        '<section id="where-to-play" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{block["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-3">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        f'<p {p_style}>{_noads_links(block["p1"])}</p>\r\n'
        f'<p {p_style}>{block["p2"]}</p>\r\n'
        '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )


def build_sections(lang: str, loc: dict) -> dict[str, str]:
    out: dict[str, str] = {}
    for sid, en_html in EN_SECTIONS.items():
        if sid == "where-to-play":
            out[sid] = build_where_to_play(lang)
            continue
        if sid == "lead":
            out[sid] = (
                '<div class="about_content page-content-lead">\n'
                f"<h1>{PAGE_TITLES[lang]}</h1>\n"
                "</div>"
            )
            continue
        pairs = _flat_pairs(EN_KEYS.get(sid, {}), loc.get(sid, {}))
        out[sid] = localize_html(en_html, lang, pairs)
    return out


def main() -> None:
    if not TRANS_PATH.exists():
        raise SystemExit(f"Missing {TRANS_PATH} — run build_cluster_translations_json.py first")
    all_trans = json.loads(TRANS_PATH.read_text(encoding="utf-8"))
    sections_i18n = {}
    for lang in LANGS:
        if lang not in all_trans:
            raise SystemExit(f"Missing locale {lang} in translations JSON")
        sections_i18n[lang] = build_sections(lang, all_trans[lang])

    header = '''# -*- coding: utf-8 -*-
"""Localized home cluster sections (pages#1) — 15 non-EN locales, EN-canonical structure."""

from __future__ import annotations

P = \'style="font-size: 20px; line-height: 1.6;"\'
CELL = \'style="font-size: 20px; line-height: 1.6; word-wrap: break-word;"\'
TABLE = \'style="width: 100%; max-width: 100%; table-layout: fixed;"\'
FAQ_ITEM = (
    \'style="border: 1px solid rgba(0,0,0,.15); border-radius: 10px; \'
    \'padding: 4px 20px; margin-bottom: 12px; background: rgba(0,0,0,.02);"\'
)
FAQ_SUM = \'style="cursor: pointer; font-weight: bold; font-size: 20px; padding: 14px 0;"\'
FAQ_ANS = \'style="font-size: 20px; line-height: 1.6; padding-bottom: 16px; margin: 0;"\'
LI = (
    \'style="font-size: 20px; line-height: 1.6; display: list-item; \'
    \'list-style-type: disc; list-style-position: outside; margin-bottom: .4em;"\'
)
OLI = (
    \'style="font-size: 20px; line-height: 1.6; display: list-item; \'
    \'list-style-type: decimal; list-style-position: outside; margin-bottom: .4em;"\'
)
FIG = (
    \'class="section-media__figure" style="width: 100%; margin: 0 0 1.2em 0; \'
    \'overflow: hidden; border-radius: 10px;"\'
)
IMG = (
    \'style="width: 100%; max-width: 100%; height: 340px; object-fit: cover; \'
    \'object-position: center 35%; display: block;"\'
)
STEP_IMG = (
    \'style="width: 100%; max-width: 100%; height: 190px; object-fit: cover; \'
    \'object-position: left center; display: block;"\'
)

PAGE_TITLES = '''
    body = header + json.dumps(PAGE_TITLES, ensure_ascii=False, indent=4)
    body += '''


def L(lang: str, path: str, text: str) -> str:
    if path.startswith("/demo"):
        href = "/demo/"
    else:
        href = path.replace("/en/", f"/{lang}/", 1)
    return f\'<noads><a href="{href}">{text}</a></noads>\'


def _p(html: str) -> str:
    return f"<p {P}>{html}</p>"


def build_lead(lang: str) -> str:
    title = PAGE_TITLES[lang]
    return (
        \'<div class="about_content page-content-lead">\\n\'
        f"<h1>{title}</h1>\\n"
        "</div>"
    )


'''
    body += f"SECTIONS_I18N = {json.dumps(sections_i18n, ensure_ascii=False, indent=4)}\n"
    OUT.write_text(body, encoding="utf-8")
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
