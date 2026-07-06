#!/usr/bin/env bash
# Install Hestia web nginx proxy cache templates caching-ice-fish.
#
# Run on the server (as root):
#   sudo bash hestia/install_caching_icefish_templates.sh
#
# Does NOT rebuild/reload nginx by itself. Apply template in Hestia UI or via:
#   sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl <USER> <DOMAIN> caching-ice-fish
#   sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>
#   sudo nginx -t && sudo systemctl reload nginx

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/templates/nginx"
DEST=/usr/local/hestia/data/templates/web/nginx

if [[ ! -f "$SRC/caching-ice-fish.tpl" || ! -f "$SRC/caching-ice-fish.stpl" ]]; then
  echo "error: missing $SRC/caching-ice-fish.tpl or caching-ice-fish.stpl" >&2
  exit 1
fi

install -d -m 755 "$DEST"
install -m 755 "$SRC/caching-ice-fish.tpl" "$DEST/caching-ice-fish.tpl"
install -m 755 "$SRC/caching-ice-fish.stpl" "$DEST/caching-ice-fish.stpl"

echo "Installed:"
ls -la "$DEST/caching-ice-fish.tpl" "$DEST/caching-ice-fish.stpl"
echo ""
echo "Next:"
echo "  - Hestia → Web → domain → Edit → Proxy template → caching-ice-fish → Save"
echo "  - sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>"
echo "  - sudo nginx -t && sudo systemctl reload nginx"
