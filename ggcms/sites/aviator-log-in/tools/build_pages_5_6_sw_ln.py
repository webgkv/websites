#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build pages_5.json and pages_6.json sw/ln editorial data for Aviator Log In."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "pages_sw_ln_data"
SEG = Path.home() / "Downloads/02/aviator-pages"
CHICKEN = TOOLS.parents[1] / "chickenroad" / "tools"
sys.path.insert(0, str(CHICKEN))
from ln_quality_replacements import polish_ln  # noqa: E402


def load_segs(name: str) -> list[str]:
    return json.loads((SEG / name).read_text(encoding="utf-8"))


def pairs_from_lists(keys: list[str], vals: list[str]) -> list[list[str]]:
    if len(keys) != len(vals):
        raise ValueError(f"count mismatch: {len(keys)} keys vs {len(vals)} vals")
    return [[k, v] for k, v in zip(keys, vals)]


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


# --- Page #5 Swahili (EN -> SW) ---
SW5 = [
    "Pakua app ya Aviator: jinsi ya kufikia mchezo kwenye Android, iPhone na PC",
    "App ya Aviator ni Nini?",
    "Mahitaji ya Mfumo kwa APK ya Aviator",
    "Jinsi ya Kupakua App ya Mchezo wa Aviator",
    "Kusakinisha APK ya Aviator kwenye Android",
    "Kusakinisha Aviator kwenye iOS",
    "Kusakinisha Aviator kwenye Windows",
    "Toleo la Wavuti la Aviator",
    "Mchezo wa Aviator: App dhidi ya Tovuti ya Simu",
    "App kwenye Android, iOS na PC: Tofauti Kuu",
    "App Inayopakuliwa dhidi ya Tovuti ya Simu",
    "Ni Nini Bora Zaidi kwa Kucheza Aviator?",
    "Jinsi ya Kutumia App ya Mchezo wa Kamari wa Aviator",
    "Kucheza Aviator kwa Pesa Halisi kwenye App",
    "Chagua Kasino Inayoaminika",
    "Jisajili",
    "Pakua App ya Simu",
    "Ingia na Upate Mchezo",
    "Matatizo Yanayowezekana na Suluhisho",
    "Skrini Nyeusi",
    "Matatizo ya Sasisho",
    "Amana Imeshindwa",
    "FAQ",
    "Ufikiaji wa app ya Aviator kwenye simu na desktop",
    "Kufikia Aviator kupitia app ya Android au APK",
    "Kutumia Aviator kwenye iPhone au iPad",
    "Ufikiaji wa Aviator kupitia app na wavuti ya simu",
    "Aviator ni crash game watu hutambua haraka: sheria rahisi, raundi fupi, na inafanya kazi vizuri kwenye simu au laptop. Hivyo kisanduku cha utafutaji kinajaa na “Aviator app”. Hapa kuna mstari unaokuokoa muda: Spribe haitoi install moja ya “rasmi” ya Aviator inayosimama peke yake kwa kila mtu kucheza kwa pesa halisi.",
    "Mara nyingi unafikia mchezo kupitia kasino au sportsbook inayouhost kwenye lobby yao. Ukweli huo pekee hubadilisha maana ya “download Aviator” — build ya demo, app ya operator yenye Aviator ndani, au ukurasa wa APK wa shaka unaostahili kukataliwa mara moja.",
    "Ukijali dau halisi, maswali muhimu ni leseni, malipo yanayothibitishwa, na kama kikao cha kivinjari kwenye tovuti ile ile tayari kinatosha. Lebo ya “latest APK” hairekebishi chochote kati ya hayo.",
    "“Aviator app” ni lugha isiyo na mpangilio. Watu kwa kawaida wanamaanisha mojawapo ya vitu vitatu: paketi ya demo inayosimama peke yake, app ya operator aliye na leseni inayoorodhesha Aviator, au njia ya mkato ya kivinjari iliyobandikwa kwenye skrini ya nyumbani. Kwa wachezaji wengi, njia ya pili na tatu pekee ndizo za kweli.",
    "Sahau hadithi ya download moja rasmi ya kimataifa. Fikiria kwa muundo wa ufikiaji: sakinisha kupitia chapa unayoamini, au fungua mchezo kwenye kivinjari na uichukulie njia ya mkato kama ikoni ya app.",
    "Maelezo",
    "Toleo la sasa",
    "Crash betting game",
    "Lugha",
    "Nyingi",
    "Intaneti",
    "Inahitajika",
    "Msanidi",
    "Usakinishaji wowote — APK ya operator, build iliyopakiwa kwa njia ya upande, au wrapper nyembamba — bado unahitaji kifaa kinachoweza kupumua. Nambari hubadilika kulingana na chanzo, lakini orodha ni rahisi na ya kawaida: nafasi huru, muunganisho thabiti, na OS ya kisasa kutosha kupata viratibu vya usalama.",
    "Kigezo",
    "10.0 au mpya zaidi",
    "11.0 au mpya zaidi",
    "60&ndash;110 MB (APK)",
    "35&ndash;130 MB (IPA)",
    "Zaidi ya 1 GB",
    "Zaidi ya 1.2 GB",
    "4G, 5G, Wi‑Fi",
    "Kukagua operator ni bora kuliko kufuatilia jina la faili. Ikiwa leseni ya nyumba inaonekana dhaifu, uondoaji unaonekana kama hadithi, au msaada haujibu kamwe, APK tayari ni njia mbaya.",
    "Chagua operator aliye na leseni anayeorodhesha Aviator kweli kwenye app au tovuti yake ya simu.",
    "Fungua tovuti ya operator huyo kwenye kifaa cha Android na tumia kizuizi chao cha download.",
    "Chukua APK kutoka kwao tu au chanzo kingine unachoweza kuthibitisha mwisho hadi mwisho — si kutoka kwenye mirror ya kubahatisha.",
    "Ikiwa Android inaomba usakinishaji kutoka vyanzo visivyojulikana, geuza swichi tu unapoamini chanzo cha faili.",
    "Sakinisha, ingia, na thibitisha Aviator inaonekana kwenye lobby kabla ya kuweka fedha kwenye pochi.",
    "Chagua operator unayemwamini anayesaidia Aviator kwenye iPhone au iPad.",
    "Fungua tovuti ya simu ya operator au ukurasa wa app kutoka kwenye kifaa.",
    "Fuata njia ya usakinishaji ya iOS wanayochapisha — TestFlight, wasifu wa enterprise, au kiungo cha App Store — kamwe dump ya IPA kutoka kwa mtu usiyemjua.",
    "Fungua app au njia ya mkato iliyokamilika, ingia, na cheza raundi ya majaribio kabla ya kuitegemea kwa pesa.",
    "Chagua operator aliyedhibitiwa anayeorodhesha Aviator kwenye desktop.",
    "Soma tovuti yao: mteja halisi wa Windows, au mchezo wa kivinjari pekee.",
    "Wakishatoa install ya desktop, ipakue kutoka kwenye kikoa chao tu.",
    "Ingia na thibitisha crash game inapakia kabla ya kuichukulia binary hiyo kama mlango wako mkuu wa kuingia Aviator.",
    "Mchezo wa kivinjari hauchukuliwi kwa uzito. Wakati hakuna build ya asili, au hutaki binary nyingine kwenye diski, tovuti yenyewe ndiyo app:",
    "Fungua URL ya operator kwenye Chrome, Safari, au Edge.",
    "Baada ya kupakia, tumia menyu ya kivinjari na uchague “Add to Home screen” (maneno hubadilika).",
    "Unapata ikoni inayofungua kikao kile kile — hakuna paketi tofauti ya kudhibiti.",
    "Hakuna mshindi wa dhahabu hapa. Simu zinatofautiana, viwango vya uaminifu vinatofautiana, na baadhi ya watu wanachukia arifa za sasisho zaidi kuliko alamisho.",
    "Kiolesura",
    "Imeboreshwa kwa skrini ndogo",
    "Imeboreshwa kwa mguso",
    "Imebuniwa kwa skrini kubwa na panya/keyboard",
    "Utendaji",
    "Inaweza kutofautiana kwenye vifaa dhaifu",
    "Kwa ujumla laini kwenye vifaa vinavyoungwa mkono",
    "Utendaji imara kwenye vifaa vinavyoweza",
    "Mwenyewe au kiotomatiki",
    "Kwa kawaida mwenyewe, mara nyingi baada ya kidokezo",
    "Uoanifu",
    "Android 5.1+",
    "iOS 11.0+",
    "Matoleo mengi ya sasa ya Windows",
    "App inayopakuliwa",
    "Tovuti ya simu",
    "Inahitaji usakinishaji",
    "Hakuna usakinishaji",
    "Inategemea mahitaji ya kifaa",
    "Hakuna mahitaji maalum",
    "Inaweza kutuma arifa za push",
    "Arifa za ndani ya kivinjari pekee",
    "Inahitaji sasisho mara kwa mara",
    "Daima imesasishwa",
    "Inaendeshwa kama app tofauti",
    "Inafunguka kwenye kivinjari pekee",
    "Inaunganishwa na OS",
    "Hakuna muunganisho wa OS (mf. widgets, Face ID)",
    "App iliyofungashwa inaweza kuhisi kama asili na kuonyesha njia za mkato za akaunti haraka — lakini urithi maamuzi ya uaminifu kila wakati install inaposasishwa.",
    "Tovuti ya simu inaepuka msongamano wa diski, inabaki ya kisasa bila kubonyeza “update”, na inapunguza uwezekano wa kuchukua APK mbaya kutoka kwenye kikoa kinachofanana.",
    "Ndani ya ganda la operator halisi, Aviator ina tabia unayojua tayari: multiplier inapanda, unabonyeza Cash Out kabla ndege kuondoka. Mpangilio hubadilika; fizikia haibadiliki.",
    "Wenyeji wengi huonyesha udhibiti sawa wa vitendo — dau la mkono, auto-bet, auto Cash Out wanapounga mkono. Utulivu wa skrini unashinda maandishi ya uuzaji kuhusu build “rasmi”.",
    "Mpya kwenye simu? Tumia salio la demo kwanza ikiwa tovuti inatoa, jifunze sehemu za kugusa, kisha uamue kama mchezo wa fedha bado una maana.",
    "Install ni mlango. Operator ndiye jengo. Ukiingia lobby mbaya, ubora wa mlango hauhusiki tena.",
    "Tafuta leseni unayoweza kubofya hadi kwenye rejista, sheria za Cash Out kwa lugha rahisi, na msaada unaojibu kabla ya kuweka amana, si baadaye.",
    "Tumia tovuti ya operator au app iliyoorodheshwa kwenye store. Jina halisi, barua pepe halisi, nenosiri imara — KYC baadaye haitasamehe data duni ya usajili.",
    "Baada ya akaunti kuwepo, fuata njia ya Android au iOS iliyochapishwa na chapa ile ile. Wakitoa mchezo wa wavuti pekee, mara nyingi ni salama zaidi kuliko kusakinisha APK ya “muujiza” kutoka kwenye mjadala wa jukwaa.",
    "Ingia, tafuta Aviator kwenye lobby, au vinjari vichupo vya crash / maarufu na kichujio cha Spribe. Ikiwa haipo, usilazimishe amana ukihimiza itaonekana baadaye.",
    "App za kamari huvunjika kama programu nyingine — isipokuwa skrini nyeusi kwenye APK ya kubahatisha inaweza pia kumaanisha umesakinisha malware inayojifanya Aviator.",
    "Ikiwa app inafunguka kwenye nyeusi:",
    "Funga kwa nguvu na uifungue tena.",
    "Washa upya simu au laptop.",
    "Bado imevunjika? Ondoa usakinishaji na chukua build mpya kutoka kwa operator unayemwamini tayari.",
    "Ikiwa sasisho zimeshindwa:",
    "Thibitisha Wi-Fi au data ya simu ni thabiti.",
    "Achia mamia kadhaa ya megabytes — paketi za patch hazipendi diski zenye nafasi ndogo.",
    "Bado umefungiwa? Hakikisha binary ilitoka kwenye chaneli rasmi ya operator, si CDN ya mirror ya zamani.",
    "Ikiwa pesa haionekani kamwe:",
    "Baadhi ya njia zinahitaji saa 24&ndash;48; soma SLA ya malipo yenyewe kabla ya kufadhaika.",
    "Ikiwa leja inabaki tupu baada ya dirisha hilo, zungumza na processor au msaada wa kasino kabla ya kurudia amana.",
    "Je, kuna app ya Aviator inayosimama peke yake rasmi kutoka Spribe?Hakuna paketi ya pesa halisi ya kimataifa inayotolewa kama memes zinavyopendekeza. Kawaida unaingia kupitia app za operator au kikao cha kivinjari uliokuingia.",
    "Je, naweza kupakua Aviator kwenye Android na iPhone?Ndiyo — lakini ikoni kwenye skrini ya nyumbani mara nyingi ni wrapper ya nyumba, si binary moja ya Spribe duniani kote.",
    "Je, download ya APK daima ni chaguo bora kwenye Android?La, wakati build ya kivinjari inaendeshwa vizuri au mwenyeji wa APK anaonekana asiyejulikana. Wavuti inaweza kuwa njia nzuri zaidi.",
    "Je, ninahitaji app tofauti kucheza Aviator?Hapana. Tovuti nyingi zilizodhibitiwa zinaendeshwa vizuri kwenye Safari ya simu au Chrome; kuacha usakinishaji pia kunaepuka vidokezo bandia vya “update”.",
    "Je, naweza kucheza Aviator kwa pesa halisi kwenye app yoyote ninayopata mtandaoni?Hapana. Mchezo wa fedha unafaa tu nyuma ya leseni uliyokagua mwenyewe. Kurasa za download za SEO za kubahatisha hazihesabiki kama uangalifu.",
    "App au operator: unapaswa kuamini nani kwanza?Operator anashinda. Leseni, historia ya malipo, na majibu ya msaada yanashinda umeanzia APK au alamisho.",
    "Kamari kwa uwajibikaji: Tovuti hii ni jukwaa la taarifa huru na haihusiani na operator wanaotajwa. Fuata umri wa kisheria na sheria za eneo kabla ya kucheza kamari, na uichukulie viungo vya download visivyojulikana kama viambatisho vya barua pepe kutoka kwa wageni.",
]

