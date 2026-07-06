<?php
/**
 * Admin module: Authors management (E-E-A-T)
 */

$authors_table = 'site_authors';

// Check if table exists (for initial install phase)
$has_table = @mysql_select("SHOW TABLES LIKE '$authors_table'", 'num_rows') > 0;

if (!$has_table) {
	echo "<div class='alert alert-warning'>Table `$authors_table` not found. Please run migrations first.</div>";
	return;
}

// Fetch all authors for list and selectors
$authors_list = mysql_select("SELECT id, name FROM `$authors_table` ORDER BY name ASC", 'array');

$query = "SELECT * FROM `$authors_table` WHERE 1";

$table = array(
	'id'        => 'id:desc',
	'photo'     => 'img',
	'name'      => '',
	'job_title' => '',
	'display'   => 'boolean'
);

$tabs = array(
	1 => 'Profile',
	2 => 'Photo',
	3 => 'Social & links',
);

$is_new = (!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new');

$form[1][] = array('checkbox td2', 'display');

// New author: create base row first (translations need entity_id).
if ($is_new) {
	$form[1][] = array('input td6', 'name');
	$form[1][] = array('input td4', 'job_title');
	$form[1][] = array('input td4', 'url', array('name' => 'URL slug', 'help' => 'e.g. james-mitchell — public /authors/{slug}/'));
	$form[1][] = array('textarea td12', 'bio_short', array('attr' => 'style="height:80px"', 'name' => 'Short bio (card excerpt)'));
	$form[1][] = array('textarea td12', 'bio', array('attr' => 'style="height:150px"', 'name' => 'Full biography (profile page)'));
	$form[1][] = array('input td6', 'meta_title', array('name' => 'SEO title (optional)'));
	$form[1][] = array('input td6', 'meta_description', array('name' => 'SEO description (optional)'));
	$form[1][] = array('input td6', 'photo_alt', array('name' => 'Photo alt text'));
}

$form[2][] = array('file td6', 'photo', array('sizes' => array('' => '')));

// --- Randomization Settings ---
// We store this in the `variables` table under `authors_settings` key
$authors_settings = array('randomize_missing' => 0);
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$vr = mysql_select("SELECT value FROM variables WHERE `key`='authors_settings' LIMIT 1", 'row');
	if ($vr && $vr['value'] !== '') {
		$dec = json_decode($vr['value'], true);
		if (is_array($dec)) $authors_settings = array_merge($authors_settings, $dec);
	}
}

// Handle settings update
if (!empty($_POST['save_authors_settings'])) {
	$authors_settings['randomize_missing'] = !empty($_POST['randomize_missing']) ? 1 : 0;
	mysql_fn('insert update', 'variables', array(
		'key' => 'authors_settings',
		'value' => json_encode($authors_settings)
	));
	$_SESSION['admin_flash_success'] = 'Settings saved.';
	header('Location: ' . $_SERVER['REQUEST_URI']);
	exit;
}

// Add settings to the filter area or as a separate block
$filter[] = '<form method="post" class="d-inline-block ml-3 border-left pl-3">';
$filter[] = '<label class="mb-0 mr-2"><input type="checkbox" name="randomize_missing" value="1"' . ($authors_settings['randomize_missing'] ? ' checked' : '') . '> Randomize missing</label>';
$filter[] = '<button type="submit" name="save_authors_settings" value="1" class="btn btn-xs btn-outline-info">Save Settings</button>';
$filter[] = '</form>';

// Optional: Bulk randomize action
if (isset($get['u']) && $get['u'] === 'bulk_randomize') {
	$content_tables = array('blog', 'guides', 'games', 'casino_articles', 'pages');
	$all_author_ids = array_keys($authors_list);
	if (empty($all_author_ids)) {
		$_SESSION['admin_flash_error'] = 'No active authors to assign.';
	} else {
		$updated_total = 0;
		foreach ($content_tables as $tbl) {
			if (@mysql_select("SHOW TABLES LIKE '$tbl'", 'num_rows') > 0) {
				$rows = mysql_select("SELECT id FROM `$tbl` WHERE author_id = 0", 'rows');
				if ($rows) {
					foreach ($rows as $r) {
						$rand_id = $all_author_ids[array_rand($all_author_ids)];
						mysql_fn('query', "UPDATE `$tbl` SET author_id = " . (int)$rand_id . " WHERE id = " . (int)$r['id']);
						$updated_total++;
					}
				}
			}
		}
		$_SESSION['admin_flash_success'] = "Successfully assigned random authors to $updated_total items.";
	}
	header('Location: /admin.php?m=authors');
	exit;
}

$filter[] = '<a href="/admin.php?m=authors&u=bulk_randomize" class="btn btn-sm btn-outline-warning ml-2" onclick="return confirm(\'Assign random authors to all content where it is currently missing?\')">Bulk Randomize Missing</a>';

