# -*- coding: utf-8 -*-
"""Locale pack payloads (build_i18n_data format) for 14 non-French locales."""

from __future__ import annotations

from build_i18n_data import specs

LOCALE_PACKS: dict = {}

# Import remaining packs from submodules (one file per locale keeps editor responsive).
from i18n_packs import de, es, hi, pt, ru, ar, az, bn, it, nl, pl, vi, ua, ro  # noqa: E402

for _mod in (de, es, hi, pt, ru, ar, az, bn, it, nl, pl, vi, ua, ro):
    LOCALE_PACKS[_mod.LANG] = _mod.PACK
