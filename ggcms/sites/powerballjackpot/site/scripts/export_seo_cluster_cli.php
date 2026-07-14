#!/usr/bin/env php
<?php
/**
 * CLI: export seo cluster JSON from DB (pages#33 etc.).
 * Usage: php export_seo_cluster_cli.php pages 33 full > seo-pages-33-full.json
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$entity = $argv[1] ?? 'pages';
$entity_id = (int)($argv[2] ?? 0);
$mode = $argv[3] ?? 'full';

if ($entity_id <= 0) {
	fwrite(STDERR, "Usage: php export_seo_cluster_cli.php <entity> <entity_id> [meta|full]\n");
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

$pack = seo_monitor_export_cluster_array($entity, $entity_id, $mode === 'meta' ? 'meta' : 'full');
if (empty($pack['ok']) || empty($pack['data'])) {
	fwrite(STDERR, json_encode($pack, JSON_UNESCAPED_UNICODE) . "\n");
	exit(1);
}

echo json_encode($pack['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
