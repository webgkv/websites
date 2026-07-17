#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build editorial games_7/8/9.json sw/ln translation data."""

from __future__ import annotations

import json
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "games_sw_ln_data"
DL = Path("/home/lenovo/Downloads/02/chickenroad-games")

import sys

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


# --- games #7 Swahili (EN -> SW) ---
SW7 = [
    "Michezo ya Mfululizo wa Chicken",
    "Michezo ya Chicken Road",
    "Chicken Road Asili",
    "Chicken Road 2.0",
    "Mfululizo wa Chicken Road",
    "Chicken Road 2 Bonus",
    "Chicken Road Vegas",
    "Chicken Road Gold",
    "Chicken Road Race",
    "Chicken Royal",
    "Chicken Coin",
    "Chicken Banana",
    "Chicken Shoot",
    "Jedwali la Ulinganisho wa Michezo ya Chicken Road",
    "Michezo Yanayofanana na Chicken Road",
    "Michezo ya Crash",
    "Michezo ya Instant",
    "Michezo ya Mines",
    "Michezo ya Tower",
    "Michezo ya kuvuka barabara",
    "Michezo ya Multiplier",
    "Ni Nini Kinachofanya Michezo Hii Zivutie",
    "Matoleo ya Demo ya Michezo ya Chicken Road",
    "Je, Michezo ya Chicken Road ni Salama?",
    "FAQ",
    "Picha ya skrini ya mchezo Chicken Road",
    "Picha ya skrini ya mchezo Chicken Road 2.0",
    "Picha ya skrini ya mchezo Chicken Road 2 Bonus",
    "Picha ya skrini ya mchezo Chicken Road Vegas",
    "Picha ya skrini ya mchezo Chicken Road Gold",
    "Picha ya skrini ya mchezo Chicken Road Race",
    "Picha ya skrini ya mchezo Chicken Royal",
    "Picha ya skrini ya mchezo Chicken Coin",
    "Picha ya skrini ya mchezo Chicken Banana",
    "Picha ya skrini ya mchezo Chicken Shoot",
    "Chicken Road ilionekana Aprili 4, 2024 na mwanzo ilionekana kama mchezo wa kasino wa kawaida tu. Kuku, barabara, hatua chache, vitufe rahisi. Hakuna kinachopiga kelele \"franchise kubwa\".",
    "Lakini mchezo ulivutia haraka. Wachezaji walipenda muundo, na InOut Games iliona wazi kuwa mhusika alikuwa na uwezo zaidi. Hivyo Chicken Road haikukaa peke yake muda mrefu. Baada ya toleo la kwanza kulikuwa na Chicken Road 2, kisha majina zaidi yaliyojengwa kuzunguka kuku huyo wa kuchekesha na hisia ile ile ya arcade.",
    "Ninachopenda kuhusu mfululizo huu ni kwamba haujaribu kuiga slot za kawaida. Hakuna reels zinazozunguka mbele yako. Hakuna alama zisizo na mwisho. Hakuna skrini ya bonus inayochukua nusu dakika kuelewa.",
    "Hapa mchezaji anapata wazo safi zaidi. Fanya hatua. Angalia kinachotokea. Amua kama kinatosha au jaribu tena. Uamuzi mdogo huo unarudiwa katika mchezo na kwa njia fulani unafanya kazi vizuri kuliko mechanics nyingi za kasino zilizojaa vitu.",
    "Mfululizo wa Chicken Road ni rahisi, lakini si tupu. Unaonekana wa kawaida, karibu kama mchezo wa arcade wa simu, huku sehemu ya hatari ikiwa wazi sana. Hatua moja zaidi inaweza kutoa matokeo bora. Hatua mbaya moja inaweza kumaliza raundi. Hiyo ndiyo formula yote. Na ndiyo sababu mfululizo ulikua haraka.",
    "Chicken Road ya kwanza ndiyo mchezo uliyoanzisha kila kitu. Hakuna hadithi ya ziada, hakuna skrini ya slot iliyojaa vitu, hakuna setup ndefu. Kuku, barabara, na uamuzi unaorudiwa kila raundi: chukua hatua nyingine au fanya Cash Out.",
    "Mechanic ni rahisi. Unachagua kiwango cha ugumu, weka dau lako, na kuanza kusonga mbele. Kila hatua salama huongeza multiplier. Ukiridhika na matokeo ya sasa, unaweza kubonyeza Cash Out na kuchukua ushindi. Ukitaka zaidi, unamtuma kuku zaidi chini ya barabara.",
    "Hapo ndipo mchezo unapovutia. Mchezaji hana subiri tu matokeo ya kiotomatiki. Unafanya uamuzi wewe mwenyewe, hatua kwa hatua. Easy mode inahisi tulivu, Medium inaongeza msukumo, na Hard au Hardcore inaweza kuchoma salio haraka sana ukijikosesha.",
    "Toleo la asili lilipata umaarufu kwa sababu lilikuwa rahisi kuelewa tangu raundi ya kwanza. Linavutia kwa urahisi na upatikanaji wake. Mechanic inaeleweka kwa maneno machache: songa mbele na kukusanya zawadi yako. Wakati huo huo, mchezo bado unaacha nafasi ya strategy na mpango, huku ukibaki mchezo wa kasino wa kawaida msingi wake.",
    "Hapa kwenye tovuti, unaweza kucheza Chicken Road asili katika demo mode. Demo ni bora kwa kujifunza. Kucheza kwa pesa halisi huhisi tofauti, kwa sababu kila kosa hugusa salio lako mwenyewe.",
    "Mchanganyiko huo wa vidhibiti rahisi, multiplier inayokua, uchaguzi wa ugumu, na uamuzi wa mchezaji ndiyo sababu Chicken Road ya kwanza ilikua mchezo mkuu katika mfululizo.",
    "Chicken Road 2 ni toleo jipya la mchezo. Inaendelea na wazo lile lile kuu, lakini uwasilishaji unahisi wa kisasa zaidi. Barabara inaonekana safi zaidi, magari yanahisi hai zaidi, na mchezo mzima una muonekano mpya wa arcade.",
    "Chicken Road ya kwanza tayari ilikuwa rahisi na wazi. Chicken Road 2 haijaribu kuvunja formula hiyo. Bado unasonga mbele, unatazama multiplier ikikua, na unaamua lini utafanya Cash Out. Swali lile lile linakusubiri wakati wa raundi: simama sasa, au jaribu hatua moja zaidi?",
    "Tofauti iko zaidi katika hisia. Chicken Road 2 inaonekana angavu na iliyopangwa vizuri kidogo. Mwendo unahisi zaidi kama mchezo wa arcade wa kuvuka barabara, si mechanic ya kasino tu na kuku juu yake. Kwa wachezaji waliopenda toleo la kwanza lakini walitaka kitu kipya kwa macho, sehemu hii ina maana.",
    "Bado, hatari ya msingi haikuondoka popote. Kabla ya kila hatua, una muda wa kufikiria kabla ya kuamua. Cheza kwa akili, na usisahau kufurahia uzoefu.",
    "Chicken Road 2 Bonus ni tofauti kwa wachezaji wanaotaka gameplay ya mtindo wa barabara, lakini na ladha zaidi ya bonus karibu nayo. Toleo hili linafaa wale wanaopenda Chicken Road 2, lakini wanataka kitu kinachohisi na nguvu zaidi ya mchezo.",
    "Chicken Road Vegas inaonekana zaidi kama toleo la kufurahisha la mchezo huo huo wa kuku. Rangi zaidi, mwanga zaidi, hali zaidi ya \"Vegas\" kwenye skrini.",
    "Chicken Road Gold inaonekana kama toleo la premium zaidi la mfululizo. Lengo hapa labda ni mtindo wa kuona tajiri zaidi, mandhari ya dhahabu, na hisia iliyopangwa vizuri zaidi wakati wa raundi.",
    "Hii inaweza kuvutia wachezaji wanaojali uwasilishaji na wanataka mechanic ile ile rahisi na muundo unaonekana ghali zaidi.",
    "Chicken Road Race inasogeza mfululizo karibu na mbio na kasi. Badala ya kufikiria barabara kama kizuizi tu, toleo hili linampa mchezo hali ya ushindani na shughuli zaidi.",
    "Inaweza kufaa wachezaji wanaopenda visuals za haraka, mwendo, na action kidogo zaidi karibu na wazo la kawaida la Chicken Road.",
    "Chicken Royal inahisi kama toleo la ngazi ya juu la ulimwengu wa kuku. Jina linampa hisia ya premium zaidi au ya \"tukio kuu\", ikilinganishwa na matoleo mepesi.",
    "Mchezo huu unaweza kuwa bora kwa wachezaji wanaojua tayari muundo wa msingi wa Chicken Road na wanataka kitu kinachoonekana zaidi au kilichoboreshwa.",
    "Chicken Coin ni tofauti kwa sababu inaonekana karibu na muundo wa sarafu au zawadi. Inaweza kuvutia wachezaji wanaopenda mechanics rahisi za kasino zenye kukusanya, kushikilia, au kujenga thamani wakati wa raundi.",
    "Toleo hili linaweza kufanya vizuri kwa wachezaji wanaofurahia mhusika wa Chicken Road, lakini wanataka kitu karibu na mini-mchezo wa kasino wa kawaida.",
    "Chicken Banana ni wazi mojawapo ya matoleo mepesi na ya kucheza zaidi. Hii si tena uzoefu wa kawaida wa Chicken Road. Inahisi zaidi kama mchezo wa scratch card wa mtindo wa lottery.",
    "Chicken Shoot inaonekana yenye action zaidi kuliko muundo wa barabara wa kawaida. Badala ya kusonga hatua kwa hatua tu, mchezo huenda unaelekea kwenye reactions za haraka na nguvu ya arcade shooter.",
    "Toleo hili linaweza kuvutia wachezaji wanaotaka mwendo zaidi, action zaidi, na rhythm tofauti kutoka Chicken Road asili.",
    "Mtindo Mkuu",
    "Kiwango cha Hatari",
    "Bora kwa",
    "Chicken Road",
    "Mchezo wa hatua wa kawaida",
    "Inaweza kubadilishwa",
    "Wachezaji wapya",
    "Toleo la barabara lililosasishwa",
    "Medium / High",
    "Wachezaji wanaotaka visuals mpya",
    "Chicken Road Bonus",
    "Toleo linalolenga bonus",
    "Inategemea mode",
    "Wawindaji wa bonus",
    "Toleo lenye mandhari ya kasino",
    "Wachezaji wanaopenda mtindo wa Vegas",
    "Mtindo wa Hold &amp; Win",
    "Wachezaji wa slot",
    "Arcade shooter",
    "Haraka / Hai",
    "Wachezaji wanaopenda action",
    "Ukikipenda Chicken Road, labda utafurahia michezo mingine iliyojengwa kuzunguka wazo lile lile: raundi fupi, sheria rahisi, hatari inayokua, na uamuzi mmoja wazi wakati unaofaa.",
    "Hizi si slot za kawaida. Hakuna spin ndefu, bonus buys ngumu, au skrini zilizojaa vitu. Mchezaji anaingia raundi haraka na karibu mara moja lazima afanye uchaguzi: endelea au simama.",
    "Michezo ya crash ndiyo kategoria iliyo karibu zaidi kwa hisia. Multiplier inakua, na mchezaji anahitaji kuondoka raundi kabla kila kitu hakijaisha. Msukumo wote unatoka kwa timing. Subiri sana, na dau lako linapotea.",
    "Michezo ya instant ni michezo ya kasino ya haraka ambapo matokeo yanakuja upesi. Kwa kawaida ni rahisi kuanza na kuelewa. Vitufe vichache, raundi fupi, hakuna setup ndefu. Ndio maana michezo hii inahisi ya asili sana kwenye simu.",
    "Michezo ya Mines pia yanategemea hatari hatua kwa hatua. Unafungua seli, unaepuka mitego iliyofichwa, na unaamua lini kuchukua ushindi wa sasa. Mantiki iko karibu na Chicken Road: kila hatua salama inaboresha matokeo, lakini uchaguzi mbaya mmoja unamaliza raundi.",
    "Michezo ya Tower hutumia msukumo ule ule kwa umbo tofauti. Katika michezo ya tower, kila kiwango kipya huongeza shinikizo. Unapanda juu, multiplier inakuwa bora, na kusimama kuanza kuhisi ngumu zaidi baada ya kila hatua iliyofanikiwa.",
    "Hizi ndizo zinazofanana zaidi kwa macho. Mhusika anavuka barabara, anaepuka hatari, na husonga mbele hatua moja kwa wakati. Chicken Road ilifanya muundo huu uwe maarufu kwa mtindo wa kasino kwa sababu unahisi wa kawaida, karibu kama mchezo wa arcade, lakini bado una hatari ya kweli.",
    "Michezo ya multiplier imejengwa kuzunguka jambo moja rahisi: fanya coefficient ikue na uamue lini kukusanya. Baadhi hutumia ndege, roketi, mines, towers, au barabara. Mandhari ya kuona hubadilika, lakini hisia kuu inabaki ile ile: hatua inayofuata inaweza kuongeza ushindi au kumaliza raundi.",
    "Kinachounganisha michezo hii yote ni psychology ile ile. Inaonekana rahisi, inaanza haraka, na inamfanya mchezaji ahisi ameshiriki. Huna subiri tu skrini. Unafanya maamuzi madogo tena na tena. Ndio maana michezo inayofanana na Chicken Road inaweza kuvutia sana, hasa kwa wachezaji wanaopenda muundo wa kasino wa haraka bila mechanics za slot za kawaida.",
    "Michezo kama Chicken Road inafanya kazi kwa sababu hazipotezi muda. Unafungua mchezo na unaelewa wazo karibu mara moja. Hakuna sheria ndefu, hakuna skrini nzito ya slot, hakuna alama zinazoruka kila mahali.",
    "Raundi inaanza haraka. Mchezaji anaona barabara, mhusika, dau, na hatua inayofuata. Hiyo inatosha. Baada ya hatua ya kwanza, mchezo tayari unakupa uamuzi: chukua matokeo ya sasa au jaribu tena.",
    "Hii ndiyo ndoano kuu. Hatua moja zaidi daima inaonekana inawezekana. Multiplier ni bora kidogo, hatari inahisi inaweza kudhibitiwa, na mchezo haukusukumi kwa timer. Kwa hivyo unafikiria sekunde moja na unabonyeza tena.",
    "Hisia ya udhibiti pia ni muhimu. Mchezaji haketi tu akisubiri mchezo ufanye kila kitu. Unabonyeza kitufe, unachagua wakati wa kusimama, na unaamua unataka kusukuma raundi hadi wapi. Bado, matokeo hayako mikononi mwako. Hiyo ndiyo sehemu watu wakimsahau. Lakini inahisi hai zaidi kuliko michezo mingi ya kasino ya kawaida.",
    "Sababu nyingine ni muundo safi. Chicken Road na michezo inayofanana iko karibu zaidi na michezo ya arcade kuliko slot za jadi. Muundo pia husaidia. Michezo hii ni rahisi kusoma kwenye skrini, raundi hazichukui muda mrefu, na vidhibiti vya simu vinahisi vya asili.",
    "Ukichanganya yote hayo, unapata sababu kuu kwa nini muundo huu unafanya kazi: mwanzo wa haraka, sheria rahisi, raundi fupi, gameplay inayofaa simu, na wazo dogo linalokera: labda hatua moja zaidi. Inaonekana nyepesi, lakini hatari haiondoki kamwe kwenye mchezo.",
    "Kabla ya kucheza mchezo wowote wa Chicken Road kwa pesa halisi, ni bora kuanza na demo. Si kwa sababu demo itakufanya mchezaji mkamilifu. Haitafanya. Lakini inakupa muda kuelewa kinachotokea kweli kwenye mchezo.",
    "Unaweza kujaribu mechanics za msingi, kujaribu matoleo tofauti, kubadilisha viwango vya ugumu, na kuona hatari inabadilika haraka kiasi gani baada ya kila hatua. Katika demo mode, makosa si ghali. Unapoteza mikopo ya kawaida, unaanza upya, na unajaribu tena.",
    "Hii ni muhimu hasa ukilinganisha michezo kadhaa ya Chicken Road. Toleo moja linaweza kuhisi tulivu, lingine linaweza kuonekana la haraka, na baadhi ya modes zinaweza kuwa na msukumo mkubwa sana kwa mtindo wako. Demo inakusaidia kuona hilo kabla salio lako halisi halijahusishwa.",
    "Michezo ya Chicken Road ni rahisi kufungua, lakini bado ni muhimu unacheza wapi. Tumia tovuti za kasino zinazoaminika au kurasa zinazoonyesha wazi provider wa mchezo. Ikiwa mchezo ni kutoka InOut Games, jina la provider halipaswi kufichwa au kubadilishwa na nakala ya ajabu.",
    "Kuwa makini na faili za APK za nasibu. Demo ya kawaida au toleo la browser halihitaji \"installer maalum\" kutoka Telegram, matangazo ya pop-up, au tovuti zisizojulikana. Faili kama hizo zinaweza kuwa bandia, na baadhi zimetengenezwa tu kukusanya data au kusukuma ofa hatari.",
    "Vile vile kwa app za predictor. Hakuna app inayoweza kuonyesha hatua salama inayofuata au kuhakikisha ushindi. Mtu anauza \"signals\" au \"bot ya Chicken Road inayofanya kazi 100%\", hiyo tayari ni red flag.",
    "Cheza kwa furaha, furahia uzoefu mzuri, na usitumie zaidi ya bajeti uliyopanga.",
    "Kuna michezo mingi ya Chicken Road? Mfululizo sio tena mchezo mmoja tu. Ulianza na Chicken Road asili, kisha kulikuwa na Chicken Road 2 na majina mengine na mhusika huyo huyo wa kuku. Idadi inaweza kubadilika wakati InOut inaendelea kuongeza matoleo mapya.",
    "Ni nini mchezo wa kwanza wa Chicken Road? Chicken Road asili ndiyo toleo la msingi la mfululizo. Kuku anavuka barabara, multiplier inakua baada ya hatua salama, na mchezaji anaamua lini kusimama. Rahisi, lakini ndio maana ilifanya kazi.",
    "Ni nini Chicken Road 2.0? Chicken Road 2.0 ni toleo jipya. Inaonekana ya kisasa zaidi, na visuals za barabara na magari zilizosasishwa. Wazo kuu bado linajulikana: songa mbele, ongeza multiplier, au fanya Cash Out kabla raundi haijapotea.",
    "Toleo gani wanaoanza wanapaswa kujaribu kwanza? Anza na Chicken Road asili katika demo mode. Ni rahisi kuelewa, na unaweza kuzoea mechanic ya msingi kabla ya kujaribu matoleo mapya au yenye hatari zaidi.",
    "Je, kuna michezo inayohisi kufanana? Ndiyo. Angalia michezo ya crash, instant, mines, tower, kuvuka barabara, na multiplier. Yote hutumia aina ile ile ya shinikizo: endelea kwa zaidi, au simama kabla ya kupoteza raundi.",
    "Naweza kucheza bila malipo? Demo tu. Michezo ya Chicken Road ina demo mode. Unacheza na mikopo ya kawaida, hivyo ni njia nzuri ya kujaribu mchezo bila kugusa pesa halisi.",
    "Je, michezo ya Chicken Road inafanya kazi kwenye simu? Ndiyo, na browser kwa kawaida inatosha.",
    "Je, ninapaswa kupakua chochote? Kwa kawaida huhitaji pakua tofauti. Cheza kwenye browser au kupitia app ya kasino inayoaminika. Kuwa makini na faili za APK za nasibu, hasa zikiahidi hacks, predictors, au matoleo maalum.",
    "Je, matokeo yanaweza kutabiriwa? Hapana. Michezo ya Chicken Road hayawezi kutabiriwa. Raundi zinazofanana hazimaani kuna pattern. App yoyote inayodai kujua matokeo yanayofuata inapaswa kuchukuliwa kuwa ya shaka.",
    "Je, kucheza kwa pesa halisi kuna hatari? Ndiyo. Cheza kwa akili na panga bajeti yako kwa uangalifu.",
]

