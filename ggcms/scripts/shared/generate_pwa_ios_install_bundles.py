#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate per-brand files/i18n/pwa-ios-install.php with trust-first motivating intros."""

from __future__ import annotations

import re
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]

BRANDS = {
    "aviator-log-in": {
        "product": "Aviator",
        "domain": "aviator-log-in.com",
        "site_dir": REPO / "sites/aviator-log-in/site",
    },
    "chickenroad": {
        "product": "Chicken Road",
        "domain": "chickenroad.run",
        "site_dir": REPO / "sites/chickenroad/site",
    },
    "ice-fish": {
        "product": "Ice Fishing",
        "domain": "ice-fish.run",
        "site_dir": REPO / "sites/ice-fish/site",
    },
    "powerballjackpot": {
        "product": "PowerBall Jackpot",
        "domain": "powerballjackpot.run",
        "site_dir": REPO / "sites/powerballjackpot/site",
    },
}

# Trust-first intro: lead with outcome = App Store parity, Apple official path, no upfront "NOT App Store".
INTROS: dict[str, str] = {
    "en": (
        "In about a minute, {product} will sit on your Home Screen — full screen, one tap, the same daily flow you already know from your favorite apps. "
        "The icon stays where you expect it, opens in its own window, and feels familiar from the very first launch. "
        "Apple built this official iPhone shortcut into Safari; the three steps below are the same path millions of people use — and the result matches a regular installed app."
    ),
    "ru": (
        "Примерно за минуту {product} окажется на экране «Домой» — полный экран, один тап, тот же привычный сценарий, что у приложений, которыми вы уже пользуетесь. "
        "Иконка стоит там, где вы её ждёте, открывается в отдельном окне и с первого запуска ощущается своей. "
        "Apple встроила этот официальный способ в Safari; три шага ниже — тот же путь, которым пользуются миллионы, и итог не отличается от обычного установленного приложения."
    ),
    "fr": (
        "En une minute environ, {product} sera sur votre écran d’accueil — plein écran, un tap, le même rituel que vos applis habituelles. "
        "L’icône reste à sa place, s’ouvre dans sa propre fenêtre et paraît familière dès le premier lancement. "
        "Apple a intégré ce raccourci officiel dans Safari ; les trois étapes ci-dessous mènent au même résultat qu’une application installée."
    ),
    "de": (
        "In etwa einer Minute liegt {product} auf Ihrem Home-Bildschirm — Vollbild, ein Tippen, derselbe Ablauf wie bei Apps, die Sie schon nutzen. "
        "Das Symbol bleibt dort, wo Sie es erwarten, öffnet sich in einem eigenen Fenster und wirkt vom ersten Start an vertraut. "
        "Apple hat diesen offiziellen Safari-Weg in jedes iPhone eingebaut; die drei Schritte unten führen zum gleichen Ergebnis wie eine installierte App."
    ),
    "es": (
        "En un minuto, {product} estará en tu pantalla de inicio — pantalla completa, un toque, el mismo ritmo diario que tus apps favoritas. "
        "El icono queda donde lo esperas, se abre en su propia ventana y se siente familiar desde el primer uso. "
        "Apple integró este acceso oficial en Safari; los tres pasos de abajo dan el mismo resultado que una app instalada."
    ),
    "pt": (
        "Em cerca de um minuto, {product} ficará na sua Tela Inicial — ecrã inteiro, um toque, o mesmo fluxo das apps que já usa. "
        "O ícone fica onde espera, abre numa janela própria e parece familiar desde o primeiro arranque. "
        "A Apple integrou este atalho oficial no Safari; os três passos abaixo levam ao mesmo resultado de uma app instalada."
    ),
    "it": (
        "In circa un minuto, {product} sarà sulla Home — schermo intero, un tap, lo stesso flusso delle app che usi ogni giorno. "
        "L’icona resta dove la cerchi, si apre in una finestra dedicata e risulta familiare dal primo avvio. "
        "Apple ha integrato questo percorso ufficiale in Safari; i tre passi sotto portano allo stesso risultato di un’app installata."
    ),
    "pl": (
        "W około minutę {product} trafi na ekran główny — pełny ekran, jedno stuknięcie, ten sam schemat co w ulubionych aplikacjach. "
        "Ikona zostaje tam, gdzie jej oczekujesz, otwiera się w osobnym oknie i od pierwszego uruchomienia wydaje się znajoma. "
        "Apple wbudowało tę oficjalną ścieżkę w Safari; trzy kroki poniżej dają ten sam efekt co zwykła zainstalowana aplikacja."
    ),
    "uk": (
        "Приблизно за хвилину {product} з’явиться на головному екрані — повний екран, один дотик, той самий звичний сценарій, що й у ваших улюблених застосунках. "
        "Піктограма залишається там, де ви її очікуєте, відкривається в окремому вікні й з першого запуску відчувається своєю. "
        "Apple вбудувала цей офіційний шлях у Safari; три кроки нижче дають той самий результат, що й звичайний установлений застосунок."
    ),
    "nl": (
        "Binnen ongeveer een minuut staat {product} op je beginscherm — vol scherm, één tik, hetzelfde dagelijkse ritme als je favoriete apps. "
        "Het pictogram blijft waar je het verwacht, opent in een eigen venster en voelt vanaf de eerste start vertrouwd. "
        "Apple heeft deze officiële Safari-route in elke iPhone gezet; de drie stappen hieronder geven hetzelfde resultaat als een geïnstalleerde app."
    ),
    "ro": (
        "În aproximativ un minut, {product} va fi pe ecranul de pornire — ecran complet, o atingere, același flux ca la aplicațiile pe care le folosiți zilnic. "
        "Pictograma rămâne unde vă așteptați, se deschide într-o fereastră proprie și pare familiară de la prima lansare. "
        "Apple a integrat această cale oficială în Safari; cei trei pași de mai jos duc la același rezultat ca o aplicație instalată."
    ),
    "hi": (
        "लगभग एक मिनट में {product} आपकी होम स्क्रीन पर होगा — फुल स्क्रीन, एक टैप, वही रोज़ का अनुभव जो आपके पसंदीदा ऐप्स देते हैं। "
        "आइकन वहीं रहता है जहाँ आप उम्मीद करते हैं, अपनी विंडो में खुलता है और पहली बार से ही परिचित लगता है। "
        "Apple ने Safari में यह आधिकारिक शॉर्टकट दिया है; नीचे के तीन चरण वही परिणाम देते हैं जो एक इंस्टॉल किए गए ऐप से मिलता है।"
    ),
    "ar": (
        "خلال دقيقة تقريباً سيكون {product} على شاشتك الرئيسية — ملء الشاشة، نقرة واحدة، نفس الإيقاع اليومي لتطبيقاتك المألوفة. "
        "تبقى الأيقونة حيث تتوقعها، وتفتح في نافذة خاصة وتبدو مألوفة من أول تشغيل. "
        "أدمجت Apple هذا الاختصار الرسمي في Safari؛ الخطوات الثلاث أدناه تؤدي إلى نفس نتيجة التطبيق المثبت."
    ),
    "bn": (
        "প্রায় এক মিনিটের মধ্যে {product} আপনার হোম স্ক্রিনে থাকবে — ফুল স্ক্রিন, এক ট্যাপ, প্রিয় অ্যাপের মতোই দৈনন্দিন অভিজ্ঞতা। "
        "আইকন থাকে যেখানে আপনি চান, নিজস্ব উইন্ডোতে খুলে এবং প্রথম চালু থেকেই পরিচিত মনে হয়। "
        "Apple Safari-তে এই অফিসিয়াল শর্টকাট দিয়েছে; নিচের তিন ধাপ ইনস্টল করা অ্যাপের মতো একই ফল দেয়।"
    ),
    "vi": (
        "Khoảng một phút nữa, {product} sẽ nằm trên màn hình chính — toàn màn hình, một chạm, cùng nhịp quen thuộc như app bạn dùng hàng ngày. "
        "Biểu tượng ở đúng chỗ bạn mong đợi, mở trong cửa sổ riêng và quen ngay từ lần đầu. "
        "Apple tích hợp lối tắt chính thức này trong Safari; ba bước bên dưới cho kết quả giống app đã cài."
    ),
    "az": (
        "Təxminən bir dəqiqəyə {product} Əsas ekranınızda olacaq — tam ekran, bir toxunuş, sevdiyiniz tətbiqlər kimi eyni gündəlik axın. "
        "Nişan gözlədiyiniz yerdə qalır, ayrıca pəncərədə açılır və ilk işə salınmadan tanış hiss olunur. "
        "Apple bu rəsmi Safari qısa yolunu hər iPhone-a qoyub; aşağıdakı üç addım quraşdırılmış tətbiq kimi eyni nəticəni verir."
    ),
}

