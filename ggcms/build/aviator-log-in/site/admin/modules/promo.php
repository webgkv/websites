<?php
/**
 * Content → Promo: push landing pages (active / archive, optional end date).
 * Table: promo
 */

if (mysql_select("SHOW TABLES LIKE 'promo'", 'num_rows') === 0) {
	mysql_fn('query', "
		CREATE TABLE `promo` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL DEFAULT '',
			`name_2` varchar(512) NOT NULL DEFAULT '',
			`url` varchar(255) NOT NULL DEFAULT '',
			`text` longtext NOT NULL,
			`img` varchar(255) NOT NULL DEFAULT '',
			`category` varchar(32) NOT NULL DEFAULT 'active',
			`promo_unlimited` tinyint(1) NOT NULL DEFAULT 1,
			`date_end` datetime DEFAULT NULL,
			`display` tinyint(1) NOT NULL DEFAULT 1,
			`position` int(11) NOT NULL DEFAULT 0,
			`date` datetime DEFAULT NULL,
			`author_id` int(11) unsigned NOT NULL DEFAULT 0,
			`title` varchar(255) NOT NULL DEFAULT '',
			`description` text,
			`created_at` datetime DEFAULT NULL,
			`updated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `url` (`url`),
			KEY `display` (`display`),
			KEY `category` (`category`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	");
}

$promo_categories = array(
	'active' => 'Active',
	'archive' => 'Archive',
);

$where = '';
if (isset($get['category']) && isset($promo_categories[$get['category']])) {
	$where = " AND category = '" . mysql_res($get['category']) . "'";
}
$search = isset($get['search']) ? trim((string)$get['search']) : '';
$search_id_raw = isset($get['search_id']) ? trim((string)$get['search_id']) : '';
$search_id = ($search_id_raw !== '' && ctype_digit($search_id_raw)) ? (int)$search_id_raw : 0;
if ($search !== '') {
	$search_l = mysql_res(strtolower($search));
	$where_search = "(LOWER(name) LIKE '%" . $search_l . "%' OR LOWER(name_2) LIKE '%" . $search_l . "%' OR LOWER(url) LIKE '%" . $search_l . "%')";
	if (ctype_digit($search)) {
		$where_search .= " OR id=" . (int)$search;
	}
	$where .= " AND (" . $where_search . ")";
}
if ($search_id > 0) {
	$where .= " AND id=" . (int)$search_id;
}

$query = "SELECT * FROM promo WHERE 1 $where";

$table = array(
	'id'       => 'id:desc name date',
	'img'      => 'img',
	'name'     => '',
	'url'      => '',
	'category' => $promo_categories,
	'date_end' => 'date',
	'display'  => 'boolean',
);

$filter[] = array('search');
$filter[] = '<div class="form-group col-xl-2"><input class="form-control" type="number" min="1" step="1" name="search_id" value="' . htmlspecialchars($search_id_raw, ENT_QUOTES, 'UTF-8') . '" placeholder="ID"></div>';
$filter[] = '<div class="form-group col-xl-2"><button type="submit" class="btn btn-sm btn-primary">Search</button></div>';
$filter[] = '<select class="form-control form-control-sm d-inline-block w-auto ml-1" name="category" onchange="(function(v){var p=new URLSearchParams(location.search);if(v)p.set(\'category\',v);else p.delete(\'category\');location.href=\'/admin.php?\'+p.toString();})(this.value);">';
$filter[] = '<option value="">All categories</option>';
foreach ($promo_categories as $k => $v) {
	$filter[] = '<option value="' . htmlspecialchars($k) . '"' . (isset($get['category']) && $get['category'] === $k ? ' selected' : '') . '>' . htmlspecialchars($v) . '</option>';
}
$filter[] = '</select>';

$tabs = array(1 => 'Common', 2 => 'Main image');
$is_new = (!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new');

if ($is_new) {
	$form[1][] = array('input td6', 'name');
}
$form[1][] = array('input td4', 'name_2', array('name' => 'Short description'));
if ($is_new) {
	$form[1][] = array('input td4', 'url');
}
$form[1][] = array('select td3', 'category', array('value' => array(true, $promo_categories, '')));
$form[1][] = array('checkbox td2', 'promo_unlimited', array('name' => 'No end date'));
$form[1][] = array('input td3', 'date_end', array('name' => 'End date (UTC)'));
$form[1][] = array('input td2', 'position');
$form[1][] = array('input td2', 'date');
$authors_list = @mysql_select("SELECT id, name FROM site_authors WHERE display=1 ORDER BY name ASC", 'array') ?: array();
$form[1][] = array('select td3', 'author_id', array('name' => 'Author (E-E-A-T)', 'value' => array(true, $authors_list, '--- Default ---')));
$form[1][] = array('checkbox td1', 'display');

if ($is_new) {
	$form[1][] = array('tinymce td12', 'text', array('attr' => 'style="height:500px"'));
	$form[1][] = array('seo', 'seo url title description');
}

$form[2][] = array('file td6', 'img', array('sizes' => array('' => '')));

if (isset($get['u']) && ($get['u'] === 'form' || $get['u'] === 'edit') && !empty($get['id']) && $get['id'] !== 'new' && isset($post['text'])) {
	$post['text'] = str_replace(array('{{PROMO_ID}}', '{{ID}}'), (string)(int)$get['id'], (string)$post['text']);
}

if (isset($get['u']) && $get['u'] === 'form' && isset($get['id']) && $get['id'] !== 'new' && ($pid = (int)$get['id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	if (!empty($get['i18n_clear'])) {
		$clear_lang_id = $i18n_lang_id > 0 ? $i18n_lang_id : 0;
		$res = admin_i18n_clear('promo', $pid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=content&tab=promo&u=form&id=' . $pid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id);
		exit;
	}
	if (!empty($_POST['i18n_clear'])) {
		$clear_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$res = admin_i18n_clear('promo', $pid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=content&tab=promo&u=form&id=' . $pid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id);
		exit;
	}
	if (!empty($_POST['i18n_save'])) {
		$save_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$payload = array(
			'url' => isset($_POST['i18n_url']) ? trim((string)$_POST['i18n_url'], '/') : '',
			'name' => isset($_POST['i18n_name']) ? (string)$_POST['i18n_name'] : '',
			'title' => isset($_POST['i18n_title']) ? (string)$_POST['i18n_title'] : '',
			'description' => isset($_POST['i18n_description']) ? (string)$_POST['i18n_description'] : '',
			'content' => isset($_POST['i18n_content']) ? (string)$_POST['i18n_content'] : '',
			'status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : 'draft',
		);
		$res = admin_i18n_save('promo', $pid, $save_lang_id, $payload);
		if (!empty($res['ok'])) {
			admin_i18n_sync_canonical_row_to_base_table('promo', $pid, $save_lang_id);
		}
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=content&tab=promo&u=form&id=' . $pid . '&ftab=1&i18n_lang_id=' . (int)$save_lang_id);
		exit;
	}
	$base_url = '/admin.php?m=content&tab=promo&u=form&id=' . $pid . '&ftab=1';
	$defaults = array(
		'url' => isset($post['url']) ? (string)$post['url'] : '',
		'name' => isset($post['name']) ? (string)$post['name'] : '',
		'title' => isset($post['title']) ? (string)$post['title'] : '',
		'description' => isset($post['description']) ? (string)$post['description'] : '',
		'content' => isset($post['text']) ? (string)$post['text'] : '',
	);
	$form[1][] = admin_i18n_render_form('promo', $pid, $i18n_lang_id, $base_url, $defaults);
}
