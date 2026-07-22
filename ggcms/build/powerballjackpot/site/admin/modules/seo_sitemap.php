<?php

/**
 * SEO: Sitemap dashboard — controls which sections are included and shows stats.
 * Canonical index /api/sitemap/index_hub.xml lists sitemap_{lang}_{NNN}.xml (legacy /index.xml → 301 hub).
 */

$page_name = 'SEO: Sitemap.xml';

/**
 * Count &lt;url&gt; entries in a sitemap urlset file (see cron/web_sitemap_build.php or cron/run.php sitemap_build).
 *
 * @return int|null null if file unreadable
 */
function seo_sitemap_part_url_count($absPath) {
	if (!is_readable($absPath)) {
		return null;
	}
	$raw = @file_get_contents($absPath);
	if ($raw === false) {
		return null;
	}
	return substr_count($raw, '<url>');
}

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
$opts = array(
	'pages'   => 1,
	'blog'    => 1,
	'guides'  => 1,
	'games'   => 1,
	'casinos' => 1,
	'promo' => 1,
);
if ($variables_exists) {
	$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'sitemap_include' LIMIT 1", 'row');
	if ($row && $row['value'] !== '') {
		$dec = json_decode($row['value'], true);
		if (is_array($dec)) {
			foreach (array_keys($opts) as $k) {
				if (isset($dec[$k])) $opts[$k] = (int)(bool)$dec[$k];
			}
		}
	}
}

// Which languages to include in sitemap (empty = all with display=1)
$sitemap_lang_ids = array();
if ($variables_exists) {
	$row_lang = mysql_select("SELECT value FROM `variables` WHERE `key` = 'sitemap_languages' LIMIT 1", 'row');
	if ($row_lang && $row_lang['value'] !== '') {
		$dec = json_decode($row_lang['value'], true);
		if (is_array($dec)) $sitemap_lang_ids = array_map('intval', $dec);
	}
}
$all_languages = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows');
if (!$all_languages) $all_languages = array();

$translation_enabled_lang_ids = array();
if ($variables_exists) {
	$row_ts = mysql_select("SELECT value FROM `variables` WHERE `key` = 'translation_settings' LIMIT 1", 'row');
	if ($row_ts && $row_ts['value'] !== '') {
		$ts = json_decode($row_ts['value'], true);
		if (is_array($ts) && !empty($ts['enabled_lang_ids']) && is_array($ts['enabled_lang_ids'])) {
			$translation_enabled_lang_ids = array_values(array_filter(array_map('intval', $ts['enabled_lang_ids'])));
		}
	}
}

$saved = false;
if ($variables_exists && isset($_POST['sitemap_include']) && is_array($_POST['sitemap_include'])) {
	$new = array();
	foreach (array_keys($opts) as $k) {
		$new[$k] = isset($_POST['sitemap_include'][$k]) ? 1 : 0;
	}
	$json = json_encode($new);
	$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = 'sitemap_include' LIMIT 1", 'row');
	if ($exists) {
		mysql_fn('update', 'variables', array('value' => $json), " AND `key` = 'sitemap_include' ");
	} else {
		mysql_fn('insert', 'variables', array('key' => 'sitemap_include', 'value' => $json));
	}
	$opts = $new;
	$saved = true;
}
if ($variables_exists && isset($_POST['sitemap_languages']) && is_array($_POST['sitemap_languages'])) {
	$ids = array_map('intval', $_POST['sitemap_languages']);
	$ids = array_filter($ids);
	$json = json_encode(array_values($ids));
	$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = 'sitemap_languages' LIMIT 1", 'row');
	if ($exists) {
		mysql_fn('update', 'variables', array('value' => $json), " AND `key` = 'sitemap_languages' ");
	} else {
		mysql_fn('insert', 'variables', array('key' => 'sitemap_languages', 'value' => $json));
	}
	$sitemap_lang_ids = array_values($ids);
	$saved = true;
}

// Counts per section (tables may not exist on all installs)
$stats = array();
$stats['pages']   = (int) @mysql_select("SELECT COUNT(*) FROM pages WHERE display=1", 'string');
$stats['blog']    = (int) @mysql_select("SELECT COUNT(*) FROM blog WHERE display=1", 'string');
$stats['guides']  = (int) @mysql_select("SELECT COUNT(*) FROM guides WHERE display=1", 'string');
$stats['games']   = (int) @mysql_select("SELECT COUNT(*) FROM games WHERE display=1", 'string');
$stats['casinos'] = (int) @mysql_select("SELECT COUNT(*) FROM casino_articles WHERE display=1", 'string');
$stats['promo'] = (int) @mysql_select("SELECT COUNT(*) FROM promo WHERE display=1", 'string');
$stats['langs']   = (int) @mysql_select("SELECT COUNT(*) FROM languages WHERE display=1", 'string');

$base = rtrim($config['http_domain'], '/');
$url_index = $base . '/api/sitemap/index_hub.xml';
$sitemapDir = ROOT_DIR . 'api/sitemap/';
$partFiles = array();
if (is_dir($sitemapDir)) {
	foreach (glob($sitemapDir . 'sitemap_*_*.xml') ?: array() as $abs) {
		$bn = basename($abs);
		if (preg_match('/^sitemap_[a-z0-9]+_[0-9]{3}\.xml$/', $bn)) {
			$partFiles[] = $bn;
		}
	}
	sort($partFiles, SORT_NATURAL);
}

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">' . htmlspecialchars($page_name) . '</h5>';

