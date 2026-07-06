<?php

// site tree, handles first-level URL
/*
 * v1.2.21 - $languages перенес в /admin/config_multilingual.php
 * v1.4.16 - $delete удалил confirm
 * v1.4.17 - сокращение параметров form
 */

// --- Export: output JSON and exit ---
if (isset($get['u']) && $get['u'] === 'export_pages') {
	$cols = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
	if (!$cols) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'Could not describe table pages'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
	$rows = mysql_select("SELECT * FROM `pages` ORDER BY left_key ASC", 'rows');
	if ($rows === false) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'Could not select from pages'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
	$out = array(
		'exported_at' => date('c'),
		'table'       => 'pages',
		'columns'     => array_column($cols, 'Field'),
		'rows'        => $rows,
		'count'       => count($rows),
	);
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="pages-' . date('Y-m-d-His') . '.json"');
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// --- Import: validate, replace table, redirect ---
if (isset($get['u']) && $get['u'] === 'import_pages' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$import_error = '';
	$confirm = !empty($_POST['confirm_replace']);
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;

	if (!$confirm) {
		$import_error = 'Please confirm that all data will be completely replaced.';
	} elseif (!$file) {
		$import_error = 'Please select a JSON file to upload.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		if ($raw === false) {
			$import_error = 'Could not read uploaded file.';
		} else {
			$data = @json_decode($raw, true);
			if (!$data || !isset($data['rows']) || !is_array($data['rows'])) {
				$import_error = 'Invalid JSON or missing "rows" array.';
			} elseif (isset($data['table']) && $data['table'] !== 'pages') {
				$import_error = 'This file is not a pages export (table name mismatch).';
			} else {
				$columns = isset($data['columns']) && is_array($data['columns']) ? $data['columns'] : array();
				$rows = $data['rows'];
				$ok = mysql_transaction('start');
				if (!$ok) {
					$import_error = 'Transaction start failed.';
				} else {
					$replaced = 0;
					foreach ($rows as $row) {
						if (!is_array($row)) continue;
						if ($columns) {
							$filtered = array();
							foreach ($columns as $col) {
								if (array_key_exists($col, $row)) $filtered[$col] = $row[$col];
							}
							$row = $filtered;
						}
						$set = array();
						foreach ($row as $k => $v) {
							$set[] = '`' . preg_replace('/[^a-z0-9_]/i', '', $k) . '` = \'' . mysql_res($v) . '\'';
						}
						if (!empty($set)) {
							mysql_fn('query', 'REPLACE INTO `pages` SET ' . implode(', ', $set));
							$replaced++;
						}
					}
					mysql_transaction('commit');
					header('Location: /admin.php?m=pages&u=export_import&success=1&inserted=' . (int)$replaced);
					exit;
				}
			}
		}
	}
	// On error: redirect back to export_import with error
	header('Location: /admin.php?m=pages&u=export_import&import_error=' . urlencode($import_error));
	exit;
}

// --- Single-page export: one page as JSON (for modal edit form) ---
if (isset($get['u']) && $get['u'] === 'export_page_single' && isset($get['id']) && ($page_id = intval($get['id'])) > 0) {
	$cols = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
	$row = mysql_select("SELECT * FROM `pages` WHERE id = " . $page_id, 'row');
	if (!$row || !$cols) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'Page not found or table error'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
	$out = array(
		'exported_at'   => date('c'),
		'table'         => 'pages',
		'single_page'   => true,
		'columns'       => array_column($cols, 'Field'),
		'rows'          => array($row),
		'count'         => 1,
	);
	$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(isset($row['url']) ? $row['url'] : (isset($row['url1']) ? $row['url1'] : 'page')));
	$filename = 'page-' . $page_id . ($slug ? '-' . $slug : '') . '.json';
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	// Optionally save a copy to json/pages/
	$json_dir = ROOT_DIR . 'json' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR;
	if (is_dir($json_dir) && is_writable($json_dir)) {
		@file_put_contents($json_dir . $filename, json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	}
	exit;
}

