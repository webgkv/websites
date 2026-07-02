#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build seo-casino_articles-25-full.json from RU blocks + per-lang text maps."""

from __future__ import annotations

import json
import shutil
from datetime import datetime, timezone
from pathlib import Path

from fansport_25_blocks_en import BLOCKS_EN, H1_EN, IMG_ALTS_EN, META_EN

ROOT = Path(__file__).resolve().parents[1]
BLOCKS_RU_PATH = Path(__file__).parent / "fansport_25_blocks_ru.json"
OUT_PATH = ROOT / "site/files/reference/seo-casino_articles-25-full.json"
SRC_JSON = Path("/home/lenovo/Downloads/tmp_report/Fansport Aviator review/seo-casino_articles-25-full.json")
SRC_IMG = Path("/home/lenovo/Downloads/tmp_report/Fansport Aviator review/images")
DST_IMG = ROOT / "site/images/casinos"

# image3.png (old 01) — not used in article; homepage shot is meta only
SKIP_IMG_SRCS = {"images/image3.png"}

IMG_MAP = {
    "images/image7.png": "/images/casinos/fan-sport-aviator-02.png",
    "images/image9.png": "/images/casinos/fan-sport-aviator-03.png",
    "images/image6.png": "/images/casinos/fan-sport-aviator-04.png",
    "images/image2.png": "/images/casinos/fan-sport-aviator-05.png",
    "images/image4.png": "/images/casinos/fan-sport-aviator-06.png",
    "images/image8.png": "/images/casinos/fan-sport-aviator-07.png",
    "images/image5.png": "/images/casinos/fan-sport-aviator-08.png",
    "images/image10.jpg": "/images/casinos/fan-sport-aviator-09.jpg",
    "images/image1.jpg": "/images/casinos/fan-sport-aviator-10.jpg",
}
HERO = "/images/casinos/fan-sport-aviator-meta.png"
LIST_IMG = "fan-sport-aviator-meta.png"
PROMOTE_IDX_H2 = {6}

META_RU = {
    "name": "Fan-Sport Aviator",
    "title": "Fan-Sport Aviator: как играть, бонусы, мобильная | Aviator Log In",
    "description": "Обзор Fan-Sport Aviator: запуск crash-игры с главной, бонусы и акции, демо и игра на деньги, мобильная версия и приложение.",
}
H1_RU = "Обзор Fan-Sport Aviator"

IMG_ALTS_RU = {
    "meta": "Главная Fan-Sport с Aviator",
    "02": "Запуск Aviator на Fan-Sport",
    "03": "Игровое окно Aviator на Fan-Sport",
    "04": "Акции Fan-Sport для казино",
    "05": "Бонусные предложения Fan-Sport",
    "06": "Выбор демо и игры на деньги в Aviator",
    "07": "Демо-режим Aviator на Fan-Sport",
    "08": "Мобильный интерфейс Aviator на Fan-Sport",
    "09": "Aviator на телефоне Fan-Sport",
    "10": "Aviator в приложении Fan-Sport",
}


