#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#5 download cluster — ChickenRoaddownload.html in site layout."""

from __future__ import annotations

import html
import json
import re
from datetime import datetime, timezone
from pathlib import Path

from chickenroad_download_full_locales import get_all_full_locales
from chickenroad_download_full_ru import get_russian
from chickenroad_download_html_extract import extract_english

ROOT = Path(__file__).resolve().parents[1]
CLUSTER_IN = ROOT / "tmp/jason/seo-pages-5-full.json"
OUT_REPO = ROOT / "site/files/reference/seo-pages-5-full.json"
OUT_DL = ROOT / "tmp/jason/seo-pages-5-full.json"

IMAGES = {
    "hero": "/assets/images/chickenroad-download-hero.webp",
    "app": "/assets/images/chickenroad-app-desktop-mobile.webp",
    "mobile": "/assets/images/chickenroad-mobile.webp",
    "gameplay": "/assets/images/chickenroad-gameplay.webp",
    "interface": "/assets/images/chickenroad-download-interface.webp",
}

LOCALE_META = {
    1: {
        "code": "en",
        "name": "Download",
        "title": "Chicken Road App Download: Android, iPhone and PC",
        "description": "Download the Chicken Road demo app for Android and iPhone, check system requirements, and learn how the shortcut works before real-money play.",
    },
    3: {
        "code": "fr",
        "name": "Télécharger",
        "title": "Télécharger Chicken Road : Android, iPhone et PC",
        "description": "Téléchargez l’app démo Chicken Road sur Android et iPhone, consultez les prérequis et découvrez le raccourci avant le jeu en argent réel.",
    },
    4: {
        "code": "de",
        "name": "Download",
        "title": "Chicken Road App: Download für Android, iPhone und PC",
        "description": "Laden Sie die Chicken-Road-Demo-App für Android und iPhone herunter, prüfen Sie die Systemanforderungen und testen Sie den Shortcut.",
    },
    6: {
        "code": "es",
        "name": "Descargar",
        "title": "Descargar Chicken Road: Android, iPhone y PC",
        "description": "Descarga la app demo de Chicken Road en Android e iPhone, revisa requisitos del sistema y prueba el acceso directo antes de jugar con dinero real.",
    },
    7: {
        "code": "hi",
        "name": "डाउनलोड",
        "title": "Chicken Road ऐप डाउनलोड: Android, iPhone और PC",
        "description": "Android और iPhone के लिए Chicken Road डेमो ऐप डाउनलोड करें, सिस्टम आवश्यकताएँ देखें और असली पैसे से पहले शॉर्टकट आज़माएँ।",
    },
    8: {
        "code": "pt",
        "name": "Download",
        "title": "Download Chicken Road: Android, iPhone e PC",
        "description": "Baixe o app demo do Chicken Road para Android e iPhone, veja requisitos de sistema e teste o atalho antes de jogar com dinheiro real.",
    },
    9: {
        "code": "ru",
        "name": "Скачать",
        "title": "Скачать Chicken Road: Android, iPhone и PC",
        "description": "Скачайте демо-приложение Chicken Road для Android и iPhone, проверьте системные требования и протестируйте быстрый доступ до игры на деньги.",
    },
    11: {
        "code": "ar",
        "name": "تنزيل",
        "title": "تنزيل Chicken Road: Android وiPhone وPC",
        "description": "حمّل تطبيق Chicken Road التجريبي على Android وiPhone، واطّلع على المتطلبات وتعرّف على الاختصار قبل اللعب بمال حقيقي.",
    },
    12: {
        "code": "az",
        "name": "Yüklə",
        "title": "Chicken Road yüklə: Android, iPhone və PC",
        "description": "Android və iPhone üçün Chicken Road demo tətbiqini yükləyin, sistem tələblərinə baxın və real pul oyunundan əvvəl qısayolu sınayın.",
    },
    13: {
        "code": "bn",
        "name": "ডাউনলোড",
        "title": "Chicken Road অ্যাপ ডাউনলোড: Android, iPhone ও PC",
        "description": "Android ও iPhone-এ Chicken Road ডেমো অ্যাপ ডাউনলোড করুন, সিস্টেম চাহিদা দেখুন এবং আসল টাকায় খেলার আগে শর্টকাট চেষ্টা করুন।",
    },
    14: {
        "code": "it",
        "name": "Download",
        "title": "Download Chicken Road: Android, iPhone e PC",
        "description": "Scarica l’app demo Chicken Road per Android e iPhone, controlla i requisiti di sistema e prova la scorciatoia prima del gioco con soldi veri.",
    },
    15: {
        "code": "nl",
        "name": "Download",
        "title": "Chicken Road download: Android, iPhone en PC",
        "description": "Download de Chicken Road demo-app voor Android en iPhone, bekijk systeemvereisten en test de snelkoppeling vóór echt geld spelen.",
    },
    16: {
        "code": "pl",
        "name": "Pobierz",
        "title": "Pobierz Chicken Road: Android, iPhone i PC",
        "description": "Pobierz aplikację demo Chicken Road na Android i iPhone, sprawdź wymagania systemowe i przetestuj skrót przed grą na prawdziwe pieniądze.",
    },
    17: {
        "code": "vi",
        "name": "Tải xuống",
        "title": "Tải Chicken Road: Android, iPhone và PC",
        "description": "Tải app demo Chicken Road cho Android và iPhone, xem yêu cầu hệ thống và thử lối tắt trước khi chơi bằng tiền thật.",
    },
    18: {
        "code": "ua",
        "name": "Завантажити",
        "title": "Завантажити Chicken Road: Android, iPhone та PC",
        "description": "Завантажте демо-додаток Chicken Road для Android і iPhone, перевірте системні вимоги та протестуйте швидкий доступ перед грою на гроші.",
    },
    19: {
        "code": "ro",
        "name": "Descărcare",
        "title": "Descarcă Chicken Road: Android, iPhone și PC",
        "description": "Descarcă aplicația demo Chicken Road pentru Android și iPhone, verifică cerințele de sistem și testează scurtătura înainte de joc pe bani reali.",
    },
}

