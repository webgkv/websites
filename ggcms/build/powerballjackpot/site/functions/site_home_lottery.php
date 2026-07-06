<?php

function site_home_lottery_img($file)
{
	return '/assets/images/home/' . ltrim((string) $file, '/');
}

function site_home_lottery_icon($file)
{
	return site_home_lottery_img('icon/' . ltrim((string) $file, '/'));
}

function site_home_lottery_cta_url(array $abc)
{
	return !empty($abc['ad_offer_path']) ? (string) $abc['ad_offer_path'] : '#';
}

function site_home_lottery_arrow_icon()
{
	return '<i class="fas fa-angles-right ms-2" aria-hidden="true"></i>';
}

function site_home_lottery_i18n($key, $fallback = '')
{
	$val = i18n('common|' . $key);
	if ($val === '' || $val === 'common|' . $key) {
		return $fallback;
	}
	return $val;
}

function site_home_lottery_format($template, ...$args)
{
	$n = 1;
	foreach ($args as $arg) {
		$template = str_replace('%' . $n, (string) $arg, $template);
		$n++;
	}
	return $template;
}

/**
 * UI strings for home-lucky-picker.js and home-ticket-checker.js.
 *
 * @return array<string, string>
 */
function site_home_lottery_js_i18n_keys()
{
	return [
		'home_checker_aria_main',
		'home_checker_aria_bonus',
		'home_checker_err_all',
		'home_checker_err_invalid',
		'home_checker_err_range',
		'home_checker_err_unique',
		'home_checker_no_draw_loaded',
		'home_checker_latest_draw',
		'home_checker_latest_draw_no_date',
		'home_checker_no_draw_data',
		'home_checker_draw_on',
		'home_checker_no_matches',
		'home_checker_matched_main',
		'home_checker_matched_bonus',
		'home_checker_matched_end',
		'home_checker_jackpot_hint',
		'home_checker_all_main_hint',
		'home_checker_winning_label',
		'home_picker_stage_complete',
		'home_picker_main_set',
		'home_picker_selected_main',
		'home_picker_bonus_fallback',
		'home_ticket_price_lines_zero',
		'home_ticket_price_pending',
		'home_ticket_price_one',
		'home_ticket_price_lines',
	];
}

/**
 * @return array<string, string>
 */
function site_home_lottery_js_i18n_map()
{
	$map = [];
	foreach (site_home_lottery_js_i18n_keys() as $key) {
		$map[$key] = site_home_lottery_i18n($key, '');
	}
	return $map;
}

function site_home_lottery_js_i18n_json()
{
	return json_encode(
		site_home_lottery_js_i18n_map(),
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
}

function site_home_lottery_hero_h1_html()
{
	$prefix = site_home_lottery_i18n('hero_h1_prefix', 'Get');
	$accent1 = site_home_lottery_i18n('hero_h1_accent_1', 'Involved');
	$mid = site_home_lottery_i18n('hero_h1_mid', 'For a');
	$accent2 = site_home_lottery_i18n('hero_h1_accent_2', 'quick win');
	$tail = site_home_lottery_i18n('hero_h1_tail', 'the jackpots');

	return site_home_lottery_esc($prefix)
		. ' <span>' . site_home_lottery_esc($accent1) . '</span> '
		. site_home_lottery_esc($mid) . ' <span class="win">' . site_home_lottery_esc($accent2) . '</span> '
		. site_home_lottery_esc($tail);
}

function site_home_lottery_games_config_path()
{
	return ROOT_DIR . 'files/reference/home_lottery_games.json';
}

function site_home_lottery_live_cache_path()
{
	return ROOT_DIR . 'files/cache/home_lottery_live.json';
}

/**
 * Cached live jackpots / draws from lottery_sync_run().
 *
 * @return array<string, mixed>
 */
function site_home_lottery_live_load()
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}
	$cache = array();
	$path = site_home_lottery_live_cache_path();
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
 * @return array<string, mixed>
 */
