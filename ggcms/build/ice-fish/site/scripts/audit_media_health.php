#!/usr/bin/env php
<?php
/**
 * Audit files/media references vs disk; optional --apply to rewrite .png→.webp and purge ghosts.
 *
 * CLI: php audit_media_health.php [--apply] [--limit=50]
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
$limit = 50;
foreach ($_SERVER['argv'] ?? array() as $arg) {
	if ($arg === '--apply') {
		$apply = true;
	} elseif (strpos($arg, '--limit=') === 0) {
		$limit = max(1, (int)substr($arg, 8));
	}
}

$stats = array('img_fixed' => 0, 'img_cleared' => 0, 'content_rows' => 0, 'imgs_removed' => 0, 'imgs_rewritten' => 0);

$img_tables = array('games', 'guides', 'casino_articles', 'blog', 'pages', 'news');
foreach ($img_tables as $table) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		continue;
	}
	if (@mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "` LIKE 'img'", 'num_rows') <= 0) {
		continue;
	}
	$rows = mysql_select("SELECT id, img FROM `" . mysql_res($table) . "` WHERE img != '' AND img LIKE 'files/media/%'", 'rows') ?: array();
	foreach ($rows as $row) {
		$rel = media_library_normalize_db_path($row['img']);
		$resolved = media_image_resolve_disk_media_path($rel);
		if ($resolved === $rel) {
			continue;
		}
		if ($resolved === '') {
			echo ($apply ? '' : '[dry-run] ') . "CLEAR {$table}.img#{$row['id']}: {$rel}\n";
			$stats['img_cleared']++;
			if ($apply) {
				mysql_fn('update', $table, array('id' => (int)$row['id'], 'img' => ''));
			}
		} else {
			echo ($apply ? '' : '[dry-run] ') . "FIX {$table}.img#{$row['id']}: {$rel} -> {$resolved}\n";
			$stats['img_fixed']++;
			if ($apply) {
				mysql_fn('update', $table, array('id' => (int)$row['id'], 'img' => $resolved));
			}
		}
	}
}

$n = 0;
$rows = mysql_select("SELECT id, entity, entity_id, lang_id, content FROM content_i18n WHERE content LIKE '%/files/media/%' ORDER BY updated_at DESC", 'rows') ?: array();
foreach ($rows as $row) {
	if ($n >= $limit) {
		break;
	}
	$fin = media_image_finalize_html_media_refs($row['content']);
	if ($fin['html'] === $row['content']) {
		continue;
	}
	echo ($apply ? '' : '[dry-run] ') . "content_i18n#{$row['id']} {$row['entity']}#{$row['entity_id']} lang={$row['lang_id']} rewritten={$fin['rewritten']} removed={$fin['removed']}\n";
	$stats['content_rows']++;
	$stats['imgs_rewritten'] += $fin['rewritten'];
	$stats['imgs_removed'] += $fin['removed'];
	if ($apply) {
		mysql_fn('update', 'content_i18n', array('id' => (int)$row['id'], 'content' => $fin['html']));
	}
	$n++;
}

foreach (array('games', 'guides', 'casino_articles', 'blog', 'pages') as $table) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		continue;
	}
	if (@mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "` LIKE 'text'", 'num_rows') <= 0) {
		continue;
	}
	$trows = mysql_select("SELECT id, text FROM `" . mysql_res($table) . "` WHERE text LIKE '%/files/media/%'", 'rows') ?: array();
	foreach ($trows as $row) {
		$fin = media_image_finalize_html_media_refs($row['text']);
		if ($fin['html'] === $row['text']) {
			continue;
		}
		echo ($apply ? '' : '[dry-run] ') . "{$table}.text#{$row['id']} rewritten={$fin['rewritten']} removed={$fin['removed']}\n";
		$stats['content_rows']++;
		$stats['imgs_rewritten'] += $fin['rewritten'];
		$stats['imgs_removed'] += $fin['removed'];
		if ($apply) {
			mysql_fn('update', $table, array('id' => (int)$row['id'], 'text' => $fin['html']));
		}
	}
}

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
