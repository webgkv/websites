#!/usr/bin/env python3
"""Extract site overlay files (differs from or absent in core) into ggcms/sites/<brand>/site/.

Usage:
    python3 ggcms/scripts/extract_overlay.py [brand ...]
    python3 ggcms/scripts/extract_overlay.py all
"""
import hashlib
import os
import shutil
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CORE = os.path.join(ROOT, 'ggcms', 'core')
BRANDS = ['chickenroad', 'aviator-log-in', 'powerballjackpot']
SKIP_DIRS = {'media', 'venv', 'node_modules', '.git', '__pycache__', 'logs'}
SKIP_FILES = {'.gitignore'}


def file_hash(path):
    h = hashlib.md5()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(65536), b''):
            h.update(chunk)
    return h.hexdigest()


def should_skip(rel):
    parts = rel.replace('\\', '/').split('/')
    if any(p in SKIP_DIRS for p in parts):
        return True
    if rel.endswith('.log'):
        return True
    return False


def extract_brand(brand):
    src_site = os.path.join(ROOT, 'ggcms', 'sites', brand, '_import_source', 'site')
    dst_site = os.path.join(ROOT, 'ggcms', 'sites', brand, 'site')
    if not os.path.isdir(src_site):
        print('skip (no import source): %s' % brand)
        return 0

    if os.path.isdir(dst_site):
        shutil.rmtree(dst_site)
    os.makedirs(dst_site, exist_ok=True)

    copied = skipped_core = 0
    for root, dirs, files in os.walk(src_site):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for fn in files:
            if fn in SKIP_FILES:
                continue
            full = os.path.join(root, fn)
            rel = os.path.relpath(full, src_site).replace('\\', '/')
            if should_skip(rel):
                continue
            core_path = os.path.join(CORE, rel)
            if os.path.isfile(core_path) and file_hash(full) == file_hash(core_path):
                skipped_core += 1
                continue
            dst = os.path.join(dst_site, rel)
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            shutil.copy2(full, dst)
            copied += 1

    print('%s: overlay %d files (skipped %d identical to core)' % (brand, copied, skipped_core))
    return copied


def main():
    if not os.path.isdir(CORE):
        print('Error: ggcms/core not found. Copy shared/core first.')
        sys.exit(1)
    targets = sys.argv[1:] if len(sys.argv) > 1 else BRANDS
    if targets == ['all']:
        targets = BRANDS
    for brand in targets:
        extract_brand(brand)


if __name__ == '__main__':
    main()
