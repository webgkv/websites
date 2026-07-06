<?php
/**
 * On-demand SEO report: DB / SEO Monitor export vs live HTML (same auth as telemetry_snapshot).
 *
 * GET /api/telemetry_page_seo?token=...
 *   &url=https://example.com/en/casinos/1win-aviator/
 *   OR &entity=casino_articles&entity_id=123
 * Optional: &lang_id=2  &fetch=0  &normalize=1
 *
 * Header X-Telemetry-Token works instead of ?token=.
 *
 * - fetch=0 — skip HTTP fetch of public page (DB/export only).
 * - normalize=1 — also run seo_monitor_list_row_issue_scan (may write trimmed meta to DB, same as admin).
 */
require_once ROOT_DIR . 'functions/site_telemetry.php';
require_once ROOT_DIR . 'functions/site_telemetry_page_seo.php';

$token = site_telemetry_request_token();
if (!site_telemetry_token_matches($token)) {
	$api = array(
		'ok' => false,
		'error' => 'unauthorized',
		'hint' => 'Enable telemetry + endpoint in admin, set token, pass ?token= or X-Telemetry-Token (same as telemetry_snapshot)',
	);
} else {
	$api = site_telemetry_page_seo_collect(array(
		'url' => isset($_REQUEST['url']) ? (string)$_REQUEST['url'] : '',
		'entity' => isset($_REQUEST['entity']) ? (string)$_REQUEST['entity'] : '',
		'entity_id' => isset($_REQUEST['entity_id']) ? (int)$_REQUEST['entity_id'] : 0,
		'lang_id' => isset($_REQUEST['lang_id']) ? (int)$_REQUEST['lang_id'] : 0,
		'fetch' => isset($_REQUEST['fetch']) ? (string)$_REQUEST['fetch'] : '1',
		'normalize' => isset($_REQUEST['normalize']) ? (string)$_REQUEST['normalize'] : '0',
	));
}
