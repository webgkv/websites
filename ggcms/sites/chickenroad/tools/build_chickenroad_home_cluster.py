#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#1 home cluster — full ChickenRoadHome.html body in Aviator-style layout."""

from __future__ import annotations

import html
import json
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

from chickenroad_html_extract import extract_english
from chickenroad_home_full_ru import get_russian

ROOT = Path(__file__).resolve().parents[1]
CLUSTER_IN = ROOT / "tmp/jason/seo-pages-1-full.json"
OUT_REPO = ROOT / "site/files/reference/seo-pages-1-full.json"
OUT_DL = ROOT / "tmp/jason/seo-pages-1-full.json"

IMAGES = {
    "app": "/assets/images/chickenroad-app-desktop-mobile.webp",
    "gameplay": "/assets/images/chickenroad-gameplay.webp",
    "step1": "/assets/images/chickenroad-step-1.webp",
    "step2": "/assets/images/chickenroad-step-2.webp",
    "step3": "/assets/images/chickenroad-step-3.webp",
    "mobile": "/assets/images/chickenroad-mobile.webp",
}

LOCALE_META = {
    1: {"code": "en", "name": "Home", "title": "Chicken Road Online: How It Works, Demo and Real Money", "description": "Learn how Chicken Road works, why the demo matters, and how to play responsibly before trying real-money rounds."},
    3: {"code": "fr", "name": "Accueil", "title": "Chicken Road en ligne : fonctionnement, démo et argent réel", "description": "Découvrez comment Chicken Road fonctionne, pourquoi la démo compte et comment jouer de façon responsable."},
    4: {"code": "de", "name": "Start", "title": "Chicken Road online: So funktioniert es, Demo und Echtgeld", "description": "Erfahren Sie, wie Chicken Road funktioniert, warum die Demo wichtig ist und wie Sie verantwortungsvoll spielen."},
    6: {"code": "es", "name": "Inicio", "title": "Chicken Road online: cómo funciona, demo y dinero real", "description": "Descubra cómo funciona Chicken Road, por qué importa la demo y cómo jugar con responsabilidad."},
    7: {"code": "hi", "name": "होम", "title": "Chicken Road ऑनलाइन: कैसे काम करता है, डेमो और असली पैसा", "description": "जानें Chicken Road कैसे काम करता है, डेमो क्यों ज़रूरी है और जिम्मेदारी से कैसे खेलें।"},
    8: {"code": "pt", "name": "Início", "title": "Chicken Road online: como funciona, demo e dinheiro real", "description": "Veja como Chicken Road funciona, por que a demo importa e como jogar com responsabilidade."},
    9: {"code": "ru", "name": "Главная", "title": "Chicken Road онлайн: как работает, демо и реальные деньги", "description": "Узнайте, как устроена Chicken Road, зачем нужен демо-режим и как играть ответственно перед ставками на деньги."},
    11: {"code": "ar", "name": "الرئيسية", "title": "Chicken Road أونلاين: كيف تعمل، الديمو والمال الحقيقي", "description": "تعرّف على آلية Chicken Road ولماذا يهم الوضع التجريبي وكيف تلعب بمسؤولية."},
    12: {"code": "az", "name": "Ana səhifə", "title": "Chicken Road onlayn: necə işləyir, demo və real pul", "description": "Chicken Road-un necə işlədiyini, demonun niyə vacib olduğunu və məsuliyyətli oyunu öyrənin."},
    13: {"code": "bn", "name": "হোম", "title": "Chicken Road অনলাইনে: কীভাবে কাজ করে, ডেমো ও আসল টাকা", "description": "Chicken Road কীভাবে কাজ করে, ডেমো কেন গুরুত্বপূর্ণ এবং দায়িত্বশীলভাবে কীভাবে খেলবেন জানুন।"},
    14: {"code": "it", "name": "Home", "title": "Chicken Road online: come funziona, demo e soldi veri", "description": "Scopri come funziona Chicken Road, perché conta la demo e come giocare in modo responsabile."},
    15: {"code": "nl", "name": "Home", "title": "Chicken Road online: zo werkt het, demo en echt geld", "description": "Leer hoe Chicken Road werkt, waarom de demo telt en hoe je verantwoord speelt."},
    16: {"code": "pl", "name": "Strona główna", "title": "Chicken Road online: jak działa, demo i prawdziwe pieniądze", "description": "Dowiedz się, jak działa Chicken Road, po co jest demo i jak grać odpowiedzialnie."},
    17: {"code": "vi", "name": "Trang chủ", "title": "Chicken Road trực tuyến: cách chơi, bản demo và tiền thật", "description": "Tìm hiểu Chicken Road hoạt động thế nào, vì sao bản demo quan trọng và cách chơi có trách nhiệm."},
    18: {"code": "ua", "name": "Головна", "title": "Chicken Road онлайн: як працює, демо та реальні гроші", "description": "Дізнайтеся, як влаштована Chicken Road, навіщо потрібен демо-режим і як грати відповідально."},
    19: {"code": "ro", "name": "Acasă", "title": "Chicken Road online: cum funcționează, demo și bani reali", "description": "Află cum funcționează Chicken Road, de ce contează demo-ul și cum să joci responsabil."},
}


