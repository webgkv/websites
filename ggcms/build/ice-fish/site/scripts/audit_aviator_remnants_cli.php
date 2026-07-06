#!/usr/bin/env php
<?php
if (php_sapi_name() !== 'cli') exit(1);
define('ROOT_DIR', dirname(__DIR__) . '/');
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'ice-fish.run';
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';

$like = '%aviator%';
$esc = mysql_res($like);

function cnt($sql) {
	$r = mysql_select($sql, 'row', 0);
	return (int)($r['c'] ?? 0);
}

$checks = array(
	'pages.url' => "SELECT COUNT(*) c FROM pages WHERE url LIKE '{$esc}'",
	'games.url' => "SELECT COUNT(*) c FROM games WHERE url LIKE '{$esc}'",
	'guides.url' => "SELECT COUNT(*) c FROM guides WHERE url LIKE '{$esc}'",
	'blog.url' => "SELECT COUNT(*) c FROM blog WHERE url LIKE '{$esc}'",
	'casino_articles.url' => "SELECT COUNT(*) c FROM casino_articles WHERE url LIKE '{$esc}'",
	'content_i18n.url' => "SELECT COUNT(*) c FROM content_i18n WHERE url LIKE '{$esc}'",
	'content_i18n.text' => "SELECT COUNT(*) c FROM content_i18n WHERE content LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'pages.text' => "SELECT COUNT(*) c FROM pages WHERE text LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'games.text' => "SELECT COUNT(*) c FROM games WHERE text LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'guides.text' => "SELECT COUNT(*) c FROM guides WHERE text LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'blog.text' => "SELECT COUNT(*) c FROM blog WHERE text LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'casino_articles.text' => "SELECT COUNT(*) c FROM casino_articles WHERE text LIKE '{$esc}' OR title LIKE '{$esc}' OR description LIKE '{$esc}' OR name LIKE '{$esc}'",
	'redirects' => "SELECT COUNT(*) c FROM redirects WHERE old_url LIKE '{$esc}' OR new_url LIKE '{$esc}'",
);

foreach ($checks as $label => $sql) {
	if (strpos($label, '.') !== false) {
		list($table,) = explode('.', $label, 2);
		if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
			echo "{$label}: (no table)\n";
			continue;
		}
	}
	echo "{$label}: " . cnt($sql) . "\n";
}

echo "\ncasino i18n url mismatches (sample):\n";
$rows = mysql_select("
	SELECT ci.entity_id, ci.lang_id, ci.url AS iurl, ca.url AS curl
	FROM content_i18n ci
	INNER JOIN casino_articles ca ON ca.id = ci.entity_id
	WHERE ci.entity='casino_articles' AND ci.url <> ca.url AND ci.url LIKE '{$esc}'
	LIMIT 15
", 'rows', 0) ?: array();
foreach ($rows as $x) {
	echo "  id {$x['entity_id']} lang {$x['lang_id']} i18n={$x['iurl']} base={$x['curl']}\n";
}
