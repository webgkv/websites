<?php
/**
 * SEO: centralized index rules (table seo_index_rules).
 */
$page_name = 'Index rules';

$get = array_merge(array('u' => '', 'entity' => '', 'id' => '', 'n' => 1), (array) $get);
$seo_u = isset($get['u']) ? (string) $get['u'] : '';

require_once ROOT_DIR . 'functions/seo_index_rules.php';
seo_index_rules_ensure_table();

$entity_map = seo_index_rules_entity_map();
$engine_opts = seo_index_rules_engine_options();
$route_map = seo_index_rules_route_map();

// --- Save overview (site + routes + entities)
if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_index_rules_overview_save'])) {
	$site_block = !empty($_POST['site_block']);
	$site_eng = isset($_POST['site_engines']) ? (string) $_POST['site_engines'] : 'all';
	seo_index_rules_save('site', 'site', 0, $site_block, $site_eng);

	foreach ($route_map as $rk => $rlabel) {
		$block = !empty($_POST['route_block'][$rk]);
		$eng = isset($_POST['route_engines'][$rk]) ? (string) $_POST['route_engines'][$rk] : 'inherit';
		$list = null;
		if ($rk === 'demo_app' && isset($_POST['demo_app_langs'])) {
			$list = trim(stripslashes_smart((string) $_POST['demo_app_langs']));
		}
		if ($block || $site_block || $rk === 'demo_app') {
			seo_index_rules_save('route', $rk, 0, $block ? 1 : 0, $eng, $list);
		} else {
			seo_index_rules_delete('route', $rk, 0);
		}
	}

	foreach ($entity_map as $ent => $info) {
		$block = !empty($_POST['entity_block'][$ent]);
		$eng = isset($_POST['entity_engines'][$ent]) ? (string) $_POST['entity_engines'][$ent] : 'inherit';
		if ($block) {
			seo_index_rules_save('entity', $ent, 0, 1, $eng);
		} else {
			seo_index_rules_delete('entity', $ent, 0);
		}
	}

	seo_index_rules_load_all(true);
	$_SESSION['admin_flash_success'] = 'Saved.';
	header('Location: /admin.php?m=seo_index_rules');
	exit;
}

// --- Save entity list (per-item overrides)
if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_index_rules_list_save'])) {
	$entity_post = isset($_POST['entity']) ? trim((string) $_POST['entity']) : '';
	if (!isset($entity_map[$entity_post])) {
		$_SESSION['admin_flash_error'] = 'Unknown entity.';
		header('Location: /admin.php?m=seo_index_rules');
		exit;
	}
	$blocks = isset($_POST['item_block']) && is_array($_POST['item_block']) ? $_POST['item_block'] : array();
	$engines = isset($_POST['item_engines']) && is_array($_POST['item_engines']) ? $_POST['item_engines'] : array();
	$cleared = isset($_POST['item_clear']) && is_array($_POST['item_clear']) ? $_POST['item_clear'] : array();
	$all_ids = array_unique(array_merge(array_keys($blocks), array_keys($engines), array_keys($cleared)));

	foreach ($all_ids as $raw_id) {
		$id = (int) $raw_id;
		if ($id <= 0) {
			continue;
		}
		if (!empty($cleared[$raw_id])) {
			seo_index_rules_delete('item', $entity_post, $id);
			continue;
		}
		$block = !empty($blocks[$raw_id]) ? 1 : 0;
		$eng = isset($engines[$raw_id]) ? (string) $engines[$raw_id] : 'inherit';
		seo_index_rules_save('item', $entity_post, $id, $block, $eng);
	}

	seo_index_rules_load_all(true);
	$_SESSION['admin_flash_success'] = 'Saved.';
	$n = isset($_POST['return_n']) ? max(1, (int) $_POST['return_n']) : 1;
	header('Location: /admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_post) . '&n=' . $n);
	exit;
}

