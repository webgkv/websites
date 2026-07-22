<?php
/**
 * SEO: centralized index rules (table seo_index_rules).
 */
$page_name = 'Index rules';

$get = array_merge(array('u' => '', 'entity' => '', 'id' => '', 'n' => 1, 'per_page' => 50, 'q' => ''), (array) $get);
$seo_u = isset($get['u']) ? (string) $get['u'] : '';

require_once ROOT_DIR . 'functions/seo_index_rules.php';
seo_index_rules_ensure_table();

$entity_map = seo_index_rules_entity_map();
$engine_opts = seo_index_rules_engine_options();

$seo_index_rules_save_engines_list = function ($engines, $custom_raw, $keep_list = null) {
	$engines = seo_index_rules_normalize_engines($engines);
	if ($engines === 'custom') {
		return seo_index_rules_sanitize_custom_bot_name($custom_raw);
	}
	return $keep_list;
};

$seo_index_rules_render_engine_fields = function ($field_prefix, $selected, $custom_value = '', $inherit_ok = true) use ($engine_opts) {
	$selected = seo_index_rules_normalize_engines($selected);
	$html = '<select class="form-control form-control-sm seo-idx-engines" name="' . htmlspecialchars($field_prefix, ENT_QUOTES, 'UTF-8') . '" data-custom-for="' . htmlspecialchars($field_prefix, ENT_QUOTES, 'UTF-8') . '">';
	foreach ($engine_opts as $ek => $elabel) {
		if (!$inherit_ok && $ek === 'inherit') {
			continue;
		}
		$html .= '<option value="' . htmlspecialchars($ek, ENT_QUOTES, 'UTF-8') . '"' . ($selected === $ek ? ' selected' : '') . '>' . htmlspecialchars($elabel, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	$html .= '</select>';
	$custom_name = str_replace('engines', 'custom', $field_prefix);
	$show = ($selected === 'custom') ? '' : ' style="display:none"';
	$html .= '<input type="text" class="form-control form-control-sm mt-1 seo-idx-custom-bot" name="' . htmlspecialchars($custom_name, ENT_QUOTES, 'UTF-8') . '" data-custom-for="' . htmlspecialchars($field_prefix, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $custom_value, ENT_QUOTES, 'UTF-8') . '" placeholder="Bot meta name or UA token"' . $show . '>';
	return $html;
};

// --- Save site-wide rule
if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_index_rules_scope_save'])) {
	$site_block = !empty($_POST['site_block']);
	$site_eng = isset($_POST['site_engines']) ? (string) $_POST['site_engines'] : 'all';
	$site_custom = isset($_POST['site_custom']) ? (string) $_POST['site_custom'] : '';
	$site_list = $seo_index_rules_save_engines_list($site_eng, $site_custom);
	seo_index_rules_save('site', 'site', 0, $site_block, $site_eng, $site_list);

	seo_index_rules_load_all(true);
	$_SESSION['admin_flash_success'] = 'Site rule saved.';
	header('Location: /admin.php?m=seo_index_rules');
	exit;
}

// --- Save entity section rule
if ((string) ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_index_rules_entity_save'])) {
	$entity_post = isset($_POST['entity']) ? trim((string) $_POST['entity']) : '';
	if (!isset($entity_map[$entity_post])) {
		$_SESSION['admin_flash_error'] = 'Unknown entity.';
		header('Location: /admin.php?m=seo_index_rules');
		exit;
	}
	$block = !empty($_POST['entity_block']);
	$eng = isset($_POST['entity_engines']) ? (string) $_POST['entity_engines'] : 'inherit';
	$custom = isset($_POST['entity_custom']) ? (string) $_POST['entity_custom'] : '';
	if ($block) {
		$list = $seo_index_rules_save_engines_list($eng, $custom);
		seo_index_rules_save('entity', $entity_post, 0, 1, $eng, $list);
	} else {
		seo_index_rules_delete('entity', $entity_post, 0);
	}
	seo_index_rules_load_all(true);
	$_SESSION['admin_flash_success'] = 'Section rule saved.';
	header('Location: /admin.php?m=seo_index_rules&u=entity&entity=' . rawurlencode($entity_post));
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
	$customs = isset($_POST['item_custom']) && is_array($_POST['item_custom']) ? $_POST['item_custom'] : array();
	$cleared = isset($_POST['item_clear']) && is_array($_POST['item_clear']) ? $_POST['item_clear'] : array();
	$all_ids = array_unique(array_merge(array_keys($blocks), array_keys($engines), array_keys($customs), array_keys($cleared)));

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
		$custom = isset($customs[$raw_id]) ? (string) $customs[$raw_id] : '';
		$list = $seo_index_rules_save_engines_list($eng, $custom);
		seo_index_rules_save('item', $entity_post, $id, $block, $eng, $list);
	}

	seo_index_rules_load_all(true);
	$_SESSION['admin_flash_success'] = 'Materials saved.';
	$n = isset($_POST['return_n']) ? max(1, (int) $_POST['return_n']) : 1;
	$per_page = isset($_POST['return_per_page']) ? (int) $_POST['return_per_page'] : 50;
	header('Location: /admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_post) . '&n=' . $n . '&per_page=' . $per_page);
	exit;
}

$seo_index_rules_engine_js = '<script>(function(){function toggleCustom(sel){var key=sel.getAttribute("data-custom-for");if(!key)return;document.querySelectorAll(".seo-idx-custom-bot[data-custom-for=\'"+key+"\']").forEach(function(inp){inp.style.display=sel.value==="custom"?"":"none";});}document.querySelectorAll(".seo-idx-engines").forEach(function(sel){sel.addEventListener("change",function(){toggleCustom(sel);});toggleCustom(sel);});})();</script>';

// --- Entity section screen
if ($seo_u === 'entity') {
	$entity_cur = isset($get['entity']) ? trim((string) $get['entity']) : '';
	if (!isset($entity_map[$entity_cur])) {
		$content = '<div class="alert alert-warning">Unknown entity.</div>';
		require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
		exit;
	}
	$info = $entity_map[$entity_cur];
	$ent_row = seo_index_rules_get('entity', $entity_cur, 0);
	$eb = $ent_row && !empty($ent_row['block']);
	$eeng = $ent_row ? $ent_row['engines'] : 'inherit';
	$ecustom = ($ent_row && $eeng === 'custom') ? (string) ($ent_row['engines_list'] ?? '') : '';
	$cnt = (int) @mysql_select("SELECT COUNT(*) FROM `" . mysql_res($info['table']) . "` WHERE display=1", 'string');

	$page_name = 'Index rules: ' . $info['label'];
	$content = '<div class="card"><div class="card-body">';
	$content .= '<p class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_index_rules">&larr; Overview</a></p>';
	$content .= '<h5 class="mb-1">' . htmlspecialchars($info['label']) . '</h5>';
	$content .= '<p class="text-muted small mb-3">' . (int) $cnt . ' published rows • <a href="/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_cur) . '">Open materials list</a></p>';

	$content .= '<div class="p-3 rounded border bg-white mb-4">';
	$content .= '<h6 class="font-weight-bold text-dark mb-3">Section rule</h6>';
	$content .= '<form method="post" action="/admin.php?m=seo_index_rules&u=entity&entity=' . rawurlencode($entity_cur) . '">';
	$content .= '<input type="hidden" name="seo_index_rules_entity_save" value="1">';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '">';
	$content .= '<div class="form-row align-items-end">';
	$content .= '<div class="form-group col-md-3 mb-2 mb-md-0"><label class="mb-1 d-block"><input type="checkbox" name="entity_block" value="1"' . ($eb ? ' checked' : '') . '> Block indexing</label></div>';
	$content .= '<div class="form-group col-md-4 mb-2 mb-md-0"><label class="small text-muted d-block mb-1">Engines</label>' . $seo_index_rules_render_engine_fields('entity_engines', $eeng, $ecustom, true) . '</div>';
	$content .= '<div class="form-group col-md-3 mb-0"><button type="submit" class="btn btn-primary btn-block">Save section</button></div>';
	$content .= '</div></form></div>';

	$content .= '</div></div>';
	$content .= $seo_index_rules_engine_js;
	require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
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
	$per_page = isset($get['per_page']) ? (int) $get['per_page'] : 50;
	if (!in_array($per_page, array(50, 100, 200), true)) {
		$per_page = 50;
	}
	$n = isset($get['n']) ? max(1, (int) $get['n']) : 1;
	$q = isset($get['q']) ? trim(stripslashes_smart((string) $get['q'])) : '';
	$where = 'display=1';
	if ($q !== '') {
		if (ctype_digit($q)) {
			$where .= ' AND id=' . (int) $q;
		} else {
			$like = mysql_res('%' . $q . '%');
			$where .= " AND (COALESCE(NULLIF(title,''), name) LIKE '{$like}' OR url LIKE '{$like}')";
		}
	}
	$total = (int) @mysql_select("SELECT COUNT(*) FROM `" . mysql_res($table) . "` WHERE {$where}", 'string');
	$pages_total = max(1, (int) ceil($total / $per_page));
	if ($n > $pages_total) {
		$n = $pages_total;
	}
	$offset = ($n - 1) * $per_page;

	$select = !empty($info['profile_only'])
		? "id, name AS title, '' AS url"
		: "id, COALESCE(NULLIF(title,''), name) AS title, url";
	$rows = mysql_select("
		SELECT {$select}
		FROM `" . mysql_res($table) . "`
		WHERE {$where}
		ORDER BY id DESC
		LIMIT {$offset}, {$per_page}
	", 'rows');
	if (!$rows) {
		$rows = array();
	}

	$ent_row = seo_index_rules_get('entity', $entity_cur, 0);
	$section_hint = 'Section: open';
	if ($ent_row && !empty($ent_row['block'])) {
		$section_hint = 'Section: blocked · ' . seo_index_rules_engine_label($ent_row['engines'], $ent_row['engines_list'] ?? '');
	}

	$page_name = 'Index rules: ' . $info['label'];
	$base_url = '/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_cur) . '&per_page=' . $per_page;
	if ($q !== '') {
		$base_url .= '&q=' . rawurlencode($q);
	}

	$content = '<div class="card"><div class="card-body">';
	$content .= '<p class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_index_rules">&larr; Overview</a> ';
	$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_index_rules&u=entity&entity=' . rawurlencode($entity_cur) . '">Section rule</a></p>';
	$content .= '<h5 class="mb-1">' . htmlspecialchars($info['label']) . '</h5>';
	$content .= '<p class="text-muted small mb-3">' . htmlspecialchars($section_hint) . '</p>';

	$content .= '<form method="get" action="/admin.php" class="d-flex flex-wrap align-items-center gap-2 mb-3">';
	$content .= '<input type="hidden" name="m" value="seo_index_rules" />';
	$content .= '<input type="hidden" name="u" value="list" />';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="n" value="1" />';
	$content .= '<label class="mb-0"><span class="text-muted small d-block">Search by ID or title</span>';
	$content .= '<input type="text" name="q" value="' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '" class="form-control form-control-sm" placeholder="125 or aviator signals" /></label>';
	$content .= '<span class="text-muted small">Per page:</span>';
	foreach (array(50, 100, 200) as $pp) {
		$rid = 'seo_idx_per_' . $pp;
		$chk = ($per_page === $pp) ? ' checked' : '';
		$content .= '<div class="form-check form-check-inline mb-0">';
		$content .= '<input class="form-check-input" type="radio" name="per_page" id="' . $rid . '" value="' . $pp . '"' . $chk . ' onchange="this.form.submit()" />';
		$content .= '<label class="form-check-label" for="' . $rid . '">' . $pp . '</label></div>';
	}
	$content .= '<button type="submit" class="btn btn-primary btn-sm">Apply</button></form>';

	$content .= '<form method="post" action="/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($entity_cur) . '&n=' . $n . '&per_page=' . $per_page . '">';
	$content .= '<input type="hidden" name="seo_index_rules_list_save" value="1">';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '">';
	$content .= '<input type="hidden" name="return_n" value="' . $n . '">';
	$content .= '<input type="hidden" name="return_per_page" value="' . $per_page . '">';

	$content .= '<div class="table-responsive"><table class="table table-sm align-middle">';
	$content .= '<thead><tr><th style="width:4rem">Block</th><th>ID</th><th>Title</th><th>URL</th><th>Engines</th><th>Clear</th></tr></thead><tbody>';
	if (empty($rows)) {
		$content .= '<tr><td colspan="6" class="text-muted">No rows in this view.</td></tr>';
	} else {
		foreach ($rows as $row) {
			$id = (int) $row['id'];
			$item = seo_index_rules_get('item', $entity_cur, $id);
			$blocked = $item && !empty($item['block']);
			$eng = $item ? $item['engines'] : 'inherit';
			$custom = ($item && $eng === 'custom') ? (string) ($item['engines_list'] ?? '') : '';
			$title = trim((string) ($row['title'] ?? ''));
			if ($title === '') {
				$title = '#' . $id;
			}
			$url = trim((string) ($row['url'] ?? ''));
			$content .= '<tr>';
			$content .= '<td><input type="checkbox" class="form-check-input m-0" name="item_block[' . $id . ']" value="1"' . ($blocked ? ' checked' : '') . '></td>';
			$content .= '<td>' . $id . '</td>';
			$content .= '<td>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</td>';
			$content .= '<td class="small text-muted">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</td>';
			$content .= '<td style="min-width:12rem">' . $seo_index_rules_render_engine_fields('item_engines[' . $id . ']', $eng, $custom, true) . '</td>';
			$content .= '<td>';
			if ($item) {
				$content .= '<label class="mb-0"><input type="checkbox" class="form-check-input m-0" name="item_clear[' . $id . ']" value="1"> Clear</label>';
			} else {
				$content .= '<span class="text-muted">—</span>';
			}
			$content .= '</td></tr>';
		}
	}
	$content .= '</tbody></table></div>';

	if ($total > $per_page) {
		$count_max = 7;
		$count = (int) ceil($total / $per_page);
		$list = array();
		if ($count <= $count_max) {
			for ($i = 1; $i <= $count; $i++) {
				$list[] = array($i, $i);
			}
		} else {
			if ($n < ($e = $count_max - 2)) {
				for ($i = 1; $i <= $e; $i++) {
					$list[] = array($i, $i);
				}
				$list[] = array(ceil(($count + $e) / 2), 0);
				$list[] = array($count, $count);
			} elseif ($n > ($s = $count - $count_max + 3)) {
				$list[] = array(1, 1);
				$list[] = array(ceil(($s + 1) / 2), 0);
				for ($i = $s; $i <= $count; $i++) {
					$list[] = array($i, $i);
				}
			} else {
				$s = $n - 2;
				$e = $n + 2;
				$list[] = array(1, 1);
				$list[] = array(ceil(($s + 1) / 2), 0);
				for ($i = $s; $i <= $e; $i++) {
					$list[] = array($i, $i);
				}
				$list[] = array(ceil(($count + $e) / 2), 0);
				$list[] = array($count, $count);
			}
		}
		$content .= '<div class="pagination pagination-bottom mt-3"><nav><ul class="pagination pagination-sm pagination-rounded mb-0">';
		foreach ($list as $v) {
			$page = (int) ($v[0] ?? 1);
			if ((int) ($v[1] ?? 1) === 0) {
				$content .= '<li class="page-item"><span class="page-link">…</span></li>';
				continue;
			}
			$link = $base_url . '&n=' . $page;
			if ($page === $n) {
				$content .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
			} else {
				$content .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . $page . '</a></li>';
			}
		}
		$content .= '</ul></nav></div>';
	}

	$content .= '<button type="submit" class="btn btn-primary mt-3">Save materials</button>';
	$content .= '</form></div></div>';
	$content .= $seo_index_rules_engine_js;
	require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
	exit;
}

// --- Overview
$site = seo_index_rules_get('site', 'site', 0);
if (!$site) {
	$site = array('block' => 0, 'engines' => 'inherit', 'engines_list' => '');
}
$site_custom = (!empty($site['engines']) && $site['engines'] === 'custom') ? (string) ($site['engines_list'] ?? '') : '';

$page_name = 'Index rules (overview)';
$content = '<div class="card"><div class="card-body">';
$content .= '<h5 class="mb-3">Index rules</h5>';

$content .= '<div class="p-3 rounded border bg-white mb-4">';
$content .= '<h6 class="font-weight-bold text-dark mb-3">Site-wide</h6>';
$content .= '<form method="post" action="/admin.php?m=seo_index_rules">';
$content .= '<input type="hidden" name="seo_index_rules_scope_save" value="1">';
$content .= '<div class="form-row align-items-end">';
$content .= '<div class="form-group col-md-3 mb-2 mb-md-0"><label class="mb-0"><input type="checkbox" name="site_block" value="1"' . (!empty($site['block']) ? ' checked' : '') . '> Block indexing</label></div>';
$content .= '<div class="form-group col-md-4 mb-2 mb-md-0"><label class="small text-muted d-block mb-1">Engines</label>' . $seo_index_rules_render_engine_fields('site_engines', !empty($site['engines']) ? $site['engines'] : 'all', $site_custom, false) . '</div>';
$content .= '<div class="form-group col-md-3 mb-0"><button type="submit" class="btn btn-primary btn-sm btn-block">Save</button></div>';
$content .= '</div></form></div>';

$content .= '<div class="tstats-overview">';
$content .= '<h6 class="tstats-section-label mb-3">Content types</h6>';
$content .= '<div class="row">';
foreach ($entity_map as $ent => $info) {
	$_tbl = $info['table'];
	$exists = @mysql_select("SHOW TABLES LIKE '" . mysql_res($_tbl) . "'", 'num_rows');
	if ((int) $exists <= 0) {
		continue;
	}
	$cnt = (int) @mysql_select("SELECT COUNT(*) FROM `" . mysql_res($_tbl) . "` WHERE display=1", 'string');
	$ent_row = seo_index_rules_get('entity', $ent, 0);
	$status = 'Open';
	$status_cls = 'text-success';
	if ($ent_row && !empty($ent_row['block'])) {
		$status = 'Blocked · ' . seo_index_rules_engine_label($ent_row['engines'], $ent_row['engines_list'] ?? '');
		$status_cls = 'text-warning';
	}
	$list_href = '/admin.php?m=seo_index_rules&u=list&entity=' . rawurlencode($ent);
	$section_href = '/admin.php?m=seo_index_rules&u=entity&entity=' . rawurlencode($ent);
	$content .= '<div class="col-xl-4 col-md-6 mb-4">';
	$content .= '<div class="card h-100 tstats-overview-target-card shadow-sm border-0 position-relative" style="border-top:4px solid #0d6efd !important;border:1px solid rgba(0,0,0,.08);">';
	$content .= '<a href="' . htmlspecialchars($list_href, ENT_QUOTES, 'UTF-8') . '" class="stretched-link text-dark tstats-overview-target-link" aria-label="Open ' . htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') . ' list"></a>';
	$content .= '<div class="card-body position-relative">';
	$content .= '<h5 class="mb-1 text-dark">' . htmlspecialchars($info['label']) . '</h5>';
	$content .= '<span class="badge badge-secondary">' . htmlspecialchars($ent) . '</span>';
	$content .= '<div class="row mt-3 align-items-end">';
	$content .= '<div class="col-6"><div class="tstats-metric mb-0"><div class="tstats-metric-label">Published rows</div>';
	$content .= '<div class="tstats-metric-value text-dark">' . $cnt . '</div></div></div>';
	$content .= '<div class="col-6 text-end position-relative" style="z-index:2;">';
	$content .= '<div class="tstats-metric-label">Index status</div>';
	$content .= '<div class="tstats-metric-value small ' . $status_cls . '">' . htmlspecialchars($status) . '</div>';
	$content .= '<a class="btn btn-link btn-sm p-0" href="' . htmlspecialchars($section_href, ENT_QUOTES, 'UTF-8') . '">Section rule</a>';
	$content .= '</div></div></div></div></div>';
}
$content .= '</div></div>';
$content .= '</div></div>';
$content .= $seo_index_rules_engine_js;
unset($_tbl);
$table = array();

require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
