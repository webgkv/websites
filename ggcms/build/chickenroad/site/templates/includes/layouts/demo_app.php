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
	$logo_alt = function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road';
}
$try_bonus_label = trim(i18n('common|cta_try_bonus'));
if ($try_bonus_label === '' || strpos($try_bonus_label, 'common|') === 0) {
	$try_bonus_label = 'Try Bonus';
}
$offer_path = (isset($abc['ad_offer_path']) && is_string($abc['ad_offer_path'])) ? trim($abc['ad_offer_path']) : '';
$icon_path = (defined('ROOT_DIR') && file_exists(ROOT_DIR . 'assets/images/egg.svg'))
	? ROOT_DIR . 'assets/images/egg.svg'
	: '';
$logo_v = $icon_path !== '' ? (int) filemtime($icon_path) : time();
?>
<div class="demo-app-shell">
	<header class="demo-app-bar" role="banner">
		<div class="demo-app-bar-start">
		<a class="demo-app-brand" href="<?= htmlspecialchars($demo_back_url, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($logo_alt, ENT_QUOTES, 'UTF-8') ?>">
			<span class="demo-app-logo-icon" style="--demo-egg-mask: url('/assets/images/egg.svg?v=<?= (int) $logo_v ?>');" aria-hidden="true"></span>
		</a>
		<a class="demo-app-icon-btn demo-app-portal" href="<?= htmlspecialchars($demo_portal_url, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($portal_aria, ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i>
		</a>
		</div>
		<div class="demo-app-actions">
			<?php if ($offer_path !== ''): ?>
			<div class="main_btn demo-app-cta-btn">
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
