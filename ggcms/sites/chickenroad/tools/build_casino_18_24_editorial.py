#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial casino_18.json and casino_24.json sw/ln translation data."""

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


# --- casino #18 Swahili (EN -> SW) ---
SW18 = [
    "MOSTBET — Chicken Road",
    "Chicken Road kwenye MOSTBET",
    "Kuhusu MOSTBET",
    "Je, Chicken Road inapatikana kwenye MOSTBET?",
    "Guidebook ya MOSTBET na Chicken Road",
    "Jinsi Chicken Road inavyohisi kwenye MOSTBET",
    "Chicken Road kwenye MOSTBET mobile",
    "Bonus na promos kwenye MOSTBET",
    "Hitimisho",
    "FAQ",
    "Chicken Road kwenye jukwaa la kasino la MOSTBET",
    "Ukurasa wa nyumbani wa sportsbook na kasino la MOSTBET",
    "Chicken Road iliyoorodheshwa chini ya InOut Games kwenye MOSTBET",
    "Ukurasa wa guidebook wa Chicken Road wa MOSTBET",
    "Chicken Road kwenye programu ya simu ya MOSTBET",
    "MOSTBET ni jukwaa la kubeti na kasino linalojulikana, hivyo inaeleweka kwamba watu hutafuta michezo huko. Chapa imekuwa mojawapo ya majukwaa yanayoonekana zaidi, hasa miongoni mwa wachezaji wanaopendelea raundi fupi na gameplay ya arcade rahisi.",
    "Chicken Road haihitaji maandalizi mengi. Huhitaji kusoma paytable ndefu au kuelewa alama nyingi za slot kabla ya kuifungua. Wazo linashikika haraka: kuku husonga mbele, shinikizo linakua, na mchezaji anaamua lini kusimama.",
    "Hii ndiyo sababu mchezo unafaa kwa hadhira ya MOSTBET. Watumiaji wengi huja kwenye jukwaa kwa ufikiaji wa haraka — dau za michezo, michezo ya kasino, sehemu za live, na majina ya michezo ya haraka katika akaunti moja. Chicken Road ni aina ile ile ya tabia: fungua mchezo, jaribu kasi, na uelewe mechanics bila kupoteza muda.",
    "Maelezo mengine yanayofanya MOSTBET kuwa tofauti ni ukurasa wake wa guidebook wa Chicken Road pekee. Hii inampa mchezo mwonekano zaidi kwenye jukwaa na inawasaidia wachezaji wapya kuelewa wanachofungua kabla ya kuanza kucheza.",
    "MOSTBET ni aina ya jukwaa ambapo wachezaji kwa kawaida hufanya zaidi ya jambo moja. Wengine huja kwa dau za michezo, wengine hufungua michezo ya kasino, wengine hutumia live casino, na wengine huangalia majina ya haraka kutoka simu wanapokuwa na dakika chache.",
    "MOSTBET si sportsbook tu. Inafanya kazi kama jukwaa la kamari mchanganyiko ambapo wachezaji wanaweza kusonga kati ya dau za michezo, michezo ya kasino, live casino, slots, michezo ya crash, majina ya instant, kucheza kwa simu, bonus, na zana za malipo kutoka akaunti moja. Mchanganyiko huo unaelezea kwa nini Chicken Road haionekani ya ajabu huko.",
    "Muundo huo ndiyo sababu Chicken Road inaweza kufaa hapa. Watumiaji wa MOSTBET tayari wamezoea vitendo vya haraka: kuweka dau, kufungua soko la live, kubadili kwenda mchezo wa kasino, kuangalia promo, au kucheza kutoka simu. Chicken Road ina mantiki ile ile ya kikao kifupi.",
    "Maoni ya wachezaji kuhusu MOSTBET hayafanani, hata hivyo. Kuna mapitio chanya kuhusu uteuzi wa michezo na matumizi ya jumla ya jukwaa, lakini pia kuna malalamiko kuhusu uondoaji, akaunti zilizofungwa, uthibitishaji, matatizo ya bonus, na ucheleweshaji wa msaada. Kwa Chicken Road haswa, ulinganifu ni wazi: MOSTBET tayari ina michezo, kasino, ufikiaji wa simu, promos, na muundo wa kamari wa haraka katika akaunti moja. Njia pekee ya busara ni kutumia urahisi huo bila kupuuza maelezo ya vitendo nyuma yake.",
    "Ndiyo, Chicken Road inapatikana kwenye MOSTBET. Unaweza kuifungua kupitia sehemu ya kasino, si kupitia upande wa sportsbook wa jukwaa.",
    "Njia ya kawaida ni rahisi: nenda Casino, fungua Slots, kisha utafute mtoa huduma InOut Games. Chicken Road inapaswa kuorodheshwa huko kama kichwa cha asili cha InOut. Unaweza pia kuandika Chicken Road kwenye utafutaji wa kasino ikiwa unataka kufika kwenye mchezo haraka zaidi.",
    "MOSTBET pia ina ukurasa wa guidebook wa Chicken Road pekee, ambao unafanya mchezo uonekane zaidi kwa wachezaji wapya. Lakini uzinduzi halisi hufanyika ndani ya lobby ya kasino, ambapo kadi ya mchezo inafunguka katika hali ya demo au pesa halisi.",
    "Ningeanza na demo kwanza. Zingatia jina la studio na RTP. Pia angalia kama vipengele na mechanics zote za gameplay zinapatikana. Ikiwa kila kitu kinafanya kazi vizuri, unaweza kubadili kwa usalama kwenda hali ya pesa halisi.",
    "Njia fupi:",
    "Casino → Slots → InOut Games → Chicken Road → Demo au Play",
    "MOSTBET ina undani mmoja unaofanya ukurasa huu kuwa tofauti na orodha ya kawaida ya kasino: Chicken Road ina sehemu yake ya guidebook. Sio kila kasino kinatoa ukurasa pekee kwa mchezo mmoja wa haraka, hivyo hii tayari inafanya kichwa kiwe na mwonekano zaidi kwa wachezaji wa MOSTBET.",
    "Guidebook ni muhimu kabla ya kufungua mchezo kwenye lobby ya kasino. Inampa mgeni wazo la haraka la Chicken Road ni nini, kwa nini wachezaji huitafuta, na nini cha kutarajia kutoka kwa muundo.",
    "Hii inafanya kazi vizuri kwa Chicken Road kwa sababu mchezo unaonekana rahisi, lakini uamuzi ndani ya kila raundi bado una umuhimu. Guidebook inaweza kusaidia kuelezea wazo kuu, kucheza demo, hali ya pesa halisi, ufikiaji wa simu, na sheria za jumla bila kubadilisha ukurasa kuwa mwongozo mrefu.",
    "Kwa MOSTBET, hii pia ni busara upande wa jukwaa. Mchezaji anaweza kwanza kutua kwenye guidebook ya Chicken Road, kuelewa mchezo, na kisha tu kuhamia kwenye sehemu ya kasino. Njia hiyo inahisi ya asili zaidi kuliko kutafuta kupitia lobby bila muktadha.",
    "Hivyo guidebook si makala ya ziada tu. Kwa Chicken Road kwenye MOSTBET, inafanya kazi kama ukurasa mdogo wa kuingia: soma misingi, fungua demo, angalia mchezo kutoka InOut Games, na kisha uamue kama utacheza na salio halisi.",
    "Chicken Road kwenye MOSTBET inahisi karibu na mchezo wa arcade wa haraka kuliko slot ya kawaida. Hakuna reels nzito, animation ndefu za bonus, au jedwali za alama zilizojaa. Skrini ni safi, wazo ni la kuona, na mchezo hauwafanyi wachezaji kusubiri kabla ya jambo fulani kutokea.",
    "Hii ndiyo sababu inafaa vizuri kwenye sehemu ya kasino ya MOSTBET. Jukwaa tayari lina vitendo vingi vya haraka: dau za michezo, masoko ya live, slots, michezo ya crash, na majina ya kasino ya simu. Chicken Road ina kasi ile ile. Unaufunua, unaangalia raundi ikijenga msukumo, na unafanya uamuzi bila kukaa kupitia usanidi wa polepole.",
    "Mchezo pia unafanya kazi vizuri kwa wachezaji wasiopenda interfaces za kasino zilizojaa vitu. Chicken Road ni rahisi juu, lakini bado inashikilia umakini kwa sababu kila raundi inahisi kama chaguo dogo chini ya shinikizo. Kwenye MOSTBET, hii inafanya Chicken Road ihisi kama mchezo mzuri wa kikao kifupi.",
    "Chicken Road inafanya kazi vizuri kwenye MOSTBET mobile kwa sababu mchezo hauhitaji skrini kubwa. Raundi ni rahisi kufuatilia kutoka simu, na vitendo vikuu hubaki karibu kwenye skrini.",
    "MOSTBET pia ni jukwaa ambalo wachezaji wengi hutumia kwa simu kwa chaguo-msingi. Unaweza kufungua kasino kutoka tovuti ya simu au kupitia programu rasmi ya MOSTBET, kwenda slots, kuchagua InOut Games, na kuzindua Chicken Road bila kukaa kwenye desktop.",
    "Programu ni rahisi hasa ikiwa unacheza kutoka simu mara nyingi. Ufikiaji wa akaunti, salio, lobby ya kasino, bonus, cashier, na msaada vyote viko mahali pamoja.",
    "Jambo pekee ambalo nisingefanya ni kupakua faili za APK za kubahatisha kutoka tovuti zisizojulikana. Tumia programu rasmi ya MOSTBET au toleo la simu la tovuti. Ikiwa Chicken Road inapatikana ndani ya MOSTBET, hakuna sababu ya kusakinisha APK ya Chicken Road pekee kutoka Telegram, matangazo, au kurasa za mirror.",
    "Bonus kwenye MOSTBET hazihusiani na Chicken Road yenyewe. Mchezo hauunda promo; jukwaa ndilo hufanya hivyo. Hiyo inamaanisha kila ofa lazima iangaliwe kupitia sheria za MOSTBET, si kupitia skrini ya mchezo.",
    "Kwa Chicken Road, undani muhimu zaidi ni ustahili wa mchezo. Bonus zingine za kasino hufanya kazi tu na slots. Promos zingine zimetengenezwa kwa dau za michezo. Cashback inaweza kuwa na sheria zake. Free Spins kwa kawaida hazihusiki hapa kabisa, isipokuwa promo inajumuisha wazi aina hii ya mchezo.",
    "Kabla ya kuwezesha chochote, angalia mambo manne: wagering, muda wa kuisha, dau la juu, na michezo inayostahili. Chicken Road iko karibu na mchezo wa kasino wa instant wa haraka, hivyo inaweza kutohesabiwa kila wakati kwa njia ile ile kama slots za kawaida.",
    "Hapa ndipo wachezaji wengi hufanya kosa. Wanachukua bonus kwa sababu bango linaonekana kuvutia, kisha baadaye hugundua kwamba mchezo waliotaka kucheza hauwasaidii na wagering. Kwenye MOSTBET, ni bora kufungua masharti ya promo kwanza na kisha uamue kama ofa ina maana kwa Chicken Road.",
    "Hivyo bonus inaweza kuwa muhimu, lakini tu wakati sheria zinafaa kweli kwa mchezo. Usiangalie tu kiasi cha bonus. Hakikisha unasoma masharti na hali kwanza na uangalie kama bado unaweza kurekebisha kucheza kwako kwa pesa halisi baada ya kuwezesha.",
    "MOSTBET inaweza kuwa mahali rahisi pa kucheza Chicken Road kwa sababu jukwaa tayari linampa mchezo mwonekano mzuri. Kuna ukurasa wa guidebook pekee, ufikiaji kupitia lobby ya kasino, kucheza kwa simu, na chaguo la kuanza na demo kabla ya kutumia salio halisi.",
    "Mchezo pia unaendana na jinsi watu wengi hutumia MOSTBET: ufikiaji wa haraka, vikao vifupi, na muundo rahisi wa kasino ambao hauhitaji usanidi mrefu. Chicken Road inahisi ya asili huko kwa sababu ni rahisi kufungua na rahisi kuelewa kwa kuona.",
    "Bado, demo inapaswa kuja kwanza. MOSTBET inafanya kazi vizuri kwa Chicken Road, hasa kwa wachezaji wa simu na watumiaji wanaotaka kuanza haraka. Tumia tu tovuti au programu rasmi, fungua toleo la InOut Games, jaribu demo kwanza, na kumbuka kwamba kucheza kwa pesa halisi daima kuna hatari.",
    "Je, naweza kucheza Chicken Road bure kwenye MOSTBET?",
    "Ndiyo. MOSTBET inatoa kucheza demo kwa Chicken Road, hivyo unaweza kufungua mchezo bila kutumia salio halisi.",
    "RTP ya Chicken Road ni nini?",
    "Chicken Road asili kutoka InOut Games ina RTP ya 98%. Hii ni thamani ya nadharia ya muda mrefu, huku kila raundi bado inaweza kuisha kwa njia yoyote.",
    "Je, Chicken Road inapatikana kwenye simu?",
    "Ndiyo. Chicken Road inafanya kazi vizuri kwenye simu kupitia MOSTBET.",
    "Je, Chicken Road ni slot?",
    "Si kabisa. Wachezaji wengi huiita Chicken Road slot kwa sababu huipata ndani ya sehemu ya kasino, lakini mchezo wenyewe uko karibu na mchezo wa arcade wa instant cash-out.",
    "Je, Chicken Road ina Free Spins?",
    "Hapana, Chicken Road asili haijengwa kuzunguka Free Spins.",
    "Je, kuna vipengele maalum katika Chicken Road?",
    "Viwango tofauti vya ugumu vinaweza kubadilisha hisia ya raundi, lakini wazo kuu hubaki rahisi: chukua matokeo ya sasa au hatari ya kuendelea mbele.",
    "Je, naweza kucheza Chicken Road kwenye MOSTBET kwa pesa halisi?",
    "Ndiyo. Ni bora kujaribu mchezo katika demo kwanza.",
    "Je, naweza kutumia PayPal kuweka amana kwa Chicken Road kwenye MOSTBET?",
    "PayPal kwa kawaida si chaguo kuu kwenye MOSTBET.",
    "Je, naweza kucheza Chicken Road bila usajili?",
    "Lazima ukamilishe usajili kwanza.",
    "Ninawezaje kufikia Chicken Road kwenye MOSTBET?",
    "Nenda Casino, fungua Slots, chagua mtoa huduma InOut Games, na upate Chicken Road.",
    "Je, kuna bonus kwa Chicken Road?",
    "MOSTBET inaweza kuendesha bonus za kasino, lakini hazitengenezwi kila wakati kwa Chicken Road. Angalia masharti ya promo kwanza.",
]

