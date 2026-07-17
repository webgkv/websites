#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Hub listing pages — French & Arabic meta (global/international wording)."""

from __future__ import annotations

HUB_META: dict[int, dict[str, dict[str, str]]] = {
    35: {
        "fr": {
            "name": "Auteurs",
            "title": "Auteurs",
            "description": (
                "Découvrez les rédacteurs et experts qui produisent nos guides "
                "et articles sur Chicken Road."
            ),
        },
        "ar": {
            "name": "الكتّاب",
            "title": "الكتّاب",
            "description": (
                "تعرّف على الكتّاب والخبراء الذين يعدّون أدلتنا ومقالاتنا "
                "حول Chicken Road."
            ),
        },
    },
}
