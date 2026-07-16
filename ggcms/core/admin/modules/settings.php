<?php
/**
 * Settings: Main (Users, Roles), Variables, Counters. Port from prn_cross.
 */
$page_name = 'Settings';

$tab = isset($get['tab']) ? $get['tab'] : 'main';
if (!in_array($tab, array('main', 'variables', 'counters', 'cron'), true)) $tab = 'main';

$retention_var_keys = array(
	'system_logs_cleanup_days', 'system_logs_cleanup_interval_hours', 'system_logs_cleanup_last_run',
	'admin_jobs_cleanup_days', 'admin_jobs_cleanup_interval_hours', 'admin_jobs_cleanup_last_run', 'admin_jobs_cleanup_statuses',
	'cron_schedule',
);

// ----- Settings → Variables: system_logs cleanup card (POST)
if ($tab === 'variables' && !empty($_POST['system_logs_cleanup_save'])) {
	$vars_ok = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
	if ($vars_ok) {
		require_once ROOT_DIR . 'functions/system_log.php';
		$days = isset($_POST['system_logs_cleanup_days']) ? max(1, (int)$_POST['system_logs_cleanup_days']) : 30;
		$hours = isset($_POST['system_logs_cleanup_interval_hours']) ? max(1, min(720, (int)$_POST['system_logs_cleanup_interval_hours'])) : 24;
		foreach (array(
			'system_logs_cleanup_days' => (string)$days,
			'system_logs_cleanup_interval_hours' => (string)$hours,
		) as $vk => $vv) {
			$exists = mysql_select("SELECT id FROM variables WHERE `key` = '" . mysql_res($vk) . "' LIMIT 1", 'row');
			if ($exists && !empty($exists['id'])) {
				mysql_fn('update', 'variables', array('value' => $vv), " AND `key` = '" . mysql_res($vk) . "' ");
			} else {
				mysql_fn('insert', 'variables', array('key' => $vk, 'value' => $vv));
			}
		}
	}
	header('Location: /admin.php?m=settings&tab=variables&saved=1&logs_cleanup_saved=1');
	exit;
}
if ($tab === 'variables' && !empty($_POST['system_logs_cleanup_run_now'])) {
	require_once ROOT_DIR . 'functions/system_log.php';
	$dry = !empty($_POST['system_logs_cleanup_dry_run']);
	$res = system_logs_cleanup_run_scheduled(true, $dry);
	$msg = isset($res['message']) ? (string)$res['message'] : '';
	header('Location: /admin.php?m=settings&tab=variables&logs_cleanup_msg=' . rawurlencode($msg));
	exit;
}

// ----- Settings → Variables: admin_jobs cleanup card (POST)
if ($tab === 'variables' && !empty($_POST['admin_jobs_cleanup_save'])) {
	$vars_ok = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
	if ($vars_ok) {
		require_once ROOT_DIR . 'functions/admin_jobs_cleanup.php';
		$days = isset($_POST['admin_jobs_cleanup_days']) ? max(1, (int)$_POST['admin_jobs_cleanup_days']) : 30;
		$hours = isset($_POST['admin_jobs_cleanup_interval_hours']) ? max(1, min(720, (int)$_POST['admin_jobs_cleanup_interval_hours'])) : 24;
		$st = array();
		if (!empty($_POST['aj_st_done'])) {
			$st[] = 'done';
		}
		if (!empty($_POST['aj_st_failed'])) {
			$st[] = 'failed';
		}
		if (!empty($_POST['aj_st_cancelled'])) {
			$st[] = 'cancelled';
		}
		if (empty($st)) {
			$st = array('done', 'failed');
		}
		$statuses = implode(',', $st);
		foreach (array(
			'admin_jobs_cleanup_days' => (string)$days,
			'admin_jobs_cleanup_interval_hours' => (string)$hours,
			'admin_jobs_cleanup_statuses' => $statuses,
		) as $vk => $vv) {
			$exists = mysql_select("SELECT id FROM variables WHERE `key` = '" . mysql_res($vk) . "' LIMIT 1", 'row');
			if ($exists && !empty($exists['id'])) {
				mysql_fn('update', 'variables', array('value' => $vv), " AND `key` = '" . mysql_res($vk) . "' ");
			} else {
				mysql_fn('insert', 'variables', array('key' => $vk, 'value' => $vv));
			}
		}
	}
	header('Location: /admin.php?m=settings&tab=variables&saved=1&jobs_cleanup_saved=1');
	exit;
}
if ($tab === 'variables' && !empty($_POST['admin_jobs_cleanup_run_now'])) {
	require_once ROOT_DIR . 'functions/admin_jobs_cleanup.php';
	$dry = !empty($_POST['admin_jobs_cleanup_dry_run']);
	$res = admin_jobs_cleanup_run_scheduled(true, $dry);
	$msg = isset($res['message']) ? (string)$res['message'] : '';
	header('Location: /admin.php?m=settings&tab=variables&jobs_cleanup_msg=' . rawurlencode($msg));
	exit;
}

