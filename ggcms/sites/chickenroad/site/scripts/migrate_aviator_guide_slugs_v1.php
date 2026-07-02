#!/usr/bin/env php
<?php
/**
 * Rename legacy Aviator guide page slugs on chickenroad.run and rebrand stored copy.
 *
 * CLI: php site/scripts/migrate_aviator_guide_slugs_v1.php
 *       php site/scripts/migrate_aviator_guide_slugs_v1.php --force
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'chickenroad.run';
$_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/site_brand.php';
require_once ROOT_DIR . 'functions/chickenroad_legacy_slugs.php';

$force = in_array('--force', $_SERVER['argv'] ?? array(), true);

if (!$force && @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$done = mysql_select("SELECT id FROM variables WHERE `key`='migration_aviator_guide_slugs_v1' LIMIT 1", 'row');
	if ($done) {
		echo "Already applied. Use --force to re-run.\n";
		exit(0);
	}
}

if (!mysql_connect_db()) {
	fwrite(STDERR, "DB connection failed.\n");
	exit(1);
}

$map = chickenroad_legacy_slug_map();
$report = array(
	'pages_renamed' => 0,
	'pages_url_columns' => 0,
	'content_i18n_url' => 0,
	'content_i18n_text' => 0,
	'pages_text' => 0,
	'redirects' => 0,
);

function mig_find_page_by_slug($slug) {
	$slug = mysql_res(trim((string) $slug, '/'));
	$row = mysql_select("SELECT * FROM pages WHERE url='" . $slug . "' LIMIT 1", 'row', 0);
	if ($row) {
		return $row;
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$row = mysql_select("
			SELECT p.*
			FROM pages p
			INNER JOIN content_i18n ci ON ci.entity='pages' AND ci.entity_id=p.id
			WHERE ci.url='" . $slug . "'
			   OR ci.url='/" . $slug . "'
			   OR ci.url='/" . $slug . "/'
			   OR ci.url='" . $slug . "/'
			LIMIT 1
		", 'row', 0);
		if ($row) {
			return $row;
		}
	}
	return null;
}

function mig_rebrand_fields(array $row, array $fields) {
	$patch = array();
	foreach ($fields as $f) {
		if (!isset($row[$f]) || trim((string) $row[$f]) === '') {
			continue;
		}
		$orig = (string) $row[$f];
		$new = site_brand_rebrand_text($orig);
		$new = chickenroad_normalize_legacy_slug_urls_in_text($new);
		if ($new !== $orig) {
			$patch[$f] = $new;
		}
	}
	return $patch;
}

$page_cols = array();
foreach (mysql_select("SHOW COLUMNS FROM pages", 'rows', 0) ?: array() as $c) {
	if (!empty($c['Field'])) {
		$page_cols[(string) $c['Field']] = true;
	}
}

foreach ($map as $old_slug => $new_slug) {
	$page = mig_find_page_by_slug($old_slug);
	if (!$page || empty($page['id'])) {
		echo "Skip: no page for slug {$old_slug}\n";
		continue;
	}
	$page_id = (int) $page['id'];

	$target = mig_find_page_by_slug($new_slug);
	if ($target && (int) $target['id'] !== $page_id) {
		echo "WARN: target slug {$new_slug} already on page id=" . (int) $target['id'] . "; hiding legacy id={$page_id}\n";
		mysql_fn('update', 'pages', array('display' => 0, 'menu' => 0), ' AND id=' . $page_id);
		continue;
	}

	$patch = array('url' => $new_slug);
	foreach (array('url1', 'url2', 'url3', 'url4', 'url5') as $col) {
		if (!isset($page_cols[$col])) {
			continue;
		}
		$val = isset($page[$col]) ? trim((string) $page[$col], '/') : '';
		if ($val === $old_slug) {
			$patch[$col] = $new_slug;
			$report['pages_url_columns']++;
		}
	}
	$text_patch = mig_rebrand_fields($page, array('name', 'title', 'description', 'text', 'name1', 'title1', 'description1', 'text1'));
	$patch = array_merge($patch, $text_patch);
	if (!empty($text_patch)) {
		$report['pages_text']++;
	}
	mysql_fn('update', 'pages', $patch, ' AND id=' . $page_id);
	$report['pages_renamed']++;
	echo "Renamed page id={$page_id}: {$old_slug} → {$new_slug}\n";

	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$rows = mysql_select("
			SELECT id, url, name, title, description, content
			FROM content_i18n
			WHERE entity='pages' AND entity_id=" . $page_id . "
		", 'rows', 0) ?: array();
		foreach ($rows as $r) {
			$ci_patch = mig_rebrand_fields($r, array('name', 'title', 'description', 'content'));
			$url = isset($r['url']) ? trim((string) $r['url'], '/') : '';
			if ($url === $old_slug || $url === 'guides/' . $old_slug) {
				$ci_patch['url'] = $new_slug;
				$report['content_i18n_url']++;
			} elseif ($url !== '' && $url !== $new_slug) {
				$ci_patch['url'] = chickenroad_normalize_legacy_slug_urls_in_text($url);
				if ($ci_patch['url'] !== $url) {
					$report['content_i18n_url']++;
				}
			}
			if (!empty($ci_patch)) {
				mysql_fn('update', 'content_i18n', $ci_patch, ' AND id=' . (int) $r['id']);
				$report['content_i18n_text']++;
			}
		}
	}
}

// Fix Aviator slugs in any remaining content_i18n URLs / HTML (blog, guides, etc.)
if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$all = mysql_select("SELECT id, url, title, description, content, name FROM content_i18n", 'rows', 0) ?: array();
	foreach ($all as $r) {
		$patch = mig_rebrand_fields($r, array('name', 'title', 'description', 'content'));
		$url = isset($r['url']) ? (string) $r['url'] : '';
		$new_url = chickenroad_normalize_legacy_slug_urls_in_text($url);
		if ($new_url !== $url) {
			$patch['url'] = trim($new_url, '/');
		}
		if (!empty($patch)) {
			mysql_fn('update', 'content_i18n', $patch, ' AND id=' . (int) $r['id']);
			$report['content_i18n_text']++;
		}
	}
}

// Admin redirects table (optional)
if (@mysql_select("SHOW TABLES LIKE 'redirects'", 'num_rows') > 0) {
	$langs = mysql_select("SELECT url FROM languages WHERE display=1", 'rows', 0) ?: array();
	$lang_segs = array('');
	foreach ($langs as $l) {
		$u = trim((string) ($l['url'] ?? ''), '/');
		if ($u !== '') {
			$lang_segs[] = $u;
		}
	}
	foreach ($map as $old_slug => $new_slug) {
		foreach ($lang_segs as $lang) {
			$prefix = ($lang !== '' ? '/' . $lang : '');
			$pairs = array(
				array($prefix . '/' . $old_slug . '/', $prefix . '/' . $new_slug . '/'),
				array($prefix . '/guides/' . $old_slug . '/', $prefix . '/' . $new_slug . '/'),
			);
			foreach ($pairs as $pair) {
				list($old_path, $new_path) = $pair;
				$exists = mysql_select("SELECT id FROM redirects WHERE old_url='" . mysql_res($old_path) . "' LIMIT 1", 'row', 0);
				if ($exists) {
					continue;
				}
				mysql_fn('insert', 'redirects', array(
					'old_url' => $old_path,
					'new_url' => $new_path,
				));
				$report['redirects']++;
			}
		}
	}
}

$payload = json_encode(array_merge($report, array('at' => date('c'))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$ex = mysql_select("SELECT id FROM variables WHERE `key`='migration_aviator_guide_slugs_v1' LIMIT 1", 'row', 0);
	if ($ex && !empty($ex['id'])) {
		mysql_fn('update', 'variables', array('value' => $payload), ' AND id=' . (int) $ex['id']);
	} else {
		mysql_fn('insert', 'variables', array('key' => 'migration_aviator_guide_slugs_v1', 'value' => $payload));
	}
}

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
