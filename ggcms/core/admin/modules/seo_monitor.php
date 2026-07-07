<?php
/**
 * SEO Monitor: per-entity lists, per-cluster checks, JSON export/import (meta vs full).
 */
$page_name = 'SEO Monitor';

$get = array_merge(array('u' => '', 'entity' => '', 'id' => '', 'filter' => 'all', 'q' => ''), (array)$get);
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	foreach (array('u', 'entity', 'filter', 'q') as $pk) {
		if (isset($_POST[$pk]) && (string)$_POST[$pk] !== '') {
			$get[$pk] = stripslashes_smart((string)$_POST[$pk]);
		}
	}
	foreach (array('id', 'entity_id') as $pk) {
		if (isset($_POST[$pk]) && (string)$_POST[$pk] !== '') {
			$get['id'] = (int)$_POST[$pk];
		}
	}
}

$seo_u = isset($get['u']) ? (string)$get['u'] : '';

require_once(ROOT_DIR . 'functions/seo_monitor.php');

// Async SEO score (dashboard + overview cards)
if ($seo_u === 'ajax_entity') {
	header('Content-Type: application/json; charset=utf-8');
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$ent = isset($get['entity']) ? trim(stripslashes_smart((string)$get['entity'])) : '';
	$refresh = !empty($get['refresh']);
	echo json_encode(seo_monitor_resolve_entity_score_state($ent, $refresh), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($seo_u === 'ajax_enqueue_rebuild_overview') {
	header('Content-Type: application/json; charset=utf-8');
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		echo json_encode(array('ok' => false, 'message' => 'admin_jobs table missing'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	if (!function_exists('admin_jobs_enqueue')) {
		require_once ROOT_DIR . 'functions/admin_jobs.php';
	}
	$jid = seo_monitor_pending_overview_rebuild_job_id();
	if ($jid <= 0) {
		$new_id = admin_jobs_enqueue('seo_monitor', 'rebuild_overview', array(), array('priority' => 0));
		$jid = $new_id ? (int)$new_id : 0;
	}
	if ($jid <= 0) {
		echo json_encode(array('ok' => false, 'message' => 'Could not enqueue job'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	echo json_encode(array('ok' => true, 'job_id' => $jid), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($seo_u === 'ajax_job_status') {
	header('Content-Type: application/json; charset=utf-8');
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$jid = isset($get['job_id']) ? (int)$get['job_id'] : 0;
	if ($jid <= 0 || @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		echo json_encode(array('ok' => false, 'message' => 'Bad job id'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$row = mysql_select("SELECT id, module, action, status, message FROM admin_jobs WHERE id = " . $jid . " LIMIT 1", 'row');
	if (!$row || (string)$row['module'] !== 'seo_monitor' || !in_array((string)$row['action'], array('rebuild_entity', 'rebuild_overview'), true)) {
		echo json_encode(array('ok' => false, 'message' => 'Job not found'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$pp = seo_monitor_job_progress_public($row);
	echo json_encode(array(
		'ok' => true,
		'job' => array(
			'id' => $jid,
			'status' => $pp['status'],
			'percent' => $pp['percent'],
			'message' => $pp['message'],
			'action' => $pp['action'],
		),
	), JSON_UNESCAPED_UNICODE);
	exit;
}

if ($seo_u === 'ajax_list_row_check') {
	header('Content-Type: application/json; charset=utf-8');
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$ent = isset($get['entity']) ? trim(stripslashes_smart((string)$get['entity'])) : '';
	$rid = isset($get['id']) ? (int)$get['id'] : 0;
	echo json_encode(seo_monitor_list_row_issue_scan($ent, $rid), JSON_UNESCAPED_UNICODE);
	exit;
}

if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_monitor_hub_save'])) {
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
	$raw_slugs = isset($_POST['hub_page_slugs']) ? stripslashes_smart((string)$_POST['hub_page_slugs']) : '';
	$parts = array_filter(array_map('trim', preg_split('#[\s,]+#u', $raw_slugs)));
	$blog_on = !empty($_POST['hub_blog_listing']);
	$raw_ids = isset($_POST['hub_page_ids_extra']) ? stripslashes_smart((string)$_POST['hub_page_ids_extra']) : '';
	$ids = array_filter(array_map('intval', preg_split('#[\s,]+#', $raw_ids)));
	if (seo_monitor_hub_settings_save(array(
		'page_slugs' => $parts,
		'blog_listing_module' => $blog_on,
		'page_ids_extra' => $ids,
	))) {
		$_SESSION['admin_flash_success'] = 'Hub page rules saved (body_empty skipped for these listings).';
	} else {
		$_SESSION['admin_flash_error'] = 'Could not save hub page rules.';
	}
	header('Location: /admin.php?m=seo_monitor');
	exit;
}

$table_content_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
if (!$table_content_ok) {
	$content = '<div class="alert alert-warning"><strong>Table content_i18n not found.</strong> Run migration first.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

require_once(ROOT_DIR . 'admin/modules/_i18n.php');

$entity_map = seo_monitor_entity_map();
$cfg_ts = seo_monitor_translation_settings();
$source_lang_id = isset($cfg_ts['source_lang_id']) ? (int)$cfg_ts['source_lang_id'] : 1;
$cluster_langs = seo_monitor_cluster_languages($source_lang_id);
$cluster_lang_ids = array_map(function ($l) {
	return (int)$l['id'];
}, $cluster_langs);
$entity_cur = isset($get['entity']) ? trim((string)$get['entity']) : '';
$entity_id_cur = isset($get['id']) ? (int)$get['id'] : 0;
if ($entity_id_cur <= 0 && isset($get['entity_id'])) {
	$entity_id_cur = (int)$get['entity_id'];
}
// Match export JSON keys (e.g. Guides vs guides) to seo_monitor_entity_map() canonical keys.
$entity_cur = seo_monitor_entity_key_canonical($entity_cur);

// --- List: bulk include / exclude from SEO validation (stored in variables)
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_monitor_validation_bulk'])) {
	if (!function_exists('access') || !access('admin module', 'seo_monitor')) {
		header('HTTP/1.1 403 Forbidden');
		exit;
	}
	$entity_post = isset($_POST['entity']) ? trim(stripslashes_smart((string)$_POST['entity'])) : '';
	if (!isset($entity_map[$entity_post])) {
		$_SESSION['admin_flash_error'] = 'Unknown entity.';
		header('Location: /admin.php?m=seo_monitor');
		exit;
	}
	$action = isset($_POST['validation_action']) ? (string)$_POST['validation_action'] : '';
	if (!in_array($action, array('include', 'exclude'), true)) {
		$_SESSION['admin_flash_error'] = 'Invalid action.';
	} else {
		$ids = array();
		if (!empty($_POST['validation_ids']) && is_array($_POST['validation_ids'])) {
			foreach ($_POST['validation_ids'] as $raw) {
				$i = (int)$raw;
				if ($i > 0) {
					$ids[] = $i;
				}
			}
		}
		$ids = array_values(array_unique($ids));
		if ($ids === array()) {
			$_SESSION['admin_flash_error'] = 'No rows selected.';
		} elseif (seo_monitor_exclusions_apply_bulk($entity_post, $action, $ids)) {
			$_SESSION['admin_flash_success'] = $action === 'exclude'
				? ('Excluded ' . count($ids) . ' row(s) from SEO validation (overview score and list issues).')
				: ('Restored ' . count($ids) . ' row(s) to SEO validation.');
		} else {
			$_SESSION['admin_flash_error'] = 'Could not save exclusions.';
		}
	}
	$rf = isset($_POST['return_filter']) ? stripslashes_smart((string)$_POST['return_filter']) : 'all';
	$allowed_rf = array_merge(array('all'), seo_monitor_issue_codes_for_filter(), array('any_issue'));
	if (!in_array($rf, $allowed_rf, true)) {
		$rf = 'all';
	}
	$rn = isset($_POST['return_n']) ? max(1, (int)$_POST['return_n']) : 1;
	$rpp = isset($_POST['return_per_page']) ? (int)$_POST['return_per_page'] : 50;
	if (!in_array($rpp, array(50, 100, 200), true)) {
		$rpp = 50;
	}
	$rs = isset($_POST['return_sort']) ? stripslashes_smart((string)$_POST['return_sort']) : 'id';
	if (!in_array($rs, array('id', 'title'), true)) {
		$rs = 'id';
	}
	$rd = isset($_POST['return_dir']) ? stripslashes_smart((string)$_POST['return_dir']) : 'desc';
	if (!in_array($rd, array('asc', 'desc'), true)) {
		$rd = 'desc';
	}
	$rq = isset($_POST['q']) ? trim(stripslashes_smart((string)$_POST['q'])) : '';
	$redir = '/admin.php?m=seo_monitor&u=list&entity=' . rawurlencode($entity_post)
		. '&filter=' . rawurlencode($rf) . '&q=' . rawurlencode($rq) . '&n=' . $rn . '&per_page=' . $rpp
		. '&sort=' . rawurlencode($rs) . '&dir=' . rawurlencode($rd);
	header('Location: ' . $redir);
	exit;
}

// --- JSON export (download)
if ($seo_u === 'export') {
	if (!isset($entity_map[$entity_cur]) || $entity_id_cur <= 0) {
		header('Content-Type: application/json; charset=utf-8', true, 400);
		echo json_encode(array('error' => 'Bad entity or id'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$mode = isset($_GET['mode']) ? (string)$_GET['mode'] : 'meta';
	if ($mode === 'report') {
		$pack = seo_monitor_export_report_array($entity_cur, $entity_id_cur);
	} elseif ($mode === 'full') {
		$pack = seo_monitor_export_cluster_array($entity_cur, $entity_id_cur, 'full');
	} else {
		$mode = 'meta';
		$pack = seo_monitor_export_cluster_array($entity_cur, $entity_id_cur, 'meta');
	}
	if (empty($pack['ok'])) {
		header('Content-Type: application/json; charset=utf-8', true, 400);
		echo json_encode(array('error' => isset($pack['message']) ? $pack['message'] : 'Export failed'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$fn = 'seo-' . preg_replace('/[^a-z0-9_-]+/i', '_', $entity_cur) . '-' . $entity_id_cur . '-' . $mode . '.json';
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $fn . '"');
	echo json_encode($pack['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// --- Import POST (cluster screen)
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['seo_monitor_import'])) {
	if ($seo_u !== 'cluster' || !isset($entity_map[$entity_cur]) || $entity_id_cur <= 0) {
		$_SESSION['admin_flash_error'] = 'Import: bad route or record.';
		header('Location: /admin.php?m=seo_monitor');
		exit;
	}
	$apply_mode = isset($_POST['apply_mode']) ? (string)$_POST['apply_mode'] : 'full';
	if (!in_array($apply_mode, array('meta', 'full'), true)) {
		$apply_mode = 'full';
	}
	$dry = !empty($_POST['dry_run']);
	$raw = '';
	if (!empty($_FILES['import_file']['tmp_name']) && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
		$raw = (string)file_get_contents($_FILES['import_file']['tmp_name']);
	}
	$dec = seo_monitor_decode_import_json($raw);
	if (empty($dec['ok'])) {
		$_SESSION['admin_flash_error'] = isset($dec['message']) ? $dec['message'] : 'Invalid file';
	} else {
		$file_mode = 'meta';
		$val = seo_monitor_validate_import_payload($dec['payload'], $entity_cur, $entity_id_cur);
		if (!empty($val['ok']) && isset($val['file_mode'])) {
			$file_mode = ($val['file_mode'] === 'full') ? 'full' : 'meta';
		}
		// Cluster exports with mode=full carry HTML; Meta-only import leaves EN on old content_i18n.
		$forced_full = ($file_mode === 'full' && $apply_mode === 'meta');
		if ($forced_full) {
			$apply_mode = 'full';
		}
		$res = seo_monitor_import_cluster($entity_cur, $entity_id_cur, $dec['payload'], $apply_mode, $dry);
		if (!empty($res['ok'])) {
			$msg = $res['message'];
			if ($forced_full) {
				$msg .= ' Applied as Full import because the JSON was exported with mode=full (HTML body).';
			}
			$_SESSION['admin_flash_success'] = $msg;
		} else {
			$_SESSION['admin_flash_error'] = $res['message'];
		}
	}
	$redir = '/admin.php?m=seo_monitor&u=cluster&entity=' . rawurlencode($entity_cur) . '&id=' . $entity_id_cur;
	header('Location: ' . $redir);
	exit;
}

// --- Overview
if ($seo_u === '' || $seo_u === 'overview') {
	$page_name = 'SEO Monitor (overview)';
	$seo_rebuild_resume = isset($_GET['seo_rebuild_job']) ? (int)$_GET['seo_rebuild_job'] : 0;
	$content = '<div class="card"><div class="card-body">';
	$content .= '<h5 class="mb-2">SEO Monitor</h5>';
	$rebuild_alert_cls = 'alert-secondary mb-3';
	if ($seo_rebuild_resume <= 0) {
		$rebuild_alert_cls .= ' d-none';
	}
	$content .= '<div class="d-flex flex-wrap align-items-center gap-2 mb-2">';
	$content .= '<button type="button" class="btn btn-primary btn-sm" id="seo-mon-recalc-all">Recalculate all SEO scores</button>';
	$content .= '</div>';
	$content .= '<div class="alert ' . htmlspecialchars($rebuild_alert_cls, ENT_QUOTES, 'UTF-8') . '" id="seo-mon-rebuild-alert" data-job-id="' . (int)$seo_rebuild_resume . '">';
	$content .= '<strong>SEO score rebuild</strong>';
	$content .= '<div class="progress mt-2" style="height:6px;"><div class="progress-bar" id="seo-mon-rebuild-bar" role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div></div>';
	$content .= '<div class="small mt-1 text-muted" id="seo-mon-rebuild-text">Queued…</div>';
	$content .= '</div>';
	$content .= '<div class="tstats-overview">';
	$content .= '<h6 class="tstats-section-label mb-3">Content types</h6>';
	$content .= '<div class="row">';
	foreach ($entity_map as $ent => $info) {
		$table = $info['table'];
		$exists = @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows');
		if ((int)$exists <= 0) {
			continue;
		}
		$where = seo_monitor_display_where($table);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where, 'row');
		$cnt = $r && isset($r['c']) ? (int)$r['c'] : 0;
		$href = '/admin.php?m=seo_monitor&u=list&entity=' . rawurlencode($ent);
		$content .= '<div class="col-xl-4 col-md-6 mb-4">';
		$content .= '<div class="card h-100 tstats-overview-target-card shadow-sm border-0 position-relative" style="border-top:4px solid #0d6efd !important;border:1px solid rgba(0,0,0,.08);">';
		$content .= '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="stretched-link text-dark tstats-overview-target-link" aria-label="Open ' . htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') . ' list"></a>';
		// No z-index here: same z-index as .stretched-link::after would stack card-body above the link and block navigation (clicks hit an empty layer).
		$content .= '<div class="card-body position-relative">';
		$content .= '<h5 class="mb-1 text-dark">' . htmlspecialchars($info['label']) . '</h5>';
		$content .= '<span class="badge badge-secondary">' . htmlspecialchars($ent) . '</span>';
		$content .= '<div class="row mt-3 align-items-end">';
		$content .= '<div class="col-6">';
		$content .= '<div class="tstats-metric mb-0"><div class="tstats-metric-label">Published rows</div>';
		$content .= '<div class="tstats-metric-value text-dark">' . $cnt . '</div></div>';
		$content .= '</div>';
		$content .= '<div class="col-6 text-end seo-mon-entity-score position-relative" style="z-index:2;" data-entity="' . htmlspecialchars($ent, ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<div class="tstats-metric-label d-flex justify-content-end align-items-center flex-wrap" style="gap:2px;"><span>SEO score</span><button type="button" class="btn btn-link btn-sm p-0 lh-1 seo-mon-refresh" style="font-size:1rem;text-decoration:none;" title="Recalculate">↻</button></div>';
		$content .= '<div class="tstats-metric-value text-muted seo-mon-score-value">…</div>';
		$content .= '<div class="small text-muted seo-mon-score-hint" style="min-height:1.25em;"></div>';
		$content .= '</div></div>';
		// inner: close score col, metrics row, inner card-body, inner card, outer col (5 levels → 3 closes after row)
		$content .= '</div></div></div>';
	}
	$content .= '</div></div>';
	$content .= '<p class="mb-0"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations&amp;tab=overview">Translate Stats</a> ';
	$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations_settings">Translation settings</a></p>';
	$hub_cfg = seo_monitor_hub_settings_load();
	$content .= '<hr class="my-4">';
	$content .= '<div class="p-3 rounded border bg-white">';
	$content .= '<h6 class="font-weight-bold text-dark mb-3">Listing / hub pages</h6>';
	$content .= '<form method="post">';
	$content .= '<input type="hidden" name="seo_monitor_hub_save" value="1">';
	$content .= '<div class="form-row align-items-end">';
	$content .= '<div class="form-group col-md-5 mb-2 mb-md-0">';
	$content .= '<label class="font-weight-bold text-dark small d-block mb-1" for="hub_page_slugs">URL slugs</label>';
	$content .= '<span class="d-block small text-muted mb-1">Applies only when <code>pages.module</code> is <code>pages</code>. Each token must match that row&rsquo;s <code>pages.url</code> slug (case-insensitive, slashes trimmed). List tokens separated by comma or space.</span>';
	$content .= '<input type="text" class="form-control" name="hub_page_slugs" id="hub_page_slugs" value="' . htmlspecialchars(implode(', ', $hub_cfg['page_slugs']), ENT_QUOTES, 'UTF-8') . '">';
	$content .= '</div>';
	$content .= '<div class="form-group col-md-4 mb-2 mb-md-0">';
	$content .= '<label class="font-weight-bold text-dark small d-block mb-1" for="hub_page_ids_extra">Extra page IDs</label>';
	$content .= '<span class="d-block small text-muted mb-1">Always treat these <code>pages.id</code> values as hubs (comma or space). Use for odd listing rows that are not caught by URL slugs or module rules.</span>';
	$content .= '<input type="text" class="form-control" name="hub_page_ids_extra" id="hub_page_ids_extra" value="' . htmlspecialchars(implode(', ', $hub_cfg['page_ids_extra']), ENT_QUOTES, 'UTF-8') . '" placeholder="3, 12">';
	$content .= '</div>';
	$content .= '<div class="form-group col-md-3 mb-0">';
	$content .= '<button type="submit" class="btn btn-primary btn-block">Save rules</button>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '<div class="form-group mb-0 mt-2 pt-2 border-top">';
	$content .= '<div class="form-check">';
	$content .= '<input type="checkbox" class="form-check-input" name="hub_blog_listing" id="hub_blog_listing" value="1"' . (!empty($hub_cfg['blog_listing_module']) ? ' checked' : '') . '>';
	$content .= '<label class="form-check-label text-dark small font-weight-bold" for="hub_blog_listing">Blog listing (<code>pages.module = blog</code>)</label>';
	$content .= '</div>';
	$content .= '</div>';
	$content .= '</form>';
	$content .= '</div>';
	$content .= '<script>
(function(){
	function fallbackColor(pct){
		if (pct === null || pct === undefined) return "text-muted";
		if (pct < 50) return "text-danger";
		if (pct <= 80) return "text-warning";
		return "text-success";
	}
	function applySlot(el, data){
		if (!data || !data.ok) return;
		var v = el.querySelector(".seo-mon-score-value");
		var hint = el.querySelector(".seo-mon-score-hint");
		if (!v) return;
		v.className = "tstats-metric-value " + (data.color_class || fallbackColor(data.pct));
		v.textContent = (data.pct_display != null && data.pct_display !== "") ? data.pct_display : "—";
		if (data.good != null && data.relevant != null) {
			el.setAttribute("title", data.good + " / " + data.relevant + " locale cells pass all checks");
		}
		if (hint) {
			hint.textContent = "";
			if (data.source === "queued" && data.message) hint.textContent = data.message;
			else if (data.source === "pending" && data.message) hint.textContent = data.message;
			else if (data.source === "cache" && data.computed_at) hint.textContent = "Cached · " + data.computed_at;
		}
	}
	function load(ent, refresh, el){
		var v = el.querySelector(".seo-mon-score-value");
		if (v && refresh) v.textContent = "…";
		var url = "/admin.php?m=seo_monitor&u=ajax_entity&entity=" + encodeURIComponent(ent) + (refresh ? "&refresh=1" : "");
		fetch(url, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
			.then(function(r){ return r.json(); })
			.then(function(d){ applySlot(el, d); })
			.catch(function(){ if (v) v.textContent = "?"; });
	}
	document.querySelectorAll(".seo-mon-entity-score").forEach(function(el){
		var ent = el.getAttribute("data-entity");
		if (!ent) return;
		load(ent, false, el);
		var btn = el.querySelector(".seo-mon-refresh");
		if (btn) btn.addEventListener("click", function(e){ e.preventDefault(); e.stopPropagation(); load(ent, true, el); });
	});
	var rebuildAlert = document.getElementById("seo-mon-rebuild-alert");
	var rebuildBar = document.getElementById("seo-mon-rebuild-bar");
	var rebuildText = document.getElementById("seo-mon-rebuild-text");
	var recalcBtn = document.getElementById("seo-mon-recalc-all");
	function pollRebuildJob(jobId){
		if (!jobId) return;
		fetch("/admin.php?m=seo_monitor&u=ajax_job_status&job_id=" + encodeURIComponent(jobId), { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
			.then(function(r){ return r.json(); })
			.then(function(d){
				if (!d || !d.ok || !d.job) return;
				var t = d.job;
				var p = t.percent != null ? parseInt(t.percent, 10) : 0;
				if (isNaN(p) || p < 0) p = 0;
				if (p > 100) p = 100;
				if (rebuildBar) {
					rebuildBar.style.width = p + "%";
					rebuildBar.setAttribute("aria-valuenow", p);
				}
				if (rebuildText) {
					var msg = t.message || "";
					rebuildText.textContent = (p ? (p + "% — ") : "") + (msg || "Working…");
				}
				if (t.status === "done" || t.status === "failed") {
					if (rebuildAlert && t.status === "done") {
						rebuildAlert.className = rebuildAlert.className.replace(/alert-secondary|alert-danger/g, "alert-success");
					} else if (rebuildAlert && t.status === "failed") {
						rebuildAlert.className = rebuildAlert.className.replace(/alert-secondary|alert-success/g, "alert-danger");
					}
					if (t.status === "done") {
						document.querySelectorAll(".seo-mon-entity-score").forEach(function(el){
							var ent = el.getAttribute("data-entity");
							if (ent) load(ent, false, el);
						});
					}
					if (recalcBtn) recalcBtn.disabled = false;
					return;
				}
				setTimeout(function(){ pollRebuildJob(jobId); }, 3000);
			})
			.catch(function(){
				if (rebuildText) rebuildText.textContent = "Task status: request failed.";
				if (recalcBtn) recalcBtn.disabled = false;
			});
	}
	if (recalcBtn) recalcBtn.addEventListener("click", function(){
		recalcBtn.disabled = true;
		fetch("/admin.php?m=seo_monitor&u=ajax_enqueue_rebuild_overview", { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
			.then(function(r){ return r.json(); })
			.then(function(d){
				if (!d || !d.ok || !d.job_id) {
					window.alert(d && d.message ? d.message : "Could not queue rebuild");
					recalcBtn.disabled = false;
					return;
				}
				if (rebuildAlert) {
					rebuildAlert.classList.remove("d-none");
					rebuildAlert.setAttribute("data-job-id", d.job_id);
					rebuildAlert.className = rebuildAlert.className.replace(/alert-success|alert-danger/g, "alert-secondary");
					if (rebuildAlert.className.indexOf("alert-secondary") < 0) rebuildAlert.className += " alert-secondary";
				}
				if (rebuildBar) rebuildBar.style.width = "0%";
				if (rebuildText) rebuildText.textContent = "Queued…";
				pollRebuildJob(d.job_id);
			})
			.catch(function(){
				window.alert("Request failed");
				recalcBtn.disabled = false;
			});
	});
	(function initRebuildResume(){
		if (!rebuildAlert) return;
		var jid = parseInt(rebuildAlert.getAttribute("data-job-id"), 10) || 0;
		if (!jid) return;
		rebuildAlert.classList.remove("d-none");
		pollRebuildJob(jid);
	})();
})();
</script>';
	$content .= '</div></div>';
}

// --- Cluster detail
elseif ($seo_u === 'cluster') {
	$page_name = 'SEO Monitor: cluster';
	if (!isset($entity_map[$entity_cur]) || $entity_id_cur <= 0) {
		$content = '<div class="alert alert-warning">Open a cluster from the list (need <code>entity</code> and <code>id</code>). <a href="/admin.php?m=seo_monitor">Overview</a></div>';
	} else {
	$info = $entity_map[$entity_cur];
	$main = seo_monitor_fetch_main_row($entity_cur, $entity_id_cur, $info);
	if (!$main) {
		$content = '<div class="alert alert-danger">Record not found.</div>';
	} else {
		if (seo_monitor_normalize_canonical_main_meta($entity_cur, $entity_id_cur)) {
			$main = seo_monitor_fetch_main_row($entity_cur, $entity_id_cur, $info);
			if (!$main) {
				$content = '<div class="alert alert-danger">Record not found.</div>';
				$main = null;
			}
		}
	}
	if ($main) {
		$i18n_by = array();
		if (!empty($cluster_lang_ids)) {
			$batch = seo_monitor_batch_i18n_rows($entity_cur, array($entity_id_cur), $cluster_lang_ids);
			$i18n_by = isset($batch[$entity_id_cur]) ? $batch[$entity_id_cur] : array();
		}
		$display_title = seo_monitor_display_title((string)($main['title'] ?? ''), (string)($main['name'] ?? ''));
		$title_safe = $display_title !== '' ? $display_title : ('#' . $entity_id_cur);

		$content = '<div class="card"><div class="card-body">';
		$content .= '<p class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_monitor&u=list&entity=' . htmlspecialchars(rawurlencode($entity_cur), ENT_QUOTES, 'UTF-8') . '">&larr; Back to list</a></p>';
		$content .= '<h5 class="mb-1">' . htmlspecialchars($info['label']) . ': ' . htmlspecialchars($title_safe) . '</h5>';
		$content .= '<p class="text-muted small">ID <strong>' . $entity_id_cur . '</strong> • slug <code>' . htmlspecialchars((string)($main['url'] ?? ''), ENT_QUOTES, 'UTF-8') . '</code></p>';
		$cluster_val_excluded = seo_monitor_row_is_excluded_from_validation($entity_cur, $entity_id_cur);
		if ($cluster_val_excluded) {
			$content .= '<div class="alert alert-info small mb-3">This record is <strong>excluded from SEO validation</strong> (overview score and list issue counts). Technical columns below still reflect content; use the list + <strong>Include in validation</strong> when it should participate in scoring.</div>';
		}

		$export_meta = '/admin.php?m=seo_monitor&u=export&entity=' . rawurlencode($entity_cur) . '&id=' . $entity_id_cur . '&mode=meta';
		$export_full = '/admin.php?m=seo_monitor&u=export&entity=' . rawurlencode($entity_cur) . '&id=' . $entity_id_cur . '&mode=full';
		$export_report = '/admin.php?m=seo_monitor&u=export&entity=' . rawurlencode($entity_cur) . '&id=' . $entity_id_cur . '&mode=report';
		$content .= '<div class="d-flex flex-wrap gap-2 mb-2">';
		$content .= '<a class="btn btn-outline-primary btn-sm" href="' . htmlspecialchars($export_meta, ENT_QUOTES, 'UTF-8') . '">Download JSON (meta)</a>';
		$content .= '<a class="btn btn-primary btn-sm" href="' . htmlspecialchars($export_full, ENT_QUOTES, 'UTF-8') . '">Download JSON (full)</a>';
		$content .= '<a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($export_report, ENT_QUOTES, 'UTF-8') . '">Download JSON (report)</a>';
		$content .= '</div>';

		$content .= '<h6 class="mt-4">Locales</h6>';
		$content .= '<div class="table-responsive"><table class="table table-sm table-bordered align-middle">';
		$content .= '<thead><tr><th>Lang</th><th>ID</th><th>Status</th><th>Title len</th><th title="H1 count on full page (content + template)">H1 page</th><th>Img no alt</th><th>Issues</th><th>Source</th></tr></thead><tbody>';
		foreach ($cluster_langs as $lm) {
			$lid = (int)$lm['id'];
			$i18n = isset($i18n_by[$lid]) ? $i18n_by[$lid] : null;
			$loc = seo_monitor_locale_payload($entity_cur, $entity_id_cur, $lm, $main, $i18n, $source_lang_id, false);
			$issues = $cluster_val_excluded ? array() : seo_monitor_analyze_locale($loc);
			$metrics = seo_monitor_locale_html_metrics($loc);
			$tl = seo_monitor_display_title($loc['title'] ?? '', $loc['name'] ?? '');
			$tlen = $tl !== '' ? seo_monitor_strlen_utf8($tl) : 0;
			$badges = '';
			if ($cluster_val_excluded) {
				$badges = '<span class="badge bg-secondary">Excluded from validation</span>';
			} elseif (empty($issues)) {
				$badges = '<span class="badge bg-success">OK</span>';
			} else {
				foreach ($issues as $is) {
					$badges .= '<span class="badge bg-warning text-dark me-1 mb-1">' . htmlspecialchars(seo_monitor_issue_label($is['code'])) . '</span> ';
				}
			}
			$h1_cell = '—';
			$h1_cls = 'text-muted';
			if ($metrics['has_html']) {
				$h1n = (int)$metrics['h1_count'];
				$h1_cell = (string)$h1n;
				$h1_cls = ($h1n === 1) ? 'text-success' : 'text-warning fw-bold';
			}
			$img_cell = '—';
			$img_cls = 'text-muted';
			if ($metrics['has_html']) {
				$imn = (int)$metrics['img_missing_alt'];
				$img_cell = (string)$imn;
				$img_cls = ($imn === 0) ? 'text-success' : 'text-warning fw-bold';
			}
			$content .= '<tr>';
			$content .= '<td><strong>' . htmlspecialchars((string)$lm['name']) . '</strong><br><span class="small text-muted">' . htmlspecialchars((string)$lm['url']) . '</span></td>';
			$content .= '<td>' . $lid . '</td>';
			$content .= '<td><span class="badge bg-secondary">' . htmlspecialchars((string)($loc['status'] ?? '')) . '</span></td>';
			$content .= '<td>' . (int)$tlen . '</td>';
			$content .= '<td class="small ' . htmlspecialchars($h1_cls, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($h1_cell) . '</td>';
			$content .= '<td class="small ' . htmlspecialchars($img_cls, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($img_cell) . '</td>';
			$content .= '<td>' . $badges . '</td>';
			$content .= '<td class="small">' . htmlspecialchars((string)($loc['source'] ?? '')) . '</td>';
			$content .= '</tr>';
		}
		$content .= '</tbody></table></div>';

		$content .= '<h6 class="mt-4">Import cluster</h6>';
		$content .= '<form method="post" action="/admin.php?m=seo_monitor&u=cluster&entity=' . htmlspecialchars(rawurlencode($entity_cur), ENT_QUOTES, 'UTF-8') . '&id=' . $entity_id_cur . '" enctype="multipart/form-data" class="border rounded p-3 bg-light">';
		$content .= '<input type="hidden" name="seo_monitor_import" value="1" />';
		$content .= '<input type="hidden" name="u" value="cluster" />';
		$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="id" value="' . $entity_id_cur . '" />';
		$content .= '<div class="mb-3"><label class="form-label">JSON file</label><input type="file" name="import_file" class="form-control form-control-sm" accept="application/json,.json" required /></div>';
		$content .= '<div class="mb-3"><label class="form-label">Apply mode</label><select name="apply_mode" class="form-select form-select-sm">';
		$content .= '<option value="meta">Meta only (title, description, name, URL — no HTML)</option>';
		$content .= '<option value="full" selected>Full (include HTML body — required for English/canonical page text)</option>';
		$content .= '</select>';
		$content .= '<p class="small text-muted mb-0 mt-1">The live site reads English HTML from <code>content_i18n</code> when present. Use <strong>Full</strong> when importing a cluster JSON with <code>mode: full</code>.</p></div>';
		$content .= '<div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="dry_run" value="1" id="seoDry" /><label class="form-check-label" for="seoDry">Dry run (validate only, no DB writes)</label></div>';
		$content .= '<button type="submit" class="btn btn-primary btn-sm">Import</button>';
		$content .= '</form>';

		$content .= '</div></div>';
	}
	}
}

// --- List
elseif ($seo_u === 'list' && isset($entity_map[$entity_cur])) {
	$page_name = 'SEO Monitor: list';
	$info = $entity_map[$entity_cur];
	$table = $info['table'];
	$filter = isset($get['filter']) ? (string)$get['filter'] : 'all';
	$allowed_filters = array_merge(array('all'), seo_monitor_issue_codes_for_filter(), array('any_issue'));
	if (!in_array($filter, $allowed_filters, true)) {
		$filter = 'all';
	}
	$n = isset($get['n']) ? (int)$get['n'] : 1;
	if ($n < 1) {
		$n = 1;
	}
	$allowed_per_page = array(50, 100, 200);
	$per_page = isset($get['per_page']) ? (int)$get['per_page'] : 50;
	if (!in_array($per_page, $allowed_per_page, true)) {
		$per_page = 50;
	}
	$sort_by = isset($get['sort']) ? (string)$get['sort'] : 'id';
	$dir = strtolower(isset($get['dir']) ? (string)$get['dir'] : 'desc');
	$q = trim(isset($get['q']) ? (string)$get['q'] : '');
	if (!in_array($dir, array('asc', 'desc'), true)) {
		$dir = 'desc';
	}
	if (!in_array($sort_by, array('id', 'title'), true)) {
		$sort_by = 'id';
	}

	$display_where = seo_monitor_display_where($table);
	$search_where = function_exists('seo_monitor_list_search_where')
		? seo_monitor_list_search_where($entity_cur, $q, $info)
		: '';

	$base_url = '/admin.php?m=seo_monitor&u=list&entity=' . rawurlencode($entity_cur)
		. '&filter=' . rawurlencode($filter)
		. '&q=' . rawurlencode($q)
		. '&sort=' . rawurlencode($sort_by)
		. '&dir=' . rawurlencode($dir)
		. '&per_page=' . (int)$per_page;

	$row_total = mysql_select("
		SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $display_where . $search_where, 'row');
	$total = $row_total && isset($row_total['c']) ? (int)$row_total['c'] : 0;

	$max_page = $per_page > 0 ? (int)max(1, (int)ceil((float)$total / (float)$per_page)) : 1;
	if ($n > $max_page) {
		$n = $max_page;
	}
	$offset = ($n - 1) * $per_page;

	$order_sql = function_exists('seo_monitor_list_order_sql')
		? seo_monitor_list_order_sql($sort_by, $dir, $info)
		: (' ORDER BY id ' . ($dir === 'asc' ? 'ASC' : 'DESC') . ' ');

	$sql_rows = "
		SELECT " . seo_monitor_main_row_select_sql($info) . "
		FROM `" . mysql_res($table) . "`
		WHERE 1 " . $display_where . $search_where . "
		" . $order_sql . "
		LIMIT " . (int)$per_page . " OFFSET " . (int)$offset . "
	";
	$rows_main = mysql_select($sql_rows, 'rows') ?: array();
	$ids = array_map(function ($r) {
		return (int)$r['id'];
	}, $rows_main);
	$i18n_batch = array();
	if ($ids !== array() && !empty($cluster_lang_ids)) {
		$i18n_batch = seo_monitor_batch_i18n_rows($entity_cur, $ids, $cluster_lang_ids);
	}

	$excluded_flip = array_flip(seo_monitor_exclusions_for_entity($entity_cur));
	$built = array();
	foreach ($rows_main as $mr) {
		$eid = (int)$mr['id'];
		if (isset($excluded_flip[$eid])) {
			$built[] = array(
				'id' => $eid,
				'title' => seo_monitor_display_title((string)($mr['title'] ?? ''), (string)($mr['name'] ?? '')),
				'url' => (string)($mr['url'] ?? ''),
				'issue_count' => 0,
				'issue_codes' => array(),
				'excluded' => true,
			);
			continue;
		}
		if (seo_monitor_normalize_canonical_main_meta($entity_cur, $eid)) {
			$fresh = seo_monitor_fetch_main_row($entity_cur, $eid, $info);
			if (is_array($fresh)) {
				$mr = $fresh;
			}
		}
		$worst = 0;
		$labels = array();
		foreach ($cluster_langs as $lm) {
			$lid = (int)$lm['id'];
			$i18n = isset($i18n_batch[$eid][$lid]) ? $i18n_batch[$eid][$lid] : null;
			$loc = seo_monitor_locale_payload($entity_cur, $eid, $lm, $mr, $i18n, $source_lang_id, false);
			foreach (seo_monitor_analyze_locale($loc) as $is) {
				$labels[$is['code']] = true;
				$worst++;
			}
		}
		$issue_codes = array_keys($labels);
		if ($filter === 'all') {
			$built[] = array(
				'id' => $eid,
				'title' => seo_monitor_display_title((string)($mr['title'] ?? ''), (string)($mr['name'] ?? '')),
				'url' => (string)($mr['url'] ?? ''),
				'issue_count' => $worst,
				'issue_codes' => $issue_codes,
			);
			continue;
		}
		if ($filter === 'any_issue') {
			$ok = ($worst > 0);
		} else {
			$ok = isset($labels[$filter]);
		}
		if ($ok) {
			$built[] = array(
				'id' => $eid,
				'title' => seo_monitor_display_title((string)($mr['title'] ?? ''), (string)($mr['name'] ?? '')),
				'url' => (string)($mr['url'] ?? ''),
				'issue_count' => $worst,
				'issue_codes' => $issue_codes,
			);
		}
	}

	$content = '<div class="card"><div class="card-body">';
	$content .= '<p class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=seo_monitor">&larr; Overview</a></p>';
	$content .= '<h5 class="mb-1">' . htmlspecialchars($info['label']) . '</h5>';

	$filter_labels = function_exists('seo_monitor_issue_filter_labels')
		? seo_monitor_issue_filter_labels($entity_cur)
		: array('all' => 'All', 'any_issue' => 'Any issue');
	$filters_nav = '';
	foreach ($filter_labels as $fk => $lab) {
		$u = $base_url . '&filter=' . rawurlencode($fk) . '&n=1';
		$op = ($filter === $fk) ? ' style="font-weight:bold"' : '';
		$filters_nav .= ' <a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($u, ENT_QUOTES, 'UTF-8') . '"' . $op . '>' . $lab . '</a>';
	}
	$content .= '<div class="mb-3">' . $filters_nav . '</div>';

	$content .= '<form method="get" action="/admin.php" class="d-flex flex-wrap align-items-center gap-2 mb-3">';
	$content .= '<input type="hidden" name="m" value="seo_monitor" />';
	$content .= '<input type="hidden" name="u" value="list" />';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="filter" value="' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="sort" value="' . htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="dir" value="' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="n" value="1" />';
	$content .= '<label class="mb-0">';
	$content .= '<span class="text-muted small d-block">Search by ID or title</span>';
	$content .= '<input type="text" name="q" value="' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '" class="form-control form-control-sm" placeholder="125 or aviator signals" />';
	$content .= '</label>';
	$content .= '<span class="text-muted small">Per page:</span>';
	foreach ($allowed_per_page as $pp) {
		$rid = 'seo_per_' . $pp;
		$chk = ($per_page === $pp) ? ' checked' : '';
		$content .= '<div class="form-check form-check-inline mb-0">';
		$content .= '<input class="form-check-input" type="radio" name="per_page" id="' . htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') . '" value="' . (int)$pp . '"' . $chk . ' onchange="this.form.submit()" />';
		$content .= '<label class="form-check-label" for="' . htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') . '">' . (int)$pp . '</label>';
		$content .= '</div>';
	}
	$content .= '<button type="submit" class="btn btn-primary btn-sm">Apply</button>';
	$content .= '</form>';

	$sort_url = function ($sb, $nd) use ($base_url, $n) {
		return $base_url . '&sort=' . rawurlencode($sb) . '&dir=' . rawurlencode($nd) . '&n=' . (int)$n;
	};

	$content .= '<form method="post" action="/admin.php?m=seo_monitor" class="mb-2">';
	$content .= '<input type="hidden" name="seo_monitor_validation_bulk" value="1" />';
	$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="return_filter" value="' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="return_n" value="' . (int)$n . '" />';
	$content .= '<input type="hidden" name="return_per_page" value="' . (int)$per_page . '" />';
	$content .= '<input type="hidden" name="return_sort" value="' . htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="return_dir" value="' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<input type="hidden" name="q" value="' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '" />';
	$content .= '<div class="d-flex flex-wrap align-items-center gap-2">';
	$content .= '<span class="small text-muted me-1">Validation scope:</span>';
	$content .= '<button type="submit" name="validation_action" value="include" class="btn btn-sm btn-success">Include in validation</button>';
	$content .= '<button type="submit" name="validation_action" value="exclude" class="btn btn-sm btn-warning text-dark">Exclude from validation</button>';
	$content .= '</div>';

	$content .= '<div class="table-responsive mt-2"><table class="table table-sm align-middle">';
	$content .= '<thead><tr>';
	$content .= '<th style="width:2.5rem;"><input type="checkbox" class="form-check-input m-0" id="seo-mon-val-sel-all" title="Select all on this page" /></th>';
	$content .= '<th><a href="' . htmlspecialchars($sort_url('id', $dir === 'asc' ? 'desc' : 'asc'), ENT_QUOTES, 'UTF-8') . '">ID</a></th>';
	$content .= '<th><a href="' . htmlspecialchars($sort_url('title', $dir === 'asc' ? 'desc' : 'asc'), ENT_QUOTES, 'UTF-8') . '">Title</a></th>';
	$content .= '<th>URL</th><th>Issues</th><th class="text-end">Actions</th></tr></thead><tbody>';
	if (empty($built)) {
		$content .= '<tr><td colspan="6" class="text-muted">No rows in this view.</td></tr>';
	} else {
		foreach ($built as $br) {
			$cluster_href = '/admin.php?m=seo_monitor&u=cluster&entity=' . rawurlencode($entity_cur) . '&id=' . (int)$br['id'];
			$is_ex = !empty($br['excluded']);
			$row_cls = $is_ex ? ' class="seo-mon-row-excluded table-secondary"' : '';
			$hint = '';
			if ($is_ex) {
				$hint = 'Not scored (section/menu-only or manually excluded)';
			} elseif (!empty($br['issue_codes'])) {
				$parts = array();
				foreach ($br['issue_codes'] as $ic) {
					$parts[] = seo_monitor_issue_label($ic);
				}
				$hint = implode(', ', $parts);
			} else {
				$hint = '—';
			}
			$content .= '<tr' . $row_cls . '>';
			$content .= '<td><input type="checkbox" class="form-check-input seo-mon-val-cb m-0" name="validation_ids[]" value="' . (int)$br['id'] . '" /></td>';
			$content .= '<td>' . (int)$br['id'] . '</td>';
			$content .= '<td>' . htmlspecialchars((string)$br['title']);
			if ($is_ex) {
				$content .= ' <span class="badge bg-secondary ms-1">Excluded</span>';
			}
			$content .= '</td>';
			$content .= '<td class="small text-muted">' . htmlspecialchars((string)$br['url']) . '</td>';
			$content .= '<td class="small seo-mon-list-issues-cell">';
			$content .= '<div class="seo-mon-list-issues" data-entity="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" data-row-id="' . (int)$br['id'] . '">';
			if ($is_ex) {
				$content .= '<span class="seo-mon-list-issues-body"><span class="text-muted">—</span><br><span class="text-muted small">' . htmlspecialchars($hint) . '</span></span>';
			} else {
				$content .= '<span class="seo-mon-list-issues-body">';
				$content .= ($br['issue_count'] > 0 ? '<span class="text-warning fw-bold">' . (int)$br['issue_count'] . '</span>' : '<span class="text-success">0</span>');
				$content .= '<br><span class="text-muted seo-mon-list-issues-hint">' . htmlspecialchars($hint) . '</span>';
				$content .= '</span>';
			}
			$content .= '</div></td>';
			$content .= '<td class="text-end text-nowrap">';
			$content .= '<a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($cluster_href, ENT_QUOTES, 'UTF-8') . '">Cluster</a> ';
			$content .= '<button type="button" class="btn btn-sm btn-outline-secondary seo-mon-row-check" data-entity="' . htmlspecialchars($entity_cur, ENT_QUOTES, 'UTF-8') . '" data-row-id="' . (int)$br['id'] . '" title="Re-check">Check</button>';
			$content .= '</td>';
			$content .= '</tr>';
		}
	}
	$content .= '</tbody></table></div>';
	$content .= '</form>';
	$content .= '<script>(function(){
	var sel=document.getElementById("seo-mon-val-sel-all");if(sel)sel.addEventListener("change",function(){document.querySelectorAll(".seo-mon-val-cb").forEach(function(c){c.checked=sel.checked;});});
	function esc(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;}
	document.querySelectorAll(".seo-mon-row-check").forEach(function(btn){
		btn.addEventListener("click",function(){
			var ent=btn.getAttribute("data-entity"),id=btn.getAttribute("data-row-id");
			var row=btn.closest("tr"),wrap=row?row.querySelector(".seo-mon-list-issues"):null,bodyEl=wrap?wrap.querySelector(".seo-mon-list-issues-body"):null;
			if(!wrap||!bodyEl)return;
			btn.disabled=true;
			fetch("/admin.php?m=seo_monitor&u=ajax_list_row_check&entity="+encodeURIComponent(ent)+"&id="+encodeURIComponent(id),{credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}})
				.then(function(r){return r.json();})
				.then(function(d){
					btn.disabled=false;
					if(!d||!d.ok){bodyEl.innerHTML="<span class=\\"text-danger small\\">"+esc(d&&d.message?d.message:"?")+"</span>";return;}
					if(d.excluded){bodyEl.innerHTML="<span class=\\"text-muted\\">—</span><br><span class=\\"text-muted small\\">Not scored (excluded)</span>";return;}
					if(d.all_ok){bodyEl.innerHTML="<span class=\\"seo-mon-list-ok text-success\\" title=\\"No issues\\" style=\\"font-size:1.35rem;line-height:1;font-weight:600;\\">✓</span>";return;}
					var hint=(d.issue_labels&&d.issue_labels.length)?d.issue_labels.join(", "):"—";
					bodyEl.innerHTML="<span class=\\"text-warning fw-bold\\">"+(parseInt(d.issue_count,10)||0)+"</span><br><span class=\\"text-muted seo-mon-list-issues-hint\\">"+esc(hint)+"</span>";
				})
				.catch(function(){btn.disabled=false;bodyEl.innerHTML="<span class=\\"text-danger small\\">Request failed</span>";});
		});
	});
})();</script>';

	// Pagination (same pattern as translate_stats drilldown)
	if ($total > 0) {
		$count_max = 7;
		$per = (int)$per_page;
		$count = $per > 0 ? (int)ceil((float)$total / (float)$per) : 1;
		if ($count < 1) {
			$count = 1;
		}
		$list = array();
		if ($per > 0 && $per < $total) {
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
				} elseif ($n > ($s = $count - $count_max + 2 + 1)) {
					$list[] = array(1, 1);
					$list[] = array(ceil(($s + 1) / 2), 0);
					for ($i = $s; $i <= $count; $i++) {
						$list[] = array($i, $i);
					}
				} else {
					$s = $n - ceil(($count_max - 4 - 1) / 2);
					$e = $n + floor(($count_max - 4 - 1) / 2);
					$list[] = array(1, 1);
					$list[] = array((ceil(($s + 1) / 2)), 0);
					for ($i = $s; $i <= $e; $i++) {
						$list[] = array($i, $i);
					}
					$list[] = array(ceil(($count + $e) / 2), 0);
					$list[] = array($count, $count);
				}
			}
		} else {
			$list[] = array(1, 1);
		}
		$content .= '<div class="pagination pagination-bottom mt-3"><nav aria-label="Pagination"><ul class="pagination pagination-sm pagination-rounded mb-0">';
		foreach ($list as $v) {
			$page = (int)($v[0] ?? 1);
			$is_ellipsis = ((int)($v[1] ?? 1) === 0);
			if ($is_ellipsis) {
				$content .= '<li class="page-item"><span class="page-link">…</span></li>';
				continue;
			}
			$link = $base_url . '&n=' . $page;
			if ($page === (int)$n) {
				$content .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
			} else {
				$content .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . $page . '</a></li>';
			}
		}
		$content .= '</ul></nav></div>';
	}

	$content .= '</div></div>';
}

else {
	$content = '<div class="alert alert-warning">Unknown view. <a href="/admin.php?m=seo_monitor">Overview</a></div>';
}