# --- Page #5 Lingala (FR -> LN) ---
LN5 = [
    "Téléchargement ya app Aviator : ndenge ya kokota na lisano na Android, iPhone mpe PC",
    "Application Aviator ezali nini ?",
    "Ba exigences système mpo na APK Aviator",
    "Ndeni nini ko-télécharger app ya lisano Aviator",
    "Ko-installer APK Aviator na Android",
    "Ko-installer Aviator na iOS",
    "Ko-installer Aviator na Windows",
    "Version web Aviator",
    "Lisano Aviator : app vs site mobile",
    "App na Android, iOS mpe PC : ba différence ya liboso",
    "App oyo ekoki ko-télécharger vs site mobile",
    "Nini eleki malamu mpo na kobeta Aviator ?",
    "Ndeni nini kosalela app ya pari Aviator",
    "Kobeta Aviator na mbongo ya solo na app",
    "Kopona casino ya solo",
    "Kosala compte",
    "Ko-télécharger app mobile",
    "Ko connecter mpe kozua lisano",
    "Ba problème oyo ekoki mpe ba solution",
    "Écran noir",
    "Ba problème ya mise à jour",
    "Dépôt oyo elongi te",
    "FAQ",
    "Accès na app Aviator na mobile mpe ordinateur",
    "Kokota Aviator via app Android to APK",
    "Kosalela Aviator na iPhone to iPad",
    "Accès Aviator via ba app mpe web mobile",
    "Aviator ezali crash game oyo bato bakamwa nokinoki : mibeko pépé, ba round ya mokuse, mpe esalaka malamu na téléphone to ordinateur portable. Lelo barre ya recherche etondi na « Aviator app ». Ligne oyo eponi yo temps : Spribe epesaka te installateur « officiel » moko ya solo mpo na Aviator na mbongo ya solo mpo na bato nionso.",
    "Mbala mingi okokota na lisano via casino to bookmaker oyo e héberger yango na lobby na bango. Fait moko wana e changer sens ya « télécharger Aviator » — version demo ya solo, app ya opérateur oyo ezali na Aviator na kati, to page APK ya doute oyo esengeli koboya.",
    "Soki olingi kobeta na solo, ba question ya malamu ezali : licence, ba paiement oyo ekoki ko vérifier, mpe soki session navigateur na site moko ekoki kozala déjà enough. Libellé ya « dernier APK » e résoudre ata eloko moko te.",
    "« Aviator app », ezali moto ya malamu te. Mbala mingi bato balingi koloba moko na ba eloko misato : paquet demo ya solo, app ya opérateur oyo ezali na licence mpe oyo e lister Aviator, to raccourci navigateur oyo e pincer na écran d’accueil. Mpo na basaleli mingi, nzela ya mibale mpe ya misato pe nde ezali ya solo.",
    "Bolimbola myth ya téléchargement officiel moko mpo na monde mobimba. Kanisa na format ya accès : installer via marque oyo o confiance, to kofungola lisano na navigateur mpe kotya raccourci lokola icône ya app.",
    "Spécification",
    "Version actuelle",
    "Crash betting game",
    "Ebele",
    "Internet",
    "Esengeli",
    "Développeur",
    "Installation nionso — APK ya opérateur, build sideloadé, to enveloppe ya mince — esengeli mpe appareil oyo ekoki kopumua. Ba chiffre e changer selon source, kasi liste ezali pépé mpe universelle : espace libre, connexion stable, mpe OS ya solo oyo ezali na ba patch ya sécurité.",
    "Paramètre",
    "10.0 to version ya nsima",
    "11.0 to version ya nsima",
    "60&ndash;110 MB (APK)",
    "35&ndash;130 MB (IPA)",
    "Plus de 1 Go",
    "Plus de 1,2 Go",
    "4G, 5G, Wi‑Fi",
    "Ko vérifier opérateur eleki koluka nkombo ya fichier. Soki licence ya site e zala na nguya moke, ba retrait e zala lokola fiction, to support e répondi ata mbala moko te, APK ezali déjà mauvaise bifurcation.",
    "Pona opérateur oyo ezali na licence mpe oyo e lister Aviator solo na app to site mobile na ye.",
    "Fungola site ya opérateur wana na appareil Android mpe salela bloc ya téléchargement na bango.",
    "Zua APK kaka na bango to na source oyo okoki ko vérifier de bout en bout — te na miroir ya hasard.",
    "Soki Android esengi ba install depuis sources inconnues, activer option yango kaka soki o confiance na origine ya fichier.",
    "Installer, connecter, mpe vérifier Aviator ezali na lobby liboso ya ko alimenter portefeuille.",
    "Pona opérateur ya solo oyo e supporter Aviator na iPhone to iPad.",
    "Fungola site mobile ya opérateur to page ya app na appareil.",
    "Suivre parcours iOS oyo babandaka — TestFlight, profil enterprise, to lien App Store — jamais dump IPA ya moto oyo oyebi te.",
    "Fungola app to raccourci oyo esili, connecter, mpe beta round ya test liboso ya kotya confiance na yango mpo na mbongo.",
    "Pona opérateur réglementé oyo e lister Aviator na ordinateur.",
    "Tanga site na bango : vrai client Windows, to lisano navigateur kaka.",
    "Soki bapesi installateur bureau, télécharger yango kaka na domaine na bango.",
    "Connecter mpe vérifier crash game e charger liboso ya kotya binary yango lokola porte principale na yo mpo na Aviator.",
    "Kobeta na navigateur e sous-estimer mingi. Soki build natif ezali te, to olingi te executable mosusu na disque, site moko ezali app :",
    "Fungola URL ya opérateur na Chrome, Safari to Edge.",
    "Na nsima ya chargement, salela menu ya navigateur mpe pona « Ajouter à l’écran d’accueil » (libellé e changer selon appareil).",
    "Ozua icône oyo efungola session moko — ata paquet ya solo ya ko surveiller te.",
    "Ata gagnant couronné ezali te awa. Ba téléphone e différent, niveau ya confiance e différent, mpe bato mosusu balingi ba mise à jour koleka ba favori.",
    "Fonctionnalité",
    "Interface",
    "Adaptée na ba petits écrans",
    "Optimisée mpo na tactile",
    "Pensée mpo na ba grands écrans, souris mpe clavier",
    "Performances",
    "Ekoki kozala different na ba appareils ya solo",
    "Mbala mingi fluide na ba appareils oyo e supporter",
    "Bonnes perfs na matériel solide",
    "Mises à jour",
    "Manuelles to automatiques",
    "Mbala mingi manuelles, mbala mingi na nsima ya invite",
    "Compatibilité",
    "Android 5.1+",
    "iOS 11.0+",
    "Ba versions Windows ya lelo mingi",
    "App oyo ekoki ko-télécharger",
    "Site mobile",
    "Esengi installation",
    "Installation ezali te",
    "Soumise na ba exigences matérielles",
    "Ba exigences spécifiques ezali te",
    "Ekoki kotinda ba notifications push",
    "Ba alertes na navigateur kaka",
    "Esengi ba mise à jour mbala na mbala",
    "Toujours à jour",
    "Esalaka lokola app distincte",
    "Efungwaka kaka na navigateur",
    "E s’intègre na OS",
    "Intégration OS ezali te (ex. widgets, Face ID)",
    "App packagée ekoki kozala native mpe ko mettre ba raccourcis compte nokinoki — kasi chaque mise à jour ya installateur e rappeler ba choix ya confiance.",
    "Site mobile e éviter encombrer disque, e rester à jour sans ko appuyer « mettre à jour », mpe e diminuer risque ya kozua mauvais APK na domaine homophone.",
    "Na enveloppe ya opérateur ya solo, Aviator esalaka ndenge oyebi déjà : multiplier ekobaka, obetaka Cash Out liboso ya avion kokende. Mise en page e changer ; mécanique e changer te.",
    "Ba hôtes mingi e exposer ba mêmes contrôles pratiques — mise manuelle, auto-bet, auto Cash Out soki e supporter. Stabilité ya écran eleki marketing ya ba build « officiels ».",
    "O débutant na mobile ? Beta demo liboso soki site e proposer, yekola ba zones tactiles, mpe tala soki jeu payant ezali encore raisonnable.",
    "Installateur ezali porte. Opérateur ezali immeuble. Soki okota na mauvais lobby, porte moko e zala na ntina te.",
    "Luka licence oyo okoki ko cliquer tii na registre, mibeko ya Cash Out na langage clair, mpe support oyo e répondre liboso ya dépôt, te kaka nsima.",
    "Salela site propre ya opérateur to app oyo ezali na store. Vrai nom, vrai e-mail, mot de passe solide — KYC na nsima e pardonner te ba données ya inscription ya mabe.",
    "Na nsima ya compte, suivre procédure Android to iOS oyo marque moko e publier. Soki bapesaka kaka web, yango mbala mingi ezali plus sûr koleka sideloader APK « miracle » na fil ya forum.",
    "Connecter, koluka Aviator na lobby, to parcourir onglets crash / populaires mpe filtre Spribe. Soki ezali absent, ko forcer dépôt na espoir ete e bimaka nsima te.",
    "Ba app ya jeu e casser lokola logiciel mosusu — sauf écran noir na APK ya hasard ekoki mpe koloba ofonaki malware oyo e cosplay Aviator.",
    "Soki app efungwaka na noir :",
    "Force fermeture mpe relancer.",
    "Redémarrer téléphone to ordinateur portable.",
    "Ezali encore cassé ? Désinstaller mpe zua build ya sika kaka na opérateur oyo o confiance déjà.",
    "Soki ba mise à jour echouer :",
    "Vérifier Wi‑Fi to data mobile ezali stable.",
    "Libérer ba centaines ya mégaoctets — ba patch e détester ba disques pleins.",
    "Ezali encore bloqué ? Assurer binary e vient na canal officiel ya opérateur, te na vieux miroir CDN.",
    "Soki mbongo e bimaka jamais :",
    "Ba rails mosusu esengi 24&ndash;48 h ; tanga SLA ya moyen ya paiement liboso ya paniquer.",
    "Soki registre e rester vide na nsima ya délai wana, koloba na processeur to support casino liboso ya répéter ba dépôts.",
    "Ezali app Aviator ya solo « officielle » ya Spribe ?Ata paquet universel na mbongo ya solo e circuler te lokola memes e suggérer. Mbala mingi okokota via ba app ya opérateur to session navigateur connectée.",
    "Nakoki ko-télécharger Aviator na Android mpe iPhone ?Ee — kasi icône na écran d’accueil mbala mingi ezali enveloppe ya maison, te binary moko ya Spribe mpo na monde mobimba.",
    "Téléchargement APK ezali toujours meilleur choix na Android ?Te soki version navigateur ezali fluide to hébergeur APK e zala anonyme. Web ekoki kozala nzela ya solo.",
    "Esengeli app séparée mpo na kobeta Aviator ?Te. Ba site réglementés mingi esalaka malamu na Safari mobile to Chrome ; koboya ba install e éviter mpe ba fausses invites ya « mise à jour ».",
    "Nakoki kobeta Aviator na mbongo ya solo na app nionso oyo nazui online ?Te. Jeu payant ezali kaka na sima ya licence oyo overifié yo moko. Ba pages download SEO ya hasard e valoir te diligence.",
    "App to opérateur : na nani ko confiance liboso ?Opérateur e gagner. Licence, historique ya paiements mpe réactivité ya support eleki kozala ofungoli na APK to favori.",
    "Jeu responsable : Site oyo ezali plateforme ya information indépendante mpe ezali na lien te na ba opérateurs oyo elobami. Respecter âge légal mpe réglementation locale liboso ya kobeta, mpe traiter ba liens ya téléchargement oyo oyebi te lokola ba pièces jointes ya e-mail ya bato oyo oyebi te.",
]

