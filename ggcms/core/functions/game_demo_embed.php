<?php

/**
 * Shared game demo embed helpers (all brands).
 * Brand-specific launch (InOut / Spribe mirror) is selected via $config['game_demo_provider']
 * and optional site overlay (e.g. game_demo_embed_spribe.php).
 */

function game_demo_request_uri_first_path_segment() {
	$path = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = preg_replace('#\?.*$#', '', $path);
	$path = preg_replace('#/+#', '/', '/' . ltrim($path, '/'));
	$parts = array_values(array_filter(explode('/', trim($path, '/')), function ($p) {
		return $p !== '';
	}));
	return isset($parts[0]) ? strtolower((string) $parts[0]) : '';
}

function game_demo_lang_path_map() {
	return array(
		'en' => 'EN', 'fr' => 'FR', 'de' => 'DE', 'es' => 'ES', 'pt' => 'PT', 'ru' => 'RU',
		'hi' => 'HI', 'ar' => 'AR', 'bn' => 'BN', 'it' => 'IT', 'nl' => 'NL', 'pl' => 'PL',
		'ua' => 'UK', 'uk' => 'UK', 'ro' => 'RO', 'vi' => 'VI', 'az' => 'AZ', 'tr' => 'TR',
		'id' => 'ID', 'cs' => 'CS', 'sv' => 'SV', 'da' => 'DA', 'fi' => 'FI', 'no' => 'NO',
		'nb' => 'NB', 'nn' => 'NN', 'el' => 'EL', 'he' => 'HE', 'th' => 'TH', 'ms' => 'MS',
		'fa' => 'FA', 'ur' => 'UR', 'sk' => 'SK', 'hu' => 'HU', 'bg' => 'BG', 'hr' => 'HR',
		'sr' => 'SR', 'sl' => 'SL', 'et' => 'ET', 'lv' => 'LV', 'lt' => 'LT', 'ja' => 'JA',
		'ko' => 'KO', 'zh' => 'ZH',
	);
}

function game_demo_currency_for_country($country_alpha2) {
	$cc = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $country_alpha2));
	if (strlen($cc) !== 2) {
		return 'USD';
	}
	static $euro = null;
	if ($euro === null) {
		$euro = array(
			'AT' => true, 'BE' => true, 'CY' => true, 'DE' => true, 'EE' => true, 'ES' => true, 'FI' => true, 'FR' => true,
			'GR' => true, 'HR' => true, 'IE' => true, 'IT' => true, 'LT' => true, 'LU' => true, 'LV' => true, 'MT' => true,
			'NL' => true, 'PT' => true, 'SK' => true, 'SI' => true, 'AD' => true, 'MC' => true, 'SM' => true, 'VA' => true,
		);
	}
	if (isset($euro[$cc])) {
		return 'EUR';
	}
	static $map = null;
	if ($map === null) {
		$map = array(
			'US' => 'USD', 'GB' => 'GBP', 'CH' => 'CHF', 'NO' => 'NOK', 'SE' => 'SEK', 'DK' => 'DKK', 'IS' => 'ISK',
			'PL' => 'PLN', 'CZ' => 'CZK', 'HU' => 'HUF', 'RO' => 'RON', 'BG' => 'BGN', 'UA' => 'UAH', 'TR' => 'TRY',
			'RU' => 'RUB', 'BY' => 'BYN', 'MD' => 'MDL', 'GE' => 'GEL', 'AM' => 'AMD', 'AZ' => 'AZN', 'KZ' => 'KZT',
			'CA' => 'CAD', 'MX' => 'MXN', 'BR' => 'BRL', 'AR' => 'ARS', 'CL' => 'CLP', 'CO' => 'COP', 'PE' => 'PEN',
			'UY' => 'UYU', 'IN' => 'INR', 'BD' => 'BDT', 'PK' => 'PKR', 'LK' => 'LKR', 'NP' => 'NPR', 'ID' => 'IDR',
			'TH' => 'THB', 'VN' => 'VND', 'PH' => 'PHP', 'MY' => 'MYR', 'SG' => 'SGD', 'HK' => 'HKD', 'MO' => 'MOP',
			'TW' => 'TWD', 'JP' => 'JPY', 'KR' => 'KRW', 'CN' => 'CNY', 'AU' => 'AUD', 'NZ' => 'NZD', 'ZA' => 'ZAR',
			'NG' => 'NGN', 'KE' => 'KES', 'EG' => 'EGP', 'MA' => 'MAD', 'TN' => 'TND', 'DZ' => 'DZD', 'GH' => 'GHS',
			'IL' => 'ILS', 'AE' => 'AED', 'SA' => 'SAR', 'QA' => 'QAR', 'KW' => 'KWD', 'BH' => 'BHD', 'OM' => 'OMR',
			'JO' => 'JOD', 'LB' => 'LBP', 'IQ' => 'IQD', 'IR' => 'IRR', 'RS' => 'RSD', 'BA' => 'BAM', 'MK' => 'MKD',
			'AL' => 'ALL', 'XK' => 'EUR', 'ME' => 'EUR',
		);
	}
	return isset($map[$cc]) ? $map[$cc] : 'USD';
}

