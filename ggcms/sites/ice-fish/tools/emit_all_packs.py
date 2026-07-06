#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Generate i18n pack modules from FR base + per-locale overrides, then rebuild."""

from __future__ import annotations

import copy
import importlib.util
import json
import subprocess
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent

spec = importlib.util.spec_from_file_location("bd", TOOLS / "build_i18n_data.py")
bd = importlib.util.module_from_spec(spec)
spec.loader.exec_module(bd)
FR_BASE = copy.deepcopy(bd.TRANSLATIONS["fr"])


def _merge(base: dict, ov: dict) -> dict:
    out = copy.deepcopy(base)
    for k, v in ov.items():
        if isinstance(v, dict) and isinstance(out.get(k), dict):
            out[k] = _merge(out[k], v)
        else:
            out[k] = v
    return out


def _write_pack(lang: str, pack: dict) -> None:
    path = TOOLS / "i18n_packs" / f"{lang}.py"
    path.write_text(
        "# -*- coding: utf-8 -*-\n"
        f'"""{lang.upper()} locale pack."""\n\n'
        "from __future__ import annotations\n\n"
        "from build_i18n_data import specs\n\n"
        f'LANG = "{lang}"\n\n'
        f"PACK = {pack!r}\n",
        encoding="utf-8",
    )


# Load overrides from JSON (hand-authored literary translations)
OVERRIDES: dict = json.loads((TOOLS / "lang_overrides.json").read_text(encoding="utf-8"))

LANGS = ["de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]

if __name__ == "__main__":
    for lang in LANGS:
        if lang not in OVERRIDES:
            print(f"skip {lang} (no overrides)")
            continue
        pack = _merge(FR_BASE, OVERRIDES[lang])
        _write_pack(lang, pack)
        print(f"wrote i18n_packs/{lang}.py")
    subprocess.check_call([sys.executable, str(TOOLS / "complete_i18n_build.py")], cwd=TOOLS)
