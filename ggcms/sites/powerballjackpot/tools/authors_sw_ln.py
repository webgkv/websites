#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Load authors sw/ln profile fields from JSON data files."""

from __future__ import annotations

import json
from pathlib import Path

DATA_DIR = Path(__file__).resolve().parent / "authors_sw_ln_data"


def _load(entity_id: int) -> dict | None:
    path = DATA_DIR / f"authors_{entity_id}.json"
    if not path.is_file():
        return None
    return json.loads(path.read_text(encoding="utf-8"))


def get_locale(entity_id: int, lang: str) -> dict[str, str] | None:
    data = _load(entity_id)
    if not data:
        return None
    loc = data.get("locales", {}).get(lang)
    if not loc:
        return None
    return dict(loc)
