#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Compose locale packs from FR canonical + per-locale string maps, then rebuild SECTIONS_I18N."""

from __future__ import annotations

import importlib.util
import json
import subprocess
import sys
from copy import deepcopy
from pathlib import Path

TOOLS = Path(__file__).resolve().parent.parent
PACKS_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from i18n_packs._link_labels import LINK_LABELS  # noqa: E402

KEEP = {
    "1. BET", "2. ADVANCE", "3. CASH OUT",
    "98%", "95.5%", "~$10,000", "~$20,000",
    "Easy, Medium, Hard, Hardcore",
    "Ice Fish", "Ice Fish 2.0", "InOut Games",
    "Play", "Cash Out", "RTP",
    "__INOUT__", "__CR2__ (95.5% RTP)",
}


def _load_fr_pack() -> dict:
    spec = importlib.util.spec_from_file_location("bd", TOOLS / "build_i18n_data.py")
    bd = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(bd)
    return deepcopy(bd.TRANSLATIONS["fr"])


def _load_de_pack() -> dict:
    spec = importlib.util.spec_from_file_location("de", PACKS_DIR / "_template.py")
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return deepcopy(mod.PACK)


def _translate_obj(obj, mapping: dict[str, str]):
    if isinstance(obj, str):
        if obj in KEEP:
            return obj
        return mapping.get(obj, obj)
    if isinstance(obj, dict):
        return {k: _translate_obj(v, mapping) for k, v in obj.items()}
    if isinstance(obj, list):
        out = []
        for item in obj:
            if isinstance(item, tuple) and len(item) == 2:
                out.append((_translate_obj(item[0], mapping), _translate_obj(item[1], mapping)))
            else:
                out.append(_translate_obj(item, mapping))
        return out
    return obj


def _write_pack(lang: str, pack: dict) -> None:
    labels = LINK_LABELS.get(lang, {})
    pack = deepcopy(pack)
    pack["_link_labels"] = labels
    path = PACKS_DIR / f"{lang}.py"
    body = (
        "# -*- coding: utf-8 -*-\n"
        f'"""{lang.upper()} locale pack."""\n\n'
        "from __future__ import annotations\n\n"
        "LANG = "
        + repr(lang)
        + "\n\n"
        "PACK = "
        + repr(pack)
        + "\n"
    )
    path.write_text(body, encoding="utf-8")
    print(f"Wrote {path}")


def main() -> None:
    maps_dir = PACKS_DIR / "locale_maps"
    fr = _load_fr_pack()
    de = _load_de_pack()
    _write_pack("de", de)

    for lang in ["es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro"]:
        map_path = maps_dir / f"{lang}.json"
        if not map_path.exists():
            raise FileNotFoundError(map_path)
        mapping = json.loads(map_path.read_text(encoding="utf-8"))
        pack = _translate_obj(fr, mapping)
        _write_pack(lang, pack)

    subprocess.check_call([sys.executable, str(TOOLS / "complete_i18n_build.py")], cwd=TOOLS)


if __name__ == "__main__":
    main()