# --- casino #18 Lingala (FR -> LN) ---
LN18 = [
    "MOSTBET — Chicken Road",
    "Chicken Road na MOSTBET",
    "Et MOSTBET",
    "Chicken Road ezali disponible na MOSTBET ?",
    "Guidebook MOSTBET mpe Chicken Road",
    "Ndenge Chicken Road ezali na MOSTBET",
    "Chicken Road na mobile MOSTBET",
    "Bonus mpe ba promo na MOSTBET",
    "Mokuse ya nsuka",
    "FAQ",
    "Chicken Road na plateforme casino MOSTBET",
    "Page ya liboso ya paris sportifs mpe casino MOSTBET",
    "Chicken Road oyo ezali na InOut Games na MOSTBET",
    "Page guidebook Chicken Road na MOSTBET",
    "Chicken Road na application mobile MOSTBET",
    "MOSTBET ezali plateforme ya paris mpe casino oyo eyebani, donc ezali logique koluka ba lisano wana. Marque ezali visible mingi, mingi mpo na basali oyo balingi ba round ya mokuse mpe gameplay ya arcade ya pépé.",
    "Chicken Road esengeli préparation mingi te. Ozali na besoin te ya koyekola paytable molai to ba symbole mingi liboso ya kofungola lisano. Likanisi e comprendre nokinoki : poule ekende liboso, pression ekóma, mpe mosali azui décision ya kotika.",
    "Yango wana lisano e correspondre na audience MOSTBET. Bato mingi bakoya mpo na accès ya nokinoki — paris sportifs, casino, live mpe ba titre instantanés na compte moko. Chicken Road ezali na logique moko : kofungola, komeka rythme, mpe koyeba mécanique sans kobunga ntango.",
    "MOSTBET e différencier mpe na page guidebook dédiée na Chicken Road. Yango epesi lisano visibility mingi mpe esalisaka basali ya sika koyeba oyo bakofungola liboso ya kobeta.",
    "MOSTBET ezali plateforme oyo basali basala mbala mingi makambo mingi : paris sportifs, casino, live casino to ba jeux rapides na téléphone.",
    "Ezali bookmaker kaka te. Ezali hub ya jeu mixte : sports, casino, live, slots, crash, instant games, mobile, bonus mpe paiements na compte moko. Chicken Road e paraitre malamu te na contexte oyo.",
    "Basali MOSTBET bazali habitués na ba action ya nokinoki : parier, kofungola marché live, kokende na casino, kotala promo to kobeta na mobile. Chicken Road ezali na logique ya session ya mokuse.",
    "Ba avis na MOSTBET ezali mitigés : bonne sélection ya ba jeux, kasi mpe ba plaintes na retraits, vérification, bonus to support. Mpo na Chicken Road, avantage ezali accès ya nokinoki — soki obosani ba détails pratiques te.",
    "Ee, Chicken Road ezali disponible na MOSTBET na section casino, te na sportsbook.",
    "Nzelá ya mbala na mbala : Casino → Slots → fournisseur InOut Games. Okoki mpe kotia Chicken Road na recherche ya casino.",
    "MOSTBET epesi mpe page guidebook Chicken Road, oyo esalela lisano plus visible. Lancement ya solo esalemi na lobby casino, na Demo to na mbongo ya solo.",
    "Bandá na Demo. Talá nkombo ya studio mpe RTP, mpe ba mécaniques oyo ezali. Soki nyonso ezali malamu, pasa na mode ya solo.",
    "Nzelá ya mokuse :",
    "Casino → Slots → InOut Games → Chicken Road → Demo to Kobeta",
    "MOSTBET ezali na détail ya rare : section guidebook dédiée na Chicken Road. Ba casino mingi te epesi page séparée mpo na lisano ya nokinoki.",
    "Guidebook esalisaka liboso ya kofungola lisano na lobby : Chicken Road ezali nini, mpo na nini bato baluka yango, mpe nini ya kozela.",
    "Malamu mpo lisano e paraitre pépé, kasi round moko na moko esengeli décision ya solo. Guidebook e expliquer Demo, mobile mpe mibeko ya jumla sans manuel ya molai.",
    "Mosali akoki koyekola guidebook liboso, sima kokende na casino — plus naturel que koluka na lobby sans contexte.",
    "Ezali article ya kobakisa te : ezali page ya entrée ya moke — koyekola base, kofungola Demo InOut, sima kozua décision mpo na mbongo ya solo.",
    "Na MOSTBET, Chicken Road e ressembler plus na arcade ya nokinoki que na slot classique. Reel ya lourde te mpe animation ya molai te.",
    "Yango e correspondre na casino MOSTBET : paris, live, slots, crash mpe mobile. Même tempo — kofungola, koyoka tension, décider sans attente ya molai.",
    "Bon choix mpo na oyo alingi interface ya casino oyo ezali chargée te. Pépé na surface, kasi round moko na moko e garder pression ya moke.",
    "Chicken Road esalaka malamu na mobile MOSTBET : écran ya monene esengeli te, ba action ezali pene pene.",
    "Fungola casino na site mobile to app officielle MOSTBET, kende na slots, pona InOut Games mpe lancer Chicken Road.",
    "App e regrouper compte, solde, lobby, bonus, caisse mpe support — pratique soki obetaka mingi na téléphone.",
    "Kozua APK ya hasard te. Salela app to site officiel MOSTBET, te « Chicken Road APK » na Telegram to ba miroirs.",
    "Bonus e vient na MOSTBET, te na lisango moko. Offre moko na moko esengeli kotá na mibeko ya plateforme.",
    "Point clé mpo na Chicken Road : éligibilité ya lisano. Ba bonus mosusu ezali mpo na slots to sport kaka ; free spins e compter mingi te awa.",
    "Liboso ya activation, talá wagering, délai, mise max mpe ba jeux éligibles. Chicken Road ezali jeu instantané — e compter ntango nyonso te lokola slot.",
    "Libunga ya mbala na mbala : kozua bonus oyo e paraitre malamu sima koyeba ete esalisaka te mpo na Chicken Road. Tá ba conditions liboso.",
    "Bonus ekoki kozala malamu kaka soki mibeko e correspondre vraiment na lisano.",
    "MOSTBET ekoki kozala malamu mpo na Chicken Road : guidebook dédié, accès lobby, mobile mpe Demo liboso ya mbongo ya solo.",
    "Lisano e correspondre na usage ya nokinoki ya plateforme : session ya mokuse, format ya pépé, ouverture ya facile.",
    "Bandá na Demo, salela site to app officiel, fungola version InOut Games mpe kobanga ete mbongo ya solo ezali na riski.",
    "Nakoki kobeta Chicken Road ofele na MOSTBET ?",
    "Ee. MOSTBET epesi Demo mpo na Chicken Road sans kosalela solde ya solo.",
    "RTP ya Chicken Road ezali nini ?",
    "Original InOut Games e afficher 98 % ya RTP — valeur théorique ya long terme, te promesse na session moko.",
    "Chicken Road ezali disponible na mobile ?",
    "Ee, na MOSTBET mobile.",
    "Chicken Road ezali slot ?",
    "Te vraiment. Oyo ezwaka yango na casino, kasi ezali plutôt jeu instantané na Cash Out.",
    "Ezali na Free Spins ?",
    "Te, original Chicken Road e construire te autour ya Free Spins.",
    "Ezali na ba fonction spéciale ?",
    "Ba niveau ya difficulté e changer sensation, kasi likanisi ezali : Cash Out to kokende liboso.",
    "Nakoki kobeta na mbongo ya solo na MOSTBET ?",
    "Ee. Meka Demo liboso.",
    "Nakoki kofuta na PayPal ?",
    "PayPal ezali généralement option principale te na MOSTBET.",
    "Sans inscription ?",
    "Inscription esengeli.",
    "Ndeni nini nakoki kozwa Chicken Road ?",
    "Casino → Slots → InOut Games → Chicken Road.",
    "Ezali na bonus mpo na Chicken Road ?",
    "MOSTBET ekoki kopesa bonus casino, kasi e cibler ntango nyonso te Chicken Road — tala ba conditions.",
]

