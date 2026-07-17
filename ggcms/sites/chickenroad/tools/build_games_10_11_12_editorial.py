#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial games_10/11/12.json from EN (+ FR for #12 ln) segment lists."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DL = Path("/home/lenovo/Downloads/02/chickenroad-games")
OUT = TOOLS / "games_sw_ln_data"


def pairs_from_lists(en: list[str], sw: list[str], ln: list[str]) -> dict:
    if not (len(en) == len(sw) == len(ln)):
        raise ValueError(f"length mismatch en={len(en)} sw={len(sw)} ln={len(ln)}")
    return {"sw": [[a, b] for a, b in zip(en, sw)], "ln": [[a, b] for a, b in zip(en, ln)]}


def fr_ln_pairs(fr: list[str], ln: list[str]) -> list[list[str]]:
    if len(fr) != len(ln):
        raise ValueError(f"fr_ln length mismatch fr={len(fr)} ln={len(ln)}")
    return [[a, b] for a, b in zip(fr, ln)]


def write_json(entity_id: int, payload: dict) -> Path:
    path = OUT / f"games_{entity_id}.json"
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    return path


# --- games #10 Chicken Banana ---
G10_META = {
    "sw": {
        "name": "Chicken Banana",
        "title": "Mchezo wa Chicken Banana: Sheria, RTP na Mahali pa Kucheza",
        "description": "Mapitio ya Chicken Banana: sheria, RTP na kasino bora za kucheza Chicken Banana mtandaoni leo.",
    },
    "ln": {
        "name": "Chicken Banana",
        "title": "Lisano Chicken Banana: Mibeko, RTP mpe Esika ya Kobeta",
        "description": "Tala ya Chicken Banana: mibeko, RTP mpe ba casino ya malamu mpo na kobeta Chicken Banana na internet lelo.",
    },
}

G10_EN = json.loads((DL / "games-10-en-segments.json").read_text(encoding="utf-8"))

G10_SW = [
    "&nbsp;",
    "Chicken Banana",
    "Vipengele vya Chicken Banana",
    "Ushindi wa Juu na Mzigo wa Chicken Banana",
    "Mbinu za Msingi za Mchezo wa Chicken Banana",
    "Alama na Thamani",
    "Uanzishaji wa Bonus na Jackpot",
    "Free Spins &mdash; Alama 3 za FS",
    "Mchezo wa Bonus &mdash; Alama 3 za Chest",
    "FAQ",
    "Picha ya skrini ya mchezo wa Chicken Banana wa instant-win",
    "Chicken Banana ni mojawapo ya michezo nyepesi zaidi katika msururu wa Chicken Road. Hata kwa jina tu, inahisi si ya kiseriousi kama ya asili. Kuku, ndizi, raundi za haraka &mdash; mchezo unaelekea wazi kwenye hali ya arcade ya kuchekesha badala ya mtindo wa kawaida wa kasino.",
    "Toleo hili linaweza kuwafaa wachezaji wanaopenda michezo rahisi bila msukumo mkubwa kwenye skrini. Hakuna mpangilio mzito wa slot, hakuna sheria ndefu, hakuna alama ngumu. Wazo ni karibu na mini-mchezo wa haraka: ufungue, uelewe misingi, na ucheze raundi fupi.",
    "Tumeainisha muhtasari kamili wa kiufundi na mbinu zote kuu zilizojengwa ndani ya mchezo katika jedwali la maelezo hapa chini.",
    "Jina la mchezo",
    "Mtoa huduma",
    "InOut Games",
    "Tarehe ya kutolewa",
    "2026-03-26",
    "Kategoria",
    "Instant win / mchezo wa kuchagua kadi",
    "Multiplier ya juu",
    "1,000x bet",
    "Ushindi wa juu wa pesa",
    "5&times;4, 20 cards",
    "Teknolojia",
    "HTML5, JS",
    "Vipengele",
    "Malipo yaliyohakikishwa kila raundi, sanduku za bonus, jackpot zilizowekwa, free spins, uthibitisho wa provably fair",
    "Chicken Banana inabaki na roho ile ile ya jumla ya msururu. Ni angavu, rahisi kuanza, na imejengwa kwa wachezaji wanaofurahia maamuzi ya haraka. Mchezo haujaribu kuonekana premium au wa kiseriousi. Unafanya kazi kwa sababu unahisi wa kawaida na wa kuchekesha kidogo, lakini bado una hatari ya kasino chini.",
    "Kwa wachezaji ambao tayari wanapenda Chicken Road, Chicken Banana inaweza kuwa toleo la kufurahisha upande. Mhusika na hisia ile ile ya arcade, lakini na mada laini na ya kucheza zaidi.",
    "Bado, ni mchezo mzuri kwa mashabiki wa Chicken. Ikiwa pesa halisi inahusika, hatari ni ya kweli pia. Anza na demo, angalia mbinu, na ucheze na salio halisi tu ukiwa unaelewa unachofanya.",
    "Chicken Banana inakuwezesha kucheza kutoka dau dogo sana au kwenda juu zaidi. Mzigo wa dau unaanza $0.01 na unapanda hadi $200 kwa kila raundi.",
    "Hii ndiyo sababu dau kubwa si daima bora katika Chicken Banana. Unaweza kuhatarisha pesa zaidi, lakini malipo ya juu bado yamefungwa na kikomo.",
    "Kwa wachezaji wanaojali thamani safi ya multiplier, dau hadi $20 huwa na maana zaidi. Juu ya hapo, kikomo cha ushindi wa juu huanza kukata matokeo ya juu.",
    "Wazo rahisi:",
    "dau dogo huruhusu 1,000x kamili kufanya kazi;",
    "$20 inafaa kikomo kikamilifu;",
    "dau kubwa zinapiga dari mapema sana.",
    "Chicken Banana haichezi kama slot ya reel ya kawaida. Hakuna mlolongo mrefu wa kuzungusha ambapo unasubiri tu alama ziache. Mchezo umejengwa karibu na mtindo wa haraka wa kuchagua na kulinganisha, hivyo matokeo yanakuja haraka na raundi haichelewi.",
    "Wazo ni wa moja kwa moja zaidi: chagua dau lako, anza raundi, na uache mchezo utatue matokeo karibu papo hapo. Hii inafanya Chicken Banana ihisi karibu na mchezo wa kasino wa instant kuliko slot ya zamani.",
    "Hisabati nyuma ya mchezo bado ni muhimu. Matokeo hayategemei kukisia au kusoma skrini. Matokeo yanashughulikiwa na injini ya mchezo, na kila raundi ina hatari yake. Muundo wa haraka unafanya mchakato uwe wa haraka tu.",
    "Hii ndiyo sababu udhibiti wa bankroll unahitaji hapa. Chicken Banana inaweza kuhisi nyepesi na rahisi, lakini raundi za haraka pia zinamaanisha salio linaweza kusogea haraka. Weka dau lako kabla ya kuanza, epuka kubofya kwa hisia, na usichukulie mchezo kama kitu unachoweza kutabiri.",
    "Malipo ya Multiplier",
    "Matokeo Halisi kwa Dau la $10",
    "Peeled Banana",
    "$1 Return (-$9.00)",
    "Fried Chicken",
    "$2 Return (-$8.00)",
    "Two Bananas",
    "$5 Return (-$5.00)",
    "Small Chicken Bucket",
    "$10 Return (Break Even)",
    "Three Bananas",
    "$15 Return (+$5.00)",
    "Large Chicken Bucket",
    "$20 Return (+$10.00)",
    "Banana Cluster",
    "$40 Return (+$30.00)",
    "$100 Return (+$90.00)",
    "Chicken Banana ina vichocheo viwili vikuu vya bonus, na vyote hufanya kazi kupitia alama za scatter. Wazo ni rahisi: pata scatter tatu zinazolingana na mchezo unaingia kwenye mojawapo ya njia za bonus.",
    "Ukifunua alama 3 za FS, mchezo hutoa idadi ya nasibu ya raundi za bure &mdash; kutoka 1 hadi 10.",
    "Sehemu nzuri ni kwamba free spins zinaweza kuongezwa. Alama 3 zaidi za FS zikionekana wakati wa raundi ya bure inayoendelea, spins za ziada huongezwa kwenye foleni. Hivyo feature haiishi kila wakati baada ya kundi la kwanza. Wakati mwingine inaweza kuendelea, jambo linalofanya raundi iwe ya kuvutia zaidi.",
    "Alama za Chest hufungua mchezo wa bonus wa mtindo wa jackpot. Hii si feature ya kawaida ya malipo ya mstari. Bonus ikianza, mchezo unaingia kwenye tawi tofauti ambapo malipo yanategemea multiplier za jackpot zilizowekwa.",
    "Mega Jackpot ndiyo matokeo ya juu hapa. Ikishuka, raundi hulipa multiplier kamili ya 1,000x, isipokuwa kikomo cha ushindi wa juu cha mchezo&rsquo; kikate malipo.",
    "Mpangilio huu wa bonus unafanya Chicken Banana ihisi si kama mchezo wa kawaida wa spin-and-wait. Huchagui tu kuangalia ushindi mdogo. Unatafuta mpangilio sahihi wa scatter unaoweza kufungua free spins au kutuma raundi moja kwa moja kwenye feature ya jackpot. Weka dau chini ya udhibiti, hasa ukiwa unafuatilia vichocheo vya bonus.",
    "Kiwango cha Jackpot",
    "Multiplier Iliyohakikishwa",
    "Malipo kwa Dau la $10",
    "Chicken Banana ni nini? Chicken Banana ni mchezo wa haraka wa instant-win wa 5&times;4 kutoka InOut Games. Hauhisi kama slot ya reel ya kawaida. Badala ya kusubiri reels zizunguke, unacheza kwenye gridi ya kadi 20 na kutafuta alama 3 zinazolingana. Maelezo muhimu: mechi haimaanishi kila wakati faida. Baadhi ya alama zinaweza kulipa chini ya dau lako, hivyo raundi inaweza kushinda kiufundi na bado kukuacha chini.",
    "Chicken Banana ina RTP gani? Mchezo unafanya kazi kwa RTP ya 96%. Hiyo ni nambari ya muda mrefu, si ahadi kwa kikao kimoja. Unaweza kuwa na mfululizo mzuri, mkavu, au kati ya hizo. RTP huanza kuwa na maana tu baada ya idadi kubwa ya raundi.",
    "Je, kuna bonus katika Chicken Banana? Ndiyo. Kuna vichocheo viwili vikuu vya bonus: Free Spins kupitia alama 3 za FS, na mchezo wa bonus wa chest kupitia alama 3 za Chest.",
    "Naweza kupata free spins ngapi? Feature huanza na kiasi cha nasibu kutoka raundi 1 hadi 10 za bure. Inaweza pia kujirudia. Alama zaidi za FS zikionekana wakati raundi za bure tayari zinaendelea, mchezo huongeza spins za ziada badala ya kumaliza feature haraka sana.",
    "Je, kuna toleo la demo? Ndiyo. Chicken Banana ina hali ya demo na salio kubwa la kawaida, kwa kawaida mikopo 1,000,000. Tumia kabla ya pesa halisi. Mchezo una muundo tofauti kutoka slot za kawaida, hivyo inafaa kujaribu gridi ya kadi, malipo, vichocheo vya bonus, na feature ya jackpot kwanza.",
    "Chicken Banana inatofautianaje na slot? Tofauti kubwa ni muundo. Hakuna reels za kawaida zinazozunguka au paylines. Chicken Banana hutumia gridi ya instant-win ya kadi 20. Kila raundi imejengwa karibu na kutafuta mechi ya alama 3.",
]

