#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build per-locale section keys from EN + string-level translation table."""

from __future__ import annotations

import json
import re
from copy import deepcopy
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
EN_KEYS = json.loads(Path("/tmp/en_keys.json").read_text(encoding="utf-8"))
FR_KEYS = json.loads((TOOLS / "cluster_section_translations.json").read_text(encoding="utf-8"))["fr"]

# UI / numeric literals kept identical across locales
KEEP = {
    "1. BET", "2. ADVANCE", "3. CASH OUT",
    "98%", "95.5%", "~$10,000", "~$20,000",
    "Easy, Medium, Hard, Hardcore",
    "Chicken Road", "Chicken Road 2.0", "InOut Games",
    "Play", "Cash Out", "Chicken Road 2.0 review",
}


def _align_pairs(en_block: dict, loc_block: dict) -> dict[str, str]:
    out: dict[str, str] = {}
    for field in en_block:
        en_list = en_block.get(field) or []
        loc_list = loc_block.get(field) or []
        if not isinstance(en_list, list):
            en_list = [en_list]
        if not isinstance(loc_list, list):
            loc_list = [loc_list]
        for en, loc in zip(en_list, loc_list):
            if en and loc:
                out[en] = loc
    return out


def _fr_map() -> dict[str, str]:
    m: dict[str, str] = {}
    for sid in EN_KEYS:
        if sid in ("lead", "where-to-play"):
            continue
        if sid not in FR_KEYS:
            continue
        m.update(_align_pairs(EN_KEYS[sid], FR_KEYS[sid]))
    return m


# String-level translations: EN text -> locale text
# fr is derived from cluster_section_translations.json via _fr_map()
STRING_TABLE: dict[str, dict[str, str]] = {}

# Load hand-authored table if present
_table_path = TOOLS / "string_translation_table.json"
if _table_path.exists():
    STRING_TABLE = json.loads(_table_path.read_text(encoding="utf-8"))


def _apply_map(en_text: str, lang: str) -> str:
    if en_text in KEEP or en_text.strip() in KEEP:
        return en_text
    if lang == "fr":
        return _fr_map().get(en_text, en_text)
    per_lang = STRING_TABLE.get(en_text, {})
    return per_lang.get(lang, en_text)


def _translate_block(block: dict, lang: str) -> dict:
    out = {}
    for field, vals in block.items():
        if isinstance(vals, list):
            out[field] = [_apply_map(v, lang) for v in vals]
        else:
            out[field] = _apply_map(vals, lang)
    return out


def build_locale(lang: str) -> dict:
    loc = {}
    for sid, block in EN_KEYS.items():
        if sid in ("lead", "where-to-play"):
            continue
        loc[sid] = _translate_block(block, lang)
    return loc


def main() -> None:
    langs = ["fr", "de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]
    out = {lang: build_locale(lang) for lang in langs}
    path = TOOLS / "cluster_section_translations.json"
    path.write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Wrote {path} ({path.stat().st_size} bytes) for {len(langs)} locales")
    # sanity: fr should differ from en
    en_sample = EN_KEYS["chickenroad-app"]["h2"][0]
    fr_sample = out["fr"]["chickenroad-app"]["h2"][0]
    de_sample = out["de"]["chickenroad-app"]["h2"][0]
    print("en h2:", en_sample)
    print("fr h2:", fr_sample)
    print("de h2:", de_sample)


if __name__ == "__main__":
    main()
