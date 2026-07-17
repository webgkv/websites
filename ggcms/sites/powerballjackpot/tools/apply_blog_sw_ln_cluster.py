#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply sw/ln to Ice Fish blog cluster JSON."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
CHICKEN = Path(__file__).resolve().parents[1].parent / "chickenroad" / "tools"
sys.path.insert(0, str(CHICKEN))
sys.path.insert(0, str(TOOLS))

from games_i18n_utils import apply_pairs, localize_hrefs, plain_len, sanitize_en_html, tag_counts, wrap_internal_links_noads  # noqa: E402
from blog_sw_ln import get_fr_ln_pairs, get_meta, get_pairs, ln_from_fr  # noqa: E402
from fix_en_seo_html import fix_en_content  # noqa: E402

LANG_IDS = {20: "sw", 21: "ln"}
PLAIN_MIN = 20


def en_template(data: dict) -> str:
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    eid = int(data.get("entity_id") or 0)
    entity = str(data.get("entity") or "blog")
    html = fix_en_content(entity, eid, en.get("content") or "")
    en["content"] = html
    return sanitize_en_html(html)


def fr_template(data: dict) -> str:
    fr = next((x for x in data["locales"] if x["lang_id"] == 3), None)
    return (fr.get("content") or "") if fr else ""


def build_sw(data: dict) -> str | None:
    eid = int(data.get("entity_id") or 0)
    pairs = get_pairs(eid, "sw")
    if not pairs:
        return None
    return wrap_internal_links_noads(localize_hrefs(apply_pairs(en_template(data), pairs), "sw"))


def build_ln(data: dict) -> str | None:
    eid = int(data.get("entity_id") or 0)
    if ln_from_fr(eid):
        pairs, src = get_fr_ln_pairs(eid), fr_template(data)
    else:
        pairs, src = get_pairs(eid, "ln"), en_template(data)
    if not pairs or not src:
        return None
    return wrap_internal_links_noads(localize_hrefs(apply_pairs(src, pairs), "ln"))


def apply_cluster(data: dict) -> list[str]:
    eid = int(data.get("entity_id") or 0)
    entity = str(data.get("entity") or "blog")
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en["content"] = fix_en_content(entity, eid, en.get("content") or "")
    en_s = sanitize_en_html(en.get("content") or "")
    base = tag_counts(en_s)
    fr_b = tag_counts(fr_template(data)) if fr_template(data) else base
    for lang_id, lang in LANG_IDS.items():
        loc = next((x for x in data["locales"] if x["lang_id"] == lang_id), None)
        if not loc:
            continue
        meta = get_meta(eid, lang)
        content = build_sw(data) if lang == "sw" else build_ln(data)
        if not meta or not content or plain_len(content) < PLAIN_MIN:
            logs.append(f"blog#{eid} {lang}: missing/short")
            continue
        tb = fr_b if lang == "ln" and ln_from_fr(eid) else base
        bad = [k for k in tb if tag_counts(content).get(k, 0) != tb[k]]
        loc.update(lang_url=lang, name=meta["name"], title=meta["title"], description=meta["description"], content=content, status="published")
        logs.append(f"blog#{eid} {lang}: {plain_len(content)} tags={bad or 'match'}")
    return logs


def main() -> int:
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))
    if dst == src:
        b = src.with_name(f"{src.stem}.backup-{datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')}{src.suffix}")
        shutil.copy2(src, b)
    for line in apply_cluster(data):
        print(line)
    dst.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
