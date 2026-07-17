#!/usr/bin/env bash
# Phase 2 fr/ar parity fixes: apply, audit, import to prod.
set -euo pipefail

TOOLS="$(cd "$(dirname "$0")" && pwd)"
DL="${DL:-$HOME/Downloads/02}"
SSH="ssh -i ~/.ssh/webgkv -p 20203 dikodo@38.133.213.49"
REMOTE="cd /home/dikodo/web/chickenroad.run/public_html"
SCP="scp -i ~/.ssh/webgkv -P 20203"

sync_one() {
  local entity="$1"
  local id="$2"
  local apply_cmd="$3"
  local subdir="$4"
  local json_name="$5"

  local dl="$DL/chickenroad-${subdir}"
  local json="$dl/${json_name}"
  local remote_json="/tmp/${json_name%.json}-fr-ar-phase2.json"

  mkdir -p "$dl"
  echo "== Export ${entity}#${id} =="
  $SSH "$REMOTE && php scripts/export_seo_cluster_cli.php ${entity} ${id} full" > "$json"

  echo "== Apply (${apply_cmd}) =="
  python3 "$TOOLS/${apply_cmd}" "$json"

  echo "== Audit local =="
  python3 "$TOOLS/audit_fr_ar_parity.py" "$json"

  echo "== Import ${entity}#${id} =="
  $SCP "$json" "dikodo@38.133.213.49:${remote_json}"
  $SSH "$REMOTE && php scripts/import_seo_cluster_cli.php ${remote_json} ${entity} ${id} full"

  echo "== OK ${entity}#${id} =="
}

case "${1:-all}" in
  all)
    sync_one pages 4 apply_pages_fr_ar_cluster.py pages seo-pages-4-full.json
    sync_one pages 26 apply_pages_fr_ar_cluster.py pages seo-pages-26-full.json
    sync_one pages 27 apply_pages_fr_ar_cluster.py pages seo-pages-27-full.json
    sync_one pages 28 apply_pages_fr_ar_cluster.py pages seo-pages-28-full.json
    sync_one pages 29 apply_pages_fr_ar_cluster.py pages seo-pages-29-full.json
    sync_one pages 33 apply_pages_fr_ar_cluster.py pages seo-pages-33-full.json
    sync_one pages 34 apply_pages_fr_ar_cluster.py pages seo-pages-34-full.json
    sync_one games 3 apply_games_fr_ar_tune.py games seo-games-3-full.json
    sync_one games 6 apply_games_fr_ar_tune.py games seo-games-6-full.json
    sync_one games 7 apply_games_fr_ar_tune.py games seo-games-7-full.json
    sync_one casino_articles 25 apply_casino_fr_ar_cluster_expand.py casinos seo-casino_articles-25-full.json
    sync_one casino_articles 26 apply_casino_fr_ar_cluster_expand.py casinos seo-casino_articles-26-full.json
    sync_one blog 1 apply_blog_fr_ar_cluster.py blog seo-blog-1-full.json
    sync_one blog 2 apply_blog_fr_ar_cluster.py blog seo-blog-2-full.json
    sync_one blog 3 apply_blog_fr_ar_cluster.py blog seo-blog-3-full.json
    sync_one blog 4 apply_blog_fr_ar_cluster.py blog seo-blog-4-full.json
    sync_one authors 1 apply_authors_fr_ar_cluster.py authors seo-authors-1-full.json
    echo "== Deploy verify script =="
    $SCP "$TOOLS/verify_fr_ar_prod.php" "dikodo@38.133.213.49:/tmp/verify_fr_ar_prod.php"
    $SSH "$REMOTE && cp /tmp/verify_fr_ar_prod.php scripts/verify_fr_ar_prod.php && php -l scripts/verify_fr_ar_prod.php"
    echo "== Prod re-export audit =="
    mkdir -p "$DL/chickenroad-"{pages,games,casinos,blog,authors}
    reexport() {
      local ent="$1" id="$2" sub="$3" json="$4"
      $SSH "$REMOTE && php scripts/export_seo_cluster_cli.php ${ent} ${id} full" > "$DL/chickenroad-${sub}/${json}"
    }
    reexport pages 4 pages seo-pages-4-full.json
    reexport pages 26 pages seo-pages-26-full.json
    reexport pages 27 pages seo-pages-27-full.json
    reexport pages 28 pages seo-pages-28-full.json
    reexport pages 29 pages seo-pages-29-full.json
    reexport pages 33 pages seo-pages-33-full.json
    reexport pages 34 pages seo-pages-34-full.json
    reexport games 3 games seo-games-3-full.json
    reexport games 6 games seo-games-6-full.json
    reexport games 7 games seo-games-7-full.json
    reexport casino_articles 25 casinos seo-casino_articles-25-full.json
    reexport casino_articles 26 casinos seo-casino_articles-26-full.json
    reexport blog 1 blog seo-blog-1-full.json
    reexport blog 2 blog seo-blog-2-full.json
    reexport blog 3 blog seo-blog-3-full.json
    reexport blog 4 blog seo-blog-4-full.json
    reexport authors 1 authors seo-authors-1-full.json
    python3 "$TOOLS/audit_fr_ar_parity.py" \
      "$DL/chickenroad-pages/seo-pages-4-full.json" \
      "$DL/chickenroad-pages/seo-pages-26-full.json" \
      "$DL/chickenroad-pages/seo-pages-27-full.json" \
      "$DL/chickenroad-pages/seo-pages-28-full.json" \
      "$DL/chickenroad-pages/seo-pages-29-full.json" \
      "$DL/chickenroad-pages/seo-pages-33-full.json" \
      "$DL/chickenroad-pages/seo-pages-34-full.json" \
      "$DL/chickenroad-games/seo-games-3-full.json" \
      "$DL/chickenroad-games/seo-games-6-full.json" \
      "$DL/chickenroad-games/seo-games-7-full.json" \
      "$DL/chickenroad-casinos/seo-casino_articles-25-full.json" \
      "$DL/chickenroad-casinos/seo-casino_articles-26-full.json" \
      "$DL/chickenroad-blog/seo-blog-1-full.json" \
      "$DL/chickenroad-blog/seo-blog-2-full.json" \
      "$DL/chickenroad-blog/seo-blog-3-full.json" \
      "$DL/chickenroad-blog/seo-blog-4-full.json" \
      "$DL/chickenroad-authors/seo-authors-1-full.json"
    ;;
  *)
    echo "Usage: $0 {all}" >&2
    exit 1
    ;;
esac
