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
 * Resolves 3-letter currency for Spribe demo: query override, config override, else resolved offer country geo.
 */
function aviator_spribe_demo_currency_param(array $abc, array $config = array()) {
	// 1. Support query parameter override for easy testing and debugging
	if (!empty($_GET['country']) && preg_match('/^[A-Za-z]{2}$/', $_GET['country'])) {
		$country = strtoupper($_GET['country']);
		$cur = aviator_spribe_demo_currency_for_country($country);
		return aviator_spribe_demo_currency_normalize_for_launch($cur);
	}
	if (!empty($_GET['currency']) && preg_match('/^[A-Za-z]{3}$/', $_GET['currency'])) {
		return strtoupper($_GET['currency']);
	}

	// 2. Check config override first
	if (!empty($config['aviator_demo_currency']) && preg_match('/^[A-Za-z]{3}$/', (string) $config['aviator_demo_currency'])) {
		return strtoupper((string) $config['aviator_demo_currency']);
	}

	// 3. Resolve using the exact same logic as offers (using advertising_api functions)
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
 * Resolved mirror/Spribe demo launch parameters (shared by loader, probe, debug page).
 */
function aviator_demo_mirror_resolve_params(array $abc, array $config) {
	$currency = aviator_spribe_demo_currency_param($abc, $config);
	$langSpribe = aviator_spribe_demo_lang_param($abc);
	$lang = strtolower($langSpribe);

	$apiUrl = !empty($config['aviator_demo_mirror_api_url'])
		? trim((string) $config['aviator_demo_mirror_api_url'])
		: 'https://fwu21pcmpk1m14q.gmngdoor.link/';

	$gameId = !empty($config['aviator_demo_mirror_game_id'])
		? trim((string) $config['aviator_demo_mirror_game_id'])
		: 'aviator_spribe';

	if (!empty($config['aviator_demo_mirror_bank_group_id'])) {
		$bankGroupId = trim((string) $config['aviator_demo_mirror_bank_group_id']);
	} else {
		$bankGroupId = 'GoldenBet_' . $currency;
	}

	if (!function_exists('aviator_ad_resolve_ip_context') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/advertising_api.php')) {
		require_once ROOT_DIR . 'functions/advertising_api.php';
	}
	$playerIp = '127.0.0.1';
	if (function_exists('aviator_ad_resolve_ip_context')) {
		$ad = (isset($abc['advertising_api']) && is_array($abc['advertising_api'])) ? $abc['advertising_api'] : array();
		$ip_ctx = aviator_ad_resolve_ip_context($ad);
		$playerIp = isset($ip_ctx['ip_sent_to_backend']) ? trim((string) $ip_ctx['ip_sent_to_backend']) : '127.0.0.1';
	}

	$spribeFallbackUrl = 'https://demo.spribe.io/launch/aviator?' . http_build_query(
		array('currency' => $currency, 'lang' => $langSpribe),
		'',
		'&',
		PHP_QUERY_RFC3986
	);

	return array(
		'mirror_api_url' => $apiUrl,
		'game_id' => $gameId,
		'bank_group_id' => $bankGroupId,
		'currency' => $currency,
		'lang_spribe' => $langSpribe,
		'lang' => $lang,
		'player_ip' => $playerIp,
		'spribe_fallback_url' => $spribeFallbackUrl,
	);
}

/**
 * Build Session.CreateDemo JSON-RPC payload (server-side: real PlayerIp).
 */
function aviator_demo_mirror_session_payload(array $params, $playerIpOverride = null) {
	$playerIp = ($playerIpOverride !== null) ? (string) $playerIpOverride : (string) $params['player_ip'];
	return array(
		'jsonrpc' => '2.0',
		'method' => 'Session.CreateDemo',
		'id' => (int) time(),
		'params' => array(
			'BankGroupId' => $params['bank_group_id'],
			'GameId' => $params['game_id'],
			'StartBalance' => 10000,
			'Params' => array(
				'language' => $params['lang'],
			),
			'PlayerIp' => $playerIp,
		),
	);
}

/**
 * Lightweight HTTP probe (HEAD, fallback GET) for debug pages.
 */
function aviator_demo_probe_http_url($url, $timeoutSec = 5) {
	$url = trim((string) $url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return array('url' => $url, 'ok' => false, 'error' => 'invalid_url');
	}
	$started = microtime(true);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeoutSec);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(3, (int) $timeoutSec));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_exec($ch);
	$err = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	$elapsedMs = (int) round((microtime(true) - $started) * 1000);
	$httpCode = isset($info['http_code']) ? (int) $info['http_code'] : 0;
	if ($httpCode === 0 && $err !== '') {
		return array(
			'url' => $url,
			'ok' => false,
			'method' => 'HEAD',
			'http_code' => 0,
			'curl_error' => $err,
			'elapsed_ms' => $elapsedMs,
		);
	}
	return array(
		'url' => $url,
		'ok' => ($httpCode >= 200 && $httpCode < 400),
		'method' => 'HEAD',
		'http_code' => $httpCode,
		'effective_url' => isset($info['url']) ? (string) $info['url'] : $url,
		'elapsed_ms' => $elapsedMs,
	);
}

