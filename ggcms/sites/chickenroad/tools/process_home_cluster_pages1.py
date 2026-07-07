#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Normalize pages#1 home cluster: EN canonical fixes + locale structural parity."""

from __future__ import annotations

import json
import re
import sys
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DEFAULT_IN = Path("/Users/gk/Downloads/08/seo-pages-1-full.json")

CELL_STYLE = 'style="font-size: 20px; line-height: 1.6; word-wrap: break-word;"'
TABLE_STYLE = 'style="width: 100%; max-width: 100%; table-layout: fixed;"'
TABLE_WRAP = '<div style="width: 100%;" class="table-responsive mt-4">'

WHERE_TO_PLAY: dict[str, dict[str, str]] = {
    "en": {
        "h2": "Chicken Road Game Casino: Where to Play",
        "p1": (
            'Chicken Road is available at several licensed <strong>Chicken Road casino</strong> brands, '
            'each with its own welcome bonus and terms. Compare all '
            '<a href="/en/casinos/">Chicken Road casino options</a>, or go straight to a specific site: '
            '<a href="/en/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/en/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/en/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/en/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/en/casinos/fansport-chicken-road/">FanSport</a>, or '
            '<a href="/en/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Every listed casino runs the same InOut Games build of Chicken Road, so the game itself "
            "doesn't change — what differs is the welcome bonus, payment methods, and licensing. "
            "Check the bonus terms before you deposit, and start in demo mode if a site is new to you."
        ),
    },
    "fr": {
        "h2": "Casino Chicken Road : où jouer",
        "p1": (
            'Chicken Road est disponible chez plusieurs marques de casino agréées, chacune avec son propre '
            'bonus de bienvenue et ses conditions. Comparez toutes les '
            '<a href="/fr/casinos/">options casino Chicken Road</a>, ou allez directement sur un site : '
            '<a href="/fr/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/fr/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/fr/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/fr/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/fr/casinos/fansport-chicken-road/">FanSport</a> ou '
            '<a href="/fr/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Chaque casino listé propose la même version InOut Games de Chicken Road — le jeu ne change pas ; "
            "ce qui diffère, ce sont le bonus, les moyens de paiement et la licence. "
            "Lisez les conditions du bonus avant de déposer et commencez en démo si le site vous est nouveau."
        ),
    },
    "de": {
        "h2": "Chicken Road Casino: Wo spielen",
        "p1": (
            'Chicken Road ist bei mehreren lizenzierten <strong>Chicken Road Casino</strong>-Marken verfügbar, '
            'jeweils mit eigenem Willkommensbonus und AGB. Vergleichen Sie alle '
            '<a href="/de/casinos/">Chicken Road Casino Optionen</a> oder gehen Sie direkt zu: '
            '<a href="/de/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/de/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/de/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/de/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/de/casinos/fansport-chicken-road/">FanSport</a> oder '
            '<a href="/de/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Jedes gelistete Casino nutzt denselben InOut-Games-Build von Chicken Road — das Spiel bleibt gleich; "
            "Unterschiede gibt es bei Bonus, Zahlungsmethoden und Lizenz. "
            "Prüfen Sie die Bonusbedingungen vor der Einzahlung und starten Sie in der Demo, wenn die Seite neu für Sie ist."
        ),
    },
    "es": {
        "h2": "Casino Chicken Road: dónde jugar",
        "p1": (
            'Chicken Road está disponible en varias marcas de casino con licencia, cada una con su bono de bienvenida '
            'y condiciones. Compare todas las '
            '<a href="/es/casinos/">opciones de casino Chicken Road</a> o vaya directamente a: '
            '<a href="/es/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/es/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/es/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/es/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/es/casinos/fansport-chicken-road/">FanSport</a> o '
            '<a href="/es/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Cada casino listado usa la misma versión de InOut Games de Chicken Road; el juego no cambia — "
            "varían el bono, los métodos de pago y la licencia. "
            "Revise los términos del bono antes de depositar y empiece en demo si el sitio es nuevo para usted."
        ),
    },
    "hi": {
        "h2": "Chicken Road कैसीनो: कहाँ खेलें",
        "p1": (
            'Chicken Road कई लाइसेंस प्राप्त <strong>Chicken Road casino</strong> ब्रांडों पर उपलब्ध है, '
            'हर एक का अपना वेलकम बोनस और नियम हैं। सभी '
            '<a href="/hi/casinos/">Chicken Road casino विकल्प</a> देखें, या सीधे जाएँ: '
            '<a href="/hi/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/hi/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/hi/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/hi/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/hi/casinos/fansport-chicken-road/">FanSport</a>, '
            '<a href="/hi/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "हर सूचीबद्ध कैसीनो पर InOut Games का वही Chicken Road बिल्ड चलता है — गेम वही रहता है; "
            "बोनस, भुगतान के तरीके और लाइसेंस अलग हो सकते हैं। "
            "जमा करने से पहले बोनस की शर्तें पढ़ें; नई साइट हो तो पहले डेमो से शुरू करें।"
        ),
    },
    "pt": {
        "h2": "Cassino Chicken Road: onde jogar",
        "p1": (
            'O Chicken Road está disponível em várias marcas de cassino licenciadas, cada uma com bônus de boas-vindas '
            'e termos próprios. Compare todas as '
            '<a href="/pt/casinos/">opções de cassino Chicken Road</a> ou vá direto para: '
            '<a href="/pt/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/pt/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/pt/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/pt/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/pt/casinos/fansport-chicken-road/">FanSport</a> ou '
            '<a href="/pt/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Cada cassino listado usa o mesmo build InOut Games do Chicken Road — o jogo não muda; "
            "o que muda é o bônus, os métodos de pagamento e a licença. "
            "Leia os termos do bônus antes de depositar e comece no demo se o site for novo para você."
        ),
    },
    "ru": {
        "h2": "Казино Chicken Road: где играть",
        "p1": (
            'Chicken Road доступна в нескольких лицензированных <strong>Chicken Road casino</strong> брендах — '
            'у каждого свой welcome bonus и условия. Сравните все '
            '<a href="/ru/casinos/">варианты казино Chicken Road</a> или перейдите сразу на: '
            '<a href="/ru/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/ru/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/ru/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/ru/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/ru/casinos/fansport-chicken-road/">FanSport</a> или '
            '<a href="/ru/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "На каждой площадке из списка — один и тот же билд InOut Games; игра не меняется, "
            "различаются бонус, платёжные методы и лицензия. "
            "Перед депозитом читайте условия бонуса; если сайт новый — начните с демо."
        ),
    },
    "ar": {
        "h2": "كازينو Chicken Road: أين تلعب",
        "p1": (
            'Chicken Road متاحة في عدة علامات كازينو مرخّصة، لكل منها مكافأة ترحيب وشروط خاصة. '
            'قارن كل <a href="/ar/casinos/">خيارات كازينو Chicken Road</a> أو انتقل مباشرة إلى: '
            '<a href="/ar/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/ar/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/ar/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/ar/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/ar/casinos/fansport-chicken-road/">FanSport</a>, '
            '<a href="/ar/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "كل كازينو في القائمة يشغّل نفس نسخة InOut Games — اللعبة لا تتغير؛ "
            "ما يختلف هو المكافأة وطرق الدفع والترخيص. "
            "اقرأ شروط المكافأة قبل الإيداع وابدأ بالديمو إذا كان الموقع جديداً لك."
        ),
    },
    "az": {
        "h2": "Chicken Road kazinosu: harada oynamaq",
        "p1": (
            'Chicken Road bir neçə lisenziyalı <strong>Chicken Road casino</strong> brendində mövcuddur — '
            'hər birinin öz xoş gəldin bonusu və şərtləri var. Bütün '
            '<a href="/az/casinos/">Chicken Road casino seçimlərini</a> müqayisə edin və ya birbaşa keçin: '
            '<a href="/az/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/az/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/az/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/az/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/az/casinos/fansport-chicken-road/">FanSport</a>, '
            '<a href="/az/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Siyahıdakı hər kazinoda eyni InOut Games build-i işləyir — oyun dəyişmir; "
            "bonus, ödəniş üsulları və lisenziya fərqlənir. "
            "Depozitdən əvvəl bonus şərtlərini oxuyun; sayt yeni dirsə, demodan başlayın."
        ),
    },
    "bn": {
        "h2": "Chicken Road ক্যাসিনো: কোথায় খেলবেন",
        "p1": (
            'Chicken Road কয়েকটি লাইসেন্সপ্রাপ্ত <strong>Chicken Road casino</strong> ব্র্যান্ডে পাওয়া যায় — '
            'প্রতিটির নিজস্ব ওয়েলকাম বোনাস ও শর্ত আছে। সব '
            '<a href="/bn/casinos/">Chicken Road casino অপশন</a> দেখুন, বা সরাসরি যান: '
            '<a href="/bn/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/bn/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/bn/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/bn/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/bn/casinos/fansport-chicken-road/">FanSport</a>, '
            '<a href="/bn/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "তালিকাভুক্ত প্রতিটি ক্যাসিনোতে একই InOut Games বিল্ড চলে — গেম একই; "
            "বোনাস, পেমেন্ট পদ্ধতি ও লাইসেন্স আলাদা হতে পারে। "
            "ডিপোজিটের আগে বোনাসের শর্ত পড়ুন; সাইট নতুন হলে ডেমো দিয়ে শুরু করুন।"
        ),
    },
    "it": {
        "h2": "Casinò Chicken Road: dove giocare",
        "p1": (
            'Chicken Road è disponibile su diversi brand di casinò con licenza, ciascuno con bonus di benvenuto '
            'e termini propri. Confronta tutte le '
            '<a href="/it/casinos/">opzioni casinò Chicken Road</a> o vai direttamente a: '
            '<a href="/it/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/it/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/it/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/it/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/it/casinos/fansport-chicken-road/">FanSport</a> o '
            '<a href="/it/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Ogni casinò elencato usa lo stesso build InOut Games di Chicken Road — il gioco non cambia; "
            "cambiano bonus, metodi di pagamento e licenza. "
            "Leggi i termini del bonus prima del deposito e inizia in demo se il sito è nuovo per te."
        ),
    },
    "nl": {
        "h2": "Chicken Road casino: waar spelen",
        "p1": (
            'Chicken Road is beschikbaar bij meerdere gelicentieerde <strong>Chicken Road casino</strong>-merken, '
            'elk met eigen welkomstbonus en voorwaarden. Vergelijk alle '
            '<a href="/nl/casinos/">Chicken Road casino-opties</a> of ga direct naar: '
            '<a href="/nl/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/nl/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/nl/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/nl/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/nl/casinos/fansport-chicken-road/">FanSport</a> of '
            '<a href="/nl/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Elk casino in de lijst draait dezelfde InOut Games-build — het spel verandert niet; "
            "bonus, betaalmethoden en licentie wel. "
            "Lees bonusvoorwaarden vóór storten en start in demo als de site nieuw voor je is."
        ),
    },
    "pl": {
        "h2": "Kasyno Chicken Road: gdzie grać",
        "p1": (
            'Chicken Road jest dostępna w kilku licencjonowanych markach <strong>Chicken Road casino</strong>, '
            'każda z własnym bonusem powitalnym i warunkami. Porównaj wszystkie '
            '<a href="/pl/casinos/">opcje kasyna Chicken Road</a> lub przejdź od razu do: '
            '<a href="/pl/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/pl/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/pl/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/pl/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/pl/casinos/fansport-chicken-road/">FanSport</a> lub '
            '<a href="/pl/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "W każdym kasynie z listy działa ten sam build InOut Games — gra się nie zmienia; "
            "różnią się bonus, metody płatności i licencja. "
            "Przed wpłatą przeczytaj warunki bonusu; jeśli strona jest nowa, zacznij od demo."
        ),
    },
    "vi": {
        "h2": "Casino Chicken Road: chơi ở đâu",
        "p1": (
            'Chicken Road có tại nhiều thương hiệu casino được cấp phép, mỗi nơi có bonus chào mừng và điều khoản riêng. '
            'So sánh mọi <a href="/vi/casinos/">lựa chọn casino Chicken Road</a> hoặc vào thẳng: '
            '<a href="/vi/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/vi/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/vi/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/vi/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/vi/casinos/fansport-chicken-road/">FanSport</a>, '
            '<a href="/vi/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Mỗi casino trong danh sách chạy cùng bản build InOut Games — game không đổi; "
            "khác nhau ở bonus, phương thức thanh toán và giấy phép. "
            "Đọc điều khoản bonus trước khi nạp; nếu site mới với bạn, hãy bắt đầu bằng demo."
        ),
    },
    "ua": {
        "h2": "Казино Chicken Road: де грати",
        "p1": (
            'Chicken Road доступна в кількох ліцензованих брендах <strong>Chicken Road casino</strong> — '
            'у кожного свій welcome bonus і умови. Порівняйте всі '
            '<a href="/ua/casinos/">варіанти казино Chicken Road</a> або перейдіть одразу на: '
            '<a href="/ua/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/ua/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/ua/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/ua/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/ua/casinos/fansport-chicken-road/">FanSport</a> або '
            '<a href="/ua/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "На кожному майданчику зі списку — той самий build InOut Games; гра не змінюється, "
            "відрізняються бонус, платіжні методи та ліцензія. "
            "Перед депозитом читайте умови бонусу; якщо сайт новий — почніть із демо."
        ),
    },
    "ro": {
        "h2": "Cazino Chicken Road: unde joci",
        "p1": (
            'Chicken Road este disponibil la mai multe branduri de cazino licențiate, fiecare cu bonus de bun venit '
            'și termeni proprii. Compară toate <a href="/ro/casinos/">opțiunile de cazino Chicken Road</a> '
            'sau mergi direct la: '
            '<a href="/ro/casinos/1win-chicken-road/">1win</a>, '
            '<a href="/ro/casinos/mostbet-chicken-road/">Mostbet</a>, '
            '<a href="/ro/casinos/bc-game-chicken-road/">BC.Game</a>, '
            '<a href="/ro/casinos/1xbet-chicken-road/">1xBet</a>, '
            '<a href="/ro/casinos/fansport-chicken-road/">FanSport</a> sau '
            '<a href="/ro/casinos/jack-pot-chicken-road/">Jack-Pot</a>.'
        ),
        "p2": (
            "Fiecare cazino listat rulează același build InOut Games — jocul nu se schimbă; "
            "diferă bonusul, metodele de plată și licența. "
            "Citește termenii bonusului înainte de depunere și începe în demo dacă site-ul e nou pentru tine."
        ),
    },
}


