#!/usr/bin/env python3
"""Normalize games cluster HTML: Bootstrap tables + centred section-media images."""

from __future__ import annotations

import html
import json
import re
import sys
from pathlib import Path


def _clean_text(fragment: str) -> str:
    text = re.sub(r"<br\s*/?>", " ", fragment, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = (
        text.replace("&nbsp;", " ")
        .replace("&amp;", "&")
        .replace("&quot;", '"')
        .replace("&#39;", "'")
        .replace("&ldquo;", '"')
        .replace("&rdquo;", '"')
        .replace("&mdash;", "—")
    )
    return re.sub(r"\s+", " ", text).strip()


def _table_rows(table_inner: str) -> list[list[str]]:
    rows: list[list[str]] = []
    for tr in re.findall(r"<tr[^>]*>(.*?)</tr>", table_inner, re.S | re.I):
        cells = [
            _clean_text(c)
            for c in re.findall(r"<t[dh][^>]*>(.*?)</t[dh]>", tr, re.S | re.I)
        ]
        if any(cells):
            rows.append(cells)
    return rows


def _convert_c17_table(match: re.Match[str]) -> str:
    inner = match.group(1)
    rows = _table_rows(inner)
    if not rows:
        return match.group(0)

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


def _normalize_img_tag(img_tag: str) -> str:
    src_m = re.search(r'\bsrc="([^"]*)"', img_tag, re.I)
    alt_m = re.search(r'\balt="([^"]*)"', img_tag, re.I)
    if not src_m:
        return img_tag
    src = src_m.group(1)
    alt = alt_m.group(1) if alt_m else ""
    border = ' border="0"' if re.search(r'\bborder="0"', img_tag, re.I) else ""
    return (
        f'<figure class="section-media__figure">'
        f'<img src="{src}"{border} alt="{alt}" />'
        f"</figure>"
    )


def _unwrap_img_paragraphs(content: str) -> str:
    # <p><span ...><img .../></span></p> or bare <p><img/></p>
    def repl_p(m: re.Match[str]) -> str:
        inner = m.group(1)
        imgs = re.findall(r"<img[^>]*?/?>", inner, re.I)
        if not imgs:
            return m.group(0)
        if re.sub(r"<img[^>]*?/?>", "", inner, flags=re.I).strip():
            return m.group(0)
        return "\n".join(_normalize_img_tag(img) for img in imgs)

    content = re.sub(
        r"<p[^>]*>(\s*(?:<span[^>]*>\s*)?<img[^>]*?/?>(?:\s*</span>)?\s*)</p>",
        repl_p,
        content,
        flags=re.I,
    )
    # Images inside list items — only raw <img>, skip if already in <figure>
    def repl_li_img(m: re.Match[str]) -> str:
        prefix, img_tag, suffix = m.group(1), m.group(2), m.group(3)
        if "<figure" in prefix.lower():
            return m.group(0)
        return prefix + _normalize_img_tag(img_tag) + suffix

    content = re.sub(
        r"(<li[^>]*>.*?)(<img[^>]*?/?>)(.*?</li>)",
        repl_li_img,
        content,
        flags=re.I | re.S,
    )
    return content


def _collapse_nested_figures(content: str) -> str:
    prev = None
    while prev != content:
        prev = content
        content = re.sub(
            r'(<figure class="section-media__figure">)\s*<figure class="section-media__figure">',
            r"\1",
            content,
            flags=re.I,
        )
        content = re.sub(
            r"</figure>\s*</figure>",
            "</figure>",
            content,
            flags=re.I,
        )
    return content


def _move_figures_out_of_spans(content: str) -> str:
    """Figure inside inline <span> (common in Google Docs lists) breaks centring."""

    def repl(m: re.Match[str]) -> str:
        span_open, title, br, figure, tail = (
            m.group(1),
            m.group(2),
            m.group(3) or "",
            m.group(4),
            m.group(5),
        )
        title = re.sub(r"<br\s*/?>\s*$", "", title, flags=re.I)
        return f"{span_open}{title}</span>{br}{figure}{tail}"

    content = re.sub(
        r"(<span[^>]*>)([\s\S]*?)(<br\s*/?>\s*)?"
        r'(<figure class="section-media__figure">[\s\S]*?</figure>)'
        r"(\s*(?:<br\s*/?>)?\s*</span>)",
        repl,
        content,
        flags=re.I,
    )
    # Google Docs list items often omit </span> before the figure
    content = re.sub(
        r"(<span[^>]*>)([^<]+?)\s*<br\s*/?>\s*"
        r'(<figure class="section-media__figure">[\s\S]*?</figure>)',
        r"\1\2</span>\n\3",
        content,
        flags=re.I,
    )
    return content


def _fix_li_figure_spans(content: str) -> str:
    """Close title span before figure inside <li> and drop empty trailing spans."""

    def fix_li(m: re.Match[str]) -> str:
        li_open, inner, li_close = m.group(1), m.group(2), m.group(3)
        if "section-media__figure" not in inner:
            return m.group(0)
        inner = re.sub(
            r"(<span[^>]*>)([^<]+?)\s*<br\s*/?>\s*"
            r'(<figure class="section-media__figure">[\s\S]*?</figure>)',
            r"\1\2</span>\n\3",
            inner,
            flags=re.I,
        )
        inner = re.sub(r"<span[^>]*>\s*</span>\s*", "", inner, flags=re.I)
        return li_open + inner + li_close

    return re.sub(r"(<li[^>]*>)(.*?)(</li>)", fix_li, content, flags=re.I | re.S)


def _cleanup_figure_wrappers(content: str) -> str:
    content = re.sub(
        r"<p[^>]*>\s*(?:<span[^>]*>\s*)?(<figure class=\"section-media__figure\">[\s\S]*?</figure>)\s*(?:</span>\s*)?</p>",
        r"\1",
        content,
        flags=re.I,
    )
    return content


def _add_break_after_comparison_table(content: str) -> str:
    """Section 6 (h.pebmnhn1iavc) follows the comparison table — add a visible line break."""
    if 'id="h.pebmnhn1iavc"' not in content:
        return content
    if re.search(
        r"</table>\s*</div>\s*<br",
        content,
        flags=re.I,
    ):
        return content
    return re.sub(
        r"(</table>\s*</div>)\s*(<h3[^>]*\bid=\"h\.pebmnhn1iavc\"[^>]*>)",
        r"\1\r\n<br /><br />\r\n\2",
        content,
        count=1,
        flags=re.I,
    )


def _remove_spacer_paragraphs(content: str) -> str:
    """Drop Google Docs spacer paragraphs that create huge vertical gaps."""
    prev = None
    while prev != content:
        prev = content
        content = re.sub(
            r"<p[^>]*>\s*(?:&nbsp;|&#160;|\u00a0)(?:\s*(?:&nbsp;|&#160;|\u00a0))*\s*</p>",
            "",
            content,
            flags=re.I,
        )
        content = re.sub(
            r"<p[^>]*>\s*<span[^>]*>\s*(?:&nbsp;|&#160;|\u00a0)\s*</span>\s*</p>",
            "",
            content,
            flags=re.I,
        )
        content = re.sub(r"<p[^>]*>\s*</p>", "", content, flags=re.I)
    content = re.sub(r"(\r?\n){3,}", "\r\n\r\n", content)
    return content


def _fix_img_alt_entities(content: str) -> str:
    def fix_alt(m: re.Match[str]) -> str:
        alt = m.group(1)
        alt = alt.replace("&amp;rsquo;", "&rsquo;").replace("&amp;quot;", "&quot;")
        alt = alt.replace("&amp;amp;", "&amp;")
        return f'alt="{alt}"'

    return re.sub(r'alt="([^"]*)"', fix_alt, content, flags=re.I)


def normalize_content(content: str) -> str:
    out = content
    out = re.sub(
        r'<table\s+class="c17"[^>]*>(.*?)</table>',
        _convert_c17_table,
        out,
        flags=re.I | re.S,
    )
    out = re.sub(
        r"<img[^>]*class=\"[^\"]*img-fluid[^\"]*\"[^>]*?/?>",
        lambda m: _normalize_img_tag(m.group(0)),
        out,
        flags=re.I,
    )
    out = _unwrap_img_paragraphs(out)
    out = _collapse_nested_figures(out)
    out = _move_figures_out_of_spans(out)
    out = _fix_li_figure_spans(out)
    out = _cleanup_figure_wrappers(out)
    out = _remove_spacer_paragraphs(out)
    out = _add_break_after_comparison_table(out)
    out = _fix_img_alt_entities(out)
    return out


def main() -> None:
    path = Path(sys.argv[1] if len(sys.argv) > 1 else "seo-games-7-full.json")
    data = json.loads(path.read_text(encoding="utf-8"))
    changed = 0
    for loc in data.get("locales", []):
        old = loc.get("content") or ""
        new = normalize_content(old)
        if new != old:
            loc["content"] = new
            changed += 1
    path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Updated {changed} locales in {path}")


if __name__ == "__main__":
    main()
