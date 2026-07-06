<?php

/**
 * SEO origin / structured-data helpers (host-aware; legacy Aviator export safe).
 */

/** Hostnames from legacy deployments — never use as canonical on current domain. */
function site_seo_legacy_canonical_hosts() {
	if (function_exists('site_brand_profile_value')) {
		$hosts = site_brand_profile_value('legacy_canonical_hosts', array());
		if (is_array($hosts) && !empty($hosts)) {
			return $hosts;
		}
	}
	return array();
}

/**
 * Fix seo_structured loaded from DB after rebrand or wrong import.
 *
 * @param array $seo
 */
function site_seo_normalize_structured(array &$seo) {
	$host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
	$host = preg_replace('/:\d+$/', '', $host);

	if (!empty($seo['canonical_base'])) {
		$base = trim((string) $seo['canonical_base']);
		$baseHost = strtolower((string) parse_url($base, PHP_URL_HOST));
		$legacy = site_seo_legacy_canonical_hosts();
		$mismatch = ($host !== '' && $baseHost !== '' && $baseHost !== $host);
		$isLegacy = ($baseHost !== '' && in_array($baseHost, $legacy, true));
		if ($mismatch || $isLegacy) {
			$seo['canonical_base'] = '';
		}
	}

	$name = isset($seo['site_name']) ? trim((string) $seo['site_name']) : '';
	if ($name === '' || preg_match('/aviator\s*log\s*in|chicken\s*road|powerball/i', $name)) {
		$seo['site_name'] = function_exists('site_brand_name') ? site_brand_name() : 'Site';
	}

	if (!empty($seo['breadcrumbs']['home_label']) && function_exists('site_brand_rebrand_text')) {
		$seo['breadcrumbs']['home_label'] = site_brand_rebrand_text($seo['breadcrumbs']['home_label']);
	} elseif (empty($seo['breadcrumbs']['home_label']) || preg_match('/aviator/i', (string) ($seo['breadcrumbs']['home_label'] ?? ''))) {
		$seo['breadcrumbs']['home_label'] = function_exists('site_brand_name') ? site_brand_name() : 'Home';
	}

	if (!empty($seo['faq']) && is_array($seo['faq']) && function_exists('site_brand_rebrand_text')) {
		foreach ($seo['faq'] as $i => $row) {
			if (!is_array($row)) {
				continue;
			}
			if (!empty($row['q'])) {
				$seo['faq'][$i]['q'] = site_brand_rebrand_text($row['q']);
			}
			if (!empty($row['a'])) {
				$seo['faq'][$i]['a'] = site_brand_rebrand_text($row['a']);
			}
		}
	}
}

/**
 * Absolute origin for canonical, hreflang, og:url (current host unless canonical_base matches HTTP_HOST).
 */
function site_seo_public_origin() {
	global $abc;
	$cfg = isset($abc['seo_structured']) && is_array($abc['seo_structured']) ? $abc['seo_structured'] : array();
	$b = isset($cfg['canonical_base']) ? trim((string) $cfg['canonical_base']) : '';
	if ($b !== '') {
		$host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
		$host = preg_replace('/:\d+$/', '', $host);
		$baseHost = strtolower((string) parse_url($b, PHP_URL_HOST));
		if ($host !== '' && $baseHost !== '' && $baseHost === $host) {
			return rtrim($b, '/');
		}
		if ($host !== '' && $baseHost !== '' && $baseHost !== $host) {
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
			return $scheme . '://' . $host;
		}
		return rtrim($b, '/');
	}
	$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
	if ($host === '') {
		return 'https://localhost';
	}
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	return $scheme . '://' . $host;
}

if (!function_exists('aviator_seo_public_origin')) {
	function aviator_seo_public_origin() {
		return site_seo_public_origin();
	}
}

/**
 * Load seo_structured from DB when data_func did not run (sitemap CLI/API).
 *
 * @return array
 */
function site_seo_structured_from_db() {
	$seo = array(
		'canonical_base' => '',
		'site_name' => '',
		'breadcrumbs' => array('home_label' => 'Home', 'use_site_tree' => 0),
		'faq' => array(),
	);
	if (!function_exists('mysql_select') || @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
		site_seo_normalize_structured($seo);
		return $seo;
	}
	$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'seo_structured' LIMIT 1", 'row');
	if ($row && isset($row['value']) && (string) $row['value'] !== '') {
		$dec = json_decode((string) $row['value'], true);
		if (is_array($dec)) {
			if (isset($dec['canonical_base'])) {
				$seo['canonical_base'] = (string) $dec['canonical_base'];
			}
			if (isset($dec['site_name'])) {
				$seo['site_name'] = (string) $dec['site_name'];
			}
			if (isset($dec['breadcrumbs']) && is_array($dec['breadcrumbs'])) {
				$seo['breadcrumbs'] = array_merge($seo['breadcrumbs'], $dec['breadcrumbs']);
			}
			if (isset($dec['faq']) && is_array($dec['faq'])) {
				$seo['faq'] = $dec['faq'];
			}
		}
	}
	site_seo_normalize_structured($seo);
	return $seo;
}

/**
 * Canonical base for sitemap XML (same host rules as public origin).
 */
function site_seo_sitemap_base_url() {
	global $abc;
	if (!isset($abc) || !is_array($abc)) {
		$abc = array();
	}
	if (empty($abc['seo_structured']) || !is_array($abc['seo_structured'])) {
		$abc['seo_structured'] = site_seo_structured_from_db();
	}
	return site_seo_public_origin();
}

/**
 * When enabled, only homepage (index module) and configured /{lang}/demo/app/ paths are indexable.
 */
