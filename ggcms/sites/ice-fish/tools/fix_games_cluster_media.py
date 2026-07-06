#!/usr/bin/env python3
"""Rewrite games cluster JSON media paths to files that exist on prod disk."""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

SERVER_MEDIA_LIST = Path("/tmp/server_media_list.txt")
INPUT_DIR = Path("/Users/gk/Downloads/05")
OUTPUT_DIR = INPUT_DIR / "fixed"

# Missing originals on prod — map to existing webp assets (prefer 2026/06).
MANUAL_MAP: dict[str, str] = {
    "/files/media/2026/06/ice-fish-2-medium-level-1024x471.png": "/files/media/2026/06/crashed-ice-fish-2-1024x574.webp",
    "/files/media/2026/06/chicken_road2_mobile.png": "/files/media/2026/06/chicken-mobile.webp",
    "/files/media/2026/06/ice-fish-2-1024x573.png": "/files/media/2026/06/ice-fish-2.webp",
    "/files/media/2026/06/ice-fish-2-bonus-game-1024x550.jpeg": "/files/media/2026/06/ice-fish-2-bonus.webp",
    "/files/media/2026/06/ice-fish-2-bonus-game-casino-1024x542.jpeg": "/files/media/2026/06/ice-fish-2-bonus.webp",
    "/files/media/2026/06/ice-fish-2-buy-bonus-1024x538.jpeg": "/files/media/2026/06/ice-fish-2-bonus.webp",
    "/files/media/2026/06/screenshot-2026-06-04-131633.png": "/files/media/2026/06/screenshot-2026-06-04-161507.webp",
    "/files/media/2026/06/screenshot-2026-06-04-132107.png": "/files/media/2026/06/screenshot-2026-06-04-161507.webp",
    "/files/media/2026/06/ice-fish-vegas-demo-gameplay-1024x569.gif": "/files/media/2026/06/ice-fish-vegas.webp",
    "/files/media/2026/06/chicken_road_vegas_bann.png": "/files/media/2026/06/ice-fish-vegas.webp",
    "/files/media/2026/06/failed-ice-fish-vegas-1024x563.png": "/files/media/2026/06/ice-fish-fail-round-1024x425.webp",
    "/files/media/2026/06/vegas_mobile.jpg": "/files/media/2026/06/chicken-mobile.webp",
    "/files/media/2026/06/chicken_gold.jpg": "/files/media/2026/06/ice-fish-gold.webp",
    "/files/media/2026/06/chicken_road_gold_mobile.jpg": "/files/media/2026/06/chicken-mobile.webp",
    "/files/media/2026/06/demo-ice-fish-gold-1024x588.gif": "/files/media/2026/06/gameplay-ice-fish-gold-2048x1126.webp",
    "/files/media/2026/06/ice-fish-race-games-1024x526.jpeg": "/files/media/2026/06/game-ice-fish-race.webp",
    "/files/media/2026/06/ice-fish-race-inout-games-1024x531.jpeg": "/files/media/2026/06/game-ice-fish-race.webp",
    "/files/media/2026/06/chicken_race_bann.jpg": "/files/media/2026/06/game-ice-fish-race.webp",
    "/files/media/2026/06/screenshot_chicken-_road_race.png": "/files/media/2026/06/game-ice-fish-race.webp",
    "/files/media/2026/06/chicken-royal-demo-1024x627.png": "/files/media/2026/06/chicken-royal.webp",
    "/files/media/2026/06/chicken-royal-money-game-vid.gif": "/files/media/2026/06/chicken-royal.webp",
    "/files/media/2026/06/screenshot-2026-06-02-161728.png": "/files/media/2026/06/screenshot-2026-06-02-143255.webp",
    "/files/media/2026/06/chicken-coin-game-1024x540.jpeg": "/files/media/2026/06/chicken-coin.webp",
    "/files/media/2026/06/ice-fish-coin-1024x541.jpeg": "/files/media/2026/06/chicken-coin.webp",
    "/files/media/2026/06/coin-chicken-1024x545.jpeg": "/files/media/2026/06/chicken-coin.webp",
    "/files/media/2026/06/screenshot-2026-06-02-141135.png": "/files/media/2026/06/screenshot-2026-06-02-142223.webp",
    "/files/media/2026/06/screenshot-2026-06-02-142125.png": "/files/media/2026/06/screenshot-2026-06-02-142223.webp",
    "/files/media/2026/06/screenshot-2026-06-02-142237.png": "/files/media/2026/06/screenshot-2026-06-02-143255.webp",
    "/files/media/2026/06/screenshot-2026-06-02-143434.png": "/files/media/2026/06/screenshot-2026-06-02-143255.webp",
    "/files/media/2026/06/demo-chicken-banana.gif": "/files/media/2026/06/chicken-banana.webp",
    "/files/media/2026/06/screenshot-2026-06-02-144843.png": "/files/media/2026/06/chicken-banana.webp",
    "/files/media/2026/06/screenshot-2026-06-02-152630.png": "/files/media/2026/06/chicken-banana.webp",
    "/files/media/2026/06/screenshot-2026-06-02-152605.png": "/files/media/2026/06/chicken-banana.webp",
    "/files/media/2026/06/screenshot2.jpg": "/files/media/2026/06/chicken-banana.webp",
}

