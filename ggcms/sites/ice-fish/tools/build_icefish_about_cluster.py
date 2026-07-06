#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#26 about-us cluster for Ice Fish."""

from __future__ import annotations

import json
import re
from datetime import datetime, timezone
from pathlib import Path

from icefish_about_locales import get_content

ROOT = Path(__file__).resolve().parents[1]
CLUSTER_IN = ROOT / "tmp/jason/seo-pages-26-full.json"
OUT_REPO = ROOT / "site/files/reference/seo-pages-26-full.json"
OUT_TMP = ROOT / "tmp/jason/seo-pages-26-full.json"

LOCALE_META = {
    1: {
        "code": "en",
        "name": "About Us",
        "title": "About Us | Ice Fish",
        "description": "Independent guide to Ice Fish, casinos, and responsible play—not a gambling operator.",
    },
    3: {
        "code": "fr",
        "name": "À propos",
        "title": "À propos | Ice Fish",
        "description": "Présentation du site : guide indépendant sur Ice Fish, les casinos et le jeu responsable.",
    },
    4: {
        "code": "de",
        "name": "Über uns",
        "title": "Über uns | Ice Fish",
        "description": "Unabhängiger Leitfaden zu Ice Fish, Casinos und verantwortungsvollem Spiel—kein Glücksspielanbieter.",
    },
    6: {
        "code": "es",
        "name": "Acerca de",
        "title": "Acerca de | Ice Fish",
        "description": "Guía independiente sobre Ice Fish, casinos y juego responsable—no somos un operador de apuestas.",
    },
    7: {
        "code": "hi",
        "name": "हमारे बारे में",
        "title": "हमारे बारे में | Ice Fish",
        "description": "Ice Fish, कैसिनो और जिम्मेदार खेल के लिए स्वतंत्र गाइड—कोई जुआ ऑपरेटर नहीं।",
    },
    8: {
        "code": "pt",
        "name": "Sobre nós",
        "title": "Sobre nós | Ice Fish",
        "description": "Guia independente sobre Ice Fish, cassinos e jogo responsável—não somos operador de apostas.",
    },
    9: {
        "code": "ru",
        "name": "О нас",
        "title": "О нас | Ice Fish",
        "description": "Независимый справочник о Ice Fish, казино и ответственной игре — мы не оператор азартных игр.",
    },
    11: {
        "code": "ar",
        "name": "حولنا",
        "title": "حولنا | Ice Fish",
        "description": "دليل مستقل عن Ice Fish والكازينوهات واللعب المسؤول—لسنا مشغّل مقامرة.",
    },
    12: {
        "code": "az",
        "name": "Haqqımızda",
        "title": "Haqqımızda | Ice Fish",
        "description": "Ice Fish, kazinolar və məsuliyyətli oyun üçün müstəqil bələdçi—qumar operatoru deyilik.",
    },
    13: {
        "code": "bn",
        "name": "আমাদের সম্পর্কে",
        "title": "আমাদের সম্পর্কে | Ice Fish",
        "description": "Ice Fish, ক্যাসিনো ও দায়িত্বশীল খেলার স্বাধীন গাইড—আমরা গেমিং অপারেটর নই।",
    },
    14: {
        "code": "it",
        "name": "Chi siamo",
        "title": "Chi siamo | Ice Fish",
        "description": "Guida indipendente a Ice Fish, casinò e gioco responsabile—non siamo un operatore di scommesse.",
    },
    15: {
        "code": "nl",
        "name": "Over ons",
        "title": "Over ons | Ice Fish",
        "description": "Onafhankelijke gids over Ice Fish, casino's en verantwoord spelen—geen gokoperateur.",
    },
    16: {
        "code": "pl",
        "name": "O nas",
        "title": "O nas | Ice Fish",
        "description": "Niezależny przewodnik po Ice Fish, kasynach i odpowiedzialnej grze—nie jesteśmy operatorem hazardu.",
    },
    17: {
        "code": "vi",
        "name": "Giới thiệu",
        "title": "Giới thiệu | Ice Fish",
        "description": "Hướng dẫn độc lập về Ice Fish, casino và chơi có trách nhiệm—không phải nhà điều hành cờ bạc.",
    },
    18: {
        "code": "ua",
        "name": "Про нас",
        "title": "Про нас | Ice Fish",
        "description": "Незалежний довідник про Ice Fish, казино та відповідальну гру — ми не оператор азартних ігор.",
    },
    19: {
        "code": "ro",
        "name": "Despre noi",
        "title": "Despre noi | Ice Fish",
        "description": "Ghid independent despre Ice Fish, cazinouri și joc responsabil—nu suntem operator de jocuri de noroc.",
    },
}


def verify_locales(locales: list[dict]) -> None:
    bad: list[str] = []
    for loc in locales:
        lid = loc["lang_id"]
        code = LOCALE_META[lid]["code"]
        c = loc["content"]
        if len(loc["title"]) > 70:
            bad.append(f"{code}: title {len(loc['title'])} chars")
        if len(loc["description"]) > 160:
            bad.append(f"{code}: description {len(loc['description'])} chars")
        if c.lower().count("<h1") != 1:
            bad.append(f"{code}: h1 count != 1")
        if c.lower().count("<h2") != 4:
            bad.append(f"{code}: h2 count != 4")
        for needle in ("aviator", "spribe", "crash game", "crash-game"):
            if needle in c.lower():
                bad.append(f"{code}: still contains {needle!r}")
        if "<p>\n<h2" in c or "<p>\r\n<h2" in c:
            bad.append(f"{code}: broken paragraph before h2")
    if bad:
        raise SystemExit("Verification failed:\n" + "\n".join(bad))


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)
    old_by_lang = {loc["lang_id"]: loc for loc in cluster["locales"]}
    new_locales = []
    for lang_id, meta in LOCALE_META.items():
        code = meta["code"]
        old = old_by_lang[lang_id]
        loc = {
            "lang_id": lang_id,
            "lang_url": old.get("lang_url", code),
            "url": old.get("url", "about-us"),
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": get_content(code),
            "status": old.get("status", "published"),
            "source": old.get("source", "export"),
            "seo_monitor_ctx": old.get("seo_monitor_ctx"),
        }
        new_locales.append(loc)

    verify_locales(new_locales)
    cluster["locales"] = new_locales
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(cluster, ensure_ascii=False, indent=4)
    OUT_REPO.parent.mkdir(parents=True, exist_ok=True)
    OUT_REPO.write_text(payload + "\n", encoding="utf-8")
    OUT_TMP.write_text(payload + "\n", encoding="utf-8")

    en = next(x for x in new_locales if x["lang_id"] == 1)
    print(f"Wrote {OUT_REPO}")
    print(f"EN content length: {len(en['content'])}")
    for loc in new_locales:
        code = LOCALE_META[loc["lang_id"]]["code"]
        print(
            f"{code}: title={len(loc['title'])} desc={len(loc['description'])} "
            f"content={len(loc['content'])}"
        )


if __name__ == "__main__":
    main()
