#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build pages#1 home cluster: EN canonical + full locale parity from section translations."""

from __future__ import annotations

import json
import re
import sys
from copy import deepcopy
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
DEFAULT_IN = Path("/Users/gk/Downloads/08/seo-pages-1-full.json")
DEFAULT_OUT = DEFAULT_IN
TABLE_TEXT_STYLE = 'font-size: 16px; line-height: 1.5;'

SECTION_IDS = (
    "lead",
    "ice-fish-app",
    "game-works",
    "features",
    "demo-steps",
    "batting",
    "demo-vs-real",
    "where-to-play",
    "tips",
    "game-specs",
    "faq",
)

LOCALE_META = {
    1: {"code": "en", "name": "Home"},
    3: {"code": "fr", "name": "Accueil"},
    4: {"code": "de", "name": "Start"},
    6: {"code": "es", "name": "Inicio"},
    7: {"code": "hi", "name": "होम"},
    8: {"code": "pt", "name": "Início"},
    9: {"code": "ru", "name": "Главная"},
    11: {"code": "ar", "name": "الرئيسية"},
    12: {"code": "az", "name": "Ana səhifə"},
    13: {"code": "bn", "name": "হোম"},
    14: {"code": "it", "name": "Home"},
    15: {"code": "nl", "name": "Home"},
    16: {"code": "pl", "name": "Strona główna"},
    17: {"code": "vi", "name": "Trang chủ"},
    18: {"code": "ua", "name": "Головна"},
    19: {"code": "ro", "name": "Acasă"},
}


def extract_en_sections(en_html: str) -> dict[str, str]:
    sections: dict[str, str] = {}
    lead = re.search(
        r'(<div class="about_content page-content-lead">[\s\S]*?</div>)',
        en_html,
        re.I,
    )
    sections["lead"] = lead.group(1) if lead else ""
    for sid in SECTION_IDS:
        if sid == "lead":
            continue
        m = re.search(rf'(<section id="{re.escape(sid)}"[\s\S]*?</section>)', en_html, re.I)
        sections[sid] = m.group(1) if m else ""
    return sections


def assemble_content(sections: dict[str, str]) -> str:
    parts = [sections.get("lead", "")]
    for sid in SECTION_IDS:
        if sid == "lead":
            continue
        block = sections.get(sid, "")
        if block:
            parts.append(block)
    return "\n".join(parts)


def normalize_table_inline_styles(html: str) -> str:
    """Keep table text readable on mobile: smaller text, no forced word splitting."""

    def repl(match: re.Match[str]) -> str:
        block = match.group(0)
        block = re.sub(
            r'<(thead|tbody|tr)([^>]*)\sstyle="[^"]*"([^>]*)>',
            r"<\1\2\3>",
            block,
            flags=re.I,
        )
        block = re.sub(
            r'<table([^>]*)\sstyle="[^"]*"([^>]*)>',
            r"<table\1\2>",
            block,
            count=1,
            flags=re.I,
        )
        block = re.sub(
            r"<(th|td)\b([^>]*)>",
            lambda m: _normalize_table_cell_tag(m.group(1), m.group(2)),
            block,
            flags=re.I,
        )
        return block

    return re.sub(
        r"(?:<div[^>]*table-responsive[^>]*>\s*)?<table[\s\S]*?</table>\s*(?:</div>)?",
        repl,
        html,
        flags=re.I,
    )


def _normalize_table_cell_tag(tag: str, attrs: str) -> str:
    attrs = re.sub(r'\sstyle="[^"]*"', "", attrs, flags=re.I)
    attrs = attrs.rstrip()
    return f'<{tag}{attrs} style="{TABLE_TEXT_STYLE}">'


def stats(html: str) -> dict:
    return {
        "bytes": len(html),
        "h1": len(re.findall(r"<h1\b", html, re.I)),
        "h2": len(re.findall(r"<h2\b", html, re.I)),
        "font20": len(re.findall(r"font-size:\s*20px", html)),
        "internal_a": len(
            [
                1
                for m in re.finditer(r'href="(/[^"]+)"', html)
                if m.group(1).startswith("/") and not m.group(1).startswith("//")
            ]
        ),
        "noads": len(re.findall(r"<noads>\s*<a\b", html, re.I)),
    }


def main() -> None:
    src = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_IN
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_OUT

    data = json.loads(src.read_text(encoding="utf-8"))
    old_by_lang = {loc["lang_id"]: loc for loc in data["locales"]}

    en_loc = old_by_lang[1]
    en_html = en_loc["content"]
    en_sections = extract_en_sections(en_html)

    try:
        from home_cluster_sections_i18n import SECTIONS_I18N  # noqa: WPS433
    except ImportError as exc:
        raise SystemExit(
            "Missing home_cluster_sections_i18n.py — run section translation generator first."
        ) from exc

    new_locales = []
    for lang_id, meta in LOCALE_META.items():
        code = meta["code"]
        old = old_by_lang.get(lang_id, {})
        if code == "en":
            content = assemble_content(en_sections)
        else:
            trans = SECTIONS_I18N.get(code)
            if not trans:
                raise SystemExit(f"No translations for locale {code}")
            missing = [s for s in SECTION_IDS if not trans.get(s)]
            if missing:
                raise SystemExit(f"Locale {code} missing sections: {missing}")
            content = assemble_content(trans)
        content = normalize_table_inline_styles(content)

        loc = {
            "lang_id": lang_id,
            "lang_url": old.get("lang_url", code),
            "url": old.get("url", "/"),
            "name": old.get("name", meta["name"]),
            "title": old.get("title", ""),
            "description": old.get("description", ""),
            "content": content,
            "status": old.get("status", "published"),
            "source": old.get("source", "export"),
            "seo_monitor_ctx": old.get("seo_monitor_ctx"),
        }
        st = stats(content)
        print(
            f"{code}: bytes={st['bytes']} h1={st['h1']} h2={st['h2']} "
            f"font20={st['font20']} links={st['internal_a']} noads={st['noads']}"
        )
        new_locales.append(loc)

    data["locales"] = new_locales
    data["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(data, ensure_ascii=False, indent=4)
    dst.write_text(payload + "\n", encoding="utf-8")
    print(f"\nWrote {dst}")


if __name__ == "__main__":
    main()
