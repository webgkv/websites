#!/usr/bin/env php
<?php
/**
 * Ensure addon languages exist in DB (Swahili id=20, Lingala id=21) and update Portuguese metadata.
 *
 * Usage: php scripts/ensure_languages_cli.php
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/site_seo.php';

$ref_path = ROOT_DIR . 'files/reference/languages_addon.json';
if (!is_file($ref_path)) {
	fwrite(STDERR, "Missing $ref_path\n");
	exit(1);
}
$ref = json_decode(file_get_contents($ref_path), true);
if (!is_array($ref)) {
	fwrite(STDERR, "Invalid languages_addon.json\n");
	exit(1);
}

function ensure_language_row(array $row) {
	$id = isset($row['id']) ? (int) $row['id'] : 0;
	$url = isset($row['url']) ? trim((string) $row['url']) : '';
	$name = isset($row['name']) ? trim((string) $row['name']) : '';
	$localization = isset($row['localization']) ? trim((string) $row['localization']) : $url;
	$rank = isset($row['rank']) ? (int) $row['rank'] : 0;
	$display = !empty($row['display']) ? 1 : 0;
	if ($id <= 0 || $url === '' || $name === '') {
		return array('ok' => false, 'message' => 'Invalid language row');
	}

	$by_id = mysql_select("SELECT id FROM languages WHERE id = " . (int) $id . " LIMIT 1", 'row');
	$by_url = mysql_select("SELECT id FROM languages WHERE url = '" . mysql_res($url) . "' LIMIT 1", 'row');
	if ($by_url && !empty($by_url['id']) && (int) $by_url['id'] !== $id) {
		return array('ok' => false, 'message' => "URL $url already used by id " . (int) $by_url['id']);
	}

	$data = array(
		'name' => $name,
		'rank' => $rank,
		'url' => $url,
		'localization' => $localization,
		'display' => $display,
	);
	if ($by_id && !empty($by_id['id'])) {
		$data['id'] = $id;
		mysql_fn('update', 'languages', $data);
		$action = 'updated';
	} else {
		$data['id'] = $id;
		mysql_fn('insert', 'languages', $data);
		$action = 'inserted';
	}

	$row_ts = mysql_select("SELECT id, value FROM variables WHERE `key` = 'translation_settings' LIMIT 1", 'row');
	if ($row_ts && $row_ts['value'] !== '') {
		$dec = json_decode($row_ts['value'], true);
		if (is_array($dec)) {
			if (empty($dec['enabled_lang_ids']) || !is_array($dec['enabled_lang_ids'])) {
				$dec['enabled_lang_ids'] = array();
			}
			$ids = array_values(array_filter(array_map('intval', $dec['enabled_lang_ids'])));
			if (!in_array($id, $ids, true)) {
				$ids[] = $id;
				$dec['enabled_lang_ids'] = $ids;
				mysql_fn('update', 'variables', array('id' => (int) $row_ts['id'], 'value' => json_encode($dec, JSON_UNESCAPED_UNICODE)));
			}
		}
	}

	if (function_exists('site_seo_sitemap_languages_ensure_ids')) {
		site_seo_sitemap_languages_ensure_ids(array($id));
	}

	return array('ok' => true, 'message' => "$action language $name ($url, id=$id)");
}

$messages = array();
if (!empty($ref['languages']) && is_array($ref['languages'])) {
	foreach ($ref['languages'] as $row) {
		if (!is_array($row)) {
			continue;
		}
		$res = ensure_language_row($row);
		$messages[] = $res['message'];
		if (!$res['ok']) {
			fwrite(STDERR, $res['message'] . "\n");
			exit(1);
		}
	}
}

if (!empty($ref['portuguese']) && is_array($ref['portuguese'])) {
	$res = ensure_language_row(array_merge($ref['portuguese'], array('display' => 1, 'rank' => 50)));
	$messages[] = $res['message'];
	if (!$res['ok']) {
		fwrite(STDERR, $res['message'] . "\n");
		exit(1);
	}
}

echo implode("\n", $messages) . "\n";
