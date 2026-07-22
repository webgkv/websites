<?php

// Form edit handler
/*
 *  v1.4.4 - html_array для таблицы
 *  v1.4.14 - event_func
 *  v1.4.15 - multiple
 */

// Ensure we always return parseable JSON for iframe/ajaxSubmit.
// Any PHP warnings/notices or fatals before the final echo can break JSON parsing in JS,
// resulting in "parsererror/Unexpected end of JSON input".
$__admin_edit_ob_started = false;
try {
	if (ob_get_level() >= 0) {
		ob_start();
		$__admin_edit_ob_started = true;
	}
} catch (Throwable $e) {}

register_shutdown_function(function () use (&$__admin_edit_ob_started) {
	$err = error_get_last();
	if (!$err) return;
	// Fatal errors that would otherwise yield empty/HTML output.
	$fatal_types = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
	if (!in_array($err['type'], $fatal_types, true)) return;

	// Drop any buffered output so we can respond cleanly.
	if ($__admin_edit_ob_started) {
		while (ob_get_level() > 0) {
			@ob_end_clean();
		}
	}
	if (!headers_sent()) {
		header('Content-Type: text/html; charset=utf-8');
	}
	$msg = 'Fatal error: ' . (isset($err['message']) ? (string)$err['message'] : 'unknown') .
		' in ' . (isset($err['file']) ? basename((string)$err['file']) : 'unknown') .
		':' . (isset($err['line']) ? (int)$err['line'] : 0);
	echo '<textarea>' . json_encode(array('error' => $msg), JSON_HEX_AMP) . '</textarea>';
});

// preserve id from POST when form action strips it from URL (e.g. pages edit modal)
if ((!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new') && isset($_POST['id']) && (int)$_POST['id'] > 0) {
	$get['id'] = (int)$_POST['id'];
}

// HARD DEBUG: log endpoint hit and POST keys when edit_debug=1 is enabled.
$__edit_debug_on = (!empty($get['edit_debug']) && (string)$get['edit_debug'] === '1')
	|| (!empty($_POST['edit_debug']) && (string)$_POST['edit_debug'] === '1');
if ($__edit_debug_on) {
	if (!function_exists('system_log_add')) {
		@require_once(ROOT_DIR . 'functions/system_log.php');
	}
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'warning', 'HARD edit.php hit', array(
			'get' => array(
				'u' => isset($get['u']) ? (string)$get['u'] : '',
				'id' => isset($get['id']) ? (int)$get['id'] : 0,
				'm' => isset($get['m']) ? (string)$get['m'] : '',
			),
			'post_keys' => is_array($_POST) ? array_keys($_POST) : array(),
			'i18n_save' => isset($_POST['i18n_save']) ? 1 : 0,
			'i18n_clear' => isset($_POST['i18n_clear']) ? 1 : 0,
			'i18n_lang_id' => isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : 0,
			'i18n_status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : '',
			'i18n_content_len' => isset($_POST['i18n_content']) ? strlen((string)$_POST['i18n_content']) : 0,
		));
	}
}
// single-page import from modal (main form submits with import=1 and json_file)
if (isset($get['m']) && $get['m'] === 'pages' && isset($_POST['import']) && (int)$_POST['import'] === 1 && isset($get['id']) && (int)$get['id'] > 0) {
	$page_id = (int)$get['id'];
	if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
		header('Location: /admin.php?m=pages&import_page_error=' . urlencode('Please select a JSON file.') . '&import_page_id=' . $page_id);
		exit;
	}
	$import_error = '';
	$file = $_FILES['json_file'];
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
	header('Location: /admin.php?m=pages&import_page_error=' . urlencode($import_error) . '&import_page_id=' . $page_id);
	exit;
}
// single-casino import (form submits with u=edit and import_single=1 + json_file)
// Note: form can be opened with m=content&tab=casinos OR m=casino_articles (from table data-module)
$cid = (int)(isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new' ? $get['id'] : (isset($_POST['id']) ? $_POST['id'] : 0));
$is_casino_import = $cid > 0 && isset($_POST['import_single']) && (int)$_POST['import_single'] === 1
	&& (($get['m'] === 'content' && isset($get['tab']) && $get['tab'] === 'casinos') || $get['m'] === 'casino_articles');
if ($is_casino_import) {
	$list_url = 'm=content&tab=casinos';
	if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
		header('Location: /admin.php?' . $list_url . '&single_error=' . urlencode('Please select a JSON file.') . '&import_id=' . $cid);
		exit;
	}
	$raw = file_get_contents($_FILES['json_file']['tmp_name']);
	$data = $raw !== false ? @json_decode($raw, true) : null;
	if (!$data || !isset($data['row']) || !is_array($data['row'])) {
		header('Location: /admin.php?' . $list_url . '&single_error=' . urlencode('Invalid JSON or missing "row" object.') . '&import_id=' . $cid);
		exit;
	}
	if (isset($data['table']) && $data['table'] !== 'casino_articles') {
		header('Location: /admin.php?' . $list_url . '&single_error=' . urlencode('This file is not a casino article export.') . '&import_id=' . $cid);
		exit;
	}
	$allowed = array('name','url','name_2','text','img','display','position','date','title','description');
	$update = array('id' => $cid);
	foreach ($data['row'] as $k => $v) {
		if (in_array($k, $allowed, true)) $update[$k] = $v;
	}
	if (count($update) > 1 && mysql_fn('update', 'casino_articles', $update)) {
		// Redirect to list (full styled page), then template JS will open form modal with success
		$redirect = '/admin.php?m=content&tab=casinos&single_ok=1&import_id=' . $cid;
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}
	$redirect = '/admin.php?m=content&tab=casinos&single_error=' . urlencode('Nothing to update or update failed.') . '&import_id=' . $cid;
	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('redirect' => $redirect, 'error' => 1));
		exit;
	}
	header('Location: ' . $redirect);
	exit;
}
// Build and process POST array
$post = stripslashes_smart($_POST); //error_handler(1,serialize($post),1,1);

