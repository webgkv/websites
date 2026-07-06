#!/usr/bin/env php
<?php
/**
 * Cron CLI dispatcher.
 *
 *   php cron/run.php tick              — run due tasks (one crontab line, every minute)
 *   php cron/run.php tick --force      — run all enabled tasks now
 *   php cron/run.php <task>            — run a single task (manual / debug)
 *   php cron/run.php --show-path
 */
if (php_sapi_name() !== 'cli') {
	die("CLI only.\n");
}

require_once __DIR__ . '/tasks_registry.php';

$argv = isset($GLOBALS['argv']) ? $GLOBALS['argv'] : array();
if (isset($argv[1]) && $argv[1] === '--show-path') {
	echo (realpath(__FILE__) ?: __FILE__) . "\n";
	exit(0);
}

$task = null;
if (defined('CRON_TASK_LEGACY')) {
	$task = CRON_TASK_LEGACY;
} elseif (isset($argv[1]) && $argv[1] !== '' && $argv[1][0] !== '-') {
	$task = $argv[1];
}

$registry = cron_tasks_registry();
$tasks = array();
foreach ($registry as $id => $meta) {
	$tasks[$id] = $meta['file'];
}

if ($task === 'tick') {
	require_once __DIR__ . '/bootstrap.php';
	require_once dirname(__DIR__) . '/functions/cron_schedule.php';
	$force = false;
	foreach ($argv as $arg) {
		if ($arg === '--force') {
			$force = true;
		}
	}
	exit(cron_schedule_run_tick(array('force' => $force)));
}

if ($task === null || $task === '') {
	fwrite(STDERR, 'Usage: php ' . basename(__FILE__) . " tick [--force] | <task>\nTasks: " . implode(', ', array_keys($tasks)) . "\n");
	exit(1);
}
if (!isset($tasks[$task])) {
	fwrite(STDERR, "Unknown task: {$task}\n");
	exit(1);
}

require_once __DIR__ . '/bootstrap.php';
require __DIR__ . '/tasks/' . $tasks[$task];
