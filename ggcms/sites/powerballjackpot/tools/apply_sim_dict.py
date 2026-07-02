#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Merge sim_* keys from powerball_sim_dict_all_locales.json into common.php files."""

from __future__ import annotations

import ast
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LANG_DIR = ROOT / "site/files/languages"
PACK = Path(__file__).resolve().parent / "powerball_sim_dict_all_locales.json"


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


def main() -> int:
    pack = json.loads(PACK.read_text(encoding="utf-8"))
    en_keys = set(pack["1"].keys())
    updated = 0
    for lang_id, sim_dict in sorted(pack.items(), key=lambda x: int(x[0])):
        path = LANG_DIR / lang_id / "dictionary/common.php"
        if not path.is_file():
            print(f"skip missing {path}", file=sys.stderr)
            continue
        data = parse_php_dict(path)
        for key in list(data.keys()):
            if key.startswith("sim_") and key not in en_keys:
                del data[key]
        for key, val in sim_dict.items():
            data[key] = val
        write_php_dict(path, data)
        updated += 1
        print(f"updated {path} ({len(sim_dict)} sim keys)")
    print(f"done: {updated} locales")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
