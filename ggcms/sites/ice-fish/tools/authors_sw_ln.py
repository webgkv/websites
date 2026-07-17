#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Load Ice Fish authors sw/ln profile fields."""

from __future__ import annotations

import json
from pathlib import Path

DATA_DIR = Path(__file__).resolve().parent / "authors_sw_ln_data"


def get_locale(entity_id: int, lang: str) -> dict[str, str] | None:
    path = DATA_DIR / f"authors_{entity_id}.json"
    if not path.is_file():
        return None
    data = json.loads(path.read_text(encoding="utf-8"))
    loc = data.get("locales", {}).get(lang)
    return dict(loc) if loc else None
