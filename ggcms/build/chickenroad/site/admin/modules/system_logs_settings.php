<?php
/**
 * Logs: cleanup settings stored in variables.system_logs_cleanup_days
 */
$page_name = 'Logs: cleanup settings';

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
if (!$variables_exists) {
	$content = '<div class="alert alert-warning">Table <code>variables</code> not found. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

$key = 'system_logs_cleanup_days';
$row = mysql_select("SELECT value FROM `variables` WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
$days = $row && $row['value'] !== '' ? (int)$row['value'] : 30;
if ($days <= 0) $days = 30;

$saved = false;
if (!empty($_POST['save_logs_cleanup'])) {
	$days_new = isset($_POST['days']) ? (int)$_POST['days'] : 30;
	if ($days_new <= 0) $days_new = 30;
	$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
	if ($exists) mysql_fn('update', 'variables', array('value' => (string)$days_new), " AND `key` = '" . mysql_res($key) . "' ");
	else mysql_fn('insert', 'variables', array('key' => $key, 'value' => (string)$days_new));
	$days = $days_new;
	$saved = true;
}

$content = '<div class="card"><div class="card-body">';
$content .= '<h5 class="mb-2">System logs cleanup</h5>';
if ($saved) $content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
$content .= '<form method="post" class="row g-2 align-items-end">';
$content .= '<input type="hidden" name="save_logs_cleanup" value="1">';
$content .= '<div class="col-md-3"><label class="form-label">Days to keep</label><input class="form-control" type="number" min="1" name="days" value="' . (int)$days . '"></div>';
$content .= '<div class="col-md-9"><button class="btn btn-primary btn-sm" type="submit">Save</button></div>';
$content .= '</form>';
$content .= '</div></div>';

