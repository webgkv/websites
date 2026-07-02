<?php

/**
 * Shared Quick Access block for Demo and Download pages.
 */

function aviator_quick_access_lang_key($lang) {
	$u = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return $u === '' ? 'en' : $u;
}

function aviator_quick_access_esc($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function aviator_quick_access_defaults() {
	return array(
		'quick_access_eyebrow' => 'Quick Access',
		'quick_access_title' => 'Open the Aviator demo in your browser or use the mobile options',
		'quick_access_open_demo' => 'Open Demo Page',
		'quick_access_google_play' => 'Google Play',
		'quick_access_app_store' => 'App Store',
		'quick_access_demo_note' => 'Need the APK route instead? Use the mobile options here.',
		'quick_access_download_lead' => 'Want the quickest way in? Open the demo in your browser, jump to the Android route, or use the iPhone guide for the Safari web app version.',
		'quick_access_download_note' => 'This is not a separate official App Store or Google Play app. We explain that below. For quick access, though, these are the most practical options.',
		'quick_access_demo_step1' => '1. Bet',
		'quick_access_demo_step2' => '2. Watch',
		'quick_access_demo_step3' => '3. Cash Out',
		'quick_access_demo_alt1' => 'Aviator demo interface preview',
		'quick_access_demo_alt2' => 'Aviator multiplier round preview',
		'quick_access_demo_alt3' => 'Aviator cash-out result preview',
	);
}

function aviator_quick_access_text($key) {
	$defaults = aviator_quick_access_defaults();
	$fallback = isset($defaults[$key]) ? (string) $defaults[$key] : '';
	if (!function_exists('i18n')) {
		return $fallback;
	}
	$value = trim((string) i18n('common|' . $key));
	if ($value === '' || strpos($value, 'common|') === 0) {
		return $fallback;
	}
	return $value;
}

function aviator_quick_access_copy($context = 'demo') {
	$out = array(
		'eyebrow' => aviator_quick_access_text('quick_access_eyebrow'),
		'title' => aviator_quick_access_text('quick_access_title'),
		'open_demo' => aviator_quick_access_text('quick_access_open_demo'),
		'google_play' => aviator_quick_access_text('quick_access_google_play'),
		'app_store' => aviator_quick_access_text('quick_access_app_store'),
		'demo_note' => aviator_quick_access_text('quick_access_demo_note'),
		'download_lead' => aviator_quick_access_text('quick_access_download_lead'),
		'download_note' => aviator_quick_access_text('quick_access_download_note'),
	);
	if ($context !== 'download') {
		$out['download_lead'] = '';
		$out['download_note'] = '';
	}
	return $out;
}

function aviator_demo_preview_html($lang) {
	$b = array(
		'step1' => aviator_quick_access_text('quick_access_demo_step1'),
		'step2' => aviator_quick_access_text('quick_access_demo_step2'),
		'step3' => aviator_quick_access_text('quick_access_demo_step3'),
		'alt1' => aviator_quick_access_text('quick_access_demo_alt1'),
		'alt2' => aviator_quick_access_text('quick_access_demo_alt2'),
		'alt3' => aviator_quick_access_text('quick_access_demo_alt3'),
	);
	return '<noinc><section class="demo-preview-strip" dir="' . (aviator_quick_access_lang_key($lang) === 'ar' ? 'rtl' : 'ltr') . '">'
		. '<div class="demo-preview-strip__grid">'
		. '<article class="demo-preview-card"><div class="demo-preview-card__image"><img src="/assets/images/aviator-welcome-demo-1024x549.png" alt="' . aviator_quick_access_esc($b['alt1']) . '" loading="lazy" decoding="async"></div><p class="demo-preview-card__label">' . aviator_quick_access_esc($b['step1']) . '</p></article>'
		. '<article class="demo-preview-card"><div class="demo-preview-card__image"><img src="/assets/images/aviator-demo-multiplier-1024x580.png" alt="' . aviator_quick_access_esc($b['alt2']) . '" loading="lazy" decoding="async"></div><p class="demo-preview-card__label">' . aviator_quick_access_esc($b['step2']) . '</p></article>'
		. '<article class="demo-preview-card"><div class="demo-preview-card__image"><img src="/assets/images/aviator-demo-win-1024x638.png" alt="' . aviator_quick_access_esc($b['alt3']) . '" loading="lazy" decoding="async"></div><p class="demo-preview-card__label">' . aviator_quick_access_esc($b['step3']) . '</p></article>'
		. '</div>'
		. '</section></noinc>';
}

function aviator_quick_access_page_url($slug, $lang) {
	static $rows = array();
	$slug = trim((string) $slug);
	if ($slug === '') {
		return '/';
	}
	if (!isset($rows[$slug])) {
		$rows[$slug] = array();
		if (function_exists('mysql_select') && function_exists('mysql_res')) {
			$rows[$slug] = mysql_select(
				"SELECT * FROM pages WHERE display=1 AND module='pages' AND url='" . mysql_res($slug) . "' LIMIT 1",
				'row',
				0
			);
		}
	}
	if (!empty($rows[$slug]) && is_array($rows[$slug]) && function_exists('get_url')) {
		$url = rtrim((string) get_url('page', $rows[$slug]), '/');
		if ($url !== '') {
			return $url . '/';
		}
	}
	$lu = aviator_quick_access_lang_key($lang);
	return '/' . $lu . '/' . $slug . '/';
}

function aviator_quick_access_demo_url($lang) {
	$lu = aviator_quick_access_lang_key($lang);
	return '/' . $lu . '/demo/app/';
}

function aviator_quick_access_download_url($lang) {
	return aviator_quick_access_page_url('download', $lang);
}

function aviator_quick_access_google_url($lang) {
	// “Google Play” tile → localized Android install guide (APK), not Play Store.
	return rtrim(aviator_quick_access_download_url($lang), '/') . '/install-apk/';
}

function aviator_quick_access_app_store_url($abc, $lang) {
	if (function_exists('aviator_pwa_ios_guide_url')) {
		$url = (string) aviator_pwa_ios_guide_url($abc, $lang);
		if ($url !== '') {
			return $url;
		}
	}
	return rtrim(aviator_quick_access_download_url($lang), '/') . '/install-pwa/';
}

function aviator_quick_access_html($abc, $lang, $context = 'demo') {
	$bundle = aviator_quick_access_copy($context);
	$demo_url = aviator_quick_access_demo_url($lang);
	$google_url = aviator_quick_access_google_url($lang);
	$app_store_url = aviator_quick_access_app_store_url($abc, $lang);
	$lead = $context === 'download' ? (string) $bundle['download_lead'] : '';
	$note = $context === 'download' ? (string) $bundle['download_note'] : (string) $bundle['demo_note'];
	$dir = aviator_quick_access_lang_key($lang) === 'ar' ? 'rtl' : 'ltr';

	$html = '<noinc><aside class="demo-quick-access demo-quick-access--' . aviator_quick_access_esc($context) . '" dir="' . $dir . '">'
		. '<p class="demo-quick-access__eyebrow">' . aviator_quick_access_esc($bundle['eyebrow']) . '</p>'
		. '<h2 class="demo-quick-access__title">' . aviator_quick_access_esc($bundle['title']) . '</h2>';

	if ($lead !== '') {
		$html .= '<p class="demo-quick-access__lead">' . aviator_quick_access_esc($lead) . '</p>';
	}

	$html .= '<div class="demo-quick-access__actions">'
		. '<a class="demo-quick-access__primary" href="' . aviator_quick_access_esc($demo_url) . '">' . aviator_quick_access_esc($bundle['open_demo']) . '</a>'
		. '<div class="demo-quick-access__stores">'
		. '<a class="demo-quick-access__store" href="' . aviator_quick_access_esc($google_url) . '">'
		. '<img class="demo-quick-access__store-icon demo-quick-access__store-icon--google" src="/assets/images/aviator-store-googleplay.svg" alt="' . aviator_quick_access_esc($bundle['google_play']) . '" width="24" height="24" loading="lazy" decoding="async">'
		. '<span>' . aviator_quick_access_esc($bundle['google_play']) . '</span>'
		. '</a>'
		. '<a class="demo-quick-access__store" href="' . aviator_quick_access_esc($app_store_url) . '">'
		. '<img class="demo-quick-access__store-icon demo-quick-access__store-icon--appstore" src="/assets/images/aviator-store-appstore.svg" alt="' . aviator_quick_access_esc($bundle['app_store']) . '" width="24" height="24" loading="lazy" decoding="async">'
		. '<span>' . aviator_quick_access_esc($bundle['app_store']) . '</span>'
		. '</a>'
		. '</div>'
		. '</div>'
		. '<p class="demo-quick-access__note">' . aviator_quick_access_esc($note) . '</p>'
		. '</aside></noinc>';

	return $html;
}

function aviator_quick_access_insert_after_paragraph(string $html, string $insert, int $paragraph_index): string {
	if ($html === '' || $insert === '' || $paragraph_index < 1) {
		return $html;
	}
	if (!preg_match_all('/<p\b[^>]*>.*?<\/p>/ius', $html, $matches, PREG_OFFSET_CAPTURE)) {
		return $html . "\n" . $insert;
	}
	if (empty($matches[0][$paragraph_index - 1])) {
		return $html . "\n" . $insert;
	}
	$match = $matches[0][$paragraph_index - 1];
	$start = (int) $match[1];
	$chunk = (string) $match[0];
	$end = $start + strlen($chunk);
	return substr($html, 0, $end) . "\n" . $insert . "\n" . substr($html, $end);
}

function aviator_quick_access_strip_legacy_noinc_blocks(string $html, array $needles): string {
	if ($html === '' || empty($needles)) {
		return $html;
	}
	$updated = preg_replace_callback(
		'/(?:\s*<br\s*\/?>\s*)*\s*<noinc\b[^>]*>.*?<\/noinc>\s*(?:<br\s*\/?>\s*)*/ius',
		static function ($match) use ($needles) {
			$block = isset($match[0]) ? (string) $match[0] : '';
			foreach ($needles as $needle) {
				if ($needle !== '' && strpos($block, (string) $needle) !== false) {
					return "\n";
				}
			}
			return $block;
		},
		$html
	);
	return is_string($updated) ? $updated : $html;
}

function aviator_demo_apply_quick_access(string $html, $abc, $lang): string {
	if ($html === '') {
		return $html;
	}
	$html = aviator_quick_access_strip_legacy_noinc_blocks(
		$html,
		array(
			'aviator-store-googleplay.svg',
			'aviator-store-appstore.svg',
			'aviator-welcome-demo-1024x549.png',
			'aviator-demo-multiplier-1024x580.png',
			'aviator-demo-win-1024x638.png',
		)
	);
	if (strpos($html, 'demo-quick-access') !== false) {
		return $html;
	}
	$block = aviator_quick_access_html($abc, $lang, 'demo') . "\n" . aviator_demo_preview_html($lang);
	return aviator_quick_access_insert_after_paragraph($html, $block, 3);
}

function aviator_download_apply_quick_access(string $html, $abc, $lang): string {
	if ($html === '') {
		return $html;
	}
	$html = aviator_quick_access_strip_legacy_noinc_blocks(
		$html,
		array(
			'aviator-store-googleplay.svg',
			'aviator-store-appstore.svg',
		)
	);
	if (strpos($html, 'id="android-download"') === false) {
		$html = preg_replace('/<h3\b(?![^>]*\bid=)/i', '<h3 id="android-download"', $html, 1);
	}
	if (strpos($html, 'demo-quick-access') !== false) {
		return $html;
	}
	$block = aviator_quick_access_html($abc, $lang, 'download');
	$updated = preg_replace('/(<h1\b[^>]*>.*?<\/h1>)/ius', '$1' . "\n" . $block, $html, 1, $count);
	if ((int) $count > 0 && is_string($updated)) {
		return $updated;
	}
	$updated = preg_replace('/(<figure\b[^>]*>)/i', $block . "\n" . '$1', $html, 1, $count);
	if ((int) $count > 0 && is_string($updated)) {
		return $updated;
	}
	return $block . "\n" . $html;
}