function site_home_lottery_live_game($game_id)
{
	$live = site_home_lottery_live_load();
	$game_id = (string) $game_id;
	if (!empty($live['games'][$game_id]) && is_array($live['games'][$game_id])) {
		return $live['games'][$game_id];
	}
	return array();
}

/**
 * Load lottery games config from JSON. Future: merge override from variables.home_lottery_games.
 */
function site_home_lottery_games_load()
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	$defaults = [
		'lines_max' => 5,
		'currency' => 'USD',
		'currency_symbol' => '$',
	];
	$games = [];

	$path = site_home_lottery_games_config_path();
	if (is_readable($path)) {
		$raw = file_get_contents($path);
		$dec = json_decode((string) $raw, true);
		if (is_array($dec)) {
			if (!empty($dec['defaults']) && is_array($dec['defaults'])) {
				$defaults = array_merge($defaults, $dec['defaults']);
			}
			if (!empty($dec['games']) && is_array($dec['games'])) {
				$games = $dec['games'];
			}
		}
	}

	$cache = ['defaults' => $defaults, 'games' => $games];
	return $cache;
}

function site_home_lottery_enabled_games()
{
	$cfg = site_home_lottery_games_load();
	$out = [];
	foreach ($cfg['games'] as $game) {
		if (empty($game['enabled'])) {
			continue;
		}
		if (empty($game['id']) || empty($game['main'])) {
			continue;
		}
		$out[] = $game;
	}
	return $out;
}

function site_home_lottery_game_by_id($id)
{
	$id = (string) $id;
	foreach (site_home_lottery_enabled_games() as $game) {
		if ((string) $game['id'] === $id) {
			return $game;
		}
	}
	return null;
}

