#!/usr/bin/env bash
# Deploy one GGCMS site: build + rsync/lftp.
#
# Usage:
#   ./ggcms/scripts/deploy_site.sh chickenroad
#   ./ggcms/scripts/deploy_site.sh aviator-log-in --reset

set -euo pipefail

GGCMS_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$GGCMS_ROOT/scripts/deploy_lib.sh"

# Brand -> domain map via case (portable: no bash 4 associative arrays, so this
# runs on macOS's stock bash 3.2 as well as Linux bash 4+).
brand_domain() {
	case "$1" in
		chickenroad) echo "chickenroad.run" ;;
		aviator-log-in) echo "aviator-log-in.com" ;;
		powerballjackpot) echo "powerballjackpot.run" ;;
		*) echo "" ;;
	esac
}

BRAND="${1:-}"
DOMAIN="$(brand_domain "$BRAND")"
if [ -z "$BRAND" ] || [ -z "$DOMAIN" ]; then
	echo "Usage: $0 <chickenroad|aviator-log-in|powerballjackpot> [deploy flags]"
	exit 1
fi
shift

run_ggcms_deploy "$BRAND" "$DOMAIN" "$@"
