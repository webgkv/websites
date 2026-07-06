# -*- coding: utf-8 -*-
"""Structured body + meta for casino_articles#26 BC.Game Ice Fish cluster."""

from __future__ import annotations

from copy import deepcopy

LOCALE_META = {
    "1": {
        "code": "en",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish on BC.Game: play, demo, crypto & app",
        "description": "Play Ice Fish on BC.Game: find InOut titles in the lobby, demo mode, mobile app, crypto bonuses, wagering rules and FAQ.",
        "url": "bc-game-ice-fish",
    },
    "3": {
        "code": "fr",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish sur BC.Game : jouer, démo, crypto et app",
        "description": "Jouez à Ice Fish sur BC.Game : titres InOut dans le lobby, mode démo, app mobile, bonus crypto, wager et FAQ.",
        "url": "bc-game-ice-fish",
    },
    "4": {
        "code": "de",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish auf BC.Game: Spielen, Demo, Krypto & App",
        "description": "Ice Fish auf BC.Game: InOut-Spiele im Lobby finden, Demo, Mobile-App, Krypto-Boni, Wager-Regeln und FAQ.",
        "url": "bc-game-ice-fish",
    },
    "6": {
        "code": "es",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish en BC.Game: jugar, demo, cripto y app",
        "description": "Juega Ice Fish en BC.Game: títulos InOut en el lobby, demo, app móvil, bonos cripto, wagering y FAQ.",
        "url": "bc-game-ice-fish",
    },
    "7": {
        "code": "hi",
        "name": "BC.Game - Ice Fish",
        "title": "BC.Game पर Ice Fish: खेलें, डेमो, क्रिप्टो और ऐप",
        "description": "BC.Game पर Ice Fish — InOut गेम खोजें, डेमो, मोबाइल ऐप, क्रिप्टो बोनस, वेजर नियम और FAQ।",
        "url": "bc-game-ice-fish",
    },
    "8": {
        "code": "pt",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish na BC.Game: jogar, demo, cripto e app",
        "description": "Jogue Ice Fish na BC.Game: títulos InOut no lobby, demo, app mobile, bônus cripto, wagering e FAQ.",
        "url": "bc-game-ice-fish",
    },
    "9": {
        "code": "ru",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish на BC.Game: игра, демо, крипто и приложение",
        "description": "Ice Fish на BC.Game: как найти InOut в лобби, демо, мобильное приложение, криптобонусы, вейджер и FAQ.",
        "url": "bc-game-ice-fish",
    },
    "11": {
        "code": "ar",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish على BC.Game: لعب وديمو وكريبتو وتطبيق",
        "description": "العب Ice Fish على BC.Game: اعثر على InOut في اللوبي، تجريبي، تطبيق موبايل، مكافآت كريبتو وFAQ.",
        "url": "bc-game-ice-fish",
    },
    "12": {
        "code": "az",
        "name": "BC.Game - Ice Fish",
        "title": "BC.Game-də Ice Fish: oyun, demo, kripto və app",
        "description": "BC.Game-də Ice Fish oynayın — lobidə InOut tapın, demo, mobil app, kripto bonuslar, wager və FAQ.",
        "url": "bc-game-ice-fish",
    },
    "13": {
        "code": "bn",
        "name": "BC.Game - Ice Fish",
        "title": "BC.Game-এ Ice Fish: খেলা, ডেমো, ক্রিপ্টো ও অ্যাপ",
        "description": "BC.Game-এ Ice Fish খেলুন — লবিতে InOut খুঁজুন, ডেমো, মোবাইল অ্যাপ, ক্রিপ্টো বোনাস, ওয়েজার ও FAQ।",
        "url": "bc-game-ice-fish",
    },
    "14": {
        "code": "it",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish su BC.Game: giocare, demo, crypto e app",
        "description": "Gioca a Ice Fish su BC.Game: titoli InOut nel lobby, demo, app mobile, bonus crypto, wagering e FAQ.",
        "url": "bc-game-ice-fish",
    },
    "15": {
        "code": "nl",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish op BC.Game: spelen, demo, crypto en app",
        "description": "Speel Ice Fish op BC.Game: vind InOut in de lobby, demo, mobiele app, cryptobonussen, wagering en FAQ.",
        "url": "bc-game-ice-fish",
    },
    "16": {
        "code": "pl",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish na BC.Game: gra, demo, krypto i aplikacja",
        "description": "Ice Fish na BC.Game: znajdź InOut w lobby, demo, aplikacja mobilna, bonusy krypto, wagering i FAQ.",
        "url": "bc-game-ice-fish",
    },
    "17": {
        "code": "vi",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish trên BC.Game: chơi, demo, crypto và app",
        "description": "Chơi Ice Fish trên BC.Game: tìm InOut trong lobby, demo, app mobile, bonus crypto, wagering và FAQ.",
        "url": "bc-game-ice-fish",
    },
    "18": {
        "code": "ua",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish на BC.Game: гра, демо, крипто та додаток",
        "description": "Ice Fish на BC.Game: як знайти InOut у лобі, демо, мобільний додаток, криптобонуси, вейджер і FAQ.",
        "url": "bc-game-ice-fish",
    },
    "19": {
        "code": "ro",
        "name": "BC.Game - Ice Fish",
        "title": "Ice Fish pe BC.Game: joc, demo, cripto și aplicație",
        "description": "Joacă Ice Fish pe BC.Game: găsește InOut în lobby, demo, aplicație mobilă, bonusuri cripto, wagering și FAQ.",
        "url": "bc-game-ice-fish",
    },
}

