#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Local parity audit for fr (3) and ar (11) vs EN in seo cluster exports."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

HUB_PAGES = {2, 3, 7, 8, 9, 10, 11, 12, 35}
LANGS = {3: "fr", 11: "ar"}
RATIO_MIN = 0.85
RATIO_MAX = 1.15


def plain_len(html: str) -> int:
    return len(re.sub(r"<[^>]+>", "", html or "").strip())


def tag_counts(html: str) -> dict[str, int]:
    return {
        "h1": len(re.findall(r"<h1\b", html, re.I)),
        "h2": len(re.findall(r"<h2\b", html, re.I)),
        "h3": len(re.findall(r"<h3\b", html, re.I)),
        "p": len(re.findall(r"<p[> ]", html, re.I)),
        "li": len(re.findall(r"<li\b", html, re.I)),
        "img": len(re.findall(r"<img\b", html, re.I)),
        "table": len(re.findall(r"<table\b", html, re.I)),
    }


def audit_file(path: Path) -> list[str]:
    data = json.loads(path.read_text(encoding="utf-8"))
    entity = data.get("entity") or data.get("seo_monitor_ctx", {}).get("entity", "?")
    eid = int(data.get("entity_id") or 0)
    locs = {int(x["lang_id"]): x for x in data.get("locales", [])}
    en = locs.get(1, {})
    en_plain = plain_len(en.get("content") or "")
    en_tags = tag_counts(en.get("content") or "")
    is_hub = entity == "pages" and eid in HUB_PAGES and en_plain == 0
    issues: list[str] = []

    for lid, code in LANGS.items():
        loc = locs.get(lid, {})
        status = loc.get("status", "missing")
        plain = plain_len(loc.get("content") or "")
        prefix = f"{entity}#{eid} {code}"

        if status != "published":
            issues.append(f"FAIL {prefix}: status={status}")
            continue

        if is_hub:
            if not (loc.get("title") or "").strip():
                issues.append(f"FAIL {prefix}: hub missing title")
            continue

        if entity == "authors" and plain < 20:
            issues.append(f"FAIL {prefix}: bio too short ({plain}b)")
            continue

        if en_plain > 100 and plain < 20:
            issues.append(f"FAIL {prefix}: empty body")
            continue

        if en_plain > 100:
            ratio = plain / en_plain
            if ratio < RATIO_MIN or ratio > RATIO_MAX:
                issues.append(f"FAIL {prefix}: plain ratio {ratio:.0%} (want {RATIO_MIN:.0%}-{RATIO_MAX:.0%})")
            bad = [k for k in en_tags if tag_counts(loc.get("content") or "").get(k, 0) != en_tags[k]]
            if bad:
                issues.append(f"FAIL {prefix}: tag mismatch {bad}")
        else:
            issues.append(f"OK {prefix}: meta-only hub")

    if not issues:
        issues.append(f"OK {entity}#{eid}")
    return issues


def main() -> int:
    paths = [Path(p) for p in sys.argv[1:]] if len(sys.argv) > 1 else []
    if not paths:
        print("Usage: audit_fr_ar_parity.py <cluster.json> [...]", file=sys.stderr)
        return 1
    fails = 0
    for path in paths:
        if not path.is_file():
            print(f"MISSING {path}")
            fails += 1
            continue
        lines = audit_file(path)
        for line in lines:
            print(line)
            if line.startswith("FAIL"):
                fails += 1
    print(f"\nSUMMARY fails={fails}")
    return 1 if fails else 0


if __name__ == "__main__":
    raise SystemExit(main())