# --- games #7 Lingala (FR -> LN), polished ---
LN7 = [
    "Ba lisano ya série Chicken",
    "Ba lisano ya Chicken Road",
    "Chicken Road ya liboso",
    "Chicken Road 2.0",
    "Série Chicken Road",
    "Chicken Road 2 Bonus",
    "Chicken Road Vegas",
    "Chicken Road Gold",
    "Chicken Road Race",
    "Chicken Royal",
    "Chicken Coin",
    "Chicken Banana",
    "Chicken Shoot",
    "Table ya comparison ya ba lisano Chicken Road",
    "Ba lisano oyo ezali ndenge moko na Chicken Road",
    "Ba lisano crash",
    "Ba lisano instant",
    "Ba lisano Mines",
    "Ba lisano Tower",
    "Ba lisano ya kotala nzela",
    "Ba lisano ya multiplier",
    "Mpo na nini ba lisano oyo ezali popular",
    "Ba version demo ya ba lisano Chicken Road",
    "Ba lisano Chicken Road ezali safe?",
    "FAQ",
    "Capture ya ecran ya lisano Chicken Road",
    "Capture ya ecran ya lisano Chicken Road 2.0",
    "Capture ya ecran ya lisano Chicken Road 2 Bonus",
    "Capture ya ecran ya lisano Chicken Road Vegas",
    "Capture ya ecran ya lisano Chicken Road Gold",
    "Capture ya ecran ya lisano Chicken Road Race",
    "Capture ya ecran ya lisano Chicken Royal",
    "Capture ya ecran ya lisano Chicken Coin",
    "Capture ya ecran ya lisano Chicken Banana",
    "Capture ya ecran ya lisano Chicken Shoot",
    "Chicken Road ebandi na 4 avril 2024 mpe na ebandi ezalaki lokola lisano ya casino ya pépé mosusu. Nkoko moko, nzela, ba step moke, ba bouton ya pépé. Eloko moko te oyo elobaka « franchise monene ».",
    "Kasi lisano ezwaki attention nokinoki. Basali balingi format, mpe InOut Games emoni malamu ete personnage ezalaki na potential mingi. Yango wana Chicken Road ebakisaki te solo ntango molai. Na nsima ya version ya liboso ebandi Chicken Road 2, na nsima ba titre mosusu na nkoko ya esengo moko mpe ndenge arcade moko.",
    "Oyo nalingi na série oyo ezali ete e copie te ba slot ya liboso. Ba reel ezali te oyo ezali kozonga na liboso na yo. Ba symbole ya libela te. Ecran ya bonus oyo esengeli seconde ebele mpo na koyeba te.",
    "Awana mosali azali na idea ya pépé mingi. Sala step moko. Tala oyo esalemaka. Pona soki ezali malamu to leka lisusu. Likambo ya kopona moke wana e répète na lisano mpe na ndenge moko esalaka malamu koleka mechanics ya casino ebele oyo ezali mpimba.",
    "Série Chicken Road ezali pépé, kasi mpamba te. Ezali lokola casual, pene na lisano ya arcade na telefone, kasi riski ezali clear mingi. Step moko mosusu ekoki kopesa resultat ya malamu. Step moko ya mabe ekoki kosilisa round. Yango wana formula mobimba. Mpe ezali ntina oyo série ekaki kobakisa nokinoki.",
    "Chicken Road ya liboso ezali lisano oyo ebandisaki nyonso. Story ya liboso te, ecran ya slot mpimba te, setup molai te. Kaka nkoko, nzela, mpe likambo ya kopona oyo e répète round nionso: sala step mosusu to Cash Out.",
    "Mécanique ezali pépé. Opona niveau ya difficulty, botia pari na yo, mpe obanda kokende liboso. Step nionso ya safe ekobakisa multiplier. Soki resultat ya lelo elongi yo, okoki kobetisa Cash Out mpe kozua gain. Soki olingi mingi, otindela nkoko liboso na nzela.",
    "Yango wana lisano ezali kozala interesting. Mosali azali kozela resultat automatique te. Opesi likambo ya kopona yo moko, step na step. Mode Easy ezali calme, Medium epesi tension mingi, mpe Hard to Hardcore ekoki koboma solde nokinoki soki olimbi.",
    "Version ya liboso elongi mpo na ete ezalaki pépé koyeba banda round ya liboso. Ezali kobenda na pépé mpe accessibilité. Mécanique ezali pépé koyeba na maloba moke: kende liboso mpe zua reward na yo. Na tango moko, lisano etikaka espace mpo na strategy mpe plan, kasi ezali lisusu lisano ya casino ya solo na ntina na yango.",
    "Na site oyo, okoki kobeta Chicken Road ya liboso na demo mode. Demo ezali malamu mpo na koyekola. Kobeta na mbongo ya solo ezali different, mpo na ete libunga nionso ekufaka solde na yo moko.",
    "Mix ya ba contrôle ya pépé, multiplier oyo ekobaka, kopona ya difficulty, mpe likambo ya kopona ya mosali ezali ntina mpo na nini Chicken Road ya liboso ekomaki lisano ya liboso na série.",
    "Chicken Road 2 ezali version ya sika ya lisano. Ebatelaka idea ya liboso, kasi présentation ezali moderne. Nzela ezali propre, ba voiture ezali active, mpe lisano mobimba ezali na look arcade ya sika.",
    "Chicken Road ya liboso ezalaki déjà pépé mpe clair. Chicken Road 2 e casser formula wana te. O continuer kokende liboso, otalela multiplier ekobaka, mpe opona tango ya Cash Out. Question moko ezali na yo na round: tika lelo, to leka step moko mosusu?",
    "Différence ezali mingi na ndenge. Chicken Road 2 ezali clair mpe polished moke. Mouvement ezali lokola lisano ya arcade ya kotala nzela, te kaka mécanique ya casino na nkoko likolo. Mpo na basali oyo balingi version ya liboso kasi balingi eloko ya sika na miso, yango ezali na sens.",
    "Kasi riski ya liboso ebebi te. Liboso ya step nionso, ozali na tango ya kokanisa liboso ya kopona. Beta na mayele, mpe kobosana te kosepela na expérience.",
    "Chicken Road 2 Bonus ezali variant mpo na basali oyo balingi gameplay ya nzela moko, kasi na saveur ya bonus mingi. Version oyo esalelaka basali oyo balingi Chicken Road 2, kasi balingi eloko oyo ezali na energy ya lisano mingi.",
    "Chicken Road Vegas ezali lokola version ya esengo ya lisano ya nkoko moko. Langi mingi, shine mingi, ambiance « Vegas » mingi na ecran.",
    "Chicken Road Gold ezali lokola version ya premium ya série. Focus ezali na style ya visual ya monene, thème ya gold, mpe ndenge ya polished mingi na round.",
    "Oyo ekoki kozala interesting mpo na basali oyo bataleli présentation mpe balingi mécanique ya pépé moko na design oyo ezali lokola ya monene.",
    "Chicken Road Race esalelaka série pene na course mpe vitesse. Na esika ya kokanisa nzela lokola obstacle kaka, version oyo epesi lisano mood ya active mpe compétitif.",
    "Ekoki kolongwa basali oyo balingi ba visual ya pépé, mouvement, mpe action moke mingi na idea ya Chicken Road ya liboso.",
    "Chicken Royal ezali lokola version ya likolo na mokili ya nkoko. Nkombo epesi ndenge ya premium to « main event », koleka ba version ya pépé.",
    "Lisano oyo ekoki kozala malamu mpo na basali oyo bazali koyeba déjà format ya Chicken Road mpe balingi eloko oyo ezali makasi to upgraded.",
    "Chicken Coin ezali different mpo na ete nkombo ezali pene na format ya coin to reward. Ekoki kobenda basali oyo balingi mechanics ya casino ya pépé na collecte, kobatela, to kobakisa valeur na round.",
    "Version oyo ekoki kosalela malamu basali oyo balingi personnage ya Chicken Road, kasi balingi eloko pene na mini-lisano ya casino ya casual.",
    "Chicken Banana ezali clairement moko ya ba version ya pépé mpe ya esengo. Ezali lisusu te expérience ya Chicken Road ya solo. Ezali lokola lisano ya scratch card ya style lottery.",
    "Chicken Shoot ezali orienté action koleka format ya nzela ya liboso. Na esika ya kokende step na step kaka, lisano elingi réaction ya pépé mpe energy ya arcade shooter.",
    "Version oyo ekoki kozala interesting mpo na basali oyo balingi mouvement mingi, action mingi, mpe rhythm different na Chicken Road ya liboso.",
    "Style ya liboso",
    "Niveau ya riski",
    "Malamu mpo na",
    "Chicken Road",
    "Lisano ya step ya liboso",
    "Ekoki kobongolama",
    "Basali ya sika",
    "Version ya nzela ya sika",
    "Moyen / Likolo",
    "Basali oyo balingi ba visual ya sika",
    "Chicken Road Bonus",
    "Version oyo ezali na bonus",
    "Etalela mode",
    "Ba chasseur ya bonus",
    "Version na thème ya casino",
    "Basali oyo balingi style Vegas",
    "Style Hold &amp; Win",
    "Basali ya slot",
    "Arcade shooter",
    "Ya pépé / Active",
    "Basali oyo balingi action",
    "Soki olingi Chicken Road, okoki kosepela ba lisano mosusu na idea moko: ba round ya pépé, mibeko ya pépé, riski oyo ekobaka, mpe likambo ya kopona moko clair na tango ya malamu.",
    "Ezali te ba slot ya liboso. Spin molai te, bonus buy mpimba te, to ecran oyo ezali mpimba te. Mosali akota na round nokinoki mpe asengeli kopona mbala moko: continuer to tika.",
    "Ba lisano crash ezali category oyo ezali pene na ndenge. Multiplier ekobaka, mpe mosali asengeli kobima na round liboso ete nyonso esili. Tension ezali na timing. Ozela mingi, pari ebimaka.",
    "Ba lisano instant ezali ba lisano ya casino ya pépé oyo resultat ekiti nokinoki. Ezali pépé kobanda mpe pépé koyeba. Bouton moke, round ya pépé, setup molai te. Yango wana ba lisano oyo ezali natural na telefone.",
    "Ba lisano Mines etiam basé na riski step na step. Ofungola ba case, oboya ba piège, mpe opona tango ya kozua gain ya lelo. Logic ezali pene na Chicken Road: step nionso ya safe ebongisaka resultat, kasi kopona moko ya mabe esilisa round.",
    "Ba lisano Tower esalelaka pression moko na forme mosusu. Na level nionso ya sika, pression ekobaka. Okweya likolo, multiplier ebongwana, mpe kotika ebandi kozala mpimba na nsima ya step nionso oyo elongi.",
    "Oyo ezali pene na visual. Personnage akataka nzela, akima danger, mpe akende liboso step moko na tango. Chicken Road elongi format oyo na style casino mpo na ete ezali familier, pene na lisano ya arcade, kasi ezali na riski ya solo.",
    "Ba lisano ya multiplier esalemi na eloko moko ya pépé: sala coefficient ekobaka mpe pona tango ya collecte. Basusu basalela ndege, fusee, mines, tower, to nzela. Thème ya visual e change, kasi ndenge ya liboso ezali moko: step oyo ekoya ekoki kobakisa gain to kosilisa round.",
    "Oyo e liaison ba lisano nionso ezali psychology moko. Ezali lokola pépé, ebandi nokinoki, mpe esalela ete mosali azala na part. Otaleli kaka ecran te. Opesi ba likambo ya kopona moke mbala na mbala. Yango wana ba lisano oyo ezali ndenge moko na Chicken Road ekoki kozala engaging, mingi mpo na basali oyo balingi format ya casino ya pépé na mécanique ya slot ya liboso te.",
    "Ba lisano lokola Chicken Road esalaka mpo na ete e perde temps te. Ofungola lisano mpe oyeba idea mbala moko. Mibeko molai te, ecran ya slot mpimba te, ba symbole oyo ezali kobeta partout te.",
    "Round ebandi nokinoki. Mosali atali nzela, personnage, pari, mpe step oyo ekoya. Yango ekoki. Na nsima ya step ya liboso, lisano epesi déjà likambo ya kopona: zua resultat ya lelo to leka lisusu.",
    "Yango wana crochet ya liboso. Step moko mosusu ezali ntango nyonso possible. Multiplier ezali malamu moke, riski ezali lokola ekoki kobatama, mpe lisano e pushi yo na timer te. Okanisa seconde moko mpe obetisa lisusu.",
    "Ndenge ya libateli ezali na ntina. Mosali azali kozela lisano esala nyonso te. Obetisa bouton, opona tango ya kotika, mpe opona liboso wapi olingi kokende na round. Kasi resultat ezali na maboko na yo te. Yango wana bato bakundaka mbala mosusu. Kasi ezali active koleka ba lisano ya casino mingi ya liboso.",
    "Ntina mosusu ezali design ya pépé. Chicken Road mpe ba lisano oyo ezali ndenge moko ezali pene na ba lisano ya arcade koleka ba slot ya liboso. Design esalisaka: ba lisano oyo pépé kotalela na ecran, round ezali te molai, mpe ba contrôle na telefone ezali natural.",
    "Soki obongisa nyonso, oyeba ntina format oyo esalaka: ebandi nokinoki, mibeko ya pépé, round ya pépé, gameplay oyo esalemi na telefone, mpe makanisi moke oyo ekangi: peut-être step mosusu. Ezali lokola pépé, kasi riski ebebi lisusu te na lisano.",
    "Liboso ya kobeta lisano ya Chicken Road na mbongo ya solo, malamu kobanda na demo. Te mpo na ete demo ekosala yo mosali parfait. Ekosala yo te. Kasi epesi yo temps mpo na koyeba oyo esalemaka vraiment na lisano.",
    "Okoki komeka mechanics ya liboso, ba version different, kobongola ba niveau ya difficulty, mpe kotala riski e change nokinoki na nsima ya step nionso. Na demo mode, ba libunga ezali te ya monene. O perde ba crédit ya virtual, obanda lisusu, mpe oleka lisusu.",
    "Yango ezali mpenza useful soki ozali kotalela ba lisano ebele ya Chicken Road. Version moko ekoki kozala calme, mosusu ya pépé, mpe ba mode mosusu ekoki kozala trop aggressive mpo na style na yo. Demo esalisaka oyo koyeba liboso ete solde na yo ya solo ekota.",
    "Ba lisano Chicken Road ezali pépé kofungola, kasi esika oyo obetaka ezali na ntina. Salela ba site ya casino oyo etalemi to ba page oyo emonisaka provider ya lisano clairement. Soki lisano euti na InOut Games, nkombo ya provider esengeli kobombama te to kobongolama na copie ya etrange.",
    "Boya ba fichier APK ya hasard. Demo ya solo to version navigateur esengeli installer spécial te na Telegram, pop-up, to site oyo oyeba te. Ba fichier boye ekoki kozala faux, mpe basusu esalemi kaka mpo na kozua ba data to kopesa ba offre ya riski.",
    "Même chose mpo na ba app ya prédiction. App moko te ekoki kolakisa step ya safe oyo ekoya to kondima gain. Moto atekaka « signaux » to « bot Chicken Road 100% », yango ezali déjà red flag.",
    "Beta mpo na esengo, sepela na expérience, mpe kobeta te koleka budget oyo o planifie.",
    "Ba lisano ya Chicken Road ezali boni? Série ezali lisusu te lisano moko kaka. Ebandi na Chicken Road ya liboso, na nsima Chicken Road 2 mpe ba titre mosusu na personnage ya nkoko moko. Motango ekoki kobongwana mpo InOut e continuer kobakisa ba version ya sika.",
    "Lisano ya liboso ya Chicken Road ezali nini? Chicken Road ya liboso ezali version ya base ya série. Nkoko akataka nzela, multiplier ekobaka na nsima ya ba step ya safe, mpe mosali apona tango ya kotika. Pépé, kasi yango wana elongi.",
    "Chicken Road 2.0 ezali nini? Chicken Road 2.0 ezali version ya sika. Ezali moderne, na nzela mpe ba voiture ya sika. Idea ya liboso ezali moko: kende liboso, bakisa multiplier, to Cash Out liboso ete round ebebi.",
    "Version nini basali ya sika basengeli komeka liboso? Banda na Chicken Road ya liboso na demo mode. Ezali pépé koyeba, mpe okoki kozala na mécanique ya liboso liboso ya komeka ba version ya sika to ya riski mingi.",
    "Ezali ba lisano oyo ezali ndenge moko? Ee. Tala ba lisano crash, instant, Mines, Tower, kotala nzela, mpe multiplier. Nionso ezali na pression moko: continuer mpo na mingi, to tika liboso ya koboya round.",
    "Nakoki kobeta ofele? Kaka na demo. Ba lisano Chicken Road ezali na demo mode. Obeta na ba crédit ya virtual, yango wana ezali ndenge malamu ya komeka lisano na mbongo ya solo te.",
    "Ba lisano Chicken Road esalaka na telefone? Ee, mpe navigateur ekoki mbala mingi.",
    "Nasengeli kozua eloko? Mbala mingi download mosusu esengeli te. Beta na navigateur to na app ya casino oyo etalemi. Boya ba APK ya hasard, mingi soki epesi hacks, predictors, to ba version spéciales.",
    "Resultat ekoki kozala prédit? Te. Ba lisano Chicken Road ezali predictable te. Ba round oyo ezali ndenge moko elobaka pattern te. App nyonso oyo elobaka ete eyeba resultat oyo ekoya esengeli kotala lokola suspect.",
    "Kobeta na mbongo ya solo ezali na riski? Ee. Beta na mayele mpe planifier budget na yo malamu.",
]

