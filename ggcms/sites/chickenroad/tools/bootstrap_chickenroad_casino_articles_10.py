#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Bootstrap casino_articles#10 EN body into locales.py."""

from __future__ import annotations

import json
from pathlib import Path

from chickenroad_casino_articles_10_parse import fix_en_body, parse_content_to_body

CLUSTER = Path("/Users/gk/Downloads/05/seo-casino_articles-10-full.json")
TOOLS = Path(__file__).resolve().parent

LOCALE_META = {
    1: {
        "code": "en",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road on 1win: alternatives, demo and tips",
        "description": "Chicken Road is not on 1win yet — find Turbo Games alternatives, demo play, mobile tips, bonuses, safety notes and FAQ.",
        "url": "1win-chicken-road",
    },
    3: {
        "code": "fr",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road sur 1win : alternatives, démo et conseils",
        "description": "Chicken Road n’est pas sur 1win — alternatives Turbo Games, démo, mobile, bonus, sécurité et FAQ.",
        "url": "1win-chicken-road",
    },
    4: {
        "code": "de",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road auf 1win: Alternativen, Demo & Tipps",
        "description": "Chicken Road ist auf 1win nicht verfügbar — Turbo Games Alternativen, Demo, Mobile, Boni, Sicherheit und FAQ.",
        "url": "1win-chicken-road",
    },
    6: {
        "code": "es",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road en 1win: alternativas, demo y consejos",
        "description": "Chicken Road no está en 1win — alternativas Turbo Games, demo, móvil, bonos, seguridad y FAQ.",
        "url": "1win-chicken-road",
    },
    7: {
        "code": "hi",
        "name": "1WIN - Chicken Road",
        "title": "1win पर Chicken Road: विकल्प, डेमो और टिप्स",
        "description": "Chicken Road 1win पर नहीं है — Turbo Games विकल्प, डेमो, मोबाइल, बोनस, सुरक्षा और FAQ।",
        "url": "1win-chicken-road",
    },
    8: {
        "code": "pt",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road na 1win: alternativas, demo e dicas",
        "description": "Chicken Road não está na 1win — alternativas Turbo Games, demo, mobile, bônus, segurança e FAQ.",
        "url": "1win-chicken-road",
    },
    9: {
        "code": "ru",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road на 1win: альтернативы, демо и советы",
        "description": "Chicken Road нет на 1win — альтернативы Turbo Games, демо, мобильная игра, бонусы, безопасность и FAQ.",
        "url": "1win-chicken-road",
    },
    11: {
        "code": "ar",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road على 1win: بدائل وديمو ونصائح",
        "description": "Chicken Road غير متاح على 1win — بدائل Turbo Games، تجريبي، موبايل، مكافآت، أمان وFAQ.",
        "url": "1win-chicken-road",
    },
    12: {
        "code": "az",
        "name": "1WIN - Chicken Road",
        "title": "1win-də Chicken Road: alternativlər, demo və məsləhətlər",
        "description": "Chicken Road 1win-də yoxdur — Turbo Games alternativləri, demo, mobil, bonuslar, təhlükəsizlik və FAQ.",
        "url": "1win-chicken-road",
    },
    13: {
        "code": "bn",
        "name": "1WIN - Chicken Road",
        "title": "1win-এ Chicken Road: বিকল্প, ডেমো ও টিপস",
        "description": "Chicken Road 1win-এ নেই — Turbo Games বিকল্প, ডেমো, মোবাইল, বোনাস, নিরাপত্তা ও FAQ।",
        "url": "1win-chicken-road",
    },
    14: {
        "code": "it",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road su 1win: alternative, demo e consigli",
        "description": "Chicken Road non è su 1win — alternative Turbo Games, demo, mobile, bonus, sicurezza e FAQ.",
        "url": "1win-chicken-road",
    },
    15: {
        "code": "nl",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road op 1win: alternatieven, demo en tips",
        "description": "Chicken Road staat niet op 1win — Turbo Games-alternatieven, demo, mobiel, bonussen, veiligheid en FAQ.",
        "url": "1win-chicken-road",
    },
    16: {
        "code": "pl",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road na 1win: alternatywy, demo i porady",
        "description": "Chicken Road nie ma na 1win — alternatywy Turbo Games, demo, mobile, bonusy, bezpieczeństwo i FAQ.",
        "url": "1win-chicken-road",
    },
    17: {
        "code": "vi",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road trên 1win: thay thế, demo và mẹo",
        "description": "Chicken Road chưa có trên 1win — thay thế Turbo Games, demo, mobile, bonus, an toàn và FAQ.",
        "url": "1win-chicken-road",
    },
    18: {
        "code": "ua",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road на 1win: альтернативи, демо та поради",
        "description": "Chicken Road немає на 1win — альтернативи Turbo Games, демо, мобільна гра, бонуси, безпека та FAQ.",
        "url": "1win-chicken-road",
    },
    19: {
        "code": "ro",
        "name": "1WIN - Chicken Road",
        "title": "Chicken Road pe 1win: alternative, demo și sfaturi",
        "description": "Chicken Road nu e pe 1win — alternative Turbo Games, demo, mobil, bonusuri, siguranță și FAQ.",
        "url": "1win-chicken-road",
    },
}