$edit_debug_on = false;
if ((!empty($get['edit_debug']) && (string)$get['edit_debug'] === '1')
	|| (!empty($post['edit_debug']) && (string)$post['edit_debug'] === '1')) {
	$edit_debug_on = true;
}

// i18n translation save can be submitted from the modal using u=edit.
// Translations tab renders i18n fields inside the main edit form, which posts to admin/actions/edit.php.
// Handle it here so content_i18n updates persist even when module handlers expect u=form.
$i18n_error_msg = '';
$handled_i18n_save = false;
$handled_i18n_clear = false;

// i18n save: when editing an existing record that supports translations, the language switcher
// (i18n_lang_id) is always present in POST. This ensures both main "Save" and tab "Save translation"
// clicks persist translatable fields and sync canonical language changes correctly.
if (($get['u'] ?? '') === 'edit'
	&& !empty($module['table'])
	&& (int)($get['id'] ?? 0) > 0
	&& !empty($post['i18n_lang_id'])
) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$entity = admin_i18n_entity_key(isset($get['m']) ? (string)$get['m'] : '', isset($module['table']) ? (string)$module['table'] : '');
	$entity_id = (int)$get['id'];
	$save_lang_id = isset($post['i18n_lang_id']) ? (int)$post['i18n_lang_id'] : 0;
	if ($save_lang_id > 0) {
		$handled_i18n_save = true;
		$payload = array(
			'url' => isset($post['i18n_url']) ? trim((string)$post['i18n_url'], '/') : '',
			'name' => isset($post['i18n_name']) ? (string)$post['i18n_name'] : '',
			'title' => isset($post['i18n_title']) ? (string)$post['i18n_title'] : '',
			'description' => isset($post['i18n_description']) ? (string)$post['i18n_description'] : '',
			'status' => isset($post['i18n_status']) ? (string)$post['i18n_status'] : 'draft',
		);
		// content field is present only when content_managed_elsewhere=false
		if (array_key_exists('i18n_content', $post)) $payload['content'] = (string)$post['i18n_content'];

		$res = admin_i18n_save($entity, $entity_id, $save_lang_id, $payload, $edit_debug_on);
		if (empty($res['ok'])) {
			$i18n_error_msg = isset($res['message']) ? (string)$res['message'] : 'i18n_save failed';
		} else {
			// Canonical language: mirror content_i18n back into base tables.
			// This makes `Common` and frontend reads consistent when editing on Translations tab.
			$canonical_lang_id = 1;
			if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
				$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
				if ($vr && $vr['value'] !== '') {
					$dec = json_decode($vr['value'], true);
					if (is_array($dec) && isset($dec['source_lang_id'])) $canonical_lang_id = (int)$dec['source_lang_id'];
				}
			}

			if ($save_lang_id === (int)$canonical_lang_id) {
				// Mirror persisted content_i18n (incl. SEO-trimmed meta) into base tables — not raw POST.
				admin_i18n_sync_canonical_row_to_base_table($entity, $entity_id, $save_lang_id);
			}
		}
	}
}

