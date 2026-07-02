#!/usr/bin/env python3
"""
Prepare Aviator Sypex Dumper (SXD20) dump for Chicken Road.

Keeps ALL Aviator content (pages, menu, guides, games, casinos, translations, …)
except blog posts and blog-related i18n/jobs.

Output format = same SXD20 as source (Sypex Dumper 2.x import).

Usage:
  python3 scripts/prepare_chickenroad_db.py \\
    tmp/db/dikodo_aviator_2026-05-18_14-51-15.sql.gz \\
    tmp/db/chickenroad_prepared.sql.gz
"""

from __future__ import annotations

import gzip
import re
import sys
from pathlib import Path

# No INSERT at all (structure only via CREATE)
SKIP_TABLE_INSERT = frozenset({
    "blog",
    "blog_category",
    "blog_tags",
})

# Filter rows inside these INSERT blocks
FILTER_ENTITY_FIELD = frozenset({
    "content_i18n",
    "translation_cluster_state",
})

FILTER_ADMIN_JOBS = True

REBRAND = (
    (re.compile(r"Aviator Log In", re.I), "Chicken Road"),
    (re.compile(r"Aviator Log-in", re.I), "Chicken Road"),
    (re.compile(r"\bAviator\b"), "Chicken Road"),
    (re.compile(r"\baviator\b"), "chicken-road"),
    (re.compile(r"dikodo_aviator", re.I), "dikodo_chickenroad"),
    (re.compile(r"chickenroad-hero\.png", re.I), "chickenroad-hero.webp"),
    (re.compile(r"aviator-app-and-mobile-version\.png", re.I), "chickenroad-hero.webp"),
    (re.compile(r"aviator-app-and-mobile-version\.webp", re.I), "chickenroad-hero.webp"),
)

TA_CHUNK_RE = re.compile(r"([^`|]+)`(\d+)`(\d+)")
ROW_START_RE = re.compile(r"^\s*\((\d+),'([^']+)'")
ROW_END_RE = re.compile(r"\)\s*,?\s*(;)?\s*\t?\s*$")


def rebrand(text: str) -> str:
    for pat, repl in REBRAND:
        text = pat.sub(repl, text)
    return text


def patch_sxd_header(line: str) -> str:
    if not line.startswith("#SXD20|"):
        return line
    parts = line.rstrip("\n").split("|")
    if len(parts) >= 6:
        parts[5] = "dikodo_chickenroad"
    return "|".join(parts) + "\n"


def patch_ta_line(line: str) -> str:
    """Zero row counts for emptied blog tables in #TA metadata."""
    if not line.startswith("#TA "):
        return line
    body = line[3:].strip()
    chunks = TA_CHUNK_RE.findall(body)
    out = []
    for name, count, size in chunks:
        if name in SKIP_TABLE_INSERT:
            out.append(f"{name}`0`16384")
        else:
            out.append(f"{name}`{count}`{size}")
    return "#TA " + "|".join(out) + "\n"


def table_from_marker(line: str) -> str | None:
    m = re.search(r"`([^`]+)`", line)
    return m.group(1) if m else None


def should_skip_entity_row(entity: str) -> bool:
    return entity == "blog"


def filter_insert_line(table: str, line: str, state: dict) -> str | None:
    """Return line to write, or None to skip. Updates state['skip_row']."""
    if table in FILTER_ENTITY_FIELD:
        m = ROW_START_RE.match(line)
        if m:
            state["skip_row"] = should_skip_entity_row(m.group(2))
        if state.get("skip_row"):
            if ROW_END_RE.search(line.rstrip("\n")):
                state["skip_row"] = False
            return None
        out = rebrand(line)
        if ROW_END_RE.search(line.rstrip("\n")):
            state["skip_row"] = False
        return out

    if table == "admin_jobs" and FILTER_ADMIN_JOBS:
        if '"entity":"blog"' in line or "'entity\":\"blog'" in line:
            return None
        return rebrand(line)

    return rebrand(line)


def main() -> int:
    if len(sys.argv) != 3:
        print(__doc__, file=sys.stderr)
        return 1
    src, dst = Path(sys.argv[1]), Path(sys.argv[2])
    if not src.is_file():
        print(f"Missing: {src}", file=sys.stderr)
        return 1

    mode = "header"
    current_table: str | None = None
    filter_state: dict = {"skip_row": False}
    stats = {
        "insert_kept": 0,
        "insert_skipped_tables": 0,
        "rows_skipped_blog": 0,
    }

    dst.parent.mkdir(parents=True, exist_ok=True)
    with gzip.open(src, "rt", encoding="utf-8", errors="replace") as fin, gzip.open(
        dst, "wt", encoding="utf-8"
    ) as fout:
        for line in fin:
            if line.startswith("#SXD20|"):
                fout.write(patch_sxd_header(line))
                continue
            if line.startswith("#TA "):
                fout.write(patch_ta_line(line))
                continue
            if line.startswith("#EOH"):
                fout.write(line)
                mode = "body"
                continue
            if line.startswith("#\tTC`"):
                current_table = table_from_marker(line)
                mode = "create"
                filter_state["skip_row"] = False
                fout.write(line)
                continue
            if line.startswith("#\tTD`"):
                current_table = table_from_marker(line)
                filter_state["skip_row"] = False
                if current_table in SKIP_TABLE_INSERT:
                    mode = "skip_table"
                    stats["insert_skipped_tables"] += 1
                elif current_table in FILTER_ENTITY_FIELD or (
                    current_table == "admin_jobs" and FILTER_ADMIN_JOBS
                ):
                    mode = "filter_insert"
                    fout.write(line)
                    stats["insert_kept"] += 1
                else:
                    mode = "insert_pass"
                    fout.write(line)
                    stats["insert_kept"] += 1
                continue
            if line.startswith("CREATE TABLE"):
                mode = "create"
                fout.write(line)
                continue
            if line.startswith("INSERT INTO"):
                current_table = table_from_marker(line) or current_table
                filter_state["skip_row"] = False
                if current_table in SKIP_TABLE_INSERT:
                    mode = "skip_table"
                    stats["insert_skipped_tables"] += 1
                    continue
                if current_table in FILTER_ENTITY_FIELD or (
                    current_table == "admin_jobs" and FILTER_ADMIN_JOBS
                ):
                    mode = "filter_insert"
                    out = filter_insert_line(current_table or "", line, filter_state)
                    if out is not None:
                        fout.write(out)
                    stats["insert_kept"] += 1
                else:
                    mode = "insert_pass"
                    fout.write(rebrand(line))
                    stats["insert_kept"] += 1
                continue

            if mode == "skip_table":
                continue
            if mode == "filter_insert":
                before = filter_state.get("skip_row")
                out = filter_insert_line(current_table or "", line, filter_state)
                if before and not filter_state.get("skip_row"):
                    stats["rows_skipped_blog"] += 1
                if out is not None:
                    fout.write(out)
                continue
            if mode == "insert_pass":
                fout.write(rebrand(line))
                continue
            if mode == "create" or mode == "body" or mode == "header":
                fout.write(line if line.startswith("#") else rebrand(line))

    print(f"Written: {dst}")
    print(f"  Blog tables without data: {', '.join(sorted(SKIP_TABLE_INSERT))}")
    print(f"  INSERT blocks kept: {stats['insert_kept']}")
    print(f"  INSERT blocks skipped (blog tables): {stats['insert_skipped_tables']}")
    print(f"  Filtered blog rows (content_i18n / cluster / jobs): ~{stats['rows_skipped_blog']}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
