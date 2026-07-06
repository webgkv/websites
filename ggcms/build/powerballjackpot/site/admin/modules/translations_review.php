<?php
/**
 * Translations review: cluster-first workspace.
 */
$page_name = 'Translations: review';

$table_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table content_i18n not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

require_once ROOT_DIR . 'admin/modules/_i18n.php';
require_once ROOT_DIR . 'functions/translation_cluster.php';
require_once ROOT_DIR . 'functions/translation_hub.php';
translation_cluster_ensure_tables();

$get = array_merge(array(
	'u' => '',
	'n' => 1,
	'status' => 'all',
	'entity' => 'all',
	'q' => '',
	'sort' => 'updated_at',
	'dir' => 'desc',
	'ce' => '',
	'cid' => 0,
	'review_ok' => '',
	'review_err' => '',
	'review_err_msg' => '',
	'publish_ok' => '',
	'manual_ok' => '',
	'manual_err' => '',
	'manual_err_msg' => '',
	'manual_jobs' => '',
), (array)$get);

$n = max(1, (int)($get['n'] ?? 1));
$perPage = 40;
$status = (string)($get['status'] ?? 'all');
$entityFilter = (string)($get['entity'] ?? 'all');
$q = trim((string)($get['q'] ?? ''));
$sort = (string)($get['sort'] ?? 'updated_at');
$dir = strtolower((string)($get['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$clusterEntity = trim((string)($get['ce'] ?? ''));
$clusterId = (int)($get['cid'] ?? 0);
$detailMode = ($clusterEntity !== '' && $clusterId > 0);

// JSON: live cluster pipeline + queue (poll from "Online monitor" card; same pattern as seo_monitor job progress).
if (isset($get['u']) && (string)$get['u'] === 'cluster_live_poll') {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	if (!function_exists('access') || !translation_hub_access()) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$ent = isset($get['ce']) ? trim((string)$get['ce']) : '';
	$eid = isset($get['cid']) ? (int)$get['cid'] : 0;
	if ($ent === '' || $eid <= 0) {
		echo json_encode(array('ok' => false, 'message' => 'ce and cid required'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	require_once ROOT_DIR . 'functions/site_telemetry.php';
	$cluster = site_telemetry_cluster_compact_summary($ent, $eid);
	if (!is_array($cluster) || !empty($cluster['missing'])) {
		echo json_encode(array('ok' => false, 'message' => 'cluster not found'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$ready = (int)($cluster['ready_locales'] ?? 0);
	$tot = max(0, (int)($cluster['total_locales'] ?? 0));
	$pipeline_pct = $tot > 0 ? (int)min(100, max(0, (int)round(100 * $ready / $tot))) : 0;
	$lc = isset($cluster['locales_compact']) && is_array($cluster['locales_compact']) ? $cluster['locales_compact'] : array();
	$okn = 0;
	$tn = count($lc);
	foreach ($lc as $row) {
		if (!is_array($row)) {
			continue;
		}
		if (!empty($row['ok'])) {
			$okn++;
		}
	}
	$locale_ok_pct = $tn > 0 ? (int)min(100, max(0, (int)round(100 * $okn / $tn))) : null;
	$pending_raw = site_telemetry_cluster_fetch_pending_jobs($ent, $eid);
	$pending_out = array();
	foreach ($pending_raw as $r) {
		$sched = isset($r['scheduled_at']) ? (string)$r['scheduled_at'] : '';
		$pending_out[] = array(
			'id' => (int)($r['id'] ?? 0),
			'action' => isset($r['action']) ? (string)$r['action'] : '',
			'scheduled_at' => $sched,
			'scheduled_future' => ($sched !== '' && strtotime($sched) > time()) ? 1 : 0,
		);
		if (count($pending_out) >= 20) {
			break;
		}
	}
	$running_raw = site_telemetry_cluster_fetch_running_jobs($ent, $eid);
	$running_out = array();
	foreach ($running_raw as $r) {
		$running_out[] = array(
			'id' => (int)($r['id'] ?? 0),
			'action' => isset($r['action']) ? (string)$r['action'] : '',
			'message' => isset($r['message']) ? mb_substr((string)$r['message'], 0, 500, 'UTF-8') : '',
			'started_at' => isset($r['started_at']) ? (string)$r['started_at'] : '',
			'running_seconds' => isset($r['running_seconds']) ? (int)$r['running_seconds'] : null,
		);
		if (count($running_out) >= 10) {
			break;
		}
	}
	$qh = site_telemetry_translation_queue_health();
	$jobs_pending_global = 0;
	$jobs_running_global = 0;
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
		$jobs_pending_global = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='pending'", 'string');
		$jobs_running_global = (int)mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE module='translations' AND status='running'", 'string');
	}
	$activity = '';
	if ($running_out !== array()) {
		$activity = trim((string)($running_out[0]['message'] ?? ''));
		if ($activity === '') {
			$activity = (string)($running_out[0]['action'] ?? '') . ' #' . (int)($running_out[0]['id'] ?? 0);
		}
	} elseif ($pending_out !== array()) {
		$activity = 'Queued: ' . (string)($pending_out[0]['action'] ?? '') . ' #' . (int)($pending_out[0]['id'] ?? 0);
	} else {
		$activity = 'Idle (no jobs for this cluster in the queue)';
	}
	echo json_encode(array(
		'ok' => true,
		'cluster' => $cluster,
		'pipeline_pct' => $pipeline_pct,
		'locale_ok_pct' => $locale_ok_pct,
		'locale_ok' => $okn,
		'locale_total' => $tn,
		'pending' => $pending_out,
		'running' => $running_out,
		'pending_count' => count($pending_raw),
		'running_count' => count($running_raw),
		'activity' => $activity,
		'server_now' => isset($qh['server_now']) ? (string)$qh['server_now'] : '',
		'queue' => array(
			'pending_translations_globally' => $jobs_pending_global,
			'running_translations_globally' => $jobs_running_global,
			'stale_running_count' => isset($qh['stale_running_count']) ? (int)$qh['stale_running_count'] : 0,
		),
	), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	exit;
}

// Standalone legacy URL → hub (poll/live JSON handlers above already exited).
if (!defined('TRANSLATIONS_HUB')) {
	$q = $_GET;
	$q['m'] = 'translations';
	$q['tab'] = 'review';
	header('Location: /admin.php?' . http_build_query($q));
	exit;
}

$allowedStatus = array('all', 'new', 'translating', 'needs_review', 'blocked', 'ready_to_publish', 'published', 'legacy_unnormalized');
if (!in_array($status, $allowedStatus, true)) {
	$status = 'all';
}
$allowedEntity = array('all', 'pages', 'guides', 'games', 'casino_articles', 'blog', 'authors');
if (!in_array($entityFilter, $allowedEntity, true)) {
	$entityFilter = 'all';
}
if (!in_array($sort, array('updated_at', 'entity_id', 'title', 'status', 'blockers'), true)) {
	$sort = 'updated_at';
}

if (($get['u'] ?? '') === 'cluster_publish' && $clusterEntity !== '' && $clusterId > 0) {
	$pub_ok = translation_cluster_publish_all_content_i18n($clusterEntity, $clusterId);
	$params = $get;
	unset($params['u'], $params['ce'], $params['cid']);
	$params['m'] = 'translations';
	$params['tab'] = 'review';
	$params['publish_ok'] = $pub_ok ? '1' : '0';
	header('Location: /admin.php?' . http_build_query($params));
	exit;
}
if (($get['u'] ?? '') === 'cluster_review' && $clusterEntity !== '' && $clusterId > 0) {
	$mr = translation_cluster_mark_human_reviewed($clusterEntity, $clusterId);
	$params = $get;
	unset($params['u'], $params['ce'], $params['cid']);
	$params['m'] = 'translations';
	$params['tab'] = 'review';
	if (!empty($mr['ok'])) {
		$params['review_ok'] = '1';
	} else {
		$params['review_err'] = '1';
		if (!empty($mr['message'])) {
			$params['review_err_msg'] = (string)$mr['message'];
		}
	}
	header('Location: /admin.php?' . http_build_query($params));
	exit;
}
if (($get['u'] ?? '') === 'cluster_manual_approve' && $clusterEntity !== '' && $clusterId > 0) {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	$mr = translation_cluster_manual_total_approve($clusterEntity, $clusterId);
	$params = $get;
	unset($params['u']);
	$params['m'] = 'translations';
	$params['tab'] = 'review';
	if (!empty($mr['ok'])) {
		$params['manual_ok'] = '1';
		if (isset($mr['jobs_cancelled'])) {
			$params['manual_jobs'] = (string)(int)$mr['jobs_cancelled'];
		}
	} else {
		$params['manual_err'] = '1';
		if (!empty($mr['message'])) {
			$params['manual_err_msg'] = (string)$mr['message'];
		}
	}
	header('Location: /admin.php?' . http_build_query($params));
	exit;
}
if (($get['u'] ?? '') === 'locale_review' && (int)($get['id'] ?? 0) > 0) {
	$ciId = (int)$get['id'];
	mysql_fn('update', 'content_i18n', array(
		'status' => 'review',
		'updated_at' => date('Y-m-d H:i:s'),
	), " AND id=" . $ciId . " ");
	$params = $get;
	unset($params['u']);
	unset($params['id']);
	$params['m'] = 'translations';
	$params['tab'] = 'review';
	header('Location: /admin.php?' . http_build_query($params));
	exit;
}

$statusExpr = "CASE
	WHEN COALESCE(cs.cluster_status,'') <> '' THEN cs.cluster_status
	WHEN c.locale_count > 0 AND c.published_count >= c.locale_count THEN 'published'
	WHEN c.review_count > 0 THEN 'needs_review'
	ELSE 'legacy_unnormalized'
END";
$baseSql = "
	FROM (
		SELECT ci.entity,
			ci.entity_id,
			MAX(ci.updated_at) AS last_updated_at,
			COUNT(*) AS locale_count,
			COUNT(DISTINCT ci.lang_id) AS distinct_lang_count,
			SUM(ci.status='published') AS published_count,
			SUM(ci.status='review') AS review_count,
			SUM(ci.status='draft') AS draft_count
		FROM content_i18n ci
		GROUP BY ci.entity, ci.entity_id
	) c
	LEFT JOIN translation_cluster_state cs
		ON cs.entity = c.entity AND cs.entity_id = c.entity_id
";

$where = " WHERE 1 ";
if ($entityFilter !== 'all') {
	$where .= " AND c.entity='" . mysql_res($entityFilter) . "' ";
}
if ($status !== 'all') {
	$where .= " AND " . $statusExpr . "='" . mysql_res($status) . "' ";
}
if ($q !== '') {
	if (preg_match('/^\d+$/', $q)) {
		$where .= " AND c.entity_id=" . (int)$q . " ";
	} else {
		$qSql = mysql_res('%' . $q . '%');
		$where .= " AND (
			cs.search_title LIKE '" . $qSql . "'
			OR cs.search_slug LIKE '" . $qSql . "'
			OR EXISTS (
				SELECT 1
				FROM content_i18n ciq
				WHERE ciq.entity=c.entity
				  AND ciq.entity_id=c.entity_id
				  AND (
					ciq.title LIKE '" . $qSql . "'
					OR ciq.name LIKE '" . $qSql . "'
					OR ciq.url LIKE '" . $qSql . "'
				  )
			)
		) ";
	}
}

$sortSql = 'last_updated_at DESC, entity DESC, entity_id DESC';
if ($sort === 'entity_id') {
	$sortSql = 'entity_id ' . $dir . ', entity ' . $dir;
}
if ($sort === 'title') {
	$sortSql = 'search_title ' . $dir . ', last_updated_at DESC';
}
if ($sort === 'status') {
	$sortSql = 'cluster_status_sort ' . $dir . ', last_updated_at DESC';
}
if ($sort === 'blockers') {
	$sortSql = 'blocker_count_sort ' . $dir . ', last_updated_at DESC';
}
if ($sort === 'updated_at') {
	$sortSql = 'last_updated_at ' . $dir . ', entity DESC, entity_id DESC';
}

$totalRow = mysql_select("SELECT COUNT(*) AS c FROM (
	SELECT c.entity, c.entity_id
	" . $baseSql . $where . "
) z", 'row');
$total = (int)($totalRow['c'] ?? 0);
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
if ($totalPages < 1) {
	$totalPages = 1;
}
if ($n > $totalPages) {
	$n = $totalPages;
}
$offset = ($n - 1) * $perPage;

$rows = mysql_select("
	SELECT c.entity,
		c.entity_id,
		c.last_updated_at,
		c.locale_count,
		c.distinct_lang_count,
		c.published_count,
		c.review_count,
		c.draft_count,
		COALESCE(NULLIF(cs.search_title,''), '') AS search_title,
		COALESCE(NULLIF(cs.search_slug,''), '') AS search_slug,
		COALESCE(cs.ready_locales, 0) AS ready_locales,
		COALESCE(cs.total_locales, 0) AS total_locales,
		COALESCE(cs.failed_locales, 0) AS failed_locales,
		COALESCE(cs.blocker_count, 0) AS blocker_count_sort,
		COALESCE(cs.warning_count, 0) AS warning_count,
		COALESCE(cs.pipeline_stage, '') AS pipeline_stage,
		COALESCE(cs.source_lang_id, 1) AS cs_source_lang_id,
		cs.human_reviewed_at AS cs_human_reviewed_at,
		" . $statusExpr . " AS cluster_status_sort
	" . $baseSql . $where . "
	ORDER BY " . $sortSql . "
	LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
", 'rows') ?: array();

$baseParams = array(
	'm' => 'translations',
	'tab' => 'review',
	'status' => $status,
	'entity' => $entityFilter,
	'q' => $q,
	'sort' => $sort,
	'dir' => strtolower($dir),
);
$listUrl = '/admin.php?' . http_build_query($baseParams);

// Thin header strip: only when not inside hub (hub sets $page_header_extra in translations.php).
if (!defined('TRANSLATIONS_HUB') && !$detailMode) {
	$page_header_extra = '';
	$focus = translation_cluster_find_header_focus();
	if ($focus !== null) {
		$fent = (string)$focus['entity'];
		$feid = (int)$focus['entity_id'];
		$fstate = isset($focus['state']) && is_array($focus['state']) ? $focus['state'] : array();
		$ready = isset($fstate['ready_locales']) ? (int)$fstate['ready_locales'] : 0;
		$tot = isset($fstate['total_locales']) ? (int)$fstate['total_locales'] : 0;
		if ($tot <= 0) {
			$pct = 0;
		} else {
			$pct = (int)min(100, max(0, (int)round(100 * $ready / $tot)));
		}
		$cstat = isset($fstate['cluster_status']) ? (string)$fstate['cluster_status'] : '—';
		$titleHint = trim((string)($fstate['search_title'] ?? ''));
		if ($titleHint === '') {
			$titleHint = $fent . '#' . $feid;
		} else {
			$titleHint = $fent . '#' . $feid . ' — ' . $titleHint;
		}
		$statusForDetail = (string)$cstat;
		if (!in_array($statusForDetail, $allowedStatus, true)) {
			$statusForDetail = 'all';
		}
		$detailOpenParams = array(
			'm' => 'translations',
			'tab' => 'review',
			'status' => $statusForDetail,
			'entity' => $fent,
			'q' => '',
			'sort' => 'updated_at',
			'dir' => 'desc',
			'ce' => $fent,
			'cid' => $feid,
		);
		$detailOpenUrl = '/admin.php?' . http_build_query($detailOpenParams);
		$pollUrl = '/admin.php?m=translations&tab=review&u=cluster_live_poll&ce=' . rawurlencode($fent) . '&cid=' . (int)$feid;
		$page_header_extra = '<div class="tc-review-cluster-strip translations-review-header border rounded bg-white px-2 py-2 mb-2" style="border-left:3px solid #1c5bbf!important;">';
		$page_header_extra .= '<div class="d-flex align-items-center flex-wrap" style="gap:8px;">';
		$page_header_extra .= '<div class="flex-grow-1" style="min-width:140px;">';
		$page_header_extra .= '<div class="small text-truncate mb-0 tc-review-strip-title" title="' . htmlspecialchars($titleHint, ENT_QUOTES, 'UTF-8') . '">Current cluster · ' . htmlspecialchars($titleHint, ENT_QUOTES, 'UTF-8') . '</div>';
		$page_header_extra .= '<div class="progress mt-1" style="height:4px;"><div class="progress-bar" id="tc-review-header-pipeline" role="progressbar" style="width:' . (int)$pct . '%" aria-valuenow="' . (int)$pct . '" aria-valuemin="0" aria-valuemax="100"></div></div>';
		$page_header_extra .= '<div class="small tc-review-strip-hint" id="tc-review-header-hint">' . htmlspecialchars($cstat, ENT_QUOTES, 'UTF-8') . ' · ready ' . (int)$ready . '/' . (int)$tot . '</div>';
		$page_header_extra .= '</div>';
		$page_header_extra .= '<a class="btn btn-sm flex-shrink-0 btn-tc-review-open" id="tc-review-header-open" href="' . htmlspecialchars($detailOpenUrl, ENT_QUOTES, 'UTF-8') . '">Open progress</a>';
		$page_header_extra .= '</div></div>';
		$page_header_extra .= '<script>(function(){var u=' . json_encode($pollUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
		$page_header_extra .= 'function tick(){fetch(u,{credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){return r.json();}).then(function(d){';
		$page_header_extra .= 'if(!d||!d.ok)return;var pp=d.pipeline_pct!=null?parseInt(d.pipeline_pct,10):NaN;var bar=document.getElementById("tc-review-header-pipeline");';
		$page_header_extra .= 'if(bar&&!isNaN(pp)){bar.style.width=Math.min(100,Math.max(0,pp))+"%";bar.setAttribute("aria-valuenow",pp);}';
		$page_header_extra .= 'var h=document.getElementById("tc-review-header-hint");if(h&&d.cluster){var cs=d.cluster.cluster_status||"—";var rl=d.cluster.ready_locales||"0";var tl=d.cluster.total_locales||"0";h.textContent=cs+" · ready "+rl+"/"+tl+(d.activity?" · "+String(d.activity).substring(0,80):"");}}).catch(function(){});}tick();setInterval(tick,3000);})();</script>';
	}
}

$content = '<div class="card translations-review-page"><div class="card-body">';
$content .= '<h5 class="mb-3 text-dark font-weight-bold">Translations review</h5>';
if (!$detailMode && (string)($get['review_ok'] ?? '') === '1') {
	$content .= '<div class="alert alert-success alert-dismissible fade show" role="alert">Cluster marked as reviewed. Status set to <strong>ready to publish</strong>; approved translation pairs were added to the vector example store for RAG.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (!$detailMode && (string)($get['publish_ok'] ?? '') === '1') {
	$content .= '<div class="alert alert-success alert-dismissible fade show" role="alert">Cluster published: all locales set to <strong>published</strong> in content_i18n.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (!$detailMode && (string)($get['publish_ok'] ?? '') === '0') {
	$content .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">Could not publish cluster (no content rows or database error).<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (!$detailMode && (string)($get['review_err'] ?? '') === '1') {
	$rem = trim((string)($get['review_err_msg'] ?? ''));
	$detail = $rem !== '' ? (' ' . htmlspecialchars($rem, ENT_QUOTES, 'UTF-8')) : '';
	$content .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">Could not mark cluster as reviewed.' . $detail . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
}
if (!$detailMode) {
	$content .= '<form method="get" action="/admin.php" class="mb-3">';
	$content .= '<input type="hidden" name="m" value="translations">';
	$content .= '<input type="hidden" name="tab" value="review">';
	$content .= '<div class="form-row align-items-end">';
	$content .= '<div class="col-md-4 mb-2"><label class="small text-muted d-block">Search by ID or title</label><input class="form-control form-control-sm" type="text" name="q" value="' . htmlspecialchars($q, ENT_QUOTES, 'UTF-8') . '" placeholder="125 or aviator signals"></div>';
	$content .= '<div class="col-md-2 mb-2"><label class="small text-muted d-block">Entity</label><select class="form-control form-control-sm" name="entity">';
	foreach ($allowedEntity as $opt) {
		$label = $opt === 'all' ? 'All entities' : $opt;
		$content .= '<option value="' . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '"' . ($entityFilter === $opt ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-2 mb-2"><label class="small text-muted d-block">Status</label><select class="form-control form-control-sm" name="status">';
	foreach ($allowedStatus as $opt) {
		$label = $opt === 'all' ? 'All statuses' : $opt;
		$content .= '<option value="' . htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') . '"' . ($status === $opt ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-2 mb-2"><label class="small text-muted d-block">Sort</label><select class="form-control form-control-sm" name="sort">';
	foreach (array('updated_at' => 'Updated', 'status' => 'Status', 'entity_id' => 'ID', 'title' => 'Title', 'blockers' => 'Blockers') as $k => $label) {
		$content .= '<option value="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '"' . ($sort === $k ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-1 mb-2"><label class="small text-muted d-block">Dir</label><select class="form-control form-control-sm" name="dir">';
	$content .= '<option value="desc"' . ($dir === 'DESC' ? ' selected' : '') . '>Desc</option>';
	$content .= '<option value="asc"' . ($dir === 'ASC' ? ' selected' : '') . '>Asc</option>';
	$content .= '</select></div>';
	$content .= '<div class="col-md-1 mb-2"><button type="submit" class="btn btn-primary btn-sm btn-block">Apply</button></div>';
	$content .= '</div></form>';

	$content .= '<div class="table-responsive"><table class="table table-sm mb-0">';
	$content .= '<thead><tr><th>Entity</th><th>ID</th><th>Title</th><th>Stage</th><th>Status</th><th>Locales</th><th>Blockers</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
	$scope_n_cache = array();
	foreach ($rows as $r) {
		$ent = (string)$r['entity'];
		$eid = (int)$r['entity_id'];
		$title = trim((string)$r['search_title']);
		if ($title === '') {
			$snapshot = translation_cluster_get_source_snapshot($ent, $eid, 1);
			$title = trim((string)($snapshot['title'] ?? ''));
			if ($title === '') {
				$title = trim((string)($snapshot['name'] ?? ''));
			}
		}
		$clusterStatus = (string)$r['cluster_status_sort'];
		$stage = trim((string)$r['pipeline_stage']) !== '' ? (string)$r['pipeline_stage'] : 'legacy';
		$readyLocales = (int)$r['ready_locales'] > 0 ? (int)$r['ready_locales'] : (int)$r['published_count'];
		$totalLocales = (int)$r['total_locales'] > 0 ? (int)$r['total_locales'] : (int)$r['locale_count'];
		$src_lang_row = (int)($r['cs_source_lang_id'] ?? 1);
		if ($src_lang_row <= 0) {
			$src_lang_row = 1;
		}
		if (!isset($scope_n_cache[$src_lang_row])) {
			$scope_n_cache[$src_lang_row] = count(translation_cluster_scope_language_ids($src_lang_row));
		}
		$scope_n = (int)$scope_n_cache[$src_lang_row];
		$distinctLang = (int)($r['distinct_lang_count'] ?? 0);
		$allLocalesPresent = ($scope_n > 0 && $distinctLang >= $scope_n);
		$blockers = (int)$r['blocker_count_sort'];
		$qs = $baseParams;
		$qs['ce'] = $ent;
		$qs['cid'] = $eid;
		$openUrl = '/admin.php?' . http_build_query($qs);
		$pubParams = $qs;
		$pubParams['u'] = 'cluster_publish';
		$revParams = $qs;
		$revParams['u'] = 'cluster_review';
		$badge = 'warning';
		if ($clusterStatus === 'ready_to_publish' || $clusterStatus === 'published') {
			$badge = 'success';
		}
		if ($clusterStatus === 'blocked' || $clusterStatus === 'legacy_unnormalized') {
			$badge = 'danger';
		}
		$content .= '<tr>';
		$content .= '<td>' . htmlspecialchars($ent) . '</td>';
		$content .= '<td>' . (int)$eid . '</td>';
		$content .= '<td style="max-width:360px;white-space:normal;">' . htmlspecialchars($title !== '' ? $title : ('#' . $eid)) . '</td>';
		$content .= '<td>' . htmlspecialchars($stage) . '</td>';
		$content .= '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($clusterStatus) . '</span></td>';
		$content .= '<td>' . (int)$readyLocales . '/' . (int)$totalLocales . '</td>';
		$content .= '<td>' . $blockers . ((int)$r['warning_count'] > 0 ? ' +' . (int)$r['warning_count'] . 'w' : '') . '</td>';
		$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['last_updated_at']) . '</td>';
		$content .= '<td class="text-nowrap">';
		$content .= '<a class="btn btn-outline-primary btn-sm mr-1 mb-1" href="' . htmlspecialchars($openUrl, ENT_QUOTES, 'UTF-8') . '">Open</a>';
		if ($clusterStatus !== 'published') {
			$content .= '<a class="btn btn-success btn-sm mr-1 mb-1" href="' . htmlspecialchars('/admin.php?' . http_build_query($pubParams), ENT_QUOTES, 'UTF-8') . '" onclick="return confirm(&quot;Publish all locales in this cluster?&quot;)">Publish cluster</a>';
		}
		$hr_raw = isset($r['cs_human_reviewed_at']) ? trim((string)$r['cs_human_reviewed_at']) : '';
		$humanReviewed = ($hr_raw !== '' && $hr_raw !== '0000-00-00 00:00:00');
		$canMarkReview = $allLocalesPresent && $blockers === 0;
		if ($clusterStatus === 'published') {
			$content .= '<span class="btn btn-outline-secondary btn-sm mb-1 disabled" aria-disabled="true" title="Cluster is published">Mark review</span>';
		} elseif ($canMarkReview && $humanReviewed && $clusterStatus === 'ready_to_publish') {
			$content .= '<span class="btn btn-outline-secondary btn-sm mb-1 disabled" aria-disabled="true" title="Human review already recorded for this cluster">Mark review</span>';
			$content .= '<span class="small text-muted ml-1 align-middle">Already reviewed</span>';
		} elseif ($canMarkReview) {
			$content .= '<a class="btn btn-outline-secondary btn-sm mb-1" href="' . htmlspecialchars('/admin.php?' . http_build_query($revParams), ENT_QUOTES, 'UTF-8') . '" title="Mark entire cluster human-reviewed (stays on this list)">Mark review</a>';
		} elseif ($allLocalesPresent && $blockers > 0) {
			$content .= '<span class="btn btn-outline-secondary btn-sm mb-1 disabled" aria-disabled="true" title="Clear validation blockers before marking review">Mark review</span>';
		} else {
			$content .= '<span class="btn btn-outline-secondary btn-sm mb-1 disabled" aria-disabled="true" title="Available only when all locales are filled">Mark review</span>';
		}
		$content .= '</td>';
		$content .= '</tr>';
	}
	if ($rows === array()) {
		$content .= '<tr><td colspan="9" class="text-muted">No clusters found for the current filters.</td></tr>';
	}
	$content .= '</tbody></table></div>';

	$qPag = array(
		'n' => $n,
		'limit' => $perPage,
		'num_rows' => $total,
		'array_count' => $perPage,
		'url' => http_build_query($baseParams),
	);
	$content .= '<div class="pagination pagination-bottom mt-3">' . html_render('pagination/default', $qPag) . '</div>';
}

if ($detailMode) {
	$state = translation_cluster_get_state($clusterEntity, $clusterId);
	$src_lang_for_scope = $state ? (int)($state['source_lang_id'] ?? 1) : 1;
	if ($src_lang_for_scope <= 0) {
		$src_lang_for_scope = 1;
	}
	$scope_ids = translation_cluster_scope_language_ids($src_lang_for_scope);
	$langs = array();
	if ($scope_ids !== array()) {
		$ids_sql = implode(',', array_map('intval', $scope_ids));
		$langs = mysql_select("SELECT id, url, name FROM languages WHERE id IN (" . $ids_sql . ") ORDER BY rank DESC", 'rows') ?: array();
	}
	$detailRows = mysql_select("
		SELECT ci.id, ci.lang_id, ci.url, ci.name, ci.title, ci.description, ci.status, ci.updated_at,
			l.url AS lang_url, l.name AS lang_name
		FROM content_i18n ci
		LEFT JOIN languages l ON l.id = ci.lang_id
		WHERE ci.entity='" . mysql_res($clusterEntity) . "'
		  AND ci.entity_id=" . (int)$clusterId . "
		ORDER BY l.rank DESC, ci.lang_id ASC, ci.id DESC
	", 'rows') ?: array();
	$detailMap = array();
	foreach ($detailRows as $dr) {
		$lid = isset($dr['lang_id']) ? (int)$dr['lang_id'] : 0;
		if ($lid > 0 && !isset($detailMap[$lid])) {
			$detailMap[$lid] = $dr;
		}
	}
	// Source language often has no content_i18n row (canonical text lives in pages/blog/games/… tables).
	if (!isset($detailMap[$src_lang_for_scope])) {
		$snap = translation_cluster_get_source_snapshot($clusterEntity, $clusterId, $src_lang_for_scope);
		if (is_array($snap)) {
			$u = trim((string)($snap['url'] ?? ''));
			$c = trim((string)($snap['content'] ?? ''));
			$t = trim((string)($snap['title'] ?? ''));
			$n = trim((string)($snap['name'] ?? ''));
			if ($u !== '' || $c !== '' || $t !== '' || $n !== '') {
				$detailMap[$src_lang_for_scope] = array(
					'id' => 0,
					'lang_id' => $src_lang_for_scope,
					'url' => $u,
					'name' => $n,
					'title' => $t !== '' ? $t : $n,
					'description' => isset($snap['description']) ? (string)$snap['description'] : '',
					'status' => 'source',
					'updated_at' => '',
				);
			}
		}
	}
	$presentLocales = count($detailMap);
	$totalLocales = count($langs);
	$reviewedLocales = 0;
	$publishedLocales = 0;
	foreach ($detailMap as $row) {
		$rowStatus = isset($row['status']) ? (string)$row['status'] : '';
		if ($rowStatus === 'review') {
			$reviewedLocales++;
		}
		if ($rowStatus === 'published') {
			$publishedLocales++;
		}
	}
	$detailNavParams = array_merge($baseParams, array('ce' => $clusterEntity, 'cid' => $clusterId));
	$manualApproveParams = array_merge($detailNavParams, array('u' => 'cluster_manual_approve'));
	$manualApproveUrl = '/admin.php?' . http_build_query($manualApproveParams);
	$content .= '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">';
	$content .= '<h6 class="mb-0">Cluster detail: ' . htmlspecialchars($clusterEntity) . '#' . (int)$clusterId . '</h6>';
	$content .= '<div class="d-flex flex-wrap align-items-center gap-2">';
	$content .= '<a class="btn btn-success btn-sm" href="' . htmlspecialchars($manualApproveUrl, ENT_QUOTES, 'UTF-8') . '" onclick="return confirm(&quot;Manual total approve: mark this cluster as human-verified, set status to ready to publish, cancel pending translation jobs for this cluster, and exclude it from autopilot until content_i18n changes after this time?&quot;)">Manual total approve</a>';
	$content .= '<a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') . '">Back to list</a>';
	$content .= '</div></div>';
	if ((string)($get['manual_ok'] ?? '') === '1') {
		$mj = (int)($get['manual_jobs'] ?? 0);
		$content .= '<div class="alert alert-success alert-dismissible fade show" role="alert">Manual total approve applied: cluster is <strong>ready to publish</strong>; autopilot will skip this material until any locale is edited after this approval. Pending jobs cancelled: <strong>' . $mj . '</strong>.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
	}
	if ((string)($get['manual_err'] ?? '') === '1') {
		$mrem = trim((string)($get['manual_err_msg'] ?? ''));
		$mdetail = $mrem !== '' ? (' ' . htmlspecialchars($mrem, ENT_QUOTES, 'UTF-8')) : '';
		$content .= '<div class="alert alert-warning alert-dismissible fade show" role="alert">Could not apply manual total approve.' . $mdetail . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
	}
	$pollUrl = '/admin.php?m=translations&tab=review&u=cluster_live_poll&ce=' . rawurlencode($clusterEntity) . '&cid=' . (int)$clusterId;
	$content .= '<div class="card border-primary mb-3" id="tc-cluster-live-monitor">';
	$content .= '<div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">';
	$content .= '<span><strong>Online monitor</strong> <span class="badge badge-secondary">live</span></span>';
	$content .= '<span class="small text-muted font-monospace" id="tc-live-clock">—</span>';
	$content .= '</div><div class="card-body py-3">';
	$content .= '<p class="small text-muted mb-2">Updates every few seconds (same idea as SEO Monitor rebuild progress). Shows this cluster&rsquo;s jobs and overall pipeline/validation progress.</p>';
	$content .= '<div class="mb-2"><div class="small text-muted mb-1">Pipeline — ready locales (cluster state)</div>';
	$content .= '<div class="progress" style="height:24px"><div class="progress-bar" id="tc-live-pipeline-bar" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div></div>';
	$content .= '<div class="small mt-1" id="tc-live-pipeline-hint">Loading…</div></div>';
	$content .= '<div class="mb-3"><div class="small text-muted mb-1">Validation — locales passing checks (no blockers)</div>';
	$content .= '<div class="progress" style="height:24px"><div class="progress-bar bg-info text-dark" id="tc-live-valid-bar" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div></div>';
	$content .= '<div class="small mt-1" id="tc-live-valid-hint">Loading…</div></div>';
	$content .= '<div class="mb-2"><strong>Current activity</strong>: <span id="tc-live-activity">Loading…</span></div>';
	$content .= '<div class="row"><div class="col-md-6 mb-2"><div class="small text-muted">Running (this cluster)</div><ul class="small mb-0 pl-3" id="tc-live-running"></ul></div>';
	$content .= '<div class="col-md-6 mb-2"><div class="small text-muted">Pending (this cluster)</div><ul class="small mb-0 pl-3" id="tc-live-pending"></ul></div></div>';
	$content .= '<div class="small text-muted border-top pt-2 mt-2" id="tc-live-queue-glob">Global translations queue: …</div>';
	$content .= '<p class="mb-0 mt-2 small"><a href="/admin.php?m=telemetry">Telemetry</a> · <a href="/admin.php?m=translations&tab=monitor&mtab=jobs">Translation jobs</a> · <a href="/admin.php?m=translations&tab=monitor">Monitor</a></p>';
	$content .= '</div></div>';
	$content .= '<script>(function(){var url=' . json_encode($pollUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
	$content .= 'function esc(s){if(s==null)return"";var t=document.createTextNode(String(s));var d=document.createElement("div");d.appendChild(t);return d.innerHTML;}';
	$content .= 'function tick(){fetch(url,{credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){return r.json();}).then(function(d){';
	$content .= 'if(!d||!d.ok)return;';
	$content .= 'var pp=d.pipeline_pct!=null?parseInt(d.pipeline_pct,10):0;if(isNaN(pp))pp=0;var pb=document.getElementById("tc-live-pipeline-bar");if(pb){pb.style.width=pp+"%";pb.setAttribute("aria-valuenow",pp);pb.textContent=pp+"%";}';
	$content .= 'var ph=document.getElementById("tc-live-pipeline-hint");if(ph&&d.cluster){ph.textContent=(d.cluster.cluster_status||"—")+" · ready "+(d.cluster.ready_locales||"0")+"/"+(d.cluster.total_locales||"0")+" · blockers "+(d.cluster.blocker_count||"0");}';
	$content .= 'var vp=d.locale_ok_pct!=null?parseInt(d.locale_ok_pct,10):null;var vb=document.getElementById("tc-live-valid-bar");';
	$content .= 'if(vb){if(vp==null){vb.style.width="0%";vb.textContent="—";}else{vb.style.width=Math.min(100,Math.max(0,vp))+"%";vb.setAttribute("aria-valuenow",vp);vb.textContent=vp+"%";}}';
	$content .= 'var vh=document.getElementById("tc-live-valid-hint");if(vh&&d.locale_ok!=null&&d.locale_total!=null){vh.textContent=d.locale_ok+" / "+d.locale_total+" locales pass validation";}';
	$content .= 'var act=document.getElementById("tc-live-activity");if(act)act.textContent=d.activity||"—";';
	$content .= 'var clk=document.getElementById("tc-live-clock");if(clk)clk.textContent=d.server_now||"—";';
	$content .= 'var ru=document.getElementById("tc-live-running");if(ru){ru.innerHTML="";(d.running||[]).forEach(function(j){var li=document.createElement("li");li.innerHTML="<strong>#"+j.id+"</strong> "+esc(j.action)+" — "+esc((j.message||"").substring(0,120));ru.appendChild(li);});if(!d.running||!d.running.length){ru.innerHTML="<li class=\\"text-muted\\">None</li>";}}';
	$content .= 'var pe=document.getElementById("tc-live-pending");if(pe){pe.innerHTML="";(d.pending||[]).forEach(function(j){var li=document.createElement("li");var sf=j.scheduled_future?" <span class=\\"text-warning\\">scheduled</span>":"";li.innerHTML="<strong>#"+j.id+"</strong> "+esc(j.action)+sf+(j.scheduled_at?" <span class=\\"text-muted\\">"+esc(j.scheduled_at)+"</span>":"");pe.appendChild(li);});if(!d.pending||!d.pending.length){pe.innerHTML="<li class=\\"text-muted\\">None</li>";}}';
	$content .= 'var qg=document.getElementById("tc-live-queue-glob");if(qg&&d.queue){qg.textContent="Global: "+(d.queue.pending_translations_globally||0)+" pending · "+(d.queue.running_translations_globally||0)+" running translations · stale running (warn): "+(d.queue.stale_running_count||0);}';
	$content .= '}).catch(function(){});}tick();setInterval(tick,2500);})();</script>';
	if ($state) {
		$content .= '<div class="alert alert-light border small">';
		$content .= '<strong>Status</strong>: ' . htmlspecialchars((string)$state['cluster_status']) . '. ';
		$content .= '<strong>Stage</strong>: ' . htmlspecialchars((string)$state['pipeline_stage']) . '. ';
		$content .= '<strong>Ready locales</strong>: ' . (int)$state['ready_locales'] . '/' . (int)$state['total_locales'] . '. ';
		$content .= '<strong>Blockers</strong>: ' . (int)$state['blocker_count'] . '. ';
		$content .= '<strong>Warnings</strong>: ' . (int)$state['warning_count'] . '.';
		$content .= '</div>';
	}
	$content .= '<div class="alert alert-secondary small py-2">';
	$content .= '<strong>Coverage</strong>: present ' . $presentLocales . '/' . $totalLocales . ', missing ' . max(0, $totalLocales - $presentLocales) . ', review ' . $reviewedLocales . ', published ' . $publishedLocales . '.';
	$content .= '</div>';
	$content .= '<div class="table-responsive"><table class="table table-sm mb-0">';
	$content .= '<thead><tr><th>Lang</th><th>Coverage</th><th>URL</th><th>Title</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';
	foreach ($langs as $lang) {
		$lid = isset($lang['id']) ? (int)$lang['id'] : 0;
		if ($lid <= 0) {
			continue;
		}
		$dr = isset($detailMap[$lid]) ? $detailMap[$lid] : null;
		$editHref = admin_i18n_review_edit_url((string)$clusterEntity, (int)$clusterId, $lid);
		$langUrl = trim((string)($lang['url'] ?? ''), '/');
		$slug = $dr ? trim((string)$dr['url'], '/') : '';
		$viewUrl = ($slug !== '') ? admin_i18n_public_material_path((string)$clusterEntity, (int)$clusterId, $langUrl, $slug) : '';
		$langLabel = trim((string)($lang['name'] ?? '')) !== '' ? ((string)$lang['name'] . ' (' . $langUrl . ')') : ('lang_id=' . $lid);
		$isCanonicalSource = $dr && (int)($dr['id'] ?? -1) === 0 && isset($dr['status']) && (string)$dr['status'] === 'source';
		if ($isCanonicalSource) {
			$coverage = 'canonical';
			$coverageBadge = 'info';
		} else {
			$coverage = $dr ? 'present' : 'missing';
			$coverageBadge = $dr ? 'success' : 'secondary';
		}
		$title = $dr ? (string)$dr['title'] : '';
		$rowStatus = $dr ? (string)$dr['status'] : 'missing';
		$updatedAt = $dr ? (string)$dr['updated_at'] : '';
		$localeReviewUrl = '';
		if ($dr) {
			$localeParams = $baseParams;
			$localeParams['ce'] = $clusterEntity;
			$localeParams['cid'] = $clusterId;
			$localeParams['u'] = 'locale_review';
			$localeParams['id'] = (int)$dr['id'];
			$localeReviewUrl = '/admin.php?' . http_build_query($localeParams);
		}
		$content .= '<tr>';
		$content .= '<td>' . htmlspecialchars($langLabel) . '</td>';
		$content .= '<td><span class="badge badge-' . $coverageBadge . '">' . htmlspecialchars($coverage) . '</span></td>';
		if ($viewUrl !== '') {
			$content .= '<td><a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . htmlspecialchars($viewUrl) . '</a></td>';
		} else {
			$content .= '<td class="text-muted">No URL yet</td>';
		}
		$content .= '<td style="max-width:420px;white-space:normal;">' . htmlspecialchars($title) . '</td>';
		$content .= '<td>' . htmlspecialchars($rowStatus) . '</td>';
		$content .= '<td class="text-muted small">' . htmlspecialchars($updatedAt) . '</td>';
		$content .= '<td>';
		if ($editHref !== '') {
			$content .= '<a class="btn btn-outline-primary btn-sm mr-1 mb-1" href="' . htmlspecialchars($editHref, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">Edit locale</a>';
		}
		if ($localeReviewUrl !== '' && $rowStatus !== 'review' && $rowStatus !== 'published') {
			$content .= '<a class="btn btn-outline-secondary btn-sm mb-1" href="' . htmlspecialchars($localeReviewUrl, ENT_QUOTES, 'UTF-8') . '">Make review</a>';
		}
		$content .= '</td>';
		$content .= '</tr>';
	}
	if ($langs === array()) {
		$content .= '<tr><td colspan="7" class="text-muted">No enabled locales found.</td></tr>';
	}
	$content .= '</tbody></table></div>';
}

$content .= '</div></div>';

?>
