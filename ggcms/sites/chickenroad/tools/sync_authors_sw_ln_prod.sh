#!/usr/bin/env bash
# Export authors cluster from prod, apply sw/ln, import back.
set -euo pipefail

ENTITY_ID="${1:?usage: sync_authors_sw_ln_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/chickenroad-authors}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
JSON="$DL/seo-authors-${ENTITY_ID}-full.json"
REMOTE_JSON="/tmp/seo-authors-${ENTITY_ID}-full.json"

mkdir -p "$DL"
echo "== Export authors#${ENTITY_ID} =="
$SSH "$REMOTE && php scripts/export_seo_cluster_cli.php authors ${ENTITY_ID} full" > "$JSON"

echo "== Apply sw/ln =="
python3 "$TOOLS/apply_authors_sw_ln_cluster.py" "$JSON"

echo "== Import authors#${ENTITY_ID} =="
scp -i ~/.ssh/webgkv -P 20203 "$JSON" "dikodo@38.133.213.49:${REMOTE_JSON}"
$SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${REMOTE_JSON} authors ${ENTITY_ID} full"

echo "== Verify =="
$SSH "$REMOTE && php -r '
define(\"ROOT_DIR\", getcwd() . \"/\");
foreach ([\"HTTP_HOST\",\"REMOTE_ADDR\",\"SERVER_ADDR\",\"SERVER_NAME\",\"REQUEST_URI\"] as \$k) {
  if (!isset(\$_SERVER[\$k])) \$_SERVER[\$k] = (\$k===\"HTTP_HOST\") ? \"localhost\" : \"127.0.0.1\";
}
require_once ROOT_DIR . \"config/config.php\";
require_once ROOT_DIR . \"functions/mysql_func.php\";
require_once ROOT_DIR . \"functions/string_func.php\";
require_once ROOT_DIR . \"functions/seo_monitor.php\";
\$id='${ENTITY_ID}';
\$scan=seo_monitor_list_row_issue_scan(\"authors\", \$id);
\$pack=seo_monitor_export_cluster_array(\"authors\", \$id, \"full\");
foreach (\$pack[\"data\"][\"locales\"] as \$loc) {
  if (!in_array((int)\$loc[\"lang_id\"], [20,21], true)) continue;
  \$bio=strlen(trim(strip_tags(\$loc[\"content\"]??\"\")));
  echo \$loc[\"lang_url\"], \" \", \$loc[\"status\"], \" bio=\", \$bio, \" title=\", substr(\$loc[\"title\"]??\"\",0,40), \" issues=\", (int)\$scan[\"issue_count\"], \"\\n\";
}
'"

echo "== Done =="
