#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build locale maps from FR canonical strings + per-lang translation lists, then rebuild SECTIONS_I18N."""

from __future__ import annotations

import importlib.util
import json
import subprocess
import sys
from copy import deepcopy
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
PACKS_DIR = TOOLS / "i18n_packs"
MAPS_DIR = PACKS_DIR / "locale_maps"
sys.path.insert(0, str(TOOLS))
sys.path.insert(0, str(PACKS_DIR))

from i18n_packs._link_labels import LINK_LABELS  # noqa: E402
from i18n_packs.locale_maps.translation_lists import LISTS  # noqa: E402

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


def _load_fr_ordered() -> list[str]:
    return json.loads((PACKS_DIR / "locale_maps" / "fr_ordered.json").read_text(encoding="utf-8"))


def _translate_obj(obj, mapping: dict[str, str]):
    if isinstance(obj, str):
        if obj in KEEP:
            return obj
        return mapping.get(obj, obj)
    if isinstance(obj, dict):
        return {k: _translate_obj(v, mapping) for k, v in obj.items() if k != "_link_labels"}
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
    pack = deepcopy(pack)
    pack["_link_labels"] = LINK_LABELS[lang]
    path = PACKS_DIR / f"{lang}.py"
    path.write_text(
        "# -*- coding: utf-8 -*-\n"
        f'"""{lang.upper()} locale pack."""\n\n'
        "from __future__ import annotations\n\n"
        f"LANG = {lang!r}\n\n"
        f"PACK = {pack!r}\n",
        encoding="utf-8",
    )
    print(f"Wrote {path}")


def main() -> None:
    fr_ordered = _load_fr_ordered()
    fr_pack = _load_fr_pack()

    for lang, target_list in LISTS.items():
        if len(target_list) != len(fr_ordered):
            raise ValueError(f"{lang}: expected {len(fr_ordered)} strings, got {len(target_list)}")
        mapping = dict(zip(fr_ordered, target_list))
        pack = _translate_obj(fr_pack, mapping)
        _write_pack(lang, pack)

    # de from template
    spec = importlib.util.spec_from_file_location("de", PACKS_DIR / "_template.py")
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    de_pack = deepcopy(mod.PACK)
    de_pack["_link_labels"] = LINK_LABELS["de"]
    _write_pack("de", de_pack)

    subprocess.check_call([sys.executable, str(TOOLS / "complete_i18n_build.py")], cwd=TOOLS)


if __name__ == "__main__":
    main()
