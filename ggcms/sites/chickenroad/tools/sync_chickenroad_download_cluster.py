#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Build pages#5 download cluster from live EN HTML + per-locale body patches.
Keeps fr/de/es from live export unchanged.
"""

from __future__ import annotations

import argparse
import copy
import importlib
import json
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TOOLS = ROOT / "tools"
sys.path.insert(0, str(TOOLS))

from chickenroad_download_v2_builder import build_download_content  # noqa: E402
from chickenroad_download_v2_en import _ol1, get_en_body  # noqa: E402
from chickenroad_download_v2_locales_all import _loc, get_ru_body  # noqa: E402

LIVE = ROOT / "tmp/jason/seo-pages-5-live-export.json"
TABLES_JSON = ROOT / "tmp/jason/download-locale-tables.json"
PATCH_DIR = TOOLS / "download_locale_patches"
OUT = ROOT / "site/files/reference/seo-pages-5-full.json"
OUT_TMP = ROOT / "tmp/jason/seo-pages-5-full.json"

KEEP_LANG_IDS = {3, 4, 6}  # fr, de, es


def body_for(code: str) -> dict:
    if code == "en":
        b = get_en_body()
        b["download_ol"] = [_ol1] + list(b["download_ol"][1:])
        return b
    if code == "ru":
        return get_ru_body()
    mod = importlib.import_module(f"download_locale_patches.{code}")
    patch = getattr(mod, "PATCH")
    b = _loc(**patch)
    if "download_ol" in patch:
        b["download_ol"] = [_ol1] + patch["download_ol"]
    return b


def content_for(code: str, en_html: str) -> str:
    if code == "en":
        return en_html
    return build_download_content(body_for(code), code)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--import", dest="do_import", action="store_true")
    args = parser.parse_args()

    cluster = json.loads(LIVE.read_text(encoding="utf-8"))
    en_html = next(x["content"] for x in cluster["locales"] if x["lang_id"] == 1)
    new_locales = []

    for loc in cluster["locales"]:
        lid = loc["lang_id"]
        code = loc.get("lang_url") or "en"
        if lid in KEEP_LANG_IDS:
            new_locales.append(copy.deepcopy(loc))
            print(f"KEEP {code}")
            continue
        try:
            content = content_for(code, en_html)
        except ModuleNotFoundError:
            print(f"WARN no patch for {code}, keeping live", file=sys.stderr)
            new_locales.append(copy.deepcopy(loc))
            continue
        updated = copy.deepcopy(loc)
        updated["content"] = content
        updated["source"] = "content_i18n"
        new_locales.append(updated)
        print(
            f"BUILD {code} len={len(content)} h2={content.count('<h2')} "
            f"noads={content.count('noads')} links={content.count(f'/{code}/')}"
        )

    cluster["locales"] = new_locales
    cluster["exported_at"] = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    payload = json.dumps(cluster, ensure_ascii=False, indent=4) + "\n"
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(payload, encoding="utf-8")
    OUT_TMP.write_text(payload, encoding="utf-8")
    print(f"Wrote {OUT}")

    if args.do_import:
        cli = ROOT / "site/scripts/import_seo_cluster_cli.php"
        subprocess.run(["php", str(cli), str(OUT), "pages", "5", "full"], check=True)
        print("Imported pages#5")


if __name__ == "__main__":
    main()
