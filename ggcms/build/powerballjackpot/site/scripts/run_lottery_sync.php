<?php
/**
 * Manual homepage lottery sync (debug / one-off). Not for crontab — use cron/run.php tick (task lottery_sync).
 * CLI:  php site/scripts/run_lottery_sync.php
 * URL:  /scripts/run_lottery_sync.php?run=1
 */
$is_cli = (php_sapi_name() === 'cli');

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

$config_file = ROOT_DIR . 'config/config.php';
if (!is_file($config_file)) {
	if ($is_cli) {
		fwrite(STDERR, "Error: config/config.php not found.\n");
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('ok' => false, 'message' => 'Config not found'));
	exit(1);
}

if ($is_cli) {
	if (!isset($_SERVER['HTTP_HOST'])) {
		$_SERVER['HTTP_HOST'] = 'localhost';
	}
	if (!isset($_SERVER['REMOTE_ADDR'])) {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}
	if (!isset($_SERVER['SERVER_ADDR'])) {
		$_SERVER['SERVER_ADDR'] = '127.0.0.1';
	}
}

if (!$is_cli && empty($_GET['run'])) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('ok' => false, 'message' => 'Add ?run=1 to execute'));
	exit;
}

require_once $config_file;
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/lottery_sync.php';

$result = lottery_sync_run();

if ($is_cli) {
	echo ($result['ok'] ? 'OK' : 'FAIL') . ': ' . ($result['message'] ?? '') . "\n";
	if (!empty($result['log'])) {
		foreach ($result['log'] as $line) {
			echo '  - ' . $line . "\n";
		}
	}
	if (!empty($result['errors'])) {
		foreach ($result['errors'] as $line) {
			fwrite(STDERR, '  ! ' . $line . "\n");
		}
	}
	exit(!empty($result['ok']) ? 0 : 1);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