ENTITY_COVER: dict[int, str] = {
    1: "/files/media/2026/06/ice-fish.webp",
    2: "/files/media/2026/06/ice-fish-2.webp",
    3: "/files/media/2026/06/ice-fish-2-bonus.webp",
    4: "/files/media/2026/06/ice-fish-vegas.webp",
    5: "/files/media/2026/06/ice-fish-gold.webp",
    6: "/files/media/2026/06/game-ice-fish-race.webp",
    7: "/files/media/2026/06/inout-ice-fish-win-1024x576.webp",
    8: "/files/media/2026/06/chicken-royal.webp",
    9: "/files/media/2026/06/chicken-coin.webp",
    10: "/files/media/2026/06/chicken-banana.webp",
    11: "/files/media/2026/06/chicken-shoot.webp",
    12: "/files/media/2026/06/chicken-vs-zombies-inout.webp",
}

MEDIA_RE = re.compile(r"/files/media/[^\"'\s>]+")


def load_server_media() -> set[str]:
    if not SERVER_MEDIA_LIST.is_file():
        raise SystemExit(f"Missing {SERVER_MEDIA_LIST}; run server inventory first")
    return {line.strip().lstrip("/") for line in SERVER_MEDIA_LIST.read_text().splitlines() if line.strip()}


def resolve_path(ref: str, server: set[str], entity_id: int, by_stem: dict[str, list[str]]) -> str:
    ref = ref if ref.startswith("/") else "/" + ref
    key = ref.lstrip("/")
    if key in server:
        return ref

    if ref in MANUAL_MAP:
        mapped = MANUAL_MAP[ref]
        if mapped.lstrip("/") in server:
            return mapped

    p = Path(ref)
    webp = str(p.with_suffix(".webp"))
    if webp.lstrip("/") in server:
        return webp

    for cand in by_stem.get(p.stem.lower(), []):
        if cand.endswith(".webp"):
            return "/" + cand

    cover = ENTITY_COVER.get(entity_id)
    if cover and cover.lstrip("/") in server:
        return cover

    return ref


FIGURE_RE = re.compile(r'<figure class="section-media__figure">.*?</figure>', re.S)
FIGURE_SRC_RE = re.compile(r'src="(/files/media/[^"]+)"')


def dedupe_duplicate_figures(html: str) -> str:
    """Keep the first figure per unique /files/media src; drop later repeats."""
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


def fix_content(content: str, server: set[str], entity_id: int, by_stem: dict[str, list[str]]) -> tuple[str, list[tuple[str, str]]]:
    changes: list[tuple[str, str]] = []

    def repl(m: re.Match[str]) -> str:
        old = m.group(0)
        new = resolve_path(old, server, entity_id, by_stem)
        if new != old:
            changes.append((old, new))
        return new

    html = MEDIA_RE.sub(repl, content or "")
    html = dedupe_duplicate_figures(html)
    return html, changes


def main() -> int:
    server = load_server_media()
    by_stem: dict[str, list[str]] = {}
    for p in server:
        by_stem.setdefault(Path(p).stem.lower(), []).append(p)

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    all_unresolved: set[str] = set()
    summary: list[str] = []

    for src in sorted(INPUT_DIR.glob("seo-games-*-full.json")):
        data = json.loads(src.read_text(encoding="utf-8"))
        entity_id = int(data.get("entity_id") or 0)
        file_changes: dict[str, str] = {}

        for loc in data.get("locales") or []:
            if not isinstance(loc, dict):
                continue
            content = loc.get("content") or ""
            fixed, changes = fix_content(content, server, entity_id, by_stem)
            loc["content"] = fixed
            for old, new in changes:
                file_changes[old] = new
            for m in MEDIA_RE.findall(fixed):
                if m.lstrip("/") not in server:
                    all_unresolved.add(f"games#{entity_id}: {m}")

        out = OUTPUT_DIR / src.name
        out.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
        summary.append(f"{src.name}: {len(file_changes)} path rewrites")

    print("\n".join(summary))
    if all_unresolved:
        print("\nUNRESOLVED (still missing on disk):")
        for u in sorted(all_unresolved):
            print(" ", u)
        return 1
    print("\nAll media paths resolve on server.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
