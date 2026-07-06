<?php
/**
 * Retention for system_logs and admin_jobs.
 */
@set_time_limit(120);
if (function_exists('ini_set')) {
	@ini_set('memory_limit', '128M');
}

require_once ROOT_DIR . 'functions/system_log.php';
require_once ROOT_DIR . 'functions/admin_jobs_cleanup.php';

$force = php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--force';
$res1 = system_logs_cleanup_run_scheduled($force, false);
$res2 = admin_jobs_cleanup_run_scheduled($force, false);
$line = date('c')
	. ' system_logs: ' . (string)$res1['message'] . ' skipped=' . ($res1['skipped'] ? '1' : '0')
	. ' | admin_jobs: ' . (string)$res2['message'] . ' skipped=' . ($res2['skipped'] ? '1' : '0')
	. "\n";

if (php_sapi_name() !== 'cli') {
	header('Content-Type: text/plain; charset=utf-8');
}
echo $line;
if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
	exit(0);
}