# --- games #8 meta polish (pairs mostly OK) ---
META8 = {
    "sw": {
        "name": "Chicken Royal",
        "title": "Chicken Royal: Cheza Slot na Mwongozo wa Kasino",
        "description": "Mapitio ya Chicken Royal: sheria, demo, RTP na mahali pa kucheza Chicken Royal (Royal Chicken) kwa pesa halisi.",
    },
    "ln": {
        "name": "Chicken Royal",
        "title": "Chicken Royal: Bina slot na mwongozo ya casino",
        "description": "Tala ya Chicken Royal: mibeko, demo, RTP mpe esika ya kobeta Chicken Royal (Royal Chicken) na mbongo ya solo.",
    },
}

# --- games #9 Swahili ---
SW9 = [
    "&nbsp;",
    "Chicken Coin",
    "Jinsi ya Kucheza Chicken Coin",
    "Super Chicken Coin",
    "Alama ya Chicken Coin",
    "Alama ya Bonus Coin",
    "Kipengele cha Chicken Boost",
    "Mchezo wa Bonus Coin Chicken",
    "Jackpots",
    "FAQ",
    "Alama ya Super Chicken Coin kwenye reels",
    "Kama Chicken Road na michezo mingine yenye mada ya kuku kutoka InOut, Chicken Coin inahisi kama ugani wa asili wa ulimwengu huo huo. Mtindo huo huo wa mhusika mwepesi, lakini rhythm tofauti. Hapa lengo ni zaidi sarafu, bonus, na zawadi za slot za kawaida.",
    "Mchezo una muonekano wa shamba angavu, visuals za kuchekesha, na setup rahisi. Haujaribu kuonekana mzito au mkali. Unaufungua na unaelewa haraka hali: kuku, sarafu, raundi za haraka, na uwasilishaji wa arcade mwepesi.",
    "Wakati huo huo, Chicken Coin bado ni mchezo wa kasino wa kweli. Ina RTP ya 96.5% na uwezo wa ushindi wa juu hadi $100,000. Hivyo nyuma ya muundo wa kucheza kuna hesabu halisi ya slot, hatari, na volatility.",
    "Mchezo huu unaweza kufaa wachezaji wanaopenda mhusika wa Chicken Road, lakini wanataka kitu karibu na slot badala ya mchezo wa barabara hatua kwa hatua. Kuvuka kidogo, kukusanya zaidi. Hatua moja zaidi kidogo, gameplay inayolenga bonus zaidi.",
    "Chicken Coin inaonekana ya kufurahisha, lakini haipaswi kuchukuliwa kama isiyo na madhara. Ikiwa pesa halisi inahusika, hatari ni halisi pia. Jaribu demo mode kwanza ikiwa inapatikana, jifunze features, na usichukulie slot yoyote kama njia iliyohakikishwa ya kushinda.",
    "Jina la Mchezo",
    "Mtengenezaji",
    "Aina ya Mchezo",
    "Slot ya Mtandaoni",
    "Shamba / Ukusanyaji wa Sarafu",
    "Dau la Chini",
    "Dau la Juu",
    "Ushindi wa Juu",
    "$100,000",
    "Vipengele Maalum",
    "Kipengele cha Collect, Chicken Boost, Mchezo wa Bonus",
    "Raundi ya Bonus",
    "Teknolojia",
    "HTML5, Provably Fair System",
    "Desktop, Simu, Tablet",
    "Chicken Coin ni mchezo mwingine kutoka ulimwengu wa Chicken Road, lakini unahisi tofauti na muundo wa kawaida wa kuvuka barabara. Hapa lengo si kusonga mbele hatua kwa hatua tu. Jina lenyewe tayari linatoa hali nyingine: sarafu, zawadi, kukusanya, na hisia ya kasino ya kawaida zaidi.",
    "Toleo hili linaweza kuvutia wachezaji wanaopenda mhusika wa Chicken Road, lakini wanataka kitu karibu na mchezo rahisi unaotegemea zawadi. Bado ina mtindo huo huo mwepesi wa arcade kutoka InOut Games: raundi za haraka, visuals rahisi, na hakuna skrini nzito ya slot iliyojaa alama na features zinazochanganya.",
    "Chicken Coin inaonekana chaguo nzuri kwa wachezaji wanaofurahia michezo ya haraka, lakini hawataki kila wakati shinikizo la kuvuka barabara hatua moja kwa wakati. Mada ya kuku inabaki ya kawaida, huku gameplay ikihisi tulivu zaidi na karibu na mini-mchezo wa kasino.",
    "Kivutio kuu hapa ni urahisi. Unafungua mchezo, unaelewa wazo haraka, na unaingia raundi bila maelezo marefu. Hiyo ndiyo sababu spin-offs hizi za Chicken Road zinafanya kazi vizuri: zinaweka mhusika huyo huyo unaotambulika, lakini huwapa wachezaji rhythm kidogo tofauti.",
    "Bado, Chicken Coin inapaswa kuchukuliwa kama mchezo wowote wa kasino. Ikiwa pesa halisi inahusika, kuna hatari kila wakati. Mtindo wa kuona unaweza kuonekana wa kuchekesha na usio na madhara, lakini matokeo hayajawahi kuhakikishwa. Demo mode ndiyo njia bora ya kuanza ukitaka kuelewa Chicken Coin inavyofanya kazi kabla ya kucheza na salio halisi.",
    "Chicken Coin imejengwa kwenye grid ndogo ya 4&times;3 na mistari 8 ya malipo iliyowekwa. Hakuna kitu kikubwa kwenye skrini, hakuna setup ya mistari mia. Unaspin, unatazama alama, na unatafuta mchanganyiko unaolingana kutoka kushoto kwenda kulia.",
    "Kabla ya kuanza, weka dau. Katika toleo hili, stake inaweza kwenda kutoka $0.10 hadi $20,000 kwa spin. Masafa hiyo ni pana, hivyo ni bora usibonyeze haraka sana. Angalia thamani ya sarafu, angalia kiwango cha dau, kisha bonyeza Spin.",
    "Mchezo wa msingi ni rahisi kufuatilia. Alama zinazolingana kwenye paylines huleta ushindi. Alama maalum zina umuhimu zaidi, hasa Chicken Coin na Super Chicken Coin. Hizi ndizo zinazoongeza mechanics za ziada na kufanya mchezo kuwa zaidi ya slot rahisi ya malipo ya mstari.",
    "Pia kuna Autoplay ikiwa hutaki kubonyeza Spin kila wakati. Lakini na dau kubwa, autoplay inaweza kuchoma salio haraka sana, hivyo ni bora uitumie kwa uangalifu.",
    "Hivyo mtiririko wa mchezo ni rahisi: chagua dau, spin reels, angalia paylines, na makini na alama za sarafu. Mada ni nyepesi na ya kuchekesha, lakini upande wa pesa bado ni halisi ikiwa huchezi demo.",
    "Super Chicken Coin ndiyo alama unayotaka kuona kweli katika Chicken Coin. Haipo tu kwa mapambo. Inaposhuka, raundi inaweza kuanza kusonga kwa mwelekeo wa kuvutia zaidi.",
    "Katika mchezo wa msingi, alama hii inaweza kuanzisha Collect Feature au kuchochea Chicken Boost. Katika raundi ya bonus, inakuwa na thamani zaidi kwa sababu inaweza kukusanya thamani zote za sarafu zinazoonekana kwenye skrini.",
    "Hiyo ndiyo sababu Super Chicken Coin hubadilisha hisia ya mchezo. Spin ya kawaida inaweza kuonekana tulivu, kisha alama hii inaonekana, na ghafla raundi ina uwezo zaidi.",
    "Inaweza kufanya nini:",
    "kuanzisha Collect Feature;",
    "kuamsha Chicken Boost katika mchezo mkuu;",
    "kuamsha Chicken Boost katika bonus;",
    "kukusanya thamani za sarafu zinazoonekana wakati wa Mchezo wa Bonus.",
    "Kwa maneno rahisi, hii ni mojawapo ya alama zinazompa Chicken Coin tabaka la ziada. Bila hii, mchezo ungehisi karibu zaidi na slot ya kawaida. Nayo, raundi za bonus zinakuwa makini zaidi, hai zaidi, na ngumu kuzipuuza.",
    "Ikiwa feature inachochewa, mchezo huchukua thamani za sarafu zinazoonekana kwenye reels na kuzizidisha mara 5. Hivyo thamani ya alama hii inategemea sana kile kilichopo tayari kwenye skrini. Spin moja inaweza kuonekana ya wastani, kisha coin feature inapiga, na ghafla matokeo ni bora zaidi kuliko ulivyotarajia.",
    "Kinachofanya Chicken Coin kuwa na manufaa ni kwamba inafanya kazi katika mchezo mkuu na katika bonus. Si alama unayosubiri tu katika mode moja. Inabaki muhimu katika kipindi chote.",
    "Kimsingi, hii ndiyo alama inayofanya mechanics za sarafu ziwe na maana. Ushindi wa mstari wa kawaida ni sawa, lakini Chicken Coin ndipo mchezo unaanza kuongeza thamani ya ziada.",
    "Bonus Coin ndiyo alama inayoweza kufungua raundi ya bonus, lakini inahitaji setup sahihi.",
    "Hii inafanya trigger iwe ya kuvutia zaidi kwa sababu hausubiri alama moja tu. Hivyo hata kabla bonus haijaanza, trigger tayari inaweza kuhisi tofauti. Wakati mwingine inaonekana ndogo. Wakati mwingine inaonekana kama raundi ina uwezo zaidi.",
    "Kwa maneno rahisi, Bonus Coin ni tiketi ya kuingia kwenye feature kuu. Haifanyi mengi peke yake, lakini alama sahihi zikishuka karibu nayo, mchezo unaingia kwenye sehemu ya bonus.",
    "Hii ndiyo wakati spin ya kawaida inaweza kuwa ya kuvutia ghafla. Super Chicken Coin moja inaweza kuonekana ndogo mwanzo, lakini Chicken Boost ikiwaamsha, matokeo yanaweza kubadilika haraka sana.",
    "Aina ya Boost",
    "Maelezo",
    "Multiplier Boost",
    "Inatumia multiplier ya nasibu ya &times;2, &times;3, &times;5, &times;7, au &times;10 kwenye thamani ya Super Chicken Coin wakati wa spin ile ile.",
    "Jackpot Trigger",
    "Inaambatanisha mara moja mojawapo ya zawadi za jackpot zinazopatikana kwenye Super Chicken Coin.",
    "Extra Coins",
    "Inaongeza Bonus Coins 2, 3, au 5 zenye thamani za nasibu kwenye reels.",
    "Boost inaweza kuongeza thamani ya ziada kwenye raundi na kufanya mchezo uhisi volatile zaidi. Hiyo inamaanisha uwezo mkubwa zaidi, lakini pia kutabirika kidogo. Hujui ni uboreshaji gani utaonekana, au matokeo ya mwisho yatakuwa na nguvu kiasi gani.",
    "Kwa maneno rahisi: Chicken Boost ndiyo feature inayompa Super Chicken Coin uzito wake wa kweli. Bila hii, alama ingehisi ya kawaida zaidi. Nayo, hata spin ya wastani inaweza kubadilika kuwa kitu kinachostahili kutazama.",
    "Mchezo wa Bonus ndiyo feature kuu katika Chicken Coin. Spin ya kawaida hubadilika kuwa raundi tofauti ya bonus. Skrini inakuwa na lengo la alama za sarafu tu, na wazo lote ni rahisi &mdash; kukusanya thamani iwezekanavyo kabla spin hazijaisha.",
    "Bonus inaanza na spin 3. Kila Bonus Coin mpya inaposhuka inarejesha counter tena kwenye 3. Hii ndiyo sehemu inayofanya feature iwe ya kuvutia. Sarafu moja ya ziada inaweza kuweka raundi hai, na matone mazuri machache mfululizo yanaweza kubadilisha bonus ndogo kuwa kitu chenye nguvu zaidi.",
    "Wakati wa bonus, alama zinazohusiana na sarafu tu ndizo zinaonekana:",
    "Bonus Coins;",
    "Chicken Coins;",
    "Super Chicken Coins.",
    "Baadhi ya sarafu zina thamani za pesa, huku zingine zikileta zawadi za jackpot. Feature inapomalizika, thamani zote zinazoonekana za Chicken Coin na Super Chicken Coin hukusanywa na kuongezwa pamoja. Jumla hiyo inakuwa malipo ya mwisho ya bonus.",
    "Hii ndiyo wakati Chicken Coin inaacha kuhisi kama slot rahisi ya 4&times;3. Mchezo wa msingi ni rahisi kufuatilia, lakini bonus inaongeza msukumo. Unasubiri sarafu moja zaidi, reset moja zaidi, thamani moja zaidi kwenye skrini.",
    "Chicken Coin pia ina zawadi nne za jackpot. Zinaweza kuonekana katika mchezo mkuu na ndani ya Mchezo wa Bonus Coin Chicken.",
    "25x total bet",
    "50x total bet",
    "150x total bet",
    "1000x total bet",
    "Grand ndiyo zawadi ambayo wachezaji wengi wataona kwanza, lakini hata Mini, Minor, na Major zinaweza kuongeza msukumo mkubwa kwenye raundi zikishuka wakati unaofaa.",
    "Hii ndiyo sababu bonus inahisi hai zaidi kuliko spin ya kawaida. Huna subiri thamani za sarafu tu. Kuna daima nafasi mojawapo ya zawadi za jackpot ionekane na kusukuma matokeo juu.",
    "Bado, jackpots si kitu unachoweza kulazimisha. Ni sehemu ya mechanics za nasibu za mchezo. Nzuri kupata, ya kusisimua kutazama, lakini haijahakikishwa kamwe.",
    "Je, Chicken Coin ni slot ya volatility ya juu au ya kati? Chicken Coin iko karibu na eneo la volatility ya kati hadi juu. Hivyo usitarajie kila spin ilete kitu kikubwa. Kunaweza kuwa na vipindi tulivu, kisha feature moja au coin iliyoboost hubadilisha raundi kabisa. Hiyo ndiyo asili ya slot hii: wakati wa polepole, kisha malipo makali zaidi mechanics za bonus zinapoamka.",
    "Je, Chicken Coin inafanya kazi kwenye simu? Ndiyo. Chicken Coin inaendeshwa kupitia HTML5 kwenye browsers za kisasa za simu na ndani ya app za kasino zinazoshirikiana.",
    "Je, kuna progressive jackpots? Hapana, jackpots katika Chicken Coin ni za kudumu.",
    "Je, kasino zinaweza kubadilisha RTP? RTP ya kawaida ni 96.5%, iliyowekwa na mtengenezaji. Kwa mchezaji, hatua bora ni rahisi: angalia paneli ya taarifa za mchezo kabla ya kucheza. Hapo ndipo RTP na sheria zinapaswa kuonyeshwa.",
    "Je, kuna demo mode? Ndiyo, InOut inatoa toleo la demo la bure kwa Chicken Coin. Ipo ili wachezaji wajaribu slot, waone features za bonus, na waelewe mechanics za sarafu kabla ya kutumia pesa halisi. Demo haifanyi mchezo usiwe na hatari katika mode halisi, lakini inakusaidia kuepuka kuingia bila kujua chochote.",
]