# --- Page #6 Swahili (EN -> SW) ---
SW6 = [
    "Aviator Predictor: APK, iOS, RNG na Kwa Nini Bado Haifanyi Kazi",
    "Utabiri wa Aviator Unapaswa Kufanya Kazi Vipi",
    "Kupakua Aviator Predictor kwenye Android na iOS",
    "Pata predictor kwenye Android",
    "Pata predictor kwenye iPhone au iPad",
    "Kuingia kwenye bot ya predictor",
    "Kutumia vidokezo vya predictor wakati wa mchezo",
    "Kwa Nini Aviator Haiwezi Kutabiriwa (Hata na AI)",
    "Predictor na Kasino za Mtandaoni",
    "Predictor ya Aviator ya 1xBet",
    "Predictor ya Aviator ya 1Win",
    "Predictor ya Aviator ya Melbet",
    "MSport na Hollywood Bet",
    "Betway na Betplay",
    "Matoleo Tofauti ya Programu ya Predictor",
    "Aviator Predictor v4.0",
    "Aviator Predictor v12.0.5",
    "Bot ya predictor ya Aviator premium",
    "FAQ",
    "Madai ya predictor ya Aviator na kiolesura cha crash game",
    "Mfano wa kiolesura cha app ya predictor ya Aviator",
    "APK ya predictor ya Aviator kwenye kifaa cha Android",
    "Dhana ya app ya predictor ya Aviator kwenye iPhone na iPad",
    "Madai ya predictor ya Aviator ukilinganisha na mipaka ya AI na RNG",
    "Matoleo ya predictor yanayouzwa karibu na chapa maarufu za kasino za Aviator",
    "App za predictor zinauza njia ya mkato: zinasema zinasoma mifumo, kukisia crash inayofuata, au kuweka lebo ya multiplier kama “salama” kabla ya Cash Out. Hadithi hiyo inawafanya watu kutafuta APK, sideload za iPhone, bot za Telegram, na programu-jalizi za kivinjari.",
    "Hapa ndipo tatizo liko: Aviator inaendeshwa na RNG ndani ya sheria za provably fair. Hakuna app ya nje inayoweza kujua raundi inayofuata kabla ya seva. Baadhi ya vifuniko ni makisio yenye vitufe vya neon; vingine ni ulaghai kamili vinavyoongeza hatari ya kifaa na wizi wa data juu ya hatari ya kawaida ya kamari.",
    "Wauzaji huzungumza kana kwamba randomness ina mgongo wanaoweza kuhisi. Hadithi hubadilika — multiplier za zamani, “volatility scans”, mpigo wa timing, au beji ya “AI” — lakini mstari ni ule ule: zana inajifanya paneli ya historia ni mpira wa kioo.",
    "Raundi za haraka na ticker iliyojaa hufanya hadithi hiyo ionekane karibu na busara kwa mgeni. Raundi za zamani bado hazikupi ramani ya inayofuata. Upeo wa juu unapata uhuishaji wa ujasiri. Ni maigizo, si uvujaji wa crash inayofuata.",
    "Utafutaji wa simu hugawanyika njia mbili: wenyeji wa APK wasiojulikana kwa Android, na kwa iOS mchanganyiko wa viungo vya mwaliko, ganda la wavuti, na usakinishaji wa “enterprise” unaiga polish ya App Store. Njia zote mbili zinaweza kuonekana bila msuguano.",
    "Bila msuguano si sawa na halali. Kabla ya kusakinisha chochote kwa njia ya upande, uliza kwa nini mtu usiyemjua anastahili uaminifu kwenye simu yako, pochi, au kuingia kasino ikiwa hawawezi kuonyesha uthibitisho mmoja wa utabiri uliochunguzwa.",
    "Njia za Android karibu kila wakati huelekeza kwenye APK za mbichi — mara chache kwenye orodha ya store iliyokaguliwa. Mchezo unajirudia:",
    "Fungua tovuti, ukurasa wa kutua, au chaneli inayosukuma APK ya predictor.",
    "Tambua majina kama “Aviator predictor”, “casino predictor”, au nambari ya build inayojivuna kuhusu usahihi.",
    "Pakua APK kwenye simu.",
    "Ikiwa Android inazuia, washa usakinishaji kutoka vyanzo visivyojulikana.",
    "Zindua paketi na fuata hatua za usajili, kuunganisha, au arifa inazohitaji.",
    "Ndio maana Android ina hatari ya ziada. APK isiyo rasmi inamaanisha uliamini aliyeisaini — si fizikia. Usakinishaji uliovunjika, ruhusa za kuingilia, nakala, na malware wazi ni ya kawaida zaidi kuliko faida ya siri dhidi ya RNG.",
    "Matoleo ya iOS mara nyingi yanakuja kwa ufungaji tofauti:",
    "Tafuta App Store, ganda la wavuti, au mwaliko wa kibinafsi kwa kitu kinachoandikwa Aviator predictor.",
    "Fungua orodha au mwaliko na soma kinachodai haswa.",
    "Pakua au sakinisha kwa njia ya upande ikiwa njia hiyo ipo kwa wasifu wa kifaa chako.",
    "Fungua kutoka skrini ya nyumbani au njia ya mkato baada ya usakinishaji kukamilika.",
    "Jisajili, ingia, au wezesha arifa ikiwa muuzaji anasisitiza.",
    "Zana nyingi huficha “ishara” hadi uunde akaunti. Lango hilo linawaruhusu kukutumia barua taka, kuuza viwango vya premium, na kuchukua alama za tabia yako. Mtiririko unabaki mfupi:",
    "Fungua app, bot, au dashibodi kwenye simu au desktop.",
    "Bonyeza kitufe cha usajili.",
    "Jaza sehemu wanazofungua — mara nyingi barua pepe na nenosiri tu.",
    "Kubali masharti, ruhusa za push, au swichi za arifa ukilazimishwa.",
    "Mchawi wa usajili unaonekana isiyo na hatia. Nyuma yake kuna biashara halisi: wanaweza tayari kushikilia njia yako ya mawasiliano, haki za kifaa, na tabia yako ya kamari. Hakuna hiyo inagusa RNG ndani ya Aviator.",
    "Baada ya kuingia kwa kawaida unaona mpigo, vipima muda, au lebo zinazomaanisha raundi hii “inaonekana moto”. Baadhi ya mtiririko hukufanya kuchagua chapa ya kasino kwanza. Vingine vinaonyesha kitufe cha Anza, skani ya uwongo ya moja kwa moja, kisha kutoa dirisha la multiplier. Grafu za harakati zilizopangwa bado zinauza makisio. Hazifikii mbegu za baadaye, seva zilizofichwa, au build ya kibinafsi ya mchezo.",
    "Mchezo unaweza kujifunza kama burudani; hauwezi kutabiriwa kama hali ya hewa. Iwe broshua inasema hesabu, bot, AI, au sayansi ya mifumo, kizuizi ni kile kile: kila raundi inasimama peke yake, na watu wa nje hawawezi kusoma matokeo mapema.",
    "Randomness: Raundi zinatolewa na mantiki ya RNG iliyolindwa, hivyo historia ya umma haiwezi kubadilishwa nyuma kuwa cheat code thabiti.",
    "Muundo wa haki: Kila mtu anacheza chini ya sheria sawa. Kuona crash za baadaye kwa kweli kungevunja bidhaa na usingesurvive kimya ndani ya APK ya soko kijivu.",
    "Historia finyu: Multiplier za zamani zinaonekana zenye maana kwenye chati; bado hazifanyi kazi kama mfano wa kuaminika wa kuondoka inayofuata.",
    "Kurasa za ulaghai hukopa nembo kubwa za chapa ili ofa ihisi imethibitishwa. Nakala inaweza kudokeza APK ime “rekebishwa” kwa operator mmoja au inatumia udhaifu wa mandhari hiyo. Ikiwa raundi bado inaendeshwa na RNG, kifuniko ni rangi, si nguvu.",
    "Utafutaji unaohusiana na 1xBet mara nyingi huvuta bot zinazoamini zinaboresha timing au kuweka alama kwa raundi “zenye nguvu”. Chapa hubadilika; madai hayabadiliki — programu ya nje inashinda injini ya kubahatisha. Ushahidi haupo.",
    "Paketi za mandhari ya 1Win zinaahidi UI safi, mzigo mdogo wa simu, au arifa za sauti zaidi. Ufungashaji bora hauondoi matokeo ya kubahatisha; unaivisha makisio yale yale.",
    "Matangazo ya Melbet yanapenda maneno kama “signal app” au “smart crash timing”. Msamiati wa uchambuzi ni vipodozi juu ya nguruwe ile ile: hakuna ujuzi unaothibitishwa wa kuita crash inayofuata.",
    "Katika MSport na Hollywood Bet, predictor husafiri kupitia machapisho ya kijamii, paketi za APK, na mazungumzo ya upande yanayoamini yanaweza “kusoma” ubao. Randomness iliyothibitishwa haipunguzwi kwa sababu nembo imebadilika. Hatari yako ya adabu ya akaunti au faili zilizoambukizwa inaweza kupanda.",
    "Majina ya Betway na Betplay pia hutumika kama nanga za uaminifu — dau salama zaidi, kuingia kwa akili, makosa machache. Mtazamo baridi: nidhamu ya bankroll na radar ya ulaghai vinashinda ukuta wowote wa ishara.",
    "Wauzaji mara chache hutoa SKU moja. Nambari za toleo, uboreshaji wa “AI”, na viwango vya VIP zipo kuonyesha kasi. Lebo mpya hazithibitishi uaminifu au usahihi.",
    "Beji za zamani kama v4.0 huonyeshwa kama zilizojaribiwa vitani. Uuzaji unaweza kusema inachambua multiplier za zamani, inaunganishwa na 1xBet au Mostbet, au inatoa vidokezo vya kuingia vinavyofaa. Historia bado haiwezi kushinda RNG.",
    "Build zenye nambari za juu zinauza kasi, uangavu, na “usahihi”. Vichujio au lugha za ziada zinaweza kuhisi daraja la biashara; uwasilishaji si uthibitisho kwamba utabiri unafanya kazi.",
    "Paywall zinauza injini ya “kweli” nyuma ya onyesho la bure. Mara nyingi unanunua uuzaji wenye sauti zaidi, ruhusa pana zaidi, au vioo vya APK vyenye shaka — si faida.",
    "Je, app za predictor ya Aviator zinafanya kazi kweli?Hakuna kitu cha umma kimeonyesha uwezo wa kuona crash points kabla ya raundi. Skrini za ujasiri si uthibitisho.",
    "Je, ni salama kusakinisha predictor ya Aviator?Chukulia hatari kwa chaguo-msingi. Vyanzo visivyo rasmi, ruhusa za kula, malware, ulaghai wa data, na shida za akaunti ziko juu zaidi kwenye uwezekano kuliko ishara ya kichawi.",
    "Je, AI inaweza kutabiri Aviator bora kuliko app za kawaida?Hapana. AI inaweza kuvika makisio kwenye chati; bado haiwezi kusoma michoro ya RNG ya baadaye au kubadilisha multiplier za zamani kuwa uhakika.",
    "Kwa nini baadhi ya wachezaji wanasema predictor husaidia?Ishara huongeza rhythm na faraja ya uwongo. Kuhisi mpangilio si kuona siku zijazo.",
    "Hatari kuu na faili za APK za predictor ni nini?Hasara mbaya zaidi mara nyingi si dau mbaya — ni usakinishaji uliotiwa sumu, hati zilizoibiwa, au ulaghai unauza uhakika.",
    "Ni mbadala salama gani wa kufuatilia predictor?Jifunze jinsi Aviator inavyofanya kazi, tumia salio la demo inapopatikana, weka mipaka ya kikao, na uichukulie kila mstari wa “timing iliyohakikishwa” kama ishara ya ulaghai.",
    "Kamari kwa uwajibikaji: Tovuti hii ni chanzo cha taarifa huru na haihusiani na operator waliotajwa hapo juu. Angalia umri wa kisheria na sheria za eneo kabla ya kucheza kamari, na uichukulie viungo vya nje na matoleo ya app za watu wengine kwa uangalifu.",
]

