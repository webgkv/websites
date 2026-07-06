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

function site_seo_load_index_rules() {
	static $loaded = false;
	if ($loaded) {
		return;
	}
	$path = defined('ROOT_DIR') ? ROOT_DIR . 'functions/seo_index_rules.php' : '';
	if ($path !== '' && is_file($path)) {
		require_once $path;
	}
	$loaded = true;
}

/**
 * True when site-wide block is active in SEO → Index rules (DB only).
 */
function site_seo_index_whitelist_enabled() {
	site_seo_load_index_rules();
	return function_exists('seo_index_rules_site_blocked') && seo_index_rules_site_blocked();
}

/** @return string[] language url segments (e.g. en) that may expose /demo/app/ to search engines */
function site_seo_index_demo_app_langs() {
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_demo_app_langs')) {
		return seo_index_rules_demo_app_langs();
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
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_allow_search_indexing')) {
		return seo_index_rules_allow_search_indexing($abc, $u, $lang);
	}
	return true;
}

/** @return array{robots:string,googlebot:string} */
function site_seo_robots_meta_tags(?array $abc = null, ?array $u = null, ?array $lang = null) {
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_robots_meta_tags')) {
		return seo_index_rules_robots_meta_tags($abc, $u, $lang);
	}
	return array('robots' => '', 'googlebot' => '');
}

/** @return string robots meta / X-Robots-Tag value, or empty when indexing is allowed */
function site_seo_robots_meta_content(?array $abc = null, ?array $u = null, ?array $lang = null) {
	$tags = site_seo_robots_meta_tags($abc, $u, $lang);
	if ($tags['robots'] !== '') {
		return $tags['robots'];
	}
	if ($tags['googlebot'] !== '') {
		return $tags['googlebot'];
	}
	return '';
}

function site_seo_echo_robots_meta_tags(?array $abc = null, ?array $u = null, ?array $lang = null) {
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_echo_robots_meta_tags')) {
		seo_index_rules_echo_robots_meta_tags($abc, $u, $lang);
		return;
	}
	$tags = site_seo_robots_meta_tags($abc, $u, $lang);
	foreach ($tags as $name => $content) {
		if ($content === '') {
			continue;
		}
		echo '        <meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">' . "\n";
	}
}

function site_seo_apply_robots_header(?array $abc = null, ?array $u = null, ?array $lang = null) {
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_apply_robots_header')) {
		seo_index_rules_apply_robots_header($abc, $u, $lang);
		return;
	}
	$content = site_seo_robots_meta_content($abc, $u, $lang);
	if ($content !== '' && !headers_sent()) {
		header('X-Robots-Tag: ' . $content, true);
	}
}

/** Sitemap: true when site-wide block mode is active (SEO → Index rules). */
function site_seo_sitemap_whitelist_mode() {
	site_seo_load_index_rules();
	return function_exists('seo_index_rules_site_blocked') && seo_index_rules_site_blocked();
}

/** Sitemap: include entity section when not blocked at site/entity level. */
function site_seo_sitemap_entity_allowed($entity) {
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_sitemap_include_entity')) {
		return seo_index_rules_sitemap_include_entity($entity);
	}
	return true;
}

/**
 * Apply SEO → Index rules to sitemap section toggles.
 *
 * @param array<string,int> $include
 * @return array<string,int>
 */
function site_seo_sitemap_apply_index_rules_to_include(array $include) {
	$map = array(
		'pages' => 'pages',
		'blog' => 'blog',
		'guides' => 'guides',
		'games' => 'games',
		'casinos' => 'casino_articles',
		'authors' => 'authors',
	);
	foreach ($map as $key => $entity) {
		if (!site_seo_sitemap_entity_allowed($entity)) {
			$include[$key] = 0;
		}
	}
	return $include;
}

/**
 * Sitemap entries for one language when site-wide block is active.
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
	site_seo_load_index_rules();
	if (function_exists('seo_index_rules_admin_restrictions')) {
		return seo_index_rules_admin_restrictions();
	}
	return array();
}

function site_seo_admin_indexing_restrictions_active() {
	return !empty(site_seo_admin_indexing_restrictions());
}

/** @return array{page_limit:int,minutes_limit:int} */
function site_seo_admin_indexing_guard_timing() {
	return array(
		'page_limit' => 30,
		'minutes_limit' => 30,
	);
}
