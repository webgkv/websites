#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply Swahili (lang_id 20) and Lingala (lang_id 21) to pages#1 home cluster JSON."""

from __future__ import annotations

import json
import re
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DEFAULT_JSON = Path("/home/lenovo/Downloads/02/seo-pages-1-full.json")

sys.path.insert(0, str(TOOLS))
from home_cluster_sw_ln_sections import PAGE_META, build_content  # noqa: E402

LANG_MAP = {
    20: "sw",
    21: "ln",
}


def stats(html: str) -> dict[str, int]:
    return {
        "bytes": len((html or "").encode()),
        "h2": len(re.findall(r"<h2", html or "", re.I)),
        "h3": len(re.findall(r"<h3", html or "", re.I)),
        "p": len(re.findall(r"<p[> ]", html or "", re.I)),
        "details": len(re.findall(r"<details", html or "", re.I)),
        "noads": len(re.findall(r"<noads", html or "", re.I)),
        "img": len(re.findall(r"<img", html or "", re.I)),
        "li": len(re.findall(r"<li", html or "", re.I)),
    }


def verify_noads_links(html: str, lang: str) -> list[str]:
    errors: list[str] = []
    for m in re.finditer(r'href="([^"]+)"', html):
        href = m.group(1)
        if not href.startswith("/"):
            continue
        if href.startswith("/demo"):
            continue
        if href.startswith(f"/{lang}/"):
            continue
        if href.startswith("/assets/"):
            continue
        errors.append(f"bad href {href}")
    for m in re.finditer(r'(?<!<noads>)<a\b[^>]*href="(/[^"]+)"', html, re.I):
        href = m.group(1)
        if href.startswith("/assets/"):
            continue
        errors.append(f"unwrapped internal link: {href}")
    return errors


def main() -> int:
    json_path = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_JSON
    if not json_path.is_file():
        print(f"Missing file: {json_path}", file=sys.stderr)
        return 1

    stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    backup = json_path.with_name(f"{json_path.stem}.backup-{stamp}{json_path.suffix}")
    shutil.copy2(json_path, backup)
    print(f"Backup: {backup}")

    data = json.loads(json_path.read_text(encoding="utf-8"))
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    base = stats(en["content"])

    for lang_id, code in LANG_MAP.items():
        loc = next(x for x in data["locales"] if x["lang_id"] == lang_id)
        meta = PAGE_META[code]
        content = build_content(code)
        loc["lang_url"] = code
        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = content
        loc["status"] = "published"

        s = stats(content)
        ratio = s["bytes"] / base["bytes"] if base["bytes"] else 0
        bad_tags = [k for k in base if s[k] != base[k]]
        link_errors = verify_noads_links(content, code)
        flag = "OK" if not bad_tags and 0.85 <= ratio <= 1.15 and not link_errors else "CHECK"
        print(
            f"{flag} lang_id={lang_id} ({code}) bytes={ratio:.0%} tags={bad_tags or 'match'} "
            f"links={len(link_errors)} title={len(meta['title'])} desc={len(meta['description'])}"
        )
        for err in link_errors[:5]:
            print(f"  - {err}")

    json_path.write_text(
        json.dumps(data, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
    )
    print(f"Updated: {json_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
