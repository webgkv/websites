#!/usr/bin/env bash
# Rebuild Ice Fish home page content images from gameplay screenshots (never hero art).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
IMG="$ROOT/site/assets/images"
SRC="$IMG/_src"
HERO="$IMG/ice-fish-hero.webp"

mkdir -p "$SRC"
cd "$SRC"

fetch() {
  local out="$1" url="$2"
  if [[ ! -s "$out" ]]; then
    curl -fsSL -o "$out" "$url"
  fi
}

fetch interface.png 'https://slotexpanse.com/wp-content/uploads/2026/02/ice-fish-interface-1024x567.png'
fetch multipliers.png 'https://inoutgames.com/wp-content/uploads/2025/06/ice-fish-multipliers-1024x576.png'
fetch win-cashout.png 'https://inoutgames.com/wp-content/uploads/2025/06/inout-ice-fish-win-1024x576.png'
fetch mobile-ui.png 'https://slotexpanse.com/wp-content/uploads/2026/02/ice-fish-interface-768x425.png'

to_webp() {
  local src="$1" dest="$2" width="$3"
  magick "$src" -resize "${width}x>" -quality 82 "$dest"
}

to_webp "$SRC/interface.png" "$IMG/ice-fish-app-desktop-mobile.webp" 900
to_webp "$SRC/multipliers.png" "$IMG/ice-fish-gameplay.webp" 900
to_webp "$SRC/mobile-ui.png" "$IMG/ice-fish-mobile.webp" 640
to_webp "$SRC/interface.png" "$IMG/ice-fish-step-1.webp" 640
to_webp "$SRC/multipliers.png" "$IMG/ice-fish-step-2.webp" 640
to_webp "$SRC/win-cashout.png" "$IMG/ice-fish-step-3.webp" 640

hero_md5="$(md5sum "$HERO" | awk '{print $1}')"
fail=0
for f in \
  ice-fish-app-desktop-mobile.webp \
  ice-fish-gameplay.webp \
  ice-fish-mobile.webp \
  ice-fish-step-1.webp \
  ice-fish-step-2.webp \
  ice-fish-step-3.webp
do
  path="$IMG/$f"
  md5="$(md5sum "$path" | awk '{print $1}')"
  if [[ "$md5" == "$hero_md5" ]]; then
    echo "ERROR: $f matches hero md5" >&2
    fail=1
  fi
  identify "$path"
done

if [[ "$fail" -ne 0 ]]; then
  exit 1
fi

echo "OK: home content images rebuilt (hero stays in template only)"
