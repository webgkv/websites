#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Canonical EN body for pages#5 download cluster (live export 2026-07)."""

from __future__ import annotations


def _ol1(_lang: str, _b: dict) -> str:
    from chickenroad_download_v2_builder import _e, _link

    L = _lang
    b = _b
    return (
        f"{b['ol_1_a']}{_link(L, '/download/install-apk/', b['lnk_apk'])}{b['ol_1_b']}"
        f"{_link(L, '/download/install-pwa/', b['lnk_pwa'])}{b['ol_1_c']}"
    )


def get_en_body() -> dict:
    return {
        "h1": "Chicken Road Download: Free App for Android and iOS",
        "h2_intro": "Download Chicken Road Game: Free App Overview",
        "h2_what": "What Is the Chicken Road Application?",
        "h2_road2": "Download Chicken Road: Original or Chicken Road 2?",
        "h2_req": "Chicken Road App Download: System Requirements",
        "h2_download": "How to Get the Chicken Road Download",
        "h2_diff": "Chicken Road Game App Download: Android vs iOS vs PC",
        "h2_use": "How to Use the Chicken Road Game App",
        "h2_faq": "FAQ — Chicken Road Download",
        "alt_hero": "Chicken Road app download on mobile and desktop",
        "alt_app": "Chicken Road demo app on phone and computer",
        "alt_mobile": "Chicken Road mobile interface in portrait mode",
        "alt_interface": "Chicken Road app interface across devices",
        "alt_gameplay": "Chicken Road demo gameplay with multiplier on screen",
        "intro_p1_a": "On our website, you can try the ",
        "lnk_demo": "Chicken Road demo",
        "intro_p1_b": " without putting real money at risk. It is the same game, only with a virtual balance, so you can make mistakes, test different difficulty levels, and see how far the chicken can go without worrying about your own funds.",
        "lnk_cr": "Chicken Road",
        "intro_p2_b": " is simple, funny, and surprisingly catchy. Just try a few rounds and it becomes clear why so many players like it. Every next step feels tempting, because the multiplier grows, and stopping too early always feels a little annoying.",
        "intro_p3_b": " managed to create a very strong casino title here. The game does not need complicated rules or heavy visuals to keep attention. It works because the idea is clean: one step can increase your win, and one wrong step can end the round. That balance between fun animation and real risk is exactly what makes Chicken Road stand out.",
        "intro_p4": "You have probably already seen Chicken Road somewhere, or at least heard people talking about it. The game spread fast for a reason: it is simple, quick, and easy to understand after the first round. On our website, you can try it yourself and get a real feel for how Chicken Road works.",
        "intro_p5": "For your convenience, we offer the option to download and install the app so it's always easily accessible to you. Just launch the app, find Chicken Road, choose the mode, set your bet, and start playing.",
        "what_p1_a": "The app works as quick access to the Chicken Road demo. The demo itself comes from the official provider, ",
        "what_p1_b": ". We only prepared the app to make access easier, so the game is always close on your phone.",
        "what_p2": "This is not a separate copy or a modified version of Chicken Road. It is the full demo game with the same basic mechanics, interface, difficulty modes, and gameplay logic. The only limitation is the balance: you play with virtual credits, not real money.",
        "what_p3": "The app was not created for commercial gambling purposes. Its main goal is simple — to let users get familiar with Chicken Road, test the mechanics, understand how the game works, and try different approaches without financial risk.",
        "what_p4": "So you can treat it as a convenient demo shortcut. Open the app, launch Chicken Road, play with virtual balance, and learn the game at your own pace.",
        "th_spec_1": "Specification",
        "th_spec_2": "Details",
        "spec_rows": [
            ("Game Type", "Crash / instant game"),
            ("Developer", ("/games/inout-games/", "InOut Games")),
            ("Visual Style", "Arcade-style, cartoon-like"),
            ("Cost", "Free"),
            ("Languages", "Multiple"),
        ],
        "road2_p_a": "This app gives you the ",
        "road2_strong": "Chicken Road original game download",
        "road2_p_b": " — the classic version by InOut Games, with a 98% RTP and the calmer, step-by-step road-crossing theme covered on our ",
        "lnk_cr_orig": "original Chicken Road",
        "road2_p_c": " page. If you specifically want to try the sequel instead, visit the ",
        "lnk_cr2": "Chicken Road 2",
        "road2_p_d": " page for its own demo and details — it runs the same way, just with a 95.5% RTP and a busier visual theme.",
        "th_req_1": "Parameter",
        "th_req_2": "Android",
        "th_req_3": "iOS",
        "req_rows": [
            ("Device", "Smartphone / tablet", "iPhone / iPad"),
            ("OS Version", "Android 8.0 or newer", "iOS 13 or newer"),
            ("Browser", "Chrome, Firefox, Opera, Samsung Internet", "Safari, Chrome"),
            ("RAM", "2 GB minimum, 3 GB+ recommended", "2 GB minimum, 3 GB+ recommended"),
            ("Internet", "Stable 4G, 5G or Wi-Fi", "Stable 4G, 5G or Wi-Fi"),
            ("Storage", "~15–30 MB (APK)", "Minimal, PWA home-screen icon"),
            (
                "App Installation",
                ["Required (", ("/download/install-apk/", "APK"), ")"],
                ["Required (", ("/download/install-pwa/", "PWA"), ")"],
            ),
            ("Screen", 'Works better on 5.5"+ screens', 'Works better on 5.5"+ screens'),
            ("Technology", "HTML5 / JavaScript", "HTML5 / JavaScript"),
        ],
        "req_apk_cell_a": "Required (",
        "req_apk_cell_b": ")",
        "req_pwa_cell_a": "Required (",
        "req_pwa_cell_b": ")",
        "ol_1_a": "Pick the guide that matches your device: ",
        "lnk_apk": "Android APK",
        "ol_1_b": " or ",
        "lnk_pwa": "iPhone PWA",
        "ol_1_c": ".",
        "download_ol": [
            _ol1,
            "Follow the install steps on that page — it only takes a minute.",
            "Once installed, the Chicken Road icon will appear on your home screen.",
            "Tap the icon, choose the mode, set your bet, and start playing.",
        ],
        "download_why_h": "Why bother installing at all instead of using the browser?",
        "download_ul": [
            "one tap opens the game — no searching for the site again;",
            "the icon sits on your home screen like any other app;",
            "it loads a little faster on repeat visits;",
            "it takes up almost no storage — it's still the browser game underneath.",
        ],
        "download_p_casino_a": "The app is focused on demo play. You can use it to get familiar with Chicken Road, test the controls, try different risk levels, and see how the game feels before playing at any of the ",
        "lnk_casinos": "Chicken Road casino",
        "download_p_casino_b": " options with real money.",
        "th_diff_1": "Feature",
        "th_diff_2": "Android",
        "th_diff_3": "iOS",
        "th_diff_4": "PC",
        "diff_rows": [
            ("Interface", "Adapted for small screens", "Optimised for touch", "Designed for large screens and mouse/keyboard"),
            ("Performance", "May vary on weaker devices", "Generally smooth on supported devices", "Strong performance on capable hardware"),
            ("Updates", "Manual or automatic", "Manual or automatic", "Usually manual, often after a prompt"),
            ("Compatibility", "Android 8.0+", "iOS 13.0+", "Most current Windows versions"),
        ],
        "use_p1": "The app plays exactly like the real Chicken Road game. Same road, same chicken, same multipliers, same decisions. The only difference is the balance: here you use virtual money, and there is no option to place real-money bets.",
        "use_p2": "Set your bet, press play, and start moving along the road. If the chicken gets through safely, you can cash out and keep the current multiplier. Or you can try another step and risk the round for a bigger result.",
        "use_p3_a": "That is the whole idea of the demo. You repeat the same process again and again, but without financial risk. It is a simple way to understand how Chicken Road feels before playing with real money anywhere else. For a deeper walkthrough, see our full ",
        "lnk_demo_guide": "Chicken Road demo guide",
        "use_p3_b": ".",
        "faq": [
            (
                "Is this the official app?",
                [
                    "No. This is not the official Chicken Road app from InOut Games. On our site, the app works as a quick-access version for the Chicken Road demo. The game itself is provided by ",
                    ("/games/inout-games/", "InOut Games"),
                    ", but the app is prepared by our website for easier access to the demo version.",
                ],
            ),
            (
                "Are there any risks when downloading or installing the app?",
                [
                    "The app is made only for demo play and does not require real-money deposits inside it. For safety tips on avoiding fake APKs, see our ",
                    ("/download/install-apk/", "Chicken Road APK guide"),
                    ".",
                ],
            ),
            (
                "Having trouble downloading the app?",
                [
                    "There can be several reasons. Most often, these are temporary issues. Simply try downloading the APK again a little later.",
                ],
            ),
            (
                "Does the app take space on my phone?",
                [
                    "Not much. Since it works like quick access to the Chicken Road demo, it does not take as much space as a full casino app. No large game files, no heavy updates, no extra setup.",
                ],
            ),
            (
                "Chicken Road game money download: can I play for real money in the app?",
                [
                    "No. The app is created for demo play only, with a virtual balance. To play for real money, choose one of the ",
                    ("/casinos/", "Chicken Road casino"),
                    " options instead.",
                ],
            ),
            (
                "Why install the app if the demo can be played in the browser?",
                [
                    "Mainly for convenience. The icon stays on your phone, and you can launch the Chicken Road demo faster. The browser version and the app lead to the same idea: free demo play with virtual credits. The app is just a more convenient shortcut for users who want the game close at hand.",
                ],
            ),
            (
                "Which guide do I need — APK or PWA?",
                [
                    "Android users want the ",
                    ("/download/install-apk/", "APK guide"),
                    ". iPhone users, since there's no App Store listing, want the ",
                    ("/download/install-pwa/", "PWA guide"),
                    " instead.",
                ],
            ),
        ],
    }
