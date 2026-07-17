#!/usr/bin/env php
<?php
/** Verify fr (3) and ar (11) locales for all SEO Monitor entities on prod. */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$cr_root = getenv('CR_ROOT');
if ($cr_root) {
	$root = rtrim($cr_root, '/') . '/';
} elseif (is_file(dirname(__DIR__) . '/config/config.php')) {
	$root = dirname(__DIR__) . '/';
} else {
	$root = dirname(__DIR__) . '/site/';
}
define('ROOT_DIR', $root);
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/string_func.php';
require_once ROOT_DIR . 'functions/seo_monitor.php';

$hub_pages = array(2, 3, 7, 8, 9, 10, 11, 12, 35);

$entities = array(
	'pages' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35),
	'guides' => array(1, 2, 3, 4, 5, 6, 7, 8),
	'games' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
	'casino_articles' => array(10, 11, 18, 24, 25, 26),
	'blog' => array(1, 2, 3, 4),
	'authors' => array(1, 2),
);

function verify_plain_len($html) {
	return strlen(trim(strip_tags((string)$html)));
}

function verify_loc_by_id(array $data, $lang_id) {
	foreach ((array)($data['locales'] ?? array()) as $loc) {
		if ((int)($loc['lang_id'] ?? 0) === (int)$lang_id) {
			return $loc;
		}
	}
	return null;
}

function verify_locale_issues(array $scan, $lang_id) {
	foreach ((array)($scan['locales'] ?? array()) as $lr) {
		if ((int)($lr['lang_id'] ?? 0) === (int)$lang_id) {
			return (int)($lr['issue_count'] ?? 0);
		}
	}
	return -1;
}

$total = 0;
$bad = 0;
$bad_rows = array();

foreach ($entities as $entity => $ids) {
	echo '=== ' . strtoupper($entity) . " ===\n";
	foreach ($ids as $id) {
		$total++;
		$scan = seo_monitor_list_row_issue_scan($entity, $id);
		$pack = seo_monitor_export_cluster_array($entity, $id, 'full');
		if (empty($pack['ok']) || empty($pack['data'])) {
			echo "$entity#$id EXPORT_FAIL\n";
			$bad++;
			$bad_rows[] = "$entity#$id export_fail";
			continue;
		}
		$data = $pack['data'];
		$fr = verify_loc_by_id($data, 3);
		$ar = verify_loc_by_id($data, 11);
		$row_issues = (int)($scan['issue_count'] ?? -1);
		$fr_issues = verify_locale_issues($scan, 3);
		$ar_issues = verify_locale_issues($scan, 11);
		$fr_status = $fr['status'] ?? '?';
		$ar_status = $ar['status'] ?? '?';
		$fr_len = verify_plain_len($fr['content'] ?? '');
		$ar_len = verify_plain_len($ar['content'] ?? '');
		$en = verify_loc_by_id($data, 1);
		$en_len = verify_plain_len($en['content'] ?? '');
		$is_hub_page = ($entity === 'pages' && in_array((int)$id, $hub_pages, true) && $en_len === 0);
		$is_bad = ($row_issues > 0 || $fr_issues > 0 || $ar_issues > 0
			|| $fr_status !== 'published' || $ar_status !== 'published');
		if ($is_hub_page) {
			$fr_title = trim((string)($fr['title'] ?? ''));
			$ar_title = trim((string)($ar['title'] ?? ''));
			$is_bad = $is_bad || $fr_title === '' || $ar_title === '';
		} elseif ($entity === 'authors') {
			$is_bad = $is_bad || $fr_len < 20 || $ar_len < 20;
		} else {
			$is_bad = $is_bad || $fr_len < 1 || $ar_len < 1;
		}
		if ($is_bad) {
			$bad++;
			$bad_rows[] = "$entity#$id";
		}
		printf(
			"%s#%-3d row=%d fr=%s(%db,i=%d) ar=%s(%db,i=%d)%s\n",
			$entity,
			$id,
			$row_issues,
			$fr_status,
			$fr_len,
			$fr_issues,
			$ar_status,
			$ar_len,
			$ar_issues,
			$is_bad ? ' BAD' : ''
		);
		if ($is_bad && !empty($scan['issue_labels'])) {
			echo '  row_labels: ' . implode(', ', (array)$scan['issue_labels']) . "\n";
		}
	}
}

echo "\nSUMMARY clusters=$total bad=$bad ok=" . ($total - $bad) . "\n";
if ($bad_rows) {
	echo "BAD: " . implode(', ', $bad_rows) . "\n";
}