def py_dict(d: dict) -> str:
    return json.dumps(d, ensure_ascii=False, indent=4)


def main() -> None:
    cluster = json.loads(CLUSTER.read_text(encoding="utf-8"))
    en = next(l for l in cluster["locales"] if int(l["lang_id"]) == 1)
    body = fix_en_body(parse_content_to_body(en["content"]))
    body.pop("images", None)
    body.pop("title", None)
    body.pop("description", None)

    text = (
        '# -*- coding: utf-8 -*-\n'
        '"""Structured body + meta for casino_articles#10 1win Chicken Road cluster."""\n\n'
        "from __future__ import annotations\n\n"
        "from copy import deepcopy\n\n"
        f"LOCALE_META = {py_dict(LOCALE_META)}\n\n"
        f"_EN_BODY = {py_dict(body)}\n\n"
        "IMAGES = {\n"
        '    "hero": "/files/media/2026/06/screenshot-2026-06-08-131035.png",\n'
        '    "search_main": "/files/media/2026/06/screenshot-2026-06-08-132535.png",\n'
        '    "search1": "/files/media/2026/06/screenshot-2026-06-08-131134.png",\n'
        '    "search2": "/files/media/2026/06/screenshot-2026-06-08-131153.png",\n'
        '    "play1": "/files/media/2026/06/screenshot-2026-06-08-130854.png",\n'
        '    "play2": "/files/media/2026/06/screenshot-2026-06-08-131322.png",\n'
        '    "demo": "/files/media/2026/06/screenshot-2026-06-08-134835.png",\n'
        '    "mobile": "/files/media/2026/06/screenshot-2026-06-08-134940.png",\n'
        '    "strategy": "/files/media/2026/06/screenshot-2026-06-08-131333.png",\n'
        "}\n\n\n"
        "def get_body(lang_code: str) -> dict:\n"
        '    if lang_code == "en":\n'
        "        return deepcopy(_EN_BODY)\n"
        "    try:\n"
        "        from chickenroad_casino_articles_10_overrides import LOCALE_OVERRIDES  # type: ignore\n"
        "    except Exception:\n"
        "        LOCALE_OVERRIDES = {}\n"
        "    patch = LOCALE_OVERRIDES.get(lang_code)\n"
        "    if not patch:\n"
        "        return deepcopy(_EN_BODY)\n"
        "    out = deepcopy(_EN_BODY)\n"
        "    out.update(patch)\n"
        "    return out\n"
    )
    (TOOLS / "chickenroad_casino_articles_10_locales.py").write_text(text, encoding="utf-8")
    print("Wrote chickenroad_casino_articles_10_locales.py")


if __name__ == "__main__":
    main()
