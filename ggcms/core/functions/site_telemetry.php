<?php
/**
 * Site-wide telemetry: events table, request sampling, token snapshot API.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

function site_telemetry_defaults() {
	return array(
		'enabled' => 0,
		'endpoint_enabled' => 0,
		'control_enabled' => 0,
		'auth_token' => '',
		'retention_days' => 7,
		'request_sample_pct' => 10,
		'request_slow_ms' => 2000,
		'snapshot_limit' => 25,
	);
}

function site_telemetry_generate_token() {
	if (function_exists('random_bytes')) {
		try {
			return bin2hex(random_bytes(24));
		} catch (Exception $e) {
		}
	}
	return sha1(uniqid('site-telemetry-', true) . mt_rand());
}

function site_telemetry_variable_upsert($key, $value) {
	if ($key === '' || @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return false;
	}
	$row = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
	if ($row && !empty($row['id'])) {
		return mysql_fn('update', 'variables', array('value' => (string)$value), " AND id=" . (int)$row['id'] . " ");
	}
	return mysql_fn('insert', 'variables', array('key' => (string)$key, 'value' => (string)$value));
}

function site_telemetry_load_settings() {
	$cfg = site_telemetry_defaults();
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return $cfg;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='site_telemetry_settings' LIMIT 1", 'row');
	if ($row && isset($row['value']) && (string)$row['value'] !== '') {
		$dec = json_decode((string)$row['value'], true);
		if (is_array($dec)) {
			$cfg = array_merge($cfg, $dec);
		}
	}
	$cfg['enabled'] = !empty($cfg['enabled']) ? 1 : 0;
	$cfg['endpoint_enabled'] = !empty($cfg['endpoint_enabled']) ? 1 : 0;
	$cfg['control_enabled'] = !empty($cfg['control_enabled']) ? 1 : 0;
	$cfg['auth_token'] = trim((string)(isset($cfg['auth_token']) ? $cfg['auth_token'] : ''));
	$cfg['retention_days'] = max(1, min(90, (int)(isset($cfg['retention_days']) ? $cfg['retention_days'] : 7)));
	$cfg['request_sample_pct'] = max(0, min(100, (int)(isset($cfg['request_sample_pct']) ? $cfg['request_sample_pct'] : 10)));
	$cfg['request_slow_ms'] = max(100, min(60000, (int)(isset($cfg['request_slow_ms']) ? $cfg['request_slow_ms'] : 2000)));
	$cfg['snapshot_limit'] = max(5, min(100, (int)(isset($cfg['snapshot_limit']) ? $cfg['snapshot_limit'] : 25)));
	return $cfg;
}

function site_telemetry_save_settings(array $cfg) {
	$cfg = array_merge(site_telemetry_defaults(), $cfg);
	$cfg['enabled'] = !empty($cfg['enabled']) ? 1 : 0;
	$cfg['endpoint_enabled'] = !empty($cfg['endpoint_enabled']) ? 1 : 0;
	$cfg['control_enabled'] = !empty($cfg['control_enabled']) ? 1 : 0;
	$cfg['auth_token'] = trim((string)$cfg['auth_token']);
	$cfg['retention_days'] = max(1, min(90, (int)$cfg['retention_days']));
	$cfg['request_sample_pct'] = max(0, min(100, (int)$cfg['request_sample_pct']));
	$cfg['request_slow_ms'] = max(100, min(60000, (int)$cfg['request_slow_ms']));
	$cfg['snapshot_limit'] = max(5, min(100, (int)$cfg['snapshot_limit']));
	site_telemetry_variable_upsert('site_telemetry_settings', json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	return $cfg;
}

function site_telemetry_ensure_tables() {
	static $done = false;
	if ($done) {
		return true;
	}
	if (!function_exists('mysql_select') || !function_exists('mysql_fn')) {
		return false;
	}
	$done = true;
	if (@mysql_select("SHOW TABLES LIKE 'site_telemetry_events'", 'num_rows') === 0) {
		mysql_fn('query', "CREATE TABLE IF NOT EXISTS `site_telemetry_events` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`channel` varchar(64) NOT NULL DEFAULT '',
			`event_type` varchar(64) NOT NULL DEFAULT '',
			`status` varchar(32) NOT NULL DEFAULT '',
			`request_id` varchar(64) NOT NULL DEFAULT '',
			`source` varchar(64) NOT NULL DEFAULT '',
			`module` varchar(64) NOT NULL DEFAULT '',
			`entity` varchar(64) NOT NULL DEFAULT '',
			`entity_id` int NOT NULL DEFAULT 0,
			`duration_ms` int NOT NULL DEFAULT 0,
			`http_code` int NOT NULL DEFAULT 0,
			`payload` mediumtext NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_created_at` (`created_at`),
			KEY `idx_channel_created` (`channel`,`created_at`),
			KEY `idx_status_created` (`status`,`created_at`),
			KEY `idx_source_created` (`source`,`created_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}
	return true;
}

function site_telemetry_request_id() {
	static $rid = null;
	if ($rid !== null) {
		return $rid;
	}
	$rid = substr(site_telemetry_generate_token(), 0, 16);
	return $rid;
}

function site_telemetry_is_secret_key($key) {
	return (bool)preg_match('/token|secret|password|authorization|cookie|api[_-]?key|bearer/i', (string)$key);
}

function site_telemetry_sanitize($value, $key = '', $depth = 0) {
	if ($depth > 6) {
		return '[depth-limit]';
	}
	if (is_array($value)) {
		$out = array();
		$i = 0;
		foreach ($value as $k => $v) {
			$i++;
			if ($i > 80) {
				$out['__truncated__'] = 'array-limit';
				break;
			}
			$out[$k] = site_telemetry_sanitize($v, (string)$k, $depth + 1);
		}
		return $out;
	}
	if (is_object($value)) {
		return site_telemetry_sanitize((array)$value, $key, $depth + 1);
	}
	if (site_telemetry_is_secret_key($key)) {
		$s = trim((string)$value);
		if ($s === '') {
			return '';
		}
		return substr($s, 0, 4) . '...[masked]';
	}
	if (is_string($value)) {
		$s = trim($value);
		if (stripos($s, 'bearer ') === 0) {
			return 'Bearer ...[masked]';
		}
		if (strlen($s) > 4000) {
			$s = substr($s, 0, 4000) . '...[truncated]';
		}
		return $s;
	}
	if (is_bool($value)) {
		return $value ? 1 : 0;
	}
	return $value;
}

function site_telemetry_cleanup_if_due() {
	static $done = false;
	if ($done || @mysql_select("SHOW TABLES LIKE 'site_telemetry_events'", 'num_rows') === 0) {
		return;
	}
	$done = true;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return;
	}
	$cfg = site_telemetry_load_settings();
	$row = mysql_select("SELECT value FROM variables WHERE `key`='site_telemetry_cleanup_last_run' LIMIT 1", 'row');
	$last = $row && !empty($row['value']) ? strtotime((string)$row['value']) : 0;
	if ($last && (time() - $last) < 3600 * 12) {
		return;
	}
	mysql_fn('query', "DELETE FROM site_telemetry_events WHERE created_at < DATE_SUB(NOW(), INTERVAL " . (int)$cfg['retention_days'] . " DAY)");
	site_telemetry_variable_upsert('site_telemetry_cleanup_last_run', date('Y-m-d H:i:s'));
}

function site_telemetry_log_event($channel, $event_type, $status, $payload = array(), $meta = array()) {
	if (!function_exists('mysql_select') || !function_exists('mysql_fn')) {
		return false;
	}
	$cfg = site_telemetry_load_settings();
	if (empty($cfg['enabled'])) {
		return false;
	}
	site_telemetry_ensure_tables();
	site_telemetry_cleanup_if_due();
	$meta = is_array($meta) ? $meta : array();
	$payload = site_telemetry_sanitize(is_array($payload) ? $payload : array('value' => $payload));
	return mysql_fn('insert', 'site_telemetry_events', array(
		'channel' => substr(trim((string)$channel), 0, 64),
		'event_type' => substr(trim((string)$event_type), 0, 64),
		'status' => substr(trim((string)$status), 0, 32),
		'request_id' => substr((string)(isset($meta['request_id']) ? $meta['request_id'] : site_telemetry_request_id()), 0, 64),
		'source' => substr((string)(isset($meta['source']) ? $meta['source'] : ''), 0, 64),
		'module' => substr((string)(isset($meta['module']) ? $meta['module'] : ''), 0, 64),
		'entity' => substr((string)(isset($meta['entity']) ? $meta['entity'] : ''), 0, 64),
		'entity_id' => (int)(isset($meta['entity_id']) ? $meta['entity_id'] : 0),
		'duration_ms' => (int)(isset($meta['duration_ms']) ? $meta['duration_ms'] : 0),
		'http_code' => (int)(isset($meta['http_code']) ? $meta['http_code'] : 0),
		'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'created_at' => date('Y-m-d H:i:s'),
	));
}

function site_telemetry_request_begin($source, $meta = array()) {
	if (!function_exists('register_shutdown_function')) {
		return;
	}
	$cfg = site_telemetry_load_settings();
	if (empty($cfg['enabled'])) {
		return;
	}
	$GLOBALS['site_telemetry_request_state'] = array(
		'started_at' => microtime(true),
		'source' => (string)$source,
		'meta' => is_array($meta) ? $meta : array(),
		'request_id' => site_telemetry_request_id(),
	);
	static $registered = false;
	if (!$registered) {
		$registered = true;
		register_shutdown_function('site_telemetry_request_finish');
	}
}

function site_telemetry_request_finish() {
	static $done = false;
	if ($done || empty($GLOBALS['site_telemetry_request_state']) || !is_array($GLOBALS['site_telemetry_request_state'])) {
		return;
	}
	$done = true;
	$state = $GLOBALS['site_telemetry_request_state'];
	$cfg = site_telemetry_load_settings();
	$duration_ms = (int)round((microtime(true) - (float)$state['started_at']) * 1000);
	$err = error_get_last();
	$fatal = false;
	if (is_array($err) && isset($err['type'])) {
		$fatal_types = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
		$fatal = in_array((int)$err['type'], $fatal_types, true);
	}
	$sample = mt_rand(1, 100) <= (int)$cfg['request_sample_pct'];
	$status = 'ok';
	if ($fatal) {
		$status = 'fatal';
	} elseif ($duration_ms >= (int)$cfg['request_slow_ms']) {
		$status = 'slow';
	} elseif (!$sample) {
		return;
	}
	$payload = array_merge(is_array($state['meta']) ? $state['meta'] : array(), array(
		'method' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : '',
		'uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
		'query' => isset($_SERVER['QUERY_STRING']) ? (string)$_SERVER['QUERY_STRING'] : '',
		'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '',
		'php_sapi' => php_sapi_name(),
		'memory_peak' => function_exists('memory_get_peak_usage') ? (int)memory_get_peak_usage(true) : 0,
	));
	if ($fatal && is_array($err)) {
		$payload['fatal'] = array(
			'type' => (int)$err['type'],
			'message' => isset($err['message']) ? (string)$err['message'] : '',
			'file' => isset($err['file']) ? (string)$err['file'] : '',
			'line' => isset($err['line']) ? (int)$err['line'] : 0,
		);
	}
	$http_code = function_exists('http_response_code') ? (int)http_response_code() : 0;
	site_telemetry_log_event('request', 'http_request', $status, $payload, array(
		'request_id' => isset($state['request_id']) ? (string)$state['request_id'] : site_telemetry_request_id(),
		'source' => isset($state['source']) ? (string)$state['source'] : '',
		'module' => isset($state['meta']['module']) ? (string)$state['meta']['module'] : '',
		'duration_ms' => $duration_ms,
		'http_code' => $http_code,
	));
}

function site_telemetry_request_token() {
	if (!empty($_GET['token'])) {
		return trim((string)$_GET['token']);
	}
	if (!empty($_SERVER['HTTP_X_TELEMETRY_TOKEN'])) {
		return trim((string)$_SERVER['HTTP_X_TELEMETRY_TOKEN']);
	}
	return '';
}

function site_telemetry_token_matches($token) {
	$cfg = site_telemetry_load_settings();
	$token = trim((string)$token);
	if (empty($cfg['enabled']) || empty($cfg['endpoint_enabled']) || $token === '' || $cfg['auth_token'] === '') {
		return false;
	}
	if (function_exists('hash_equals')) {
		return hash_equals((string)$cfg['auth_token'], $token);
	}
	return (string)$cfg['auth_token'] === $token;
}

/**
 * Token + telemetry + endpoint + explicit control flag (remote autopilot / queue).
 */
function site_telemetry_control_allowed($token) {
	$cfg = site_telemetry_load_settings();
	$token = trim((string)$token);
	if (empty($cfg['enabled']) || empty($cfg['endpoint_enabled']) || empty($cfg['control_enabled']) || $token === '' || $cfg['auth_token'] === '') {
		return false;
	}
	if (function_exists('hash_equals')) {
		return hash_equals((string)$cfg['auth_token'], $token);
	}
	return (string)$cfg['auth_token'] === $token;
}

/**
 * Recent translation + AI lines for control API responses (tail after tick).
 *
 * @return array<string,mixed>
 */
