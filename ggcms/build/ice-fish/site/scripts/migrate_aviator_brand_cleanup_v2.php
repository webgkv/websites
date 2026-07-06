#!/usr/bin/env php
<?php
/**
 * Full Aviator → Ice Fish cleanup on ice-fish.run (DB + dictionary files).
 *
 * CLI: php site/scripts/migrate_aviator_brand_cleanup_v2.php
 *       php site/scripts/migrate_aviator_brand_cleanup_v2.php --force
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'ice-fish.run';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/site_brand.php';
require_once ROOT_DIR . 'functions/icefish_legacy_slugs.php';

$force = in_array('--force', $_SERVER['argv'] ?? array(), true);
$key = 'migration_aviator_brand_cleanup_v2';

if (!$force && @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$done = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row', 0);
	if ($done) {
		echo "Already applied. Use --force to re-run.\n";
		exit(0);
	}
}

if (!mysql_connect_db()) {
	fwrite(STDERR, "DB connection failed.\n");
	exit(1);
}

$report = array(
	'hidden_casino_deleted' => 0,
	'casino_i18n_deleted' => 0,
	'casino_i18n_url_synced' => 0,
	'table_rows_rebranded' => array(),
	'dictionary_files' => 0,
	'redirects_added' => 0,
);

function mig_rebrand_patch(array $row, array $fields) {
	$patch = array();
	foreach ($fields as $f) {
		if (!isset($row[$f]) || trim((string) $row[$f]) === '') {
			continue;
		}
		$orig = (string) $row[$f];
		$new = site_brand_rebrand_value($orig);
		if ($new !== $orig) {
			$patch[$f] = $new;
		}
	}
	return $patch;
}

function mig_rebrand_table($table, array $text_fields, array &$report) {
	if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
		return;
	}
	$cols = array();
	foreach (mysql_select("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`", 'rows', 0) ?: array() as $c) {
		if (!empty($c['Field'])) {
			$cols[(string) $c['Field']] = true;
		}
	}
	$use_fields = array_values(array_filter($text_fields, function ($f) use ($cols) {
		return isset($cols[$f]);
	}));
	if (empty($use_fields)) {
		return;
	}
	$like = mysql_res('%aviator%');
	$where_parts = array();
	foreach ($use_fields as $f) {
		$where_parts[] = "`" . str_replace('`', '``', $f) . "` LIKE '{$like}'";
	}
	if (isset($cols['url'])) {
		$where_parts[] = "`url` LIKE '{$like}'";
	}
	$rows = mysql_select("SELECT * FROM `" . str_replace('`', '``', $table) . "` WHERE " . implode(' OR ', $where_parts), 'rows', 0) ?: array();
	$updated = 0;
	foreach ($rows as $row) {
		$patch = mig_rebrand_patch($row, $use_fields);
		if (isset($cols['url']) && isset($row['url']) && stripos((string) $row['url'], 'aviator') !== false && $table === 'casino_articles') {
			$patch['url'] = icefish_casino_slug_to_icefish($row['url']);
		}
		if (empty($patch)) {
			continue;
		}
		$id = (int) $row['id'];
		mysql_fn('update', $table, $patch, ' AND id=' . $id);
		$updated++;
	}
	$report['table_rows_rebranded'][$table] = $updated;
}

// 1) Remove hidden legacy casino articles (display=0, aviator slugs) + their i18n.
if (@mysql_select("SHOW TABLES LIKE 'casino_articles'", 'num_rows') > 0) {
	$like = mysql_res('%aviator%');
	$hidden = mysql_select("SELECT id, url FROM casino_articles WHERE display=0 AND url LIKE '{$like}'", 'rows', 0) ?: array();
	$hidden_ids = array();
	foreach ($hidden as $h) {
		$hidden_ids[] = (int) $h['id'];
	}
	if (!empty($hidden_ids)) {
		$in = implode(',', $hidden_ids);
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$c = mysql_select("SELECT COUNT(*) AS c FROM content_i18n WHERE entity='casino_articles' AND entity_id IN ({$in})", 'row', 0);
			$report['casino_i18n_deleted'] = (int) ($c['c'] ?? 0);
			mysql_fn('query', "DELETE FROM content_i18n WHERE entity='casino_articles' AND entity_id IN ({$in})");
		}
		mysql_fn('query', "DELETE FROM casino_articles WHERE id IN ({$in})");
		$report['hidden_casino_deleted'] = count($hidden_ids);
		echo 'Deleted hidden casino_articles: ' . count($hidden_ids) . "\n";
	}
}

// 2) Sync active casino content_i18n URLs to canonical casino_articles.url.
if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$rows = mysql_select("
		SELECT ci.id, ci.url AS iurl, ca.url AS curl
		FROM content_i18n ci
		INNER JOIN casino_articles ca ON ca.id = ci.entity_id AND ca.display = 1
		WHERE ci.entity = 'casino_articles'
		  AND (ci.url IS NULL OR ci.url = '' OR ci.url <> ca.url OR ci.url LIKE '%aviator%')
	", 'rows', 0) ?: array();
	foreach ($rows as $r) {
		mysql_fn('update', 'content_i18n', array('url' => (string) $r['curl']), ' AND id=' . (int) $r['id']);
		$report['casino_i18n_url_synced']++;
	}
}

// 3) Rebrand entity tables + content_i18n copy.
$entity_tables = array(
	'pages' => array('name', 'title', 'description', 'text', 'name1', 'title1', 'description1', 'text1'),
	'games' => array('name', 'title', 'description', 'text'),
	'guides' => array('name', 'title', 'description', 'text'),
	'blog' => array('name', 'title', 'description', 'text'),
	'casino_articles' => array('name', 'title', 'description', 'text'),
);
foreach ($entity_tables as $table => $fields) {
	mig_rebrand_table($table, $fields, $report);
}

if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$like = mysql_res('%aviator%');
	$rows = mysql_select("
		SELECT id, entity, entity_id, lang_id, url, name, title, description, content
		FROM content_i18n
		WHERE url LIKE '{$like}'
		   OR content LIKE '{$like}'
		   OR title LIKE '{$like}'
		   OR description LIKE '{$like}'
		   OR name LIKE '{$like}'
	", 'rows', 0) ?: array();
	$ci_updated = 0;
	foreach ($rows as $r) {
		$patch = mig_rebrand_patch($r, array('name', 'title', 'description', 'content'));
		$url = isset($r['url']) ? trim((string) $r['url'], '/') : '';
		if ($url !== '' && stripos($url, 'aviator') !== false) {
			if (($r['entity'] ?? '') === 'casino_articles' && !empty($r['entity_id'])) {
				$base = mysql_select("SELECT url FROM casino_articles WHERE id=" . (int) $r['entity_id'] . " LIMIT 1", 'row', 0);
				if ($base && !empty($base['url'])) {
					$patch['url'] = (string) $base['url'];
				} else {
					$patch['url'] = icefish_casino_slug_to_icefish($url);
				}
			} else {
				$patch['url'] = icefish_normalize_legacy_slug_urls_in_text($url);
				$patch['url'] = trim((string) $patch['url'], '/');
			}
		}
		if (!empty($patch)) {
			mysql_fn('update', 'content_i18n', $patch, ' AND id=' . (int) $r['id']);
			$ci_updated++;
		}
	}
	$report['table_rows_rebranded']['content_i18n'] = $ci_updated;
}

// 4) Dictionary PHP files under files/languages/*/dictionary/
$lang_root = ROOT_DIR . 'files/languages';
if (is_dir($lang_root)) {
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lang_root));
	foreach ($it as $file) {
		if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
			continue;
		}
		$path = $file->getPathname();
		$src = file_get_contents($path);
		if ($src === false || stripos($src, 'aviator') === false) {
			continue;
		}
		$new = preg_replace_callback(
			"/(=>\\s*['\"])((?:[^'\\\\]|\\\\.)*)(['\"])/s",
			function ($m) {
				if (stripos($m[2], 'aviator') === false) {
					return $m[0];
				}
				$inner = stripcslashes($m[2]);
				$rebranded = site_brand_rebrand_value($inner);
				$quote = $m[3];
				$escaped = str_replace($quote === "'" ? "'" : '"', '\\' . $quote, $rebranded);
				return $m[1] . $escaped . $quote;
			},
			$src
		);
		if ($new !== $src) {
			file_put_contents($path, $new);
			$report['dictionary_files']++;
		}
	}
}