// ----- Settings → Cron (POST)
if ($tab === 'cron' && !empty($_POST['cron_schedule_save']) && isset($_POST['cron_task']) && is_array($_POST['cron_task'])) {
	require_once ROOT_DIR . 'functions/cron_schedule.php';
	cron_schedule_update_tasks($_POST['cron_task']);
	header('Location: /admin.php?m=settings&tab=cron&saved=1');
	exit;
}
if ($tab === 'cron' && !empty($_POST['cron_tick_run_now'])) {
	require_once ROOT_DIR . 'cron/bootstrap.php';
	require_once ROOT_DIR . 'functions/cron_schedule.php';
	$force = !empty($_POST['cron_tick_force']);
	ob_start();
	cron_schedule_run_tick(array('force' => $force));
	$out = trim((string)ob_get_clean());
	$msg = $out !== '' ? $out : 'Tick finished.';
	if (strlen($msg) > 2000) {
		$msg = substr($msg, 0, 2000) . '…';
	}
	header('Location: /admin.php?m=settings&tab=cron&cron_msg=' . rawurlencode($msg));
	exit;
}
if ($tab === 'cron' && !empty($_POST['cron_task_run_now']) && isset($_POST['cron_task_id'])) {
	require_once ROOT_DIR . 'cron/bootstrap.php';
	require_once ROOT_DIR . 'functions/cron_schedule.php';
	$tid = preg_replace('/[^a-z0-9_]/', '', (string)$_POST['cron_task_id']);
	$res = cron_schedule_run_task($tid);
	$msg = isset($res['message']) ? (string)$res['message'] : 'Done.';
	header('Location: /admin.php?m=settings&tab=cron&cron_msg=' . rawurlencode($msg));
	exit;
}

// ----- Save Counters (POST)
if ($tab === 'counters' && !empty($_POST['counters_save']) && isset($_POST['counters']) && is_array($_POST['counters'])) {
	$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
	if ($variables_exists) {
		require_once ROOT_DIR . 'functions/site_counters.php';
		$list = array();
		foreach ($_POST['counters'] as $row) {
			if (!isset($row['name'])) {
				continue;
			}
			$name = trim((string)$row['name']);
			$code_head = isset($row['code_head']) ? trim((string)$row['code_head']) : '';
			$code_body = isset($row['code_body']) ? trim((string)$row['code_body']) : '';
			$code_footer = isset($row['code_footer']) ? trim((string)$row['code_footer']) : '';
			if ($name === '' && $code_head === '' && $code_body === '' && $code_footer === '') {
				continue;
			}
			$list[] = array(
				'name'         => $name ?: 'Counter',
				'kind'         => isset($row['kind']) ? trim((string)$row['kind']) : '',
				'code_head'    => $code_head,
				'code_body'    => $code_body,
				'code_footer'  => $code_footer,
				'display'      => !empty($row['display']) ? 1 : 0,
				'place_head'   => !empty($row['place_head']) ? 1 : 0,
				'place_body'   => !empty($row['place_body']) ? 1 : 0,
				'place_footer' => !empty($row['place_footer']) ? 1 : 0,
			);
		}
		$counters_settings = array(
			'source' => isset($_POST['counters_source']) ? (string)$_POST['counters_source'] : 'json',
			'onesignal_web_enabled' => !empty($_POST['onesignal_web_enabled']) ? 1 : 0,
		);
		site_counters_save_to_db($list);
		site_counters_save_settings($counters_settings);
		// When source=json, frontend and this tab read files/reference/counters.json — sync on save.
		if ($counters_settings['source'] === 'json') {
			site_counters_save_reference_file($list, $counters_settings);
		}
	}
	header('Location: /admin.php?m=settings&tab=counters&saved=1');
	exit;
}

if ($tab === 'counters' && !empty($_POST['counters_import_json']) && !empty($_FILES['counters_json_file']['tmp_name'])) {
	require_once ROOT_DIR . 'functions/site_counters.php';
	$raw = file_get_contents($_FILES['counters_json_file']['tmp_name']);
	$pack = json_decode($raw, true);
	$msg = 'Invalid JSON';
	if (is_array($pack)) {
		$res = site_counters_import_pack($pack, 'db');
		$msg = $res['message'];
	}
	header('Location: /admin.php?m=settings&tab=counters&import_msg=' . rawurlencode($msg));
	exit;
}

