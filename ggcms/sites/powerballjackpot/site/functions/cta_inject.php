<?php
/**
 * Inject promo buttons into HTML after specific paragraph indices.
 * Used to place CTAs multiple times inside long-form content.
 */

function aviator_cta_buttons_html(string $offer_path): string {
	$offer_path = trim($offer_path);
	if ($offer_path === '') return '';

	// Both CTAs go to the same advertising offer redirect.
	$play_label = htmlspecialchars(i18n('common|cta_play_aviator_now'), ENT_QUOTES, 'UTF-8');
	$try_label = htmlspecialchars(i18n('common|cta_try_bonus'), ENT_QUOTES, 'UTF-8');
	return '<div class="blog-promo-btns btn-area mt-4">'
		. '<a href="' . htmlspecialchars($offer_path) . '" class="cmn-btn">' . $play_label . '</a> '
		. '<a href="' . htmlspecialchars($offer_path) . '" class="cmn-btn alt">' . $try_label . '</a>'
		. '</div>'
		. '<br>';
}

/**
 * Inserts $buttons_html after N-th <p> blocks (1-based).
 * If the page has fewer paragraphs than N, insertion simply won't happen.
 * Content wrapped in a closed <noinc>...</noinc> pair is protected from CTA injection.
 * Unclosed <noinc> is ignored (no protection; tag stays in output).
 */
function aviator_cta_extract_noinc_blocks(string $html): array {
	require_once ROOT_DIR . 'functions/content_exclude_tags.php';
	return content_exclude_extract_blocks($html, array('noinc'));
}

function aviator_cta_restore_noinc_blocks(string $html, array $protected): string {
	require_once ROOT_DIR . 'functions/content_exclude_tags.php';
	return content_exclude_restore_blocks($html, $protected);
}

function aviator_insert_cta_after_paragraphs(string $html, string $buttons_html, array $paragraph_positions): string {
	$html = (string)$html;
	$buttons_html = (string)$buttons_html;
	if ($html === '' || $buttons_html === '') return $html;

	list($html_masked, $protected) = aviator_cta_extract_noinc_blocks($html);

	if (!preg_match_all('/<p\\b[^>]*>.*?<\\/p>/ius', $html_masked, $matches, PREG_OFFSET_CAPTURE)) {
		return aviator_cta_restore_noinc_blocks($html_masked, $protected);
	}

	$positions_set = [];
	foreach ($paragraph_positions as $pos) {
		$pos = (int)$pos;
		if ($pos > 0) $positions_set[$pos] = true;
	}
	if (empty($positions_set)) {
		return aviator_cta_restore_noinc_blocks($html_masked, $protected);
	}

	$cnt = 0;
	$out = '';
	$last = 0;

	foreach ($matches[0] as $m) {
		$cnt++;
		$start = (int)$m[1];
		$p_html = (string)$m[0];

		$out .= substr($html_masked, $last, $start - $last);
		$out .= $p_html;

		if (isset($positions_set[$cnt])) {
			$out .= $buttons_html;
		}

		$last = $start + strlen($p_html);
	}

	$out .= substr($html_masked, $last);
	return aviator_cta_restore_noinc_blocks($out, $protected);
}

