#!/usr/bin/env python3
"""Patch PWA iOS install step copy for all locales (new 3-step flow)."""

from __future__ import annotations

import re
from pathlib import Path

REPO = Path(__file__).resolve().parents[2]

BRANDS = {
    "chickenroad": "Chicken Road",
    "aviator-log-in": "Aviator",
    "ice-fish": "Ice Fishing",
    "powerballjackpot": "PowerBall Jackpot",
}

LOCALE_KEYS = (
    "fr", "de", "es", "pt", "ru", "it", "pl", "uk", "nl", "ro", "hi", "ar", "bn", "vi", "az"
)

QUICK_LEAD_TAIL = {
    "fr": ". Puis suivez les étapes ci-dessous, en commençant par le bouton ⋯.",
    "de": ". Dann befolgen Sie die Schritte unten, beginnend mit der ⋯-Taste.",
    "es": ". Luego siga los pasos de abajo, empezando por el botón ⋯.",
    "pt": ". Depois siga os passos abaixo, começando pelo botão ⋯.",
    "ru": ". Дальше следуйте шагам ниже, начиная с кнопки ⋯.",
    "it": ". Poi segui i passi sotto, partendo dal pulsante ⋯.",
    "pl": ". Potem wykonaj kroki poniżej, zaczynając od przycisku ⋯.",
    "uk": ". Далі кроки нижче, починаючи з кнопки ⋯.",
    "nl": ". Volg daarna de stappen hieronder, beginnend met de ⋯-knop.",
    "ro": ". Apoi urmați pașii de mai jos, începând cu butonul ⋯.",
    "hi": ". फिर नीचे दिए चरणों का पालन करें, ⋯ बटन से शुरू करें।",
    "ar": ". ثم اتبع الخطوات أدناه، بدءاً من زر ⋯.",
    "bn": ". তারপর নিচের ধাপগুলো অনুসরণ করুন, ⋯ বোতাম দিয়ে শুরু করে।",
    "vi": ". Sau đó làm theo các bước bên dưới, bắt đầu bằng nút ⋯.",
    "az": ". Sonra aşağıdakı addımları ⋯ düyməsindən başlayaraq edin.",
}

