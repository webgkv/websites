<?php
/**
 * Languages: system i18n + Full language pack export/import.
 * This module replaces Pages → i18n_sys to avoid duplication.
 *
 * Features:
 * - Per-language menu name/url (stored in content_i18n entity=pages)
 * - System messages (common dictionary file)
 * - Full language pack JSON: create/update language + import/export menu_i18n + common dict
 */

$page_name = 'Languages / i18n';

require_once(ROOT_DIR . 'admin/modules/_i18n.php');

function langpack_find_or_create_language($lng) {
	global $config;
	$name = isset($lng['name']) ? trim((string)$lng['name']) : '';
	$url = isset($lng['url']) ? trim((string)$lng['url']) : '';
	$rank = isset($lng['rank']) ? (int)$lng['rank'] : 0;
	$localization = isset($lng['localization']) ? trim((string)$lng['localization']) : $url;
	$display = !empty($lng['display']) ? 1 : 0;
	if ($name === '' || $url === '') return array('ok' => false, 'message' => 'Missing language.name or language.url');

	$existing = mysql_select("SELECT * FROM languages WHERE url='" . mysql_res($url) . "' LIMIT 1", 'row');
	$is_new = false;
	if ($existing && !empty($existing['id'])) {
		$id = (int)$existing['id'];
		mysql_fn('update', 'languages', array(
			'id' => $id,
			'name' => $name,
			'rank' => $rank,
			'url' => $url,
			'localization' => $localization,
			'display' => $display,
		));
	} else {
		$id = (int)mysql_fn('insert', 'languages', array(
			'name' => $name,
			'rank' => $rank,
			'url' => $url,
			'localization' => $localization,
			'display' => $display,
		));
		$is_new = true;
	}

	// When multilingual, add columns to lang tables for new language
	if ($is_new && !empty($config['multilingual']) && !empty($config['lang_tables']) && is_array($config['lang_tables'])) {
		foreach ($config['lang_tables'] as $key => $val) {
			foreach ($val as $k => $v) {
				mysql_fn('query', "ALTER TABLE `" . $key . "` ADD `" . $k . intval($id) . "` " . $v . " AFTER `" . $k . "`");
			}
		}
	}

	return array('ok' => true, 'id' => $id, 'is_new' => $is_new);
}

function langpack_enable_lang_id($lang_id) {
	$lang_id = (int)$lang_id;
	if ($lang_id <= 0) return;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) return;
	$row = mysql_select("SELECT id, value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') return;
	$dec = json_decode($row['value'], true);
	if (!is_array($dec)) return;
	if (empty($dec['enabled_lang_ids']) || !is_array($dec['enabled_lang_ids'])) $dec['enabled_lang_ids'] = array();
	$ids = array_values(array_filter(array_map('intval', $dec['enabled_lang_ids'])));
	if (!in_array($lang_id, $ids, true)) {
		$ids[] = $lang_id;
		$dec['enabled_lang_ids'] = $ids;
		mysql_fn('update', 'variables', array('id' => (int)$row['id'], 'value' => json_encode($dec, JSON_UNESCAPED_UNICODE)));
	}
}

function langpack_export_common_multi_payload($langs) {
	$payload = array(
		'schema' => 'common_dictionary_multi_v1',
		'exported_at' => date('c'),
		'languages' => array(),
		'dictionaries' => array(),
	);
	foreach ((array)$langs as $lang_row) {
		$lang_id = isset($lang_row['id']) ? (int)$lang_row['id'] : 0;
		$lang_url = isset($lang_row['url']) ? trim((string)$lang_row['url']) : '';
		if ($lang_id <= 0 || $lang_url === '') continue;
		$payload['languages'][] = array(
			'id' => $lang_id,
			'name' => isset($lang_row['name']) ? (string)$lang_row['name'] : $lang_url,
			'rank' => isset($lang_row['rank']) ? (int)$lang_row['rank'] : 0,
			'url' => $lang_url,
			'localization' => isset($lang_row['localization']) ? (string)$lang_row['localization'] : $lang_url,
			'display' => !empty($lang_row['display']) ? 1 : 0,
		);
		$payload['dictionaries'][$lang_url] = admin_load_common_dict($lang_id);
	}
	return $payload;
}

