#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial locale meta patches for selected ProLinks entities."""

from __future__ import annotations

import json
from pathlib import Path


MANIFEST = Path("/Users/gk/Downloads/04/prolinks-meta-clusters/prolinks_meta_manifest.json")
OUT = Path("/Users/gk/Downloads/04/prolinks-meta-locales")
TARGETS = {
    "casino_articles": set(range(14, 26)),
    "games": set(range(1, 7)),
}

# Each line is written for the intended topic, not copied word-for-word from EN.
COPY = {
    "hi": (
        "{name} ✈ Aviator: {topic}",
        "{name} Aviator की गेम, एक्सेस और सुविधाओं की गाइड पढ़ें। USD में दांव, गुणक, कैशआउट और मोबाइल एक्सेस की जानकारी पाएं।",
    ),
    "pt": (
        "{name} ✈ Aviator: {topic}",
        "Veja o guia do {name} Aviator: jogo, acesso e recursos. Inclui apostas em USD, multiplicadores, cashout e uso no celular.",
    ),
    "ru": (
        "{name} ✈ Aviator: {topic}",
        "Читайте гид по {name} Aviator: игре, доступу и функциям. Разбираем ставки в USD, множители, кэшаут и мобильную версию.",
    ),
    "ar": (
        "{name} ✈ Aviator: {topic}",
        "اقرأ دليل {name} Aviator حول اللعبة والوصول والميزات، مع شرح للرهانات بالدولار USD والمضاعفات والسحب من الهاتف.",
    ),
    "az": (
        "{name} ✈ Aviator: {topic}",
        "{name} Aviator üçün oyun, giriş və funksiyalar barədə bələdçini oxuyun. USD mərcləri, əmsallar, cashout və mobil giriş izah olunur.",
    ),
    "bn": (
        "{name} ✈ Aviator: {topic}",
        "{name} Aviator-এর খেলা, অ্যাক্সেস ও ফিচার সম্পর্কে গাইড দেখুন। USD বাজি, মাল্টিপ্লায়ার, ক্যাশআউট ও মোবাইল তথ্য রয়েছে।",
    ),
    "it": (
        "{name} ✈ Aviator: {topic}",
        "Leggi la guida a {name} Aviator: gioco, accesso e funzioni. Trovi informazioni su puntate in USD, moltiplicatori, cashout e mobile.",
    ),
    "nl": (
        "{name} ✈ Aviator: {topic}",
        "Lees de gids voor {name} Aviator: spel, toegang en functies. Bekijk inzetten in USD, multipliers, cashout en mobiel gebruik.",
    ),
    "pl": (
        "{name} ✈ Aviator: {topic}",
        "Przeczytaj przewodnik po {name} Aviator: grze, dostępie i funkcjach. Poznaj stawki w USD, mnożniki, cashout i wersję mobilną.",
    ),
    "vi": (
        "{name} ✈ Aviator: {topic}",
        "Xem hướng dẫn {name} Aviator về trò chơi, truy cập và tính năng. Có thông tin về cược USD, hệ số nhân, cashout và di động.",
    ),
    "ua": (
        "{name} ✈ Aviator: {topic}",
        "Читайте гід по {name} Aviator: грі, доступу та функціях. Розглядаємо ставки в USD, множники, кешаут і мобільну версію.",
    ),
    "ro": (
        "{name} ✈ Aviator: {topic}",
        "Citește ghidul {name} Aviator despre joc, acces și funcții. Găsești informații despre mize în USD, multiplicatori, cashout și mobil.",
    ),
    "sw": (
        "{name} ✈ Aviator: {topic}",
        "Soma mwongozo wa {name} Aviator kuhusu mchezo, ufikiaji na vipengele. Unaeleza dau za USD, vizidishi, cashout na simu.",
    ),
    "ln": (
        "{name} ✈ Aviator: {topic}",
        "Tala buku ya {name} Aviator mpo na masano, bokɔti mpe makoki. Ezali na mabɛtɛ na USD, ba multiplicateur, cashout mpe telefone.",
    ),
}

