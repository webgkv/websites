<?php

// Canonical sitemap index URL: /api/sitemap/index_hub.xml — links to sitemap_{lang}_{NNN}.xml (see cron/run.php sitemap_build).
// /api/sitemap/index.xml redirects here (301) for legacy bookmarks and Search Console.

header('Content-type: text/xml; charset=UTF-8');

$config['cache'] = false;

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__FILE__) . '/../');
}

$xml = new SimpleXMLElement('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

$dir = ROOT_DIR . 'api/sitemap/';
$files = glob($dir . 'sitemap_*_*.xml');
if ($files) {
	$basenames = array_map('basename', $files);
	sort($basenames, SORT_NATURAL);
	$base = rtrim((string)($config['http_domain'] ?? ''), '/');
	// Fallback for edge cases where http_domain becomes "https:" (empty host).
	if ($base === '' || preg_match('#^https?:$#i', $base) || preg_match('#^https?://$#i', $base)) {
		require_once ROOT_DIR . 'functions/site_brand.php';
		require_once ROOT_DIR . 'functions/site_seo.php';
		$base = site_seo_sitemap_base_url();
	}
	foreach ($basenames as $bn) {
		if (!preg_match('/^sitemap_[a-z0-9]+_[0-9]{3}\.xml$/', $bn)) {
			continue;
		}
		$sitemap = $xml->addChild('sitemap');
		$sitemap->addChild('loc', $base . '/api/sitemap/' . $bn);
	}
}

echo $xml->asXML();
die();
