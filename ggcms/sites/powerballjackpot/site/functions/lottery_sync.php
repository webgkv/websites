<?php
/**
 * Fetch homepage lottery jackpots + latest draws from configured API providers.
 * Writes site/files/cache/home_lottery_live.json (atomic).
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

require_once ROOT_DIR . 'functions/lottery_data_sources.php';

function lottery_sync_live_cache_path()
{
	return ROOT_DIR . 'files/cache/home_lottery_live.json';
}

/**
 * @return array<string, array<string, mixed>>
 */
function lottery_sync_game_providers()
{
	return array(
		'powerball' => array(
			'lrf_id' => 17,
			'ldata_slug' => 'powerball',
			'lra_slug' => 'us_powerball',
			'ny_dataset' => 'd6yy-54nr',
			'currency_symbol' => '$',
		),
		'mega-millions' => array(
			'lrf_id' => 18,
			'ldata_slug' => 'megamillions',
			'lra_slug' => 'us_mega_millions',
			'ny_dataset' => '5xaw-6ayf',
			'currency_symbol' => '$',
		),
		'euro-millions' => array(
			'lrf_id' => 722,
			'ldata_slug' => 'euromillions',
			'lra_slug' => 'euromillions',
			'currency_symbol' => '€',
		),
		'florida' => array(
			'lrf_id' => 64,
			'ldata_slug' => '',
			'lra_slug' => 'us_florida_lotto',
			'currency_symbol' => '$',
		),
		'hot-lotto' => array(
			'lrf_id' => 95,
			'ldata_slug' => 'lottoamerica',
			'lra_slug' => 'us_lotto_america',
			'currency_symbol' => '$',
		),
	);
}

/**
 * @return array<string, mixed>
 */
function lottery_sync_live_load()
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$path = lottery_sync_live_cache_path();
	$cache = array();
	if (!is_readable($path)) {
		return $cache;
	}
	$dec = json_decode((string) file_get_contents($path), true);
	if (is_array($dec)) {
		$cache = $dec;
	}
	return $cache;
}

/**
 * @param array<string, mixed> $payload
 */
function lottery_sync_live_save(array $payload)
{
	$dir = dirname(lottery_sync_live_cache_path());
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	$path = lottery_sync_live_cache_path();
	$tmp = $path . '.tmp.' . getmypid();
	$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	if ($json === false || file_put_contents($tmp, $json . "\n") === false) {
		@unlink($tmp);
		return false;
	}
	if (!@rename($tmp, $path)) {
		@unlink($tmp);
		return false;
	}
	return true;
}

/**
 * @param array<string, mixed> $options
 * @return array{ok: bool, message: string, games_updated: int, errors: array<int, string>, log: array<int, string>}
 */
function lottery_sync_run(array $options = array())
{
	require_once ROOT_DIR . 'functions/site_home_lottery.php';

	$log = array();
	$errors = array();
	$games_out = array();
	$providers = lottery_sync_game_providers();
	$enabled_ids = array();
	foreach (site_home_lottery_enabled_games() as $g) {
		$enabled_ids[] = (string) $g['id'];
	}

	foreach ($enabled_ids as $game_id) {
		if (!isset($providers[$game_id])) {
			$errors[] = $game_id . ': no provider mapping';
			continue;
		}
		$static = site_home_lottery_game_by_id($game_id);
		$symbol = (string) ($static['currency_symbol'] ?? $providers[$game_id]['currency_symbol'] ?? '$');
		$fetched = lottery_sync_fetch_game($game_id, $providers[$game_id], $symbol, $log);
		if ($fetched === null) {
			$errors[] = $game_id . ': no data from any provider';
			$games_out[$game_id] = lottery_sync_static_game_fallback($static);
			continue;
		}
		$games_out[$game_id] = $fetched;
		$log[] = $game_id . ': ok via ' . ($fetched['source'] ?? 'unknown');
	}

	$recent = lottery_sync_build_recent_results($games_out, $enabled_ids);
	$payload = array(
		'fetched_at' => gmdate('c'),
		'games' => $games_out,
		'recent_results' => $recent,
		'sync_log' => $log,
		'errors' => $errors,
	);
	$saved = lottery_sync_live_save($payload);
	if (!$saved) {
		$errors[] = 'Failed to write cache file';
	}

	if (function_exists('system_log_add')) {
		$level = empty($errors) ? 'info' : 'warning';
		system_log_add('lottery', $level, 'Homepage lottery sync: ' . count($games_out) . ' games', array(
			'errors' => $errors,
			'log' => $log,
		));
	}

	$ok = $saved && count($errors) < count($enabled_ids);
	return array(
		'ok' => $ok,
		'message' => $saved
			? ('Synced ' . count($games_out) . ' games' . (empty($errors) ? '' : ' (' . count($errors) . ' warnings)'))
			: 'Sync failed — could not write cache',
		'games_updated' => count($games_out),
		'errors' => $errors,
		'log' => $log,
		'fetched_at' => $payload['fetched_at'],
	);
}