// --- Single-page import: update one page from JSON (from modal edit form) ---
if (isset($get['u']) && $get['u'] === 'import_page_single' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$import_error = '';
	$page_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	if ($page_id <= 0) {
		$import_error = 'Invalid page id.';
	} elseif (!$file) {
		$import_error = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		if ($raw === false) {
			$import_error = 'Could not read file.';
		} else {
			$data = @json_decode($raw, true);
			if (!$data || !isset($data['rows']) || !is_array($data['rows']) || empty($data['rows'])) {
				$import_error = 'Invalid JSON or missing "rows" array with at least one row.';
			} elseif (isset($data['table']) && $data['table'] !== 'pages') {
				$import_error = 'This file is not a pages export (table name mismatch).';
			} else {
				$existing = mysql_select("SELECT id FROM `pages` WHERE id = " . $page_id, 'row');
				if (!$existing) {
					$import_error = 'Page not found.';
				} else {
					$cols = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
					$table_columns = $cols ? array_column($cols, 'Field') : array();
					$protected = array('id', 'left_key', 'right_key', 'level', 'parent');
					$row = $data['rows'][0];
					$update = array('id' => $page_id);
					foreach ($row as $col => $value) {
						if (in_array($col, $protected, true)) continue;
						if (!in_array($col, $table_columns)) continue;
						$update[$col] = $value;
					}
					if (count($update) > 1 && mysql_fn('update', 'pages', $update)) {
						header('Location: /admin.php?m=pages&import_page_ok=1&import_page_id=' . $page_id);
						exit;
					}
					$import_error = 'Update failed.';
				}
			}
		}
	}
	header('Location: /admin.php?m=pages&import_page_error=' . urlencode($import_error) . '&import_page_id=' . $page_id);
	exit;
}

$modules_site = array(
	'pages'			=> 'Text page',
	'index'			=> 'Index page',
	'blog'			=> 'Blog',
	'advices'		=> 'Advices',
	'videos'		=> 'Videos',
	'casinos'		=> 'Casinos',
	'sportsbooks'		=> 'Sportsbooks',
	'ticketoftheday'	=> 'Ticket of the Day',
	'ticketoftheday-last'	=> 'Ticket of the Day last',
	'betoftheday'		=> 'Bet of the Day',
	'betoftheday-last'	=> 'Bet of the Day last',
	'search'		=> 'Search',
	'betting'		=> 'Betting',
	'recenzii'		=> 'Reviews',
	'more'			=> 'More',
);

//$a18n['menu2']  = 'menu 2';
//$a18n['title1'] = 'title';

for($i=2;$i<=count($languages);$i++) {
  $a18n["name$i"]        = 'name';
  $a18n["text$i"]        = 'text';
  $a18n["url$i"]         = 'url';
  $a18n["title$i"]       = 'title';
  $a18n["description$i"] = 'description';
}

if ($get['u']=='form') {
	if (empty($post['module'])) $post['module'] = 'pages';
	foreach ($modules_site as $k=>$v)
		if (!file_exists(ROOT_DIR.'modules/'.$k.'.php'))
			unset($modules_site[$k]);
}

$table = array(
	'_tree'		=> true,
	'_edit'		=> true,
	'id'		=> '',
	'_view'		=> 'page',
	'name'		=> '',
	'title'		=> '',
	'url'		=> '',
	'module'	=> $modules_site,
	'menu'		=> 'boolean',
	'menu2'		=> 'boolean',
//	'noindex'  	=> 'boolean',
	'display'	=> 'display'
);

// When editing a page form, hide the site tree/table under the form.
// This page acts as a dedicated editor; showing the tree below is confusing and unnecessary.
if ((isset($get['m']) && $get['m'] === 'pages') && (isset($get['u']) && $get['u'] === 'form')) {
	$table = null;
}

$tabs = array(
	1=>a18n('common'),
	2=>a18n('media'),
);

//print_r($languages);exit;
/*
// Only when site is multilingual
if ($config['multilingual']) {
	// POST over GET
	if (isset($post['language'])) $get['language'] = $post['language'];
*/

//	if (@$get['language'] == 0) $get['language'] = key($languages)['id'];
//	$query = "
//		SELECT pages.*
//		FROM pages
//		WHERE pages.language = '".$get['language']."'
//	";

/*
	$filter[] = array('language', $languages);
	$form[1][] = '<input name="language" type="hidden" value="'.$get['language'].'" />';
}
*/
//echo $query;exit;

// v1.4.16 - $delete removed confirm
$delete = array('pages'=>'parent');

if (!(isset($get['m']) && $get['m'] === 'pages' && isset($get['u']) && $get['u'] === 'form')) {
	$filter[] = '<a href="/admin.php?m=media" class="btn btn-sm btn-outline-primary"><i data-feather="image" class="mr-1"></i>Media library</a>';
	$filter[] = '<a href="/admin.php?m=pages" class="btn btn-sm btn-outline-secondary">&larr; Back to site tree</a>';
	$filter[] = '<a href="/admin.php?m=pages&u=export_import" class="btn btn-sm btn-outline-secondary"><i data-feather="download" class="mr-1"></i>Export / Import</a>';
	// i18n/sys moved to Languages JSON section
}

