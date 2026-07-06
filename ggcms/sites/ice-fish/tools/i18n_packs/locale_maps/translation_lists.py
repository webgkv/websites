# -*- coding: utf-8 -*-
"""Per-locale translation lists (139 strings each, aligned with fr_ordered.json)."""

from __future__ import annotations

from locale_maps.lists.ar import LIST as AR
from locale_maps.lists.az import LIST as AZ
from locale_maps.lists.bn import LIST as BN
from locale_maps.lists.es import LIST as ES
from locale_maps.lists.hi import LIST as HI
from locale_maps.lists.it import LIST as IT
from locale_maps.lists.nl import LIST as NL
from locale_maps.lists.pl import LIST as PL
from locale_maps.lists.pt import LIST as PT
from locale_maps.lists.ro import LIST as RO
from locale_maps.lists.ru import LIST as RU
from locale_maps.lists.ua import LIST as UA
from locale_maps.lists.vi import LIST as VI

LISTS: dict[str, list[str]] = {
    "es": ES,
    "hi": HI,
    "pt": PT,
    "ru": RU,
    "ar": AR,
    "az": AZ,
    "bn": BN,
    "it": IT,
    "nl": NL,
    "pl": PL,
    "vi": VI,
    "ua": UA,
    "ro": RO,
}
