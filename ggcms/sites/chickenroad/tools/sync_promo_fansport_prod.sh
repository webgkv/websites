#!/usr/bin/env bash
# Build FanSport promo cluster JSON and import to chickenroad prod.
set -euo pipefail

TOOLS="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$TOOLS/.." && pwd)"
JSON="$ROOT/site/files/reference/seo-promo-1-full.json"
REMOTE_JSON="/tmp/seo-promo-1-full.json"
ENTITY_ID=1
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"

echo "== Build cluster =="
python3 "$TOOLS/build_chickenroad_promo_fansport_cluster.py"

echo "== Upload JSON =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"

echo "== Import promo#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} promo ${ENTITY_ID} full"

echo "== Done =="