function game_demo_currency_normalize_for_launch($currency_alpha3) {
	$c = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $currency_alpha3));
	if (strlen($c) !== 3) {
		return 'USD';
	}
	static $ok = null;
	if ($ok === null) {
		$ok = array(
			'USD' => true, 'EUR' => true, 'GBP' => true, 'RUB' => true, 'UAH' => true, 'TRY' => true, 'INR' => true,
			'BRL' => true, 'CAD' => true, 'AUD' => true, 'NZD' => true, 'CHF' => true, 'NOK' => true, 'SEK' => true,
			'DKK' => true, 'PLN' => true, 'CZK' => true, 'HUF' => true, 'RON' => true, 'BGN' => true, 'JPY' => true,
			'KRW' => true, 'CNY' => true, 'MXN' => true, 'ZAR' => true, 'BDT' => true, 'AED' => true, 'SAR' => true,
			'THB' => true, 'IDR' => true, 'MYR' => true, 'SGD' => true, 'PHP' => true, 'VND' => true, 'ARS' => true,
			'CLP' => true, 'COP' => true, 'PEN' => true, 'EGP' => true, 'NGN' => true, 'KES' => true, 'MAD' => true,
			'ILS' => true, 'BYN' => true, 'GEL' => true, 'KZT' => true, 'AMD' => true, 'AZN' => true, 'MDL' => true,
			'ISK' => true, 'RSD' => true, 'BAM' => true, 'ALL' => true, 'JOD' => true, 'QAR' => true, 'KWD' => true,
			'UYU' => true, 'TWD' => true, 'HKD' => true, 'MOP' => true, 'LKR' => true, 'PKR' => true, 'NPR' => true,
		);
	}
	return isset($ok[$c]) ? $c : 'USD';
}

/**
 * Read demo config: neutral keys first, then legacy *_{brand}_inout_* keys from overlays.
 */
function game_demo_config_value(array $config, $name, $default = null) {
	$names = array_unique(array((string) $name, str_replace('demo_', '', (string) $name)));
	foreach ($names as $n) {
		$candidates = array(
			'inout_demo_' . $n,
			'inout_' . $n,
			'game_demo_' . $n,
		);
		if ($n === 'currency' || $n === 'demo_currency') {
			$candidates[] = 'game_demo_currency';
			$candidates[] = 'aviator_demo_currency';
		}
		foreach ($candidates as $key) {
			if (array_key_exists($key, $config) && $config[$key] !== '' && $config[$key] !== null) {
				return $config[$key];
			}
		}
		foreach ($config as $key => $value) {
			if ($value === '' || $value === null) {
				continue;
			}
			if (preg_match('/_inout_(?:demo_)?' . preg_quote($n, '/') . '$/', (string) $key)) {
				return $value;
			}
		}
	}
	return $default;
}