/**
 * @param array<string, mixed>|null $static
 * @return array<string, mixed>
 */
function lottery_sync_static_game_fallback($static)
{
	if (!is_array($static)) {
		return array();
	}
	return array(
		'prize_display' => (string) ($static['prize_display'] ?? ''),
		'draw_hint' => (string) ($static['draw_hint'] ?? ''),
		'source' => 'static',
	);
}

/**
 * @param array<string, mixed> $map
 * @param array<int, string> $log
 * @return array<string, mixed>|null
 */
function lottery_sync_fetch_game($game_id, array $map, $symbol, array &$log)
{
	$creds = lottery_data_source_get_credentials('lottery_results_feed');
	if ($creds !== null && !empty($map['lrf_id']) && $creds['api_key'] !== '') {
		$row = lottery_sync_fetch_lrf((int) $map['lrf_id'], $creds['api_key'], $symbol);
		if ($row !== null) {
			$row['source'] = 'lottery_results_feed';
			return $row;
		}
		$log[] = $game_id . ': LRF failed';
	}

	$creds = lottery_data_source_get_credentials('lotterydata_io');
	if ($creds !== null && !empty($map['ldata_slug']) && $creds['api_key'] !== '') {
		$row = lottery_sync_fetch_lotterydata((string) $map['ldata_slug'], $creds['api_key'], $symbol);
		if ($row !== null) {
			$row['source'] = 'lotterydata_io';
			return $row;
		}
		$log[] = $game_id . ': LotteryData.io failed';
	}

	$creds = lottery_data_source_get_credentials('lottery_results_api');
	if ($creds !== null && !empty($map['lra_slug']) && $creds['api_key'] !== '') {
		$row = lottery_sync_fetch_lra((string) $map['lra_slug'], $creds['api_key'], $symbol);
		if ($row !== null) {
			$row['source'] = 'lottery_results_api';
			return $row;
		}
		$log[] = $game_id . ': Lottery Results API failed';
	}

	$creds = lottery_data_source_get_credentials('ny_open_data');
	if ($creds !== null && !empty($map['ny_dataset'])) {
		$row = lottery_sync_fetch_ny((string) $map['ny_dataset'], $creds, $symbol);
		if ($row !== null) {
			$row['source'] = 'ny_open_data';
			return $row;
		}
		$log[] = $game_id . ': NY Open Data failed';
	}

	return null;
}

function lottery_sync_fetch_lrf($lrf_id, $api_key, $symbol)
{
	$url = 'https://www.lotteryresultsfeed.com/api/lottery/lottery?id=' . (int) $lrf_id;
	$res = lottery_sync_http_get($url, array(
		'Authorization' => 'Bearer ' . $api_key,
	));
	if (!$res['ok'] || !is_array($res['json'])) {
		return null;
	}
	$lottery = $res['json']['lottery'] ?? null;
	if (!is_array($lottery)) {
		return null;
	}
	$latest = $lottery['results_latest'] ?? null;
	if (!is_array($latest)) {
		return null;
	}

	$nums = array();
	if (!empty($latest['balls']) && is_array($latest['balls'])) {
		foreach ($latest['balls'] as $b) {
			$nums[] = (int) $b;
		}
	}
	if (isset($latest['ball_bonus']) && $latest['ball_bonus'] !== null && $latest['ball_bonus'] !== '') {
		if (is_array($latest['ball_bonus'])) {
			foreach ($latest['ball_bonus'] as $b) {
				$nums[] = (int) $b;
			}
		} else {
			$nums[] = (int) $latest['ball_bonus'];
		}
	}

	$jackpot_raw = $latest['next_jackpot'] ?? $latest['jackpot'] ?? null;
	$draw_date = (string) ($latest['draw_date'] ?? '');

	return array(
		'prize_display' => lottery_sync_format_jackpot($jackpot_raw, $symbol),
		'draw_hint' => lottery_sync_draw_hint_from_lrf($lottery),
		'latest' => array(
			'draw_date' => $draw_date,
			'nums' => $nums,
			'jackpot_raw' => $jackpot_raw,
			'jackpot_display' => lottery_sync_format_jackpot($latest['jackpot'] ?? $jackpot_raw, $symbol),
		),
	);
}