def e(text: str) -> str:
    return html.escape(text, quote=True)


def img_section(key: str, alt: str) -> str:
    return f'<img src="{IMAGES[key]}" border="0" alt="{e(alt)}" />'


def img_step(key: str, alt: str) -> str:
    return f'<img src="{IMAGES[key]}" border="0" alt="{e(alt)}" />'


def media_col(key: str, alt: str) -> str:
    return f"""<div class="col-xl-6 col-lg-6 col-md-6">
<div class="about_content section-media">
<figure class="section-media__figure">{img_section(key, alt)}</figure>
</div>
</div>"""


def text_col(inner: str) -> str:
    return f"""<div class="col-xl-6 col-lg-6 col-md-6">
<div class="about_content">
{inner}
</div>
</div>"""


def paras_block(items: list[str]) -> str:
    return "".join(f"<p>{e(p)}</p>" for p in items if p)


def inline_figure(key: str, alt: str) -> str:
    return f'<figure class="section-media__figure">{img_section(key, alt)}</figure>'


def paras_block_with_figure(
    items: list[str], after_index: int, key: str, alt: str
) -> str:
    out: list[str] = []
    for i, p in enumerate(items):
        if p:
            out.append(f"<p>{e(p)}</p>")
        if i == after_index:
            out.append(inline_figure(key, alt))
    return "".join(out)


def build_content(s: dict, page_title: str) -> str:
    t = s["titles"]
    a = s["alts"]
    table_rows = "".join(
        f"<tr><td>{e(label)}</td><td>{e(val)}</td></tr>" for label, val in s["table"]
    )
    faq_items = "".join(
        f"<p><strong>{e(q)}</strong> {e(ans)}</p>" for q, ans in s["faq"]
    )
    hooks = "".join(f"<li><i></i>{e(h)}</li>" for h in s["hooks"])

    return f"""<section id="chickenroad-app" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(t["app_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-12">
<div class="about_content">
{paras_block_with_figure(s["what_paras"], 3, "app", a["app"])}
</div>
</div>
</div>
</div>
</section>
<section id="game-works" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["works_h3"])}</h3>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-12">
<div class="about_content">
{paras_block_with_figure(s["works_paras"], 4, "gameplay", a["gameplay"])}
</div>
</div>
</div>
</div>
</section>
<section id="features" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["why_h3"])}</h3>
</div>
</div>
<div class="row mt-5">
<div class="col-12">
<div class="about_content">
{paras_block(s["why_paras"])}
<p><strong>{e(t["why_hooks_title"])}</strong></p>
<ul>
{hooks}
</ul>
</div>
</div>
</div>
</div>
</section>
<section id="demo-steps" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["steps_h3"])}</h3>
<p>{e(t["steps_p"])}</p>
</div>
</div>
<div class="col-xl-9 col-lg-9 mx-auto">
<div class="row mt-5 align-items-center">
<div class="col-xl-4 col-lg-4 col-md-6">
<div class="steps_box">{img_step("step1", a["step1"])}
<div class="steps_content">
<h3>{e(t["step1_h"])}</h3>
<p>{e(t["step1_p"])}</p>
</div>
</div>
</div>
<div class="col-xl-4 col-lg-4 col-md-6">
<div class="steps_box">{img_step("step2", a["step2"])}
<div class="steps_content">
<h3>{e(t["step2_h"])}</h3>
<p>{e(t["step2_p"])}</p>
</div>
</div>
</div>
<div class="col-xl-4 col-lg-4 col-md-6">
<div class="steps_box">{img_step("step3", a["step3"])}
<div class="steps_content">
<h3>{e(t["step3_h"])}</h3>
<p>{e(t["step3_p"])}</p>
</div>
</div>
</div>
</div>
</div>
</div>
</section>
<section id="batting" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["mobile_h3"])}</h3>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
{media_col("mobile", a["mobile"])}
{text_col(paras_block(s["mobile_paras"]) + f'<div class="main_btn mt-5"><a href="/demo/">{e(t["demo_cta"])}</a></div>')}
</div>
</div>
</section>
<section id="demo-vs-real" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["demo_h3"])}</h3>
</div>
</div>
<div class="row mt-5">
<div class="col-12">
<div class="about_content">
{paras_block(s["demo_paras"])}
</div>
</div>
</div>
</div>
</section>
<section id="tips" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["tips_h3"])}</h3>
</div>
</div>
<div class="row mt-5">
<div class="col-12">
<div class="about_content">
{paras_block(s["tips_paras"])}
</div>
</div>
</div>
</div>
</section>
<section id="game-specs" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["specs_h3"])}</h3>
</div>
</div>
<div class="row mt-3">
<div class="col-12">
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>{e(t["table_h1"])}</th>
<th>{e(t["table_h2"])}</th>
</tr>
</thead>
<tbody>
{table_rows}
</tbody>
</table>
</div>
</div>
</div>
</div>
</section>
<section id="faq" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h3>{e(t["faq_h3"])}</h3>
</div>
</div>
<div class="row mt-3">
<div class="col-12">
<div class="about_content">
{faq_items}
</div>
</div>
</div>
</div>
</section>"""