// Profile tab: per-locale name / job title / bio (content_i18n), same pattern as Content → Guides.
if (isset($get['u']) && $get['u'] === 'form' && isset($get['id']) && $get['id'] !== 'new' && ($aid = (int)$get['id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	$authors_i18n_ftab = 1;
	if (!empty($get['i18n_clear'])) {
		$clear_lang_id = $i18n_lang_id > 0 ? $i18n_lang_id : 0;
		$res = admin_i18n_clear('authors', $aid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=authors&u=form&id=' . (int)$aid . '&ftab=' . $authors_i18n_ftab . '&i18n_lang_id=' . (int)$clear_lang_id);
		exit;
	}
	if (!empty($_POST['i18n_clear'])) {
		$clear_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$res = admin_i18n_clear('authors', $aid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=authors&u=form&id=' . (int)$aid . '&ftab=' . $authors_i18n_ftab . '&i18n_lang_id=' . (int)$clear_lang_id);
		exit;
	}
	if (!empty($_POST['i18n_save'])) {
		$save_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$payload = array(
			'url' => isset($_POST['i18n_url']) ? (string)$_POST['i18n_url'] : '',
			'name' => isset($_POST['i18n_name']) ? (string)$_POST['i18n_name'] : '',
			'title' => isset($_POST['i18n_title']) ? (string)$_POST['i18n_title'] : '',
			'description' => isset($_POST['i18n_description']) ? (string)$_POST['i18n_description'] : '',
			'content' => isset($_POST['i18n_content']) ? (string)$_POST['i18n_content'] : '',
			'status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : 'draft',
		);
		$res = admin_i18n_save('authors', $aid, $save_lang_id, $payload);
		if (!empty($res['ok'])) {
			admin_i18n_sync_canonical_row_to_base_table('authors', $aid, $save_lang_id);
		}
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
		header('Location: /admin.php?m=authors&u=form&id=' . (int)$aid . '&ftab=' . $authors_i18n_ftab . '&i18n_lang_id=' . (int)$save_lang_id);
		exit;
	}
	$base_url = '/admin.php?m=authors&u=form&id=' . (int)$aid . '&ftab=' . $authors_i18n_ftab;
	admin_i18n_repair_author_canonical_from_base($aid);
	$defaults = admin_i18n_fetch_base_translation('authors', $aid);
	if (!is_array($defaults)) {
		$defaults = array(
			'url' => '',
			'name' => isset($post['name']) ? (string)$post['name'] : '',
			'title' => isset($post['job_title']) ? (string)$post['job_title'] : '',
			'description' => '',
			'content' => isset($post['bio']) ? (string)$post['bio'] : '',
			'status' => 'published',
		);
	}
	$_canonical_lang_id = admin_i18n_source_lang_id();
	$form[1][] = array('input td4', 'url', array('name' => 'URL slug (canonical)', 'help' => 'Synced from source-language translation when saved'));
	$form[1][] = array('input td6', 'meta_title', array('name' => 'SEO title (not translated)'));
	$form[1][] = array('input td6', 'meta_description', array('name' => 'SEO description (not translated)'));
	$form[1][] = array('input td6', 'photo_alt', array('name' => 'Photo alt text (not translated)'));
	$form[1][] = admin_i18n_render_form('authors', $aid, $i18n_lang_id, $base_url, $defaults, array(
		'canonical_lang_id' => $_canonical_lang_id,
		'profile_only' => true,
	));
}

require_once ROOT_DIR . 'functions/author_social.php';

if (isset($get['u']) && $get['u'] === 'form') {
	$_author_form_row = array();
	if (!$is_new && ($aid = (int)$get['id']) > 0) {
		$_author_form_row = mysql_select("SELECT * FROM `$authors_table` WHERE id=" . $aid . " LIMIT 1", 'row') ?: array();
	}
	$form[3][] = admin_author_links_form_block($_author_form_row);
}

/**
 * Persist structured social profiles + reference links after main row save.
 *
 * @param array<string,mixed> $q
 * @param array<string,mixed>|false $old
 */
function event_change_site_authors($q, $old = false) {
	if (!is_array($q) || empty($q['id'])) {
		return;
	}
	if (!isset($_POST['author_social']) && !isset($_POST['author_ref_url']) && !isset($_POST['author_ref_label'])) {
		return;
	}
	if (!function_exists('author_social_pack_from_post')) {
		require_once ROOT_DIR . 'functions/author_social.php';
	}
	$packed = author_social_pack_from_post($_POST);
	mysql_fn('update', 'site_authors', array(
		'social_profiles' => $packed['social_profiles'],
		'reference_links' => $packed['reference_links'],
		'social_links' => $packed['social_links'],
	), ' AND id=' . (int)$q['id'] . ' ');
}