# --- Page #6 Lingala (FR -> LN) ---
LN6 = [
    "Prédicteur Aviator : APK, iOS, RNG mpe mpo na nini e salaka noki te",
    "Ndeni « prédiction » Aviator esengeli kosala",
    "Ko-télécharger prédicteur Aviator na Android mpe iOS",
    "Kozua prédicteur na Android",
    "Kozua prédicteur na iPhone to iPad",
    "Ko connecter na bot prédicteur",
    "Kosalela ba indices prédicteur na kati ya partie",
    "Mpo na nini Aviator e prédire te (ata na AI)",
    "Ba prédicteurs mpe ba casinos online",
    "Prédicteur Aviator 1xBet",
    "Prédicteur Aviator 1Win",
    "Prédicteur Aviator Melbet",
    "MSport mpe Hollywood Bet",
    "Betway mpe Betplay",
    "Ba versions mosusu ya logiciel prédicteur",
    "Aviator Predictor v4.0",
    "Aviator Predictor v12.0.5",
    "Bot prédicteur Aviator premium",
    "FAQ",
    "Ba promesse ya prédicteur Aviator mpe interface ya crash game",
    "Exemple ya interface ya app prédicteur Aviator",
    "APK prédicteur Aviator na appareil Android",
    "Concept ya app prédicteur Aviator na iPhone mpe iPad",
    "Ba promesse ya prédicteur Aviator vs ba limites ya AI mpe RNG",
    "Ba offres prédicteur calées na ba marques casino Aviator populaires",
    "Ba app « prédicteur » e vendre raccourci : e prétendre kolire ba motifs, kobosana crash oyo ekoya, to kotya étiquette « sûre » na multiplier liboso ya Cash Out. Discours oyo e pousse bato koluka ba APK, ba sideload iPhone, ba bots Telegram mpe ba extensions navigateur.",
    "Problème ezali awa : Aviator esalemi na RNG na kati ya mibeko provably fair. Ata app ya libanda e koyeba te round oyo ekoya liboso ya serveur. Ba coques mosusu ezali kaka ba supposition na ba boutons néon ; ba mosusu ezali vraies arnaques oyo e ajouter risque appareil mpe vol ya ba données na risque ya solo ya jeu.",
    "Ba vendeurs balobaka lokola hasard ezalaki na colonne vertébrale oyo bakoki ko palper. Histoire e changer — ba multipliers ya kala, « scans de volatilité », impulsions ya timing, to badge « AI » — kasi ligne ezali moko : outil e faire croire historique ezali boule de cristal.",
    "Ba round ya nokinoki mpe ticker ya mobimba e rendre récit yango presque crédible mpo na débutant. Ba tours passés e pesa noki te carte ya oyo ekoya. Na maximum ozua animation ya confiance. Ezali théâtre, te fuite ya crash oyo ekoya.",
    "Ba recherche mobile e se diviser : ba hébergeurs APK anonymes mpo na Android, mpe mpo na iOS brouhaha ya ba liens invitation, coques web mpe ba install « entreprise » oyo e imiter polish App Store. Ba deux chemins ekoki kozala sans accroc.",
    "Sans accroc e zala te synonyme ya légitime. Liboso ya sideloader eloko, demander mpo na nini moto oyo oyebi te azalaki na confiance na téléphone, portefeuille to login casino soki akoki ko montrer ata preuve auditée moko ya prédiction.",
    "Ba tunnels Android e pointer presque toujours vers ba APK bruts — mbala moke vers fiche store oyo e vérifier. Chorégraphie e répéter :",
    "Fungola site, landing to canal oyo e pousser APK prédicteur.",
    "Repérer ba noms lokola « prédicteur Aviator », « prédicteur casino », to numéro ya build oyo e vanter précision.",
    "Télécharger APK na téléphone.",
    "Soki Android e bloquer, activer ba install depuis sources inconnues.",
    "Lancer paquet mpe suivre inscription, appairage to notifications soki e exiger.",
    "Recette oyo e expliquer danger supplémentaire na Android. APK non officiel, ezali ko confiance na oyo asigné ba bytes — te na physique. Ba install cassées, permissions envahissantes, clones mpe malware pur e zala mingi koleka avantage secret vs RNG.",
    "Ba offres iOS e arriver souvent na emballage different :",
    "Luka App Store, coque web to invitation privée mpo na eloko oyo e étiqueter prédicteur Aviator.",
    "Fungola fiche to invitation mpe tanga oyo e prétendre mot na mot.",
    "Télécharger to sideloader soki nzela ezali mpo na profil appareil na yo.",
    "Fungola na écran d’accueil to raccourci na nsima ya install.",
    "S’inscrire, se connecter to activer alertes soki vendeur e insister.",
    "Ba outils mingi e cacher « signaux » tii o créer compte. Portillon oyo e permetre spam, monter paliers payants mpe empreinter comportement. Parcours e rester court :",
    "Fungola app, bot to dashboard na mobile to bureau.",
    "Appuyer contrôle ya inscription.",
    "Remplir ba champs oyo e ouvrir — mbala mingi e-mail mpe mot de passe kaka.",
    "Accepter conditions, permissions push to notifications soki e forcer.",
    "Assistant ya inscription e zala innocent. Na sima ezali vrai marché : bakoki déjà kozala na contact na yo, droits appareil mpe ba habitudes ya jeu. Rien na yango e toucher RNG na kati ya Aviator.",
    "Na nsima ya connexion ozali kaka na ba pulsations, minuteurs to étiquettes oyo e suggérer round oyo « chauffe ». Ba flux mosusu e faire kopona marque casino liboso. Ba mosusu e montrer Démarrer, faux scan live, mpe ba fenêtres ya multipliers. Motion design ya malamu e vender mpe ba suppositions. E atteindre te ba graines futures, ba serveurs cachés to build privée ya lisano.",
    "Lisano e prêter na divertissement ; e prédire te lokola météo. Que brochure e koloba maths, bots, AI to science ya motifs, barrière ezali moko : round nionso ezali isolée, mpe ba outsiders e koyeba te résultat liboso.",
    "Hasard : ba rounds e vient na logique RNG sécurisée ; historique public e reverse-engineer te na cheat code stable.",
    "Conception équitable : bato nionso e betaka na mibeko moko. Vrai aperçu ya ba crashs oyo ekoya e casser produit mpe e survivre te tranquillement na APK gris.",
    "Historique maigre : ba vieux multipliers e paraitre parlants na graph ; e zala mauvais modèle fiable mpo na prochain décollage.",
    "Ba pages arnaque e emprunter ba gros logos mpo na offre e zala validée. Texte ekoki kolobela APK e « calibré » mpo na opérateur moko to e exploiter faille ya thème wana. Soki round e rester pilotée na RNG, enveloppe ezali peinture, te puissance.",
    "Ba recherche liées na 1xBet e attirer ba bots oyo e jurer affiner timing to repérer ba rounds « forts ». Branding e changer ; affirmation e changer te — logiciel externe oyo e battre moteur aléatoire. Ba preuves e manquer.",
    "Ba packs thème 1Win e promettre UI plus propre, mobile plus léger to alertes plus bruyantes. Meilleur emballage e effacer te ba issues aléatoires ; e habiller ba mêmes suppositions.",
    "Ba promos Melbet balingi ba termes « appli signal » to « timing crash intelligent ». Vocabulaire analytique ezali vernis na même impasse : ata compétence vérifiable te mpo na annoncer prochain crash.",
    "Na MSport mpe Hollywood Bet, ba prédicteurs e circuler via posts sociaux, bundles APK mpe chats parallèles oyo e jurer « lire » tableau. Hasard certifié e adoucir te mpo na logo e changer. Exposition na yo na sanctions compte to fichiers infectés ekoki kozala monene.",
    "Ba noms Betway mpe Betplay e servir mpe ancres ya confiance — paris plus sûrs, entrées plus malines, ba erreurs moins. Lecture plus froide : discipline bankroll mpe radar arnaque e battre n’importe quel fond d’écran ya signaux.",
    "Ba vendeurs e livrer mbala moke SKU moko. Ba numéros version, upgrades « AI » mpe paliers VIP e suggérer élan. Ba étiquettes plus récentes e prouver te honnêteté to justesse.",
    "Ba vieux badges v4.0 e vendre lokola éprouvés. Marketing ekoki koloba e parse ba multipliers passés, e brancher na 1xBet to Mostbet, to e pesa repères concrets. Historique e passer outre RNG noki te.",
    "Ba builds na gros chiffres e vendre vitesse, brillance mpe « précision ». Ba filtres to langues supplémentaires ekoki kozala ton entreprise ; présentation e zala preuve te ete prédiction e tenir.",
    "Ba paywalls e vendre « vrai » moteur na sima ya démo gratuite. Mbala mingi opeleka marketing plus criard, ramassages permissions plus larges to miroirs APK plus louches — te avantage.",
    "Ba app prédicteur Aviator e salaka solo ?Rien ya public e montrer prévision fiable ya crashs liboso ya round. Ba écrans confiance e zala preuve te.",
    "Ezali sûr ko installer prédicteur Aviator ?Partir principe ya risque. Ba sources non officielles, permissions voraces, malware, hameçonnage mpe galères compte e zala mingi koleka signal magique.",
    "AI ekoki prédire Aviator malamu koleka ba app classiques ?Te. AI ekoki habiller ba suppositions na ba graphiques ; e koyeba te ba tirages RNG ya mikolo mpe e transformer te ba multipliers ya kala na certitude.",
    "Mpo na nini basaleli mosusu balobaka prédicteurs e salaka ?Ba signaux e ajouter rythme mpe faux réconfort. Ko sentir structuré e zala te komona l’avenir.",
    "Risque principal na ba fichiers APK prédicteur ezali nini ?Perte ya solo mbala mingi e zala te mauvais pari — ezali install empoisonnée, identifiants volés to arnaque oyo e vendre certitude.",
    "Nini nzela plus saine koleka koluka ba prédicteurs ?Koyeba comportement ya solo ya Aviator, kosalela solde demo soki e proposer, limiter ba sessions, mpe traiter promesse ya « timing garanti » lokola signal arnaque.",
    "Jeu responsable : Site oyo ezali ressource ya information indépendante mpe ezali na lien te na ba opérateurs oyo elobami. Vérifier âge légal mpe réglementation locale liboso ya kobeta, mpe zala prudent na ba liens externes mpe ba offres app tierces.",
]

