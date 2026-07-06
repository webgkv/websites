<?php
/**
 * Translations hub — Activity.
 */
$page_name = 'Translations: activity';

require_once ROOT_DIR . 'functions/translation_hub.php';

if (!defined('TRANSLATIONS_HUB')) {
	if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
		header('Location: /admin.php?' . http_build_query(array('m' => 'translations', 'tab' => 'activity')));
		exit;
	}
	return;
}

/**
 * @param string $payload_json
 * @return string
 */
function translations_activity_cluster_label_from_payload($payload_json) {
	$d = json_decode((string)$payload_json, true);
	if (!is_array($d)) {
		return '—';
	}
	$e = isset($d['entity']) ? trim((string)$d['entity']) : '';
	$id = isset($d['entity_id']) ? (int)$d['entity_id'] : 0;
	if ($e !== '' && $id > 0) {
		return $e . '#' . $id;
	}
	return '—';
}

/**
 * @param string $payload_json
 * @return string href or ''
 */
function translations_activity_cluster_review_href_from_payload($payload_json) {
	$d = json_decode((string)$payload_json, true);
	if (!is_array($d)) {
		return '';
	}
	$e = isset($d['entity']) ? trim((string)$d['entity']) : '';
	$id = isset($d['entity_id']) ? (int)$d['entity_id'] : 0;
	if ($e === '' || $id <= 0) {
		return '';
	}
	return '/admin.php?' . http_build_query(array(
		'm' => 'translations',
		'tab' => 'review',
		'ce' => $e,
		'cid' => $id,
	));
}

/**
 * @param string $s
 * @param int $max
 * @return string
 */
function translations_activity_trunc($s, $max = 120) {
	$s = trim((string)$s);
	if (function_exists('mb_substr')) {
		return mb_strlen($s, 'UTF-8') > $max ? mb_substr($s, 0, $max, 'UTF-8') . '…' : $s;
	}
	return strlen($s) > $max ? substr($s, 0, $max) . '…' : $s;
}

$get = isset($get) && is_array($get) ? array_merge(array('n' => 1, 'jn' => 1), $get) : array('n' => 1, 'jn' => 1);
$per = 10;
$n = max(1, (int)($get['n'] ?? 1));
$jn = max(1, (int)($get['jn'] ?? 1));

$logs_ok = @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0;
$jobs_ok = @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0;

$activity_job_in = translation_hub_activity_admin_job_actions_sql_in();

