#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild blog#3 EN canonical HTML in the working *-full.json.

Only lang_id=1 is regenerated from the structured EN body. All target locales
stay as edited in the JSON (agent handoff).
"""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT / "tools") not in sys.path:
    sys.path.insert(0, str(ROOT / "tools"))

from chickenroad_blog_3_locales import (  # noqa: E402
    EXAMPLE_LINK,
    IMAGES,
    LOCALE_META,
    PARTNER_HREFS,
    get_en_body,
)

CLUSTER_IN = Path("/home/lenovo/Downloads/09/seo-blog-3-full.json")
OUT_PATH = CLUSTER_IN


def e(text: str) -> str:
    return html.escape(text, quote=False)


def figure(src: str, alt: str) -> str:
    return (
        f'<figure class="section-media__figure">'
        f'<img src="{src}" border="0" alt="{e(alt)}" />'
        f"</figure>"
    )


def paras(items) -> str:
    return "".join(f"<p>{e(p)}</p>" for p in items if p)


def link_para(pre: str, anchor: str, post: str, href: str) -> str:
    return f'<p>{e(pre)}<a href="{e(href)}">{e(anchor)}</a>{e(post)}</p>'


def ul(items) -> str:
    return "<ul>" + "".join(f"<li>{e(i)}</li>" for i in items) + "</ul>"


def build_content(body: dict, lang_code: str) -> str:
    demo_href = f"/{lang_code}/demo/"
    c: list[str] = []
    c.append(f"<h1>{e(body['h1'])}</h1>")
    c.append(figure(IMAGES["hero"], body["img_hero_alt"]))
    c.append(paras(body["intro_paras"]))

    c.append(f"<h2>{e(body['h2_short'])}</h2>")
    c.append(paras(body["short_paras"]))

    c.append(f"<h2>{e(body['h2_work'])}</h2>")
    c.append(paras(body["work_paras"]))
    c.append(ul(body["red_flags"]))
    c.append(paras([body["work_after"]]))

    c.append(f"<h2>{e(body['h2_casino'])}</h2>")
    c.append(figure(IMAGES["casino"], body["img_casino_alt"]))
    c.append(paras(body["casino_paras"]))

    c.append(f"<h2>{e(body['h2_crash'])}</h2>")
    c.append(figure(IMAGES["crash"], body["img_crash_alt"]))
    c.append(link_para(body["crash_p1_pre"], body["crash_p1_anchor"], body["crash_p1_post"], demo_href))
    c.append(paras([body["crash_p2"]]))

    c.append(f"<h2>{e(body['h2_bingo'])}</h2>")
    c.append(paras(body["bingo_paras"]))

    c.append(f"<h2>{e(body['h2_paypal'])}</h2>")
    c.append(paras(body["paypal_paras"]))

    c.append(f"<h2>{e(body['h2_free'])}</h2>")
    c.append(paras(body["free_paras"]))

    c.append(f"<h2>{e(body['h2_mobile'])}</h2>")
    c.append(figure(IMAGES["mobile"], body["img_mobile_alt"]))
    c.append(paras(body["mobile_paras"]))

    c.append(f"<h2>{e(body['h2_other'])}</h2>")
    c.append(paras(body["other_paras"]))
    ol = "<ol>"
    for lead, rest in body["other_list"]:
        ol += f"<li><strong>{e(lead)} </strong>{e(rest)}</li>"
    ol += "</ol>"
    c.append(ol)
    c.append(paras([body["other_after"]]))

    c.append(f"<h2>{e(body['h2_uk'])}</h2>")
    c.append(paras(body["uk_paras"]))

    c.append(f"<h2>{e(body['h2_where'])}</h2>")
    c.append(paras([body["where_intro"]]))
    partners_ul = "<ul>"
    for name, desc in body["partners"]:
        href = PARTNER_HREFS[name]
        partners_ul += f'<li><a href="{e(href)}">{e(name)}</a> &ndash; {e(desc)}</li>'
    partners_ul += "</ul>"
    c.append(partners_ul)
    c.append(link_para(body["where_after_pre"], body["where_after_anchor"], body["where_after_post"], EXAMPLE_LINK))

    c.append(f"<h2>{e(body['h2_safe'])}</h2>")
    c.append(paras([body["safe_p1"]]))
    c.append(link_para(body["safe_p2_pre"], body["safe_p2_anchor"], body["safe_p2_post"], EXAMPLE_LINK))
    c.append(link_para(body["safe_p3_pre"], body["safe_p3_anchor"], body["safe_p3_post"], EXAMPLE_LINK))

    c.append(f"<h2>{e(body['h2_faq'])}</h2>")
    for q, a in body["faq"]:
        c.append(f"<h3>{e(q)}</h3>")
        c.append(f"<p>{e(a)}</p>")

    return "\n".join(c)


def main() -> None:
    with CLUSTER_IN.open(encoding="utf-8") as f:
        cluster = json.load(f)

    meta = LOCALE_META["1"]
    content = build_content(get_en_body(), meta["code"])

    updated = False
    for i, loc in enumerate(cluster["locales"]):
        if int(loc["lang_id"]) != 1:
            continue
        cluster["locales"][i] = {
            **loc,
            "lang_url": meta["code"],
            "url": meta["url"],
            "name": meta["name"],
            "title": meta["title"],
            "description": meta["description"],
            "content": content,
            "status": "published",
            "source": "main",
        }
        updated = True
        break

    if not updated:
        raise SystemExit("lang_id=1 not found in cluster JSON")

    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S+00:00")
    with OUT_PATH.open("w", encoding="utf-8") as f:
        json.dump(cluster, f, ensure_ascii=False, indent=4)
        f.write("\n")

    n = len(cluster["locales"])
    print(f"Wrote EN canonical to {OUT_PATH} ({n} locales in file, only EN rebuilt)")


if __name__ == "__main__":
    main()
