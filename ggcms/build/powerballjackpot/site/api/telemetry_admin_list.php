<?php
/**
 * Admin Content list diagnostics: effective sort, SQL, first/last page ids (same token as snapshot).
 *
 * GET /api/telemetry_admin_list?token=...&section=content_casinos
 *   section: content_casinos | content_blog | content_guides | content_games
 * Optional: o, s, n, c, search, search_id, category, sample_limit (5–50, default 20)
 *
 * Header X-Telemetry-Token works instead of ?token=.
 */
require_once ROOT_DIR . 'functions/site_telemetry.php';
require_once ROOT_DIR . 'functions/site_telemetry_admin_list.php';

$token = site_telemetry_request_token();
if (!site_telemetry_token_matches($token)) {
	$api = array(
		'ok' => false,
		'error' => 'unauthorized',
		'hint' => 'Enable telemetry + JSON snapshot API, pass ?token= or X-Telemetry-Token (same as telemetry_snapshot)',
	);
} else {
	$params = array_merge($_GET, $_POST);
	$api = site_telemetry_admin_list_collect($params);
}