def _p(text: str) -> str:
    return f'<p {CELL_STYLE}>{text}</p>'


def build_where_to_play(lang: str) -> str:
    block = WHERE_TO_PLAY.get(lang) or WHERE_TO_PLAY["en"]
    return (
        '<section id="where-to-play" class="mt-5 pt-5">\n'
        '<div class="container">\n'
        '<div class="col-12">\n'
        '<div class="main_heading">\n'
        f'<h2>{block["h2"]}</h2>\n'
        '</div>\n'
        '</div>\n'
        '<div class="row mt-3">\n'
        '<div class="col-12">\n'
        '<div class="about_content">\n'
        f'{_p(block["p1"])}\n'
        f'{_p(block["p2"])}\n'
        '</div>\n'
        '</div>\n'
        '</div>\n'
        '</div>\n'
        '</section>'
    )


def ensure_h1_lead(html: str, title: str) -> str:
    """Homepage title is rendered in hero H2; do not inject hidden page-content-lead H1."""
    return html


def localize_hrefs(html: str, lang: str) -> str:
    if lang == "en":
        return html
    return re.sub(r'href="/en/', f'href="/{lang}/', html)


def is_internal_href(href: str) -> bool:
    href = (href or "").strip()
    if not href or href.startswith("#"):
        return False
    if href.startswith("//"):
        return False
    if href.startswith("http://") or href.startswith("https://"):
        return "chickenroad.run" in href
    return href.startswith("/")


