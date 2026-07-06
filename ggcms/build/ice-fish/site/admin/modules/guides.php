<?php
/**
 * Content → Guides: list, add/edit, export/import JSON.
 */

require_once(ROOT_DIR . 'functions/guides_categories_func.php');
$guide_categories = guides_categories_get_map('', false);
if (empty($guide_categories)) {
	$guide_categories = array(
		'analysis'       => 'Analysis',
		'bonus'          => 'Bonus',
		'how-to-win'     => 'How to Win',
		'signals'        => 'Signals',
		'crash-gambling' => 'Crash Gambling',
	);
}

$where = '';
if (isset($get['category']) && isset($guide_categories[$get['category']])) {
    $where = " AND category = '" . mysql_res($get['category']) . "'";
}
$search = isset($get['search']) ? trim((string)$get['search']) : '';
$search_id_raw = isset($get['search_id']) ? trim((string)$get['search_id']) : '';
$search_id = ($search_id_raw !== '' && ctype_digit($search_id_raw)) ? (int)$search_id_raw : 0;
if ($search !== '') {
    $search_l = mysql_res(strtolower($search));
    $where_search = "(LOWER(name) LIKE '%" . $search_l . "%' OR LOWER(name_2) LIKE '%" . $search_l . "%')";
    if (ctype_digit($search)) {
        $where_search .= " OR id=" . (int)$search;
    }
    $where .= " AND (" . $where_search . ")";
}
if ($search_id > 0) {
    $where .= " AND id=" . (int)$search_id;
}

$query = "SELECT * FROM guides WHERE 1 $where";

$table = array(
    'id'       => 'position:desc name date',
    'img'      => 'img',
    'name'     => '',
    'category' => $guide_categories,
    'date'     => 'date',
    'display'  => 'boolean',
);

$filter[] = array('search');
$filter[] = '<div class="form-group col-xl-2"><input class="form-control" type="number" min="1" step="1" name="search_id" value="' . htmlspecialchars($search_id_raw, ENT_QUOTES, 'UTF-8') . '" placeholder="ID"></div>';
$filter[] = '<div class="form-group col-xl-2"><button type="submit" class="btn btn-sm btn-primary">Search</button></div>';
$filter[] = '<select class="form-control form-control-sm d-inline-block w-auto ml-1" name="category" onchange="(function(v){var p=new URLSearchParams(location.search);if(v)p.set(\'category\',v);else p.delete(\'category\');location.href=\'/admin.php?\'+p.toString();})(this.value);">';
$filter[] = '<option value="">All categories</option>';
foreach ($guide_categories as $k => $v) {
    $filter[] = '<option value="' . htmlspecialchars($k) . '"' . (isset($get['category']) && $get['category'] === $k ? ' selected' : '') . '>' . htmlspecialchars($v) . '</option>';
}
$filter[] = '</select>';
$filter[] = '<a href="/admin.php?m=content&tab=guides&u=export_import" class="btn btn-sm btn-outline-secondary ml-2"><i data-feather="download" class="mr-1"></i>Export / Import</a>';

