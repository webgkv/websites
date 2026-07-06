<?php
/**
 * Enqueue and run background tasks stored in admin_jobs.
 */

/**
 * Merge translation job tuning from variables.translation_settings (optional keys).
 */
function admin_translation_payload_merge_settings($payload) {
	if (!is_array($payload)) {
		return $payload;
	}
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return $payload;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') {
		return $payload;
	}
	$dec = json_decode((string)$row['value'], true);
	if (!is_array($dec)) {
		return $payload;
	}
	foreach (array('content_chunk_cap', 'bisect_max_depth', 'bisect_min_chars', 'english_leak_min_words') as $k) {
		if (!array_key_exists($k, $payload) && isset($dec[$k])) {
			$iv = (int)$dec[$k];
			if ($iv > 0) {
				$payload[$k] = $iv;
			}
		}
	}
	if (!array_key_exists('english_leak_max_retries', $payload) && isset($dec['english_leak_max_retries'])) {
		$payload['english_leak_max_retries'] = max(0, min(3, (int)$dec['english_leak_max_retries']));
	}
	if (!array_key_exists('english_leak_retry', $payload) && array_key_exists('english_leak_retry', $dec)) {
		$payload['english_leak_retry'] = !empty($dec['english_leak_retry']) ? 1 : 0;
	}
	return $payload;
}

function admin_jobs_enqueue($module, $action, $payload, $options = array()) {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return false;
	}
	if ($module === 'translations' && ($action === 'translate' || $action === 'translate_common_dict' || $action === 'translate_cluster' || $action === 'repair_locale') && is_array($payload)) {
		$payload = admin_translation_payload_merge_settings($payload);
	}
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	$payload_str = is_array($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : (string)$payload;
	$ins = array(
		'module' => substr((string)$module, 0, 64),
		'action' => substr((string)$action, 0, 64),
		'payload' => $payload_str,
		'status' => 'pending',
		'created_at' => $now,
		'updated_at' => $now,
	);
	$ins['scheduled_at'] = isset($options['scheduled_at']) ? $options['scheduled_at'] : $now;
	$ins['priority'] = isset($options['priority']) ? (int)$options['priority'] : 0;
	$ins['max_attempts'] = isset($options['max_attempts']) ? (int)$options['max_attempts'] : 3;
	return mysql_fn('insert', 'admin_jobs', $ins);
}

/**
 * Use MySQL clock for delayed scheduling so scheduled_at matches NOW() in lock_next (PHP date() can skew vs DB TZ).
 *
 * @param int $seconds delay from MySQL NOW() (0–3600)
 */
function admin_jobs_mysql_schedule_delay_seconds($seconds = 20) {
	$seconds = max(0, min(3600, (int)$seconds));
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return date('Y-m-d H:i:s', time() + $seconds);
	}
	$row = mysql_select("SELECT DATE_ADD(NOW(), INTERVAL " . $seconds . " SECOND) AS t", 'row');
	return $row && isset($row['t']) ? (string)$row['t'] : date('Y-m-d H:i:s', time() + $seconds);
}

/**
 * Fail pending validate_cluster rows for one cluster so cluster_pipeline can replace them (avoids duplicate work).
 *
 * @return int rows updated
 */
function admin_jobs_supersede_pending_validate_cluster_for_cluster($entity, $entity_id) {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return 0;
	}
	$ent = mysql_res($entity);
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	$msg = 'Superseded by cluster_pipeline (autopilot)';
	$rows = mysql_select("
		SELECT id
		FROM admin_jobs
		WHERE module='translations'
		  AND action='validate_cluster'
		  AND status='pending'
		  AND payload LIKE '%\"entity\":\"" . $ent . "\"%'
		  AND payload LIKE '%\"entity_id\":" . $entity_id . "%'
		LIMIT 100
	", 'rows') ?: array();
	$n = 0;
	foreach ($rows as $r) {
		$id = isset($r['id']) ? (int)$r['id'] : 0;
		if ($id <= 0) {
			continue;
		}
		mysql_fn('update', 'admin_jobs', array(
			'status' => 'failed',
			'message' => $msg,
			'finished_at' => $now,
			'updated_at' => $now,
		), " AND id=" . $id . " AND status='pending' ");
		$n++;
	}
	return $n;
}

/**
 * Fail pending translation jobs for one cluster (payload entity + entity_id).
 *
 * @return int rows updated
 */
