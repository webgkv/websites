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
		$canonical_base = '';
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
			$row_seo = mysql_select("SELECT value FROM `variables` WHERE `key`='seo_structured' LIMIT 1", 'row');
			if ($row_seo && isset($row_seo['value']) && (string)$row_seo['value'] !== '') {
				$dec = @json_decode((string)$row_seo['value'], true);
				if (is_array($dec) && !empty($dec['canonical_base'])) {
					$canonical_base = trim((string)$dec['canonical_base']);
				}
			}
		}
		if ($canonical_base !== '') $base = rtrim($canonical_base, '/');
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
