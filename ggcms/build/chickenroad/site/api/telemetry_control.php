<?php
/**
 * Token JSON control API (autopilot tick, process jobs, diagnostics).
 * POST /api/telemetry_control?token=...
 * Body: {"action":"autopilot_tick","process_jobs":3,"log_limit":50}
 * Same token as snapshot; requires control_enabled in admin Telemetry settings.
 * SEO meta write: {"action":"seo_page_meta_patch","entity":"pages","entity_id":3,"lang_id":1,"fields":{"title":"…","description":"…"},"dry_run":false}
 */
require_once ROOT_DIR . 'functions/site_telemetry.php';

$params = array();
if (isset($_SERVER['CONTENT_TYPE']) && stripos((string)$_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
	$raw = file_get_contents('php://input');
	if (is_string($raw) && strlen($raw) > 0 && strlen($raw) < 10485760) {
		$dec = json_decode($raw, true);
		if (is_array($dec)) {
			$params = $dec;
		}
	}
}
$token = site_telemetry_request_token();
if ($token === '' && !empty($params['token'])) {
	$token = trim((string)$params['token']);
}
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
if ($action === '' && !empty($params['action'])) {
	$action = trim((string)$params['action']);
}
foreach (array('token', 'action') as $rk) {
	if (isset($params[$rk])) {
		unset($params[$rk]);
	}
}
foreach (array('limit', 'translation_limit', 'log_limit', 'process_jobs', 'count', 'job_id', 'entity', 'entity_id', 'src_lang', 'dst_lang', 'lang_id', 'priority', 'max_steps', 'max_seconds', 'enqueue_translate_cluster', 'stop_on_ready') as $gk) {
	if (isset($_GET[$gk])) {
		$params[$gk] = $_GET[$gk];
	}
}

if (!site_telemetry_control_allowed($token)) {
	$api = array(
		'ok' => false,
		'error' => 'unauthorized',
		'hint' => 'Enable telemetry + endpoint + control API; set token; pass ?token=, X-Telemetry-Token, or JSON token',
	);
} else {
	$api = site_telemetry_control_dispatch($action, $params);
}
