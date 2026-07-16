#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Apply Swahili (lang_id 20) and Lingala (lang_id 21) to a pages cluster JSON.

Rules:
- Treat locale as missing if plain-text body < 20 chars (ignore stale published status).
- Hub pages (empty EN body): meta only (name, title, description), content stays empty.
- Content pages: require full body via pages_content_sw_ln.get_content(entity_id, lang).
"""

from __future__ import annotations

import json
import re
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from pages_hub_meta_sw_ln import HUB_META  # noqa: E402

try:
    from pages_content_sw_ln import get_content, get_meta_override  # noqa: E402
except ImportError:
    get_content = None  # type: ignore
    get_meta_override = None  # type: ignore

try:
    from home_cluster_sw_ln_sections import PAGE_META as HOME_PAGE_META, build_content as build_home_content  # noqa: E402
except ImportError:
    HOME_PAGE_META = None  # type: ignore
    build_home_content = None  # type: ignore

LANG_IDS = {20: "sw", 21: "ln"}
PLAIN_MIN = 20


def plain_len(html: str) -> int:
    return len(re.sub(r"<[^>]+>", "", html or "").strip())


def is_hub_cluster(data: dict) -> bool:
    en = next((x for x in data["locales"] if x["lang_id"] == 1), None)
    if not en:
        return False
    return plain_len(en.get("content") or "") < PLAIN_MIN


def needs_body(loc: dict | None) -> bool:
    if not loc:
        return True
    return plain_len(loc.get("content") or "") < PLAIN_MIN


def localize_hrefs(html: str, lang: str) -> str:
    if lang == "en":
        return html
    return re.sub(r'href="/en/', f'href="/{lang}/', html)


def wrap_internal_links_noads(html: str) -> str:
    def repl(m: re.Match[str]) -> str:
        tag = m.group(0)
        pre = html[max(0, m.start() - 80) : m.start()]
        if re.search(r"<noads>\s*$", pre, re.I):
            return tag
        href_m = re.search(r'href="([^"]+)"', tag, re.I)
        if not href_m:
            return tag
        href = href_m.group(1)
        if not href.startswith("/") or href.startswith("//"):
            return tag
        if href.startswith("/assets/"):
            return tag
        return f"<noads>{tag}</noads>"

    parts: list[str] = []
    pos = 0
    for m in re.finditer(r"<a\b[^>]*>.*?</a>", html, re.I | re.S):
        parts.append(html[pos : m.start()])
        parts.append(repl(m))
        pos = m.end()
    parts.append(html[pos:])
    return "".join(parts)


def apply_locale(
    loc: dict,
    lang: str,
    meta: dict[str, str],
    content: str | None,
    hub: bool,
) -> None:
    loc["lang_url"] = lang
    loc["name"] = meta["name"]
    loc["title"] = meta["title"]
    loc["description"] = meta["description"]
    if hub:
        loc["content"] = ""
    elif content is not None:
        loc["content"] = content
    loc["status"] = "published"


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    hub = is_hub_cluster(data)
    logs: list[str] = []

    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if loc is None:
            logs.append(f"lang_id={lang_id}: no locale row in cluster")
            continue

        if hub:
            meta = HUB_META.get(entity_id, {}).get(lang)
            if not meta:
                logs.append(f"pages#{entity_id} {lang}: hub meta missing")
                continue
            apply_locale(loc, lang, meta, None, hub=True)
            logs.append(f"pages#{entity_id} {lang}: hub meta applied")
            continue

        if entity_id == 1 and HOME_PAGE_META and build_home_content:
            meta = HOME_PAGE_META.get(lang)
            if not meta:
                logs.append(f"pages#{entity_id} {lang}: home meta missing")
                continue
            content = build_home_content(lang)
            content = localize_hrefs(content, lang)
            content = wrap_internal_links_noads(content)
            apply_locale(loc, lang, meta, content, hub=False)
            logs.append(
                f"pages#{entity_id} {lang}: home body {len(content.encode())}B "
                f"title={len(meta['title'])} desc={len(meta['description'])}"
            )
            continue

        if get_content is None:
            logs.append(f"pages#{entity_id} {lang}: content module missing")
            continue

        content = get_content(entity_id, lang)
        if not content:
            logs.append(f"pages#{entity_id} {lang}: content translation missing")
            continue

        content = localize_hrefs(content, lang)
        content = wrap_internal_links_noads(content)

        meta_override = get_meta_override(entity_id, lang) if get_meta_override else None
        meta_override = meta_override or {}

        en = next(x for x in data["locales"] if x["lang_id"] == 1)
        meta = {
            "name": meta_override.get("name") or en.get("name") or "",
            "title": meta_override.get("title") or en.get("title") or "",
            "description": meta_override.get("description") or en.get("description") or "",
        }

        apply_locale(loc, lang, meta, content, hub=False)
        logs.append(
            f"pages#{entity_id} {lang}: body {len(content.encode())}B "
            f"title={len(meta['title'])} desc={len(meta['description'])}"
        )

    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_pages_sw_ln_cluster.py <cluster.json> [out.json]", file=sys.stderr)
        return 1

    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))

    if dst == src:
        stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        backup = src.with_name(f"{src.stem}.backup-{stamp}{src.suffix}")
        shutil.copy2(src, backup)
        print(f"Backup: {backup}")

    for line in apply_cluster(data):
        print(line)

    dst.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Written: {dst}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
