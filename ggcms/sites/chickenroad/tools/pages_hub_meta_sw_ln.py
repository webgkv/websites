#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Hub listing pages (empty body) — Swahili & Lingala meta only."""

from __future__ import annotations

# entity_id -> {sw: {name,title,description}, ln: {...}}
HUB_META: dict[int, dict[str, dict[str, str]]] = {
    2: {
        "sw": {
            "name": "Blog",
            "title": "Chicken Road Blog: Habari, Miongozo na Matukio",
            "description": (
                "Blog ya Chicken Road: masasisho ya mchezo, habari za watoa huduma, "
                "vidokezo vya mkakati na kila kitu kuhusu ulimwengu wa Chicken Road."
            ),
        },
        "ln": {
            "name": "Blog",
            "title": "Chicken Road Blog: Bansango, Mwongozo mpe Ba Sika",
            "description": (
                "Blog ya Chicken Road: ba sika ya lisano, bansango ya ba provider, "
                "ba likanisi ya strategy mpe makambo nionso na mokili ya Chicken Road."
            ),
        },
    },
    3: {
        "sw": {
            "name": "Kasino",
            "title": "Watoa Huduma wa Chicken Road Casino — Mahali pa Kucheza",
            "description": (
                "Linganisha watoa huduma wa michezo ya kasino ya Chicken Road na kasino. "
                "Pata tovuti bora za kucheza Chicken Road na bonasi halisi."
            ),
        },
        "ln": {
            "name": "Casinos",
            "title": "Ba Provider ya Casino Chicken Road — Esika ya Kobeta",
            "description": (
                "Linganisa ba provider mpe ba casinos ya Chicken Road. "
                "Luka ba sites ya malamu mpo na kobeta Chicken Road na ba bonus ya solo."
            ),
        },
    },
    7: {
        "sw": {
            "name": "Michezo",
            "title": "Michezo ya Chicken Road — Katalogi Kamili na Mapitio",
            "description": (
                "Vinjari katalogi kamili ya michezo ya Chicken Road: michezo ya crash, "
                "ainati na watoa huduma — yote yamepitia mahali pamoja."
            ),
        },
        "ln": {
            "name": "Ba Lisano",
            "title": "Ba Lisano Chicken Road — Lisi Mobimba mpe Ba Review",
            "description": (
                "Tala lisi mobimba ya ba lisano Chicken Road: ba titres crash, "
                "ba variantes mpe ba provider — nionso na esika moko."
            ),
        },
    },
    8: {
        "sw": {
            "name": "Miongozo",
            "title": "Miongozo ya Chicken Road: Mkakati, Sheria na Vidokezo",
            "description": (
                "Miongozo yote ya Chicken Road mahali pamoja: jinsi ya kucheza, mkakati, "
                "ishara, usalama na vidokezo vya bonasi."
            ),
        },
        "ln": {
            "name": "Ba Mwongozo",
            "title": "Ba Mwongozo Chicken Road: Strategy, Mibeko mpe Ba Likanisi",
            "description": (
                "Ba mwongozo nionso ya Chicken Road na esika moko: ndenge ya kobeta, strategy, "
                "ba elembo, libateli mpe ba likanisi ya bonus."
            ),
        },
    },
    9: {
        "sw": {
            "name": "Uchambuzi",
            "title": "Uchambuzi wa Chicken Road: Sheria, Ugumu na Usalama",
            "description": (
                "Miongozo ya kina ya uchambuzi wa Chicken Road: jinsi ya kucheza, "
                "viwango vya ugumu na usalama umeelezwa."
            ),
        },
        "ln": {
            "name": "Tala",
            "title": "Tala Chicken Road: Mibeko, Difficulty mpe Libateli",
            "description": (
                "Ba mwongozo ya tala ya Chicken Road: ndenge ya kobeta, ba niveau ya difficulty "
                "mpe libateli elimbolami na ndenge ya pépé."
            ),
        },
    },
    10: {
        "sw": {
            "name": "Jinsi ya Kushinda",
            "title": "Jinsi ya Kushinda kwenye Chicken Road: Miongozo na Vidokezo",
            "description": (
                "Miongozo ya jinsi ya kushinda kwenye Chicken Road: mkakati, makosa ya kawaida "
                "na vidokezo vya vitendo vinavyofanya kazi."
            ),
        },
        "ln": {
            "name": "Ndenge ya Kolonga",
            "title": "Ndenge ya Kolonga na Chicken Road: Mwongozo mpe Ba Likanisi",
            "description": (
                "Ba mwongozo mpo na kolonga na Chicken Road: strategy, mabunga ya minene "
                "mpe ba likanisi ya solo oyo esalaka solo na solo."
            ),
        },
    },
    11: {
        "sw": {
            "name": "Ishara",
            "title": "Ishara za Chicken Road: Miongozo ya Cash Out na Demo",
            "description": (
                "Miongozo ya ishara za Chicken Road: wakati wa Cash Out na mazoezi ya demo "
                "kuboresha maamuzi yako."
            ),
        },
        "ln": {
            "name": "Ba Elembo",
            "title": "Ba Elembo Chicken Road: Cash Out mpe Demo",
            "description": (
                "Ba mwongozo ya elembo Chicken Road: tango ya Cash Out mpe koyekola na demo "
                "mpo na kobongisa ba likambo ya kopona na yo."
            ),
        },
    },
    12: {
        "sw": {
            "name": "Bonasi",
            "title": "Miongozo ya Bonasi ya Chicken Road: Matoleo Yameelezwa",
            "description": (
                "Miongozo yote ya bonasi ya Chicken Road: jinsi matoleo yanavyofanya kazi, "
                "masharti ya kubeti na jinsi ya kuyadai."
            ),
        },
        "ln": {
            "name": "Bonus",
            "title": "Ba Mwongozo Bonus Chicken Road: Ba Offre Elimbolami",
            "description": (
                "Ba mwongozo nionso ya bonus Chicken Road: ndenge ba offre esalelaka, "
                "ba mibeko ya wagering mpe ndenge ya kozua yango."
            ),
        },
    },
    35: {
        "sw": {
            "name": "Waandishi",
            "title": "Waandishi",
            "description": "Kutana na waandishi na wataalamu walio nyuma ya miongozo na makala zetu.",
        },
        "ln": {
            "name": "Ba Mokomi",
            "title": "Ba Mokomi",
            "description": "Zala na ba mokomi mpe ba experts oyo bakomaki ba mwongozo mpe ba article na biso.",
        },
    },
}
