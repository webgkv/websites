#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build seo-promo-1-full.json for FanSport landing (all cluster locales)."""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
ROOT = TOOLS.parent
if str(TOOLS) not in sys.path:
    sys.path.insert(0, str(TOOLS))

from chickenroad_promo_fansport_locales import (  # noqa: E402
    ENTITY_ID,
    LANG_CODES,
    LOCALES,
    SLUG,
)

OUT = ROOT / "site/files/reference/seo-promo-1-full.json"
OFFER_URL = "https://tcdu1.live/t.php?o=5GyyB"
IMG = "/files/media/2026/07/chicken-fansport-fs.webp"


def e(text: str) -> str:
    return html.escape(text, quote=True)


def build_content(body: dict, lang_code: str) -> str:
    offer = OFFER_URL
    demo = f"/{lang_code}/demo/app/"
    return f"""<section class="promo-land promo-land--fansport">
\t<div class="promo-land-hero">
\t\t<div class="promo-land-hero__visual" aria-hidden="true">
\t\t\t<img src="{IMG}" alt="" width="640" height="360" loading="eager" class="promo-land-hero__img">
\t\t\t<div class="promo-land-hero__glow"></div>
\t\t</div>
\t\t<p class="promo-land-hero__eyebrow">{e(body['eyebrow'])}</p>
\t\t<h1 class="promo-land-hero__headline">{e(body['headline'])}</h1>
\t\t<p class="promo-land-hero__lead">{e(body['lead'])}</p>
\t\t<div class="main_btn promo-land-hero__cta">
\t\t\t<noads><a href="{e(offer)}">{e(body['cta'])}</a></noads>
\t\t</div>
\t</div>
\t<div class="promo-land-body about_content">
\t\t<p>{e(body['p_open'])}</p>
\t\t<div class="promo-land-steps">
\t\t\t<div class="promo-land-step">
\t\t\t\t<h2>{e(body['h2_existing'])}</h2>
\t\t\t\t<p>{e(body['p_existing'])}</p>
\t\t\t</div>
\t\t\t<div class="promo-land-step">
\t\t\t\t<h2>{e(body['h2_new'])}</h2>
\t\t\t\t<p>{e(body['p_new'])}</p>
\t\t\t</div>
\t\t</div>
\t\t<p class="promo-land-closing">{e(body['p_closing'])}</p>
\t\t<h2>{e(body['h2_where'])}</h2>
\t\t<ul>
\t\t\t<li><strong>{e(body['li_web'].split(': ', 1)[0])}:</strong> {e(body['li_web'].split(': ', 1)[1] if ': ' in body['li_web'] else body['li_web'])}</li>
\t\t\t<li><strong>{e(body['li_mobile'].split(': ', 1)[0])}:</strong> {e(body['li_mobile'].split(': ', 1)[1] if ': ' in body['li_mobile'] else body['li_mobile'])}</li>
\t\t</ul>
\t\t<p class="promo-land-foot-cta">
\t\t\t<noads><a href="{e(offer)}" class="promo-land-btn-secondary">{e(body['btn_go'])}</a></noads>
\t\t\t<a href="{e(demo)}" class="promo-land-btn-ghost">{e(body['btn_demo'])}</a>
\t\t</p>
\t</div>
</section>"""


def build_cluster() -> dict:
    locales = []
    for lang_id in sorted(LOCALES.keys()):
        loc = LOCALES[lang_id]
        lang_code = LANG_CODES[lang_id]
        content = build_content(loc["body"], lang_code)
        locales.append(
            {
                "lang_id": lang_id,
                "lang_code": lang_code,
                "url": SLUG,
                "name": loc["name"],
                "title": loc["title"],
                "description": loc["description"],
                "content": content,
                "status": "published" if lang_id == 1 else "published",
            }
        )
    return {
        "schema": "seo_cluster_v1",
        "exported_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S+00:00"),
        "entity": "promo",
        "entity_id": ENTITY_ID,
        "mode": "full",
        "locales": locales,
    }


def main() -> None:
    data = build_cluster()
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Wrote {OUT} ({len(data['locales'])} locales)")


if __name__ == "__main__":
    main()