G10_LN = [
    "&nbsp;",
    "Chicken Banana",
    "Ba eloko ya Chicken Banana",
    "Gain ya likolo mpe interval ya pari ya Chicken Banana",
    "Mekaniki ya liboso ya lisano Chicken Banana",
    "Ba symbole mpe valeur",
    "Activation ya bonus mpe Jackpot",
    "Free Spins &mdash; 3 symbole FS",
    "Lisano ya bonus &mdash; 3 symbole Chest",
    "FAQ",
    "Capture ya ecran ya lisano Chicken Banana instant-win",
    "Chicken Banana ezali moko ya ba lisano ya pete na series ya Chicken Road. Na nkombo moko, ezali pete koleka original. Nkoko, banane, ba round ya mbangu &mdash; lisano etambola na mood ya arcade ya esengo koleka style ya casino ya classique.",
    "Version oyo ekoki kolonga basali oyo balingi ba lisano ya pépé na tention ya moke na ecran. Slot ya makasi te, mibeko ya molai te, ba symbole ya compliqué te. Idea ezali pene na mini-lisano ya mbangu: fungola, yeba base, mpe beta round moke.",
    "Tosalaki breakdown ya technique mobimba mpe mekaniki nionso ya lisano na tableau ya specification na nse.",
    "Nkombo ya lisano",
    "Provider",
    "InOut Games",
    "Date ya publication",
    "2026-03-26",
    "Category",
    "Instant win / lisano ya kozua carte",
    "Multiplier ya likolo",
    "1,000x bet",
    "Gain ya mbongo ya likolo",
    "5&times;4, 20 cards",
    "Technology",
    "HTML5, JS",
    "Ba eloko",
    "Payout garanti round nionso, ba chest ya bonus, jackpot fixe, free spins, verification provably fair",
    "Chicken Banana ebatelaka spirit ya liboso ya series. Ezali pole, pépé mpo na kobanda, mpe esalemi mpo na basali oyo balingi ba decision ya mbangu. Lisano e lingi te kolook premium to sérieux. Esalaka mpo na ete ezali casual mpe moke ya esengo, kasi riski ya casino ezali awa na nse.",
    "Mpo na basali oyo bazali koyeba Chicken Road, Chicken Banana ekoki kozala version ya side ya esengo. Personnage moko mpe feeling ya arcade, kasi na theme ya pete mpe ya koseka.",
    "Kasi ezali lisusu lisano ya malamu mpo na ba fan ya Chicken. Soki mbongo ya solo ezali, riski ezali solo mpe. Banda na demo, talá mekaniki, mpe beta na solde ya solo kaka soki oyebi oyo ozali kosala.",
    "Chicken Banana epesi yo kobeta na pari ya moke mingi to kende likolo. Interval ya pari ebandi na $0.01 mpe ekomi $200 na round.",
    "Yango wana pari ya monene ezali malamu te ntango nyonso na Chicken Banana. Okoki kobeta riski ya mbongo mingi, kasi payout ya likolo ezali nase na cap.",
    "Mpo na basali oyo balingi valeur ya multiplier ya pete, pari tii $20 ezali malamu mingi. Likolo ya yango, limit ya gain ya likolo ebandi kokata resultat ya likolo.",
    "Idea ya pépé:",
    "pari ya moke epesi 1,000x mobimba kosala;",
    "$20 ekokani na cap;",
    "pari ya monene ekouta plafond liboso.",
    "Chicken Banana e beta te lokola slot ya reel ya classique. Sequence ya spin ya molai te oyo ozali kaka kozela ba symbole. Lisano esalemi na style ya pick-and-match ya mbangu, resultat ezali mbangu mpe round e lingi te.",
    "Idea ezali direct: pona pari na yo, banda round, mpe tika lisano etatola resultat mbala moko. Yango esalelaka Chicken Banana ezala pene na lisano ya casino instant koleka slot ya kala.",
    "Math na nsima ya lisano ezali ntina. Resultat ezali te na guess to na kotanga ecran. Resultat esalemi na engine ya lisano, mpe round nionso ezali na riski na yango. Format ya mbangu esalelaka processus mbangu.",
    "Yango wana contrôle ya bankroll ezali na ntina awa. Chicken Banana ekoki kozala pete, kasi ba round ya mbangu elingi kolinga solde ekobaluka mbangu. Botia pari liboso ya kobanda, koboya ba click ya emotion, mpe kotala lisano lokola eloko oyo okoki kotya te.",
    "Payout ya multiplier",
    "Resultat net na pari ya $10",
    "Peeled Banana",
    "$1 Return (-$9.00)",
    "Fried Chicken",
    "$2 Return (-$8.00)",
    "Two Bananas",
    "$5 Return (-$5.00)",
    "Small Chicken Bucket",
    "$10 Return (Break Even)",
    "Three Bananas",
    "$15 Return (+$5.00)",
    "Large Chicken Bucket",
    "$20 Return (+$10.00)",
    "Banana Cluster",
    "$40 Return (+$30.00)",
    "$100 Return (+$90.00)",
    "Chicken Banana ezali na ba trigger ya bonus mibale ya liboso, mibale esalaka na ba symbole scatter. Idea ezali pépé: zua ba scatter misato oyo ezali ndenge moko mpe lisano ekota na moko ya ba chemin ya bonus.",
    "Soki ofungoli 3 symbole FS, lisano epesi motango ya hasard ya ba round ya liboso &mdash; kowuta 1 tii 10.",
    "Part ya malamu ezali ete free spins ekoki kobakisama. Soki 3 symbole FS mosusu ebimaka na round ya liboso oyo ezali actif, ba spin ya sika ebakisami na queue. Feature e suka te ntango nyonso na batch ya liboso. Parfois ekoki kokende, oyo esalelaka round ezala interesting.",
    "Ba symbole Chest efungolaka lisano ya bonus ya style jackpot. Ezali feature ya line-pay ya solo te. Bonus ekobanda, lisano ekota na branche ya kati oyo payout ezali na ba multiplier ya jackpot fixe.",
    "Mega Jackpot ezali resultat ya likolo awa. Soki ekiti, round efuta multiplier mobimba ya 1,000x, longola soki cap ya gain ya likolo ya lisano&rsquo; ekata payout.",
    "Setup oyo ya bonus esalelaka Chicken Banana ezala pete koleka lisano ya spin-and-wait ya solo. Ozali kotala kaka ba gain ya moke te. Ozali koluka setup ya scatter oyo ekoki kofungola free spins to kotinda round mbala moko na feature ya jackpot. Batela pari na contrôle, mingi soki ozali kolanda ba trigger ya bonus.",
    "Niveau ya Jackpot",
    "Multiplier garanti",
    "Payout na pari ya $10",
    "Chicken Banana ezali nini? Chicken Banana ezali lisano ya instant-win ya mbangu 5&times;4 ya InOut Games. Ezali lokola slot ya reel ya solo te. Na esika ya kozela ba reel, obeta na grid ya carte 20 mpe otala ba symbole 3 oyo ezali ndenge moko. Detail ya ntina: match elingi kolonga mbongo te ntango nyonso. Ba symbole mosusu ekoki kofuta na nse ya pari na yo, yango wana round ekoki kogagner na technique mpe kozela yo na nse.",
    "RTP ya Chicken Banana ezali boni? Lisano esalaka na RTP 96%. Ezali nombre ya long terme, promise mpo na session moko te. Okoki kozala bon run, dry run, to eloko na kati. RTP ebandi kozala na sensi kaka na motango monene ya ba round.",
    "Ezali na bonus na Chicken Banana? Ee. Ezali na ba trigger ya bonus mibale ya liboso: Free Spins na 3 symbole FS, mpe lisano ya bonus ya chest na 3 symbole Chest.",
    "Nakoki kozua ba free spins boni? Feature ebandi na motango ya hasard kowuta 1 tii 10 round ya liboso. Ekoki retrigger mpe. Soki ba symbole FS mosusu ebimaka na round ya liboso oyo ezali actif, lisano ebakisa ba spin ya sika na esika ya kosilisa feature mbangu.",
    "Ezali na version demo? Ee. Chicken Banana ezali na mode demo na solde ya virtual ya monene, mingi ntango 1,000,000 credits. Sala yango liboso ya mbongo ya solo. Lisano ezali na structure ya different na ba slot ya classique, yango wana esengeli komeka grid ya carte, payout, trigger ya bonus, mpe feature ya jackpot liboso.",
    "Chicken Banana e different na slot ndenge nini? Difference ya likolo ezali format. Ba reel ya spin to payline ya classique ezali te. Chicken Banana esalelaka grid instant-win ya carte 20. Round nionso esalemi na koluka match ya symbole 3.",
]

