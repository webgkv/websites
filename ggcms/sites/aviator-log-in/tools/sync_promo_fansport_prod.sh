#!/usr/bin/env bash
# Build FanSport promo cluster JSON and import to aviator-log-in prod.
set -euo pipefail

TOOLS="$(cd "$(dirname "$0")" && pwd)"
ROOT="$(cd "$TOOLS/.." && pwd)"
JSON="$ROOT/site/files/reference/seo-promo-1-full.json"
REMOTE_JSON="/tmp/seo-promo-1-full.json"
ENTITY_ID=1
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/aviator-log-in.com/public_html"

echo "== Build cluster =="
python3 "$TOOLS/build_aviator_promo_fansport_cluster.py"

echo "== Upload JSON =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"

echo "== Upload hero webp =="
$SSH "mkdir -p /home/dikodo/web/aviator-log-in.com/public_html/files/media/2026/07"
scp -i ~/.ssh/webgkv -P 20203 \
	"$ROOT/site/files/media/2026/07/aviator-fansport-fs.webp" \
	"dikodo@38.133.213.49:/home/dikodo/web/aviator-log-in.com/public_html/files/media/2026/07/aviator-fansport-fs.webp"

echo "== Run seed (promo row + main img) =="
$SSH "$REMOTE && php scripts/run_migrate_BD.php"

echo "== Import promo#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} promo ${ENTITY_ID} full"

echo "== Slug redirects (fansport-15-free-spins -> fansport-free-spins) =="
scp -i ~/.ssh/webgkv -P 20203 "$ROOT/site/scripts/promo_fansport_slug_redirect.php" "dikodo@38.133.213.49:/home/dikodo/web/aviator-log-in.com/public_html/scripts/promo_fansport_slug_redirect.php"
$SSH "$REMOTE && php scripts/promo_fansport_slug_redirect.php"

echo "== Done =="