# --- games #9 Lingala ---
LN9 = [
    "&nbsp;",
    "Chicken Coin",
    "Ndenge ya kobeta Chicken Coin",
    "Super Chicken Coin",
    "Symbole ya Chicken Coin",
    "Symbole ya Bonus Coin",
    "Eloko ya Chicken Boost",
    "Lisano ya bonus Coin Chicken",
    "Jackpots",
    "FAQ",
    "Symbole ya Super Chicken Coin na ba reel",
    "Lokola Chicken Road mpe ba lisano mosusu na thème ya nkoko ya InOut, Chicken Coin ezali lokola extension ya solo ya mokili moko. Style ya personnage ya pépé moko, kasi rhythm different. Awa focus ezali mingi na ba coin, bonus, mpe ba reward ya slot ya liboso.",
    "Lisano ezali na look ya farm ya clair, ba visual ya esengo, mpe setup ya pépé. E lingi te kozala mpimba to sérieux. Ofungola yango mpe oyeba nokinoki mood: ba nkoko, ba coin, ba round ya pépé, mpe présentation ya arcade ya pépé.",
    "Na tango moko, Chicken Coin ezali lisusu lisano ya casino ya solo. Ezali na RTP 96.5% mpe potential ya gain ya likolo tii $100,000. Yango wana na nsima ya design ya esengo ezali math ya slot ya solo, riski, mpe volatility.",
    "Lisano oyo ekoki kolongwa basali oyo balingi personnage ya Chicken Road, kasi balingi eloko pene na slot koleka lisano ya nzela step na step. Kotala moke, collecte mingi. Step mosusu moke, gameplay oyo ezali na bonus mingi.",
    "Chicken Coin ezali lokola esengo, kasi esengeli kotalama lokola eloko ya pépé te. Soki mbongo ya solo ezali na jeu, riski ezali solo mpe. Leka demo mode liboso soki ezali, yekola ba feature, mpe kotala slot nyonso lokola ndenge garantie ya gain te.",
    "Nkombo ya lisano",
    "Developer",
    "Type ya lisano",
    "Slot online",
    "Farm / Collecte ya coin",
    "Pari ya moke",
    "Pari ya likolo",
    "Gain ya likolo",
    "$100,000",
    "Ba feature spéciales",
    "Collect Feature, Chicken Boost, Lisano ya bonus",
    "Round ya bonus",
    "Technologie",
    "HTML5, Provably Fair System",
    "Desktop, Mobile, Tablet",
    "Chicken Coin ezali lisano mosusu ya mokili ya Chicken Road, kasi ezali different na format ya kotala nzela ya liboso. Awa focus ezali te kaka kokende liboso step na step. Nkombo epesi déjà mood mosusu: ba coin, ba reward, collecte, mpe ndenge ya casino ya casual.",
    "Version oyo ekoki kozala interesting mpo na basali oyo balingi personnage ya Chicken Road, kasi balingi eloko pene na lisano ya reward ya pépé. Ebatelaka style ya arcade ya pépé ya InOut Games: ba round ya pépé, ba visual ya pépé, mpe ecran ya slot mpimba te na ba symbole mpe ba feature oyo e confusé.",
    "Chicken Coin ezali option ya malamu mpo na basali oyo balingi ba lisano ya pépé, kasi balingi te pression ya kotala nzela step moko na tango nyonso. Thème ya nkoko ezali moko, kasi gameplay ekoki kozala calme mpe pene na mini-lisano ya casino.",
    "Appeal ya liboso awa ezali pépé. Ofungola lisano, oyeba idea nokinoki, mpe okota na round na ba explication molai te. Yango wana ba spin-off ya Chicken Road esalaka malamu: ebatelaka personnage moko oyo eyebani, kasi epesi basali rhythm moke different.",
    "Kasi Chicken Coin esengeli kotalama ndenge moko na lisano nyonso ya casino. Soki mbongo ya solo ezali na jeu, riski ezali ntango nyonso. Style ya visual ekoki kozala ya esengo mpe harmless, kasi resultat e garanti te. Demo mode ezali ndenge ya malamu ya kobanda soki olingi koyeba ndenge Chicken Coin esalaka liboso ya kobeta na solde ya solo.",
    "Chicken Coin esalemi na grid ya pépé 4&times;3 na ba payline 8 fixe. Eloko monene te na ecran, setup ya ligne 100 te. O spin, otalela ba symbole, mpe otalela ba combinaison oyo ezali ndenge moko banda gauche tii droite.",
    "Liboso ya kobanda, botia pari. Na version oyo, pari ekoki kobanda na $0.10 tii $20,000 na spin. Range wana ezali monene, yango wana malamu kobetisa nokinoki mingi te. Tala valeur ya coin, tala niveau ya pari, mpe na nsima betisa Spin.",
    "Lisano ya base ezali pépé kolanda. Ba symbole oyo ezali ndenge moko na ba payline epesi ba gain. Ba symbole spéciales ezali na ntina mingi, mingi Chicken Coin mpe Super Chicken Coin. Oyo bazali oyo bakotisa ba mécanique ya liboso mpe basalela ete lisano ezala koleka slot ya line-pay ya pépé.",
    "Ezali mpe Autoplay soki olingi te kobetisa Spin chaque fois. Kasi na ba pari ya likolo, autoplay ekoki koboma solde nokinoki, yango wana malamu kosalela yango na attention.",
    "Yango wana flow ya lisano ezali pépé: pona pari, spin ba reel, tala ba payline, mpe tala ba symbole ya coin. Thème ezali pépé mpe ya esengo, kasi côté mbongo ezali solo soki obetaka demo te.",
    "Super Chicken Coin ezali symbole oyo olingi vraiment kotala na Chicken Coin. Ezali awa te mpo na decoration kaka. Soki ekiti, round ekoki kobanda kokende na direction ya interesting mingi.",
    "Na lisano ya base, symbole oyo ekoki kobandisa Collect Feature to kocher Chicken Boost. Na round ya bonus, ezali valuable mingi mpo na ete ekoki kozua ba valeur ya coin nionso oyo ezali visible na ecran.",
    "Yango wana Super Chicken Coin e change ndenge ya lisano. Spin ya solo ekoki kozala calme, na nsima symbole oyo ebimaka, mpe mbala moko round ezali na potential mingi.",
    "Oyo ekoki kosala:",
    "kobandisa Collect Feature;",
    "ko-activer Chicken Boost na lisano ya liboso;",
    "ko-activer Chicken Boost na bonus;",
    "kozua ba valeur ya coin visible na Lisano ya bonus.",
    "Na maloba ya pépé, oyo ezali moko ya ba symbole oyo epesi Chicken Coin couche ya liboso. Soki ezali te, lisano ekokifaka pene na slot ya solo. Na yango, ba round ya bonus ezali sharp mingi, active mingi, mpe mpimba koboya.",
    "Soki feature e trigger, lisano e zua ba valeur ya coin oyo ezali visible na ba reel mpe e multiplier na 5x. Yango wana valeur ya symbole oyo etaleli mingi eloko oyo ezali déjà na ecran. Spin moko ekoki kozala average, na nsima coin feature e hiti, mpe mbala moko resultat ezali malamu koleka oyo okanisaki.",
    "Oyo esalela Chicken Coin useful ezali ete esalaka na lisano ya liboso mpe na bonus. Ezali te symbole oyo ozali kozela kaka na mode moko. Ezali ntina na session mobimba.",
    "Na ntina ya solo, oyo ezali symbole oyo esalela ete mécanique ya coin ezala na ntina. Ba gain ya ligne ya solo ezali malamu, kasi Chicken Coin ezali esika lisano ebandi kobakisa valeur ya liboso.",
    "Bonus Coin ezali symbole oyo ekoki kofungola round ya bonus, kasi esengeli setup ya malamu.",
    "Yango esalela trigger interesting koleka mpo na ete ozali kozela symbole moko te. Yango wana ata liboso ete bonus ebandi te, trigger ekoki déjà kozala different. Parfois ezali lokola moke. Parfois ezali lokola round ezali na potential mingi.",
    "Na maloba ya pépé, Bonus Coin ezali ticket ya kokota na feature ya liboso. Esalaka mingi te solo, kasi ba symbole ya malamu ekoti na pene na yango, lisano ekota na part ya bonus.",
    "Yango wana moment oyo spin ya solo ekoki kozala interesting mbala moko. Super Chicken Coin moko ekoki kozala moke na ebandi, kasi soki Chicken Boost e activate, resultat ekoki kobongwana nokinoki.",
    "Type ya Boost",
    "Description",
    "Multiplier Boost",
    "E appliquer multiplier ya hasard ya &times;2, &times;3, &times;5, &times;7, to &times;10 na valeur ya Super Chicken Coin na spin moko.",
    "Jackpot Trigger",
    "E attacha mbala moko moko ya ba prix ya jackpot oyo ezali na Super Chicken Coin.",
    "Extra Coins",
    "E bakisa Bonus Coins 2, 3, to 5 na ba valeur ya hasard na ba reel.",
    "Boost ekoki kobakisa valeur ya liboso na round mpe esalela ete lisano ezala volatile mingi. Yango elobaka potential monene, kasi mpe predictable moke. Oyeba te enhancement nini ekobima, to resultat ya nsuka ekozala makasi boni.",
    "Na maloba ya pépé: Chicken Boost ezali feature oyo epesi Super Chicken Coin poids ya solo. Soki ezali te, symbole ekokifaka ordinary mingi. Na yango, ata spin ya moyenne ekoki kobongwana na eloko oyo esengeli kotala.",
    "Lisano ya bonus ezali feature ya liboso na Chicken Coin. Spin ya solo e change na round ya bonus mosusu. Ecran e focus kaka na ba symbole ya coin, mpe idea mobimba ezali pépé &mdash; collecte valeur mingi na esika oyo ekoki liboso ete ba spin esili.",
    "Bonus ebandi na spin 3. Bonus Coin nionso ya sika oyo ekiti e reset compteur lisusu na 3. Yango wana part oyo esalela ete feature ezala interesting. Coin moko ya liboso ekoki kobatela round, mpe ba drop malamu moke na suite ekoki kobongola bonus moke na eloko ya makasi.",
    "Na bonus, kaka ba symbole oyo ezali na coin e monani:",
    "Bonus Coins;",
    "Chicken Coins;",
    "Super Chicken Coins.",
    "Ba coin mosusu ezali na ba valeur ya cash, mpe basusu ekoki kozua ba prix ya jackpot. Soki feature esili, ba valeur nionso ya Chicken Coin mpe Super Chicken Coin oyo ezali visible e collecté mpe e additionné. Total wana ezali payout ya bonus ya nsuka.",
    "Yango wana moment oyo Chicken Coin etiki kozala lokola slot ya pépé 4&times;3. Lisano ya base ezali pépé kolanda, kasi bonus ebakisa tension. Ozali kozela coin mosusu, reset mosusu, valeur mosusu na ecran.",
    "Chicken Coin ezali mpe na ba prix ya jackpot minei. Ekoki kozala na lisano ya liboso mpe na Lisano ya bonus Coin Chicken.",
    "25x total bet",
    "50x total bet",
    "150x total bet",
    "1000x total bet",
    "Grand ezali prix oyo basali ebele bakotala liboso, kasi ata Mini, Minor, mpe Major ekoki kobakisa boost makasi na round soki ekiti na tango ya malamu.",
    "Yango wana bonus ezali alive koleka spin ya solo. Otaleli kaka ba valeur ya coin te. Ezali ntango nyonso chance ete moko ya ba prix ya jackpot ebimaka mpe esukisa resultat likolo.",
    "Kasi jackpots ezali eloko oyo okoki ko-force te. Ezali part ya mécanique ya hasard ya lisano. Malamu kozua, esengo kotalela, kasi e garanti te.",
    "Chicken Coin ezali slot ya volatility ya likolo to ya moyenne? Chicken Coin ezali pene na zone ya volatility moyenne tii likolo. Yango wana ozela te ete spin nionso ekobimisa eloko monene. Ekoki kozala ba moment calme, na nsima feature moko to coin boosted e change round mobimba. Yango wana nature ya slot oyo: ba moment ya polepole, na nsima ba payout ya sharp soki mécanique ya bonus e réveille.",
    "Chicken Coin esalaka na telefone? Ee. Chicken Coin esalaka na HTML5 na ba navigateur ya sika ya telefone mpe na ba app ya casino oyo ezali partenaire.",
    "Ezali ba jackpot progressive? Te, ba jackpot na Chicken Coin ezali fixe.",
    "Ba casino ekoki kobongola RTP? RTP ya solo ezali 96.5%, oyo developer abotaki. Mpo na mosali, step ya malamu ezali pépé: tala panel ya info ya lisano liboso ya kobeta. Awa RTP mpe mibeko esengeli komonana.",
    "Ezali demo mode? Ee, InOut epesi version demo ya ofele mpo na Chicken Coin. Ezali mpo basali bameka slot, batala ba feature ya bonus, mpe bayebana mécanique ya coin liboso ya kosalela mbongo ya solo. Demo esalela te ete lisano ezala sans riski na mode ya solo, kasi esalisaka oboya kokota completement na mpamba.",
]

