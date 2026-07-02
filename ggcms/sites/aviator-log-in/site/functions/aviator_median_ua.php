<?php
/**
 * Median.co / GoNative native shell WebView detection.
 * Web OneSignal must not load inside the app — native OneSignal uses FCM/APNs via Median.
 *
 * @see https://docs.median.co/docs/detecting-app-usage
 */
function aviator_is_median_native_webview_user_agent($ua) {
	if ($ua === null || $ua === '') {
		return false;
	}
	$ua = (string) $ua;
	if (preg_match('/MedianAndroid|MedianIOS|Median\\/|gonative\\.io/i', $ua)) {
		return true;
	}
	if (preg_match('/GoNativeAndroid|GoNativeIOS/i', $ua)) {
		return true;
	}
	if (preg_match('/\\bmedian\\b/i', $ua) || preg_match('/\\bgonative\\b/i', $ua)) {
		return true;
	}
	return false;
}

/**
 * True if this HTML/JS block is OneSignal Web Push (do not inject in Median WebView).
 */
function aviator_counter_snippet_is_onesignal_web($html) {
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
function aviator_counters_strip_onesignal_web_for_native_shell(&$head, &$body, &$footer) {
	$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if (!aviator_is_median_native_webview_user_agent($ua)) {
		return;
	}
	$filter = function ($arr) {
		if (!is_array($arr)) {
			return array();
		}
		return array_values(array_filter($arr, function ($chunk) {
			return !aviator_counter_snippet_is_onesignal_web($chunk);
		}));
	};
	$head = $filter($head);
	$body = $filter($body);
	$footer = $filter($footer);
}