# --- games #11 Chicken Shoot ---
G11_META = {
    "sw": {
        "name": "Chicken Shoot",
        "title": "Mchezo wa Chicken Shoot: Cheza kwa Pesa Halisi",
        "description": "Mapitio ya Chicken Shoot: jinsi ya kucheza, RTP na kasino bora zinazotoa Chicken Shoot kwa pesa halisi.",
    },
    "ln": {
        "name": "Chicken Shoot",
        "title": "Lisano Chicken Shoot: Bina na Mbongo ya Solo",
        "description": "Tala ya Chicken Shoot: ndenge ya kobeta, RTP mpe ba casino ya malamu oyo epesi Chicken Shoot na mbongo ya solo.",
    },
}

G11_EN = json.loads((DL / "games-11-en-segments.json").read_text(encoding="utf-8"))

G11_SW = [
    "Chicken Shoot",
    "Ushindi wa Juu wa Chicken Shoot",
    "Chagua Dau Kabla ya Kila Raundi",
    "Chagua Lengo",
    "Aina za Malengo",
    "Chicken Shoot Mtandaoni — Kupiga Risasi na Raundi",
    "Hali ya Mkono",
    "Auto Game",
    "FAQ",
    "Picha ya skrini ya mchezo wa arcade wa Chicken Shoot mtandaoni",
    "Mchezo wa Chicken Shoot na malengo ya kuku yanayosogea",
    "Hatua ya kupiga risasi ya Chicken Shoot kwenye skrini ya shamba",
    "Chicken Shoot ni mojawapo ya michezo yenye vitendo zaidi katika ulimwengu wa Chicken Road. Inabaki na mtindo ule ule wa kuku wa kuchekesha, lakini hisia ni tofauti na mchezo wa asili wa kuvuka barabara. Hapa jina tayari linakuambia unachotarajia: harakati za haraka, mbinu za kupiga risasi, na hali ya arcade yenye shughuli zaidi.",
    "Toleo hili linaweza kuwafaa wachezaji wanaotaka kitu chenye nguvu zaidi. Si kusogea hatua kwa hatua tu, si kusubiri tu wakati sahihi wa cash out, bali mchezo unaohisi karibu na shooter mdogo wa arcade. Mada ya kuku inabaki, lakini mpangilio unakuwa mkali zaidi.",
    "Chicken Shoot inahisi kama ndugu wa kelele wa msururu wa Chicken Road. Ulimwengu ule ule wa kuku, lakini hali si tena kuhusu kuvuka barabara kwa utulivu. Hapa kila kitu kinasikika hai zaidi kutoka jina pekee — kupiga risasi, harakati, majibu ya haraka.",
    "Toleo hili ni kwa wachezaji wanaotaka vitendo zaidi kwenye skrini. Si hatua, subiri, cash out tu. Mchezo ni shooter mdogo wa arcade — unahisi wa haraka na wa fujo kidogo.",
    "Mpangilio ni tofauti — si sana kuhusu msukumo wa polepole bali kuhusu majibu. Hii inafanya iwe mchezo mzuri wa upande kwa wachezaji ambao tayari wanajua Chicken Road na wanataka kitu chenye tempo tofauti.",
    "Kinachofanya kazi hapa ni kitu kile kile kinachofanya kazi katika msururu mzima: mchezo hauonekani kama slot ya kawaida. Hakuna reels, hakuna gridi nzito ya alama, hakuna maelezo marefu kabla ya kuanza. Unafungua na unaelewa wazo haraka.",
    "Jaribu mchezo wa mtindo wa shooter uone jinsi unavyofanya kazi kwa vitendo — ni uzoefu tofauti kabisa kutoka muundo wa kuvuka barabara.",
    "Chicken Shoot inaweza kulipa hadi $20,000 katika build hii. Hapa raundi moja ni risasi moja. Unachagua dau, unachagua lengo, unapiga, na unapata matokeo karibu papo hapo.",
    "Kasi hiyo ndiyo inayofanya mchezo uwe wa kufurahisha, lakini pia wa hatari. Risasi chache zinaweza kutokea haraka sana, na ukishindwa kuangalia ukubwa wa dau, jumla ya kiasi kilichowekwa dau inakua haraka kuliko inavyoonekana.",
    "Kabla ya kila risasi, unachagua kiasi unachotaka kuhatarisha. Katika Chicken Shoot, raundi 1 = risasi 1, hivyo dau linatumika tu kwa hatua inayofuata.",
    "Mtiririko ni rahisi:",
    "weka dau;",
    "chagua lengo la kuku;",
    "piga risasi;",
    "risasi ikifanikiwa, malipo hutumia multiplier ya lengo hilo;",
    "risasi ikishindwa, raundi inaisha bila malipo.",
    "Hii inafanya mchezo uwe rahisi kusoma. Unajua kila wakati kiasi kilicho hatarini kabla ya kubofya kitufe. Hakuna mlolongo uliofichwa, hakuna mzunguko mrefu wa spin, hakuna kusubiri hatua kadhaa zimalizike.",
    "Bado, hiyo haimaanishi mchezo ni salama. Raundi ni fupi, na raundi fupi zinaweza kuwafanya wachezaji kubofya haraka kuliko walivyopanga. Weka dau kwa makini kabla ya kila risasi.",
    "Baada ya kuweka dau, unachagua unachopiga risasi. Kuku husogea kwenye skrini, na kila lengo linaweza kuwa na multiplier yake.",
    "Kuna njia mbili za kucheza:",
    "Uchaguzi wa mkono — gusa kuku unayotaka kupiga risasi.",
    "Uchaguzi wa kiotomatiki — tumia kitufe cha SPIN au mipangilio ya auto kupiga risasi bila kuchagua kila lengo mwenyewe.",
    "Kucheza kwa mkono hutoa udhibiti zaidi wa mpangilio. Unaweza kusubiri lengo bora, kupuuza multiplier za chini, au kusimama kati ya risasi. Hali ya auto ni ya haraka zaidi, lakini pia inaweza kusogeza salio haraka ukishindwa kuwa makini.",
    "Lengo unalochagua linahitaji kwa sababu multiplier inabadilisha kurudi inayowezekana. Multiplier za chini zinahisi tulivu zaidi. Multiplier za juu zinaonekana bora, lakini kwa kawaida huja na uzoefu wa kutokuwa thabiti zaidi.",
    "Chicken Shoot ina kuku tofauti warukao kwenye shamba, na kila mmoja anaweza kubeba multiplier tofauti.",
    "Masafa ya msingi ya multiplier huenda kutoka 1.1x hadi 48x. Kwa mbinu ya ukuaji wa kuendelea, thamani ya lengo inaweza kupanda zaidi, hadi 120x.",
    "Sifa",
    "Masafa ya Msingi ya Multiplier",
    "1.1x to 48x",
    "Uwezo wa Juu wa Ukuaji",
    "Hadi 120x",
    "Tabia ya Skrini",
    "Kuku husogea kwa nasibu",
    "Njia ya Uchaguzi",
    "Gusa kwa mkono au kulenga kiotomatiki",
    "Mchezaji anaona malengo yanayosogea kwenye skrini, lakini malipo yanategemea kuku uliochaguliwa na matokeo ya risasi. Hiyo ndiyo kiini cha mchezo: chagua lengo, chukua hatari, na uone kama risasi inalipa.",
    "Mzunguko wa msingi wa Chicken Shoot ni mfupi sana: weka dau, subiri malengo, piga risasi, na upate matokeo.",
    "Risasi ikifanikiwa, kuku hulipuka na dau linazidishwa na mgawo wa lengo. Risasi ikishindwa, raundi inaisha bila malipo.",
    "Kwa sababu kila raundi ni ya haraka sana, Chicken Shoot inahisi haraka kwenye majukwaa ya kasino.",
    "Hali ya mkono inakupa udhibiti wa kila risasi. Gusa moja linamaanisha raundi moja. Unachagua lengo, wakati, na lini uache.",
    "Hali hii ni bora kwa wachezaji wanaotaka kufikiria kati ya risasi badala ya kuacha mchezo uendelee haraka sana.",
    "Hali ya mkono inakupa nafasi ya:",
    "kuchagua kuku halisi kwenye skrini;",
    "kusubiri multiplier unayopendelea;",
    "kubadilisha dau kati ya raundi;",
    "kusimama baada ya hasara;",
    "kuepuka kucheza kwa rapid-fire kiotomatiki.",
    "Auto Game ni chaguo la haraka zaidi. Mipangilio ikishajiandaa, mchezo unaweza kuendelea kupiga risasi kiotomatiki hadi mlolongo uishe au mchezaji auisitishe.",
    "Hali hii ni rahisi, lakini inahitaji udhibiti zaidi. Kwa kuwa raundi za Chicken Shoot tayari ni fupi, autoplay inaweza kupitia risasi nyingi haraka sana.",
    "Kabla ya kutumia Auto Game, angalia dau, mipangilio ya lengo, na vikomo vyovyote vinavyopatikana. Usiifungue tu uone kinachotokea. Hiyo ndiyo kawaida jinsi wachezaji wanavyopoteza mfuatilio wa kikao.",
    "Hali ya auto ni bora kutumia na dau ndogo na vikomo wazi. Salio likianza kusogea haraka sana, isitishe kwa mkono na urudi kwenye kucheza polepole.",
    "Je, Chicken Shoot ni slot? Si sana. Chicken Shoot inahisi zaidi kama mchezo wa instant wa arcade. Hakuna reels zinazozunguka au paylines. Unaweka dau, unachagua kuku, unapiga risasi, na unapata matokeo mara moja.",
    "Kiasi gani naweza kuweka dau? Risasi moja ni raundi moja, hivyo kila unapopiga, kiasi hicho cha dau kiko hatarini. Raundi ikimalizika, unaweza kubadilisha dau tena.",
    "Je, Chicken Shoot ni multiplayer? Hapana. Chicken Shoot ni mchezo wa instant wa mchezaji mmoja.",
    "Naweza kuangalia kama raundi ilikuwa ya haki? Ndiyo. Chicken Shoot ina zana za Provably Fair ndani ya mchezo. Baada ya raundi, wachezaji wanaweza kuangalia matokeo kupitia mfumo wa uthibitisho.",
    "Je, kuna bonus au jackpot? Chicken Shoot haitumii bonus za slot za kawaida au raundi za jackpot. Mchezo umejengwa karibu na kupiga risasi, multiplier za lengo, hali ya Auto Game, na Progressive Win Growth.",
    "Auto Game inafanya kazi vipi? Katika Auto Game, unaweza kuchagua kutoka aina 1 hadi 8 za malengo. Baada ya hapo, mchezo unaendelea kupiga risasi kwenye kuku waliochaguliwa hadi idadi iliyowekwa ya risasi imalizike au uisitishe kwa mkono.",
    "Je, Chicken Shoot inafanya kazi kwenye simu? Ndiyo. Chicken Shoot inafanya kazi kupitia HTML5 kwenye vivinjari vya simu vya kisasa na ndani ya programu za kasino zinazoshirikiana.",
    "Je, Chicken Shoot ni nzuri kwa vikao virefu? Si mchezo wa kikao kirefu kweli. Ni wa haraka — risasi moja, matokeo papo hapo, risasi inayofuata. Hiyo ni nzuri ukipenda mchezo wa haraka, lakini pia inamaanisha unapaswa kuangalia salio lako kwa makini.",
    "Adjusted variance inamaanisha nini? Inamaanisha mchezo haujafungwa kwenye mfano mmoja wa hatari. Chicken Shoot imejengwa kutoa hisia ya hatari-na-zawadi inayobadilika, kulingana na uchaguzi wa lengo, multiplier, na hali ya kucheza.",
    "Je, Chicken Shoot imeunganishwa na Chicken Road? Ndiyo. Chicken Shoot ni sehemu ya ulimwengu wa Chicken Road kutoka InOut Games, lakini inatumia mbinu za shooter badala ya muundo wa barabara hatua kwa hatua.",
]