META5 = {
    "sw": {
        "name": "Pakua",
        "title": "Pakua App ya Aviator: Android, iPhone, PC na Kivinjari",
        "description": "Jifunze jinsi ya kufikia Aviator kwenye Android, iPhone na PC, lini APK ni hatari, na kwa nini kivinjari kinaweza kuwa salama zaidi.",
    },
    "ln": {
        "name": "Télécharger",
        "title": "Télécharger App Aviator: Android, iPhone, PC mpe Navigateur",
        "description": "Yeba ndenge ya kokota Aviator na Android, iPhone mpe PC, tango APK ezali riski, mpe mpo na nini navigateur ekoki kozala plus sûr.",
    },
}

META6 = {
    "sw": {
        "name": "Predictor",
        "title": "Aviator Predictor: APK, iOS, RNG na Kwa Nini Haifanyi Kazi",
        "description": "App za predictor za Aviator: yanayodai, kwa nini RNG inazuia utabiri halisi, hatari za APK na iOS, na njia salama zaidi.",
    },
    "ln": {
        "name": "Prédicteur",
        "title": "Prédicteur Aviator: APK, iOS, RNG mpe Mpo na Nini E Salaka Te",
        "description": "Ba app prédicteur Aviator: oyo e prétendre, mpo na nini RNG e bloquer prédiction solo, ba risques APK mpe iOS, mpe ba nzela plus sûres.",
    },
}


