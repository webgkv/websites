#!/usr/bin/env php
<?php
/**
 * CLI: import seo-pages cluster JSON into content_i18n (pages#1 etc.).
 * Usage: php scripts/import_seo_cluster_cli.php /path/to/seo-pages-1-full.json pages 1 full
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$json_path = $argv[1] ?? '';
$entity = $argv[2] ?? 'pages';
$entity_id = (int)($argv[3] ?? 1);
$mode = $argv[4] ?? 'full';

if ($json_path === '' || !is_file($json_path)) {
	fwrite(STDERR, "Usage: php import_seo_cluster_cli.php <json-file> [entity] [entity_id] [meta|full]\n");
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/string_func.php';
require_once ROOT_DIR . 'functions/seo_monitor.php';

$raw = file_get_contents($json_path);
$j = json_decode($raw, true);
if (!is_array($j)) {
	fwrite(STDERR, "Invalid JSON\n");
	exit(1);
}

$res = seo_monitor_import_cluster($entity, $entity_id, $j, $mode === 'meta' ? 'meta' : 'full', false);
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit(!empty($res['ok']) ? 0 : 1);