$total_pub = 0;
$published = array();
if ($logs_ok) {
	$total_pub = (int)mysql_select("
		SELECT COUNT(*) AS c FROM system_logs
		WHERE channel = 'translations' AND message = 'translation_cluster_publish_all_content_i18n'
	", 'string');
	$pages_pub = $per > 0 ? (int)max(1, (int)ceil($total_pub / $per)) : 1;
	if ($n > $pages_pub) {
		$n = $pages_pub;
	}
	$offset_pub = ($n - 1) * $per;
	$published = mysql_select("
		SELECT id, message, context, created_at
		FROM system_logs
		WHERE channel = 'translations' AND message = 'translation_cluster_publish_all_content_i18n'
		ORDER BY id DESC
		LIMIT " . (int)$per . " OFFSET " . (int)$offset_pub . "
	", 'rows') ?: array();
} else {
	$pages_pub = 1;
}

$total_jobs = 0;
$jobs = array();
if ($jobs_ok) {
	$total_jobs = (int)mysql_select("
		SELECT COUNT(*) AS c FROM admin_jobs
		WHERE module = 'translations' AND status = 'done'
			AND action IN (" . $activity_job_in . ")
	", 'string');
	$pages_jobs = $per > 0 ? (int)max(1, (int)ceil($total_jobs / $per)) : 1;
	if ($jn > $pages_jobs) {
		$jn = $pages_jobs;
	}
	$offset_jobs = ($jn - 1) * $per;
	$jobs = mysql_select("
		SELECT id, action, message, payload, finished_at, created_at, updated_at
		FROM admin_jobs
		WHERE module = 'translations' AND status = 'done'
			AND action IN (" . $activity_job_in . ")
		ORDER BY COALESCE(NULLIF(finished_at, ''), updated_at, created_at) DESC, id DESC
		LIMIT " . (int)$per . " OFFSET " . (int)$offset_jobs . "
	", 'rows') ?: array();
} else {
	$pages_jobs = 1;
}

$content = '<div class="card translations-activity-page mb-3"><div class="card-body">';
$content .= '<h5 class="mb-3 text-dark font-weight-bold">Activity</h5>';

$content .= '<h6 class="font-weight-bold mb-2">Published clusters</h6>';
$content .= '<div class="table-responsive mb-0"><table class="table table-sm table-bordered bg-white mb-0 translations-activity-table">';
$content .= '<thead class="thead-light"><tr><th style="width:11rem;">Time</th><th>Cluster</th><th style="width:6rem;"></th></tr></thead><tbody>';
if (!$logs_ok) {
	$content .= '<tr><td colspan="3" class="text-muted">system_logs table not available.</td></tr>';
} elseif ($published === array()) {
	$content .= '<tr><td colspan="3" class="text-muted">No publish events logged yet.</td></tr>';
} else {
	foreach ($published as $r) {
		$ctx = isset($r['context']) ? (string)$r['context'] : '';
		$dec = json_decode($ctx, true);
		$ent = is_array($dec) && isset($dec['entity']) ? trim((string)$dec['entity']) : '';
		$eid = is_array($dec) && isset($dec['entity_id']) ? (int)$dec['entity_id'] : 0;
		$label = ($ent !== '' && $eid > 0) ? $ent . '#' . $eid : '—';
		$review = ($ent !== '' && $eid > 0)
			? '/admin.php?' . http_build_query(array('m' => 'translations', 'tab' => 'review', 'ce' => $ent, 'cid' => $eid))
			: '';
		$content .= '<tr class="ta-row-link"' . ($review !== '' ? ' data-href="' . htmlspecialchars($review, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
		$content .= '<td class="text-muted small" style="white-space:nowrap;">' . htmlspecialchars((string)$r['created_at']) . '</td>';
		$content .= '<td class="font-weight-bold text-primary">' . htmlspecialchars($label) . '</td>';
		$content .= '<td class="text-right">';
		if ($review !== '') {
			$content .= '<a class="btn btn-outline-primary btn-sm" href="' . htmlspecialchars($review, ENT_QUOTES, 'UTF-8') . '" onclick="event.stopPropagation();">Open</a>';
		}
		$content .= '</td>';
		$content .= '</tr>';
	}
}
$content .= '</tbody></table></div>';

$qPagPub = array(
	'n' => $n,
	'limit' => $per,
	'num_rows' => $total_pub,
	'array_count' => $per,
);
$content .= '<div class="pagination pagination-bottom mt-3">' . html_render('pagination/default', $qPagPub) . '</div>';

$content .= '<h6 class="font-weight-bold mb-2 mt-4">Finished translation jobs</h6>';
$content .= '<div class="table-responsive mb-0"><table class="table table-sm table-bordered bg-white mb-0 translations-activity-table">';
$content .= '<thead class="thead-light"><tr><th style="width:11rem;">Time</th><th style="width:9rem;">Job</th><th>Cluster</th><th>Result</th></tr></thead><tbody>';
if (!$jobs_ok) {
	$content .= '<tr><td colspan="4" class="text-muted">admin_jobs table not available.</td></tr>';
} elseif ($jobs === array()) {
	$content .= '<tr><td colspan="4" class="text-muted">No finished jobs in this list.</td></tr>';
} else {
	foreach ($jobs as $r) {
		$jid = (int)$r['id'];
		$action = htmlspecialchars((string)$r['action']);
		$msg = translations_activity_trunc((string)($r['message'] ?? ''), 120);
		$when = (string)($r['finished_at'] ?? '');
		if ($when === '') {
			$when = (string)($r['created_at'] ?? '');
		}
		$payload = isset($r['payload']) ? (string)$r['payload'] : '';
		$label = translations_activity_cluster_label_from_payload($payload);
		$cluster_href = translations_activity_cluster_review_href_from_payload($payload);
		$job_url = '/admin.php?m=jobs&id=' . $jid;
		$row_href = $cluster_href !== '' ? $cluster_href : $job_url;
		$content .= '<tr class="ta-row-link" data-href="' . htmlspecialchars($row_href, ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<td class="text-muted small" style="white-space:nowrap;">' . htmlspecialchars($when) . '</td>';
		$content .= '<td><a href="' . htmlspecialchars($job_url, ENT_QUOTES, 'UTF-8') . '" onclick="event.stopPropagation();">#' . $jid . '</a> <span class="text-muted">' . $action . '</span></td>';
		$content .= '<td class="font-weight-bold text-primary">' . htmlspecialchars($label) . '</td>';
		$content .= '<td style="max-width:380px;" class="small">' . htmlspecialchars($msg) . '</td>';
		$content .= '</tr>';
	}
}
$content .= '</tbody></table></div>';

$qPagJobs = array(
	'n' => $jn,
	'limit' => $per,
	'num_rows' => $total_jobs,
	'array_count' => $per,
	'page_key' => 'jn',
);
$content .= '<div class="pagination pagination-bottom mt-3">' . html_render('pagination/default', $qPagJobs) . '</div>';

$content .= '</div></div>';

$content .= '<style>
.translations-activity-table tbody tr.ta-row-link[data-href]{cursor:pointer}
.translations-activity-table tbody tr.ta-row-link[data-href]:hover{background-color:rgba(0,123,255,.06)}
</style>';

$content .= '<script>document.addEventListener("click",function(e){var tr=e.target.closest("tr.ta-row-link[data-href]");if(!tr||!tr.closest(".translations-activity-page"))return;if(e.target.closest("a"))return;var h=tr.getAttribute("data-href");if(h)window.location.href=h;});document.addEventListener("keydown",function(e){if(e.key!=="Enter"&&e.key!==" ")return;var tr=e.target.closest("tr.ta-row-link[data-href]");if(!tr||!tr.closest(".translations-activity-page"))return;var h=tr.getAttribute("data-href");if(h)window.location.href=h;});document.querySelectorAll(".translations-activity-page tr.ta-row-link[data-href]").forEach(function(tr){tr.setAttribute("tabindex","0");});</script>';
