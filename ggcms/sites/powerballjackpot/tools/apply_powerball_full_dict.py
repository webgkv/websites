#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Merge EN canonical + multi pack + hand-crafted home patches into all common.php files."""

from __future__ import annotations

import ast
import json
import re
import sys
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "site/files/languages"
TOOLS = Path(__file__).resolve().parent
META_FILE = TOOLS / "powerball_lang_meta.json"
PATCHES_FILE = TOOLS / "powerball_common_dict_patches.json"
HOME_PATCHES_FILE = TOOLS / "powerball_home_dict_patches.json"
MENU_FILE = TOOLS / "powerball_menu_items.json"
DEFAULT_PACK = Path("/home/lenovo/Downloads/04/full-language-pack-multi-2026-06-22-153454.json")
DEFAULT_EXPORT_DIR = Path("/home/lenovo/Downloads/04")

ALLOW_SAME_AS_EN = {
    "sitename",
    "quick_access_google_play",
    "quick_access_app_store",
    "breadcrumb_separator",
    "games_cat_crash",
    "games_cat_crash-p2e",
    "predictor_menu",
    "guides_cat_bonus",
    "popup_partner",
    "guides_title",
    "home_checker_matched_end",
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


def build_menus(meta: dict, source_pack: dict) -> dict[str, list]:
    if isinstance(source_pack.get("menus"), dict) and source_pack["menus"]:
        return source_pack["menus"]

    menu_by_id: dict[str, list] = {}
    if MENU_FILE.is_file():
        raw = load_json(MENU_FILE)
        menu_by_id = {str(k): v for k, v in raw.items() if isinstance(v, list)}

    menus: dict[str, list] = {}
    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        url = meta[lang_id]["url"]
        menus[url] = menu_by_id.get(lang_id) or menu_by_id.get("1") or []
    return menus


def build_locale(
    lang_id: str,
    en: dict[str, str],
    pack_dict: dict[str, str],
    php_dict: dict[str, str],
    common_patches: dict[str, str],
    home_patches: dict[str, str],
) -> dict[str, str]:
    if lang_id == "1":
        return {k: en[k] for k in en}

    out: dict[str, str] = {}
    for key in en:
        if key in ALLOW_SAME_AS_EN:
            out[key] = en[key]
        elif key in home_patches:
            out[key] = home_patches[key]
        elif key in common_patches:
            out[key] = common_patches[key]
        elif key in pack_dict and pack_dict[key] != en[key]:
            out[key] = pack_dict[key]
        elif key in php_dict and php_dict[key] != en[key]:
            out[key] = php_dict[key]
        else:
            out[key] = en[key]
    return out


def audit(lang_id: str, data: dict[str, str], en: dict[str, str], url: str) -> list[str]:
    if lang_id == "1":
        return []
    same = [k for k in en if data.get(k) == en[k] and k not in ALLOW_SAME_AS_EN]
    if not same:
        return []
    return [f"{url} ({lang_id}) still EN ({len(same)}): {', '.join(same[:8])}{'…' if len(same) > 8 else ''}"]


def export_pack(
    all_locales: dict[str, dict[str, str]],
    meta: dict,
    menus: dict[str, list],
    export_dir: Path,
) -> tuple[Path, Path]:
    export_dir.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime("%Y-%m-%d-%H%M%S")
    exported_at = datetime.now().astimezone().isoformat(timespec="seconds")
    languages = []
    dictionaries: dict[str, dict[str, str]] = {}
    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        row = dict(meta[lang_id])
        row["id"] = int(lang_id)
        row["display"] = 1
        languages.append(row)
        dictionaries[row["url"]] = all_locales[lang_id]

    dict_only = {
        "schema": "common_dictionary_multi_v1",
        "exported_at": exported_at,
        "languages": languages,
        "dictionaries": dictionaries,
    }
    dict_path = export_dir / f"common-dictionary-multi-translated-{stamp}.json"
    dict_path.write_text(json.dumps(dict_only, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

    full = {
        "schema": "full_language_pack_multi_v1",
        "exported_at": exported_at,
        "languages": languages,
        "dictionaries": dictionaries,
        "menus": menus,
    }
    full_path = export_dir / f"full-language-pack-multi-translated-{stamp}.json"
    full_path.write_text(json.dumps(full, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    return dict_path, full_path


def main() -> None:
    pack_path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_PACK
    export_dir = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_EXPORT_DIR

    if not HOME_PATCHES_FILE.is_file():
        raise SystemExit(f"Missing hand-crafted patches: {HOME_PATCHES_FILE}")

    en = parse_php_dict(LANG_DIR / "1/dictionary/common.php")
    meta = load_json(META_FILE)
    pack = load_json(pack_path)
    common_patches_raw = load_json(PATCHES_FILE) if PATCHES_FILE.is_file() else {}
    home_patches_raw = load_json(HOME_PATCHES_FILE)

    all_locales: dict[str, dict[str, str]] = {}
    issues: list[str] = []

    for lang_id in sorted(meta.keys(), key=lambda x: int(x)):
        url = meta[lang_id]["url"]
        pack_dict = pack.get("dictionaries", {}).get(url, {})
        php_path = LANG_DIR / lang_id / "dictionary/common.php"
        php_dict = parse_php_dict(php_path) if php_path.is_file() else {}
        common_patches = {str(k): str(v) for k, v in common_patches_raw.get(lang_id, {}).items()}
        home_patches = {str(k): str(v) for k, v in home_patches_raw.get(lang_id, {}).items()}

        locale = build_locale(lang_id, en, pack_dict, php_dict, common_patches, home_patches)
        all_locales[lang_id] = locale

        out_path = LANG_DIR / lang_id / "dictionary/common.php"
        if not out_path.parent.parent.is_dir():
            print(f"SKIP lang {lang_id} — directory missing")
            continue
        existing = parse_php_dict(out_path) if out_path.is_file() else {}
        if locale != existing:
            write_php_dict(out_path, locale)
            print(f"UPDATED lang {lang_id} ({url}) — {len(locale)} keys")
        else:
            print(f"OK lang {lang_id} ({url})")

        issues.extend(audit(lang_id, locale, en, url))

    export_dict, export_full = export_pack(all_locales, meta, build_menus(meta, pack), export_dir)
    print(f"EXPORTED {export_dict}")
    print(f"EXPORTED {export_full}")

    if issues:
        print("\nAUDIT WARNINGS:")
        for issue in issues:
            print(f"  - {issue}")
        raise SystemExit(1)

    print(f"\nAUDIT OK: {len(meta)} locales, {len(en)} keys each.")


if __name__ == "__main__":
    main()
