<?php
/**
 * JSON snapshot for external monitoring: GET /api/telemetry_snapshot?token=...
 * Also: header X-Telemetry-Token
 */
require_once ROOT_DIR . 'functions/site_telemetry.php';

$token = site_telemetry_request_token();
if (!site_telemetry_token_matches($token)) {
	$api = array(
		'ok' => false,
		'error' => 'unauthorized',
		'hint' => 'Enable telemetry + endpoint in admin, set token, pass ?token= or X-Telemetry-Token',
	);
} else {
	$lim = isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 0;
	$opts = array();
	if ($lim > 0) {
		$opts['limit'] = $lim;
	}
	$tlim = isset($_REQUEST['translation_limit']) ? (int)$_REQUEST['translation_limit'] : 0;
	if ($tlim > 0) {
		$opts['translation_limit'] = $tlim;
	}
	$api = site_telemetry_collect_snapshot($opts);
}
