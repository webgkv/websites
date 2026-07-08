#!/usr/bin/env bash
# GGCMS deploy library — build standalone site, then rsync/lftp to server.
#
# Called from deploy_site.sh with: run_ggcms_deploy <brand> <domain_title> "$@"

deploy_print_usage() {
	cat <<'EOF'
GGCMS deploy — build a standalone site and upload it to the server.

Entry points:
  ./deploy.sh --<brand> [flags]              Deploy one site (recommended)
  ./deploy.sh --all [flags]                  Deploy every site, one by one
  ./ggcms/scripts/deploy_site.sh <brand> [flags]   Low-level worker (same flags)

Brands:
  --chickenroad         chickenroad.run
  --ice-fish           ice-fish.run
  --aviator-log-in      aviator-log-in.com
  --powerballjackpot    powerballjackpot.run
  --all                 all of the above, in order

Deploy flags (pass after the brand; forwarded to the per-site upload step):
  --reset               Re-sync by file size (rsync without --only-newer).
                        Use after deploy glitches or when timestamps are wrong.
  --transfer-all        With --reset: force re-upload of every file
                        (rsync --ignore-times). Slow; use for a full refresh.
  --delete-remote       Remove remote files that no longer exist locally
                        (rsync --delete / lftp mirror -e).
  --no-delete-remote    Keep remote-only files even if USE_MIRROR_DELETE=1
                        in deploy.ftp.local.
  --extras_plus         Also upload server-editable files that are skipped by
                        default: robots.txt, .htaccess, assets/css/*.css.
                        Use when intentionally pushing base styles/config from repo.

Default upload mode (no flags):
  Incremental — only new or changed files (--only-newer). Remote-only files
  are kept unless --delete-remote or USE_MIRROR_DELETE is enabled.

Skipped on normal deploy (live on server; edit via admin SEO → Site CSS / robots):
  robots.txt, .htaccess, assets/css/*.css

Per-site config (ggcms/sites/<brand>/deploy.ftp.local):
  HOST                  Server hostname (required)
  USER                  SSH/FTP user (required)
  REMOTE_PATH           Document root on server (required)
  SSH_KEY               Path to SSH private key (recommended; PROTO=sftp)
  PORT                  SSH/FTP port (default 22)
  PROTO                 sftp or ftp (default sftp when SSH_KEY is set)
  PASS                  FTP password (only if SSH_KEY is not used)
  LOCAL_PATH            Local upload dir (default: ggcms/build/<brand>/site)
  USE_MIRROR_DELETE     1/true to mirror-delete by default (overridable)

Build step (always runs before upload):
  ggcms/scripts/build_site.sh <brand>  →  ggcms/build/<brand>/site/

Examples:
  ./deploy.sh --chickenroad
  ./deploy.sh --aviator-log-in --reset
  ./deploy.sh --chickenroad --extras_plus
  ./deploy.sh --powerballjackpot --reset --transfer-all
  ./deploy.sh --all --reset --transfer-all --delete-remote
  ./deploy.sh --help
  ./ggcms/scripts/deploy_site.sh chickenroad --reset

EOF
}

# Server-editable files: excluded from normal deploy; use --extras_plus to upload.
deploy_protected_paths_note() {
	echo "robots.txt, .htaccess, assets/css/*.css"
}

deploy_rsync_upload_protected() {
	local SSH_OPTS="$1" LOCAL_PATH="$2" REMOTE="$3" RSYNC_OPTS="$4"
	local has_any=0
	[ -f "$LOCAL_PATH/robots.txt" ] && has_any=1
	[ -f "$LOCAL_PATH/.htaccess" ] && has_any=1
	[ -d "$LOCAL_PATH/assets/css" ] && has_any=1
	if [ "$has_any" -eq 0 ]; then
		echo "extras_plus: no protected files found locally — skip"
		return 0
	fi
	echo "extras_plus: uploading protected files..."
	rsync -a -z $RSYNC_OPTS -e "ssh $SSH_OPTS" \
		--include 'robots.txt' \
		--include '.htaccess' \
		--include 'assets/' \
		--include 'assets/css/' \
		--include 'assets/css/***' \
		--exclude '*' \
		"$LOCAL_PATH/" "$REMOTE"
}

deploy_lftp_upload_protected() {
	local SCHEME="$1" USER="$2" PASS="$3" LOCAL_PATH="$4" REMOTE_PATH="$5"
	local ONLY_NEWER="$6" TRANSFER_ALL="$7"
	local has_any=0
	[ -f "$LOCAL_PATH/robots.txt" ] && has_any=1
	[ -f "$LOCAL_PATH/.htaccess" ] && has_any=1
	[ -d "$LOCAL_PATH/assets/css" ] && has_any=1
	if [ "$has_any" -eq 0 ]; then
		echo "extras_plus: no protected files found locally — skip"
		return 0
	fi
	echo "extras_plus: uploading protected files..."
	local LFTP_CMDS="set ssl:verify-certificate no
set net:timeout 30
set net:max-retries 3
lcd $LOCAL_PATH
cd $REMOTE_PATH"
	if [ -f "$LOCAL_PATH/robots.txt" ]; then
		LFTP_CMDS="$LFTP_CMDS
put -O . robots.txt"
	fi
	if [ -f "$LOCAL_PATH/.htaccess" ]; then
		LFTP_CMDS="$LFTP_CMDS
put -O . .htaccess"
	fi
	if [ -d "$LOCAL_PATH/assets/css" ]; then
		LFTP_CMDS="$LFTP_CMDS
mirror -R assets/css assets/css $ONLY_NEWER $TRANSFER_ALL --verbose"
	fi
	LFTP_CMDS="$LFTP_CMDS
quit"
	printf '%s\n' "$LFTP_CMDS" | lftp -u "$USER,$PASS" "$SCHEME"
}

run_ggcms_deploy() {
	local BRAND="$1"
	local TITLE="$2"
	shift 2

	local GGCMS_ROOT SCRIPT_DIR REPO_ROOT
	GGCMS_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
	REPO_ROOT="$(cd "$GGCMS_ROOT/.." && pwd)"

	local SITE_DIR="$GGCMS_ROOT/sites/$BRAND"
	local BUILD_DIR="$GGCMS_ROOT/build/$BRAND/site"

	cd "$SITE_DIR"

	# 1) Build full standalone site.
	echo "Building $BRAND..."
	"$GGCMS_ROOT/scripts/build_site.sh" "$BRAND"

	# 2) Parse flags.
	local FULL_RESET=0 TRANSFER_ALL_FLAG=0 DELETE_REMOTE_OVERRIDE="" EXTRAS_PLUS=0
	local arg
	for arg in "$@"; do
		case "$arg" in
			--reset) FULL_RESET=1 ;;
			--transfer-all) TRANSFER_ALL_FLAG=1 ;;
			--delete-remote) DELETE_REMOTE_OVERRIDE=1 ;;
			--no-delete-remote) DELETE_REMOTE_OVERRIDE=0 ;;
			--extras_plus) EXTRAS_PLUS=1 ;;
		esac
	done

	# 3) Load deploy config.
	if [ ! -f deploy.ftp.local ]; then
		echo "Error: deploy.ftp.local not found in $SITE_DIR"
		echo "Copy deploy.ftp.example to deploy.ftp.local and configure."
		return 1
	fi
	# shellcheck disable=SC1091
	source deploy.ftp.local

	# Expand a leading ~ so the same deploy.ftp.local works on macOS and Linux
	# (e.g. SSH_KEY=~/.ssh/webgkv resolves per-machine).
	case "${SSH_KEY:-}" in "~/"*) SSH_KEY="$HOME/${SSH_KEY#\~/}" ;; esac
	case "${LOCAL_PATH:-}" in "~/"*) LOCAL_PATH="$HOME/${LOCAL_PATH#\~/}" ;; esac

	# Default LOCAL_PATH to build output if not set.
	if [ -z "${LOCAL_PATH:-}" ]; then
		LOCAL_PATH="$BUILD_DIR"
	fi

	[ -z "$HOST" ] && { echo "Error: set HOST in deploy.ftp.local."; return 1; }
	[ -z "$USER" ] && { echo "Error: set USER in deploy.ftp.local."; return 1; }
	if [ -z "${SSH_KEY:-}" ]; then
		[ -z "${PASS:-}" ] && { echo "Error: set PASS or SSH_KEY in deploy.ftp.local."; return 1; }
	else
		[ ! -f "$SSH_KEY" ] && { echo "Error: SSH_KEY not found: $SSH_KEY"; return 1; }
		[ -z "${PROTO:-}" ] && PROTO="sftp"
		[ "$PROTO" != "sftp" ] && { echo "Error: SSH_KEY requires PROTO=sftp."; return 1; }
	fi
	[ ! -d "$LOCAL_PATH" ] && { echo "Error: LOCAL_PATH missing: $LOCAL_PATH"; return 1; }
	[ -z "${REMOTE_PATH:-}" ] && { echo "Error: set REMOTE_PATH in deploy.ftp.local."; return 1; }

	[ -z "${PROTO:-}" ] && PROTO="sftp"
	local SCHEME="$PROTO://$HOST"
	[ -n "${PORT:-}" ] && SCHEME="$PROTO://$HOST:$PORT"

	echo "=============================================="
	echo "Deploy $TITLE ($PROTO)"
	echo "=============================================="
	echo "Build:      $BUILD_DIR"
	echo "Server:     $SCHEME"
	[ -n "${SSH_KEY:-}" ] && echo "Auth:       SSH key $SSH_KEY"
	echo "Local:      $LOCAL_PATH"
	echo "Remote:     $REMOTE_PATH"
	echo "----------------------------------------------"

	local DELETE_REMOTE=0
	if [ -n "${USE_MIRROR_DELETE:-}" ]; then
		case "$USE_MIRROR_DELETE" in
			1|true|TRUE|yes|YES|on|ON) DELETE_REMOTE=1 ;;
			0|false|FALSE|no|NO|off|OFF) DELETE_REMOTE=0 ;;
		esac
	fi
	[ -n "$DELETE_REMOTE_OVERRIDE" ] && DELETE_REMOTE="$DELETE_REMOTE_OVERRIDE"

	local ONLY_NEWER TRANSFER_ALL RESET_MODE
	if [ "$FULL_RESET" = "1" ]; then
		ONLY_NEWER=""
		if [ "$TRANSFER_ALL_FLAG" = "1" ]; then
			TRANSFER_ALL="--transfer-all"; RESET_MODE="full re-upload"
		else
			TRANSFER_ALL=""; RESET_MODE="diff by size (--reset)"
		fi
	else
		ONLY_NEWER="--only-newer"; TRANSFER_ALL=""; RESET_MODE="new/changed only"
	fi

	echo "Mode:       $RESET_MODE"
	echo "Delete:     $([ "$DELETE_REMOTE" = "1" ] && echo yes || echo no)"
	if [ "$EXTRAS_PLUS" = "1" ]; then
		echo "Protected:  upload ($(deploy_protected_paths_note))"
	else
		echo "Protected:  skip ($(deploy_protected_paths_note))"
	fi
	echo "----------------------------------------------"

	if [ -n "${SSH_KEY:-}" ]; then
		local SSH_OPTS="-i $SSH_KEY -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=accept-new"
		[ -n "${PORT:-}" ] && [ "$PORT" != "22" ] && SSH_OPTS="$SSH_OPTS -p $PORT"
		local RSYNC_EXCLUDE=(--exclude '.DS_Store' --exclude '.git' --exclude '*.md' --exclude '*.log' \
			--exclude 'node_modules' --exclude 'venv' --exclude '.venv' --exclude 'env' --exclude '.env' \
			--exclude 'files/media/***' \
			--exclude 'robots.txt' --exclude '.htaccess' --exclude 'assets/css/***')
		local REMOTE="$USER@$HOST:$REMOTE_PATH/"
		local RSYNC_OPTS="-v"
		[ "$FULL_RESET" = "1" ] && [ "$TRANSFER_ALL_FLAG" = "1" ] && RSYNC_OPTS="$RSYNC_OPTS --ignore-times"
		[ "$DELETE_REMOTE" = "1" ] && RSYNC_OPTS="$RSYNC_OPTS --delete"
		command -v rsync &>/dev/null || { echo "Error: rsync not found"; return 1; }
		rsync -a -z $RSYNC_OPTS -e "ssh $SSH_OPTS" "${RSYNC_EXCLUDE[@]}" "$LOCAL_PATH/" "$REMOTE"
		if [ "$EXTRAS_PLUS" = "1" ]; then
			deploy_rsync_upload_protected "$SSH_OPTS" "$LOCAL_PATH" "$REMOTE" "$RSYNC_OPTS"
		fi
	else
		command -v lftp &>/dev/null || { echo "Error: lftp not found"; return 1; }
		local MIRROR_OPTS="-R $ONLY_NEWER $TRANSFER_ALL --verbose"
		[ "$DELETE_REMOTE" = "1" ] && MIRROR_OPTS="-R -e $ONLY_NEWER $TRANSFER_ALL --verbose"
		MIRROR_OPTS="$MIRROR_OPTS --exclude \\.DS_Store --exclude \\.git --exclude \\.md\$ --exclude \\.log\$"
		MIRROR_OPTS="$MIRROR_OPTS --exclude '^node_modules' --exclude '^venv' --exclude '^files/media/'"
		MIRROR_OPTS="$MIRROR_OPTS --exclude '^robots\\.txt\$' --exclude '^\\.htaccess\$' --exclude '^assets/css/'"
		lftp -u "$USER,$PASS" "$SCHEME" <<EOF
set ssl:verify-certificate no
set net:timeout 30
set net:max-retries 3
mirror $MIRROR_OPTS "$LOCAL_PATH" "$REMOTE_PATH"
quit
EOF
		if [ "$EXTRAS_PLUS" = "1" ]; then
			deploy_lftp_upload_protected "$SCHEME" "$USER" "$PASS" "$LOCAL_PATH" "$REMOTE_PATH" "$ONLY_NEWER" "$TRANSFER_ALL"
		fi
	fi

	echo ""
	if [ "$EXTRAS_PLUS" != "1" ] && [ -d "$LOCAL_PATH/assets/css" ]; then
		echo "Note: assets/css/*.css were NOT uploaded (SEO edits live on server)."
		echo "      To push CSS from repo, re-run with --extras_plus"
		echo ""
	fi
	echo "=============================================="
	echo "Deploy complete: $TITLE"
	echo "=============================================="
}
