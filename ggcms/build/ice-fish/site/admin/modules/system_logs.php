<?php
/**
 * Logs viewer for system_logs table.
 */
$page_name = 'System logs';

$table_ok = @mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table system_logs not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

$get = array_merge(array('channel' => '', 'level' => '', 'q' => ''), (array)$get);
$channel = trim((string)$get['channel']);
$level = trim((string)$get['level']);
$q = trim((string)$get['q']);

$where = array("1=1");
if ($channel !== '') $where[] = "channel = '" . mysql_res($channel) . "'";
if ($level !== '' && in_array($level, array('debug','info','warning','error'), true)) $where[] = "level = '" . mysql_res($level) . "'";
if ($q !== '') $where[] = "message LIKE '%" . mysql_res($q) . "%'";
$where_sql = implode(' AND ', $where);

$rows = mysql_select("SELECT id, channel, level, message, created_at FROM system_logs WHERE {$where_sql} ORDER BY id DESC LIMIT 200", 'rows') ?: array();

$content = '<div class="card mb-3"><div class="card-body">';
$content .= '<h5 class="mb-2">System logs</h5>';
$content .= '<form method="get" class="row g-2 align-items-end">';
$content .= '<input type="hidden" name="m" value="system_logs">';
$content .= '<div class="col-md-3"><label class="form-label">Channel</label><input class="form-control" name="channel" value="' . htmlspecialchars($channel) . '" placeholder="translations, jobs, sitemap..."></div>';
$content .= '<div class="col-md-2"><label class="form-label">Level</label><select class="form-select" name="level">';
$content .= '<option value="">All</option>';
foreach (array('debug','info','warning','error') as $lv) {
	$content .= '<option value="' . $lv . '"' . ($lv === $level ? ' selected' : '') . '>' . strtoupper($lv) . '</option>';
}
$content .= '</select></div>';
$content .= '<div class="col-md-4"><label class="form-label">Search</label><input class="form-control" name="q" value="' . htmlspecialchars($q) . '" placeholder="text..."></div>';
$content .= '<div class="col-md-3"><button class="btn btn-primary btn-sm" type="submit">Filter</button></div>';
$content .= '</form>';
$content .= '</div></div>';

$content .= '<div class="card"><div class="card-body">';
$content .= '<div class="table-responsive"><table class="table table-sm">';
$content .= '<thead><tr><th>ID</th><th>Time</th><th>Channel</th><th>Level</th><th>Message</th></tr></thead><tbody>';
foreach ($rows as $r) {
	$content .= '<tr>';
	$content .= '<td>' . (int)$r['id'] . '</td>';
	$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['created_at']) . '</td>';
	$content .= '<td>' . htmlspecialchars((string)$r['channel']) . '</td>';
	$content .= '<td>' . htmlspecialchars(strtoupper((string)$r['level'])) . '</td>';
	$content .= '<td style="max-width:700px;white-space:normal;">' . htmlspecialchars((string)$r['message']) . '</td>';
	$content .= '</tr>';
}
if (empty($rows)) {
	$content .= '<tr><td colspan="5" class="text-muted">No logs found.</td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '</div></div>';