def expand_html_pairs(keys: list[str], vals: list[str]) -> list[list[str]]:
    """Add split pairs where segment strings differ from HTML (FAQ, labelled lists)."""
    out: list[list[str]] = []
    seen: set[str] = set()
    for k, v in zip(keys, vals):
        out.append([k, v])
        extras: list[tuple[str, str]] = []
        qm = None
        for i, ch in enumerate(k):
            if ch == "?" and i + 1 < len(k) and k[i + 1].isupper():
                qm = i
                break
        if qm is not None:
            q, a = k[: qm + 1], k[qm + 1 :]
            vqm = v.find("?")
            if vqm >= 0:
                vq, va = v[: vqm + 1], v[vqm + 1 :]
            else:
                vq, va = v, v
            extras.extend([(q, vq), (a, va)])
        else:
            sep = " : " if " : " in k else (": " if ": " in k else None)
            if sep:
                label, rest = k.split(sep, 1)
                if len(label) < 40 and rest:
                    if sep == " : ":
                        vsep = " : " if " : " in v else ": "
                    else:
                        vsep = ": "
                    if vsep in v:
                        vlabel, vrest = v.split(vsep, 1)
                    else:
                        vlabel, vrest = v, v
                    extras.extend([
                        (f"{label}{sep.rstrip()}:", f"{vlabel.rstrip()}:" if not vlabel.endswith(":") else vlabel),
                        (rest, vrest),
                    ])
        for ek, ev in extras:
            if ek and ek not in seen:
                out.append([ek, ev])
                seen.add(ek)
        seen.add(k)
    return out


def build_page(page_id: int, sw_vals: list[str], ln_vals: list[str], meta: dict) -> dict:
    en = load_segs(f"pages-{page_id}-en-segments.json")
    fr = load_segs(f"pages-{page_id}-fr-segments.json")
    sw_pairs = expand_html_pairs(en, sw_vals)
    ln_pairs = expand_html_pairs(fr, [polish_ln(v) for v in ln_vals])
    for lang in ("sw", "ln"):
        t, d = truncate(meta[lang]["title"], meta[lang]["description"])
        meta[lang]["title"] = t
        meta[lang]["description"] = d
    return {
        "ln_from_fr": True,
        "meta": meta,
        "pairs": {"sw": sw_pairs, "fr_ln": ln_pairs},
    }


def main() -> int:
    pages = [
        (5, SW5, LN5, META5),
        (6, SW6, LN6, META6),
    ]
    for pid, sw, ln, meta in pages:
        data = build_page(pid, sw, ln, meta)
        out = OUT / f"pages_{pid}.json"
        out.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Written {out} sw={len(data['pairs']['sw'])} fr_ln={len(data['pairs']['fr_ln'])}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
