<?php
/**
 * Language-agnostic entry URLs (no /{lang}/ prefix) → 302 to /{lang}/…/
 *
 * Covers /demo/app/, /download/, and any other public path where the first segment
 * is not an enabled language code (multilingual_u0 routing).
 *
 * Target language: site_lang_pref cookie → Accept-Language → country from IP/CF → DB default.
 */

/** @var string */
define('SITE_LANG_PREF_COOKIE', 'site_lang_pref');

/**
 * @param array $u URL segments after multilingual_u0 shift (0-based)
 * @return bool
 */
function site_language_agnostic_path_is_lang_segment($u) {
	if (!is_array($u) || !isset($u[0])) {
		return false;
	}
	$seg = trim((string) $u[0], '/');
	if ($seg === '') {
		return false;
	}
	return site_language_agnostic_validate_lang_url($seg) !== '';
}

/**
 * Build trailing-slash path from shifted $u (e.g. download → /download/, demo+app → /demo/app/).
 *
 * @param array $u
 * @return string path starting with /
 */
function site_language_agnostic_path_from_u($u) {
	$parts = array();
	if (!is_array($u)) {
		return '/';
	}
	foreach ($u as $seg) {
		$seg = trim((string) $seg, '/');
		if ($seg !== '') {
			$parts[] = $seg;
		}
	}
	if ($parts === array()) {
		return '/';
	}
	return '/' . implode('/', $parts) . '/';
}

/**
 * Segments that must not receive an automatic language prefix (handled elsewhere).
 *
 * @param string $seg first path segment
 * @return bool
 */
function site_language_agnostic_path_is_reserved($seg) {
	$seg = strtolower(trim((string) $seg, '/'));
	return in_array($seg, array('api', 'images', 'files', 'scripts', 'admin', 'banner-img.php'), true);
}

/**
 * If first URL segment is not a language code, 302 to /{resolved_lang}/{path} and exit.
 */
function site_language_agnostic_redirect_if_needed() {
	global $u, $config, $abc;
	if (empty($config['multilingual']) || empty($config['multilingual_u0'])) {
		return;
	}
	if (!is_array($u) || count($u) === 0) {
		return;
	}
	$u0 = trim((string) ($u[0] ?? ''), '/');
	if ($u0 === '' || site_language_agnostic_path_is_lang_segment($u)) {
		return;
	}
	if (site_language_agnostic_path_is_reserved($u0)) {
		return;
	}

	$target = site_language_agnostic_resolve_target_lang_url();
	site_language_agnostic_set_lang_pref_cookie($target);
	$path = site_language_agnostic_path_from_u($u);
	$qs = (!empty($_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : '';
	$loc = '/' . $target . ($path === '/' ? '/' : $path) . $qs;

	if (!empty($abc['route_debug']) && is_array($abc['route_debug'])) {
		$abc['route_debug']['language_agnostic_redirect'] = array(
			'target_lang_url' => $target,
			'path' => $path,
			'redirect_to' => $loc,
		);
	}

	header('Location: ' . $loc, true, 302);
	exit;
}

/**
 * @return array<string,bool> url segment => true
 */
function site_language_agnostic_enabled_lang_urls() {
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$cache = array();
	if (!function_exists('mysql_select')) {
		return $cache;
	}
	$rows = @mysql_select("SELECT url FROM languages WHERE display=1", 'rows', 60);
	if (!is_array($rows)) {
		return $cache;
	}
	foreach ($rows as $r) {
		$seg = isset($r['url']) ? trim((string) $r['url'], '/') : '';
		if ($seg !== '') {
			$cache[$seg] = true;
		}
	}
	return $cache;
}

/**
 * @param string $seg
 * @return string normalized url segment or ''
 */
function site_language_agnostic_validate_lang_url($seg) {
	$seg = strtolower(trim((string) $seg, '/'));
	if ($seg === '') {
		return '';
	}
	$enabled = site_language_agnostic_enabled_lang_urls();
	return isset($enabled[$seg]) ? $seg : '';
}

/**
 * Map ISO 3166-1 alpha-2 to site language url (first match wins; only keys that exist in DB are returned).
 *
 * @param string $cc
 * @return string
 */
function site_language_agnostic_country_to_lang_url($cc) {
	$cc = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $cc));
	if (strlen($cc) !== 2) {
		return '';
	}
	$map = array(
		'UA' => 'ua',
		'PL' => 'pl',
		'DE' => 'de',
		'AT' => 'de',
		'CH' => 'de',
		'FR' => 'fr',
		'BE' => 'fr',
		'ES' => 'es',
		'MX' => 'es',
		'AR' => 'es',
		'CO' => 'es',
		'PT' => 'pt',
		'BR' => 'pt',
		'KE' => 'sw',
		'TZ' => 'sw',
		'CD' => 'ln',
		'CG' => 'ln',
		'IN' => 'hi',
		'BD' => 'bn',
		'SA' => 'ar',
		'AE' => 'ar',
		'EG' => 'ar',
		'VN' => 'vi',
		'AZ' => 'az',
		'RU' => 'ru',
		'BY' => 'ru',
		'KZ' => 'ru',
		'KG' => 'ru',
		'NL' => 'nl',
		'RO' => 'ro',
		'MD' => 'ro',
		'GB' => 'en',
		'IE' => 'en',
		'US' => 'en',
		'AU' => 'en',
		'NZ' => 'en',
		'CA' => 'en',
		'JP' => 'en',
		'KR' => 'en',
		'IT' => 'it',
		'TR' => 'en',
	);
	if (!isset($map[$cc])) {
		return '';
	}
	return site_language_agnostic_validate_lang_url($map[$cc]);
}