$is_new = (!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new');

if ($is_new) {
	$form[1][] = array('input td7','name');
}
$form[1][] = array('select td3','module',array(
	'value'=>array(true,$modules_site),
	'help'=>'The module is responsible for the type of information on the page. For example, on the page of the "Blog" module a list of blog posts will be displayed.'
));
$form[1][] = array('checkbox','display');
//$form[1][] = array('input td7','h1');
$form[1][] = array('checkbox','menu');
//$form[1][] = array('checkbox','menu2',array('help'=>'second menu, usually displayed in the footer of the site'));
$form[1][] = 'clear';
$form[1][] = array('parent td3 td4','parent');
$authors_list = @mysql_select("SELECT id, name FROM site_authors WHERE display=1 ORDER BY name ASC", 'array') ?: array();
$form[1][] = array('select td3', 'author_id', array('name' => 'Author (E-E-A-T)', 'value' => array(true, $authors_list, '--- Default ---')));
// Pages that display content from the Content section (or wrapper URLs): no text editor here.
$content_section_modules = array('blog', 'guides', 'games', 'casinos');
// install-pwa / ios-pwa: canonical `pages.text` holds JSON (`_pwa_locale_bundles`), not HTML — hide TinyMCE like section landings.
$content_section_urls = function_exists('site_content_section_url_slugs')
	? site_content_section_url_slugs()
	: array('blog', 'guides', 'games', 'casinos', 'demo', 'predictor', 'download', 'install-pwa', 'ios-pwa', 'install-apk');
$page_module = (isset($get['u']) && $get['u'] === 'form' && isset($post['module'])) ? (string)$post['module'] : '';
$page_url = (isset($get['u']) && $get['u'] === 'form' && isset($post['url'])) ? trim((string)$post['url'], '/') : '';
$is_content_page = (int)(isset($get['id']) ? $get['id'] : 0) > 0 && (
	($page_module && in_array($page_module, $content_section_modules, true))
	|| ($page_module === 'pages' && $page_url !== '' && in_array($page_url, $content_section_urls, true))
);
if ($is_new) {
	if (!$is_content_page) {
		$form[1][] = array('tinymce td12','text',array('attr'=>'style="height:500px"'));
	}
	//$form[1][] = array('seo','seo url title description noindex');
	$form[1][] = array('seo','seo url title description');
}

$form[2][] = array('file_multi','imgs',array(
	'name'=>'Images',
//	'sizes'=>array(''=>'resize 1000x1000')
	'sizes'=>array(''=>'')
));

// Tab "Import / Export" only when editing an existing page (not new)
if (isset($get['u']) && $get['u'] === 'form' && isset($get['id']) && $get['id'] !== 'new' && ($_page_id = intval($get['id'])) > 0) {
	$tabs[3] = 'Import / Export';
	$_import_ok = isset($get['import_page_ok']) && $get['import_page_ok'] == '1';
	$_import_err = isset($get['import_page_error']) ? htmlspecialchars($get['import_page_error'], ENT_QUOTES, 'UTF-8') : '';
	$form[3][] = '<div class="col-12">';
	if ($_import_ok) {
		$form[3][] = '<div class="alert alert-success mb-3">This page has been updated from the imported JSON.</div>';
	}
	if ($_import_err) {
		$form[3][] = '<div class="alert alert-danger mb-3">' . $_import_err . '</div>';
	}
	$form[3][] = '<p class="mb-1"><strong>Export this page</strong></p>';
	$form[3][] = '<a href="/admin.php?m=pages&u=export_page_single&id=' . $_page_id . '" class="btn btn-primary mb-4">Download JSON</a>';
	$form[3][] = '<p class="mb-1"><strong>Import into this page</strong></p>';
	$form[3][] = '<div class="form-inline flex-wrap align-items-end"><div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" /></div><div class="form-group mb-2"><button type="submit" name="import" value="1" class="btn btn-secondary">Import and update this page</button></div></div>';
	$form[3][] = '</div>';

	// Translations (content_i18n) — scalable for 3+ languages, directly on Tab 1
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	$_i18n_langs = admin_i18n_enabled_languages();
	if ($i18n_lang_id <= 0 && !empty($_i18n_langs)) {
		$i18n_lang_id = (int)$_i18n_langs[0]['id'];
	}
	$_content_section_modules = array('blog', 'guides', 'games', 'casinos');
	// For pages with `module='pages'` we treat them as "content sections" only when
	// the URL is actually a section landing (blog/guides/games/casinos).
	// Other informational pages (e.g. predictor/download) are normal text pages:
	// their translation text is stored in `content_i18n.content` and must be editable.
	// install-pwa / ios-pwa: do NOT list here — their body is JSON in `pages.text` / canonical `content_i18n.content`,
	// but the Translations tab must still show the TinyMCE content field (otherwise users see no editor / ad-hoc raw UI).
	$_content_section_urls = function_exists('site_content_section_url_slugs')
		? site_content_section_url_slugs()
		: array('blog', 'guides', 'games', 'casinos');
	if (!isset($post['module']) || !isset($post['url'])) {
		$_pmeta = mysql_select("SELECT module, url FROM pages WHERE id=" . (int)$_page_id . " LIMIT 1", 'row');
		if (is_array($_pmeta)) {
			if (!isset($post['module'])) {
				$post['module'] = isset($_pmeta['module']) ? (string)$_pmeta['module'] : 'pages';
			}
			if (!isset($post['url'])) {
				$post['url'] = isset($_pmeta['url']) ? (string)$_pmeta['url'] : '';
			}
		}
	}
	$_page_module = isset($post['module']) ? (string)$post['module'] : '';
	$_page_url = isset($post['url']) ? trim((string)$post['url'], '/') : '';
	$_page_is_content_section =
		(in_array($_page_module, $_content_section_modules, true))
		|| ($_page_module === 'pages' && $_page_url !== '' && in_array($_page_url, $_content_section_urls, true));
	$_canonical_lang_id = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($vr && $vr['value'] !== '') {
			$dec = json_decode($vr['value'], true);
			if (is_array($dec) && isset($dec['source_lang_id'])) $_canonical_lang_id = (int)$dec['source_lang_id'];
		}
	}
	// Full-page clear (from "Del translate" link) so form values are reloaded reliably.
	if (!empty($get['i18n_clear'])) {
		$clear_lang_id = (int)$i18n_lang_id;
		$res = admin_i18n_clear('pages', $_page_id, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=pages&u=form&id=' . (int)$_page_id . '&tab=1&i18n_lang_id=' . (int)$clear_lang_id;
		header('Location: ' . $redirect);
		exit;
	}
	if (!empty($_POST['i18n_clear'])) {
		$clear_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$res = admin_i18n_clear('pages', $_page_id, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=pages&u=form&id=' . (int)$_page_id . '&tab=1&i18n_lang_id=' . (int)$clear_lang_id;
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}

	if (!empty($_POST['i18n_save'])) {
		$save_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$payload = array(
			'url' => isset($_POST['i18n_url']) ? trim((string)$_POST['i18n_url'], '/') : '',
			'name' => isset($_POST['i18n_name']) ? (string)$_POST['i18n_name'] : '',
			'title' => isset($_POST['i18n_title']) ? (string)$_POST['i18n_title'] : '',
			'description' => isset($_POST['i18n_description']) ? (string)$_POST['i18n_description'] : '',
			'status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : 'draft',
		);
		if (!$_page_is_content_section) {
			$payload['content'] = isset($_POST['i18n_content']) ? (string)$_POST['i18n_content'] : '';
		}
		$res = admin_i18n_save('pages', $_page_id, $save_lang_id, $payload);
		if (!empty($res['ok'])) {
			admin_i18n_sync_canonical_row_to_base_table('pages', $_page_id, $save_lang_id);
		}
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=pages&u=form&id=' . (int)$_page_id . '&tab=1&i18n_lang_id=' . (int)$save_lang_id;
		// When saving via admin/template2 iframe submit, we must return JSON so the client can reload the form.
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}
	$base_url = '/admin.php?m=pages&u=form&id=' . (int)$_page_id . '&tab=1';
	// Defaults from main tab (canonical) so they are pre-filled for the language form
	$defaults = array(
		'url' => isset($post['url']) ? (string)$post['url'] : '',
		'name' => isset($post['name']) ? (string)$post['name'] : '',
		'title' => isset($post['title']) ? (string)$post['title'] : '',
		'description' => isset($post['description']) ? (string)$post['description'] : '',
		'content' => isset($post['text']) ? (string)$post['text'] : '',
	);
	$i18n_options = $_page_is_content_section ? array('content_managed_elsewhere' => true) : array();
	$i18n_options['canonical_lang_id'] = $_canonical_lang_id;
	$form[1][] = admin_i18n_render_form('pages', $_page_id, $i18n_lang_id, $base_url, $defaults, $i18n_options);
}

//if($post['url']=='biletul-zilei') {
//  $tabs[3] = 'texts';
//  $form[3][] = array('tinymce td12','text2',array('attr'=>'style="height:250px"'));
//  $form[3][] = array('tinymce td12','text3',array('attr'=>'style="height:250px"'));
//}

//$form[2][] = array('textarea td12','video',array('name'=>a18n('video')));

// --- Menu i18n: export JSON for one language ---
if (isset($get['u']) && $get['u'] === 'export_menu_i18n' && isset($get['lang_id']) && ($_lid = (int)$get['lang_id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$canonical = mysql_select("SELECT id, module, level, name, url FROM pages WHERE display=1 AND menu=1 AND level<3 ORDER BY left_key", 'rows') ?: array();
	$out = array('exported_at' => date('c'), 'lang_id' => $_lid, 'menu_items' => array());
	foreach ($canonical as $p) {
		$pid = (int)$p['id'];
		$name = (string)$p['name'];
		$url = isset($p['url']) ? trim((string)$p['url'], '/') : '';
		if ($_lid > 1 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$row = mysql_select("SELECT name, url FROM content_i18n WHERE entity='pages' AND entity_id=" . $pid . " AND lang_id=" . $_lid . " LIMIT 1", 'row');
			if ($row) {
				$name = isset($row['name']) ? (string)$row['name'] : $name;
				$url = isset($row['url']) ? trim((string)$row['url'], '/') : $url;
			}
		}
		$out['menu_items'][] = array('page_id' => $pid, 'name' => $name, 'url' => $url);
	}
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="menu-i18n-lang' . $_lid . '-' . date('Y-m-d-His') . '.json"');
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// --- Menu i18n: import JSON for one language ---
if (isset($get['u']) && $get['u'] === 'import_menu_i18n' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$import_err = '';
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	$_lid = isset($_POST['lang_id']) ? (int)$_POST['lang_id'] : 0;
	if ($_lid <= 0 && isset($get['lang_id']) && (int)$get['lang_id'] > 0) {
		$_lid = (int)$get['lang_id'];
	}
	$_redirect_lang = $_lid;
	if ($_redirect_lang <= 0 && !empty($_SERVER['HTTP_REFERER']) && preg_match('/[?&]lang_id=(\d+)/', $_SERVER['HTTP_REFERER'], $m)) {
		$_redirect_lang = (int)$m[1];
	}
	if (!$file) {
		$import_err = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		$data = $raw !== false ? @json_decode($raw, true) : null;
		if (!$data || !isset($data['menu_items']) || !is_array($data['menu_items'])) {
			$import_err = 'Invalid JSON or missing "menu_items" array.';
		} elseif (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
			$import_err = 'Table content_i18n not found.';
		} else {
			// Use lang_id from JSON file when present (so French file always imports to lang 3)
			if (isset($data['lang_id']) && (int)$data['lang_id'] > 0) {
				$_lid = (int)$data['lang_id'];
				$_redirect_lang = $_lid;
			}
			if ($_lid <= 0) {
				$import_err = 'Language not set. Select language above or use a JSON file that contains "lang_id".';
			} else {
				$updated = 0;
				foreach ($data['menu_items'] as $item) {
					$pid = isset($item['page_id']) ? (int)$item['page_id'] : 0;
					if ($pid <= 0) continue;
					$name = isset($item['name']) ? (string)$item['name'] : '';
					$url = isset($item['url']) ? trim((string)$item['url'], '/') : '';
					$res = admin_i18n_save('pages', $pid, $_lid, array('name' => $name, 'url' => $url, 'status' => 'published'));
					if ($res['ok']) $updated++;
				}
				$_SESSION['admin_flash_success'] = 'Menu i18n import: ' . $updated . ' item(s) updated for language ' . $_lid . '.';
				header('Location: /admin.php?m=pages&u=i18n_sys&lang_id=' . $_redirect_lang);
				exit;
			}
		}
	}
	$_SESSION['admin_flash_error'] = $import_err;
	header('Location: /admin.php?m=pages&u=i18n_sys' . ($_redirect_lang > 0 ? '&lang_id=' . $_redirect_lang : ''));
	exit;
}

// --- System dictionary: export JSON (common.php for one language) ---
if (isset($get['u']) && $get['u'] === 'export_dictionary' && isset($get['lang_id']) && ($_lid = (int)$get['lang_id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$dict = admin_load_common_dict($_lid);
	$out = array('exported_at' => date('c'), 'lang_id' => $_lid, 'dictionary' => 'common', 'keys' => $dict);
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="dictionary-common-lang' . $_lid . '-' . date('Y-m-d-His') . '.json"');
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// --- System dictionary: import JSON (replace common.php for one language) ---
if (isset($get['u']) && $get['u'] === 'import_dictionary' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$import_err = '';
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	$_lid = isset($_POST['lang_id']) ? (int)$_POST['lang_id'] : 0;
	if ($_lid <= 0 && isset($get['lang_id']) && (int)$get['lang_id'] > 0) {
		$_lid = (int)$get['lang_id'];
	}
	$_redirect_lang = $_lid;
	if ($_redirect_lang <= 0 && !empty($_SERVER['HTTP_REFERER']) && preg_match('/[?&]lang_id=(\d+)/', $_SERVER['HTTP_REFERER'], $m)) {
		$_redirect_lang = (int)$m[1];
	}
	if (!$file) {
		$import_err = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		$data = $raw !== false ? @json_decode($raw, true) : null;
		if (!$data || !isset($data['keys']) || !is_array($data['keys'])) {
			$import_err = 'Invalid JSON or missing "keys" object.';
		} else {
			if (isset($data['lang_id']) && (int)$data['lang_id'] > 0) {
				$_lid = (int)$data['lang_id'];
				$_redirect_lang = $_lid;
			}
			if ($_lid <= 0) {
				$import_err = 'Language not set. Select language above or use a JSON file that contains "lang_id".';
			} else {
				$res = admin_save_common_dict($_lid, $data['keys']);
				if ($res['ok']) {
					$_SESSION['admin_flash_success'] = 'Dictionary imported for language ' . $_lid . '.';
					header('Location: /admin.php?m=pages&u=i18n_sys&lang_id=' . $_redirect_lang);
					exit;
				}
				$import_err = $res['message'];
			}
		}
	}
	$_SESSION['admin_flash_error'] = $import_err;
	header('Location: /admin.php?m=pages&u=i18n_sys' . ($_redirect_lang > 0 ? '&lang_id=' . $_redirect_lang : ''));
	exit;
}

// --- Menu & system i18n sub-page (Canonical menu + per-language names/URLs + export/import) ---
if (isset($get['u']) && $get['u'] === 'i18n_sys') {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_langs = admin_i18n_enabled_languages();
	if (empty($i18n_langs)) {
		$i18n_langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
	}
	$canonical = mysql_select("SELECT id, module, level, name, url FROM pages WHERE display=1 AND menu=1 AND level<3 ORDER BY left_key", 'rows') ?: array();
	$i18n_lang_id = isset($get['lang_id']) ? (int)$get['lang_id'] : 0;
	if ($i18n_lang_id <= 0 && !empty($i18n_langs)) {
		$i18n_lang_id = (int)$i18n_langs[0]['id'];
	}

	// Save menu i18n (per-language name/url)
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_menu_i18n']) && $i18n_lang_id > 0) {
		$saved = 0;
		foreach ($canonical as $p) {
			$pid = (int)$p['id'];
			$name_key = 'menu_name_' . $pid;
			$url_key = 'menu_url_' . $pid;
			if (!array_key_exists($name_key, $_POST) && !array_key_exists($url_key, $_POST)) continue;
			$name = isset($_POST[$name_key]) ? (string)$_POST[$name_key] : '';
			$url = isset($_POST[$url_key]) ? trim((string)$_POST[$url_key], '/') : '';
			$res = admin_i18n_save('pages', $pid, $i18n_lang_id, array('name' => $name, 'url' => $url, 'status' => 'published'));
			if ($res['ok']) $saved++;
		}
		$_SESSION['admin_flash_success'] = $saved ? 'Saved ' . $saved . ' menu item(s) for this language.' : 'No changes.';
		header('Location: /admin.php?m=pages&u=i18n_sys&lang_id=' . $i18n_lang_id);
		exit;
	}

	// Save system messages (dictionary common.php)
	$system_message_keys = array(
		'txt_no_page_text' => '404 / Page not found text',
		'no_content' => 'No content',
		'back_to_home' => 'Back to Home (button on 404)',
		'msg_no_results' => 'No results message',
		'footer_about_us' => 'Footer: About Us',
		'footer_terms' => 'Footer: Terms',
		'footer_privacy' => 'Footer: Privacy',
		'footer_responsible' => 'Footer: Responsible Gambling',
		'footer_responsible_text' => 'Footer: Responsible text',
		'footer_play_label' => 'Footer: Play responsibly label',
		'footer_play_responsibly' => 'Footer: Play responsibly text',
		'footer_copyright' => 'Footer: Copyright ({year})',
		'hero_subtitle' => 'Hero: Subtitle (gold line)',
		'hero_h1_prefix' => 'Hero: H1 prefix',
		'hero_h1_accent_1' => 'Hero: H1 accent (green)',
		'hero_h1_mid' => 'Hero: H1 middle',
		'hero_h1_accent_2' => 'Hero: H1 accent (red)',
		'hero_h1_tail' => 'Hero: H1 ending',
		'hero_lead' => 'Hero: Lead paragraph',
		'hero_cta' => 'Hero: Primary CTA',
		'hero_explore' => 'Hero: Secondary CTA',
		'popup_join' => 'Popup: Join our',
		'popup_partner' => 'Popup: Partner',
		'aria_close' => 'Aria: Close',
		'go_to_top' => 'Go to top',
	);
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_system_messages']) && $i18n_lang_id > 0) {
		$dict = admin_load_common_dict($i18n_lang_id);
		if (empty($dict) && $i18n_lang_id !== 1) {
			$dict = admin_load_common_dict(1);
		}
		foreach (array_keys($system_message_keys) as $key) {
			$post_key = 'sys_' . $key;
			if (array_key_exists($post_key, $_POST)) {
				$dict[$key] = (string)$_POST[$post_key];
			}
		}
		$res = admin_save_common_dict($i18n_lang_id, $dict);
		if ($res['ok']) {
			$_SESSION['admin_flash_success'] = 'System messages saved for this language.';
		} else {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=pages&u=i18n_sys&lang_id=' . $i18n_lang_id);
		exit;
	}

	$content = '<div class="card"><div class="card-body">';
	$content .= '<p class="mb-3"><a href="/admin.php?m=pages" class="btn btn-sm btn-outline-secondary">&larr; Back to site tree</a> ';
	$content .= '<a href="/admin.php?m=pages&u=export_import" class="btn btn-sm btn-outline-secondary">Export / Import pages</a></p>';
	$content .= '<h5 class="mb-3">Menu &amp; system i18n</h5>';

	// Canonical (read-only)
	$content .= '<h6 class="mt-4 mb-2">Canonical menu</h6>';
	$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Module</th><th>Level</th><th>Name (default)</th><th>URL (default)</th></tr></thead><tbody>';
	foreach ($canonical as $p) {
		$content .= '<tr><td>' . (int)$p['id'] . '</td><td>' . htmlspecialchars((string)$p['module']) . '</td><td>' . (int)$p['level'] . '</td>';
		$content .= '<td>' . htmlspecialchars((string)$p['name']) . '</td><td>' . htmlspecialchars(isset($p['url']) ? $p['url'] : '') . '</td></tr>';
	}
	$content .= '</tbody></table></div>';

	// Per-language editor
	$content .= '<h6 class="mt-4 mb-2">By language</h6>';
	$base_i18n = '/admin.php?m=pages&u=i18n_sys';
	$content .= '<form method="get" class="mb-3"><input type="hidden" name="m" value="pages"/><input type="hidden" name="u" value="i18n_sys"/>';
	$content .= '<label class="me-2">Language:</label><select name="lang_id" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">';
	foreach ($i18n_langs as $l) {
		$sel = ((int)$l['id'] === $i18n_lang_id) ? ' selected' : '';
		$content .= '<option value="' . (int)$l['id'] . '"' . $sel . '>' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</option>';
	}
	$content .= '</select></form>';

	$content .= '<form method="post" class="mb-4">';
	$content .= '<input type="hidden" name="save_menu_i18n" value="1"/>';
	$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Canonical name</th><th>Name for this language</th><th>URL for this language</th></tr></thead><tbody>';
	$i18n_rows = array();
	if ($i18n_lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$ids = array_map(function($p) { return (int)$p['id']; }, $canonical);
		if (!empty($ids)) {
			$rows = mysql_select("SELECT entity_id, name, url FROM content_i18n WHERE entity='pages' AND lang_id=" . $i18n_lang_id . " AND entity_id IN (" . implode(',', $ids) . ")", 'rows') ?: array();
			foreach ($rows as $r) {
				$i18n_rows[(int)$r['entity_id']] = $r;
			}
		}
	}
	foreach ($canonical as $p) {
		$pid = (int)$p['id'];
		$cur = isset($i18n_rows[$pid]) ? $i18n_rows[$pid] : array();
		$cur_name = isset($cur['name']) ? (string)$cur['name'] : (string)$p['name'];
		$cur_url = isset($cur['url']) ? trim((string)$cur['url'], '/') : (isset($p['url']) ? trim((string)$p['url'], '/') : '');
		$content .= '<tr><td>' . $pid . '</td><td class="text-muted">' . htmlspecialchars((string)$p['name']) . '</td>';
		$content .= '<td><input type="text" class="form-control form-control-sm" name="menu_name_' . $pid . '" value="' . htmlspecialchars($cur_name) . '" placeholder="' . htmlspecialchars((string)$p['name']) . '"/></td>';
		$content .= '<td><input type="text" class="form-control form-control-sm" name="menu_url_' . $pid . '" value="' . htmlspecialchars($cur_url) . '" placeholder="' . htmlspecialchars(isset($p['url']) ? $p['url'] : '') . '"/></td></tr>';
	}
	$content .= '</tbody></table></div>';
	$content .= '<button type="submit" class="btn btn-primary">Save menu for this language</button>';
	$content .= '</form>';

	// Export / Import JSON for this language
	$content .= '<h6 class="mt-4 mb-2">Export / Import JSON (this language)</h6>';
	$content .= '<div class="mb-2"><a href="/admin.php?m=pages&u=export_menu_i18n&lang_id=' . $i18n_lang_id . '" class="btn btn-sm btn-primary">Export JSON</a></div>';
	$content .= '<form method="post" action="/admin.php?m=pages&u=import_menu_i18n&lang_id=' . (int)$i18n_lang_id . '" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end">';
	$content .= '<input type="hidden" name="lang_id" value="' . (int)$i18n_lang_id . '"/>';
	$content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required/></div>';
	$content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-sm btn-secondary">Import JSON</button></div>';
	$content .= '</form>';

	// System messages (404, footer, hero, etc.) — dictionary common.php
	$content .= '<hr class="my-4"><h6 class="mt-4 mb-2">System messages (404, footer, hero, etc.)</h6>';
	$sys_dict = admin_load_common_dict($i18n_lang_id);
	if (empty($sys_dict) && $i18n_lang_id !== 1) {
		$sys_dict = admin_load_common_dict(1);
	}
	$content .= '<form method="post" class="mb-4">';
	$content .= '<input type="hidden" name="save_system_messages" value="1"/>';
	$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Key</th><th>Value for this language</th></tr></thead><tbody>';
	foreach ($system_message_keys as $key => $label) {
		$val = isset($sys_dict[$key]) ? (string)$sys_dict[$key] : '';
		$content .= '<tr><td class="text-nowrap"><label class="mb-0 small text-muted">' . htmlspecialchars($label) . '</label><br><code>' . htmlspecialchars($key) . '</code></td>';
		$content .= '<td><input type="text" class="form-control form-control-sm" name="sys_' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '" placeholder="' . htmlspecialchars($label) . '"/></td></tr>';
	}
	$content .= '</tbody></table></div>';
	$content .= '<button type="submit" class="btn btn-primary">Save system messages</button>';
	$content .= '</form>';

	$content .= '<h6 class="mt-3 mb-2">Export / Import dictionary (common) for this language</h6>';
	$content .= '<div class="mb-2"><a href="/admin.php?m=pages&u=export_dictionary&lang_id=' . $i18n_lang_id . '" class="btn btn-sm btn-primary">Export dictionary JSON</a></div>';
	$content .= '<form method="post" action="/admin.php?m=pages&u=import_dictionary&lang_id=' . (int)$i18n_lang_id . '" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end">';
	$content .= '<input type="hidden" name="lang_id" value="' . (int)$i18n_lang_id . '"/>';
	$content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required/></div>';
	$content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-sm btn-secondary">Import dictionary JSON</button></div>';
	$content .= '</form>';

	$content .= '</div></div>';
	$table = null;
	$filter = array();
}

// --- Export / Import sub-page ---
if (isset($get['u']) && $get['u'] === 'export_import') {
	$ei_success = isset($get['success']) && $get['success'] == '1';
	$ei_inserted = isset($get['inserted']) ? (int)$get['inserted'] : 0;
	$ei_error = isset($get['import_error']) ? htmlspecialchars($get['import_error'], ENT_QUOTES, 'UTF-8') : '';

	$content = '<div class="card"><div class="card-body">';
	$content .= '<p class="mb-3"><a href="/admin.php?m=pages" class="btn btn-sm btn-outline-secondary">&larr; Back to site tree</a></p>';
	$content .= '<h5 class="mb-3">Export / Import pages</h5>';

	if ($ei_success) {
		$content .= '<div class="alert alert-success mb-3">Import completed. ' . $ei_inserted . ' record(s) inserted.</div>';
	}
	if ($ei_error) {
		$content .= '<div class="alert alert-danger mb-3">' . $ei_error . '</div>';
	}

	$content .= '<div class="mb-4"><p class="mb-1"><strong>Export</strong></p>';
	$content .= '<a href="/admin.php?m=pages&u=export_pages" class="btn btn-primary">Download JSON</a></div>';

	$content .= '<div><p class="mb-1"><strong>Import</strong></p>';
	$content .= '<form method="post" action="/admin.php?m=pages&u=import_pages" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end" onsubmit="return document.getElementById(\'confirm_replace\').checked;">';
	$content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required /></div>';
	$content .= '<div class="form-group mr-2 mb-2"><label class="d-block"><input type="checkbox" name="confirm_replace" id="confirm_replace" value="1" /> Confirm: all data will be completely replaced</label></div>';
	$content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-danger">Import and replace</button></div>';
	$content .= '</form></div>';

	$content .= '</div></div>';
	$table = null;
	$filter = array();
}
