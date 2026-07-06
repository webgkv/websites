<?php

/**
 * Public URL slugs for content sections (PowerBall: blog→articles, guides→odds, casinos→lotteries).
 * Internal modules/tables stay blog/guides/casinos; only front URLs and admin labels may differ.
 */

function site_section_slug_map()
{
	static $map = null;
	if ($map !== null) {
		return $map;
	}
	$map = array(
		'blog' => 'blog',
		'guides' => 'guides',
		'casinos' => 'casinos',
	);
	global $config;
	if (!empty($config['section_slugs']) && is_array($config['section_slugs'])) {
		foreach ($config['section_slugs'] as $module => $slug) {
			$module = (string) $module;
			if (!isset($map[$module])) {
				continue;
			}
			$slug = trim((string) $slug, '/');
			if ($slug !== '') {
				$map[$module] = $slug;
			}
		}
		return $map;
	}
	if (function_exists('mysql_select') && @mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
		$blog_row = mysql_select("SELECT url FROM pages WHERE display=1 AND level=1 AND module='blog' ORDER BY left_key ASC LIMIT 1", 'row', 0);
		if ($blog_row && trim((string) ($blog_row['url'] ?? ''), '/') !== '') {
			$map['blog'] = trim((string) $blog_row['url'], '/');
		}
		$guides_row = mysql_select("
			SELECT url FROM pages
			WHERE display=1 AND level=1
			  AND (module='guides' OR (module='pages' AND url IN ('guides','odds')))
			ORDER BY left_key ASC
			LIMIT 1
		", 'row', 0);
		if ($guides_row && trim((string) ($guides_row['url'] ?? ''), '/') !== '') {
			$map['guides'] = trim((string) $guides_row['url'], '/');
		}
		$casinos_row = mysql_select("
			SELECT url FROM pages
			WHERE display=1 AND level=1
			  AND (module='casinos' OR (module='pages' AND url IN ('casinos','lotteries')))
			ORDER BY left_key ASC
			LIMIT 1
		", 'row', 0);
		if ($casinos_row && trim((string) ($casinos_row['url'] ?? ''), '/') !== '') {
			$map['casinos'] = trim((string) $casinos_row['url'], '/');
		}
	}
	return $map;
}

function site_section_slug_sql_variants($path_segment)
{
	$seg = trim((string) $path_segment, '/');
	$variants = array();
	if ($seg !== '') {
		$variants[] = $seg;
	}
	$module = site_section_module_for_slug($seg);
	if ($module !== null) {
		$variants[] = site_section_public_slug($module);
		$variants[] = (string) $module;
	}
	return array_values(array_unique(array_filter($variants, function ($s) {
		return trim((string) $s, '/') !== '';
	})));
}

/**
 * SQL WHERE fragment: pages.url / url{lang} match public or legacy section slugs.
 */
function site_section_build_pages_slug_where($path_segment, $langid = '')
{
	$variants = site_section_slug_sql_variants($path_segment);
	if (empty($variants)) {
		return null;
	}
	$url_conds = array();
	foreach ($variants as $v) {
		$esc = mysql_res($v);
		$url_conds[] = "url='" . $esc . "'";
	}
	$where = '(' . implode(' OR ', $url_conds) . ')';
	if ($langid !== '') {
		static $url_langid_has_col = array();
		if (!array_key_exists($langid, $url_langid_has_col)) {
			$col = 'url' . $langid;
			$url_langid_has_col[$langid] = (mysql_select("SHOW COLUMNS FROM pages LIKE '" . mysql_res($col) . "'", 'num_rows') > 0);
		}
		if (!empty($url_langid_has_col[$langid])) {
			$lang_conds = array();
			foreach ($variants as $v) {
				$esc = mysql_res($v);
				$lang_conds[] = 'url' . $langid . "='" . $esc . "'";
			}
			$where = '(' . implode(' OR ', $lang_conds) . ' OR ' . $where . ')';
		}
	}
	return $where;
}

/**
 * 301 target when the first path segment uses a legacy section slug (guides→odds, blog→articles).
 */
function site_section_legacy_canonical_path(array $u, array $lang = array())
{
	if (empty($u[1])) {
		return null;
	}
	$seg = trim((string) $u[1], '/');
	$module = site_section_module_for_slug($seg);
	if ($module === null) {
		return null;
	}
	$public = site_section_public_slug($module);
	if ($seg === $public) {
		return null;
	}
	$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : '';
	$parts = array();
	if ($lu !== '') {
		$parts[] = $lu;
	}
	$parts[] = $public;
	for ($i = 2, $n = count($u); $i < $n; $i++) {
		if (!isset($u[$i])) {
			continue;
		}
		$piece = trim((string) $u[$i], '/');
		if ($piece !== '') {
			$parts[] = $piece;
		}
	}
	$path = '/' . implode('/', $parts) . '/';
	return preg_replace('#/+#', '/', $path);
}

function site_section_legacy_redirect_if_needed(array $u, array $lang = array())
{
	global $config;
	$path = site_section_legacy_canonical_path($u, $lang);
	if ($path === null) {
		return;
	}
	$qs = !empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '';
	$origin = isset($config['http_domain']) ? (string) $config['http_domain'] : '';
	if ($origin === '' && function_exists('site_seo_public_origin')) {
		$origin = site_seo_public_origin();
	}
	header('Location: ' . $origin . $path . $qs, true, 301);
	exit;
}

function site_section_public_slug($module)
{
	$module = (string) $module;
	$map = site_section_slug_map();
	return isset($map[$module]) ? $map[$module] : $module;
}

function site_section_module_for_slug($slug)
{
	$slug = trim((string) $slug, '/');
	if ($slug === '') {
		return null;
	}
	foreach (site_section_slug_map() as $module => $public) {
		if ($public === $slug) {
			return $module;
		}
	}
	if ($slug === 'blog') {
		return 'blog';
	}
	if ($slug === 'guides') {
		return 'guides';
	}
	if ($slug === 'casinos') {
		return 'casinos';
	}
	return null;
}

function site_section_admin_label($module, $default)
{
	global $config;
	$module = (string) $module;
	if (!empty($config['section_admin_labels']) && is_array($config['section_admin_labels']) && !empty($config['section_admin_labels'][$module])) {
		return (string) $config['section_admin_labels'][$module];
	}
	return (string) $default;
}

function site_page_menu_url_slug(array $page, $lid = null)
{
	if ($lid === null || $lid === '') {
		global $langid;
		$lid = isset($langid) ? $langid : '';
	}
	$seg = '';
	if ($lid !== '' && isset($page['url' . $lid])) {
		$seg = trim((string) $page['url' . $lid], '/');
	}
	if ($seg === '' && isset($page['url'])) {
		$seg = trim((string) $page['url'], '/');
	}
	return $seg;
}

function site_page_is_section(array $page, $section_module, $langid = '')
{
	$section_module = (string) $section_module;
	if (!isset($page['module'])) {
		return false;
	}
	$module = (string) $page['module'];
	if ($module === $section_module) {
		return true;
	}
	if ($section_module === 'guides' && $module === 'pages') {
		$slug = site_page_menu_url_slug($page, $langid);
		return in_array($slug, array(site_section_public_slug('guides'), 'guides'), true);
	}
	if ($section_module === 'blog' && $module === 'pages') {
		$slug = site_page_menu_url_slug($page, $langid);
		return in_array($slug, array(site_section_public_slug('blog'), 'blog'), true);
	}
	if ($section_module === 'casinos') {
		if ($module === 'casinos') {
			return true;
		}
		if ($module === 'pages') {
			$slug = site_page_menu_url_slug($page, $langid);
			return in_array($slug, array(site_section_public_slug('casinos'), 'casinos', 'lotteries'), true);
		}
	}
	return false;
}

function site_section_public_base($section_module, array $abc = array())
{
	$lang_prefix = isset($abc['lang']['url']) ? trim((string) $abc['lang']['url'], '/') : '';
	$seg = site_section_public_slug($section_module);
	$base = ($lang_prefix !== '' ? '/' . $lang_prefix . '/' . $seg . '/' : '/' . $seg . '/');
	return preg_replace('#/+#', '/', $base);
}

function site_section_link_segment($section_module)
{
	return site_section_public_slug($section_module);
}

/** All URL slugs that identify a section landing (public + legacy canonical). */
function site_content_section_url_slugs()
{
	$slugs = array(
		site_section_public_slug('blog'),
		site_section_public_slug('guides'),
		'blog',
		'guides',
		'games',
		'casinos',
		'lotteries',
		'demo',
		'predictor',
		'download',
		'install-pwa',
		'ios-pwa',
		'install-apk',
	);
	return array_values(array_unique(array_filter($slugs, function ($s) {
		return trim((string) $s, '/') !== '';
	})));
}

function site_menu_item_matches_section(array $item, $section_module)
{
	$slug = site_page_menu_url_slug($item);
	if ($slug === '') {
		return false;
	}
	return $slug === site_section_public_slug($section_module) || $slug === (string) $section_module;
}