function admin_jobs_fail_pending_translations_for_cluster($entity, $entity_id, $message = 'Cancelled by cluster action') {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return 0;
	}
	$ent = mysql_res($entity);
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	$msg = mb_substr((string)$message, 0, 500, 'UTF-8');
	$actions = array('translate', 'translate_cluster', 'validate_locale', 'repair_locale', 'validate_cluster', 'cluster_pipeline', 'metadata_normalize');
	$n = 0;
	foreach ($actions as $act) {
		$a = mysql_res($act);
		$rows = mysql_select("
			SELECT id FROM admin_jobs
			WHERE module='translations'
			  AND action='" . $a . "'
			  AND status='pending'
			  AND payload LIKE '%\"entity\":\"" . $ent . "\"%'
			  AND payload LIKE '%\"entity_id\":" . $entity_id . "%'
			LIMIT 200
		", 'rows') ?: array();
		foreach ($rows as $r) {
			$id = isset($r['id']) ? (int)$r['id'] : 0;
			if ($id <= 0) {
				continue;
			}
			mysql_fn('update', 'admin_jobs', array(
				'status' => 'failed',
				'message' => $msg,
				'finished_at' => $now,
				'updated_at' => $now,
			), " AND id=" . $id . " AND status='pending' ");
			$n++;
		}
	}
	return $n;
}

/**
 * Pending jobs scheduled absurdly far in the future never run; bump to NOW() so the worker can pick them up.
 *
 * @return int rows updated
 */
