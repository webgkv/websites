#!/usr/bin/env bash
# Install Hestia web nginx proxy cache templates caching-aviator.
#
# Run on the server (as root):
#   sudo bash hestia/install_caching_aviator_templates.sh
#
# Does NOT rebuild/reload nginx by itself. Apply template in Hestia UI or via:
#   sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl <USER> <DOMAIN> caching-aviator
#   sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>
#   sudo nginx -t && sudo systemctl reload nginx

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/templates/nginx"
DEST=/usr/local/hestia/data/templates/web/nginx

if [[ ! -f "$SRC/caching-aviator.tpl" || ! -f "$SRC/caching-aviator.stpl" ]]; then
  echo "error: missing $SRC/caching-aviator.tpl or caching-aviator.stpl" >&2
  exit 1
fi

install -d -m 755 "$DEST"
install -m 755 "$SRC/caching-aviator.tpl" "$DEST/caching-aviator.tpl"
install -m 755 "$SRC/caching-aviator.stpl" "$DEST/caching-aviator.stpl"

echo "Installed:"
ls -la "$DEST/caching-aviator.tpl" "$DEST/caching-aviator.stpl"
echo ""
echo "Next:"
echo "  - Hestia → Web → domain → Edit → Proxy template → caching-aviator → Save"
echo "  - sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>"
echo "  - sudo nginx -t && sudo systemctl reload nginx"