_FULL_LOCALES = get_all_full_locales()


def e(text: str) -> str:
    return html.escape(text, quote=True)


def img_tag(key: str, alt: str) -> str:
    return f'<img src="{IMAGES[key]}" border="0" alt="{e(alt)}" />'


def inline_figure(key: str, alt: str) -> str:
    return f'<figure class="section-media__figure">{img_tag(key, alt)}</figure>'


def paras_block(items: list[str]) -> str:
    return "".join(f"<p>{e(p)}</p>" for p in items if p)


def paras_with_figure(items: list[str], after_index: int, key: str, alt: str) -> str:
    out: list[str] = []
    for i, p in enumerate(items):
        if p:
            out.append(f"<p>{e(p)}</p>")
        if i == after_index:
            out.append(inline_figure(key, alt))
    return "".join(out)


def table_2col(rows: list[tuple[str, str]], h1: str, h2: str) -> str:
    body = "".join(f"<tr><td>{e(a)}</td><td>{e(b)}</td></tr>" for a, b in rows)
    return f"""<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>{e(h1)}</th><th>{e(h2)}</th></tr></thead>
<tbody>{body}</tbody>
</table>
</div>"""


def table_3col(rows: list[tuple[str, str, str]], headers: tuple[str, str, str]) -> str:
    body = "".join(
        f"<tr><td>{e(a)}</td><td>{e(b)}</td><td>{e(c)}</td></tr>" for a, b, c in rows
    )
    h1, h2, h3 = headers
    return f"""<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>{e(h1)}</th><th>{e(h2)}</th><th>{e(h3)}</th></tr></thead>
<tbody>{body}</tbody>
</table>
</div>"""