function lottery_sync_fetch_lotterydata($slug, $api_key, $symbol)
{
	$url = lottery_data_source_lotterydata_url($slug, 'latest');
	if ($url === '') {
		return null;
	}
	$res = lottery_sync_http_get($url, array('x-api-key' => $api_key));
	if (!$res['ok'] || !is_array($res['json'])) {
		return null;
	}
	$row = null;
	if (!empty($res['json']['data'][0]) && is_array($res['json']['data'][0])) {
		$row = $res['json']['data'][0];
	} elseif (is_array($res['json']) && isset($res['json']['drawing_date'])) {
		$row = $res['json'];
	}
	if ($row === null) {
		return null;
	}

	$nums = lottery_sync_extract_number_fields($row, array(
		'ball1', 'ball2', 'ball3', 'ball4', 'ball5', 'ball6',
		'powerball', 'megaball', 'mega_ball', 'star_ball', 'lucky_star_1', 'lucky_star_2',
	));
	if (empty($nums) && !empty($row['number_set'])) {
		$nums = lottery_sync_parse_number_set((string) $row['number_set']);
	}

	$draw_date = (string) ($row['drawing_date'] ?? $row['draw_date'] ?? '');
	$jackpot = $row['next_jackpot'] ?? $row['jackpot'] ?? null;
	$draw_hint = '';
	if (!empty($row['next_drawing_date'])) {
		$draw_hint = lottery_sync_draw_hint_from_datetime((string) $row['next_drawing_date']);
	}

	return array(
		'prize_display' => lottery_sync_format_jackpot($jackpot, $symbol),
		'draw_hint' => $draw_hint,
		'latest' => array(
			'draw_date' => $draw_date,
			'nums' => $nums,
			'jackpot_raw' => $jackpot,
			'jackpot_display' => lottery_sync_format_jackpot($row['jackpot'] ?? $jackpot, $symbol),
		),
	);
}

function lottery_sync_fetch_lra($slug, $api_key, $symbol)
{
	$url = 'https://api.lotteryresultsapi.com/lottery/' . rawurlencode($slug) . '/sdraw/latest';
	$res = lottery_sync_http_get($url, array('X-API-Token' => $api_key));
	if (!$res['ok'] || !is_array($res['json'])) {
		return null;
	}
	$draw = $res['json']['sdraw'] ?? $res['json']['draw'] ?? $res['json'];
	if (!is_array($draw)) {
		return null;
	}

	$nums = array();
	if (!empty($draw['numbers']) && is_array($draw['numbers'])) {
		foreach ($draw['numbers'] as $n) {
			$nums[] = (int) $n;
		}
	} elseif (!empty($draw['balls']) && is_array($draw['balls'])) {
		foreach ($draw['balls'] as $n) {
			$nums[] = (int) $n;
		}
	}
	if (!empty($draw['bonus']) && is_array($draw['bonus'])) {
		foreach ($draw['bonus'] as $n) {
			$nums[] = (int) $n;
		}
	} elseif (isset($draw['bonus_ball'])) {
		$nums[] = (int) $draw['bonus_ball'];
	}

	$draw_date = (string) ($draw['date'] ?? $draw['draw_date'] ?? '');
	$jackpot = $draw['jackpot'] ?? $draw['prize'] ?? null;

	return array(
		'prize_display' => lottery_sync_format_jackpot($jackpot, $symbol),
		'draw_hint' => '',
		'latest' => array(
			'draw_date' => $draw_date,
			'nums' => $nums,
			'jackpot_raw' => $jackpot,
			'jackpot_display' => lottery_sync_format_jackpot($jackpot, $symbol),
		),
	);
}

/**
 * @param array{api_key: string, api_secret: string} $creds
 */