META_EN = (
    "Add {product} to your iPhone Home Screen in about a minute — full-screen demo, one tap, "
    "same experience as a regular app. Official Safari shortcut: Share → Add to Home Screen."
)


def rebrand_text(text: str, from_brand: dict, to_brand: dict) -> str:
    pairs = [
        ("aviator-log-in.com", to_brand["domain"]),
        ("Aviator Log In", to_brand["product"]),
        ("Aviator demo", f"{to_brand['product']} demo"),
        ("Aviator Demo", f"{to_brand['product']} demo"),
        ("the Aviator demo", f"the {to_brand['product']} demo"),
        ("The Aviator demo", f"The {to_brand['product']} demo"),
        ("Install Aviator", f"Install {to_brand['product']}"),
        ("open Aviator", f"open {to_brand['product']}"),
        ("Open Aviator", f"Open {to_brand['product']}"),
        ("Aviator", to_brand["product"]),
    ]
    out = text
    for old, new in pairs:
        out = re.sub(re.escape(old), new, out, flags=re.IGNORECASE)
    return out


def patch_php_content(content: str, brand: dict) -> str:
    for key, template in INTROS.items():
        intro = template.format(product=brand["product"])
        intro_php = intro.replace("'", "\\'")
        # Match 'intro' => '...' or with double quotes in HTML inside - use flexible match
        pattern = rf"('{key}'\s*=>\s*array\([\s\S]*?'intro'\s*=>\s*)'(?:\\'|[^'])*'"
        if not re.search(pattern, content):
            pattern = rf"('en'\s*=>\s*\$en,[\s\S]*?'{key}'\s*=>\s*array\([\s\S]*?'intro'\s*=>\s*)'(?:\\'|[^'])*'"
        content = re.sub(pattern, rf"\1'{intro_php}'", content, count=1)

    # EN block intro (inside $en = array)
    en_intro = INTROS["en"].format(product=brand["product"]).replace("'", "\\'")
    content = re.sub(
        r"(\$en\s*=\s*array\([\s\S]*?'intro'\s*=>\s*)'(?:\\'|[^'])*'",
        rf"\1'{en_intro}'",
        content,
        count=1,
    )

    meta = META_EN.format(product=brand["product"]).replace("'", "\\'")
    content = re.sub(
        r"('meta_description'\s*=>\s*)'(?:\\'|[^'])*'",
        rf"\1'{meta}'",
        content,
        count=1,
    )

    if brand["domain"] != "aviator-log-in.com":
        content = rebrand_text(content, BRANDS["aviator-log-in"], brand)
    return content


def main() -> None:
    src = BRANDS["aviator-log-in"]["site_dir"] / "files/i18n/pwa-ios-install.php"
    base = src.read_text(encoding="utf-8")

    for slug, brand in BRANDS.items():
        out_dir = brand["site_dir"] / "files/i18n"
        out_dir.mkdir(parents=True, exist_ok=True)
        out_path = out_dir / "pwa-ios-install.php"
        patched = patch_php_content(base, brand)
        header = patched.split("$en = array(", 1)[0]
        header = re.sub(
            r"Product name:.*?\n",
            f"Product name: {brand['product']}. Trust-first motivating copy.\n",
            header,
            count=1,
        )
        if "Trust-first" not in header:
            header = header.replace(
                "Tone and localization:",
                "Trust-first motivating copy. Tone and localization:",
            )
        patched = header + "$en = array(" + patched.split("$en = array(", 1)[1]
        out_path.write_text(patched, encoding="utf-8")
        print(f"wrote {out_path}")

        build_out = REPO / "build" / slug / "site/files/i18n/pwa-ios-install.php"
        build_out.parent.mkdir(parents=True, exist_ok=True)
        build_out.write_text(patched, encoding="utf-8")
        print(f"wrote {build_out}")


if __name__ == "__main__":
    main()
