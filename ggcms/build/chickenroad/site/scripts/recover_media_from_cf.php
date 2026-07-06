#!/usr/bin/env php
<?php
/**
 * Recover files/media assets from live site (Cloudflare cache) and wire DB.
 *
 * CLI:
 *   php recover_media_from_cf.php --dry-run
 *   php recover_media_from_cf.php --apply
 *   php recover_media_from_cf.php --apply --restore-content=/path/to/content_export.tsv
 */
$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli) {
	header('Content-Type: text/plain; charset=utf-8');
}

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

if ($is_cli) {
	foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
		if (!isset($_SERVER[$k])) {
			$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
		}
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/media_library.php';
require_once ROOT_DIR . 'functions/media_image.php';

$apply = false;
$dry_run = true;
$base_url = 'https://chickenroad.run';
$media_subdir = 'files/media/2026/05';
$content_export = '';
$min_bytes = 1024;

if ($is_cli) {
	foreach ($_SERVER['argv'] ?? array() as $arg) {
		if ($arg === '--apply') {
			$apply = true;
			$dry_run = false;
		} elseif (strpos($arg, '--base-url=') === 0) {
			$base_url = rtrim(substr($arg, 11), '/');
		} elseif (strpos($arg, '--restore-content=') === 0) {
			$content_export = substr($arg, 18);
		}
	}
}

/** @var array<int,string> */
$games_img = array(
	1  => 'chicken-road.jpg',
	2  => 'chicken-road-2.png',
	3  => 'chicken-road-2-bonus.jpg',
	4  => 'chicken-road-vegas.png',
	5  => 'chicken-road-gold.png',
	6  => 'game-chicken-road-race.jpeg',
	7  => 'chicken_road_series.png',
	8  => 'chicken-royal.png',
	9  => 'chicken-coin.jpeg',
	10 => 'chicken-banana.jpg',
	11 => 'chicken-shoot.jpg',
	12 => 'chicken-vs-zombies-inout.jpg',
);

/** @var array<int,string> */
$guides_img = array(
	1 => 'chicken-howto.png',
	2 => 'chicken-difficulty.png',
	3 => 'chicken-cashout.png',
	4 => 'chicken-demo.png',
	5 => 'chicken-mobile.png',
	6 => 'chicken-strategy.png',
	7 => 'chicken-mistakes.png',
	8 => 'chicken-safety.png',
);

$content_files = array(
	'chicken-banana.jpg',
	'chicken-coin.jpeg',
	'chicken-road-2-bonus.jpg',
	'chicken-road-2.png',
	'chicken-road-fail-round-1024x425.png',
	'chicken-road-gold.png',
	'chicken-road-vegas.png',
	'chicken-road.jpg',
	'chicken-royal.png',
	'chicken-shoot-game-1024x522.jpeg',
	'chicken-shoot-online-1024x548.jpeg',
	'chicken-shoot.jpg',
	'chicken-vs-zombies-inout.jpg',
	'chicken_road_series.png',
	'chickenroad-multipliers-1024x576.png',
	'download1.jpg',
	'download2.jpg',
	'download3.jpg',
	'fsportcasino.png',
	'game-chicken-road-race.jpeg',
	'inout-chicken-road-win-1024x576.png',
	'photo_5823550529383108476_y.jpg',
	'photo_5823550529383108478_y.jpg',
	'photo_5823550529383108479_y.jpg',
	'photo_5823550529383108480_y.jpg',
	'screenshot-2026-05-29-150035.png',
	'screenshot-2026-05-29-150235.png',
	'screenshot-2026-05-29-154958.png',
	'screenshot-2026-05-29-155253.png',
	'screenshot-2026-05-29-155641.png',
	'shoot-chicken-1024x576.jpeg',
);

$cover_files = array();
foreach (array_merge(array_values($games_img), array_values($guides_img)) as $f) {
	$cover_files[$f] = true;
}
$all_files = array();
foreach (array_merge(array_keys($cover_files), $content_files) as $f) {
	$all_files[$f] = true;
}

/**
 * @return array{ok:bool, message:string, rel?:string, bytes?:int}
 */
function recover_media_import_file($filename, $profile, $subdir, $base_url, $min_bytes, $dry_run) {
	$rel_dir = trim($subdir, '/');
	$dest_dir = ROOT_DIR . $rel_dir . '/';
	$dest_orig = $dest_dir . $filename;

	if (is_file($dest_orig)) {
		$ext = strtolower(pathinfo($dest_orig, PATHINFO_EXTENSION));
		$webp = $dest_dir . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
		if ($ext === 'webp' && is_file($dest_orig)) {
			return array('ok' => true, 'message' => 'Already on disk', 'rel' => $rel_dir . '/' . $filename, 'bytes' => filesize($dest_orig));
		}
		if (is_file($webp)) {
			return array('ok' => true, 'message' => 'WebP already exists', 'rel' => $rel_dir . '/' . basename($webp), 'bytes' => filesize($webp));
		}
		if (!$dry_run && media_image_is_raster_extension($ext)) {
			$norm = media_image_normalize_absolute($dest_orig, $profile);
			if ($norm['ok']) {
				if (media_image_is_raster_extension(pathinfo($norm['abs'], PATHINFO_EXTENSION))) {
					media_image_write_admin_thumb($norm['abs']);
				}
				media_library_invalidate_index();
				return array(
					'ok' => true,
					'message' => 'Normalized existing file',
					'rel' => $norm['rel'],
					'bytes' => is_file($norm['abs']) ? filesize($norm['abs']) : 0,
				);
			}
		}
		if ($dry_run) {
			return array('ok' => true, 'message' => 'Would normalize existing file', 'rel' => $rel_dir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp', 'bytes' => filesize($dest_orig));
		}
	}

	$url = rtrim($base_url, '/') . '/' . $rel_dir . '/' . $filename;
	$tmp = sys_get_temp_dir() . '/cr-recover-' . md5($filename) . '-' . basename($filename);

	if ($dry_run) {
		$ctx = stream_context_create(array('http' => array('timeout' => 20, 'ignore_errors' => true)));
		$data = @file_get_contents($url, false, $ctx);
		$bytes = is_string($data) ? strlen($data) : 0;
		if ($bytes < $min_bytes) {
			return array('ok' => false, 'message' => 'Not in cache (' . $bytes . ' bytes)', 'bytes' => $bytes);
		}
		return array('ok' => true, 'message' => 'Would import', 'rel' => $rel_dir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp', 'bytes' => $bytes);
	}

	if (!is_dir($dest_dir) && !mkdir($dest_dir, 0755, true)) {
		return array('ok' => false, 'message' => 'Cannot create directory');
	}

	$ctx = stream_context_create(array('http' => array('timeout' => 60, 'ignore_errors' => true)));
	$data = @file_get_contents($url, false, $ctx);
	if (!is_string($data) || strlen($data) < $min_bytes) {
		@unlink($tmp);
		return array('ok' => false, 'message' => 'Download failed or too small', 'bytes' => is_string($data) ? strlen($data) : 0);
	}
	file_put_contents($tmp, $data);

	if (!@copy($tmp, $dest_orig)) {
		@unlink($tmp);
		return array('ok' => false, 'message' => 'Cannot write destination');
	}
	@chmod($dest_orig, 0644);
	@unlink($tmp);

	$norm = media_image_normalize_absolute($dest_orig, $profile);
	if (!$norm['ok']) {
		@unlink($dest_orig);
		return array('ok' => false, 'message' => $norm['message']);
	}
	if (media_image_is_raster_extension(pathinfo($norm['abs'], PATHINFO_EXTENSION))) {
		media_image_write_admin_thumb($norm['abs']);
	}
	media_library_invalidate_index();

	return array(
		'ok' => true,
		'message' => $norm['message'],
		'rel' => $norm['rel'],
		'bytes' => is_file($norm['abs']) ? filesize($norm['abs']) : 0,
	);
}

/**
 * Normalize file already on disk (e.g. chicken-strategy.png).
 */
function recover_media_normalize_existing($filename, $profile, $subdir, $dry_run) {
	$rel_dir = trim($subdir, '/');
	$abs = ROOT_DIR . $rel_dir . '/' . $filename;
	if (!is_file($abs)) {
		return array('ok' => false, 'message' => 'Missing on disk');
	}
	if ($dry_run) {
		return array('ok' => true, 'message' => 'Would normalize', 'rel' => $rel_dir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.webp');
	}
	$norm = media_image_normalize_absolute($abs, $profile);
	if (!$norm['ok']) {
		return $norm;
	}
	if (media_image_is_raster_extension(pathinfo($norm['abs'], PATHINFO_EXTENSION))) {
		media_image_write_admin_thumb($norm['abs']);
	}
	media_library_invalidate_index();
	return array('ok' => true, 'message' => $norm['message'], 'rel' => $norm['rel']);
}

/** @var array<string,string> */
$imported = array();

echo ($dry_run ? '[dry-run] ' : '') . "Recover media from {$base_url}/{$media_subdir}\n\n";

foreach (array_keys($all_files) as $filename) {
	$profile = isset($cover_files[$filename]) ? 'card' : 'content';
	$res = recover_media_import_file($filename, $profile, $media_subdir, $base_url, $min_bytes, $dry_run);
	$tag = $res['ok'] ? 'OK' : 'MISS';
	$bytes = isset($res['bytes']) ? (int)$res['bytes'] : 0;
	echo "{$tag}\t{$filename}\t{$res['message']}" . ($bytes ? " ({$bytes} b)" : '') . "\n";
	if ($res['ok'] && !empty($res['rel'])) {
		$imported[$filename] = $res['rel'];
	}
}

// chicken-strategy may already exist as PNG only
if (!isset($imported['chicken-strategy.png'])) {
	$res = recover_media_normalize_existing('chicken-strategy.png', 'card', $media_subdir, $dry_run);
	echo ($res['ok'] ? 'OK' : 'MISS') . "\tchicken-strategy.png (local)\t{$res['message']}\n";
	if ($res['ok'] && !empty($res['rel'])) {
		$imported['chicken-strategy.png'] = $res['rel'];
	}
}

echo "\n--- DB img fields ---\n";
$db_updated = 0;
$db_skipped = 0;

foreach ($games_img as $id => $filename) {
	if (empty($imported[$filename])) {
		echo "SKIP games#{$id} — no file for {$filename}\n";
		$db_skipped++;
		continue;
	}
	$rel = $imported[$filename];
	echo ($dry_run ? '[dry-run] ' : '') . "SET games#{$id}.img = {$rel}\n";
	$db_updated++;
	if ($apply) {
		mysql_fn('update', 'games', array('id' => (int)$id, 'img' => $rel));
	}
}

foreach ($guides_img as $id => $filename) {
	if (empty($imported[$filename])) {
		echo "SKIP guides#{$id} — no file for {$filename}\n";
		$db_skipped++;
		continue;
	}
	$rel = $imported[$filename];
	echo ($dry_run ? '[dry-run] ' : '') . "SET guides#{$id}.img = {$rel}\n";
	$db_updated++;
	if ($apply) {
		mysql_fn('update', 'guides', array('id' => (int)$id, 'img' => $rel));
	}
}

if ($content_export !== '' && is_file($content_export)) {
	echo "\n--- Restore content_i18n from export ---\n";
	$lines = file($content_export, FILE_IGNORE_NEW_LINES);
	if (!is_array($lines)) {
		echo "Cannot read content export\n";
	} else {
		$content_rows = 0;
		foreach ($lines as $line) {
			if ($line === '') {
				continue;
			}
			$parts = str_getcsv($line, "\t");
			if (count($parts) < 5) {
				continue;
			}
			list($row_id, $entity, $entity_id, $lang_id, $b64) = $parts;
			$content = base64_decode($b64, true);
			if (!is_string($content) || $content === '') {
				continue;
			}
			$missing = 0;
			if (preg_match_all('#/files/media/2026/05/([^"\']+)#', $content, $m)) {
				foreach ($m[1] as $fn) {
					$base_fn = preg_replace('/\.webp$/i', '', $fn);
					$found = false;
					foreach ($imported as $src => $rel) {
						if ($src === $fn || $src === $base_fn || pathinfo($src, PATHINFO_FILENAME) === pathinfo($fn, PATHINFO_FILENAME)) {
							$content = str_replace('/files/media/2026/05/' . $fn, '/' . $rel, $content);
							$found = true;
							break;
						}
					}
					if (!$found) {
						$missing++;
					}
				}
			}
			$purged = media_image_purge_missing_media_from_html($content);
			$content = $purged['html'];
			if ($missing > 0) {
				echo "PARTIAL {$entity}#{$entity_id} lang={$lang_id} — {$missing} missing, purged={$purged['removed']}\n";
			}
			$prod = mysql_select(
				"SELECT id FROM content_i18n WHERE entity='" . mysql_res($entity) . "'"
				. " AND entity_id=" . (int)$entity_id
				. " AND lang_id=" . (int)$lang_id . " LIMIT 1",
				'row'
			);
			if (empty($prod['id'])) {
				echo "SKIP {$entity}#{$entity_id} lang={$lang_id} — no prod row\n";
				continue;
			}
			$prod_id = (int)$prod['id'];
			echo ($dry_run ? '[dry-run] ' : '') . "UPDATE content_i18n#{$prod_id} ({$entity}#{$entity_id} lang={$lang_id})\n";
			$content_rows++;
			if ($apply) {
				mysql_fn('update', 'content_i18n', array(
					'id' => $prod_id,
					'content' => $content,
				));
			}
		}
		echo "Content rows: {$content_rows}\n";
	}
}

echo "\nSummary: imported=" . count($imported) . " db_updates={$db_updated} db_skipped={$db_skipped}\n";
if ($dry_run) {
	echo "Run with --apply to write files and DB.\n";
}