// Translations tab: "Del translate" (i18n_clear) submitted from modal.
if (($get['u'] ?? '') === 'edit'
	&& !empty($post['i18n_clear'])
	&& !empty($module['table'])
	&& (int)($get['id'] ?? 0) > 0
) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$entity = admin_i18n_entity_key(isset($get['m']) ? (string)$get['m'] : '', isset($module['table']) ? (string)$module['table'] : '');
	$entity_id = (int)$get['id'];
	$clear_lang_id = isset($post['i18n_lang_id']) ? (int)$post['i18n_lang_id'] : 0;
	if ($clear_lang_id > 0) {
		$handled_i18n_clear = true;
		$res = admin_i18n_clear($entity, $entity_id, $clear_lang_id, $edit_debug_on);
		// Reuse $i18n_error_msg for response payload.
		$i18n_error_msg = isset($res['ok']) && $res['ok'] ? '' : (isset($res['message']) ? (string)$res['message'] : 'i18n_clear failed');
	}
}

// Deferred redirect flag for i18n_save/clear so that main table updates and file uploads can still run.
$redirect_to_i18n = '';
$i18n_saved_successful = false;

if ($handled_i18n_save && !empty($post['i18n_lang_id'])) {
	if (!empty($i18n_error_msg)) {
		$data = array('error' => $i18n_error_msg);
		header('Content-Type: text/html; charset=utf-8');
		echo '<textarea>' . json_encode($data, JSON_HEX_AMP) . '</textarea>';
		exit;
	}
	$save_lang_id = isset($post['i18n_lang_id']) ? (int)$post['i18n_lang_id'] : 0;
	$redirect = '/admin.php?m=' . urlencode((string)$get['m']) . '&u=form&id=' . (int)$entity_id;
	if (isset($get['tab']) && $get['tab'] !== '') $redirect .= '&tab=' . urlencode((string)$get['tab']);
	if (isset($get['ftab']) && $get['ftab'] !== '') $redirect .= '&ftab=' . urlencode((string)$get['ftab']);
	if ($save_lang_id > 0) $redirect .= '&i18n_lang_id=' . (int)$save_lang_id;
	if (isset($get['inline']) && (string)$get['inline'] === '1') $redirect .= '&inline=1';
	$redirect_to_i18n = $redirect;
	$i18n_saved_successful = true;
}

if ($handled_i18n_clear && !empty($post['i18n_clear'])) {
	if (!empty($i18n_error_msg)) {
		$data = array('error' => $i18n_error_msg);
		header('Content-Type: text/html; charset=utf-8');
		echo '<textarea>' . json_encode($data, JSON_HEX_AMP) . '</textarea>';
		exit;
	}
	$clear_lang_id = isset($post['i18n_lang_id']) ? (int)$post['i18n_lang_id'] : 0;
	$redirect = '/admin.php?m=' . urlencode((string)$get['m']) . '&u=form&id=' . (int)$entity_id;
	if (isset($get['tab']) && $get['tab'] !== '') $redirect .= '&tab=' . urlencode((string)$get['tab']);
	if (isset($get['ftab']) && $get['ftab'] !== '') $redirect .= '&ftab=' . urlencode((string)$get['ftab']);
	if ($clear_lang_id > 0) $redirect .= '&i18n_lang_id=' . (int)$clear_lang_id;
	if (isset($get['inline']) && (string)$get['inline'] === '1') $redirect .= '&inline=1';
	$redirect_to_i18n = $redirect;
	$i18n_saved_successful = true;
}

