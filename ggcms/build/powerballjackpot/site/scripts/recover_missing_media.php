#!/usr/bin/env php
<?php
/**
 * Recover missing files/media assets referenced in DB from live site (CF cache).
 *
 * CLI:
 *   php recover_missing_media.php --dry-run
 *   php recover_missing_media.php --apply
 *   php recover_missing_media.php --apply --fix-db
 */
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
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

$apply = false;
$fix_db = false;
$base_url = 'https://powerballjackpot.run';
$min_bytes = 512;

foreach ($_SERVER['argv'] ?? array() as $arg) {
	if ($arg === '--apply') {
		$apply = true;
	} elseif ($arg === '--fix-db') {
		$fix_db = true;
	} elseif (strpos($arg, '--base-url=') === 0) {
		$base_url = rtrim(substr($arg, 11), '/');
	}
}

/** @var array<string,true> */
$paths = array();

function recover_collect_path($p, array &$paths) {
	$p = media_library_normalize_db_path($p);
	if ($p !== '' && strpos($p, 'files/media/') === 0) {
		$paths[$p] = true;
	}
}

function recover_scan_html($html, array &$paths) {
	if (!preg_match_all('#/files/media/[^"\'\s>]+#i', (string)$html, $m)) {
		return;
	}
	foreach ($m[0] as $u) {
		recover_collect_path(ltrim($u, '/'), $paths);
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
		foreach (mysql_select("SELECT img FROM `" . mysql_res($table) . "` WHERE img LIKE 'files/media/%'", 'rows') ?: array() as $row) {
			recover_collect_path($row['img'], $paths);
		}
	}
	if (in_array('text', $colnames, true)) {
		foreach (mysql_select("SELECT text FROM `" . mysql_res($table) . "` WHERE text LIKE '%/files/media/%'", 'rows') ?: array() as $row) {
			recover_scan_html($row['text'], $paths);
		}
	}
}
foreach (mysql_select("SELECT content FROM content_i18n WHERE content LIKE '%/files/media/%'", 'rows') ?: array() as $row) {
	recover_scan_html($row['content'], $paths);
}

$missing = array();
foreach (array_keys($paths) as $rel) {
	if (media_image_resolve_disk_media_path($rel) !== '') {
		continue;
	}
	$missing[$rel] = true;
}

echo ($apply ? '' : '[dry-run] ') . 'Missing unique refs: ' . count($missing) . "\n";

$recovered = 0;
$failed = 0;
/** @var array<string,string> */
$imported = array();

foreach (array_keys($missing) as $rel) {
	$filename = basename($rel);
	$subdir = pathinfo($rel, PATHINFO_DIRNAME);
	$url = $base_url . '/' . $rel;
	$dest_abs = ROOT_DIR . $rel;

	echo ($apply ? '' : '[dry-run] ') . "TRY {$rel}\n";

	if (!$apply) {
		$ctx = stream_context_create(array('http' => array('timeout' => 15, 'ignore_errors' => true)));
		$data = @file_get_contents($url, false, $ctx);
		$bytes = is_string($data) ? strlen($data) : 0;
		if ($bytes >= $min_bytes) {
			echo "  would recover ({$bytes} b)\n";
			$recovered++;
		} else {
			echo "  CF/origin miss ({$bytes} b)\n";
			$failed++;
		}
		continue;
	}

	$ctx = stream_context_create(array('http' => array('timeout' => 60, 'ignore_errors' => true)));
	$data = @file_get_contents($url, false, $ctx);
	if (!is_string($data) || strlen($data) < $min_bytes) {
		echo "  MISS\n";
		$failed++;
		continue;
	}

	$dir = dirname($dest_abs);
	if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
		echo "  FAIL mkdir\n";
		$failed++;
		continue;
	}
	if (!@file_put_contents($dest_abs, $data)) {
		echo "  FAIL write\n";
		$failed++;
		continue;
	}
	@chmod($dest_abs, 0644);

	$profile = (preg_match('#-(1024x\d+|card)#', $filename) || strpos($filename, 'screenshot-') === 0) ? 'content' : 'content';
	if (media_image_is_raster_extension(pathinfo($dest_abs, PATHINFO_EXTENSION))) {
		$norm = media_image_normalize_absolute($dest_abs, $profile);
		if (!$norm['ok']) {
			@unlink($dest_abs);
			echo "  FAIL webp: {$norm['message']}\n";
			$failed++;
			continue;
		}
		media_image_write_admin_thumb($norm['abs']);
		$imported[$rel] = $norm['rel'];
		echo "  OK -> {$norm['rel']}\n";
	} else {
		$imported[$rel] = $rel;
		echo "  OK (passthrough)\n";
	}
	$recovered++;
}

media_library_invalidate_index();

if ($apply && $fix_db) {
	echo "\n--- fix-db ---\n";
	passthru(PHP_BINARY . ' ' . escapeshellarg(ROOT_DIR . 'scripts/audit_media_health.php') . ' --apply');
}

echo "\nSummary: recovered={$recovered} failed={$failed}\n";
