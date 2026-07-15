#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild casino_articles#26 BC.Game Ice Fish / Ice Fishing cluster."""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT / "tools") not in sys.path:
    sys.path.insert(0, str(ROOT / "tools"))

from chickenroad_casino_articles_26_icefish_locales import (  # noqa: E402
    IMAGES,
    LOCALE_META,
    get_body,
)

CLUSTER_IN = Path("/home/lenovo/Downloads/03/seo-casino_articles-26-full.json")
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


def comparison_table(body: dict) -> str:
    rows = body["table_rows"]
    head = (
        "<div class=\"table-responsive\"><table class=\"table table-bordered\">"
        "<thead><tr>"
        f"<th>{e(body['table_header_feature'])}</th>"
        f"<th>{e(body['table_header_inout'])}</th>"
        f"<th>{e(body['table_header_evolution'])}</th>"
        "</tr></thead><tbody>"
    )
    body_rows = "".join(
        "<tr>"
        f"<td>{e(row[0])}</td>"
        f"<td>{e(row[1])}</td>"
        f"<td>{e(row[2])}</td>"
        "</tr>"
        for row in rows
    )
    return head + body_rows + "</tbody></table></div>"


def build_content(body: dict) -> str:
    chunks: list[str] = []
    chunks.append(f"<h1>{e(body['h1'])}</h1>")
    chunks.append(figure(IMAGES["hero"], body["img_hero_alt"]))
    chunks.append(paras(body["intro_paras"]))

    chunks.append(f"<h2>{e(body['h2_inout'])}</h2>")
    chunks.append(figure(IMAGES["inout"], body["img_inout_alt"]))
    chunks.append(paras(body["inout_paras"]))

    chunks.append(f"<h2>{e(body['h2_evolution'])}</h2>")
    chunks.append(figure(IMAGES["evolution"], body["img_evolution_alt"]))
    chunks.append(paras(body["evolution_paras"]))

    chunks.append(f"<h2>{e(body['h2_why_both'])}</h2>")
    chunks.append(figure(IMAGES["why_both"], body["img_why_both_alt"]))
    chunks.append(paras(body["why_both_paras"]))

    chunks.append(f"<h2>{e(body['h2_how_find'])}</h2>")
    chunks.append(figure(IMAGES["how_find"], body["img_how_find_alt"]))
    chunks.append(paras(body["how_find_paras"]))

    chunks.append(f"<h2>{e(body['h2_compare'])}</h2>")
    chunks.append(comparison_table(body))

    chunks.append(f"<h2>{e(body['h2_mobile'])}</h2>")
    chunks.append(figure(IMAGES["mobile"], body["img_mobile_alt"]))
    chunks.append(paras(body["mobile_paras"]))

    chunks.append(f"<h2>{e(body['h2_desktop'])}</h2>")
    chunks.append(paras(body["desktop_paras"]))

    chunks.append(f"<h2>{e(body['h2_choose'])}</h2>")
    chunks.append(paras(body["choose_paras"]))

    chunks.append(f"<h2>{e(body['h2_bonuses'])}</h2>")
    chunks.append(paras(body["bonus_paras"]))

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
            "lang_id": int(lang_id),
            "lang_url": old_by_lang.get(int(lang_id), {}).get("lang_url", code),
            "url": meta["url"],
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": build_content(body),
            "status": old_by_lang.get(int(lang_id), {}).get("status", "published"),
            "source": "content_i18n" if int(lang_id) != 1 else "main",
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