G11_LN = [
    "Chicken Shoot",
    "Gain ya likolo ya Chicken Shoot",
    "Pona pari liboso ya round nionso",
    "Pona cible",
    "Ba type ya cible",
    "Chicken Shoot Online — Tir mpe ba round",
    "Mode manuel",
    "Auto Game",
    "FAQ",
    "Capture ya ecran ya lisano Chicken Shoot arcade online",
    "Gameplay ya Chicken Shoot na ba cible ya nkoko oyo ezali kotambola",
    "Action ya tir ya Chicken Shoot na ecran ya farm",
    "Chicken Shoot ezali moko ya ba lisano ya action mingi na mokili ya Chicken Road. Ebatelaka style ya nkoko ya esengo moko, kasi feeling ezali different na lisano ya liboso ya kovuka nzela. Awa nkombo epesi kolobela oyo o zela: movement ya mbangu, mekaniki ya tir, mpe mood ya arcade ya active.",
    "Version oyo ekoki kolonga basali oyo balingi eloko ya dynamic. Kozala kaka kotambola na step te, kozela moment ya cash out te, kasi lisano oyo ezali pene na shooter ya moke ya arcade. Theme ya nkoko ezali, kasi rhythm ezali makasi.",
    "Chicken Shoot ezali lokola cousin ya bruit na series ya Chicken Road. Mokili ya nkoko moko, kasi mood ezali te kovuka nzela na calme. Awa eloko nionso ezali active banda na nkombo moko — tir, movement, reaction ya mbangu.",
    "Version oyo ezali mpo na basali oyo balingi action mingi na ecran. Step, kozela, cash out kaka te. Lisano ezali shooter ya moke ya arcade — ezali mbangu mpe moke ya chaos.",
    "Rhythm ezali different — ezali moke na pression ya molai mpe mingi na reaction. Yango esalelaka yango lisano ya side ya malamu mpo na basali oyo bazali koyeba Chicken Road mpe balingi eloko na tempo ya different.",
    "Oyo esalaka awa ezali eloko moko oyo esalaka na series mobimba: lisano e look te lokola slot ya classique. Ba reel te, grid ya symbole ya makasi te, explication ya molai liboso ya kobanda te. Ofungola mpe oyebi idea mbangu.",
    "Leka lisano ya style shooter mpo na kotala ndenge esalaka na pratique — ezali experience ya solo different na format ya kovuka nzela.",
    "Chicken Shoot ekoki kofuta tii $20,000 na build oyo. Awa round moko ezali tir moko. Pona pari, pona cible, tire, mpe zua resultat mbala moko.",
    "Vitesse yango wana esalelaka lisano ezala esengo, kasi riski mpe. Ba tir moke ekoki kosala mbangu, mpe soki otala te taille ya pari, motango ya pari total ekobaluka mbangu koleka oyo ezali komonana.",
    "Liboso ya tir nionso, pona motango oyo olingi kobeta riski. Na Chicken Shoot, 1 round = 1 tir, yango wana pari esalelaka kaka action oyo ekolanda.",
    "Flow ezali pépé:",
    "botia pari;",
    "pona cible ya nkoko;",
    "tire;",
    "soki tir elongi, payout esalelaka multiplier ya cible wana;",
    "soki tir e echoue, round esuka na payout te.",
    "Yango esalelaka lisano pépé mpo na kotanga. Oyebi ntango nyonso motango oyo ezali na riski liboso ya kopresser bouton. Sequence ya caché te, cycle ya spin ya molai te, kozela ba étape ebele te.",
    "Kasi yango elingi koloba te ete lisano ezali safe. Ba round ezali moke, mpe ba round ya moke ekoki kosala basali click mbangu koleka oyo baplanifie. Botia pari na attention liboso ya tir nionso.",
    "Na nsima ya pari, pona oyo okotaka tir. Ba nkoko batambolaka na ecran, mpe cible nionso ekoki kozala na multiplier na yango.",
    "Ezali na ndenge mibale ya kobeta:",
    "Selection manuelle — tap nkoko oyo olingi kotira.",
    "Selection automatique — sala na bouton SPIN to ba setting ya auto mpo na kotira na kozanga kopona cible nionso.",
    "Kobeta na maboko epesi contrôle mingi na rhythm. Okoki kozela cible ya malamu, koboya multiplier ya moke, to kozela kati ya ba tir. Mode auto ezali mbangu, kasi ekoki kosala solde ekobaluka mbangu soki ozali careful te.",
    "Cible oyo oponi ezali na ntina mpo na ete multiplier e change retour oyo ekoki. Multiplier ya moke ezali calme. Multiplier ya likolo e look malamu, kasi mingi ntango ezali na experience ya volatile.",
    "Chicken Shoot ezali na ba nkoko ya different oyo batambolaka na farm, mpe nionso ekoki kozala na multiplier ya different.",
    "Range ya multiplier ya base ekomi kowuta 1.1x tii 48x. Na mekaniki ya progressive growth, valeur ya cible ekoki kobanda likolo, tii 120x.",
    "Caractéristique",
    "Range ya multiplier ya base",
    "1.1x to 48x",
    "Potentiel ya likolo ya croissance",
    "Tii 120x",
    "Comportement ya ecran",
    "Ba nkoko batambolaka na hasard",
    "Méthode ya sélection",
    "Tap manuel to ciblage automatique",
    "Mosali atali ba cible oyo batambolaka na ecran, kasi payout etalelaka nkoko oyo eponami mpe resultat ya tir. Yango wana core ya lisano: pona cible, beta riski, mpe tala soki tir efuti.",
    "Loop ya liboso ya Chicken Shoot ezali moke mingi: botia pari, zela ba cible, tire, mpe zua resultat.",
    "Soki tir elongi, nkoko e explose mpe pari e multiply na coefficient ya cible. Soki tir e echoue, round esuka na payout te.",
    "Mpo na ete round nionso ezali mbangu, Chicken Shoot ezali mbangu na ba plateforme ya casino.",
    "Mode manuel epesi yo contrôle na tir nionso. Tap moko elingi koloba round moko. Pona cible, timing, mpe tango ya kozela.",
    "Mode oyo ezali malamu mpo na basali oyo balingi kofikiri kati ya ba tir na esika ya kotala lisano ekobeta mbangu mingi.",
    "Mode manuel epesi yo espace mpo na:",
    "kopona nkoko ya solo na ecran;",
    "kozela multiplier oyo olingi;",
    "kobongola pari kati ya ba round;",
    "kozela nsima ya perte;",
    "koboya rapid-fire automatique.",
    "Auto Game ezali option ya mbangu. Soki ba setting esili, lisano ekoki kotira automatiquement tii sequence esuka to mosali a stop.",
    "Mode oyo ezali pratique, kasi esengeli contrôle mingi. Mpo na ete ba round ya Chicken Shoot ezali moke, autoplay ekoki kobeta ba tir ebele mbangu.",
    "Liboso ya kosalela Auto Game, talá pari, ba setting ya cible, mpe ba limit oyo ezali. Kobanda yango kaka mpo na kotala oyo ekobima te. Yango ndenge basali babungaka track ya session.",
    "Mode auto ezali malamu na pari ya moke mpe limit ya polele. Soki solde ebandi kobaluka mbangu, stop yango na maboko mpe zonga na kobeta ya molai.",
    "Chicken Shoot ezali slot? Te vraiment. Chicken Shoot ezali pene na lisano instant ya arcade. Ba reel to payline ezali te. Botia pari, pona nkoko, tire, mpe zua resultat mbala moko.",
    "Nakoki kobeta boni? Tir moko ezali round moko, yango wana chaque fois o tire, pari wana ezali na riski. Round esuka, okoki kobongola pari lisusu.",
    "Chicken Shoot ezali multijoueur? Te. Chicken Shoot ezali lisano instant ya mosali moko.",
    "Nakoki kotya soki round ezalaki fair? Ee. Chicken Shoot ezali na ba outil Provably Fair na kati ya lisano. Nsima ya round, basali bakoki kotya resultat na système ya verification.",
    "Ezali na bonus to jackpot? Chicken Shoot esalelaka te bonus ya slot ya classique to round ya jackpot. Lisano esalemi na tir, multiplier ya cible, mode Auto Game, mpe Progressive Win Growth.",
    "Auto Game esalaka ndenge nini? Na Auto Game, okoki kopona kowuta 1 tii 8 type ya cible. Na nsima, lisano e continuer kotira ba nkoko oyo eponami tii motango ya tir esili to o stop na maboko.",
    "Chicken Shoot esalaka na mobile? Ee. Chicken Shoot esalaka na HTML5 na ba navigateur mobile ya sika mpe na ba app ya casino partenaire.",
    "Chicken Shoot ezali malamu mpo na ba session ya molai? Ezali lisusu te lisano ya session ya molai. Ezali mbangu — tir moko, resultat instant, tir oyo elandi. Ezali malamu soki olingi gameplay ya mbangu, kasi esengeli kotala solde na yo na attention.",
    "Adjusted variance elingi koloba nini? Elingi koloba lisano e lock te na modèle ya riski moko. Chicken Shoot esalemi mpo na kopesa feeling ya riski-et-reward flexible, kotalela choix ya cible, multiplier, mpe mode ya kobeta.",
    "Chicken Shoot ezali connecté na Chicken Road? Ee. Chicken Shoot ezali na mokili ya Chicken Road ya InOut Games, kasi esalelaka mekaniki ya shooter na esika ya format ya nzela na step.",
]

