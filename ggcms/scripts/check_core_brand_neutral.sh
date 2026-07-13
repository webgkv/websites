#!/usr/bin/env bash
# Fail if ggcms/core PHP exposes Aviator-branded function/config APIs.
set -euo pipefail

GGCMS_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CORE="$GGCMS_ROOT/core"

if [ ! -d "$CORE" ]; then
	echo "Error: $CORE not found"
	exit 1
fi

# Function definitions and config keys — not casino slug URLs in dev tools or rebrand regexes.
PATTERN='function aviator_|aviator_lang_pref|aviator_demo_iframe_url|AVIATOR_AD_'

matches=$(rg "$PATTERN" "$CORE" --glob '*.php' 2>/dev/null || true)

if [ -n "$matches" ]; then
	echo "Aviator-branded API in ggcms/core:"
	echo "$matches"
	exit 1
fi

echo "OK: no aviator_* functions or legacy config keys in ggcms/core"
