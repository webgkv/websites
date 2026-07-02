#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Rebuild cluster #24 content with all images from 02/JackPotCasino.html placement."""

from __future__ import annotations

import json
import re
import shutil
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC_DIR = Path("/home/lenovo/Downloads/tmp_report/02/Обзор Jack-Pot Casino")
SRC_IMG = SRC_DIR / "images"
HTML_PATH = SRC_DIR / "JackPotCasino.html"
CLUSTER_IN = Path("/home/lenovo/Downloads/tmp_report/02/seo-casino_articles-24-full.json")
DST_IMG = ROOT / "site/images/casinos"
OUT_REPO = ROOT / "site/files/reference/seo-casino_articles-24-full.json"
OUT_DL = CLUSTER_IN

IMG_ORDER = [
    ("image1.png", "jack-pot-aviator-01.png"),
    ("image9.png", "jack-pot-aviator-02.png"),
    ("image12.png", "jack-pot-aviator-03.png"),
    ("image11.png", "jack-pot-aviator-04.png"),
    ("image4.png", "jack-pot-aviator-05.png"),
    ("image3.png", "jack-pot-aviator-06.png"),
    ("image2.png", "jack-pot-aviator-07.png"),
    ("image8.png", "jack-pot-aviator-08.png"),
    ("image5.png", "jack-pot-aviator-09.png"),
    ("image6.png", "jack-pot-aviator-10.png"),
    ("image7.jpg", "jack-pot-aviator-11.jpg"),
    ("image10.png", "jack-pot-aviator-12.png"),
]
SRC_TO_WEB = {f"images/{a}": f"/images/casinos/{b}" for a, b in IMG_ORDER}
HERO = "/images/casinos/jack-pot-aviator-meta.png"
LIST_IMG = "jack-pot-aviator-meta.png"

PROMOTE_H2_TEXTS = {"Игры на сайте", "Games on the site"}

PROMOTE_H3_TEXTS = {
    "Личные данные и верификация",
    "Ответственная игра",
    "Вывод по безопасности",
    "Итог",
    "Personal data and verification",
    "Responsible gaming",
    "Safety summary",
    "Conclusion",
}

ALT_EN = {
    "meta": "Jack-Pot Casino logo",
    "01": "Jack-Pot Casino overview",
    "02": "Jack-Pot Casino platform screen",
    "03": "Jack-Pot registration — step one",
    "04": "Jack-Pot welcome bonus selection",
    "05": "Jack-Pot bonuses and promotions",
    "06": "Jack-Pot casino games lobby",
    "07": "Jack-Pot slots catalogue",
    "08": "Jack-Pot live casino and sportsbook",
    "09": "Jack-Pot Aviator game screen",
    "10": "Jack-Pot payment methods",
    "11": "Jack-Pot mobile version",
    "12": "Jack-Pot licence information",
}


class BlockParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.blocks: list[dict] = []
        self._cur: dict | None = None
        self._in_table = False
        self._row: list = []
        self._table: list = []
        self._cell: list[str] = []
        self._cell_tag = ""

    def handle_starttag(self, tag: str, attrs) -> None:
        a = dict(attrs)
        if tag in ("h1", "h2", "h3"):
            self._cur = {"type": tag, "parts": []}
        elif tag == "p" and not self._in_table:
            self._cur = {"type": "p", "parts": []}
        elif tag == "img":
            self.blocks.append({"type": "img", "src": a.get("src", "")})
            self._cur = None
        elif tag == "table":
            self._in_table = True
            self._table = []
        elif tag == "tr" and self._in_table:
            self._row = []
        elif tag in ("th", "td") and self._in_table:
            self._cell_tag = tag
            self._cell = []

    def handle_endtag(self, tag: str) -> None:
        if tag in ("h1", "h2", "h3", "p") and self._cur:
            text = "".join(self._cur["parts"]).strip()
            if text or tag in ("h1", "h2", "h3"):
                self.blocks.append({"type": self._cur["type"], "text": text})
            self._cur = None
        elif tag in ("th", "td") and self._in_table:
            self._row.append((self._cell_tag, "".join(self._cell).strip()))
        elif tag == "tr" and self._in_table and self._row:
            self._table.append(self._row)
        elif tag == "table" and self._in_table:
            self.blocks.append({"type": "table", "rows": self._table})
            self._in_table = False

    def handle_data(self, data: str) -> None:
        if self._in_table and self._cell_tag:
            self._cell.append(data)
        elif self._cur is not None:
            self._cur["parts"].append(data)


def parse_blocks(html: str) -> list[dict]:
    i = html.lower().find("<h1")
    p = BlockParser()
    p.feed(html[i:] if i >= 0 else html)
    return p.blocks


