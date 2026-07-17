#!/usr/bin/env php
<?php
/**
 * Audit missing content_i18n translations vs English baseline (Translate Stats rules).
 *
 * Usage:
 *   php scripts/audit_missing_translations_cli.php
 *   php scripts/audit_missing_translations_cli.php --entity=pages
 *   php scripts/audit_missing_translations_cli.php --lang=20,21
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';

$opts = array(
	'entity' => '',
	'lang' => '20,21',
);
foreach (array_slice($argv, 1) as $arg) {
	if (strpos($arg, '--entity=') === 0) {
		$opts['entity'] = trim(substr($arg, 9));
	} elseif (strpos($arg, '--lang=') === 0) {
		$opts['lang'] = trim(substr($arg, 7));
	}
}

if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
	fwrite(STDERR, "Table content_i18n not found.\n");
	exit(1);
}

$entity_map = array(
	'pages' => 'pages',
	'guides' => 'guides',
	'games' => 'games',
	'casino_articles' => 'casino_articles',
	'blog' => 'blog',
);
if ($opts['entity'] !== '') {
	if (!isset($entity_map[$opts['entity']])) {
		fwrite(STDERR, "Unknown entity: {$opts['entity']}\n");
		exit(1);
	}
	$entity_map = array($opts['entity'] => $entity_map[$opts['entity']]);
}

$target_langs = array();
foreach (explode(',', $opts['lang']) as $part) {
	$lid = (int)trim($part);
	if ($lid > 0) {
		$target_langs[] = $lid;
	}
}
if (empty($target_langs)) {
	fwrite(STDERR, "No target languages.\n");
	exit(1);
}

$lang_rows = mysql_select('SELECT id, url, name FROM languages WHERE id IN (' . implode(',', $target_langs) . ')', 'rows') ?: array();
$lang_labels = array();
foreach ($lang_rows as $lr) {
	$lang_labels[(int)$lr['id']] = (string)$lr['url'] . ' (' . (string)$lr['name'] . ')';
}
foreach ($target_langs as $lid) {
	if (!isset($lang_labels[$lid])) {
		$lang_labels[$lid] = 'lang#' . $lid;
	}
}

function audit_table_display_where($table) {
	$cols = mysql_select('SHOW COLUMNS FROM `' . mysql_res($table) . '`', 'rows');
	$has_display = false;
	if ($cols) {
		foreach ($cols as $c) {
			if (isset($c['Field']) && (string)$c['Field'] === 'display') {
				$has_display = true;
				break;
			}
		}
	}
	return $has_display ? ' AND display=1 ' : '';
}

function audit_missing_for_entity($entity, $lang_id) {
	$table = $entity;
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		return null;
	}
	$where = audit_table_display_where($table);
	$r = mysql_select('SELECT COUNT(*) AS c FROM `' . mysql_res($table) . '` WHERE 1 ' . $where, 'row');
	$total = $r && isset($r['c']) ? (int)$r['c'] : 0;
	$counts = array('draft' => 0, 'review' => 0, 'published' => 0);
	$group = mysql_select("
		SELECT status, COUNT(*) AS c
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND lang_id=" . (int)$lang_id . "
		GROUP BY status
	", 'rows') ?: array();
	foreach ($group as $g) {
		$st = isset($g['status']) ? (string)$g['status'] : '';
		if (isset($counts[$st])) {
			$counts[$st] = (int)$g['c'];
		}
	}
	$translated_any = (int)$counts['draft'] + (int)$counts['review'] + (int)$counts['published'];
	return array(
		'total' => $total,
		'missing' => max(0, $total - $translated_any),
		'published' => (int)$counts['published'],
		'draft' => (int)$counts['draft'],
		'review' => (int)$counts['review'],
	);
}

$brand = isset($config['site_brand_name']) ? (string)$config['site_brand_name'] : 'site';
echo "Brand: $brand\n";
echo "DB: " . (isset($config['mysql_database']) ? (string)$config['mysql_database'] : '?') . "\n\n";

foreach ($target_langs as $lang_id) {
	$label = $lang_labels[$lang_id];
	$sum_missing = 0;
	$sum_total = 0;
	echo "=== $label (id=$lang_id) ===\n";
	foreach ($entity_map as $entity => $table) {
		$m = audit_missing_for_entity($entity, $lang_id);
		if ($m === null) {
			continue;
		}
		$sum_missing += $m['missing'];
		$sum_total += $m['total'];
		echo sprintf(
			"  %-18s missing %3d / %3d  (published %d, draft %d, review %d)\n",
			$entity,
			$m['missing'],
			$m['total'],
			$m['published'],
			$m['draft'],
			$m['review']
		);
	}
	echo sprintf("  %-18s missing %3d / %3d\n", 'TOTAL', $sum_missing, $sum_total);

	$home = mysql_select("
		SELECT p.id, p.name, p.title, ci.status, ci.id AS i18n_id
		FROM pages p
		LEFT JOIN content_i18n ci
		  ON ci.entity='pages' AND ci.entity_id=p.id AND ci.lang_id=" . (int)$lang_id . "
		WHERE p.display=1 AND p.module='index' AND (p.url='' OR p.url IS NULL)
		LIMIT 1
	", 'row');
	if ($home) {
		$st = isset($home['status']) ? trim((string)$home['status']) : '';
		if ($st === '' || $st === 'missing') {
			$home_state = 'NEEDS_TRANSLATION';
		} elseif (in_array($st, array('draft', 'review'), true)) {
			$home_state = strtoupper($st);
		} elseif ($st === 'published') {
			$home_state = 'OK (published)';
		} else {
			$home_state = $st;
		}
		echo sprintf(
			"  homepage pages#%d \"%s\" => %s\n",
			(int)$home['id'],
			(string)$home['name'],
			$home_state
		);
	}
	echo "\n";
}
