#!/usr/bin/env php
<?php
/**
 * Apply pages#6 menu label + slug: Strategies at /{lang}/strategies/ (all locales).
 * CLI: php scripts/apply_pages_6_strategies_menu.php
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'admin/modules/_i18n.php';

$json_path = ROOT_DIR . 'files/reference/menu-pages-6-strategies.json';
if (!is_file($json_path)) {
	fwrite(STDERR, "Missing $json_path\n");
	exit(1);
}
$j = json_decode(file_get_contents($json_path), true);
if (!is_array($j) || empty($j['menu_items']) || !is_array($j['menu_items'])) {
	fwrite(STDERR, "Invalid menu JSON\n");
	exit(1);
}

$page_id = isset($j['page_id']) ? (int)$j['page_id'] : 6;
$new_slug = isset($j['url']) ? trim((string)$j['url'], '/') : 'strategies';
if ($new_slug === '') {
	$new_slug = 'strategies';
}
$updated = 0;
$errors = array();

foreach ($j['menu_items'] as $item) {
	if (!is_array($item)) {
		continue;
	}
	$lang_id = isset($item['lang_id']) ? (int)$item['lang_id'] : 0;
	$name = isset($item['name']) ? trim((string)$item['name']) : '';
	$url = isset($item['url']) ? trim((string)$item['url'], '/') : $new_slug;
	if ($lang_id <= 0 || $name === '') {
		continue;
	}
	if ($url === '') {
		$url = $new_slug;
	}
	$existing = admin_i18n_get('pages', $page_id, $lang_id);
	$fields = array('name' => $name, 'url' => $url);
	if ($existing && isset($existing['status'])) {
		$fields['status'] = (string)$existing['status'];
	} elseif ($lang_id !== 1) {
		$fields['status'] = 'published';
	}
	$res = admin_i18n_save('pages', $page_id, $lang_id, $fields);
	if (!empty($res['ok'])) {
		$updated++;
		if ($lang_id === 1) {
			mysql_fn('update', 'pages', array('name' => $name, 'url' => $url), ' AND id=' . $page_id);
		}
	} else {
		$errors[] = 'lang_id=' . $lang_id . ': ' . (isset($res['message']) ? $res['message'] : 'save failed');
	}
}

// Legacy urlN columns on pages (menu cache / fallbacks).
$page_row = mysql_select('SELECT * FROM pages WHERE id=' . $page_id . ' LIMIT 1', 'row');
if ($page_row) {
	$patch = array('url' => $new_slug);
	for ($n = 1; $n <= 20; $n++) {
		$col = 'url' . $n;
		if (!array_key_exists($col, $page_row)) {
			break;
		}
		$val = isset($page_row[$col]) ? trim((string)$page_row[$col], '/') : '';
		if ($val === 'predictor') {
			$patch[$col] = $new_slug;
		}
	}
	if (count($patch) > 1) {
		mysql_fn('update', 'pages', $patch, ' AND id=' . $page_id);
	}
}

echo json_encode(array(
	'ok' => empty($errors),
	'page_id' => $page_id,
	'slug' => $new_slug,
	'updated' => $updated,
	'errors' => $errors,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit(empty($errors) ? 0 : 1);
