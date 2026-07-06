<?php
/**
 * admin_jobs handlers: seo_monitor
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', __DIR__ . '/');
}

/**
 * Recompute SEO overview aggregate for one entity and store in variables cache.
 *
 * @param array $payload
 * @param array $job
 * @return array{ok:bool,message:string}
 */
function run_seo_monitor_rebuild_entity($payload, $job) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$map = seo_monitor_entity_map();
	if (!isset($map[$entity])) {
		return array('ok' => false, 'message' => 'Bad entity');
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
		return array('ok' => false, 'message' => 'content_i18n not found');
	}
	if (function_exists('set_time_limit')) {
		@set_time_limit(0);
	}
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	$ref = null;
	$r = seo_monitor_rebuild_entity_chunked($entity, $job_id, $ref);
	if (empty($r['ok'])) {
		return array('ok' => false, 'message' => isset($r['message']) ? (string)$r['message'] : 'Rebuild failed');
	}
	$agg = $r['agg'];
	return array(
		'ok' => true,
		'message' => 'SEO cache updated for ' . $entity . ' (' . (int)$agg['rows'] . ' rows, ' . (int)$agg['relevant'] . ' cells).',
	);
}

/**
 * Rebuild overview cache for every non-empty segment (chunked; progress in admin_jobs.message).
 *
 * @param array $payload
 * @param array $job
 * @return array{ok:bool,message:string}
 */
function run_seo_monitor_rebuild_overview($payload, $job) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
		return array('ok' => false, 'message' => 'content_i18n not found');
	}
	if (function_exists('set_time_limit')) {
		@set_time_limit(0);
	}
	$entities = seo_monitor_overview_rebuild_entity_keys();
	$global_total = 0;
	foreach ($entities as $ent) {
		$global_total += seo_monitor_validation_included_row_count($ent);
	}
	if ($global_total <= 0) {
		return array('ok' => true, 'message' => 'No rows in validation scope (empty or all excluded).');
	}
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	$progress = array('done' => 0, 'total' => $global_total);
	$n_ent = 0;
	foreach ($entities as $ent) {
		$r = seo_monitor_rebuild_entity_chunked($ent, $job_id, $progress);
		if (empty($r['ok'])) {
			return array('ok' => false, 'message' => isset($r['message']) ? (string)$r['message'] : 'Rebuild failed at ' . $ent);
		}
		$n_ent++;
	}
	return array(
		'ok' => true,
		'message' => 'SEO overview rebuilt (' . $n_ent . ' segment(s), ' . $global_total . ' row(s)).',
	);
}
