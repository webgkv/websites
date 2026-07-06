#!/usr/bin/env php
<?php
/**
 * Remove DB/HTML references to files/media/ paths that do not exist on disk.
 *
 * CLI:  php purge_stale_media_paths.php --dry-run
 *        php purge_stale_media_paths.php --apply
 */
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
	header('Content-Type: text/plain; charset=utf-8');
}

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

if ($is_cli) {
	if (!isset($_SERVER['HTTP_HOST'])) {
		$_SERVER['HTTP_HOST'] = 'localhost';
	}
	if (!isset($_SERVER['REMOTE_ADDR'])) {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}
	if (!isset($_SERVER['SERVER_ADDR'])) {
		$_SERVER['SERVER_ADDR'] = '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/media_library.php';
require_once ROOT_DIR . 'functions/media_image.php';

$apply = false;
$dry_run = true;
if ($is_cli) {
	foreach ($_SERVER['argv'] ?? array() as $arg) {
		if ($arg === '--apply') {
			$apply = true;
			$dry_run = false;
		}
	}
} else {
	$apply = !empty($_GET['apply']);
	$dry_run = !$apply;
}

$report = array(
	'img_cleared' => 0,
	'content_rows' => 0,
	'content_imgs_removed' => 0,
	'text_rows' => 0,
);

$img_tables = array('games', 'guides', 'casino_articles', 'blog', 'pages', 'news');
foreach ($img_tables as $table) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		continue;
	}
	if (@mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "` LIKE 'img'", 'num_rows') <= 0) {
		continue;
	}
	$rows = mysql_select("SELECT id, img FROM `" . mysql_res($table) . "` WHERE img != '' AND img LIKE 'files/media/%'", 'rows');
	if (!is_array($rows)) {
		continue;
	}
	foreach ($rows as $row) {
		$rel = media_library_normalize_db_path($row['img']);
		if ($rel === '' || media_library_file_exists($rel)) {
			continue;
		}
		echo ($dry_run ? '[dry-run] ' : '') . "CLEAR {$table}.img id={$row['id']}: {$rel}\n";
		$report['img_cleared']++;
		if ($apply) {
			mysql_fn('update', $table, array('id' => (int)$row['id'], 'img' => ''));
		}
	}
}

if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$rows = mysql_select("SELECT id, entity, entity_id, lang_id, content FROM content_i18n WHERE content LIKE '%/files/media/%'", 'rows');
	if (is_array($rows)) {
		foreach ($rows as $row) {
			$purged = media_image_purge_missing_media_from_html((string)$row['content']);
			if ($purged['removed'] <= 0) {
				continue;
			}
			echo ($dry_run ? '[dry-run] ' : '') . "PURGE content_i18n id={$row['id']} entity={$row['entity']}#{$row['entity_id']} lang={$row['lang_id']}: {$purged['removed']} img(s)\n";
			$report['content_rows']++;
			$report['content_imgs_removed'] += (int)$purged['removed'];
			if ($apply) {
				mysql_fn('update', 'content_i18n', array(
					'id' => (int)$row['id'],
					'content' => $purged['html'],
					'updated_at' => date('Y-m-d H:i:s'),
				));
			}
		}
	}
}

$text_tables = array('pages', 'games', 'guides', 'casino_articles', 'blog');
foreach ($text_tables as $table) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		continue;
	}
	if (@mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "` LIKE 'text'", 'num_rows') <= 0) {
		continue;
	}
	$rows = mysql_select("SELECT id, text FROM `" . mysql_res($table) . "` WHERE text LIKE '%/files/media/%'", 'rows');
	if (!is_array($rows)) {
		continue;
	}
	foreach ($rows as $row) {
		$purged = media_image_purge_missing_media_from_html((string)$row['text']);
		if ($purged['html'] === (string)$row['text']) {
			continue;
		}
		echo ($dry_run ? '[dry-run] ' : '') . "PURGE {$table}.text id={$row['id']}: {$purged['removed']} img(s)\n";
		$report['text_rows']++;
		$report['content_imgs_removed'] += (int)$purged['removed'];
		if ($apply) {
			mysql_fn('update', $table, array('id' => (int)$row['id'], 'text' => $purged['html']));
		}
	}
}

echo "\nSummary: img_cleared={$report['img_cleared']} content_rows={$report['content_rows']} text_rows={$report['text_rows']} imgs_removed={$report['content_imgs_removed']}\n";
echo $dry_run ? "Dry run only. Re-run with --apply to write changes.\n" : "Applied.\n";

exit(0);
