#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Append UA + translated locales to icefish_home_strings.py"""

from __future__ import annotations

import ast
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
STRINGS_PY = ROOT / "tools/icefish_home_strings.py"
I18N_JSON = ROOT / "tools/icefish_home_i18n.json"


def main() -> None:
    data = json.loads(I18N_JSON.read_text(encoding="utf-8"))
    base = STRINGS_PY.read_text(encoding="utf-8")
    if "STRINGS.update(" in base:
        base = re.sub(r"\nSTRINGS\.update\(.*", "", base, flags=re.S)

    # parse existing STRINGS from file
    mod: dict = {}
    exec(compile(base, str(STRINGS_PY), "exec"), mod)
    strings = mod["STRINGS"]
    strings.update(data)

    out = STRINGS_PY.read_text(encoding="utf-8").split("STRINGS.update(")[0].rstrip() + "\n"
    out += "\nSTRINGS.update(" + json.dumps(data, ensure_ascii=False, indent=4) + ")\n"
    STRINGS_PY.write_text(out, encoding="utf-8")
    print("Updated", STRINGS_PY, "locales:", sorted(strings.keys()))


if __name__ == "__main__":
    main()