$tabs = array(1 => 'Common', 2 => 'Main image');
$is_new = (!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new');

if ($is_new) {
	$form[1][] = array('input td6', 'name');
}
$form[1][] = array('input td4', 'name_2', array('name' => 'Short description'));
$form[1][] = array('select td3', 'category', array('value' => array(true, $guide_categories, '')));
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

// Replace {{GUIDE_ID}} placeholders so images render in TinyMCE during edit
if (isset($get['u']) && ($get['u'] === 'form' || $get['u'] === 'edit') && !empty($get['id']) && $get['id'] !== 'new' && isset($post['text'])) {
	$post['text'] = str_replace(array('{{GUIDE_ID}}', '{{ID}}'), (string)(int)$get['id'], (string)$post['text']);
}

// Translations (content_i18n) — directly on Tab 1
if (isset($get['u']) && $get['u'] === 'form' && isset($get['id']) && $get['id'] !== 'new' && ($gid = (int)$get['id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	// Full-page clear (from "Del translate" link) so form values are reloaded reliably.
	if (!empty($get['i18n_clear'])) {
		$clear_lang_id = $i18n_lang_id > 0 ? $i18n_lang_id : 0;
		$res = admin_i18n_clear('guides', $gid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=guides&u=form&id=' . (int)$gid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id;
		header('Location: ' . $redirect);
		exit;
	}
	if (!empty($_POST['i18n_clear'])) {
		$clear_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$res = admin_i18n_clear('guides', $gid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=guides&u=form&id=' . (int)$gid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id;
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
			'content' => isset($_POST['i18n_content']) ? (string)$_POST['i18n_content'] : '',
			'status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : 'draft',
		);
		$res = admin_i18n_save('guides', $gid, $save_lang_id, $payload);
		if (!empty($res['ok'])) {
			admin_i18n_sync_canonical_row_to_base_table('guides', $gid, $save_lang_id);
		}
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=guides&u=form&id=' . (int)$gid . '&ftab=1&i18n_lang_id=' . (int)$save_lang_id;
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}
	$base_url = '/admin.php?m=content&tab=guides&u=form&id=' . (int)$gid . '&ftab=1';
	$defaults = array(
		'url' => isset($post['url']) ? (string)$post['url'] : '',
		'name' => isset($post['name']) ? (string)$post['name'] : '',
		'title' => isset($post['title']) ? (string)$post['title'] : '',
		'description' => isset($post['description']) ? (string)$post['description'] : '',
		'content' => isset($post['text']) ? (string)$post['text'] : '',
	);
	// Same pattern as pages.php: for the canonical (source) language, Translations tab shows Common-tab data.
	$_canonical_lang_id = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($vr && $vr['value'] !== '') {
			$dec = json_decode($vr['value'], true);
			if (is_array($dec) && isset($dec['source_lang_id'])) {
				$_canonical_lang_id = (int)$dec['source_lang_id'];
			}
		}
	}
	$i18n_options = array('canonical_lang_id' => $_canonical_lang_id);
	$form[1][] = admin_i18n_render_form('guides', $gid, $i18n_lang_id, $base_url, $defaults, $i18n_options);
}

// --- Export: download JSON ---
if (isset($get['u']) && $get['u'] === 'export_guides') {
    $cols = mysql_select("SHOW COLUMNS FROM `guides`", 'rows');
    $rows = mysql_select("SELECT * FROM `guides` ORDER BY category, position, id", 'rows');
    if (!$cols) $rows = array();
    $out = array(
        'exported_at' => date('c'),
        'table'       => 'guides',
        'columns'     => $cols ? array_column($cols, 'Field') : array(),
        'rows'        => $rows ?: array(),
        'count'       => count($rows ?: array()),
    );
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="guides-' . date('Y-m-d-His') . '.json"');
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// --- Import: upload JSON, merge or replace ---
if (isset($get['u']) && $get['u'] === 'import_guides' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $import_error = '';
    $file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
    $replace = !empty($_POST['replace']);
    if (!$file) {
        $import_error = 'Please select a JSON file.';
    } else {
        $raw = file_get_contents($file['tmp_name']);
        $data = @json_decode($raw, true);
        if (!$data || !isset($data['rows']) || !is_array($data['rows'])) {
            $import_error = 'Invalid JSON or missing "rows" array.';
        } elseif (isset($data['table']) && $data['table'] !== 'guides') {
            $import_error = 'This file is not a guides export.';
        } else {
            $columns = isset($data['columns']) && is_array($data['columns']) ? $data['columns'] : array();
            $allowed = array('id','category','name','url','name_2','text','img','display','position','date','created_at','title','description');
            $table_exists = mysql_select("SHOW TABLES LIKE 'guides'", 'num_rows') > 0;
            if (!$table_exists) {
                mysql_fn('query', "
                    CREATE TABLE `guides` (
                        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                        `category` varchar(64) NOT NULL DEFAULT '',
                        `name` varchar(255) NOT NULL DEFAULT '',
                        `url` varchar(255) NOT NULL DEFAULT '',
                        `name_2` varchar(512) NOT NULL DEFAULT '',
                        `text` longtext NOT NULL,
                        `img` varchar(255) NOT NULL DEFAULT '',
                        `display` tinyint(1) NOT NULL DEFAULT 1,
                        `position` int(11) NOT NULL DEFAULT 0,
                        `date` datetime DEFAULT NULL,
                        `created_at` datetime DEFAULT NULL,
                        `title` varchar(255) NOT NULL DEFAULT '',
                        `description` text,
                        PRIMARY KEY (`id`),
                        KEY `category` (`category`),
                        KEY `display` (`display`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            }
            if ($replace && $table_exists) {
                mysql_fn('query', 'TRUNCATE TABLE `guides`');
            }
            $inserted = 0;
            foreach ($data['rows'] as $row) {
                if (!is_array($row)) continue;
                $filtered = array();
                foreach ($row as $k => $v) {
                    if (in_array($k, $allowed)) $filtered[$k] = $v;
                }
                if (empty($filtered['name'])) continue;
                unset($filtered['id']);
                if (!isset($filtered['created_at'])) $filtered['created_at'] = date('Y-m-d H:i:s');
                $res = mysql_fn('insert', 'guides', $filtered);
                if ($res !== false) $inserted++;
            }
            header('Location: /admin.php?m=content&tab=guides&import_ok=1&inserted=' . $inserted);
            exit;
        }
    }
    header('Location: /admin.php?m=content&tab=guides&u=export_import&import_error=' . urlencode($import_error));
    exit;
}

// --- Export/Import UI (no table list when this tab) ---
if (isset($get['u']) && $get['u'] === 'export_import') {
    $table = null;
    $content = '<div class="card"><div class="card-body">';
    $content .= '<p class="mb-3"><a href="/admin.php?m=content&tab=guides" class="btn btn-sm btn-outline-secondary">&larr; Back to Guides</a></p>';
    $content .= '<h5 class="mb-3">Export / Import guides</h5>';
    if (!empty($get['import_ok'])) {
        $content .= '<div class="alert alert-success mb-3">Import completed. ' . (int)@$get['inserted'] . ' record(s) added.</div>';
    }
    if (!empty($get['import_error'])) {
        $content .= '<div class="alert alert-danger mb-3">' . htmlspecialchars($get['import_error']) . '</div>';
    }
    $content .= '<div class="mb-4"><p class="mb-1"><strong>Export</strong></p>';
    $content .= '<a href="/admin.php?m=content&tab=guides&u=export_guides" class="btn btn-primary">Download JSON</a></div>';
    $content .= '<div><p class="mb-1"><strong>Import</strong></p>';
    $content .= '<form method="post" action="/admin.php?m=content&tab=guides&u=import_guides" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end">';
    $content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required /></div>';
    $content .= '<div class="form-group mr-2 mb-2"><label class="d-block"><input type="checkbox" name="replace" value="1" /> Replace all before import</label></div>';
    $content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-secondary">Import</button></div>';
    $content .= '</form></div></div></div>';
    return;
}

// Show import success message on list page (after redirect)
if (isset($get['import_ok']) && isset($get['inserted'])) {
    $content = '<div class="alert alert-success mb-3">Import completed. ' . (int)$get['inserted'] . ' record(s) added.</div>';
}
