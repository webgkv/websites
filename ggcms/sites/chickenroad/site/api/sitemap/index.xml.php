<?php

/**
 * Legacy URL /api/sitemap/index.xml — permanent redirect to /api/sitemap/index_hub.xml.
 * New filename avoids stale cached index in Search Console when parts change.
 * Routed via site/api/index.php (expects $config, mysql_select).
 */

$base = rtrim((string)($config['http_domain'] ?? ''), '/');
if ($base === '' || preg_match('#^https?:$#i', $base) || preg_match('#^https?://$#i', $base)) {
	require_once ROOT_DIR . 'functions/site_brand.php';
	require_once ROOT_DIR . 'functions/site_seo.php';
	$base = site_seo_sitemap_base_url();
}
header('Location: ' . $base . '/api/sitemap/index_hub.xml', true, 301);
exit;
