<?php

require_once ROOT_DIR . 'functions/site_home_lottery.php';

function site_lottery_sim_i18n($key, $fallback = '')
{
	return site_home_lottery_i18n($key, $fallback);
}

/**
 * Keys passed to lottery-sim-ui.js (includes reused home picker/checker strings).
 *
 * @return array<int, string>
 */
function site_lottery_sim_js_i18n_keys()
{
	$sim = [
		'sim_disclaimer',
		'sim_your_tickets',
		'sim_quick_pick_all',
		'sim_latest_draw',
		'sim_draw_waiting',
		'sim_draw_label',
		'sim_play',
		'sim_stop',
		'sim_turbo',
		'sim_reset',
		'sim_game_stats',
		'sim_money_stats',
		'sim_stat_played',
		'sim_stat_won',
		'sim_stat_lost',
		'sim_stat_spent',
		'sim_stat_won_money',
		'sim_stat_lost_money',
		'sim_prize_payouts',
		'sim_last_games',
		'sim_col_game',
		'sim_col_drawing',
		'sim_col_result',
		'sim_real_draw_title',
		'sim_real_draw_none',
		'sim_time_tracker',
		'sim_time_years',
		'sim_time_months',
		'sim_time_weeks',
		'sim_jackpot_banner',
		'sim_jackpot_after',
		'sim_no_tickets',
		'sim_tier_jackpot',
		'sim_tier_main_bonus',
		'sim_tier_main_only',
		'sim_tier_bonus_only',
		'sim_result_none',
		'sim_result_win',
		'sim_try_simulator',
		'sim_select_lottery',
		'sim_tickets_count',
		'sim_col_match',
		'sim_col_prize',
		'sim_col_hits',
	];
	return array_merge(site_home_lottery_js_i18n_keys(), $sim);
}

/**
 * @return array<string, string>
 */
function site_lottery_sim_js_i18n_map()
{
	$map = [];
	foreach (site_lottery_sim_js_i18n_keys() as $key) {
		$map[$key] = site_lottery_sim_i18n($key, '');
	}
	return $map;
}