/**
 * First Accept-Language tag that matches an enabled site language (path segment).
 *
 * @return string
 */
function site_language_agnostic_from_accept_language() {
	$hdr = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
	if (trim($hdr) === '') {
		return '';
	}
	$enabled = site_language_agnostic_enabled_lang_urls();
	$parts = preg_split('/\s*,\s*/', $hdr);
	if (!is_array($parts)) {
		return '';
	}
	foreach ($parts as $part) {
		$part = trim((string) $part);
		if ($part === '') {
			continue;
		}
		$tag = strtolower(trim(explode(';', $part, 2)[0]));
		$tag = str_replace('_', '-', $tag);
		$primary = explode('-', $tag)[0];
		if ($primary === 'uk') {
			$primary = 'ua';
		}
		if ($primary !== '' && isset($enabled[$primary])) {
			return $primary;
		}
	}
	return '';
}

/**
 * Default language url segment (same rule as lang() fallback).
 *
 * @return string
 */
function site_language_agnostic_default_lang_url() {
	if (!function_exists('mysql_select')) {
		return 'en';
	}
	$row = @mysql_select("SELECT url FROM languages WHERE display=1 ORDER BY `rank` DESC LIMIT 1", 'row', 60);
	if ($row && !empty($row['url'])) {
		$u = trim((string) $row['url'], '/');
		if ($u !== '') {
			return $u;
		}
	}
	return 'en';
}

/**
 * @return string language url segment
 */
function site_language_agnostic_resolve_target_lang_url() {
	foreach (array(SITE_LANG_PREF_COOKIE) as $cookie_name) {
		if (empty($_COOKIE[$cookie_name])) {
			continue;
		}
		$v = site_language_agnostic_validate_lang_url($_COOKIE[$cookie_name]);
		if ($v !== '') {
			return $v;
		}
	}
	$al = site_language_agnostic_from_accept_language();
	if ($al !== '') {
		return $al;
	}
	require_once ROOT_DIR . 'functions/advertising_api.php';
	$ip_ctx = site_ad_resolve_ip_context(array());
	$ct = site_ad_resolve_country_context(array(), $ip_ctx);
	$country = isset($ct['country_sent_to_backend']) ? strtoupper((string) $ct['country_sent_to_backend']) : '';
	if ($country !== '' && $country !== 'XX') {
		$byc = site_language_agnostic_country_to_lang_url($country);
		if ($byc !== '') {
			return $byc;
		}
	}
	return site_language_agnostic_default_lang_url();
}

/**
 * Persist chosen UI language for the next language-agnostic entry hit.
 *
 * @param string $lang_url
 */
function site_language_agnostic_set_lang_pref_cookie($lang_url) {
	global $config;
	$lang_url = site_language_agnostic_validate_lang_url($lang_url);
	if ($lang_url === '') {
		return;
	}
	$exp = time() + 400 * 24 * 3600;
	$path = '/';
	$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	$dom = (!empty($config['.main_domain'])) ? (string) $config['.main_domain'] : '';
	$cookie = SITE_LANG_PREF_COOKIE;
	if ($dom !== '') {
		setcookie($cookie, $lang_url, $exp, $path, $dom, $secure, true);
	} else {
		setcookie($cookie, $lang_url, $exp, $path, '', $secure, true);
	}
}
