<?php
/**
 * Spribe official demo for /demo/app/ only: iframe fills space under the chrome (not 16:9 letterbox).
 * Spribe does not publish a public responsive-embed spec; this follows common full-viewport iframe practice.
 * /en/demo/ body in CMS can keep the classic 16:9 .main__frame markup separately.
 */
if (!function_exists('aviator_spribe_official_demo_url')) {
	require_once ROOT_DIR . 'functions/aviator_demo_embed.php';
}
global $abc, $config;
$demo_iframe_url = aviator_spribe_official_demo_url(
	isset($abc) && is_array($abc) ? $abc : array(),
	isset($config) && is_array($config) ? $config : array()
);
?>
<div class="main__frame main__frame--app-shell">
	<div class="main__frame-app-inner">
		<iframe class="spribe-demo-iframe chickenroad-demo-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" title="PowerBall Jackpot Demo" src="<?= htmlspecialchars($demo_iframe_url, ENT_QUOTES, 'UTF-8') ?>" width="300" height="150" frameborder="0" scrolling="no" allow="autoplay; fullscreen" allowfullscreen>Browser not compatible.</iframe>
	</div>
</div>
