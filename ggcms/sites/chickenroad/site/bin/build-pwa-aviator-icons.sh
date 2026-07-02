#!/usr/bin/env bash
# Build PWA icons from Chicken Road game tile (square cover crop, like Aviator).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
IMG="$ROOT/assets/images"
SRC=""

if [[ -n "${1:-}" ]]; then
	SRC="$1"
elif [[ -f "$IMG/chickenroad-pwa-source.png" ]]; then
	SRC="$IMG/chickenroad-pwa-source.png"
elif [[ -f "$IMG/chickenroad-pwa-source.webp" ]]; then
	SRC="$IMG/chickenroad-pwa-source.webp"
elif [[ -f "$IMG/chickenroad-hero.webp" ]]; then
	SRC="$IMG/chickenroad-hero.webp"
elif [[ -f "$IMG/chickenroad-hero.png" ]]; then
	SRC="$IMG/chickenroad-hero.png"
else
	echo "Missing source image. Save one as:" >&2
	echo "  $IMG/chickenroad-pwa-source.png" >&2
	echo "or run: $0 /path/to/icon.png" >&2
	exit 1
fi

# Cover-crop to square (fills icon like Aviator); background matches manifest.php (#2c2a33).
python3 - "$SRC" "$IMG" "$ROOT" <<'PY'
import os
import shutil
import sys
from PIL import Image

src_path, img_dir, root = sys.argv[1:4]
bg = (44, 42, 51, 255)  # #2c2a33

def cover_square(im, size):
    im = im.convert('RGBA')
    tw = th = size
    sw, sh = im.size
    scale = max(tw / sw, th / sh)
    nw, nh = round(sw * scale), round(sh * scale)
    im = im.resize((nw, nh), Image.Resampling.LANCZOS)
    left = (nw - tw) // 2
    top = (nh - th) // 2
    cropped = im.crop((left, top, left + tw, top + th))
    out = Image.new('RGBA', (tw, th), bg)
    out.paste(cropped, (0, 0), cropped)
    return out.convert('RGB')

src = Image.open(src_path)
for size, name in ((180, 'pwa-icon-180.png'), (192, 'pwa-icon-192.png'), (512, 'pwa-icon-512.png')):
    out_path = os.path.join(img_dir, name)
    cover_square(src, size).save(out_path, 'PNG', optimize=True)

shutil.copy2(os.path.join(img_dir, 'pwa-icon-180.png'), os.path.join(root, 'apple-touch-icon.png'))
print(f"Wrote {img_dir}/pwa-icon-{{180,192,512}}.png and {root}/apple-touch-icon.png")
PY
