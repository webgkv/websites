#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build guides/games/blog sw_ln JSON from segment exports."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
SEG = Path.home() / "Downloads/02/ice-fish"

sys.path.insert(0, str(TOOLS))
from icefish_sw_ln_batch_data import (  # noqa: E402
    BLOG_5_EXTRA_FR_LN,
    BLOG_5_EXTRA_SW,
    BLOG_5_META,
    GAMES_13_META,
    GAMES_14_META,
    GUIDES_9_EXTRA_FR_LN,
    GUIDES_9_EXTRA_SW,
    GUIDES_9_META,
    load_translations,
)


def _load(path: Path) -> list[str]:
    return json.loads(path.read_text(encoding="utf-8"))


def _zip_pairs(src: list[str], targets: list[str], label: str) -> list[list[str]]:
    if len(src) != len(targets):
        raise SystemExit(f"{label}: count {len(targets)} != source {len(src)}")
    return [[a, b] for a, b in zip(src, targets, strict=True)]


def build_guides_9() -> dict:
    en = _load(SEG / "guides/guides-9-en-segments.json")
    fr = _load(SEG / "guides/guides-9-fr-segments.json")
    tr = load_translations("guides_9")
    sw = _zip_pairs(en, tr["sw"], "guides_9 sw") + GUIDES_9_EXTRA_SW
    fr_ln = _zip_pairs(fr, tr["ln"], "guides_9 fr_ln") + GUIDES_9_EXTRA_FR_LN
    return {"ln_from_fr": True, "meta": GUIDES_9_META, "pairs": {"sw": sw, "fr_ln": fr_ln}}


def build_games_13() -> dict:
    en = _load(SEG / "games/games-13-en-segments.json")
    fr = _load(SEG / "games/games-13-fr-segments.json")
    tr = load_translations("games_13")
    sw = _zip_pairs(en, tr["sw"], "games_13 sw")
    fr_ln = _zip_pairs(fr, tr["ln"], "games_13 fr_ln")
    return {"ln_from_fr": True, "meta": GAMES_13_META, "pairs": {"sw": sw, "fr_ln": fr_ln}}


def build_games_14() -> dict:
    en = _load(SEG / "games/games-14-en-segments.json")
    tr = load_translations("games_14")
    sw = _zip_pairs(en, tr["sw"], "games_14 sw")
    ln = _zip_pairs(en, tr["ln"], "games_14 ln")
    return {"ln_from_fr": False, "meta": GAMES_14_META, "pairs": {"sw": sw, "ln": ln}}


def build_blog_5() -> dict:
    en = _load(SEG / "blog/blog-5-en-segments.json")
    fr = _load(SEG / "blog/blog-5-fr-segments.json")
    tr = load_translations("blog_5")
    sw = _zip_pairs(en, tr["sw"], "blog_5 sw") + BLOG_5_EXTRA_SW
    fr_ln = _zip_pairs(fr, tr["ln"], "blog_5 fr_ln") + BLOG_5_EXTRA_FR_LN
    return {"ln_from_fr": True, "meta": BLOG_5_META, "pairs": {"sw": sw, "fr_ln": fr_ln}}


def write(name: str, payload: dict) -> None:
    out = TOOLS / name
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    sw_n = len(payload["pairs"]["sw"])
    ln_key = "fr_ln" if payload.get("ln_from_fr") else "ln"
    ln_n = len(payload["pairs"][ln_key])
    print(f"Wrote {out.name}: sw={sw_n} {ln_key}={ln_n} ln_from_fr={payload.get('ln_from_fr')}")


def main() -> None:
    write("guides_sw_ln_data/guides_9.json", build_guides_9())
    write("games_sw_ln_data/games_13.json", build_games_13())
    write("games_sw_ln_data/games_14.json", build_games_14())
    write("blog_sw_ln_data/blog_5.json", build_blog_5())


if __name__ == "__main__":
    main()
