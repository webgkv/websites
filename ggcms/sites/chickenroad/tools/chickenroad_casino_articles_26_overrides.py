# -*- coding: utf-8 -*-
"""Aggregator for casino_articles#26 locale overrides."""

from chickenroad_casino_articles_26_overrides_fr_de_es_ru import EXPORT as FR_DE_ES_RU
from chickenroad_casino_articles_26_overrides_rest_ar_az import EXPORT as AR_AZ
from chickenroad_casino_articles_26_overrides_rest_pl_ro_ua import EXPORT as PL_RO_UA
from chickenroad_casino_articles_26_overrides_rest_pt_it_nl import EXPORT as PT_IT_NL
from chickenroad_casino_articles_26_overrides_rest_vi_hi_bn import EXPORT as VI_HI_BN

LOCALE_OVERRIDES: dict[str, dict] = {}
LOCALE_OVERRIDES.update(FR_DE_ES_RU)
LOCALE_OVERRIDES.update(PT_IT_NL)
LOCALE_OVERRIDES.update(PL_RO_UA)
LOCALE_OVERRIDES.update(VI_HI_BN)
LOCALE_OVERRIDES.update(AR_AZ)
