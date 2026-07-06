<?php

/**
 * Site brand helpers. Brand values from $config + config/brand.profile.php (build-time).
 */

require_once __DIR__ . '/brand_profile.php';

function site_brand_name() {
	global $config;
	if (!empty($config['site_brand_name'])) {
		return (string) $config['site_brand_name'];
	}
	if (function_exists('site_brand_profile_value')) {
		$name = site_brand_profile_value('name', '');
		if ($name !== '') {
			return (string) $name;
		}
	}
	return 'Site';
}

function site_brand_title_suffix() {
	global $config;
	if (!empty($config['site_title_suffix'])) {
		return (string) $config['site_title_suffix'];
	}
	return ' | ' . site_brand_name();
}

function site_brand_mid_suffix() {
	return ' | ' . site_brand_name();
}

function site_brand_hero_image_path() {
	global $config;
	if (!empty($config['site_hero_image'])) {
		return (string) $config['site_hero_image'];
	}
	$from_profile = function_exists('site_brand_profile_value')
		? site_brand_profile_value('default_hero_image', '')
		: '';
	return $from_profile !== '' ? (string) $from_profile : '/assets/images/hero.webp';
}

function site_brand_hero_image_url() {
	return site_brand_asset_url(site_brand_hero_image_path());
}

function site_brand_favicon_path() {
	global $config;
	if (!empty($config['site_favicon'])) {
		return (string) $config['site_favicon'];
	}
	$from_profile = function_exists('site_brand_profile_value')
		? site_brand_profile_value('default_favicon', '')
		: '';
	return $from_profile !== '' ? (string) $from_profile : '/assets/images/favicon.png';
}

function site_brand_favicon_url() {
	return site_brand_asset_url(site_brand_favicon_path());
}

function site_admin_favicon_href() {
	global $config;
	$path = site_brand_favicon_path();
	$root = defined('ROOT_DIR') ? ROOT_DIR : '';
	if ($root !== '' && is_file($root . ltrim($path, '/'))) {
		return site_brand_favicon_url();
	}
	$style = isset($config['style']) ? trim((string) $config['style'], '/') : 'admin/templates2';
	return '/' . $style . '/assets/media/image/favicon.png';
}

function site_brand_asset_url($path) {
	$path = (string) $path;
	if ($path === '') {
		return '';
	}
	$root = defined('ROOT_DIR') ? ROOT_DIR : '';
	$file = $root . ltrim($path, '/');
	$v = @is_file($file) ? (string) filemtime($file) : (string) time();
	global $config;
	if (!empty($config['assets_version'])) {
		$v = (string) $config['assets_version'] . '.' . $v;
	}
	return $path . '?v=' . rawurlencode($v);
}

function site_brand_default_og_image_path() {
	global $config;
	if (!empty($config['site_default_og_image'])) {
		return (string) $config['site_default_og_image'];
	}
	return site_brand_hero_image_path();
}

function site_brand_asset_legacy_map() {
	if (function_exists('site_brand_profile_value')) {
		$map = site_brand_profile_value('asset_legacy_map', array());
		if (is_array($map) && !empty($map)) {
			return $map;
		}
	}
	return array();
}

function site_brand_normalize_image_paths($html) {
	if ($html === '' || $html === null) {
		return (string) $html;
	}
	$html = (string) $html;
	$hero = site_brand_hero_image_url();
	$path_q = preg_quote(site_brand_hero_image_path(), '#');
	$html = preg_replace('#' . $path_q . '(?:\?v=[^"\'\s>]*)+#i', site_brand_hero_image_path(), $html);
	foreach (site_brand_asset_legacy_map() as $legacy => $target) {
		$pattern = '#(/assets/images/' . preg_quote($legacy, '#') . ')(?:\?[^"\'\s>]*)?#i';
		$html = preg_replace($pattern, site_brand_asset_url($target), $html);
	}
	$html = preg_replace(
		'#(/images/games/Aviatrix-header\.webp)(?:\?[^"\'\s>]*)?#i',
		site_brand_asset_url(site_brand_hero_image_path()),
		$html
	);
	$html = preg_replace_callback(
		'#(/assets/images/[^"\'\s>]+\.(?:webp|png|jpe?g|svg))(?:\?[^"\'\s>]*)?#i',
		function ($m) {
			return site_brand_asset_url($m[1]);
		},
		$html
	);
	return $html;
}

function site_brand_demo_preview_step_paths() {
	if (function_exists('site_brand_profile_value')) {
		$steps = site_brand_profile_value('demo_preview_steps', array());
		if (is_array($steps) && !empty($steps)) {
			return $steps;
		}
	}
	return array();
}

function site_brand_demo_preview_step_urls() {
	$urls = array();
	foreach (site_brand_demo_preview_step_paths() as $path) {
		$urls[] = site_brand_asset_url($path);
	}
	return $urls;
}

function site_brand_store_google_icon_path() {
	global $config;
	if (!empty($config['site_store_google_icon'])) {
		return (string) $config['site_store_google_icon'];
	}
	$from_profile = function_exists('site_brand_profile_value')
		? site_brand_profile_value('default_store_google', '')
		: '';
	return $from_profile !== '' ? (string) $from_profile : '/assets/images/store-googleplay.svg';
}

function site_brand_store_appstore_icon_path() {
	global $config;
	if (!empty($config['site_store_appstore_icon'])) {
		return (string) $config['site_store_appstore_icon'];
	}
	$from_profile = function_exists('site_brand_profile_value')
		? site_brand_profile_value('default_store_appstore', '')
		: '';
	return $from_profile !== '' ? (string) $from_profile : '/assets/images/store-appstore.svg';
}

