<?php
/**
 * Fullscreen game demo shell for /demo/app/ (static iframe under app chrome).
 */
if (!function_exists('game_demo_official_url')) {
	require_once ROOT_DIR . 'functions/game_demo_embed.php';
}
global $abc, $config;
$demo_iframe_url = game_demo_official_url(
	isset($abc) && is_array($abc) ? $abc : array(),
	isset($config) && is_array($config) ? $config : array()
);
$demo_title = (function_exists('site_brand_name') ? site_brand_name() : 'Game') . ' Demo';
?>
<div class="main__frame main__frame--app-shell">
	<div class="main__frame-app-inner">
		<iframe class="app-demo-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" title="<?= htmlspecialchars($demo_title, ENT_QUOTES, 'UTF-8') ?>" src="<?= htmlspecialchars($demo_iframe_url, ENT_QUOTES, 'UTF-8') ?>" width="300" height="150" frameborder="0" scrolling="no" allow="autoplay; fullscreen" allowfullscreen>Browser not compatible.</iframe>
	</div>
</div>