def wrap_internal_links_noads(html: str) -> str:
    """Wrap internal <a> tags in <noads> when not already wrapped."""

    def repl(m: re.Match[str]) -> str:
        tag = m.group(0)
        if re.search(r'href\s*=\s*["\']([^"\']+)["\']', tag, re.I):
            href = re.search(r'href\s*=\s*["\']([^"\']+)["\']', tag, re.I).group(1)
        else:
            return tag
        if not is_internal_href(href):
            return tag
        return f"<noads>{tag}</noads>"

    parts: list[str] = []
    pos = 0
    for m in re.finditer(r"<a\b[^>]*>.*?</a>", html, re.I | re.S):
        chunk = html[pos : m.start()]
        if "<noads>" in chunk.split("<a")[-1] if "<a" in chunk else False:
            parts.append(html[pos : m.end()])
            pos = m.end()
            continue
        # Skip if immediately preceded by opening noads without close
        pre = html[max(0, m.start() - 80) : m.start()]
        if re.search(r"<noads>\s*$", pre, re.I):
            parts.append(html[pos : m.end()])
            pos = m.end()
            continue
        parts.append(html[pos : m.start()])
        parts.append(repl(m))
        pos = m.end()
    parts.append(html[pos:])
    out = "".join(parts)
    # Collapse double wraps
    out = re.sub(r"<noads>\s*<noads>", "<noads>", out, flags=re.I)
    out = re.sub(r"</noads>\s*</noads>", "</noads>", out, flags=re.I)
    return out


