<?php
/**
 * Translations: batch job creator (enqueue translations_translate).
 */
$page_name = 'Translations: batch';

require_once ROOT_DIR . 'functions/translation_hub.php';
if (!defined('TRANSLATIONS_HUB')) {
	if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
		header('Location: /admin.php?m=translations&tab=batch');
		exit;
	}
}

$has_jobs = @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0;
$has_vars = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
if (!$has_jobs || !$has_vars) {
	$content = '<div class="alert alert-warning">Missing DB tables. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	if (!defined('TRANSLATIONS_HUB')) {
		require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
		exit;
	}
	return;
}

require_once(ROOT_DIR . 'functions/admin_jobs.php');

// Load settings
$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
$cfg = array('source_lang_id' => 1, 'chunk_max_len' => 2500);
if ($row && $row['value'] !== '') {
	$dec = json_decode($row['value'], true);
	if (is_array($dec)) $cfg = array_merge($cfg, $dec);
}

$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
$pages = mysql_select("SELECT id, name, url FROM pages WHERE display=1 ORDER BY left_key ASC LIMIT 2000", 'rows') ?: array();

$created = 0;
if (!empty($_POST['create_batch'])) {
	$dst_langs = isset($_POST['dst_lang_ids']) && is_array($_POST['dst_lang_ids']) ? array_values(array_filter(array_map('intval', $_POST['dst_lang_ids']))) : array();
	$page_ids = isset($_POST['page_ids']) && is_array($_POST['page_ids']) ? array_values(array_filter(array_map('intval', $_POST['page_ids']))) : array();
	$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
	$only_missing = !empty($_POST['only_missing']);
	$src_lang = (int)$cfg['source_lang_id'];
	foreach ($dst_langs as $dst) {
		foreach ($page_ids as $pid) {
			if ($dst === $src_lang) continue;
			if ($only_missing && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
				$exists = mysql_select("SELECT id FROM content_i18n WHERE entity='pages' AND entity_id=" . (int)$pid . " AND lang_id=" . (int)$dst . " LIMIT 1", 'row');
				if ($exists) continue;
			}
			$jid = admin_jobs_enqueue('translations', 'translate', array(
				'entity' => 'pages',
				'entity_id' => (int)$pid,
				'src_lang' => $src_lang,
				'dst_lang' => (int)$dst,
				'fields' => array('title','description','content'),
				'chunk_max_len' => isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500,
			), array('priority' => $priority));
			if ($jid) $created++;
		}
	}
}

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">Batch translate (pages)</h5>';
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Enqueue jobs</strong></div><div class="card-body">';
if ($created > 0) {
	$content .= '<div class="alert alert-success py-2 mb-3">Created ' . (int)$created . ' job(s). See <a href="/admin.php?m=translations&amp;tab=monitor&amp;mtab=jobs">Translation jobs</a>.</div>';
}
$content .= '<form method="post">';
$content .= '<input type="hidden" name="create_batch" value="1">';

$content .= '<div class="row g-2">';
$content .= '<div class="col-md-4"><label class="form-label">Target languages</label><select class="form-select" name="dst_lang_ids[]" multiple size="8">';
foreach ($langs as $l) {
	$content .= '<option value="' . (int)$l['id'] . '">' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</option>';
}
$content .= '</select><div class="small text-muted mt-1">Hold Ctrl/Cmd to select multiple.</div></div>';

$content .= '<div class="col-md-8"><label class="form-label">Pages</label><select class="form-select" name="page_ids[]" multiple size="12">';
foreach ($pages as $p) {
	$label = (string)$p['name'];
	$url = (string)$p['url'];
	$content .= '<option value="' . (int)$p['id'] . '">' . htmlspecialchars('#' . (int)$p['id'] . ' ' . $label . ($url !== '' ? ' — ' . $url : '')) . '</option>';
}
$content .= '</select></div>';
$content .= '</div>';

$content .= '<div class="row g-2 mt-2 align-items-end">';
$content .= '<div class="col-md-2"><label class="form-label">Priority</label><input class="form-control" type="number" name="priority" value="0"></div>';
$content .= '<div class="col-md-6"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="only_missing" id="only_missing" value="1" checked><label class="form-check-label" for="only_missing">Only create jobs if translation missing</label></div></div>';
$content .= '<div class="col-md-4"><button class="btn btn-primary btn-sm" type="submit">Create jobs</button></div>';
$content .= '</div>';

$content .= '</form>';
$content .= '</div></div></div>';

