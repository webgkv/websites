# -*- coding: utf-8 -*-
"""Natural demo-page locale bodies split by language group."""

from __future__ import annotations

from demo_natural.europe import get_europe_locales
from demo_natural.slavic import get_slavic_locales
from demo_natural.asia import get_asia_locales


def get_all_full_locales() -> dict[str, dict]:
    out: dict[str, dict] = {}
    out.update(get_europe_locales())
    out.update(get_slavic_locales())
    out.update(get_asia_locales())
    return out