// Let module preprocess $post on save (e.g. user_types serializes access_admin/access_editable)
if (isset($get['m']) && isset($get['u']) && $get['u'] === 'edit' && file_exists(ROOT_DIR . 'admin/modules/' . $get['m'] . '.php')) {
	require_once(ROOT_DIR . 'admin/modules/' . $get['m'] . '.php');
}
$data = array();
// Generate SEO fields
if (isset($post['seo'])) {
	if($post['seo']==1) {
		$data['seo'] = array();
		//v1.2.94 [3.164] form edit SEO
		if (isset($post['name']) AND isset($post['url'])) $data['seo']['url'] = $post['url'] = trunslit($post['name']);
		if (isset($post['title'])) $data['seo']['title'] = $post['title'] = $post['name'];
		if (isset($post['description'])) $data['seo']['description'] = $post['description'] = description((isset($post['about']) ? $post['about'].' ' : '').(isset($post['text']) ? $post['text'].' ' : '').$post['name']);
		// SEO fields for languages
		if ($config['multilingual']) {
			foreach ($config['languages'] as $k => $v) {
				// v1.2.32 — SEO fields are generated only if module is described as mirror in /admin/config_multilingual.php
				if (isset($config['lang_fields'][$module['table']])) {
					if (isset($post['name' . $v['id']]) AND isset($post['url' . $v['id']])) $data['seo']['url' . $v['id']] = $post['url' . $v['id']] = trunslit($post['name' . $v['id']]);
					if (isset($post['title' . $v['id']])) $data['seo']['title' . $v['id']] = $post['title' . $v['id']] = $post['name' . $v['id']];
					if (isset($post['description' . $v['id']])) $data['seo']['description' . $v['id']] = $post['description' . $v['id']] = description((isset($post['about' . $v['id']]) ? $post['about'] . ' ' : '') . (isset($post['text' . $v['id']]) ? $post['text' . $v['id']] . ' ' : '') . $post['name' . $v['id']]);
				}
			}
		}
	}
	unset($post['seo']);
}
// Nested sets (excluded from post)
if (isset($post['nested_sets'])) unset($post['nested_sets']);
// Depends
if (isset($config['depend'][$module['table']])) foreach ($config['depend'][$module['table']] as $key=>$value)
	$post[$key] = isset($post[$key]) ? implode(',',$post[$key]) : '';

// Load module
require_once(ROOT_DIR.'admin/modules/'.$get['m'].'.php');
// Edit allowed by default
if (!isset($table['_edit'])) $table = array_merge(array('_edit'=>true),$table);

// No edit permission
if ($table['_edit']===false) die();

// Extend form (multilingual)
multilingual();
//dd($post);
// For tree: drop parent/prev from post
if (is_array($form)) {
	// With tabs
	if (count($tabs)>0) {
		foreach ($form as $k => $v) {
			foreach ($v as $k1 => $v1) {
				if (is_array($v1)) {
					if (preg_match('/simple|file_multi/', $v1[0])) {
						// v1.3.8 remove temp key, not needed in DB
						if (isset($post[$v1[1]])) foreach ($post[$v1[1]] as $k=>$v) {
							if (isset($v['temp']) AND $v['temp']=='') unset($post[$v1[1]][$k]['temp']);
						}
						$post[$v1[1]] = isset($post[$v1[1]]) ? serialize($post[$v1[1]]) : '';
					}
					// Strip file_multi_db from post
					if ($v1[0] == 'file_multi_db' AND isset($post[$v1[1]])) {
						unset($post[$v1[1]]);
					}
					// multicheckbox / multiple
					if (preg_match('/multicheckbox|multiple/', $v1[0])) {
						// Empty field so checkbox can be unchecked
						if (empty($post[$v1[1]])) $post[$v1[1]] = '';
						elseif (is_array($post[$v1[1]])) {
							$post[$v1[1]] = implode(',', $post[$v1[1]]);
						}
					}
				}
			}
		}
	}
	// Without tabs
	else {
		foreach ($form as $k1 => $v1) {
			if (is_array($v1)) {
				if (preg_match('/simple|file_multi/', $v1[0])) {
					// Drop temp key before save
					if (isset($post[$v1[1]])) foreach ($post[$v1[1]] as $k=>$v) {
						if (isset($v['temp']) AND $v['temp']=='') unset($post[$v1[1]][$k]['temp']);
					}
					$post[$v1[1]] = isset($post[$v1[1]]) ? serialize($post[$v1[1]]) : '';
				}
				// Strip file_multi_db from post
				if ($v1[0] == 'file_multi_db' AND isset($post[$v1[1]])) {
					unset($post[$v1[1]]);
				}
				if (preg_match('/multicheckbox|multiple/', $v1[0])) {
					// Empty field so checkbox can be unchecked
					if (empty($post[$v1[1]])) $post[$v1[1]] = '';
					elseif (is_array($post[$v1[1]])) {
						$post[$v1[1]] = implode(',', $post[$v1[1]]);
					}
				}
			}
		}
	}
}
//dd($post);