# --- games #12 Chicken vs Zombies ---
G12_META = {
    "sw": {
        "name": "Chicken vs Zombies",
        "title": "Chicken vs Zombies: Mchezo, Demo na Kasino",
        "description": "Mapitio ya Chicken vs Zombies: mchezo, hali ya demo, RTP na kasino bora za kucheza Chicken vs Zombies mtandaoni.",
    },
    "ln": {
        "name": "Chicken vs Zombies",
        "title": "Chicken vs Zombies: Lisano, Demo mpe Ba Casino",
        "description": "Tala ya Chicken vs Zombies: gameplay, demo, RTP mpe ba casino ya malamu mpo na kobeta Chicken vs Zombies na internet.",
    },
}

G12_EN = json.loads((DL / "games-12-en-segments.json").read_text(encoding="utf-8"))
G12_FR = json.loads((DL / "games-12-fr-segments.json").read_text(encoding="utf-8"))

G12_SW = [
    "&nbsp;",
    "Chicken vs Zombies",
    "Chicken vs Zombies Rasmi",
    "Ugumu wa Kiwango - Aina 4 za Zombies",
    "Medium - Pot-Headed Zombie",
    "Hard - Football Player Zombie",
    "Hardcore - Soldier Zombie",
    "Fikia Chicken Boss - Jackpot ya Juu",
    "Vikomo vya sasa ndani ya mchezo:",
    "Kwa muhtasari:",
    "Uadilifu wa Chicken vs Zombies",
    "FAQ",
    "Mchezo wa Chicken vs Zombies hatua kwa hatua wa provably fair",
    "Chicken vs Zombies ni mchezo mwingine wa haraka wa mchezaji mmoja kutoka timu ya INOUT. Kila hatua salama huongeza malipo yanayowezekana, lakini kuumwa mara moja na zombie huisha mfululizo. Mchezo unafanya kazi karibu na msukumo ule ule unaofanya michezo hii ya kasino ya mtindo wa arcade iwe ya kuvutia. Unaweza kusimama na cash out, au kuendelea kusogea na kuhatarisha dau lote kwa matokeo bora. Hakuna mzigo wa ziada, hakuna sheria ndefu - raundi fupi tu, maamuzi ya haraka, na swali hilo la kudumu: endelea zaidi au chukua ulichonacho tayari?",
    "Aina ya Mchezo",
    "Mchezo wa cash-out wa mchezaji mmoja",
    "Hali za Ugumu",
    "Easy, Medium, Hard, Hardcore",
    "Mbinu Kuu",
    "Hatua salama huongeza malipo",
    "Kuumwa mara moja na zombie huisha raundi",
    "Tarehe ya Kutolewa",
    "23.10.2025",
    "Msanidi",
    "Unaweza kujaribu demo ya Chicken vs Zombies bila kugusa pesa halisi. Demo inakupa salio la kawaida la $1,000,000, hivyo kuna nafasi ya kutosha kujaribu mchezo vizuri badala ya kucheza raundi moja au mbili za makini.",
    "Ni mantiki ile ile ya mchezo, tu na pesa ya kucheza. Unaweza kuchagua ugumu wowote - Easy, Medium, Hard, au Hardcore - kubadilisha dau, kujaribu cash out hatua kwa hatua, na kujaribu chaguo la \"Space to spin &amp; go\" ukitaka hisia ya shughuli zaidi.",
    "Hii ndiyo hasa demo inafaa. Unaweza kusukuma kuku mbali zaidi kuliko unavyofanya kawaida, kuangalia kasi hatari inavyokua, na kuelewa lini inahisi bora cash out. Hakuna fedha halisi ziko hatarini, hivyo makosa hayagharami chochote.",
    "Kabla ya kuanza, chagua njia ya zombie na weka dau lako. Hali rahisi zinahisi tulivu zaidi na zinatoa nafasi zaidi kujaribu mchezo. Hali ngumu zinakua haraka, lakini kosa moja linaweza kuisha mfululizo haraka sana.",
    "Kiwango Aina ya Zombie Idadi ya Zombies Wasifu wa Hatari Ukuaji wa Malipo",
    "Easy Citizen Zombie 30 Hatari ya chini, msamaha zaidi Tulivu na taratibu",
    "Medium Pot-Headed Zombie 25 Hatari ya wastani, msukumo wa mapema Ujenzi wa haraka",
    "Hard Football Player Zombie 22 Hatari ya juu, mfululizo mgumu Ongezeko la haraka",
    "Hardcore Soldier Zombie 18 Hatari ya juu sana, hali kali Mshindo mkali",
    "Medium tayari inahisi msukumo zaidi. Njia ni nyembamba zaidi, hatari inaonekana mapema, lakini malipo pia yanajengwa haraka.",
    "Kwa dau la $2, maendeleo yanayowezekana ni: 2 &rarr; 2.40 &rarr; 2.88 &rarr; 3.46. Hiyo ni takriban +73% baada ya hatua 3 salama. Wachezaji wengi wanapendelea kuondoka karibu na hatua 2-3 hapa badala ya kusukuma mbali sana.",
    "Hali ya Hard ndipo mchezo unaacha kuwa tulivu. Multiplier inakua haraka, lakini nafasi ya kosa ni ndogo zaidi. Ni bora kuamua mahali pa kutoka kabla raundi haijaanza.",
    "Mfano: kwa dau la $2, malipo yanaweza kwenda 2 &rarr; 2.56 &rarr; 3.28 &rarr; 4.20 baada ya hatua 3 salama. Hiyo ni takriban +110%, lakini hatua moja mbaya inamaanisha mfululizo unaisha na $0.",
    "Hardcore ndiyo hali ya juu zaidi. Mfululizo mifupi, msukumo mkubwa, kutoka haraka. Hii si kiwango cha kujaribu kwa urahisi isipokuwa unaelewa kasi salio linaweza kutoweka.",
    "Mfano: kwa dau la $2, malipo yanaweza kusogea 2 &rarr; 2.70 &rarr; 3.64 baada ya hatua 2-3 salama tu. Ukuaji ni mkali, lakini kukaa muda mrefu ni hatari. Chukua mshindo na usikae sana.",
    "Chicken Boss ni sehemu ya mwisho ya msukumo katika Chicken vs Zombies. Ukivuka njia kwa usafi na kufikia mwisho, mfululizo unapanda hadi hatua ya Boss. Hatua moja sahihi inaikamilisha. Kuumwa mara moja huisha kila kitu.",
    "Huhitaji kukabiliana na Boss ukishindwa. Mchezo unakuacha cash out kabla ya wakati huo, na kwa wachezaji wengi hiyo itakuwa chaguo salama zaidi. Boss iko kwa watu wanaotaka kusukuma mfululizo hadi mwisho na kukubali hatari ya ziada.",
    "Thamani ya Kikomo",
    "Kikomo cha Ushindi wa Juu",
    "Boss anaonekana baada ya mfululizo wa hatua salama;",
    "mahali halisi hutegemea ugumu uliochaguliwa;",
    "kukamilisha tile ya Boss huweka mfululizo kwenye njia ya malipo ya juu;",
    "ushindi wa juu umefungwa kwa $20,000 katika build hii;",
    "bado unaweza cash out kabla ya kukabiliana na Boss.",
    "Chicken vs Zombies inatumia RNG huru hatua kwa hatua. Kwa maneno rahisi, kila hatua huhesabiwa tofauti, na mchezo haufanyi kazi na kumbukumbu ya \"hot\" au \"cold\".",
    "Hatua chache nzuri hazifanyi inayofuata iwe salama. Raundi chache mbaya hazimaanishi mchezo unakudai matokeo bora. Kila hatua bado ni hatari mpya.",
    "Mchezo unafanya kazi kwa mfano wa RTP wa 95.5% kwa muda mrefu. Hiyo haimaanishi kila mchezaji anarudishiwa 95.5% katika kikao kimoja. RTP ni nambari ya muda mrefu, si ahadi kwa mfululizo wako unaofuata.",
    "Ugumu hubadilisha umbo la mchezo. Easy inatoa nafasi zaidi ya kupumua. Medium huongeza msukumo. Hard na Hardcore hufanya malipo yakue haraka, lakini hatari inakuwa mkali zaidi. Chaguo la Space-to-step hubadilisha jinsi unavyodhibiti mchezo, si uwezekano nyuma ya hatua.",
    "RTP ya Chicken vs Zombies ni nini? Chicken vs Zombies inafanya kazi kwa RTP ya 95.5% katika build ya sasa. Kumbuka tu, RTP ni nambari ya muda mrefu. Haimaanishi kikao kifupi kitarudisha haswa 95.5%. Mchezaji mmoja anaweza kushinda haraka, mwingine kupoteza haraka - hiyo ni kawaida kwa aina hii ya mchezo.",
    "Je, Chicken vs Zombies ina autoplay? Si kwa maana ya kawaida ya kasino. Mchezo umejengwa karibu na kucheza kwa mkono: bonyeza Play, songa hatua kwa hatua, na uamue lini cash out. Pia kuna chaguo la \"Space to step\" kwa ingizo la haraka, lakini haligeuzi mchezo kuwa autoplay kiotomatiki. Bado unadhibiti mfululizo wewe mwenyewe.",
    "Lugha na sarafu? Chicken vs Zombies imejengwa kwa HTML5, hivyo inaweza kufanyiwa localization kwa masoko tofauti. Lugha na sarafu hutegemea kasino unapofungua mchezo. Kasino zinazoshirikiana zinaweza kurekebisha mchezo kwa mipangilio yao ya pochi, sarafu za ndani, na lugha zinazotumika.",
    "Chicken vs Zombies inafanya matokeo kuwa ya nasibu vipi? Kila hatua inashughulikiwa na RNG huru. Kwa maneno rahisi, mchezo haukumbuki raundi za \"hot\" au \"cold\". Hatua salama sasa haifanyi inayofuata iwe salama zaidi. Mfululizo mbaya haimaanishi mchezo unakudai matokeo bora. Kila hatua ni hatari mpya. Tofauti pekee ni kwamba demo inatumia pesa ya kawaida.",
    "Je, kuna programu, au inafanya kazi kwenye kivinjari? Kwa kawaida huhitaji pakua tofauti. Cheza kwenye kivinjari au kupitia programu ya kasino inayoaminika. Kuwa makini na faili za APK za nasibu, hasa zikiahidi hacks, predictors, au matoleo maalum.",
]