function site_telemetry_control_log_tail($limit) {
	$limit = max(5, min(150, (int)$limit));
	$out = array(
		'translations' => array(),
		'ai_events' => site_telemetry_recent_events("channel='ai'", min(40, $limit)),
	);
	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0) {
		$rows = mysql_select("
			SELECT id, created_at, level, message, context
			FROM system_logs
			WHERE channel IN ('translations','jobs')
			ORDER BY id DESC
			LIMIT " . (int)$limit, 'rows') ?: array();
		foreach ($rows as &$log) {
			$log['context'] = !empty($log['context']) ? @json_decode((string)$log['context'], true) : array();
			if (!is_array($log['context'])) {
				$log['context'] = array();
			}
			$log['context'] = site_telemetry_sanitize($log['context']);
		}
		unset($log);
		$out['translations'] = $rows;
	}
	return $out;
}

/**
 * Allowed entities for API-enqueued translate jobs (align with job runner).
 *
 * @return array<int,string>
 */
function site_telemetry_control_pipeline_entities_allowed() {
	return array('blog', 'pages', 'guides', 'games', 'casino_articles', 'menu', 'system_dictionary');
}

/**
 * Snapshot of one content_i18n row (lengths only, no full HTML).
 *
 * @return array<string,mixed>|null
 */
function site_telemetry_control_pipeline_i18n_snapshot($entity, $entity_id, $lang_id) {
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
		return null;
	}
	$entity = (string)$entity;
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) {
		return null;
	}
	$row = mysql_select("
		SELECT id, entity, entity_id, lang_id, status, url, updated_at,
			CHAR_LENGTH(COALESCE(name,'')) AS name_len,
			CHAR_LENGTH(COALESCE(title,'')) AS title_len,
			CHAR_LENGTH(COALESCE(description,'')) AS description_len,
			CHAR_LENGTH(COALESCE(content,'')) AS content_len
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " AND lang_id=" . $lang_id . "
		LIMIT 1
	", 'row');
	if (!$row) {
		return array('missing' => true, 'entity' => $entity, 'entity_id' => $entity_id, 'lang_id' => $lang_id);
	}
	return $row;
}

/**
 * Recent system_logs lines mentioning this admin job id (translations channel).
 *
 * @return array<int,array<string,mixed>>
 */
function site_telemetry_control_logs_for_job($job_id, $limit) {
	$job_id = (int)$job_id;
	$limit = max(1, min(80, (int)$limit));
	if ($job_id <= 0 || @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') === 0) {
		return array();
	}
	$like = '%job#' . $job_id . '%';
	$rows = mysql_select("
		SELECT id, created_at, level, message, context
		FROM system_logs
		WHERE channel IN ('translations','jobs')
		  AND message LIKE '" . mysql_res($like) . "'
		ORDER BY id DESC
		LIMIT " . (int)$limit, 'rows') ?: array();
	foreach ($rows as &$log) {
		$log['context'] = !empty($log['context']) ? @json_decode((string)$log['context'], true) : array();
		if (!is_array($log['context'])) {
			$log['context'] = array();
		}
		$log['context'] = site_telemetry_sanitize($log['context']);
	}
	unset($log);
	return $rows;
}

/**
 * After a job finishes: job row, i18n snapshot, cluster (blog), logs.
 *
 * @param int $job_id
 * @return array<string,mixed>
 */
function site_telemetry_control_trace_job_outcome($job_id) {
	$job_id = (int)$job_id;
	$out = array(
		'trace_error' => '',
		'job' => null,
		'payload_summary' => array(),
		'content_i18n' => null,
		'cluster' => null,
		'logs' => array(),
	);
	if ($job_id <= 0) {
		$out['trace_error'] = 'invalid job_id';
		return $out;
	}
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		$out['trace_error'] = 'admin_jobs missing';
		return $out;
	}
	$job = mysql_select("
		SELECT id, module, action, status, message, payload, finished_at, started_at, created_at, updated_at, locked_at,
			TIMESTAMPDIFF(SECOND, started_at, NOW()) AS running_seconds,
			TIMESTAMPDIFF(SECOND, COALESCE(updated_at, started_at), NOW()) AS seconds_since_heartbeat
		FROM admin_jobs WHERE id=" . $job_id . " LIMIT 1
	", 'row');
	if (!$job) {
		$out['trace_error'] = 'job not found';
		return $out;
	}
	$payload = !empty($job['payload']) ? @json_decode((string)$job['payload'], true) : array();
	if (!is_array($payload)) {
		$payload = array();
	}
	$entity = isset($payload['entity']) ? (string)$payload['entity'] : '';
	$eid = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$dst = isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0;
	$out['job'] = array(
		'id' => (int)$job['id'],
		'module' => (string)$job['module'],
		'action' => (string)$job['action'],
		'status' => (string)$job['status'],
		'message' => substr((string)$job['message'], 0, 600),
		'created_at' => (string)($job['created_at'] ?? ''),
		'started_at' => (string)($job['started_at'] ?? ''),
		'updated_at' => (string)($job['updated_at'] ?? ''),
		'locked_at' => (string)($job['locked_at'] ?? ''),
		'finished_at' => (string)($job['finished_at'] ?? ''),
	);
	if ((string)$job['status'] === 'running') {
		require_once ROOT_DIR . 'functions/admin_jobs.php';
		$hb_sec = admin_jobs_translation_reap_heartbeat_seconds();
		$tot_sec = admin_jobs_translation_reap_total_seconds();
		$rs = isset($job['running_seconds']) ? (int)$job['running_seconds'] : 0;
		$sh = isset($job['seconds_since_heartbeat']) ? (int)$job['seconds_since_heartbeat'] : 0;
		$out['timing'] = array(
			'running_seconds' => $rs,
			'seconds_since_heartbeat' => $sh,
			'reap_heartbeat_after_seconds' => $hb_sec,
			'reap_total_after_seconds' => $tot_sec,
			'reap_stale_after_seconds' => $tot_sec,
			'stale_by_heartbeat' => ($sh > $hb_sec) ? 1 : 0,
			'stale_by_total_run' => ($rs > $tot_sec) ? 1 : 0,
			'stale_by_reap_rule' => ($sh > $hb_sec || $rs > $tot_sec) ? 1 : 0,
		);
	}
	$out['payload_summary'] = site_telemetry_sanitize(array(
		'entity' => $entity,
		'entity_id' => $eid,
		'src_lang' => isset($payload['src_lang']) ? (int)$payload['src_lang'] : 0,
		'dst_lang' => $dst,
		'fields' => isset($payload['fields']) && is_array($payload['fields']) ? $payload['fields'] : array(),
		'api_pipeline' => !empty($payload['api_pipeline']) ? 1 : 0,
	));
	if ($entity !== '' && $eid > 0 && $dst > 0) {
		$out['content_i18n'] = site_telemetry_control_pipeline_i18n_snapshot($entity, $eid, $dst);
	}
	if ($entity === 'blog' && $eid > 0 && file_exists(ROOT_DIR . 'functions/translation_cluster.php')) {
		require_once ROOT_DIR . 'functions/translation_cluster.php';
		if (function_exists('translation_cluster_get_state')) {
			$st = translation_cluster_get_state($entity, $eid);
			$out['cluster'] = $st ? site_telemetry_sanitize($st) : null;
		}
	}
	$out['logs'] = site_telemetry_control_logs_for_job($job_id, 40);
	return $out;
}

/**
 * Build translate payload and enqueue (same path as autopilot, no monitor gate).
 *
 * @param array<string,mixed> $p
 * @return array{ok:bool,message:string,job_id?:int}
 */
function site_telemetry_control_enqueue_translate(array $p) {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	$entity = isset($p['entity']) ? trim((string)$p['entity']) : '';
	$entity_id = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
	$src = isset($p['src_lang']) ? (int)$p['src_lang'] : 0;
	$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
	if ($entity === '' || $entity_id <= 0 || $src <= 0 || $dst <= 0) {
		return array('ok' => false, 'message' => 'entity, entity_id, src_lang, dst_lang required');
	}
	if (!in_array($entity, site_telemetry_control_pipeline_entities_allowed(), true)) {
		return array('ok' => false, 'message' => 'entity not allowed: ' . $entity);
	}
	$fields = isset($p['fields']) && is_array($p['fields']) ? $p['fields'] : array('name', 'title', 'description', 'content');
	$clean_fields = array();
	foreach ($fields as $f) {
		$f = preg_replace('/[^a-z0-9_]/i', '', (string)$f);
		if ($f !== '') {
			$clean_fields[] = $f;
		}
	}
	if ($clean_fields === array()) {
		return array('ok' => false, 'message' => 'fields empty');
	}
	$chunk_max = isset($p['chunk_max_len']) ? (int)$p['chunk_max_len'] : 0;
	if ($chunk_max <= 0 && file_exists(ROOT_DIR . 'functions/translation_autopilot.php')) {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		$cfg = translation_autopilot_load_cfg();
		$chunk_max = isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500;
	}
	if ($chunk_max <= 0) {
		$chunk_max = 2500;
	}
	$priority = isset($p['priority']) ? (int)$p['priority'] : 0;
	$payload = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'src_lang' => $src,
		'dst_lang' => $dst,
		'fields' => $clean_fields,
		'chunk_max_len' => $chunk_max,
		'order_id' => isset($p['order_id']) ? (int)$p['order_id'] : 0,
		'candidate_id' => isset($p['candidate_id']) ? (int)$p['candidate_id'] : 0,
		'autopilot' => 0,
		'api_pipeline' => 1,
	);
	if (!empty($p['metadata_normalize'])) {
		$payload['metadata_normalize'] = 1;
	}
	$jid = admin_jobs_enqueue('translations', 'translate', $payload, array('priority' => $priority));
	if (empty($jid)) {
		return array('ok' => false, 'message' => 'enqueue failed');
	}
	return array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid);
}

/**
 * Pending admin_jobs for one cluster (same ordering intent as admin_jobs_lock_next).
 *
 * @return array<int,array<string,mixed>>
 */
function site_telemetry_cluster_fetch_pending_jobs($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0 || @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return array();
	}
	// Include future scheduled_at so cluster_simulate/snapshot match admin "pending" list; run_admin_job_by_id can still lock them.
	$rows = mysql_select("
		SELECT id, action, priority, scheduled_at, payload
		FROM admin_jobs
		WHERE module='translations' AND status='pending'
		ORDER BY id ASC
	", 'rows') ?: array();
	$out = array();
	foreach ($rows as $r) {
		$p = @json_decode((string)($r['payload'] ?? ''), true);
		if (!is_array($p)) {
			continue;
		}
		if ((isset($p['entity']) ? (string)$p['entity'] : '') !== $entity) {
			continue;
		}
		if ((int)($p['entity_id'] ?? 0) !== $entity_id) {
			continue;
		}
		$out[] = $r;
	}
	$prio = array(
		'translate' => 0,
		'repair_locale' => 1,
		'translate_common_dict' => 2,
		'validate_locale' => 3,
		'translate_cluster' => 4,
		'validate_cluster' => 5,
		'cluster_pipeline' => 6,
	);
	usort($out, function ($a, $b) use ($prio) {
		$pra = (int)($a['priority'] ?? 0);
		$prb = (int)($b['priority'] ?? 0);
		if ($pra !== $prb) {
			return $prb <=> $pra;
		}
		$ea = isset($a['action']) ? (string)$a['action'] : '';
		$eb = isset($b['action']) ? (string)$b['action'] : '';
		$pa = isset($prio[$ea]) ? $prio[$ea] : 99;
		$pb = isset($prio[$eb]) ? $prio[$eb] : 99;
		if ($pa !== $pb) {
			return $pa - $pb;
		}
		return (int)($a['id'] ?? 0) - (int)($b['id'] ?? 0);
	});
	return $out;
}

/**
 * Compact cluster row + per-locale validation (blockers/title pipeline visibility).
 *
 * @return array<string,mixed>|null
 */
function site_telemetry_cluster_compact_summary($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0 || !file_exists(ROOT_DIR . 'functions/translation_cluster.php')) {
		return null;
	}
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	if (!function_exists('translation_cluster_get_state')) {
		return null;
	}
	$st = translation_cluster_get_state($entity, $entity_id);
	if (!$st || !is_array($st)) {
		return array('missing' => true, 'entity' => $entity, 'entity_id' => $entity_id);
	}
	$out = array(
		'cluster_status' => isset($st['cluster_status']) ? (string)$st['cluster_status'] : '',
		'pipeline_stage' => isset($st['pipeline_stage']) ? (string)$st['pipeline_stage'] : '',
		'ready_locales' => isset($st['ready_locales']) ? (string)$st['ready_locales'] : '',
		'total_locales' => isset($st['total_locales']) ? (string)$st['total_locales'] : '',
		'failed_locales' => isset($st['failed_locales']) ? (string)$st['failed_locales'] : '',
		'blocker_count' => isset($st['blocker_count']) ? (string)$st['blocker_count'] : '',
		'warning_count' => isset($st['warning_count']) ? (string)$st['warning_count'] : '',
		'last_error_excerpt' => isset($st['last_error_excerpt']) ? substr((string)$st['last_error_excerpt'], 0, 200) : '',
		'search_title' => isset($st['search_title']) ? substr((string)$st['search_title'], 0, 120) : '',
		'locales_compact' => array(),
	);
	$vj = isset($st['validation_json']) ? (string)$st['validation_json'] : '';
	if ($vj !== '') {
		$dec = @json_decode($vj, true);
		if (is_array($dec) && !empty($dec['locales']) && is_array($dec['locales'])) {
			foreach ($dec['locales'] as $loc) {
				if (!is_array($loc)) {
					continue;
				}
				$lid = isset($loc['lang_id']) ? (int)$loc['lang_id'] : 0;
				if ($lid <= 0) {
					continue;
				}
				$title = isset($loc['title']) ? (string)$loc['title'] : '';
				$out['locales_compact'][] = array(
					'lang_id' => $lid,
					'ok' => !empty($loc['ok']),
					'missing' => !empty($loc['missing']),
					'status' => isset($loc['status']) ? (string)$loc['status'] : '',
					'blockers' => isset($loc['blockers']) && is_array($loc['blockers']) ? $loc['blockers'] : array(),
					'warnings' => isset($loc['warnings']) && is_array($loc['warnings']) ? $loc['warnings'] : array(),
					'title_len' => $title !== '' ? mb_strlen($title, 'UTF-8') : null,
				);
			}
		}
	}
	return $out;
}