// Keep only table columns in $post (strip form-only fields like import_single, json_file, etc.)
if (!empty($module['table'])) {
	$table_cols = mysql_select("SHOW COLUMNS FROM `" . $module['table'] . "`", 'rows');
	$allowed_keys = $table_cols ? array_column($table_cols, 'Field') : array();
	if (!empty($allowed_keys)) {
		$post = array_intersect_key($post, array_flip($allowed_keys));
	}
	// Sanitize base URL slug (converting underscores to hyphens and formatting)
	if (array_key_exists('url', $post) && trim((string)$post['url']) !== '') {
		$u = trim((string)$post['url'], '/');
		$u = str_replace('_', '-', $u);
		$u = trunslit($u);
		$u = mb_strtolower($u, 'UTF-8');
		$u = preg_replace('~[^a-z0-9-]+~u', '-', $u);
		$u = preg_replace('~-+~u', '-', $u);
		$post['url'] = trim($u, '-');
	}
	// Main image from media picker: store as files/media/… or images/games/… (no leading slash).
	if (isset($post['img']) && (string)$post['img'] !== '' && function_exists('media_library_normalize_db_path')) {
		$post['img'] = media_library_normalize_db_path($post['img']);
	}
}

// Do not write file-field columns until form_file() has verified disk / finished upload.
$deferred_file_values = array();
if (!empty($module['table']) && !empty($form) && function_exists('admin_deferred_file_field_keys')) {
	$deferred_file_keys = admin_deferred_file_field_keys($form, isset($tabs) ? $tabs : array());
	foreach ($deferred_file_keys as $dfk) {
		if (array_key_exists($dfk, $post)) {
			$deferred_file_values[$dfk] = $post[$dfk];
			unset($post[$dfk]);
		}
	}
}

// Same SEO limits on main table rows (English/source is audited from `pages` / `blog` / etc., not only content_i18n).
if (!empty($module['table']) && in_array((string)$module['table'], array('pages', 'guides', 'games', 'casino_articles', 'promo', 'blog'), true)) {
	if (!function_exists('translation_cluster_trim_seo_text')) {
		require_once ROOT_DIR . 'functions/translation_cluster.php';
	}
	foreach (array('name' => 70, 'title' => 70, 'description' => 160) as $col => $lim) {
		if (array_key_exists($col, $post) && trim((string)$post[$col]) !== '') {
			$post[$col] = translation_cluster_trim_seo_text($post[$col], $lim);
		}
	}
	if ((string)$module['table'] === 'blog' && array_key_exists('name_2', $post) && trim((string)$post['name_2']) !== '') {
		$post['name_2'] = translation_cluster_trim_seo_text($post['name_2'], 160);
	}
	if (isset($post['text']) && stripos((string)$post['text'], '/files/media/') !== false) {
		require_once ROOT_DIR . 'functions/media_library.php';
		require_once ROOT_DIR . 'functions/media_image.php';
		$purged = media_image_purge_missing_media_from_html((string)$post['text']);
		$post['text'] = $purged['html'];
	}
}

