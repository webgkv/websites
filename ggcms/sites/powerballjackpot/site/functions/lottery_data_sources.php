<?php
/**
 * Homepage lottery data providers — catalog, DB keys, connection tests.
 * Keys are stored in data_source_keys (admin → Data Source → API keys).
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * @return array<string, array<string, mixed>>
 */
function lottery_data_source_providers()
{
	return array(
		'lottery_results_feed' => array(
			'label' => 'Lottery Results Feed',
			'role' => 'primary',
			'auth' => 'Bearer token',
			'auth_header' => 'Authorization',
			'auth_prefix' => 'Bearer ',
			'register_url' => 'https://www.lotteryresultsfeed.com/',
			'docs_url' => 'https://www.lotteryresultsfeed.com/api-docs/introduction',
			'pricing_url' => 'https://www.lotteryresultsfeed.com/pricing',
			'free_tier' => '100 requests/month, ~6h delay on free',
			'key_required' => true,
			'games' => 'Powerball, Mega Millions, EuroMillions, Florida Lotto, Lotto America',
			'notes' => 'Main source for jackpots, draw numbers and Recent Results.',
		),
		'lottery_results_api' => array(
			'label' => 'Lottery Results API',
			'role' => 'backup',
			'auth' => 'X-API-Token',
			'auth_header' => 'X-API-Token',
			'auth_prefix' => '',
			'register_url' => 'https://www.lotteryresultsapi.com/',
			'docs_url' => 'https://docs.lotteryresultsapi.com/documentation/',
			'pricing_url' => 'https://www.lotteryresultsapi.com/en/pricing/',
			'free_tier' => 'Free plan (0€), 8 lotteries, 30-day history',
			'key_required' => true,
			'games' => 'Powerball, EuroMillions, Lotto America (no Mega Millions, no Florida)',
			'notes' => 'Backup for US/EU draw numbers when Feed quota is exhausted.',
		),
		'lotterydata_io' => array(
			'label' => 'LotteryData.io',
			'role' => 'backup',
			'auth' => 'x-api-key header',
			'auth_header' => 'x-api-key',
			'auth_prefix' => '',
			'register_url' => 'https://www.lotterydata.io/',
			'docs_url' => 'https://www.lotterydata.io/docs',
			'pricing_url' => 'https://www.lotterydata.io/pricing',
			'free_tier' => '50 requests/month on free plan',
			'key_required' => true,
			'games' => 'Powerball, Mega Millions, EuroMillions, Lotto America (no Florida)',
			'notes' => 'Reserve for jackpots / Mega Millions when other APIs fail. API path: /{game}/v1/latest (e.g. /powerball/v1/latest).',
		),
		'ny_open_data' => array(
			'label' => 'NY Open Data (Socrata)',
			'role' => 'fallback',
			'auth' => 'API Key ID + Secret (Basic) or App Token (X-App-Token)',
			'auth_header' => 'X-App-Token',
			'auth_prefix' => '',
			'register_url' => 'https://data.ny.gov/profile/edit/developer_settings',
			'docs_url' => 'https://dev.socrata.com/docs/app-tokens.html',
			'pricing_url' => 'https://www.data.ny.gov/developers',
			'free_tier' => 'Free public datasets; higher limits with credentials',
			'key_required' => false,
			'secret_supported' => true,
			'key_label' => 'API Key ID',
			'secret_label' => 'API Key Secret',
			'key_placeholder' => 'API Key ID or App Token',
			'secret_placeholder' => 'Required with API Key ID',
			'games' => 'Powerball and Mega Millions numbers only (no jackpot)',
			'notes' => 'Developer Settings → API Key: paste ID + Secret (both fields). App Token is separate — paste only in Key ID, leave Secret empty.',
		),
	);
}

function lottery_data_source_provider_ids()
{
	return array_keys(lottery_data_source_providers());
}

function lottery_data_source_provider_label($provider)
{
	$all = lottery_data_source_providers();
	$provider = (string) $provider;
	return isset($all[$provider]['label']) ? (string) $all[$provider]['label'] : $provider;
}

/**
 * @return array<string, array{api_key: string, api_secret: string}>|null
 */
function lottery_data_source_enabled_credentials_map()
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$cache = array();
	if (@mysql_select("SHOW TABLES LIKE 'data_source_keys'", 'num_rows') === 0) {
		return $cache;
	}
	$has_secret = @mysql_select("SHOW COLUMNS FROM `data_source_keys` LIKE 'api_secret'", 'num_rows') > 0;
	$cols = $has_secret ? 'provider, api_key, api_secret' : 'provider, api_key';
	$rows = mysql_select(
		'SELECT ' . $cols . ' FROM data_source_keys WHERE enabled=1 ORDER BY id ASC',
		'rows'
	);
	if (!is_array($rows)) {
		return $cache;
	}
	foreach ($rows as $row) {
		$p = trim((string) ($row['provider'] ?? ''));
		$k = trim((string) ($row['api_key'] ?? ''));
		$s = $has_secret ? trim((string) ($row['api_secret'] ?? '')) : '';
		if ($p === '' || ($k === '' && $s === '')) {
			continue;
		}
		if (!isset($cache[$p])) {
			$cache[$p] = array('api_key' => $k, 'api_secret' => $s);
		}
	}
	return $cache;
}

/**
 * @return array<string, string>|null provider => api_key
 */
function lottery_data_source_enabled_keys_map()
{
	$out = array();
	foreach (lottery_data_source_enabled_credentials_map() as $p => $creds) {
		$out[$p] = (string) $creds['api_key'];
	}
	return $out;
}

