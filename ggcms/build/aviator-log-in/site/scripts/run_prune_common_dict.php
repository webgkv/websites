#!/usr/bin/env php
<?php
/**
 * Trim files/languages/{id}/dictionary/common.php on target langs to key set of source (canonical) only.
 *
 * CLI:
 *   php run_prune_common_dict.php
 *   php run_prune_common_dict.php --dry-run
 *   php run_prune_common_dict.php --lang=5
 *
 * Web (optional):
 *   /scripts/run_prune_common_dict.php?run=1
 *   /scripts/run_prune_common_dict.php?run=1&dry_run=1&lang=5
 */
$is_cli = (php_sapi_name() === 'cli');

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

$config_file = ROOT_DIR . 'config/config.php';
if (!is_file($config_file)) {
	$msg = "Error: config/config.php not found.\n";
	if ($is_cli) {
		fwrite(STDERR, $msg);
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('error' => 'Config not found'));
	exit(1);
}

if ($is_cli) {
	if (!isset($_SERVER['HTTP_HOST'])) {
		$_SERVER['HTTP_HOST'] = 'localhost';
	}
	if (!isset($_SERVER['REMOTE_ADDR'])) {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'admin/modules/_i18n.php';

$dry_run = false;
$only_lang = 0;
if ($is_cli) {
	foreach ($_SERVER['argv'] ?? array() as $arg) {
		if ($arg === '--dry-run' || $arg === '-n') {
			$dry_run = true;
		} elseif (preg_match('/^--lang=(\d+)$/', $arg, $m)) {
			$only_lang = (int)$m[1];
		}
	}
} else {
	$dry_run = !empty($_GET['dry_run']);
	$only_lang = isset($_GET['lang']) ? (int)$_GET['lang'] : 0;
	if (empty($_GET['run'])) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('hint' => 'Add ?run=1 (optional &dry_run=1, &lang=N)'));
		exit(0);
	}
}

$source = admin_i18n_source_lang_id();
$targets = array();

if ($only_lang > 0) {
	if ($only_lang === $source) {
		$msg = 'Refusing to prune canonical language id ' . $source . "\n";
		if ($is_cli) {
			echo $msg;
			exit(1);
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('ok' => false, 'message' => trim($msg)));
		exit(1);
	}
	$targets[] = $only_lang;
} else {
	$ids = array();
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') {
			$dec = json_decode($row['value'], true);
			if (is_array($dec) && !empty($dec['enabled_lang_ids']) && is_array($dec['enabled_lang_ids'])) {
				$ids = array_values(array_filter(array_map('intval', $dec['enabled_lang_ids'])));
			}
		}
	}
	if (empty($ids)) {
		$rows = mysql_select("SELECT id FROM languages WHERE display=1", 'rows') ?: array();
		foreach ($rows as $r) {
			$ids[] = (int)$r['id'];
		}
	}
	foreach ($ids as $lid) {
		if ($lid > 0 && $lid !== $source) {
			$targets[] = $lid;
		}
	}
	$targets = array_values(array_unique($targets));
}

$results = array();
foreach ($targets as $tid) {
	$res = admin_prune_common_dict_to_canonical($tid, $source, $dry_run);
	$results[] = array(
		'lang_id' => $tid,
		'ok' => !empty($res['ok']),
		'message' => isset($res['message']) ? $res['message'] : '',
		'removed' => isset($res['removed']) ? (int)$res['removed'] : 0,
		'removed_keys' => isset($res['removed_keys']) ? $res['removed_keys'] : array(),
	);
}

if ($is_cli) {
	echo 'Source (canonical) lang id: ' . $source . ($dry_run ? " [dry-run]\n" : "\n");
	foreach ($results as $r) {
		echo 'Lang ' . $r['lang_id'] . ': ' . $r['message'] . "\n";
		if ($dry_run && !empty($r['removed_keys'])) {
			echo '  Would remove: ' . implode(', ', $r['removed_keys']) . "\n";
		}
	}
	exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('source_lang_id' => $source, 'dry_run' => $dry_run, 'results' => $results), JSON_UNESCAPED_UNICODE);
exit(0);
