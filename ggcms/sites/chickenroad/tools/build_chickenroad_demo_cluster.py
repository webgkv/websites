#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#4 demo cluster — ChickenRoadDemo.html in site layout."""

from __future__ import annotations

import html
import json
import re
from datetime import datetime, timezone
from pathlib import Path

from chickenroad_demo_full_locales import get_all_full_locales
from chickenroad_demo_full_ru import get_russian
from chickenroad_demo_html_extract import extract_english

ROOT = Path(__file__).resolve().parents[1]
CLUSTER_IN = ROOT / "tmp/jason/seo-pages-4-full.json"
OUT_REPO = ROOT / "site/files/reference/seo-pages-4-full.json"
OUT_TMP = ROOT / "tmp/jason/seo-pages-4-full.json"

IMAGES = {
    "gameplay": "/assets/images/chickenroad-gameplay.webp",
    "app": "/assets/images/chickenroad-app-desktop-mobile.webp",
    "mobile": "/assets/images/chickenroad-mobile.webp",
    "interface": "/assets/images/chickenroad-download-interface.webp",
    "step1": "/assets/images/chickenroad-step-1.webp",
    "step2": "/assets/images/chickenroad-step-2.webp",
    "step3": "/assets/images/chickenroad-step-3.webp",
}

# Match homepage cluster inline media (home_cluster_sections_i18n.py)
FIG = (
    'class="section-media__figure" style="width: 100%; margin: 0 0 1.2em 0; '
    'overflow: hidden; border-radius: 10px;"'
)
IMG = (
    'style="width: 100%; max-width: 100%; height: 340px; object-fit: cover; '
    'object-position: center 35%; display: block;"'
)
IMG_PORTRAIT = (
    'style="width: 100%; max-width: 100%; height: 420px; object-fit: contain; '
    'object-position: center top; display: block;"'
)
STEP_BOX = 'style="overflow: hidden; border-radius: 10px;"'
STEP_IMG = (
    'style="width: 100%; max-width: 100%; height: 190px; object-fit: cover; '
    'object-position: left center; display: block;"'
)
P = 'style="font-size: 20px; line-height: 1.6;"'
OLI = (
    'style="font-size: 20px; line-height: 1.6; display: list-item; '
    'list-style-type: decimal; list-style-position: outside; margin-bottom: .4em;"'
)

