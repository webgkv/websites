#!/usr/bin/env bash
# Install shared Hestia web nginx proxy cache templates (caching-ggcms).
#
# Run on the server (as root):
#   sudo bash ggcms/hestia/install_caching_ggcms_templates.sh
#
# Does NOT rebuild/reload nginx by itself. Apply template per domain:
#   sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl <USER> <DOMAIN> caching-ggcms
#   sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>
#   sudo nginx -t && sudo systemctl reload nginx

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SRC="$SCRIPT_DIR/templates/nginx"
DEST=/usr/local/hestia/data/templates/web/nginx

if [[ ! -f "$SRC/caching-ggcms.tpl" || ! -f "$SRC/caching-ggcms.stpl" ]]; then
  echo "error: missing $SRC/caching-ggcms.tpl or caching-ggcms.stpl" >&2
  exit 1
fi

install -d -m 755 "$DEST"
install -m 755 "$SRC/caching-ggcms.tpl" "$DEST/caching-ggcms.tpl"
install -m 755 "$SRC/caching-ggcms.stpl" "$DEST/caching-ggcms.stpl"

echo "Installed:"
ls -la "$DEST/caching-ggcms.tpl" "$DEST/caching-ggcms.stpl"
echo ""
echo "Next (all GGCMS domains):"
echo "  sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl <USER> <DOMAIN> caching-ggcms"
echo "  sudo /usr/local/hestia/bin/v-rebuild-web-domains <USER>"
echo "  sudo nginx -t && sudo systemctl reload nginx"