STEPS = {
    "fr": {
        "step1_title": "Étape 1 — bouton ⋯",
        "step1_body": "Touchez le bouton <strong>⋯</strong> (trois points) dans la barre d’outils Safari.",
        "step1_img_alt": "Bouton ⋯ dans Safari sur la page démo {brand}",
        "step2_title": "Étape 2 — Partager",
        "step2_body": "Choisissez <strong>Partager</strong> — dans Safari, c’est en général le carré avec une flèche vers le haut.",
        "step2_img_alt": "Bouton Partager dans Safari",
        "step3_title": "Étape 3 — Sur l’écran d’accueil",
        "step3_body": "Faites défiler la liste d’actions et choisissez <strong>Sur l’écran d’accueil</strong>. Absent de la liste ? Touchez <strong>Modifier les actions</strong> en bas et activez-le. Dans la feuille suivante, si iOS le propose, activez <strong>Ouvrir comme application web</strong>, puis touchez <strong>Ajouter</strong>.",
        "step3_img_alt": "Feuille Sur l’écran d’accueil avec l’option web app actif",
    },
    "de": {
        "step1_title": "Schritt 1 — ⋯-Menü",
        "step1_body": "Tippen Sie auf die Schaltfläche <strong>⋯</strong> (drei Punkte) in der Safari-Symbolleiste.",
        "step1_img_alt": "⋯-Schaltfläche in Safari auf der {brand}-Demo",
        "step2_title": "Schritt 2 — Teilen",
        "step2_body": "Wählen Sie <strong>Teilen</strong> — in Safari ist das meist das Quadrat mit Pfeil nach oben.",
        "step2_img_alt": "Teilen-Schaltfläche in Safari",
        "step3_title": "Schritt 3 — Zum Home-Bildschirm",
        "step3_body": "Scrollen Sie in der Aktionsliste und wählen Sie <strong>Zum Home-Bildschirm</strong>. Fehlt der Eintrag, öffnen Sie unten <strong>Aktionen bearbeiten</strong> und schalten Sie ihn ein. Im nächsten Dialog, wenn iOS es anbietet, aktivieren Sie <strong>Als Web-App öffnen</strong> und tippen Sie auf <strong>Hinzufügen</strong>.",
        "step3_img_alt": "Dialog Zum Home-Bildschirm mit Web-App-Schalter an",
    },
    "es": {
        "step1_title": "Paso 1 — botón ⋯",
        "step1_body": "Toca el botón <strong>⋯</strong> (tres puntos) en la barra de Safari.",
        "step1_img_alt": "Botón ⋯ en Safari en la página demo de {brand}",
        "step2_title": "Paso 2 — Compartir",
        "step2_body": "Elige <strong>Compartir</strong> — en Safari suele ser el cuadrado con flecha hacia arriba.",
        "step2_img_alt": "Botón Compartir en Safari",
        "step3_title": "Paso 3 — Añadir a la pantalla de inicio",
        "step3_body": "Desplázate por la lista de acciones y elige <strong>Añadir a la pantalla de inicio</strong>. Si no aparece, abre <strong>Editar acciones</strong> abajo y actívala. En la siguiente hoja, si iOS lo ofrece, activa <strong>Abrir como app web</strong> y pulsa <strong>Añadir</strong>.",
        "step3_img_alt": "Diálogo con Abrir como app web activo",
    },
    "pt": {
        "step1_title": "Passo 1 — botão ⋯",
        "step1_body": "Toque no botão <strong>⋯</strong> (três pontos) na barra do Safari.",
        "step1_img_alt": "Botão ⋯ no Safari na página demo {brand}",
        "step2_title": "Passo 2 — Partilhar",
        "step2_body": "Escolha <strong>Partilhar</strong> — no Safari costuma ser o quadrado com seta para cima.",
        "step2_img_alt": "Botão Partilhar no Safari",
        "step3_title": "Passo 3 — Adicionar à Tela Inicial",
        "step3_body": "Role a lista de ações e escolha <strong>Adicionar à Tela Inicial</strong>. Se não aparecer, abra <strong>Editar ações</strong> em baixo e ative-a. Na folha seguinte, se o iOS oferecer, ative <strong>Abrir como app web</strong> e toque em <strong>Adicionar</strong>.",
        "step3_img_alt": "Folha com Abrir como app web ligada",
    },
    "ru": {
        "step1_title": "Шаг 1. Кнопка ⋯",
        "step1_body": "Нажмите кнопку с <strong>⋯</strong> (тремя точками) в панели Safari.",
        "step1_img_alt": "Кнопка ⋯ на странице демо {brand} в Safari",
        "step2_title": "Шаг 2. «Поделиться»",
        "step2_body": "Выберите кнопку <strong>«Поделиться»</strong> — в Safari чаще это квадрат со стрелкой вверх.",
        "step2_img_alt": "Кнопка «Поделиться» в Safari",
        "step3_title": "Шаг 3. «На экран «Домой»»",
        "step3_body": "Прокрутите список действий, выберите <strong>«На экран „Домой“»</strong>. Пункта нет — откройте «Изменить действия» внизу и включите его. В следующем окне, если iOS предложит, включите <strong>«Открыть как веб‑приложение»</strong> и нажмите <strong>«Добавить»</strong>.",
        "step3_img_alt": "Окно добавления с «Открыть как веб‑приложение»",
    },
    "it": {
        "step1_title": "Passo 1 — pulsante ⋯",
        "step1_body": "Tocca il pulsante <strong>⋯</strong> (tre puntini) nella barra di Safari.",
        "step1_img_alt": "Pulsante ⋯ in Safari sulla demo {brand}",
        "step2_title": "Passo 2 — Condividi",
        "step2_body": "Scegli <strong>Condividi</strong> — in Safari di solito è il quadrato con freccia verso l’alto.",
        "step2_img_alt": "Pulsante Condividi in Safari",
        "step3_title": "Passo 3 — Aggiungi a Home",
        "step3_body": "Scorri l’elenco delle azioni e scegli <strong>Aggiungi a Home</strong>. Se manca, apri <strong>Modifica azioni</strong> in basso e attivala. Nella scheda successiva, se iOS lo propone, attiva <strong>Apri come app web</strong> e tocca <strong>Aggiungi</strong>.",
        "step3_img_alt": "Finestra con Apri come app web attivo",
    },
    "pl": {
        "step1_title": "Krok 1 — przycisk ⋯",
        "step1_body": "Stuknij przycisk <strong>⋯</strong> (trzy kropki) na pasku Safari.",
        "step1_img_alt": "Przycisk ⋯ w Safari na stronie dema {brand}",
        "step2_title": "Krok 2 — Udostępnij",
        "step2_body": "Wybierz <strong>Udostępnij</strong> — w Safari to zwykle kwadrat ze strzałką w górę.",
        "step2_img_alt": "Przycisk Udostępnij w Safari",
        "step3_title": "Krok 3 — Dodaj do ekranu głównego",
        "step3_body": "Przewiń listę czynności i wybierz <strong>Dodaj do ekranu głównego</strong>. Jeśli go nie ma, otwórz na dole <strong>Edytuj akcje</strong> i włącz go. W kolejnym oknie, jeśli iOS zaproponuje, włącz <strong>Otwórz jako aplikację internetową</strong> i stuknij <strong>Dodaj</strong>.",
        "step3_img_alt": "Okno dodawania z włączoną opcją aplikacji internetowej",
    },
    "uk": {
        "step1_title": "Крок 1. Кнопка ⋯",
        "step1_body": "Натисніть кнопку з <strong>⋯</strong> (трьома крапками) на панелі Safari.",
        "step1_img_alt": "Кнопка ⋯ на демо {brand} у Safari",
        "step2_title": "Крок 2. «Поділитися»",
        "step2_body": "Оберіть кнопку <strong>«Поділитися»</strong> — у Safari частіше це квадрат зі стрілкою вгору.",
        "step2_img_alt": "Кнопка «Поділитися» в Safari",
        "step3_title": "Крок 3. «На головний екран»",
        "step3_body": "Прокрутіть список дій і оберіть <strong>«На головний екран»</strong>. Якщо рядка немає, відкрийте «Змінити дії» внизу та увімкніть його. У наступному вікні, якщо iOS запропонує, увімкніть <strong>«Відкрити як вебпрограму»</strong> і натисніть <strong>«Додати»</strong>.",
        "step3_img_alt": "Вікно додавання з увімкненим режимом вебпрограми",
    },
    "nl": {
        "step1_title": "Stap 1 — ⋯-knop",
        "step1_body": "Tik op de knop <strong>⋯</strong> (drie puntjes) in de Safari-werkbalk.",
        "step1_img_alt": "⋯-knop in Safari op de {brand}-demo",
        "step2_title": "Stap 2 — Deel",
        "step2_body": "Kies <strong>Deel</strong> — in Safari is dat meestal het vierkant met pijl omhoog.",
        "step2_img_alt": "Deel-knop in Safari",
        "step3_title": "Stap 3 — Voeg toe aan beginscherm",
        "step3_body": "Scroll door de actielijst en kies <strong>Voeg toe aan beginscherm</strong>. Staat het er niet? Open onderaan <strong>Bewerk acties</strong> en schakel het in. In het volgende venster, als iOS het aanbiedt, zet <strong>Openen als webapp</strong> aan en tik op <strong>Voeg toe</strong>.",
        "step3_img_alt": "Dialoogvenster met webapp-schakelaar ingeschakeld",
    },
    "ro": {
        "step1_title": "Pasul 1 — buton ⋯",
        "step1_body": "Atinge butonul <strong>⋯</strong> (trei puncte) din bara Safari.",
        "step1_img_alt": "Buton ⋯ în Safari pe pagina demo {brand}",
        "step2_title": "Pasul 2 — Partajare",
        "step2_body": "Alege <strong>Partajare</strong> — în Safari este de obicei pătratul cu săgeata în sus.",
        "step2_img_alt": "Buton Partajare în Safari",
        "step3_title": "Pasul 3 — Adaugă pe ecranul de pornire",
        "step3_body": "Derulează lista de acțiuni și alege <strong>Adaugă pe ecranul de pornire</strong>. Dacă lipsește, deschide <strong>Editează acțiunile</strong> jos și activeaz-o. În foaia următoare, dacă iOS o oferă, activează <strong>Deschide ca aplicație web</strong> și atinge <strong>Adaugă</strong>.",
        "step3_img_alt": "Ecranul de adăugare cu comutatorul de aplicație web pornit",
    },
    "hi": {
        "step1_title": "चरण 1 — ⋯ बटन",
        "step1_body": "Safari की टूलबार में <strong>⋯</strong> (तीन बिंदु) बटन दबाएँ।",
        "step1_img_alt": "Safari में ⋯ बटन, {brand} डेमो पेज पर",
        "step2_title": "चरण 2 — शेयर",
        "step2_body": "<strong>शेयर</strong> चुनें — Safari में यह आमतौर पर ऊपर तीर वाला चौकोर होता है।",
        "step2_img_alt": "Safari में शेयर बटन",
        "step3_title": "चरण 3 — होम स्क्रीन में जोड़ें",
        "step3_body": "कार्रवाइयों की सूची स्क्रॉल करें और <strong>होम स्क्रीन में जोड़ें</strong> चुनें। विकल्प नहीं दिखे तो नीचे <strong>क्रियाएँ संपादित करें</strong> खोलें और चालू करें। अगले विंडो में, यदि iOS पेश करे, <strong>वेब ऐप के रूप में खोलें</strong> चालू करें और <strong>जोड़ें</strong> दबाएँ।",
        "step3_img_alt": "वेब ऐप विकल्प सक्रिय जोड़ें संवाद",
    },
    "ar": {
        "step1_title": "الخطوة 1 — زر ⋯",
        "step1_body": "اضغط زر <strong>⋯</strong> (ثلاث نقاط) في شريط Safari.",
        "step1_img_alt": "زر ⋯ في Safari على صفحة تجربة {brand}",
        "step2_title": "الخطوة 2 — مشاركة",
        "step2_body": "اختر <strong>مشاركة</strong> — في Safari غالباً مربع بسهم للأعلى.",
        "step2_img_alt": "زر المشاركة في Safari",
        "step3_title": "الخطوة 3 — إضافة إلى الشاشة الرئيسية",
        "step3_body": "مرّر قائمة الإجراءات واختر <strong>إضافة إلى الشاشة الرئيسية</strong>. إن غابت، افتح <strong>تعديل الإجراءات</strong> في الأسفل وفعّلها. في النافذة التالية، إذا عرض iOS ذلك، فعّل <strong>فتح كتطبيق ويب</strong> ثم اضغط <strong>إضافة</strong>.",
        "step3_img_alt": "مربع حوار الإضافة مع تفعيل تطبيق الويب",
    },
    "bn": {
        "step1_title": "ধাপ ১ — ⋯ বোতাম",
        "step1_body": "Safari-এর টুলবারে <strong>⋯</strong> (তিনটি বিন্দু) বোতামে ট্যাপ করুন।",
        "step1_img_alt": "Safari-তে ⋯ বোতাম, {brand} ডেমো পৃষ্ঠায়",
        "step2_title": "ধাপ ২ — শেয়ার",
        "step2_body": "<strong>শেয়ার</strong> বেছে নিন — Safari-তে এটি সাধারণত উপরের দিকে তীরযুক্ত বর্গ।",
        "step2_img_alt": "Safari-তে শেয়ার বোতাম",
        "step3_title": "ধাপ ৩ — হোম স্ক্রিনে যোগ",
        "step3_body": "অ্যাকশনের তালিকা স্ক্রল করে <strong>হোম স্ক্রিনে যোগ করুন</strong> বেছে নিন। না থাকলে নিচে <strong>ক্রিয়া সম্পাদনা</strong> খুলে সক্রিয় করুন। পরের শিটে iOS দিলে <strong>ওয়েব অ্যাপ হিসেবে খোলা</strong> চালু করে <strong>যোগ করুন</strong> ট্যাপ করুন।",
        "step3_img_alt": "ওয়েব অ্যাপ সুইচসহ যোগ-সংলাপ",
    },
    "vi": {
        "step1_title": "Bước 1 — nút ⋯",
        "step1_body": "Chạm nút <strong>⋯</strong> (ba chấm) trên thanh công cụ Safari.",
        "step1_img_alt": "Nút ⋯ trong Safari trên trang demo {brand}",
        "step2_title": "Bước 2 — Chia sẻ",
        "step2_body": "Chọn <strong>Chia sẻ</strong> — trong Safari thường là hình vuông có mũi tên hướng lên.",
        "step2_img_alt": "Nút Chia sẻ trong Safari",
        "step3_title": "Bước 3 — Thêm vào màn hình chính",
        "step3_body": "Cuộn danh sách hành động và chọn <strong>Thêm vào Màn hình chính</strong>. Không thấy? Mở <strong>Sửa hành động</strong> ở cuối và bật lên. Ở cửa sổ tiếp theo, nếu iOS đề xuất, bật <strong>Mở bằng ứng dụng web</strong> rồi chạm <strong>Thêm</strong>.",
        "step3_img_alt": "Hộp thoại thêm lệnh với ứng dụng web bật",
    },
    "az": {
        "step1_title": "Addım 1 — ⋯ düyməsi",
        "step1_body": "Safari panelində <strong>⋯</strong> (üç nöqtə) düyməsinə toxunun.",
        "step1_img_alt": "Safari-də ⋯ düyməsi, {brand} demo səhifəsində",
        "step2_title": "Addım 2 — Paylaş",
        "step2_body": "<strong>Paylaş</strong> seçin — Safari-də adətən yuxarı oxlu kvadratdır.",
        "step2_img_alt": "Safari-də Paylaş düyməsi",
        "step3_title": "Addım 3 — Əsas ekrana əlavə et",
        "step3_body": "Fəaliyyətlər siyahısını sürüşdürün və <strong>Əsas ekrana əlavə et</strong> seçin. Yoxdursa, aşağıda <strong>Əməliyyatları redaktə et</strong> açın və aktivləşdirin. Növbəti pəncərədə iOS təklif edərsə, <strong>Veb tətbiq kimi aç</strong> aktiv edib <strong>Əlavə et</strong> toxunun.",
        "step3_img_alt": "Veb tətbiq keçidi aktiv edilmiş əlavə pəncərəsi",
    },
}