def normalize_tables(html: str) -> str:
    def fix_table(m: re.Match[str]) -> str:
        block = m.group(0)
        if "table-layout: fixed" in block:
            return block
        block = re.sub(
            r"<table([^>]*)>",
            lambda tm: f'<table class="table table-bordered" {TABLE_STYLE}>',
            block,
            count=1,
            flags=re.I,
        )
        block = re.sub(r"<th([^>]*)>", f"<th {CELL_STYLE}>", block, flags=re.I)
        block = re.sub(r"<td([^>]*)>", f"<td {CELL_STYLE}>", block, flags=re.I)
        if "table-responsive" not in block:
            block = block.replace(
                "<table",
                f'{TABLE_WRAP}\n<table',
                1,
            )
            block = block + "\n</div>"
        elif 'style="width: 100%;"' not in block:
            block = block.replace(
                'class="table-responsive"',
                'style="width: 100%;" class="table-responsive mt-4"',
                1,
            )
        return block

    return re.sub(
        r"(?:<div[^>]*table-responsive[^>]*>\s*)?<table[\s\S]*?</table>\s*(?:</div>)?",
        fix_table,
        html,
        flags=re.I,
    )


def insert_where_to_play(html: str, lang: str) -> str:
    if 'id="where-to-play"' in html:
        return html
    section = build_where_to_play(lang)
    anchor = '<section id="tips"'
    if anchor in html:
        return html.replace(anchor, section + "\n" + anchor, 1)
    anchor = '<section id="game-specs"'
    if anchor in html:
        return html.replace(anchor, section + "\n" + anchor, 1)
    return html + "\n" + section


