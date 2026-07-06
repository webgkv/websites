<?php
/**
 * Logs: system_logs viewer, export, cleanup, live JSON (UI like prn_cross Drop Monitor → Log).
 */
$page_name = 'Log';

$get = array_merge(array('u' => '', 'log_id' => '', 'log_page' => '1', 'limit' => '50'), (array)$get);

// Admin-style pagination uses query param `n` (see pagination/default.php).
$log_n = isset($_GET['n']) ? (int)$_GET['n'] : (int)($get['log_page'] ?? 1);
if ($log_n < 1) $log_n = 1;
$perPage = 50;

$table_ok = @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table system_logs not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

require_once(ROOT_DIR . 'functions/system_log.php');

// Retention days from variables
$retention_days = 30;
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$row = mysql_select("SELECT value FROM variables WHERE `key`='system_logs_cleanup_days' LIMIT 1", 'row');
	if ($row && $row['value'] !== '') $retention_days = max(1, (int)$row['value']);
}

// --- Export CSV (last 1000)
if ($get['u'] === 'export_log_csv') {
	$rows = mysql_select("SELECT id, created_at, channel, level, message, context FROM system_logs ORDER BY id DESC LIMIT 1000", 'rows') ?: array();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="system_logs_last1000_' . date('Y-m-d_His') . '.csv"');
	$out = fopen('php://output', 'w');
	// Write UTF-8 BOM for Excel-friendly CSV without using \x escapes (to avoid linter issues)
	fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
	fputcsv($out, array('id','created_at','channel','level','message','context'));
	foreach ($rows as $r) {
		fputcsv($out, array($r['id'], $r['created_at'], $r['channel'], $r['level'], $r['message'], $r['context']));
	}
	fclose($out);
	exit;
}

// --- Export full CSV (up to 50k)
if ($get['u'] === 'export_log_csv_full') {
	$rows = mysql_select("SELECT id, created_at, channel, level, message, context FROM system_logs ORDER BY id DESC LIMIT 50000", 'rows') ?: array();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="system_logs_full_' . date('Y-m-d_His') . '.csv"');
	$out = fopen('php://output', 'w');
	// Write UTF-8 BOM for Excel-friendly CSV without using \x escapes (to avoid linter issues)
	fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
	fputcsv($out, array('id','created_at','channel','level','message','context'));
	foreach ($rows as $r) {
		fputcsv($out, array($r['id'], $r['created_at'], $r['channel'], $r['level'], $r['message'], $r['context']));
	}
	fclose($out);
	exit;
}

// --- Cleanup old by retention
if ($get['u'] === 'log_cleanup') {
	$deleted = system_log_cleanup($retention_days);
	header('Location: /admin.php?m=logs&log_cleanup=1&log_cleanup_deleted=' . (int)$deleted . '&log_cleanup_days=' . (int)$retention_days);
	exit;
}

// --- Live JSON (AJAX)
if ($get['u'] === 'log_entries_json') {
	$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : (int)$get['limit'];
	if ($limit <= 0) $limit = 50;
	if ($limit > 200) $limit = 200;
	$rows = mysql_select("SELECT id, created_at, channel, level, message, context FROM system_logs ORDER BY id DESC LIMIT " . (int)$limit, 'rows') ?: array();
	$entries = array();
	foreach ($rows as $e) {
		$req = isset($e['context']) ? (string)$e['context'] : '';
		$req_short = mb_substr($req, 0, 50);
		if (mb_strlen($req) > 50) $req_short .= '…';
		$resp = isset($e['message']) ? (string)$e['message'] : '';
		$resp_short = mb_substr(strip_tags($resp), 0, 60);
		if (mb_strlen($resp) > 60) $resp_short .= '…';
		$entries[] = array(
			'id' => (int)$e['id'],
			'created_at' => $e['created_at'],
			'service' => $e['channel'],
			'level' => $e['level'],
			'http_code' => '',
			'duration_ms' => '',
			'request_text' => $req,
			'request_short' => $req_short,
			'response_short' => $resp_short,
		);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('entries' => $entries), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	exit;
}

// List + detail (paginated)
// Use mysql_select(..., 'string') for COUNT — casting a row array to (int) yields 1 in PHP, breaking pagination.
$totalLogs = (int)mysql_select('SELECT COUNT(*) FROM system_logs', 'string');
$totalLogs = max(0, $totalLogs);
$totalPages = $perPage > 0 ? (int)ceil($totalLogs / $perPage) : 1;
if ($totalPages < 1) $totalPages = 1;
if ($log_n > $totalPages) $log_n = $totalPages;
$offset = ($log_n - 1) * $perPage;

$log_entries = mysql_select("
	SELECT id, created_at, channel, level, message, context
	FROM system_logs
	ORDER BY id DESC
	LIMIT " . (int)$perPage . " OFFSET " . (int)$offset, 'rows') ?: array();
$log_detail = null;
if ((int)$get['log_id'] > 0) {
	$log_detail = mysql_select("SELECT * FROM system_logs WHERE id=" . (int)$get['log_id'] . " LIMIT 1", 'row');
}

$q = array(
	'log_table_exists' => true,
	'log_entries' => $log_entries,
	'log_detail' => $log_detail,
	'pagination_html' => html_render('pagination/default', array(
		'n' => $log_n,
		'limit' => $perPage,
		'num_rows' => $totalLogs,
		'array_count' => $perPage,
	)),
	'log_cleanup' => !empty($get['log_cleanup']),
	'log_cleanup_deleted' => isset($get['log_cleanup_deleted']) ? (int)$get['log_cleanup_deleted'] : -1,
	'log_cleanup_days' => isset($get['log_cleanup_days']) ? (int)$get['log_cleanup_days'] : $retention_days,
);
$content = html_array('modules/logs', $q);

