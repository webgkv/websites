#!/usr/bin/env bash
# Export games cluster from prod, apply fr/ar, import back.
set -euo pipefail

ENTITY_ID="${1:?usage: sync_games_fr_ar_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/chickenroad-games}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
JSON="$DL/seo-games-${ENTITY_ID}-full.json"
REMOTE_JSON="/tmp/seo-games-${ENTITY_ID}-fr-ar-full.json"

mkdir -p "$DL"
echo "== Export games#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php games ${ENTITY_ID} full" > "$JSON"

echo "== Apply fr/ar =="
python3 "$TOOLS/apply_games_fr_ar_cluster.py" "$JSON"

echo "== Import games#${ENTITY_ID} =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} games ${ENTITY_ID} full"

echo "== Verify =="
python3 "$TOOLS/audit_fr_ar_parity.py" "$JSON"

echo "== Done =="