function site_brand_rebrand_preserve_urls($text) {
	$tokens = array();
	$n = 0;
	$text = preg_replace_callback(
		'#(?:https?://[^/"\'\s<>]+)?/(?:assets/images|files)/[^\s"\'<>]+#i',
		function ($m) use (&$tokens, &$n) {
			$key = '%%BRANDURL' . $n . '%%';
			$tokens[$key] = $m[0];
			$n++;
			return $key;
		},
		$text
	);
	return array($text, $tokens);
}

function site_brand_rebrand_restore_urls($text, array $tokens) {
	foreach ($tokens as $key => $url) {
		$text = str_replace($key, $url, $text);
	}
	return $text;
}

/**
 * Build rebrand string-replacement pairs from brand profile + runtime brand name.
 *
 * @return array<string,string>
 */
function site_brand_rebrand_pairs() {
	$brand = site_brand_name();
	$pairs = array(
		'Aviator Log In' => $brand,
		'Chicken Road Log In' => $brand,
		'Aviator Home' => $brand,
		'Chicken Road Home' => $brand,
		'Aviator demo' => $brand . ' demo',
		'Aviator Demo' => $brand . ' demo',
		'Chicken Road demo' => $brand . ' demo',
		'Chicken Road Demo' => $brand . ' demo',
		'Install Aviator demo (Android APK)' => 'Install ' . $brand . ' demo (Android APK)',
		'Install the Aviator demo on your iPhone' => 'Install the ' . $brand . ' demo on your iPhone',
		'Install Aviator demo on iPhone' => 'Install ' . $brand . ' demo on iPhone',
		'Install Chicken Road demo (Android APK)' => 'Install ' . $brand . ' demo (Android APK)',
		'Install the Chicken Road demo on your iPhone' => 'Install the ' . $brand . ' demo on your iPhone',
		'Install Chicken Road demo on iPhone' => 'Install ' . $brand . ' demo on iPhone',
		'/files/aviator.apk' => '/files/' . site_brand_apk_basename(),
		'/files/chickenroad.apk' => '/files/' . site_brand_apk_basename(),
		'download="aviator.apk"' => 'download="' . site_brand_apk_basename() . '"',
		'download="chickenroad.apk"' => 'download="' . site_brand_apk_basename() . '"',
		'#aviator-app' => '#demo',
	);
	if (function_exists('site_brand_profile_value')) {
		$host_map = site_brand_profile_value('legacy_hostname_map', array());
		if (is_array($host_map)) {
			foreach ($host_map as $from => $to) {
				$pairs[(string) $from] = (string) $to;
			}
		}
		$from_brands = site_brand_profile_value('rebrand_from_brands', array());
		if (is_array($from_brands)) {
			foreach ($from_brands as $legacy_brand) {
				$legacy_brand = (string) $legacy_brand;
				if ($legacy_brand !== '' && strcasecmp($legacy_brand, $brand) !== 0) {
					$pairs[$legacy_brand] = $brand;
				}
			}
		}
	}
	uksort($pairs, function ($a, $b) {
		return strlen($b) - strlen($a);
	});
	return $pairs;
}

function site_brand_rebrand_text($text) {
	if ($text === null || $text === '') {
		return (string) $text;
	}
	$text = (string) $text;
	list($text, $url_tokens) = site_brand_rebrand_preserve_urls($text);
	foreach (site_brand_rebrand_pairs() as $from => $to) {
		$text = str_ireplace($from, $to, $text);
	}
	return site_brand_rebrand_restore_urls($text, $url_tokens);
}

function site_brand_rebrand_value($text) {
	$text = site_brand_rebrand_text($text);
	if (function_exists('chickenroad_normalize_legacy_slug_urls_in_text')) {
		$text = chickenroad_normalize_legacy_slug_urls_in_text($text);
	}
	return $text;
}

function site_brand_rebrand_row_fields(array &$row, array $fields) {
	foreach ($fields as $k) {
		if (!empty($row[$k])) {
			$row[$k] = site_brand_rebrand_value($row[$k]);
		}
	}
}

function site_brand_apply_rebrand_to_abc(array &$abc) {
	if (isset($abc['page']['title'])) {
		$abc['page']['title'] = site_brand_rebrand_value($abc['page']['title']);
	}
	if (isset($abc['page']['description'])) {
		$abc['page']['description'] = site_brand_rebrand_value($abc['page']['description']);
	}
	if (!empty($abc['content'])) {
		$abc['content'] = site_brand_rebrand_value($abc['content']);
	}
	if (isset($abc['page_i18n']) && is_array($abc['page_i18n'])) {
		site_brand_rebrand_row_fields($abc['page_i18n'], array('title', 'name', 'description', 'content'));
	}
	foreach (array('guide_single', 'game_single', 'casino_single', 'blog_single', 'news_single') as $block) {
		if (!empty($abc[$block]) && is_array($abc[$block])) {
			site_brand_rebrand_row_fields($abc[$block], array('title', 'name', 'description', 'text', 'content'));
		}
	}
}

function site_brand_apk_basename() {
	global $config;
	if (!empty($config['site_apk_filename'])) {
		return (string) $config['site_apk_filename'];
	}
	if (function_exists('site_brand_profile_value')) {
		$apk = site_brand_profile_value('default_apk', '');
		if ($apk !== '') {
			return (string) $apk;
		}
	}
	return 'app.apk';
}

function site_game_demo_iframe_url(array $config = array()) {
	if (!empty($config['game_demo_iframe_url'])) {
		return trim((string) $config['game_demo_iframe_url']);
	}
	if (!empty($config['aviator_demo_iframe_url'])) {
		return trim((string) $config['aviator_demo_iframe_url']);
	}
	return '';
}
