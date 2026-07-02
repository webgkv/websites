#!/usr/bin/env bash
# Build a standalone site into ggcms/build/<brand>/site/
#
# Layers (later wins):
#   1. ggcms/core/
#   2. ggcms/sites/<brand>/site/
#   3. ggcms/sites/<brand>/modules/  -> site/modules/
#   4. each ggcms/sites/<brand>/plugins/<name>/ -> site/
#
# Usage:
#   ./ggcms/scripts/build_site.sh aviator-log-in
#   ./ggcms/scripts/build_site.sh all

set -euo pipefail

GGCMS_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_ROOT="$(cd "$GGCMS_ROOT/.." && pwd)"

build_one() {
	local brand="$1"
	local core="$GGCMS_ROOT/core"
	local overlay="$GGCMS_ROOT/sites/$brand/site"
	local modules="$GGCMS_ROOT/sites/$brand/modules"
	local plugins_dir="$GGCMS_ROOT/sites/$brand/plugins"
	local out="$GGCMS_ROOT/build/$brand/site"

	if [ ! -d "$core" ]; then
		echo "Error: ggcms/core not found"
		return 1
	fi
	if [ ! -d "$overlay" ]; then
		echo "Error: overlay not found: $overlay"
		echo "Run: python3 ggcms/scripts/extract_overlay.py $brand"
		return 1
	fi

	echo "Building $brand -> $out"
	rm -rf "$out"
	mkdir -p "$out"

	rsync -a "$core/" "$out/"
	rsync -a "$overlay/" "$out/"

	if [ -d "$modules" ]; then
		mkdir -p "$out/modules"
		rsync -a "$modules/" "$out/modules/"
	fi

	if [ -d "$plugins_dir" ]; then
		for plug in "$plugins_dir"/*/; do
			[ -d "$plug" ] || continue
			echo "  + plugin $(basename "$plug")"
			rsync -a "$plug" "$out/"
		done
	fi

	# Inject brand profile marker for runtime (optional single include)
	if [ -f "$GGCMS_ROOT/sites/$brand/brand.php" ]; then
		mkdir -p "$out/config"
		if [ ! -f "$out/config/brand.php" ]; then
			cp "$GGCMS_ROOT/sites/$brand/brand.php" "$out/config/brand.profile.php"
		fi
	fi

	echo "OK: $brand ($(find "$out" -type f | wc -l) files)"
}

if [ $# -lt 1 ]; then
	echo "Usage: $0 <brand|all>"
	exit 1
fi

if [ "$1" = "all" ]; then
	for b in chickenroad aviator-log-in powerballjackpot; do
		build_one "$b"
	done
else
	build_one "$1"
fi
