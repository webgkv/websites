#!/usr/bin/env bash
set -euo pipefail

# One-off cleanup for unused betPawa images.
# Default mode: dry-run (no deletions).
#
# Usage:
#   bash site/tools/delete_unused_betpawa_images.sh
#   bash site/tools/delete_unused_betpawa_images.sh --cluster "/home/lenovo/Downloads/08/seo-casino_articles-22-full.json"
#   bash site/tools/delete_unused_betpawa_images.sh --apply

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PROJECT_DIR="$(cd "${SITE_DIR}/.." && pwd)"

IMAGES_DIR="${SITE_DIR}/images/casinos"
CLUSTER="${PROJECT_DIR}/../Downloads/08/seo-casino_articles-22-full.json"
APPLY=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --cluster)
      CLUSTER="${2:-}"
      shift 2
      ;;
    --apply)
      APPLY=1
      shift
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ ! -d "$IMAGES_DIR" ]]; then
  echo "Images directory not found: $IMAGES_DIR" >&2
  exit 1
fi
if [[ ! -f "$CLUSTER" ]]; then
  echo "Cluster file not found: $CLUSTER" >&2
  exit 1
fi

mapfile -t USED_FILES < <(
  python - "$CLUSTER" <<'PY'
import json, re, sys
p = sys.argv[1]
data = json.load(open(p, encoding='utf-8'))
used = set()
for loc in data.get('locales', []):
    c = loc.get('content') or ''
    for m in re.findall(r'/images/casinos/([A-Za-z0-9._-]+)', c):
        used.add(m)
for f in sorted(used):
    print(f)
PY
)

declare -A USED_MAP=()
for f in "${USED_FILES[@]:-}"; do
  USED_MAP["$f"]=1
done

shopt -s nullglob
ALL_FILES=( "${IMAGES_DIR}"/betpawa-aviator* )
shopt -u nullglob

if [[ ${#ALL_FILES[@]} -eq 0 ]]; then
  echo "No betPawa image files found in ${IMAGES_DIR}"
  exit 0
fi

UNUSED=()
for f in "${ALL_FILES[@]}"; do
  b="$(basename "$f")"
  if [[ -z "${USED_MAP[$b]:-}" ]]; then
    UNUSED+=( "$f" )
  fi
done

echo "Cluster: $CLUSTER"
echo "Images dir: $IMAGES_DIR"
echo "Total betPawa files: ${#ALL_FILES[@]}"
echo "Referenced in cluster: ${#USED_FILES[@]}"
echo "Unused candidates: ${#UNUSED[@]}"
echo

if [[ ${#UNUSED[@]} -eq 0 ]]; then
  echo "Nothing to delete."
  exit 0
fi

for f in "${UNUSED[@]}"; do
  echo "- $(basename "$f")"
done

if [[ $APPLY -ne 1 ]]; then
  echo
  echo "Dry-run only. Re-run with --apply to delete these files."
  exit 0
fi

echo
echo "Deleting..."
DELETED=0
FAILED=0
for f in "${UNUSED[@]}"; do
  if rm -f -- "$f"; then
    DELETED=$((DELETED + 1))
    echo "OK  $(basename "$f")"
  else
    FAILED=$((FAILED + 1))
    echo "ERR $(basename "$f")"
  fi
done

echo
echo "Done. Deleted: $DELETED, Failed: $FAILED"
[[ $FAILED -eq 0 ]]

