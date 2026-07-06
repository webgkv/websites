<?php
/**
 * Translation autopilot cron (same logic as legacy cron_translation_autopilot.php).
 */
@set_time_limit(900);
if (function_exists('ini_set')) {
	@ini_set('memory_limit', '512M');
}

require_once ROOT_DIR . 'functions/system_log.php';
require_once ROOT_DIR . 'functions/site_telemetry.php';
require_once ROOT_DIR . 'functions/translation_autopilot.php';
require_once ROOT_DIR . 'jobs/job_runner_lib.php';

if (function_exists('site_telemetry_request_begin')) {
	site_telemetry_request_begin('cron', array('module' => 'cron_translation_autopilot'));
}

$res = translation_autopilot_run();
$cfg = translation_autopilot_load_cfg();
$def = translation_autopilot_defaults();
$process_limit = isset($cfg['autopilot_process_jobs_per_tick']) ? (int)$cfg['autopilot_process_jobs_per_tick'] : (int)$def['autopilot_process_jobs_per_tick'];
if ($process_limit < 0) {
	$process_limit = 0;
}
if ($process_limit > 20) {
	$process_limit = 20;
}
$max_wall = isset($cfg['autopilot_cron_max_wall_seconds']) ? (int)$cfg['autopilot_cron_max_wall_seconds'] : (int)$def['autopilot_cron_max_wall_seconds'];
if ($max_wall < 60) {
	$max_wall = 60;
}
if ($max_wall > 840) {
	$max_wall = 840;
}
$tick_clock = microtime(true);
$processed = 0;
$processed_ok = 0;
$processed_fail = 0;
$cron_wall_budget_hit = false;
for ($i = 0; $i < $process_limit; $i++) {
	if ((microtime(true) - $tick_clock) >= $max_wall) {
		$cron_wall_budget_hit = true;
		break;
	}
	$job_res = process_one_admin_job_filtered(array('module' => 'translations'));
	if (empty($job_res['processed'])) {
		break;
	}
	$processed++;
	if (!empty($job_res['ok'])) {
		$processed_ok++;
	} else {
		$processed_fail++;
	}
}
if (function_exists('site_telemetry_log_event')) {
	$tick_payload = array(
		'reaped' => (int)$res['reaped'],
		'enqueued' => (int)$res['enqueued'],
		'skipped' => (string)$res['skipped'],
		'activity_logs_deleted' => isset($res['activity_logs_deleted']) ? (int)$res['activity_logs_deleted'] : 0,
		'activity_jobs_deleted' => isset($res['activity_jobs_deleted']) ? (int)$res['activity_jobs_deleted'] : 0,
		'processed_jobs' => (int)$processed,
		'processed_ok' => (int)$processed_ok,
		'processed_failed' => (int)$processed_fail,
		'message' => (string)$res['message'],
	);
	if (defined('TRANSLATION_AUTOPILOT_BUILD')) {
		$tick_payload['autopilot_build'] = TRANSLATION_AUTOPILOT_BUILD;
	}
	if (!empty($res['cluster_blocking_detail'])) {
		$tick_payload['cluster_blocking_detail'] = $res['cluster_blocking_detail'];
	}
	$tick_payload['cron_max_wall_seconds'] = (int)$max_wall;
	$tick_payload['cron_wall_budget_hit'] = $cron_wall_budget_hit ? 1 : 0;
	site_telemetry_log_event('cron', 'translation_autopilot_tick', !empty($res['ok']) ? 'ok' : 'fail', $tick_payload, array('source' => 'cron_translation_autopilot'));
}
$line = date('c') . ' ' . ($res['ok'] ? 'OK' : 'FAIL') . ' ' . (string)$res['message']
	. ' reaped=' . (int)$res['reaped']
	. ' enqueued=' . (int)$res['enqueued']
	. ' skipped=' . (string)$res['skipped']
	. ' activity_logs_deleted=' . (isset($res['activity_logs_deleted']) ? (int)$res['activity_logs_deleted'] : 0)
	. ' activity_jobs_deleted=' . (isset($res['activity_jobs_deleted']) ? (int)$res['activity_jobs_deleted'] : 0)
	. ' processed=' . (int)$processed
	. ' ok=' . (int)$processed_ok
	. ' failed=' . (int)$processed_fail
	. ' wall_max=' . (int)$max_wall
	. ' wall_stop=' . ($cron_wall_budget_hit ? '1' : '0')
	. "\n";

if (php_sapi_name() !== 'cli') {
	header('Content-Type: text/plain; charset=utf-8');
}
echo $line;
if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
	exit(0);
}