function lottery_sync_fetch_ny($dataset, array $creds, $symbol)
{
	$dataset = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $dataset));
	if ($dataset === '') {
		return null;
	}
	$url = 'https://data.ny.gov/resource/' . $dataset . '.json?$order=draw_date%20DESC&$limit=1';
	$headers = array();
	$basic = null;
	if ($creds['api_key'] !== '' && $creds['api_secret'] !== '') {
		$basic = array($creds['api_key'], $creds['api_secret']);
	} elseif ($creds['api_key'] !== '') {
		$headers['X-App-Token'] = $creds['api_key'];
	}
	$res = lottery_sync_http_get($url, $headers, $basic);
	if (!$res['ok'] || !is_array($res['json']) || empty($res['json'][0])) {
		return null;
	}
	$row = $res['json'][0];
	$nums = array();
	if (!empty($row['winning_numbers'])) {
		$nums = lottery_sync_parse_number_set((string) $row['winning_numbers']);
	}
	foreach (array('mega_ball', 'powerball', 'bonus_ball') as $k) {
		if (isset($row[$k]) && $row[$k] !== '') {
			$nums[] = (int) $row[$k];
		}
	}

	$draw_date = lottery_sync_normalize_date((string) ($row['draw_date'] ?? ''));

	return array(
		'prize_display' => '',
		'draw_hint' => '',
		'latest' => array(
			'draw_date' => $draw_date,
			'nums' => $nums,
			'jackpot_raw' => null,
			'jackpot_display' => '',
		),
	);
}

/**
 * @param array<string, mixed> $games_live
 * @param array<int, string> $order
 * @return array<int, array<string, mixed>>
 */
function lottery_sync_build_recent_results(array $games_live, array $order)
{
	$rows = array();
	foreach ($order as $game_id) {
		if (empty($games_live[$game_id]['latest']['nums'])) {
			continue;
		}
		$static = site_home_lottery_game_by_id($game_id);
		if ($static === null) {
			continue;
		}
		$latest = $games_live[$game_id]['latest'];
		$nums = array_map('intval', (array) $latest['nums']);
		$draw_date = (string) ($latest['draw_date'] ?? '');
		$amount = (string) ($latest['jackpot_display'] ?? $games_live[$game_id]['prize_display'] ?? '');
		if ($amount === '') {
			$amount = '—';
		}
		$rows[] = array(
			'game_id' => $game_id,
			'img' => (string) ($static['icon'] ?? 'ball-icon-1.png'),
			'winner' => (string) ($static['name'] ?? $game_id),
			'ago' => lottery_sync_time_ago($draw_date),
			'amount' => $amount,
			'date' => lottery_sync_format_display_date($draw_date),
			'nums' => $nums,
			'active' => lottery_sync_pick_active_ball($nums),
			'_sort_ts' => lottery_sync_date_ts($draw_date),
		);
	}
	usort($rows, function ($a, $b) {
		return ($b['_sort_ts'] ?? 0) <=> ($a['_sort_ts'] ?? 0);
	});
	$out = array();
	foreach (array_slice($rows, 0, 4) as $row) {
		unset($row['_sort_ts']);
		$out[] = $row;
	}
	return $out;
}

function lottery_sync_pick_active_ball(array $nums)
{
	if (empty($nums)) {
		return null;
	}
	if (count($nums) > 1) {
		return (int) $nums[count($nums) - 2];
	}
	return (int) $nums[0];
}

function lottery_sync_format_jackpot($amount, $symbol = '$')
{
	if ($amount === null || $amount === '') {
		return '';
	}
	if (is_string($amount) && preg_match('/[\$€£]/u', $amount)) {
		return trim($amount);
	}
	$n = (float) preg_replace('/[^0-9.]/', '', (string) $amount);
	if ($n <= 0) {
		return '';
	}
	if ($n >= 1000000000) {
		$v = round($n / 1000000000, 1);
		return $symbol . rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.') . 'B';
	}
	if ($n >= 1000000) {
		return $symbol . round($n / 1000000) . 'M';
	}
	if ($n >= 1000) {
		return $symbol . round($n / 1000) . 'K';
	}
	return $symbol . number_format($n);
}

