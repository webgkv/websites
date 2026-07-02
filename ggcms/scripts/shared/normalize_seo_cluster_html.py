#!/usr/bin/env python3
"""
Normalize HTML in seo_cluster_v1 JSON exports.

Reference layout: pages/1 homepage cluster (Bootstrap tables, section-media figures, plain <p>).

Removes Google Docs cruft (inline styles, c17 tables, img-fluid/my-4 figures) while preserving
structural classes on full page layouts (sections, grid, about_content).
"""

from __future__ import annotations

import html
import json
import re
import sys
from pathlib import Path

_SCRIPTS = Path(__file__).resolve().parent
if str(_SCRIPTS) not in sys.path:
    sys.path.insert(0, str(_SCRIPTS))

# Reuse battle-tested helpers from games normalizer
from normalize_games_html import (  # noqa: E402
    _clean_text,
    _collapse_nested_figures,
    _convert_c17_table,
    _fix_img_alt_entities,
    _fix_li_figure_spans,
    _cleanup_figure_wrappers,
    _move_figures_out_of_spans,
    _normalize_img_tag,
    _remove_spacer_paragraphs,
    _table_rows,
    _unwrap_img_paragraphs,
)

STRUCTURAL_CLASS_PREFIXES = (
    "about_content",
    "page-content",
    "container",
    "row",
    "col-",
    "section",
    "main_",
    "steps_",
    "table",
    "section-media",
)


def _strip_style_attrs(content: str) -> str:
    content = re.sub(r'\sstyle="[^"]*"', "", content, flags=re.I)
    content = re.sub(r"\sstyle='[^']*'", "", content, flags=re.I)
    return content


def _unwrap_redundant_spans(content: str) -> str:
    """Remove <span> used only for Docs font/size/color."""
    prev = None
    while prev != content:
        prev = content
        content = re.sub(
            r"<span[^>]*>\s*</span>",
            "",
            content,
            flags=re.I,
        )
        content = re.sub(
            r"<span(?:\s[^>]*)?>([\s\S]*?)</span>",
            r"\1",
            content,
            flags=re.I,
        )
    return content


def _unwrap_font_tags(content: str) -> str:
    return re.sub(r"</?font[^>]*>", "", content, flags=re.I)


def _strip_google_classes(content: str) -> str:
    """Drop Docs-specific classes; keep Bootstrap / site layout classes."""

    def repl(m: re.Match[str]) -> str:
        tag, before, classes, after = m.group(1), m.group(2), m.group(3), m.group(4)
        keep = []
        for cls in classes.split():
            low = cls.lower()
            if low.startswith("c") and low[1:].isdigit():
                continue
            if low.startswith("docs-internal"):
                continue
            if low in ("msonormal", "msonormalcxspfirst", "msonormalcxspmiddle", "msonormalcxsplast"):
                continue
            if low in ("img-fluid", "rounded", "my-4", "my-3", "my-5"):
                continue
            if any(low.startswith(p) for p in STRUCTURAL_CLASS_PREFIXES):
                keep.append(cls)
            elif low in ("table", "table-bordered", "table-responsive"):
                keep.append(cls)
        if keep:
            return f"<{tag}{before} class=\"{' '.join(keep)}\"{after}>"
        return f"<{tag}{before}{after}>"

    return re.sub(
        r"<(\w+)([^>]*?)\sclass=\"([^\"]+)\"([^>]*)>",
        repl,
        content,
        flags=re.I,
    )


def _normalize_any_table(match: re.Match[str]) -> str:
    block = match.group(0)
    if "table-responsive" in block.lower():
        # Normalize inner <table> classes
        block = re.sub(
            r"<table[^>]*>",
            '<table class="table table-bordered">',
            block,
            count=1,
            flags=re.I,
        )
        return block

    inner_m = re.search(r"<table[^>]*>([\s\S]*)</table>", block, re.I)
    if not inner_m:
        return block
    rows = _table_rows(inner_m.group(1))
    if not rows:
        return block
    header, body = rows[0], rows[1:]
    parts = [
        '<div class="table-responsive">',
        '<table class="table table-bordered">',
        "<thead>",
        "<tr>",
    ]
    for cell in header:
        parts.append(f"<th>{html.escape(cell)}</th>")
    parts.extend(["</tr>", "</thead>", "<tbody>"])
    for row in body:
        parts.append("<tr>")
        for cell in row:
            parts.append(f"<td>{html.escape(cell)}</td>")
        parts.append("</tr>")
    parts.extend(["</tbody>", "</table>", "</div>"])
    return "\n".join(parts)