/**
 * Translation queue health: running job age vs reap rule, heartbeat (updated_at), future-scheduled pending, recent failures.
 * Helps diagnose “hangs” (long-running jobs, stale heartbeats) and why work is blocked.
 *
 * @return array<string,mixed>
 */
function site_telemetry_translation_queue_health() {
	$out = array(
		'reap_heartbeat_after_seconds' => null,
		'reap_total_after_seconds' => null,
		'reap_stale_after_seconds' => null,
		'heartbeat_stale_warn_seconds' => null,
		'server_now' => '',
		'running' => array(),
		'stale_running_count' => 0,
		'heartbeat_stale_count' => 0,
		'pending_validate_cluster' => 0,
		'hint' => '',
		'pending_future_scheduled' => array(),
		'failed_recent' => array(),
	);
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return $out;
	}
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	$hb_reap = admin_jobs_translation_reap_heartbeat_seconds();
	$tot_reap = admin_jobs_translation_reap_total_seconds();
	$out['reap_heartbeat_after_seconds'] = $hb_reap;
	$out['reap_total_after_seconds'] = $tot_reap;
	$out['reap_stale_after_seconds'] = $tot_reap;
	$out['heartbeat_stale_warn_seconds'] = max(30, (int)floor($hb_reap * 0.85));
	$now_row = @mysql_select('SELECT NOW() AS t', 'row');
	$out['server_now'] = $now_row && isset($now_row['t']) ? (string)$now_row['t'] : date('Y-m-d H:i:s');

	$run = mysql_select("
		SELECT id, action, priority, scheduled_at, created_at, started_at, updated_at, locked_at, message, payload,
			TIMESTAMPDIFF(SECOND, started_at, NOW()) AS running_seconds,
			TIMESTAMPDIFF(SECOND, COALESCE(updated_at, started_at), NOW()) AS seconds_since_heartbeat
		FROM admin_jobs
		WHERE module='translations' AND status='running'
		ORDER BY started_at ASC
		LIMIT 50
	", 'rows') ?: array();
	foreach ($run as $r) {
		$rs = isset($r['running_seconds']) ? (int)$r['running_seconds'] : 0;
		$sh = isset($r['seconds_since_heartbeat']) ? (int)$r['seconds_since_heartbeat'] : 0;
		$payload = !empty($r['payload']) ? @json_decode((string)$r['payload'], true) : array();
		if (!is_array($payload)) {
			$payload = array();
		}
		$stale_total = $rs > $tot_reap;
		$stale_hb_reap = $sh > $hb_reap;
		$stale_hb_warn = $sh > (int)$out['heartbeat_stale_warn_seconds'];
		if ($stale_total || $stale_hb_reap) {
			$out['stale_running_count']++;
		}
		if ($stale_hb_warn) {
			$out['heartbeat_stale_count']++;
		}
		$out['running'][] = array(
			'id' => (int)$r['id'],
			'action' => isset($r['action']) ? (string)$r['action'] : '',
			'priority' => isset($r['priority']) ? (int)$r['priority'] : 0,
			'started_at' => isset($r['started_at']) ? (string)$r['started_at'] : '',
			'updated_at' => isset($r['updated_at']) ? (string)$r['updated_at'] : '',
			'locked_at' => isset($r['locked_at']) ? (string)$r['locked_at'] : '',
			'message' => isset($r['message']) ? substr((string)$r['message'], 0, 400) : '',
			'running_seconds' => $rs,
			'seconds_since_heartbeat' => $sh,
			'stale_by_heartbeat_reap' => $stale_hb_reap ? 1 : 0,
			'stale_by_total_run' => $stale_total ? 1 : 0,
			'stale_by_reap_rule' => ($stale_hb_reap || $stale_total) ? 1 : 0,
			'heartbeat_stale_warn' => $stale_hb_warn ? 1 : 0,
			'payload_summary' => site_telemetry_sanitize(array(
				'entity' => isset($payload['entity']) ? (string)$payload['entity'] : '',
				'entity_id' => isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0,
				'src_lang' => isset($payload['src_lang']) ? (int)$payload['src_lang'] : 0,
				'dst_lang' => isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0,
				'candidate_id' => isset($payload['candidate_id']) ? (int)$payload['candidate_id'] : 0,
			)),
		);
	}

	$pvc = @mysql_select("
		SELECT COUNT(*) AS c FROM admin_jobs
		WHERE module='translations' AND status='pending' AND action='validate_cluster'
	", 'row');
	$out['pending_validate_cluster'] = $pvc && isset($pvc['c']) ? (int)$pvc['c'] : 0;
	if ($out['heartbeat_stale_count'] > 0 || $out['stale_running_count'] > 0) {
		$out['hint'] = 'Stale heartbeat or long gap since updated_at on running jobs (LLM/DOM can block PHP). Reap uses translation_reap_heartbeat_seconds; validate_cluster may wait while repair_locale/translate holds the worker.';
	} elseif ($out['pending_validate_cluster'] > 0) {
		$out['hint'] = 'validate_cluster is pending; it runs after earlier cluster steps finish or when a worker picks it.';
	}

	$future = mysql_select("
		SELECT id, action, priority, scheduled_at, created_at, message, payload,
			TIMESTAMPDIFF(SECOND, NOW(), scheduled_at) AS seconds_until_scheduled
		FROM admin_jobs
		WHERE module='translations' AND status='pending'
		  AND scheduled_at IS NOT NULL AND scheduled_at > NOW()
		ORDER BY scheduled_at ASC
		LIMIT 40
	", 'rows') ?: array();
	foreach ($future as $f) {
		$payload = !empty($f['payload']) ? @json_decode((string)$f['payload'], true) : array();
		if (!is_array($payload)) {
			$payload = array();
		}
		$out['pending_future_scheduled'][] = array(
			'id' => (int)$f['id'],
			'action' => isset($f['action']) ? (string)$f['action'] : '',
			'scheduled_at' => isset($f['scheduled_at']) ? (string)$f['scheduled_at'] : '',
			'seconds_until_scheduled' => isset($f['seconds_until_scheduled']) ? (int)$f['seconds_until_scheduled'] : 0,
			'message' => isset($f['message']) ? substr((string)$f['message'], 0, 200) : '',
			'payload_summary' => site_telemetry_sanitize(array(
				'entity' => isset($payload['entity']) ? (string)$payload['entity'] : '',
				'entity_id' => isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0,
			)),
		);
	}

	$fail = mysql_select("
		SELECT id, action, message, started_at, finished_at, created_at,
			TIMESTAMPDIFF(SECOND, started_at, COALESCE(finished_at, NOW())) AS run_span_seconds
		FROM admin_jobs
		WHERE module='translations' AND status='failed'
		ORDER BY id DESC
		LIMIT 15
	", 'rows') ?: array();
	foreach ($fail as $x) {
		$out['failed_recent'][] = array(
			'id' => (int)$x['id'],
			'action' => isset($x['action']) ? (string)$x['action'] : '',
			'message' => isset($x['message']) ? substr((string)$x['message'], 0, 500) : '',
			'started_at' => isset($x['started_at']) ? (string)$x['started_at'] : '',
			'finished_at' => isset($x['finished_at']) ? (string)$x['finished_at'] : '',
			'run_span_seconds' => isset($x['run_span_seconds']) ? (int)$x['run_span_seconds'] : 0,
		);
	}

	return $out;
}

/**
 * Running translation jobs for one cluster (payload entity + entity_id).
 *
 * @return array<int,array<string,mixed>>
 */
function site_telemetry_cluster_fetch_running_jobs($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0 || @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return array();
	}
	$rows = mysql_select("
		SELECT id, action, priority, scheduled_at, created_at, started_at, updated_at, locked_at, message, payload,
			TIMESTAMPDIFF(SECOND, started_at, NOW()) AS running_seconds,
			TIMESTAMPDIFF(SECOND, COALESCE(updated_at, started_at), NOW()) AS seconds_since_heartbeat
		FROM admin_jobs
		WHERE module='translations' AND status='running'
		ORDER BY started_at ASC
		LIMIT 80
	", 'rows') ?: array();
	$out = array();
	foreach ($rows as $r) {
		$p = @json_decode((string)($r['payload'] ?? ''), true);
		if (!is_array($p)) {
			continue;
		}
		if ((isset($p['entity']) ? (string)$p['entity'] : '') !== $entity) {
			continue;
		}
		if ((int)($p['entity_id'] ?? 0) !== $entity_id) {
			continue;
		}
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		$out[] = array(
			'id' => (int)$r['id'],
			'action' => isset($r['action']) ? (string)$r['action'] : '',
			'started_at' => isset($r['started_at']) ? (string)$r['started_at'] : '',
			'updated_at' => isset($r['updated_at']) ? (string)$r['updated_at'] : '',
			'message' => isset($r['message']) ? substr((string)$r['message'], 0, 400) : '',
			'running_seconds' => isset($r['running_seconds']) ? (int)$r['running_seconds'] : 0,
			'seconds_since_heartbeat' => isset($r['seconds_since_heartbeat']) ? (int)$r['seconds_since_heartbeat'] : 0,
			'dst_lang' => $dst,
		);
	}
	return $out;
}

/**
 * Enqueue translate_cluster like autopilot (all target langs from settings unless dst_langs set).
 *
 * @param array<string,mixed> $p
 * @return array{ok:bool,message:string,job_id?:int}
 */
function site_telemetry_control_enqueue_translate_cluster(array $p) {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	require_once ROOT_DIR . 'functions/translation_autopilot.php';
	$entity = isset($p['entity']) ? trim((string)$p['entity']) : 'blog';
	$entity_id = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
	$src = isset($p['src_lang']) ? (int)$p['src_lang'] : 1;
	if ($entity_id <= 0) {
		return array('ok' => false, 'message' => 'entity_id required');
	}
	$cfg = translation_autopilot_load_cfg();
	$targets = array();
	if (!empty($p['dst_langs']) && is_array($p['dst_langs'])) {
		foreach ($p['dst_langs'] as $x) {
			$t = (int)$x;
			if ($t > 0) {
				$targets[] = $t;
			}
		}
		$targets = array_values(array_unique($targets));
	} else {
		$targets = translation_autopilot_target_lang_ids($cfg);
	}
	if ($targets === array()) {
		return array('ok' => false, 'message' => 'no dst_langs');
	}
	$chunk_max = isset($p['chunk_max_len']) ? (int)$p['chunk_max_len'] : (int)(isset($cfg['chunk_max_len']) ? $cfg['chunk_max_len'] : 2500);
	if ($chunk_max <= 0) {
		$chunk_max = 2500;
	}
	$jid = admin_jobs_enqueue('translations', 'translate_cluster', array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'src_lang' => $src,
		'dst_langs' => $targets,
		'chunk_max_len' => $chunk_max,
		'autopilot' => 0,
		'api_pipeline' => 1,
	), array('priority' => isset($p['priority']) ? (int)$p['priority'] : 0));
	if (empty($jid)) {
		return array('ok' => false, 'message' => 'enqueue failed');
	}
	return array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid);
}

/**
 * Enqueue a single translation pipeline job (cluster_pipeline, validate_cluster, validate_locale, repair_locale, translate, translate_cluster).
 *
 * @param array<string,mixed> $p job_action, entity, entity_id, src_lang, dst_lang, dst_langs, validation_blockers, …
 * @return array{ok:bool,message:string,job_id?:int}
 */
function site_telemetry_control_enqueue_translation_job(array $p) {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	require_once ROOT_DIR . 'functions/translation_autopilot.php';
	$job_action = isset($p['job_action']) ? trim((string)$p['job_action']) : '';
	if ($job_action === 'translate_cluster') {
		return site_telemetry_control_enqueue_translate_cluster($p);
	}
	$entity = isset($p['entity']) ? trim((string)$p['entity']) : '';
	$entity_id = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
	$src = isset($p['src_lang']) ? (int)$p['src_lang'] : 1;
	if ($entity === '' || $entity_id <= 0) {
		return array('ok' => false, 'message' => 'entity, entity_id required');
	}
	if (!in_array($entity, site_telemetry_control_pipeline_entities_allowed(), true)) {
		return array('ok' => false, 'message' => 'entity not allowed');
	}
	$priority = isset($p['priority']) ? (int)$p['priority'] : 0;
	$enqueue_opts = array('priority' => $priority);
	if (!empty($p['scheduled_at'])) {
		$enqueue_opts['scheduled_at'] = trim((string)$p['scheduled_at']);
	}
	$cfg = translation_autopilot_load_cfg();
	$dst_langs = array();
	if (!empty($p['dst_langs']) && is_array($p['dst_langs'])) {
		foreach ($p['dst_langs'] as $x) {
			if ((int)$x > 0) {
				$dst_langs[] = (int)$x;
			}
		}
		$dst_langs = array_values(array_unique($dst_langs));
	} else {
		$dst_langs = translation_autopilot_target_lang_ids($cfg);
	}
	if ($dst_langs === array()) {
		return array('ok' => false, 'message' => 'no dst_langs (configure enabled langs / pass dst_langs)');
	}
	if ($job_action === 'validate_cluster') {
		$round = isset($p['cluster_repair_round']) ? max(0, (int)$p['cluster_repair_round']) : 0;
		$jid = admin_jobs_enqueue('translations', 'validate_cluster', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src,
			'dst_langs' => $dst_langs,
			'autopilot' => !empty($p['autopilot']) ? 1 : 0,
			'cluster_repair_round' => $round,
			'api_pipeline' => 1,
		), $enqueue_opts);
		return !empty($jid) ? array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid) : array('ok' => false, 'message' => 'enqueue failed');
	}
	if ($job_action === 'cluster_pipeline') {
		require_once ROOT_DIR . 'functions/translation_cluster.php';
		$dst_langs = translation_cluster_normalize_target_lang_ids($src, $dst_langs);
		$round = isset($p['cluster_repair_round']) ? max(0, (int)$p['cluster_repair_round']) : 0;
		$pl = array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src,
			'dst_langs' => $dst_langs,
			'autopilot' => !empty($p['autopilot']) ? 1 : 0,
			'cluster_repair_round' => $round,
			'api_pipeline' => 1,
		);
		if (isset($p['max_seconds'])) {
			$pl['max_seconds'] = (int)$p['max_seconds'];
		}
		if (isset($p['max_steps'])) {
			$pl['max_steps'] = (int)$p['max_steps'];
		}
		if (isset($p['max_idle_rounds'])) {
			$pl['max_idle_rounds'] = (int)$p['max_idle_rounds'];
		}
		if (!isset($p['priority'])) {
			$enqueue_opts['priority'] = 2;
		}
		$jid = admin_jobs_enqueue('translations', 'cluster_pipeline', $pl, $enqueue_opts);
		return !empty($jid) ? array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid) : array('ok' => false, 'message' => 'enqueue failed');
	}
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	$dst_langs = translation_cluster_normalize_target_lang_ids($src, $dst_langs);
	if ($job_action === 'validate_locale') {
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		if ($dst <= 0) {
			return array('ok' => false, 'message' => 'dst_lang required');
		}
		$jid = admin_jobs_enqueue('translations', 'validate_locale', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src,
			'dst_lang' => $dst,
			'dst_langs' => $dst_langs,
			'repair_attempt' => isset($p['repair_attempt']) ? (int)$p['repair_attempt'] : 0,
			'autopilot' => !empty($p['autopilot']) ? 1 : 0,
			'api_pipeline' => 1,
		), $enqueue_opts);
		return !empty($jid) ? array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid) : array('ok' => false, 'message' => 'enqueue failed');
	}
	if ($job_action === 'repair_locale') {
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		if ($dst <= 0) {
			return array('ok' => false, 'message' => 'dst_lang required');
		}
		$vb = isset($p['validation_blockers']) && is_array($p['validation_blockers']) ? $p['validation_blockers'] : array();
		$vw = isset($p['validation_warnings']) && is_array($p['validation_warnings']) ? $p['validation_warnings'] : array();
		$jid = admin_jobs_enqueue('translations', 'repair_locale', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src,
			'dst_lang' => $dst,
			'dst_langs' => $dst_langs,
			'repair_attempt' => isset($p['repair_attempt']) ? max(1, (int)$p['repair_attempt']) : 1,
			'validation_blockers' => $vb,
			'validation_warnings' => $vw,
			'autopilot' => !empty($p['autopilot']) ? 1 : 0,
			'api_pipeline' => 1,
		), $enqueue_opts);
		return !empty($jid) ? array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid) : array('ok' => false, 'message' => 'enqueue failed');
	}
	if ($job_action === 'translate') {
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		if ($dst <= 0) {
			return array('ok' => false, 'message' => 'dst_lang required');
		}
		$fields = isset($p['fields']) && is_array($p['fields']) ? $p['fields'] : array('name', 'title', 'description', 'content');
		$chunk_max = isset($p['chunk_max_len']) ? (int)$p['chunk_max_len'] : (int)(isset($cfg['chunk_max_len']) ? $cfg['chunk_max_len'] : 2500);
		if ($chunk_max <= 0) {
			$chunk_max = 2500;
		}
		$pl_tr = array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src,
			'dst_lang' => $dst,
			'fields' => $fields,
			'chunk_max_len' => $chunk_max,
			'autopilot' => !empty($p['autopilot']) ? 1 : 0,
			'api_pipeline' => 1,
		);
		if (!empty($p['metadata_normalize'])) {
			$pl_tr['metadata_normalize'] = 1;
		}
		if (isset($p['order_id'])) {
			$pl_tr['order_id'] = (int)$p['order_id'];
		}
		if (isset($p['candidate_id'])) {
			$pl_tr['candidate_id'] = (int)$p['candidate_id'];
		}
		if (isset($p['english_leak_min_words'])) {
			$pl_tr['english_leak_min_words'] = max(3, min(12, (int)$p['english_leak_min_words']));
		}
		$jid = admin_jobs_enqueue('translations', 'translate', $pl_tr, $enqueue_opts);
		return !empty($jid) ? array('ok' => true, 'message' => 'enqueued', 'job_id' => (int)$jid) : array('ok' => false, 'message' => 'enqueue failed');
	}
	return array('ok' => false, 'message' => 'job_action must be cluster_pipeline, validate_cluster, validate_locale, repair_locale, translate, or translate_cluster');
}