// Edit existing record
$old = false;
if ($get['id'] > 0) {
	$post['id'] = $get['id'];
	// Track changed fields
	$fields = array();
	$old = mysql_select("SELECT * FROM `" . $module['table'] . "` WHERE id=" . intval($get['id']), 'row');
	foreach ($post as $k => $v) {
		if ($v != $old[$k]) $fields[] = $k;
	}
	$fields = implode(',', $fields);

		$module_table = (string)$module['table'];
		$updated_rows = mysql_fn('update', $module_table, $post);

		// HARD verification for Common tab edits (guides/games/casino_articles/blog):
		// ensure DB content equals POSTed values (TinyMCE issues can send stale content).
		if (in_array($module_table, array('guides','games','casino_articles','promo','blog'), true) && !empty($post['text'])) {
			$after = mysql_select("
				SELECT url,name,title,description,text
				FROM `" . mysql_res($module_table) . "`
				WHERE id=" . (int)$get['id'] . "
				LIMIT 1
			", 'row');
			if ($after) {
				$mismatch_fields = array();
				foreach (array('url','name','title','description','text') as $col) {
					if (!array_key_exists($col, $post)) continue;
					$exp = isset($post[$col]) ? (string)$post[$col] : '';
					$got = isset($after[$col]) ? (string)$after[$col] : '';
					if (md5($exp) !== md5($got)) $mismatch_fields[] = $col;
				}
				if (!empty($mismatch_fields) && function_exists('system_log_add')) {
					system_log_add('translations', 'error', 'Common DB save verification mismatch', array(
						'entity' => $module_table,
						'entity_id' => (int)$get['id'],
						'updated_rows' => $updated_rows,
						'mismatch_fields' => $mismatch_fields,
						'content_len_post' => strlen((string)($post['text'] ?? '')),
						'content_len_db' => strlen((string)($after['text'] ?? '')),
					));
				}
			}
		}

		// Sync canonical content_i18n with Common tab edits.
		// Frontend for guides/games/casinos/blog prefers content_i18n when it exists.
		// So when you edit Common (canonical) we mirror it into content_i18n for source_lang_id.
		$sync_entities = array('guides', 'games', 'casino_articles', 'promo', 'blog');
		$sync_authors = ((string)($get['m'] ?? '') === 'authors' || (string)$module['table'] === 'site_authors');
		if (($sync_authors || in_array((string)$module['table'], $sync_entities, true))
			&& @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0
		) {
			$canonical_lang_id = 1;
			if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
				$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
				if ($vr && $vr['value'] !== '') {
					$dec = json_decode($vr['value'], true);
					if (is_array($dec) && isset($dec['source_lang_id'])) $canonical_lang_id = (int)$dec['source_lang_id'];
				}
			}
			if ($canonical_lang_id > 0) {
				require_once(ROOT_DIR . 'admin/modules/_i18n.php');
				$entity = admin_i18n_entity_key(isset($get['m']) ? (string)$get['m'] : '', isset($module['table']) ? (string)$module['table'] : '');
				$eid = (int)$get['id'];
				$payload = array('status' => 'published');
				if ($entity === 'authors') {
					if (isset($post['name'])) {
						$payload['name'] = (string)$post['name'];
					}
					if (isset($post['job_title'])) {
						$payload['title'] = (string)$post['job_title'];
					}
					if (isset($post['bio_short'])) {
						$payload['description'] = (string)$post['bio_short'];
					}
					if (isset($post['bio'])) {
						$payload['content'] = (string)$post['bio'];
					}
					if (isset($post['url'])) {
						$payload['url'] = trim((string)$post['url'], '/');
					}
				} else {
					// Common tab fields are named consistently across these modules.
					if (isset($post['url'])) {
						$payload['url'] = trim((string)$post['url'], '/');
					}
					if (isset($post['name'])) {
						$payload['name'] = (string)$post['name'];
					}
					if (isset($post['title'])) {
						$payload['title'] = (string)$post['title'];
					}
					if (isset($post['description'])) {
						$payload['description'] = (string)$post['description'];
					}
					// Main text field in these modules is stored as `text`.
					if (isset($post['text'])) {
						$payload['content'] = (string)$post['text'];
					}
				}

				// Only sync when we actually have main content (or any author field).
				$has_sync_body = !empty($payload['content'])
					|| ($entity === 'authors' && (!empty($payload['name']) || !empty($payload['title'])));
				if ($has_sync_body) {
					if (function_exists('system_log_add')) {
						system_log_add('translations', 'info', 'Common->content_i18n sync', array(
							'entity' => $entity,
							'entity_id' => $eid,
							'canonical_lang_id' => $canonical_lang_id,
							'url' => isset($payload['url']) ? (string)$payload['url'] : '',
							'name' => isset($payload['name']) ? mb_substr((string)$payload['name'], 0, 120) : '',
							'title' => isset($payload['title']) ? mb_substr((string)$payload['title'], 0, 120) : '',
							'content_len' => strlen((string)$payload['content']),
						));
					}
					admin_i18n_save($entity, $eid, $canonical_lang_id, $payload, false);
				}
			}
		}
	$logs['type'] = 2;
}
// Create new record
else {
	if ($table['_edit']===true) {
		$post['id'] = $get['id'] = mysql_fn('insert', $module['table'], $post);
		$logs['type'] = 1;
		$fields = '';
	}
	else die(); // No create permission
}
$err_msg = ($config['mysql_connect'] instanceof mysqli) ? mysqli_error($config['mysql_connect']) : '';
// Treat "0 affected rows" as success (e.g. same values submitted); only a real DB error should block UI success.
$error = $err_msg !== '' ? $err_msg : 0;
// If i18n_save failed inside u=edit handler, propagate it so UI won't treat it as successful save.
if (!empty($i18n_error_msg)) $error = $i18n_error_msg;