function site_home_lottery_games_json_for_js()
{
	$cfg = site_home_lottery_games_load();
	return json_encode(
		['defaults' => $cfg['defaults'], 'games' => site_home_lottery_enabled_games()],
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
}

function site_home_lottery_checker_json_for_js()
{
	$games = [];
	foreach (site_home_lottery_enabled_games() as $game) {
		$live = site_home_lottery_live_game((string) $game['id']);
		$latest = is_array($live['latest'] ?? null) ? $live['latest'] : [];
		$games[] = [
			'id' => (string) $game['id'],
			'name' => (string) $game['name'],
			'main' => $game['main'],
			'bonus' => !empty($game['bonus']) && is_array($game['bonus']) ? $game['bonus'] : [],
			'winning' => [
				'draw_date' => (string) ($latest['draw_date'] ?? ''),
				'nums' => array_values(array_map('intval', (array) ($latest['nums'] ?? []))),
			],
		];
	}
	return json_encode(
		['games' => $games],
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
}

/**
 * @param array<int, array<string, mixed>> $slides
 */
function site_home_lottery_checker_panel(array $slides)
{
	$first_id = !empty($slides[0]['id']) ? (string) $slides[0]['id'] : '';
	$html = '<div class="check-numbers pbj-ticket-checker" id="pbj-ticket-checker">';
	$html .= '<h6 class="mb-1">' . site_home_lottery_esc(site_home_lottery_i18n('home_checker_title', 'Check My Numbers')) . '</h6>';
	$html .= '<p class="mdtxt mb-3">' . site_home_lottery_esc(site_home_lottery_i18n('home_checker_lead', 'Compare your numbers with the latest published draw.')) . '</p>';
	$html .= '<div class="select-area mb-3">';
	$html .= '<select class="form-select" id="pbj-checker-lottery" aria-label="' . site_home_lottery_esc(site_home_lottery_i18n('home_select_lottery', 'Select lottery')) . '">';
	foreach ($slides as $i => $slide) {
		$sid = (string) $slide['id'];
		$html .= '<option value="' . site_home_lottery_esc($sid) . '"' . ($sid === $first_id ? ' selected' : '') . '>'
			. site_home_lottery_esc((string) $slide['name']) . '</option>';
	}
	$html .= '</select></div>';
	$html .= '<div class="lucky-number"><div class="tab-content"><ul class="justify-content-start pbj-checker-slots" id="pbj-checker-slots"></ul></div></div>';
	$html .= '<p class="pbj-checker-draw small text-muted mt-2 mb-0" id="pbj-checker-draw" aria-live="polite"></p>';
	$html .= '<div class="btn-area mt-4"><button type="button" class="cmn-btn" id="pbj-checker-submit">' . site_home_lottery_esc(site_home_lottery_i18n('home_checker_submit', 'Check Numbers')) . '</button></div>';
	$html .= '<div class="pbj-checker-result mt-3" id="pbj-checker-result" hidden></div>';
	$html .= '<script type="application/json" id="pbj-checker-config">' . site_home_lottery_checker_json_for_js() . '</script>';
	$html .= '</div>';
	return $html;
}

function site_home_lottery_slides()
{
	$slides = [];
	foreach (site_home_lottery_enabled_games() as $game) {
		$live = site_home_lottery_live_game((string) $game['id']);
		$prize = (string) ($game['prize_display'] ?? '');
		$days = (string) ($game['draw_hint'] ?? '');
		if (!empty($live['prize_display'])) {
			$prize = (string) $live['prize_display'];
		}
		if (!empty($live['draw_hint'])) {
			$days = (string) $live['draw_hint'];
		}
		$slides[] = [
			'id' => (string) $game['id'],
			'name' => (string) $game['name'],
			'icon' => (string) $game['icon'],
			'prize' => $prize,
			'days' => $days,
		];
	}
	return $slides;
}

function site_home_lottery_recent_results()
{
	$live = site_home_lottery_live_load();
	if (!empty($live['recent_results']) && is_array($live['recent_results'])) {
		return $live['recent_results'];
	}
	return site_home_lottery_recent_results_fallback();
}

function site_home_lottery_recent_results_fallback()
{
	return [
		['img' => 'ball-icon-1.png', 'winner' => 'PowerBall', 'ago' => '—', 'amount' => '—', 'date' => '—', 'nums' => [], 'active' => null],
	];
}

function site_home_lottery_counters()
{
	$games = site_home_lottery_enabled_games();
	$lottery_count = count($games);

	$recent_count = 0;
	foreach (site_home_lottery_recent_results() as $row) {
		if (!empty($row['nums']) && is_array($row['nums'])) {
			$recent_count++;
		}
	}

	$usd_sum = 0.0;
	foreach ($games as $game) {
		$symbol = (string) ($game['currency_symbol'] ?? '$');
		if ($symbol === '€') {
			continue;
		}
		$live = site_home_lottery_live_game((string) $game['id']);
		$amount = site_home_lottery_counter_jackpot_amount($live, $game);
		if ($amount > 0) {
			$usd_sum += $amount;
		}
	}

	$jackpot_value = '—';
	$jackpot_suffix = '';
	if ($usd_sum >= 1000000000) {
		$jackpot_value = '$' . rtrim(rtrim(number_format($usd_sum / 1000000000, 1, '.', ''), '0'), '.');
		$jackpot_suffix = 'B+';
	} elseif ($usd_sum >= 1000000) {
		$jackpot_value = '$' . (string) round($usd_sum / 1000000);
		$jackpot_suffix = 'M+';
	}

	return [
		['icon' => 'counter-icon-1.png', 'value' => (string) $lottery_count, 'suffix' => '', 'label' => site_home_lottery_i18n('home_counter_lotteries', 'Lotteries tracked'), 'even' => false],
		['icon' => 'counter-icon-2.png', 'value' => (string) $recent_count, 'suffix' => '', 'label' => site_home_lottery_i18n('home_counter_draws', 'Latest draws'), 'even' => true],
		['icon' => 'counter-icon-3.png', 'value' => $jackpot_value, 'suffix' => $jackpot_suffix, 'label' => site_home_lottery_i18n('home_counter_jackpots', 'Combined US jackpots'), 'even' => false],
		['icon' => 'counter-icon-4.png', 'value' => '24', 'suffix' => 'h', 'label' => site_home_lottery_i18n('home_counter_refresh', 'Data refresh'), 'even' => true],
	];
}

/**
 * @param array<string, mixed> $live
 * @param array<string, mixed> $game
 */
function site_home_lottery_counter_jackpot_amount(array $live, array $game)
{
	if (!empty($live['latest']['jackpot_raw']) && is_numeric($live['latest']['jackpot_raw'])) {
		return (float) $live['latest']['jackpot_raw'];
	}
	if (!empty($live['prize_display'])) {
		return site_home_lottery_parse_jackpot_amount($live['prize_display']);
	}
	if (!empty($game['prize_display'])) {
		return site_home_lottery_parse_jackpot_amount((string) $game['prize_display']);
	}
	return 0.0;
}

function site_home_lottery_parse_jackpot_amount($value)
{
	if ($value === null || $value === '') {
		return 0.0;
	}
	if (is_numeric($value)) {
		return (float) $value;
	}
	$s = trim((string) $value);
	if (preg_match('/([\d.]+)\s*B/i', $s, $m)) {
		return (float) $m[1] * 1000000000;
	}
	if (preg_match('/([\d.]+)\s*M/i', $s, $m)) {
		return (float) $m[1] * 1000000;
	}
	if (preg_match('/([\d.]+)\s*K/i', $s, $m)) {
		return (float) $m[1] * 1000;
	}
	$n = (float) preg_replace('/[^0-9.]/', '', $s);
	return $n > 0 ? $n : 0.0;
}

function site_home_lottery_how_steps()
{
	return [
		['num' => 1, 'side' => 'left', 'icon' => 'how-works-icon-1.png', 'title' => site_home_lottery_i18n('home_how_step1_title', 'Set A Budget'), 'text' => site_home_lottery_i18n('home_how_step1_text', '')],
		['num' => 2, 'side' => 'right', 'icon' => 'how-works-icon-2.png', 'title' => site_home_lottery_i18n('home_how_step2_title', 'Choose Your Lottery'), 'text' => site_home_lottery_i18n('home_how_step2_text', '')],
		['num' => 3, 'side' => 'left', 'icon' => 'how-works-icon-3.png', 'title' => site_home_lottery_i18n('home_how_step3_title', 'Pick Your Numbers'), 'text' => site_home_lottery_i18n('home_how_step3_text', '')],
		['num' => 4, 'side' => 'right', 'icon' => 'how-works-icon-4.png', 'title' => site_home_lottery_i18n('home_how_step4_title', 'Check Results'), 'text' => site_home_lottery_i18n('home_how_step4_text', '')],
	];
}

function site_home_lottery_why_items()
{
	return [
		['icon' => 'why-best-icon-1.png', 'title' => site_home_lottery_i18n('home_why_1_title', 'Live jackpots & results'), 'text' => site_home_lottery_i18n('home_why_1_text', '')],
		['icon' => 'why-best-icon-2.png', 'title' => site_home_lottery_i18n('home_why_2_title', 'Check your numbers'), 'text' => site_home_lottery_i18n('home_why_2_text', '')],
		['icon' => 'why-best-icon-3.png', 'title' => site_home_lottery_i18n('home_why_3_title', 'Five major games'), 'text' => site_home_lottery_i18n('home_why_3_text', '')],
		['icon' => 'why-best-icon-4.png', 'title' => site_home_lottery_i18n('home_why_4_title', 'Responsible play'), 'text' => site_home_lottery_i18n('home_why_4_text', '')],
	];
}

function site_home_lottery_number_list($from, $to, array $activeNums = [], $activeClass = 'numActive', $clickable = false)
{
	$html = '';
	$activeClass = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $activeClass);
	for ($i = (int) $from; $i <= (int) $to; $i++) {
		$attrs = $clickable ? ' data-num="' . $i . '"' : '';
		$cls = in_array($i, $activeNums, true) ? ' class="' . $activeClass . '"' : '';
		$html .= '<li' . $cls . $attrs . '><span>' . $i . '</span></li>';
	}
	return $html;
}

function site_home_lottery_picker_area(array $game, array $defaults = [])
{
	$main = $game['main'];
	$main_label = !empty($main['label'])
		? (string) $main['label']
		: site_home_lottery_format(site_home_lottery_i18n('home_picker_pick_main', 'Pick %1 Numbers'), (int) $main['count']);
	$plus_icon = site_home_lottery_esc(site_home_lottery_icon('plus.png'));
	$delete_icon = site_home_lottery_esc(site_home_lottery_icon('delete.png'));

	$lines_max = (int) ($game['lines_max'] ?? $defaults['lines_max'] ?? 5);
	$line_hint = site_home_lottery_i18n('home_picker_line_hint_before', 'Ticket')
		. ' <span class="pbj-line-hint-num">01</span> '
		. site_home_lottery_format(
			site_home_lottery_i18n('home_picker_line_hint_after', 'of %1 — tap a row on the right to edit another line'),
			sprintf('%02d', $lines_max)
		);
	$html = '<div class="picks-number-area pbj-lucky-picker" data-pbj-game="' . site_home_lottery_esc($game['id']) . '">'
		. '<p class="pbj-line-hint ndtxt mb-2">' . $line_hint . '</p>'
		. '<div class="top-area">'
		. '<h5 class="pbj-main-label">' . site_home_lottery_esc($main_label) . '</h5>'
		. '<div class="btn-area d-flex flex-wrap mt-3 align-items-center justify-content-center gap-2 gap-md-4">'
		. '<button type="button" class="cmn-btn pbj-quick-pick">'
		. '<img src="' . $plus_icon . '" alt="">' . site_home_lottery_esc(site_home_lottery_i18n('home_picker_quick_pick', 'Quick pick')) . '</button>'
		. '<button type="button" class="cmn-btn alt remove pbj-clear-all">'
		. '<img src="' . $delete_icon . '" alt="">' . site_home_lottery_esc(site_home_lottery_i18n('home_picker_clear_all', 'Clear all')) . '</button>'
		. '</div></div>'
		. '<div class="pick-number"><ul class="pbj-main-numbers">'
		. site_home_lottery_number_list((int) $main['min'], (int) $main['max'], [], 'numActive', true)
		. '</ul></div>'
		. '<p class="pbj-stage-hint ndtxt mb-2 text-center" aria-live="polite"></p>';

	$bonus_pools = !empty($game['bonus']) && is_array($game['bonus']) ? $game['bonus'] : [];
	if ($bonus_pools) {
		foreach ($bonus_pools as $pool) {
			$pool_id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($pool['id'] ?? 'bonus'));
			$pool_label = !empty($pool['label']) ? (string) $pool['label'] : site_home_lottery_i18n('home_picker_pick_bonus', 'Pick bonus');
			$html .= '<div class="pick-power-ball text-center pbj-bonus-section" data-pbj-pool="' . site_home_lottery_esc($pool_id) . '">'
				. '<h5 class="pbj-bonus-label">' . site_home_lottery_esc($pool_label) . '</h5>'
				. '<ul class="pbj-bonus-numbers" data-pbj-pool="' . site_home_lottery_esc($pool_id) . '">'
				. site_home_lottery_number_list((int) $pool['min'], (int) $pool['max'], [], 'ballActive', true)
				. '</ul></div>';
		}
	}

	$html .= '</div>';
	return $html;
}

