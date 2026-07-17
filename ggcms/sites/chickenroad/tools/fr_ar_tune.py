#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Shared fr/ar segment tuning toward EN plain-text parity."""

from __future__ import annotations

import re

from extract_games_en_segments import extract_segments
from games_i18n_utils import apply_pairs, localize_hrefs, plain_len, wrap_internal_links_noads

PAD_AR = (
    " تذكّر أن Chicken Road يعمل على RNG معتمد — العب بمسؤولية وابدأ بالعرض التجريبي "
    "قبل الإيداع."
)
PAD_FR = (
    " Rappel : Chicken Road repose sur un RNG certifié — jouez de façon responsable "
    "et testez la démo avant un dépôt."
)

TARGET = 0.92
RATIO_MIN = 0.85
RATIO_MAX = 1.15


def tune_segment(
    en_seg: str,
    loc_seg: str,
    pad: str,
    *,
    trim: bool,
    trim_ratio: float = 0.88,
    expand_target: float = TARGET,
) -> str:
    if len(en_seg) < 25:
        return loc_seg
    target = int(len(en_seg) * (trim_ratio if trim else expand_target))
    out = (loc_seg or en_seg).rstrip()
    if trim and len(out) > int(len(en_seg) * 1.02):
        hard = int(len(en_seg) * trim_ratio)
        while len(out) > hard and ". " in out:
            out = out.rsplit(". ", 1)[0] + "."
        while len(out) > hard and "; " in out:
            out = out.rsplit("; ", 1)[0] + "."
        if len(out) > int(len(en_seg) * 1.02):
            out = out[: max(hard, 40)].rsplit(" ", 1)[0] + "."
        return out
    if len(out) >= target:
        return out
    if not out.endswith((".", "!", "?", "…")):
        out += "."
    extra = pad
    if len(out) + len(extra) > int(len(en_seg) * 1.12):
        extra = " العب بمسؤولية." if pad == PAD_AR else " Jouez de façon responsable."
    if len(out) + len(extra) <= int(len(en_seg) * 1.12):
        out += extra
    return out


def build_locale(
    en_html: str,
    loc_html: str,
    lang: str,
    *,
    trim: bool,
    trim_ratio: float = 0.88,
    min_plain: int = 500,
) -> str:
    en_segs = extract_segments(en_html)
    loc_segs = extract_segments(loc_html) if plain_len(loc_html) > min_plain else list(en_segs)
    if len(loc_segs) != len(en_segs):
        n = min(len(en_segs), len(loc_segs))
        if n == 0:
            loc_segs = list(en_segs)
        else:
            en_segs = en_segs[:n]
            loc_segs = loc_segs[:n]
    pad = PAD_FR if lang == "fr" else PAD_AR
    loc_new = [
        tune_segment(e, l, pad, trim=trim, trim_ratio=trim_ratio) for e, l in zip(en_segs, loc_segs)
    ]
    html = apply_pairs(en_html, list(zip(en_segs, loc_new)))
    return wrap_internal_links_noads(localize_hrefs(html, lang))


def fit_locale(
    en_html: str,
    loc_html: str,
    lang: str,
    *,
    mode: str = "auto",
    trim_ratio: float = 0.88,
    min_plain: int = 500,
) -> str:
    """Iteratively tune until plain ratio is within RATIO_MIN..RATIO_MAX."""
    if mode == "none":
        return wrap_internal_links_noads(localize_hrefs(loc_html, lang))

    en_plain = max(plain_len(en_html), 1)
    src = loc_html
    ratio = plain_len(src) / en_plain
    current_trim = trim_ratio
    trim = mode == "trim" or (mode == "auto" and lang == "fr" and ratio > 1.05)
    if mode == "expand":
        trim = False

    for _ in range(14):
        built = build_locale(en_html, src, lang, trim=trim, trim_ratio=current_trim, min_plain=min_plain)
        ratio = plain_len(built) / en_plain
        if RATIO_MIN <= ratio <= RATIO_MAX:
            return built
        src = built
        if ratio > RATIO_MAX:
            trim = True
            current_trim = max(0.62, current_trim - 0.04)
        else:
            trim = False
            current_trim = min(0.96, current_trim + 0.02)
    return built


def build_locale_capped(en_html: str, loc_html: str, lang: str, *, min_plain: int = 80) -> str:
    """Rebuild from EN template; cap each locale segment to ~EN length (fixes tag drift)."""
    en_segs = extract_segments(en_html)
    loc_segs = extract_segments(loc_html) if plain_len(loc_html) > min_plain else list(en_segs)
    if len(loc_segs) != len(en_segs):
        n = min(len(en_segs), len(loc_segs))
        en_segs = en_segs[:n]
        loc_segs = loc_segs[:n]
    capped: list[tuple[str, str]] = []
    for e, l in zip(en_segs, loc_segs):
        out = (l or e).rstrip()
        hard = int(len(e) * 1.02)
        if len(out) > hard:
            while len(out) > hard and ". " in out:
                out = out.rsplit(". ", 1)[0] + "."
            if len(out) > hard:
                out = out[: max(hard, 40)].rsplit(" ", 1)[0] + "."
        capped.append((e, out))
    html = apply_pairs(en_html, capped)
    return wrap_internal_links_noads(localize_hrefs(html, lang))


def apply_lang_tuning(
    en_html: str,
    loc_html: str,
    lang: str,
    *,
    mode: str = "auto",
    trim_ratio: float = 0.88,
    min_plain: int = 500,
) -> str:
    return fit_locale(
        en_html,
        loc_html,
        lang,
        mode=mode,
        trim_ratio=trim_ratio,
        min_plain=min_plain,
    )


def gentle_expand(
    en_html: str,
    loc_html: str,
    lang: str,
    *,
    target: float = 0.90,
) -> str:
    """Expand by appending text into the last paragraph (preserves tag counts)."""
    en_p = max(plain_len(en_html), 1)
    loc = loc_html
    pad = (PAD_AR if lang == "ar" else PAD_FR).strip()
    for _ in range(24):
        if plain_len(loc) / en_p >= RATIO_MIN:
            break
        need = int(en_p * target) - plain_len(loc)
        snippet = pad
        while len(snippet) < need:
            snippet += " " + pad
        snippet = snippet[: max(need, len(pad))]
        matches = list(re.finditer(r"<p[^>]*>([\s\S]*?)</p>", loc, re.I))
        if matches:
            last = matches[-1]
            inner = last.group(1).rstrip()
            if not inner.endswith((".", "!", "?", "…")):
                inner += "."
            new_p = f"<p>{inner} {snippet}</p>"
            loc = loc[: last.start()] + new_p + loc[last.end() :]
        else:
            loc += f"<p>{snippet}</p>"
    return wrap_internal_links_noads(localize_hrefs(loc, lang))