def table_4col(rows: list[tuple[str, str, str, str]], headers: tuple[str, str, str, str]) -> str:
    body = "".join(
        f"<tr><td>{e(a)}</td><td>{e(b)}</td><td>{e(c)}</td><td>{e(d)}</td></tr>"
        for a, b, c, d in rows
    )
    h1, h2, h3, h4 = headers
    return f"""<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>{e(h1)}</th><th>{e(h2)}</th><th>{e(h3)}</th><th>{e(h4)}</th></tr></thead>
<tbody>{body}</tbody>
</table>
</div>"""


def build_content(body: dict, page_title: str) -> str:
    t = body["titles"]
    a = body["alts"]
    faq_items = "".join(
        f"<p><strong>{e(q)}</strong> {e(ans)}</p>" for q, ans in body["faq"]
    )

    hero_fig = inline_figure("hero", a["hero"])
    return f"""<div class="about_content page-content-lead">
<h1>{e(page_title)}</h1>
</div>
<section id="download-intro" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="about_content">
{hero_fig}
{paras_with_figure(body["intro_paras"], 2, "app", a["app"])}
</div>
</div>
</div>
</section>
<section id="what-is-app" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["what_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{paras_block(body["what_paras"])}
</div>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
{table_2col(body["spec_table"], t["spec_h1"], t["spec_h2"])}
</div>
</div>
</div>
</section>
<section id="system-requirements" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["req_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
{table_3col(body["req_table"], (t["req_h1"], t["req_h2"], t["req_h3"]))}
</div>
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content section-media">
{inline_figure("mobile", a["mobile"])}
</div>
</div>
</div>
</div>
</section>
<section id="how-to-download" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["download_h2"])}</h2>
</div>
</div>
<div class="row mt-4">
<div class="col-12">
<div class="about_content">
{paras_block(body["download_paras"])}
</div>
</div>
</div>
</div>
</section>
<section id="platform-differences" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["diff_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-7 col-lg-7 col-md-12">
{table_4col(body["diff_table"], (t["diff_h1"], t["diff_h2"], t["diff_h3"], t["diff_h4"]))}
</div>
<div class="col-xl-5 col-lg-5 col-md-12">
<div class="about_content section-media">
{inline_figure("interface", a["interface"])}
</div>
</div>
</div>
</div>
</section>
<section id="how-to-use" class="mt-5 pt-5">
<div class="container">
<div class="col-12">
<div class="main_heading">
<h2>{e(body["use_h2"])}</h2>
</div>
</div>
<div class="row mt-4 align-items-start g-4">
<div class="col-xl-6 col-lg-6 col-md-12">
<div class="about_content">
{paras_block(body["use_paras"])}
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
        "spec_h1": "Specification",
        "spec_h2": "Details",
        "req_h1": "Parameter",
        "req_h2": "Android",
        "req_h3": "iOS",
        "diff_h1": "Feature",
        "diff_h2": "Android",
        "diff_h3": "iOS",
        "diff_h4": "PC",
    }


def english_alts() -> dict:
    return {
        "hero": "Chicken Road app download on mobile and desktop",
        "app": "Chicken Road demo app on phone and computer",
        "mobile": "Chicken Road mobile interface in portrait mode",
        "interface": "Chicken Road app interface across devices",
        "gameplay": "Chicken Road demo gameplay with multiplier on screen",
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
        "download_paras",
        "use_paras",
    ):
        for p in body[key]:
            total += len(p.split())
    for rows, n in ((body["spec_table"], 2), (body["req_table"], 3), (body["diff_table"], 4)):
        for row in rows:
            for cell in row[:n]:
                total += len(cell.split())
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
            "url": old_by_lang[lang_id].get("url", "download"),
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

    print(f"Wrote {OUT_REPO}")
    print(f"EN body words: {word_count(locale_body('en'))}")
    print(f"RU body words: {word_count(locale_body('ru'))}")
    en_loc = next(x for x in new_locales if x["lang_id"] == 1)
    print("Images:", sorted(set(re.findall(r'src=\"([^\"]+)\"', en_loc["content"]))))


if __name__ == "__main__":
    main()