function game_demo_currency_param(array $abc, array $config = array()) {
	$forced = game_demo_config_value($config, 'currency', '');
	if (preg_match('/^[A-Za-z]{3}$/', (string) $forced)) {
		return strtoupper((string) $forced);
	}
	if (!function_exists('aviator_ad_resolve_ip_context') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/advertising_api.php')) {
		require_once ROOT_DIR . 'functions/advertising_api.php';
	}
	if (function_exists('aviator_ad_resolve_ip_context') && function_exists('aviator_ad_resolve_country_context')) {
		$ad = (isset($abc['advertising_api']) && is_array($abc['advertising_api'])) ? $abc['advertising_api'] : array();
		$ip_ctx = aviator_ad_resolve_ip_context($ad);
		$ct = aviator_ad_resolve_country_context($ad, $ip_ctx);
		$country = isset($ct['country_sent_to_backend']) ? strtoupper((string) $ct['country_sent_to_backend']) : '';
		if ($country !== '' && $country !== 'XX' && preg_match('/^[A-Z]{2}$/', $country)) {
			return game_demo_currency_normalize_for_launch(game_demo_currency_for_country($country));
		}
	}
	return 'USD';
}

function game_demo_inout_currency(array $abc, array $config = array()) {
	$forced = game_demo_config_value($config, 'currency', '');
	if (preg_match('/^[A-Za-z]{3}$/', (string) $forced)) {
		return strtoupper((string) $forced);
	}
	return 'USD';
}

function game_demo_lang_param(array $abc) {
	$by_path = game_demo_lang_path_map();
	$from_url = game_demo_request_uri_first_path_segment();
	if ($from_url !== '' && isset($by_path[$from_url])) {
		return $by_path[$from_url];
	}
	$lang = (isset($abc['lang']) && is_array($abc['lang'])) ? $abc['lang'] : array();
	$url = isset($lang['url']) ? strtolower(trim((string) $lang['url'], '/')) : 'en';
	if (isset($by_path[$url])) {
		return $by_path[$url];
	}
	$loc = isset($lang['localization']) ? strtolower((string) $lang['localization']) : '';
	$loc = str_replace('_', '-', $loc);
	if ($loc !== '' && preg_match('/^([a-z]{2})/', $loc, $m)) {
		$two = $m[1];
		if (isset($by_path[$two])) {
			return $by_path[$two];
		}
		return strtoupper($two);
	}
	return 'EN';
}

function game_demo_lobby_url() {
	if (function_exists('site_seo_public_origin')) {
		$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
		return site_seo_public_origin() . preg_replace('#/+#', '/', $path === '' ? '/' : $path);
	}
	if (function_exists('aviator_seo_public_origin')) {
		$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
		return aviator_seo_public_origin() . preg_replace('#/+#', '/', $path === '' ? '/' : $path);
	}
	$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
	if ($host === '') {
		return '';
	}
	return $scheme . '://' . $host . preg_replace('#/+#', '/', $path === '' ? '/' : $path);
}

function game_demo_provider(array $config = array()) {
	if (!empty($config['game_demo_provider'])) {
		return strtolower(trim((string) $config['game_demo_provider']));
	}
	return 'inout';
}

/**
 * Load optional Spribe/mirror provider overlay (aviator-log-in only).
 */
function game_demo_ensure_spribe_provider() {
	if (function_exists('game_demo_spribe_official_url')) {
		return;
	}
	if (!defined('ROOT_DIR')) {
		return;
	}
	$path = ROOT_DIR . 'functions/game_demo_embed_spribe.php';
	if (is_file($path)) {
		require_once $path;
	}
}

function game_demo_is_mirror_shell(array $config = array()) {
	$p = game_demo_provider($config);
	return $p === 'spribe_mirror' || $p === 'spribe';
}

