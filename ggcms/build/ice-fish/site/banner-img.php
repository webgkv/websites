<?php
/**
 * Serves cached banner images so the frontend never requests the backend directly.
 * Called as /banner-img.php?id={md5(backend_url)}. Images are cached by advertising_api.
 */
if (!defined('ROOT_DIR')) define('ROOT_DIR', dirname(__FILE__) . '/');

$id = isset($_GET['id']) ? preg_replace('/[^a-f0-9]/', '', strtolower((string)$_GET['id'])) : '';
if ($id === '' || strlen($id) !== 32) {
	header('HTTP/1.1 404 Not Found');
	exit;
}

$cache_dir = rtrim(ROOT_DIR, '/') . '/data/banner-cache';
$file = $cache_dir . '/' . $id;
$ct_file = $file . '.ct';

if (!is_file($file) || !is_readable($file)) {
	header('HTTP/1.1 404 Not Found');
	exit;
}

$ct = 'image/jpeg';
if (is_file($ct_file) && is_readable($ct_file)) {
	$ct = trim((string)file_get_contents($ct_file));
	if ($ct === '') $ct = 'image/jpeg';
}

header('Content-Type: ' . $ct);
header('Cache-Control: public, max-age=86400');
readfile($file);
exit;