/**
 * Segment JSON diagnostics: same extract + prompts + ai_gateway as repair_locale (NVIDIA via provider=nvidia).
 *
 * Params:
 * - entity, entity_id, dst_lang; src_lang optional (default 1)
 * - phase: extract | single_batch | full | import_apply | parse_mock
 * - extract: optional include_full_segments (0/1) — if 0, only counts + preview (default 1 for export)
 * - single_batch: batch_index (0-based), batch_size (optional; default from translation_settings + dense-script detection)
 * - full: full translation_html_segment_translate_full + apply + structure compare
 * - import_apply: segments[] must match extract segment_count (re-run extract from source; compares signatures)
 * - parse_mock: mock_reply (raw LLM text) — test JSON parse only
 *
 * @param array<string,mixed> $params
 * @return array<string,mixed>
 */
function site_telemetry_segment_json_simulate(array $params) {
	@set_time_limit(600);
	if (function_exists('ini_set')) {
		@ini_set('memory_limit', '512M');
	}
	$entity = isset($params['entity']) ? trim((string)$params['entity']) : '';
	$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
	$src_lang = isset($params['src_lang']) ? (int)$params['src_lang'] : 1;
	$dst_lang = isset($params['dst_lang']) ? (int)$params['dst_lang'] : 0;
	$phase = isset($params['phase']) ? trim((string)$params['phase']) : 'extract';
	if ($entity === '' || $eid <= 0 || $dst_lang <= 0) {
		return array('ok' => false, 'message' => 'entity, entity_id, dst_lang required');
	}
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	require_once ROOT_DIR . 'functions/translation_html_segment.php';
	require_once ROOT_DIR . 'functions/ai_prompt_templates.php';
	require_once ROOT_DIR . 'functions/ai_gateway.php';

	if ($phase === 'parse_mock') {
		$txt = isset($params['mock_reply']) ? (string)$params['mock_reply'] : '';
		$dec = translation_html_segment_parse_json_reply($txt);
		$got = (is_array($dec) && isset($dec['segments']) && is_array($dec['segments'])) ? array_values($dec['segments']) : array();
		return array(
			'ok' => true,
			'phase' => 'parse_mock',
			'parse_ok' => is_array($dec) && isset($dec['segments']) && is_array($dec['segments']),
			'parsed_segment_count' => count($got),
			'reply_chars' => strlen($txt),
			'reply_preview' => mb_substr($txt, 0, 6000, 'UTF-8') . (strlen($txt) > 6000 ? '…' : ''),
			'message' => 'parse_mock',
		);
	}

	$src = translation_cluster_get_source_snapshot($entity, $eid, $src_lang);
	if (!$src || !isset($src['content']) || trim((string)$src['content']) === '') {
		return array('ok' => false, 'message' => 'Source content not found for entity=' . $entity . ' id=' . $eid . ' lang=' . $src_lang);
	}
	$html = (string)$src['content'];
	$lang_src = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$src_lang . " LIMIT 1", 'row');
	$lang_dst = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$src_lang_name = !empty($lang_src['name']) ? (string)$lang_src['name'] : ('lang_id=' . (int)$src_lang);
	$dst_lang_name = !empty($lang_dst['name']) ? (string)$lang_dst['name'] : ('lang_id=' . (int)$dst_lang);

	$src_sig = function_exists('translation_cluster_content_signature') ? translation_cluster_content_signature($html) : array();
	$ext = translation_html_segment_extract($html);
	if (empty($ext['ok'])) {
		return array(
			'ok' => false,
			'message' => 'segment extract failed: ' . (isset($ext['message']) ? (string)$ext['message'] : ''),
			'source_signature' => $src_sig,
		);
	}
	$nseg = isset($ext['segments']) && is_array($ext['segments']) ? count($ext['segments']) : 0;
	$ids = isset($ext['ids']) && is_array($ext['ids']) ? $ext['ids'] : array();
	$preview = array();
	if (!empty($ext['segments']) && is_array($ext['segments'])) {
		foreach (array_slice($ext['segments'], 0, 5) as $s) {
			$s = (string)$s;
			$preview[] = mb_substr($s, 0, 200, 'UTF-8') . (mb_strlen($s, 'UTF-8') > 200 ? '…' : '');
		}
	}
	$include_full = !isset($params['include_full_segments']) || !empty($params['include_full_segments']);
	$base = array(
		'ok' => true,
		'phase' => $phase,
		'entity' => $entity,
		'entity_id' => $eid,
		'src_lang' => $src_lang,
		'dst_lang' => $dst_lang,
		'src_lang_name' => $src_lang_name,
		'dst_lang_name' => $dst_lang_name,
		'source_signature' => $src_sig,
		'extract' => array(
			'segment_count' => $nseg,
			'segments_preview' => $preview,
			'template_len' => isset($ext['template']) ? mb_strlen((string)$ext['template'], 'UTF-8') : 0,
			'batch_size' => translation_html_segment_batch_size_for_dst_lang((int)$dst_lang),
			'dense_script' => translation_html_segment_lang_looks_dense_script((int)$dst_lang) ? 1 : 0,
		),
		'message' => 'segment_json_simulate',
	);

	if ($phase === 'extract') {
		if ($include_full) {
			$base['segments'] = $ext['segments'];
			$base['template'] = isset($ext['template']) ? (string)$ext['template'] : '';
			$base['ids'] = $ids;
		} else {
			$base['hint'] = 'Set include_full_segments=1 to get segments[], template, ids for offline edit / import_apply.';
		}
		return $base;
	}

	$key_id = isset($params['provider_key_id']) ? (int)$params['provider_key_id'] : 0;
	if ($key_id > 0) {
		$key = mysql_select("SELECT * FROM ai_provider_keys WHERE id=" . (int)$key_id . " AND enabled=1 LIMIT 1", 'row');
	} else {
		$key = mysql_select("SELECT * FROM ai_provider_keys WHERE enabled=1 ORDER BY id ASC LIMIT 1", 'row');
	}
	if (!$key) {
		return array('ok' => false, 'message' => 'No enabled ai_provider_keys', 'source_signature' => $src_sig, 'extract' => $base['extract']);
	}
	$provider = trim((string)$key['provider']);
	$api_key = (string)$key['api_key'];
	$model = isset($key['model_default']) ? trim((string)$key['model_default']) : '';
	if ($model === '' && function_exists('ai_gateway_default_model')) {
		$model = ai_gateway_default_model($provider);
	}
	$key_meta = array(
		'id' => (int)$key['id'],
		'provider' => $provider,
		'model' => $model,
	);

	if ($phase === 'import_apply') {
		$imp = isset($params['segments']) && is_array($params['segments']) ? $params['segments'] : null;
		if ($imp === null) {
			return array('ok' => false, 'message' => 'segments[] required for import_apply', 'extract' => $base['extract']);
		}
		$imp = array_values(array_map('strval', $imp));
		if (count($imp) !== $nseg) {
			return array(
				'ok' => false,
				'message' => 'segments count mismatch: expected ' . $nseg . ', got ' . count($imp),
				'extract' => $base['extract'],
			);
		}
		$applied = translation_html_segment_apply((string)$ext['template'], $ids, $imp);
		$sig_a = function_exists('translation_cluster_content_signature') ? translation_cluster_content_signature($applied) : array();
		$match = ($src_sig == $sig_a);
		return array_merge($base, array(
			'ok' => $match,
			'import_apply' => array(
				'html_len' => mb_strlen($applied, 'UTF-8'),
				'structure_signature' => $sig_a,
				'structure_match_vs_source' => $match ? 1 : 0,
				'signature_diff' => $match ? null : array('source' => $src_sig, 'applied' => $sig_a),
			),
			'message' => $match ? 'import_apply: structure OK' : 'import_apply: structure mismatch (see signature_diff)',
		));
	}

	$prompt_tpl = ai_prompt_templates_merged();
	$batch_size = isset($params['batch_size']) ? (int)$params['batch_size'] : translation_html_segment_batch_size_for_dst_lang((int)$dst_lang);
	if ($batch_size < 4) {
		$batch_size = 4;
	}
	if ($batch_size > 80) {
		$batch_size = 80;
	}

	if ($phase === 'single_batch') {
		if (empty($ext['segments']) || !is_array($ext['segments'])) {
			return array('ok' => false, 'message' => 'no segments', 'source_signature' => $src_sig);
		}
		$batches = array_chunk($ext['segments'], $batch_size);
		$bi = isset($params['batch_index']) ? (int)$params['batch_index'] : 0;
		if ($bi < 0 || $bi >= count($batches)) {
			return array(
				'ok' => false,
				'message' => 'batch_index must be 0..' . (count($batches) - 1),
				'batch_total' => count($batches),
				'extract' => $base['extract'],
			);
		}
		$batch = $batches[$bi];
		$sys = isset($prompt_tpl['translation_segment_json']) ? (string)$prompt_tpl['translation_segment_json'] : '';
		if ($sys === '') {
			return array('ok' => false, 'message' => 'translation_segment_json template missing');
		}
		$sys = ai_prompt_templates_render($sys, array(
			'src_lang_name' => (string)$src_lang_name,
			'dst_lang_name' => (string)$dst_lang_name,
		));
		$user_payload = array(
			'source_lang' => (string)$src_lang_name,
			'target_lang' => (string)$dst_lang_name,
			'source_lang_id' => (int)$src_lang,
			'target_lang_id' => (int)$dst_lang,
			'segments' => array_values($batch),
		);
		$user = "Translate each string in `segments` to {$dst_lang_name}. Return JSON only.\n\nINPUT:\n" . json_encode($user_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$res = ai_gateway_chat($provider, $api_key, $model, array(
			array('role' => 'system', 'content' => $sys),
			array('role' => 'user', 'content' => $user),
		));
		$txt = trim((string)($res['reply_text'] ?? ''));
		$dec = translation_html_segment_parse_json_reply($txt);
		$got = (is_array($dec) && isset($dec['segments']) && is_array($dec['segments'])) ? array_values($dec['segments']) : array();
		$parse_ok = is_array($dec) && isset($dec['segments']) && count($got) === count($batch);
		$ok = !empty($res['ok']) && $parse_ok;
		$fr = isset($res['full_response']) && is_array($res['full_response']) ? $res['full_response'] : array();
		return array_merge($base, array(
			'ok' => $ok,
			'provider_key' => $key_meta,
			'llm' => array(
				'phase' => 'single_batch',
				'batch_size' => $batch_size,
				'batch_index' => $bi,
				'batch_total' => count($batches),
				'batch_segment_count' => count($batch),
				'gateway_ok' => !empty($res['ok']) ? 1 : 0,
				'gateway_message' => isset($res['message']) ? (string)$res['message'] : '',
				'parse_ok' => $parse_ok ? 1 : 0,
				'expected_batch_segment_count' => count($batch),
				'parsed_segment_count' => count($got),
				'reply_chars' => strlen($txt),
				'reply_preview' => mb_substr($txt, 0, 8000, 'UTF-8') . (strlen($txt) > 8000 ? '…' : ''),
				'full_response_sanitized' => site_telemetry_sanitize($fr),
			),
			'message' => $ok ? 'single_batch: OK' : 'single_batch: gateway or JSON parse mismatch',
		));
	}

	if ($phase === 'full') {
		$t0 = time();
		$tr = translation_html_segment_translate_full(
			$prompt_tpl,
			$provider,
			$api_key,
			$model,
			$ext['segments'],
			$src_lang_name,
			$dst_lang_name,
			$src_lang,
			$dst_lang,
			'telemetry_segment_json_simulate',
			0,
			900,
			$t0
		);
		if (empty($tr['ok']) || empty($tr['translated']) || !is_array($tr['translated'])) {
			return array_merge($base, array(
				'ok' => false,
				'provider_key' => $key_meta,
				'translate_full' => array(
					'ok' => 0,
					'message' => isset($tr['message']) ? (string)$tr['message'] : 'failed',
				),
				'message' => isset($tr['message']) ? (string)$tr['message'] : 'translate_full failed',
			));
		}
		$applied = translation_html_segment_apply((string)$ext['template'], $ids, $tr['translated']);
		$sig_a = function_exists('translation_cluster_content_signature') ? translation_cluster_content_signature($applied) : array();
		$match = ($src_sig == $sig_a);
		return array_merge($base, array(
			'ok' => $match,
			'provider_key' => $key_meta,
			'full' => array(
				'translate_full_ok' => 1,
				'html_len' => mb_strlen($applied, 'UTF-8'),
				'structure_signature' => $sig_a,
				'structure_match_vs_source' => $match ? 1 : 0,
				'signature_diff' => $match ? null : array('source' => $src_sig, 'applied' => $sig_a),
			),
			'message' => $match ? 'full: structure OK' : 'full: translate OK but structure mismatch (unexpected for segment path)',
		));
	}

	return array('ok' => false, 'message' => 'Unknown phase. Use: extract | single_batch | full | import_apply | parse_mock', 'extract' => $base['extract']);
}

