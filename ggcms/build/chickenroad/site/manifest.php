<?php
/**
 * Web App Manifest — start_url follows the page where install was initiated (query ?start=).
 * Icons stay fixed; scope is site root.
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: private, max-age=300');

$raw = isset($_GET['start']) ? (string) $_GET['start'] : '';
$path = rawurldecode($raw);
if ($path === '' || $path[0] !== '/') {
	$path = '/';
}
$path = preg_replace('#/+#', '/', $path);
if (strpos($path, "\0") !== false) {
	$path = '/';
}
// Strip path traversal segments (allow UTF-8 slugs; disallow .. and slashes inside a segment)
$segs = array();
foreach (explode('/', trim($path, '/')) as $seg) {
	if ($seg === '' || $seg === '.' || $seg === '..') {
		continue;
	}
	if (strpos($seg, '/') !== false || strpos($seg, '\\') !== false) {
		continue;
	}
	$segs[] = $seg;
}
$path = '/' . implode('/', $segs);
if ($path !== '/') {
	$path .= (substr($path, -1) === '/' ? '' : '/');
}

$start_url = $path;
$start_url .= (strpos($start_url, '?') !== false ? '&' : '?') . 'utm_source=pwa';

$icon_v = '';
$icon_180 = __DIR__ . '/assets/images/pwa-icon-180.png';
if (is_file($icon_180)) {
	$icon_v = '?v=' . (int) filemtime($icon_180);
}
$icons = array(
	array('src' => '/assets/images/pwa-icon-180.png' . $icon_v, 'sizes' => '180x180', 'type' => 'image/png', 'purpose' => 'any'),
	array('src' => '/assets/images/pwa-icon-192.png' . $icon_v, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'),
	array('src' => '/assets/images/pwa-icon-512.png' . $icon_v, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'),
);

$manifest = array(
	'id' => $path,
	'name' => 'Chicken Road',
	'short_name' => 'Chicken Road',
	'description' => 'Chicken Road game guides, casinos, and resources.',
	'start_url' => $start_url,
	'scope' => '/',
	'display' => 'standalone',
	'orientation' => 'any',
	'background_color' => '#2c2a33',
	'theme_color' => '#2c2a33',
	'icons' => $icons,
);

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
