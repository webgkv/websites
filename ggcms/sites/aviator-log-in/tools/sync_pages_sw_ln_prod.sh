#!/usr/bin/env bash
set -euo pipefail
ENTITY_ID="${1:?usage: sync_pages_sw_ln_prod.sh <entity_id>}"
TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02/aviator-pages}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/aviator-log-in.com/public_html"
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
\$scan=seo_monitor_list_row_issue_scan(\"pages\", \$id);
\$pack=seo_monitor_export_cluster_array(\"pages\", \$id, \"full\");
foreach (\$pack[\"data\"][\"locales\"] as \$loc) {
  if (!in_array((int)\$loc[\"lang_id\"], [20,21], true)) continue;
  \$plain=strlen(trim(strip_tags(\$loc[\"content\"]??\"\")));
  echo \$loc[\"lang_url\"], \" \", \$loc[\"status\"], \" body=\", \$plain, \" issues=\", (int)\$scan[\"issue_count\"], \"\\n\";
}
'"
echo "== Done =="
