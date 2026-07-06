#!/usr/bin/env python3
"""Remove duplicate section-media figures (same src) from games cluster JSON."""
from __future__ import annotations

import json
import re
import sys
from collections import Counter
from pathlib import Path

INPUT_DIR = Path("/Users/gk/Downloads/05/fixed")
FIGURE_RE = re.compile(r'<figure class="section-media__figure">.*?</figure>', re.S)
FIGURE_SRC_RE = re.compile(r'src="(/files/media/[^"]+)"')


def dedupe_duplicate_figures(html: str) -> str:
    if not html:
        return html or ""
    seen_src: set[str] = set()

    def repl(m: re.Match[str]) -> str:
        fig = m.group(0)
        sm = FIGURE_SRC_RE.search(fig)
        if not sm:
            return fig
        src = sm.group(1)
        if src in seen_src:
            return ""
        seen_src.add(src)
        return fig

    return FIGURE_RE.sub(repl, html)


def main() -> int:
    paths = sorted(INPUT_DIR.glob("seo-games-*-full.json"))
    if not paths:
        print(f"No JSON in {INPUT_DIR}", file=sys.stderr)
        return 1

    for path in paths:
        data = json.loads(path.read_text(encoding="utf-8"))
        entity_id = int(data.get("entity_id") or 0)
        before = after = 0
        for loc in data.get("locales") or []:
            if not isinstance(loc, dict):
                continue
            content = loc.get("content") or ""
            before += len(FIGURE_RE.findall(content))
            loc["content"] = dedupe_duplicate_figures(content)
            after += len(FIGURE_RE.findall(loc["content"]))

        path.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
        c = data["locales"][0]["content"]
        dup = [k for k, v in Counter(FIGURE_SRC_RE.findall(c)).items() if v > 1]
        print(f"games#{entity_id}: figures {before // max(len(data['locales']), 1)} -> {after // max(len(data['locales']), 1)} EN dupes={dup or 'none'}")

    return 0


if __name__ == "__main__":
    sys.exit(main())
