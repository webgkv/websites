#!/usr/bin/env python3
"""Regenerate core file inventory (ggcms/core vs site overlays)."""
import hashlib, json, os, sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CORE = os.path.join(ROOT, 'ggcms', 'core')
BRANDS = ['chickenroad', 'aviator-log-in', 'powerballjackpot']

def fh(p):
    h = hashlib.md5()
    with open(p, 'rb') as f:
        for c in iter(lambda: f.read(65536), b''): h.update(c)
    return h.hexdigest()

def list_files(base):
    out = set()
    for root, dirs, files in os.walk(base):
        dirs[:] = [d for d in dirs if d not in ('media', '.git', 'venv')]
        for f in files:
            if f == '.gitignore': continue
            out.add(os.path.relpath(os.path.join(root, f), base).replace('\\', '/'))
    return out

core_files = list_files(CORE)
report = {'core_files': len(core_files), 'brands': {}}
for brand in BRANDS:
    ov = os.path.join(ROOT, 'ggcms', 'sites', brand, 'site')
    ov_files = list_files(ov) if os.path.isdir(ov) else set()
    report['brands'][brand] = {'overlay_files': len(ov_files)}
json.dump(report, sys.stdout, indent=2)
