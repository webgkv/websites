#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Load pages sw/ln data for Aviator from JSON files."""

from __future__ import annotations

import json
from pathlib import Path

DATA_DIR = Path(__file__).resolve().parent / "pages_sw_ln_data"


def _load(entity_id: int) -> dict | None:
    path = DATA_DIR / f"pages_{entity_id}.json"
    if not path.is_file():
        return None
    return json.loads(path.read_text(encoding="utf-8"))


def get_meta(entity_id: int, lang: str) -> dict[str, str] | None:
    data = _load(entity_id)
    if not data:
        return None
    meta = data.get("meta", {}).get(lang)
    return dict(meta) if meta else None


def get_pairs(entity_id: int, lang: str) -> list[tuple[str, str]] | None:
    data = _load(entity_id)
    if not data:
        return None
    raw = data.get("pairs", {}).get(lang)
    if not raw:
        return None
    return [(a, b) for a, b in raw]


def get_fr_ln_pairs(entity_id: int) -> list[tuple[str, str]] | None:
    data = _load(entity_id)
    if not data:
        return None
    raw = data.get("pairs", {}).get("fr_ln")
    if not raw:
        return None
    return [(a, b) for a, b in raw]


def ln_from_fr(entity_id: int) -> bool:
    data = _load(entity_id)
    return bool(data and data.get("ln_from_fr"))


def is_hub_only(entity_id: int) -> bool:
    data = _load(entity_id)
    return bool(data and data.get("hub_only"))
