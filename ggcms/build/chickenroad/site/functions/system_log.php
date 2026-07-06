<?php
/**
 * System logs stored in DB (system_logs).
 */

function system_log_add($channel, $level, $message, $context = null) {
	if (!function_exists('mysql_select') || !function_exists('mysql_fn')) {
		return false;
	}
	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') === 0) {
		return false;
	}
	$channel = substr(trim((string)$channel), 0, 64);
	if ($channel === '') $channel = 'system';
	$level = strtolower(trim((string)$level));
	if (!in_array($level, array('debug','info','warning','error'), true)) $level = 'info';
	$message = trim((string)$message);
	if ($message === '') return false;
	if (strlen($message) > 4000) {
		$message = substr($message, 0, 3997) . '…';
	}
	$ctx = null;
	if ($context !== null) {
		$ctx = is_string($context) ? $context : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (is_string($ctx) && strlen($ctx) > 60000) {
			$ctx = substr($ctx, 0, 59997) . '…';
		}
	}
	return mysql_fn('insert', 'system_logs', array(
		'channel' => $channel,
		'level' => $level,
		'message' => $message,
		'context' => $ctx,
		'created_at' => date('Y-m-d H:i:s'),
	));
}

function system_log_cleanup($days_to_keep) {
	$days_to_keep = (int)$days_to_keep;
	if ($days_to_keep <= 0) $days_to_keep = 30;
	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') === 0) {
		return 0;
	}
	mysql_fn('query', "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL " . $days_to_keep . " DAY)");
	$row = @mysql_select("SELECT ROW_COUNT() AS c", 'row');
	return $row ? (int)$row['c'] : 0;
}

/**
 * Rows that would be removed by cleanup (older than retention).
 */
function system_log_cleanup_count($days_to_keep) {
	$days_to_keep = (int)$days_to_keep;
	if ($days_to_keep <= 0) {
		$days_to_keep = 30;
	}
	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') === 0) {
		return 0;
	}
	$r = mysql_select("SELECT COUNT(*) AS c FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL " . (int)$days_to_keep . " DAY)", 'row');
	return $r && isset($r['c']) ? (int)$r['c'] : 0;
}

/**
 * Read cleanup settings from variables (Settings → Variables card).
 *
 * @return array{days:int,interval_hours:int,last_run:string}
 */
function system_logs_cleanup_get_config() {
	$out = array('days' => 30, 'interval_hours' => 24, 'last_run' => '');
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return $out;
	}
	$rows = mysql_select("
		SELECT `key`, `value` FROM variables
		WHERE `key` IN ('system_logs_cleanup_days','system_logs_cleanup_interval_hours','system_logs_cleanup_last_run')
	", 'rows');
	if (is_array($rows)) {
		foreach ($rows as $r) {
			$k = isset($r['key']) ? (string)$r['key'] : '';
			$v = isset($r['value']) ? trim((string)$r['value']) : '';
			if ($k === 'system_logs_cleanup_days' && $v !== '') {
				$out['days'] = max(1, (int)$v);
			} elseif ($k === 'system_logs_cleanup_interval_hours' && $v !== '') {
				$out['interval_hours'] = max(1, min(720, (int)$v));
			} elseif ($k === 'system_logs_cleanup_last_run') {
				$out['last_run'] = $v;
			}
		}
	}
	return $out;
}

/** @return void */
function system_logs_cleanup_set_last_run($datetime) {
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return;
	}
	$key = 'system_logs_cleanup_last_run';
	$val = trim((string)$datetime);
	$exists = mysql_select("SELECT id FROM variables WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
	if ($exists && !empty($exists['id'])) {
		mysql_fn('update', 'variables', array('value' => $val), " AND `key` = '" . mysql_res($key) . "' ");
	} else {
		mysql_fn('insert', 'variables', array('key' => $key, 'value' => $val));
	}
}

/**
 * Run retention cleanup when interval elapsed, or always if $force.
 *
 * @param bool $force bypass interval
 * @param bool $dry_run only report count
 * @return array{ok:bool,skipped:bool,dry_run:bool,deleted:int,message:string}
 */
function system_logs_cleanup_run_scheduled($force = false, $dry_run = false) {
	$cfg = system_logs_cleanup_get_config();
	if (!$force && $cfg['interval_hours'] > 0 && $cfg['last_run'] !== '') {
		$last_ts = strtotime($cfg['last_run']);
		if ($last_ts && (time() - $last_ts) < $cfg['interval_hours'] * 3600) {
			return array(
				'ok' => true,
				'skipped' => true,
				'dry_run' => false,
				'deleted' => 0,
				'message' => 'Skipped: last run was within ' . (int)$cfg['interval_hours'] . ' h interval.',
			);
		}
	}
	$n = system_log_cleanup_count($cfg['days']);
	if ($dry_run) {
		return array(
			'ok' => true,
			'skipped' => false,
			'dry_run' => true,
			'deleted' => $n,
			'message' => '[dry run] Would delete ' . $n . ' row(s) older than ' . (int)$cfg['days'] . ' day(s).',
		);
	}
	if ($n > 0) {
		system_log_cleanup($cfg['days']);
	}
	// Record last attempt (successful cleanup pass; keeps interval throttling accurate).
	system_logs_cleanup_set_last_run(date('Y-m-d H:i:s'));
	return array(
		'ok' => true,
		'skipped' => false,
		'dry_run' => false,
		'deleted' => $n,
		'message' => $n > 0
			? ('Deleted ' . $n . ' system_logs row(s) (retention ' . (int)$cfg['days'] . ' day(s)).')
			: ('Nothing to delete (retention ' . (int)$cfg['days'] . ' day(s)).'),
	);
}

