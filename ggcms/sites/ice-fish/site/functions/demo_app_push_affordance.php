<?php

/**
 * iOS standalone PWA push affordance for /demo/app/ header (replaces install icon slot).
 */

if (!function_exists('demo_app_push_ui_strings')) {
	/**
	 * @return array{enable_label:string,denied_label:string,denied_title:string,denied_body:string,modal_ok:string}
	 */
	function demo_app_push_ui_strings() {
		$brand = function_exists('site_brand_name') ? site_brand_name() : 'this app';
		$enable = trim((string) i18n('common|demo_app_push_enable_label'));
		if ($enable === '' || strpos($enable, 'common|') === 0) {
			$enable = 'Enable notifications';
		}
		$denied_label = trim((string) i18n('common|demo_app_push_denied_label'));
		if ($denied_label === '' || strpos($denied_label, 'common|') === 0) {
			$denied_label = 'Notifications off';
		}
		$title = trim((string) i18n('common|demo_app_push_denied_title'));
		if ($title === '' || strpos($title, 'common|') === 0) {
			$title = 'Turn on notifications';
		}
		$body = trim((string) i18n('common|demo_app_push_denied_body'));
		if ($body === '' || strpos($body, 'common|') === 0) {
			$body = 'Open the Settings app → Notifications → ' . $brand
				. ' → turn on Allow Notifications. Then return to the app.';
		} else {
			$body = str_replace('{brand}', $brand, $body);
		}
		$ok = trim((string) i18n('common|demo_app_modal_got_it'));
		if ($ok === '' || strpos($ok, 'common|') === 0) {
			$ok = 'Got it';
		}

		return array(
			'enable_label' => $enable,
			'denied_label' => $denied_label,
			'denied_title' => $title,
			'denied_body' => $body,
			'modal_ok' => $ok,
		);
	}
}

if (!function_exists('demo_app_push_affordance_markup')) {
	/**
	 * @param array $ui from demo_app_push_ui_strings()
	 */
	function demo_app_push_affordance_markup(array $ui) {
		$enable = htmlspecialchars((string) $ui['enable_label'], ENT_QUOTES, 'UTF-8');
		$denied = htmlspecialchars((string) $ui['denied_label'], ENT_QUOTES, 'UTF-8');

		return '<span class="demo-app-install-wrap demo-app-push-wrap" id="demoAppPushWrap" hidden>'
			. '<button type="button" class="demo-app-icon-btn demo-app-push demo-app-push--idle" id="demoAppPushBtn"'
			. ' data-label-enable="' . $enable . '" data-label-denied="' . $denied . '"'
			. ' title="' . $enable . '" aria-label="' . $enable . '">'
			. '<i class="fa-solid fa-bell" id="demoAppPushIcon" aria-hidden="true"></i>'
			. '</button>'
			. '</span>';
	}
}

if (!function_exists('demo_app_push_affordance_modal')) {
	/**
	 * @param array $ui from demo_app_push_ui_strings()
	 */
	function demo_app_push_affordance_modal(array $ui) {
		$title = htmlspecialchars((string) $ui['denied_title'], ENT_QUOTES, 'UTF-8');
		$body = htmlspecialchars((string) $ui['denied_body'], ENT_QUOTES, 'UTF-8');
		$ok = htmlspecialchars((string) $ui['modal_ok'], ENT_QUOTES, 'UTF-8');

		return '<div class="demo-app-safari-hint demo-app-push-hint" id="demoAppPushHint" hidden role="dialog" aria-modal="true" aria-labelledby="demoAppPushHintTitle">'
			. '<div class="demo-app-safari-hint__backdrop" data-demo-push-dismiss></div>'
			. '<div class="demo-app-safari-hint__panel">'
			. '<p class="demo-app-safari-hint__title" id="demoAppPushHintTitle">' . $title . '</p>'
			. '<p class="demo-app-safari-hint__body">' . $body . '</p>'
			. '<button type="button" class="demo-app-safari-hint__ok" data-demo-push-dismiss>' . $ok . '</button>'
			. '</div>'
			. '</div>';
	}
}