function site_seo_index_whitelist_enabled() {
	global $config;
	return !empty($config['seo_index_whitelist']);
}

/** @return string[] language url segments (e.g. en) that may expose /demo/app/ to search engines */
function site_seo_index_demo_app_langs() {
	global $config;
	if (!empty($config['seo_index_demo_app_langs']) && is_array($config['seo_index_demo_app_langs'])) {
		$out = array();
		foreach ($config['seo_index_demo_app_langs'] as $seg) {
			$seg = trim((string) $seg, '/');
			if ($seg !== '') {
				$out[] = $seg;
			}
		}
		if (!empty($out)) {
			return array_values(array_unique($out));
		}
	}
	return array('en');
}

function site_seo_page_is_home(array $abc) {
	return !empty($abc['module']) && (string) $abc['module'] === 'index';
}

function site_seo_page_is_whitelisted_demo_app(array $abc, array $u, array $lang) {
	if (empty($abc['layout']) || (string) $abc['layout'] !== 'demo_app') {
		return false;
	}
	$lang_url = trim((string) ($lang['url'] ?? ''), '/');
	if ($lang_url === '' && !empty($abc['lang']['url'])) {
		$lang_url = trim((string) $abc['lang']['url'], '/');
	}
	if (!in_array($lang_url, site_seo_index_demo_app_langs(), true)) {
		return false;
	}
	$u1 = isset($u[1]) ? strtolower(trim((string) $u[1], '/')) : '';
	$u2 = isset($u[2]) ? strtolower(trim((string) $u[2], '/')) : '';
	return $u1 === 'demo' && $u2 === 'app';
}

/**
 * @return bool true when crawlers may index the current HTML response
 */
function site_seo_allow_search_indexing(?array $abc = null, ?array $u = null, ?array $lang = null) {
	if (!site_seo_index_whitelist_enabled()) {
		return true;
	}
	if ($abc === null) {
		global $abc;
	}
	if ($u === null) {
		global $u;
	}
	if ($lang === null) {
		global $lang;
	}
	if (!is_array($abc)) {
		$abc = array();
	}
	if (!is_array($u)) {
		$u = array();
	}
	if (!is_array($lang)) {
		$lang = array();
	}
	if (site_seo_page_is_home($abc)) {
		return true;
	}
	if (site_seo_page_is_whitelisted_demo_app($abc, $u, $lang)) {
		return true;
	}
	return false;
}

/** @return string robots meta / X-Robots-Tag value, or empty when indexing is allowed */
function site_seo_robots_meta_content(?array $abc = null, ?array $u = null, ?array $lang = null) {
	if (site_seo_allow_search_indexing($abc, $u, $lang)) {
		return '';
	}
	return 'noindex, nofollow';
}

function site_seo_apply_robots_header(?array $abc = null, ?array $u = null, ?array $lang = null) {
	$content = site_seo_robots_meta_content($abc, $u, $lang);
	if ($content !== '' && !headers_sent()) {
		header('X-Robots-Tag: ' . $content, true);
	}
}

/**
 * Sitemap entries for one language when seo_index_whitelist is on.
 *
 * @return array<int, array{loc:string,lastmod:?string}>
 */
function site_seo_sitemap_whitelist_entries($base, array $lang) {
	$base = rtrim((string) $base, '/');
	$langSlug = trim((string) ($lang['url'] ?? ''), '/');
	$path = $langSlug !== '' ? '/' . $langSlug . '/' : '/';
	$entries = array(
		array('loc' => $base . $path, 'lastmod' => null),
	);
	if (in_array($langSlug, site_seo_index_demo_app_langs(), true)) {
		$entries[] = array('loc' => $base . '/' . $langSlug . '/demo/app/', 'lastmod' => null);
	}
	return $entries;
}

/**
 * Active search-indexing restrictions for admin UI (whitelist, blog de-index, …).
 *
 * @return list<array{id:string,label:string,detail:string}>
 */
function site_seo_admin_indexing_restrictions() {
	global $config;
	$items = array();
	if (function_exists('site_seo_index_whitelist_enabled') && site_seo_index_whitelist_enabled()) {
		$demo_langs = function_exists('site_seo_index_demo_app_langs') ? site_seo_index_demo_app_langs() : array('en');
		$demo_paths = array();
		foreach ($demo_langs as $seg) {
			$demo_paths[] = '/' . $seg . '/demo/app/';
		}
		$items[] = array(
			'id' => 'whitelist',
			'label' => 'Index whitelist',
			'detail' => 'Only language homepages and ' . implode(', ', $demo_paths) . ' are indexable (meta noindex + trimmed sitemap).',
		);
	}
	if (!empty($config['blog_google_deindex'])) {
		$items[] = array(
			'id' => 'blog_deindex',
			'label' => 'Articles / blog de-index',
			'detail' => 'Blog section uses googlebot noindex and is omitted from sitemaps.',
		);
	}
	return $items;
}

function site_seo_admin_indexing_restrictions_active() {
	return !empty(site_seo_admin_indexing_restrictions());
}

/** @return array{page_limit:int,minutes_limit:int} */
function site_seo_admin_indexing_guard_timing() {
	global $config;
	$page_limit = 30;
	$minutes_limit = 30;
	if (!empty($config['seo_index_admin_remind_pages'])) {
		$page_limit = max(1, (int) $config['seo_index_admin_remind_pages']);
	}
	if (!empty($config['seo_index_admin_remind_minutes'])) {
		$minutes_limit = max(1, (int) $config['seo_index_admin_remind_minutes']);
	}
	return array(
		'page_limit' => $page_limit,
		'minutes_limit' => $minutes_limit,
	);
}
