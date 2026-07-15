<?php
/**
 * Inject promo buttons into HTML after specific paragraph indices.
 * Slot ids are page-scoped and numbered top-to-bottom (gu_pn_01, gu_tb_01, gu_pn_02…).
 */

function site_cta_content_plain_length(string $html): int {
	$text = strip_tags($html);
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = preg_replace('/\s+/u', ' ', trim($text));
	if ($text === '') {
		return 0;
	}
	return (int) mb_strlen($text, 'UTF-8');
}

/**
 * How many CTA blocks to inject based on visible text length + paragraph count.
 *
 * Thresholds (plain text after strip_tags):
 *   < 800 chars or < 3 paragraphs → 0
 *   < 1500 chars or < 6 paragraphs → 1
 *   < 4500 chars or < 12 paragraphs → 2
 *   otherwise → 3
 */
function site_cta_resolve_injection_count(string $html): int {
	$len = site_cta_content_plain_length($html);
	$paras = site_count_content_paragraphs($html);
	if ($paras < 3 || $len < 800) {
		return 0;
	}
	if ($len < 1500 || $paras < 6) {
		return 1;
	}
	if ($len < 4500 || $paras < 12) {
		return 2;
	}
	return 3;
}

function site_cta_buttons_html(string $offer_path, string $page_key = '', int $instance = 1, string $variant = 'text'): string {
	$offer_path = trim($offer_path);
	if ($offer_path === '') {
		return '';
	}

	if (!function_exists('site_cta_make_slot')) {
		require_once ROOT_DIR . 'functions/site_cta_analytics.php';
	}
	global $abc;
	if ($page_key === '' && isset($abc) && is_array($abc)) {
		$page_key = site_cta_resolve_page_key($abc);
	}
	if ($page_key === '') {
		$page_key = 'page';
	}

	$slot_play = site_cta_make_slot($page_key, 'play_now', $instance);
	$slot_bonus = site_cta_make_slot($page_key, 'bonus', $instance);
	$href_play = site_cta_offer_href($offer_path, $page_key, $slot_play, 'play_now');
	$href_bonus = site_cta_offer_href($offer_path, $page_key, $slot_bonus, 'bonus');

	$play_label = htmlspecialchars(i18n('common|cta_play_now'), ENT_QUOTES, 'UTF-8');
	$try_label = htmlspecialchars(i18n('common|cta_try_bonus'), ENT_QUOTES, 'UTF-8');
	return '<div class="blog-promo-btns mt-4">'
		. '<div class="main_btn"><a href="' . htmlspecialchars($href_play, ENT_QUOTES, 'UTF-8') . '"'
		. site_cta_data_attrs($slot_play, 'play_now', $variant) . '>' . $play_label . '</a></div> '
		. '<div class="main_btn"><a href="' . htmlspecialchars($href_bonus, ENT_QUOTES, 'UTF-8') . '"'
		. site_cta_data_attrs($slot_bonus, 'bonus', $variant) . '>' . $try_label . '</a></div>'
		. '</div>'
		. '<br>';
}

/**
 * Content wrapped in a closed <noinc>...</noinc> pair is protected from CTA injection.
 */
function site_cta_extract_noinc_blocks(string $html): array {
	require_once ROOT_DIR . 'functions/content_exclude_tags.php';
	return content_exclude_extract_blocks($html, array('noinc'));
}

function site_cta_restore_noinc_blocks(string $html, array $protected): string {
	require_once ROOT_DIR . 'functions/content_exclude_tags.php';
	return content_exclude_restore_blocks($html, $protected);
}