if (!$variables_exists) {
	$content .= '<div class="alert alert-warning py-2 mb-4">Table <code>variables</code> is required to store sitemap options. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank" rel="noopener">run_migrate_BD.php</a>.</div>';
}

if ($saved) {
	$content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
}

// —— Actions ——
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Actions</strong></div><div class="card-body">';
$content .= '<a href="' . htmlspecialchars($url_index) . '" target="_blank" rel="noopener" class="btn btn-primary btn-sm mr-2 mb-2">Open sitemap index</a>';
$content .= '<a href="/cron/web_sitemap_build.php" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm mb-2">Rebuild sitemaps</a>';
$content .= '<div class="small text-muted mt-2 mb-0">Index: <code class="small">' . htmlspecialchars($url_index) . '</code></div>';
$content .= '</div></div>';

// —— Generated part files ——
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Generated part files</strong></div><div class="card-body py-3">';
if (!empty($partFiles)) {
	$content .= '<div class="row">';
	foreach ($partFiles as $pf) {
		$url = $base . '/api/sitemap/' . $pf;
		$absPart = $sitemapDir . $pf;
		$urlCnt = seo_sitemap_part_url_count($absPart);
		$cntLabel = ($urlCnt === null) ? '—' : (string)(int)$urlCnt;
		$content .= '<div class="col-6 col-md-4 col-lg-2 mb-2">';
		$content .= '<div class="border rounded text-center h-100 py-2 px-1 d-flex flex-column align-items-center justify-content-center" style="min-height:4.25rem;">';
		$content .= '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener" class="small text-break d-inline-block mb-1" style="line-height:1.25;">' . htmlspecialchars($pf) . '</a>';
		$content .= '<div class="small text-muted"><span class="badge badge-secondary">' . htmlspecialchars($cntLabel) . '</span> <span class="small">links</span></div>';
		$content .= '</div></div>';
	}
	$content .= '</div>';
} else {
	$content .= '<div class="alert alert-warning py-2 mb-0">No part files yet. Run rebuild.</div>';
}
$content .= '</div></div>';

// —— Counts ——
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Content counts</strong> <span class="badge badge-secondary align-middle">display=1</span></div><div class="card-body py-3">';
$content .= '<div class="row">';
$kpi = array(
	array('key' => 'pages', 'label' => 'Pages'),
	array('key' => 'blog', 'label' => 'Blog'),
	array('key' => 'guides', 'label' => 'Guides'),
	array('key' => 'games', 'label' => 'Games'),
	array('key' => 'casinos', 'label' => 'Casinos'),
	array('key' => 'promo', 'label' => 'Promo'),
	array('key' => 'langs', 'label' => 'Languages'),
);
foreach ($kpi as $item) {
	$k = $item['key'];
	$content .= '<div class="col-6 col-md-4 col-lg-2 mb-3 mb-lg-0"><div class="border rounded p-3 text-center h-100">';
	$content .= '<div class="h5 mb-0">' . (int)$stats[$k] . '</div><div class="small text-muted">' . htmlspecialchars($item['label']) . '</div>';
	$content .= '</div></div>';
}
$content .= '</div></div></div>';

if ($variables_exists) {
	$content .= '<form method="post">';
	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Sections</strong></div><div class="card-body">';
	$content .= '<div class="form-row">';
	$sections = array(
		'pages' => 'Pages',
		'blog' => 'Blog',
		'guides' => 'Guides',
		'games' => 'Games',
		'casinos' => 'Casinos',
		'promo' => 'Promo',
	);
	foreach ($sections as $sk => $slab) {
		$cid = 'inc_' . $sk;
		$content .= '<div class="form-group col-6 col-md-4 mb-md-0"><div class="form-check">';
		$content .= '<input type="checkbox" class="form-check-input" name="sitemap_include[' . htmlspecialchars($sk) . ']" id="' . htmlspecialchars($cid) . '" value="1"' . (!empty($opts[$sk]) ? ' checked' : '') . '>';
		$content .= '<label class="form-check-label" for="' . htmlspecialchars($cid) . '">' . htmlspecialchars($slab) . '</label></div></div>';
	}
	$content .= '</div></div></div>';

	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Languages</strong></div><div class="card-body">';
	$content .= '<div class="border rounded p-3 bg-light"><div class="form-row">';
	$use_all_langs = (count($sitemap_lang_ids) === 0);
	foreach ($all_languages as $l) {
		$lid = (int)$l['id'];
		$checked = $use_all_langs || in_array($lid, $sitemap_lang_ids, true) || in_array($lid, $translation_enabled_lang_ids, true);
		$label = htmlspecialchars($l['name'] . ($l['url'] !== '' ? ' (' . $l['url'] . ')' : ''));
		$content .= '<div class="col-md-4 col-lg-3 mb-2"><div class="form-check">';
		$content .= '<input type="checkbox" class="form-check-input" name="sitemap_languages[]" id="lang_' . $lid . '" value="' . $lid . '"' . ($checked ? ' checked' : '') . '>';
		$content .= '<label class="form-check-label" for="lang_' . $lid . '">' . $label . '</label></div></div>';
	}
	$content .= '</div></div>';
	$content .= '<button type="submit" class="btn btn-primary btn-sm mt-3">Save</button>';
	$content .= '</div></div>';
	$content .= '</form>';
}

$content .= '</div>';