// 5) Redirects for legacy casino slugs → landing or mapped article.
if (@mysql_select("SHOW TABLES LIKE 'redirects'", 'num_rows') > 0) {
	$langs = mysql_select("SELECT url FROM languages WHERE display=1", 'rows', 0) ?: array();
	$lang_segs = array('');
	foreach ($langs as $l) {
		$u = trim((string) ($l['url'] ?? ''), '/');
		if ($u !== '') {
			$lang_segs[] = $u;
		}
	}
	$legacy_casino = array(
		'battery-aviator', 'aviator-bet365', 'bet9ja-aviator', 'aviator-betplay', 'betway-aviator',
		'bluechip-aviator', 'dafabet-aviator', 'bolabet-aviator', 'elephant-bet-aviator',
		'4rabet-aviator', '888bets-aviator', 'aviator-golden-crown', 'parimatch-aviator',
		'pin-up-aviator', 'premier-bet-aviator', 'msport-aviator', 'sportybet-aviator',
		'betika-aviator', 'betpawa-aviator', 'pepeta-aviator',
	);
	foreach ($legacy_casino as $old_slug) {
		$candidate = icefish_casino_slug_to_icefish($old_slug);
		foreach ($lang_segs as $lang) {
			$prefix = ($lang !== '' ? '/' . $lang : '');
			$new_path = ($candidate !== '' && icefish_casino_article_exists($candidate))
				? $prefix . '/casinos/' . $candidate . '/'
				: $prefix . '/casinos/';
			$old_path = $prefix . '/casinos/' . $old_slug . '/';
			$exists = mysql_select("SELECT id FROM redirects WHERE old_url='" . mysql_res($old_path) . "' LIMIT 1", 'row', 0);
			if ($exists) {
				continue;
			}
			mysql_fn('insert', 'redirects', array('old_url' => $old_path, 'new_url' => $new_path));
			$report['redirects_added']++;
		}
	}
}

$payload = json_encode(array_merge($report, array('at' => date('c'))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$ex = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row', 0);
	if ($ex && !empty($ex['id'])) {
		mysql_fn('update', 'variables', array('value' => $payload), ' AND id=' . (int) $ex['id']);
	} else {
		mysql_fn('insert', 'variables', array('key' => $key, 'value' => $payload));
	}
}

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
