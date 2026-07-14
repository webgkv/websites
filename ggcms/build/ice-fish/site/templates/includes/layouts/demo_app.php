<?php
global $config, $abc, $lang;
$demo_back_url = preg_replace('#/+#', '/', (string) get_url('page', $abc['page']));
$_lu = isset($abc['lang']['url']) ? trim((string) $abc['lang']['url'], '/') : '';
$demo_portal_url = ($_lu !== '') ? '/' . $_lu . '/' : '/';
$portal_aria = trim((string) i18n('common|back_to_home'));
if ($portal_aria === '' || strpos($portal_aria, 'common|') === 0) {
	$portal_aria = 'Back to Home';
}
$fs_label = trim(i18n('common|demo_app_fullscreen'));
if ($fs_label === '' || strpos($fs_label, 'common|') === 0) {
	$fs_label = 'Fullscreen';
}
$close_label = trim(i18n('common|aria_close'));
if ($close_label === '' || strpos($close_label, 'common|') === 0) {
	$close_label = 'Close';
}
$logo_alt = trim(i18n('common|sitename'));
if ($logo_alt === '' || strpos($logo_alt, 'common|') === 0) {
	$logo_alt = function_exists('site_brand_name') ? site_brand_name() : 'Ice Fish';
}
$try_bonus_label = trim(i18n('common|cta_try_bonus'));
if ($try_bonus_label === '' || strpos($try_bonus_label, 'common|') === 0) {
	$try_bonus_label = 'Try Bonus';
}
$offer_path = (isset($abc['ad_offer_path']) && is_string($abc['ad_offer_path'])) ? trim($abc['ad_offer_path']) : '';
// DEMO_INSTALL_AFFORDANCE — ggcms/DEMO_INSTALL_AFFORDANCE_ROLLBACK.md
$_demo_install = (function_exists('demo_app_install_affordance') && isset($lang) && is_array($lang))
	? demo_app_install_affordance($abc, $lang)
	: array('enabled' => false);
$_demo_install_ui = function_exists('demo_app_install_ui_strings') ? demo_app_install_ui_strings() : array(
	'safari_title' => 'Open in Safari',
	'safari_body' => 'Open this page in Safari, then use Share → Add to Home Screen.',
	'modal_ok' => 'Got it',
);
$icon_path = (defined('ROOT_DIR') && file_exists(ROOT_DIR . 'assets/images/hook.svg'))
	? ROOT_DIR . 'assets/images/hook.svg'
	: '';
$logo_v = $icon_path !== '' ? (int) filemtime($icon_path) : time();
?>
<div class="demo-app-shell">
	<header class="demo-app-bar" role="banner">
		<div class="demo-app-bar-start">
		<a class="demo-app-brand" href="<?= htmlspecialchars($demo_back_url, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>">
			<span class="demo-app-logo-icon" style="--demo-hook-mask: url('/assets/images/hook.svg?v=<?= (int) $logo_v ?>');" aria-hidden="true"></span>
		</a>
		<a class="demo-app-icon-btn demo-app-portal" href="<?= htmlspecialchars($demo_portal_url, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
		</a>
<?php if (!empty($_demo_install['enabled'])): ?>
		<span class="demo-app-install-wrap">
		<a class="demo-app-icon-btn demo-app-install" id="demoAppInstallBtn"
			href="<?= htmlspecialchars((string) $_demo_install['href'], ENT_QUOTES, 'UTF-8') ?>"
			data-platform="<?= htmlspecialchars((string) $_demo_install['platform'], ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars((string) $_demo_install['label'], ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars((string) $_demo_install['label'], ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
		</a>
		<span class="demo-app-install-tooltip" id="demoAppInstallTooltip" hidden role="tooltip"><?= htmlspecialchars((string) ($_demo_install_ui['inapp_tooltip'] ?? 'Open in Safari to add the app'), ENT_QUOTES, 'UTF-8') ?></span>
		</span>
<?php endif; ?>
		</div>
		<div class="demo-app-actions">
			<?php if ($offer_path !== ''): ?>
			<div class="main_btn demo-app-cta-btn" id="demoAppCtaBtn">
				<a href="<?= htmlspecialchars($offer_path, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($try_bonus_label, ENT_QUOTES, 'UTF-8') ?></a>
			</div>
			<?php endif; ?>
			<button type="button" class="demo-app-icon-btn demo-app-fs-btn" id="demoAppFsBtn" aria-pressed="false" title="<?= htmlspecialchars($fs_label, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($fs_label, ENT_QUOTES, 'UTF-8') ?>">
				<i class="fa-solid fa-expand" aria-hidden="true"></i>
			</button>
			<a class="demo-app-icon-btn demo-app-close" href="<?= htmlspecialchars($demo_back_url, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($close_label, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($close_label, ENT_QUOTES, 'UTF-8') ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</a>
		</div>
	</header>
	<div class="demo-app-frame-host" id="demoAppFrameHost">
		<?php
		require_once ROOT_DIR . 'functions/game_demo_embed.php';
		if (function_exists('game_demo_is_mirror_shell') && game_demo_is_mirror_shell($config)) {
			require ROOT_DIR . 'templates/includes/common/app_demo_mirror.php';
		} else {
			require ROOT_DIR . 'templates/includes/common/app_demo.php';
		}
		?>
	</div>
