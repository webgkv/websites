#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate home_cluster_sections_i18n.py with full SECTIONS_I18N for 15 locales."""

from __future__ import annotations

import json
import re
import textwrap
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "home_cluster_sections_i18n.py"

LANGS = ["fr", "de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]

PAGE_TITLES = {
    "fr": "Chicken Road en ligne : fonctionnement, démo et argent réel",
    "de": "Chicken Road online: So funktioniert es, Demo und Echtgeld",
    "es": "Chicken Road online: cómo funciona, demo y dinero real",
    "hi": "Chicken Road ऑनलाइन: कैसे काम करता है, डेमो और असली पैसा",
    "pt": "Chicken Road online: como funciona, demo e dinheiro real",
    "ru": "Chicken Road онлайн: как работает, демо и реальные деньги",
    "ar": "Chicken Road أونلاين: كيف تعمل، الديمو والمال الحقيقي",
    "az": "Chicken Road onlayn: necə işləyir, demo və real pul",
    "bn": "Chicken Road অনলাইনে: কীভাবে কাজ করে, ডেমো ও আসল টাকা",
    "it": "Chicken Road online: come funziona, demo e soldi veri",
    "nl": "Chicken Road online: zo werkt het, demo en echt geld",
    "pl": "Chicken Road online: jak działa, demo i prawdziwe pieniądze",
    "vi": "Chicken Road trực tuyến: cách chơi, bản demo và tiền thật",
    "ua": "Chicken Road онлайн: як працює, демо та реальні гроші",
    "ro": "Chicken Road online: cum funcționează, demo și bani reali",
}

# Load WHERE_TO_PLAY from sibling module
import sys

sys.path.insert(0, str(TOOLS))
from process_home_cluster_pages1 import WHERE_TO_PLAY  # noqa: E402

# Translation payloads — imported from companion data file generated below
from _home_cluster_i18n_data import TRANSLATIONS  # noqa: E402


def _noads_links(html: str) -> str:
    return re.sub(
        r'<a href="([^"]+)">([^<]+)</a>',
        r'<noads><a href="\1">\2</a></noads>',
        html,
    )


def _subs(lang: str, text: str) -> str:
    links = {
        "__INOUT__": ('/en/games/inout-games/', "InOut Games"),
        "__GAMES__": ('/en/games/', "Chicken Road games"),
        "__CR__": ('/en/games/chicken-road/', "Chicken Road"),
        "__CR2__": ('/en/games/chicken-road2/', "Chicken Road 2.0"),
        "__CR_ORIG__": ('/en/games/chicken-road/', "the original Chicken Road game"),
        "__CR2_REV__": ('/en/games/chicken-road2/', "Chicken Road 2.0 review"),
        "__CASINOS__": ('/en/casinos/', "Chicken Road casino"),
        "__CASINOS_OPTS__": ('/en/casinos/', "Chicken Road casino options"),
        "__DEMO__": ('/demo/', "Chicken Road demo"),
        "__DEMO_GUIDE__": ('/demo/', "Chicken Road demo guide"),
        "__DEMO_MODE__": ('/demo/', "demo mode"),
        "__DEMO_USUALLY__": ('/demo/', "usually available"),
        "__DOWNLOAD__": ('/en/download/', "Chicken Road download"),
        "__DL_GUIDE__": ('/en/download/', "download guide"),
        "__DL_FULL__": ('/en/download/', "Chicken Road download guide"),
        "__STRATEGIES__": ('/en/guides/how-to-win/chicken-road-strategy-guide/', "Chicken Road strategies"),
        "__CASHOUT__": ('/en/guides/signals/chicken-road-cash-out-guide/', "when to cash out"),
        "__STRATEGY__": ('/en/guides/how-to-win/chicken-road-strategy-guide/', "strategy guide"),
    }
    labels = TRANSLATIONS[lang].get("_link_labels", {})
    for key, (path, default) in links.items():
        label = labels.get(key.strip("_").lower(), default)
        if key == "__CASINOS__":
            # context-specific labels from data
            label = labels.get("casinos", default)
        href = "/demo/" if path.startswith("/demo") else path.replace("/en/", f"/{lang}/", 1)
        text = text.replace(key, f'<noads><a href="{href}">{label}</a></noads>')
    return text


def build_sections(lang: str, t: dict) -> dict[str, str]:
    P = 'style="font-size: 20px; line-height: 1.6;"'
    CELL = 'style="font-size: 20px; line-height: 1.6; word-wrap: break-word;"'
    LI = (
        'style="font-size: 20px; line-height: 1.6; display: list-item; '
        'list-style-type: disc; list-style-position: outside; margin-bottom: .4em;"'
    )
    OLI = (
        'style="font-size: 20px; line-height: 1.6; display: list-item; '
        'list-style-type: decimal; list-style-position: outside; margin-bottom: .4em;"'
    )
    FIG = (
        'class="section-media__figure" style="width: 100%; margin: 0 0 1.2em 0; '
        'overflow: hidden; border-radius: 10px;"'
    )
    IMG = (
        'style="width: 100%; max-width: 100%; height: 340px; object-fit: cover; '
        'object-position: center 35%; display: block;"'
    )
    STEP_IMG = (
        'style="width: 100%; max-width: 100%; height: 190px; object-fit: cover; '
        'object-position: left center; display: block;"'
    )
    FAQ_ITEM = (
        'style="border: 1px solid rgba(0,0,0,.15); border-radius: 10px; '
        'padding: 4px 20px; margin-bottom: 12px; background: rgba(0,0,0,.02);"'
    )
    FAQ_SUM = 'style="cursor: pointer; font-weight: bold; font-size: 20px; padding: 14px 0;"'
    FAQ_ANS = 'style="font-size: 20px; line-height: 1.6; padding-bottom: 16px; margin: 0;"'

    def p(html: str) -> str:
        return f"<p {P}>{_subs(lang, html)}</p>"

    def cell(html: str) -> str:
        return f"<td {CELL}>{_subs(lang, html)}</td>"

    sections: dict[str, str] = {}

    sections["lead"] = (
        '<div class="about_content page-content-lead">\n'
        f"<h1>{PAGE_TITLES[lang]}</h1>\n"
        "</div>"
    )

    a = t["app"]
    sections["chickenroad-app"] = (
        '<section id="chickenroad-app" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{a["h2"]}</h2>\r\n'
        '</div>\r\n'
        '</div>\r\n'
        '<div class="row mt-4 align-items-start g-4">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in a["p"])
        + f'<figure {FIG}><img {IMG} src="/assets/images/chickenroad-app-desktop-mobile.webp" border="0" alt="{a["img_alt"]}" data-admin-img-edit="aie-1781875791288" /></figure>\r\n'
        + "".join(p(x) + "\r\n" for x in a["p2"])
        + '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    w = t["works"]
    sections["game-works"] = (
        '<section id="game-works" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{w["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-4 align-items-start g-4">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in w["p"][:5])
        + f'<figure {FIG}><img {IMG} src="/assets/images/chickenroad-gameplay.webp" border="0" alt="{w["img_alt"]}" /></figure>\r\n'
        + "".join(p(x) + "\r\n" for x in w["p"][5:])
        + '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    f = t["features"]
    hooks_li = "".join(
        f'<li {LI}>{h}</li>\r\n' for h in f["hooks"]
    )
    tbl = f["tbl"]
    sections["features"] = (
        '<section id="features" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{f["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-5">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in f["p"][:7])
        + p(f["hooks_title"])
        + '\r\n<ul style="list-style: disc; padding-left: 1.4em; margin: 0 0 1.2em 0;">\r\n'
        + hooks_li
        + '</ul>\r\n'
        '<div style="width: 100%;" class="table-responsive mt-4">\r\n'
        '<table class="table table-bordered" style="width: 100%; max-width: 100%; table-layout: fixed;">\r\n'
        '<thead>\r\n<tr>\r\n'
        f'<th {CELL}>{tbl["metric"]}</th>\r\n'
        f'<th {CELL}>{tbl["orig"]}</th>\r\n'
        f'<th {CELL}>{tbl["v2"]}</th>\r\n'
        '</tr>\r\n</thead>\r\n<tbody>\r\n'
        f'<tr>{cell(tbl["rtp_lbl"])}{cell("98%")}{cell("95.5%")}</tr>\r\n'
        f'<tr>{cell(tbl["released_lbl"])}{cell(tbl["rel_o"])}{cell(tbl["rel_v"])}</tr>\r\n'
        f'<tr>{cell(tbl["theme_lbl"])}{cell(tbl["theme_o"])}{cell(tbl["theme_v"])}</tr>\r\n'
        f'<tr>{cell(tbl["max_lbl"])}{cell("~$10,000")}{cell("~$20,000")}</tr>\r\n'
        f'<tr>{cell(tbl["best_lbl"])}{cell(tbl["best_o"])}{cell(tbl["best_v"])}</tr>\r\n'
        '</tbody>\r\n</table>\r\n</div>\r\n'
        + p(f["p_footer"])
        + '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    s = t["steps"]
    sections["demo-steps"] = (
        '<section id="demo-steps" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{s["h2"]}</h2>\r\n'
        f'<p {P}>{s["intro"]}</p>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="col-xl-9 col-lg-9 mx-auto">\r\n'
        '<div class="row mt-5 align-items-center">\r\n'
        '<div class="col-xl-4 col-lg-4 col-md-6">\r\n'
        '<div style="overflow: hidden; border-radius: 10px;" class="steps_box">'
        f'<img {STEP_IMG} src="/assets/images/chickenroad-step-1.webp" border="0" alt="{s["alt1"]}" width="640" height="354" />\r\n'
        '<div class="steps_content">\r\n'
        f'<h3>{s["s1h"]}</h3>\r\n'
        f'<p {P}>{s["s1p"]}</p>\r\n'
        '</div></div></div>\r\n'
        '<div class="col-xl-4 col-lg-4 col-md-6">\r\n'
        '<div style="overflow: hidden; border-radius: 10px;" class="steps_box">'
        f'<img {STEP_IMG} src="/assets/images/chickenroad-step-2.webp" border="0" alt="{s["alt2"]}" width="640" height="360" />\r\n'
        '<div class="steps_content">\r\n'
        f'<h3>{s["s2h"]}</h3>\r\n'
        f'<p {P}>{s["s2p"]}</p>\r\n'
        '</div></div></div>\r\n'
        '<div class="col-xl-4 col-lg-4 col-md-6">\r\n'
        '<div style="overflow: hidden; border-radius: 10px;" class="steps_box">'
        f'<img {STEP_IMG} src="/assets/images/chickenroad-step-3.webp" border="0" alt="{s["alt3"]}" width="640" height="360" />\r\n'
        '<div class="steps_content">\r\n'
        f'<h3>{s["s3h"]}</h3>\r\n'
        f'<p {P}>{s["s3p"]}</p>\r\n'
        '</div></div></div>\r\n'
        '</div></div>\r\n'
        '<div class="col-xl-9 col-lg-9 mx-auto mt-4">\r\n'
        '<div class="about_content">\r\n'
        + p(f'<strong>{s["check"]}</strong>')
        + '\r\n<ol style="list-style: decimal; padding-left: 1.4em; margin: 0 0 1.2em 0;">\r\n'
        + "".join(f'<li {OLI}>{_subs(lang, x)}</li>\r\n' for x in s["ol"])
        + '</ol>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    b = t["batting"]
    sections["batting"] = (
        '<section id="batting" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{b["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-4 align-items-start g-4">\r\n'
        '<div class="col-xl-6 col-lg-6 col-md-6">\r\n'
        '<div class="about_content section-media">\r\n'
        f'<figure {FIG}><img {IMG} src="/assets/images/chickenroad-mobile.webp" border="0" alt="{b["img_alt"]}" /></figure>\r\n'
        '</div></div>\r\n'
        '<div class="col-xl-6 col-lg-6 col-md-6">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in b["p"])
        + '<div style="display: flex; justify-content: center; width: 100%;" class="main_btn mt-5">'
        f'<noads><a style="display: inline-block;" href="/demo/">{b["btn"]}</a></noads></div>\r\n'
        '</div></div>\r\n</div>\r\n</div>\r\n</section>'
    )

    d = t["demo"]
    sections["demo-vs-real"] = (
        '<section id="demo-vs-real" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{d["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-5">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in d["p"])
        + '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    wtp = WHERE_TO_PLAY.get(lang) or WHERE_TO_PLAY["en"]
    sections["where-to-play"] = (
        '<section id="where-to-play" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{wtp["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-3">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        f'<p style="font-size: 20px; line-height: 1.6;">{_noads_links(wtp["p1"])}</p>\r\n'
        f'<p style="font-size: 20px; line-height: 1.6;">{wtp["p2"]}</p>\r\n'
        '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    tips = t["tips"]
    sections["tips"] = (
        '<section id="tips" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{tips["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-5">\r\n'
        '<div class="col-12">\r\n'
        '<div class="about_content">\r\n'
        + "".join(p(x) + "\r\n" for x in tips["p"])
        + '</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    sp = t["specs"]
    spec_rows = ""
    for label, val in sp["rows"]:
        spec_rows += f"<tr>{cell(label)}{cell(val)}</tr>\r\n"

    sections["game-specs"] = (
        '<section id="game-specs" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{sp["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-3">\r\n'
        '<div class="col-12">\r\n'
        '<div style="width: 100%;" class="table-responsive">\r\n'
        '<table class="table table-bordered" style="width: 100%; max-width: 100%; table-layout: fixed;">\r\n'
        '<thead>\r\n<tr>\r\n'
        f'<th {CELL}>{sp["th1"]}</th>\r\n'
        f'<th {CELL}>{sp["th2"]}</th>\r\n'
        '</tr>\r\n</thead>\r\n<tbody>\r\n'
        + spec_rows
        + '</tbody>\r\n</table>\r\n</div>\r\n</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    fq = t["faq"]
    faq_items = ""
    for q, a in fq["items"]:
        faq_items += (
            f'<details class="faq-item" {FAQ_ITEM}><summary {FAQ_SUM}>{q}</summary>\r\n'
            f'<p {FAQ_ANS}>{_subs(lang, a)}</p>\r\n</details> '
        )
    sections["faq"] = (
        '<section id="faq" class="mt-5 pt-5">\r\n'
        '<div class="container">\r\n'
        '<div class="col-12">\r\n'
        '<div class="main_heading">\r\n'
        f'<h2>{fq["h2"]}</h2>\r\n'
        '</div>\r\n</div>\r\n'
        '<div class="row mt-3">\r\n'
        '<div class="col-12">\r\n'
        f'<div class="faq-list">{faq_items}</div>\r\n'
        '</div>\r\n</div>\r\n</div>\r\n</section>'
    )

    return sections


def emit_output() -> None:
    sections_i18n = {lang: build_sections(lang, TRANSLATIONS[lang]) for lang in LANGS}

    framework = textwrap.dedent(
        '''\
        # -*- coding: utf-8 -*-
        """Localized home cluster sections (pages#1) — 15 non-EN locales, EN-canonical structure."""

        from __future__ import annotations

        import json
        import re

        P = 'style="font-size: 20px; line-height: 1.6;"'
        CELL = 'style="font-size: 20px; line-height: 1.6; word-wrap: break-word;"'
        TABLE = 'style="width: 100%; max-width: 100%; table-layout: fixed;"'
        FAQ_ITEM = (
            'style="border: 1px solid rgba(0,0,0,.15); border-radius: 10px; '
            'padding: 4px 20px; margin-bottom: 12px; background: rgba(0,0,0,.02);"'
        )
        FAQ_SUM = 'style="cursor: pointer; font-weight: bold; font-size: 20px; padding: 14px 0;"'
        FAQ_ANS = 'style="font-size: 20px; line-height: 1.6; padding-bottom: 16px; margin: 0;"'
        LI = (
            'style="font-size: 20px; line-height: 1.6; display: list-item; '
            'list-style-type: disc; list-style-position: outside; margin-bottom: .4em;"'
        )
        OLI = (
            'style="font-size: 20px; line-height: 1.6; display: list-item; '
            'list-style-type: decimal; list-style-position: outside; margin-bottom: .4em;"'
        )
        FIG = (
            'class="section-media__figure" style="width: 100%; margin: 0 0 1.2em 0; '
            'overflow: hidden; border-radius: 10px;"'
        )
        IMG = (
            'style="width: 100%; max-width: 100%; height: 340px; object-fit: cover; '
            'object-position: center 35%; display: block;"'
        )
        STEP_IMG = (
            'style="width: 100%; max-width: 100%; height: 190px; object-fit: cover; '
            'object-position: left center; display: block;"'
        )

        PAGE_TITLES = '''
    )
    framework += json.dumps(PAGE_TITLES, ensure_ascii=False, indent=4)
    framework += textwrap.dedent(
        '''

        def L(lang: str, path: str, text: str) -> str:
            if path.startswith("/demo"):
                href = "/demo/"
            else:
                href = path.replace("/en/", f"/{lang}/", 1)
            return f'<noads><a href="{href}">{text}</a></noads>'


        def _p(html: str) -> str:
            return f"<p {P}>{html}</p>"


        def build_lead(lang: str) -> str:
            title = PAGE_TITLES[lang]
            return (
                '<div class="about_content page-content-lead">\\n'
                f"<h1>{title}</h1>\\n"
                "</div>"
            )

        '''
    )

    payload = json.dumps(sections_i18n, ensure_ascii=False, indent=4)
    OUT.write_text(
        framework + f"\nSECTIONS_I18N = {payload}\n",
        encoding="utf-8",
    )
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")


if __name__ == "__main__":
    emit_output()
