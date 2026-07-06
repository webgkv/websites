<?php
/**
 * Jobs: global queue of background tasks (admin_jobs). Copied in spirit from prn_cross.
 */
$page_name = 'Jobs';

$get = array_merge(array(
	'status' => '', 'module' => '', 'page' => '1', 'id' => '', 'job_do' => '', 'job_msg' => '', 'u' => '',
	'log_cleanup' => '', 'log_cleanup_deleted' => '', 'log_cleanup_days' => '',
), (array)$get);
$status_filter = trim((string)$get['status']);
$module_filter = trim((string)$get['module']);
$page = max(1, (int)$get['page']);
$per_page = 30;

$table_ok = @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table admin_jobs not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

// Same as Logs → Clean old (by retention): system_logs, variable system_logs_cleanup_days
$logs_table_ok = @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0;
$retention_days = 30;
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$row_ret = mysql_select("SELECT value FROM variables WHERE `key`='system_logs_cleanup_days' LIMIT 1", 'row');
	if ($row_ret && $row_ret['value'] !== '') {
		$retention_days = max(1, (int)$row_ret['value']);
	}
}
if ($logs_table_ok && isset($get['u']) && (string)$get['u'] === 'log_cleanup') {
	require_once(ROOT_DIR . 'functions/system_log.php');
	$deleted = system_log_cleanup($retention_days);
	if (function_exists('system_logs_cleanup_set_last_run')) {
		system_logs_cleanup_set_last_run(date('Y-m-d H:i:s'));
	}
	$rb = '/admin.php?m=jobs';
	if ($status_filter !== '') {
		$rb .= '&status=' . urlencode($status_filter);
	}
	if ($module_filter !== '') {
		$rb .= '&module=' . urlencode($module_filter);
	}
	if ($page > 1) {
		$rb .= '&page=' . (int)$page;
	}
	header('Location: ' . $rb . '&log_cleanup=1&log_cleanup_deleted=' . (int)$deleted . '&log_cleanup_days=' . (int)$retention_days);
	exit;
}

// Build redirect URL with current filters/page
$redirect_base = '/admin.php?m=jobs';
if ($status_filter !== '') $redirect_base .= '&status=' . urlencode($status_filter);
if ($module_filter !== '') $redirect_base .= '&module=' . urlencode($module_filter);
if ($page > 1) $redirect_base .= '&page=' . $page;

// Bulk actions via checkboxes (cancel/delete selected)
if (!empty($_POST['bulk_jobs_action'])) {
	$bulk_action = trim((string)$_POST['bulk_jobs_action']);
	$ids = isset($_POST['job_ids']) && is_array($_POST['job_ids']) ? array_values(array_filter(array_map('intval', $_POST['job_ids']))) : array();
	if (empty($ids)) {
		header('Location: ' . $redirect_base . '&job_msg=' . urlencode('Select at least one job.'));
		exit;
	}
	$in = implode(',', $ids);
	if ($bulk_action === 'cancel_selected') {
		mysql_fn('query', "UPDATE admin_jobs SET status='cancelled', finished_at='" . mysql_res(date('Y-m-d H:i:s')) . "' WHERE id IN (" . $in . ") AND status IN ('pending','running')");
		header('Location: ' . $redirect_base . '&job_msg=' . urlencode('Selected jobs cancelled.'));
		exit;
	}
	if ($bulk_action === 'delete_selected') {
		mysql_fn('query', "DELETE FROM admin_jobs WHERE id IN (" . $in . ")");
		header('Location: ' . $redirect_base . '&job_msg=' . urlencode('Selected jobs removed.'));
		exit;
	}
}