function admin_jobs_unstick_absurd_future_scheduled_pending($limit = 25) {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$limit = max(1, min(100, (int)$limit));
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	// Translation jobs only use short delays (+20s). Anything far in the future blocks the worker (scheduled_at <= NOW()).
	// Non-translation: only bump if absurd (>1h) to avoid surprising other modules.
	$ids = mysql_select("
		SELECT id
		FROM admin_jobs
		WHERE status='pending'
		  AND scheduled_at IS NOT NULL
		  AND scheduled_at > NOW()
		  AND (
			(module='translations' AND scheduled_at > DATE_ADD(NOW(), INTERVAL 5 MINUTE))
			OR (IFNULL(module,'') <> 'translations' AND scheduled_at > DATE_ADD(NOW(), INTERVAL 1 HOUR))
		  )
		ORDER BY id ASC
		LIMIT " . $limit . "
	", 'rows') ?: array();
	$n = 0;
	foreach ($ids as $r) {
		$id = isset($r['id']) ? (int)$r['id'] : 0;
		if ($id <= 0) {
			continue;
		}
		mysql_fn('update', 'admin_jobs', array(
			'scheduled_at' => $now,
			'updated_at' => $now,
		), " AND id=" . $id . " AND status='pending' ");
		$n++;
	}
	return $n;
}

/**
 * Mark long-running admin_jobs as failed (frees queue). Updates translation_order_candidates when linked.
 *
 * @param int $stale_seconds threshold in seconds
 * @param int $limit max rows per call
 * @param array<string,mixed> $options basis: started_at (default) = wall time since job start; heartbeat = time since last updated_at (translations only, chunk-aware)
 * @return int number of jobs reaped
 */
function admin_jobs_reap_stale_running_jobs($stale_seconds, $limit = 20, array $options = array()) {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$basis = isset($options['basis']) ? (string)$options['basis'] : 'started_at';
	if ($basis !== 'heartbeat' && $basis !== 'started_at') {
		$basis = 'started_at';
	}
	$stale_seconds = (int)$stale_seconds;
	if ($basis === 'heartbeat') {
		// Must stay above a single LLM HTTP attempt (~55s curl) so we do not reap mid-request.
		$stale_seconds = max(60, min(1200, $stale_seconds));
	} else {
		$stale_seconds = max(60, $stale_seconds);
	}
	$limit = max(1, min(100, (int)$limit));
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');

	if ($basis === 'heartbeat') {
		$stale_jobs = mysql_select("
			SELECT id, module, action, started_at, payload
			FROM admin_jobs
			WHERE status='running'
			  AND module='translations'
			  AND started_at IS NOT NULL
			  AND TIMESTAMPDIFF(SECOND, COALESCE(updated_at, started_at), NOW()) > " . (int)$stale_seconds . "
			ORDER BY id DESC
			LIMIT " . (int)$limit . "
		", 'rows') ?: array();
		$reason_label = 'heartbeat ' . (int)$stale_seconds . 's';
	} else {
		$stale_jobs = mysql_select("
			SELECT id, module, action, started_at, payload
			FROM admin_jobs
			WHERE status='running'
			  AND started_at IS NOT NULL
			  AND TIMESTAMPDIFF(SECOND, started_at, NOW()) > " . (int)$stale_seconds . "
			ORDER BY id DESC
			LIMIT " . (int)$limit . "
		", 'rows') ?: array();
		$reason_label = 'total run ' . (int)$stale_seconds . 's';
	}

	$n = 0;
	foreach ($stale_jobs as $sj) {
		$jid = (int)($sj['id'] ?? 0);
		if ($jid <= 0) {
			continue;
		}
		$payload = isset($sj['payload']) ? @json_decode((string)$sj['payload'], true) : null;
		$candidate_id = is_array($payload) && isset($payload['candidate_id']) ? (int)$payload['candidate_id'] : 0;
		$order_id = is_array($payload) && isset($payload['order_id']) ? (int)$payload['order_id'] : 0;

		if (isset($sj['module'], $sj['action']) && (string)$sj['module'] === 'translations' && (string)$sj['action'] === 'translate' && $candidate_id > 0) {
			$cur = mysql_select("SELECT candidate_status FROM translation_order_candidates WHERE id=" . (int)$candidate_id . " LIMIT 1", 'row');
			mysql_fn('update', 'translation_order_candidates', array(
				'candidate_status' => 'failed',
				'i18n_status' => 'missing',
				'last_error' => 'Reaped stale running admin_job (' . $reason_label . ')',
				'updated_at' => $now,
			), " AND id=" . (int)$candidate_id . " ");

			if ($order_id > 0 && (!isset($cur['candidate_status']) || (string)$cur['candidate_status'] !== 'failed')) {
				$col = 'failed_count';
				mysql_fn('query', "UPDATE translation_orders SET `{$col}` = `{$col}` + 1, updated_at = '" . mysql_res($now) . "' WHERE id = " . (int)$order_id);
				$ord = mysql_select("SELECT total_candidates, translated_count, failed_count FROM translation_orders WHERE id=" . (int)$order_id . " LIMIT 1", 'row');
				if ($ord && ((int)$ord['translated_count'] + (int)$ord['failed_count']) >= (int)$ord['total_candidates']) {
					mysql_fn('update', 'translation_orders', array('status' => 'completed', 'updated_at' => $now), " AND id=" . (int)$order_id . " ");
				}
			}
		}

		mysql_fn('update', 'admin_jobs', array(
			'status' => 'failed',
			'message' => 'Reaped stale running admin_job (' . $reason_label . ')',
			'finished_at' => $now,
			'updated_at' => $now,
		), " AND id=" . (int)$jid . " ");
		$n++;
		if (function_exists('system_log_add')) {
			$pref = 'job#' . (int)$jid . ' cand#' . (int)$candidate_id;
			system_log_add('translations', 'error', 'Stale running job reaped (' . $pref . ')', array(
				'job_id' => (int)$jid,
				'candidate_id' => (int)$candidate_id,
				'module' => (string)($sj['module'] ?? ''),
				'action' => (string)($sj['action'] ?? ''),
				'started_at' => (string)($sj['started_at'] ?? ''),
				'reap_basis' => $basis,
				'stale_seconds' => (int)$stale_seconds,
			));
		}
	}
	return $n;
}

/**
 * Heartbeat threshold: no admin_jobs `updated_at` refresh for this long ⇒ reap (chunk/LLM stuck).
 *
 * @return int seconds
 */
function admin_jobs_translation_reap_heartbeat_seconds() {
	static $sec = null;
	if ($sec !== null) {
		return (int)$sec;
	}
	$sec = 240;
	if (file_exists(ROOT_DIR . 'functions/translation_autopilot.php')) {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		if (function_exists('translation_autopilot_load_cfg')) {
			$apc = translation_autopilot_load_cfg();
			$h = isset($apc['translation_reap_heartbeat_seconds']) ? (int)$apc['translation_reap_heartbeat_seconds'] : 240;
			$sec = max(60, min(1200, $h));
		}
	}
	return (int)$sec;
}

/**
 * Total wall time since job start — safety net (long but bounded).
 *
 * @return int seconds
 */
function admin_jobs_translation_reap_total_seconds() {
	static $sec = null;
	if ($sec !== null) {
		return (int)$sec;
	}
	$sec = 3600;
	if (file_exists(ROOT_DIR . 'functions/translation_autopilot.php')) {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
		if (function_exists('translation_autopilot_load_cfg')) {
			$apc = translation_autopilot_load_cfg();
			$t = isset($apc['translation_reap_total_seconds']) ? (int)$apc['translation_reap_total_seconds'] : 3600;
			$sec = max(600, min(86400, $t));
		}
	}
	return (int)$sec;
}

/**
 * @deprecated Use admin_jobs_translation_reap_heartbeat_seconds / admin_jobs_translation_reap_total_seconds.
 * @return int same as total seconds (for older telemetry call sites)
 */
function admin_jobs_translation_reap_stale_seconds() {
	return admin_jobs_translation_reap_total_seconds();
}

function admin_jobs_lock_next($filters = array()) {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) return null;
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');

	// Reap: (1) translations with stale heartbeat — aligns with chunk work + admin_jobs_touch between chunks.
	// (2) any job over total wall-time cap.
	admin_jobs_reap_stale_running_jobs(admin_jobs_translation_reap_heartbeat_seconds(), 20, array('basis' => 'heartbeat'));
	admin_jobs_reap_stale_running_jobs(admin_jobs_translation_reap_total_seconds(), 20, array('basis' => 'started_at'));
	admin_jobs_unstick_absurd_future_scheduled_pending(25);

	$where = array(
		"status='pending'",
		"(scheduled_at IS NULL OR scheduled_at <= NOW())",
	);
	if (is_array($filters)) {
		if (!empty($filters['module'])) {
			$where[] = "module='" . mysql_res((string)$filters['module']) . "'";
		}
		if (!empty($filters['action'])) {
			$where[] = "action='" . mysql_res((string)$filters['action']) . "'";
		}
		// Narrow by JSON payload (cluster_pipeline / drain helpers).
		if (!empty($filters['cluster_entity']) && isset($filters['cluster_entity_id'])) {
			$ent = mysql_res((string)$filters['cluster_entity']);
			$eid = (int)$filters['cluster_entity_id'];
			$where[] = "payload LIKE '%\"entity\":\"" . $ent . "\"%'";
			$where[] = "payload LIKE '%\"entity_id\":" . $eid . "%'";
		}
		if (!empty($filters['cluster_actions']) && is_array($filters['cluster_actions'])) {
			$acts = array();
			foreach ($filters['cluster_actions'] as $a) {
				$a = trim((string)$a);
				if ($a !== '') {
					$acts[] = "'" . mysql_res($a) . "'";
				}
			}
			if ($acts !== array()) {
				$where[] = 'action IN (' . implode(',', $acts) . ')';
			}
		}
	}
	$where_sql = implode("\n\t\t  AND ", $where);

	$job = mysql_select("
		SELECT *
		FROM admin_jobs
		WHERE " . $where_sql . "
		ORDER BY
		  priority DESC,
		  CASE
			WHEN module='translations' AND action='translate' THEN 0
			WHEN module='translations' AND action='repair_locale' THEN 1
			WHEN module='translations' AND action='translate_common_dict' THEN 2
			WHEN module='translations' AND action='validate_locale' THEN 3
			WHEN module='translations' AND action='translate_cluster' THEN 4
			WHEN module='translations' AND action='validate_cluster' THEN 5
			WHEN module='translations' AND action='cluster_pipeline' THEN 6
			ELSE 10
		  END ASC,
		  id ASC
		LIMIT 1
	", 'row');
	if (!$job) return null;
	// Try lock by status change
	mysql_fn('update', 'admin_jobs', array(
		'status' => 'running',
		'locked_at' => $now,
		'started_at' => $now,
		'updated_at' => $now,
	), " AND id = " . (int)$job['id'] . " AND status='pending' ");
	$locked = mysql_select("SELECT * FROM admin_jobs WHERE id = " . (int)$job['id'] . " LIMIT 1", 'row');
	if (!$locked || $locked['status'] !== 'running') return null;
	return $locked;
}

function admin_jobs_finish($id, $ok, $message) {
	$id = (int)$id;
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	$status = $ok ? 'done' : 'failed';
	mysql_fn('update', 'admin_jobs', array(
		'status' => $status,
		'message' => (string)$message,
		'finished_at' => $now,
		'updated_at' => $now,
	), " AND id = " . $id . " ");
}

/**
 * Heartbeat: update running job message + updated_at.
 * Useful for UI visibility and for detecting stuck workers.
 * @param int $id
 * @param string $message
 * @return void
 */
function admin_jobs_touch($id, $message = '') {
	$id = (int)$id;
	if ($id <= 0) return;
	$row = @mysql_select("SELECT NOW() AS t", 'row');
	$now = $row ? $row['t'] : date('Y-m-d H:i:s');
	$upd = array('updated_at' => $now);
	if ($message !== '') $upd['message'] = substr((string)$message, 0, 240);
	mysql_fn('update', 'admin_jobs', $upd, " AND id = " . (int)$id . " AND status='running' ");
}