if ($tab === 'counters' && !empty($_GET['counters_export']) && $_GET['counters_export'] === '1') {
	require_once ROOT_DIR . 'functions/site_counters.php';
	$pack = site_counters_export_pack('db');
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="counters-export.json"');
	echo json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	exit;
}

// ----- Save Variables (POST) — tab must be in URL (form action) so $get['tab'] is set
if ($tab === 'variables' && !empty($_POST['settings_save'])) {
	$vars_table_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
	if ($vars_table_exists) {
		// Delete requested (key !== counters)
		if (!empty($_POST['settings_delete']) && is_array($_POST['settings_delete'])) {
			foreach ($_POST['settings_delete'] as $k => $v) {
				if ($v && $k !== 'counters' && preg_match('/^[a-z0-9_]+$/i', $k)) {
					mysql_fn('delete', 'variables', 0, " AND `key` = '".mysql_res($k)."'");
				}
			}
		}
		// Update existing
		if (!empty($_POST['settings']) && is_array($_POST['settings'])) {
			foreach ($_POST['settings'] as $k => $val) {
				if ($k === 'counters' || !preg_match('/^[a-z0-9_]+$/i', $k)) continue;
				$val = is_string($val) ? trim($val) : '';
				$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = '".mysql_res($k)."' LIMIT 1", 'row');
				if ($exists && !empty($exists['id'])) {
					mysql_fn('update', 'variables', array('id' => $exists['id'], 'value' => $val));
				} else {
					mysql_fn('insert', 'variables', array('key' => $k, 'value' => $val));
				}
			}
		}
		// Add new
		if (isset($_POST['new_key']) && isset($_POST['new_value'])) {
			$new_key = preg_replace('/[^a-z0-9_]/i', '', trim((string)$_POST['new_key']));
			$new_value = trim((string)$_POST['new_value']);
			if ($new_key !== '' && $new_key !== 'counters') {
				$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = '".mysql_res($new_key)."' LIMIT 1", 'row');
				if ($exists && !empty($exists['id'])) {
					mysql_fn('update', 'variables', array('id' => $exists['id'], 'value' => $new_value));
				} else {
					mysql_fn('insert', 'variables', array('key' => $new_key, 'value' => $new_value));
				}
			}
		}
	}
	header('Location: /admin.php?m=settings&tab=variables&saved=1');
	exit;
}

// ----- Main tab: counts
$users_count  = (int) @mysql_select("SELECT COUNT(*) FROM users WHERE id > 1", 'string');
$roles_count  = (int) @mysql_select("SELECT COUNT(*) FROM user_types", 'string');

// ----- Variables tab
$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
$vars = array();
$variables_list = array(); // id, key, value for UI (exclude counters — managed in Counters tab)
if ($variables_exists) {
	$vars_raw = @mysql_select("SELECT id, `key`, `value` FROM `variables` ORDER BY `key`", 'rows');
	if (is_array($vars_raw)) {
		foreach ($vars_raw as $r) {
			$vars[$r['key']] = $r['value'];
			if ($r['key'] !== 'counters' && !in_array($r['key'], $retention_var_keys, true)) {
				$variables_list[] = array('id' => (int)$r['id'], 'key' => $r['key'], 'value' => $r['value']);
			}
		}
	}
}

// ----- Counters tab: load from variables or default (counter.dev)
$counters = array();
$counters_settings = array('source' => 'json', 'onesignal_web_enabled' => 1);
$counters_json_path = '';
$counters_json_exists = false;
if (is_file(ROOT_DIR . 'functions/site_counters.php')) {
	require_once ROOT_DIR . 'functions/site_counters.php';
	$counters_settings = site_counters_load_settings();
	$counters_json_path = site_counters_reference_path();
	$counters_json_exists = is_file($counters_json_path);
	if ($counters_settings['source'] === 'json' && $counters_json_exists) {
		$json_pack = site_counters_load_json_file($counters_json_path);
		if ($json_pack !== null) {
			$counters = $json_pack['counters'];
		}
	}
}
if ($variables_exists) {
	if (empty($counters) || $counters_settings['source'] === 'db') {
		$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'counters' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') {
			$dec = json_decode($row['value'], true);
			if (is_array($dec)) {
				$counters = $dec;
			}
		}
	}
}
if (empty($counters)) {
	$counters = array(array(
		'name'    => 'Counter.dev',
		'code'    => '<script src="https://cdn.counter.dev/script.js" data-id="a555a78e-c2d3-41eb-95d4-8c319224b944" data-utcoffset="3"></script>',
		'display' => 1,
	));
}

