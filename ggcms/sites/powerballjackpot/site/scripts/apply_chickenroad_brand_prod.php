#!/usr/bin/env php
<?php
/**
 * One-shot prod helper: seo_structured preset + rebrand pages install-pwa/apk content_i18n.
 * CLI: php scripts/apply_chickenroad_brand_prod.php
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'chickenroad.run';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/site_brand.php';
require_once ROOT_DIR . 'functions/site_seo.php';

$out = array('ok' => true, 'steps' => array());

// —— seo_structured ——
$preset_path = ROOT_DIR . 'files/reference/seo_structured-chickenroad-preset.json';
if (is_file($preset_path)) {
	$dec = json_decode(file_get_contents($preset_path), true);
	if (is_array($dec)) {
		site_seo_normalize_structured($dec);
		$payload = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		require_once ROOT_DIR . 'functions/site_telemetry.php';
		if (function_exists('site_telemetry_variable_upsert')) {
			site_telemetry_variable_upsert('seo_structured', $payload);
			$out['steps'][] = 'seo_structured via site_telemetry_variable_upsert';
		} else {
			$row = mysql_select("SELECT id FROM variables WHERE `key`='seo_structured' LIMIT 1", 'row');
			if ($row && !empty($row['id'])) {
				mysql_fn('update', 'variables', array('value' => $payload), ' AND id=' . (int) $row['id']);
				$out['steps'][] = 'seo_structured updated id=' . $row['id'];
			} else {
				mysql_fn('insert', 'variables', array('key' => 'seo_structured', 'value' => $payload));
				$out['steps'][] = 'seo_structured inserted';
			}
		}
	}
}

// —— Rebrand install-apk / install-pwa rows in content_i18n ——
$page_urls = array('install-apk', 'install-pwa', 'ios-pwa');
$pages = mysql_select(
	"SELECT id, url FROM pages WHERE module='pages' AND url IN ('install-apk','install-pwa','ios-pwa')",
	'rows'
);
$ids = array();
foreach ($pages as $p) {
	$ids[] = (int) $p['id'];
}
if (!empty($ids)) {
	$id_list = implode(',', $ids);
	$rows = mysql_select(
		"SELECT id, entity_id, lang_id, title, description, content, name FROM content_i18n WHERE entity='pages' AND entity_id IN ($id_list)",
		'rows'
	);
	$updated = 0;
	foreach ($rows as $r) {
		$patch = array();
		foreach (array('title', 'description', 'content', 'name') as $f) {
			if (!isset($r[$f]) || (string) $r[$f] === '') {
				continue;
			}
			$new = site_brand_rebrand_text((string) $r[$f]);
			if ($new !== (string) $r[$f]) {
				$patch[$f] = $new;
			}
		}
		if (!empty($patch)) {
			mysql_fn('update', 'content_i18n', $patch, ' AND id=' . (int) $r['id']);
			$updated++;
		}
	}
	$out['steps'][] = 'content_i18n rebranded rows=' . $updated . ' pages=' . implode(',', $ids);
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