CASINO_TOPICS = {
    14: ("game guide & how to play", "wapi mchezo hupatikana na raundi ya kawaida inavyochezwa"),
    15: ("demo, app & game guide", "chaguo za demo, app na mchezo wa mtandaoni"),
    16: ("demo, app & game guide", "demo na jinsi app au APK inavyofanya kazi kwenye simu"),
    17: ("access, login & game guide", "upatikanaji wa app au APK na maelezo ya Malawi"),
    18: ("demo, app & game guide", "demo, app na ufikiaji wa mtandaoni"),
    19: ("login, app & how to play", "hatua za kuingia na kufungua mchezo kwenye app"),
    20: ("login, app & how to play", "hatua za kuingia na kufungua mchezo kwenye app"),
    21: ("demo, app & game guide", "kuingia, demo na ufikiaji wa app kabla ya kucheza"),
    22: ("login & how to play", "hatua za kuingia na kuanza raundi"),
    23: ("login & game guide", "kuingia na kufungua mchezo mtandaoni"),
    24: ("game guide & how to play", "wapi mchezo hupatikana na raundi ya kawaida inavyochezwa"),
    25: ("game guide & how to play", "wapi mchezo hupatikana na raundi ya kawaida inavyochezwa"),
}

GAME_DATA = {
    1: ("Aviatrix", "demo, app & how it works", "chaguo za demo, app na jinsi mchezo unavyofanya kazi"),
    2: ("JetX Casino", "demo, rules & how to play", "demo, sheria za mchezo na jinsi raundi zinavyochezwa"),
    3: ("Lottery 7", "login, rules & how to play", "mahali pa kuingia, sheria na mzunguko wa raundi"),
    4: ("Navigator", "rules, access & how to play", "ufikiaji, sheria za msingi na vidhibiti vya mchezo"),
    5: ("Rocketman", "app, rules & how to play", "app, sheria kuu na jinsi raundi zinavyochezwa"),
    6: ("Rocket Gambling", "crash betting guide & app", "mchezo wa crash, dau, multipliers zinazopanda na app"),
}

TOPICS = {
    "hi": {
        "game guide & how to play": "गेम गाइड और कैसे खेलें", "demo, app & game guide": "डेमो, ऐप और गेम गाइड",
        "access, login & game guide": "एक्सेस, लॉगिन और गेम गाइड", "login, app & how to play": "लॉगिन, ऐप और कैसे खेलें",
        "login & how to play": "लॉगिन और कैसे खेलें", "login & game guide": "लॉगिन और गेम गाइड",
        "demo, app & how it works": "डेमो, ऐप और यह कैसे काम करता है", "demo, rules & how to play": "डेमो, नियम और कैसे खेलें",
        "login, rules & how to play": "लॉगिन, नियम और कैसे खेलें", "rules, access & how to play": "नियम, एक्सेस और कैसे खेलें",
        "app, rules & how to play": "ऐप, नियम और कैसे खेलें", "crash betting guide & app": "क्रैश बेटिंग गाइड और ऐप",
    },
    "pt": {"game guide & how to play": "guia do jogo e como jogar", "demo, app & game guide": "demo, app e guia do jogo", "access, login & game guide": "acesso, login e guia do jogo", "login, app & how to play": "login, app e como jogar", "login & how to play": "login e como jogar", "login & game guide": "login e guia do jogo", "demo, app & how it works": "demo, app e como funciona", "demo, rules & how to play": "demo, regras e como jogar", "login, rules & how to play": "login, regras e como jogar", "rules, access & how to play": "regras, acesso e como jogar", "app, rules & how to play": "app, regras e como jogar", "crash betting guide & app": "guia de crash e app"},
    "ru": {"game guide & how to play": "гид по игре и правила", "demo, app & game guide": "демо, приложение и гид", "access, login & game guide": "доступ, вход и гид", "login, app & how to play": "вход, приложение и правила", "login & how to play": "вход и правила игры", "login & game guide": "вход и гид по игре", "demo, app & how it works": "демо, приложение и принцип игры", "demo, rules & how to play": "демо, правила и игра", "login, rules & how to play": "вход, правила и игра", "rules, access & how to play": "правила, доступ и игра", "app, rules & how to play": "приложение, правила и игра", "crash betting guide & app": "гид по crash-ставкам и приложение"},
    "ar": {"game guide & how to play": "دليل اللعبة وطريقة اللعب", "demo, app & game guide": "التجربة والتطبيق ودليل اللعبة", "access, login & game guide": "الوصول وتسجيل الدخول ودليل اللعبة", "login, app & how to play": "تسجيل الدخول والتطبيق وطريقة اللعب", "login & how to play": "تسجيل الدخول وطريقة اللعب", "login & game guide": "تسجيل الدخول ودليل اللعبة", "demo, app & how it works": "التجربة والتطبيق وكيف تعمل", "demo, rules & how to play": "التجربة والقواعد وطريقة اللعب", "login, rules & how to play": "تسجيل الدخول والقواعد وطريقة اللعب", "rules, access & how to play": "القواعد والوصول وطريقة اللعب", "app, rules & how to play": "التطبيق والقواعد وطريقة اللعب", "crash betting guide & app": "دليل رهانات crash والتطبيق"},
}