# --- casino #24 Swahili (EN -> SW) ---
SW24 = [
    "FANSPORT - Chicken Road",
    "Kuhusu Fan-Sport",
    "Mahali pa Kupata Chicken Road kwenye Fan-Sport",
    "Jinsi ya Kucheza Chicken Road kwenye Fan-Sport",
    "Demo na Kucheza kwa Pesa Halisi kwenye Fan-Sport",
    "Chicken Road kwenye Simu kupitia Fan-Sport",
    "Mikakati na Makosa ya Kawaida ya Wachezaji",
    "Usalama na Hatari",
    "Mawazo ya Mwisho",
    "FAQ",
    "Tafuta Chicken Road kwenye Slots za Fan-Sport",
    "Michezo ya mtoa huduma InOut kwenye Fan-Sport",
    "Mpangilio wa simu wa Chicken Road kwenye Fan-Sport",
    "Skrini ya simu ya Chicken Road iliyofupishwa",
    "Multiplier inayopanda wakati wa raundi ya Chicken Road",
    "Fan-Sport ni jukwaa la mtandaoni ambapo michezo ya kasino na dau za michezo zimekusanywa katika akaunti moja. Tovuti imejengwa kwa ufikiaji wa haraka: slots, live casino, michezo ya crash na instant, na sportsbook zimewekwa katika sehemu tofauti.",
    "Jambo kuu ninalopenda kuhusu Fan-Sport ni kwamba jukwaa halihisi limejaa vitu. Unaweza kupata michezo kupitia katalogi au upau wa utafutaji na majina maarufu kwa kawaida huwekwa karibu na ukurasa wa nyumbani.",
    "Kwenye Fan-Sport, Chicken Road inafaa kwa asili katika kategoria ya michezo ya kasino ya haraka.",
    "Fan-Sport tayari ina muundo sahihi kwa michezo ya haraka, na Chicken Road inafaidika na ufikiaji wa haraka na interface safi.",
    "Chicken Road si ngumu kupata kwenye Fan-Sport. Fungua eneo la kasino na nenda Slots. Andika Chicken Road kwenye upau wa utafutaji na ufungue bango la mchezo katika dirisha jipya.",
    "Unaweza pia kutafuta kwa mtoa huduma. Andika InOut na Fan-Sport inapaswa kuonyesha michezo kutoka studio hii. Wakati mwingine Chicken Road pia huorodheshwa chini ya Other, ambapo Fan-Sport huhifadhi michezo ya arcade na instant ya haraka zaidi.",
    "Njia fupi: Slots → Search → Chicken Road → fungua bango. Ikiwa kichwa hakionekani kwa jina, jaribu kutafuta InOut badala yake.",
    "Chicken Road kwenye Fan-Sport ni rahisi kuanza: chagua kiwango cha hatari, weka dau lako, songa kuku mbele, na fanya Cash Out kabla raundi iende vibaya.",
    "Fungua mchezo na uchague ugumu. Ikiwa wewe ni mpya, anza na Easy. Medium tayari ni kali kidogo, huku Hard na Hardcore ni bora kuachwa kwa wachezaji wanaojua jinsi haraka salio linaweza kutoweka. Weka dau lako na uangalie tena kabla ya Play, hasa kwenye simu. Kila hatua salama huongeza multiplier; hatua moja ya ziada inaweza kubadilisha raundi nzima.",
    "Kitufe muhimu zaidi ni Cash Out. Bonyeza wakati unataka kuchukua ushindi wa sasa na kusimama. Ikiwa ni Chicken Road asili kutoka InOut Games, sheria hubaki sawa kwenye jukwaa lolote.",
    "Demo ya Chicken Road inapatikana kwenye Fan-Sport — anza huko. Si kwa sababu demo itakufanya ushinde baadaye, bali kwa sababu inakupa muonekano wa kwanza wa kawaida wa mchezo bila shinikizo.",
    "Katika hali ya demo, unacheza na salio la kawaida. Unaweza kuchagua ugumu, kujaribu ukubwa wa dau, kusogeza kuku kwenye barabara, na kujaribu Cash Out katika nyakati tofauti. Ikiwa raundi inaenda vibaya, hakuna kinachotokea kwa pesa yako halisi.",
    "Kucheza kwa pesa halisi ni tofauti. Vitufe ni vile vile na multiplier inakua kwa njia ile ile, lakini hisia hubadilika. Salio lako mwenyewe linapohusika, hata uamuzi mdogo unaweza kuhisi mzito zaidi.",
    "Tumia demo kwenye Fan-Sport kwanza. Jifunze mchezo huko. Hali ya pesa halisi inapaswa kuja tu baada ya kuelewa mechanics na kukubali hatari.",
    "Chicken Road inahisi vizuri kwenye simu. Fungua Fan-Sport, pata Chicken Road, weka dau, na raundi iko tayari kufuatiliwa.",
    "Kwenye simu, kila kitu kinakuwa compact zaidi. Vitufe hubaki wazi, sehemu ya dau ni rahisi kupata, na Cash Out hubaki karibu vya kutosha kutumia bila kutafuta skrini.",
    "Raundi ni fupi, hivyo mchezo unafaa kucheza kwa simu vizuri. Miguso michache inatosha kuanza, kusonga mbele, na kuchukua ushindi ukikataa hatua inayofuata.",
    "Kasi inategemea simu yako, kivinjari, na muunganisho. Kwenye skrini ndogo ni rahisi kubonyeza haraka sana au kuweka dau lisilo sahihi — angalia dau kabla ya Play. Cheza kwenye kivinjari; epuka faili za APK za kubahatisha kutoka tovuti zisizojulikana.",
    "Chicken Road inakupa udhibiti wa kutosha kujenga mtindo wa kucheza, lakini si wa kutosha kuhakikisha ushindi. Hiyo ni muhimu kabla ya kutumia strategy yoyote kwenye Fan-Sport.",
    "Strategy hapa ni mpango tu: ukubwa wa dau, ugumu, na lini unafanya Cash Out. Haifanyi hatua inayofuata iwe salama.",
    "Kosa la kwanza ni kufuatilia multiplier ya juu bila mpango wowote. Amua lengo lako la cash-out kabla raundi huanza.",
    "Usiinue dau baada ya kupoteza. Raundi mbaya haimaanishi raundi inayofuata itakuwa bora. Dau kubwa zaidi hufanya salio kutoweka haraka zaidi tu.",
    "Hardcore inaonekana kuvutia kwa sababu ya multipliers za juu, lakini si hali ya mwanzo. Jaribu Easy au Medium kwanza. Ikiwa demo inapatikana kwenye Fan-Sport, jaribu mbinu yako huko kabla ya pesa halisi.",
    "Mbinu rahisi: chagua ugumu wako, weka dau la kawaida, jua unataka Cash Out lini, na simama ikiwa kikao kinakuwa cha kihisia.",
    "Strategy haitashinda mchezo, lakini inaweza kusimamisha fujo.",
    "Chicken Road bado ni mchezo wa kamari. Inaweza kuonekana rahisi na kama arcade, lakini hatua inayofuata haiwezi kutabiriwa. Usiamini predictors, signals, au ahadi za ushindi uliothibitishwa.",
    "Ukifungua Chicken Road kwenye Fan-Sport, hakikisha uko kwenye tovuti halisi ya Fan-Sport. Kurasa bandia zinaweza kuonekana sawa lakini zipo kuiba logins, data ya kadi, au kusukuma upakuaji wa shaka.",
    "Usiweke data ya malipo kwenye kurasa zinazojifanya kuwa Fan-Sport tu. Epuka faili za APK za kubahatisha kutoka Telegram, matangazo, au tovuti zisizojulikana. Demo ya bure haipaswi kuuliza kadi yako ya benki. Kucheza kwa pesa halisi daima ni hatari — tazama Chicken Road kama burudani, si mapato.",
    "Chicken Road si mchezo wa kawaida wa kasino. Inahisi ya kuingiliana na kama arcade: chagua ugumu, weka dau lako, songa mbele, na uamue lini Cash Out.",
    "Raundi fupi, vitufe wazi, multiplier inayokua, na wazo la hatua moja zaidi — rahisi kwenye kifaa chochote.",
    "Bado, tazama Chicken Road kama burudani. Cheza na mipaka, elewa hatari, na usiweke pesa ambazo hauko tayari kupoteza.",
    "Je, Chicken Road inapatikana kwenye Fan-Sport?",
    "Ndiyo, Chicken Road inapatikana kwenye Fan-Sport kama mojawapo ya michezo ya kasino ya haraka.",
    "Ninawezaje kupata Chicken Road kwenye Fan-Sport?",
    "Fungua Slots na utumie upau wa utafutaji. Andika Chicken Road au utafute kwa mtoa huduma InOut. Wakati mwingine mchezo pia uko chini ya Other.",
    "Je, naweza kucheza Chicken Road kwenye simu?",
    "Ndiyo, Chicken Road inafanya kazi vizuri kutoka simu kwenye kivinjari.",
    "Chicken Road inafanya kazi vipi?",
    "Chagua ugumu, weka dau lako, bonyeza Play, na songa kuku mbele. Kila hatua salama huongeza multiplier. Bonyeza Cash Out wakati wowote kuchukua ushindi wa sasa.",
    "Je, naweza kutabiri matokeo?",
    "Hapana. Chicken Road haiwezi kutabiriwa. Programu na bots zinazoahidi utabiri zinapaswa kuchukuliwa kuwa za shaka.",
    "Je, Chicken Road ni hatari kwa pesa halisi?",
    "Ndiyo. Kucheza kwa pesa halisi daima kuna hatari — weka dau tu unachoweza kumudu kupoteza.",
    "Ugumu gani wanaoanza wanapaswa kuchagua?",
    "Wanaoanza wanapaswa kuanza na Easy. Inatoa nafasi zaidi kujifunza mchezo na inahisi chini ya shambulio kuliko Hard au Hardcore.",
    "Je, naweza kutumia strategies?",
    "Unaweza kutumia strategy kudhibiti kucheza kwako: ukubwa wa dau, lini Cash Out, na kuepuka maamuzi ya kubahatisha. Hakuna strategy inayohakikisha faida — inakusaidia tu kucheza na mpango.",
    "Je, Fan-Sport ni tovuti rasmi ya InOut Games?",
    "Hapana. Fan-Sport si tovuti rasmi ya InOut Games. Ni jukwaa la kasino ambapo Chicken Road inaweza kupatikana kupitia katalogi ya mtoa huduma.",
]

