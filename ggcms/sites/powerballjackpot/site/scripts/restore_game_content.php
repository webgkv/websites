#!/usr/bin/env php
<?php
/**
 * Restore games content (content_i18n + games.text EN) from export TSV.
 *
 * CLI: php restore_game_content.php --export=recover_content_media_export.tsv [--apply]
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
$export = ROOT_DIR . 'scripts/recover_content_media_export.tsv';
$media_dir = 'files/media/2026/05';

foreach ($_SERVER['argv'] ?? array() as $arg) {
	if ($arg === '--apply') {
		$apply = true;
	} elseif (strpos($arg, '--export=') === 0) {
		$export = substr($arg, 9);
	}
}

if (!is_file($export)) {
	fwrite(STDERR, "Export not found: {$export}\n");
	exit(1);
}

/** @var array<string,string> */
$webp_map = array();
$abs_dir = ROOT_DIR . $media_dir . '/';
if (is_dir($abs_dir)) {
	foreach (scandir($abs_dir) as $f) {
		if (!preg_match('/\.webp$/i', $f)) {
			continue;
		}
		$base = pathinfo($f, PATHINFO_FILENAME);
		$rel = $media_dir . '/' . $f;
		$webp_map[$base . '.webp'] = $rel;
		foreach (array('jpg', 'jpeg', 'png', 'gif', 'webp') as $ext) {
			$webp_map[$base . '.' . $ext] = $rel;
		}
	}
}

function restore_map_media_html($html, array $webp_map) {
	$html = (string)$html;
	if ($html === '') {
		return $html;
	}
	$html = preg_replace_callback(
		'#(/files/media/2026/05/)([^"\']+)#',
		function ($m) use ($webp_map) {
			$fn = $m[2];
			if (isset($webp_map[$fn])) {
				return '/' . $webp_map[$fn];
			}
			$base = pathinfo($fn, PATHINFO_FILENAME);
			if (isset($webp_map[$base . '.webp'])) {
				return '/' . $webp_map[$base . '.webp'];
			}
			return $m[0];
		},
		$html
	);
	$nav_map = array(
		'/images/games/navigator-1024x576-1-1024x576.jpg' => 'navigator-1024x576-1-1024x576.jpg',
		'/images/games/navigator-2-508x1024.png' => 'navigator-2-508x1024.png',
	);
	foreach ($nav_map as $old => $key) {
		if (isset($webp_map[$key])) {
			$html = str_replace($old, '/' . $webp_map[$key], $html);
		} elseif (isset($webp_map['chicken-road-vegas.png'])) {
			$html = str_replace($old, '/' . $webp_map['chicken-road-vegas.png'], $html);
		}
	}
	$purged = media_image_purge_missing_media_from_html($html);
	return $purged['html'];
}

$lines = file($export, FILE_IGNORE_NEW_LINES);
$ci = 0;
$text = 0;
$en_text_ids = array();

foreach ($lines as $line) {
	if ($line === '') {
		continue;
	}
	$parts = str_getcsv($line, "\t", '"', '\\');
	if (count($parts) < 5 || $parts[1] !== 'games') {
		continue;
	}
	list(, , $entity_id, $lang_id, $b64) = $parts;
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$content = base64_decode($b64, true);
	if (!is_string($content) || $content === '') {
		continue;
	}
	$content = restore_map_media_html($content, $webp_map);

	$prod = mysql_select(
		"SELECT id FROM content_i18n WHERE entity='games'"
		. " AND entity_id={$entity_id} AND lang_id={$lang_id} LIMIT 1",
		'row'
	);
	if (empty($prod['id'])) {
		echo "SKIP content_i18n games#{$entity_id} lang={$lang_id} — no row\n";
		continue;
	}
	echo ($apply ? '' : '[dry-run] ') . "content_i18n games#{$entity_id} lang={$lang_id}\n";
	$ci++;
	if ($apply) {
		mysql_fn('update', 'content_i18n', array('id' => (int)$prod['id'], 'content' => $content));
	}
	if ($lang_id === 1) {
		$en_text_ids[$entity_id] = $content;
	}
}

foreach ($en_text_ids as $entity_id => $content) {
	echo ($apply ? '' : '[dry-run] ') . "games.text id={$entity_id}\n";
	$text++;
	if ($apply) {
		mysql_fn('update', 'games', array('id' => (int)$entity_id, 'text' => $content));
	}
}

echo "Done: content_i18n={$ci} games.text={$text}\n";