function langpack_import_common_multi_payload($data) {
	if (empty($data) || !is_array($data) || empty($data['dictionaries']) || !is_array($data['dictionaries'])) {
		return array('ok' => false, 'message' => 'Invalid JSON or missing "dictionaries" object.');
	}
	$languages_meta = array();
	if (!empty($data['languages']) && is_array($data['languages'])) {
		foreach ($data['languages'] as $row) {
			if (!is_array($row)) continue;
			$url = isset($row['url']) ? trim((string)$row['url']) : '';
			if ($url === '') continue;
			$languages_meta[$url] = $row;
		}
	}
	$updated = 0;
	$created = 0;
	foreach ($data['dictionaries'] as $lang_url => $dict) {
		$lang_url = trim((string)$lang_url);
		if ($lang_url === '') continue;
		if (!is_array($dict)) {
			return array('ok' => false, 'message' => 'Dictionary for "' . $lang_url . '" must be an object of key/value pairs.');
		}
		$existing = mysql_select("SELECT * FROM languages WHERE url='" . mysql_res($lang_url) . "' LIMIT 1", 'row');
		$lang_row = $existing && is_array($existing) ? $existing : null;
		if (!$lang_row) {
			$meta = isset($languages_meta[$lang_url]) && is_array($languages_meta[$lang_url]) ? $languages_meta[$lang_url] : null;
			if (!$meta) {
				return array('ok' => false, 'message' => 'Language "' . $lang_url . '" does not exist and is missing from the "languages" array.');
			}
			if (empty($meta['name'])) $meta['name'] = strtoupper($lang_url);
			if (!isset($meta['localization']) || $meta['localization'] === '') $meta['localization'] = $lang_url;
			$create = langpack_find_or_create_language($meta);
			if (empty($create['ok'])) {
				return array('ok' => false, 'message' => 'Language "' . $lang_url . '": ' . (isset($create['message']) ? $create['message'] : 'create failed'));
			}
			$lang_row = mysql_select("SELECT * FROM languages WHERE id=" . (int)$create['id'] . " LIMIT 1", 'row');
			$created++;
		}
		$lang_id = isset($lang_row['id']) ? (int)$lang_row['id'] : 0;
		if ($lang_id <= 0) {
			return array('ok' => false, 'message' => 'Failed to resolve language "' . $lang_url . '".');
		}
		$res = admin_save_common_dict($lang_id, $dict);
		if (empty($res['ok'])) {
			return array('ok' => false, 'message' => 'Language "' . $lang_url . '": ' . $res['message']);
		}
		langpack_enable_lang_id($lang_id);
		$updated++;
	}
	return array('ok' => true, 'updated' => $updated, 'created' => $created);
}

// Canonical menu
$canonical = mysql_select("SELECT id, module, level, name, url FROM pages WHERE display=1 AND menu=1 AND level<3 ORDER BY left_key", 'rows') ?: array();

// Enabled languages (fallback to display=1)
$i18n_langs = admin_i18n_enabled_languages();
if (empty($i18n_langs)) {
	$i18n_langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
}
$i18n_lang_id = isset($get['lang_id']) ? (int)$get['lang_id'] : 0;
if ($i18n_lang_id <= 0 && !empty($i18n_langs)) $i18n_lang_id = (int)$i18n_langs[0]['id'];
$i18n_source_lang_id = admin_i18n_source_lang_id();

