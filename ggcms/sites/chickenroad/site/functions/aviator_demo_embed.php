<?php

/**
 * Always https://demo.spribe.io/launch/aviator?currency=…&lang=… (lang from URL path, e.g. /en/…, then $abc['lang']).
 * Ignores $config['aviator_demo_iframe_url'] — use for /demo/app/ and any place that must stay on Spribe.
 *
 * Currency: optional $config['aviator_demo_currency'] (3 letters) forces a single fiat code; otherwise resolved from
 * visitor geo the same way as the ads layer (CF country header, else IP→country via aviator_ad_country_by_ip in advertising_api.php).
 *
 * @param array $abc Template globals (expects $abc['lang'] from router; optional $abc['advertising_api'] for geo).
 * @param array $config Site config.
 */
function aviator_request_uri_first_path_segment() {
	$path = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
	$path = preg_replace('#\?.*$#', '', $path);
	$path = preg_replace('#/+#', '/', '/' . ltrim($path, '/'));
	$parts = array_values(array_filter(explode('/', trim($path, '/')), function ($p) {
		return $p !== '';
	}));
	return isset($parts[0]) ? strtolower((string) $parts[0]) : '';
}

/**
 * URL slug (en, fr, …) → Spribe demo ?lang= code.
 */
function aviator_spribe_demo_lang_path_map() {
	return array(
		'en' => 'EN',
		'fr' => 'FR',
		'de' => 'DE',
		'es' => 'ES',
		'pt' => 'PT',
		'ru' => 'RU',
		'hi' => 'HI',
		'ar' => 'AR',
		'bn' => 'BN',
		'it' => 'IT',
		'nl' => 'NL',
		'pl' => 'PL',
		'ua' => 'UK',
		'uk' => 'UK',
		'ro' => 'RO',
		'vi' => 'VI',
		'az' => 'AZ',
		'tr' => 'TR',
		'id' => 'ID',
		'cs' => 'CS',
		'sv' => 'SV',
		'da' => 'DA',
		'fi' => 'FI',
		'no' => 'NO',
		'nb' => 'NB',
		'nn' => 'NN',
		'el' => 'EL',
		'he' => 'HE',
		'th' => 'TH',
		'ms' => 'MS',
		'fa' => 'FA',
		'ur' => 'UR',
		'sk' => 'SK',
		'hu' => 'HU',
		'bg' => 'BG',
		'hr' => 'HR',
		'sr' => 'SR',
		'sl' => 'SL',
		'et' => 'ET',
		'lv' => 'LV',
		'lt' => 'LT',
		'ja' => 'JA',
		'ko' => 'KO',
		'zh' => 'ZH',
	);
}

/**
 * ISO 3166-1 alpha-2 → primary retail ISO 4217 (for demo/balance display). Unmapped or unknown → USD.
 */
function aviator_spribe_demo_currency_for_country($country_alpha2) {
	$cc = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $country_alpha2));
	if (strlen($cc) !== 2) {
		return 'USD';
	}
	// Euro area + Kosovo unilateral EUR often shown as EUR in UIs; microstates using EUR.
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
	if (isset($map[$cc])) {
		return $map[$cc];
	}
	return 'USD';
}

/**
 * Fiat codes commonly accepted on operator/demo stacks; if mapped currency is exotic, fall back to USD.
 */
function aviator_spribe_demo_currency_normalize_for_launch($currency_alpha3) {
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
 * Resolves 3-letter currency for Spribe demo: config override, else geo (same IP/country path as advertising_api).
 */
function aviator_spribe_demo_currency_param(array $abc, array $config = array()) {
	if (!empty($config['aviator_demo_currency']) && preg_match('/^[A-Za-z]{3}$/', (string) $config['aviator_demo_currency'])) {
		return strtoupper((string) $config['aviator_demo_currency']);
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
			$cur = aviator_spribe_demo_currency_for_country($country);
			return aviator_spribe_demo_currency_normalize_for_launch($cur);
		}
	}
	return 'USD';
}

/**
 * Fiat code for InOut Chicken Road 2 demo launch.
 * Geo-mapped currencies (e.g. RON for RO) hang the client on infinite loading; USD matches chickenrd2.com / inout.games.
 */
