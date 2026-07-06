#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild pages#6 predictor cluster for Ice Fish."""

from __future__ import annotations

import json
import re
from datetime import datetime, timezone
from pathlib import Path

from icefish_predictor_en_ru import IMAGES, get_english, get_russian

ROOT = Path(__file__).resolve().parents[1]
CLUSTER_IN = ROOT / "tmp/jason/seo-pages-6-full.json"
OUT_REPO = ROOT / "site/files/reference/seo-pages-6-full.json"
OUT_TMP = ROOT / "tmp/jason/seo-pages-6-full.json"

STRATEGIES_MENU_NAME = {
    1: "Strategies",
    3: "Stratégies",
    4: "Strategien",
    6: "Estrategias",
    7: "रणनीतियाँ",
    8: "Estratégias",
    9: "Стратегии",
    11: "استراتيجيات",
    12: "Strategiyalar",
    13: "কৌশল",
    14: "Strategie",
    15: "Strategieën",
    16: "Strategie",
    17: "Chiến lược",
    18: "Стратегії",
    19: "Strategii",
}

LOCALE_META = {
    1: {
        "code": "en",
        "name": STRATEGIES_MENU_NAME[1],
        "title": "Ice Fish Predictor: APK, iOS, RNG and Why It Does Not Work",
        "description": "Learn why Ice Fish predictor APKs and bots cannot beat RNG, how scam apps spread on Android and iOS, and safer ways to play the step game.",
    },
    3: {
        "code": "fr",
        "name": STRATEGIES_MENU_NAME[3],
        "title": "Prédicteur Ice Fish : APK, iOS, RNG et pourquoi ça ne marche pas",
        "description": "Pourquoi les APK et bots prédicteur Ice Fish ne battent pas le RNG, comment les arnaques se propagent sur Android et iOS, et des alternatives plus sûres.",
    },
    4: {
        "code": "de",
        "name": STRATEGIES_MENU_NAME[4],
        "title": "Ice Fish Predictor: APK, iOS, RNG und warum es nicht funktioniert",
        "description": "Warum Ice-Fish-Predictor-APKs und Bots den RNG nicht schlagen, wie Betrugsmaschen auf Android und iOS verbreitet werden und sicherere Alternativen.",
    },
    6: {
        "code": "es",
        "name": STRATEGIES_MENU_NAME[6],
        "title": "Predictor Ice Fish: APK, iOS, RNG y por qué no funciona",
        "description": "Por qué los APK y bots predictor de Ice Fish no vencen el RNG, cómo se propagan estafas en Android e iOS y alternativas más seguras al juego por pasos.",
    },
    7: {
        "code": "hi",
        "name": STRATEGIES_MENU_NAME[7],
        "title": "Ice Fish Predictor: APK, iOS, RNG और यह क्यों काम नहीं करता",
        "description": "Ice Fish प्रेडिक्टर APK और बॉट RNG को क्यों नहीं हराते, Android/iOS पर स्कैम कैसे फैलते हैं और स्टेप गेम खेलने के सुरक्षित विकल्प।",
    },
    8: {
        "code": "pt",
        "name": STRATEGIES_MENU_NAME[8],
        "title": "Predictor Ice Fish: APK, iOS, RNG e por que não funciona",
        "description": "Por que APKs e bots predictor Ice Fish não vencem o RNG, como golpes se espalham no Android e iOS e alternativas mais seguras ao jogo por passos.",
    },
    9: {
        "code": "ru",
        "name": STRATEGIES_MENU_NAME[9],
        "title": "Ice Fish Predictor: APK, iOS, RNG и почему он не работает",
        "description": "Почему APK и боты-предикторы Ice Fish не обыгрывают RNG, как мошеннические приложения распространяются на Android и iOS и чем играть безопаснее.",
    },
    11: {
        "code": "ar",
        "name": STRATEGIES_MENU_NAME[11],
        "title": "Ice Fish Predictor: APK وiOS وRNG ولماذا لا يعمل",
        "description": "لماذا لا تتغلب تطبيقات وبوتات تنبؤ Ice Fish على RNG، وكيف تنتشر الاحتيال على Android وiOS، وبدائل أكثر أماناً للعب بالخطوات.",
    },
    12: {
        "code": "az",
        "name": STRATEGIES_MENU_NAME[12],
        "title": "Ice Fish Predictor: APK, iOS, RNG və niyə işləmir",
        "description": "Ice Fish predictor APK və botlarının RNG-ni niyə qala bilmədiyi, Android/iOS-da fırıldaqçılığın yayılması və addım oyunu üçün daha təhlükəsiz yollar.",
    },
    13: {
        "code": "bn",
        "name": STRATEGIES_MENU_NAME[13],
        "title": "Ice Fish Predictor: APK, iOS, RNG এবং কেন কাজ করে না",
        "description": "Ice Fish প্রেডিক্টর APK ও বট RNG কেন হারাতে পারে না, Android/iOS-এ প্রতারণা কীভাবে ছড়ায় এবং ধাপ-ভিত্তিক খেলার নিরাপদ বিকল্প।",
    },
    14: {
        "code": "it",
        "name": STRATEGIES_MENU_NAME[14],
        "title": "Predictor Ice Fish: APK, iOS, RNG e perché non funziona",
        "description": "Perché APK e bot predictor Ice Fish non battono l’RNG, come le truffe si diffondono su Android e iOS e alternative più sicure al gioco a passi.",
    },
    15: {
        "code": "nl",
        "name": STRATEGIES_MENU_NAME[15],
        "title": "Ice Fish Predictor: APK, iOS, RNG en waarom het niet werkt",
        "description": "Waarom Ice Fish predictor-APK’s en bots RNG niet verslaan, hoe oplichting op Android en iOS zich verspreidt en veiligere manieren om het stapspel te spelen.",
    },
    16: {
        "code": "pl",
        "name": STRATEGIES_MENU_NAME[16],
        "title": "Ice Fish Predictor: APK, iOS, RNG i dlaczego nie działa",
        "description": "Dlaczego APK i boty predictor Ice Fish nie pokonują RNG, jak oszustwa rozprzestrzeniają się na Androidzie i iOS i bezpieczniejsze sposoby gry krok po kroku.",
    },
    17: {
        "code": "vi",
        "name": STRATEGIES_MENU_NAME[17],
        "title": "Ice Fish Predictor: APK, iOS, RNG và vì sao không hiệu quả",
        "description": "Vì sao APK và bot dự đoán Ice Fish không thắng RNG, lừa đảo lan trên Android/iOS thế nào và cách chơi game theo bước an toàn hơn.",
    },
    18: {
        "code": "ua",
        "name": STRATEGIES_MENU_NAME[18],
        "title": "Ice Fish Predictor: APK, iOS, RNG і чому він не працює",
        "description": "Чому APK і боти-predictor Ice Fish не обіграють RNG, як шахрайські застосунки поширюються на Android і iOS та безпечніші альтернативи.",
    },
    19: {
        "code": "ro",
        "name": STRATEGIES_MENU_NAME[19],
        "title": "Ice Fish Predictor: APK, iOS, RNG și de ce nu funcționează",
        "description": "De ce APK-urile și boții predictor Ice Fish nu înving RNG-ul, cum se răspândesc escrocheriile pe Android și iOS și alternative mai sigure la jocul pe pași.",
    },
}