LOCALE_META = {
    1: {
        "code": "en",
        "name": "Demo",
        "title": "Chicken Road Demo Free Play | Test the Game Online",
        "description": "Play Chicken Road demo free with virtual balance. Learn when to cash out, try difficulty levels, and see how demo mode differs from real-money play.",
    },
    3: {
        "code": "fr",
        "name": "Démo",
        "title": "Démo Chicken Road gratuite | Jouer en ligne sans risque",
        "description": "Jouez à la démo Chicken Road gratuitement avec un solde virtuel. Apprenez à encaisser, testez les niveaux de difficulté et comparez démo et argent réel.",
    },
    4: {
        "code": "de",
        "name": "Demo",
        "title": "Chicken Road Demo gratis | Spiel online testen",
        "description": "Spielen Sie die Chicken Road Demo kostenlos mit virtuellem Guthaben. Üben Sie Cash-out, testen Sie Schwierigkeitsgrade und vergleichen Sie Demo und Echtgeld.",
    },
    6: {
        "code": "es",
        "name": "Demo",
        "title": "Demo Chicken Road gratis | Prueba el juego online",
        "description": "Juega a la demo de Chicken Road gratis con saldo virtual. Aprende cuándo cobrar, prueba dificultades y compara la demo con el juego con dinero real.",
    },
    7: {
        "code": "hi",
        "name": "डेमो",
        "title": "Chicken Road डेमो मुफ्त | ऑनलाइन गेम आज़माएं",
        "description": "वर्चुअल बैलेंस के साथ Chicken Road डेमो मुफ्त खेलें। कैश-आउट का समय, कठिनाई स्तर और डेमो व असली खेल का अंतर समझें।",
    },
    8: {
        "code": "pt",
        "name": "Demo",
        "title": "Demo Chicken Road grátis | Teste o jogo online",
        "description": "Jogue a demo Chicken Road grátis com saldo virtual. Aprenda quando sacar, teste dificuldades e veja a diferença para o jogo com dinheiro real.",
    },
    9: {
        "code": "ru",
        "name": "Демо",
        "title": "Демо Chicken Road бесплатно | Играйте онлайн без риска",
        "description": "Играйте в демо Chicken Road бесплатно на виртуальный баланс. Разберитесь с выводом выигрыша, уровнями сложности и отличиями от игры на деньги.",
    },
    11: {
        "code": "ar",
        "name": "تجريبي",
        "title": "تجريبي Chicken Road مجاناً | جرّب اللعبة أونلاين",
        "description": "العب نسخة Chicken Road التجريبية مجاناً برصيد افتراضي. تعلّم توقيت السحب، جرّب مستويات الصعوبة وقارن بين التجريبي واللعب بمال حقيقي.",
    },
    12: {
        "code": "az",
        "name": "Demo",
        "title": "Chicken Road demo pulsuz | Oyunu onlayn sınayın",
        "description": "Virtual balansla Chicken Road demosunu pulsuz oynayın. Cash-out vaxtını öyrənin, çətinlik səviyyələrini sınayın və real pul oyunu ilə fərqi görün.",
    },
    13: {
        "code": "bn",
        "name": "ডেমো",
        "title": "Chicken Road ডেমো বিনামূল্যে | অনলাইনে গেম চেষ্টা করুন",
        "description": "ভার্চুয়াল ব্যালেন্সে Chicken Road ডেমো বিনামূল্যে খেলুন। ক্যাশ-আউটের সময়, কঠিনতার স্তর এবং ডেমো ও আসল খেলার পার্থক্য বুঝুন।",
    },
    14: {
        "code": "it",
        "name": "Demo",
        "title": "Demo Chicken Road gratis | Prova il gioco online",
        "description": "Gioca alla demo Chicken Road gratis con saldo virtuale. Impara quando incassare, prova le difficoltà e confronta demo e gioco con soldi veri.",
    },
    15: {
        "code": "nl",
        "name": "Demo",
        "title": "Chicken Road demo gratis | Test het spel online",
        "description": "Speel Chicken Road demo gratis met virtueel saldo. Leer wanneer je uitcasht, test moeilijkheidsgraden en zie het verschil met echt geld spelen.",
    },
    16: {
        "code": "pl",
        "name": "Demo",
        "title": "Demo Chicken Road za darmo | Testuj grę online",
        "description": "Graj w demo Chicken Road za darmo na wirtualnym saldzie. Naucz się wypłacać wygrane, testuj poziomy trudności i porównaj demo z grą na prawdziwe pieniądze.",
    },
    17: {
        "code": "vi",
        "name": "Demo",
        "title": "Demo Chicken Road miễn phí | Thử game trực tuyến",
        "description": "Chơi demo Chicken Road miễn phí với số dư ảo. Học thời điểm rút tiền, thử độ khó và so sánh demo với chơi bằng tiền thật.",
    },
    18: {
        "code": "ua",
        "name": "Демо",
        "title": "Демо Chicken Road безкоштовно | Грайте онлайн без ризику",
        "description": "Грайте в демо Chicken Road безкоштовно на віртуальний баланс. Зрозумійте виведення виграшу, рівні складності та відмінності від гри на гроші.",
    },
    19: {
        "code": "ro",
        "name": "Demo",
        "title": "Demo Chicken Road gratuit | Testează jocul online",
        "description": "Joacă demo Chicken Road gratuit cu sold virtual. Învață când să încasezi, testează dificultățile și compară demo cu jocul pe bani reali.",
    },
}

_FULL_LOCALES = get_all_full_locales()


def e(text: str) -> str:
    return html.escape(text, quote=True)