function site_lottery_sim_js_i18n_json()
{
	return json_encode(
		site_lottery_sim_js_i18n_map(),
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
}

/**
 * Full simulator config: games rules, payouts, live latest draws.
 */
function site_lottery_sim_config_json()
{
	$cfg = site_home_lottery_games_load();
	$games = [];
	foreach (site_home_lottery_enabled_games() as $game) {
		$live = site_home_lottery_live_game((string) $game['id']);
		$latest = is_array($live['latest'] ?? null) ? $live['latest'] : [];
		$jackpot_sim = (int) ($game['jackpot_sim'] ?? 0);
		if (!empty($live['prize_display']) && $jackpot_sim <= 0) {
			$jackpot_sim = site_lottery_sim_parse_jackpot_amount((string) $live['prize_display']);
		}
		$games[] = [
			'id' => (string) $game['id'],
			'name' => (string) $game['name'],
			'icon' => (string) ($game['icon'] ?? ''),
			'price' => (float) ($game['price'] ?? 2),
			'currency_symbol' => (string) ($game['currency_symbol'] ?? $cfg['defaults']['currency_symbol'] ?? '$'),
			'draws_per_week' => (int) ($game['draws_per_week'] ?? 2),
			'jackpot_sim' => $jackpot_sim,
			'prize_display' => (string) ($live['prize_display'] ?? $game['prize_display'] ?? ''),
			'main' => $game['main'],
			'bonus' => !empty($game['bonus']) && is_array($game['bonus']) ? $game['bonus'] : [],
			'payouts' => !empty($game['payouts']) && is_array($game['payouts']) ? $game['payouts'] : [],
			'lines_max' => (int) ($game['lines_max'] ?? $cfg['defaults']['lines_max'] ?? 5),
			'latest' => [
				'draw_date' => (string) ($latest['draw_date'] ?? ''),
				'nums' => array_values(array_map('intval', (array) ($latest['nums'] ?? []))),
			],
		];
	}
	$payload = [
		'defaults' => $cfg['defaults'],
		'games' => $games,
	];
	return json_encode(
		$payload,
		JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
}

function site_lottery_sim_parse_jackpot_amount($display)
{
	$s = trim((string) $display);
	if ($s === '') {
		return 0;
	}
	if (preg_match('/([\d.]+)\s*([MB])/i', $s, $m)) {
		$n = (float) $m[1];
		$u = strtoupper($m[2]);
		if ($u === 'B') {
			return (int) round($n * 1000000000);
		}
		return (int) round($n * 1000000);
	}
	return 0;
}

function site_lottery_sim_demo_url($abc = null)
{
	global $lang;
	if (function_exists('site_quick_access_demo_url') && isset($lang)) {
		return site_quick_access_demo_url($lang);
	}
	$lu = '';
	if (is_array($abc) && isset($abc['lang']['url'])) {
		$lu = trim((string) $abc['lang']['url'], '/');
	}
	return ($lu !== '') ? '/' . $lu . '/demo/app/' : '/demo/app/';
}

/**
 * Ticket lines panel for simulator (no buy / payment).
 */
function site_lottery_sim_ticket_panel(array $game, array $defaults = [])
{
	$lines_max = (int) ($game['lines_max'] ?? $defaults['lines_max'] ?? 5);
	$main_count = (int) ($game['main']['count'] ?? 5);
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

	return '<div class="selected-number pbj-ticket-panel pbj-sim-ticket-panel" data-pbj-game="' . $game_id . '" data-pbj-price="' . site_home_lottery_esc(number_format($price, 2, '.', '')) . '" data-pbj-currency="' . site_home_lottery_esc($symbol) . '">'
		. '<div class="select-number-area">'
		. '<h5>' . site_home_lottery_esc(site_lottery_sim_i18n('sim_your_tickets', 'Your tickets')) . '</h5>'
		. $ticket_rows
		. '</div>'
		. '<div class="ticket-price mt-3 mb-3 d-flex justify-content-between align-items-center">'
		. '<div class="left-area"><p class="lgtxt">' . site_home_lottery_esc(site_home_lottery_i18n('home_ticket_price', 'Ticket Price')) . '</p><span class="mdtxt pbj-price-detail"></span></div>'
		. '<div class="right-area"><p class="lgtxt pbj-price-total">' . site_home_lottery_esc($symbol) . '0.00</p></div>'
		. '</div>'
		. '<div class="btn-area d-flex flex-wrap gap-2">'
		. '<button type="button" class="cmn-btn pbj-sim-quick-all">' . site_home_lottery_esc(site_lottery_sim_i18n('sim_quick_pick_all', 'Quick Pick All')) . '</button>'
		. '</div>'
		. '</div>';
}

function site_lottery_sim_render(array $slides, array $games, array $defaults)
{
	$first_id = !empty($slides[0]['id']) ? (string) $slides[0]['id'] : '';
	ob_start();
	?>
<div class="pbj-lottery-sim dark-ui" id="pbj-lottery-sim">
	<script type="application/json" id="pbj-sim-i18n"><?= site_lottery_sim_js_i18n_json() ?></script>
	<script type="application/json" id="pbj-sim-config"><?= site_lottery_sim_config_json() ?></script>
	<script type="application/json" id="pbj-lucky-config"><?= site_home_lottery_games_json_for_js() ?></script>

	<p class="pbj-sim-disclaimer" role="note"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_disclaimer', 'Simulation only. No real money is involved and you cannot win actual prizes.')) ?></p>

	<div class="pbj-sim-toolbar">
		<select class="form-select pbj-sim-game-select" id="pbj-sim-game-select" aria-label="<?= site_home_lottery_esc(site_lottery_sim_i18n('sim_select_lottery', 'Select lottery')) ?>">
<?php foreach ($slides as $i => $slide): ?>
			<option value="<?= site_home_lottery_esc($slide['id']) ?>"<?= $i === 0 ? ' selected' : '' ?>><?= site_home_lottery_esc($slide['name']) ?></option>
<?php endforeach; ?>
		</select>
		<div class="pbj-sim-real-draw" id="pbj-sim-real-draw" aria-live="polite"></div>
	</div>

	<div class="pbj-sim-dashboard row g-3">
		<div class="col-xl-5">
			<div class="lucky-number pbj-sim-picker-wrap">
				<div class="tab-content">
<?php foreach ($games as $i => $game): ?>
					<div class="tab-pane pbj-lucky-tab<?= $i === 0 ? ' show active' : '' ?>" id="pbj-sim-<?= site_home_lottery_esc($game['id']) ?>" role="tabpanel" data-pbj-game="<?= site_home_lottery_esc($game['id']) ?>"<?= $i > 0 ? ' hidden' : '' ?>>
						<div class="row g-3">
							<div class="col-md-6">
								<?= site_home_lottery_picker_area($game, $defaults) ?>
							</div>
							<div class="col-md-6">
								<?= site_lottery_sim_ticket_panel($game, $defaults) ?>
							</div>
						</div>
					</div>
<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="col-xl-4">
			<div class="pbj-sim-draw-card">
				<h6 class="pbj-sim-draw-title"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_latest_draw', 'Latest drawing')) ?> <span id="pbj-sim-draw-id">#0</span></h6>
				<ul class="pbj-sim-draw-balls" id="pbj-sim-draw-balls" aria-live="polite"></ul>
				<p class="pbj-sim-draw-result" id="pbj-sim-draw-result"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_draw_waiting', 'Waiting...')) ?></p>
				<div class="pbj-sim-controls btn-area d-flex flex-wrap gap-2">
					<button type="button" class="cmn-btn" id="pbj-sim-play"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_play', 'Play')) ?></button>
					<button type="button" class="cmn-btn alt" id="pbj-sim-turbo"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_turbo', 'Turbo')) ?></button>
					<button type="button" class="cmn-btn alt remove" id="pbj-sim-reset"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_reset', 'Reset')) ?></button>
				</div>
				<div class="pbj-sim-jackpot" id="pbj-sim-jackpot" hidden>
					<p class="pbj-sim-jackpot-text" id="pbj-sim-jackpot-text"></p>
					<p class="pbj-sim-jackpot-stats" id="pbj-sim-jackpot-stats"></p>
				</div>
			</div>
		</div>
		<div class="col-xl-3">
			<div class="pbj-sim-stats-card">
				<h6><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_game_stats', 'Game stats')) ?></h6>
				<ul class="pbj-sim-stats-list">
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_played', 'Played')) ?></span><strong id="pbj-sim-played">0</strong></li>
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_won', 'Won')) ?></span><strong id="pbj-sim-won">0</strong></li>
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_lost', 'Lost')) ?></span><strong id="pbj-sim-lost">0</strong></li>
				</ul>
				<h6 class="mt-3"><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_money_stats', 'Money stats')) ?></h6>
				<ul class="pbj-sim-stats-list">
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_spent', 'Played')) ?></span><strong id="pbj-sim-spent">$0</strong></li>
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_won_money', 'Won')) ?></span><strong id="pbj-sim-won-money">$0</strong></li>
					<li><span><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_stat_lost_money', 'Lost')) ?></span><strong id="pbj-sim-lost-money">$0</strong></li>
				</ul>
				<p class="pbj-sim-time small" id="pbj-sim-time"></p>
			</div>
		</div>
	</div>

	<div class="row g-3 pbj-sim-bottom">
		<div class="col-lg-6">
			<div class="pbj-sim-table-card">
				<h6><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_prize_payouts', 'Prize payouts')) ?></h6>
				<div class="table-responsive">
					<table class="table pbj-sim-payout-table">
						<thead>
							<tr>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_match', 'Match')) ?></th>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_prize', 'Prize')) ?></th>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_hits', 'Hits')) ?></th>
							</tr>
						</thead>
						<tbody id="pbj-sim-payout-body"></tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-6">
			<div class="pbj-sim-table-card">
				<h6><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_last_games', 'Last 7 games')) ?></h6>
				<div class="table-responsive">
					<table class="table pbj-sim-history-table">
						<thead>
							<tr>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_game', 'Game')) ?></th>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_drawing', 'Drawing')) ?></th>
								<th><?= site_home_lottery_esc(site_lottery_sim_i18n('sim_col_result', 'Result')) ?></th>
							</tr>
						</thead>
						<tbody id="pbj-sim-history-body"></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php
	return (string) ob_get_clean();
}
