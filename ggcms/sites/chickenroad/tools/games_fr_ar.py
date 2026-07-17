#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Load games fr/ar meta and translation pairs from JSON data files."""

from __future__ import annotations

import json
from pathlib import Path

DATA_DIR = Path(__file__).resolve().parent / "games_fr_ar_data"


def _load(entity_id: int) -> dict | None:
    path = DATA_DIR / f"games_{entity_id}.json"
    if not path.is_file():
        return None
    return json.loads(path.read_text(encoding="utf-8"))


def get_meta(entity_id: int, lang: str) -> dict[str, str] | None:
    data = _load(entity_id)
    if not data:
        return None
    meta = data.get("meta", {}).get(lang)
    if not meta:
        return None
    return dict(meta)


def get_pairs(entity_id: int, lang: str) -> list[tuple[str, str]] | None:
    data = _load(entity_id)
    if not data:
        return None
    raw = data.get("pairs", {}).get(lang)
    if not raw:
        return None
    return [(a, b) for a, b in raw]


def get_content_override(entity_id: int, lang: str) -> str | None:
    data = _load(entity_id)
    if not data:
        return None
    content = data.get("content", {}).get(lang)
    return content if content else None


def langs_for(entity_id: int) -> list[str]:
    data = _load(entity_id)
    if not data:
        return []
    langs = set()
    for key in (data.get("pairs") or {}):
        langs.add(key)
    for key in (data.get("content") or {}):
        langs.add(key)
    return sorted(langs)
