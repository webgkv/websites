<?php
/**
 * Translations hub: tabs Overview | Review | Monitor | Batch (default: Review).
 * Settings & autopilot: separate module translations_settings.
 */
$page_name = 'Translations';
define('TRANSLATIONS_HUB', true);
require_once ROOT_DIR . 'functions/translation_hub.php';

if (!translation_hub_access()) {
	http_response_code(403);
	$content = '<div class="alert alert-danger">Access denied.</div>';
	return;
}

$u = isset($get['u']) ? (string)$get['u'] : '';
// JSON / live views must run inside the same modules (payload handlers).
if ($u === 'cluster_live_poll') {
	require_once ROOT_DIR . 'admin/modules/translations_review.php';
	exit;
}
if ($u === 'candidate_live_poll' || $u === 'candidate_live') {
	require_once ROOT_DIR . 'admin/modules/translations_monitor.php';
	exit;
}
$tab = isset($get['tab']) ? (string)$get['tab'] : 'review';
if (!in_array($tab, array('overview', 'review', 'monitor', 'batch', 'activity'), true)) {
	$tab = 'review';
}

$content = '';
switch ($tab) {
	case 'overview':
		require ROOT_DIR . 'admin/modules/translate_stats.php';
		break;
	case 'review':
		require ROOT_DIR . 'admin/modules/translations_review.php';
		break;
	case 'monitor':
		require ROOT_DIR . 'admin/modules/translations_monitor.php';
		break;
	case 'batch':
		require ROOT_DIR . 'admin/modules/translations_batch.php';
		break;
	case 'activity':
		require ROOT_DIR . 'admin/modules/translations_activity.php';
		break;
}

// After tab content: tabs + autopilot strip (must not be overwritten by submodules).
$page_header_extra = translation_hub_render_nav_tabs($tab);
$page_header_extra .= translation_hub_render_autopilot_strip();

$page_name = 'Translations';
