#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""ProLinksLab Meta tab -> seo_cluster_v1 meta JSON (phase 1: EN/FR/DE/ES only)."""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import time
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlparse

TOOLS = Path(__file__).resolve().parent
sys.path.insert(0, str(TOOLS))

from aviator_seo_config import SSH  # noqa: E402

DEFAULT_XLSX = Path.home() / "Downloads/04/aviator_keyword_map_ProLinksLab.xlsx"
DEFAULT_OUT = Path.home() / "Downloads/04/prolinks-meta-clusters"
MANIFEST_OUT = DEFAULT_OUT / "prolinks_meta_manifest.json"
URL_MAP_FILE = TOOLS / "data" / "prolinks_url_map.json"

FILE_LANG_TO_ID = {"EN": 1, "FR": 3, "DE": 4, "ES": 6}
LANG_ID_TO_CODE = {1: "en", 3: "fr", 4: "de", 6: "es", 7: "hi", 8: "pt", 9: "ru", 11: "ar", 12: "az", 13: "bn", 14: "it", 15: "nl", 16: "pl", 17: "vi", 18: "ua", 19: "ro", 20: "sw", 21: "ln"}

PATH_ALIASES = {"download/install-apk": "install-apk", "download/install-pwa": "install-pwa"}
LANG_PATH_PREFIXES = {"en", "fr", "de", "es", "hi", "pt", "ru", "ar", "az", "bn", "it", "nl", "pl", "vi", "ua", "ro", "sw", "ln"}


def log(msg: str) -> None:
    print(f"[{datetime.now().strftime('%H:%M:%S')}] {msg}", flush=True)


def fmt_eta(seconds: float) -> str:
    seconds = int(max(0, seconds))
    m, s = divmod(seconds, 60)
    h, m = divmod(m, 60)
    return f"{h}h {m}m" if h else (f"{m}m {s}s" if m else f"{s}s")


def utf8_len(text: str) -> int:
    return len(text or "")


def trim_seo(text: str, limit: int) -> str:
    text = re.sub(r"\s+", " ", (text or "").strip())
    if utf8_len(text) <= limit:
        return text
    cut = text[:limit]
    if " " in cut:
        cut = cut.rsplit(" ", 1)[0]
    return cut.rstrip(" ,.;:-")


def load_openpyxl():
    try:
        import openpyxl  # noqa: F401
    except ImportError:
        subprocess.check_call([sys.executable, "-m", "pip", "install", "openpyxl", "-q"])
    import openpyxl

    return openpyxl


def load_url_map() -> dict[str, dict]:
    data = json.loads(URL_MAP_FILE.read_text(encoding="utf-8"))
    return {k: {"entity": v["entity"], "entity_id": int(v["entity_id"]), "slug": k} for k, v in data.items()}


def url_to_slug(url: str) -> str:
    path = urlparse(url).path.strip("/")
    parts = path.split("/") if path else []
    slug = "/".join(parts[1:]) if parts and parts[0] in LANG_PATH_PREFIXES else path
    return PATH_ALIASES.get(slug, slug)


def parse_meta_sheet(xlsx: Path) -> dict[str, dict[int, dict[int, dict[str, str]]]]:
    openpyxl = load_openpyxl()
    wb = openpyxl.load_workbook(xlsx, read_only=True, data_only=True)
    ws = wb["Meta"]
    url_map = load_url_map()
    by_entity: dict[str, dict[int, dict[int, dict[str, str]]]] = defaultdict(lambda: defaultdict(dict))

    for row in ws.iter_rows(min_row=2, values_only=True):
        if not row or not row[0] or not row[1]:
            continue
        lang_key = str(row[0]).strip().upper()
        if lang_key not in FILE_LANG_TO_ID:
            continue
        lang_id = FILE_LANG_TO_ID[lang_key]
        url = str(row[1]).strip()
        title = trim_seo(str(row[2] or "").strip(), 70)
        desc = trim_seo(str(row[4] or "").strip(), 160)
        if not title and not desc:
            continue
        slug = url_to_slug(url)
        rec = url_map.get(slug)
        if not rec:
            raise SystemExit(f"Unmapped URL slug: {slug} ({url})")
        by_entity[rec["entity"]][rec["entity_id"]][lang_id] = {"title": title, "description": desc}

    wb.close()
    return by_entity


def write_manifest(by_entity: dict, path: Path) -> None:
    items = []
    for entity in sorted(by_entity.keys()):
        for entity_id in sorted(by_entity[entity].keys()):
            meta = by_entity[entity][entity_id]
            en = meta.get(1, {})
            items.append(
                {
                    "entity": entity,
                    "entity_id": entity_id,
                    "file_meta": {LANG_ID_TO_CODE[lid]: meta[lid] for lid in sorted(meta.keys())},
                    "en_title": en.get("title", ""),
                    "en_description": en.get("description", ""),
                }
            )
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(items, ensure_ascii=False, indent=2), encoding="utf-8")
    log(f"manifest: {len(items)} pages -> {path}")


