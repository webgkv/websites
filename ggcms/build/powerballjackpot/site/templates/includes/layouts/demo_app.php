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
	$logo_alt = function_exists('site_brand_name') ? site_brand_name() : 'PowerBall Jackpot';
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

require_once ROOT_DIR . 'functions/site_lottery_simulator.php';
$pbj_slides = site_home_lottery_slides();
$pbj_games_cfg = site_home_lottery_games_load();
$pbj_games = site_home_lottery_enabled_games();
$pbj_games_defaults = $pbj_games_cfg['defaults'];
?>
<div class="demo-app-shell">
	<header class="demo-app-bar" role="banner">
		<div class="demo-app-bar-start">
		<a class="demo-app-brand" href="<?= htmlspecialchars($demo_back_url, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>">
			<svg class="demo-app-logo-icon" viewBox="0 0 68 68" width="28" height="28" aria-hidden="true" focusable="false">
				<defs>
					<radialGradient id="demoAppBall" cx="35%" cy="30%" r="65%">
						<stop offset="0%" stop-color="#ff5a66"/>
						<stop offset="55%" stop-color="#d21828"/>
						<stop offset="100%" stop-color="#8c0c18"/>
					</radialGradient>
				</defs>
				<circle cx="34" cy="34" r="30" fill="url(#demoAppBall)"/>
				<ellipse cx="24" cy="22" rx="11" ry="7" fill="#ffffff" opacity="0.35"/>
				<text x="34" y="44" text-anchor="middle" font-family="Arial Black, Arial, Helvetica, sans-serif" font-weight="900" font-size="32" fill="#ffffff" transform="rotate(-10 34 34)">P</text>
			</svg>
		</a>
		<a class="demo-app-icon-btn demo-app-portal" href="<?= htmlspecialchars($demo_portal_url, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
		</a>
<?php if (!empty($_demo_install['enabled'])): ?>
		<a class="demo-app-icon-btn demo-app-install" id="demoAppInstallBtn"
			href="<?= htmlspecialchars((string) $_demo_install['href'], ENT_QUOTES, 'UTF-8') ?>"
			data-platform="<?= htmlspecialchars((string) $_demo_install['platform'], ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars((string) $_demo_install['label'], ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars((string) $_demo_install['label'], ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
		</a>
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
	<div class="demo-app-frame-host demo-app-sim-host" id="demoAppFrameHost">
		<?= site_lottery_sim_render($pbj_slides, $pbj_games, $pbj_games_defaults) ?>
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
/* DEMO_INSTALL_AFFORDANCE — ggcms/DEMO_INSTALL_AFFORDANCE_ROLLBACK.md */
(function () {
	var installBtn = document.getElementById('demoAppInstallBtn');
	if (!installBtn) return;
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
	if (isStandaloneShell()) {
		installBtn.style.display = 'none';
		installBtn.setAttribute('aria-hidden', 'true');
		return;
	}
	if (!prefersReducedMotion() && !sessionStorage.getItem('demo_install_affordance_seen')) {
		installBtn.classList.add('demo-app-install--attention');
		sessionStorage.setItem('demo_install_affordance_seen', '1');
	}
	var hint = document.getElementById('demoAppSafariHint');
	var platform = installBtn.getAttribute('data-platform') || '';
	installBtn.addEventListener('click', function (e) {
		installBtn.classList.remove('demo-app-install--attention');
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
	if (cta && !prefersReducedMotion() && !sessionStorage.getItem('demo_cta_clicked')) {
		var CTA_FIRST_MS = 60000;
		var CTA_REPEAT_MS = 240000;
		var CTA_BURST_MS = 2600;
		var ctaBurstTimer = null;

		function stopCtaBurst() {
			if (ctaBurstTimer) {
				clearTimeout(ctaBurstTimer);
				ctaBurstTimer = null;
			}
			cta.classList.remove('demo-app-cta-btn--burst');
		}

		function scheduleNextCtaBurst() {
			if (sessionStorage.getItem('demo_cta_clicked')) return;
			ctaBurstTimer = setTimeout(runCtaBurst, CTA_REPEAT_MS);
		}

		function runCtaBurst() {
			if (sessionStorage.getItem('demo_cta_clicked')) return;
			cta.classList.remove('demo-app-cta-btn--burst');
			void cta.offsetWidth;
			cta.classList.add('demo-app-cta-btn--burst');
			ctaBurstTimer = setTimeout(scheduleNextCtaBurst, CTA_BURST_MS);
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
