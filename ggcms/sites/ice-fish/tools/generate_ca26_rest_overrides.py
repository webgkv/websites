#!/usr/bin/env python3
"""One-shot generator for casino_articles#26 remaining locale override files."""
from __future__ import annotations

from copy import deepcopy
from pathlib import Path

from icefish_casino_articles_26_overrides_fr_de_es_ru import DE, ES, RU

TOOLS = Path(__file__).resolve().parent


def compact(body: dict, **meta) -> dict:
    out = deepcopy(body)
    out.update(meta)
    return out


PL = compact(
    DE,
    h2_intro="Ice Fish na BC.Game",
    short_path_title="Krótka ścieżka:",
    short_path_item="Kasyno → Wyszukaj → Ice Fish → Wybierz wersję → Demo lub Graj",
    h2_why="Dlaczego Ice Fish pasuje do BC.Game",
    h2_inout="Ice Fish od InOut na BC.Game",
    h2_bonuses="Bonusy, krypto i obrót",
    h2_app="BC.Game APK i dostęp do aplikacji",
    h2_final="Podsumowanie",
)

RO = compact(
    ES,
    h2_intro="Ice Fish pe BC.Game",
    short_path_title="Calea scurtă:",
    short_path_item="Cazino → Căutare → Ice Fish → Alege versiunea → Demo sau Joacă",
    h2_why="De ce Ice Fish se potrivește BC.Game",
    h2_inout="Ice Fish de la InOut pe BC.Game",
    h2_bonuses="Bonusuri, cripto și rulaj",
    h2_app="BC.Game APK și acces la aplicație",
    h2_final="Concluzie",
)

UA = compact(
    RU,
    h2_intro="Ice Fish на BC.Game",
    h2_about="Про BC.Game",
    h2_available="Ice Fish доступний на BC.Game",
    short_path_title="Короткий шлях:",
    short_path_item="Казино → Пошук → Ice Fish → Обрати версію → Demo або Грати",
    h2_why="Чому Ice Fish пасує BC.Game",
    h2_inout="Ice Fish від InOut на BC.Game",
    h2_mobile="Мобільний досвід на BC.Game",
    h2_bonuses="Бонуси, крипто та відіграш",
    h2_app="BC.Game APK і доступ до застосунку",
    h2_final="Висновок",
)
UA["intro_paras"] = [
    "BC.Game — популярна платформа онлайн-казино, а Ice Fish — одна з ігор, яку гравці зараз активно шукають на великих сайтах. Логіка проста: люди вже знають гру, знають BC.Game і хочуть швидко відкрити її там, де вже грають.",
    "Оригінальний Ice Fish від InOut Games доступний на BC.Game, тож не потрібно шукати копії. Знайдіть гру в лобі казино та відкрийте справжній тайтл від провайдера.",
    "Це важливо, бо Ice Fish — гра, яку зазвичай хочуть запустити швидко. BC.Game має велике лобі та зручний доступ з десктопа й мобільного.",
    "Для тих, хто прийшов саме за Ice Fish, BC.Game — зручний варіант: відкрийте казино, скористайтеся пошуком, знайдіть InOut і почніть грати.",
]

VI = compact(
    ES,
    h1="BC.Game — Ice Fish",
    h2_intro="Ice Fish trên BC.Game",
    short_path_title="Lộ trình ngắn:",
    short_path_item="Casino → Tìm kiếm → Ice Fish → Chọn phiên bản → Demo hoặc Chơi",
    h2_why="Vì sao Ice Fish hợp với BC.Game",
    h2_inout="Ice Fish từ InOut trên BC.Game",
    h2_bonuses="Bonus, crypto và wagering",
    h2_app="BC.Game APK và truy cập app",
    h2_final="Kết luận",
)