/*
//функция после сохранения
//v1.4.14 - event_func
$event_function = 'event_change_'.$module['table'];
if ($error==0 AND function_exists($event_function)) {
	$event_function($post,$old);
}
*/

// Log action
//if ($error===0) {
	mysql_fn('insert','logs',array(
		'user'		=> $user['id'],
		'date'		=> date('Y-m-d H:i:s'),
		'parent'	=> $get['id'],
		'module'	=> $module['table'],
		'type'		=> $logs['type'],
		'ip'        => get_ip(),
		'fields'    => $fields
	));
//}

// Process depends
if (isset($config['depend'][$module['table']])) foreach ($config['depend'][$module['table']] as $key=>$value) {
	$depend = mysql_select("SELECT id,parent name FROM `".$value."` WHERE child = '".intval($get['id'])."'",'array');
	if ($depend==false) $depend = array();
	if ($post[$key]=='' AND count($depend)>0) mysql_fn('delete',$value,array()," AND child = '".intval($get['id'])."'");
	elseif ($post[$key]) {
		$depend2 = explode(',',$post[$key]);
		foreach ($depend2 as $k=>$v) {
			if (!in_array($v,$depend))
				mysql_fn('insert',$value,array('child'=>intval($get['id']),'parent'=>intval($v)));
		}
		foreach ($depend as $k=>$v)
			if (is_array($depend2) AND !in_array($v,$depend2))
				mysql_fn('delete',$value,$k);
	}
}

// Copy files on Save As
if (@$_GET['save_as']>0) {
	rcopy(ROOT_DIR.'files/'.$module['table'].'/'.intval($_GET['save_as']).'/', ROOT_DIR.'files/'.$module['table'].'/'.intval($get['id']).'/');
}

// File uploads
if (!empty($deferred_file_values)) {
	foreach ($deferred_file_values as $dfk => $dfv) {
		$post[$dfk] = $dfv;
	}
}
if (is_array($form)) {
	if (count($tabs) > 0) {
		foreach ($form as $k=>$v) {
			foreach ($v as $k1=>$v1) {
				if (is_array($v1) && preg_match('/mysql|simple|file|file_multi|file_multi_db|gallery|gallery_multi/',$v1[0])) {
					// Copy file_multi_db folders on Save As
					if ($v1[0]=='file_multi_db' AND @$_GET['save_as']>0) {
						$file_multi_db = mysql_select("SELECT * FROM `".$v1[1]."` WHERE parent=".intval($_GET['save_as']),'rows');
						foreach($file_multi_db as $row) {
							$old = ROOT_DIR."files/".$v1[1]."/".$row['id']."/";
							unset($row['id']);
							$row['parent'] = $get['id'];
							$row['id'] = mysql_fn('insert',$v1[1],$row);
							$new = ROOT_DIR."files/".$v1[1]."/".$row['id']."/";
							if(is_dir($new)||mkdir($new,0755,true)) {
								rcopy($old, $new);
							}
						}
					}

					$data['files'][$v1[1]] = call_user_func_array('form_file', $v1);
					// Update file image in row
					if (current(explode(' ',$v1[0]))=='file') $q[$v1[1]] = $post[$v1[1]];
				}
			}
		}
	}
	else {
		foreach ($form as $k=>$v) {
			if (is_array($v) && preg_match('/mysql|simple|file|file_multi|file_multi_db|gallery|gallery_multi/',$v[0])) {
				$data['files'][$v[1]] = call_user_func_array('form_file', $v);
				// Update file image in row
				if (current(explode(' ',$v[0]))=='file') $q[$v[1]] = $post[$v[1]];
			}
		}
	}
}

// Post-save hook (v1.4.14 - event_func)
$event_function = 'event_change_'.$module['table'];
if ($error==0 AND function_exists($event_function)) {
	$event_function($post,$old);
}

