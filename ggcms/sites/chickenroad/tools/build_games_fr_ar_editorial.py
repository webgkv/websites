#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build games_fr_ar_data/*.json from EN segment lists + editorial FR/AR translations."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DL = Path("/home/lenovo/Downloads/02/chickenroad-games")
OUT = TOOLS / "games_fr_ar_data"


def load_en(entity_id: int) -> list[str]:
    return json.loads((DL / f"games-{entity_id}-en-segments.json").read_text(encoding="utf-8"))


def load_fr(entity_id: int) -> list[str]:
    path = DL / f"games-{entity_id}-fr-segments.json"
    if path.stat().st_size <= 10:
        return []
    return json.loads(path.read_text(encoding="utf-8"))


def pairs_from_lists(en: list[str], loc: list[str]) -> list[list[str]]:
    if len(en) != len(loc):
        raise ValueError(f"length mismatch en={len(en)} loc={len(loc)}")
    return [[a, b] for a, b in zip(en, loc)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def write_json(entity_id: int, payload: dict) -> Path:
    OUT.mkdir(parents=True, exist_ok=True)
    path = OUT / f"games_{entity_id}.json"
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    return path


# ---------------------------------------------------------------------------
# Meta (fr / ar) — title ≤70, description ≤160
# ---------------------------------------------------------------------------
META = {
    1: {
        "fr": {
            "name": "Chicken Road original",
            "title": "Chicken Road : le crash game original expliqué",
            "description": "Guide Chicken Road : règles, démo gratuite, niveaux Easy à Hardcore, Provably Fair et gains max jusqu'à $20,000.",
        },
        "ar": {
            "name": "Chicken Road الأصلي",
            "title": "Chicken Road: شرح لعبة الكrash الأصلية",
            "description": "دليل Chicken Road: القواعد، العرض التجريبي المجاني، مستويات Easy وMedium وHard وHardcore، Provably Fair والحد الأقصى $20,000.",
        },
    },
    4: {
        "fr": {
            "name": "Chicken Road Vegas",
            "title": "Chicken Road Vegas : jeu, démo et casinos",
            "description": "Chicken Road Vegas : thème néon, niveaux Skill Tiers, démo gratuite et où jouer en argent réel.",
        },
        "ar": {
            "name": "Chicken Road Vegas",
            "title": "Chicken Road Vegas: اللعبة والعرض التجريبي والكازينوهات",
            "description": "مراجعة Chicken Road Vegas: ثيم Vegas، مستويات الصعوبة، العرض التجريبي وأين تلعب بأموال حقيقية.",
        },
    },
    5: {
        "fr": {
            "name": "Chicken Road Gold",
            "title": "Chicken Road Gold : jeu, démo et où jouer",
            "description": "Chicken Road Gold : thème Vintage London, niveaux de difficulté, démo et casinos pour jouer en argent réel.",
        },
        "ar": {
            "name": "Chicken Road Gold",
            "title": "Chicken Road Gold: اللعبة والعرض التجريبي وأين تلعب",
            "description": "Chicken Road Gold: ثيم لندن الكلاسيكي، مستويات الصعوبة، العرض التجريبي وأفضل الكازينوهات.",
        },
    },
    8: {
        "fr": {
            "name": "Chicken Royal",
            "title": "Chicken Royal : slot, règles et guide casino",
            "description": "Test Chicken Royal : règles, démo, RTP, Wild, Scatter, Free Spins et où jouer Chicken Royal en argent réel.",
        },
        "ar": {
            "name": "Chicken Royal",
            "title": "Chicken Royal: السلوت والقواعد ودليل الكازينو",
            "description": "مراجعة Chicken Royal: القواعد، العرض التجريبي، RTP، Wild وScatter وFree Spins وأين تلعب بأموال حقيقية.",
        },
    },
    9: {
        "fr": {
            "name": "Chicken Coin",
            "title": "Chicken Coin : slot, règles et où jouer",
            "description": "Chicken Coin : fonctionnement, RTP 96,5 %, volatilité, bonus et où jouer au slot Chicken Coin en ligne.",
        },
        "ar": {
            "name": "Chicken Coin",
            "title": "Chicken Coin: السلوت والقواعد وأين تلعب",
            "description": "مراجعة Chicken Coin: آلية اللعب، RTP 96.5%، التقلب، المكافآت وأين تلعب سلوت Chicken Coin.",
        },
    },
    10: {
        "fr": {
            "name": "Chicken Banana",
            "title": "Chicken Banana : règles, RTP et où jouer",
            "description": "Chicken Banana : règles, RTP 96 %, multiplicateur max 1 000x et casinos pour jouer Chicken Banana en ligne.",
        },
        "ar": {
            "name": "Chicken Banana",
            "title": "Chicken Banana: القواعد وRTP وأين تلعب",
            "description": "Chicken Banana: القواعد، RTP 96%، أقصى مضاعف 1,000x وأفضل الكازينوهات للعب Chicken Banana.",
        },
    },
    11: {
        "fr": {
            "name": "Chicken Shoot",
            "title": "Chicken Shoot : jouer en argent réel",
            "description": "Chicken Shoot : comment jouer, RTP, Provably Fair et meilleurs casinos pour Chicken Shoot en argent réel.",
        },
        "ar": {
            "name": "Chicken Shoot",
            "title": "Chicken Shoot: العب بأموال حقيقية",
            "description": "Chicken Shoot: طريقة اللعب، RTP، Provably Fair وأفضل الكازينوهات التي تقدم Chicken Shoot.",
        },
    },
}

for eid, langs in META.items():
    for lang in langs:
        t, d = truncate(langs[lang]["title"], langs[lang]["description"])
        langs[lang]["title"] = t
        langs[lang]["description"] = d


def build_games_8() -> dict:
    en = load_en(8)
    fr = [
        "Chicken Royal",
        "Table de paiement Chicken Royal",
        "Free Spins dans Chicken Royal",
        "Symboles bonus Chicken Royal",
        "Wild",
        "Scatter",
        "FAQ",
        "Gameplay Chicken Royal avec symboles monétaires",
        "vidéo jeu d'argent chicken royal",
        "démo chicken royal 1024x627",
        "gros gain chicken royal 1024x633",
        "Chicken Royal fait partie des jeux de l'univers Chicken Road. Il reprend le même personnage de poulet amusant, mais l'ambiance paraît un peu plus premium que le jeu de route original.",
        "Le nom le dit déjà : ce n'est plus un simple poulet qui traverse la route. Chicken Royal sonne comme une version plus haut de gamme de la même idée arcade, pour les joueurs qui connaissent déjà le format Chicken Road et veulent quelque chose de plus soigné.",
        "La raison principale pour laquelle ce jeu s'intègre à la série est simple : InOut Games comprend clairement pourquoi le thème du poulet fonctionne. Le mode Demo est le meilleur point de départ, surtout si vous voulez sentir Chicken Royal avant de jouer avec un solde réel.",
        "InOut Games développe activement la franchise Chicken Road avec plusieurs titres connexes, et Chicken Royal figure parmi les variantes premium de cette gamme. Cela en fait partie d'une stratégie plus large : prendre un personnage à succès, garder l'esprit arcade et construire différentes versions pour différents profils de joueurs.",
        "La table de paiement fonctionne de la manière habituelle. Il faut 3 symboles identiques sur des rouleaux voisins pour obtenir un gain. Visez de meilleures combinaisons pour une récompense plus élevée.",
        "Comme les 20 lignes de paiement sont fixes, le jeu vérifie chaque ligne automatiquement. Rien à régler avant le spin : fixez la mise, lancez et regardez où les symboles atterrissent.",
        "3 correspondances",
        "4 correspondances",
        "5 correspondances",
        "10, J, Q",
        "Poulet (X yeux)",
        "Borne incendie",
        "Camionnette verte",
        "Camion à glaces",
        "Voiture de police",
        "Camion de pompiers",
        "C'est pourquoi le round Free Spins compte. Si quelques Wild arrivent tôt, ils restent sur les rouleaux et peuvent aider le reste du bonus. Plus ils apparaissent tôt, plus la fonction peut être intéressante.",
        "Un seul Sticky Wild est déjà utile. Mais si deux ou trois atterrissent en bonnes positions, la même ligne peut produire des gains bien plus forts. C'est là que Chicken Royal devient plus intéressant qu'un slot 5&times;3 basique.",
        "Bien sûr, les Free Spins ne garantissent pas un gros gain. Parfois la fonction reste calme. Parfois les Wild arrivent tard ou en mauvaise position. Mais quand des Sticky Wild apparaissent tôt avec de bons multiplicateurs, le bonus peut créer une vraie valeur.",
        "Dans Chicken Royal, la plupart des symboles font leur travail habituel : atterrir sur la ligne et payer si la combinaison est bonne. Wild et Scatter sont différents. Ces deux-là méritent qu'on les surveille, car ils peuvent vraiment changer la manche.",
        "Le Wild n'apparaît pas partout. Son rôle principal est simple : il remplace les symboles ordinaires et aide à compléter les gains. Scatter est le seul symbole qu'il ne remplace pas. Mais la vraie valeur du Wild, c'est le multiplicateur. C'est là qu'un hit de ligne ordinaire peut soudain devenir bien meilleur. Dans Chicken Royal, Wild est l'un des symboles qui donnent du poids à un spin.",
        "3 symboles Scatter n'importe où sur les rouleaux lancent la fonction. S'ils atterrissent en nombre suffisant, le bonus s'ouvre.",
        "C'est pourquoi Scatter compte. Ce n'est pas qu'un symbole déclencheur. Il ouvre la partie de Chicken Royal où le jeu a plus de marge pour construire de plus grosses combinaisons.",
        "Quel type de jeu est Chicken Royal ? Chicken Royal est un slot simple à 5 rouleaux avec un thème poulet. La disposition est familière : rouleaux, rangées, lignes fixes. Rien à activer manuellement &mdash; toutes les lignes fonctionnent dès le départ.",
        "Puis-je ouvrir Chicken Royal sur mon téléphone ? Oui. Chicken Royal fonctionne sur mobile. L'écran est plus compact, mais le slot lui-même ne change pas. Vous faites tourner les mêmes rouleaux avec les mêmes symboles bonus.",
        "Combien Chicken Royal peut-il payer ? Consultez la limite de gain maximum indiquée dans le panneau d'infos du jeu avant de jouer en argent réel.",
        "Est-ce que $0.20 est la mise la plus basse ? Oui &mdash; $0.20 est en général la mise minimum dans Chicken Royal, mais la limite exacte peut dépendre de la plateforme casino.",
        "Puis-je jouer sans argent d'abord ? Oui, si le mode Demo est disponible sur le site où vous ouvrez le jeu.",
        "Chicken Royal est-il sûr à jouer ? Jouez uniquement via des sites de confiance ou des partenaires officiels du provider. Évitez les APK aléatoires, fausses pages demo et sites promettant des gains garantis. Chicken Royal reste un jeu de casino : le jeu en argent réel comporte toujours un risque.",
    ]
    ar = [
        "Chicken Royal",
        "جدول الدفع Chicken Royal",
        "Free Spins في Chicken Royal",
        "رموز المكافأة Chicken Royal",
        "Wild",
        "Scatter",
        "FAQ",
        "أسلوب لعب Chicken Royal مع رموز المال",
        "فيديو لعبة المال chicken royal",
        "عرض Chicken Royal التجريبي 1024x627",
        "فوز كبير Chicken Royal 1024x633",
        "Chicken Royal واحدة من ألعاب عالم Chicken Road. تحتفظ بنفس شخصية الدجاج المضحكة، لكن الأجواء أكثر فخامة ولمسة premium مقارنة بلعبة الطريق الأصلية.",
        "الاسم وحده يعطي هذا الإحساس. لم يعد مجرد دجاجة تعبر الطريق. Chicken Royal يبدو كنسخة أرقى من نفس فكرة الأركيد، للاعبين الذين يعرفون تنسيق Chicken Road الأساسي ويريدون شيئاً أكثر أناقة وتشطيباً.",
        "السبب الرئيسي لملاءمة هذه اللعبة للسلسلة بسيط: InOut Games تفهم بوضوح لماذا ينجح موضوع الدجاج. وضع Demo أفضل نقطة للبدء، خاصة إذا أردت فهم Chicken Royal قبل اللعب برصيد حقيقي من حسابك.",
        "InOut Games توسّع بنشاط امتياز Chicken Road بعدة عناوين ذات صلة، وChicken Royal مدرجة ضمن النسخ premium في هذه المجموعة الأوسع. هذا يجعلها جزءاً من استراتيجية أكبر: أخذ شخصية ناجحة، الإبقاء على روح الأركيد وبناء نسخ مختلفة لأنواع مختلفة من اللاعبين.",
        "جدول الدفع يعمل بالطريقة المعتادة في سلوتات الكازينو. تحتاج 3 رموز متطابقة على بكرات متجاورة للحصول على دفع. حاول الحصول على تركيبات أفضل لمكافأة أكبر.",
        "بما أن جميع خطوط الدفع الـ20 ثابتة، تفحص اللعبة كل خط تلقائياً. لا حاجة لتعديل أي شيء قبل الدوران. حدّد الرهان، دوّر وشاهد أين تهبط الرموز.",
        "3 تطابقات",
        "4 تطابقات",
        "5 تطابقات",
        "10, J, Q",
        "دجاج (X عيون)",
        "صنبور إطفاء",
        "شاحنة خضراء",
        "شاحنة آيس كريم",
        "سيارة شرطة",
        "سيارة إطفاء",
        "لهذا السبب جولة Free Spins مهمة للغاية. إذا هبطت بعض رموز Wild مبكراً، تبقى على البكرات ويمكنها مساعدة بقية المكافأة. كلما ظهرت أبكر، كانت الميزة أفضل وأكثر قيمة.",
        "Wild لزج واحد مفيد بالفعل. لكن إذا هبط اثنان أو ثلاثة في مواضع جيدة، يمكن لنفس خط الدفع أن يحقق أرباحاً أقوى بكثير. هنا يصبح Chicken Royal أكثر إثارة من سلوت 5&times;3 أساسي.",
        "بالطبع Free Spins لا تضمن دفعاً كبيراً. أحياناً تبقى الميزة هادئة. أحياناً تهبط Wild متأخرة أو في مواضع ضعيفة. لكن عندما تظهر Sticky Wild مبكراً مع مضاعفات جيدة، يمكن لجولة المكافأة أن تبني قيمة حقيقية.",
        "في Chicken Royal، معظم الرموز تقوم بعملها المعتاد: تهبط على الخط وتدفع إذا كان التركيب صحيحاً. Wild وScatter مختلفان. هذان يستحقان المتابعة لأنهما يغيّران الجولة فعلاً.",
        "Wild لا يظهر في كل مكان. مهمته الرئيسية بسيطة: يستبدل الرموز العادية ويساعد على إكمال الأرباح. Scatter هو الرمز الوحيد الذي لا يستبدله. لكن القيمة الحقيقية لـ Wild هي المضاعف. هنا يمكن لضربة خط عادية أن تصبح أفضل بكثير. في Chicken Royal، Wild من الرموز التي تمنح الدوران وزناً حقيقياً.",
        "3 رموز Scatter في أي مكان على البكرات تبدأ الميزة. إذا هبطت بالعدد الصحيح، تُفتح المكافأة.",
        "لهذا Scatter مهم. ليس مجرد رمز محفّز. يفتح الجزء من Chicken Royal حيث للعبة مساحة أكبر لبناء تركيبات أكبر.",
        "ما نوع لعبة Chicken Royal؟ Chicken Royal سلوت بسيط بـ5 بكرات وموضوع دجاج. التخطيط مألوف: بكرات، صفوف، خطوط دفع ثابتة. لا شيء يحتاج تفعيلاً يدوياً &mdash; جميع الخطوط تعمل من البداية.",
        "هل يمكنني فتح Chicken Royal على هاتفي؟ نعم. Chicken Royal يعمل على الأجهزة المحمولة. الشاشة أصغر، لكن السلوت نفسه لا يتغير. ما زلت تدور نفس البكرات وتلعب بنفس رموز المكافأة.",
        "كم يمكن أن يدفع Chicken Royal؟ راجع حد الدفع الأقصى المعروض في لوحة معلومات اللعبة قبل اللعب بأموال حقيقية.",
        "هل $0.20 أقل رهان؟ نعم &mdash; $0.20 عادة الحد الأدنى للرهان في Chicken Royal، لكن الحد الدقيق قد يعتمد على منصة الكازينو.",
        "هل يمكنني اللعب بدون مال أولاً؟ نعم، إذا كان وضع Demo متاحاً على الموقع الذي تفتح فيه اللعبة.",
        "هل Chicken Royal آمن للعب؟ العب فقط عبر مواقع موثوقة أو شركاء المزود الرسميين. تجنّب ملفات APK عشوائية وصفحات demo مزيفة ومواقع تعد بأرباح مضمونة. Chicken Royal ما زالت لعبة كازينو حقيقية، لذا اللعب بأموال حقيقية يحمل دائماً مخاطرة يجب أن تتقبلها.",
    ]
    return {
        "meta": {k: META[8][k] for k in ("fr", "ar")},
        "pairs": {"fr": pairs_from_lists(en, fr), "ar": pairs_from_lists(en, ar)},
    }


# Import remaining game translations from companion module
from games_fr_ar_translations import (  # noqa: E402
    AR_1,
    AR_4,
    AR_5,
    AR_9,
    AR_10,
    AR_11,
    FR_4_FIX,
    FR_5_FIX,
    FR_9,
    FR_10,
    FR_11,
)


def build_games_1() -> dict:
    en = load_en(1)
    return {
        "meta": {k: META[1][k] for k in ("fr", "ar")},
        "pairs": {
            "fr": [["Chicken Road", "Chicken Road"]],
            "ar": pairs_from_lists(en, AR_1),
        },
    }


def build_games_4() -> dict:
    en = load_en(4)
    fr_existing = load_fr(4)
    fr = FR_4_FIX(en, fr_existing)
    return {
        "meta": {k: META[4][k] for k in ("fr", "ar")},
        "pairs": {"fr": pairs_from_lists(en, fr), "ar": pairs_from_lists(en, AR_4)},
    }


def build_games_5() -> dict:
    en = load_en(5)
    fr = FR_5_FIX(en, load_fr(5))
    return {
        "meta": {k: META[5][k] for k in ("fr", "ar")},
        "pairs": {"fr": pairs_from_lists(en, fr), "ar": pairs_from_lists(en, AR_5)},
    }


BUILDERS = {
    1: build_games_1,
    4: build_games_4,
    5: build_games_5,
    8: build_games_8,
    9: lambda: _build_simple(9, FR_9, AR_9),
    10: lambda: _build_simple(10, FR_10, AR_10),
    11: lambda: _build_simple(11, FR_11, AR_11),
}


def _build_simple(entity_id: int, fr: list[str], ar: list[str]) -> dict:
    en = load_en(entity_id)
    return {
        "meta": {k: META[entity_id][k] for k in ("fr", "ar")},
        "pairs": {
            "fr": pairs_from_lists(en, fr),
            "ar": pairs_from_lists(en, ar),
        },
    }


def main() -> int:
    ids = [int(x) for x in sys.argv[1:]] if len(sys.argv) > 1 else sorted(BUILDERS)
    for eid in ids:
        if eid not in BUILDERS:
            print(f"skip games_{eid}: no builder", file=sys.stderr)
            continue
        payload = BUILDERS[eid]()
        path = write_json(eid, payload)
        nfr = len(payload["pairs"].get("fr", []))
        nar = len(payload["pairs"].get("ar", []))
        print(f"Written {path.name}: fr_pairs={nfr} ar_pairs={nar}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