function site_insert_cta_after_paragraphs(string $html, string $buttons_html, array $paragraph_positions): string {
	$html = (string) $html;
	$buttons_html = (string) $buttons_html;
	if ($html === '' || $buttons_html === '') {
		return $html;
	}

	list($html_masked, $protected) = site_cta_extract_noinc_blocks($html);

	if (!preg_match_all('/<p\\b[^>]*>.*?<\\/p>/ius', $html_masked, $matches, PREG_OFFSET_CAPTURE)) {
		return site_cta_restore_noinc_blocks($html_masked, $protected);
	}

	$positions_set = array();
	foreach ($paragraph_positions as $pos) {
		$pos = (int) $pos;
		if ($pos > 0) {
			$positions_set[$pos] = true;
		}
	}
	if (empty($positions_set)) {
		return site_cta_restore_noinc_blocks($html_masked, $protected);
	}

	$cnt = 0;
	$out = '';
	$last = 0;

	foreach ($matches[0] as $m) {
		$cnt++;
		$start = (int) $m[1];
		$p_html = (string) $m[0];

		$out .= substr($html_masked, $last, $start - $last);
		$out .= $p_html;

		if (isset($positions_set[$cnt])) {
			$out .= $buttons_html;
		}

		$last = $start + strlen($p_html);
	}

	$out .= substr($html_masked, $last);
	return site_cta_restore_noinc_blocks($out, $protected);
}

function site_count_content_paragraphs(string $html): int {
	if (!preg_match_all('/<p\\b[^>]*>.*?<\\/p>/ius', (string) $html, $matches)) {
		return 0;
	}
	return count($matches[0]);
}

/**
 * Evenly spaced 1-based paragraph indices for long-form CTAs (~15% / 50% / 85%).
 *
 * @return int[]
 */
function site_cta_even_paragraph_positions(int $paragraph_count, int $cta_count = 3, int $min_gap = 4): array {
	if ($paragraph_count <= 0 || $cta_count <= 0) {
		return array();
	}
	if ($cta_count === 1) {
		$pos = max(1, min($paragraph_count - 1, (int) round($paragraph_count * 0.2)));
		return array($pos);
	}

	if ($paragraph_count <= 6) {
		$positions = array();
		for ($i = 1; $i <= $cta_count; $i++) {
			$positions[] = (int) max(1, min($paragraph_count - 1, round($i * $paragraph_count / ($cta_count + 1))));
		}
		return array_values(array_unique($positions));
	}

	$min_gap = max($min_gap, (int) floor($paragraph_count / ($cta_count + 3)));
	$start = max(2, (int) round($paragraph_count * 0.15));
	$end = min($paragraph_count - 1, max($start + $min_gap, (int) round($paragraph_count * 0.85)));

	$step = ($end - $start) / ($cta_count - 1);
	$positions = array();
	for ($i = 0; $i < $cta_count; $i++) {
		$positions[] = (int) round($start + $step * $i);
	}
	$positions = array_values(array_unique($positions));
	sort($positions);

	for ($i = 1; $i < count($positions); $i++) {
		if ($positions[$i] - $positions[$i - 1] < $min_gap) {
			$positions[$i] = min($paragraph_count - 1, $positions[$i - 1] + $min_gap);
		}
	}

	return $positions;
}

/**
 * Insert CTA pairs at evenly spaced paragraphs; each block gets a unique instance (01, 02…).
 *
 * @param int $cta_count -1 = auto from content length
 */
function site_insert_cta_evenly_in_content(string $html, string $offer_path, string $page_key = '', int $cta_count = -1, string $variant = 'text'): string {
	$html = (string) $html;
	$offer_path = trim($offer_path);
	if ($html === '' || $offer_path === '') {
		return $html;
	}

	if ($cta_count < 0) {
		$cta_count = site_cta_resolve_injection_count($html);
	}
	if ($cta_count <= 0) {
		return $html;
	}

	$positions = site_cta_even_paragraph_positions(
		site_count_content_paragraphs($html),
		$cta_count
	);
	if (empty($positions)) {
		return $html;
	}

	if (!function_exists('site_cta_resolve_page_key')) {
		require_once ROOT_DIR . 'functions/site_cta_analytics.php';
	}
	global $abc;
	if ($page_key === '' && isset($abc) && is_array($abc)) {
		$page_key = site_cta_resolve_page_key($abc);
	}
	if ($page_key === '') {
		$page_key = 'page';
	}

	foreach ($positions as $idx => $pos) {
		$instance = $idx + 1;
		$buttons_html = site_cta_buttons_html($offer_path, $page_key, $instance, $variant);
		$html = site_insert_cta_after_paragraphs($html, $buttons_html, array($pos));
	}

	return $html;
}