// --- Full language pack export ---
if (isset($get['u']) && $get['u'] === 'export_full' && $i18n_lang_id > 0) {
	$lang_row = mysql_select("SELECT * FROM languages WHERE id=" . (int)$i18n_lang_id . " LIMIT 1", 'row');
	if (!$lang_row) {
		header('HTTP/1.1 404 Not Found');
		echo 'Language not found';
		exit;
	}
	$payload = array(
		'schema' => 'full_language_pack_v1',
		'exported_at' => date('c'),
		'language' => array(
			'id' => (int)$lang_row['id'],
			'name' => (string)$lang_row['name'],
			'rank' => (int)$lang_row['rank'],
			'url' => (string)$lang_row['url'],
			'localization' => isset($lang_row['localization']) ? (string)$lang_row['localization'] : '',
			'display' => !empty($lang_row['display']) ? 1 : 0,
		),
		'common' => admin_load_common_dict($i18n_lang_id),
		'menu_items' => array(),
	);
	foreach ($canonical as $p) {
		$pid = (int)$p['id'];
		$name = (string)$p['name'];
		$url = isset($p['url']) ? trim((string)$p['url'], '/') : '';
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$row = mysql_select("SELECT name, url FROM content_i18n WHERE entity='pages' AND entity_id=" . $pid . " AND lang_id=" . (int)$i18n_lang_id . " LIMIT 1", 'row');
			if ($row) {
				if (isset($row['name']) && $row['name'] !== '') $name = (string)$row['name'];
				if (isset($row['url']) && $row['url'] !== '') $url = trim((string)$row['url'], '/');
			}
		}
		$payload['menu_items'][] = array('page_id' => $pid, 'name' => $name, 'url' => $url);
	}
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="full-language-pack-' . preg_replace('/[^a-z0-9_-]+/i', '_', (string)$lang_row['url']) . '-' . date('Y-m-d-His') . '.json"');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	exit;
}

// --- Full language pack import ---
if (isset($get['u']) && $get['u'] === 'import_full' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$err = '';
	$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	if (!$file) {
		$err = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		$data = $raw !== false ? @json_decode($raw, true) : null;
		if (!$data || empty($data['language']) || !is_array($data['language'])) {
			$err = 'Invalid JSON or missing "language" object.';
		} else {
			$created = langpack_find_or_create_language($data['language']);
			if (empty($created['ok'])) {
				$err = isset($created['message']) ? (string)$created['message'] : 'Failed to create/update language.';
			} else {
				$lang_id = (int)$created['id'];
				// Common dictionary
				if (isset($data['common']) && is_array($data['common'])) {
					$res = admin_save_common_dict($lang_id, $data['common']);
					if (empty($res['ok'])) $err = 'Common dictionary: ' . $res['message'];
				}
				// Menu items
				if ($err === '' && isset($data['menu_items']) && is_array($data['menu_items'])) {
					$updated = 0;
					foreach ($data['menu_items'] as $item) {
						$pid = isset($item['page_id']) ? (int)$item['page_id'] : 0;
						if ($pid <= 0) continue;
						$name = isset($item['name']) ? (string)$item['name'] : '';
						$url = isset($item['url']) ? trim((string)$item['url'], '/') : '';
						$r = admin_i18n_save('pages', $pid, $lang_id, array('name' => $name, 'url' => $url, 'status' => 'published'));
						if (!empty($r['ok'])) $updated++;
					}
				}
				// Enable in i18n_sys list by default
				langpack_enable_lang_id($lang_id);
				$redirect = '/admin.php?m=languages&import_lang_id=' . $lang_id . '&import_lang_ok=1';
				if ($is_ajax) {
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode(array('error' => 0, 'redirect' => $redirect));
					exit;
				}
				$_SESSION['admin_flash_success'] = 'Full pack imported for language ID ' . $lang_id . '.';
				header('Location: ' . $redirect);
				exit;
			}
		}
	}
	if ($is_ajax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 1, 'message' => $err));
		exit;
	}
	$_SESSION['admin_flash_error'] = $err;
	header('Location: /admin.php?m=languages&import_lang_error=' . urlencode($err) . ($i18n_lang_id > 0 ? '&import_lang_id=' . $i18n_lang_id : ''));
	exit;
}

