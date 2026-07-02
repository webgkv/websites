<?php
require_once __DIR__ . '/site_onesignal_web.php';

/**
 * Median.co / GoNative native shell WebView detection.
 * Web OneSignal must not load inside the app — native OneSignal uses FCM/APNs via Median.
 *
 * @see https://docs.median.co/docs/detecting-app-usage
 */

/**
 * Register merged /sw.js only in real browsers — not in Median/GoNative WebView (native OneSignal + no web SW).
 */
function site_should_register_pwa_service_worker() {
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	return !site_is_median_native_webview($ua);
}

/**
 * @param string|null $ua User-Agent (null/empty → use HTTP request UA when available)
 */
function site_is_median_native_webview($ua = null) {
	if ($ua === null || $ua === '') {
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	}
	if ($ua === '') {
		return false;
	}
	$ua = (string) $ua;
	$re = function_exists('site_median_native_webview_ua_regex')
		? site_median_native_webview_ua_regex()
		: '/MedianAndroid|MedianIOS|Median\\/|gonative\\.io|GoNativeAndroid|GoNativeIOS/i';
	return (bool) preg_match($re, $ua);
}

/**
 * True if this HTML/JS block is OneSignal Web Push (do not inject in Median WebView).
 */
function site_counter_snippet_is_onesignal_web($html) {
	if (!is_string($html) || $html === '') {
		return false;
	}
	$l = strtolower($html);
	if (strpos($l, 'onesignal') !== false) {
		return true;
	}
	if (strpos($l, 'cdn.onesignal.com') !== false) {
		return true;
	}
	return false;
}

/**
 * Remove OneSignal web snippets from head/body/footer counters for native shell requests.
 */
function site_counters_strip_onesignal_web_for_native_shell(&$head, &$body, &$footer) {
	if (!site_is_median_native_webview()) {
		return;
	}
	$filter = function ($arr) {
		if (!is_array($arr)) {
			return array();
		}
		return array_values(array_filter($arr, function ($chunk) {
			return !site_counter_snippet_is_onesignal_web($chunk);
		}));
	};
	$head = $filter($head);
	$body = $filter($body);
	$footer = $filter($footer);
}
