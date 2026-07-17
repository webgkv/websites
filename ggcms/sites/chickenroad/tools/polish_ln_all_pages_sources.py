#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply ln_quality_replacements to pages/home/hub translation source files."""

from __future__ import annotations

import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from ln_quality_replacements import LN_REPLACEMENTS, polish_ln  # noqa: E402

TARGETS = (
    TOOLS / "pages_content_sw_ln.py",
    TOOLS / "home_cluster_sw_ln_sections.py",
    TOOLS / "pages_hub_meta_sw_ln.py",
)


def polish_file(path: Path) -> int:
    text = path.read_text(encoding="utf-8")
    new = polish_ln(text)
    if new == text:
        return 0
    path.write_text(new, encoding="utf-8")
    # count approximate replacements
    return sum(1 for old, new in LN_REPLACEMENTS if old in text and old != new)


def main() -> int:
    total = 0
    for path in TARGETS:
        n = polish_file(path)
        print(f"{path.name}: polished ({n} rules matched in file)")
        total += n
    print(f"Done ({len(TARGETS)} files)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