def english_titles() -> dict:
    return {
        "app_h2": "What Is Chicken Road?",
        "works_h5": "How It Works",
        "works_h3": "How Chicken Road Works",
        "why_h5": "Why It Hooks Players",
        "why_h3": "Why Chicken Road Pulls Players In So Quickly",
        "why_hooks_title": "The main hooks are simple:",
        "steps_h5": "Simple Steps",
        "steps_h3": "Chicken Road in three steps",
        "steps_p": "The flow is short: set your bet and difficulty, move the chicken step by step, and cash out before a trap ends the run.",
        "step1_h": "1. BET",
        "step1_p": "Choose your stake and difficulty before the round starts and stay inside the limit you set for the session.",
        "step2_h": "2. ADVANCE",
        "step2_p": "Move the chicken when you are ready. Each safe step increases the multiplier on screen.",
        "step3_h": "3. CASH OUT",
        "step3_p": "Secure the multiplier after any successful step. If you keep going and the chicken fails, the stake is lost.",
        "mobile_h5": "Mobile Play",
        "mobile_h3": "Chicken Road on Mobile",
        "demo_h5": "Demo and Real Money",
        "demo_h3": "Chicken Road Demo — Can You Play for Free?",
        "tips_h5": "Tips",
        "tips_h3": "Strategies, Risks, and Responsible Play",
        "specs_h5": "At a Glance",
        "specs_h3": "Chicken Road — Basic Info",
        "faq_h5": "FAQ",
        "faq_h3": "Chicken Road FAQ",
        "table_h1": "Criteria",
        "table_h2": "Chicken Road",
        "demo_cta": "Try Chicken Road demo",
    }


def english_alts() -> dict:
    return {
        "app": "Chicken Road game interface on desktop and mobile",
        "gameplay": "Chicken Road multiplier climbing during a live round",
        "step1": "Set your stake and difficulty before a Chicken Road round starts",
        "step2": "Advance the chicken across road lanes during the round",
        "step3": "Cash out before the chicken hits a trap",
        "mobile": "Chicken Road mobile interface in portrait mode",
    }