def h1_title_from_locale(loc: dict) -> str:
    title = (loc.get("title") or "").strip()
    if title:
        return re.sub(r"<[^>]+>", "", title)
    name = (loc.get("name") or "").strip()
    return name


def promote_main_heading_h3_to_h2(html: str) -> str:
    """Locale exports used h3 in main_heading; canonical EN uses h2."""

    def repl(m: re.Match[str]) -> str:
        block = m.group(0)
        return re.sub(r"<h3\b", "<h2", block, flags=re.I).replace("</h3>", "</h2>")

    return re.sub(
        r'<div class="main_heading">[\s\S]*?</div>',
        repl,
        html,
        flags=re.I,
    )


def process_locale(loc: dict, *, is_canonical: bool = False) -> dict:
    out = deepcopy(loc)
    lang = (loc.get("lang_url") or "en").strip().lower()
    html = out.get("content") or ""
    title = h1_title_from_locale(out)

    html = localize_hrefs(html, lang)
    html = insert_where_to_play(html, lang)
    html = ensure_h1_lead(html, title)
    if not is_canonical:
        html = promote_main_heading_h3_to_h2(html)
    html = normalize_tables(html)
    html = wrap_internal_links_noads(html)

    out["content"] = html
    return out


def stats(html: str) -> dict:
    return {
        "bytes": len(html),
        "h1": len(re.findall(r"<h1\b", html, re.I)),
        "h2": len(re.findall(r"<h2\b", html, re.I)),
        "sections": len(re.findall(r"<section\b", html, re.I)),
        "noads": len(re.findall(r"<noads>\s*<a\b", html, re.I)),
        "internal_a": len(
            [
                1
                for m in re.finditer(r'<a\b[^>]*href=["\']([^"\']+)["\']', html, re.I)
                if is_internal_href(m.group(1))
            ]
        ),
        "tables": len(re.findall(r"<table\b", html, re.I)),
    }


def main() -> None:
    src = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_IN
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))

    new_locales = []
    en_stats = None
    for loc in data.get("locales", []):
        is_en = int(loc.get("lang_id", 0)) == 1
        processed = process_locale(loc, is_canonical=is_en)
        st = stats(processed.get("content") or "")
        if is_en:
            en_stats = st
        lang = processed.get("lang_url") or loc.get("lang_id")
        print(
            f"{lang}: h1={st['h1']} h2={st['h2']} sections={st['sections']} "
            f"noads={st['noads']}/{st['internal_a']} tables={st['tables']} bytes={st['bytes']}"
        )
        new_locales.append(processed)

    data["locales"] = new_locales
    data["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(data, ensure_ascii=False, indent=4)
    dst.write_text(payload + "\n", encoding="utf-8")
    print(f"\nWrote {dst}")
    if en_stats:
        print(f"EN canonical: {en_stats}")


if __name__ == "__main__":
    main()