def esc(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"')


def figure(src: str, alt: str) -> str:
    return (
        '<figure class="my-4"><img class="img-fluid rounded" '
        f'src="{src}" alt="{esc(alt)}" style="max-width:100%;height:auto;"></figure>'
    )


def render_table(rows: list) -> str:
    if not rows:
        return ""
    h0, h1 = rows[0][0][1], rows[0][1][1]
    out = [
        '<div class="table-responsive"><table class="table table-bordered"><thead><tr>',
        f"<th>{h0}</th><th>{h1}</th></tr></thead><tbody>",
    ]
    for row in rows[1:]:
        out.append(f"<tr><td>{row[0][1]}</td><td>{row[1][1]}</td></tr>")
    out.append("</tbody></table></div>")
    return "".join(out)


def alt_for_web_path(web_path: str, lang: str) -> str:
    stem = Path(web_path).stem.replace("jack-pot-aviator-", "")
    if lang == "en":
        return ALT_EN.get(stem, "Jack-Pot Casino")
    # Keep EN alt keys for other langs unless we have alts file — use generic Jack-Pot
    return ALT_EN.get(stem, "Jack-Pot Casino")


def extract_locale_segments(blocks: list[dict]) -> list[dict]:
    """Non-empty text/table segments in document order (skip empty h2, skip img)."""
    segs: list[dict] = []
    for b in blocks:
        if b["type"] == "img":
            continue
        if b["type"] == "h2" and not (b.get("text") or "").strip():
            continue
        if b["type"] == "table":
            segs.append({"type": "table", "rows": b["rows"]})
            continue
        text = (b.get("text") or "").strip()
        if b["type"] == "p" and not text:
            continue
        segs.append({"type": b["type"], "text": text})
    return segs


def build_html(struct: list[dict], segments: list[dict], lang: str) -> str:
    seg_i = 0
    out: list[str] = []

    def next_seg() -> dict | None:
        nonlocal seg_i
        if seg_i >= len(segments):
            return None
        s = segments[seg_i]
        seg_i += 1
        return s

    for b in struct:
        t = b["type"]
        if t == "img":
            web = SRC_TO_WEB.get(b["src"], "")
            if web:
                out.append(figure(web, alt_for_web_path(web, lang)))
            continue
        if t == "table":
            seg = next_seg()
            if seg and seg["type"] == "table":
                out.append(render_table(seg["rows"]))
            elif b.get("rows"):
                out.append(render_table(b["rows"]))
            continue

        seg = next_seg()
        if not seg:
            continue
        text = seg.get("text", "")
        if t == "h1":
            out.append(f"<h1>{text}</h1>")
            out.append(figure(HERO, alt_for_web_path(HERO, lang) if lang == "en" else ALT_EN["meta"]))
        elif t == "h2":
            out.append(f"<h2>{text}</h2>")
        elif t == "h3":
            out.append(f"<h3>{text}</h3>")
        elif t == "p":
            if text in PROMOTE_H2_TEXTS:
                out.append(f"<h2>{text}</h2>")
            elif text in PROMOTE_H3_TEXTS:
                out.append(f"<h3>{text}</h3>")
            else:
                out.append(f"<p>{text}</p>")

    return "".join(out)


def copy_images() -> None:
    DST_IMG.mkdir(parents=True, exist_ok=True)
    for src_name, dst_name in IMG_ORDER:
        src = SRC_IMG / src_name
        dst = DST_IMG / dst_name
        if not src.exists():
            raise FileNotFoundError(src)
        shutil.copy2(src, dst)
        print(f"  {src_name} -> {dst_name}")


def main() -> None:
    print("Copy images...")
    copy_images()

    struct = parse_blocks(HTML_PATH.read_text(encoding="utf-8"))
    # drop empty h2 from struct for alignment
    struct_clean = [b for b in struct if not (b["type"] == "h2" and not (b.get("text") or "").strip())]

    cluster = json.loads(CLUSTER_IN.read_text(encoding="utf-8"))
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S+00:00")

    ref_seg_count = len(extract_locale_segments(struct_clean))
    print(f"Structure blocks: {len(struct_clean)}, text segments: {ref_seg_count}")

    for loc in cluster["locales"]:
        lang = loc["lang_url"]
        loc_blocks = parse_blocks(loc.get("content", ""))
        segments = extract_locale_segments(loc_blocks)
        if len(segments) != ref_seg_count:
            print(f"  WARN {lang}: segment count {len(segments)} != {ref_seg_count}")
        loc["content"] = build_html(struct_clean, segments, lang)
        imgs = len(re.findall(r"/images/casinos/jack-pot-aviator-", loc["content"]))
        print(f"  {lang}: {imgs} images in content")

    cluster["row"] = {
        "img": LIST_IMG,
        "name_2": cluster["locales"][0].get("description", ""),
    }

    OUT_REPO.parent.mkdir(parents=True, exist_ok=True)
    OUT_REPO.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    OUT_DL.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    print(f"Wrote {OUT_REPO} ({OUT_REPO.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
