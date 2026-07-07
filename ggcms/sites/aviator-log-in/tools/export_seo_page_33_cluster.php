#!/usr/bin/env php
<?php
/**
 * Build seo_pages_33 full cluster JSON from files/i18n/pwa-ios-install.php + pwa_install_seo_cluster_content_html().
 * Output to stdout. See LOCALIZATION_GUIDE.md (entity pages, same HTML scaffold for every locale).
 */
$site = dirname(__DIR__) . '/site';
$bundles = require $site . '/files/i18n/pwa-ios-install.php';
require_once $site . '/functions/pwa_install.php';

$en = isset($bundles['en']) && is_array($bundles['en']) ? $bundles['en'] : array();

$merge = function ($langKey) use ($en, $bundles) {
	if ($langKey === 'en') {
		return $en;
	}
	$o = isset($bundles[$langKey]) && is_array($bundles[$langKey]) ? $bundles[$langKey] : array();
	return array_merge($en, $o);
};

/* lang_id, cluster lang_url (SEO file), key in pwa-ios-install.php (uk for Ukrainian) */
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
	$content = function_exists('pwa_install_seo_cluster_content_html') ? pwa_install_seo_cluster_content_html($b) : '';
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
			'entity_id' => 33,
		),
	);
}

$out = array(
	'schema' => 'seo_cluster_v1',
	'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
	'entity' => 'pages',
	'entity_id' => 33,
	'mode' => 'full',
	'locales' => $locales,
);

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n";