for code in COPY:
    # A localized, reader-friendly fallback still preserves the core page topic
    # in the title when a market uses English product terminology.
    TOPICS.setdefault(code, {})

FALLBACK_TOPIC = {
    "hi": "गेम गाइड",
    "pt": "guia do jogo",
    "ru": "гид по игре",
    "ar": "دليل اللعبة",
    "az": "oyun bələdçisi",
    "bn": "গেম গাইড",
    "it": "guida al gioco",
    "nl": "spelgids",
    "pl": "przewodnik po grze",
    "vi": "hướng dẫn trò chơi",
    "ua": "гід по грі",
    "ro": "ghid de joc",
    "sw": "mwongozo wa mchezo",
    "ln": "buku ya masano",
}


def brand_from_title(title: str, entity: str, entity_id: int) -> str:
    if entity == "games":
        return GAME_DATA[entity_id][0]
    title = title.replace("Play ", "").replace("✈ ", "").replace("Guide", "").strip()
    for suffix in ("Game: App, APK & Installation Guide", ": Login, App & How to Play", "– Login, App & How to Play",
                   ": Demo, App & Game Guide", "– Demo, App & Game Guide", ": Access, Login & Game Guide",
                   ": Game Guide & How to Play", "Guide: Login & How to Play", "Guide: Login, App & How to Play",
                   "– Login & How to Play", "Guide: Login & Game Guide"):
        title = title.replace(suffix, "")
    return title.strip(" :–")


def build() -> None:
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)
    for row in manifest:
        entity, entity_id = row["entity"], row["entity_id"]
        if entity not in TARGETS or entity_id not in TARGETS[entity]:
            continue
        if entity == "games":
            name, topic_key, details = GAME_DATA[entity_id]
        else:
            topic_key, details = CASINO_TOPICS[entity_id]
            name = brand_from_title(row["en_title"], entity, entity_id)
        locales = {}
        for code, (title_template, desc_template) in COPY.items():
            topic = TOPICS[code].get(topic_key, FALLBACK_TOPIC[code])
            locales[code] = {
                "title": title_template.format(name=name, topic=topic)[:70].rstrip(" ,:;-"),
                "description": desc_template.format(name=name, details=details)[:160].rstrip(" ,:;-"),
            }
        path = OUT / f"{entity}-{entity_id}.json"
        path.write_text(json.dumps({"entity": entity, "entity_id": entity_id, "locales": locales}, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(path)


if __name__ == "__main__":
    build()