IMAGE_REPLACEMENTS = [
    ("/images/predictor/Header-img.webp", IMAGES["hero"]),
    ("/images/predictor/Aviator-Predictor.webp", IMAGES["app"]),
    ("/images/predictor/Predictor-for-Android.jpg", IMAGES["android"]),
    ("/images/predictor/Predictor-for-iOS.jpg", IMAGES["ios"]),
    ("/images/predictor/Aviator-Predictor-AI.webp", IMAGES["ai"]),
    ("/images/predictor/Aviator-Predictor-for-Casinos.png", IMAGES["casinos"]),
]

# Longest-first per locale; Aviator brand + crash-game mechanics → Ice Fish step game.
LOCALE_REPLACEMENTS: dict[str, list[tuple[str, str]]] = {
    "fr": [
        ("Prédicteur Aviator", "Prédicteur Ice Fish"),
        ("prédicteur Aviator", "prédicteur Ice Fish"),
        ("Aviator Predictor", "Prédicteur Ice Fish"),
        ("Aviator predictor", "prédicteur Ice Fish"),
        ("Aviator", "Ice Fish"),
        ("règles de type provably fair", "règles RNG certifiées"),
        ("provably fair", "RNG certifié"),
        ("crash game", "jeu par étapes"),
        ("prochain crash", "prochain pas perdant"),
        ("points de crash", "prochains pas"),
        ("smart crash timing", "timing intelligent des pas"),
        ("lift-off", "pas sûr"),
        ("le crash", "la fin du pas"),
    ],
    "de": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator-Predictor", "Ice-Fish-Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("nachweislich fairer", "zertifiziertem"),
        ("provably fair", "zertifiziertem RNG"),
        ("Crash-Game", "Schrittspiel"),
        ("crash game", "Schrittspiel"),
        ("nächsten Crash", "nächsten Verlustschritt"),
        ("Crash-Punkte", "nächsten Schritte"),
        ("smart crash timing", "smartes Schritt-Timing"),
        ("lift-off", "sicheren Schritt"),
    ],
    "es": [
        ("Aviator Predictor", "Predictor Ice Fish"),
        ("Aviator predictor", "predictor Ice Fish"),
        ("Aviator", "Ice Fish"),
        ("reglas de juego limpio demostrable", "reglas RNG certificado"),
        ("provably fair", "RNG certificado"),
        ("crash game", "juego por pasos"),
        ("próximo crash", "siguiente paso perdedor"),
        ("siguiente crash", "siguiente paso perdedor"),
        ("puntos de crash", "próximos pasos"),
        ("smart crash timing", "timing inteligente de pasos"),
        ("lift-off", "paso seguro"),
    ],
    "hi": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "प्रमाणित RNG"),
        ("crash game", "स्टेप गेम"),
        ("अगला crash", "अगला हारने वाला कदम"),
        ("crash points", "अगले कदम"),
        ("smart crash timing", "स्मार्ट स्टेप टाइमिंग"),
    ],
    "pt": [
        ("Aviator Predictor", "Predictor Ice Fish"),
        ("Aviator predictor", "predictor Ice Fish"),
        ("Aviator", "Ice Fish"),
        ("jogo limpo comprovável", "RNG certificado"),
        ("provably fair", "RNG certificado"),
        ("crash game", "jogo por passos"),
        ("próximo crash", "próximo passo perdedor"),
        ("pontos de crash", "próximos passos"),
        ("smart crash timing", "timing inteligente de passos"),
    ],
    "ar": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "RNG معتمد"),
        ("crash game", "لعبة الخطوات"),
        ("الcrash", "الخطوة الخاسرة"),
        ("crash", "خطوة"),
    ],
    "az": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "sertifikatlaşdırılmış RNG"),
        ("crash game", "addım oyunu"),
        ("crash", "itirilmiş addım"),
    ],
    "bn": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "সার্টিফাইড RNG"),
        ("crash game", "স্টেপ গেম"),
        ("crash", "হারানো ধাপ"),
    ],
    "it": [
        ("Aviator Predictor", "Predictor Ice Fish"),
        ("Aviator predictor", "predictor Ice Fish"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "RNG certificato"),
        ("crash game", "gioco a passi"),
        ("prossimo crash", "prossimo passo perdente"),
        ("punti di crash", "prossimi passi"),
        ("smart crash timing", "timing intelligente dei passi"),
    ],
    "nl": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("crashgame-interface", "stapspel-interface"),
        ("crashgame", "stapspel"),
        ("provably fair", "gecertificeerde RNG"),
        ("crash game", "stapspel"),
        ("volgende crash", "volgende verliezende stap"),
        ("crashpunten", "volgende stappen"),
        ("smart crash timing", "slimme stap-timing"),
    ],
    "pl": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("interfejs gry crash", "interfejs gry krokowej"),
        ("następny crash", "kolejny przegrany krok"),
        ("zasad certyfikowanym RNG", "zasad certyfikowanego RNG"),
        ("provably fair", "certyfikowanym RNG"),
        ("crash game", "grze krokowej"),
        ("kolejny crash", "kolejny przegrany krok"),
        ("punktów crash", "kolejnych kroków"),
        ("smart crash timing", "inteligentnego timingu kroków"),
    ],
    "vi": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("giao diện game crash", "giao diện game theo bước"),
        ("provably fair", "RNG được chứng nhận"),
        ("crash game", "game theo bước"),
        ("crash tiếp theo", "bước thua tiếp theo"),
        ("điểm crash", "bước tiếp theo"),
    ],
    "ua": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "сертифікованого RNG"),
        ("crash game", "покрокової гри"),
        ("crash-гри", "покрокової гри"),
        ("краш", "програшний крок"),
        ("crash", "крок"),
    ],
    "ro": [
        ("Aviator Predictor", "Ice Fish Predictor"),
        ("Aviator predictor", "Ice Fish predictor"),
        ("Aviator", "Ice Fish"),
        ("provably fair", "RNG certificat"),
        ("crash game", "joc pe pași"),
        ("următorul crash", "următorul pas pierdut"),
        ("puncte de crash", "pașii următori"),
        ("smart crash timing", "timing inteligent al pașilor"),
    ],
}


