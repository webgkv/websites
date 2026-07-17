#!/usr/bin/env php
<?php
/** Verify sw/ln locales for all SEO Monitor entities on prod. */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', getenv('CR_ROOT') ?: (dirname(__DIR__) . '/site/'));
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/string_func.php';
require_once ROOT_DIR . 'functions/seo_monitor.php';

$entities = array(
	'pages' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 26, 27, 28, 29, 33, 34, 35),
	'guides' => array(1, 2, 3, 4, 5, 6, 7, 8),
	'games' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
	'casino_articles' => array(10, 11, 18, 24, 25, 26),
	'blog' => array(1, 2, 3, 4),
	'authors' => array(1, 2),
);

function plain_len($html) {
	return strlen(trim(strip_tags((string)$html)));
}

function check_entity($entity, array $ids) {
	foreach ($ids as $id) {
		$scan = seo_monitor_list_row_issue_scan($entity, $id);
		$pack = seo_monitor_export_cluster_array($entity, $id, 'full');
		if (empty($pack['ok']) || empty($pack['data'])) {
			echo "$entity#$id EXPORT_FAIL\n";
			continue;
		}
		$data = $pack['data'];
		$sw = $ln = null;
		foreach ($data['locales'] as $loc) {
			if ((int)$loc['lang_id'] === 20) {
				$sw = $loc;
			}
			if ((int)$loc['lang_id'] === 21) {
				$ln = $loc;
			}
		}
		$hub = seo_monitor_is_hub_page_entity($entity, $id);
		$swb = plain_len($sw['content'] ?? '');
		$lnb = plain_len($ln['content'] ?? '');
		$issues = isset($scan['issue_count']) ? (int)$scan['issue_count'] : -1;
		printf(
			"%s#%-3d issues=%d sw=%s(%db) ln=%s(%db) hub=%s\n",
			$entity,
			$id,
			$issues,
			$sw['status'] ?? '?',
			$swb,
			$ln['status'] ?? '?',
			$lnb,
			$hub ? 'y' : 'n'
		);
		if ($issues > 0 && !empty($scan['issue_labels'])) {
			echo '  -> ' . implode(', ', $scan['issue_labels']) . "\n";
		}
	}
}

foreach ($entities as $entity => $ids) {
	echo '=== ' . strtoupper($entity) . " ===\n";
	check_entity($entity, $ids);
}