// Single-row query for current record
$query_row = $query ? $query." AND ".$module['table'].".id = '".$get['id']."'" : "SELECT * FROM ".$module['table']." WHERE id = '".$get['id']."'";
$q = mysql_select($query_row,'row');
// Nested sets for new record
$data['table'] = '';
if (array_key_exists('level',$q) AND array_key_exists('left_key',$q)) {
	if ($_GET['id']=='new') {
		if ($module['table']!='users') {
			$q['level'] = 1;
			$where = '';
			// if filter is set (e.g. for language)
			if (isset($filter) && is_array($filter)) foreach ($filter as $k => $v) {
				$where .= " AND `" . $v[0] . "` = " . intval($q[$v[0]]);
			}
			$max = mysql_select("SELECT IFNULL(MAX(right_key),0) FROM " . $module['table'] . " WHERE 1 " . $where, 'string');
			mysql_fn('update', $module['table'], array('level' => 1, 'left_key' => ($max + 1), 'right_key' => ($max + 2), 'id' => $get['id']));
			// v1.4.4 required for template admin/templates/includes/table/row.php
			$_GET['id'] = $get['id'];
		}
	}
	// Tree move (nested sets): templates send nested_sets[on]=0 by default; JS sets 1 only after a <select> change.
	// Inline / iframe saves often skip that, so parent never updates in DB — force nested_sets when POST parent differs from row before save.
	if (($module['table'] ?? '') === 'pages' && is_array($old) && isset($old['id']) && (int) $old['id'] > 0
		&& isset($_POST['nested_sets']) && is_array($_POST['nested_sets'])
		&& (!isset($_POST['nested_sets']['on']) || (int) $_POST['nested_sets']['on'] !== 1)
		&& array_key_exists('parent', $_POST['nested_sets'])) {
		$newp = (int) $_POST['nested_sets']['parent'];
		$oldp = (int) (isset($old['parent']) ? $old['parent'] : 0);
		if ($newp !== $oldp) {
			$_POST['nested_sets']['on'] = 1;
		}
	}
	// Tree move (nested sets)
	if (isset($_POST['nested_sets']['on']) AND $_POST['nested_sets']['on']==1) {
		log_add('tree.txt','nested_sets');
		if ($_POST['nested_sets']['previous']) nested_sets($module['table'],$_POST['nested_sets']['previous'],$q['id'],'prev',$filter);
		else nested_sets($module['table'],@$_POST['nested_sets']['parent'],$q['id'],'parent',$filter);
		if (isset($table) AND is_array($table)) {
			$where = '';
			if (isset($filter) && is_array($filter)) foreach ($filter as $k=>$v) {
				$where.= " AND ".$module['table'].".".$v[0]." = '".$q[$v[0]]."'";
			}
			$query = $query ? $query.$where : "SELECT ".$module['table'].".* FROM ".$module['table']." WHERE 1 ".$where;
			$data['table'] = table($table,$query);
		}
	}
}

// Build row HTML
//$data['tr'] = (is_array($table) AND $data['table']=='') ? table_row($table,$q) : '';
//if ($_GET['id']=='new') $data['tr'] = (isset($q['parent']) ? '<tr class="is_open" data-id="'.$q['id'].'" data-parent="'.$q['parent'].'" data-level="'.$q['level'].'" class="a">' : '<tr class="is_open" data-id="'.$q['id'].'" data-parent="0" data-level="1" class="a">').$data['tr'].'</tr>';
// v1.4.4 - return new row only when not returning full table
$data['tr'] = '';
if ($data['table']==false) {
	$array = array(
		'table' => $table,
		'list' => array($q),
		'module'=>$module['table']
	);
	$data['tr'] = html_array('table/row', $array);
}

$data['error']	= $error;
$data['id']		= $get['id'];
if (!empty($redirect_to_i18n)) {
	$data['redirect'] = $redirect_to_i18n;
	$data['i18n'] = true;
}
//
// Always return JSON inside <textarea> so iframe-based form submit can read the response (browsers often do not expose application/json response body in iframe to parent/plugin)
// Drop any stray PHP output (warnings/notices/echo) that would break JSON parsing.
if ($__admin_edit_ob_started) {
	$__garbage = @ob_get_clean();
	if (is_string($__garbage) && trim($__garbage) !== '') {
		// Keep it tiny to avoid blowing up responses; still useful for debugging.
		$data['debug_php_output'] = mb_substr(trim($__garbage), 0, 2000, 'UTF-8');
	}
}
header('Content-Type: text/html; charset=utf-8');
echo '<textarea>'.json_encode($data, JSON_HEX_AMP).'</textarea>';
