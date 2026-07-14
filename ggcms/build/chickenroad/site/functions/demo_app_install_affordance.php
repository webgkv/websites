<?php

/**
 * DEMO_INSTALL_AFFORDANCE — /demo/app/ install icon (rollback: DEMO_INSTALL_AFFORDANCE_ROLLBACK.md).
 * /demo/app/ must never be cached (Cloudflare, nginx proxy, html_query) — UA-specific install affordance.
 */

if (!function_exists('site_demo_app_request_is_shell')) {
	/**
	 * True for /{lang}/demo/app/ (optional trailing slash).
	 */
	function site_demo_app_request_is_shell($uri = null) {
		if ($uri === null) {
			$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
		}
		$path = parse_url($uri, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			return false;
		}
		$path = preg_replace('#/+#', '/', $path);

		return (bool) preg_match('#/(?:[a-z]{2}(?:-[a-z]{2})?/)demo/app/?$#i', $path);
	}
}

if (!function_exists('site_demo_app_send_nocache_headers')) {
	/**
	 * Hard no-store for demo app shell — Cloudflare, nginx proxy, browser.
	 */
	function site_demo_app_send_nocache_headers() {
		if (headers_sent()) {
			return;
		}
		header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
		header('CDN-Cache-Control: no-store');
		header('Cloudflare-CDN-Cache-Control: no-store');
		header('Vary: User-Agent', false);
		header('X-GGCMS-Cache-Policy: demo-app-no-store');
	}
}

if (!function_exists('site_demo_app_bootstrap_nocache')) {
	function site_demo_app_bootstrap_nocache() {
		if (site_demo_app_request_is_shell()) {
			global $config;
			if (isset($config) && is_array($config)) {
				$config['cache'] = 0;
			}
			site_demo_app_send_nocache_headers();
		}
	}
}

if (!function_exists('demo_app_install_ua_platform')) {
	/**
	 * @param string|null $ua
	 * @return string ios|android|''
	 */
	function demo_app_install_ua_platform($ua = null) {
		if ($ua === null) {
			$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		}
		if ($ua === '') {
			return '';
		}
		if (preg_match('/Android/i', $ua)) {
			return 'android';
		}
		if (preg_match('/iPhone|iPod|iPad/i', $ua)) {
			return 'ios';
		}
		if (preg_match('/Macintosh/i', $ua) && preg_match('/Mobile/i', $ua)) {
			return 'ios';
		}

		return '';
	}
}

if (!function_exists('demo_app_install_is_native_shell_server')) {
	function demo_app_install_is_native_shell_server($ua = null) {
		return function_exists('site_is_median_native_webview') && site_is_median_native_webview($ua);
	}
}

if (!function_exists('demo_app_install_affordance')) {
	/**
	 * @param array $abc
	 * @param array $lang
	 * @return array{enabled:bool,platform?:string,href?:string,label?:string}
	 */
	function demo_app_install_affordance(array $abc, array $lang) {
		if (demo_app_install_is_native_shell_server()) {
			return array('enabled' => false);
		}
		$platform = demo_app_install_ua_platform();
		if ($platform === '') {
			return array('enabled' => false);
		}
		$href = '';
		$label = '';
		if ($platform === 'ios') {
			if (function_exists('pwa_install_guide_url')) {
				$href = (string) pwa_install_guide_url($abc, $lang);
			}
			if (function_exists('pwa_install_label')) {
				$label = (string) pwa_install_label($lang);
			}
			if ($label === '') {
				$label = 'Install on iPhone';
			}
		} elseif ($platform === 'android') {
			if (function_exists('apk_install_guide_url')) {
				$href = (string) apk_install_guide_url($abc, $lang);
			}
			if (function_exists('apk_install_label')) {
				$label = (string) apk_install_label($lang);
			}
			if ($label === '') {
				$label = 'Install APK';
			}
		}
		if ($href === '') {
			return array('enabled' => false);
		}

		return array(
			'enabled' => true,
			'platform' => $platform,
			'href' => $href,
			'label' => $label,
		);
	}
}

if (!function_exists('demo_app_install_ui_strings')) {
	/**
	 * Modal copy for iOS in-app browser hint.
	 *
	 * @return array{safari_title:string,safari_body:string,modal_ok:string,inapp_tooltip:string}
	 */
	function demo_app_install_ui_strings() {
		$brand = function_exists('site_brand_name') ? site_brand_name() : 'this app';
		$title = trim((string) i18n('common|demo_app_open_in_safari_title'));
		if ($title === '' || strpos($title, 'common|') === 0) {
			$title = 'Open in Safari';
		}
		$body = trim((string) i18n('common|demo_app_open_in_safari_body'));
		if ($body === '' || strpos($body, 'common|') === 0) {
			$body = 'To add ' . $brand . ' to your Home Screen, open this page in Safari first, then use Share → Add to Home Screen.';
		}
		$ok = trim((string) i18n('common|demo_app_modal_got_it'));
		if ($ok === '' || strpos($ok, 'common|') === 0) {
			$ok = 'Got it';
		}
		$tooltip = trim((string) i18n('common|demo_app_install_inapp_tooltip'));
		if ($tooltip === '' || strpos($tooltip, 'common|') === 0) {
			$tooltip = 'Open in Safari to add the app';
		}

		return array(
			'safari_title' => $title,
			'safari_body' => $body,
			'modal_ok' => $ok,
			'inapp_tooltip' => $tooltip,
		);
	}
}