// ----- system_logs cleanup card data
$logs_cleanup_cfg = array('days' => 30, 'interval_hours' => 24, 'last_run' => '');
$logs_stats = array('exists' => false, 'rows' => 0, 'size_mb' => 0.0);
if ($variables_exists) {
	require_once ROOT_DIR . 'functions/system_log.php';
	$logs_cleanup_cfg = system_logs_cleanup_get_config();
}
if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0) {
	$logs_stats['exists'] = true;
	$rct = mysql_select("SELECT COUNT(*) AS c FROM system_logs", 'row');
	$logs_stats['rows'] = $rct && isset($rct['c']) ? (int)$rct['c'] : 0;
	$dbn = isset($config['mysql_database']) ? (string)$config['mysql_database'] : '';
	if ($dbn !== '') {
		$sz = @mysql_select("SELECT ROUND(DATA_LENGTH/1024/1024, 2) AS mb FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . mysql_res($dbn) . "' AND TABLE_NAME = 'system_logs' LIMIT 1", 'row');
		$logs_stats['size_mb'] = $sz && isset($sz['mb']) ? (float)$sz['mb'] : 0.0;
	}
}
$logs_cleanup_cron_path = '';

// ----- Cron tab
$cron_schedule = array('tasks' => array(), 'tick_last_run' => '', 'crontab_line' => '');
if ($variables_exists) {
	require_once ROOT_DIR . 'functions/cron_schedule.php';
	$cron_schedule = cron_schedule_get();
}

// ----- admin_jobs cleanup card data
$jobs_cleanup_cfg = array('days' => 30, 'interval_hours' => 24, 'last_run' => '', 'statuses' => array('done', 'failed'));
$jobs_stats = array('exists' => false, 'rows' => 0, 'size_mb' => 0.0);
if ($variables_exists) {
	require_once ROOT_DIR . 'functions/admin_jobs_cleanup.php';
	$jobs_cleanup_cfg = admin_jobs_cleanup_get_config();
}
if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
	$jobs_stats['exists'] = true;
	$rct = mysql_select("SELECT COUNT(*) AS c FROM admin_jobs", 'row');
	$jobs_stats['rows'] = $rct && isset($rct['c']) ? (int)$rct['c'] : 0;
	$dbn = isset($config['mysql_database']) ? (string)$config['mysql_database'] : '';
	if ($dbn !== '') {
		$sz = @mysql_select("SELECT ROUND(DATA_LENGTH/1024/1024, 2) AS mb FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . mysql_res($dbn) . "' AND TABLE_NAME = 'admin_jobs' LIMIT 1", 'row');
		$jobs_stats['size_mb'] = $sz && isset($sz['mb']) ? (float)$sz['mb'] : 0.0;
	}
}

$variables_map_for_js = array();
foreach ($variables_list as $__v) {
	$variables_map_for_js[$__v['key']] = $__v['value'];
}

$table = null;
$content = html_array('modules/settings', array(
	'tab' => $tab,
	'users_count' => $users_count,
	'roles_count' => $roles_count,
	'variables_list' => $variables_list,
	'variables_map_json' => json_encode($variables_map_for_js, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE),
	'variables_exists' => $variables_exists,
	'counters' => $counters,
	'counters_settings' => $counters_settings,
	'counters_json_path' => $counters_json_path,
	'counters_json_exists' => $counters_json_exists,
	'saved' => isset($get['saved']) && $get['saved'] == '1',
	'logs_cleanup_saved' => isset($get['logs_cleanup_saved']) && $get['logs_cleanup_saved'] == '1',
	'jobs_cleanup_saved' => isset($get['jobs_cleanup_saved']) && $get['jobs_cleanup_saved'] == '1',
	'logs_cleanup_msg' => isset($get['logs_cleanup_msg']) ? trim((string)$get['logs_cleanup_msg']) : '',
	'jobs_cleanup_msg' => isset($get['jobs_cleanup_msg']) ? trim((string)$get['jobs_cleanup_msg']) : '',
	'logs_cleanup_cfg' => $logs_cleanup_cfg,
	'logs_stats' => $logs_stats,
	'jobs_cleanup_cfg' => $jobs_cleanup_cfg,
	'jobs_stats' => $jobs_stats,
	'logs_cleanup_cron_path' => $logs_cleanup_cron_path,
	'cron_schedule' => $cron_schedule,
	'cron_msg' => isset($get['cron_msg']) ? trim((string)$get['cron_msg']) : '',
));
