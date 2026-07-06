<?php
/**
 * Apply target pages structure (donor + blog + casinos from docs §3.4.1, §3.4.2).
 * Run: /scripts/bd_pages_upgrade.php?push=1
 * Optional: &dry_run=1 to only output planned changes (no DB write).
 * Optional: &file=path/to/pulled.json to merge with existing data (match by url); then target structure is applied on top.
 *
 * Target first-level: index, blog, casinos, demo, download, predictor, games, guides (+ guide children: analysis, bonus, how-to-win, signals).
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_GET['push']) || $_GET['push'] != '1') {
	echo json_encode(array('error' => 'Use ?push=1 to apply pages structure'));
	exit;
}

$dry_run = !empty($_GET['dry_run']);

// Target tree: url => array('name'=>, 'module'=>, 'parent_url'=>'' for root)
// Order of keys = display order among siblings
$target_tree = array(
	// level 1
	''                    => array('name' => 'Home', 'module' => 'index', 'parent_url' => null),
	'blog'                => array('name' => 'Blog', 'module' => 'blog', 'parent_url' => ''),
	'casinos'             => array('name' => 'Casinos', 'module' => 'casinos', 'parent_url' => ''),
	'demo'                => array('name' => 'Demo', 'module' => 'pages', 'parent_url' => ''),
	'download'            => array('name' => 'Download', 'module' => 'pages', 'parent_url' => ''),
	'predictor'            => array('name' => 'Predictor', 'module' => 'pages', 'parent_url' => ''),
	'games'               => array('name' => 'Games', 'module' => 'pages', 'parent_url' => ''),
	'guides'              => array('name' => 'Guides', 'module' => 'pages', 'parent_url' => ''),
	// guides children (donor URLs)
	'game-analysis'       => array('name' => 'Analysis', 'module' => 'pages', 'parent_url' => 'guides'),
	'how-to-win'          => array('name' => 'How to Win', 'module' => 'pages', 'parent_url' => 'guides'),
	'signals'             => array('name' => 'Signals', 'module' => 'pages', 'parent_url' => 'guides'),
	'bonus'               => array('name' => 'Bonus', 'module' => 'pages', 'parent_url' => 'guides'),
	'about-us'            => array('name' => 'About Us', 'module' => 'pages', 'parent_url' => ''),
	'terms-and-conditions'=> array('name' => 'Terms', 'module' => 'pages', 'parent_url' => ''),
	'privacy-policy'      => array('name' => 'Privacy', 'module' => 'pages', 'parent_url' => ''),
	'responsible-gambling'=> array('name' => 'Responsible Gambling', 'module' => 'pages', 'parent_url' => ''),
);

/**
 * Build flat list in DFS order from target_tree. Root = parent_url '' or null → use __root__.
 */
function target_flat($target_tree) {
	$by_parent = array();
	foreach ($target_tree as $url => $info) {
		$p = isset($info['parent_url']) ? $info['parent_url'] : '';
		if ($p === null || $p === '') $p = '__root__';
		if (!isset($by_parent[$p])) $by_parent[$p] = array();
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

/**
 * Assign left_key, right_key to flat list (must be in DFS order).
 */
function assign_nested_keys(&$flat) {
	$key = 0;
	$stack = array();
	foreach ($flat as $i => &$row) {
		while (!empty($stack) && $row['level'] <= $flat[$stack[count($stack)-1]]['level']) {
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

$flat = target_flat($target_tree);
assign_nested_keys($flat);

// Resolve parent_id: we need id for each url
$url_to_id = array();
$current = mysql_select("SELECT id, url, name, module, parent, left_key, right_key, level FROM pages ORDER BY left_key", 'rows');
if (!$current) $current = array();
foreach ($current as $r) {
	$u = isset($r['url']) ? $r['url'] : (isset($r['url1']) ? $r['url1'] : '');
	$url_to_id[$u] = $r['id'];
}
// Default url column might be url1 in multilingual
$has_url1 = false;
if (!empty($current)) {
	$first = $current[0];
	$has_url1 = array_key_exists('url1', $first);
}

$report = array('dry_run' => $dry_run, 'planned' => array(), 'errors' => array());

foreach ($flat as $i => $row) {
	$parent_url = $row['parent_url'];
	$parent_id = ($parent_url === '' || $parent_url === null) ? 0 : (isset($url_to_id[$parent_url]) ? $url_to_id[$parent_url] : 0);
	$flat[$i]['parent_id'] = $parent_id;
}

// Build url_to_id for target (we'll assign new ids for new rows)
$max_id = 0;
if ($current) {
	foreach ($current as $r) {
		if ($r['id'] > $max_id) $max_id = $r['id'];
	}
}
foreach ($flat as $row) {
	$u = $row['url'];
	$existing_id = null;
	foreach ($current as $r) {
		$ru = isset($r['url']) ? $r['url'] : (isset($r['url1']) ? $r['url1'] : '');
		if ($ru === $u) { $existing_id = $r['id']; break; }
	}
	if ($existing_id !== null) {
		$url_to_id[$u] = $existing_id;
	} else {
		$max_id++;
		$url_to_id[$u] = $max_id;
	}
}

foreach ($flat as $i => $row) {
	$parent_url = $row['parent_url'];
	$parent_id = ($parent_url === '' || $parent_url === null) ? 0 : (isset($url_to_id[$parent_url]) ? $url_to_id[$parent_url] : 0);
	$flat[$i]['parent_id'] = $parent_id;
	$flat[$i]['id'] = $url_to_id[$row['url']];
}

if ($dry_run) {
	$report['target_flat'] = $flat;
	$report['url_to_id'] = $url_to_id;
	echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// Apply: UPDATE or INSERT
$url_col = $has_url1 ? 'url1' : 'url';
$name_col = $has_url1 ? 'name1' : 'name';
$ok = mysql_transaction('start');
if (!$ok) {
	$report['errors'][] = 'Transaction start failed';
	echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}
foreach ($flat as $row) {
	$id = $row['id'];
	$exists = mysql_select("SELECT id FROM pages WHERE id = " . intval($id), 'row');
	$set = array(
		'left_key'  => $row['left_key'],
		'right_key' => $row['right_key'],
		'level'     => $row['level'],
		'parent'    => $row['parent_id'],
		'module'    => $row['module'],
		'display'   => 1,
		'menu'      => 1,
		'menu2'     => 0,
	);
	$set[$name_col] = $row['name'];
	$set[$url_col]  = $row['url'];
	if ($exists) {
		mysql_fn('update', 'pages', array_merge(array('id' => $id), $set));
		$report['planned'][] = array('action' => 'update', 'id' => $id, 'url' => $row['url']);
	} else {
		$set['id'] = $id;
		mysql_fn('insert', 'pages', $set);
		$report['planned'][] = array('action' => 'insert', 'id' => $id, 'url' => $row['url']);
	}
}
mysql_transaction('commit');

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