</div>
<?php if (!empty($_demo_install['enabled'])): ?>
<div class="demo-app-safari-hint" id="demoAppSafariHint" hidden role="dialog" aria-modal="true" aria-labelledby="demoAppSafariHintTitle">
	<div class="demo-app-safari-hint__backdrop" data-demo-safari-dismiss></div>
	<div class="demo-app-safari-hint__panel">
		<p class="demo-app-safari-hint__title" id="demoAppSafariHintTitle"><?= htmlspecialchars((string) $_demo_install_ui['safari_title'], ENT_QUOTES, 'UTF-8') ?></p>
		<p class="demo-app-safari-hint__body"><?= htmlspecialchars((string) $_demo_install_ui['safari_body'], ENT_QUOTES, 'UTF-8') ?></p>
		<button type="button" class="demo-app-safari-hint__ok" data-demo-safari-dismiss><?= htmlspecialchars((string) $_demo_install_ui['modal_ok'], ENT_QUOTES, 'UTF-8') ?></button>
	</div>
</div>
<?php endif; ?>
<script>
(function () {
	var host = document.getElementById('demoAppFrameHost');
	var btn = document.getElementById('demoAppFsBtn');
	if (!host || !btn) return;
	var iconExpand = '<i class="fa-solid fa-expand" aria-hidden="true"></i>';
	var iconCompress = '<i class="fa-solid fa-compress" aria-hidden="true"></i>';
	function isFs() {
		return document.fullscreenElement === host;
	}
	function setBtnState(on) {
		btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		btn.innerHTML = on ? iconCompress : iconExpand;
	}
	btn.addEventListener('click', function () {
		if (!document.fullscreenEnabled) return;
		if (isFs()) {
			document.exitFullscreen().catch(function () {});
		} else {
			host.requestFullscreen().catch(function () {});
		}
	});
	document.addEventListener('fullscreenchange', function () {
		setBtnState(isFs());
	});
})();
</script>
<?php if (!empty($_demo_install['enabled'])): ?>
<script>
/* DEMO_INSTALL_AFFORDANCE — rollback: ggcms/DEMO_INSTALL_AFFORDANCE_ROLLBACK.md */
(function () {
	var installBtn = document.getElementById('demoAppInstallBtn');
	if (!installBtn) return;

	var INSTALL_IOS_FIRST_MS = 20000;
	var INSTALL_IOS_REPEAT_MS = 150000;
	var INSTALL_INAPP_TOOLTIP_MS = 12000;
	var INSTALL_INAPP_TOOLTIP_DURATION_MS = 5000;
	var INSTALL_PULSE_CYCLE_MS = 2500;
	var MUTEX_DEFER_MS = 8000;
	var CTA_FIRST_MS = 60000;
	var CTA_REPEAT_MS = 240000;
	var CTA_BURST_MS = 2600;

	var barAnimState = { install: false, cta: false };

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

	function isIosInAppBrowser() {
		if (!isIosDevice()) return false;
		var ua = navigator.userAgent || '';
		if (/Telegram|FBAN|FBAV|Instagram|Line\/|Twitter|Snapchat|LinkedInApp|Pinterest|GSA\//i.test(ua)) return true;
		if (/CriOS|FxiOS|EdgiOS/i.test(ua)) return false;
		if (/Safari/i.test(ua)) return false;
		return /AppleWebKit/i.test(ua);
	}

	function prefersReducedMotion() {
		try {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		} catch (e) {
			return false;
		}
	}

	function installWasTapped() {
		return sessionStorage.getItem('demo_install_tapped') === '1';
	}

	function clearInstallAttentionClasses() {
		installBtn.classList.remove(
			'demo-app-install--attention',
			'demo-app-install--attention-ios',
			'demo-app-install--attention-ios-once',
			'demo-app-install--ios-idle'
		);
	}

	function hideInstallTooltip() {
		var tooltip = document.getElementById('demoAppInstallTooltip');
		if (tooltip) tooltip.hidden = true;
	}

	function isBarAnimating() {
		return barAnimState.install || barAnimState.cta;
	}

	function runInstallPulse(cycles, iosPulse) {
		if (installWasTapped() || prefersReducedMotion()) return;
		if (isBarAnimating()) {
			setTimeout(function () { runInstallPulse(cycles, iosPulse); }, MUTEX_DEFER_MS);
			return;
		}
		barAnimState.install = true;
		installBtn.classList.remove(
			'demo-app-install--attention',
			'demo-app-install--attention-ios',
			'demo-app-install--attention-ios-once'
		);
		void installBtn.offsetWidth;
		if (iosPulse) {
			installBtn.classList.add(cycles === 1 ? 'demo-app-install--attention-ios-once' : 'demo-app-install--attention-ios');
		} else {
			installBtn.classList.add('demo-app-install--attention');
		}
		setTimeout(function () {
			installBtn.classList.remove(
				'demo-app-install--attention',
				'demo-app-install--attention-ios',
				'demo-app-install--attention-ios-once'
			);
			barAnimState.install = false;
		}, cycles * INSTALL_PULSE_CYCLE_MS);
	}

	if (isStandaloneShell()) {
		installBtn.style.display = 'none';
		installBtn.setAttribute('aria-hidden', 'true');
		return;
	}

	var hint = document.getElementById('demoAppSafariHint');
	var platform = installBtn.getAttribute('data-platform') || '';
	var isIos = platform === 'ios';
	var reduceMotion = prefersReducedMotion();

	if (isIos && !installWasTapped()) {
		installBtn.classList.add('demo-app-install--ios-idle');
		if (!reduceMotion) {
			setTimeout(function () {
				if (installWasTapped() || sessionStorage.getItem('demo_install_ios_pulse_initial')) return;
				sessionStorage.setItem('demo_install_ios_pulse_initial', '1');
				runInstallPulse(2, true);
			}, INSTALL_IOS_FIRST_MS);
			setTimeout(function () {
				if (installWasTapped() || sessionStorage.getItem('demo_install_ios_pulse_repeat')) return;
				sessionStorage.setItem('demo_install_ios_pulse_repeat', '1');
				runInstallPulse(1, true);
			}, INSTALL_IOS_REPEAT_MS);
		}
		var tooltip = document.getElementById('demoAppInstallTooltip');
		if (tooltip && isIosInAppBrowser() && !sessionStorage.getItem('demo_install_inapp_tooltip_seen')) {
			setTimeout(function () {
				if (installWasTapped()) return;
				tooltip.hidden = false;
				sessionStorage.setItem('demo_install_inapp_tooltip_seen', '1');
				setTimeout(hideInstallTooltip, INSTALL_INAPP_TOOLTIP_DURATION_MS);
			}, INSTALL_INAPP_TOOLTIP_MS);
		}
	} else if (platform === 'android' && !reduceMotion && !sessionStorage.getItem('demo_install_affordance_seen')) {
		installBtn.classList.add('demo-app-install--attention');
		sessionStorage.setItem('demo_install_affordance_seen', '1');
	}

	installBtn.addEventListener('click', function (e) {
		clearInstallAttentionClasses();
		hideInstallTooltip();
		sessionStorage.setItem('demo_install_tapped', '1');
		if (platform === 'ios' && isIosInAppBrowser()) {
			e.preventDefault();
			if (hint) hint.hidden = false;
		}
	});

	if (hint) {
		hint.querySelectorAll('[data-demo-safari-dismiss]').forEach(function (el) {
			el.addEventListener('click', function () { hint.hidden = true; });
		});
	}

	var cta = document.getElementById('demoAppCtaBtn');
	if (cta && !reduceMotion && !sessionStorage.getItem('demo_cta_clicked')) {
		var ctaBurstTimer = null;

		function stopCtaBurst() {
			if (ctaBurstTimer) {
				clearTimeout(ctaBurstTimer);
				ctaBurstTimer = null;
			}
			cta.classList.remove('demo-app-cta-btn--burst');
			barAnimState.cta = false;
		}

		function scheduleNextCtaBurst() {
			if (sessionStorage.getItem('demo_cta_clicked')) return;
			ctaBurstTimer = setTimeout(runCtaBurst, CTA_REPEAT_MS);
		}

		function runCtaBurst() {
			if (sessionStorage.getItem('demo_cta_clicked')) return;
			if (barAnimState.install) {
				ctaBurstTimer = setTimeout(runCtaBurst, MUTEX_DEFER_MS);
				return;
			}
			barAnimState.cta = true;
			cta.classList.remove('demo-app-cta-btn--burst');
			void cta.offsetWidth;
			cta.classList.add('demo-app-cta-btn--burst');
			ctaBurstTimer = setTimeout(function () {
				cta.classList.remove('demo-app-cta-btn--burst');
				barAnimState.cta = false;
				scheduleNextCtaBurst();
			}, CTA_BURST_MS);
		}

		ctaBurstTimer = setTimeout(runCtaBurst, CTA_FIRST_MS);

		var ctaLink = cta.querySelector('a');
		if (ctaLink) {
			ctaLink.addEventListener('click', function () {
				sessionStorage.setItem('demo_cta_clicked', '1');
				stopCtaBurst();
			});
		}
	}
})();
</script>
<?php endif; ?>
