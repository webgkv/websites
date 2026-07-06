<?php
/**
 * CLI: seed pages tree for Chicken Road (after prepare_chickenroad_db import).
 * Usage: php site/scripts/seed_chickenroad_pages_cli.php
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';

if (!mysql_connect_db()) {
	fwrite(STDERR, "DB connection failed. Check site/config/config.php\n");
	exit(1);
}

$target_tree = array(
	''                     => array('name' => 'Home', 'module' => 'index', 'parent_url' => null),
	'blog'                 => array('name' => 'Blog', 'module' => 'blog', 'parent_url' => ''),
	'casinos'              => array('name' => 'Casinos', 'module' => 'casinos', 'parent_url' => ''),
	'demo'                 => array('name' => 'Demo', 'module' => 'pages', 'parent_url' => ''),
	'download'             => array('name' => 'Download', 'module' => 'pages', 'parent_url' => ''),
	'predictor'            => array('name' => 'Predictor', 'module' => 'pages', 'parent_url' => ''),
	'games'                => array('name' => 'Games', 'module' => 'pages', 'parent_url' => ''),
	'guides'               => array('name' => 'Guides', 'module' => 'pages', 'parent_url' => ''),
	'game-analysis'        => array('name' => 'Analysis', 'module' => 'pages', 'parent_url' => 'guides'),
	'how-to-win'           => array('name' => 'How to Win', 'module' => 'pages', 'parent_url' => 'guides'),
	'signals'              => array('name' => 'Signals', 'module' => 'pages', 'parent_url' => 'guides'),
	'bonus'                => array('name' => 'Bonus', 'module' => 'pages', 'parent_url' => 'guides'),
	'about-us'             => array('name' => 'About Us', 'module' => 'pages', 'parent_url' => ''),
	'terms-and-conditions' => array('name' => 'Terms', 'module' => 'pages', 'parent_url' => ''),
	'privacy-policy'       => array('name' => 'Privacy', 'module' => 'pages', 'parent_url' => ''),
	'responsible-gambling' => array('name' => 'Responsible Gambling', 'module' => 'pages', 'parent_url' => ''),
	'authors'                => array('name' => 'Authors', 'module' => 'authors', 'parent_url' => ''),
);

function cr_target_flat($target_tree) {
	$by_parent = array();
	foreach ($target_tree as $url => $info) {
		$p = isset($info['parent_url']) ? $info['parent_url'] : '';
		if ($p === null || $p === '') {
			$p = '__root__';
		}
		if (!isset($by_parent[$p])) {
			$by_parent[$p] = array();
		}
		$by_parent[$p][] = $url;
	}
	$flat = array();
	$dfs = function ($parent_key, $level) use (&$dfs, &$flat, $by_parent, $target_tree) {
		$children = isset($by_parent[$parent_key]) ? $by_parent[$parent_key] : array();
		foreach ($children as $url) {
			$flat[] = array(
				'url'        => $url,
				'name'       => $target_tree[$url]['name'],
				'module'     => $target_tree[$url]['module'],
				'parent_url' => $parent_key === '__root__' ? '' : $parent_key,
				'level'      => $level,
			);
			$dfs($url, $level + 1);
		}
	};
	$dfs('__root__', 1);
	return $flat;
}

function cr_assign_nested_keys(&$flat) {
	$key = 0;
	$stack = array();
	foreach ($flat as $i => &$row) {
		while (!empty($stack) && $row['level'] <= $flat[$stack[count($stack) - 1]]['level']) {
			$idx = array_pop($stack);
			$flat[$idx]['right_key'] = ++$key;
		}
		$row['left_key'] = ++$key;
		$stack[] = $i;
	}
	while (!empty($stack)) {
		$idx = array_pop($stack);
		$flat[$idx]['right_key'] = ++$key;
	}
}

$flat = cr_target_flat($target_tree);
cr_assign_nested_keys($flat);

$url_to_id = array();
$max_id = 0;
$row = mysql_select('SELECT MAX(id) AS m FROM pages', 'row');
if ($row && isset($row['m'])) {
	$max_id = (int) $row['m'];
}

foreach ($flat as $i => $row) {
	$u = $row['url'];
	$existing = mysql_select(
		"SELECT id FROM pages WHERE url = '" . mysql_res($u) . "' LIMIT 1",
		'row'
	);
	if ($existing && !empty($existing['id'])) {
		$url_to_id[$u] = (int) $existing['id'];
	} else {
		$max_id++;
		$url_to_id[$u] = $max_id;
	}
}

$now = date('Y-m-d H:i:s');
$n = 0;
foreach ($flat as $row) {
	$id = $url_to_id[$row['url']];
	$parent_id = ($row['parent_url'] === '') ? 0 : $url_to_id[$row['parent_url']];
	$exists = mysql_select('SELECT id FROM pages WHERE id = ' . (int) $id, 'row');
	$data = array(
		'left_key'   => $row['left_key'],
		'right_key'  => $row['right_key'],
		'level'      => $row['level'],
		'parent'     => $parent_id,
		'module'     => $row['module'],
		'display'    => 1,
		'menu'       => 1,
		'menu2'      => 0,
		'name'       => $row['name'],
		'url'        => $row['url'],
		'language'   => 1,
		'created_at' => $now,
		'updated_at' => $now,
	);
	if ($exists) {
		mysql_fn('update', 'pages', array_merge(array('id' => $id), $data));
	} else {
		$data['id'] = $id;
		mysql_fn('insert', 'pages', $data);
	}
	$n++;
}

echo "Pages seeded/updated: {$n} rows\n";
