#!/usr/bin/env bash
# Export pages hub cluster from prod, apply fr/ar meta, import back.
set -euo pipefail

ENTITY_ID="${1:?usage: sync_pages_fr_ar_hub_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/chickenroad-pages}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
JSON="$DL/seo-pages-${ENTITY_ID}-full.json"
REMOTE_JSON="/tmp/seo-pages-${ENTITY_ID}-fr-ar-full.json"

mkdir -p "$DL"
echo "== Export pages#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php pages ${ENTITY_ID} full" > "$JSON"

echo "== Apply fr/ar hub meta =="
python3 "$TOOLS/apply_pages_fr_ar_hub.py" "$JSON"

echo "== Import pages#${ENTITY_ID} =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} pages ${ENTITY_ID} full"

echo "== Verify =="
python3 "$TOOLS/audit_fr_ar_parity.py" "$JSON"

echo "== Done =="
