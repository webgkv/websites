#!/usr/bin/env bash
# Export guides cluster from prod, apply sw/ln, import back.
set -euo pipefail

ENTITY_ID="${1:?usage: sync_guides_sw_ln_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/powerballjackpot-guides}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/powerballjackpot.run/public_html"
JSON="$DL/seo-guides-${ENTITY_ID}-full.json"
REMOTE_JSON="/tmp/seo-guides-${ENTITY_ID}-full.json"

mkdir -p "$DL"
echo "== Export guides#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php guides ${ENTITY_ID} full 2>/dev/null" > "$JSON"

echo "== Apply sw/ln =="
python3 "$TOOLS/apply_guides_sw_ln_cluster.py" "$JSON"

echo "== Import guides#${ENTITY_ID} =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} guides ${ENTITY_ID} full"

echo "== Done =="
