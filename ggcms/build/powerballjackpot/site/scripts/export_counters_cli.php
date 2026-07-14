#!/usr/bin/env php
<?php
/**
 * Export counters pack JSON (DB + settings).
 * Usage: php scripts/export_counters_cli.php [db|json|effective]
 */
if (php_sapi_name() !== 'cli') {
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

$source = isset($argv[1]) ? strtolower((string) $argv[1]) : 'db';
if ($source === 'effective') {
	$pack = site_counters_build_pack(
		site_counters_effective_list(),
		site_counters_load_settings()
	);
} else {
	$pack = site_counters_export_pack($source === 'json' ? 'json' : 'db');
}

echo json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
