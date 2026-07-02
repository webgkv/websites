#!/usr/bin/env bash
# Install Hestia web nginx proxy cache templates caching-chickenroad.
#
# Run on the server (as root):
#   sudo bash hestia/install_caching_chickenroad_templates.sh
#
# Does NOT rebuild/reload nginx by itself. Apply template in Hestia UI or via:
#   sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl <USER> <DOMAIN> caching-chickenroad
#   sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>
#   sudo nginx -t && sudo systemctl reload nginx

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/templates/nginx"
DEST=/usr/local/hestia/data/templates/web/nginx

if [[ ! -f "$SRC/caching-chickenroad.tpl" || ! -f "$SRC/caching-chickenroad.stpl" ]]; then
  echo "error: missing $SRC/caching-chickenroad.tpl or caching-chickenroad.stpl" >&2
  exit 1
fi

install -d -m 755 "$DEST"
install -m 755 "$SRC/caching-chickenroad.tpl" "$DEST/caching-chickenroad.tpl"
install -m 755 "$SRC/caching-chickenroad.stpl" "$DEST/caching-chickenroad.stpl"

echo "Installed:"
ls -la "$DEST/caching-chickenroad.tpl" "$DEST/caching-chickenroad.stpl"
echo ""
echo "Next:"
echo "  - Hestia → Web → domain → Edit → Proxy template → caching-chickenroad → Save"
echo "  - sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>"
echo "  - sudo nginx -t && sudo systemctl reload nginx"