def polish_english(en: dict) -> dict:
    """Remove editorial placeholders and fix awkward phrasing in EN body copy."""
    data = deepcopy(en)
    wp = data["what_paras"]
    if len(wp) > 4:
        wp[4] = (
            "Chicken Road launched in 2024 and quickly became one of InOut Games' "
            "breakout titles, especially because of its step-based crash mechanic, "
            "selectable difficulty levels, and high RTP in the original version. "
            "InOut Games also gained industry recognition in the crash-game segment, "
            "including awards connected to Chicken Road and its wider crash portfolio."
        )
    wk = data["works_paras"]
    if len(wk) > 5:
        wk[5] = (
            "Chicken Road uses a step-based crash mechanic where every successful move "
            "increases the multiplier, while the risk changes depending on the selected "
            "difficulty level. The player controls when the chicken moves and when to cash out."
        )
    wy = data["why_paras"]
    if len(wy) > 3:
        wy[3] = (
            "Chicken Road also has a strong advantage in its original version: the RTP is "
            "listed at 98%, which is high compared to many crash-style games. The first "
            "Chicken Road version combines a 98% RTP with four difficulty levels and a "
            "step-based crash mechanic, which makes the game more flexible for different "
            "types of players."
        )
    if len(wy) > 6:
        wy[6] = (
            "That feeling can be dangerous. The player controls the button, not the outcome. "
            "You can choose when to stop, but you cannot know if the next lane is safe. "
            "You decide when the chicken moves and when to cash out, but the game still "
            "relies on RNG and risk management."
        )
    return data


def locale_body(code: str) -> dict:
    if code == "ru":
        ru = get_russian()
        return {
            "titles": ru["titles"],
            "alts": ru["alts"],
            "what_paras": ru["what_paras"],
            "works_paras": ru["works_paras"],
            "why_paras": ru["why_paras"],
            "hooks": ru["hooks"],
            "demo_paras": ru["demo_paras"],
            "mobile_paras": ru["mobile_paras"],
            "tips_paras": ru["tips_paras"],
            "table": ru["table"],
            "faq": ru["faq"],
        }
    en = polish_english(extract_english())
    return {
        "titles": english_titles(),
        "alts": english_alts(),
        "what_paras": en["what_paras"],
        "works_paras": en["works_paras"],
        "why_paras": en["why_paras"],
        "hooks": en["hooks"],
        "demo_paras": en["demo_paras"],
        "mobile_paras": en["mobile_paras"],
        "tips_paras": en["tips_paras"],
        "table": en["table"],
        "faq": en["faq"],
    }


def word_count(body: dict) -> int:
    keys = [
        "what_paras",
        "works_paras",
        "why_paras",
        "demo_paras",
        "mobile_paras",
        "tips_paras",
    ]
    total = 0
    for key in keys:
        for p in body[key]:
            total += len(p.split())
    for q, a in body["faq"]:
        total += len(q.split()) + len(a.split())
    for label, val in body["table"]:
        total += len(label.split()) + len(val.split())
    for h in body["hooks"]:
        total += len(h.split())
    return total


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)
    old_by_lang = {loc["lang_id"]: loc for loc in cluster["locales"]}
    new_locales = []
    for lang_id, meta in LOCALE_META.items():
        code = meta["code"]
        body = locale_body("ru" if code == "ru" else "en")
        loc = {
            "lang_id": lang_id,
            "lang_url": old_by_lang[lang_id].get("lang_url", code),
            "url": old_by_lang[lang_id].get("url", "/"),
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": build_content(body, meta["title"]),
            "status": old_by_lang[lang_id].get("status", "published"),
            "source": "export",
            "seo_monitor_ctx": old_by_lang[lang_id].get("seo_monitor_ctx"),
        }
        new_locales.append(loc)

    cluster["locales"] = new_locales
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(cluster, ensure_ascii=False, indent=4)
    OUT_REPO.parent.mkdir(parents=True, exist_ok=True)
    OUT_REPO.write_text(payload + "\n", encoding="utf-8")
    OUT_DL.write_text(payload + "\n", encoding="utf-8")

    en_body = locale_body("en")
    ru_body = locale_body("ru")
    html_words = word_count(en_body)
    print(f"Wrote {OUT_REPO}")
    print(f"EN body words: {html_words}")
    print(f"RU body words: {word_count(ru_body)}")
    ru = next(x for x in new_locales if x["lang_id"] == 9)
    import re

    print("Images:", sorted(set(re.findall(r'src=\"([^\"]+)\"', ru["content"]))))


if __name__ == "__main__":
    main()
