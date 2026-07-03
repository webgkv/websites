#!/usr/bin/env bash
# GGCMS unified deploy entrypoint (build + upload).
#
# Usage:
#   ./deploy.sh --aviator-log-in          # build + deploy Aviator
#   ./deploy.sh --chickenroad
#   ./deploy.sh --powerballjackpot
#   ./deploy.sh --all                     # build + deploy every site, one by one
#
# Extra deploy flags are passed through to the per-site deploy, e.g.:
#   ./deploy.sh --aviator-log-in --reset
#   ./deploy.sh --all --reset --transfer-all

set -u

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_SITE="$REPO_ROOT/ggcms/scripts/deploy_site.sh"

BRANDS=(chickenroad aviator-log-in powerballjackpot)

usage() {
	cat <<'EOF'
GGCMS deploy — build a standalone site and upload it.

Usage:
  ./deploy.sh --<brand> [flags]     Deploy a single site
  ./deploy.sh --all [flags]         Deploy every site, one by one

Brands:
  --chickenroad
  --aviator-log-in
  --powerballjackpot
  --all

Deploy flags (passed through to the per-site deploy):
  --reset             Re-sync by size diff (no --only-newer)
  --transfer-all      Full re-upload (use with --reset)
  --delete-remote     Mirror-delete remote files not present locally
  --no-delete-remote  Keep remote-only files

Examples:
  ./deploy.sh --aviator-log-in
  ./deploy.sh --chickenroad --reset
  ./deploy.sh --all --reset --transfer-all
EOF
}

is_known_brand() {
	local b="$1"
	for known in "${BRANDS[@]}"; do
		[ "$b" = "$known" ] && return 0
	done
	return 1
}

SELECTED=()
PASS_ARGS=()
DEPLOY_ALL=0

for arg in "$@"; do
	case "$arg" in
		--all)
			DEPLOY_ALL=1
			;;
		-h|--help)
			usage
			exit 0
			;;
		--*)
			candidate="${arg#--}"
			if is_known_brand "$candidate"; then
				SELECTED+=("$candidate")
			else
				# Unknown --flag: pass through to per-site deploy (e.g. --reset).
				PASS_ARGS+=("$arg")
			fi
			;;
		*)
			PASS_ARGS+=("$arg")
			;;
	esac
done

if [ "$DEPLOY_ALL" -eq 1 ]; then
	SELECTED=("${BRANDS[@]}")
fi

if [ "${#SELECTED[@]}" -eq 0 ]; then
	echo "Error: no brand selected. Use --<brand> or --all."
	echo ""
	usage
	exit 1
fi

if [ ! -x "$DEPLOY_SITE" ] && [ ! -f "$DEPLOY_SITE" ]; then
	echo "Error: deploy worker not found: $DEPLOY_SITE"
	exit 1
fi

FAILED=()
for brand in "${SELECTED[@]}"; do
	echo ""
	echo "########################################################"
	echo "# Deploy: $brand"
	echo "########################################################"
	if bash "$DEPLOY_SITE" "$brand" ${PASS_ARGS[@]+"${PASS_ARGS[@]}"}; then
		echo "OK: $brand"
	else
		echo "FAILED: $brand"
		FAILED+=("$brand")
	fi
done

echo ""
if [ "${#FAILED[@]}" -eq 0 ]; then
	echo "All deploys completed successfully."
else
	echo "Completed with failures: ${FAILED[*]}"
	exit 1
fi
