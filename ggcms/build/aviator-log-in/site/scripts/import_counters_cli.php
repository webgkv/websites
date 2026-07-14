#!/usr/bin/env php
<?php
/**
 * Import counters pack JSON into DB and/or reference file.
 * Usage: php scripts/import_counters_cli.php /path/to/counters.json [db|file|both]
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$file = isset($argv[1]) ? (string) $argv[1] : '';
$target = isset($argv[2]) ? strtolower((string) $argv[2]) : 'both';
if ($file === '' || !is_file($file)) {
	fwrite(STDERR, "Usage: php import_counters_cli.php <json-file> [db|file|both]\n");
	exit(1);
}
if (!in_array($target, array('db', 'file', 'both'), true)) {
	fwrite(STDERR, "Target must be db, file, or both\n");
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/site_counters.php';

$raw = file_get_contents($file);
$pack = json_decode($raw, true);
if (!is_array($pack)) {
	fwrite(STDERR, "Invalid JSON\n");
	exit(1);
}

$res = site_counters_import_pack($pack, $target);
fwrite($res['ok'] ? STDOUT : STDERR, $res['message'] . "\n");
exit($res['ok'] ? 0 : 1);