def _normalize_standalone_imgs(content: str) -> str:
    """Wrap bare <img> in section-media figure; leave imgs already inside <figure>."""

    def repl(m: re.Match[str]) -> str:
        tag = m.group(0)
        prefix = content[: m.start()]
        if re.search(r"<figure\b[^>]*>\s*$", prefix, flags=re.I):
            return tag
        return _normalize_img_tag(tag)

    return re.sub(r"<img\b[^>]*?/?>", repl, content, flags=re.I)


def _normalize_figure_blocks(content: str) -> str:
    """figure.my-4 + img-fluid → section-media__figure (homepage pattern)."""

    def repl_fig(m: re.Match[str]) -> str:
        inner = m.group(1)
        imgs = re.findall(r"<img[^>]*?/?>", inner, re.I)
        if not imgs:
            return m.group(0)
        return "\n".join(_normalize_img_tag(img) for img in imgs)

    content = re.sub(
        r"<figure\b[^>]*>([\s\S]*?)</figure>",
        repl_fig,
        content,
        flags=re.I,
    )
    return content


def _simplify_guide_paragraphs(content: str) -> str:
    """Guides: plain <p> without wrapper spans; keep <strong> for mini table titles."""
    if "<section" in content.lower():
        return content
    content = re.sub(
        r"<p>\s*<strong>\s*([^<]+?)\s*</strong>\s*</p>",
        r"<p><strong>\1</strong></p>",
        content,
        flags=re.I,
    )
    content = re.sub(
        r"<li>\s*<i>\s*</i>\s*",
        "<li>",
        content,
        flags=re.I,
    )
    content = re.sub(r"<li>\s*<i>\s*([^<]*?)\s*</i>\s*", r"<li>\1", content, flags=re.I)
    return content


def normalize_html(content: str, *, full_page: bool = False) -> str:
    if not content or "<" not in content:
        return content

    out = content
    out = re.sub(r"<figureclass\b", "<figure class", out, flags=re.I)
    out = _strip_style_attrs(out)
    out = _unwrap_font_tags(out)
    out = _unwrap_redundant_spans(out)

    if full_page:
        out = _remove_spacer_paragraphs(out)
        out = _fix_img_alt_entities(out)
        out = re.sub(r"(\r?\n){3,}", "\r\n\r\n", out)
        return out.strip()

    out = _strip_google_classes(out)

    out = re.sub(
        r'<table\s+class="c17"[^>]*>(.*?)</table>',
        _convert_c17_table,
        out,
        flags=re.I | re.S,
    )
    out = re.sub(
        r"(<div\s+class=\"table-responsive\">\s*)?<table\b[^>]*>[\s\S]*?</table>\s*(</div>)?",
        _normalize_any_table,
        out,
        flags=re.I,
    )

    out = _normalize_standalone_imgs(out)
    out = _normalize_figure_blocks(out)
    out = _unwrap_img_paragraphs(out)
    out = _collapse_nested_figures(out)
    out = _move_figures_out_of_spans(out)
    out = _fix_li_figure_spans(out)
    out = _cleanup_figure_wrappers(out)
    out = _remove_spacer_paragraphs(out)
    out = _fix_img_alt_entities(out)
    out = _simplify_guide_paragraphs(out)

    # Guides: optional lazy attrs (keep if already present); ensure figure img has alt
    out = re.sub(r"<p>\s*&nbsp;\s*</p>", "", out, flags=re.I)
    out = re.sub(r"(\r?\n){3,}", "\r\n\r\n", out)
    return out.strip()


def normalize_cluster_file(path: Path) -> int:
    data = json.loads(path.read_text(encoding="utf-8"))
    entity = (data.get("entity") or "").strip()
    if entity == "authors":
        return 0
    full_page = entity == "pages"
    changed = 0
    for loc in data.get("locales", []):
        old = loc.get("content") or ""
        if not old.strip() or "<" not in old:
            continue
        new = normalize_html(old, full_page=full_page)
        if new != old:
            loc["content"] = new
            changed += 1
    if changed:
        path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    return changed


def main() -> None:
    paths = [Path(p) for p in sys.argv[1:]] if len(sys.argv) > 1 else []
    if not paths:
        print("Usage: python3 normalize_seo_cluster_html.py <file.json> [more.json ...]", file=sys.stderr)
        sys.exit(1)
    total = 0
    for path in paths:
        n = normalize_cluster_file(path)
        print(f"{path}: {n} locale(s) updated")
        total += n
    print(f"Total: {total}")


if __name__ == "__main__":
    main()
