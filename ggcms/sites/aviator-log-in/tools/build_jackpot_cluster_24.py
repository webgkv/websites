#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build seo-casino_articles-24-full.json from blocks + per-lang text maps."""

from __future__ import annotations

import json
import re
from pathlib import Path

from jackpot_24_blocks_en import BLOCKS_EN, IMG_ALTS_EN, META_EN, TABLE_EN

ROOT = Path(__file__).resolve().parents[1]
BLOCKS_RU_PATH = Path(__file__).parent / "jackpot_24_blocks_ru.json"
OUT_PATH = ROOT / "site/files/reference/seo-casino_articles-24-full.json"
SRC_JSON = Path("/home/lenovo/Downloads/tmp_report/Обзор Jack-Pot Casino/seo-casino_articles-24-full.json")

IMG_MAP = {
    "images/image1.png": "/images/casinos/jack-pot-aviator-1.png",
    "images/image2.png": "/images/casinos/jack-pot-aviator-2.png",
}
HERO = "/images/casinos/jack-pot-aviator-meta.png"
AVIATOR_GAME_IMG = "/images/casinos/jack-pot-aviator-3.png"
LIST_IMG = "jack-pot-aviator-meta.png"

# Inject game screenshot after this block index (first Aviator paragraph).
INJECT_IMG_AFTER_BLOCK = {31: "3"}

# Standalone p blocks that are section titles in the source export
PROMOTE_IDX_H2 = {22}
PROMOTE_IDX_H3 = {54, 56, 60, 69}

TABLE_RU = {
    "header": ("Плюсы", "Минусы"),
    "rows": [
        ("Широкая линейка игровых провайдеров", "Возможны мелкие баги локализации"),
        ("Игры удобно разбиты по категориям", "Брендинг пока выглядит не очень сильным"),
        ("Есть казино, live casino, sport и eSport в одном аккаунте", "Нет ярко выраженного уникального предложения"),
        ("Гибкая платежная экосистема", "Местами есть ощущение White Label-продукта"),
        ("Простой и понятный UX/UI", "Репутация и доверие еще только формируются"),
        ("Фронтенд постоянно развивается", "Проекту нужно больше времени на рынке"),
        ("Поддержка отвечает достаточно быстро", "Не все разделы могут быть одинаково хорошо адаптированы под разные регионы"),
        ("Выводы, по заявлению площадки, обрабатываются быстро", "Перед депозитом всё равно нужно внимательно читать условия"),
    ],
}

IMG_ALTS_RU = {
    "meta": "Логотип Jack-Pot Casino",
    "1": "Экран выбора приветственного бонуса Jack-Pot",
    "2": "Шаг регистрации Jack-Pot",
    "3": "Скриншот игры Aviator на Jack-Pot",
}

META_RU = {
    "name": "Jack-Pot Aviator",
    "title": "Обзор Jack-Pot Aviator: игры, бонусы и платежи | Aviator Log In",
    "description": "Обзор Jack-Pot Aviator: регистрация, приветственные бонусы, казино и спортбук, crash-игры включая Aviator, платежи и лицензия.",
}


