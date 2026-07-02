<?php
/**
 * Retention cleanup for admin_jobs (done/failed). Config in variables.*.
 */

if (!function_exists('admin_jobs_cleanup_get_config')) {
	function admin_jobs_cleanup_get_config() {
		$out = array(
			'days' => 30,
			'interval_hours' => 24,
			'last_run' => '',
			'statuses' => array('done', 'failed'),
		);
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
			return $out;
		}
		$rows = mysql_select("
			SELECT `key`, `value` FROM variables
			WHERE `key` IN ('admin_jobs_cleanup_days','admin_jobs_cleanup_interval_hours','admin_jobs_cleanup_last_run','admin_jobs_cleanup_statuses')
		", 'rows');
		if (is_array($rows)) {
			foreach ($rows as $r) {
				$k = isset($r['key']) ? (string)$r['key'] : '';
				$v = isset($r['value']) ? trim((string)$r['value']) : '';
				if ($k === 'admin_jobs_cleanup_days' && $v !== '') {
					$out['days'] = max(1, (int)$v);
				} elseif ($k === 'admin_jobs_cleanup_interval_hours' && $v !== '') {
					$out['interval_hours'] = max(1, min(720, (int)$v));
				} elseif ($k === 'admin_jobs_cleanup_last_run') {
					$out['last_run'] = $v;
				} elseif ($k === 'admin_jobs_cleanup_statuses' && $v !== '') {
					$st = array();
					foreach (preg_split('/\s*,\s*/', $v, -1, PREG_SPLIT_NO_EMPTY) as $one) {
						$one = strtolower(trim($one));
						if (in_array($one, array('done', 'failed', 'cancelled'), true)) {
							$st[] = $one;
						}
					}
					if (!empty($st)) {
						$out['statuses'] = array_values(array_unique($st));
					}
				}
			}
		}
		return $out;
	}
}

if (!function_exists('admin_jobs_cleanup_set_last_run')) {
	function admin_jobs_cleanup_set_last_run($datetime) {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
			return;
		}
		$key = 'admin_jobs_cleanup_last_run';
		$val = trim((string)$datetime);
		$exists = mysql_select("SELECT id FROM variables WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
		if ($exists && !empty($exists['id'])) {
			mysql_fn('update', 'variables', array('value' => $val), " AND `key` = '" . mysql_res($key) . "' ");
		} else {
			mysql_fn('insert', 'variables', array('key' => $key, 'value' => $val));
		}
	}
}

if (!function_exists('admin_jobs_cleanup_sql_status_in')) {
	function admin_jobs_cleanup_sql_status_in(array $statuses) {
		$list = array();
		foreach ($statuses as $s) {
			$s = strtolower(trim((string)$s));
			if ($s !== '') {
				$list[] = "'" . mysql_res($s) . "'";
			}
		}
		if (empty($list)) {
			return "'done','failed'";
		}
		return implode(',', $list);
	}
}

/**
 * Rows that would be deleted (terminal jobs older than retention).
 */
function admin_jobs_cleanup_count(array $cfg) {
	$days = isset($cfg['days']) ? max(1, (int)$cfg['days']) : 30;
	$statuses = isset($cfg['statuses']) && is_array($cfg['statuses']) ? $cfg['statuses'] : array('done', 'failed');
	$in = admin_jobs_cleanup_sql_status_in($statuses);
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$cut = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
	$r = mysql_select("
		SELECT COUNT(*) AS c FROM admin_jobs
		WHERE status IN (" . $in . ")
		  AND (
			(finished_at IS NOT NULL AND finished_at < '" . mysql_res($cut) . "')
			OR (finished_at IS NULL AND created_at < '" . mysql_res($cut) . "')
		  )
	", 'row');
	return $r && isset($r['c']) ? (int)$r['c'] : 0;
}

/**
 * @param bool $force bypass interval
 * @param bool $dry_run count only
 * @return array{ok:bool,skipped:bool,dry_run:bool,deleted:int,message:string}
 */
function admin_jobs_cleanup_run_scheduled($force = false, $dry_run = false) {
	$cfg = admin_jobs_cleanup_get_config();
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return array(
			'ok' => true,
			'skipped' => false,
			'dry_run' => $dry_run,
			'deleted' => 0,
			'message' => 'Table admin_jobs not found.',
		);
	}
	if (!$force && $cfg['interval_hours'] > 0 && $cfg['last_run'] !== '') {
		$last_ts = strtotime($cfg['last_run']);
		if ($last_ts && (time() - $last_ts) < $cfg['interval_hours'] * 3600) {
			return array(
				'ok' => true,
				'skipped' => true,
				'dry_run' => false,
				'deleted' => 0,
				'message' => 'Jobs cleanup skipped: last run within ' . (int)$cfg['interval_hours'] . ' h.',
			);
		}
	}
	$in = admin_jobs_cleanup_sql_status_in($cfg['statuses']);
	$days = max(1, (int)$cfg['days']);
	$cut = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
	$n = admin_jobs_cleanup_count($cfg);
	if ($dry_run) {
		return array(
			'ok' => true,
			'skipped' => false,
			'dry_run' => true,
			'deleted' => $n,
			'message' => '[dry run] Would delete ' . $n . ' admin_jobs row(s) (status in ' . $in . ', older than ' . $days . ' d).',
		);
	}
	if ($n > 0) {
		mysql_fn('query', "
			DELETE FROM admin_jobs
			WHERE status IN (" . $in . ")
			  AND (
				(finished_at IS NOT NULL AND finished_at < '" . mysql_res($cut) . "')
				OR (finished_at IS NULL AND created_at < '" . mysql_res($cut) . "')
			  )
		");
	}
	admin_jobs_cleanup_set_last_run(date('Y-m-d H:i:s'));
	return array(
		'ok' => true,
		'skipped' => false,
		'dry_run' => false,
		'deleted' => $n,
		'message' => $n > 0
			? ('admin_jobs: deleted ' . $n . ' row(s) (retention ' . $days . ' day(s)).')
			: ('admin_jobs: nothing to delete (retention ' . $days . ' day(s)).'),
	);
}
