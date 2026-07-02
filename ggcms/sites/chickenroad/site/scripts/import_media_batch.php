#!/usr/bin/env php
<?php
/**
 * Import local files into files/media/YYYY/MM with WebP normalize.
 *
 * CLI:
 *   php import_media_batch.php --dir=/path/to/files --subdir=files/media/2026/05 [--dry-run] [--apply]
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
require_once ROOT_DIR . 'functions/media_library.php';
require_once ROOT_DIR . 'functions/media_image.php';

$apply = false;
$dir = '';
$subdir = 'files/media/2026/05';
$profile = 'content';

foreach ($_SERVER['argv'] ?? array() as $arg) {
	if ($arg === '--apply') {
		$apply = true;
	} elseif (strpos($arg, '--dir=') === 0) {
		$dir = substr($arg, 6);
	} elseif (strpos($arg, '--subdir=') === 0) {
		$subdir = substr($arg, 9);
	} elseif (strpos($arg, '--profile=') === 0) {
		$profile = substr($arg, 10);
	}
}

if ($dir === '' || !is_dir($dir)) {
	fwrite(STDERR, "Usage: php import_media_batch.php --dir=/path [--subdir=files/media/2026/05] [--profile=content|card] [--apply]\n");
	exit(1);
}

$rel_dir = trim($subdir, '/');
$dest_dir = ROOT_DIR . $rel_dir . '/';
/** @var array<string,string> */
$imported = array();

foreach (scandir($dir) as $name) {
	if ($name === '.' || $name === '..') {
		continue;
	}
	$src = $dir . '/' . $name;
	if (!is_file($src)) {
		continue;
	}
	$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
	if (!media_image_is_raster_extension($ext) && !media_image_passthrough_extension($ext)) {
		echo "SKIP unsupported: {$name}\n";
		continue;
	}

	$dest = $dest_dir . $name;
	echo ($apply ? '' : '[dry-run] ') . "IMPORT {$name}\n";

	if (!$apply) {
		$imported[$name] = $rel_dir . '/' . pathinfo($name, PATHINFO_FILENAME) . '.webp';
		continue;
	}

	if (!is_dir($dest_dir) && !mkdir($dest_dir, 0755, true)) {
		echo "FAIL mkdir {$rel_dir}\n";
		continue;
	}
	if (!@copy($src, $dest)) {
		echo "FAIL copy {$name}\n";
		continue;
	}
	@chmod($dest, 0644);

	if (media_image_is_raster_extension($ext)) {
		$norm = media_image_normalize_absolute($dest, $profile);
		if (!$norm['ok']) {
			@unlink($dest);
			echo "FAIL normalize {$name}: {$norm['message']}\n";
			continue;
		}
		if (media_image_is_raster_extension(pathinfo($norm['abs'], PATHINFO_EXTENSION))) {
			media_image_write_admin_thumb($norm['abs']);
		}
		$imported[$name] = $norm['rel'];
		echo "OK {$name} -> {$norm['rel']}\n";
	} else {
		$imported[$name] = $rel_dir . '/' . $name;
		echo "OK {$name} (passthrough)\n";
	}
}

if ($apply) {
	media_library_invalidate_index();
}

echo "Imported: " . count($imported) . "\n";
foreach ($imported as $src => $rel) {
	echo "  {$src} => {$rel}\n";
}