# --- casino #24 Lingala (FR -> LN) ---
LN24 = [
    "Chicken Road na Fan-Sport",
    "Et Fan-Sport",
    "Esika ya kozua Chicken Road na Fan-Sport",
    "Ndeni nini kobeta Chicken Road na Fan-Sport",
    "Demo mpe mbongo ya solo na Fan-Sport",
    "Chicken Road na mobile via Fan-Sport",
    "Ba strategy mpe ba libunga ya mbala na mbala",
    "Sécurité mpe ba riski",
    "Na mokuse",
    "FAQ",
    "Recherche Chicken Road na section Slots ya Fan-Sport",
    "Liste ya ba jeux InOut na Fan-Sport",
    "Interface mobile Chicken Road na Fan-Sport",
    "Vue mobile compacte ya Chicken Road",
    "Multiplicateur oyo ekóma na round ya Chicken Road",
    "Fan-Sport ezali plateforme online oyo e regrouper casino mpe paris sportifs na compte moko. Accès ezali ya nokinoki : slots, live casino, crash, ba jeux instantanés mpe bookmaker ezali na ba section distinctes, sans navigation confuse.",
    "Oyo nalingi na Fan-Sport, ezali interface ya claire. Bato bazui ba jeux na catalogue to recherche, mpe ba titre populaires e mettre mingi liboso.",
    "Mpo na lisano lokola Chicken Road, organisation oyo e compter : e s'intégrer naturellement na catégorie ya ba jeux rapides.",
    "Fan-Sport ezali déjà na structure malamu mpo na type oyo ya titre : accès ya nokinoki mpe interface épurée, idéale mpo na Chicken Road.",
    "Chicken Road ezali pépé ya kozua na Fan-Sport. Fungola casino, kende na Slots, sima salela recherche. Tia Chicken Road mpe fungola bannière ya lisano na fenêtre ya sika.",
    "Okoki mpe koluka na fournisseur : tia InOut mpo na komona ba jeux ya studio, na Chicken Road mpe ba titre InOut mosusu. Parfois lisano ezali mpe na Other, esika Fan-Sport e regrouper ba jeux arcade mpe instantanés.",
    "Nzelá ya mokuse : Slots → Recherche → Chicken Road → fungola bannière. Soki nkombo e bimaka te, meka InOut.",
    "Chicken Road ezali pépé ya lancer na Fan-Sport, ata mpo na essai ya liboso : pona difficulté, beta, advance poule mpe Cash Out liboso ete round e changer mal.",
    "Fungola lisano mpe pona difficulté. Ba débutants : bandá na Easy. Medium ezali plus vif ; Hard mpe Hardcore e correspondre na basali oyo bazali koyeba déjà rythme. Botá mise mpe talá yango liboso ya Play, mingi na mobile. Step ya solo oyo ezali sûr e augmenter multiplicateur ; step moko ya kobakisa ekoki kobongola round mobimba.",
    "Bouton clé ezali Cash Out : zua gain mpe tika. Mibeko ezali moko na plateforme nyonso soki ezali Chicken Road original ya InOut Games.",
    "Demo Chicken Road ezali disponible na Fan-Sport — bandá wana mpo na koyeba lisano sans pression, te mpo na « garantir » gain sima.",
    "Na demo obetaka na solde virtuel : difficulté, mise, avancée ya poule mpe test ya Cash Out. Soki round e changer mal, mbongo ya solo e toucher te.",
    "Mbongo ya solo e changer ambiance : ba bouton mpe multiplicateur moko, kasi décision moko na moko e pèse mingi. Na demo bato bakozwa riski mingi ; ezali esika malamu mpo na komeka mode mpe habitudes sans perte.",
    "Fan-Sport epesi demo : salela yango liboso. Pasa na mbongo ya solo kaka soki omeya mécanique mpe ondimi riski.",
    "Chicken Road e prêter malamu na téléphone : interface ya légère, écran ya monene esengeli te. Fungola Fan-Sport, zua lisano, botá mise mpe lancer round.",
    "Na mobile nyonso ezali plus compact : ba bouton ya clair, champ ya mise visible, Cash Out pene pene. Round mobimba ezali na décision moko : kokende to kotika.",
    "Ba round ezali ya mokuse — parfait mpo na mobile. Ba tap moke ekoki mpo na kobanda, kokende liboso mpe Cash Out soki ondimi step oyo ekolanda.",
    "Fluidité e dépendre na téléphone, navigateur mpe réseau. Na écran ya moke, talá mise mbala ya mibale liboso ya Play. Bina na navigateur ; éviter ba APK ya doute.",
    "Chicken Road e laisser planifier style na yo, sans garantir gain — esengeli koyeba yango liboso ya « strategy » na Fan-Sport.",
    "Strategy awa ezali plan kaka : taille ya mise, difficulté, moment ya Cash Out. E salaka step oyo ekolanda sûr te.",
    "Libunga ya classique : koluka multiplicateur ya monene sans objectif ya sortie. Décide point ya cash out liboso ya round.",
    "Kobakisa mise sima ya perte te : round oyo ekolanda ezali « dû » te. Mise ya monene e vider solde nokinoki.",
    "Hardcore e attirer na multiplicateur, kasi ezali mode ya débutant te. Meka Easy to Medium, mpe demo Fan-Sport liboso ya mbongo ya solo.",
    "Approche ya pépé : difficulté fixée, mise raisonnable, Cash Out prévu, arrêt soki session e kóma émotionnelle.",
    "Strategy e bater lisano te, kasi e éviter chaos.",
    "Chicken Road ezali lisusu jeu d'argent : visuel arcade, résultat imprévisible. Méfiez-vous ya ba prédicteur, signaux, « strategy secrète » to promesse ya gain garanti.",
    "Na Fan-Sport, talá ete ozali na site ya solo. Ba copies frauduleuses e imiter couleur mpe logo mpo na kobumba identifiants, carte to SMS, to kobimisa ba téléchargement ya doute.",
    "Kofuta te na site ya faux Fan-Sport. APK ya Telegram to annonce ya inconnu te. Carte te mpo na demo. Mbongo ya solo ezali na riski : Chicken Road ezali divertissement, te revenu.",
    "Chicken Road ezali slot classique te : interactif, ton arcade. Difficulté, mise, avance, Cash Out — sans manuel molai.",
    "Ba round ya mokuse, ba bouton clairs, multiplicateur oyo ekóma mpe envie ya step moko ya kobakisa : pépé na appareil nyonso.",
    "Traitez yango lokola divertissement, te revenu. Beta na ba limite mpe tia kaka oyo okoki kobunga.",
    "Chicken Road ezali disponible na Fan-Sport ?",
    "Ee, Chicken Road e proposer na Fan-Sport na kati ya ba jeux casino rapides.",
    "Ndeni nini kozua Chicken Road na Fan-Sport ?",
    "Fungola Slots mpe luka Chicken Road to fournisseur InOut. Parfois lisano ezali mpe na Other.",
    "Nakoki kobeta na mobile ?",
    "Ee, Chicken Road esalaka malamu mingi na téléphone via navigateur.",
    "Chicken Road esalaka ndenge nini ?",
    "Pona difficulté, beta, fina Play, advance poule. Step sûr moko na moko e augmenter multiplicateur ; Cash Out e encaisser gain actuel.",
    "Bato bakoki kotá résultat ?",
    "Te. Schéma sûr to step garanti ezali te. Ba app oyo e promettre prédiction ezali ya doute.",
    "Mbongo ya solo ezali na riski ?",
    "Ee. Jeu na mbongo ya solo ezali na riski : beta kaka oyo okoki kobunga.",
    "Difficulté nini mpo na kobanda ?",
    "Bandá na Easy : marge mingi mpo na koyekola, moins agressif que Hard to Hardcore.",
    "Ba strategy esalisaka ?",
    "E structurer mise mpe Cash Out, kasi e garantir profit te — discipline kaka.",
    "Fan-Sport ezali site officiel InOut Games ?",
    "Te. Fan-Sport ezali plateforme casino ; Chicken Road ekoki kozala wana via catalogue ya fournisseur InOut.",
]


