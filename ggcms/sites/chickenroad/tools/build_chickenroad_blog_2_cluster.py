#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild blog#2 Chicken Road legit/scam cluster — clean HTML + locale translations."""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT / "tools") not in sys.path:
    sys.path.insert(0, str(ROOT / "tools"))

from chickenroad_blog_2_locales import IMAGES, LOCALE_META, get_body  # noqa: E402

CLUSTER_IN = Path("/home/lenovo/Downloads/09/seo-blog-2-full.json")
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
    return "".join(f"<p>{item}</p>" for item in items if item)


def li_items(items: list[str]) -> str:
    return "<ul>" + "".join(f"<li>{item}</li>" for item in items) + "</ul>"


def build_content(body: dict) -> str:
    chunks: list[str] = []
    chunks.append(f"<h1>{e(body['h1'])}</h1>")
    chunks.append(figure(IMAGES["hero"], body["img_hero_alt"]))
    chunks.append(paras(body["intro_paras"]))

    chunks.append(f"<h2>{e(body['h2_short'])}</h2>")
    chunks.append(paras(body["short_paras"]))

    chunks.append(f"<h2>{e(body['h2_provider'])}</h2>")
    chunks.append(figure(IMAGES["inout"], body["img_inout_alt"]))
    chunks.append(paras(body["provider_paras"][:3]))
    chunks.append(li_items(body["casino_list"]))
    chunks.append(paras(body["provider_paras"][3:]))

    chunks.append(f"<h2>{e(body['h2_provably'])}</h2>")
    chunks.append(paras(body["provably_paras"]))

    chunks.append(f"<h2>{e(body['h2_why_scam'])}</h2>")
    chunks.append(figure(IMAGES["scams"], body["img_scams_alt"]))
    chunks.append(paras(body["why_paras"]))

    chunks.append(f"<h2>{e(body['h2_fake'])}</h2>")
    chunks.append(figure(IMAGES["fake"], body["img_fake_alt"]))
    chunks.append(paras([body["fake_p1"]]))
    chunks.append(li_items(body["fake_checklist"]))
    chunks.append(paras([body["fake_p2"]]))
    chunks.append(li_items(body["fake_redflags"]))
    chunks.append(paras([body["fake_p3"]]))

    chunks.append(f"<h2>{e(body['h2_app'])}</h2>")
    chunks.append(paras(body["app_paras"]))

    chunks.append(f"<h2>{e(body['h2_casinos'])}</h2>")
    chunks.append(paras([body["casinos_intro"]]))
    ol = "<ol>"
    for name, desc in body["casino_entries"]:
        ol += f"<li><strong>{e(name)}</strong> &ndash; {desc}</li>"
    ol += "</ol>"
    chunks.append(ol)
    chunks.append(paras([body["casinos_outro"]]))

    chunks.append(f"<h2>{e(body['h2_uk'])}</h2>")
    chunks.append(figure(IMAGES["uk"], body["img_uk_alt"]))
    chunks.append(paras(body["uk_paras"]))

    chunks.append(f"<h2>{e(body['h2_names'])}</h2>")
    chunks.append(paras(body["names_paras"]))

    chunks.append(f"<h2>{e(body['h2_safe'])}</h2>")
    chunks.append(paras(body["safe_paras"]))

    chunks.append(f"<h2>{e(body['h2_faq'])}</h2>")
    for q, a in body["faq"]:
        chunks.append(f"<h3>{e(q)}</h3>")
        chunks.append(f"<p>{e(a)}</p>")

    return "\n".join(chunks)


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)
    old_by_lang = {int(loc["lang_id"]): loc for loc in cluster["locales"]}

    for lang_id_str, meta in LOCALE_META.items():
        lang_id = int(lang_id_str)
        body = get_body(lang_id_str)
        content = build_content(body)
        old = old_by_lang.get(lang_id, {})
        cluster_loc = {
            "lang_id": lang_id,
            "lang_url": meta["code"],
            "url": meta["url"],
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": content,
            "status": "published" if lang_id == 1 else "published",
            "source": "main" if lang_id == 1 else "content_i18n",
            "seo_monitor_ctx": old.get(
                "seo_monitor_ctx", {"entity": "blog", "entity_id": 2}
            ),
        }
        for i, loc in enumerate(cluster["locales"]):
            if int(loc["lang_id"]) == lang_id:
                cluster["locales"][i] = cluster_loc
                break

    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S+00:00")
    with OUT_PATH.open("w", encoding="utf-8") as f:
        json.dump(cluster, f, ensure_ascii=False, indent=4)
        f.write("\n")
    print(f"Wrote {OUT_PATH} ({len(LOCALE_META)} locales)")


if __name__ == "__main__":
    main()
