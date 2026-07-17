#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Aviator Log In prod/sync paths for SEO sw/ln tooling."""

from __future__ import annotations

from pathlib import Path

SITE_ID = "aviator-log-in"
DOMAIN = "aviator-log-in.com"
REMOTE_ROOT = "/home/dikodo/web/aviator-log-in.com/public_html"
SSH = "ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
SCP = "scp -i ~/.ssh/webgkv -P 20203"
DL_ROOT = Path.home() / "Downloads/02" / "aviator"

PAGE_IDS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35]
GUIDE_IDS = list(range(1, 6))
GAME_IDS = list(range(1, 7))
CASINO_IDS = list(range(1, 26))
BLOG_IDS = list(range(6, 41))
AUTHOR_IDS = [1]

HUB_PAGE_IDS = {2, 3, 7, 8, 9, 10, 11, 12, 35}