if (!function_exists('demo_app_push_affordance_script')) {
	function demo_app_push_affordance_script() {
		return <<<'HTML'
<script>
(function () {
	var pushWrap = document.getElementById('demoAppPushWrap');
	var pushBtn = document.getElementById('demoAppPushBtn');
	var pushIcon = document.getElementById('demoAppPushIcon');
	var pushHint = document.getElementById('demoAppPushHint');
	var installBtn = document.getElementById('demoAppInstallBtn');
	if (!pushWrap || !pushBtn || !installBtn) return;

	var LS_AUTO = 'os_ios_push_auto_offered';

	function isStandaloneShell() {
		if (window.navigator.standalone === true) return true;
		try {
			if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return true;
			if (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches) return true;
		} catch (e) { /* ignore */ }
		return false;
	}

	function isIosDevice() {
		var ua = navigator.userAgent || '';
		if (/iPhone|iPad|iPod/i.test(ua)) return true;
		return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
	}

	function nativePermission() {
		try {
			if (window.Notification && Notification.permission) {
				return Notification.permission;
			}
		} catch (e) { /* ignore */ }
		return 'default';
	}

	function hidePushAffordance() {
		pushWrap.hidden = true;
		pushBtn.setAttribute('aria-hidden', 'true');
	}

	function showPushAffordance(state) {
		var enableLabel = pushBtn.getAttribute('data-label-enable') || 'Enable notifications';
		var deniedLabel = pushBtn.getAttribute('data-label-denied') || 'Notifications off';
		var denied = state === 'denied';
		pushBtn.classList.toggle('demo-app-push--denied', denied);
		pushBtn.classList.toggle('demo-app-push--idle', !denied);
		if (pushIcon) {
			pushIcon.className = denied ? 'fa-solid fa-bell-slash' : 'fa-solid fa-bell';
		}
		var label = denied ? deniedLabel : enableLabel;
		pushBtn.title = label;
		pushBtn.setAttribute('aria-label', label);
		pushWrap.hidden = false;
		pushBtn.removeAttribute('aria-hidden');
	}

	function syncFromPermission() {
		if (!isStandaloneShell() || !isIosDevice()) {
			hidePushAffordance();
			return;
		}
		var perm = nativePermission();
		if (perm === 'granted') {
			hidePushAffordance();
			return;
		}
		showPushAffordance(perm === 'denied' ? 'denied' : 'default');
	}

	function markAutoOffered() {
		try { localStorage.setItem(LS_AUTO, '1'); } catch (e) { /* ignore */ }
	}

	function requestPushPermission(OneSignal) {
		markAutoOffered();
		if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.requestPermission === 'function') {
			return OneSignal.Notifications.requestPermission();
		}
		if (window.Notification && typeof Notification.requestPermission === 'function') {
			return Notification.requestPermission();
		}
		return Promise.resolve();
	}

	function bindPushHint() {
		if (!pushHint) return;
		pushHint.querySelectorAll('[data-demo-push-dismiss]').forEach(function (el) {
			el.addEventListener('click', function () { pushHint.hidden = true; });
		});
	}

	function bindPushClick(OneSignal) {
		pushBtn.addEventListener('click', function () {
			var perm = nativePermission();
			if (perm === 'denied') {
				if (pushHint) pushHint.hidden = false;
				return;
			}
			requestPushPermission(OneSignal).finally(function () {
				setTimeout(syncFromPermission, 300);
			});
		});
	}

	function initWithOneSignal(OneSignal) {
		if (!isStandaloneShell() || !isIosDevice()) return;
		var installWrap = installBtn.closest('.demo-app-install-wrap');
		if (installWrap) installWrap.style.display = 'none';
		installBtn.style.display = 'none';
		installBtn.setAttribute('aria-hidden', 'true');

		syncFromPermission();

		if (OneSignal && OneSignal.Notifications && typeof OneSignal.Notifications.addEventListener === 'function') {
			OneSignal.Notifications.addEventListener('permissionChange', function () {
				syncFromPermission();
			});
		}

		bindPushClick(OneSignal);
		bindPushHint();
	}

	if (!isStandaloneShell() || !isIosDevice()) {
		hidePushAffordance();
		return;
	}

	window.OneSignalDeferred = window.OneSignalDeferred || [];
	OneSignalDeferred.push(async function (OneSignal) {
		try {
			if (!(await OneSignal.Notifications.isPushSupported())) {
				hidePushAffordance();
				return;
			}
		} catch (e) {
			hidePushAffordance();
			return;
		}
		initWithOneSignal(OneSignal);
	});

	// Fallback if OneSignal never loads — still show settings hint affordance from Notification API.
	setTimeout(function () {
		if (pushWrap.hidden === false) return;
		if (!isStandaloneShell() || !isIosDevice()) return;
		if (nativePermission() === 'granted') return;
		initWithOneSignal(null);
	}, 4000);
})();
</script>
HTML;
	}
}