function site_home_lottery_result_balls(array $nums, $activeNum = null)
{
	$html = '';
	foreach ($nums as $n) {
		$cls = ($activeNum !== null && (int) $n === (int) $activeNum) ? ' class="numActive"' : '';
		$html .= '<li' . $cls . '><span>' . (int) $n . '</span></li>';
	}
	return $html;
}

function site_home_lottery_esc($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_home_lottery_ticket_slot_html($placeholder = '--', $is_bonus = false, $slot_key = '')
{
	$cls = $is_bonus ? 'ballActive pbj-bonus-slot' : 'numActive';
	$data = $slot_key !== '' ? ' data-pbj-slot="' . site_home_lottery_esc($slot_key) . '"' : '';
	return '<li class="' . $cls . ' pbj-ticket-slot"' . $data . '><span>' . site_home_lottery_esc($placeholder) . '</span></li>';
}

function site_home_lottery_ticket_panel($cta_url, $arrow_html, array $game, array $defaults = [])
{
	$lines_max = (int) ($game['lines_max'] ?? $defaults['lines_max'] ?? 5);
	$main_count = (int) ($game['main']['count'] ?? 5);
	$bonus_count = 0;
	if (!empty($game['bonus']) && is_array($game['bonus'])) {
		foreach ($game['bonus'] as $pool) {
			$bonus_count += (int) ($pool['count'] ?? 0);
		}
	}

	$price = (float) ($game['price'] ?? 2);
	$symbol = (string) ($game['currency_symbol'] ?? $defaults['currency_symbol'] ?? '$');
	$game_id = site_home_lottery_esc($game['id']);

	$ticket_rows = '';
	for ($n = 1; $n <= $lines_max; $n++) {
		$disabled = $n > 1 ? ' disable-items' : '';
		$slots = '<li class="head-area"><span>' . sprintf('%02d', $n) . '</span></li>';
		for ($m = 0; $m < $main_count; $m++) {
			$slots .= site_home_lottery_ticket_slot_html('--', false, 'main-' . $m);
		}
		$bonus_idx = 0;
		if (!empty($game['bonus']) && is_array($game['bonus'])) {
			foreach ($game['bonus'] as $pool) {
				$pool_slots = (int) ($pool['count'] ?? 0);
				for ($b = 0; $b < $pool_slots; $b++) {
					$slots .= site_home_lottery_ticket_slot_html('--', true, 'bonus-' . $bonus_idx);
					$bonus_idx++;
				}
			}
		}
		$line_title = site_home_lottery_format(site_home_lottery_i18n('home_ticket_line_title', 'Ticket line %1'), sprintf('%02d', $n));
		$ticket_rows .= '<ul class="output-box pbj-ticket-line' . $disabled . '" data-pbj-line="' . ($n - 1) . '" title="' . site_home_lottery_esc($line_title) . '">' . $slots . '</ul>';
	}

	$payments = ['paypal.png', 'discover.png', 'visa.png', 'payoneer.png', 'mastercard.png', 'payfast.png'];
	$pay_html = '';
	foreach ($payments as $img) {
		$pay_html .= '<li><img src="' . site_home_lottery_esc(site_home_lottery_img($img)) . '" alt=""></li>';
	}

	return '<div class="selected-number pbj-ticket-panel" data-pbj-game="' . $game_id . '" data-pbj-price="' . site_home_lottery_esc(number_format($price, 2, '.', '')) . '" data-pbj-currency="' . site_home_lottery_esc($symbol) . '">'
		. '<form action="#">'
		. '<div class="select-number-area">'
		. '<h5>' . site_home_lottery_esc(site_home_lottery_i18n('home_ticket_select_number', 'Select Number')) . '</h5>'
		. $ticket_rows
		. '</div>'
		. '<div class="ticket-price mt-4 mb-4 d-flex justify-content-between align-items-center">'
		. '<div class="left-area"><p class="lgtxt">' . site_home_lottery_esc(site_home_lottery_i18n('home_ticket_price', 'Ticket Price')) . '</p><span class="mdtxt pbj-price-detail"></span></div>'
		. '<div class="right-area"><p class="lgtxt pbj-price-total">' . site_home_lottery_esc($symbol) . '0.00</p></div>'
		. '</div>'
		. '<div class="btn-area mb-4"><a href="' . site_home_lottery_esc($cta_url) . '" class="cmn-btn alt pbj-buy-ticket" data-pbj-base-url="' . site_home_lottery_esc($cta_url) . '">' . site_home_lottery_esc(site_home_lottery_i18n('home_ticket_buy', 'Buy Ticket')) . $arrow_html . '</a></div>'
		. '</form>'
		. '<div class="payment-methods"><p class="lgtxt mb-3">' . site_home_lottery_esc(site_home_lottery_i18n('home_payment_methods', 'Payment methods we accept :')) . '</p><ul>' . $pay_html . '</ul></div>'
		. '</div>';
}