G12_FR_LN = [
    "Chicken vs Zombies",
    "Chicken vs Zombies officiel",
    "Niveau ya difficulty — 4 type ya zombie",
    "Medium — Zombie na pot na moto",
    "Hard — Zombie joueur ya football",
    "Hardcore — Zombie soldat",
    "Kokóta na Chicken Boss — jackpot ya likolo",
    "Ba limit ya sika na lisano:",
    "Na mokuse:",
    "Fairness ya Chicken vs Zombies",
    "FAQ",
    "Capture ya ecran ya lisano Chicken vs Zombies online",
    "Ba mode ya difficulty mpe ba couloir ya zombie na Chicken vs Zombies",
    "Gameplay ya Chicken vs Zombies step-by-step provably fair",
    "Chicken vs Zombies ezali lisano solo ya mbangu mosusu ya équipe INOUT. Step ya solo oyo ezali safe ebakisa gain oyo ekoki, kasi mordre moko ya zombie esukisa run. Lisano esalemi na tension moko oyo esalelaka ba lisano ya casino ya style arcade ezala addictive. Okoki kozela mpe cash out, to kokende liboso na kobeta riski pari mobimba mpo na resultat ya malamu. Eloko ya overload te, mibeko ya molai te — kaka ba round ya moke, ba decision ya mbangu, mpe question oyo ezali ntango nyonso: kokende liboso to zua oyo ozali nanu?",
    "Caractéristique",
    "Type ya lisano",
    "Lisano solo na mécanique ya cash-out",
    "Ba mode ya difficulty",
    "Easy, Medium, Hard, Hardcore",
    "Mécanique ya liboso",
    "Ba step ya safe ebakisa gain",
    "Mordre moko ya zombie esukisa round",
    "Date ya publication",
    "23.10.2025",
    "Développeur",
    "Okoki komeka demo ya Chicken vs Zombies na kozanga kosalela mbongo ya solo. Demo epesi yo solde ya virtual ya $1,000,000, oyo epesi espace ya malamu mpo na komeka lisano malamu na esika ya kobeta round moko to mibale ya prudence.",
    "Ezali logic ya lisano moko, kaka na mbongo ya jeu. Okoki kopona difficulty nionso — Easy, Medium, Hard to Hardcore — kobongola pari, komeka ba cash-out na step, mpe komeka option \"Space to spin &amp; go\" soki olingi feeling ya active.",
    "Yango wana mode demo ezali na ntina. Okoki kobenda nkoko liboso koleka oyo okosala na solo, kotya vitesse oyo riski ekobaluka, mpe koyeba tango oyo cash out ezali malamu. Mbongo ya solo ezali te na riski, yango wana ba erreur e costi eloko te.",
    "Liboso ya kobanda, pona couloir ya zombie mpe botia pari na yo. Ba mode ya pépé ezali calme mpe epesi espace mingi mpo na komeka lisano. Ba mode ya makasi ekobaluka mbangu, kasi erreur moko ekoki kosukisa run mbangu mingi.",
    "Niveau Type ya zombie Motango ya zombie Profil ya riski Croissance ya gain",
    "Easy Zombie citoyen 30 Riski ya moke, plus indulgent Fluide mpe progressif",
    "Medium Zombie na pot na moto 25 Riski modéré, pression liboso Progression ya mbangu",
    "Hard Zombie joueur ya football 22 Riski ya likolo, run ya serré Montée ya mbangu",
    "Hardcore Zombie soldat 18 Riski ya likolo mingi, mode impitoyable Accélération ya makasi",
    "Mode Medium ezali déjà plus tendu. Chemin ezali serré, riski ebimaka liboso, kasi gain ebakisa mpe mbangu.",
    "Na pari ya $2, progression oyo ekoki ezali boye: 2 → 2.40 → 2.88 → 3.46. Ezali pene na +73% nsima ya 3 step ya safe. Basali ebele balingi kobima na 2 tii 3 step awa na esika ya kobenda liboso mingi.",
    "Mode Hard ezali esika oyo lisano e suka kozala détendu. Multiplier ebakisa mbangu, kasi marge ya erreur ezali moke mingi. Esengeli kozwa point ya sortie liboso ya kobanda round.",
    "Exemple: na pari ya $2, gain ekoki kolanda progression oyo: 2 → 2.56 → 3.28 → 4.20 nsima ya 3 step ya safe. Ezali pene na +110%, kasi mauvais mouvement moko elingi koloba round esuka na $0.",
    "Hardcore ezali mode ya solo ya intense. Ba run ya moke, pression ya makasi, ba sortie ya mbangu. Ezali niveau te mpo na komeka na légèreté, longola soki oyebi mbangu oyo solde ekoki kobunga.",
    "Exemple: na pari ya $2, gain ekoki kobaluka 2 → 2.70 → 3.64 nsima ya 2 tii 3 step ya safe kaka. Progression ezali makasi, kasi kozala liboso mingi ezali dangerous. Zua montée mpe kozanga kozela.",
    "Chicken Boss ezali point ya pression ya suka na Chicken vs Zombies. Soki o passé couloir na pete mpe okóti na suka, run e upgrade na étape Boss. Step moko ya malamu esukisa yango. Mordre moko esukisa eloko nionso.",
    "Osengeli ko affronter Boss te soki olingi te. Lisano epesi yo cash out liboso ya moment wana, mpe mpo na basali ebele yango ekozala choix ya solo ya safe. Boss ezali mpo na ba oyo balingi kobenda run tii na suka mpe kozwa riski ya sika.",
    "de pari Valeur",
    "Pari ya moke",
    "Pari ya likolo",
    "Gain ya likolo plafonné",
    "Boss ebimaka nsima ya série ya ba step ya safe;",
    "moment ya solo etalelaka difficulty oyo eponami;",
    "ko réussir case Boss etia run na chemin ya gain ya likolo;",
    "gain ya likolo ezali plafonné na $20,000 na version oyo;",
    "okoki ntango nyonso cash out liboso ya ko affronter Boss.",
    "Chicken vs Zombies esalelaka RNG indépendant step-by-step. Na maloba ya pépé, movement nionso e calculé séparément, mpe lisano esalaka te na mémoire \"chaude\" to \"froide\".",
    "Ba step ya malamu moke esalelaka te step oyo elandi ezala safe. Ba round ya mabe moke elingi koloba te ete lisano edev mbongo resultat ya malamu. Step nionso ezali riski ya sika.",
    "Lisano esalaka na modèle RTP 95.5% na long terme. Yango elingi koloba te ete mosali nionso azua 95.5% na session moko. RTP ezali chiffre ya long terme, promise te mpo na round oyo elandi.",
    "Difficulty e change forme ya lisano. Easy epesi marge mingi. Medium ebakisa pression. Hard mpe Hardcore esalelaka gain ekobaluka mbangu, kasi riski ezali makasi mingi. Option Space-to-step e change ndenge o contrôler lisano, te probabilité na nsima ya step.",
    "RTP ya Chicken vs Zombies ezali boni? Chicken vs Zombies esalaka na RTP 95.5% na version ya sika. Kundika kaka ete RTP ezali chiffre ya long terme. Ezali koloba te ete session ya moke ekozongisa exactly 95.5%. Mosali moko akoki kogagner mbangu, mosusu akoki kolonga mbangu — ezali normal mpo na type oyo ya lisano.",
    "Chicken vs Zombies epesi autoplay? Te na sensi ya solo ya ba jeux ya casino. Lisano esalemi na kobeta na maboko: press Play, advance na step, mpe decide tango ya cash out. Ezali mpe option \"Space to step\" mpo na input ya mbangu, kasi e transforme lisano te na autoplay automatique. O contrôler run na yo moko.",
    "Ba langue mpe ba devise? Chicken vs Zombies esalemi na HTML5, yango wana ekoki kozala localized mpo na ba marché ya different. Langue mpe devise etalelaka casino oyo ofungolaka lisano. Ba casino partenaire bakoki kobongola lisano na ba paramètre ya wallet na bango, ba devise locale, mpe ba langue oyo esungami.",
    "Chicken vs Zombies esalelaka ndenge nini ba resultat random? Step nionso esalemi na RNG indépendant. Na maloba ya pépé, lisano e remember te ba round \"chaud\" to \"froid\". Step ya safe sikoyo esalelaka te step oyo elandi ezala safe mingi. Mauvaise série elingi koloba te ete lisano edev resultat ya malamu. Movement nionso ezali riski ya sika. Difference ya solo ezali ete demo esalelaka mbongo ya virtual.",
    "Ezali na app, to esalaka na navigateur? Mingi ntango download ya solo esengeli te. Beta na navigateur to na app ya casino oyo etalemi. Zala careful na ba APK ya hasard, mingi soki epesi hacks, predictors, to ba version spéciale.",
]


