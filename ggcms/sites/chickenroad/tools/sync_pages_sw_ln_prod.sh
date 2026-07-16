#!/usr/bin/env bash
# Export pages cluster from prod, apply sw/ln, import back.
set -euo pipefail

ENTITY_ID="${1:?usage: sync_pages_sw_ln_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/chickenroad-pages}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
JSON="$DL/seo-pages-${ENTITY_ID}-full.json"
REMOTE_JSON="/tmp/seo-pages-${ENTITY_ID}-full.json"

mkdir -p "$DL"
echo "== Export pages#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php pages ${ENTITY_ID} full" > "$JSON"

echo "== Apply sw/ln =="
python3 "$TOOLS/apply_pages_sw_ln_cluster.py" "$JSON"

echo "== Import pages#${ENTITY_ID} =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} pages ${ENTITY_ID} full"

echo "== Verify =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php pages ${ENTITY_ID} meta" | python3 -c "
import json,sys,re
d=json.load(sys.stdin)
for lid in (20,21):
 loc=next(x for x in d['locales'] if x['lang_id']==lid)
 c=loc.get('content') or ''
 plain=re.sub(r'<[^>]+>','',c).strip()
 print(lid, loc.get('lang_url'), loc.get('status'), 'title', len(loc.get('title') or ''), 'body', len(plain))
"
