<?php
/**
 * Inject promo buttons into HTML after specific paragraph indices.
 * Used to place CTAs multiple times inside long-form content.
 */

function site_cta_buttons_html(string $offer_path): string {
	$offer_path = trim($offer_path);
	if ($offer_path === '') {
		return '';
	}

	$play_label = htmlspecialchars(i18n('common|cta_play_now'), ENT_QUOTES, 'UTF-8');
	$try_label = htmlspecialchars(i18n('common|cta_try_bonus'), ENT_QUOTES, 'UTF-8');
	return '<div class="blog-promo-btns mt-4">'
		. '<div class="main_btn"><a href="' . htmlspecialchars($offer_path) . '">' . $play_label . '</a></div> '
		. '<div class="main_btn"><a href="' . htmlspecialchars($offer_path) . '">' . $try_label . '</a></div>'
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
 * Insert the same CTA block at evenly spaced paragraphs (respects <noinc>).
 */
function site_insert_cta_evenly_in_content(string $html, string $buttons_html, int $cta_count = 3): string {
	$positions = site_cta_even_paragraph_positions(
		site_count_content_paragraphs($html),
		$cta_count
	);
	if (empty($positions)) {
		return $html;
	}
	return site_insert_cta_after_paragraphs($html, $buttons_html, $positions);
}