LN7 = [polish_ln(t) for t in LN7]
LN9 = [polish_ln(t) for t in LN9]


def build_games_7() -> None:
    en = load_segs("games-7-en-segments.json")
    fr = load_segs("games-7-fr-segments.json")
    assert len(en) == 116 and len(fr) == 116 and len(SW7) == 116 and len(LN7) == 116
    sw_title, sw_desc = truncate(
        "Michezo ya Chicken Road: Mapitio ya Franchise na InOut",
        "InOut Games, studio ya Chicken Road: mapitio kamili ya mtengenezaji, data ya RTP na mahali pa kucheza michezo ya Chicken Road.",
    )
    ln_title, ln_desc = truncate(
        "Ba lisano ya Chicken Road: Tala ya franchise na InOut",
        "InOut Games, studio ya Chicken Road: tala mobimba ya provider, ba data ya RTP mpe esika ya kobeta ba lisano ya Chicken Road.",
    )
    payload = {
        "ln_from_fr": True,
        "meta": {
            "sw": {"name": "Chicken Series Games", "title": sw_title, "description": sw_desc},
            "ln": {"name": "Chicken Series Games", "title": ln_title, "description": ln_desc},
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW7),
            "fr_ln": pairs_from_lists(fr, LN7),
        },
    }
    (OUT / "games_7.json").write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("games_7.json: 116 sw + 116 fr_ln")


