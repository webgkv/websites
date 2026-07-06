<?php
/**
 * Standalone document for layout demo_app: minimal chrome, no main site header/footer/scripts (exit popup, etc.).
 * Included from _template.php after title/description globals are ready.
 */
global $abc, $config;
$_desc = isset($abc['page']['description']) ? htmlspecialchars(strip_tags((string) $abc['page']['description']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
$_canon = '';
if (function_exists('aviator_seo_public_origin')) {
	$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
	$_canon = aviator_seo_public_origin() . preg_replace('#/+#', '/', $path === '' ? '/' : $path);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) $_site_html_lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<title><?= $_aviator_doc_title_esc ?><?= $_aviator_doc_suffix_esc ?></title>
	<?php if ($_desc !== ''): ?>
	<meta name="description" content="<?= $_desc ?>">
	<?php endif; ?>
	<?php if ($_canon !== ''): ?>
	<link rel="canonical" href="<?= htmlspecialchars($_canon, ENT_QUOTES, 'UTF-8') ?>">
	<?php endif; ?>
<?php
$_seo_robots = function_exists('site_seo_robots_meta_content') ? site_seo_robots_meta_content() : '';
if ($_seo_robots !== '') {
?>
	<meta name="robots" content="<?= htmlspecialchars($_seo_robots, ENT_QUOTES, 'UTF-8') ?>">
	<meta name="googlebot" content="<?= htmlspecialchars($_seo_robots, ENT_QUOTES, 'UTF-8') ?>">
<?php } ?>
	<meta name="theme-color" content="#2c2a33">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars(function_exists('site_brand_name') ? site_brand_name() : 'PowerBall Jackpot', ENT_QUOTES, 'UTF-8') ?>">
	<?php
	$_pwa180 = $r . 'assets/images/pwa-icon-180.png';
	$_pwa192 = $r . 'assets/images/pwa-icon-192.png';
	$_atRoot = $r . 'apple-touch-icon.png';
	$_favicon = $r . 'assets/images/favicon.png';
	?>
	<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/pwa-icon-180.png?v=<?= htmlspecialchars($getV($_pwa180), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="apple-touch-icon" sizes="192x192" href="/assets/images/pwa-icon-192.png?v=<?= htmlspecialchars($getV($_pwa192), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=<?= htmlspecialchars($getV($_atRoot), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="manifest" href="<?= htmlspecialchars(function_exists('aviator_pwa_manifest_href') ? aviator_pwa_manifest_href($getV, $r) : ('/manifest.php?start=%2F&v=' . $getV($r . 'manifest.php')), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="icon" type="image/png" href="/assets/images/favicon.png?v=<?= htmlspecialchars($getV($_favicon), ENT_QUOTES, 'UTF-8') ?>">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="/assets/css/style.css?v=<?= htmlspecialchars($getV($r . 'assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="stylesheet" href="/assets/css/lottery-buttons.css?v=<?= htmlspecialchars($getV($r . 'assets/css/lottery-buttons.css'), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="stylesheet" href="/assets/css/home-lottery.css?v=<?= htmlspecialchars($getV($r . 'assets/css/home-lottery.css'), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="stylesheet" href="/assets/css/lottery-simulator.css?v=<?= htmlspecialchars($getV($r . 'assets/css/lottery-simulator.css'), ENT_QUOTES, 'UTF-8') ?>">
	<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= htmlspecialchars($getV($r . 'assets/css/responsive.css'), ENT_QUOTES, 'UTF-8') ?>">
	<style>
		html, body { height: 100%; margin: 0; overflow: hidden; background: #2c2a33; }
		/* Override main site body texture for this shell */
		body.demo-app-doc { font-family: 'Poppins', system-ui, sans-serif; background: #2c2a33 !important; background-image: none !important; }
		.demo-app-shell { display: flex; flex-direction: column; height: 100dvh; min-height: 100vh; max-height: 100dvh; padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left); box-sizing: border-box; }
		.demo-app-bar { flex: 0 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 4px 8px; min-height: 0; background: #2c2a33; border-bottom: 1px solid rgba(255,255,255,.08); }
		.demo-app-bar-start { display: flex; align-items: center; gap: 4px; flex: 0 1 auto; min-width: 0; }
		.demo-app-portal { flex-shrink: 0; }
		.demo-app-brand { display: flex; align-items: center; flex: 0 0 auto; opacity: .95; }
		.demo-app-logo-icon {
			display: block;
			flex-shrink: 0;
			width: 28px;
			height: 28px;
		}
		.demo-app-actions { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; margin-left: auto; }
		/* Compact CTA in demo chrome — uses site .main_btn (yellow gradient); only scale down */
		.demo-app-cta-btn.main_btn {
			flex-shrink: 0;
		}
		.demo-app-cta-btn.main_btn a {
			padding: 8px 18px;
			font-size: 12px;
			font-weight: 900;
			line-height: 1.25;
			border-radius: 14px;
		}
		.demo-app-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.06); color: #e8eef5; text-decoration: none; cursor: pointer; transition: background .15s ease; }
		.demo-app-icon-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
		.demo-app-close { }
		/* Game area: fill all space below bar (desktop + portrait mobile); no 16:9 letterbox */
		.demo-app-frame-host { flex: 1 1 auto; min-height: 0; position: relative; background: #051423; display: flex; flex-direction: column; overflow: hidden; padding: 0; }
		.demo-app-frame-host.demo-app-sim-host { overflow: auto; -webkit-overflow-scrolling: touch; }
		.demo-app-frame-host .main__frame--app-shell { flex: 1; min-height: 0; width: 100%; max-width: none; margin: 0; display: flex; flex-direction: column; }
		.demo-app-frame-host .main__frame-app-inner { position: relative; flex: 1; min-height: 0; width: 100%; -webkit-overflow-scrolling: touch; }
		.demo-app-frame-host .main__frame-app-inner iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; display: block; }
		.demo-app-frame-host:fullscreen { width: 100%; height: 100%; min-height: 100vh; min-height: 100dvh; max-height: 100dvh; background: #000; }
		.demo-app-frame-host:-webkit-full-screen { width: 100%; height: 100%; min-height: 100vh; min-height: 100dvh; background: #000; }
		.demo-app-frame-host:fullscreen .main__frame--app-shell,
		.demo-app-frame-host:-webkit-full-screen .main__frame--app-shell { height: 100%; flex: 1 1 auto; }
		.demo-app-missing { margin: 0; color: #9aa4b2 !important; }
		@media (max-width: 768px) {
			.demo-app-fs-btn { display: none !important; }
		}
	</style>
	<?php /* No service worker on /demo/app/: avoid interfering with third-party game iframe. */ ?>
	<?php if (!empty($abc['counters_head'])) { foreach ($abc['counters_head'] as $_counter) { echo $_counter . "\n\t"; } } ?>
</head>
<body class="demo-app-doc">
<?php if (!empty($abc['counters_body'])) { foreach ($abc['counters_body'] as $_counter) { echo $_counter . "\n"; } } ?>
<?= html_render('layouts/demo_app') ?>
<?php if (!empty($abc['counters_footer'])) { foreach ($abc['counters_footer'] as $_counter) { echo $_counter . "\n"; } } ?>
<script src="/assets/js/lottery-sim-core.js?v=<?= htmlspecialchars($getV($r . 'assets/js/lottery-sim-core.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/assets/js/home-lucky-picker.js?v=<?= htmlspecialchars($getV($r . 'assets/js/home-lucky-picker.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="/assets/js/lottery-sim-ui.js?v=<?= htmlspecialchars($getV($r . 'assets/js/lottery-sim-ui.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
