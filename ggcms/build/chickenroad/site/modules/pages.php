<?php

// 404 if extra path segment — except /{lang}/demo/app/, install-pwa, install-apk (under download)
$u2 = isset($u[2]) ? strtolower(trim((string) $u[2], '/')) : '';
$demo_app_route = ($u2 === 'app' && !empty($abc['layout']) && $abc['layout'] === 'demo_app');
$demo_pwa_ios_route = (in_array($u2, array('ios-pwa', 'install-pwa'), true) && !empty($abc['layout']) && $abc['layout'] === 'demo_pwa_ios');
$demo_apk_route = ($u2 === 'install-apk' && !empty($abc['layout']) && $abc['layout'] === 'demo_apk_android');
if ($u2 !== '' && !$demo_app_route && !$demo_pwa_ios_route && !$demo_apk_route) {
	$error++;
} elseif ($demo_app_route) {
	$abc['content'] = '';
} else {
	// Replace image/video codes with HTML
	// Multilingual fix: Prefer content_i18n (page_i18n.content) when available.
	$source_used = '';
	$source_text = '';
	if (isset($abc['page_i18n']) && is_array($abc['page_i18n']) && isset($abc['page_i18n']['content']) && trim((string)$abc['page_i18n']['content']) !== '') {
		$source_text = (string)$abc['page_i18n']['content'];
		$source_used = 'page_i18n.content';
	} else {
		$source_text = isset($abc['page']["text$langid"]) ? (string)$abc['page']["text$langid"] : '';
		$source_used = 'page.text' . (isset($langid) ? (string)$langid : '');
	}

	$abc['page']['text'] = $source_text;
	$abc['page']['text'] = template_img('pages', $abc['page']);
	$abc['page']['text'] = template_video($abc['page']['text'], $abc['page']['video']);
	// Same centering as guides: wrap every img in guide-img-center
	$abc['page']['text'] = preg_replace_callback('/<img(\s[^>]*)\/?>/i', function ($m) {
		return '<div class="guide-img-center">' . $m[0] . '</div>';
	}, $abc['page']['text']);
	$abc['content'] = $abc['page']['text'];

	if (!empty($abc['debug_translit']) && !empty($abc['debug_info'])) {
		$abc['debug_info']['pages_module_source'] = array(
			'source_used' => $source_used,
			'source_text_len' => strlen((string)$source_text),
			'content_len_after_templates' => strlen((string)$abc['content']),
		);
	}

	// Advertising API: replace content links with offer path
	if (!empty($abc['ad_offer_path']) && function_exists('site_ad_replace_content_links')) {
		$abc['content'] = site_ad_replace_content_links($abc['content'], $abc['ad_offer_path']);
	}
	if ($demo_apk_route) {
		if (function_exists('apk_install_normalize_apk_link_in_content')) {
			$abc['content'] = apk_install_normalize_apk_link_in_content($abc['content']);
		}
		if (function_exists('apk_install_replace_placeholder_step_images')) {
			$abc['content'] = apk_install_replace_placeholder_step_images($abc['content']);
		}
		if (function_exists('apk_install_bust_android_image_cache')) {
			$abc['content'] = apk_install_bust_android_image_cache($abc['content']);
		}
		if (function_exists('apk_install_enhance_download_ctas')) {
			$abc['content'] = apk_install_enhance_download_ctas($abc['content']);
		}
	}
	if ($demo_pwa_ios_route) {
		if (function_exists('pwa_install_normalize_demo_links_in_content')) {
			$abc['content'] = pwa_install_normalize_demo_links_in_content($abc['content'], $abc, $lang);
		}
		if (function_exists('pwa_install_bust_ios_image_cache')) {
			$abc['content'] = pwa_install_bust_ios_image_cache($abc['content']);
		}
		if (function_exists('pwa_install_enhance_page')) {
			$abc['content'] = pwa_install_enhance_page($abc['content'], $abc, $lang);
		} elseif (function_exists('pwa_install_enhance_quick_path')) {
			$abc['content'] = pwa_install_enhance_quick_path($abc['content'], $abc, $lang);
		}
	}
	if (function_exists('site_brand_rebrand_text') && !empty($abc['content'])) {
		$abc['content'] = site_brand_rebrand_text($abc['content']);
	}
	if (function_exists('site_brand_normalize_image_paths') && !empty($abc['content'])) {
		$abc['content'] = site_brand_normalize_image_paths($abc['content']);
	}
	if (!function_exists('site_template_lazyload_content_images')) {
		$perf = (defined('ROOT_DIR') ? ROOT_DIR : '') . 'functions/site_template_perf.php';
		if ($perf !== 'functions/site_template_perf.php' && is_file($perf)) {
			require_once $perf;
		}
	}
	if (function_exists('site_template_lazyload_content_images') && !empty($abc['content'])) {
		$abc['content'] = site_template_lazyload_content_images($abc['content']);
	}
}
