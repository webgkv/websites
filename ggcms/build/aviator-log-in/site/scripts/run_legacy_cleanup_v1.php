#!/usr/bin/env php
<?php
/**
 * One-time legacy content cleanup (optional). Not part of run_migrate_BD.php.
 *
 * CLI:  php run_legacy_cleanup_v1.php
 *        php run_legacy_cleanup_v1.php --force   (re-apply after editing migrate_legacy_cleanup_v1.php)
 * Web:  /scripts/run_legacy_cleanup_v1.php?run=1
 *        /scripts/run_legacy_cleanup_v1.php?run=1&force=1
 *
 * See admin/actions/migrate_legacy_cleanup_v1.php for rules.
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

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'admin/actions/migrate_legacy_cleanup_v1.php';

$force = false;
if ($is_cli) {
	foreach ($_SERVER['argv'] ?? array() as $arg) {
		if ($arg === '--force' || $arg === '-f') {
			$force = true;
			break;
		}
	}
} else {
	$force = !empty($_GET['force']);
	$run = !empty($_GET['run']);
	if (!$run) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'error' => 'Add ?run=1 to execute (optional &force=1 to re-run).',
		));
		exit(0);
	}
}

if (!$force && @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$already = mysql_select("SELECT id, value FROM variables WHERE `key`='migration_legacy_cleanup_v1' LIMIT 1", 'row');
	if ($already) {
		$msg = 'Already applied (see variables.migration_legacy_cleanup_v1). Use --force or ?force=1 after DELETE or to re-run.';
		if ($is_cli) {
			echo $msg . "\n";
			exit(0);
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('ok' => false, 'message' => $msg, 'skipped' => true));
		exit(0);
	}
}

if (@mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') <= 0) {
	$msg = 'Table pages not found; nothing to do.';
	if ($is_cli) {
		echo $msg . "\n";
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('ok' => false, 'message' => $msg));
	exit(1);
}

$mrep = migrate_legacy_cleanup_v1_apply();
$enc = json_encode($mrep, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($enc !== false && @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$ex = mysql_select("SELECT id FROM variables WHERE `key`='migration_legacy_cleanup_v1' LIMIT 1", 'row');
	if ($ex && isset($ex['id'])) {
		mysql_fn('update', 'variables', array(
			'id' => (int)$ex['id'],
			'value' => (string)$enc,
		));
	} else {
		mysql_fn('insert', 'variables', array(
			'key' => 'migration_legacy_cleanup_v1',
			'value' => (string)$enc,
		));
	}
}

$summary = isset($mrep['summary']) ? (string)$mrep['summary'] : '';

if ($is_cli) {
	echo "Legacy cleanup v1: " . $summary . "\n";
	exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('ok' => true, 'result' => $mrep), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