/**
 * Server-side mirror Session.CreateDemo probe with full request/response capture.
 */
function aviator_demo_mirror_session_probe(array $abc, array $config, array $opts = array()) {
	$params = aviator_demo_mirror_resolve_params($abc, $config);
	$timeout = isset($opts['timeout']) ? max(1, (int) $opts['timeout']) : 4;
	$connectTimeout = isset($opts['connect_timeout']) ? max(1, (int) $opts['connect_timeout']) : 3;
	$payload = aviator_demo_mirror_session_payload($params);

	$started = microtime(true);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['mirror_api_url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$response = curl_exec($ch);
	$err = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	$data = ($response !== false && $response !== '') ? json_decode($response, true) : null;
	$launchUrl = (is_array($data) && isset($data['result']['Url'])) ? trim((string) $data['result']['Url']) : null;

	return array(
		'api_url_called' => $params['mirror_api_url'],
		'payload_sent' => $payload,
		'http_code' => isset($info['http_code']) ? (int) $info['http_code'] : 0,
		'curl_error' => $err !== '' ? $err : null,
		'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
		'response_raw' => $response !== false ? $response : null,
		'response_parsed' => $data,
		'launch_url' => ($launchUrl !== null && $launchUrl !== '') ? $launchUrl : null,
		'success' => ($launchUrl !== null && $launchUrl !== ''),
	);
}

/**
 * Full debug payload for /{lang}/demo/app/?debug_ip_check=1 (admin only).
 */
function aviator_demo_app_build_debug_payload(array $abc, array $config) {
	if (!function_exists('aviator_ad_resolve_ip_context') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/advertising_api.php')) {
		require_once ROOT_DIR . 'functions/advertising_api.php';
	}

	$params = aviator_demo_mirror_resolve_params($abc, $config);
	$ad = (isset($abc['advertising_api']) && is_array($abc['advertising_api'])) ? $abc['advertising_api'] : array();
	$ip_ctx = function_exists('aviator_ad_resolve_ip_context') ? aviator_ad_resolve_ip_context($ad) : array();
	$country_ctx = function_exists('aviator_ad_resolve_country_context') ? aviator_ad_resolve_country_context($ad, $ip_ctx) : array();

	$serverProbe = aviator_demo_mirror_session_probe($abc, $config);
	$clientPayload = aviator_demo_mirror_session_payload($params, '');

	$launchSource = 'spribe_fallback';
	$iframeUrl = $params['spribe_fallback_url'];
	if (!empty($serverProbe['launch_url'])) {
		$launchSource = 'mirror';
		$iframeUrl = $serverProbe['launch_url'];
	}

	$spribeProbe = aviator_demo_probe_http_url($params['spribe_fallback_url'], 5);
	$mirrorLaunchProbe = null;
	if (!empty($serverProbe['launch_url'])) {
		$mirrorLaunchProbe = aviator_demo_probe_http_url($serverProbe['launch_url'], 5);
	}

	return array(
		'page' => 'demo_app',
		'note' => 'Game iframe is not loaded in this mode. Server-side probes only.',
		'request' => array(
			'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
			'query' => isset($_GET) && is_array($_GET) ? $_GET : array(),
			'layout' => isset($abc['layout']) ? (string) $abc['layout'] : '',
		),
		'ip_country' => array(
			'remote_addr' => isset($ip_ctx['remote_addr']) ? (string) $ip_ctx['remote_addr'] : '',
			'trusted_real_ip' => isset($ip_ctx['trusted_real_ip']) ? (string) $ip_ctx['trusted_real_ip'] : '',
			'ip_sent_to_backend' => isset($ip_ctx['ip_sent_to_backend']) ? (string) $ip_ctx['ip_sent_to_backend'] : '',
			'country_header_cf' => isset($country_ctx['country_header_cf']) ? (string) $country_ctx['country_header_cf'] : '',
			'country_by_local_geo' => isset($country_ctx['country_by_local_geo']) ? (string) $country_ctx['country_by_local_geo'] : '',
			'country_sent_to_backend' => isset($country_ctx['country_sent_to_backend']) ? (string) $country_ctx['country_sent_to_backend'] : '',
			'source_of_country' => isset($country_ctx['source_of_country']) ? (string) $country_ctx['source_of_country'] : '',
		),
		'resolved_params' => $params,
		'production_flow' => array(
			'step_1' => 'Browser fetch() POST to mirror_api_url with Session.CreateDemo (timeout ~2800ms, PlayerIp empty string in JS).',
			'step_2' => 'If result.Url present → iframe.src = mirror launch URL.',
			'step_3' => 'On error/timeout → iframe.src = spribe_fallback_url.',
			'client_payload' => $clientPayload,
			'client_timeout_ms' => 2800,
			'server_side_note' => 'Server-side probe below uses real PlayerIp; browser JS currently sends PlayerIp as empty string.',
		),
		'mirror_api_probe_server' => $serverProbe,
		'spribe_fallback_probe' => $spribeProbe,
		'mirror_launch_url_probe' => $mirrorLaunchProbe,
		'launch_decision' => array(
			'source' => $launchSource,
			'iframe_url_would_be' => $iframeUrl,
			'mirror_session_ok' => !empty($serverProbe['success']),
		),
	);
}

/**
 * Attempt to request a dynamic session from the mirror game aggregator API (e.g., gmngdoor.link).
 *
 * @param array $abc Template globals.
 * @param array $config Site config.
 * @return string|null Resolved game launch URL, or null on failure/timeout.
 */
function aviator_load_mirror_demo_url(array $abc, array $config) {
	$probe = aviator_demo_mirror_session_probe($abc, $config);

	if (!empty($GLOBALS['abc']['debug_ip_check'])) {
		$GLOBALS['abc']['debug_ip_check_info']['mirror_api_debug'] = array(
			'api_url_called' => $probe['api_url_called'],
			'payload_sent' => $probe['payload_sent'],
			'http_code' => $probe['http_code'],
			'curl_error' => $probe['curl_error'],
			'response_raw' => $probe['response_raw'],
			'response_parsed' => $probe['response_parsed'],
		);
	}

	if (!empty($probe['curl_error']) && empty($probe['response_raw'])) {
		error_log('Aviator mirror demo session cURL error: ' . $probe['curl_error']);
		return null;
	}

	if (!empty($probe['launch_url'])) {
		return $probe['launch_url'];
	}

	if (is_array($probe['response_parsed']) && isset($probe['response_parsed']['error'])) {
		error_log('Aviator mirror demo session API error: ' . json_encode($probe['response_parsed']['error']));
	}

	return null;
}


/**
 * Returns dynamic mirror URL if available, falling back to official Spribe URL.
 */
function aviator_spribe_official_demo_url(array $abc, array $config = array()) {
	// Fallback to official Spribe launch URL directly on the server-side
	// to ensure instantaneous page loads and prevent cURL timeout hangs.
	// The mirror session is generated dynamically on the client-side instead.
	$currency = aviator_spribe_demo_currency_param($abc, $config);
	$lang = aviator_spribe_demo_lang_param($abc);
	return 'https://demo.spribe.io/launch/aviator?' . http_build_query(
		array('currency' => $currency, 'lang' => $lang),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
}

/**
 * Optional full-URL override ($config['aviator_demo_iframe_url']); otherwise same as aviator_spribe_official_demo_url().
 *
 * @param array $abc Template globals (expects $abc['lang'] from router).
 * @param array $config Site config.
 */
function aviator_spribe_demo_launch_url(array $abc, array $config) {
	if (!empty($config['aviator_demo_iframe_url']) && trim((string) $config['aviator_demo_iframe_url']) !== '') {
		return trim((string) $config['aviator_demo_iframe_url']);
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

/**
 * Filter database-loaded HTML page content, swapping any static Spribe or gmngdoor iframe with a dynamic launch URL.
 * All comments are kept in English as per project standards.
 */
function aviator_filter_demo_content_iframe($html, array $abc, array $config) {
	if (empty($html) || !is_string($html)) {
		return $html;
	}

	// Strictly restrict mirror replacements to the fullscreen demo app page (/demo/app/).
	// Informational and article pages like /en/demo/ must remain static and have game iframes stripped.
	if (isset($_SERVER['REQUEST_URI']) && stripos((string)$_SERVER['REQUEST_URI'], '/demo/app') === false) {
		// Strip iframe with closing tag
		$html = preg_replace('/<iframe\b[^>]*?src=["\']https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?["\'][^>]*?>.*?<\/iframe>/is', '', $html);
		// Strip iframe without closing tag if any
		$html = preg_replace('/<iframe\b[^>]*?src=["\']https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?["\'][^>]*?>/is', '', $html);
		return $html;
	}

	// Regular expression to identify any game iframe that contains spribe.io or gmngdoor.link in the src attribute
	$pattern = '/<iframe([^>]*?)src=(["\'])(https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?)\2([^>]*?)>/i';
	
	// Perform regex callback replacement to dynamically insert our premium client-side mirror loader
	$replaced = preg_replace_callback($pattern, function($m) use ($abc, $config) {
		$attrs = $m[1] . $m[4];
		
		// Extract width, height, class, and style to apply them to the container
		$width = '100%';
		$height = '500px';
		$class = 'spribe-demo-iframe';
		$style = '';
		
		if (preg_match('/width=(["\'])(.*?)\1/i', $attrs, $w_match)) $width = $w_match[2];
		if (preg_match('/height=(["\'])(.*?)\1/i', $attrs, $h_match)) $height = $h_match[2];
		if (preg_match('/class=(["\'])(.*?)\1/i', $attrs, $c_match)) $class = $c_match[2];
		if (preg_match('/style=(["\'])(.*?)\1/i', $attrs, $s_match)) $style = $s_match[2];
		
		// Clean up attributes for the iframe itself to avoid duplicate src/width/height/style/class
		$iframe_attrs = preg_replace('/\b(width|height|class|style|src)=["\'].*?["\']/i', '', $attrs);
		
		// Resolved PHP parameters
		$currency = aviator_spribe_demo_currency_param($abc, $config);
		$lang = strtolower(aviator_spribe_demo_lang_param($abc));
		$mirrorApiUrl = !empty($config['aviator_demo_mirror_api_url']) 
			? trim((string) $config['aviator_demo_mirror_api_url']) 
			: 'https://fwu21pcmpk1m14q.gmngdoor.link/';
		$mirrorGameId = !empty($config['aviator_demo_mirror_game_id']) 
			? trim((string) $config['aviator_demo_mirror_game_id']) 
			: 'aviator_spribe';
		$mirrorBankGroupId = !empty($config['aviator_demo_mirror_bank_group_id']) 
			? trim((string) $config['aviator_demo_mirror_bank_group_id']) 
			: 'GoldenBet_' . $currency;
			
		$spribeFallbackUrl = 'https://demo.spribe.io/launch/aviator?currency=' . rawurlencode($currency) . '&lang=' . rawurlencode($lang);
		
		// Check if user is an authorized admin
		if (!function_exists('access') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/auth_func.php')) {
			require_once ROOT_DIR . 'functions/auth_func.php';
		}
		$isAdmin = (function_exists('access') && access('user admin'));
		$isDebugAllowed = $isAdmin && !empty($_GET['debug_ip_check']);
		
		$uniqueId = 'demo_' . rand(1000, 9999);
		
		// Build the rich dynamic client-side loader markup
		return '
<div class="spribe-demo-container-outer" style="position: relative; width: ' . $width . '; height: ' . $height . '; min-height: 350px; background: #151b24; overflow: hidden; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); ' . $style . '">
    <!-- Premium Loader -->
    <div id="loader_' . $uniqueId . '" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at center, #1b2430 0%, #0f141c 100%); z-index: 10; transition: opacity 0.6s ease; font-family: \'Inter\', -apple-system, BlinkMacSystemFont, sans-serif;">
        <div style="position: relative; width: 64px; height: 64px; margin-bottom: 20px; animation: floatPlane_' . $uniqueId . ' 3s ease-in-out infinite;">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; filter: drop-shadow(0 0 10px #e50539);">
                <path d="M12 28L48 10L36 34L12 28Z" fill="#e50539"/>
                <path d="M48 10L32 44L28 52L24 38L48 10Z" fill="#b30229"/>
                <path d="M12 28L24 38L48 10L12 28Z" fill="#ff2e5f"/>
            </svg>
            <div style="position: absolute; inset: -8px; border: 2px solid rgba(229,5,57,0.3); border-radius: 50%; animation: pulseRing_' . $uniqueId . ' 1.8s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;"></div>
        </div>
        <h3 style="color: #ffffff; font-size: 1rem; font-weight: 600; margin: 0 0 6px 0; letter-spacing: 0.5px; text-transform: uppercase;">Initializing Demo</h3>
        <p id="status_' . $uniqueId . '" style="color: #8a99ad; font-size: 0.8rem; margin: 0 0 16px 0;">Connecting to secure mirror network...</p>
        <div style="width: 160px; height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden; position: relative;">
            <div id="bar_' . $uniqueId . '" style="position: absolute; left: 0; top: 0; height: 100%; width: 10%; background: linear-gradient(90deg, #ff2e5f, #e50539); border-radius: 2px; box-shadow: 0 0 6px #ff2e5f; transition: width 0.4s cubic-bezier(0.1, 0.8, 0.1, 1);"></div>
        </div>
    </div>

    <!-- Game Iframe -->
    <iframe id="iframe_' . $uniqueId . '" class="' . $class . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; opacity: 0; transition: opacity 0.8s ease;" title="Aviator Demo" ' . $iframe_attrs . ' allowfullscreen></iframe>
</div>

<style>
@keyframes floatPlane_' . $uniqueId . ' {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-6px) rotate(-2deg); }
}
@keyframes pulseRing_' . $uniqueId . ' {
    0% { transform: scale(0.7); opacity: 1; }
    80%, 100% { transform: scale(1.3); opacity: 0; }
}
</style>

<script>
(function() {
    var iframe = document.getElementById("iframe_' . $uniqueId . '");
    var loader = document.getElementById("loader_' . $uniqueId . '");
    var bar = document.getElementById("bar_' . $uniqueId . '");
    var status = document.getElementById("status_' . $uniqueId . '");
    
    var currency = ' . json_encode($currency) . ';
    var lang = ' . json_encode($lang) . ';
    var mirrorApiUrl = ' . json_encode($mirrorApiUrl) . ';
    var mirrorGameId = ' . json_encode($mirrorGameId) . ';
    var mirrorBankGroupId = ' . json_encode($mirrorBankGroupId) . ';
    var spribeFallbackUrl = ' . json_encode($spribeFallbackUrl) . ';
    var isDebugAllowed = ' . json_encode($isDebugAllowed) . ';
    
    var isLoaded = false;
    
    function setProgress(pct, statusText) {
        if (bar) bar.style.width = pct + "%";
        if (status && statusText) status.textContent = statusText;
    }
    
    function loadGame(url, source) {
        if (isLoaded) return;
        isLoaded = true;
        setProgress(100, "Launching game...");
        iframe.src = url;
        
        if (isDebugAllowed) {
            iframe.setAttribute("data-game-source", source);
            if (source === "mirror") {
                console.log("%c🟢 [Aviator Demo] Successfully loaded from MIRROR aggregator!", "color: #00ff00; font-size: 14px; font-weight: bold; padding: 4px;");
            } else {
                console.log("%c🔴 [Aviator Demo] Aggregator failed or timed out. Loaded from OFFICIAL SPRIBE fallback!", "color: #ff3333; font-size: 14px; font-weight: bold; padding: 4px;");
            }
        }
        
        iframe.onload = function() {
            setTimeout(function() {
                loader.style.opacity = "0";
                iframe.style.opacity = "1";
                setTimeout(function() {
                    loader.style.display = "none";
                }, 600);
            }, 500);
        };
    }
    
    setProgress(30, "Connecting to game server...");
    
    var controller = new AbortController();
    var timeoutId = setTimeout(function() {
        controller.abort();
    }, 2800);
    
    fetch(mirrorApiUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        signal: controller.signal,
        body: JSON.stringify({
            jsonrpc: "2.0",
            method: "Session.CreateDemo",
            id: Date.now(),
            params: {
                BankGroupId: mirrorBankGroupId,
                GameId: mirrorGameId,
                StartBalance: 10000,
                Params: { language: lang },
                PlayerIp: ""
            }
        })
    })
    .then(function(res) {
        clearTimeout(timeoutId);
        setProgress(70, "Securing dynamic session...");
        return res.json();
    })
    .then(function(data) {
        if (data.result && data.result.Url) {
            loadGame(data.result.Url, "mirror");
        } else {
            if (isDebugAllowed) {
                console.warn("Mirror aggregator API error, using fallback", data.error || data);
            }
            setProgress(85, "Switching to backup game server...");
            loadGame(spribeFallbackUrl, "spribe");
        }
    })
    .catch(function(err) {
        clearTimeout(timeoutId);
        if (isDebugAllowed) {
            console.warn("Mirror aggregator connection failed, using fallback", err);
        }
        setProgress(85, "Switching to backup game server...");
        loadGame(spribeFallbackUrl, "spribe");
    });
})();
</script>
';
	}, $html);

	if ($replaced !== null) {
		return $replaced;
	}

	return $html;
}
