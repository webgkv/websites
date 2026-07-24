#!/usr/bin/env php
<?php
/**
 * Build install-pwa seo_cluster_v1 JSON from files/i18n/pwa-ios-install.php.
 *
 * Usage:
 *   php ggcms/scripts/shared/export_pwa_ios_install_cluster.php chickenroad [entity_id]
 */
if ($argc < 2) {
	fwrite(STDERR, "Usage: {$argv[0]} <brand-slug> [entity_id]\n");
	exit(1);
}

$brand = (string) $argv[1];
$entityId = isset($argv[2]) ? (int) $argv[2] : 33;

$repo = dirname(__DIR__, 2);
$site = $repo . '/sites/' . $brand . '/site';
$i18n = $site . '/files/i18n/pwa-ios-install.php';

if (!is_file($i18n)) {
	fwrite(STDERR, "Missing {$i18n}\n");
	exit(1);
}

$bundles = require $i18n;
if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', $site . '/');
}
require_once $site . '/functions/pwa_install.php';

$en = isset($bundles['en']) && is_array($bundles['en']) ? $bundles['en'] : array();

$merge = function ($langKey) use ($en, $bundles) {
	if ($langKey === 'en') {
		return $en;
	}
	$o = isset($bundles[$langKey]) && is_array($bundles[$langKey]) ? $bundles[$langKey] : array();
	return array_merge($en, $o);
};

$map = array(
	array(1, 'en', 'en'),
	array(3, 'fr', 'fr'),
	array(4, 'de', 'de'),
	array(6, 'es', 'es'),
	array(7, 'hi', 'hi'),
	array(8, 'pt', 'pt'),
	array(9, 'ru', 'ru'),
	array(11, 'ar', 'ar'),
	array(12, 'az', 'az'),
	array(13, 'bn', 'bn'),
	array(14, 'it', 'it'),
	array(15, 'nl', 'nl'),
	array(16, 'pl', 'pl'),
	array(17, 'vi', 'vi'),
	array(18, 'ua', 'uk'),
	array(19, 'ro', 'ro'),
);

$locales = array();
foreach ($map as $row) {
	$lid = (int) $row[0];
	$lurl = (string) $row[1];
	$key = (string) $row[2];
	$b = $merge($key);
	$content = function_exists('pwa_install_seo_cluster_content_html') ? pwa_install_seo_cluster_content_html($b, $key) : '';
	$is_en = ($key === 'en');
	$locales[] = array(
		'lang_id' => $lid,
		'lang_url' => $lurl,
		'url' => 'install-pwa',
		'name' => isset($b['install_short']) ? $b['install_short'] : '',
		'title' => isset($b['page_title']) ? $b['page_title'] : '',
		'description' => isset($b['meta_description']) ? $b['meta_description'] : '',
		'content' => $content,
		'status' => 'published',
		'source' => $is_en ? 'main' : 'content_i18n',
		'seo_monitor_ctx' => array(
			'entity' => 'pages',
			'entity_id' => $entityId,
		),
	);
}

$out = array(
	'schema' => 'seo_cluster_v1',
	'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
	'entity' => 'pages',
	'entity_id' => $entityId,
	'mode' => 'full',
	'locales' => $locales,
);

$refDir = $site . '/files/reference';
if (!is_dir($refDir)) {
	mkdir($refDir, 0755, true);
}
$file = $refDir . '/seo-pages-' . $entityId . '-install-pwa-full.json';
file_put_contents($file, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "Wrote {$file} (" . count($locales) . " locales)\n";