def esc(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"')


def figure(src: str, alt: str) -> str:
    return (
        '<figure class="my-4"><img class="img-fluid rounded" '
        f'src="{src}" alt="{esc(alt)}" style="max-width:100%;height:auto;"></figure>'
    )


def alt_for_src(web_path: str, alts: dict) -> str:
    stem = Path(web_path).stem.replace("fan-sport-aviator-", "")
    if stem == "meta":
        return alts.get("meta", "Fan-Sport Aviator")
    num = stem.lstrip("0") or stem
    return alts.get(stem, alts.get(num.zfill(2), "Fan-Sport Aviator"))


def build_content(blocks: list, texts: dict, alts: dict, h1: str) -> str:
    out: list[str] = []
    out.append(f"<h1>{h1}</h1>")
    out.append(figure(HERO, alts.get("meta", "Fan-Sport Aviator")))

    for i, b in enumerate(blocks):
        t = b["type"]
        if t == "img":
            if b.get("src") in SKIP_IMG_SRCS:
                continue
            web = IMG_MAP.get(b["src"], "")
            if web:
                out.append(figure(web, alt_for_src(web, alts)))
            continue
        text = texts.get(i)
        if text is None:
            text = b.get("text", "")
        if not str(text).strip():
            continue
        if t == "h2":
            out.append(f"<h2>{text}</h2>")
        elif t == "h3":
            out.append(f"<h3>{text}</h3>")
        elif t == "p":
            if i in PROMOTE_IDX_H2:
                out.append(f"<h2>{text}</h2>")
            else:
                out.append(f"<p>{text}</p>")
    return "".join(out)


def ru_texts_from_blocks(blocks: list) -> dict:
    return {i: b.get("text", "") for i, b in enumerate(blocks) if b["type"] in ("h2", "h3", "p")}


def crop_meta_white_bars(path: Path) -> None:
    """Remove white/grey letterbox strips from funsport_hero export."""
    from PIL import Image
    import numpy as np

    im = Image.open(path).convert("RGB")
    arr = np.array(im)
    h = arr.shape[0]

    def row_is_bar(y: int) -> bool:
        row = arr[y]
        per_px = row.mean(axis=1)
        # Pure white bars (Google Docs export) or full-width light gutter rows
        if (row.min(axis=1) > 235).mean() > 0.85:
            return True
        if (per_px > 180).mean() > 0.9:
            return True
        return row.mean() > 120 and (per_px > 100).mean() > 0.95

    top = 0
    while top < h and row_is_bar(top):
        top += 1
    bot = h - 1
    while bot > top and row_is_bar(bot):
        bot -= 1
    if bot <= top:
        return
    im.crop((0, top, im.width, bot + 1)).save(path, optimize=True)


def copy_images() -> None:
    DST_IMG.mkdir(parents=True, exist_ok=True)
    meta_dst = DST_IMG / LIST_IMG
    shutil.copy2(SRC_IMG / "funsport_hero.png", meta_dst)
    crop_meta_white_bars(meta_dst)
    for src_rel, web in IMG_MAP.items():
        src_name = src_rel.split("/", 1)[1]
        dst_name = Path(web).name
        src = SRC_IMG / src_name
        if not src.exists():
            raise FileNotFoundError(src)
        shutil.copy2(src, DST_IMG / dst_name)


def load_locale_maps(blocks: list) -> dict:
    trans_dir = Path(__file__).parent / "fansport_25_translations"
    maps = {
        "en": BLOCKS_EN,
        "ru": ru_texts_from_blocks(blocks),
    }
    if trans_dir.is_dir():
        for p in sorted(trans_dir.glob("*.json")):
            if p.name.startswith(("meta_", "alts_", "_")):
                continue
            lang = p.stem
            data = json.loads(p.read_text(encoding="utf-8"))
            maps[lang] = {int(k): v for k, v in data.items()}
    return maps


def main() -> None:
    copy_images()
    blocks = json.loads(BLOCKS_RU_PATH.read_text(encoding="utf-8"))
    cluster = json.loads(SRC_JSON.read_text(encoding="utf-8"))
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S+00:00")

    locale_maps = load_locale_maps(blocks)
    meta_by_lang = {"en": META_EN, "ru": META_RU}
    h1_by_lang = {"en": H1_EN, "ru": H1_RU}
    alts_by_lang = {"en": IMG_ALTS_EN, "ru": IMG_ALTS_RU}

    trans_dir = Path(__file__).parent / "fansport_25_translations"
    if trans_dir.is_dir():
        for p in trans_dir.glob("meta_*.json"):
            lang = p.stem.replace("meta_", "")
            meta = json.loads(p.read_text(encoding="utf-8"))
            meta_by_lang[lang] = meta
            if meta.get("h1"):
                h1_by_lang[lang] = meta["h1"]
        for p in trans_dir.glob("alts_*.json"):
            lang = p.stem.replace("alts_", "")
            alts_by_lang[lang] = json.loads(p.read_text(encoding="utf-8"))

    for loc in cluster["locales"]:
        lang = loc["lang_url"]
        texts = locale_maps.get(lang)
        if not texts:
            print(f"SKIP missing translations: {lang}")
            continue
        meta = meta_by_lang.get(lang, META_EN)
        h1 = meta.get("h1") or h1_by_lang.get(lang, H1_EN)
        alts = alts_by_lang.get(lang, IMG_ALTS_EN)

        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = build_content(blocks, texts, alts, h1)
        loc["status"] = "published"
        loc["source"] = "main" if lang == "en" else "content_i18n"

    cluster["row"] = {"img": LIST_IMG, "name_2": META_EN["description"]}

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUT_PATH.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    SRC_JSON.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    print(f"Wrote {OUT_PATH} ({OUT_PATH.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