def img_tag(key: str, alt: str, *, variant: str = "cover") -> str:
    style = IMG_PORTRAIT if variant == "portrait" else (STEP_IMG if variant == "step" else IMG)
    return f'<img {style} src="{IMAGES[key]}" border="0" alt="{e(alt)}" />'


def inline_figure(key: str, alt: str, *, variant: str = "cover") -> str:
    return f"<figure {FIG}>{img_tag(key, alt, variant=variant)}</figure>"


def paras_block(items: list[str]) -> str:
    return "".join(f"<p {P}>{e(p)}</p>" for p in items if p)


def paras_with_figure(items: list[str], after_index: int, key: str, alt: str, *, variant: str = "cover") -> str:
    out: list[str] = []
    for i, p in enumerate(items):
        if p:
            out.append(f"<p {P}>{e(p)}</p>")
        if i == after_index:
            out.append(inline_figure(key, alt, variant=variant))
    return "".join(out)


def ol_block(items: list[str]) -> str:
    lis = "".join(f"<li {OLI}>{e(item)}</li>" for item in items if item)
    return f"<ol>{lis}</ol>"


def ul_block(items: list[str]) -> str:
    lis = "".join(f"<li>{e(item)}</li>" for item in items if item)
    return f"<ul>{lis}</ul>"


def steps_row(body: dict) -> str:
    t = body["titles"]
    a = body["alts"]
    steps = [
        ("step1", "step1_h", "step1_p", "step1"),
        ("step2", "step2_h", "step2_p", "step2"),
        ("step3", "step3_h", "step3_p", "step3"),
    ]
    cols = []
    for img_key, h_key, p_key, alt_key in steps:
        cols.append(
            f"""<div class="col-xl-4 col-lg-4 col-md-6">
<div {STEP_BOX} class="steps_box">{img_tag(img_key, a[alt_key], variant="step")}
<div class="steps_content">
<h3>{e(t[h_key])}</h3>
<p {P}>{e(t[p_key])}</p>
</div>
</div>
</div>"""
        )
    return f"""<div class="row mt-4">
<div class="col-xl-9 col-lg-9 mx-auto">
<div class="row align-items-stretch g-4">
{"".join(cols)}
</div>
</div>
</div>"""