// --- List view
if ($seo_u === 'list') {
	$entity_cur = isset($get['entity']) ? trim((string) $get['entity']) : '';
	if (!isset($entity_map[$entity_cur])) {
		$content = '<div class="alert alert-warning">Unknown entity.</div>';
		require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
		exit;
	}
	$info = $entity_map[$entity_cur];
	$table = $info['table'];
	$per_page = 50;
	$n = isset($get['n']) ? max(1, (int) $get['n']) : 1;
	$offset = ($n - 1) * $per_page;
	$total = (int) @mysql_select("SELECT COUNT(*) FROM `" . mysql_res($table) . "` WHERE display=1", 'string');
	$pages_total = max(1, (int) ceil($total / $per_page));

	$select = !empty($info['profile_only'])
		? "id, name AS title, '' AS url"
		: "id, COALESCE(NULLIF(title,''), name) AS title, url";
	$rows = mysql_select("
		SELECT {$select}
		FROM `" . mysql_res($table) . "`
		WHERE display=1
		ORDER BY id DESC
		LIMIT {$offset}, {$per_page}
	", 'rows');
	if (!$rows) {
		$rows = array();
	}

	$page_name = $info['label'];

	$content = '<div class="admin-module-page">';
	$content .= '<p class="mb-3"><a href="/admin.php?m=seo_index_rules">&larr; Index rules</a></p>';
	$content .= '<h5 class="mb-3">' . htmlspecialchars($page_name) . '</h5>';

	$content .= '<form method="post" action="/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_cur) . '&n=' . $n . '">';
	$content .= '<input type="hidden" name="seo_index_rules_list_save" value="1">';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '">';
	$content .= '<input type="hidden" name="return_n" value="' . $n . '">';

	$content .= '<div class="table-responsive"><table class="table table-sm table-striped">';
	$content .= '<thead><tr><th style="width:4rem">Block</th><th>ID</th><th>Title</th><th>URL</th><th>Engines</th><th>Clear</th></tr></thead><tbody>';
	foreach ($rows as $row) {
		$id = (int) $row['id'];
		$item = seo_index_rules_get('item', $entity_cur, $id);
		$blocked = $item && !empty($item['block']);
		$eng = $item ? $item['engines'] : 'inherit';
		$title = trim((string) ($row['title'] ?? ''));
		if ($title === '') {
			$title = '#' . $id;
		}
		$url = trim((string) ($row['url'] ?? ''));
		$content .= '<tr>';
		$content .= '<td><input type="checkbox" name="item_block[' . $id . ']" value="1"' . ($blocked ? ' checked' : '') . '></td>';
		$content .= '<td>' . $id . '</td>';
		$content .= '<td>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td class="small">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</td>';
		$content .= '<td><select class="form-control form-control-sm" name="item_engines[' . $id . ']">';
		foreach ($engine_opts as $ek => $elabel) {
			$content .= '<option value="' . htmlspecialchars($ek, ENT_QUOTES, 'UTF-8') . '"' . ($eng === $ek ? ' selected' : '') . '>' . htmlspecialchars($elabel, ENT_QUOTES, 'UTF-8') . '</option>';
		}
		$content .= '</select></td>';
		$content .= '<td>';
		if ($item) {
			$content .= '<label class="mb-0"><input type="checkbox" name="item_clear[' . $id . ']" value="1"> Clear</label>';
		} else {
			$content .= '—';
		}
		$content .= '</td></tr>';
	}
	$content .= '</tbody></table></div>';

	if ($pages_total > 1) {
		$content .= '<nav class="mb-3"><ul class="pagination pagination-sm">';
		for ($p = 1; $p <= $pages_total; $p++) {
			$active = ($p === $n) ? ' active' : '';
			$content .= '<li class="page-item' . $active . '"><a class="page-link" href="/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_cur) . '&n=' . $p . '">' . $p . '</a></li>';
		}
		$content .= '</ul></nav>';
	}

	$content .= '<button type="submit" class="btn btn-primary">Save</button>';
	$content .= '</form></div>';

	require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
	exit;
}

// --- Overview
$site = seo_index_rules_get('site', 'site', 0);
if (!$site) {
	$site = array('block' => 0, 'engines' => 'inherit');
}
$demo_route = seo_index_rules_get('route', 'demo_app', 0);
$demo_app_langs = ($demo_route && !empty($demo_route['engines_list']))
	? (string) $demo_route['engines_list']
	: 'en';

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">' . htmlspecialchars($page_name) . '</h5>';

$content .= '<form method="post" action="/admin.php?m=seo_index_rules">';
$content .= '<input type="hidden" name="seo_index_rules_overview_save" value="1">';

$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Site</strong></div><div class="card-body">';
$content .= '<div class="form-row align-items-center">';
$content .= '<div class="col-auto"><label class="mb-0"><input type="checkbox" name="site_block" value="1"' . (!empty($site['block']) ? ' checked' : '') . '> Block</label></div>';
$content .= '<div class="col-auto"><label class="mb-0 mr-2">Engines</label><select class="form-control form-control-sm d-inline-block" style="width:auto" name="site_engines">';
foreach ($engine_opts as $ek => $elabel) {
	if ($ek === 'inherit') {
		continue;
	}
	$sel = (!empty($site['engines']) && $site['engines'] === $ek) ? ' selected' : '';
	if ($ek === 'all' && empty($site['engines'])) {
		$sel = ' selected';
	}
	$content .= '<option value="' . htmlspecialchars($ek, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($elabel, ENT_QUOTES, 'UTF-8') . '</option>';
}
$content .= '</select></div></div>';
$content .= '</div></div>';

$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Routes</strong></div><div class="card-body p-0">';
$content .= '<table class="table table-sm mb-0"><thead><tr><th>Route</th><th>Block</th><th>Engines</th></tr></thead><tbody>';
foreach ($route_map as $rk => $rlabel) {
	$route = seo_index_rules_get('route', $rk, 0);
	$rb = $route && !empty($route['block']);
	$reng = $route ? $route['engines'] : 'inherit';
	$content .= '<tr><td>' . htmlspecialchars($rlabel, ENT_QUOTES, 'UTF-8') . '</td>';
	$content .= '<td><input type="checkbox" name="route_block[' . htmlspecialchars($rk, ENT_QUOTES, 'UTF-8') . ']" value="1"' . ($rb ? ' checked' : '') . '></td>';
	$content .= '<td><select class="form-control form-control-sm" name="route_engines[' . htmlspecialchars($rk, ENT_QUOTES, 'UTF-8') . ']">';
	foreach ($engine_opts as $ek => $elabel) {
		$content .= '<option value="' . htmlspecialchars($ek, ENT_QUOTES, 'UTF-8') . '"' . ($reng === $ek ? ' selected' : '') . '>' . htmlspecialchars($elabel, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$content .= '</select></td></tr>';
}
$content .= '</tbody></table>';
$content .= '<div class="p-3 border-top"><label class="mb-1">Demo app languages</label>';
$content .= '<input type="text" class="form-control form-control-sm" name="demo_app_langs" value="' . htmlspecialchars($demo_app_langs, ENT_QUOTES, 'UTF-8') . '"></div>';
$content .= '</div></div>';

$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Sections</strong></div><div class="card-body p-0">';
$content .= '<table class="table table-sm mb-0"><thead><tr><th>Section</th><th>Block</th><th>Engines</th><th></th></tr></thead><tbody>';
foreach ($entity_map as $ent => $info) {
	$ent_row = seo_index_rules_get('entity', $ent, 0);
	$eb = $ent_row && !empty($ent_row['block']);
	$eeng = $ent_row ? $ent_row['engines'] : 'inherit';
	$cnt = (int) @mysql_select("SELECT COUNT(*) FROM `" . mysql_res($info['table']) . "` WHERE display=1", 'string');
	$content .= '<tr><td>' . htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') . ' <span class="badge badge-secondary">' . $cnt . '</span></td>';
	$content .= '<td><input type="checkbox" name="entity_block[' . htmlspecialchars($ent, ENT_QUOTES, 'UTF-8') . ']" value="1"' . ($eb ? ' checked' : '') . '></td>';
	$content .= '<td><select class="form-control form-control-sm" name="entity_engines[' . htmlspecialchars($ent, ENT_QUOTES, 'UTF-8') . ']">';
	foreach ($engine_opts as $ek => $elabel) {
		$content .= '<option value="' . htmlspecialchars($ek, ENT_QUOTES, 'UTF-8') . '"' . ($eeng === $ek ? ' selected' : '') . '>' . htmlspecialchars($elabel, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$content .= '</select></td>';
	$content .= '<td><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($ent) . '">Materials</a></td></tr>';
}
$content .= '</tbody></table></div></div>';

$content .= '<button type="submit" class="btn btn-primary">Save</button>';
$content .= '</form></div>';

require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
