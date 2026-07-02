<?php

/**
 * Legacy URL /api/sitemap/index.xml — permanent redirect to /api/sitemap/index_hub.xml.
 * New filename avoids stale cached index in Search Console when parts change.
 * Routed via site/api/index.php (expects $config, mysql_select).
 */

$base = rtrim((string)($config['http_domain'] ?? ''), '/');
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
	if ($canonical_base !== '') {
		$base = rtrim($canonical_base, '/');
	}
}
header('Location: ' . $base . '/api/sitemap/index_hub.xml', true, 301);
exit;