def build_content(body: dict, page_title: str) -> str:
    a = body["alts"]
    faq_items = "".join(
        f"<p><strong>{e(q)}</strong> {e(ans)}</p>" for q, ans in body["faq"]
    )
    why_block = (
        paras_block(body["why_before"])
        + f"<p><strong>{e(body['why_bullets_intro'])}</strong></p>"
        + ul_block(body["why_bullets"])
        + paras_block(body["why_after"])
    )

    return f"""<div class="about_content page-content-lead">
<h1>{e(page_title)}</h1>
</div>
<section id="demo-intro" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["intro_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{paras_with_figure(body["intro_paras"], 1, "gameplay", a["gameplay"])}
</div>
</div>
</div>
</div>
</section>
<section id="what-is-demo" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["what_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-7 col-lg-7 col-md-12">
<div class="about_content">
{paras_block(body["what_paras"])}
</div>
</div>
<div class="col-xl-5 col-lg-5 col-md-12">
<div class="about_content section-media">
{inline_figure("interface", a["interface"])}
</div>
</div>
</div>
</div>
</section>
<section id="how-to-start" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["start_h2"])}</h2>
<p {P}>{e(body["start_summary"])}</p>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{ol_block(body["start_steps"])}
</div>
</div>
</div>
{steps_row(body)}
</div>
</section>
<section id="demo-vs-real" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["vs_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content">
{paras_block(body["vs_paras"])}
</div>
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{inline_figure("gameplay", a["gameplay"])}
</div>
</div>
</div>
</div>
</section>
<section id="why-demo-first" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["why_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{why_block}
</div>
</div>
</div>
</div>
</section>
<section id="demo-mobile" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["mobile_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content">
{paras_block(body["mobile_paras"])}
</div>
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{inline_figure("mobile", a["mobile"], variant="portrait")}
</div>
</div>
</div>
</div>
</section>
<section id="demo-download" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["download_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content">
{paras_block(body["download_paras"])}
</div>
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{inline_figure("app", a["app"])}
</div>
</div>
</div>
</div>
</section>
<section id="demo-troubleshooting" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["trouble_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{paras_block(body["trouble_paras"])}
</div>
</div>
</div>
</div>
</section>
<section id="demo-safety" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["safety_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{paras_with_figure(body["safety_paras"], 2, "step3", a["step3"])}
</div>
</div>
</div>
</div>
</section>
<section id="faq" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["faq_h2"])}</h2>
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
        "start_steps_title": "How to start in four steps",
        "step1_h": "1. Open",
        "step1_p": "Launch the demo in your browser or through the app shortcut from our site.",
        "step2_h": "2. Set up",
        "step2_p": "Choose a difficulty level and set your virtual bet before the round.",
        "step3_h": "3. Cash Out",
        "step3_p": "Move the chicken forward and collect the multiplier when the risk feels enough.",
    }


def english_alts() -> dict:
    return {
        "gameplay": "Chicken Road demo gameplay with multiplier on screen",
        "app": "Chicken Road demo on phone and desktop",
        "mobile": "Chicken Road mobile interface in portrait mode",
        "interface": "Chicken Road interface across devices",
        "step1": "Step 1: open Chicken Road demo",
        "step2": "Step 2: choose difficulty and bet",
        "step3": "Step 3: cash out in Chicken Road demo",
    }


def get_english_body() -> dict:
    en = extract_english()
    return {
        **en,
        "titles": english_titles(),
        "alts": english_alts(),
    }


def locale_body(code: str) -> dict:
    if code == "en":
        return get_english_body()
    if code == "ru":
        return get_russian()
    loc = _FULL_LOCALES.get(code)
    if loc:
        return loc
    return get_english_body()


def word_count(body: dict) -> int:
    total = 0
    for key in (
        "intro_paras",
        "what_paras",
        "vs_paras",
        "why_before",
        "why_after",
        "mobile_paras",
        "download_paras",
        "trouble_paras",
        "safety_paras",
    ):
        for p in body[key]:
            total += len(p.split())
    for p in body["start_steps"] + [body["start_summary"]]:
        total += len(p.split())
    for p in body["why_bullets"]:
        total += len(p.split())
    for q, ans in body["faq"]:
        total += len(q.split()) + len(ans.split())
    return total


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)
    old_by_lang = {loc["lang_id"]: loc for loc in cluster["locales"]}
    new_locales = []
    for lang_id, meta in LOCALE_META.items():
        code = meta["code"]
        body = locale_body(code)
        loc = {
            "lang_id": lang_id,
            "lang_url": old_by_lang[lang_id].get("lang_url", code),
            "url": old_by_lang[lang_id].get("url", "demo"),
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": build_content(body, meta["title"]),
            "status": old_by_lang[lang_id].get("status", "published"),
            "source": old_by_lang[lang_id].get("source", "export"),
            "seo_monitor_ctx": old_by_lang[lang_id].get("seo_monitor_ctx"),
        }
        new_locales.append(loc)

    cluster["locales"] = new_locales
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(cluster, ensure_ascii=False, indent=4)
    OUT_REPO.parent.mkdir(parents=True, exist_ok=True)
    OUT_REPO.write_text(payload + "\n", encoding="utf-8")
    OUT_TMP.write_text(payload + "\n", encoding="utf-8")

    print(f"Wrote {OUT_REPO}")
    print(f"EN body words: {word_count(locale_body('en'))}")
    print(f"RU body words: {word_count(locale_body('ru'))}")
    en_loc = next(x for x in new_locales if x["lang_id"] == 1)
    imgs = sorted(set(re.findall(r'src="([^"]+)"', en_loc["content"])))
    print("Images:", imgs)
    bad = [x for x in imgs if "aviator" in x.lower()]
    if bad:
        raise SystemExit(f"Aviator images remain: {bad}")


if __name__ == "__main__":
    main()
