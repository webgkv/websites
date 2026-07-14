#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply string-table translations to EN body dict (recursive)."""

from __future__ import annotations

import copy
from typing import Any

from chickenroad_download_v2_en import get_en_body


def _apply_table(text: str, table: dict[str, str]) -> str:
    out = text
    for src, dst in sorted(table.items(), key=lambda x: -len(x[0])):
        out = out.replace(src, dst)
    return out


def translate_body(table: dict[str, str]) -> dict:
    def walk(obj: Any) -> Any:
        if isinstance(obj, str):
            return _apply_table(obj, table)
        if isinstance(obj, list):
            return [walk(x) for x in obj]
        if isinstance(obj, tuple):
            return tuple(walk(x) for x in obj)
        return obj

    return walk(copy.deepcopy(get_en_body()))


def merge_body(base: dict, table: dict[str, str]) -> dict:
    b = copy.deepcopy(base)
    b.update(translate_body(table))
    return b
