#!/usr/bin/env bash
# Apply local fr/ar fixes and sync SEO clusters to prod.
set -euo pipefail

TOOLS="$(cd "$(dirname "$0")" && pwd)"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
SCP="scp -i ~/.ssh/webgkv -P 20203"

sync_cluster() {
  local entity="$1"
  local id="$2"
  local apply_cmd="$3"
  local dl_subdir="$4"
  local json_name="$5"

  local dl="${DL:-$HOME/Downloads/02/chickenroad-${dl_subdir}}"
  local json="$dl/${json_name}"
  local remote_json="/tmp/${json_name%.json}-fr-ar.json"

  mkdir -p "$dl"
  echo "== Export ${entity}#${id} =="
  $SSH "$REMOTE && php scripts/export_seo_cluster_cli.php ${entity} ${id} full" > "$json"

  echo "== Apply fr/ar (${apply_cmd}) =="
  eval "python3 \"$TOOLS/${apply_cmd}\" \"$json\""

  echo "== Audit local =="
  python3 "$TOOLS/audit_fr_ar_parity.py" "$json"

  echo "== Import ${entity}#${id} =="
  $SCP "$json" "dikodo@38.133.213.49:${remote_json}"
  $SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${remote_json} ${entity} ${id} full"

  echo "== OK ${entity}#${id} =="
}

case "${1:-all}" in
  pages5)
    sync_cluster pages 5 apply_pages_fr_ar_download.py pages seo-pages-5-full.json
    ;;
  pages6)
    sync_cluster pages 6 apply_pages_fr_ar_strategies.py pages seo-pages-6-full.json
    ;;
  pages1)
    sync_cluster pages 1 apply_pages_fr_ar_home.py pages seo-pages-1-full.json
    ;;
  guides)
    python3 "$TOOLS/build_guides_fr_ar_editorial.py"
    for i in 1 2 3 4 5 6 7 8; do
      sync_cluster guides "$i" apply_guides_fr_ar_cluster.py guides "seo-guides-${i}-full.json"
    done
    ;;
  casino)
    for i in 10 11 18 24; do
      sync_cluster casino_articles "$i" apply_casino_fr_ar_cluster_expand.py casinos "seo-casino_articles-${i}-full.json"
    done
    ;;
  authors2)
    sync_cluster authors 2 apply_authors_fr_ar_cluster.py authors seo-authors-2-full.json
    ;;
  all)
    "$0" pages5
    "$0" pages6
    "$0" pages1
    "$0" guides
    "$0" casino
    "$0" authors2
    echo "== Prod verify =="
    $SSH "$REMOTE && php scripts/verify_fr_ar_prod.php" || true
    ;;
  *)
    echo "Usage: $0 {all|pages1|pages5|pages6|guides|casino|authors2}" >&2
    exit 1
    ;;
esac
