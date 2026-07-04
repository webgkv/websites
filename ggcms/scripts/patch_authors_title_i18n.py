#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Add or update common|authors_title in site language dictionaries."""

from __future__ import annotations

import ast
import re
import sys
from pathlib import Path

# Lang IDs shared across chickenroad, aviator-log-in, powerballjackpot.
AUTHORS_TITLE: dict[int, str] = {
    1: "Authors",
    3: "Auteurs",
    4: "Autoren",
    6: "Autores",
    7: "लेखक",
    8: "Autores",
    9: "Авторы",
    11: "المؤلفون",
    12: "Müəlliflər",
    13: "লেখক",
    14: "Autori",
    15: "Auteurs",
    16: "Autorzy",
    17: "Tác giả",
    18: "Автори",
    19: "Autori",
}


def parse_php_dict(path: Path) -> dict[str, str]:
    out: dict[str, str] = {}
    for line in path.read_text(encoding="utf-8").splitlines():
        m = re.match(r"\t'([^']+)'\s*=>\s*(.+),$", line)
        if not m:
            continue
        out[m.group(1)] = ast.literal_eval(m.group(2))
    return out


def patch_file_in_place(path: Path, key: str, value: str) -> bool:
    text = path.read_text(encoding="utf-8")
    if re.search(rf"'{re.escape(key)}'\s*=>", text):
        new_text, count = re.subn(
            rf"('{re.escape(key)}'\s*=>\s*)(.+)(,)",
            lambda m: f"{m.group(1)}{value!r}{m.group(3)}",
            text,
            count=1,
        )
        if count:
            path.write_text(new_text, encoding="utf-8")
            return True
        return False
    marker = ");?>"
    if marker not in text:
        marker = ");"
    insert = f"\t'{key}' => {value!r},\n"
    path.write_text(text.replace(marker, insert + marker, 1), encoding="utf-8")
    return True


def write_php_dict(path: Path, data: dict[str, str]) -> None:
    lines = ["<?php", "$lang['common'] = array("]
    for key, value in data.items():
        lines.append(f"\t{key!r} => {value!r},")
    lines.append(");?>")
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def patch_site(site_root: Path) -> tuple[int, int]:
    lang_dir = site_root / "site/files/languages"
    if not lang_dir.is_dir():
        raise SystemExit(f"Missing languages dir: {lang_dir}")

    updated = 0
    skipped = 0
    for lang_id, title in sorted(AUTHORS_TITLE.items()):
        path = lang_dir / str(lang_id) / "dictionary/common.php"
        if not path.is_file():
            skipped += 1
            print(f"SKIP lang {lang_id}: {path} not found")
            continue
        data = parse_php_dict(path)
        if data.get("authors_title") == title and len(data) > 1:
            print(f"OK lang {lang_id}")
            continue
        if patch_file_in_place(path, "authors_title", title):
            updated += 1
            print(f"UPDATED lang {lang_id}: {title!r}")
            continue
        data["authors_title"] = title
        write_php_dict(path, data)
        updated += 1
        print(f"REWRITTEN lang {lang_id}: {title!r}")
    return updated, skipped


def main() -> None:
    if len(sys.argv) < 2:
        raise SystemExit(f"Usage: {sys.argv[0]} <site_root> [<site_root> ...]")

    total_updated = 0
    total_skipped = 0
    for arg in sys.argv[1:]:
        site_root = Path(arg).resolve()
        print(f"\n=== {site_root.name} ===")
        updated, skipped = patch_site(site_root)
        total_updated += updated
        total_skipped += skipped

    print(f"\nDone. Updated {total_updated} file(s), skipped {total_skipped} missing locale(s).")


if __name__ == "__main__":
    main()
