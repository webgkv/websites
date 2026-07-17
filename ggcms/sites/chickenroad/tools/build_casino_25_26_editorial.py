#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial casino_25.json and casino_26.json sw/ln translation data."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "casino_sw_ln_data"
DL = Path("/home/lenovo/Downloads/02/chickenroad-casinos")

sys.path.insert(0, str(TOOLS))
from ln_quality_replacements import polish_ln  # noqa: E402


def load_segs(name: str) -> list[str]:
    return json.loads((DL / name).read_text(encoding="utf-8"))


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    assert len(keys) == len(vals), f"{len(keys)} vs {len(vals)}"
    return [[k, v] for k, v in zip(keys, vals)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


# --- casino #25 Swahili (EN -> SW) ---
SW25 = [
    "Jack-Pot — Chicken Road",
    "Kuhusu Jack-Pot",
    "Mahali pa Kupata Chicken Road kwenye Jack-Pot",
    "Jinsi ya Kucheza Chicken Road kwenye Jack-Pot",
    "Demo na Kucheza kwa Pesa Halisi kwenye Jack-Pot",
    "Chicken Road kwenye Simu kupitia Jack-Pot",
    "Mawazo ya Mwisho",
    "FAQ",
    "Lobby ya kasino ya mtandaoni ya Jack-Pot na muhtasari wa Chicken Road",
    "Jack-Pot ni jukwaa la mtandaoni ambapo michezo ya kasino na dau za michezo zinapatikana kutoka akaunti moja. Tovuti ina sehemu tofauti zilizo wazi kama kasino, live, michezo, promos, malipo, mashindano, na cashback. Kwa watumiaji ni rahisi kuchagua wapi kwenda. Jukwaa linahisi rahisi vya kutosha. Kasino imegawanywa katika kategoria tofauti. Unaweza pia kutumia sehemu kuu ikiwa tayari unajua unataka kucheza nini.",
    "Chicken Road inafaa Jack-Pot vizuri kwa sababu ni aina haswa ya mchezo unaohitaji ufikiaji wa haraka. Hakuna mtu anayetaka kuchimba lobby kubwa ili kupata mchezo wa haraka wa arcade. Unaufunua, weka dau, uchague kiwango cha hatari, na raundi inaanza. Kwa Chicken Road, faida kuu ya Jack-Pot ni njia safi hadi kwenye mchezo: lobby ya haraka, upau wa utafutaji, ufikiaji wa simu, na mpangilio usiozuia. Hiyo inatosha kwa mchezo ulioundwa kuzunguka raundi fupi na maamuzi ya haraka.",
    "Jack-Pot pia ina bonus na promos, hivyo inafaa kuangalia sehemu ya promosheni kabla ya kucheza. Usiwezeshe chochote bila kusoma. Bonus zinaweza kuwa na muda au sheria.",
    "Chicken Road ni rahisi kupata kwenye Jack-Pot ikiwa unajua wapi kutafuta. Fungua sehemu ya kasino na nenda Slots au Crash Games. Michezo mingi ya haraka ya arcade kwa kawaida huwekwa karibu na kategoria hizi.",
    "Njia ya haraka zaidi ni utafutaji. Andika Chicken Road kwenye upau wa utafutaji, subiri bango la mchezo lionekane, na ulifungue. Mchezo unapaswa kuzinduliwa katika dirisha tofauti.",
    "Unaweza pia kutafuta kwa mtoa huduma. Andika InOut na Jack-Pot inapaswa kuonyesha michezo kutoka studio hii. Hii inasaidia ikiwa unataka kupata si tu Chicken Road, bali majina mengine ya InOut pia.",
    "Wakati mwingine mchezo pia unaweza kuonekana katika Crash Games, ambapo majukwaa mara nyingi huweka michezo ya instant, crash games, na majina ambayo hayafai kategoria za slots za kawaida.",
    "Njia fupi ni rahisi:",
    "Casino → Crash Games / Slots → Chicken Road → Play Now",
    "Ikiwa mchezo hauonekani kwa jina, jaribu kutafuta InOut badala yake.",
    "Chicken Road kwenye Jack-Pot ni rahisi kuanza. Ikiwa wewe ni mpya, anza na Easy. Inatoa nafasi zaidi kuelewa mchezo. Medium tayari inahisi kali zaidi. Hard na Hardcore ni bora kwa wachezaji wanaojua haraka salio linaweza kutoweka katika aina hii ya mchezo. Kabla ya kubonyeza Play, angalia dau tena. Hasa kwenye simu. Mguso mmoja wa haraka unaweza kuanzisha raundi na kiasi kisicho sahihi.",
    "Kitufe muhimu zaidi ni Cash Out. Bonyeza wakati multiplier ya sasa inatosha na unataka kusimama. Ukisubiri sana na raundi inakwenda vibaya, dau linapotea.",
    "Ikiwa hii ni Chicken Road asili kutoka InOut Games, sheria za msingi hubaki sawa kwenye Jack-Pot kama kwenye majukwaa mengine: songa mbele, ongeza multiplier, fanya Cash Out kabla ya kupoteza raundi.",
    "Demo ya Chicken Road inapatikana kwenye Jack-Pot, na hapo ndipo ningeanza. Si kwa sababu demo inakufundisha jinsi ya kushinda. Haifundishi. Lakini inakuruhusu kuona mchezo bila shinikizo.",
    "Katika hali ya demo, unacheza na salio la kawaida. Unaweza kubadilisha ugumu, kujaribu ukubwa tofauti wa dau, kusogeza kuku kwenye barabara, na kujaribu Cash Out katika nyakati tofauti. Ikiwa raundi inakwenda vibaya, hakuna kinachotokea kwa pesa yako halisi. Hali ya pesa halisi inahisi tofauti. Mchezo unaweza kuonekana sawa, vitufe ni vile vile, na multiplier inakua kwa njia ile ile. Lakini salio lako mwenyewe likihusika, kila hatua inahisi nzito zaidi.",
    "Katika hali ya demo, watu kwa kawaida hucheza kwa uhuru zaidi. Salio ni la kawaida, hivyo hakuna maumivu halisi katika kujaribu modes ngumu zaidi, kwenda hatua chache zaidi, au kubonyeza tena tu kuona kinachotokea.",
    "Kwa pesa halisi, hatua ile ile inahisi tofauti. Kitufe kile kile, multiplier ile ile, barabara ile ile — lakini sasa dau ni lako. Wachezaji wengine wanafanya Cash Out mapema sana kwa sababu hawataki kupoteza. Wengine huenda mbali sana kwa sababu wanataka kurejesha raundi iliyopita. Hapo ndipo shinikizo linaanza. Hivyo tumia demo kwenye Jack-Pot kwanza. Jifunze mchezo huko. Kucheza kwa pesa halisi kuna maana tu unapoelewa mechanics na kukubali hatari.",
    "Chicken Road inahisi vizuri kwenye simu. Fungua Jack-Pot, pata mchezo, weka dau, na unaweza kuanzisha raundi bila kukabiliana na interface nzito. Kwenye simu, mpangilio unakuwa compact zaidi. Sehemu ya dau, Play, na Cash Out bado ni rahisi kufikia, jambo muhimu katika mchezo huu. Hutaki kutafuta kitufe cha kutoka wakati multiplier tayari inasonga.",
    "Raundi ni fupi, hivyo kucheza kwa simu inafaa mchezo vizuri. Kikao cha kawaida cha simu kinachukua miguso michache tu. Fungua raundi, songa kuku, angalia multiplier, na uondoke wakati hatua inayofuata inaanza kuhisi hatari sana.",
    "Kasi ya mchezo kwa kiasi kikubwa inategemea kifaa chako na muunganisho. Simu mpya kwenye intaneti thabiti itahisi laini zaidi. Kivinjari cha zamani au ishara dhaifu inaweza kufanya skrini kuitikia polepole. Na kwa sababu skrini ni ndogo, angalia dau kabla ya Play — mguso mmoja wa makosa unatosha kuanzisha na kiasi kisicho sahihi. Unaweza kucheza kupitia kivinjari. Epuka faili za APK za kubahatisha kutoka tovuti zisizojulikana, hasa chochote kinachoahidi predictors, hacks, au toleo \"maalum\" la Chicken Road.",
    "Chicken Road inafaa Jack-Pot vizuri kwa sababu mchezo hauhitaji usanidi mgumu. Fungua sehemu ya kasino, pata kichwa, chagua mode, weka dau, na raundi inaanza. Kwa aina hii ya mchezo wa haraka, hiyo ndiyo unachotaka.",
    "Kivutio kuu ni rahisi: raundi fupi, vitufe wazi, multiplier inayokua, na uchaguzi wa kudumu — chukua matokeo ya sasa au hatari hatua moja zaidi. Ni rahisi kuelewa, lakini mchezo bado unaweza kukuvuta haraka.",
    "Jack-Pot inampa Chicken Road mahali safi pa kucheza, hasa kutoka simu. Utafutaji unafanya kazi, lobby si nzito sana, na demo mode ni muhimu ikiwa unataka kujaribu mechanics kwanza.",
    "Bado, Chicken Road si chombo cha kupata pesa. Ni mchezo wa kasino wenye hatari halisi. Tumia demo kabla ya pesa halisi, weka mipaka, usifuatilie hasara, na tazama kila raundi kama burudani, si mapato.",
    "Jack-Pot ni nini?",
    "Jack-Pot ni jukwaa la kasino la mtandaoni na sportsbook. Unaweza kucheza slots, live casino, michezo ya instant, crash games, na dau za michezo.",
    "Je, Jack-Pot ni kwa michezo ya kasino tu?",
    "Hapana. Kasino ni sehemu moja tu ya jukwaa.",
    "Usajili unachukua muda gani?",
    "Usajili unachukua chini ya dakika moja. Baada ya hapo, tovuti inaweza kuonyesha uchaguzi wa bonus au kufungua dirisha la amana.",
    "Je, Jack-Pot inatoa bonus?",
    "Ndiyo, lakini nenda kwenye tovuti rasmi kuona bonus gani zinapatikana kwa wachezaji sasa.",
    "Je, naweza kupata Chicken Road kwenye Jack-Pot?",
    "Ndiyo, ikiwa Chicken Road inapatikana kwenye lobby ya michezo ya Jack-Pot. Tafuta kwa jina la mchezo kwanza. Ikiwa hakuna kinachoonekana, jaribu jina la mtoa huduma: InOut.",
    "Je, Jack-Pot inafanya kazi kwenye simu?",
    "Ndiyo, Jack-Pot inafunguka kwenye simu kupitia kivinjari. Lobby ya kasino, sportsbook, cashier, na eneo la akaunti vinaweza kutumika kutoka simu. Kasi ya mchezo inategemea kifaa chako na muunganisho. Ishara dhaifu au kivinjari cha zamani inaweza kufanya baadhi ya michezo kupakia polepole.",
    "Je, Jack-Pot ni salama kutumia?",
    "Ndiyo, lakini hakikisha uko kwenye kikoa halisi cha Jack-Pot. Nakala bandia za kasino zinaweza kuonekana karibu sana na tovuti ya asili.",
    "Je, Jack-Pot ina leseni gani?",
    "Kutoka taarifa tulizokagua hapo awali, Jack-Pot inafanya kazi na leseni ya offshore ya Anjouan. Ni leseni halali, mara nyingi hutumika na chapa mpya za kasino. Bado, si kiwango kile kile cha ulinzi wa mchezaji kama wasimamizi waliokali zaidi. Hivyo sifa, historia ya malipo, na ubora wa msaada ni muhimu sana hapa.",
    "Je, Jack-Pot itaomba hati?",
    "Uwezekano mkubwa kabla ya uondoaji, ndiyo. Hakikisha unapakia hati tu kupitia tovuti rasmi ya Jack-Pot.",
    "Je, uondoaji ni wa papo hapo?",
    "Si kila wakati. Uondoaji mdogo unaweza kuwa wa haraka zaidi. Malipo makubwa yanaweza kuchukua muda mrefu kwa sababu ya uthibitishaji, mipaka ya mtoa huduma wa malipo, au ukaguzi wa mikono.",
    "Je, kucheza kwenye Jack-Pot kuna hatari?",
    "Ndiyo. Slots, crash games, live casino, na dau za michezo zote zina hatari. Unaweza kupoteza pesa. Tumia mipaka, usifuatilie hasara, na usitazame Jack-Pot kama njia ya kupata. Ni burudani ya kamari, si mapato.",
]

# --- casino #25 Lingala (FR -> LN) ---
LN25 = [
    "Jack-Pot — Chicken Road",
    "Et Jack-Pot",
    "Esika ya kozua Chicken Road na Jack-Pot",
    "Ndeni nini kobeta Chicken Road na Jack-Pot",
    "Demo mpe mbongo ya solo na Jack-Pot",
    "Chicken Road na mobile via Jack-Pot",
    "Na mokuse",
    "FAQ",
    "Lobby ya casino online Jack-Pot mpe aperçu ya Chicken Road",
    "Jack-Pot ezali plateforme online oyo ba jeux ya casino mpe paris sportifs ezali na compte moko. Site ezali na ba section ya clair : casino, live, sport, promos, paiements, tournois mpe cashback. Mpo na mosaleli, ezali pépé koyeba esika ya kokende. Plateforme ezali simple. Casino e divisé na ba catégories. Okoki mpe kosalela ba section ya liboso soki oyebi déjà oyo olingi kobeta.",
    "Chicken Road e correspondre malamu na Jack-Pot, mpo ezali type ya lisano oyo esengeli accès ya nokinoki. Moto moko te alingi koluka lobby monene mpo na kozua lisano ya arcade ya pépé. Ofungola yango, obotá mise, opona niveau ya riski mpe round ebandi. Mpo na Chicken Road, avantage ya liboso ya Jack-Pot ezali nzela ya directe : lobby ya nokinoki, barre ya recherche, mobile mpe layout oyo e gêner te. Yango ekoki mpo na lisano ya ba round ya mokuse mpe ba décision ya nokinoki.",
    "Jack-Pot ezali mpe na bonus mpe promos. Liboso ya kobeta, tala section promotion. Ko-activer eloko te na mpamba. Bonus ekoki kozala limité na temps to na mibeko.",
    "Chicken Road ezali pépé ya kozua na Jack-Pot soki oyebi esika ya koluka. Fungola section casino mpe kende na Slots to Crash Games. Ba jeux arcade ya pépé e mettre mingi na ba catégories oyo.",
    "Nzelá ya nokinoki ezali recherche. Tia Chicken Road na barre ya recherche, zela bannière ya lisano ebima mpe ofungola yango. Lisano esengeli kozala na fenêtre ya solo.",
    "Okoki mpe koluka na fournisseur. Tia InOut mpe Jack-Pot esengeli komonisa ba jeux ya studio oyo. Yango esalisaka soki olingi kozua Chicken Road mpe ba titre mosusu ya InOut.",
    "Parfois lisano ekoki kozala mpe na Crash Games, esika ba plateformes e mettre mingi ba jeux instantanés, crash games mpe ba titre oyo e rentrer te na ba catégories ya slots classiques.",
    "Nzelá ya mokuse ezali pépé :",
    "Casino → Crash Games / Slots → Chicken Road → Play Now",
    "Soki lisano e bimaka te na nkombo, meka koluka InOut.",
    "Chicken Road na Jack-Pot ezali pépé ya kobanda. Soki ozali débutant, bandá na Easy. Ezali na marge mingi mpo na koyeba lisano. Medium ezali déjà plus vif. Hard mpe Hardcore ezali malamu mpo na basali oyo bazali koyeba vitesse oyo solde ekoki kobomama na type oyo ya lisano. Liboso ya Play, talá mise lisusu. Mingi na téléphone. Tap moko ya nokinoki ekoki kobandisa round na montant ya mabe.",
    "Bouton ya liboso ezali Cash Out. Betá soki multiplier ya lelo elongi yo mpe olingi kotika. Soki ozela mingi mpe round e changer mal, mise ebimaka.",
    "Soki ezali Chicken Road original ya InOut Games, mibeko ya base ezali moko na Jack-Pot lokola na ba plateformes mosusu : kende liboso, bakisa multiplier, Cash Out liboso ya koboya round.",
    "Demo Chicken Road ezali disponible na Jack-Pot, mpe wana nakobanda. Te mpo na ete demo e enseigner gain — e enseigner te. Kasi epesi yo kotala lisano sans pression.",
    "Na demo obetaka na solde virtuel. Okoki kobongola difficulty, komeka ba mise different, kokende na poule na nzela mpe komeka Cash Out na ba moment different. Soki round e changer mal, mbongo ya solo e toucher te. Mode ya mbongo ya solo e changer ambiance. Lisano ekoki kozala ndenge moko, ba bouton moko mpe multiplier ekobaka ndenge moko. Kasi soki solde na yo moko ezali na jeu, step nionso e pèse mingi.",
    "Na demo, bato basala mingi librement. Solde ezali virtuel, donc komeka mode ya makasi to kokende moke liboso e salaka malamu te.",
    "Na mbongo ya solo, gesture moko e changer nyonso. Bouton moko, multiplier moko, nzela moko — kasi mise ezali ya yo. Basusu Cash Out nokinoki mingi mpo na koboya perte. Basusu bakende liboso mingi mpo na kozua round oyo eleki. Yango wana pression ebandi. Salela demo na Jack-Pot liboso. Yekola lisano wana. Mbongo ya solo ezali na sensi kaka soki omeya mécanique mpe ondimi riski.",
    "Chicken Road esalaka malamu na téléphone. Fungola Jack-Pot, zua lisano, botá mise mpe obanda round sans interface ya lourde. Na mobile, layout ezali compact. Champ ya mise, Play mpe Cash Out ezali pene pene, oyo ezali important na lisano oyo. Olingi te koluka bouton ya sortie soki multiplier e salaka déjà.",
    "Ba round ezali ya mokuse, donc mobile e correspondre malamu. Session ya solo na téléphone esengeli ba tap moke kaka. Fungola round, advance poule, tala multiplier mpe bima soki step oyo ekolanda e kóma trop risqué.",
    "Vitesse e dépendre mingi na appareil mpe connexion. Téléphone ya sika na internet stable ekozala plus fluide. Navigateur ya kala to signal ya mabe ekoki kolatisa écran. Mpe mpo na écran ya moke, talá mise liboso ya Play — tap moko ya mpamba ekoki kobandisa na montant ya mabe. Beta na navigateur. Boya ba APK ya hasard na ba site oyo oyeba te, mingi ba oyo e promettre predictors, hacks to version « spéciale » ya Chicken Road.",
    "Chicken Road e correspondre malamu na Jack-Pot mpo na ete lisano esengeli setup mpimba te. Fungola casino, zua titre, pona mode, botá mise mpe round ebandi. Mpo na type oyo ya lisano ya pépé, yango nde oyo olingi.",
    "Attraction ya liboso ezali pépé : ba round ya mokuse, ba bouton clairs, multiplier oyo ekobaka mpe choix ya libela — zua resultat ya lelo to riska step moko mosusu. Pépé koyeba, kasi lisano ekoki kozanga yo nokinoki.",
    "Jack-Pot epesi Chicken Road esika ya propre mpo na kobeta, mingi na mobile. Recherche esalaka, lobby ezali te trop lourde mpe demo ezali useful mpo na komeka mécanique liboso.",
    "Kasi Chicken Road ezali te outil mpo na kozua mbongo. Ezali lisano ya casino na riski ya solo. Salela demo liboso ya mbongo ya solo, botia ba limite, kobunda te perte mpe tala round nionso lokola divertissement, te revenu.",
    "Jack-Pot ezali nini ?",
    "Jack-Pot ezali plateforme ya casino online mpe paris sportifs. Okoki kobeta slots, live casino, ba jeux instantanés, crash games mpe paris sportifs.",
    "Jack-Pot ezali mpo na casino kaka ?",
    "Te. Casino ezali part moko kaka ya plateforme.",
    "Inscription ezali ya nokinoki ?",
    "Inscription esengeli moins ya minute moko. Na nsima, site ekoki komonisa choix ya bonus to kofungola fenêtre ya dépôt.",
    "Jack-Pot epesi bonus ?",
    "Ee, kasi kende na site officiel mpo na kotala bonus oyo ezali disponible sikoyo.",
    "Nakoki kozua Chicken Road na Jack-Pot ?",
    "Ee, soki Chicken Road ezali na lobby ya Jack-Pot. Luka liboso na nkombo ya lisano. Soki eloko e bimaka te, meka fournisseur : InOut.",
    "Jack-Pot esalaka na mobile ?",
    "Ee, Jack-Pot efungolaka na mobile via navigateur. Lobby casino, bookmaker, caisse mpe compte esalelaka na téléphone. Vitesse e dépendre na appareil mpe connexion. Signal ya mabe to navigateur ya kala ekoki kolatisa ba jeux.",
    "Jack-Pot ezali safe ?",
    "Ee, kasi talá ete ozali na domaine ya solo ya Jack-Pot. Ba copies frauduleuses ekoki kozala pene pene na site original.",
    "Licence nini Jack-Pot ezali na yango ?",
    "Na ba info oyo to talaki liboso, Jack-Pot esalaka na licence offshore ya Anjouan. Ezali licence ya solo, mingi mpo na ba marque ya casino ya sika. Kasi ezali te niveau moko ya protection lokola ba régulateurs ya makasi. Reputation, historique ya paiement mpe qualité ya support ezali na ntina mingi awa.",
    "Jack-Pot ekosenga ba documents ?",
    "Très probablement liboso ya retrait, ee. Tia ba documents kaka via site officiel Jack-Pot.",
    "Ba retraits ezali instantanés ?",
    "Te ntango nyonso. Ba retraits ya moke ekoki kozala nokinoki. Ba gros paiements ekoki kozwa temps mingi mpo na vérification, limites ya provider to contrôles manuels.",
    "Kobeta na Jack-Pot ezali na riski ?",
    "Ee. Slots, crash games, live casino mpe paris sportifs ezali na riski. Okoki kobunga mbongo. Salela ba limite, kobunda te perte mpe kotala Jack-Pot lokola moyen ya gain te. Ezali divertissement ya jeu, te revenu.",
]

# --- casino #26 Swahili (EN -> SW) ---
SW26 = [
    "BC.Game — Chicken Road",
    "Chicken Road kwenye BC.Game",
    "Kuhusu BC.Game",
    "Chicken Road inapatikana kwenye BC.Game",
    "Kwa Nini Chicken Road Inafaa BC.Game",
    "Chicken Road kutoka InOut kwenye BC.Game",
    "Uzoefu wa Simu kwenye BC.Game",
    "Bonus, crypto na wagering",
    "BC.Game APK na Ufikiaji wa App",
    "Hitimisho",
    "FAQ",
    "Muhtasari wa lobby ya kasino ya BC.Game na Chicken Road",
    "Ukurasa wa nyumbani wa kasino ya mtandaoni ya BC.Game na sehemu za lobby",
    "Matokeo ya utafutaji wa Chicken Road kwenye lobby ya kasino ya BC.Game",
    "Chicken Road asili na InOut Games kwenye BC.Game",
    "Chicken Road kwenye programu ya simu ya BC.Game",
    "Upakuaji wa programu ya simu ya BC.Game na ufikiaji wa lobby",
    "BC.Game ni jukwaa maarufu la kasino la mtandaoni, na Chicken Road ni mojawapo ya michezo wachezaji sasa wanayoitafuta kikamilifu kwenye tovuti kubwa za kasino. Mantiki ni rahisi: watu tayari wanajua mchezo, wanajua BC.Game, na wanataka kuufungua haraka mahali wanayotumia tayari.",
    "Chicken Road asili kutoka InOut Games inapatikana kwenye BC.Game, hivyo wachezaji hawapaswi kutafuta nakala au mbadala wa kubahatisha. Unaweza kutafuta mchezo kwenye lobby ya kasino na kufungua kichwa halisi kutoka kwa mtoa huduma.",
    "Hii ni muhimu kwa sababu Chicken Road ni aina ya mchezo watu kwa kawaida wanataka kuzindua haraka. Kasino inapatikana katika nchi nyingi, hivyo kufikia jukwaa ni haraka na rahisi. BC.Game ina lobby kubwa ya michezo na imejengwa kwa ufikiaji wa haraka kutoka desktop au simu.",
    "Kwa wachezaji waliokuja haswa kwa Chicken Road, BC.Game ni chaguo rahisi. Fungua kasino, tumia utafutaji, pata kichwa cha InOut, na anza kutoka hapo.",
    "BC.Game ni kasino ya mtandaoni yenye picha kali ya kirafiki kwa crypto. Jukwaa linajulikana kwa ufikiaji wa haraka, lobby kubwa ya michezo, na mchanganyiko wa aina tofauti za kamari mahali pamoja.",
    "Ndani ya BC.Game, wachezaji wanaweza kupata slots, live casino, michezo ya meza, crash na instant games, sportsbook, na majina mengine ya haraka. Si tovuti ya slots tu wala jukwaa la kubeti pekee. Inafanya kazi zaidi kama kitovu kikubwa cha kasino ambapo kila aina ya mchezaji anaweza kupata kitu kinachojulikana.",
    "Chicken Road inafaa aina hii ya lobby vizuri. Watumiaji wa BC.Game mara nyingi hupenda michezo inayofunguka haraka, haitegemei maelezo marefu, na inatoa matokeo wazi bila kusubiri animation nzito. Chicken Road ina rhythm ile ile.",
    "Kwa wachezaji wanaopendelea vikao vifupi, kucheza kwa simu, na mechanics rahisi za arcade, BC.Game ni mahali pa asili pa kuutafuta. Mchezo hauonekani kama wa nje huko — unakaa vizuri karibu na crash, instant, na majina mengine ya kasino ya haraka.",
    "BC.Game ina Chicken Road kwenye lobby ya kasino. Unapoandika Chicken Road, BC.Game inaonyesha safu nzima ya majina yanayohusiana mara moja.",
    "Kuna Chicken Road asili kutoka InOut Games na matoleo mengine pia. Mfululizo wa kuku tayari umejithibitisha, na wachezaji wanaweza kupata toleo linalowavutia kwa macho.",
    "Kwenye BC.Game, Chicken Road inafanya kazi karibu kama mini-kategoria tofauti. Mchezaji anaweza kufungua mchezo asili, kisha kulinganisha haraka na matoleo ya Gold, Vegas, Race au Bonus bila kuondoka kwenye ukurasa ule ule wa utafutaji.",
    "Njia rahisi zaidi bado ni utafutaji. Fungua lobby ya kasino, andika Chicken Road, na uchague toleo unalotaka kutoka kwenye kadi. Unaweza pia kuchuja kwa mtoa huduma na kutafuta InOut, hasa ikiwa unataka kuangalia orodha kamili kutoka studio.",
    "Demo mode pia inapatikana, hivyo unaweza kujaribu mchezo kabla ya kutumia salio halisi.",
    "Njia fupi:",
    "Casino → Search → Chicken Road → Chagua toleo → Demo au Play",
    "Chicken Road inahisi ya asili kwenye BC.Game kwa sababu jukwaa lenyewe limejengwa kuzunguka ufikiaji wa haraka. Watu huja huko kwa muundo wa kasino wa haraka, michezo ya kirafiki kwa crypto, michezo ya instant, majina ya crash, na michezo ambayo haitegemei joto la muda mrefu. Chicken Road inakaa vizuri katika njia hiyo. Si aina ya mchezo ambapo unasoma sheria kwa dakika kumi kabla ya kuanza. Unaufunua, unaelewa hali karibu mara moja, na raundi haichukui muda mrefu.",
    "Hii inafanya kazi vizuri haswa kwa hadhira ya BC.Game. Wachezaji wengi huko wamezoea maamuzi ya haraka, amana za haraka, vikao vifupi, na mizunguko rahisi ya mchezo. Chicken Road ina nishati ile ile: visuals safi, uzinduzi wa haraka, na swali moja wazi wakati wa raundi — endelea au simama. Sababu nyingine inayofanya ifae BC.Game ni ukubwa wa mfululizo wa kuku kwenye lobby. Si kadi moja tu ya mchezo. Unaweza kuona matoleo tofauti karibu, hivyo mchezaji anaweza kusonga kutoka Chicken Road asili hadi Gold, Vegas, Race, Bonus, au majina mengine ya InOut bila kuondoka kwenye mfumo ule ule.",
    "Kwenye BC.Game, Chicken Road haiwasilishwi kama nakala ya kubahatisha yenye mada ya kuku. Mchezo unaonekana kwenye lobby kama kichwa cha InOut Games, jambo muhimu kwa wachezaji wanaotafuta toleo asili, si clone lenye jina linalofanana.",
    "Hii ni mojawapo ya sababu kuu BC.Game inafanya kazi vizuri kwa Chicken Road. Mchezaji anaweza kuangalia kadi ya mchezo, kuona mtoa huduma, kufungua kichwa, na kuelewa kuwa ni studio ile ile iliyounda mfululizo wa Chicken Road.",
    "Kwa aina hii ya mchezo, kuonekana kwa mtoa huduma ni muhimu. Kuna nakala nyingi, kurasa za mirror, na michezo ya kuku \"karibu sawa\" mtandaoni. Baadhi zinaweza kuonekana karibu mwanzo, lakini mechanics, zana za haki, RTP, au hata mantiki ya mchezo inaweza kuwa tofauti.",
    "BC.Game inafanya ukaguzi kuwa rahisi. Tafuta Chicken Road, fungua kadi ya mchezo, na angalia mtoa huduma. Ikiwa inaonyesha InOut Games, basi haubahati tena.",
    "Hiyo ndiyo thamani halisi hapa: ufikiaji wa haraka, mtoa huduma asili, na hakuna haja ya kufuatilia mchezo kwenye tovuti zisizojulikana. Kwa wachezaji waliokuja haswa kwa Chicken Road, hii ni bora zaidi kuliko kutua kwenye ukurasa wa kubahatisha unaotumia jina tu.",
    "Chicken Road inahisi vizuri kwenye BC.Game kutoka simu. Si kwa sababu simu inafanya mchezo uwe tofauti, bali kwa sababu muundo wenyewe ni mfupi na mwepesi. Huhitaji skrini kubwa ya desktop kuelewa kinachotokea.",
    "BC.Game inaweza kufunguliwa kupitia tovuti ya simu au programu rasmi. Utafutaji wa lobby unafanya kazi vya kutosha kwenye skrini ndogo: andika jina la mchezo, fungua kadi, na tayari uko ndani ya ukurasa wa Chicken Road.",
    "Kwenye simu, jambo kuu si graphics. Ni udhibiti. Vitufe viko karibu zaidi, sehemu ya dau ni ndogo, na michezo ya haraka inaweza kukufanya kubonyeza haraka kuliko ulivyopanga. Kabla ya kuanza, angalia dau mara moja. Inachukua sekunde mbili na inaokoa makosa mengi ya kijinga.",
    "Ningepia epuka kupakua Chicken Road kutoka kurasa za APK za kubahatisha. Ikiwa mchezo tayari uko ndani ya BC.Game, hakuna sababu ya kutafuta toleo \"maalum\" nje ya jukwaa. Tumia tovuti rasmi ya BC.Game au programu, fungua mchezo wa mtoa huduma kutoka lobby, na ucheze kutoka hapo.",
    "BC.Game ni tofauti kidogo na tovuti nyingi za kasino za kawaida kwa sababu bonus huko zinaweza kutegemea zaidi ya bango la promo tu. Hali ya akaunti yako, sarafu iliyochaguliwa, kiwango cha VIP, kampeni zinazofanya kazi, na hata aina ya mchezo zinaweza kuathiri jinsi bonus inavyofaa kweli.",
    "Kwa Chicken Road, jambo la kwanza la kuangalia ni kama michezo ya instant au crash-style inahesabiwa kwa wagering. Baadhi ya promos zinaonekana nzuri mwanzo, lakini kisha unafungua sheria na unaona kwamba slots tu zinahesabiwa, au baadhi ya michezo ya haraka imeondolewa. Katika hali hiyo, bonus bado inaweza kuwepo, lakini haitasaidia sana na Chicken Road.",
    "Crypto inaongeza safu moja zaidi, lakini angalia mtandao kabla ya kuweka amana au kutoa. Mtandao mbaya, ada ya juu, au kikomo kidogo cha uondoaji kinaweza kufanya muamala rahisi kuwa cha kukatisha tamaa haraka sana.",
    "Ningezingatia mambo matatu kabla ya kutumia promo yoyote na Chicken Road kwenye BC.Game: kama mchezo unastahili, jinsi wagering inavyofanya kazi, na kinachotokea unapotoa katika sarafu uliyochagua. Bonus yenyewe si hadithi nzima. Sheria zinazoizunguka ni muhimu zaidi.",
    "Programu ya BC.Game ni muhimu ikiwa unacheza kutoka simu mara nyingi. Huhitaji kufungua kivinjari, kuandika anwani ya tovuti, kutafuta lobby tena, na kusubiri kila kitu kupakia kutoka sifuri. Icon tayari iko kwenye skrini yako, hivyo ufikiaji unahisi wa haraka zaidi.",
    "Kwa Chicken Road, urahisi huo ni muhimu. Hii si mchezo ambapo unataka kutumia muda kuchimba menyu. Unafungua programu, unaenda kwenye lobby ya kasino, unatafuta Chicken Road, na unazindua mchezo wa InOut kutoka hapo.",
    "Programu pia inaweka kila kitu karibu: akaunti, salio, promosheni, cashier, historia ya michezo, na msaada. Inaokoa muda, hasa ukibadilisha kati ya michezo au kutumia sarafu tofauti.",
    "Pia kunaweza kuwa na vipengele vya simu pekee ndani ya programu. Baadhi ya chaguo zinaweza kuhisi vizuri zaidi kwenye simu: kuingia haraka, arifa za push, ufikiaji wa haraka wa lobby, vidhibiti vya mguso laini, na madirisha ya michezo yaliyoboreshwa kwa simu. Vipengele halisi vinategemea kifaa chako na toleo la BC.Game unalotumia.",
    "Bado, pakua tu kutoka chanzo rasmi cha BC.Game. Usisakinishe faili za APK za kubahatisha kutoka Telegram, matangazo, au kurasa zisizojulikana. Ikiwa unataka programu, ipate kutoka tovuti halisi ya BC.Game au viungo rasmi vya duka, si kutoka kurasa za APK \"maalum\" za Chicken Road.",
    "BC.Game inafanya kazi vizuri kwa Chicken Road kwa sababu jukwaa tayari lina mazingira sahihi kwa aina hii ya mchezo. Lobby ya haraka, malipo ya kirafiki kwa crypto, ufikiaji wa simu, na utafutaji wa haraka hufanya mchezo uwe rahisi kufungua bila kupoteza muda.",
    "Faida kuu ni kwamba BC.Game haionyeshi Chicken Road kama kichwa kilichofichwa. Unaweza kutafuta lobby, kupata toleo asili la InOut Games, na pia kuona matoleo mengine kutoka mfululizo ule ule wa kuku karibu.",
    "Bado, ningeanza na demo kwanza. Si kupata strategy ya kichawi, bali kuelewa toleo gani linahisi bora na jinsi mchezo unavyofanya kazi kabla ya kutumia salio halisi.",
    "Hivyo ndiyo, BC.Game ni mahali rahisi kwa Chicken Road. Fungua tu tovuti rasmi au programu, angalia mchezo kupitia utafutaji, tumia demo kwa majaribio, na uhamie kwenye kucheza halisi tu unapoelewa unachofanya.",
    "Je, Chicken Road inapatikana kwenye BC.Game?",
    "Ndiyo. BC.Game ina Chicken Road asili kutoka InOut Games kwenye lobby ya kasino. Unapotafuta kichwa, unaweza pia kuona michezo mingine kutoka mfululizo ule ule wa mada ya kuku.",
    "Ninawezaje kupata Chicken Road kwenye BC.Game?",
    "Tumia utafutaji wa lobby. Andika Chicken Road na kadi za mchezo zinapaswa kuonekana mara moja. Hii ni haraka zaidi kuliko kufungua kategoria moja baada ya nyingine.",
    "Je, naweza kutafuta kwa mtoa huduma InOut?",
    "Ndiyo. Kutafuta InOut ni muhimu ikiwa unataka kuona michezo zaidi kutoka studio ile ile, si Chicken Road pekee. Kwenye BC.Game, utafutaji wa mtoa huduma unaweza kuonyesha mkusanyiko mpana wa kuku mahali pamoja.",
    "Je, ikiwa Chicken Road haipo kwenye lobby?",
    "Angalia tahajia kwanza, kisha jaribu kutafuta InOut badala ya jina la mchezo. Ikiwa bado haionekani, mchezo unaweza kuwa na kikomo cha eneo, mipangilio ya akaunti, au toleo la sasa la lobby ya BC.Game.",
    "Je, naweza kucheza demo ya Chicken Road kwenye BC.Game?",
    "Ndiyo, demo mode inapatikana. Ni njia nzuri ya kufungua mchezo, kuangalia kasi, kulinganisha matoleo, na kuelewa raundi kabla ya kutumia salio halisi.",
    "Je, BC.Game inaunga mkono Chicken Road kwenye simu?",
    "Ndiyo. Chicken Road inafanya kazi kupitia tovuti ya simu ya BC.Game na programu. Mchezo unafaa simu vizuri kwa sababu skrini haijazidiwa na raundi haihitaji mpangilio mkubwa wa desktop.",
    "Je, bonus za BC.Game zinafanya kazi na Chicken Road?",
    "Inategemea sheria za promo. Angalia kama michezo ya instant au crash-style inahesabiwa kwa wagering kabla ya kuwezesha bonus.",
    "Je, naweza kucheza Chicken Road na crypto?",
    "BC.Game ni kirafiki kwa crypto, na wachezaji wengi hutumia sarafu na fedha zingine zinazotumika. Angalia mtandao na ada kabla ya kuweka amana au kutoa.",
    "Je, programu za predictor za Chicken Road ni salama?",
    "Hapana. Ningeziepuka. Programu za predictor, bots, APK, na \"signals\" kwa kawaida huahidi kitu hawawezi kuthibitisha. Baadhi hazina faida, zingine zinaweza kuwa hatari kwa akaunti yako au kifaa.",
    "Je, BC.Game ni tovuti rasmi ya InOut Games?",
    "Hapana. BC.Game ni jukwaa la kasino la mtandaoni. Inaweza kuwa mwenyeji wa Chicken Road kutoka InOut Games, lakini si tovuti rasmi ya mtoa huduma.",
    "Je, Chicken Road ni hatari kwa pesa halisi?",
    "Ndiyo. Chicken Road bado ni mchezo wa kamari. Tumia demo kwanza, weka mipaka, na usitazame kama njia ya kupata pesa.",
]

# --- casino #26 Lingala (FR -> LN) ---
LN26 = [
    "BC.Game — Chicken Road",
    "Chicken Road na BC.Game",
    "Et BC.Game",
    "Chicken Road ezali disponible na BC.Game",
    "Mpo na nini Chicken Road e correspondre na BC.Game",
    "Chicken Road ya InOut na BC.Game",
    "Expérience mobile na BC.Game",
    "Bonus, crypto mpe wagering",
    "BC.Game APK mpe accès na ba app",
    "Mokuse ya nsuka",
    "FAQ",
    "Chicken Road na aperçu ya lobby ya casino BC.Game",
    "Page ya liboso mpe ba section ya lobby ya casino online BC.Game",
    "Ba résultat ya recherche Chicken Road na lobby ya casino BC.Game",
    "Chicken Road original na InOut Games na BC.Game",
    "Chicken Road na application mobile BC.Game",
    "Téléchargement ya application mobile BC.Game mpe accès na lobby",
    "BC.Game ezali plateforme ya casino online oyo eyebani, mpe Chicken Road ezali moko ya ba lisano oyo basali baluka sikoyo na ba gros site ya casino. Logic ezali pépé : bato bazali koyeba déjà lisano, bazali koyeba BC.Game, mpe balingi kofungola yango nokinoki na esika oyo basalela déjà.",
    "Chicken Road original ya InOut Games ezali disponible na BC.Game, donc basali basengeli koluka ba copies to ba alternatives ya hasard te. Okoki koluka lisano na lobby ya casino mpe kofungola titre ya solo ya provider.",
    "Yango ezali na ntina mpo Chicken Road ezali type ya lisano oyo bato balingi kozindisa nokinoki. Casino ezali disponible na ba pays ebele, donc accès na plateforme ezali nokinoki mpe pépé. BC.Game ezali na lobby monene mpe esalemi mpo na accès ya nokinoki na desktop to mobile.",
    "Mpo na basali oyo bakoya spécifiquement mpo na Chicken Road, BC.Game ezali option ya pratique. Fungola casino, salela recherche, zua titre ya InOut mpe banda wana.",
    "BC.Game ezali casino online na image crypto-friendly makasi. Plateforme eyebani mpo na accès ya nokinoki, lobby monene mpe mix ya ba formats ya jeu na esika moko.",
    "Na BC.Game, basali bakoki kozua slots, live casino, ba jeux ya table, crash mpe instant games, sportsbook mpe ba titre ya nokinoki mosusu. Ezali te site ya slots kaka to plateforme ya paris kaka. Esalaka lokola hub monene ya casino oyo type nionso ya mosali akoki kozua eloko oyo eyebani.",
    "Chicken Road e correspondre malamu na lobby ya ndenge oyo. Basaleli BC.Game balingi mingi ba lisano oyo efungolaka nokinoki, esengeli ba explication molai te mpe epesi resultat clair sans animation ya lourde. Chicken Road ezali na rhythm moko.",
    "Mpo na basali oyo balingi ba session ya mokuse, mobile mpe mécanique ya arcade ya pépé, BC.Game ezali esika ya nature mpo na koluka yango. Lisano e paraitre déplacé te — ezali pene na crash, instant mpe ba titre ya casino ya nokinoki.",
    "BC.Game ezali na Chicken Road na lobby ya casino. Soki otia Chicken Road, BC.Game emonisaka rangée mobimba ya ba titre oyo ezali na lien nokinoki.",
    "Ezali na Chicken Road original ya InOut Games mpe ba version mosusu. Série ya nkoko e prouver déjà, mpe basali bakoki kozua version oyo elongi na miso.",
    "Na BC.Game, Chicken Road esalaka pene na mini-catégorie ya solo. Mosali akoki kofungola lisano original, sima kotala nokinoki Gold, Vegas, Race to Bonus sans kobima na page ya recherche moko.",
    "Nzelá ya pépé ezali lisusu recherche. Fungola lobby ya casino, tia Chicken Road mpe pona version oyo olingi na ba cartes. Okoki mpe ko-filtrer na fournisseur mpe koluka InOut, mingi soki olingi kotala liste mobimba ya studio.",
    "Demo mode ezali mpe disponible, donc okoki komeka lisano liboso ya kosalela solde ya solo.",
    "Nzelá ya mokuse :",
    "Casino → Recherche → Chicken Road → Pona version → Demo to Play",
    "Chicken Road e paraitre naturel na BC.Game mpo na ete plateforme moko esalemi autour ya accès ya nokinoki. Bato bakoya mpo na format ya casino ya pépé, crypto-friendly, ba jeux instantanés, crash mpe ba lisano oyo esengeli chauffage molai te. Chicken Road ezali malamu na lane wana. Ezali te type ya lisano oyo osali koyekola mibeko na minute zomi liboso ya kobanda. Ofungola yango, oyeba ambiance mbala moko mpe round esengeli temps molai te.",
    "Yango esalaka malamu mingi mpo na audience BC.Game. Basali ebele bazali habitués na ba décision ya nokinoki, dépôt ya nokinoki, session ya mokuse mpe boucle ya lisano ya pépé. Chicken Road ezali na energy moko : ba visual clairs, lancement ya nokinoki mpe question moko clair na round — continuer to tika. Ntina mosusu ezali taille ya lineup ya nkoko na lobby. Ezali te carte moko kaka. Okoki kotala ba version different pene pene, donc mosali akoki kokende na Chicken Road original tii Gold, Vegas, Race, Bonus to ba titre mosusu ya InOut sans kobima na écosystème moko.",
    "Na BC.Game, Chicken Road e présenter te lokola copy ya hasard na thème ya nkoko. Lisano ebimaka na lobby lokola titre ya InOut Games, oyo ezali important mpo na basali oyo baluka version original, te clone na nkombo oyo ezali ndenge moko.",
    "Yango ezali moko ya ba raison ya liboso oyo BC.Game esalaka malamu mpo na Chicken Road. Mosali akoki kotala carte ya lisano, kotala provider, kofungola titre mpe koyeba ete ezali studio moko oyo e créer série Chicken Road.",
    "Mpo na type oyo ya lisano, visibilité ya provider ezali na ntina. Ezali na ba copies ebele, ba pages miroir mpe ba jeux ya nkoko « presque pareil » online. Basusu ekoki kozala pene na liboso, kasi mécanique, ba outils ya fairness, RTP to logic ya lisano ekoki kozala different.",
    "BC.Game esalela vérification ya pépé. Luka Chicken Road, fungola carte mpe tala provider. Soki emonisaka InOut Games, ozali kobahati lisusu te.",
    "Yango wana valeur ya solo : accès ya nokinoki, provider original mpe besoin te ya koluka lisano na ba site oyo oyeba te. Mpo na basali oyo bakoya spécifiquement mpo na Chicken Road, yango ezali malamu koleka kokota na page ya hasard oyo esalelaka kaka nkombo.",
    "Chicken Road e sentir malamu na BC.Game na téléphone. Te mpo mobile e change lisano, kasi mpo format moko ezali mokuse mpe léger. Ozali na besoin te ya grand écran ya desktop mpo na koyeba oyo esalemaka.",
    "BC.Game ekoki kofungolama na site mobile to application officielle. Recherche na lobby esalaka malamu na écran ya moke : tia nkombo ya lisano, fungola carte mpe ozali déjà na page Chicken Road.",
    "Na mobile, eloko ya liboso ezali te graphisme. Ezali contrôle. Ba bouton ezali pene, champ ya mise ezali moke mpe ba jeux ya pépé ekoki kosala obetisa nokinoki koleka oyo o planifie. Liboso ya kobanda, talá mise mbala moko. Esengeli seconde mibale mpe e bomba ba erreur ya stupide ebele.",
    "Nakoboya mpe ko-télécharger Chicken Road na ba page APK ya hasard. Soki lisano ezali déjà na BC.Game, ezali na raison te ya koluka version « spéciale » na libanda ya plateforme. Salela site to app officiel BC.Game, fungola lisano ya provider na lobby mpe beta wana.",
    "BC.Game ezali different moke na ba site ya casino classiques mpo bonus ekoki kotalela mingi te bannière ya promo kaka. Statut ya compte, devise oyo oponi, niveau VIP, ba campagnes actives mpe type ya lisano ekoki kobongola utilité ya bonus.",
    "Mpo na Chicken Road, eloko ya liboso ya kotá ezali soki ba jeux instant to crash-style e compter mpo na wagering. Ba promo mosusu e paraitre malamu na ebandi, kasi sima ofungola mibeko mpe omoni ete slots kaka e compter, to ba jeux ya pépé e exclure. Na cas wana, bonus ekoki kozala lisusu, kasi ekosalisa mingi te na Chicken Road.",
    "Crypto e bakisa couche moko, kasi talá réseau liboso ya dépôt to retrait. Réseau ya mabe, frais ya likolo to limite ya retrait ya moke ekoki kosala transaction ya pépé e kóma pénible nokinoki.",
    "Nakotala eloko misato liboso ya kosalela promo na Chicken Road na BC.Game : soki lisano ezali éligible, ndenge wagering esalaka mpe nini esalemaka soki ozui na devise oyo oponi. Bonus moko ezali te story mobimba. Mibeko oyo ezali na zungu na yango ezali na ntina mingi.",
    "Application BC.Game ezali useful soki obetaka mingi na téléphone. Ozali na besoin te ya kofungola navigateur, kotia adresse, koluka lobby lisusu mpe kozela chargement mobimba. Icône ezali déjà na écran, donc accès e sentir nokinoki.",
    "Mpo na Chicken Road, commodité wana ezali na ntina. Ezali te lisano oyo olingi kobunga temps na ba menus. Ofungola app, okende na lobby ya casino, oluka Chicken Road mpe olancer lisano ya InOut wana.",
    "App e garder mpe nyonso pene : compte, solde, promotions, caisse, historique mpe support. E bomba temps, mingi soki obongolaka na ba jeux to osalela ba devises different.",
    "Ekoki kozala mpe ba fonction ya mobile kaka na app. Ba option mosusu ekoki kozala malamu mingi na téléphone : login ya nokinoki, push notifications, accès ya nokinoki na lobby, contrôle tactile ya fluide mpe ba fenêtre ya lisano optimisées. Ba fonction ya solo e dépendre na appareil mpe version BC.Game oyo osaleli.",
    "Kasi télécharger kaka na source officiel BC.Game. Ko-installer ba APK ya hasard na Telegram, ba pub to ba page oyo oyeba te te. Soki olingi app, zua yango na site ya solo BC.Game to ba lien officiel ya store, te na ba page « spéciale » Chicken Road APK.",
    "BC.Game esalaka malamu mpo na Chicken Road mpo plateforme ezali déjà na environnement malamu mpo na type oyo ya lisano. Lobby ya nokinoki, paiement crypto-friendly, mobile mpe recherche ya nokinoki esalela ete lisano efungolaka pépé sans kobunga temps.",
    "Avantage ya liboso ezali ete BC.Game e monisa Chicken Road te lokola titre ya caché. Okoki koluka lobby, kozua version original ya InOut Games mpe kotala mpe ba release mosusu ya lineup ya nkoko pene.",
    "Kasi nakobanda na demo liboso. Te mpo na kozua strategy ya magie, kasi mpo na koyeba version nini ezali malamu mpe ndenge lisano esalemaka liboso ya solde ya solo.",
    "Donc ee, BC.Game ezali esika ya pratique mpo na Chicken Road. Fungola kaka site to app officiel, talá lisano na recherche, salela demo mpo na test mpe pasa na jeu ya solo kaka soki omeya oyo ozali kosala.",
    "Chicken Road ezali disponible na BC.Game ?",
    "Ee. BC.Game ezali na Chicken Road original ya InOut Games na lobby ya casino. Soki oluka titre, okoki kotala mpe ba lisano mosusu ya lineup ya thème ya nkoko.",
    "Ndeni nini nakoki kozua Chicken Road na BC.Game ?",
    "Salela recherche na lobby. Tia Chicken Road mpe ba cartes ya lisano esengeli kobima nokinoki. Ezali nokinoki koleka kofungola ba catégories moko na moko.",
    "Nakoki koluka na fournisseur InOut ?",
    "Ee. Koluka InOut ezali useful soki olingi kotala ba lisano mingi ya studio moko, Chicken Road kaka te. Na BC.Game, recherche ya fournisseur ekoki komonisa collection ya nkoko mobimba na esika moko.",
    "Soki Chicken Road ezali te na lobby ?",
    "Talá orthographe liboso, sima meka koluka InOut na esika ya nkombo ya lisano. Soki e bimaka lisusu te, lisano ekoki kozala limité na région, paramètres ya compte to version ya lobby BC.Game ya lelo.",
    "Nakoki kobeta demo Chicken Road na BC.Game ?",
    "Ee, demo mode ezali disponible. Ezali ndenge malamu ya kofungola lisano, kotala rythme, kotalela ba version mpe koyeba round liboso ya solde ya solo.",
    "BC.Game esalisaka Chicken Road na mobile ?",
    "Ee. Chicken Road esalaka na site mobile BC.Game mpe app. Lisano e correspondre malamu na mobile mpo écran e chargé te mpe round esengeli layout ya desktop monene te.",
    "Ba bonus BC.Game esalaka na Chicken Road ?",
    "Etalela mibeko ya promo. Talá soki ba jeux instant to crash-style e compter mpo na wagering liboso ya ko-activer bonus.",
    "Nakoki kobeta Chicken Road na crypto ?",
    "BC.Game ezali crypto-friendly mpe basali ebele basalelaka ba coins mpe ba devises mosusu. Talá réseau mpe frais liboso ya dépôt to retrait.",
    "Ba app ya prédiction Chicken Road ezali safe ?",
    "Te. Nakoboya yango. Ba app ya prédiction, bots, APK mpe « signaux » e promettre mingi eloko oyo ekoki ko-prouver te. Basusu ezali useless, basusu ekoki kozala unsafe mpo na compte to appareil na yo.",
    "BC.Game ezali site officiel ya InOut Games ?",
    "Te. BC.Game ezali plateforme ya casino online. Ekoki ko-héberger Chicken Road ya InOut Games, kasi ezali te site officiel ya provider.",
    "Chicken Road ezali na riski na mbongo ya solo ?",
    "Ee. Chicken Road ezali lisusu jeu d'argent. Salela demo liboso, botia ba limite mpe kotala yango lokola moyen ya kozua mbongo te.",
]


def build_casino_25() -> None:
    en = load_segs("casino-25-en-segments.json")
    fr = load_segs("casino-25-fr-segments.json")
    assert len(en) == len(SW25) == len(fr) == len(LN25) == 55
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye Jack-Pot Casino: Cheza na Bonus",
        "Cheza Chicken Road kwenye Jack-Pot: bonus, demo, simu, Cash Out na mwongozo wa kupata mchezo kwenye kasino.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na Jack-Pot Casino: Kobeta mpe Bonus",
        "Bina Chicken Road na Jack-Pot: bonus, demo, mobile, Cash Out mpe ndenge ya kozua lisano na casino.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "Jack-Pot — Chicken Road",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "Jack-Pot — Chicken Road",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW25),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, LN25)],
        },
    }
    (OUT / "casino_25.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_25.json: {len(en)} sw + {len(fr)} fr_ln")


def build_casino_26() -> None:
    en = load_segs("casino-26-en-segments.json")
    fr = load_segs("casino-26-fr-segments.json")
    assert len(en) == len(SW26) == len(fr) == len(LN26) == 78
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye BC.Game: Cheza na Crypto Bonus",
        "Cheza Chicken Road kwenye BC.Game: crypto bonus, demo, simu, InOut Games na mwongozo wa kupata mchezo kwenye kasino.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na BC.Game: Kobeta mpe Crypto Bonus",
        "Bina Chicken Road na BC.Game: crypto bonus, demo, mobile, InOut Games mpe ndenge ya kozua lisano na casino.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "BC.Game - Chicken Road",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "BC.Game - Chicken Road",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW26),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, LN26)],
        },
    }
    (OUT / "casino_26.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_26.json: {len(en)} sw + {len(fr)} fr_ln")


def main() -> int:
    build_casino_25()
    build_casino_26()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
