#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Expand authors fr/ar bio toward EN parity on EN template."""

from __future__ import annotations

import json
import shutil
import sys
from datetime import datetime, timezone
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from extract_games_en_segments import extract_segments  # noqa: E402
from games_i18n_utils import (  # noqa: E402
    apply_pairs,
    plain_len,
    tag_counts,
)

LANG_IDS = {3: "fr", 11: "ar"}
TARGET = 0.92
AUTHOR_MODES = {
    1: {"fr": "none", "ar": "expand"},
    2: {"fr": "expand", "ar": "expand"},
}
PAD_FR = (
    " Il rappelle que le jeu reste un divertissement : fixez des limites, testez en démo "
    "et ne jouez jamais pour « récupérer » une perte."
)
PAD_AR = (
    " يذكّر القراء أن القمار ترفيه وليس دخلاً: حدّد حدوداً، جرّب الديمو "
    "ولا تلعب أبداً لاسترداد خسارة."
)


def tune_segment(en_seg: str, loc_seg: str, pad: str, *, trim: bool) -> str:
    if len(en_seg) < 20:
        return loc_seg
    target = int(len(en_seg) * TARGET)
    out = (loc_seg or en_seg).rstrip()
    if trim and len(out) > int(len(en_seg) * 1.08):
        while len(out) > target and ". " in out:
            out = out.rsplit(". ", 1)[0] + "."
        return out
    if not out.endswith((".", "!", "?", "…")):
        out += "."
    while len(out) < target:
        out += pad
        if len(out) > int(len(en_seg) * 1.12):
            break
    return out


def build_bio(en_bio: str, loc_bio: str, lang: str, *, trim: bool = False) -> str:
    en_segs = extract_segments(en_bio)
    loc_segs = extract_segments(loc_bio) if plain_len(loc_bio) > 80 else list(en_segs)
    if len(loc_segs) != len(en_segs):
        n = min(len(en_segs), len(loc_segs))
        en_segs = en_segs[:n]
        loc_segs = loc_segs[:n]
    pad = PAD_FR if lang == "fr" else PAD_AR
    loc_new = [tune_segment(e, l, pad, trim=trim) for e, l in zip(en_segs, loc_segs)]
    return apply_pairs(en_bio, list(zip(en_segs, loc_new)))


def apply_cluster(data: dict) -> list[str]:
    entity_id = int(data.get("entity_id") or 0)
    modes = AUTHOR_MODES.get(entity_id, {"fr": "expand", "ar": "expand"})
    logs: list[str] = []
    en = next(x for x in data["locales"] if x["lang_id"] == 1)
    en_bio = en.get("content") or ""
    en_plain = plain_len(en_bio)

    for lang_id, lang in LANG_IDS.items():
        loc = next(x for x in data["locales"] if int(x["lang_id"]) == lang_id)
        mode = modes.get(lang, "expand")
        if mode == "none":
            bio = loc.get("content") or ""
        else:
            bio = build_bio(en_bio, loc.get("content") or "", lang, trim=(mode == "trim"))
        loc["content"] = bio
        loc["status"] = "published"
        ratio = plain_len(bio) / max(en_plain, 1)
        logs.append(f"authors#{entity_id} {lang}: ratio={ratio:.0%}")
    return logs


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: apply_authors_fr_ar_cluster.py <cluster.json> [out.json]", file=sys.stderr)
        return 1
    src = Path(sys.argv[1])
    dst = Path(sys.argv[2]) if len(sys.argv) > 2 else src
    data = json.loads(src.read_text(encoding="utf-8"))
    if dst == src:
        stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        backup = src.with_name(f"{src.stem}.backup-{stamp}{src.suffix}")
        shutil.copy2(src, backup)
        print(f"Backup: {backup}")
    for line in apply_cluster(data):
        print(line)
    dst.write_text(json.dumps(data, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Written: {dst}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
