<?php
/**
 * OneSignal Web Push helpers (browser / PWA). Native Median shell uses the app plugin, not web SDK.
 */

/**
 * Explicit Median / GoNative WebView markers only (not generic "mobile").
 *
 * @see https://docs.median.co/docs/detecting-app-usage
 */
function site_median_native_webview_ua_regex() {
	return '/MedianAndroid|MedianIOS|Median\\/|gonative\\.io|GoNativeAndroid|GoNativeIOS/i';
}

/**
 * Supplemental script: iOS home-screen PWA may request permission after OneSignal.init (DB counters).
 * Does not run in Median WebView or in regular Safari tabs (Apple requires Add to Home Screen first).
 *
 * @return string HTML script block or empty string in Median shell
 */
function site_onesignal_web_ios_prompt_script() {
	if (function_exists('site_is_median_native_webview') && site_is_median_native_webview()) {
		return '';
	}
	return <<<'HTML'
<script>
(function () {
  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(async function (OneSignal) {
    var ua = navigator.userAgent || '';
    if (/MedianAndroid|MedianIOS|Median\/|gonative\.io|GoNativeAndroid|GoNativeIOS/i.test(ua)) {
      return;
    }
    if (!/iPhone|iPad|iPod/i.test(ua)) {
      return;
    }
    try {
      if (!(await OneSignal.Notifications.isPushSupported())) {
        return;
      }
      var standalone = window.navigator.standalone === true
        || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
      if (!standalone) {
        return;
      }
      if (OneSignal.Notifications.permission) {
        return;
      }
      await OneSignal.Notifications.requestPermission();
    } catch (e) {}
  });
})();
</script>
HTML;
}