def build_games_8() -> None:
    en = load_segs("games-8-en-segments.json")
    existing = json.loads((OUT / "games_8.json").read_text(encoding="utf-8"))
    sw_pairs = existing["pairs"]["sw"]
    ln_pairs = [[a, polish_ln(b)] for a, b in existing["pairs"]["ln"]]
    assert len(sw_pairs) == len(en) == 37
    payload = {"meta": META8, "pairs": {"sw": sw_pairs, "ln": ln_pairs}}
    (OUT / "games_8.json").write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("games_8.json: meta polished, ln calques cleaned")


def build_games_9() -> None:
    en = load_segs("games-9-en-segments.json")
    assert len(en) == 88 and len(SW9) == 88 and len(LN9) == 88
    sw_title, sw_desc = truncate(
        "Chicken Coin Slot: Cheza na Jinsi Inavyofanya Kazi",
        "Mapitio ya Chicken Coin: jinsi inavyofanya kazi, RTP, volatility na mahali pa kucheza slot ya Chicken Coin mtandaoni.",
    )
    ln_title, ln_desc = truncate(
        "Chicken Coin Slot: Bina mpe yeba ndenge esalaka",
        "Tala ya Chicken Coin: ndenge esalaka, RTP, volatility mpe esika ya kobeta slot ya Chicken Coin online.",
    )
    payload = {
        "meta": {
            "sw": {"name": "Chicken Coin", "title": sw_title, "description": sw_desc},
            "ln": {"name": "Chicken Coin", "title": ln_title, "description": ln_desc},
        },
        "pairs": {
            "sw": pairs_from_lists(en, SW9),
            "ln": pairs_from_lists(en, LN9),
        },
    }
    (OUT / "games_9.json").write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print("games_9.json: 88 sw + 88 ln")


def main() -> int:
    build_games_7()
    build_games_8()
    build_games_9()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
