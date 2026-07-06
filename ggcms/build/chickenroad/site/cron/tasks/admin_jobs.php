<?php
/**
 * Run one job from admin_jobs (same as legacy cron_jobs.php).
 */
require_once ROOT_DIR . 'jobs/job_runner_lib.php';
$result = process_one_admin_job();
if (php_sapi_name() !== 'cli') {
	header('Content-Type: text/plain; charset=utf-8');
}
echo ($result['processed'] ? ($result['ok'] ? 'OK: ' : 'FAIL: ') : '') . $result['message'] . "\n";
if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
	exit(($result['processed'] && !$result['ok']) ? 1 : 0);
}
