#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Sync common.php dictionaries to EN canonical pack and apply locale patches."""

from __future__ import annotations

import json
import re
import sys
import ast
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "site/files/languages"
PATCHES_FILE = Path(__file__).with_name("powerball_common_dict_patches.json")
DEFAULT_EN_PACK = Path("/home/lenovo/Downloads/04/full-language-pack-en-2026-06-22-120332.json")

# From site/scripts/apply_hero_lottery_dict.php (already reviewed locales).
HERO_BY_LANG: dict[int, dict[str, str]] = {
    3: {
        "hero_subtitle": "C’est le moment de tenter le jackpot !",
        "hero_h1_prefix": "Participez",
        "hero_h1_accent_1": "maintenant",
        "hero_h1_mid": "pour une",
        "hero_h1_accent_2": "gagne rapide",
        "hero_h1_tail": "aux jackpots",
        "hero_lead": "Jouez aux plus grandes loteries du monde depuis chez vous.",
        "hero_cta": "Jouer à la loterie",
        "hero_explore": "En savoir plus",
    },
    4: {
        "hero_subtitle": "Jetzt ist Ihre Chance auf den Jackpot!",
        "hero_h1_prefix": "Machen Sie",
        "hero_h1_accent_1": "mit",
        "hero_h1_mid": "für einen",
        "hero_h1_accent_2": "schnellen Gewinn",
        "hero_h1_tail": "bei Jackpots",
        "hero_lead": "Spielen Sie die größten Lotterien der Welt von zu Hause aus.",
        "hero_cta": "Lotterie spielen",
        "hero_explore": "Mehr erfahren",
    },
    6: {
        "hero_subtitle": "¡Ahora es tu oportunidad de ganar el jackpot!",
        "hero_h1_prefix": "Participa",
        "hero_h1_accent_1": "ya",
        "hero_h1_mid": "para un",
        "hero_h1_accent_2": "premio rápido",
        "hero_h1_tail": "en los jackpots",
        "hero_lead": "Juega las loterías más grandes del mundo desde casa.",
        "hero_cta": "Jugar lotería",
        "hero_explore": "Explorar más",
    },
    9: {
        "hero_subtitle": "Сейчас ваш шанс выиграть джекпот!",
        "hero_h1_prefix": "Участвуйте",
        "hero_h1_accent_1": "в игре",
        "hero_h1_mid": "ради",
        "hero_h1_accent_2": "быстрого выигрыша",
        "hero_h1_tail": "в джекпотах",
        "hero_lead": "Играйте в крупнейшие лотереи мира из дома и выигрывайте джекпоты.",
        "hero_cta": "Играть в лотерею",
        "hero_explore": "Подробнее",
    },
    18: {
        "hero_subtitle": "Зараз ваш шанс виграти джекпот!",
        "hero_h1_prefix": "Беріть",
        "hero_h1_accent_1": "участь",
        "hero_h1_mid": "за",
        "hero_h1_accent_2": "швидкий виграш",
        "hero_h1_tail": "у джекпотах",
        "hero_lead": "Грайте в найбільші лотереї світу вдома та вигравайте джекпоти.",
        "hero_cta": "Грати в лотерею",
        "hero_explore": "Дізнатися більше",
    },
}

KEEP_EN_VALUES = {
    "sitename",
    "quick_access_google_play",
    "quick_access_app_store",
    "breadcrumb_separator",
    "games_cat_crash",
    "games_cat_crash-p2e",
    "predictor_menu",
    "guides_cat_bonus",
    "popup_partner",
}


def parse_php_dict(path: Path) -> dict[str, str]:
    out: dict[str, str] = {}
    for line in path.read_text(encoding="utf-8").splitlines():
        m = re.match(r"\t'([^']+)' => (.+),$", line)
        if not m:
            continue
        out[m.group(1)] = ast.literal_eval(m.group(2))
    return out


def write_php_dict(path: Path, data: dict[str, str]) -> None:
    lines = ["<?php", "$lang['common'] = array("]
    for key, value in data.items():
        lines.append(f"\t{key!r} => {value!r},")
    lines.append(");?>")
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def load_en_canonical(pack_path: Path) -> dict[str, str]:
    payload = json.loads(pack_path.read_text(encoding="utf-8"))
    common = payload.get("common")
    if not isinstance(common, dict) or not common:
        raise SystemExit(f"Invalid EN pack: {pack_path}")
    return {str(k): str(v) for k, v in common.items()}


def load_patches() -> dict[int, dict[str, str]]:
    if not PATCHES_FILE.is_file():
        return {}
    raw = json.loads(PATCHES_FILE.read_text(encoding="utf-8"))
    return {int(k): {str(kk): str(vv) for kk, vv in v.items()} for k, v in raw.items()}


def build_locale_dict(
    lang_id: int,
    en: dict[str, str],
    existing: dict[str, str],
    patches: dict[int, dict[str, str]],
) -> dict[str, str]:
    if lang_id == 1:
        return dict(en)

    out: dict[str, str] = {}
    hero_patch = HERO_BY_LANG.get(lang_id, {})
    extra_patch = patches.get(lang_id, {})

    for key in en:
        if key in extra_patch:
            out[key] = extra_patch[key]
            continue
        if key in hero_patch:
            out[key] = hero_patch[key]
            continue
        if key in existing and existing[key] != en[key]:
            out[key] = existing[key]
            continue
        if key in KEEP_EN_VALUES:
            out[key] = en[key]
            continue
        if key in existing:
            out[key] = existing[key]
        else:
            out[key] = en[key]
    return out


def main() -> None:
    pack_path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_EN_PACK
    en = load_en_canonical(pack_path)
    patches = load_patches()

    updated = 0
    for lang_dir in sorted(LANG_DIR.glob("*/dictionary/common.php"), key=lambda p: int(p.parts[-3])):
        lang_id = int(lang_dir.parts[-3])
        existing = parse_php_dict(lang_dir)
        new_dict = build_locale_dict(lang_id, en, existing, patches)
        if new_dict != {k: existing.get(k) for k in en} or set(existing) != set(en):
            write_php_dict(lang_dir, new_dict)
            updated += 1
            print(f"UPDATED lang {lang_id} ({len(new_dict)} keys)")
        else:
            print(f"OK lang {lang_id}")

    print(f"Done. Updated {updated} file(s). Canonical keys: {len(en)}.")


if __name__ == "__main__":
    main()