def esc(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"')


def figure(src: str, alt: str) -> str:
    return (
        '<figure class="my-4"><img class="img-fluid rounded" '
        f'src="{src}" alt="{esc(alt)}" style="max-width:100%;height:auto;"></figure>'
    )


def render_table(table: dict) -> str:
    h0, h1 = table["header"]
    parts = [
        '<div class="table-responsive"><table class="table table-bordered"><thead><tr>',
        f"<th>{h0}</th><th>{h1}</th></tr></thead><tbody>",
    ]
    for a, b in table["rows"]:
        parts.append(f"<tr><td>{a}</td><td>{b}</td></tr>")
    parts.append("</tbody></table></div>")
    return "".join(parts)


def block_index_for_img(src: str) -> str:
    if "image1" in src:
        return "1"
    if "image2" in src:
        return "2"
    return "1"


def build_content(blocks: list, texts: dict, table: dict, alts: dict, lang: str) -> str:
    out: list[str] = []
    img_counter = {"1": 0, "2": 0}
    for i, b in enumerate(blocks):
        t = b["type"]
        if t == "h2" and not (b.get("text") or "").strip():
            continue
        if t == "img":
            key = block_index_for_img(b["src"])
            img_counter[key] += 1
            src = IMG_MAP[b["src"]]
            alt = alts.get(key, "Jack-Pot")
            out.append(figure(src, alt))
            continue
        if t == "table":
            out.append(render_table(table))
            continue
        text = texts.get(i)
        if text is None:
            text = b.get("text", "")
        if not str(text).strip():
            continue
        if t == "h1":
            out.append(f"<h1>{text}</h1>")
            out.append(figure(HERO, alts.get("meta", "Jack-Pot")))
        elif t == "h2":
            out.append(f"<h2>{text}</h2>")
        elif t == "h3":
            out.append(f"<h3>{text}</h3>")
        elif t == "p":
            if i in PROMOTE_IDX_H2:
                out.append(f"<h2>{text}</h2>")
            elif i in PROMOTE_IDX_H3:
                out.append(f"<h3>{text}</h3>")
            else:
                out.append(f"<p>{text}</p>")
        if i in INJECT_IMG_AFTER_BLOCK:
            key = INJECT_IMG_AFTER_BLOCK[i]
            out.append(figure(AVIATOR_GAME_IMG, alts.get(key, "Jack-Pot Aviator game screen")))
    return "".join(out)


def ru_texts_from_blocks(blocks: list) -> dict:
    texts = {}
    for i, b in enumerate(blocks):
        if b["type"] in ("h1", "h2", "h3", "p"):
            texts[i] = b.get("text", "")
    return texts


def load_locale_maps() -> dict:
    """Load optional tools/jackpot_24_translations/<lang>.json files."""
    trans_dir = Path(__file__).parent / "jackpot_24_translations"
    maps = {"en": BLOCKS_EN, "ru": ru_texts_from_blocks(json.loads(BLOCKS_RU_PATH.read_text(encoding="utf-8")))}
    if trans_dir.is_dir():
        for p in trans_dir.glob("*.json"):
            if p.name.startswith(("meta_", "table_", "alts_", "_")):
                continue
            lang = p.stem
            data = json.loads(p.read_text(encoding="utf-8"))
            maps[lang] = {int(k): v for k, v in data.items()}
    return maps


def main() -> None:
    blocks = json.loads(BLOCKS_RU_PATH.read_text(encoding="utf-8"))
    cluster = json.loads(SRC_JSON.read_text(encoding="utf-8"))
    locale_maps = load_locale_maps()

    meta_by_lang = {
        "en": META_EN,
        "ru": META_RU,
    }
    table_by_lang = {"en": TABLE_EN, "ru": TABLE_RU}
    alts_by_lang = {"en": IMG_ALTS_EN, "ru": IMG_ALTS_RU}

    trans_meta = Path(__file__).parent / "jackpot_24_translations"
    if trans_meta.is_dir():
        for p in trans_meta.glob("meta_*.json"):
            lang = p.stem.replace("meta_", "")
            meta_by_lang[lang] = json.loads(p.read_text(encoding="utf-8"))

    for loc in cluster["locales"]:
        lang = loc["lang_url"]
        texts = locale_maps.get(lang)
        if not texts:
            print(f"SKIP missing translations: {lang}")
            continue
        meta = meta_by_lang.get(lang, META_EN)
        table = table_by_lang.get(lang, TABLE_EN)
        alts = alts_by_lang.get(lang, IMG_ALTS_EN)
        if lang != "en" and lang != "ru" and (trans_meta / f"table_{lang}.json").exists():
            table = json.loads((trans_meta / f"table_{lang}.json").read_text(encoding="utf-8"))
        if lang != "en" and lang != "ru" and (trans_meta / f"alts_{lang}.json").exists():
            alts = json.loads((trans_meta / f"alts_{lang}.json").read_text(encoding="utf-8"))

        loc["name"] = meta["name"]
        loc["title"] = meta["title"]
        loc["description"] = meta["description"]
        loc["content"] = build_content(blocks, texts, table, alts, lang)
        loc["status"] = "published"
        loc["source"] = "main" if lang == "en" else "content_i18n"

    cluster["row"] = {
        "img": LIST_IMG,
        "name_2": META_EN["description"],
    }

    OUT_PATH.parent.mkdir(parents=True, exist_ok=True)
    OUT_PATH.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    # Also update Downloads copy
    dl = Path("/home/lenovo/Downloads/tmp_report/Обзор Jack-Pot Casino/seo-casino_articles-24-full.json")
    dl.write_text(json.dumps(cluster, ensure_ascii=False, indent=4), encoding="utf-8")
    print(f"Wrote {OUT_PATH} ({OUT_PATH.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
