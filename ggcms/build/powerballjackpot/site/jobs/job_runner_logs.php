<?php
/**
 * Job handlers: logs cleanup.
 */

function run_logs_cleanup($payload = array(), $job = array()) {
	$days = isset($payload['days']) ? (int)$payload['days'] : 0;
	if ($days <= 0 && @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='system_logs_cleanup_days' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') $days = (int)$row['value'];
	}
	if ($days <= 0) $days = 30;
	$deleted = system_log_cleanup($days);
	return array('ok' => true, 'message' => "Cleaned system_logs older than {$days} day(s), deleted {$deleted} row(s)");
}