def validate_meta(meta: dict) -> None:
    for lang in ("sw", "ln"):
        t = meta[lang]["title"]
        d = meta[lang]["description"]
        if len(t) > 70:
            raise ValueError(f"{lang} title too long: {len(t)}")
        if len(d) > 160:
            raise ValueError(f"{lang} desc too long: {len(d)}")


def main() -> int:
    validate_meta(G10_META)
    validate_meta(G11_META)
    validate_meta(G12_META)

    if len(G10_EN) != len(G10_SW) or len(G10_EN) != len(G10_LN):
        raise SystemExit(f"games#10 segment mismatch en={len(G10_EN)} sw={len(G10_SW)} ln={len(G10_LN)}")
    if len(G11_EN) != len(G11_SW) or len(G11_EN) != len(G11_LN):
        raise SystemExit(f"games#11 segment mismatch en={len(G11_EN)} sw={len(G11_SW)} ln={len(G11_LN)}")
    if len(G12_EN) != len(G12_SW):
        raise SystemExit(f"games#12 sw mismatch en={len(G12_EN)} sw={len(G12_SW)}")
    if len(G12_FR) != len(G12_FR_LN):
        raise SystemExit(f"games#12 fr_ln mismatch fr={len(G12_FR)} ln={len(G12_FR_LN)}")

    p10 = write_json(10, {"meta": G10_META, "pairs": pairs_from_lists(G10_EN, G10_SW, G10_LN)})
    p11 = write_json(11, {"meta": G11_META, "pairs": pairs_from_lists(G11_EN, G11_SW, G11_LN)})
    p12 = write_json(
        12,
        {
            "ln_from_fr": True,
            "meta": G12_META,
            "pairs": {
                "sw": [[a, b] for a, b in zip(G12_EN, G12_SW)],
                "fr_ln": fr_ln_pairs(G12_FR, G12_FR_LN),
            },
        },
    )
    print(f"Wrote {p10.name}: {len(G10_EN)} segments")
    print(f"Wrote {p11.name}: {len(G11_EN)} segments")
    print(f"Wrote {p12.name}: sw={len(G12_EN)} fr_ln={len(G12_FR)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