def php_escape(value: str) -> str:
    return value.replace("\\", "\\\\").replace("'", "\\'")


def replace_php_string(block: str, key: str, value: str) -> str:
    esc = php_escape(value)
    pattern = rf"(\t'{key}' => ')(?:\\\\'|[^'])*(')"
    new_block, n = re.subn(pattern, rf"\1{esc}\2", block, count=1)
    if n != 1:
        raise RuntimeError(f"key {key!r}: {n} replacements")
    return new_block


def patch_quick_lead(block: str, locale_key: str) -> str:
    tail = QUICK_LEAD_TAIL[locale_key]
    pattern = r"(\t\t'quick_lead' => '.*?</a>)([^']*)(')"
    new_block, n = re.subn(pattern, rf"\1{php_escape(tail)}\3", block, count=1)
    if n != 1:
        raise RuntimeError(f"quick_lead for {locale_key}: {n} replacements")
    return new_block


def patch_file(path: Path, brand: str) -> None:
    lines = path.read_text(encoding="utf-8").splitlines(keepends=True)
    locale: str | None = None
    out: list[str] = []
    step_keys = (
        "step1_title", "step1_body", "step1_img_alt",
        "step2_title", "step2_body", "step2_img_alt",
        "step3_title", "step3_body", "step3_img_alt",
    )

    for line in lines:
        m = re.match(r"\t'([a-z]{2})' => array\(\r?\n", line)
        if m and m.group(1) in LOCALE_KEYS:
            locale = m.group(1)
            out.append(line)
            continue
        if locale and re.match(r"\t\),\r?\n", line):
            locale = None
            out.append(line)
            continue
        if locale:
            km = re.match(r"\t\t'(quick_lead|step1_title|step1_body|step1_img_alt|step2_title|step2_body|step2_img_alt|step3_title|step3_body|step3_img_alt)' => '", line)
            if km:
                key = km.group(1)
                if key == "quick_lead":
                    prefix_m = re.match(r"(\t\t'quick_lead' => '.*?</a>)", line)
                    if prefix_m:
                        tail = QUICK_LEAD_TAIL[locale]
                        out.append(prefix_m.group(1) + php_escape(tail) + "',\n")
                        continue
                if key in step_keys:
                    val = STEPS[locale][key].format(brand=brand)
                    out.append(f"\t\t'{key}' => '{php_escape(val)}',\n")
                    continue
        out.append(line)

    path.write_text("".join(out), encoding="utf-8")
    print(f"patched {path}")


def main() -> None:
    for slug, brand in BRANDS.items():
        path = REPO / "sites" / slug / "site/files/i18n/pwa-ios-install.php"
        if not path.is_file():
            raise SystemExit(f"missing {path}")
        patch_file(path, brand)


if __name__ == "__main__":
    main()