// Actions: run now, delete, cancel, priority up/down
$job_do = isset($get['job_do']) ? trim((string)$get['job_do']) : '';
$job_id = (int)$get['id'];
if ($job_do !== '' && $job_id > 0) {
	$redirect = $redirect_base;

	if ($job_do === 'run') {
		require_once(ROOT_DIR . 'jobs/job_runner_lib.php');
		$res = run_admin_job_by_id($job_id);
		$redirect .= '&job_msg=' . urlencode(($res['ok'] ? 'OK: ' : 'FAIL: ') . $res['message']);
	} elseif ($job_do === 'delete') {
		mysql_fn('delete', 'admin_jobs', array('id' => $job_id));
		$redirect .= '&job_msg=' . urlencode('Job removed from queue');
	} elseif ($job_do === 'cancel') {
		mysql_fn('update', 'admin_jobs', array('status' => 'cancelled', 'finished_at' => date('Y-m-d H:i:s')), " AND id=" . $job_id . " AND status IN ('pending','running') ");
		$redirect .= '&job_msg=' . urlencode('Job cancelled');
	} elseif ($job_do === 'priority_up' || $job_do === 'priority_down') {
		$row = mysql_select("SELECT id, priority FROM admin_jobs WHERE id=" . $job_id . " AND status='pending' LIMIT 1", 'row');
		if ($row) {
			$cur_pri = (int)$row['priority'];
			$delta = ($job_do === 'priority_up') ? 1 : -1;
			mysql_fn('update', 'admin_jobs', array('priority' => $cur_pri + $delta), " AND id=" . $job_id . " ");
			$redirect .= '&job_msg=' . urlencode('Priority updated');
		}
	}
	header('Location: ' . $redirect);
	exit;
}

// Counts by status
$counts = array('all' => 0, 'pending' => 0, 'running' => 0, 'done' => 0, 'failed' => 0, 'cancelled' => 0);
$counts['all'] = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs", 'row')['c'];
foreach (array('pending', 'running', 'done', 'failed', 'cancelled') as $s) {
	$counts[$s] = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE status = '" . mysql_res($s) . "'", 'row')['c'];
}

$where = array('1=1');
if ($status_filter !== '' && in_array($status_filter, array('pending', 'running', 'done', 'failed', 'cancelled'), true)) {
	$where[] = "status = '" . mysql_res($status_filter) . "'";
}
if ($module_filter !== '') {
	$where[] = "module = '" . mysql_res($module_filter) . "'";
}
$where_sql = implode(' AND ', $where);

$total = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE {$where_sql}", 'row')['c'];
$offset = ($page - 1) * $per_page;

$job_detail = null;
$detail_id = (int)$get['id'];
if ($detail_id > 0) {
	$job_detail = mysql_select("SELECT * FROM admin_jobs WHERE id=" . $detail_id . " LIMIT 1", 'row');
}

$modules = mysql_select("SELECT DISTINCT module FROM admin_jobs ORDER BY module ASC", 'rows') ?: array();
$module_opts = array();
foreach ($modules as $r) {
	if (!empty($r['module'])) $module_opts[] = (string)$r['module'];
}

$jobs = mysql_select("
	SELECT id, module, action, status, scheduled_at, priority, created_at, started_at, finished_at, message, payload
	FROM admin_jobs
	WHERE {$where_sql}
	ORDER BY FIELD(status,'pending','running','done','failed','cancelled'), priority DESC, id DESC
	LIMIT " . (int)$per_page . " OFFSET " . (int)$offset . "
", 'rows') ?: array();

$base_url = '/admin.php?m=jobs';
if ($status_filter !== '') $base_url .= '&status=' . urlencode($status_filter);
if ($module_filter !== '') $base_url .= '&module=' . urlencode($module_filter);
$total_pages = $total > 0 ? (int)ceil($total / $per_page) : 1;

$log_cleanup_url = '/admin.php?m=jobs&u=log_cleanup';
if ($status_filter !== '') {
	$log_cleanup_url .= '&status=' . urlencode($status_filter);
}
if ($module_filter !== '') {
	$log_cleanup_url .= '&module=' . urlencode($module_filter);
}
if ($page > 1) {
	$log_cleanup_url .= '&page=' . (int)$page;
}

$q = array(
	'jobs' => $jobs,
	'job_detail' => $job_detail,
	'job_msg' => isset($get['job_msg']) ? trim((string)$get['job_msg']) : '',
	'counts' => $counts,
	'status_filter' => $status_filter,
	'module_filter' => $module_filter,
	'module_opts' => $module_opts,
	'page' => $page,
	'per_page' => $per_page,
	'total' => $total,
	'total_pages' => $total_pages,
	'base_url' => $base_url,
	'log_cleanup_url' => $log_cleanup_url,
	'log_cleanup' => !empty($get['log_cleanup']),
	'log_cleanup_deleted' => (isset($get['log_cleanup_deleted']) && (string)$get['log_cleanup_deleted'] !== '') ? (int)$get['log_cleanup_deleted'] : -1,
	'log_cleanup_days' => (isset($get['log_cleanup_days']) && (string)$get['log_cleanup_days'] !== '') ? (int)$get['log_cleanup_days'] : $retention_days,
	'logs_table_ok' => $logs_table_ok,
);

$content = html_array('modules/jobs', $q);