HI = compact(
    DE,
    h1="BC.Game — Ice Fish",
    h2_intro="BC.Game पर Ice Fish",
    short_path_title="छोटा रास्ता:",
    short_path_item="Casino → Search → Ice Fish → संस्करण चुनें → Demo या Play",
    h2_why="Ice Fish BC.Game के लिए क्यों सही है",
    h2_inout="BC.Game पर InOut का Ice Fish",
    h2_bonuses="बोनस, क्रिप्टो और wagering",
    h2_app="BC.Game APK और ऐप एक्सेस",
    h2_final="निष्कर्ष",
)

BN = compact(
    ES,
    h1="BC.Game — Ice Fish",
    h2_intro="BC.Game-এ Ice Fish",
    short_path_title="সংক্ষিপ্ত পথ:",
    short_path_item="Casino → Search → Ice Fish → সংস্করণ বেছে নিন → Demo বা Play",
    h2_why="কেন Ice Fish BC.Game-এ মানায়",
    h2_inout="BC.Game-এ InOut-এর Ice Fish",
    h2_bonuses="বোনাস, ক্রিপ্টো ও wagering",
    h2_app="BC.Game APK ও অ্যাপ অ্যাক্সেস",
    h2_final="উপসংহার",
)

AR = compact(
    ES,
    h1="BC.Game — Ice Fish",
    h2_intro="Ice Fish على BC.Game",
    img_hero_alt="Ice Fish في لوبي كازينو BC.Game",
    short_path_title="المسار القصير:",
    short_path_item="Casino → Search → Ice Fish → اختر الإصدار → Demo أو Play",
    h2_about="عن BC.Game",
    h2_available="Ice Fish متاح على BC.Game",
    h2_why="لماذا يناسب Ice Fish منصة BC.Game",
    h2_inout="Ice Fish من InOut على BC.Game",
    h2_mobile="تجربة الجوال على BC.Game",
    h2_bonuses="المكافآت والكريبتو والرول",
    h2_app="BC.Game APK والوصول للتطبيق",
    h2_final="الخلاصة",
    h2_faq="FAQ",
)

AZ = compact(
    DE,
    h1="BC.Game — Ice Fish",
    h2_intro="BC.Game-də Ice Fish",
    short_path_title="Qısa yol:",
    short_path_item="Casino → Axtarış → Ice Fish → Versiya seç → Demo və ya Oyna",
    h2_why="Niyə Ice Fish BC.Game-ə uyğundur",
    h2_inout="BC.Game-də InOut Ice Fish",
    h2_bonuses="Bonuslar, kripto və wager",
    h2_app="BC.Game APK və tətbiq girişi",
    h2_final="Nəticə",
)


def write_py(path: Path, header: str, export: dict) -> None:
    lines = [header, ""]
    for code, data in export.items():
        lines.append(f"{code.upper()} = {repr(data)}")
        lines.append("")
    keys = ", ".join(f'"{k}": {k.upper()}' for k in export)
    lines.append(f"EXPORT = {{{keys}}}")
    lines.append("")
    path.write_text("\n".join(lines), encoding="utf-8")


write_py(
    TOOLS / "icefish_casino_articles_26_overrides_rest_pl_ro_ua.py",
    '# -*- coding: utf-8 -*-\n"""Overrides for casino_articles#26 — PL, RO, UA."""',
    {"pl": PL, "ro": RO, "ua": UA},
)
write_py(
    TOOLS / "icefish_casino_articles_26_overrides_rest_vi_hi_bn.py",
    '# -*- coding: utf-8 -*-\n"""Overrides for casino_articles#26 — VI, HI, BN."""',
    {"vi": VI, "hi": HI, "bn": BN},
)
write_py(
    TOOLS / "icefish_casino_articles_26_overrides_rest_ar_az.py",
    '# -*- coding: utf-8 -*-\n"""Overrides for casino_articles#26 — AR, AZ."""',
    {"ar": AR, "az": AZ},
)
print("Wrote PL/RO/UA, VI/HI/BN, AR/AZ override files")
