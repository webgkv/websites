<?php

/**
 * Brand profile loader (production). Profile is baked into build as config/brand.profile.php.
 */

function site_brand_profile() {
	static $profile = null;
	if ($profile !== null) {
		return $profile;
	}
	$profile = array();
	$root = defined('ROOT_DIR') ? ROOT_DIR : '';
	$path = $root . 'config/brand.profile.php';
	if (is_file($path)) {
		$loaded = require $path;
		if (is_array($loaded)) {
			$profile = $loaded;
		}
	}
	return $profile;
}

function site_brand_profile_value($key, $default = '') {
	$p = site_brand_profile();
	return isset($p[$key]) ? $p[$key] : $default;
}

/**
 * Apply static redirects from brand profile (call early in index.php after config).
 */
function site_apply_profile_static_redirects() {
	$redirects = site_brand_profile_value('static_redirects', array());
	if (!is_array($redirects) || empty($redirects)) {
		return;
	}
	$path = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
	if (!is_string($path) || !isset($redirects[$path])) {
		return;
	}
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $redirects[$path]);
	exit;
}
