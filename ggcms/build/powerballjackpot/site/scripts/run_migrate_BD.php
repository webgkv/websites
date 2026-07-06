<?php
/**
 * Universal DB migration runner. Run from CLI or by URL.
 * CLI:  php site/scripts/run_migrate_BD.php
 * URL:  /scripts/run_migrate_BD.php?run=1  (returns JSON)
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
	echo json_encode(array('error' => 'Config not found'));
	exit(1);
}

// Avoid "Undefined index" in config when run from CLI (no HTTP_HOST, REMOTE_ADDR, SERVER_ADDR)
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

require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');
require_once(ROOT_DIR . 'admin/actions/migrate_BD_run.php');

if ($is_cli) {
	if (count($done) > 0) {
		echo "Migration done: " . count($done) . " step(s)\n";
		foreach ($done as $s) echo "  - $s\n";
	} else {
		echo "Nothing to run (all steps already applied).\n";
	}
	exit(0);
}

// Browser: optional ?run=1 to avoid accidental run; return JSON
header('Content-Type: application/json; charset=utf-8');
if (count($done) > 0) {
	echo json_encode(array('ok' => true, 'steps' => $done, 'count' => count($done)));
} else {
	echo json_encode(array('ok' => true, 'steps' => array(), 'message' => 'Nothing to run (all steps already applied).'));
}