/**
 * Remote control: autopilot tick, process translation jobs, snapshot, diagnostics.
 *
 * @param string $action
 * @param array<string,mixed> $params
 * @return array<string,mixed>
 */
function site_telemetry_control_dispatch($action, array $params) {
	$action = trim((string)$action);
	$t0 = microtime(true);
	$log_limit = isset($params['log_limit']) ? (int)$params['log_limit'] : 40;
	$log_limit = max(5, min(150, $log_limit));

	$finish = function ($result, $ok = true) use ($action, $t0) {
		$result['ok'] = !empty($ok);
		$result['action'] = $action;
		$result['elapsed_ms'] = (int)round((microtime(true) - (float)$t0) * 1000);
		if (function_exists('site_telemetry_log_event')) {
			site_telemetry_log_event('telemetry', 'control', $result['ok'] ? 'ok' : 'fail', array(
				'action' => $action,
				'message' => isset($result['message']) ? (string)$result['message'] : '',
			), array('source' => 'telemetry_control', 'duration_ms' => (int)$result['elapsed_ms']));
		}
		return $result;
	};

	if ($action === '' || $action === 'ping') {
		return $finish(array('message' => 'pong', 'php' => PHP_VERSION), true);
	}

	if ($action === 'snapshot') {
		$opts = array();
		if (!empty($params['limit'])) {
			$opts['limit'] = (int)$params['limit'];
		}
		if (!empty($params['translation_limit'])) {
			$opts['translation_limit'] = (int)$params['translation_limit'];
		}
		$snap = site_telemetry_collect_snapshot($opts);
		return $finish(array_merge($snap, array('message' => 'snapshot')), !empty($snap['ok']));
	}

	if ($action === 'segment_json_simulate') {
		$r = site_telemetry_segment_json_simulate($params);
		$msg = isset($r['message']) ? (string)$r['message'] : 'segment_json_simulate';
		return $finish(array_merge($r, array('message' => $msg)), !empty($r['ok']));
	}

	if ($action === 'diagnostics') {
		$diag = array();
		if (file_exists(ROOT_DIR . 'functions/translation_autopilot.php')) {
			require_once ROOT_DIR . 'functions/translation_autopilot.php';
			$cfg = translation_autopilot_load_cfg();
			$diag['autopilot_enabled'] = !empty($cfg['autopilot_enabled']) ? 1 : 0;
			$diag['monitor_busy'] = function_exists('translation_autopilot_monitor_busy') ? (translation_autopilot_monitor_busy() ? 1 : 0) : null;
			$diag['pending_translation_jobs'] = function_exists('translation_autopilot_pending_translation_jobs_count')
				? (int)translation_autopilot_pending_translation_jobs_count() : null;
			$diag['autopilot_stuck_seconds'] = isset($cfg['autopilot_stuck_seconds']) ? (int)$cfg['autopilot_stuck_seconds'] : null;
		}
		if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
			$diag['admin_jobs_pending_translations'] = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='pending'", 'string');
			$diag['admin_jobs_running_translations'] = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='running'", 'string');
		}
		$diag['queue_health'] = site_telemetry_translation_queue_health();
		return $finish(array('diagnostics' => $diag, 'message' => 'diagnostics'), true);
	}

	if ($action === 'translation_publish_insights') {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		require_once ROOT_DIR . 'functions/translation_cluster.php';
		translation_cluster_ensure_tables();
		$cfg = translation_autopilot_load_cfg();
		$ins = array(
			'autopilot_enabled' => !empty($cfg['autopilot_enabled']) ? 1 : 0,
			'autopilot_cluster_autopublish' => !empty($cfg['autopilot_cluster_autopublish']) ? 1 : 0,
			'autopilot_respect_monitor' => !empty($cfg['autopilot_respect_monitor']) ? 1 : 0,
			'system_logs_table' => @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0,
			'publish_events_in_system_logs' => 0,
			'translation_cluster_state_table' => @mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') > 0,
			'clusters_ready_to_publish' => null,
			'clusters_published' => null,
			'clusters_blocked' => null,
		);
		if ($ins['system_logs_table']) {
			$ins['publish_events_in_system_logs'] = (int)mysql_select("
				SELECT COUNT(*) AS c FROM system_logs
				WHERE channel = 'translations' AND message = 'translation_cluster_publish_all_content_i18n'
			", 'string');
		}
		if ($ins['translation_cluster_state_table']) {
			$ins['clusters_ready_to_publish'] = (int)mysql_select("
				SELECT COUNT(*) AS c FROM translation_cluster_state WHERE cluster_status = 'ready_to_publish'
			", 'string');
			$ins['clusters_published'] = (int)mysql_select("
				SELECT COUNT(*) AS c FROM translation_cluster_state WHERE cluster_status = 'published'
			", 'string');
			$ins['clusters_blocked'] = (int)mysql_select("
				SELECT COUNT(*) AS c FROM translation_cluster_state WHERE cluster_status = 'blocked'
			", 'string');
		}
		return $finish(array('translation_publish_insights' => $ins, 'message' => 'translation_publish_insights'), true);
	}

	if ($action === 'job_inspect') {
		$jid = isset($params['job_id']) ? (int)$params['job_id'] : 0;
		if ($jid <= 0) {
			return $finish(array('message' => 'job_id required'), false);
		}
		return $finish(array(
			'message' => 'job_inspect',
			'trace' => site_telemetry_control_trace_job_outcome($jid),
			'queue_health' => site_telemetry_translation_queue_health(),
			'tail' => site_telemetry_control_log_tail($log_limit),
		), true);
	}

	if ($action === 'enqueue_translate') {
		$sub = isset($params['enqueue']) && is_array($params['enqueue']) ? $params['enqueue'] : $params;
		$r = site_telemetry_control_enqueue_translate($sub);
		return $finish(array_merge($r, array('message' => 'enqueue_translate')), !empty($r['ok']));
	}

	if ($action === 'pipeline_status') {
		$entity = isset($params['entity']) ? trim((string)$params['entity']) : '';
		$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
		$lang = isset($params['lang_id']) ? (int)$params['lang_id'] : (isset($params['dst_lang']) ? (int)$params['dst_lang'] : 0);
		if ($entity === '' || $eid <= 0 || $lang <= 0) {
			return $finish(array('message' => 'entity, entity_id, lang_id (or dst_lang) required'), false);
		}
		$out = array(
			'content_i18n' => site_telemetry_control_pipeline_i18n_snapshot($entity, $eid, $lang),
		);
		if ($entity === 'blog' && file_exists(ROOT_DIR . 'functions/translation_cluster.php')) {
			require_once ROOT_DIR . 'functions/translation_cluster.php';
			if (function_exists('translation_cluster_get_state')) {
				$st = translation_cluster_get_state($entity, $eid);
				$out['cluster'] = $st ? site_telemetry_sanitize($st) : null;
			}
		}
		if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
			$like_eid = '%"entity_id":' . (int)$eid . '%';
			$out['pending_jobs_for_entity_id'] = mysql_select("
				SELECT id, action, status, priority, message, created_at
				FROM admin_jobs
				WHERE module='translations' AND status IN ('pending','running')
				  AND payload LIKE '" . mysql_res($like_eid) . "'
				ORDER BY id ASC
				LIMIT 30
			", 'rows') ?: array();
		}
		return $finish(array('pipeline_status' => $out, 'message' => 'pipeline_status'), true);
	}

	if ($action === 'cluster_snapshot') {
		$entity = isset($params['entity']) ? trim((string)$params['entity']) : '';
		$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
		if ($entity === '' || $eid <= 0) {
			return $finish(array('message' => 'entity, entity_id required'), false);
		}
		$pending = site_telemetry_cluster_fetch_pending_jobs($entity, $eid);
		$pending_ids = array();
		foreach ($pending as $r) {
			$sched = isset($r['scheduled_at']) ? (string)$r['scheduled_at'] : '';
			$pending_ids[] = array(
				'id' => (int)$r['id'],
				'action' => isset($r['action']) ? (string)$r['action'] : '',
				'scheduled_at' => $sched,
				'scheduled_future' => ($sched !== '' && strtotime($sched) > time()) ? 1 : 0,
			);
		}
		return $finish(array(
			'message' => 'cluster_snapshot',
			'cluster' => site_telemetry_cluster_compact_summary($entity, $eid),
			'pending_jobs' => $pending_ids,
			'running_jobs' => site_telemetry_cluster_fetch_running_jobs($entity, $eid),
			'queue_health' => site_telemetry_translation_queue_health(),
		), true);
	}

	if ($action === 'enqueue_translate_cluster') {
		$sub = isset($params['enqueue']) && is_array($params['enqueue']) ? $params['enqueue'] : $params;
		$r = site_telemetry_control_enqueue_translate_cluster($sub);
		return $finish(array_merge($r, array('message' => 'enqueue_translate_cluster')), !empty($r['ok']));
	}

	if ($action === 'enqueue_translation_job') {
		$sub = isset($params['enqueue']) && is_array($params['enqueue']) ? $params['enqueue'] : $params;
		$r = site_telemetry_control_enqueue_translation_job($sub);
		return $finish(array_merge($r, array('message' => 'enqueue_translation_job')), !empty($r['ok']));
	}

	@set_time_limit(600);
	if (function_exists('ini_set')) {
		@ini_set('memory_limit', '512M');
	}

	if ($action === 'cluster_drive') {
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$entity = isset($params['entity']) ? trim((string)$params['entity']) : 'blog';
		$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
		if ($eid <= 0) {
			return $finish(array('message' => 'entity_id required'), false);
		}
		$ja = isset($params['job_action']) ? trim((string)$params['job_action']) : 'cluster_pipeline';
		$sub = $params;
		$sub['job_action'] = $ja;
		$sub['entity'] = $entity;
		$sub['entity_id'] = $eid;
		$enq = site_telemetry_control_enqueue_translation_job($sub);
		$steps = array(array('phase' => 'enqueue', 'result' => $enq));
		if (empty($enq['ok'])) {
			return $finish(array('message' => 'cluster_drive', 'steps' => $steps), false);
		}
		// Default 0: enqueue only (avoids nginx 504 on long single HTTP request). Run translate_pipeline / cron separately.
		$n = isset($params['process_jobs']) ? (int)$params['process_jobs'] : 0;
		if (!empty($params['enqueue_only'])) {
			$n = 0;
		}
		$n = max(0, min(30, $n));
		for ($i = 0; $i < $n; $i++) {
			$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
			if (empty($job_res['processed'])) {
				$steps[] = array('phase' => 'process_queue', 'processed' => false, 'message' => isset($job_res['message']) ? (string)$job_res['message'] : '');
				break;
			}
			$jid = (int)$job_res['job_id'];
			$steps[] = array(
				'phase' => 'process_queue',
				'processed' => true,
				'ok' => !empty($job_res['ok']) ? 1 : 0,
				'job_id' => $jid,
				'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
				'trace' => $jid > 0 ? site_telemetry_control_trace_job_outcome($jid) : null,
			);
		}
		return $finish(array(
			'message' => 'cluster_drive',
			'hint' => $n > 0 ? '' : 'enqueue_only: call translate_pipeline with process_jobs or wait for cron',
			'cluster' => site_telemetry_cluster_compact_summary($entity, $eid),
			'queue_health' => site_telemetry_translation_queue_health(),
			'steps' => $steps,
			'tail' => site_telemetry_control_log_tail($log_limit),
		), true);
	}

	if ($action === 'cluster_simulate') {
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$entity = isset($params['entity']) ? trim((string)$params['entity']) : 'blog';
		$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
		if ($eid <= 0) {
			return $finish(array('message' => 'entity_id required'), false);
		}
		$max_steps = isset($params['max_steps']) ? (int)$params['max_steps'] : 25;
		$max_steps = max(1, min(80, $max_steps));
		$max_sec = isset($params['max_seconds']) ? (int)$params['max_seconds'] : 600;
		$max_sec = max(30, min(3600, $max_sec));
		if (function_exists('set_time_limit')) {
			@set_time_limit($max_sec + 60);
		}
		$deadline = microtime(true) + $max_sec;
		$stop_ready = !empty($params['stop_on_ready']);
		$steps = array();
		$stop_reason = '';
		$steps[] = array(
			'phase' => 'before',
			'cluster_summary' => site_telemetry_cluster_compact_summary($entity, $eid),
			'pending_jobs' => array_map(function ($r) {
				return array('id' => (int)$r['id'], 'action' => isset($r['action']) ? (string)$r['action'] : '');
			}, site_telemetry_cluster_fetch_pending_jobs($entity, $eid)),
			'queue_health' => site_telemetry_translation_queue_health(),
			'running_jobs' => site_telemetry_cluster_fetch_running_jobs($entity, $eid),
		);
		if (!empty($params['enqueue_translate_cluster'])) {
			$ep = array('entity' => $entity, 'entity_id' => $eid);
			if (!empty($params['enqueue']) && is_array($params['enqueue'])) {
				$ep = array_merge($ep, $params['enqueue']);
			}
			$enq = site_telemetry_control_enqueue_translate_cluster($ep);
			$steps[] = array('phase' => 'enqueue_translate_cluster', 'result' => $enq);
		}
		for ($i = 0; $i < $max_steps && microtime(true) < $deadline; $i++) {
			$pending = site_telemetry_cluster_fetch_pending_jobs($entity, $eid);
			if (empty($pending)) {
				$steps[] = array(
					'phase' => 'idle',
					'message' => 'no pending jobs for this cluster',
					'cluster_summary' => site_telemetry_cluster_compact_summary($entity, $eid),
					'queue_health' => site_telemetry_translation_queue_health(),
				);
				$stop_reason = 'idle_no_pending';
				break;
			}
			$next = $pending[0];
			$jid = (int)$next['id'];
			$act = isset($next['action']) ? (string)$next['action'] : '';
			$r = run_admin_job_by_id($jid);
			$sum = site_telemetry_cluster_compact_summary($entity, $eid);
			$steps[] = array(
				'phase' => 'run_job',
				'step' => $i + 1,
				'job_id' => $jid,
				'action' => $act,
				'result' => $r,
				'trace' => site_telemetry_control_trace_job_outcome($jid),
				'cluster_summary' => $sum,
			);
			if ($stop_ready && is_array($sum) && !empty($sum['cluster_status'])) {
				$cs = (string)$sum['cluster_status'];
				if (in_array($cs, array('ready_to_publish', 'needs_review'), true)) {
					$steps[] = array('phase' => 'stop', 'reason' => 'stop_on_ready', 'cluster_status' => $cs);
					$stop_reason = 'stop_on_ready';
					break;
				}
			}
		}
		if ($stop_reason === '') {
			if (microtime(true) >= $deadline) {
				$stop_reason = 'deadline';
			} else {
				$stop_reason = 'max_steps';
			}
		}
		return $finish(array(
			'message' => 'cluster_simulate',
			'entity' => $entity,
			'entity_id' => $eid,
			'stop_reason' => $stop_reason,
			'max_seconds' => $max_sec,
			'max_steps' => $max_steps,
			'queue_health' => site_telemetry_translation_queue_health(),
			'steps' => $steps,
			'tail' => site_telemetry_control_log_tail($log_limit),
		), true);
	}

	if ($action === 'translate_pipeline') {
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$steps = array();
		$new_jid = 0;
		if (!empty($params['enqueue']) && is_array($params['enqueue'])) {
			$enr = site_telemetry_control_enqueue_translate($params['enqueue']);
			$steps[] = array('phase' => 'enqueue', 'result' => $enr);
			if (empty($enr['ok'])) {
				return $finish(array('message' => 'translate_pipeline: enqueue failed', 'steps' => $steps), false);
			}
			$new_jid = (int)($enr['job_id'] ?? 0);
		}
		$target_job = isset($params['job_id']) ? (int)$params['job_id'] : 0;
		if ($target_job > 0) {
			$r = run_admin_job_by_id($target_job);
			$steps[] = array(
				'phase' => 'run_job',
				'result' => $r,
				'trace' => site_telemetry_control_trace_job_outcome($target_job),
			);
			return $finish(array(
				'message' => 'translate_pipeline',
				'steps' => $steps,
				'queue_health' => site_telemetry_translation_queue_health(),
				'tail' => site_telemetry_control_log_tail($log_limit),
			), !empty($r['ok']));
		}
		$n_req = isset($params['process_jobs']) ? (int)$params['process_jobs'] : 1;
		$n = max(0, min(20, $n_req));
		// One repair/translate job can exceed reverse-proxy timeout (504). Cap per HTTP request unless caller opts out.
		$cap = 3;
		$capped = false;
		if (empty($params['allow_long_http']) && $n > $cap) {
			$n = $cap;
			$capped = ($n_req > $cap);
		}
		if ($new_jid > 0 && $n > 0) {
			$r = run_admin_job_by_id($new_jid);
			$steps[] = array(
				'phase' => 'run_enqueued',
				'result' => $r,
				'trace' => site_telemetry_control_trace_job_outcome($new_jid),
			);
			$n--;
		}
		for ($i = 0; $i < $n; $i++) {
			$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
			if (empty($job_res['processed'])) {
				$steps[] = array(
					'phase' => 'process_queue',
					'processed' => false,
					'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
				);
				break;
			}
			$jid = (int)$job_res['job_id'];
			$steps[] = array(
				'phase' => 'process_queue',
				'processed' => true,
				'ok' => !empty($job_res['ok']) ? 1 : 0,
				'job_id' => $jid,
				'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
				'trace' => site_telemetry_control_trace_job_outcome($jid),
			);
		}
		return $finish(array(
			'message' => 'translate_pipeline',
			'process_jobs_requested' => $n_req,
			'process_jobs_per_request_cap' => empty($params['allow_long_http']) ? $cap : null,
			'hint' => $capped ? 'process_jobs capped to ' . (int)$cap . ' per request (504). Repeat call, or pass allow_long_http=1, or raise nginx/proxy read timeout.' : '',
			'steps' => $steps,
			'queue_health' => site_telemetry_translation_queue_health(),
			'tail' => site_telemetry_control_log_tail($log_limit),
		), true);
	}

	if ($action === 'process_jobs') {
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$n_req = isset($params['count']) ? (int)$params['count'] : 3;
		$n = max(0, min(20, $n_req));
		$cap = 3;
		$capped = false;
		if (empty($params['allow_long_http']) && $n > $cap) {
			$n = $cap;
			$capped = ($n_req > $cap);
		}
		$steps = array();
		$okc = 0;
		$failc = 0;
		for ($i = 0; $i < $n; $i++) {
			$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
			if (empty($job_res['processed'])) {
				$steps[] = array('processed' => false, 'message' => isset($job_res['message']) ? (string)$job_res['message'] : '');
				break;
			}
			$ok = !empty($job_res['ok']);
			if ($ok) {
				$okc++;
			} else {
				$failc++;
			}
			$jid = isset($job_res['job_id']) ? (int)$job_res['job_id'] : 0;
			$steps[] = array(
				'processed' => true,
				'ok' => $ok ? 1 : 0,
				'job_id' => $jid,
				'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
				'trace' => $jid > 0 ? site_telemetry_control_trace_job_outcome($jid) : null,
			);
		}
		return $finish(array(
			'message' => 'process_jobs',
			'summary' => array('requested' => $n_req, 'applied' => $n, 'ok' => $okc, 'failed' => $failc),
			'hint' => $capped ? 'count capped to ' . (int)$cap . ' per request (504). Repeat or allow_long_http=1.' : '',
			'queue_health' => site_telemetry_translation_queue_health(),
			'steps' => $steps,
			'tail' => site_telemetry_control_log_tail($log_limit),
		), true);
	}

	if ($action === 'meta_fix_tick') {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$mf_opts = array();
		if (isset($params['meta_cap'])) {
			$mf_opts['meta_cap'] = (int)$params['meta_cap'];
		}
		if (isset($params['max_jobs'])) {
			$mf_opts['max_jobs'] = (int)$params['max_jobs'];
		}
		$mf = translation_autopilot_meta_fix_enqueue_tick($mf_opts);
		$n = isset($params['process_jobs']) ? (int)$params['process_jobs'] : 0;
		$n = max(0, min(20, $n));
		$steps = array();
		$okc = 0;
		$failc = 0;
		for ($i = 0; $i < $n; $i++) {
			$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
			if (empty($job_res['processed'])) {
				$steps[] = array('processed' => false, 'message' => isset($job_res['message']) ? (string)$job_res['message'] : '');
				break;
			}
			$ok = !empty($job_res['ok']);
			if ($ok) {
				$okc++;
			} else {
				$failc++;
			}
			$steps[] = array(
				'processed' => true,
				'ok' => $ok ? 1 : 0,
				'job_id' => isset($job_res['job_id']) ? (int)$job_res['job_id'] : 0,
				'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
			);
		}
		return $finish(array(
			'message' => 'meta_fix_tick',
			'meta_fix' => $mf,
			'summary' => array('process_jobs_requested' => $n, 'processed_ok' => $okc, 'processed_failed' => $failc),
			'jobs' => $steps,
			'queue_health' => site_telemetry_translation_queue_health(),
			'tail' => site_telemetry_control_log_tail($log_limit),
		), !empty($mf['ok']));
	}

	if ($action === 'autopilot_tick') {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$apcfg = translation_autopilot_load_cfg();
		$apdef = function_exists('translation_autopilot_defaults') ? translation_autopilot_defaults() : array('autopilot_process_jobs_per_tick' => 6);
		$def_proc = isset($apcfg['autopilot_process_jobs_per_tick']) ? (int)$apcfg['autopilot_process_jobs_per_tick'] : (int)(isset($apdef['autopilot_process_jobs_per_tick']) ? $apdef['autopilot_process_jobs_per_tick'] : 6);
		$n = isset($params['process_jobs']) ? (int)$params['process_jobs'] : $def_proc;
		$n = max(0, min(20, $n));
		$res = translation_autopilot_run();
		$steps = array();
		$okc = 0;
		$failc = 0;
		for ($i = 0; $i < $n; $i++) {
			$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
			if (empty($job_res['processed'])) {
				$steps[] = array('processed' => false, 'message' => isset($job_res['message']) ? (string)$job_res['message'] : '');
				break;
			}
			$ok = !empty($job_res['ok']);
			if ($ok) {
				$okc++;
			} else {
				$failc++;
			}
			$steps[] = array(
				'processed' => true,
				'ok' => $ok ? 1 : 0,
				'job_id' => isset($job_res['job_id']) ? (int)$job_res['job_id'] : 0,
				'message' => isset($job_res['message']) ? (string)$job_res['message'] : '',
			);
		}
		return $finish(array(
			'message' => 'autopilot_tick',
			'autopilot' => $res,
			'summary' => array('process_jobs_requested' => $n, 'processed_ok' => $okc, 'processed_failed' => $failc),
			'jobs' => $steps,
			'tail' => site_telemetry_control_log_tail($log_limit),
		), !empty($res['ok']));
	}

	if ($action === 'run_job') {
		require_once ROOT_DIR . 'jobs/job_runner_lib.php';
		$jid = isset($params['job_id']) ? (int)$params['job_id'] : 0;
		if ($jid <= 0) {
			return $finish(array('message' => 'job_id required'), false);
		}
		$r = run_admin_job_by_id($jid);
		return $finish(array(
			'run_job' => $r,
			'tail' => site_telemetry_control_log_tail($log_limit),
		), !empty($r['ok']));
	}

	if ($action === 'ai_prompt_templates_get') {
		require_once ROOT_DIR . 'functions/ai_prompt_templates.php';
		return $finish(array(
			'message' => 'ai_prompt_templates_get',
			'templates' => ai_prompt_templates_merged(),
			'defaults' => ai_prompt_templates_defaults(),
			'custom_keys' => ai_prompt_templates_custom_keys(),
			'allowed_keys' => ai_prompt_templates_allowed_keys(),
			'placeholders' => array(
				'translation_metadata' => '{src_lang_name}, {dst_lang_name}, {examples_prompt}',
				'translation_content' => '{src_lang_name}, {dst_lang_name}, {examples_prompt}',
				'translation_repair_suffix' => '{dst_lang_name}',
				'translation_structure_lock' => '{structure_counts}',
				'translation_meta_repair' => '{dst_lang_name}',
				'translation_segment_json' => '{src_lang_name}, {dst_lang_name}',
			),
		), true);
	}

	if ($action === 'ai_prompt_templates_set') {
		require_once ROOT_DIR . 'functions/ai_prompt_templates.php';
		$partial = array();
		if (!empty($params['templates']) && is_array($params['templates'])) {
			$partial = $params['templates'];
		} else {
			foreach (ai_prompt_templates_allowed_keys() as $k) {
				if (array_key_exists($k, $params)) {
					$partial[$k] = $params[$k];
				}
			}
		}
		$r = ai_prompt_templates_save_partial($partial);
		return $finish(array_merge(array('message' => 'ai_prompt_templates_set'), $r), !empty($r['ok']));
	}

	if ($action === 'ai_prompt_templates_reset') {
		require_once ROOT_DIR . 'functions/ai_prompt_templates.php';
		$r = ai_prompt_templates_reset_all();
		return $finish(array_merge(array('message' => 'ai_prompt_templates_reset'), $r), !empty($r['ok']));
	}

	if ($action === 'seo_page_meta_patch') {
		require_once ROOT_DIR . 'functions/seo_monitor.php';
		$entity = isset($params['entity']) ? trim((string)$params['entity']) : '';
		$eid = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
		$lang_id = isset($params['lang_id']) ? (int)$params['lang_id'] : 0;
		$fields = isset($params['fields']) && is_array($params['fields']) ? $params['fields'] : array();
		$dry = !empty($params['dry_run']);
		$r = seo_monitor_apply_meta_patch($entity, $eid, $lang_id, $fields, $dry);
		return $finish(array_merge(array('message' => 'seo_page_meta_patch'), $r), !empty($r['ok']));
	}

	return $finish(array('message' => 'unknown action', 'hint' => 'ping, snapshot, diagnostics, job_inspect, enqueue_translate, enqueue_translate_cluster, enqueue_translation_job, cluster_drive, cluster_snapshot, cluster_simulate, segment_json_simulate, pipeline_status, translate_pipeline, meta_fix_tick, autopilot_tick, process_jobs, run_job, ai_prompt_templates_get, ai_prompt_templates_set, ai_prompt_templates_reset, seo_page_meta_patch'), false);
}

function site_telemetry_recent_events($where_sql, $limit) {
	if (@mysql_select("SHOW TABLES LIKE 'site_telemetry_events'", 'num_rows') === 0) {
		return array();
	}
	$rows = mysql_select("
		SELECT id, channel, event_type, status, request_id, source, module, entity, entity_id, duration_ms, http_code, payload, created_at
		FROM site_telemetry_events
		WHERE {$where_sql}
		ORDER BY id DESC
		LIMIT " . (int)$limit, 'rows') ?: array();
	foreach ($rows as &$row) {
		$row['payload'] = !empty($row['payload']) ? @json_decode((string)$row['payload'], true) : array();
		if (!is_array($row['payload'])) {
			$row['payload'] = array();
		}
	}
	unset($row);
	return $rows;
}

function site_telemetry_ai_gateway_record($url, $full_response, $ok) {
	$cfg = site_telemetry_load_settings();
	if (empty($cfg['enabled'])) {
		return;
	}
	$http = 0;
	$elapsed = 0;
	if (is_array($full_response)) {
		$http = isset($full_response['http_code']) ? (int)$full_response['http_code'] : 0;
		$elapsed = isset($full_response['elapsed_ms']) ? (int)$full_response['elapsed_ms'] : 0;
	}
	$host = '';
	if (is_string($url)) {
		$p = @parse_url($url);
		$host = isset($p['host']) ? (string)$p['host'] : '';
	}
	$status = $ok ? 'ok' : 'failed';
	site_telemetry_log_event('ai', 'llm_http', $status, array(
		'url_host' => $host,
		'url_path' => is_string($url) ? (string)parse_url($url, PHP_URL_PATH) : '',
		'http_code' => $http,
		'elapsed_ms' => $elapsed,
		'curl_errno' => is_array($full_response) && isset($full_response['curl_errno']) ? $full_response['curl_errno'] : null,
		'curl_error' => is_array($full_response) && isset($full_response['curl_error']) ? $full_response['curl_error'] : null,
		'raw_len' => is_array($full_response) && isset($full_response['raw_len']) ? $full_response['raw_len'] : null,
	), array(
		'duration_ms' => $elapsed,
		'http_code' => $http,
	));
}

function site_telemetry_log_admin_job($job, $ok, $message, $duration_ms) {
	if (!is_array($job)) {
		return;
	}
	$mod = isset($job['module']) ? (string)$job['module'] : '';
	$act = isset($job['action']) ? (string)$job['action'] : '';
	$jid = isset($job['id']) ? (int)$job['id'] : 0;
	$payload = @json_decode(isset($job['payload']) ? (string)$job['payload'] : '', true);
	if (!is_array($payload)) {
		$payload = array();
	}
	$entity = isset($payload['entity']) ? (string)$payload['entity'] : '';
	$eid = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$ev_ch = ($mod === 'translations') ? 'translations' : 'jobs';
	site_telemetry_log_event($ev_ch, 'admin_job', $ok ? 'done' : 'failed', array(
		'job_id' => $jid,
		'action' => $act,
		'message' => substr((string)$message, 0, 500),
		'payload_summary' => site_telemetry_sanitize($payload),
	), array(
		'module' => $mod,
		'entity' => $entity,
		'entity_id' => $eid,
		'duration_ms' => (int)$duration_ms,
	));
}

/**
 * Rich translation snapshot: autopilot settings, translation jobs queue, manual monitor, clusters, content_i18n, vector TM.
 *
 * @param int $tlim row cap for list-style sections (50–300)
 * @return array<string,mixed>
 */
function site_telemetry_snapshot_translations($tlim) {
	$tlim = max(50, min(300, (int)$tlim));
	$out = array(
		'settings_json' => null,
		'autopilot_config' => null,
		'jobs' => array(
			'counts_by_action_status' => array(),
			'counts_by_status' => array(),
			'pending_count' => 0,
			'running_count' => 0,
			'pending' => array(),
			'running' => array(),
			'recent' => array(),
		),
		'manual_monitor' => array(
			'orders_by_status' => array(),
			'orders_recent' => array(),
			'candidates_by_candidate_status' => array(),
			'candidates_by_i18n_status' => array(),
			'candidates_active' => array(),
			'candidates_recent' => array(),
		),
		'cluster_state' => array(),
		'content_i18n' => array(
			'total_rows' => 0,
			'by_entity' => array(),
			'by_status' => array(),
			'by_lang' => array(),
		),
		'vector_memory' => array(
			'total_rows' => 0,
			'by_quality' => array(),
			'by_field' => array(),
		),
		'logs_translations' => array(),
	);

	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && isset($row['value']) && (string)$row['value'] !== '') {
			$dec = @json_decode((string)$row['value'], true);
			$out['settings_json'] = is_array($dec) ? site_telemetry_sanitize($dec) : array('raw' => '[invalid json]');
		}
	}
	if (file_exists(ROOT_DIR . 'functions/translation_autopilot.php')) {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		if (function_exists('translation_autopilot_load_cfg')) {
			$out['autopilot_config'] = site_telemetry_sanitize(translation_autopilot_load_cfg());
		}
		if (function_exists('translation_autopilot_cluster_blocking_detail')) {
			$out['cluster_blocking_detail'] = translation_autopilot_cluster_blocking_detail();
		}
		$out['autopilot_build'] = defined('TRANSLATION_AUTOPILOT_BUILD') ? TRANSLATION_AUTOPILOT_BUILD : null;
	}

	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
		$out['jobs']['counts_by_action_status'] = mysql_select("
			SELECT action, status, COUNT(*) AS cnt
			FROM admin_jobs
			WHERE module='translations'
			GROUP BY action, status
			ORDER BY action ASC, status ASC
		", 'rows') ?: array();
		$out['jobs']['counts_by_status'] = mysql_select("
			SELECT status, COUNT(*) AS cnt
			FROM admin_jobs
			WHERE module='translations'
			GROUP BY status
			ORDER BY status ASC
		", 'rows') ?: array();
		$pc = mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='pending'", 'row');
		$out['jobs']['pending_count'] = $pc && isset($pc['c']) ? (int)$pc['c'] : 0;
		$rc = mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='running'", 'row');
		$out['jobs']['running_count'] = $rc && isset($rc['c']) ? (int)$rc['c'] : 0;

		$pend = mysql_select("
			SELECT id, action, status, priority, scheduled_at, created_at, started_at, finished_at, updated_at, locked_at, message, payload
			FROM admin_jobs
			WHERE module='translations' AND status='pending'
			ORDER BY priority DESC, id ASC
			LIMIT 500
		", 'rows') ?: array();
		foreach ($pend as &$pj) {
			$pj['payload'] = site_telemetry_sanitize(!empty($pj['payload']) ? @json_decode((string)$pj['payload'], true) : array());
			if (!is_array($pj['payload'])) {
				$pj['payload'] = array();
			}
		}
		unset($pj);
		$out['jobs']['pending'] = $pend;

		$run = mysql_select("
			SELECT id, action, status, priority, scheduled_at, created_at, started_at, finished_at, updated_at, locked_at, message, payload,
				TIMESTAMPDIFF(SECOND, started_at, NOW()) AS running_seconds,
				TIMESTAMPDIFF(SECOND, COALESCE(updated_at, started_at), NOW()) AS seconds_since_heartbeat
			FROM admin_jobs
			WHERE module='translations' AND status='running'
			ORDER BY started_at ASC
			LIMIT 100
		", 'rows') ?: array();
		foreach ($run as &$rj) {
			$rj['payload'] = site_telemetry_sanitize(!empty($rj['payload']) ? @json_decode((string)$rj['payload'], true) : array());
			if (!is_array($rj['payload'])) {
				$rj['payload'] = array();
			}
		}
		unset($rj);
		$out['jobs']['running'] = $run;

		$rec = mysql_select("
			SELECT id, action, status, priority, scheduled_at, created_at, started_at, finished_at, updated_at, message, payload
			FROM admin_jobs
			WHERE module='translations'
			ORDER BY id DESC
			LIMIT " . (int)$tlim, 'rows') ?: array();
		foreach ($rec as &$job) {
			$job['payload'] = site_telemetry_sanitize(!empty($job['payload']) ? @json_decode((string)$job['payload'], true) : array());
			if (!is_array($job['payload'])) {
				$job['payload'] = array();
			}
		}
		unset($job);
		$out['jobs']['recent'] = $rec;

		$out['jobs']['queue_health'] = site_telemetry_translation_queue_health();
	}

	if (@mysql_select("SHOW TABLES LIKE 'translation_orders'", 'num_rows') > 0) {
		$out['manual_monitor']['orders_by_status'] = mysql_select("
			SELECT status, COUNT(*) AS cnt FROM translation_orders GROUP BY status
		", 'rows') ?: array();
		$out['manual_monitor']['orders_recent'] = mysql_select("
			SELECT id, name, source_lang_id, target_lang_id, entity, status, priority, translated_count, failed_count, total_candidates, created_at, updated_at
			FROM translation_orders
			ORDER BY id DESC
			LIMIT " . (int)$tlim, 'rows') ?: array();
	}
	if (@mysql_select("SHOW TABLES LIKE 'translation_order_candidates'", 'num_rows') > 0) {
		$out['manual_monitor']['candidates_by_candidate_status'] = mysql_select("
			SELECT candidate_status, COUNT(*) AS cnt FROM translation_order_candidates GROUP BY candidate_status
		", 'rows') ?: array();
		$out['manual_monitor']['candidates_by_i18n_status'] = mysql_select("
			SELECT i18n_status, COUNT(*) AS cnt FROM translation_order_candidates GROUP BY i18n_status
		", 'rows') ?: array();
		$out['manual_monitor']['candidates_active'] = mysql_select("
			SELECT id, order_id, entity, entity_id, candidate_status, i18n_status, last_job_id, last_error, updated_at
			FROM translation_order_candidates
			WHERE candidate_status IN ('queued','running')
			ORDER BY updated_at DESC
			LIMIT 200
		", 'rows') ?: array();
		$out['manual_monitor']['candidates_recent'] = mysql_select("
			SELECT id, order_id, entity, entity_id, candidate_status, i18n_status, last_job_id,
				LEFT(COALESCE(last_error,''), 400) AS last_error_excerpt, updated_at
			FROM translation_order_candidates
			ORDER BY id DESC
			LIMIT " . (int)$tlim, 'rows') ?: array();
	}

	if (@mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') > 0) {
		if (file_exists(ROOT_DIR . 'functions/translation_cluster.php')) {
			require_once ROOT_DIR . 'functions/translation_cluster.php';
			translation_cluster_ensure_tables();
		}
		$out['cluster_state'] = mysql_select("
			SELECT id, entity, entity_id, source_lang_id, source_mode, pipeline_stage, cluster_status,
				ready_locales, total_locales, failed_locales, blocker_count, warning_count,
				COALESCE(seo_monitor_handoff,0) AS seo_monitor_handoff,
				human_reviewed_at,
				search_title, last_job_id, LEFT(COALESCE(last_error,''), 500) AS last_error_excerpt, updated_at
			FROM translation_cluster_state
			ORDER BY
				CASE WHEN cluster_status IN ('new','queued','translating','validating','repairing') THEN 0 ELSE 1 END ASC,
				updated_at DESC
			LIMIT " . (int)$tlim, 'rows') ?: array();
	}

	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$tr = mysql_select("SELECT COUNT(*) AS c FROM content_i18n", 'row');
		$out['content_i18n']['total_rows'] = $tr && isset($tr['c']) ? (int)$tr['c'] : 0;
		$out['content_i18n']['by_entity'] = mysql_select("
			SELECT entity, COUNT(*) AS cnt FROM content_i18n GROUP BY entity ORDER BY cnt DESC
		", 'rows') ?: array();
		$out['content_i18n']['by_status'] = mysql_select("
			SELECT status, COUNT(*) AS cnt FROM content_i18n GROUP BY status
		", 'rows') ?: array();
		$out['content_i18n']['by_lang'] = mysql_select("
			SELECT lang_id, COUNT(*) AS cnt FROM content_i18n GROUP BY lang_id ORDER BY cnt DESC
			LIMIT 40
		", 'rows') ?: array();
	}

	if (@mysql_select("SHOW TABLES LIKE 'translation_vector_items'", 'num_rows') > 0) {
		$vr = mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'row');
		$out['vector_memory']['total_rows'] = $vr && isset($vr['c']) ? (int)$vr['c'] : 0;
		$out['vector_memory']['by_quality'] = mysql_select("
			SELECT quality_status, COUNT(*) AS cnt FROM translation_vector_items GROUP BY quality_status
		", 'rows') ?: array();
		$out['vector_memory']['by_field'] = mysql_select("
			SELECT field_type, COUNT(*) AS cnt FROM translation_vector_items GROUP BY field_type ORDER BY cnt DESC
			LIMIT 30
		", 'rows') ?: array();
	}

	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0) {
		$out['logs_translations'] = mysql_select("
			SELECT id, created_at, level, message, context
			FROM system_logs
			WHERE channel='translations'
			ORDER BY id DESC
			LIMIT " . (int)$tlim, 'rows') ?: array();
		foreach ($out['logs_translations'] as &$log) {
			$log['context'] = !empty($log['context']) ? @json_decode((string)$log['context'], true) : array();
			if (!is_array($log['context'])) {
				$log['context'] = array('raw' => (string)$log['context']);
			}
			$log['context'] = site_telemetry_sanitize($log['context']);
		}
		unset($log);
	}

	$out['recent_events'] = site_telemetry_recent_events("channel IN ('translations','ai','cron')", min(120, $tlim));

	return $out;
}

/**
 * Deploy identity + short hashes of key PHP files (compare with repo / expected deploy).
 *
 * @return array<string,mixed>
 */
function site_telemetry_site_code_snapshot() {
	$root = ROOT_DIR;
	if (!defined('TRANSLATION_AUTOPILOT_BUILD') && is_readable($root . 'functions/translation_autopilot.php')) {
		require_once $root . 'functions/translation_autopilot.php';
	}
	$rel = array(
		'functions/translation_autopilot.php',
		'functions/translation_cluster.php',
		'functions/admin_func.php',
		'admin/modules/blog.php',
		'admin/modules/casino_articles.php',
		'job_runner_translations.php',
		'cron_translation_autopilot.php',
		'functions/site_telemetry.php',
	);
	$fp = array();
	foreach ($rel as $r) {
		$p = $root . $r;
		if (is_readable($p)) {
			$h = @hash_file('sha256', $p);
			$fp[$r] = is_string($h) ? substr($h, 0, 12) : null;
		} else {
			$fp[$r] = null;
		}
	}
	$rev = '';
	$rev_paths = array($root . 'deploy_revision.txt', $root . 'site/deploy_revision.txt');
	foreach ($rev_paths as $rp) {
		if (is_readable($rp)) {
			$raw = @file_get_contents($rp);
			if ($raw !== false) {
				foreach (preg_split("/\r\n|\n|\r/", (string)$raw) as $ln) {
					$ln = trim($ln);
					if ($ln !== '' && $ln[0] !== '#') {
						$rev = $ln;
						break 2;
					}
				}
			}
		}
	}
	if ($rev === '' && getenv('SITE_DEPLOY_REVISION')) {
		$rev = trim((string)getenv('SITE_DEPLOY_REVISION'));
	}
	$git = '';
	$gh = $root . '.git/HEAD';
	if (is_readable($gh)) {
		$git = trim((string)@file_get_contents($gh));
		if (strlen($git) > 120) {
			$git = substr($git, 0, 120) . '…';
		}
	}
	return array(
		'deploy_revision' => $rev !== '' ? $rev : null,
		'git_head' => $git !== '' ? $git : null,
		'autopilot_build' => defined('TRANSLATION_AUTOPILOT_BUILD') ? TRANSLATION_AUTOPILOT_BUILD : null,
		'file_sha256_12' => $fp,
	);
}

function site_telemetry_collect_snapshot($options = array()) {
	$cfg = site_telemetry_load_settings();
	$limit = isset($options['limit']) ? (int)$options['limit'] : (int)$cfg['snapshot_limit'];
	$limit = max(5, min(100, $limit));
	site_telemetry_ensure_tables();
	$tlim = max(50, min(300, $limit * 4));
	if (!empty($options['translation_limit'])) {
		$tlim = max(50, min(300, (int)$options['translation_limit']));
	}

	$out = array(
		'ok' => true,
		'collected_at' => date('c'),
		'request_id' => site_telemetry_request_id(),
		'telemetry' => array(
			'enabled' => !empty($cfg['enabled']) ? 1 : 0,
			'endpoint_enabled' => !empty($cfg['endpoint_enabled']) ? 1 : 0,
			'control_enabled' => !empty($cfg['control_enabled']) ? 1 : 0,
			'auth_token_present' => $cfg['auth_token'] !== '' ? 1 : 0,
			'retention_days' => (int)$cfg['retention_days'],
			'request_sample_pct' => (int)$cfg['request_sample_pct'],
			'request_slow_ms' => (int)$cfg['request_slow_ms'],
			'snapshot_limit' => (int)$limit,
			'translation_snapshot_rows' => (int)$tlim,
		),
		'server' => array(
			'php_version' => PHP_VERSION,
			'php_sapi' => php_sapi_name(),
			'hostname' => function_exists('gethostname') ? (string)gethostname() : '',
			'memory_usage' => function_exists('memory_get_usage') ? (int)memory_get_usage(true) : 0,
			'memory_peak' => function_exists('memory_get_peak_usage') ? (int)memory_get_peak_usage(true) : 0,
			'loadavg' => function_exists('sys_getloadavg') ? sys_getloadavg() : array(),
		),
		'jobs' => array(
			'counts' => array(),
			'recent' => array(),
		),
		'translation_clusters' => array(),
		'ai_keys' => array(),
		'recent_ai' => array(),
		'recent_requests' => array(),
		'recent_errors' => array(),
		'system_logs' => array(),
	);

	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
		$out['jobs']['counts'] = mysql_select("
			SELECT module, status, COUNT(*) cnt
			FROM admin_jobs
			GROUP BY module, status
			ORDER BY module ASC, status ASC
		", 'rows') ?: array();
		$job_rows = mysql_select("
			SELECT id, module, action, status, priority, scheduled_at, created_at, started_at, finished_at, message, payload
			FROM admin_jobs
			ORDER BY id DESC
			LIMIT " . (int)$limit, 'rows') ?: array();
		foreach ($job_rows as &$job) {
			$job['payload'] = !empty($job['payload']) ? @json_decode((string)$job['payload'], true) : array();
			if (!is_array($job['payload'])) {
				$job['payload'] = array();
			}
			$job['payload'] = site_telemetry_sanitize($job['payload']);
		}
		unset($job);
		$out['jobs']['recent'] = $job_rows;
	}

	$out['translations'] = site_telemetry_snapshot_translations($tlim);
	if (!empty($out['translations']['cluster_state'])) {
		$out['translation_clusters'] = $out['translations']['cluster_state'];
	}

	$out['code'] = site_telemetry_site_code_snapshot();

	if (@mysql_select("SHOW TABLES LIKE 'ai_provider_keys'", 'num_rows') > 0) {
		$out['ai_keys'] = mysql_select("
			SELECT id, provider, model_default, enabled, created_at
			FROM ai_provider_keys
			ORDER BY enabled DESC, id ASC
		", 'rows') ?: array();
	}

	$out['recent_ai'] = site_telemetry_recent_events("channel='ai'", $limit);
	$out['recent_requests'] = site_telemetry_recent_events("channel='request'", $limit);
	$out['recent_errors'] = site_telemetry_recent_events("(status IN ('failed','fatal','error') OR http_code >= 400)", $limit);

	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0) {
		$out['system_logs'] = mysql_select("
			SELECT id, created_at, channel, level, message, context
			FROM system_logs
			WHERE channel IN ('translations','jobs')
			   OR level='error'
			ORDER BY id DESC
			LIMIT " . (int)$limit, 'rows') ?: array();
		foreach ($out['system_logs'] as &$log) {
			$log['context'] = !empty($log['context']) ? @json_decode((string)$log['context'], true) : array();
			if (!is_array($log['context'])) {
				$log['context'] = array('raw' => (string)$log['context']);
			}
			$log['context'] = site_telemetry_sanitize($log['context']);
		}
		unset($log);
	}

	return $out;
}
