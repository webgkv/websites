<?php
/**
 * Translations: job queue viewer (admin_jobs filtered by module=translations).
 */
$page_name = 'Translations: jobs';

// Deprecated: use global Jobs module with module filter
header('Location: /admin.php?m=jobs&module=translations');
exit;

$get = array_merge(array('u' => '', 'id' => '', 'status' => ''), (array)$get);

if (!empty($get['u']) && in_array($get['u'], array('retry','cancel'), true) && (int)$get['id'] > 0) {
	$id = (int)$get['id'];
	if ($get['u'] === 'retry') {
		mysql_fn('update', 'admin_jobs', array('status' => 'pending', 'message' => null, 'finished_at' => null), " AND id=" . $id . " AND module='translations' ");
	}
	if ($get['u'] === 'cancel') {
		mysql_fn('update', 'admin_jobs', array('status' => 'cancelled', 'finished_at' => date('Y-m-d H:i:s')), " AND id=" . $id . " AND module='translations' AND status IN ('pending','running') ");
	}
	header('Location: /admin.php?m=translations_jobs');
	exit;
}

$status = trim((string)$get['status']);
$where = "module='translations'";
if ($status !== '' && in_array($status, array('pending','running','done','failed','cancelled'), true)) {
	$where .= " AND status='" . mysql_res($status) . "'";
}
$rows = mysql_select("SELECT id, action, status, message, created_at, started_at, finished_at FROM admin_jobs WHERE {$where} ORDER BY id DESC LIMIT 200", 'rows') ?: array();

$content = '<div class="card mb-3"><div class="card-body">';
$content .= '<h5 class="mb-2">Translation jobs</h5>';
$content .= '<div class="dashboard-quick-links mb-2">';
$content .= '<a class="btn btn-outline-secondary btn-sm" href="/cron/web_jobs.php" target="_blank">Run one job now</a> ';
$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=system_logs&channel=translations" target="_blank">View translation logs</a> ';
$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations_jobs">Refresh</a>';
$content .= '</div>';
$content .= '<form method="get" class="row g-2 align-items-end">';
$content .= '<input type="hidden" name="m" value="translations_jobs">';
$content .= '<div class="col-md-3"><label class="form-label">Status</label><select class="form-select" name="status">';
$content .= '<option value="">All</option>';
foreach (array('pending','running','done','failed','cancelled') as $st) {
	$content .= '<option value="' . $st . '"' . ($st === $status ? ' selected' : '') . '>' . strtoupper($st) . '</option>';
}
$content .= '</select></div>';
$content .= '<div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>';
$content .= '</form>';
$content .= '</div></div>';

$content .= '<div class="card"><div class="card-body">';
$content .= '<div class="table-responsive"><table class="table table-sm">';
$content .= '<thead><tr><th>ID</th><th>Action</th><th>Status</th><th>Message</th><th>Created</th><th>Started</th><th>Finished</th><th>Actions</th></tr></thead><tbody>';
foreach ($rows as $r) {
	$id = (int)$r['id'];
	$content .= '<tr>';
	$content .= '<td>' . $id . '</td>';
	$content .= '<td>' . htmlspecialchars((string)$r['action']) . '</td>';
	$content .= '<td>' . htmlspecialchars(strtoupper((string)$r['status'])) . '</td>';
	$content .= '<td style="max-width:520px;white-space:normal;">' . htmlspecialchars((string)$r['message']) . '</td>';
	$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['created_at']) . '</td>';
	$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['started_at']) . '</td>';
	$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['finished_at']) . '</td>';
	$retry = '/admin.php?m=translations_jobs&u=retry&id=' . $id;
	$cancel = '/admin.php?m=translations_jobs&u=cancel&id=' . $id;
	$content .= '<td>';
	$content .= '<a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($retry) . '">Retry</a> ';
	$content .= '<a class="btn btn-outline-danger btn-sm" href="' . htmlspecialchars($cancel) . '" onclick="return confirm(\'Cancel this job?\')">Cancel</a>';
	$content .= '</td>';
	$content .= '</tr>';
}
if (empty($rows)) {
	$content .= '<tr><td colspan="8" class="text-muted">No jobs.</td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '</div></div>';

