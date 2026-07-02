#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build, apply and export PowerBall Jackpot common dictionaries for all 16 locales."""

from __future__ import annotations

import ast
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "site/files/languages"
TOOLS = Path(__file__).resolve().parent
META_FILE = TOOLS / "powerball_lang_meta.json"
MENU_FILE = TOOLS / "powerball_menu_items.json"
ALL_LOCALES_FILE = TOOLS / "powerball_common_dict_all_locales.json"
DEFAULT_EN_PACK = Path("/home/lenovo/Downloads/04/full-language-pack-en-2026-06-22-120332.json")
DEFAULT_EXPORT_DIR = Path("/home/lenovo/Downloads/04")

# Last-mile fixes where cognates still match EN but locale has a better form.
FINAL_OVERRIDES: dict[str, dict[str, str]] = {
    "4": {"popup_partner": "PARTNER"},
    "12": {"guides_cat_bonus": "Bonuslar"},
    "14": {"popup_partner": "SOCIO"},
    "15": {"popup_partner": "PARTNER"},
    "16": {"guides_cat_bonus": "Bonusy"},
    "19": {"guides_cat_bonus": "Bonusuri"},
}

ALLOW_SAME_AS_EN = {
    "sitename",
    "quick_access_google_play",
    "quick_access_app_store",
    "breadcrumb_separator",
    "games_cat_crash",
    "games_cat_crash-p2e",
    "predictor_menu",
    "guides_cat_bonus",
    "guides_title",
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


def load_json(path: Path) -> dict:
    return json.loads(path.read_text(encoding="utf-8"))


def load_en_canonical(pack_path: Path) -> dict[str, str]:
    payload = load_json(pack_path)
    common = payload.get("common")
    if not isinstance(common, dict) or not common:
        raise SystemExit(f"Invalid EN pack: {pack_path}")
    return {str(k): str(v) for k, v in common.items()}


def collect_current_dicts() -> dict[str, dict[str, str]]:
    out: dict[str, dict[str, str]] = {}
    for path in sorted(LANG_DIR.glob("*/dictionary/common.php"), key=lambda p: int(p.parts[-3])):
        lang_id = path.parts[-3]
        out[lang_id] = parse_php_dict(path)
    return out


def build_all_locales(en: dict[str, str], current: dict[str, dict[str, str]]) -> dict[str, dict[str, str]]:
    key_order = list(en.keys())
    meta_ids = sorted(load_json(META_FILE).keys(), key=lambda x: int(x))
    all_locales: dict[str, dict[str, str]] = {}

    for lang_id in meta_ids:
        if lang_id == "1":
            all_locales[lang_id] = {k: en[k] for k in key_order}
            continue

        src = current.get(lang_id, {})
        overrides = FINAL_OVERRIDES.get(lang_id, {})
        locale: dict[str, str] = {}
        for key in key_order:
            if key in overrides:
                locale[key] = overrides[key]
            elif key in src and src[key] != en[key]:
                locale[key] = src[key]
            elif key in ALLOW_SAME_AS_EN:
                locale[key] = en[key]
            elif key in src:
                locale[key] = src[key]
            else:
                locale[key] = en[key]
        all_locales[lang_id] = locale

    return all_locales


def apply_to_site(all_locales: dict[str, dict[str, str]], en: dict[str, str]) -> int:
    updated = 0
    key_order = list(en.keys())
    for lang_id, data in sorted(all_locales.items(), key=lambda item: int(item[0])):
        aligned = {k: data[k] for k in key_order}
        path = LANG_DIR / lang_id / "dictionary/common.php"
        if not path.parent.parent.is_dir():
            print(f"SKIP lang {lang_id} — directory missing")
            continue
        existing = parse_php_dict(path) if path.is_file() else {}
        if aligned != existing:
            write_php_dict(path, aligned)
            updated += 1
            print(f"UPDATED site lang {lang_id}")
        else:
            print(f"OK site lang {lang_id}")
    return updated


def export_packs(
    all_locales: dict[str, dict[str, str]],
    meta: dict[str, dict],
    menu: dict[str, list],
    export_dir: Path,
) -> None:
    export_dir.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime("%Y-%m-%d-%H%M%S")
    exported_at = datetime.now().astimezone().isoformat(timespec="seconds")

    multi = {
        "schema": "common_dictionary_multi_v1",
        "exported_at": exported_at,
        "languages": [],
        "dictionaries": {},
    }

    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        row = dict(meta[lang_id])
        row["id"] = int(lang_id)
        row["display"] = 1
        multi["languages"].append(row)
        multi["dictionaries"][row["url"]] = all_locales[lang_id]

    multi_path = export_dir / f"common-dictionary-multi-16-{stamp}.json"
    multi_path.write_text(json.dumps(multi, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"EXPORTED {multi_path}")

    pack_multi = {
        "schema": "full_language_pack_multi_v1",
        "exported_at": exported_at,
        "languages": multi["languages"],
        "dictionaries": multi["dictionaries"],
        "menus": {},
    }
    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        row = meta[lang_id]
        pack_multi["menus"][row["url"]] = menu.get(lang_id) or menu["1"]
    pack_path = export_dir / f"full-language-pack-multi-16-{stamp}.json"
    pack_path.write_text(json.dumps(pack_multi, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"EXPORTED {pack_path}")

    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        row = meta[lang_id]
        pack = {
            "schema": "full_language_pack_v1",
            "exported_at": exported_at,
            "language": {
                "id": int(lang_id),
                "name": row["name"],
                "rank": row["rank"],
                "url": row["url"],
                "localization": row["localization"],
                "display": 1,
            },
            "common": all_locales[lang_id],
            "menu_items": menu.get(lang_id) or menu["1"],
        }
        out = export_dir / f"full-language-pack-{row['url']}-{stamp}.json"
        out.write_text(json.dumps(pack, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
        print(f"EXPORTED {out.name}")


def audit(all_locales: dict[str, dict[str, str]], en: dict[str, str], meta: dict[str, dict]) -> list[str]:
    issues: list[str] = []
    key_order = list(en.keys())
    for lang_id, data in all_locales.items():
        if set(data.keys()) != set(key_order):
            issues.append(f"lang {lang_id}: key mismatch")
        if list(data.keys()) != key_order:
            issues.append(f"lang {lang_id}: key order mismatch")
        if lang_id == "1":
            continue
        same = [k for k in key_order if data.get(k) == en[k] and k not in ALLOW_SAME_AS_EN]
        if same:
            code = meta.get(lang_id, {}).get("url", lang_id)
            issues.append(f"{code} ({lang_id}) still EN: {', '.join(same)}")
    return issues


def main() -> None:
    en_pack = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_EN_PACK
    export_dir = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_EXPORT_DIR

    en = load_en_canonical(en_pack)
    meta = load_json(META_FILE)
    menu = load_json(MENU_FILE)
    current = collect_current_dicts()

    if set(meta.keys()) != set(current.keys()):
        missing = set(meta.keys()) - set(current.keys())
        extra = set(current.keys()) - set(meta.keys())
        raise SystemExit(f"Locale set mismatch. missing={sorted(missing)} extra={sorted(extra)}")

    all_locales = build_all_locales(en, current)
    ALL_LOCALES_FILE.write_text(
        json.dumps(all_locales, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(f"WROTE {ALL_LOCALES_FILE}")

    updated = apply_to_site(all_locales, en)
    export_packs(all_locales, meta, menu, export_dir)

    issues = audit(all_locales, en, meta)
    if issues:
        print("\nAUDIT WARNINGS:")
        for issue in issues:
            print(f"  - {issue}")
    else:
        print("\nAUDIT OK: 16 locales aligned, 68 keys each.")

    print(f"Done. Site files updated: {updated}.")


if __name__ == "__main__":
    main()
