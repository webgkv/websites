<?php
/**
 * Export pages table (and schema) for upgrade planning.
 *
 * Run: /scripts/bd_upgrade.php?pull=1
 * Output: JSON with columns list and rows (pages ordered by left_key).
 *
 * To get current pages data manually in DB (e.g. for sharing):
 *   SELECT * FROM pages ORDER BY left_key;
 * To get table structure:
 *   SHOW COLUMNS FROM pages;
 *
 * Use the JSON output with bd_pages_upgrade.php?push=1 (or dry_run=1 to preview).
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_GET['pull']) || $_GET['pull'] != '1') {
	echo json_encode(array('error' => 'Use ?pull=1 to export pages data'));
	exit;
}

// Optional: require key in production to avoid public dump
// if (empty($_GET['key']) || $_GET['key'] !== 'YOUR_SECRET') { echo json_encode(array('error'=>'Forbidden')); exit; }

$out = array(
	'exported_at' => date('c'),
	'table'       => 'pages',
	'columns'     => array(),
	'rows'        => array(),
);

// Get column list
$cols = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
if (!$cols) {
	$out['error'] = 'Could not describe table pages';
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}
$out['columns'] = array_column($cols, 'Field');

// Get all rows (ordered by tree)
$rows = mysql_select("SELECT * FROM `pages` ORDER BY left_key ASC", 'rows');
if ($rows === false) {
	$out['error'] = 'Could not select from pages';
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// Normalize: ensure all keys present, strip resource types
foreach ($rows as $i => $row) {
	$out['rows'][] = $row;
}
$out['count'] = count($out['rows']);

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