def build_casino_18() -> None:
    en = load_segs("casino-18-en-segments.json")
    fr = load_segs("casino-18-fr-segments.json")
    assert len(en) == len(SW18) == len(fr) == len(LN18) == 71
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye MOSTBET: Cheza na Bonus",
        "Cheza Chicken Road kwenye MOSTBET: bonus, RTP, demo, simu na mkakati wa Cash Out kwa mchezo wa Chicken Road.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na MOSTBET: Kobeta mpe Bonus",
        "Bina Chicken Road na MOSTBET: bonus, RTP, demo, mobile mpe strategy ya Cash Out mpo na lisano ya Chicken Road.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "MOSTBET — Chicken Road",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "MOSTBET — Chicken Road",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW18),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, LN18)],
        },
    }
    (OUT / "casino_18.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_18.json: {len(en)} sw + {len(fr)} fr_ln")


def build_casino_24() -> None:
    en = load_segs("casino-24-en-segments.json")
    fr = load_segs("casino-24-fr-segments.json")
    assert len(en) == len(SW24) == len(fr) == len(LN24) == 64
    sw_title, sw_desc = truncate(
        "Chicken Road kwenye FanSport: Cheza na Bonus",
        "Cheza Chicken Road kwenye FanSport: demo, simu, strategy na mwongozo wa kupata mchezo kwenye kasino.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Road na FanSport: Kobeta mpe Bonus",
        "Bina Chicken Road na FanSport: demo, mobile, strategy mpe ndenge ya kozua lisano na casino.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {
                "name": "FANSPORT - Chicken Road",
                "title": sw_title,
                "description": sw_desc,
            },
            "ln": {
                "name": "FANSPORT - Chicken Road",
                "title": ln_title,
                "description": ln_desc,
            },
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW24),
            "fr_ln": [[a, polish_ln(b)] for a, b in pairs_from_lists(fr, LN24)],
        },
    }
    (OUT / "casino_24.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(f"casino_24.json: {len(en)} sw + {len(fr)} fr_ln")


def main() -> int:
    build_casino_18()
    build_casino_24()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