// --- Common dictionary export/import for all languages ---
if (isset($get['u']) && $get['u'] === 'export_dictionary_multi') {
	$payload = langpack_export_common_multi_payload($i18n_langs);
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="common-dictionary-multi-' . date('Y-m-d-His') . '.json"');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	exit;
}
if (isset($get['u']) && $get['u'] === 'import_dictionary_multi' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$import_err = '';
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	if (!$file) {
		$import_err = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		$data = $raw !== false ? @json_decode($raw, true) : null;
		if (!$data || !is_array($data)) {
			$import_err = 'Invalid JSON.';
		} else {
			$res = langpack_import_common_multi_payload($data);
			if (!empty($res['ok'])) {
				$_SESSION['admin_flash_success'] = 'Multi dictionary import: ' . (int)$res['updated'] . ' language file(s) updated' . (!empty($res['created']) ? ', ' . (int)$res['created'] . ' created' : '') . '.';
				header('Location: /admin.php?m=languages_json' . ($i18n_lang_id > 0 ? '&lang_id=' . $i18n_lang_id : ''));
				exit;
			}
			$import_err = isset($res['message']) ? (string)$res['message'] : 'Import failed.';
		}
	}
	$_SESSION['admin_flash_error'] = $import_err;
	header('Location: /admin.php?m=languages_json' . ($i18n_lang_id > 0 ? '&lang_id=' . $i18n_lang_id : ''));
	exit;
}

// --- Save menu i18n from form ---
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
		if (!empty($res['ok'])) $saved++;
	}
	$_SESSION['admin_flash_success'] = $saved ? 'Saved ' . $saved . ' menu item(s).' : 'No changes.';
	header('Location: /admin.php?m=languages_json&lang_id=' . $i18n_lang_id);
	exit;
}

