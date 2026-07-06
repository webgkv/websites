#!/usr/bin/env php
<?php
/**
 * Evergreen blog slugs + {year} in name/title/description for year-sensitive posts.
 *
 *   php scripts/migrate_blog_year_slugs.php           # dry-run
 *   php scripts/migrate_blog_year_slugs.php --apply
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$apply = in_array('--apply', $argv, true);

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';

$slug_map = array(
	'is-ice-fish-legit-or-a-scam-2026-honest-verdict' => 'is-ice-fish-legit-or-a-scam-honest-verdict',
	'games-that-pay-real-money-what-actually-pays-in-2026' => 'games-that-pay-real-money-what-actually-pays',
	'games-that-pay-real-money-instantly-2026' => 'games-that-pay-real-money-instantly',
);

$meta_fields = array('name', 'title', 'description');

function migrate_blog_slug_tail($url) {
	$url = trim((string) $url, '/');
	if ($url === '') {
		return '';
	}
	$parts = explode('/', $url);
	return (string) end($parts);
}

function migrate_blog_year_macro_meta($text) {
	if (!is_string($text) || $text === '' || strpos($text, '2026') === false) {
		return $text;
	}
	return str_replace('2026', '{year}', $text);
}

$changed = 0;

$blog_rows = mysql_select('SELECT id, url, name, title, description FROM blog', 'rows') ?: array();
foreach ($blog_rows as $row) {
	$id = (int) ($row['id'] ?? 0);
	if ($id <= 0) {
		continue;
	}
	$tail = migrate_blog_slug_tail($row['url'] ?? '');
	$new_slug = isset($slug_map[$tail]) ? $slug_map[$tail] : null;
	$blog_patch = array();
	if ($new_slug !== null && $tail !== $new_slug) {
		$blog_patch['url'] = $new_slug;
	}
	foreach ($meta_fields as $f) {
		if (!isset($row[$f]) || !is_string($row[$f])) {
			continue;
		}
		$next = migrate_blog_year_macro_meta($row[$f]);
		if ($next !== $row[$f]) {
			$blog_patch[$f] = $next;
		}
	}
	if ($blog_patch === array()) {
		continue;
	}
	echo "blog #{$id}: " . json_encode($blog_patch, JSON_UNESCAPED_UNICODE) . "\n";
	if ($apply) {
		mysql_fn('update', 'blog', $blog_patch, ' AND id=' . $id);
	}
	$changed++;
}

$ci_rows = mysql_select("SELECT id, entity_id, lang_id, url, name, title, description FROM content_i18n WHERE entity='blog'", 'rows') ?: array();
foreach ($ci_rows as $row) {
	$ci_id = (int) ($row['id'] ?? 0);
	if ($ci_id <= 0) {
		continue;
	}
	$tail = migrate_blog_slug_tail($row['url'] ?? '');
	$new_slug = isset($slug_map[$tail]) ? $slug_map[$tail] : null;
	$ci_patch = array();
	if ($new_slug !== null && $tail !== $new_slug) {
		$ci_patch['url'] = $new_slug;
	}
	foreach ($meta_fields as $f) {
		if (!isset($row[$f]) || !is_string($row[$f])) {
			continue;
		}
		$next = migrate_blog_year_macro_meta($row[$f]);
		if ($next !== $row[$f]) {
			$ci_patch[$f] = $next;
		}
	}
	if ($ci_patch === array()) {
		continue;
	}
	echo "content_i18n #{$ci_id} (blog {$row['entity_id']}, lang {$row['lang_id']}): "
		. json_encode($ci_patch, JSON_UNESCAPED_UNICODE) . "\n";
	if ($apply) {
		mysql_fn('update', 'content_i18n', $ci_patch, ' AND id=' . $ci_id);
	}
	$changed++;
}

echo ($apply ? 'Applied' : 'Dry-run') . ": {$changed} row patch(es).\n";
if (!$apply && $changed > 0) {
	echo "Re-run with --apply to write.\n";
}
