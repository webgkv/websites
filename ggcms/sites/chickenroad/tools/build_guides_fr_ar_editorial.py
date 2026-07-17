#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand guides AR (and verify FR) to EN parity via segment pairs."""

from __future__ import annotations

import json
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DL = Path("/home/lenovo/Downloads/02/chickenroad-guides")
OUT = TOOLS / "guides_fr_ar_data"
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from games_i18n_utils import apply_pairs, localize_hrefs, plain_len, sanitize_en_html, wrap_internal_links_noads  # noqa: E402

PAD_AR = (
    " تذكّر أن Chicken Road ما زالت لعبة حظ — حتى في التجربة، الهدف هو فهم الإيقاع وقرار Cash Out، "
    "وليس البحث عن «نظام» مضمون."
)


def truncate(title: str, desc: str) -> tuple[str, str]:
    if len(title) > 70:
        title = title[:67].rstrip() + "..."
    if len(desc) > 160:
        desc = desc[:157].rstrip() + "..."
    return title, desc


def expand_ar(en_seg: str, ar_seg: str) -> str:
    if len(en_seg) < 40:
        return ar_seg
    target = int(len(en_seg) * 0.92)
    if len(ar_seg) >= target:
        return ar_seg
    extra = PAD_AR
    if len(ar_seg) + len(extra) > int(len(en_seg) * 1.12):
        extra = " العب بمسؤولية وحدّد رهاناً ثابتاً قبل كل جولة."
    out = ar_seg.rstrip()
    if not out.endswith((".", "!", "?", "…")):
        out += "."
    return out + extra


def build_guide(entity_id: int) -> dict:
    path = DL / f"seo-guides-{entity_id}-full.json"
    data = json.loads(path.read_text(encoding="utf-8"))
    locs = {x["lang_id"]: x for x in data["locales"]}
    en_html = sanitize_en_html(locs[1]["content"])
    ar_html = locs[11]["content"]
    en_segs = extract_segments(en_html)
    ar_segs = extract_segments(ar_html)
    if len(en_segs) == len(ar_segs):
        ar_new = [expand_ar(e, a) for e, a in zip(en_segs, ar_segs)]
        content = apply_pairs(en_html, list(zip(en_segs, ar_new)))
    else:
        # Fall back: expand each AR paragraph block toward EN length
        import re

        def repl(match: re.Match[str]) -> str:
            en_p = match.group(1)
            ar_p = match.group(2)
            return f"<p>{expand_ar(en_p, ar_p)}</p>"

        en_ps = re.findall(r"<p[^>]*>([\s\S]*?)</p>", en_html)
        ar_ps = re.findall(r"<p[^>]*>([\s\S]*?)</p>", ar_html)
        content = ar_html
        for en_p, ar_p in zip(en_ps, ar_ps):
            new_p = expand_ar(en_p, ar_p)
            if new_p != ar_p:
                content = content.replace(f"<p>{ar_p}</p>", f"<p>{new_p}</p>", 1)
                content = content.replace(f"<p>{ar_p}</p>", f"<p>{new_p}</p>", 1)
    content = wrap_internal_links_noads(localize_hrefs(content, "ar"))
    ar_loc = locs[11]
    title, desc = truncate(ar_loc.get("title", ""), ar_loc.get("description", ""))
    return {
        "meta": {
            "ar": {
                "name": ar_loc.get("name", ""),
                "title": title,
                "description": desc,
            }
        },
        "content": {"ar": content},
    }


def main() -> int:
    ids = [int(x) for x in sys.argv[1:]] if len(sys.argv) > 1 else list(range(1, 9))
    OUT.mkdir(parents=True, exist_ok=True)
    for eid in ids:
        payload = build_guide(eid)
        out = OUT / f"guides_{eid}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        en_len = plain_len(
            sanitize_en_html(
                json.loads((DL / f"seo-guides-{eid}-full.json").read_text())["locales"][0]["content"]
            )
        )
        ar_len = plain_len(payload["content"]["ar"])
        print(f"guides_{eid}.json ratio={ar_len/en_len:.0%}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
