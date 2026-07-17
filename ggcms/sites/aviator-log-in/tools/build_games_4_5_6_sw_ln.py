#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial games sw/ln JSON for Aviator games #4, #5, #6."""

from __future__ import annotations

import json
import re
from pathlib import Path

EXPORT_DIR = Path("/home/lenovo/Downloads/02/aviator-games")
OUT_DIR = Path(__file__).resolve().parent / "games_sw_ln_data"


def extract_segments(html: str) -> list[str]:
    items: list[str] = []
    for m in re.finditer(r"<(h1|h2|h3|p|figure)[^>]*>(.*?)</\1>", html, re.I | re.S):
        tag = m.group(1).lower()
        inner = m.group(2)
        if tag == "figure":
            alt = re.search(r'alt="([^"]*)"', inner, re.I)
            if alt:
                items.append(alt.group(1))
            continue
        text = re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", inner)).strip()
        if text:
            items.append(text)
    return items


def load_export(entity_id: int) -> tuple[list[str], list[str], dict]:
    data = json.loads((EXPORT_DIR / f"seo-games-{entity_id}-full.json").read_text(encoding="utf-8"))
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    fr = next(x for x in data["locales"] if x["lang_id"] == 3)
    en_segs = extract_segments(en["content"])
    fr_segs = extract_segments(fr["content"])
    if len(en_segs) != len(fr_segs):
        raise ValueError(f"games#{entity_id}: EN/FR segment count mismatch")
    meta_src = {
        "en_name": en["name"],
        "fr_name": fr["name"],
        "en_title": en["title"],
        "en_desc": en["description"],
        "fr_title": fr["title"],
        "fr_desc": fr["description"],
    }
    return en_segs, fr_segs, meta_src


