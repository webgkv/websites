#!/usr/bin/env bash
# Grep for legacy Aviator branding in user-facing paths (excludes aviator_* PHP identifiers).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "=== Aviator brand leaks (site/, excl. vendor) ==="
rg -n --glob '*.php' --glob '*.json' --glob '*.txt' --glob '*.md' \
  -e 'aviator-log-in\.com' -e 'Aviator Log In' -e 'Install Aviator' -e 'the Aviator demo' \
  site \
  | rg -v 'site_brand_rebrand|site_seo_legacy|pwa_install_normalize|site_seo_public|function aviator_|aviator_counter|/functions/aviator_' \
  || echo "(none — or only allowlisted code paths)"
