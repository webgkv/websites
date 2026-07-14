<?php

/**
 * Shared Quick Access block for Demo and Download pages (brand-agnostic helpers).
 */

function site_quick_access_lang_key($lang) {
	$u = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return $u === '' ? 'en' : $u;
}

function site_quick_access_esc($value) {
	return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_quick_access_brand_name() {
	return function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road';
}

function site_quick_access_defaults() {
	$brand = site_quick_access_brand_name();
	return array(
		'quick_access_eyebrow' => 'Quick Access',
		'quick_access_title' => 'Open the ' . $brand . ' demo in your browser or use the mobile options',
		'quick_access_open_demo' => 'Open Demo Page',
		'quick_access_google_play' => 'Google Play',
		'quick_access_app_store' => 'App Store',
		'quick_access_demo_note' => 'Need the APK route instead? Use the mobile options here.',
		'quick_access_download_lead' => 'Want the quickest way in? Open the demo in your browser, jump to the Android route, or use the iPhone guide for the Safari web app version.',
		'quick_access_download_note' => 'This is not a separate official App Store or Google Play app. We explain that below. For quick access, though, these are the most practical options.',
		'quick_access_demo_step1' => '1. Bet',
		'quick_access_demo_step2' => '2. Advance',
		'quick_access_demo_step3' => '3. Cash Out',
		'quick_access_demo_alt1' => $brand . ' demo bet and difficulty setup',
		'quick_access_demo_alt2' => $brand . ' demo round with rising multiplier',
		'quick_access_demo_alt3' => $brand . ' demo cash-out result',
	);
}

function site_quick_access_text($key) {
	$defaults = site_quick_access_defaults();
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

function site_quick_access_copy($context = 'demo') {
	$out = array(
		'eyebrow' => site_quick_access_text('quick_access_eyebrow'),
		'title' => site_quick_access_text('quick_access_title'),
		'open_demo' => site_quick_access_text('quick_access_open_demo'),
		'google_play' => site_quick_access_text('quick_access_google_play'),
		'app_store' => site_quick_access_text('quick_access_app_store'),
		'demo_note' => site_quick_access_text('quick_access_demo_note'),
		'download_lead' => site_quick_access_text('quick_access_download_lead'),
	);
	if ($context !== 'download') {
		$out['download_lead'] = '';
	}
	return $out;
}

function site_demo_preview_image_urls() {
	if (function_exists('site_brand_demo_preview_step_urls')) {
		$urls = site_brand_demo_preview_step_urls();
		if (count($urls) === 3) {
			return $urls;
		}
	}
	$paths = array(
		'/assets/images/chickenroad-step-1.webp',
		'/assets/images/chickenroad-step-2.webp',
		'/assets/images/chickenroad-step-3.webp',
	);
	$urls = array();
	foreach ($paths as $path) {
		$urls[] = function_exists('site_brand_asset_url') ? site_brand_asset_url($path) : $path;
	}
	return $urls;
}

function site_demo_preview_card_html($image_url, $alt, $label) {
	return '<article class="demo-preview-card">'
		. '<div class="demo-preview-card__image">'
		. '<img class="demo-preview-card__img" src="' . site_quick_access_esc($image_url) . '" alt="' . site_quick_access_esc($alt) . '" width="320" height="180" loading="lazy" decoding="async">'
		. '</div>'
		. '<p class="demo-preview-card__label">' . site_quick_access_esc($label) . '</p>'
		. '</article>';
}

function site_demo_preview_html($lang) {
	$b = array(
		'step1' => site_quick_access_text('quick_access_demo_step1'),
		'step2' => site_quick_access_text('quick_access_demo_step2'),
		'step3' => site_quick_access_text('quick_access_demo_step3'),
		'alt1' => site_quick_access_text('quick_access_demo_alt1'),
		'alt2' => site_quick_access_text('quick_access_demo_alt2'),
		'alt3' => site_quick_access_text('quick_access_demo_alt3'),
	);
	$imgs = site_demo_preview_image_urls();
	return '<noinc><section class="demo-preview-strip" dir="' . (site_quick_access_lang_key($lang) === 'ar' ? 'rtl' : 'ltr') . '">'
		. '<div class="demo-preview-strip__grid">'
		. site_demo_preview_card_html($imgs[0], $b['alt1'], $b['step1'])
		. site_demo_preview_card_html($imgs[1], $b['alt2'], $b['step2'])
		. site_demo_preview_card_html($imgs[2], $b['alt3'], $b['step3'])
		. '</div>'
		. '</section></noinc>';
}

function site_quick_access_page_url($slug, $lang) {
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
	$lu = site_quick_access_lang_key($lang);
	return '/' . $lu . '/' . $slug . '/';
}

function site_quick_access_demo_url($lang) {
	$lu = site_quick_access_lang_key($lang);
	return '/' . $lu . '/demo/app/';
}

function site_quick_access_download_url($lang) {
	return site_quick_access_page_url('download', $lang);
}

function site_quick_access_google_url($lang) {
	return rtrim(site_quick_access_download_url($lang), '/') . '/install-apk/';
}

function site_quick_access_app_store_url($abc, $lang) {
	if (function_exists('pwa_install_guide_url')) {
		$url = (string) pwa_install_guide_url($abc, $lang);
		if ($url !== '') {
			return $url;
		}
	}
	return rtrim(site_quick_access_download_url($lang), '/') . '/install-pwa/';
}

function site_quick_access_store_icon_url($which) {
	if ($which === 'google' && function_exists('site_brand_store_google_icon_path')) {
		$path = site_brand_store_google_icon_path();
	} elseif ($which === 'appstore' && function_exists('site_brand_store_appstore_icon_path')) {
		$path = site_brand_store_appstore_icon_path();
	} else {
		$path = $which === 'google'
			? '/assets/images/chickenroad-store-googleplay.svg'
			: '/assets/images/chickenroad-store-appstore.svg';
	}
	return function_exists('site_brand_asset_url') ? site_brand_asset_url($path) : $path;
}

function site_quick_access_html($abc, $lang, $context = 'demo') {
	$bundle = site_quick_access_copy($context);
	$demo_url = site_quick_access_demo_url($lang);
	$google_url = site_quick_access_google_url($lang);
	$app_store_url = site_quick_access_app_store_url($abc, $lang);
	$lead = $context === 'download' ? (string) $bundle['download_lead'] : '';
	$note = (string) $bundle['demo_note'];
	$dir = site_quick_access_lang_key($lang) === 'ar' ? 'rtl' : 'ltr';
	$google_icon = site_quick_access_store_icon_url('google');
	$appstore_icon = site_quick_access_store_icon_url('appstore');

	$html = '<noinc><aside class="demo-quick-access demo-quick-access--' . site_quick_access_esc($context) . '" dir="' . $dir . '">'
		. '<p class="demo-quick-access__eyebrow">' . site_quick_access_esc($bundle['eyebrow']) . '</p>'
		. '<h2 class="demo-quick-access__title">' . site_quick_access_esc($bundle['title']) . '</h2>';

	if ($lead !== '') {
		$html .= '<p class="demo-quick-access__lead">' . site_quick_access_esc($lead) . '</p>';
	}

	$html .= '<div class="demo-quick-access__actions">'
		. '<a class="demo-quick-access__primary" href="' . site_quick_access_esc($demo_url) . '">' . site_quick_access_esc($bundle['open_demo']) . '</a>'
		. '<div class="demo-quick-access__stores">'
		. '<a class="demo-quick-access__store" href="' . site_quick_access_esc($google_url) . '">'
		. '<span class="demo-quick-access__store-label">'
		. '<img class="demo-quick-access__store-icon demo-quick-access__store-icon--google" src="' . site_quick_access_esc($google_icon) . '" alt="" width="24" height="24" loading="lazy" decoding="async" aria-hidden="true">'
		. '<span class="demo-quick-access__store-text">' . site_quick_access_esc($bundle['google_play']) . '</span>'
		. '</span>'
		. '</a>'
		. '<a class="demo-quick-access__store" href="' . site_quick_access_esc($app_store_url) . '">'
		. '<span class="demo-quick-access__store-label">'
		. '<img class="demo-quick-access__store-icon demo-quick-access__store-icon--appstore" src="' . site_quick_access_esc($appstore_icon) . '" alt="" width="24" height="24" loading="lazy" decoding="async" aria-hidden="true">'
		. '<span class="demo-quick-access__store-text">' . site_quick_access_esc($bundle['app_store']) . '</span>'
		. '</span>'
		. '</a>'
		. '</div>'
		. '</div>'
		. '<p class="demo-quick-access__note">' . site_quick_access_esc($note) . '</p>'
		. '</aside></noinc>';

	return $html;
}

function site_quick_access_insert_after_paragraph(string $html, string $insert, int $paragraph_index): string {
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

function site_quick_access_strip_legacy_noinc_blocks(string $html, array $needles): string {
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

function site_demo_apply_quick_access(string $html, $abc, $lang): string {
	if ($html === '') {
		return $html;
	}
	$html = site_quick_access_strip_legacy_noinc_blocks(
		$html,
		array(
			'demo-quick-access',
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
	$block = site_quick_access_html($abc, $lang, 'demo') . "\n" . site_demo_preview_html($lang);
	return site_quick_access_insert_after_paragraph($html, $block, 3);
}

function site_download_apply_quick_access(string $html, $abc, $lang): string {
	if ($html === '') {
		return $html;
	}
	$html = site_quick_access_strip_legacy_noinc_blocks(
		$html,
		array(
			'demo-quick-access',
			'aviator-store-googleplay.svg',
			'aviator-store-appstore.svg',
		)
	);
	if (strpos($html, 'demo-quick-access') !== false) {
		return $html;
	}
	$block = site_quick_access_html($abc, $lang, 'download');
	$updated = preg_replace(
		'/(<div class="about_content page-content-lead">\s*<h1\b[^>]*>.*?<\/h1>\s*<\/div>)/ius',
		'$1' . "\n" . $block,
		$html,
		1,
		$count
	);
	if ((int) $count > 0 && is_string($updated)) {
		return $updated;
	}
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