function lottery_sync_draw_hint_from_lrf(array $lottery)
{
	$draw_days = $lottery['draw_days'] ?? array();
	if (!is_array($draw_days) || empty($draw_days)) {
		return '';
	}
	$tz_name = (string) ($lottery['draw_times']['timezone'] ?? 'UTC');
	try {
		$tz = new DateTimeZone($tz_name);
	} catch (Exception $e) {
		$tz = new DateTimeZone('UTC');
	}
	$time = (string) ($lottery['draw_times']['default'] ?? '20:00');
	$now = new DateTime('now', $tz);
	$day_map = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	for ($i = 0; $i < 14; $i++) {
		$candidate = clone $now;
		if ($i > 0) {
			$candidate->modify('+' . $i . ' day');
		}
		$day_name = $day_map[(int) $candidate->format('w')];
		if (empty($draw_days[$day_name]) || (string) $draw_days[$day_name] === '0') {
			continue;
		}
		$parts = explode(':', $time);
		$candidate->setTime((int) ($parts[0] ?? 20), (int) ($parts[1] ?? 0), 0);
		if ($candidate <= $now) {
			continue;
		}
		return lottery_sync_draw_hint_from_datetime($candidate->format('c'));
	}
	return '';
}

function lottery_sync_draw_hint_from_datetime($iso)
{
	$ts = lottery_sync_date_ts($iso);
	if ($ts <= 0) {
		return '';
	}
	$diff = $ts - time();
	if ($diff < 3600) {
		return 'Today';
	}
	if ($diff < 86400) {
		return 'Tomorrow';
	}
	$days = (int) ceil($diff / 86400);
	return 'In ' . $days . ' day' . ($days === 1 ? '' : 's');
}

function lottery_sync_time_ago($date)
{
	$ts = lottery_sync_date_ts($date);
	if ($ts <= 0) {
		return '';
	}
	$diff = time() - $ts;
	if ($diff < 60) {
		return 'Just now';
	}
	if ($diff < 3600) {
		$m = (int) floor($diff / 60);
		return $m . ' minute' . ($m === 1 ? '' : 's') . ' ago';
	}
	if ($diff < 86400) {
		$h = (int) floor($diff / 3600);
		return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
	}
	if ($diff < 172800) {
		return 'Yesterday';
	}
	$d = (int) floor($diff / 86400);
	return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
}

function lottery_sync_format_display_date($date)
{
	$ts = lottery_sync_date_ts($date);
	if ($ts <= 0) {
		return (string) $date;
	}
	return date('d/m/Y', $ts);
}

function lottery_sync_normalize_date($date)
{
	$ts = lottery_sync_date_ts($date);
	if ($ts <= 0) {
		return (string) $date;
	}
	return date('Y-m-d', $ts);
}

function lottery_sync_date_ts($date)
{
	$date = trim((string) $date);
	if ($date === '') {
		return 0;
	}
	$ts = strtotime($date);
	return $ts !== false ? (int) $ts : 0;
}

/**
 * @param array<string, mixed> $row
 * @param array<int, string> $keys
 * @return array<int, int>
 */
function lottery_sync_extract_number_fields(array $row, array $keys)
{
	$nums = array();
	foreach ($keys as $k) {
		if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) {
			$nums[] = (int) $row[$k];
		}
	}
	return $nums;
}

/**
 * @return array<int, int>
 */
function lottery_sync_parse_number_set($text)
{
	$nums = array();
	if (preg_match_all('/\d+/', (string) $text, $m)) {
		foreach ($m[0] as $n) {
			$nums[] = (int) $n;
		}
	}
	return $nums;
}

/**
 * @param array<string, string> $headers
 * @param array{0: string, 1: string}|null $basic
 * @return array{ok: bool, code: int, body: string, json: mixed, error: string}
 */
function lottery_sync_http_get($url, array $headers = array(), $basic = null)
{
	$ch = curl_init($url);
	$hdrs = array('Accept: application/json');
	foreach ($headers as $k => $v) {
		if ($v !== '') {
			$hdrs[] = $k . ': ' . $v;
		}
	}
	$opts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPGET => true,
		CURLOPT_HTTPHEADER => $hdrs,
		CURLOPT_CONNECTTIMEOUT => 15,
		CURLOPT_TIMEOUT => 45,
		CURLOPT_NOSIGNAL => 1,
	);
	if (is_array($basic) && $basic[0] !== '' && $basic[1] !== '') {
		$opts[CURLOPT_USERPWD] = $basic[0] . ':' . $basic[1];
		$opts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
	}
	curl_setopt_array($ch, $opts);
	$body = curl_exec($ch);
	$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	curl_close($ch);
	$json = @json_decode((string) $body, true);
	return array(
		'ok' => ($err === '' && $code >= 200 && $code < 300),
		'code' => $code,
		'body' => (string) $body,
		'json' => $json,
		'error' => $err,
	);
}
