<?php
/**
 * Universal job runner library (functions only).
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

require_once ROOT_DIR . 'config/config.php';
require_once(ROOT_DIR . 'functions/mysql_func.php');
require_once(ROOT_DIR . 'functions/admin_jobs.php');
require_once(ROOT_DIR . 'functions/system_log.php');
require_once(ROOT_DIR . 'functions/site_telemetry.php');

// Job handlers
require_once __DIR__ . '/job_runner_translations.php';
require_once __DIR__ . '/job_runner_logs.php';
require_once __DIR__ . '/job_runner_seo_monitor.php';

function job_runner_dispatch($job) {
	$module = isset($job['module']) ? (string)$job['module'] : '';
	$action = isset($job['action']) ? (string)$job['action'] : '';
	$payload = array();
	if (!empty($job['payload'])) {
		$dec = json_decode((string)$job['payload'], true);
		if (is_array($dec)) $payload = $dec;
	}
	$fn = 'run_' . $module . '_' . $action;
	if (function_exists($fn)) {
		return $fn($payload, $job);
	}
	return array('ok' => false, 'message' => "Handler not found: {$fn}");
}

/**
 * Process one pending job from queue (admin_jobs).
 * @return array { processed: bool, ok: bool, message: string, job_id?: int }
 */
function process_one_admin_job_filtered($filters = array()) {
	$job = admin_jobs_lock_next($filters);
	if (!$job) {
		return array('processed' => false, 'ok' => true, 'message' => 'No pending jobs.');
	}
	$job_id = (int)$job['id'];
	$finished = false;
	// Ensure we don't leave jobs in `running` forever on fatal errors.
	register_shutdown_function(function () use ($job_id, &$finished) {
		if ($finished) return;
		if ($job_id <= 0) return;
		$err = error_get_last();
		$err_msg = is_array($err) && !empty($err['message']) ? (string)$err['message'] : 'Unexpected shutdown';
		$now = date('Y-m-d H:i:s');
		$st = mysql_select("SELECT status FROM admin_jobs WHERE id=" . (int)$job_id . " LIMIT 1", 'row');
		if ($st && isset($st['status']) && (string)$st['status'] === 'running') {
			mysql_fn('update', 'admin_jobs', array(
				'status' => 'failed',
				'message' => $err_msg,
				'finished_at' => $now,
				'updated_at' => $now,
			), " AND id=" . (int)$job_id . " ");
			system_log_add('jobs', 'error', "Job #{$job_id} crashed: {$err_msg}", array('job_id' => $job_id));
		}
	});

	$t0 = microtime(true);
	$res = job_runner_dispatch($job);
	$ok = is_array($res) && !empty($res['ok']);
	$msg = is_array($res) && isset($res['message']) ? (string)$res['message'] : ($ok ? 'OK' : 'Failed');
	$dur_ms = (int)round((microtime(true) - $t0) * 1000);
	site_telemetry_log_admin_job($job, $ok, $msg, $dur_ms);
	admin_jobs_finish($job_id, $ok, $msg);
	system_log_add('jobs', $ok ? 'info' : 'error', "Job #{$job_id} {$job['module']}/{$job['action']}: {$msg}", array('job_id' => (int)$job_id));
	$finished = true;
	return array('processed' => true, 'ok' => $ok, 'message' => $msg, 'job_id' => (int)$job_id);
}

function process_one_admin_job() {
	return process_one_admin_job_filtered(array());
}

/**
 * Run one job by id (admin action). Only pending jobs can be run.
 * @return array { ok: bool, message: string }
 */
function run_admin_job_by_id($job_id) {
	$job_id = (int)$job_id;
	if ($job_id <= 0) return array('ok' => false, 'message' => 'Invalid job id');
	$job = mysql_select("SELECT * FROM admin_jobs WHERE id=" . $job_id . " LIMIT 1", 'row');
	if (!$job) return array('ok' => false, 'message' => 'Job not found');
	if ($job['status'] !== 'pending') return array('ok' => false, 'message' => 'Only pending jobs can be run now');
	// Lock this job
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	mysql_fn('update', 'admin_jobs', array(
		'status' => 'running',
		'locked_at' => $now,
		'started_at' => $now,
		'updated_at' => $now,
	), " AND id=" . $job_id . " AND status='pending' ");
	$locked = mysql_select("SELECT * FROM admin_jobs WHERE id=" . $job_id . " LIMIT 1", 'row');
	if (!$locked || $locked['status'] !== 'running') {
		return array('ok' => false, 'message' => 'Could not lock job (already running?)');
	}
	$t0 = microtime(true);
	$res = job_runner_dispatch($locked);
	$ok = is_array($res) && !empty($res['ok']);
	$msg = is_array($res) && isset($res['message']) ? (string)$res['message'] : ($ok ? 'OK' : 'Failed');
	$dur_ms = (int)round((microtime(true) - $t0) * 1000);
	site_telemetry_log_admin_job($locked, $ok, $msg, $dur_ms);
	admin_jobs_finish($job_id, $ok, $msg);
	system_log_add('jobs', $ok ? 'info' : 'error', "Job #{$job_id} {$locked['module']}/{$locked['action']}: {$msg}", array('job_id' => $job_id));
	return array('ok' => $ok, 'message' => $msg);
}

