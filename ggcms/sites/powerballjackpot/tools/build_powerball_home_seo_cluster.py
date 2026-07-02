#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build pages#1 home SEO cluster for PowerBall Jackpot (meta + minimal H1 content)."""

from __future__ import annotations

import html
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

from powerball_home_seo_locales import LOCALE_META

ROOT = Path(__file__).resolve().parents[1]
OUT_REPO = ROOT / "site/files/reference/seo-pages-1-full.json"
OUT_DL = Path("/home/lenovo/Downloads/04/seo-pages-1-full.json")


def content_block(h1: str) -> str:
    return (
        '<div class="about_content page-content-lead">\n'
        f"<h1>{html.escape(h1, quote=False)}</h1>\n"
        "</div>"
    )


def build_cluster() -> dict:
    locales = []
    for lang_id in sorted(LOCALE_META.keys()):
        meta = LOCALE_META[lang_id]
        locales.append(
            {
                "lang_id": lang_id,
                "lang_url": meta["code"],
                "url": "",
                "name": meta["name"],
                "title": meta["title"],
                "description": meta["description"],
                "content": content_block(meta["h1"]),
                "status": "published",
                "source": "export",
                "seo_monitor_ctx": {"entity": "pages", "entity_id": 1},
            }
        )
    return {
        "schema": "seo_cluster_v1",
        "exported_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "entity": "pages",
        "entity_id": 1,
        "mode": "full",
        "locales": locales,
    }


def audit(cluster: dict) -> list[str]:
    issues: list[str] = []
    for loc in cluster["locales"]:
        lid = loc["lang_id"]
        title = loc["title"]
        desc = loc["description"]
        if len(title) > 70:
            issues.append(f"lang_id {lid}: title too long ({len(title)}): {title}")
        if len(desc) > 160:
            issues.append(f"lang_id {lid}: description too long ({len(desc)}): {desc}")
        content = loc["content"]
        h1_count = content.lower().count("<h1")
        if h1_count != 1:
            issues.append(f"lang_id {lid}: expected 1 h1, got {h1_count}")
        if "chicken" in content.lower() or "chicken" in title.lower():
            issues.append(f"lang_id {lid}: legacy Chicken Road copy detected")
    return issues


def main() -> int:
    cluster = build_cluster()
    issues = audit(cluster)
    if issues:
        for msg in issues:
            print(f"AUDIT FAIL: {msg}", file=sys.stderr)
        return 1

    text = json.dumps(cluster, ensure_ascii=False, indent=4) + "\n"
    OUT_REPO.parent.mkdir(parents=True, exist_ok=True)
    OUT_REPO.write_text(text, encoding="utf-8")
    print(f"Wrote {OUT_REPO} ({len(cluster['locales'])} locales)")

    OUT_DL.parent.mkdir(parents=True, exist_ok=True)
    OUT_DL.write_text(text, encoding="utf-8")
    print(f"Wrote {OUT_DL}")

    for loc in cluster["locales"]:
        print(
            f"  [{loc['lang_id']:>2} {loc['lang_url']}] "
            f"title={len(loc['title']):>2} desc={len(loc['description']):>3} "
            f"name={loc['name']!r}"
        )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
