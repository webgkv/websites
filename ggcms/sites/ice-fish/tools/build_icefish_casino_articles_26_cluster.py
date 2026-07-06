#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild casino_articles#26 BC.Game Ice Fish cluster — clean HTML + locale translations."""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT / "tools") not in sys.path:
    sys.path.insert(0, str(ROOT / "tools"))

from icefish_casino_articles_26_locales import IMAGES, LOCALE_META, get_body  # noqa: E402

CLUSTER_IN = Path("/Users/gk/Downloads/05/seo-casino_articles-26-full.json")
OUT_PATH = CLUSTER_IN


def e(text: str) -> str:
    return html.escape(text, quote=False)


def figure(src: str, alt: str) -> str:
    return (
        f'<figure class="section-media__figure">'
        f'<img src="{src}" border="0" alt="{e(alt)}" />'
        f"</figure>"
    )


def paras(items: list[str]) -> str:
    return "".join(f"<p>{e(p)}</p>" for p in items if p)


def build_content(body: dict) -> str:
    chunks: list[str] = []
    chunks.append(f"<h1>{e(body['h1'])}</h1>")
    chunks.append(f"<h2>{e(body['h2_intro'])}</h2>")
    chunks.append(figure(IMAGES["hero"], body["img_hero_alt"]))
    chunks.append(paras(body["intro_paras"]))

    chunks.append(f"<h2>{e(body['h2_about'])}</h2>")
    chunks.append(figure(IMAGES["about"], body["img_about_alt"]))
    chunks.append(paras(body["about_paras"]))

    chunks.append(f"<h2>{e(body['h2_available'])}</h2>")
    chunks.append(paras(body["available_lead_paras"]))
    chunks.append(figure(IMAGES["search"], body["img_search_alt"]))
    chunks.append(paras(body["available_tail_paras"]))
    chunks.append(f"<p><strong>{e(body['short_path_title'])}</strong></p>")
    chunks.append("<ul>")
    chunks.append(f"<li>{e(body['short_path_item'])}</li>")
    chunks.append("</ul>")

    chunks.append(f"<h2>{e(body['h2_why'])}</h2>")
    chunks.append(paras(body["why_paras"]))

    chunks.append(f"<h2>{e(body['h2_inout'])}</h2>")
    chunks.append(figure(IMAGES["inout"], body["img_inout_alt"]))
    chunks.append(paras(body["inout_paras"]))

    chunks.append(f"<h2>{e(body['h2_mobile'])}</h2>")
    chunks.append(figure(IMAGES["mobile"], body["img_mobile_alt"]))
    chunks.append(paras(body["mobile_paras"]))

    chunks.append(f"<h2>{e(body['h2_bonuses'])}</h2>")
    chunks.append(paras(body["bonus_paras"]))

    chunks.append(f"<h2>{e(body['h2_app'])}</h2>")
    chunks.append(figure(IMAGES["app"], body["img_app_alt"]))
    chunks.append(paras(body["app_paras"]))

    chunks.append(f"<h2>{e(body['h2_final'])}</h2>")
    chunks.append(paras(body["final_paras"]))

    chunks.append(f"<h2>{e(body['h2_faq'])}</h2>")
    for q, a in body["faq"]:
        chunks.append(f"<p><strong>{e(q)}</strong></p>")
        chunks.append(f"<p>{e(a)}</p>")

    return "\n".join(chunks)


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)
    old_by_lang = {int(loc["lang_id"]): loc for loc in cluster["locales"]}

    new_locales = []
    for lang_id, meta in LOCALE_META.items():
        code = meta["code"]
        body = get_body(code)
        loc = {
            "lang_id": lang_id,
            "lang_url": old_by_lang.get(lang_id, {}).get("lang_url", code),
            "url": meta.get("url", "bc-game-ice-fish"),
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": build_content(body),
            "status": old_by_lang.get(lang_id, {}).get("status", "published"),
            "source": "content_i18n" if lang_id != 1 else "main",
            "seo_monitor_ctx": {"entity": "casino_articles", "entity_id": 26},
        }
        new_locales.append(loc)

    cluster["entity"] = "casino_articles"
    cluster["entity_id"] = 26
    cluster["locales"] = new_locales
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    OUT_PATH.write_text(json.dumps(cluster, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Wrote {OUT_PATH} ({len(new_locales)} locales)")


if __name__ == "__main__":
    main()