_EN_BODY = {
    "h1": "BC.Game — Ice Fish",
    "h2_intro": "Ice Fish on BC.Game",
    "img_hero_alt": "Ice Fish on BC.Game casino lobby overview",
    "intro_paras": [
        "BC.Game is a popular online casino platform, and Ice Fish is one of the games players now actively look for on big casino sites. The logic is simple: people already know the game, they know BC.Game, and they want to open it quickly in a place they already use.",
        "The original Ice Fish from InOut Games is available on BC.Game, so players do not have to look for copies or random alternatives. You can search the game in the casino lobby and open the real title from the provider.",
        "This matters because Ice Fish is the kind of game people usually want to launch fast. The casino is available in most countries, so accessing the platform is quick and easy. BC.Game has a large game lobby and is built for fast access from desktop or mobile.",
        "For players who came specifically for Ice Fish, BC.Game is a convenient option. Open the casino, use search, find the InOut title, and start from there.",
    ],
    "h2_about": "About BC.Game",
    "img_about_alt": "BC.Game online casino homepage and lobby sections",
    "about_paras": [
        "BC.Game is an online casino with a strong crypto-friendly image. The platform is known for quick access, a large game lobby, and a mix of different gambling formats in one place.",
        "Inside BC.Game, players can find slots, live casino, table games, crash and instant games, sportsbook, and other fast titles. It is not just a slot site and not only a betting platform. It works more like a big casino hub where every type of player can find something familiar.",
        "Ice Fish fits this kind of lobby well. BC.Game users often like games that open fast, do not need long explanations, and give a clear result without waiting through heavy animations. Ice Fish has exactly that type of rhythm.",
        "For players who prefer short sessions, mobile play, and simple arcade-style mechanics, BC.Game is a natural place to look for it. The game does not feel out of place there — it sits well next to crash, instant, and other quick-play casino titles.",
    ],
    "h2_available": "Ice Fish is available on BC.Game",
    "img_search_alt": "Ice Fish search results in the BC.Game casino lobby",
    "available_lead_paras": [
        "BC.Game has Ice Fish in the casino lobby. When you type Ice Fish, BC.Game shows a whole row of related titles right away.",
    ],
    "available_tail_paras": [
        "There is the original Ice Fish from InOut Games and other versions as well. The chicken series has already proven itself, and players can find a version that appeals to them visually.",
        "On BC.Game, Ice Fish works almost like a separate mini-category. A player can open the original game, then quickly compare it with Gold, Vegas, Race or Bonus versions without leaving the same search page.",
        "The easiest way is still search. Open the casino lobby, type Ice Fish, and choose the version you want from the cards. You can also filter by provider and look for InOut, especially if you want to check the full list from the studio.",
        "Demo mode is available as well, so you can test the game before using real balance.",
    ],
    "short_path_title": "The short path:",
    "short_path_item": "Casino → Search → Ice Fish → Pick a version → Demo or Play",
    "h2_why": "Why Ice Fish fits BC.Game",
    "why_paras": [
        "Ice Fish feels natural on BC.Game because the platform itself is built around fast access. People come there for quick casino formats, crypto-friendly play, instant games, crash titles, and games that do not need a long warm-up. Ice Fish sits in that lane pretty well. It is not the type of game where you read rules for ten minutes before starting. You open it, understand the mood almost immediately, and the round does not take much time.",
        "That works especially well for the BC.Game audience. A lot of players there are used to fast decisions, quick deposits, short sessions, and simple game loops. Ice Fish has the same energy: clean visuals, fast launch, and one clear question during the run — continue or stop. Another reason it fits BC.Game is the size of the chicken lineup in the lobby. It is not only one game card. You can see different versions next to each other, so the player can move from the original Ice Fish to Gold, Vegas, Race, Bonus, or other InOut titles without leaving the same ecosystem.",
    ],
    "h2_inout": "Ice Fish from InOut on BC.Game",
    "img_inout_alt": "Original Ice Fish by InOut Games on BC.Game",
    "inout_paras": [
        "On BC.Game, Ice Fish is not presented as some random chicken-themed copy. The game appears in the lobby as an InOut Games title, which is important for players who are looking for the original version, not a clone with a similar name.",
        "This is one of the main reasons BC.Game works well for Ice Fish. The player can check the game card, see the provider, open the title, and understand that it belongs to the same studio that created the Ice Fish series.",
        "For this type of game, provider visibility matters. There are many copies, mirror pages, and \"almost the same\" chicken games online. Some may look close at first, but the mechanics, fairness tools, RTP, or even the game logic can be different.",
        "BC.Game makes the check simple. Search for Ice Fish, open the game card, and look at the provider. If it shows InOut Games, then you are not guessing anymore.",
        "That is the real value here: fast access, original provider, and no need to chase the game across unknown websites. For players who came specifically for Ice Fish, this is much better than landing on a random page that only uses the name.",
    ],
    "h2_mobile": "Mobile experience on BC.Game",
    "img_mobile_alt": "Ice Fish on BC.Game mobile app",
    "mobile_paras": [
        "Ice Fish feels comfortable on BC.Game from a phone. Not because mobile makes the game different, but because the format itself is short and light. You do not need a wide desktop screen to understand what is happening.",
        "BC.Game can be opened through the mobile site or the official app. The lobby search works well enough on a small screen: type the game name, open the card, and you are already inside the Ice Fish page.",
        "On mobile, the main thing is not the graphics. It is control. The buttons sit closer, the bet field is smaller, and fast games can make you tap quicker than planned. Before starting, check the stake once. It takes two seconds and saves a lot of stupid mistakes.",
        "I would also avoid downloading Ice Fish from random APK pages. If the game is already inside BC.Game, there is no reason to look for some \"special version\" outside the platform. Use the official BC.Game site or app, open the provider game from the lobby, and play from there.",
    ],
    "h2_bonuses": "Bonuses, crypto and wagering",
    "bonus_paras": [
        "BC.Game is a bit different from many classic casino sites because bonuses there can depend on more than just the promo banner. Your account status, selected currency, VIP level, active campaigns, and even the type of game can affect how useful a bonus really is.",
        "For Ice Fish, the first thing to check is whether instant or crash-style games count toward wagering. Some promos look good at first, but then you open the rules and see that only slots count, or that some fast games are excluded. In that case, the bonus may still exist, but it will not help much with Ice Fish.",
        "Crypto adds one more layer, but check the network before depositing or withdrawing. A wrong network, high fee, or small withdrawal limit can make a simple transaction annoying very quickly.",
        "I would look at three things before using any promo with Ice Fish on BC.Game: whether the game is eligible, how wagering works, and what happens when you withdraw in your chosen currency. The bonus itself is not the full story. The rules around it matter more.",
    ],
    "h2_app": "BC.Game APK and App Access",
    "img_app_alt": "BC.Game mobile app download and lobby access",
    "app_paras": [
        "The BC.Game app is useful if you play from your phone often. You do not need to open the browser, type the site address, search the lobby again, and wait for everything to load from zero. The icon is already on your screen, so access feels faster.",
        "For Ice Fish, that convenience matters. This is not a game where you want to spend time digging through menus. You open the app, go to the casino lobby, search Ice Fish, and launch the InOut game from there.",
        "The app also keeps everything closer: account, balance, promotions, cashier, game history, and support. It saves time, especially if you switch between games or use different currencies.",
        "There can also be mobile-only features inside the app. Some options may feel better on a phone: quick login, push notifications, faster lobby access, smoother touch controls, and mobile-optimized game windows. The exact features depend on your device and the BC.Game version you use.",
        "Still, download only from the official BC.Game source. Do not install random APK files from Telegram, ads, or unknown pages. If you want the app, get it from the real BC.Game website or official store links, not from \"special\" Ice Fish APK pages.",
    ],
    "h2_final": "Conclusion",
    "final_paras": [
        "BC.Game works well for Ice Fish because the platform already has the right environment for this kind of game. Fast lobby, crypto-friendly payments, mobile access, and quick search make the game easy to open without wasting time.",
        "The main advantage is that BC.Game does not show Ice Fish as some hidden title. You can search the lobby, find the original InOut Games version, and also see other releases from the same chicken lineup nearby.",
        "Still, I would start with demo first. Not to find a magic strategy, but to understand which version feels better and how the game behaves before using real balance.",
        "So yes, BC.Game is a convenient place for Ice Fish. Just open the official site or app, check the game through search, use demo for testing, and move to real play only when you understand what you are doing.",
    ],
    "h2_faq": "FAQ",
    "faq": [
        [
            "Is Ice Fish available on BC.Game?",
            "Yes. BC.Game has the original Ice Fish from InOut Games in the casino lobby. When you search the title, you can also see other games from the same chicken-themed lineup.",
        ],
        [
            "How do I find Ice Fish on BC.Game?",
            "Use the lobby search. Type Ice Fish and the game cards should appear right away. This is faster than opening categories one by one.",
        ],
        [
            "Can I search by provider InOut?",
            "Yes. Searching InOut is useful if you want to see more games from the same studio, not only Ice Fish. On BC.Game, the provider search can show the wider chicken collection in one place.",
        ],
        [
            "What if Ice Fish is not in the lobby?",
            "Check the spelling first, then try searching InOut instead of the game name. If it still does not appear, the game may be limited by region, account settings, or the current BC.Game lobby version.",
        ],
        [
            "Can I play Ice Fish demo on BC.Game?",
            "Yes, demo mode is available. It is a good way to open the game, check the pace, compare versions, and understand the round before using real balance.",
        ],
        [
            "Does BC.Game support Ice Fish on mobile?",
            "Yes. Ice Fish works through the BC.Game mobile site and app. The game fits mobile well because the screen is not overloaded and the round does not need a large desktop layout.",
        ],
        [
            "Do BC.Game bonuses work with Ice Fish?",
            "It depends on the promo rules. Check whether instant or crash-style games count toward wagering before activating a bonus.",
        ],
        [
            "Can I play Ice Fish with crypto?",
            "BC.Game is crypto-friendly, and many players use coins and other supported currencies. Check the network and fees before depositing or withdrawing.",
        ],
        [
            "Are Ice Fish predictor apps safe?",
            "No. I would avoid them. Predictor apps, bots, APKs, and \"signals\" usually promise something they cannot prove. Some are useless, others may be unsafe for your account or device.",
        ],
        [
            "Is BC.Game the official InOut Games site?",
            "No. BC.Game is an online casino platform. It can host Ice Fish from InOut Games, but it is not the official website of the provider.",
        ],
        [
            "Is Ice Fish risky with real money?",
            "Yes. Ice Fish is still a gambling game. Use demo first, set limits, and do not treat it as a way to earn money.",
        ],
    ],
}

IMAGES = {
    "hero": "/files/media/2026/06/chicken_bc.webp",
    "about": "/files/media/2026/06/screenshot-2026-06-09-164313.webp",
    "search": "/files/media/2026/06/screenshot-2026-06-09-162750.webp",
    "inout": "/files/media/2026/06/screenshot-2026-06-09-172527-1.webp",
    "mobile": "/files/media/2026/06/chickenbc_app.webp",
    "app": "/files/media/2026/06/screenshot-2026-06-09-173009.webp",
}


def get_body(lang_code: str) -> dict:
    if lang_code == "en":
        return deepcopy(_EN_BODY)
    try:
        from icefish_casino_articles_26_overrides import LOCALE_OVERRIDES  # type: ignore
    except Exception:
        LOCALE_OVERRIDES = {}
    patch = LOCALE_OVERRIDES.get(lang_code)
    if not patch:
        return deepcopy(_EN_BODY)
    out = deepcopy(_EN_BODY)
    out.update(patch)
    return out