def apply_replacements(content: str, pairs: list[tuple[str, str]]) -> str:
    for old, new in sorted(pairs, key=lambda x: len(x[0]), reverse=True):
        content = content.replace(old, new)
    return content


def transform_locale(content: str, code: str, title: str) -> str:
    pairs = IMAGE_REPLACEMENTS + LOCALE_REPLACEMENTS.get(code, [])
    content = apply_replacements(content, pairs)
    content = re.sub(r"<h1>.*?</h1>", f"<h1>{title}</h1>", content, count=1)
    return content


def locale_content(code: str, old_content: str, title: str) -> str:
    if code == "en":
        return get_english()
    if code == "ru":
        return get_russian()
    return transform_locale(old_content, code, title)


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
        for needle in ("aviator", "spribe", "/images/predictor/"):
            if needle in c.lower():
                bad.append(f"{code}: still contains {needle!r}")
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
            "url": "strategies",
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": locale_content(code, old["content"], meta["title"]),
            "status": old.get("status", "published"),
            "source": "export",
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
    print("Images:", sorted(set(re.findall(r'src="([^"]+)"', en["content"]))))
    for loc in new_locales:
        print(
            f"{LOCALE_META[loc['lang_id']]['code']}: "
            f"title={len(loc['title'])} desc={len(loc['description'])} "
            f"content={len(loc['content'])}"
        )


if __name__ == "__main__":
    main()