// --- Save system messages (common) ---
$system_message_keys = array(
	'txt_no_page_text' => '404 / Page not found text',
	'no_content' => 'No content',
	'index_page' => 'Breadcrumb: Home label',
	'back_to_home' => 'Back to Home (button on 404)',
	'msg_no_results' => 'No results message',
	'make_selection' => 'Forms: make selection placeholder',
	'footer_about_us' => 'Footer: About Us',
	'footer_terms' => 'Footer: Terms',
	'footer_privacy' => 'Footer: Privacy',
	'footer_responsible' => 'Footer: Responsible Gambling',
	'footer_responsible_text' => 'Footer: Responsible text',
	'footer_play_label' => 'Footer: Play responsibly label',
	'footer_play_responsibly' => 'Footer: Play responsibly text',
	'footer_copyright' => 'Footer: Copyright ({year})',
	'hero_subtitle' => 'Hero: Subtitle',
	'hero_cta' => 'Hero: CTA button',
	'cta_play_now' => 'CTA: Play Now (promo/body)',
	'cta_try_bonus' => 'CTA: Try Bonus',
	'popup_join' => 'Popup: Join our',
	'popup_partner' => 'Popup: Partner',
	'popup_special_offer' => 'Popup: Special offer title',
	'aria_close' => 'Aria: Close',
	'demo_app_fullscreen' => 'Demo app bar: Fullscreen',
	'demo_app_open_in_safari_title' => 'Demo app: Open in Safari (modal title)',
	'demo_app_open_in_safari_body' => 'Demo app: Open in Safari (modal body)',
	'demo_app_modal_got_it' => 'Demo app: modal dismiss button',
	'go_to_top' => 'Go to top',
	'quick_access_eyebrow' => 'Quick Access: eyebrow',
	'quick_access_title' => 'Quick Access: title',
	'quick_access_open_demo' => 'Quick Access: Open demo button',
	'quick_access_google_play' => 'Quick Access: Google Play button',
	'quick_access_app_store' => 'Quick Access: App Store button',
	'quick_access_demo_note' => 'Quick Access: demo page note',
	'quick_access_download_lead' => 'Quick Access: download page lead',
	'quick_access_download_note' => 'Quick Access: download page note',
	'quick_access_demo_step1' => 'Quick Access preview: step 1 label',
	'quick_access_demo_step2' => 'Quick Access preview: step 2 label',
	'quick_access_demo_step3' => 'Quick Access preview: step 3 label',
	'quick_access_demo_alt1' => 'Quick Access preview: image 1 alt',
	'quick_access_demo_alt2' => 'Quick Access preview: image 2 alt',
	'quick_access_demo_alt3' => 'Quick Access preview: image 3 alt',
	'strategies_menu' => 'Menu fallback: Strategies (pages#6 /strategies/)',
	'predictor_menu' => 'Legacy alias for strategies_menu',
	'games_title' => 'Games: page title',
	'games_cat_all' => 'Games: category All',
	'games_cat_crash' => 'Games: category Crash',
	'games_cat_crash-p2e' => 'Games: category Crash P2E',
	'games_cat_other' => 'Games: category Other',
	'guides_title' => 'Guides: page title',
	'guides_cat_all' => 'Guides: category All',
	'guides_cat_analysis' => 'Guides: category Analysis',
	'guides_cat_bonus' => 'Guides: category Bonus',
	'guides_cat_how-to-win' => 'Guides: category How to Win',
	'guides_cat_signals' => 'Guides: category Signals',
	'guides_cat_crash-gambling' => 'Guides: category Crash Gambling',
	'read_guide' => 'Guides: card link (e.g. → Read guide)',
	'read_more' => 'Casinos/Games: Read more',
	'authors_title' => 'Authors: page title',
	'author_byline_prefix' => 'Author byline prefix (e.g. By, Par, Von)',
	'author_references_title' => 'Author profile: references section heading',
	'author_about_link' => 'Author block: About the author link (legacy, optional footer bio)',
	'breadcrumb_index' => 'Breadcrumbs: home segment',
	'breadcrumb_separator' => 'Breadcrumbs: separator',
);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['save_system_messages']) && $i18n_lang_id > 0) {
	$dict = admin_load_common_dict($i18n_lang_id);
	if (empty($dict) && $i18n_lang_id !== 1) $dict = admin_load_common_dict(1);
	foreach (array_keys($system_message_keys) as $key) {
		$post_key = 'sys_' . $key;
		if (array_key_exists($post_key, $_POST)) $dict[$key] = (string)$_POST[$post_key];
	}
	$res = admin_save_common_dict($i18n_lang_id, $dict);
	$_SESSION['admin_flash_success'] = !empty($res['ok']) ? 'System messages saved.' : '';
	if (empty($res['ok'])) $_SESSION['admin_flash_error'] = $res['message'];
	header('Location: /admin.php?m=languages_json&lang_id=' . $i18n_lang_id);
	exit;
}

// --- Prune target common.php to match source (canonical) key set only ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prune_common_to_canonical']) && $i18n_lang_id > 0) {
	$res = admin_prune_common_dict_to_canonical($i18n_lang_id);
	if (!empty($res['ok'])) {
		$_SESSION['admin_flash_success'] = $res['message'];
	} else {
		$_SESSION['admin_flash_error'] = $res['message'];
	}
	header('Location: /admin.php?m=languages_json&lang_id=' . $i18n_lang_id);
	exit;
}

// Render page
$content = '<div class="card"><div class="card-body">';
$content .= '<h5 class="mb-3">Menu &amp; system i18n</h5>';
$content .= '<form method="get" class="mb-3"><input type="hidden" name="m" value="languages_json"/>';
$content .= '<label class="me-2">Language:</label><select name="lang_id" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">';
foreach ($i18n_langs as $l) {
	$sel = ((int)$l['id'] === $i18n_lang_id) ? ' selected' : '';
	$content .= '<option value="' . (int)$l['id'] . '"' . $sel . '>' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</option>';
}
$content .= '</select></form>';

