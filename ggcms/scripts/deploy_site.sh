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

declare -A DOMAINS=(
	[chickenroad]=chickenroad.run
	[aviator-log-in]=aviator-log-in.com
	[powerballjackpot]=powerballjackpot.run
)

BRAND="${1:-}"
if [ -z "$BRAND" ] || [ -z "${DOMAINS[$BRAND]:-}" ]; then
	echo "Usage: $0 <chickenroad|aviator-log-in|powerballjackpot> [deploy flags]"
	exit 1
fi
shift

run_ggcms_deploy "$BRAND" "${DOMAINS[$BRAND]}" "$@"
