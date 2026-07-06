#!/usr/bin/env php
<?php
/**
 * Convert raster files under files/media to WebP and rewrite DB HTML/img paths.
 *
 * CLI:
 *   php convert_entity_media_webp.php --entity=casino_articles --entity-id=10 [--apply]
 *   php convert_entity_media_webp.php --dir=files/media/2026/06 [--apply]
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
$entity = '';
$entity_id = 0;
$media_dir = '';
$import_dir = '';
$profile = 'content';

foreach ($_SERVER['argv'] ?? array() as $arg) {
	if ($arg === '--apply') {
		$apply = true;
	} elseif (strpos($arg, '--entity=') === 0) {
		$entity = substr($arg, 9);
	} elseif (strpos($arg, '--entity-id=') === 0) {
		$entity_id = (int)substr($arg, 12);
	} elseif (strpos($arg, '--dir=') === 0) {
		$media_dir = trim(substr($arg, 6), '/');
	} elseif (strpos($arg, '--import-dir=') === 0) {
		$import_dir = substr($arg, 13);
	} elseif (strpos($arg, '--profile=') === 0) {
		$profile = substr($arg, 10);
	}
}

/** @var array<string,string> */
$map = array();

function convert_media_register(array &$map, $rel_path, $profile, $apply) {
	$rel_path = media_library_normalize_db_path($rel_path);
	if ($rel_path === '') {
		return;
	}
	$abs = ROOT_DIR . $rel_path;
	if (!is_file($abs)) {
		echo "MISS disk: {$rel_path}\n";
		return;
	}
	$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
	if ($ext === 'webp') {
		$map[$rel_path] = $rel_path;
		$base = pathinfo($rel_path, PATHINFO_FILENAME);
		foreach (array('png', 'jpg', 'jpeg', 'gif') as $e) {
			$map[pathinfo($rel_path, PATHINFO_DIRNAME) . '/' . $base . '.' . $e] = $rel_path;
		}
		echo "OK already webp: {$rel_path}\n";
		return;
	}
	if (!$apply) {
		$webp_rel = pathinfo($rel_path, PATHINFO_DIRNAME) . '/' . pathinfo($rel_path, PATHINFO_FILENAME) . '.webp';
		$map[$rel_path] = $webp_rel;
		echo "[dry-run] {$rel_path} -> {$webp_rel}\n";
		return;
	}
	$norm = media_image_normalize_absolute($abs, $profile);
	if (!$norm['ok'] || empty($norm['rel'])) {
		echo "FAIL {$rel_path}: {$norm['message']}\n";
		return;
	}
	media_image_write_admin_thumb($norm['abs']);
	$map[$rel_path] = $norm['rel'];
	$base = pathinfo($rel_path, PATHINFO_FILENAME);
	$dir = pathinfo($rel_path, PATHINFO_DIRNAME);
	foreach (array('png', 'jpg', 'jpeg', 'gif', 'webp') as $e) {
		$map[$dir . '/' . $base . '.' . $e] = $norm['rel'];
	}
	echo "OK {$rel_path} -> {$norm['rel']}\n";
}

if ($import_dir !== '' && is_dir($import_dir)) {
	$dest = $media_dir !== '' ? $media_dir : 'files/media/' . date('Y/m');
	$dest_abs = ROOT_DIR . trim($dest, '/') . '/';
	if ($apply && !is_dir($dest_abs)) {
		mkdir($dest_abs, 0755, true);
	}
	foreach (scandir($import_dir) as $name) {
		if ($name === '.' || $name === '..') {
			continue;
		}
		$src = $import_dir . '/' . $name;
		if (!is_file($src)) {
			continue;
		}
		if ($apply) {
			copy($src, $dest_abs . $name);
			chmod($dest_abs . $name, 0644);
		}
		convert_media_register($map, trim($dest, '/') . '/' . $name, $profile, $apply);
	}
}

if ($media_dir !== '' && $import_dir === '') {
	$abs_dir = ROOT_DIR . $media_dir . '/';
	if (is_dir($abs_dir)) {
		foreach (scandir($abs_dir) as $name) {
			if ($name === '.' || $name === '..' || strpos($name, 'a-') === 0) {
				continue;
			}
			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			if (!media_image_is_raster_extension($ext)) {
				continue;
			}
			convert_media_register($map, $media_dir . '/' . $name, $profile, $apply);
		}
	}
}

function rewrite_media_paths_html($html, array $map) {
	$html = (string)$html;
	if ($html === '' || stripos($html, '/files/media/') === false) {
		return $html;
	}
	return preg_replace_callback(
		'#(/files/media/[^"\']+\.(?:png|jpe?g|gif))#i',
		function ($m) use ($map) {
			$path = ltrim($m[1], '/');
			if (isset($map[$path])) {
				return '/' . $map[$path];
			}
			$webp = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $path);
			if (media_library_file_exists($webp)) {
				return '/' . $webp;
			}
			return $m[0];
		},
		$html
	);
}

if ($entity !== '' && $entity_id > 0) {
	$rows = mysql_select(
		"SELECT id, lang_id, content FROM content_i18n WHERE entity='" . mysql_res($entity) . "'"
		. " AND entity_id=" . (int)$entity_id,
		'rows'
	) ?: array();
	foreach ($rows as $row) {
		$new = rewrite_media_paths_html($row['content'], $map);
		if ($new === $row['content']) {
			continue;
		}
		echo ($apply ? '' : '[dry-run] ') . "content_i18n#{$row['id']} lang={$row['lang_id']}\n";
		if ($apply) {
			mysql_fn('update', 'content_i18n', array('id' => (int)$row['id'], 'content' => $new));
		}
	}

	$table = preg_replace('/[^a-z_]/', '', $entity);
	if ($table !== '' && @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') > 0) {
		$main = mysql_select('SELECT text, img FROM `' . mysql_res($table) . '` WHERE id=' . (int)$entity_id . ' LIMIT 1', 'row');
		if ($main) {
			if (!empty($main['text'])) {
				$new_text = rewrite_media_paths_html($main['text'], $map);
				if ($new_text !== $main['text']) {
					echo ($apply ? '' : '[dry-run] ') . "{$table}.text id={$entity_id}\n";
					if ($apply) {
						mysql_fn('update', $table, array('id' => $entity_id, 'text' => $new_text));
					}
				}
			}
			if (!empty($main['img'])) {
				$img = media_library_normalize_db_path($main['img']);
				$new_img = isset($map[$img]) ? $map[$img] : $img;
				if (preg_match('/\.(png|jpe?g|gif)$/i', $new_img)) {
					$candidate = preg_replace('/\.(png|jpe?g|gif)$/i', '.webp', $new_img);
					if (media_library_file_exists($candidate)) {
						$new_img = $candidate;
					}
				}
				if ($new_img !== $img) {
					echo ($apply ? '' : '[dry-run] ') . "{$table}.img id={$entity_id}: {$img} -> {$new_img}\n";
					if ($apply) {
						mysql_fn('update', $table, array('id' => $entity_id, 'img' => $new_img));
					}
				}
			}
		}
	}
}

if ($apply) {
	media_library_invalidate_index();
}

echo 'Map entries: ' . count($map) . "\n";