// Full pack
$content .= '<div class="mb-4">';
$content .= '<h6 class="mb-2">Full language pack</h6>';
$content .= '<div class="mb-2"><a href="/admin.php?m=languages_json&u=export_full&lang_id=' . (int)$i18n_lang_id . '" class="btn btn-sm btn-primary">Export full pack JSON</a></div>';
$content .= '<form method="post" action="/admin.php?m=languages_json&u=import_full&lang_id=' . (int)$i18n_lang_id . '" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end">';
$content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required/></div>';
$content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-sm btn-secondary">Import full pack JSON</button></div>';
$content .= '</form>';
$content .= '</div>';

$content .= '<div class="mb-4">';
$content .= '<h6 class="mb-2">Common dictionary: all languages</h6>';
$content .= '<p class="small text-muted mb-2">Export or import one JSON file that contains <code>common.php</code> values for every enabled language at once. This is useful when you add new dictionary keys and want to fill all locales in one pass.</p>';
$content .= '<div class="mb-2"><a href="/admin.php?m=languages_json&u=export_dictionary_multi" class="btn btn-sm btn-outline-primary">Export multi JSON (all dictionaries)</a></div>';
$content .= '<form method="post" action="/admin.php?m=languages_json&u=import_dictionary_multi' . ($i18n_lang_id > 0 ? '&lang_id=' . (int)$i18n_lang_id : '') . '" enctype="multipart/form-data" class="form-inline flex-wrap align-items-end">';
$content .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file" required/></div>';
$content .= '<div class="form-group mb-2"><button type="submit" class="btn btn-sm btn-outline-secondary">Import multi JSON (all dictionaries)</button></div>';
$content .= '</form>';
$content .= '</div>';

// Canonical
$content .= '<h6 class="mt-3 mb-2">Canonical menu</h6>';
$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Module</th><th>Level</th><th>Name (default)</th><th>URL (default)</th></tr></thead><tbody>';
foreach ($canonical as $p) {
	$content .= '<tr><td>' . (int)$p['id'] . '</td><td>' . htmlspecialchars((string)$p['module']) . '</td><td>' . (int)$p['level'] . '</td>';
	$content .= '<td>' . htmlspecialchars((string)$p['name']) . '</td><td>' . htmlspecialchars(isset($p['url']) ? $p['url'] : '') . '</td></tr>';
}
$content .= '</tbody></table></div>';