/**
 * @return array{api_key: string, api_secret: string}|null
 */
function lottery_data_source_get_credentials($provider)
{
	$provider = trim((string) $provider);
	$map = lottery_data_source_enabled_credentials_map();
	return isset($map[$provider]) ? $map[$provider] : null;
}

function lottery_data_source_get_key($provider)
{
	$creds = lottery_data_source_get_credentials($provider);
	return $creds !== null ? (string) $creds['api_key'] : '';
}

function lottery_data_source_get_secret($provider)
{
	$creds = lottery_data_source_get_credentials($provider);
	return $creds !== null ? (string) $creds['api_secret'] : '';
}

/** LotteryData.io base URL — path is /{game}/v1/... not /v1/{game}/... */
function lottery_data_source_lotterydata_url($game_slug, $endpoint = 'latest')
{
	$game_slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $game_slug));
	$endpoint = ltrim((string) $endpoint, '/');
	if ($game_slug === '' || $endpoint === '') {
		return '';
	}
	return 'https://api.lotterydata.io/' . $game_slug . '/v1/' . $endpoint;
}

function lottery_data_source_test($provider, $api_key, $api_secret = '')
{
	$provider = trim((string) $provider);
	$api_key = trim((string) $api_key);
	$api_secret = trim((string) $api_secret);
	$providers = lottery_data_source_providers();
	if (!isset($providers[$provider])) {
		return array('ok' => false, 'message' => 'Unknown provider: ' . $provider, 'full_response' => array());
	}
	$cfg = $providers[$provider];
	if (!empty($cfg['key_required']) && $api_key === '') {
		return array('ok' => false, 'message' => 'API key is required for this provider', 'full_response' => array());
	}
	if ($provider === 'lottery_results_feed') {
		return _lottery_data_source_test_get(
			'https://www.lotteryresultsfeed.com/api/lottery/lottery?id=17',
			'Authorization',
			'Bearer ' . $api_key
		);
	}
	if ($provider === 'lottery_results_api') {
		return _lottery_data_source_test_get(
			'https://api.lotteryresultsapi.com/lottery/us_powerball/sdraw/latest',
			'X-API-Token',
			$api_key
		);
	}
	if ($provider === 'lotterydata_io') {
		return _lottery_data_source_test_get(
			lottery_data_source_lotterydata_url('powerball', 'latest'),
			'x-api-key',
			$api_key
		);
	}
	if ($provider === 'ny_open_data') {
		$url = 'https://data.ny.gov/resource/d6yy-54nr.json?$order=draw_date%20DESC&$limit=1';
		if ($api_key !== '' && $api_secret !== '') {
			return _lottery_data_source_test_get($url, null, null, array(), $api_key, $api_secret);
		}
		if ($api_key !== '' && $api_secret === '') {
			$result = _lottery_data_source_test_get($url, null, null, array('X-App-Token' => $api_key));
			if (empty($result['ok']) && stripos((string) ($result['message'] ?? ''), 'app_token') !== false) {
				$result['message'] = 'Invalid App Token. If this is an API Key ID from Developer Settings, also paste API Key Secret — OAuth keys use Basic auth, not X-App-Token.';
			}
			return $result;
		}
		return _lottery_data_source_test_get($url, null, null, array());
	}
	return array('ok' => false, 'message' => 'Test not implemented', 'full_response' => array());
}

/**
 * @param array<string, string> $extra_headers
 */
function _lottery_data_source_test_get($url, $auth_header, $auth_value, $extra_headers = array(), $basic_user = '', $basic_pass = '')
{
	$ch = curl_init($url);
	$headers = array('Accept: application/json');
	if ($auth_header !== null && $auth_value !== null && $auth_value !== '') {
		$headers[] = $auth_header . ': ' . $auth_value;
	}
	foreach ($extra_headers as $hk => $hv) {
		$headers[] = $hk . ': ' . $hv;
	}
	$opts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPGET => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_CONNECTTIMEOUT => 15,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_NOSIGNAL => 1,
	);
	$basic_user = trim((string) $basic_user);
	$basic_pass = trim((string) $basic_pass);
	if ($basic_user !== '' && $basic_pass !== '') {
		$opts[CURLOPT_USERPWD] = $basic_user . ':' . $basic_pass;
		$opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
	}
	curl_setopt_array($ch, $opts);
	$raw = curl_exec($ch);
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	curl_close($ch);
	$decoded = @json_decode((string) $raw, true);
	$full = array(
		'http_code' => $code,
		'raw_body' => $raw,
		'decoded' => $decoded,
		'curl_error' => $err !== '' ? $err : null,
	);
	if ($err !== '') {
		return array('ok' => false, 'message' => 'cURL error: ' . $err, 'full_response' => $full);
	}
	if ($code < 200 || $code >= 300) {
		$msg = 'HTTP ' . $code;
		if (is_array($decoded)) {
			if (!empty($decoded['message'])) {
				$msg = (string) $decoded['message'];
			} elseif (!empty($decoded['error'])) {
				$msg = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']);
			}
		}
		return array('ok' => false, 'message' => $msg, 'full_response' => $full);
	}
	if (!is_array($decoded) && trim((string) $raw) === '') {
		return array('ok' => false, 'message' => 'Empty response', 'full_response' => $full);
	}
	return array('ok' => true, 'message' => 'Connection OK (HTTP ' . $code . ')', 'full_response' => $full);
}