GAMES: dict[int, dict] = {
    4: {
        "meta": {
            "sw": {
                "name": "Navigator Premier Bet",
                "title": "Navigator Premier Bet: Mwongozo wa Mchezo wa Crash",
                "description": "Navigator Premier Bet crash: sheria, RTP 97%, jinsi ya kucheza, mikakati na mahali pa kucheza.",
            },
            "ln": {
                "name": "Navigator Premier Bet",
                "title": "Navigator Premier Bet: Guide ya Lisano ya Crash",
                "description": "Navigator Premier Bet crash: mibeko, RTP 97%, ndenge ya kobeta, ba strategy mpe esika ya kobeta.",
            },
        },
        "sw": [
            "Premier Bet Navigator",
            "Navigator Premier Bet",
            "Navigator PremierBet ni mchezo wa crash unaotumia ndege nyekundu inayopaa juu kwenye mandhari ya giza. Unapatikana tu kwa Premier Bet na Premier Vegas.",
            "Mwongozo wa Navigator PremierBet",
            "Mchezo unaiga mchezo wa Aviator kwa muundo wazi na dau kubwa. Unaweza kuanza haraka ukiwa mgeni au mzoefu. Hapa chini: vipengele, jinsi ya kucheza, mikakati na FAQ.",
            "Mchezo-play ya Navigator",
            "Jinsi ya kucheza",
            "Weka dau lako kabla ya raundi. Ndege inapaa juu na multiplier inaongezeka. Unashinda kwa multiplier ya sasa ukifanya Cash Out kabla ya crash; usipofanya hivyo, unapoteza dau. Unaweza kutoa pesa wakati wowote. Interface ina takwimu za moja kwa moja na dau za wachezaji wengine; Cash Out wa mkono na wa auto vinapatikana.",
            "Usajili na kuingia",
            "Jisajili kwenye Premier Bet (inapatikana katika nchi nyingi, k.m. Malawi, Congo, Angola, Ghana). Ingia kwa maelezo yako na uweke pesa kwenye akaunti ili ucheze kwa pesa halisi. Chaguo za amana hutegemea nchi yako (kadi, crypto, n.k.).",
            "Mikakati",
            "Mkakati wa kutoka: Weka multiplier lengwa na ushirikiane nayo. Uchambuzi wa mwenendo: Angalia raundi za zamani (kumbuka matokeo ni nasibu). Martingale: Ongeza dau baada ya kushindwa; hatari. Dau mbili: Weka dau mbili; toa moja ili kulipia dau, acha nyingine ikimbie kwa malipo ya juu zaidi.",
            "Demo na predictor",
            "Navigator inaweza kutokuwa na demo maalum kwenye Premier Bet; unaweza kufanya mazoezi kwenye demo ya Aviator mahali pengine kwani mechanics ni sawa. Hakuna predictor ya kuaminika kwa Navigator; mchezo unategemea RNG. Cheza kwa uwajibikaji.",
            "FAQ",
            "Ninaweza kucheza wapi? Tu kwenye Premier Bet.",
            "Je, kuna app? Ndiyo. Premier Bet ina app za Android na iOS.",
            "RTP? 97%.",
            "Kamari yenye uwajibikaji: Jukwaa huru. Cheza tu mahali ambapo ni halali.",
        ],
        "ln": [
            "Navigator Premier Bet",
            "Navigator Premier Bet",
            "Navigator Premier Bet ezali lisano ya crash na avion ya moto oyo ebimaka na fond ya moindo. Ezali kaka na Premier Bet mpe Premier Vegas.",
            "Tala ya Navigator Premier Bet",
            "Lisano elobaka ba mécanique ya Aviator na interface ya polele, ba round ya mbangu mpe ba mise ya likolo. Soki ozali koyekola format oyo to okobeta mbala na mbala, okoyeba noki ndenge ezali kosala.",
            "Round ya Navigator Premier Bet",
            "Ndenge ya kobeta",
            "Tia pari na yo liboso ya round. Avion ebimaka mpe multiplicateur ekomaka na tango ya solo. Soki ozui cash-out liboso ya crash, gain na yo ekokani na multiplicateur oyo ezali; soki te, obungisa pari. Lisano ezali na cash-out ya manual to auto, ba statistique ya direct mpe ba pari ya basusu.",
            "Inscription mpe connexion",
            "Salá compte Premier Bet na mboka oyo esalelamaka, kota mpe tia mbongo na solde mpo na kobeta na mbongo ya solo. Ba méthode ya dépôt etalela région na yo mpe ekoki kozala ba carte, ba portefeuille mobile to crypto.",
            "Ba strategy",
            "Sortie planifiée: poná multiplicateur cible mpe respecte yango. Observation ya ba série: tala ba manche ya liboso kozanga kobosana ete resultat ezali hasard. Martingale: kokende pari liboso ya perte ezali riski. Ba pari mibale: zua moko na mbangu mpo na kobotala pari mobimba mpe tika mosusu ekende mpo na gain ya likolo.",
            "Demo mpe prédicteur",
            "Navigator ezali ntango nyonso te na demo ya solo na Premier Bet. Okoki komeka lisano ya type Aviator esika mosusu mpo na koyeba base moko. Predictor ya confiance ezali te: resultat etalela système ya hasard.",
            "FAQ",
            "Wapi kobeta? Lisano ezali na Premier Bet, mpe selon marché, na Premier Vegas mpe.",
            "Application ezali? Ee. Premier Bet epesi ba application Android mpe iOS selon ba pays.",
            "RTP ezali boni? RTP oyo ebondisami ezali 97 %.",
            "Jeu responsable: Plateforme indépendante. Beta kaka esika ezali legal.",
        ],
    },
    5: {
        "meta": {
            "sw": {
                "name": "Rocketman",
                "title": "Rocketman Crash: Cheza kwa Pesa Halisi | Aviator Log In",
                "description": "Mchezo wa crash Rocketman: jinsi ya kucheza, sheria, vipengele na mahali pa kucheza mtandaoni.",
            },
            "ln": {
                "name": "Rocketman",
                "title": "Rocketman Crash: Kobeta na Mbongo ya Solo",
                "description": "Lisano ya crash Rocketman: ndenge ya kobeta, mibeko, ba fonction mpe esika ya kobeta na internet.",
            },
        },
        "sw": [
            "Rocketman",
            "Mchezo wa crash Rocketman",
            "Rocketman ni mchezo wa crash ambapo roketi inapanda na multiplier inaongezeka. Unaweka dau na kuamua lini utafanya Cash Out. Ukifanya Cash Out kabla roketi iharibike, unashinda dau lako kuzidishwa na thamani ya sasa; ikiharibika kwanza, unapoteza dau.",
            "Jinsi ya kucheza",
            "Weka dau lako kabla ya raundi. Roketi inazinduliwa na multiplier (ikianza 1.00x) inaongezeka. Fanya Cash Out kwa mkono au weka multiplier ya auto Cash Out. Yeyote anayefanya Cash Out kwa wakati anapata dau × multiplier; wasiofanya hivyo wanapoteza dau. Hatua ya crash ni nasibu (RNG au provably fair).",
            "Vipengele vya Rocketman",
            "Vipengele na mahali pa kucheza",
            "Vipengele vya kawaida ni paneli ya dau, salio, historia ya dau na historia ya multiplier. Kasino nyingi mtandaoni hutoa Rocketman au michezo ya rocket crash inayofanana. Chagua tovuti yenye leseni, malipo salama na masharti wazi. Matoleo ya demo mara nyingi yanapatikana.",
            "Mikakati",
            "Weka bajeti na ushirikiane nayo. Tumia multiplier za chini kwa ushindi mdogo wa mara kwa mara au za juu kwa malipo makubwa na hatari zaidi. Auto Cash Out inaweza kuondoa hisia kwenye uamuzi. Matokeo ya zamani hayatabiri crash ijayo.",
            "FAQ",
            "Rocketman ni nini? Mchezo wa crash ambapo unadau kwenye roketi inayopanda na kufanya Cash Out kabla iharibike.",
            "Je, ni haki? Matoleo ya kuaminika hutumia RNG au teknolojia ya provably fair.",
            "Kamari yenye uwajibikaji: Jukwaa huru. Cheza tu mahali ambapo ni halali.",
        ],
        "ln": [
            "Rocketman",
            "Lisano ya crash Rocketman",
            "Rocketman ezali lisano ya crash oyo fusée ekomaka pendant multiplicateur ezali komata. Otia pari mpe oponaka tango ya cash-out liboso ya crash.",
            "Ndenge ya kobeta",
            "Poná pari na yo liboso ya round. Fusée ebimaka mpe multiplicateur ebandaka na 1,00x mpe ekomaka na tango ya solo. Okoki cash-out na manual to cash-out auto. Soki ozui na tango, gain = pari × multiplicateur; soki te, pari ebungami. Point ya crash ezali hasard via RNG to système provably fair.",
            "Ba fonction ya Rocketman",
            "Ba fonction mpe esika ya kobeta",
            "Mingi tango bazali panel ya pari, solde, historique ya ba pari mpe historique ya multiplicateur. Ba casino ya internet mingi epesi Rocketman to ba crash game ya fusée. Priorise site ya licence, ba paiement ya sécurité mpe condition ya polele. Version demo ezali mbala na mbala.",
            "Ba strategy",
            "Poná budget mpe respecte yango. Ba multiplicateur ya moke ezali malamu mpo na ba gain ya moto kasi ya mbala na mbala; ba multiplicateur ya likolo epesi ba paiement ya monene na riski mingi. Cash-out auto esalisaka kokitisa ba décision ya emotion. Ba resultat ya liboso e prévoir te crash elandi.",
            "FAQ",
            "Rocketman ezali nini? Lisano ya crash wapi obeti na fusée oyo ekomaka mpe ozui cash-out liboso ya chute.",
            "Lisano ezali fair? Ba version ya confiance basalaka RNG to technologie provably fair.",
            "Jeu responsable: Plateforme indépendante. Beta kaka esika ezali legal.",
        ],
    },
    6: {
        "meta": {
            "sw": {
                "name": "Rocket Gambling Game",
                "title": "Rocket Gambling Game: Mwongozo wa Rocket Crash",
                "description": "Mchezo wa rocket crash: sheria, jinsi ya kucheza, demo, mikakati na kasino bora za rocket crash.",
            },
            "ln": {
                "name": "Lisano ya crash Rocket",
                "title": "Lisano ya Crash Rocket: Kobeta na Mbongo ya Solo",
                "description": "Lisano ya crash Rocket: mibeko, demo, ba strategy mpe ndenge ya kobeta na mbongo ya solo.",
            },
        },
        "sw": [
            "Rocket Gambling Game",
            "Mchezo wa rocket crash",
            "Rocket Gambling Game (Rocket Crash) ni mojawapo ya vipendwa kwa wachezaji wanaotaka mchezo wa haraka na wenye mvutano. Roketi ya kidigitali inapanda kwenye skrini na multiplier inaongezeka. Unadau kiasi gani itapanda na kufanya Cash Out kabla iharibike. Ukifanya Cash Out kwa wakati, unapata dau lako kuzidishwa na thamani ya sasa; roketi ikiharibika kwanza, unapoteza dau.",
            "Rocket crash ni nini?",
            "Unaweka dau kabla ya raundi. Roketi inazinduliwa na multiplier inaanza 1.00x na kuongezeka. Unaweza kufanya Cash Out kwa mkono au kuweka auto Cash Out. Hatua ya crash ni nasibu (RNG au provably fair). Multiplier za juu zinawezekana lakini roketi inaweza kuharibika wakati wowote.",
            "Sheria na interface",
            "Weka dau kwenye dirisha la dau. Roketi inapanda na multiplier inaongezeka. Fanya Cash Out wakati wowote ili kufunga multiplier ya sasa. Crash: Usipofanya Cash Out, unapoteza dau. Malipo: Dau × multiplier wakati wa Cash Out. Skrini inaonyesha roketi, multiplier, paneli ya dau, salio na mara nyingi historia ya multiplier za zamani.",
            "Kuanza: app na usajili",
            "Kasino nyingi hutoa rocket crash kwenye wavuti na simu. Pakua app ya kasino (App Store au Google Play / APK kutoka tovuti), jisajili kwa maelezo yako, thibitisha ikihitajika, kisha weka amana na ucheze. Tumia kasino zenye leseni tu.",
            "Je, rocket crash ni halali?",
            "Wasanidi programu walioidhinishwa hutumia RNG na Provably Fair ili matokeo yawe nasibu na yanaweza kuthibitishwa. Wachezaji bado wanapaswa kuchagua kasino zenye leseni na kuangalia sheria za ndani. RNG huangaliwa; Provably Fair hukuruhusu kuthibitisha kila raundi kwa seeds na hashes.",
            "Demo na mikakati",
            "Tovuti nyingi hutoa demo bila malipo kwa mikopo ya kawaida ili ujifunze mchezo bila pesa halisi. Mikakati: weka bajeti, tumia Cash Out ya kuwa macho kwa ushindi mdogo wa mara kwa mara au multiplier za juu kwa hatari kubwa. Auto Cash Out inaweza kusaidia. Hakuna mkakati unaoweza kuhakikisha ushindi; crash ni nasibu.",
            "Michezo ya rocket crash",
            "FAQ",
            "Rocket Gambling Game ni nini? Mchezo ambapo unadau kwenye roketi inayopanda na kufanya Cash Out kabla iharibike.",
            "Inafanyaje kazi? Weka dau kabla ya kuzinduliwa; multiplier inaongezeka roketi inapopanda; fanya Cash Out ili kufunga ushindi au upoteze dau ikiharibika kwanza.",
            "Je, ni haki? Michezo ya kuaminika hutumia RNG na teknolojia ya Provably Fair.",
            "Naweza kuhakikisha ushindi? Hapana. Matokeo ni nasibu.",
            "Kamari yenye uwajibikaji: Jukwaa huru. Cheza tu mahali ambapo ni halali. Viungo vinaweza kuongoza kwenye tovuti za watu wengine.",
        ],
        "ln": [
            "Lisano ya crash Rocket",
            "Lisano ya crash Rocket",
            "Lisano ya crash Rocket ezali na ba format ya casino ya internet oyo ezali ya mbangu mingi. Fusée ya virtual ekomaka na écran pendant multiplicateur ekomaka. Obeti mpe ozui cash-out liboso ya crash.",
            "Rocket crash ezali nini?",
            "Otia pari liboso ya round. Fusée ebandaka na 1,00x mpe multiplicateur ekomaka na moto. Okoki cash-out na manual to cash-out auto. Point ya crash ezali hasard na RNG to système provably fair. Ba multiplicateur ya monene ezali, kasi fusée ekoki kosilama na tango nionso.",
            "Mibeko mpe interface",
            "Tia pari na yo na panel oyo ezali. Fusée ebimaka mpe multiplicateur ekomaka. Zua cash-out na tango nionso mpo na kobatela coefficient ya sikoyo. Crash: soki ozui cash-out te, pari ebungami. Paiement: pari × multiplicateur na moment ya cash-out. Écran emonisaka fusée, coefficient, panel ya pari, solde mpe mbala na mbala historique ya multiplicateur.",
            "Kobanda: appli mpe inscription",
            "Ba casino mingi epesi crash ya fusée na web mpe mobile. Télécharge application ya casino to salela navigateur, salá compte, vérifie soki esengeli, puis dépose mpe beta. Salela kaka casino ya licence.",
            "Rocket crash ezali légitime?",
            "Ba développeur certifiés basalaka RNG mpe provably fair mpo na kosala ba resultat hasard mpe oyo ekoki k vérifier. Riski ezali kaka: tala licence ya casino, condition ya paiement mpe legalité ya esika.",
            "Demo mpe ba strategy",
            "Ba site mingi epesi demo ya ofele na crédit virtuel mpo na koyekola kozanga mbongo ya solo. Strategy: poná budget, salela cash-out ya prudence mpo na ba gain ya mbala na mbala, to ba multiplicateur ya likolo na riski mingi. Cash-out auto ekoki kosalisa. Method ezali te oyo e garantir gain; crash ezali hasard.",
            "Ba lisano ya crash Rocket",
            "FAQ",
            "Lisano ya crash Rocket ezali nini? Lisano wapi obeti na fusée oyo ekomaka mpe ozui cash-out liboso ya crash.",
            "Ezali kosala ndenge nini? Obeti liboso ya lancement, multiplicateur ekomaka, ozui cash-out to obungisa soki fusée e crash liboso.",
            "Lisano ezali fair? Ba version ya solo basalaka RNG mpe technologie provably fair.",
            "Nakoki k garantir ba gain? Te. Ba resultat ezali hasard.",
            "Jeu responsable: Plateforme indépendante. Beta kaka esika ezali legal. Ba lien ekoki komema na ba site ya bato mosusu.",
        ],
    },
}


def build(entity_id: int) -> dict:
    en_segs, fr_segs, _ = load_export(entity_id)
    spec = GAMES[entity_id]
    if len(spec["sw"]) != len(en_segs):
        raise ValueError(f"games#{entity_id}: SW count {len(spec['sw'])} != EN {len(en_segs)}")
    if len(spec["ln"]) != len(fr_segs):
        raise ValueError(f"games#{entity_id}: LN count {len(spec['ln'])} != FR {len(fr_segs)}")
    return {
        "ln_from_fr": True,
        "meta": spec["meta"],
        "pairs": {
            "sw": [[en, sw] for en, sw in zip(en_segs, spec["sw"])],
            "fr_ln": [[fr, ln] for fr, ln in zip(fr_segs, spec["ln"])],
        },
    }


def main() -> int:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    for entity_id in (4, 5, 6):
        payload = build(entity_id)
        out = OUT_DIR / f"games_{entity_id}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"wrote {out} ({len(payload['pairs']['sw'])} sw, {len(payload['pairs']['fr_ln'])} fr_ln)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
