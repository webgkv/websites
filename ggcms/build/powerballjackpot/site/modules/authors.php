<?php
/**
 * Authors: list /{lang}/authors/ and profile /{lang}/authors/{slug}/
 */

require_once ROOT_DIR . 'functions/author_profiles.php';

$authors_base = author_public_base($abc);
$cur_lang_id = author_current_lang_id();

if (!empty($u[3])) {
	$error++;
} elseif (!empty($u[2])) {
	$author_id = author_resolve_id_by_slug($u[2], $cur_lang_id);
	$author = $author_id > 0 ? author_row_by_id($author_id) : null;
	if (!$author) {
		$error++;
	} else {
		$author = author_apply_locale($author, $cur_lang_id);
		$slug = author_public_slug($author, $cur_lang_id);
		$abc['author_single'] = $author;
		$abc['breadcrumb'][] = array(
			'name' => (string)$author['name'],
			'url' => $authors_base . $slug . '/',
		);

		$page_title = trim((string)($author['meta_title'] ?? ''));
		if ($page_title === '') {
			$brand = function_exists('site_brand_name') ? site_brand_name() : 'Site';
			$page_title = (string)$author['name'] . ' | ' . $brand;
		}
		$page_desc = trim((string)($author['meta_description'] ?? ''));
		if ($page_desc === '') {
			$page_desc = author_excerpt($author, 160);
		}
		$abc['page']['title'] = $page_title;
		$abc['page']['description'] = $page_desc;

		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			foreach ($abc['languages'] as $i => $ldata) {
				$lang_url = trim((string)($ldata['url'] ?? ''), '/');
				$target_lid = isset($ldata['id']) ? (int)$ldata['id'] : 0;
				if ($lang_url === '' || $target_lid <= 0) {
					continue;
				}
				$loc_author = author_apply_locale($author, $target_lid);
				$loc_slug = author_public_slug($loc_author, $target_lid);
				$abc['links'][$lang_url] = array($lang_url, 'authors', $loc_slug);
			}
		}
	}
} else {
	foreach ($abc['languages'] as $i => $ldata) {
		$lang_url = trim((string)($ldata['url'] ?? ''), '/');
		if ($lang_url !== '') {
			$abc['links'][$lang_url] = array($lang_url, 'authors');
		}
	}

	$list = mysql_select("SELECT * FROM site_authors WHERE display=1 ORDER BY name ASC", 'rows') ?: array();
	$authors_list = array();
	foreach ($list as $row) {
		$authors_list[] = author_apply_locale($row, $cur_lang_id);
	}
	$abc['authors_list'] = $authors_list;
}