function chickenroad_inout_demo_currency(array $abc, array $config = array()) {
	if (!empty($config['aviator_demo_currency']) && preg_match('/^[A-Za-z]{3}$/', (string) $config['aviator_demo_currency'])) {
		return strtoupper((string) $config['aviator_demo_currency']);
	}
	if (!empty($config['chickenroad_inout_demo_currency']) && preg_match('/^[A-Za-z]{3}$/', (string) $config['chickenroad_inout_demo_currency'])) {
		return strtoupper((string) $config['chickenroad_inout_demo_currency']);
	}
	return 'USD';
}

/**
 * Parent URL passed to InOut as lobbyUrl (current demo page when possible).
 */
function chickenroad_inout_demo_lobby_url() {
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

/**
 * InOut Games Chicken Road 2 demo (same embed as chickenrd2.com / inout.games/api/launch).
 */
function chickenroad_inout_official_demo_url(array $abc, array $config = array()) {
	$url = function_exists('site_game_demo_iframe_url') ? site_game_demo_iframe_url($config) : '';
	if ($url !== '') {
		return $url;
	}
	$currency = chickenroad_inout_demo_currency($abc, $config);
	$lang_code = aviator_spribe_demo_lang_param($abc);
	$lang = strtolower(substr((string) $lang_code, 0, 2));
	if ($lang === '') {
		$lang = 'en';
	}
	$operator = !empty($config['chickenroad_inout_operator_id'])
		? (string) $config['chickenroad_inout_operator_id']
		: 'ee2013ed-e1f0-4d6e-97d2-f36619e2eb52';
	$token = !empty($config['chickenroad_inout_auth_token'])
		? (string) $config['chickenroad_inout_auth_token']
		: '247d3637-c5dc-a67b-50a1-89df27733343';
	$use_launch_api = !isset($config['chickenroad_inout_use_launch_api'])
		|| !empty($config['chickenroad_inout_use_launch_api']);
	$host = !empty($config['chickenroad_inout_demo_host'])
		? rtrim((string) $config['chickenroad_inout_demo_host'], '/')
		: ($use_launch_api ? 'https://api.inout.games' : 'https://chicken-road-two.inout.games');
	$game_mode = !empty($config['chickenroad_inout_game_mode'])
		? (string) $config['chickenroad_inout_game_mode']
		: 'chicken-road-two';
	$path = ($use_launch_api && $host === 'https://api.inout.games')
		? '/api/launch'
		: '/api/modes/game';
	$lobby = chickenroad_inout_demo_lobby_url();
	return $host . $path . '?' . http_build_query(
		array(
			'gameMode' => $game_mode,
			'operatorId' => $operator,
			'authToken' => $token,
			'currency' => $currency,
			'lang' => $lang,
			'theme' => '',
			'gameCustomizationId' => '',
			'lobbyUrl' => $lobby,
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
}

function aviator_spribe_official_demo_url(array $abc, array $config = array()) {
	return chickenroad_inout_official_demo_url($abc, $config);
}

/**
 * Optional full-URL override ($config['aviator_demo_iframe_url']); otherwise same as aviator_spribe_official_demo_url().
 *
 * @param array $abc Template globals (expects $abc['lang'] from router).
 * @param array $config Site config.
 */
function aviator_spribe_demo_launch_url(array $abc, array $config) {
	$url = function_exists('site_game_demo_iframe_url') ? site_game_demo_iframe_url($config) : '';
	if ($url !== '') {
		return $url;
	}
	return aviator_spribe_official_demo_url($abc, $config);
}

/**
 * Spribe demo expects a short ISO-style language code (e.g. EN, FR, UK for Ukrainian on many operators).
 * Prefers the first path segment of the current request (e.g. /en/demo/app/ → EN) so the iframe matches the link;
 * then falls back to $abc['lang'] (router / DB).
 */
function aviator_spribe_demo_lang_param(array $abc) {
	$by_path = aviator_spribe_demo_lang_path_map();
	$from_url = aviator_request_uri_first_path_segment();
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
