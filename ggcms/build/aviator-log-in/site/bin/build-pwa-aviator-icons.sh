#!/usr/bin/env bash
# Build PWA icons from the Aviator game tile (10bet CDN or a local copy).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
IMG="$ROOT/assets/images"
SRC_URL="${PWA_AVIATOR_ICON_URL:-https://www.10bet.co.za/media/uploads/casino-operator/10BETZA/games/5eb4464a-1048-48ef-94b7-8c5944107d42.png}"
TMP="$(mktemp "${TMPDIR:-/tmp}/pwa-aviator-src.XXXXXX")"

if [[ -n "${1:-}" ]]; then
	cp "$1" "$TMP"
elif [[ -f "$IMG/pwa-aviator-source.webp" ]]; then
	cp "$IMG/pwa-aviator-source.webp" "$TMP"
elif [[ -f "$IMG/pwa-aviator-source.png" ]]; then
	cp "$IMG/pwa-aviator-source.png" "$TMP"
else
	curl -fsSL -A "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15" \
		-o "$TMP" "$SRC_URL" || {
		echo "Download failed (often Cloudflare). Save the image as:" >&2
		echo "  $IMG/pwa-aviator-source.webp or pwa-aviator-source.png" >&2
		echo "then run: $0" >&2
		exit 1
	}
fi

# Fit inside square without upscaling small sources; pad to exact PWA sizes.
# 180×180: primary size for iOS Safari "Add to Home Screen" / apple-touch-icon.
magick "$TMP" -resize '180x180>' -background '#151b24' -gravity center -extent 180x180 "$IMG/pwa-icon-180.png"
magick "$TMP" -resize '192x192>' -background '#151b24' -gravity center -extent 192x192 "$IMG/pwa-icon-192.png"
magick "$TMP" -resize '512x512>' -background '#151b24' -gravity center -extent 512x512 "$IMG/pwa-icon-512.png"
cp -f "$IMG/pwa-icon-180.png" "$ROOT/apple-touch-icon.png"
rm -f "$TMP"
echo "Wrote $IMG/pwa-icon-{180,192,512}.png and $ROOT/apple-touch-icon.png"