// Current language menu values
$i18n_rows = array();
if ($i18n_lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$ids = array_map(function($p) { return (int)$p['id']; }, $canonical);
	if (!empty($ids)) {
		$rows = mysql_select("SELECT entity_id, name, url FROM content_i18n WHERE entity='pages' AND lang_id=" . $i18n_lang_id . " AND entity_id IN (" . implode(',', $ids) . ")", 'rows') ?: array();
		foreach ($rows as $r) $i18n_rows[(int)$r['entity_id']] = $r;
	}
}
$content .= '<h6 class="mt-4 mb-2">Menu translation (this language)</h6>';
$content .= '<form method="post" class="mb-4">';
$content .= '<input type="hidden" name="save_menu_i18n" value="1"/>';
$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Canonical name</th><th>Name</th><th>URL</th></tr></thead><tbody>';
foreach ($canonical as $p) {
	$pid = (int)$p['id'];
	$cur = isset($i18n_rows[$pid]) ? $i18n_rows[$pid] : array();
	$cur_name = isset($cur['name']) && $cur['name'] !== '' ? (string)$cur['name'] : (string)$p['name'];
	$cur_url = isset($cur['url']) && $cur['url'] !== '' ? trim((string)$cur['url'], '/') : (isset($p['url']) ? trim((string)$p['url'], '/') : '');
	$content .= '<tr><td>' . $pid . '</td><td class="text-muted">' . htmlspecialchars((string)$p['name']) . '</td>';
	$content .= '<td><input type="text" class="form-control form-control-sm" name="menu_name_' . $pid . '" value="' . htmlspecialchars($cur_name) . '" placeholder="' . htmlspecialchars((string)$p['name']) . '"/></td>';
	$content .= '<td><input type="text" class="form-control form-control-sm" name="menu_url_' . $pid . '" value="' . htmlspecialchars($cur_url) . '" placeholder="' . htmlspecialchars(isset($p['url']) ? $p['url'] : '') . '"/></td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '<button type="submit" class="btn btn-primary">Save menu</button>';
$content .= '</form>';

// System messages
$content .= '<hr class="my-4"><h6 class="mt-4 mb-2">System messages (common dictionary)</h6>';
$content .= '<p class="small text-muted mb-2">The public site footer and system strings use <code>i18n(\'common|…\')</code> in <code>templates/includes/layouts/_template.php</code>.'
	. ' That loads <strong>only</strong> the on-disk file <code>files/languages/{language_id}/dictionary/common.php</code> for the <strong>current</strong> URL language (<code>languages.id</code> from the first path segment).'
	. ' The serialized <code>languages.dictionary</code> column in MySQL is <strong>not</strong> merged on the frontend (see <code>lang()</code> in <code>functions/lang_func.php</code>).</p>';
$common_php_relpath = 'files/languages/' . (int)$i18n_lang_id . '/dictionary/common.php';
$common_php_abspath = ROOT_DIR . $common_php_relpath;
$common_exists = is_file($common_php_abspath);
$common_mtime = $common_exists ? (int)@filemtime($common_php_abspath) : 0;
$content .= '<p class="small mb-2"><strong>File for this language (id ' . (int)$i18n_lang_id . '):</strong> <code>' . htmlspecialchars($common_php_relpath) . '</code>'
	. ($common_exists
		? ' — <span class="text-success">exists</span> (' . (int)@filesize($common_php_abspath) . ' bytes' . ($common_mtime > 0 ? ', ' . date('Y-m-d H:i', $common_mtime) : '') . ')'
		: ' — <span class="text-danger">missing on this server</span> (public site will show empty strings until you Save here or upload the file).'
	) . '</p>';
$sys_dict_raw = $i18n_lang_id > 0 ? admin_load_common_dict($i18n_lang_id) : array();
$used_en_placeholder = (empty($sys_dict_raw) && $i18n_lang_id > 0 && (int)$i18n_lang_id !== 1);
if ($used_en_placeholder) {
	$content .= '<div class="alert alert-warning py-2 small mb-2">The dictionary file for this language is empty or missing, so the form below is pre-filled from the <strong>source language (id '
		. (int)$i18n_source_lang_id . ')</strong> for convenience. It is <strong>not</strong> saved to this language until you click &quot;Save system messages&quot;.'
		. ' Other locales (other language IDs) each need their own <code>common.php</code> — use the language dropdown, fill footer keys, and save per language.</div>';
}
if ($i18n_lang_id > 0 && $i18n_lang_id !== $i18n_source_lang_id) {
	$content .= '<form method="post" class="mb-3" onsubmit="return confirm(\'Remove all keys in this language common.php that are not in the canonical source file? This deletes legacy entries.\');">';
	$content .= '<input type="hidden" name="prune_common_to_canonical" value="1"/>';
	$content .= '<button type="submit" class="btn btn-sm btn-outline-warning">Prune to canonical keys only</button>';
	$content .= '</form>';
}
$content .= '<form method="post" class="mb-4">';
$content .= '<input type="hidden" name="save_system_messages" value="1"/>';
$sys_dict = $sys_dict_raw;
if (empty($sys_dict) && $i18n_lang_id !== 1) {
	$sys_dict = admin_load_common_dict(1);
}
$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>';
foreach ($system_message_keys as $key => $label) {
	$val = isset($sys_dict[$key]) ? (string)$sys_dict[$key] : '';
	$content .= '<tr><td class="text-nowrap"><label class="mb-0 small text-muted">' . htmlspecialchars($label) . '</label><br><code>' . htmlspecialchars($key) . '</code></td>';
	$content .= '<td><input type="text" class="form-control form-control-sm" name="sys_' . htmlspecialchars($key) . '" value="' . htmlspecialchars($val) . '" placeholder="' . htmlspecialchars($label) . '"/></td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '<button type="submit" class="btn btn-primary">Save system messages</button>';
$content .= '</form>';

$content .= '</div></div>';