def export_cluster(entity: str, entity_id: int) -> dict:
    cmd = (
        f"{SSH} 'cd /home/dikodo/web/aviator-log-in.com/public_html "
        f"&& php scripts/export_seo_cluster_cli.php {entity} {entity_id} meta'"
    )
    raw = subprocess.check_output(cmd, text=True, shell=True)
    data = json.loads(raw)
    if data.get("schema") != "seo_cluster_v1":
        raise RuntimeError(f"export failed {entity}#{entity_id}")
    return data


def apply_file_meta(cluster: dict, meta_by_lang: dict[int, dict[str, str]]) -> int:
    patched = 0
    for loc in cluster.get("locales", []):
        lang_id = int(loc.get("lang_id") or 0)
        if lang_id not in meta_by_lang:
            continue
        loc["title"] = meta_by_lang[lang_id]["title"]
        loc["description"] = meta_by_lang[lang_id]["description"]
        patched += 1
    cluster["mode"] = "meta"
    cluster["exported_at"] = datetime.now(timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z")
    return patched


def build_file_meta_clusters(xlsx: Path, out_dir: Path, resume: bool = False) -> list[Path]:
    by_entity = parse_meta_sheet(xlsx)
    write_manifest(by_entity, MANIFEST_OUT)
    jobs = [(e, i, by_entity[e][i]) for e in sorted(by_entity) for i in sorted(by_entity[e])]
    out_dir.mkdir(parents=True, exist_ok=True)
    written: list[Path] = []
    t0_all = time.time()
    durations: list[float] = []

    for idx, (entity, entity_id, meta_by_lang) in enumerate(jobs, 1):
        path = out_dir / f"seo-{entity}-{entity_id}-meta.json"
        if resume and path.is_file():
            log(f"[{idx}/{len(jobs)}] {entity}#{entity_id} skip (exists)")
            written.append(path)
            continue
        eta = (sum(durations) / len(durations) * (len(jobs) - idx + 1)) if durations else 0
        log(f"[{idx}/{len(jobs)}] {entity}#{entity_id} export+patch EN/FR/DE/ES (ETA ~{fmt_eta(eta)})")
        t0 = time.time()
        cluster = export_cluster(entity, entity_id)
        n = apply_file_meta(cluster, meta_by_lang)
        path.write_text(json.dumps(cluster, ensure_ascii=False, indent=2), encoding="utf-8")
        written.append(path)
        durations.append(time.time() - t0)
        log(f"[{idx}/{len(jobs)}] saved {path.name} ({n} locales) in {durations[-1]:.1f}s")

    log(f"phase 1 done: {len(written)} files in {fmt_eta(time.time() - t0_all)}")
    return written


def import_cluster(entity: str, entity_id: int, path: Path, idx: int, total: int) -> dict:
    remote = f"/tmp/seo-{entity}-{entity_id}-meta.json"
    log(f"[{idx}/{total}] import {entity}#{entity_id}")
    subprocess.check_call(
        ["scp", "-i", "/Users/gk/.ssh/webgkv", "-P", "20203", str(path), f"dikodo@38.133.213.49:{remote}"],
        stdout=subprocess.DEVNULL,
    )
    cmd = (
        f"{SSH} 'cd /home/dikodo/web/aviator-log-in.com/public_html "
        f"&& php scripts/import_seo_cluster_cli.php {remote} {entity} {entity_id} meta'"
    )
    return json.loads(subprocess.check_output(cmd, text=True, shell=True))


def main() -> None:
    ap = argparse.ArgumentParser()
    ap.add_argument("--xlsx", type=Path, default=DEFAULT_XLSX)
    ap.add_argument("--out", type=Path, default=DEFAULT_OUT)
    ap.add_argument("--build-only", action="store_true")
    ap.add_argument("--import-only", action="store_true")
    ap.add_argument("--resume", action="store_true")
    ap.add_argument("--manifest-only", action="store_true")
    args = ap.parse_args()

    if args.manifest_only:
        write_manifest(parse_meta_sheet(args.xlsx), MANIFEST_OUT)
        return

    if not args.import_only:
        build_file_meta_clusters(args.xlsx, args.out, resume=args.resume)
    if args.build_only:
        return

    files = sorted(args.out.glob("seo-*-meta.json"))
    ok = fail = 0
    for idx, path in enumerate(files, 1):
        m = re.match(r"seo-(.+)-(\d+)-meta\.json$", path.name)
        if not m:
            continue
        res = import_cluster(m.group(1), int(m.group(2)), path, idx, len(files))
        if res.get("ok"):
            ok += 1
        else:
            fail += 1
            log(f"FAIL {path.name}: {res}")
    log(f"import done ok={ok} fail={fail}")


if __name__ == "__main__":
    main()
