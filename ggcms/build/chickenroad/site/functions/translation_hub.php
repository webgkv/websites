<?php
/**
 * Translations hub (m=translations): shared URLs, access, autopilot strip, nav.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * @param string $tab overview|review|monitor|batch
 * @param array<string,mixed> $extra merged into query (overwrites)
 * @return string /admin.php?...
 */
function translation_hub_url($tab, array $extra = array()) {
	$tab = (string)$tab;
	$q = array_merge(array('m' => 'translations', 'tab' => $tab), $extra);
	return '/admin.php?' . http_build_query($q);
}

/**
 * admin_jobs.action values listed under Translations → Activity (completed jobs).
 * Retention purge in translation_autopilot uses the same set.
 *
 * @return list<string>
 */
function translation_hub_activity_admin_job_actions() {
	return array(
		'translate_cluster',
		'cluster_pipeline',
		'validate_cluster',
		'translate',
		'repair_locale',
		'validate_locale',
		'metadata_normalize',
	);
}

/**
 * SQL fragment for WHERE action IN (...)
 */
function translation_hub_activity_admin_job_actions_sql_in() {
	if (!function_exists('mysql_res')) {
		return "''";
	}
	$parts = array();
	foreach (translation_hub_activity_admin_job_actions() as $a) {
		$parts[] = "'" . mysql_res((string)$a) . "'";
	}
	return $parts ? implode(',', $parts) : "''";
}

/**
 * Hub access: explicit translations module OR any legacy translations* module.
 */
function translation_hub_access() {
	if (!function_exists('access')) {
		return false;
	}
	if (access('admin module', 'translations')) {
		return true;
	}
	foreach (array('translations_review', 'translations_monitor', 'translations_batch', 'translate_stats', 'translations_settings') as $m) {
		if (access('admin module', $m)) {
			return true;
		}
	}
	return false;
}

/**
 * HTML: autopilot cluster strip (same data as Translations review header strip).
 *
 * @return string
 */
function translation_hub_render_autopilot_strip() {
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	translation_cluster_ensure_tables();
	$page_header_extra = '';
	$focus = translation_cluster_find_header_focus();
	if ($focus === null) {
		return '';
	}
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
	$allowedStatus = array('all', 'new', 'translating', 'needs_review', 'blocked', 'ready_to_publish', 'published', 'legacy_unnormalized');
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
	$page_header_extra = '<div class="tc-review-cluster-strip translations-hub-autopilot translations-review-header border rounded bg-white px-2 py-2 mb-2" style="border-left:3px solid #1c5bbf!important;">';
	$page_header_extra .= '<div class="d-flex align-items-center flex-wrap" style="gap:8px;">';
	$page_header_extra .= '<div class="flex-grow-1" style="min-width:140px;">';
	$page_header_extra .= '<div class="small text-truncate mb-0 tc-review-strip-title" title="' . htmlspecialchars($titleHint, ENT_QUOTES, 'UTF-8') . '">Autopilot · ' . htmlspecialchars($titleHint, ENT_QUOTES, 'UTF-8') . '</div>';
	$page_header_extra .= '<div class="progress mt-1" style="height:4px;"><div class="progress-bar" id="tc-hub-header-pipeline" role="progressbar" style="width:' . (int)$pct . '%" aria-valuenow="' . (int)$pct . '" aria-valuemin="0" aria-valuemax="100"></div></div>';
	$page_header_extra .= '<div class="small tc-review-strip-hint" id="tc-hub-header-hint">' . htmlspecialchars($cstat, ENT_QUOTES, 'UTF-8') . ' · ready ' . (int)$ready . '/' . (int)$tot . '</div>';
	$page_header_extra .= '</div>';
	$page_header_extra .= '<a class="btn btn-sm flex-shrink-0 btn-tc-review-open" id="tc-hub-header-open" href="' . htmlspecialchars($detailOpenUrl, ENT_QUOTES, 'UTF-8') . '">Cluster detail</a>';
	$page_header_extra .= '</div></div>';
	$page_header_extra .= '<script>(function(){var u=' . json_encode($pollUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
	$page_header_extra .= 'function tick(){fetch(u,{credentials:"same-origin",headers:{"X-Requested-With":"XMLHttpRequest"}}).then(function(r){return r.json();}).then(function(d){';
	$page_header_extra .= 'if(!d||!d.ok)return;var pp=d.pipeline_pct!=null?parseInt(d.pipeline_pct,10):NaN;var bar=document.getElementById("tc-hub-header-pipeline");';
	$page_header_extra .= 'if(bar&&!isNaN(pp)){bar.style.width=Math.min(100,Math.max(0,pp))+"%";bar.setAttribute("aria-valuenow",pp);}';
	$page_header_extra .= 'var h=document.getElementById("tc-hub-header-hint");if(h&&d.cluster){var cs=d.cluster.cluster_status||"—";var rl=d.cluster.ready_locales||"0";var tl=d.cluster.total_locales||"0";h.textContent=cs+" · ready "+rl+"/"+tl+(d.activity?" · "+String(d.activity).substring(0,80):"");}}).catch(function(){});}tick();setInterval(tick,3000);})();</script>';
	return $page_header_extra;
}

/**
 * @param string $active overview|review|monitor|batch|activity
 * @return string
 */
function translation_hub_render_nav_tabs($active) {
	$tabs = array(
		'overview' => 'Overview',
		'review' => 'Review',
		'monitor' => 'Monitor',
		'batch' => 'Batch',
		'activity' => 'Activity',
	);
	$html = '<div class="translations-hub-nav mb-2"><ul class="nav nav-tabs flex-wrap border-bottom-0">';
	foreach ($tabs as $tid => $label) {
		$is = ($active === $tid);
		$cls = 'nav-link py-2 px-3' . ($is ? ' active font-weight-bold' : '');
		$href = translation_hub_url($tid);
		$html .= '<li class="nav-item mb-1"><a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
	}
	$html .= '</ul></div>';
	return $html;
}