function game_demo_inout_official_url(array $abc, array $config = array()) {
	$url = function_exists('site_game_demo_iframe_url') ? site_game_demo_iframe_url($config) : '';
	if ($url !== '') {
		return $url;
	}
	$currency = game_demo_inout_currency($abc, $config);
	$lang_code = game_demo_lang_param($abc);
	$lang = strtolower(substr((string) $lang_code, 0, 2));
	if ($lang === '') {
		$lang = 'en';
	}
	$operator = (string) game_demo_config_value($config, 'operator_id', 'ee2013ed-e1f0-4d6e-97d2-f36619e2eb52');
	$token = (string) game_demo_config_value($config, 'auth_token', '247d3637-c5dc-a67b-50a1-89df27733343');
	$use_launch_api = game_demo_config_value($config, 'use_launch_api', null);
	if ($use_launch_api === null) {
		$use_launch_api = (game_demo_config_value($config, 'host', '') === '');
	} else {
		$use_launch_api = !empty($use_launch_api);
	}
	$host = (string) game_demo_config_value($config, 'host', '');
	if ($host === '') {
		$host = $use_launch_api ? 'https://api.inout.games' : 'https://api.inout.games';
	} else {
		$host = rtrim($host, '/');
	}
	$game_mode = (string) game_demo_config_value($config, 'game_mode', '');
	if ($game_mode === '') {
		return '';
	}
	$path = ($use_launch_api && $host === 'https://api.inout.games')
		? '/api/launch'
		: '/api/modes/game';
	$query = array(
		'gameMode' => $game_mode,
		'operatorId' => $operator,
		'authToken' => $token,
		'currency' => $currency,
		'lang' => $lang,
		'theme' => '',
		'gameCustomizationId' => '',
		'lobbyUrl' => game_demo_lobby_url(),
	);
	$skin_id = (string) game_demo_config_value($config, 'skin_id', '');
	if ($skin_id !== '') {
		$query['skinId'] = $skin_id;
	}
	return $host . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function game_demo_official_url(array $abc, array $config = array()) {
	$url = function_exists('site_game_demo_iframe_url') ? site_game_demo_iframe_url($config) : '';
	if ($url !== '') {
		return $url;
	}
	if (game_demo_is_mirror_shell($config)) {
		game_demo_ensure_spribe_provider();
		if (function_exists('game_demo_spribe_official_url')) {
			return game_demo_spribe_official_url($abc, $config);
		}
	}
	return game_demo_inout_official_url($abc, $config);
}

function game_demo_launch_url(array $abc, array $config = array()) {
	return game_demo_official_url($abc, $config);
}

// --- Legacy aliases (deprecated; remove after all call sites migrate) ---

if (!function_exists('aviator_request_uri_first_path_segment')) {
	function aviator_request_uri_first_path_segment() { return game_demo_request_uri_first_path_segment(); }
}
if (!function_exists('aviator_spribe_demo_lang_path_map')) {
	function aviator_spribe_demo_lang_path_map() { return game_demo_lang_path_map(); }
}
if (!function_exists('aviator_spribe_demo_currency_for_country')) {
	function aviator_spribe_demo_currency_for_country($cc) { return game_demo_currency_for_country($cc); }
}
if (!function_exists('aviator_spribe_demo_currency_normalize_for_launch')) {
	function aviator_spribe_demo_currency_normalize_for_launch($c) { return game_demo_currency_normalize_for_launch($c); }
}
if (!function_exists('aviator_spribe_demo_lang_param')) {
	function aviator_spribe_demo_lang_param(array $abc) { return game_demo_lang_param($abc); }
}
if (!function_exists('aviator_spribe_official_demo_url')) {
	function aviator_spribe_official_demo_url(array $abc, array $config = array()) { return game_demo_official_url($abc, $config); }
}
if (!function_exists('aviator_spribe_demo_currency_param')) {
	function aviator_spribe_demo_currency_param(array $abc, array $config = array()) {
		return function_exists('game_demo_spribe_currency_param')
			? game_demo_spribe_currency_param($abc, $config)
			: game_demo_currency_param($abc, $config);
	}
}
if (!function_exists('chickenroad_inout_official_demo_url')) {
	function chickenroad_inout_official_demo_url(array $abc, array $config = array()) { return game_demo_inout_official_url($abc, $config); }
}
if (!function_exists('icefish_inout_official_demo_url')) {
	function icefish_inout_official_demo_url(array $abc, array $config = array()) { return game_demo_inout_official_url($abc, $config); }
}
if (!function_exists('chickenroad_inout_demo_lobby_url')) {
	function chickenroad_inout_demo_lobby_url() { return game_demo_lobby_url(); }
}
if (!function_exists('icefish_inout_demo_lobby_url')) {
	function icefish_inout_demo_lobby_url() { return game_demo_lobby_url(); }
}
