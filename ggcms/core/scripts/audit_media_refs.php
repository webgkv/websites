#!/usr/bin/env php
<?php
/**
 * List every unique files/media path referenced in DB and disk status.
 *
 * CLI: php audit_media_refs.php [--json]
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/media_library.php';
require_once ROOT_DIR . 'functions/media_image.php';

$as_json = in_array('--json', $_SERVER['argv'] ?? array(), true);
/** @var array<string,true> */
$paths = array();

function audit_media_refs_add($p, array &$paths) {
	$p = media_library_normalize_db_path($p);
	if ($p === '' || strpos($p, 'files/media/') !== 0) {
		return;
	}
	$paths[$p] = true;
}

function audit_media_refs_scan_html($html, array &$paths) {
	$html = (string)$html;
	if ($html === '' || stripos($html, '/files/media/') === false) {
		return;
	}
	if (preg_match_all('#/files/media/[^"\'\s>]+#i', $html, $m)) {
		foreach ($m[0] as $u) {
			audit_media_refs_add(ltrim($u, '/'), $paths);
		}
	}
}

$tables = array('games', 'guides', 'casino_articles', 'blog', 'pages', 'news');
foreach ($tables as $table) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		continue;
	}
	$cols = mysql_select('SHOW COLUMNS FROM `' . mysql_res($table) . '`', 'rows') ?: array();
	$colnames = array_column($cols, 'Field');
	if (in_array('img', $colnames, true)) {
		$rows = mysql_select("SELECT img FROM `" . mysql_res($table) . "` WHERE img LIKE 'files/media/%'", 'rows') ?: array();
		foreach ($rows as $row) {
			audit_media_refs_add($row['img'], $paths);
		}
	}
	if (in_array('text', $colnames, true)) {
		$rows = mysql_select("SELECT text FROM `" . mysql_res($table) . "` WHERE text LIKE '%/files/media/%'", 'rows') ?: array();
		foreach ($rows as $row) {
			audit_media_refs_scan_html($row['text'], $paths);
		}
	}
}

$ci_rows = mysql_select("SELECT content FROM content_i18n WHERE content LIKE '%/files/media/%'", 'rows') ?: array();
foreach ($ci_rows as $row) {
	audit_media_refs_scan_html($row['content'], $paths);
}

$missing = array();
$ok = array();
foreach (array_keys($paths) as $rel) {
	$resolved = media_image_resolve_disk_media_path($rel);
	if ($resolved !== '') {
		$ok[$rel] = $resolved;
	} else {
		$missing[] = $rel;
	}
}
sort($missing);

$report = array(
	'unique_refs' => count($paths),
	'on_disk' => count($ok),
	'missing' => count($missing),
	'missing_paths' => $missing,
);

if ($as_json) {
	echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
	exit(0);
}

echo "Unique refs: {$report['unique_refs']}\n";
echo "On disk:     {$report['on_disk']}\n";
echo "Missing:     {$report['missing']}\n\n";
foreach ($missing as $p) {
	echo "MISS\t{$p}\n";
}
