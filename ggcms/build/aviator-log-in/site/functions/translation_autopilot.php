<?php
/**
 * Translation autopilot: enqueues admin_jobs only; translation runs via standard job_runner.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/** Bump when changing autopilot queue / blocking logic so telemetry can confirm prod revision. */
if (!defined('TRANSLATION_AUTOPILOT_BUILD')) {
	define('TRANSLATION_AUTOPILOT_BUILD', '20260415g');
}

/**
 * Defaults merged with variables.translation_settings JSON.
 * @return array
 */
function translation_autopilot_defaults() {
	return array(
		'autopilot_enabled' => 0,
		'autopilot_stuck_seconds' => 900,
		// Reap running translation jobs if no admin_jobs heartbeat (updated_at) for this long — chunk/LLM work should touch often.
		'translation_reap_heartbeat_seconds' => 240,
		// Absolute max time a running translation job may hold the worker (safety net).
		'translation_reap_total_seconds' => 3600,
		'autopilot_max_jobs_per_run' => 20,
		// Drain queue after each autopilot tick; capped by autopilot_cron_max_wall_seconds (cron only).
		'autopilot_process_jobs_per_tick' => 6,
		// Stop processing more jobs in one cron run after this many seconds (avoids overlapping crons / runaway RAM).
		'autopilot_cron_max_wall_seconds' => 360,
		'autopilot_max_pending_skip' => 50,
		'autopilot_blog_batch' => 10,
		'autopilot_cluster_mode' => 1,
		'autopilot_translate_pending_cap' => 24,
		'autopilot_cluster_child_batch' => 6,
		// Active cluster: enqueue cluster_pipeline (drains children + validate in one job) instead of validate_cluster only.
		'autopilot_use_cluster_pipeline' => 1,
		'autopilot_cluster_pipeline_max_seconds' => 900,
		// Above default admin_jobs priority (0) and autopilot translate (-5) so orchestration is not starved.
		'autopilot_cluster_pipeline_priority' => 2,
		// Segment JSON: batch sizes (dense scripts = smaller batches, fewer completion tokens per request).
		'segment_json_batch_size' => 28,
		'segment_json_batch_size_dense' => 10,
		// If non-empty, only these lang_id values use dense batch size (otherwise auto-detect by language url/name).
		'segment_json_dense_lang_ids' => array(),
		'autopilot_respect_monitor' => 1,
		// Clusters fully loaded via SEO Monitor (all scope locales) get translation_cluster_state.seo_monitor_handoff=1:
		// autopilot skips translate_cluster / pipeline / meta-fix for them; they stay validated like everyone else but are treated as manual reference (vector RAG approved pairs).
		'autopilot_blog_cursor_lang_id' => 0,
		'autopilot_locale_ids' => array(),
		// Draft/review: re-translate name/title/description from source when metadata heuristics fail.
		'autopilot_meta_fix_max_per_run' => 6,
		'autopilot_meta_fix_scan_per_table' => 40,
		'autopilot_meta_fix_cooldown_days' => 7,
		'autopilot_meta_fix_max_lifetime_runs' => 5,
		// Count full content translate jobs (entity/blog); after this many, next tick prioritizes draft meta-fix first.
		'autopilot_normal_jobs_before_draft_meta' => 10,
		'autopilot_normals_since_draft_meta' => 0,
		// entity => list of row ids to skip (full translate + meta-fix), e.g. pages home: array('pages' => array(1))
		'autopilot_exclude' => array(),
		// Same section keys as SEO Sitemap: pages, blog, guides, games, casinos (casinos = casino_articles entity).
		'autopilot_include' => array(
			'pages' => 1,
			'blog' => 1,
			'guides' => 1,
			'games' => 1,
			'casinos' => 1,
		),
		// When validate_cluster finishes, set all content_i18n rows to published for ready_to_publish or needs_review with no blockers (autopilot jobs only).
		'autopilot_cluster_autopublish' => 1,
		// Cluster validation: full SEO Monitor on HTML (H1, img alt, body) — stored in translation_settings JSON, read by translation_cluster_validation_seo_full().
		'cluster_validation_seo_full' => 0,
		// Translations → Activity: delete older system_logs (channel translations) and completed activity jobs each cron tick.
		'autopilot_activity_retention_days' => 7,
	);
}

/**
 * Normalize autopilot_include from JSON (checkbox keys match admin Sitemap Sections).
 *
 * @param array<string,mixed> $raw
 * @return array{pages:int,blog:int,guides:int,games:int,casinos:int}
 */
function translation_autopilot_normalize_include($raw) {
	$def = array(
		'pages' => 1,
		'blog' => 1,
		'guides' => 1,
		'games' => 1,
		'casinos' => 1,
	);
	if (!is_array($raw)) {
		return $def;
	}
	foreach (array_keys($def) as $k) {
		if (array_key_exists($k, $raw)) {
			$def[$k] = (int)(bool)$raw[$k];
		}
	}
	return $def;
}

/**
 * @param array{pages:int,blog:int,guides:int,games:int,casinos:int} $inc
 */
function translation_autopilot_section_enabled($entity, array $inc) {
	$entity = (string)$entity;
	$m = array(
		'pages' => 'pages',
		'guides' => 'guides',
		'games' => 'games',
		'casino_articles' => 'casinos',
		'blog' => 'blog',
	);
	if (!isset($m[$entity])) {
		return true;
	}
	return !empty($inc[$m[$entity]]);
}

/**
 * Allowed autopilot entity keys (sync with translation_autopilot_run).
 *
 * @return array<string,bool>
 */
function translation_autopilot_exclude_entity_whitelist() {
	return array(
		'pages' => true,
		'guides' => true,
		'games' => true,
		'casino_articles' => true,
		'blog' => true,
	);
}

/**
 * @param array<string,mixed> $cfg
 * @param string $entity
 * @return int[]
 */
function translation_autopilot_exclude_ids_for_entity(array $cfg, $entity) {
	$entity = (string)$entity;
	$raw = isset($cfg['autopilot_exclude']) ? $cfg['autopilot_exclude'] : array();
	if (!is_array($raw) || $entity === '' || !isset($raw[$entity])) {
		return array();
	}
	$ids = $raw[$entity];
	if (!is_array($ids)) {
		return array();
	}
	$out = array();
	foreach ($ids as $id) {
		$i = (int)$id;
		if ($i > 0) {
			$out[$i] = true;
		}
	}
	return array_keys($out);
}

/**
 * Parse textarea lines "entity:id" or "entity#id" (same as admin review notation) into autopilot_exclude.
 *
 * @return array<string,int[]>
 */
function translation_autopilot_parse_exclude_text($text) {
	$allow = translation_autopilot_exclude_entity_whitelist();
	$acc = array();
	$lines = preg_split('/\R/u', (string)$text);
	if (!is_array($lines)) {
		return array();
	}
	foreach ($lines as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		if (!preg_match('/^([a-z][a-z0-9_]*)\s*[#:]\s*(\d+)\s*$/i', $line, $m)) {
			continue;
		}
		$ent = strtolower($m[1]);
		if (!isset($allow[$ent])) {
			continue;
		}
		$eid = (int)$m[2];
		if ($eid <= 0) {
			continue;
		}
		if (!isset($acc[$ent])) {
			$acc[$ent] = array();
		}
		$acc[$ent][$eid] = true;
	}
	$out = array();
	foreach ($acc as $ent => $map) {
		$ids = array_keys($map);
		sort($ids, SORT_NUMERIC);
		$out[$ent] = $ids;
	}
	return $out;
}

/**
 * @param array<string,int[]> $exclude
 * @return string
 */
function translation_autopilot_exclude_text_from_array($exclude) {
	if (!is_array($exclude) || $exclude === array()) {
		return '';
	}
	$allow = translation_autopilot_exclude_entity_whitelist();
	$lines = array();
	$keys = array_keys($exclude);
	sort($keys);
	foreach ($keys as $ent) {
		if (!isset($allow[$ent]) || !is_array($exclude[$ent])) {
			continue;
		}
		$ids = array_map('intval', $exclude[$ent]);
		$ids = array_values(array_unique(array_filter($ids)));
		sort($ids, SORT_NUMERIC);
		foreach ($ids as $id) {
			if ($id > 0) {
				$lines[] = $ent . ':' . $id;
			}
		}
	}
	return implode("\n", $lines);
}

/**
 * @return array<string,mixed>
 */
function translation_autopilot_load_cfg() {
	$defaults = translation_autopilot_defaults();
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') {
		return $defaults;
	}
	$dec = json_decode((string)$row['value'], true);
	if (!is_array($dec)) {
		return $defaults;
	}
	$out = array_merge($defaults, $dec);
	$out['autopilot_include'] = translation_autopilot_normalize_include(isset($out['autopilot_include']) ? $out['autopilot_include'] : array());
	return $out;
}

/** @return void */
function translation_autopilot_save_blog_cursor($lang_id) {
	$key = 'translation_settings';
	$row = mysql_select("SELECT id, value FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
	if (!$row) {
		return;
	}
	$dec = json_decode((string)$row['value'], true);
	if (!is_array($dec)) {
		$dec = array();
	}
	$dec['autopilot_blog_cursor_lang_id'] = (int)$lang_id;
	$json = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	mysql_fn('update', 'variables', array('value' => $json), " AND id=" . (int)$row['id'] . " ");
}

/** @return void */
function translation_autopilot_save_normals_since_draft_meta($n) {
	$key = 'translation_settings';
	$row = mysql_select("SELECT id, value FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
	if (!$row) {
		return;
	}
	$dec = json_decode((string)$row['value'], true);
	if (!is_array($dec)) {
		$dec = array();
	}
	$dec['autopilot_normals_since_draft_meta'] = max(0, (int)$n);
	$json = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	mysql_fn('update', 'variables', array('value' => $json), " AND id=" . (int)$row['id'] . " ");
}

function translation_autopilot_table_display_where($table) {
	$cols = mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "`", 'rows');
	$has_display = false;
	if ($cols) {
		foreach ($cols as $c) {
			if (isset($c['Field']) && (string)$c['Field'] === 'display') {
				$has_display = true;
				break;
			}
		}
	}
	return $has_display ? ' AND t.display=1 ' : '';
}

/**
 * Datetime columns on entity table used to detect edits in the canonical (main) row.
 * @return string[] column names among updated_at, created_at, date
 */
function translation_autopilot_table_ts_columns($table) {
	static $cache = array();
	$tkey = (string)$table;
	if (isset($cache[$tkey])) {
		return $cache[$tkey];
	}
	$candidates = array('updated_at', 'created_at', 'date');
	$cols = mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "`", 'rows');
	$have = array();
	if ($cols) {
		foreach ($cols as $c) {
			if (isset($c['Field'])) {
				$have[(string)$c['Field']] = true;
			}
		}
	}
	$out = array();
	foreach ($candidates as $c) {
		if (!empty($have[$c])) {
			$out[] = $c;
		}
	}
	$cache[$tkey] = $out;
	return $out;
}

/**
 * SQL fragments for stale checks: cisrc_ts vs target_ts (translation row), plus entity_ts from base table t.*.
 *
 * @return array{cisrc_ts:string,target_ts:string,entity_ts:string}
 */
function translation_autopilot_sql_stale_source_parts($table) {
	$table = (string)$table;
	$cisrc_ts = 'GREATEST(COALESCE(UNIX_TIMESTAMP(cisrc.updated_at), 0), COALESCE(UNIX_TIMESTAMP(cisrc.created_at), 0))';
	$t_parts = array();
	foreach (translation_autopilot_table_ts_columns($table) as $col) {
		$t_parts[] = 'COALESCE(UNIX_TIMESTAMP(t.`' . mysql_res($col) . '`), 0)';
	}
	$t_g = $t_parts ? 'GREATEST(' . implode(', ', $t_parts) . ')' : '0';
	$target_ts = 'COALESCE(UNIX_TIMESTAMP(ci.updated_at), 0)';
	return array(
		'cisrc_ts' => $cisrc_ts,
		'target_ts' => $target_ts,
		'entity_ts' => $t_g,
	);
}

/**
 * JOIN exactly one content_i18n row per entity row (same ordering as admin_i18n_get / admin_i18n_save).
 * Required when duplicate rows exist for the same entity+lang — otherwise JOIN picks an arbitrary row and stale detection lies.
 *
 * @param string $entity content_i18n.entity
 * @param int    $lang_id
 * @param string $alias   SQL alias [A-Za-z_]+
 */
function translation_autopilot_join_primary_ci($entity, $lang_id, $alias = 'ci') {
	$entity = mysql_res((string)$entity);
	$lang_id = (int)$lang_id;
	$alias = (string)$alias;
	if ($alias === '' || !preg_match('/^[A-Za-z_]+$/', $alias)) {
		$alias = 'ci';
	}
	$ord = "FIELD(c2.status,'published','review','draft','missing') ASC, c2.id DESC";
	return 'LEFT JOIN content_i18n `' . $alias . '` ON `' . $alias . '`.id = (
		SELECT c2.id FROM content_i18n c2
		WHERE c2.entity=\'' . $entity . '\'
		  AND c2.entity_id = t.id
		  AND c2.lang_id = ' . $lang_id . '
		ORDER BY ' . $ord . '
		LIMIT 1
	)';
}

/**
 * SQL predicate: content_i18n row has no usable title/body yet (first-pass translate needed).
 * Draft/review rows that already have name + enough body are excluded so autopilot does not
 * re-enqueue full translates on every tick (manual edits stay until source changes — see stale_cond).
 *
 * @param string $alias content_i18n alias [A-Za-z_]+
 */
function translation_autopilot_ci_needs_initial_fill_sql($alias = 'ci') {
	$a = (string)$alias;
	if ($a === '' || !preg_match('/^[A-Za-z_]+$/', $a)) {
		$a = 'ci';
	}
	$c = 'COALESCE(' . $a . '.content,\'\')';
	$n = 'COALESCE(' . $a . '.name,\'\')';
	return '('
		. 'TRIM(' . $n . ") = '' OR CHAR_LENGTH(TRIM(" . $c . ')) < 40'
		. ')';
}

function translation_autopilot_monitored_common_keys($source_lang_id) {
	$source_lang_id = (int)$source_lang_id;
	$ref = admin_load_common_dict($source_lang_id);
	$keys = array_keys($ref);
	$extra = array(
		'read_guide', 'read_more', 'guides_cat_all', 'guides_title', 'games_title', 'games_cat_all',
		'authors_title', 'author_byline_prefix', 'author_references_title',
		'games_cat_crash', 'games_cat_crash-p2e', 'games_cat_other', 'guides_cat_analysis', 'guides_cat_bonus',
		'guides_cat_how-to-win', 'guides_cat_signals', 'guides_cat_crash-gambling', 'hero_subtitle', 'hero_cta',
		'cta_play_now', 'cta_try_bonus', 'predictor_menu', 'popup_special_offer', 'index_page', 'breadcrumb_index', 'breadcrumb_separator',
	);
	foreach ($extra as $k) {
		$keys[] = $k;
	}
	$keys = array_values(array_unique($keys));
	sort($keys);
	return $keys;
}

/**
 * @return array<string,bool> keys like "blog:5:3" or "common_dict:3"
 */
function translation_autopilot_inflight_keys() {
	$out = array();
	$rows = mysql_select("
		SELECT payload, action
		FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('translate','translate_common_dict','translate_cluster','validate_locale','repair_locale','validate_cluster','cluster_pipeline')
		  AND status IN ('pending','running')
		LIMIT 500
	", 'rows') ?: array();
	foreach ($rows as $r) {
		$p = isset($r['payload']) ? @json_decode((string)$r['payload'], true) : null;
		if (!is_array($p)) {
			continue;
		}
		$act = isset($r['action']) ? (string)$r['action'] : '';
		if ($act === 'translate_cluster') {
			$ent = isset($p['entity']) ? (string)$p['entity'] : '';
			$eid = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
			if ($ent !== '' && $eid > 0) {
				$out['cluster:' . $ent . ':' . $eid] = true;
			}
			continue;
		}
		if ($act === 'validate_cluster' || $act === 'cluster_pipeline') {
			$ent = isset($p['entity']) ? (string)$p['entity'] : '';
			$eid = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
			if ($ent !== '' && $eid > 0) {
				$out['cluster:' . $ent . ':' . $eid] = true;
			}
			continue;
		}
		if ($act === 'translate_common_dict') {
			$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
			if ($dst > 0) {
				$out['common_dict:' . $dst] = true;
			}
			continue;
		}
		$ent = isset($p['entity']) ? (string)$p['entity'] : '';
		$eid = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		if ($ent !== '' && $eid > 0 && $dst > 0) {
			$out[$ent . ':' . $eid . ':' . $dst] = true;
			if (!empty($p['cluster_job']) || !empty($p['autopilot'])) {
				$out['cluster:' . $ent . ':' . $eid] = true;
			}
		}
	}
	return $out;
}

function translation_autopilot_monitor_busy() {
	if (@mysql_select("SHOW TABLES LIKE 'translation_order_candidates'", 'num_rows') === 0) {
		return false;
	}
	$c = mysql_select("
		SELECT COUNT(*) AS c
		FROM translation_order_candidates
		WHERE candidate_status IN ('queued','running')
	", 'row');
	return $c && (int)$c['c'] > 0;
}

function translation_autopilot_pending_translation_jobs_count() {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$c = mysql_select("
		SELECT COUNT(*) AS c
		FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('translate','translate_common_dict','translate_cluster','validate_locale','repair_locale','validate_cluster','cluster_pipeline')
		  AND status IN ('pending','running')
	", 'row');
	return $c ? (int)$c['c'] : 0;
}

/**
 * Pending jobs that must finish before autopilot starts another cluster pipeline pass.
 * Only counts **orchestrator** jobs (translate_cluster, cluster_pipeline). Per-cluster
 * validate_cluster / repair_locale / validate_locale must NOT block autopilot from advancing
 * other clusters — otherwise one stuck repair loop stalls the whole queue (queue_busy).
 * Excludes action=translate / translate_common_dict (e.g. metadata_normalize meta-fix).
 *
 * @return int
 */
function translation_autopilot_pending_cluster_blocking_jobs_count() {
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$c = mysql_select("
		SELECT COUNT(*) AS c
		FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('translate_cluster','cluster_pipeline')
		  AND status IN ('pending','running')
	", 'row');
	return $c ? (int)$c['c'] : 0;
}

/**
 * Orchestrator vs leaf job counts for telemetry / queue_busy diagnostics.
 *
 * @return array{blocking_total:int,by_action:array<string,int>,cluster_child_pending:int,translate_pending:int}
 */
function translation_autopilot_cluster_blocking_detail() {
	$empty = array(
		'blocking_total' => 0,
		'by_action' => array('translate_cluster' => 0, 'cluster_pipeline' => 0),
		'cluster_child_pending' => 0,
		'translate_pending' => 0,
	);
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return $empty;
	}
	$rows = mysql_select("
		SELECT action, COUNT(*) AS c
		FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('translate_cluster','cluster_pipeline')
		  AND status IN ('pending','running')
		GROUP BY action
	", 'rows') ?: array();
	$by = $empty['by_action'];
	$total = 0;
	foreach ($rows as $r) {
		$a = isset($r['action']) ? (string)$r['action'] : '';
		$c = isset($r['c']) ? (int)$r['c'] : 0;
		if ($a !== '' && array_key_exists($a, $by)) {
			$by[$a] = $c;
		}
		$total += $c;
	}
	$cc = mysql_select("
		SELECT COUNT(*) AS c FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('validate_cluster','validate_locale','repair_locale')
		  AND status IN ('pending','running')
	", 'row');
	$tp = mysql_select("
		SELECT COUNT(*) AS c FROM admin_jobs
		WHERE module='translations'
		  AND action='translate'
		  AND status IN ('pending','running')
	", 'row');
	return array(
		'blocking_total' => $total,
		'by_action' => $by,
		'cluster_child_pending' => $cc && isset($cc['c']) ? (int)$cc['c'] : 0,
		'translate_pending' => $tp && isset($tp['c']) ? (int)$tp['c'] : 0,
	);
}

/**
 * @return int[]
 */
function translation_autopilot_target_lang_ids(array $cfg) {
	$src = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
	$langs = mysql_select("SELECT id, url, name, rank FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
	$enabled = array();
	if (!empty($cfg['enabled_lang_ids']) && is_array($cfg['enabled_lang_ids'])) {
		foreach ($cfg['enabled_lang_ids'] as $lid) {
			$enabled[(int)$lid] = true;
		}
	}
	$allow = array();
	if (!empty($cfg['autopilot_locale_ids']) && is_array($cfg['autopilot_locale_ids'])) {
		foreach ($cfg['autopilot_locale_ids'] as $lid) {
			$i = (int)$lid;
			if ($i > 0) {
				$allow[$i] = true;
			}
		}
	}
	$out = array();
	foreach ($langs as $l) {
		$lid = (int)$l['id'];
		if ($lid === $src || $lid <= 0) {
			continue;
		}
		if (!empty($enabled) && !isset($enabled[$lid])) {
			continue;
		}
		if (!empty($allow) && !isset($allow[$lid])) {
			continue;
		}
		$out[] = $lid;
	}
	$seen = array();
	foreach ($out as $lid) {
		$seen[(int)$lid] = true;
	}
	$seen[$src] = true;
	$candidates = array();
	if (!empty($enabled)) {
		foreach (array_keys($enabled) as $k) {
			$candidates[(int)$k] = true;
		}
	} elseif (!empty($allow)) {
		foreach (array_keys($allow) as $k) {
			$candidates[(int)$k] = true;
		}
	}
	foreach (array_keys($candidates) as $tid) {
		if ($tid <= 0 || $tid === $src || isset($seen[$tid])) {
			continue;
		}
		if (!empty($enabled) && !isset($enabled[$tid])) {
			continue;
		}
		if (!empty($allow) && !isset($allow[$tid])) {
			continue;
		}
		$row = mysql_select("SELECT id FROM languages WHERE id=" . $tid . " LIMIT 1", 'row');
		if ($row) {
			$out[] = $tid;
			$seen[$tid] = true;
		}
	}
	return $out;
}

/**
 * Entity rows that need a translate job:
 * - no content_i18n row, missing status, or draft/review with empty/minimal fill;
 * - if source-resync enabled (non-blog): when source is newer than target updated_at (see below).
 * Published: autopilot never touches except one case — source-lang content_i18n (e.g. EN) row exists and its
 * last change is newer than the target translation row’s updated_at. No resync from base table t.* alone.
 * Stale / resync (all statuses): only if source-lang content_i18n (cisrc) exists and is newer than target ci,
 * OR (no cisrc row) non-published rows may resync from entity table t.* timestamps vs target.
 * Published without cisrc: never stale from t.* alone.
 * Blog: no stale signal — same draft/review rules without resync.
 *
 * @param int $src_lang_id source language id; 0 = legacy "missing only" behaviour
 * @param int[] $exclude_entity_ids row ids on t to skip
 * @return int[]
 */
function translation_autopilot_missing_entity_ids($entity, $table, $dst_lang_id, $limit, $order_sql, $src_lang_id = 0, array $exclude_entity_ids = array()) {
	if (!function_exists('translation_cluster_autopilot_freeze_exists_sql')) {
		require_once ROOT_DIR . 'functions/translation_cluster.php';
	}
	$entity = mysql_res($entity);
	$table = mysql_res($table);
	$dst_lang_id = (int)$dst_lang_id;
	$limit = max(1, min(200, (int)$limit));
	$dw = translation_autopilot_table_display_where($table);
	$src_lang_id = (int)$src_lang_id;
	$resync_source = ($src_lang_id > 0 && $entity !== 'blog');
	$not_in = '';
	if ($exclude_entity_ids !== array()) {
		$x = array_values(array_unique(array_map('intval', $exclude_entity_ids)));
		$x = array_values(array_filter($x, function ($v) { return $v > 0; }));
		if ($x !== array()) {
			$not_in = ' AND t.id NOT IN (' . implode(',', $x) . ') ';
		}
	}
	$freeze_sql = function_exists('translation_cluster_autopilot_freeze_exists_sql')
		? translation_cluster_autopilot_freeze_exists_sql($entity, 't')
		: '';

	$fill = translation_autopilot_ci_needs_initial_fill_sql('ci');
	$need_work = '('
		. 'ci.id IS NULL'
		. " OR ci.status IS NULL OR TRIM(ci.status) = ''"
		. " OR ci.status = 'missing'"
		. " OR (ci.status IN ('draft','review') AND " . $fill . ')'
		. ')';

	$join_ci = translation_autopilot_join_primary_ci($entity, $dst_lang_id, 'ci');
	if (!$resync_source) {
		$sql = "
			SELECT t.id
			FROM `" . $table . "` t
			" . $join_ci . "
			WHERE 1
			" . $dw . $not_in . $freeze_sql . "
			  AND " . $need_work . "
			ORDER BY " . $order_sql . "
			LIMIT " . $limit . "
		";
	} else {
		$stale_parts = translation_autopilot_sql_stale_source_parts($table);
		$cisrc_ts = $stale_parts['cisrc_ts'];
		$tgt_ts = $stale_parts['target_ts'];
		$t_ent = $stale_parts['entity_ts'];
		// Source newer than this translation: English row in content_i18n only when it exists;
		// never mark published stale from entity t.* alone (avoids phantom re-translates).
		$stale_cond = '('
			. '(cisrc.id IS NOT NULL AND ' . $cisrc_ts . ' > ' . $tgt_ts . ')'
			. ' OR (cisrc.id IS NULL AND (ci.status IS NULL OR ci.status <> \'published\') AND ' . $t_ent . ' > ' . $tgt_ts . ')'
			. ')';
		$order_prio = 'CASE WHEN cisrc.id IS NOT NULL AND ci.status = \'published\' AND ' . $cisrc_ts . ' > ' . $tgt_ts . ' THEN 0 ELSE 1 END, ';
		$join_cisrc = translation_autopilot_join_primary_ci($entity, $src_lang_id, 'cisrc');
		$sql = "
			SELECT t.id
			FROM `" . $table . "` t
			" . $join_ci . "
			" . $join_cisrc . "
			WHERE 1
			" . $dw . $not_in . $freeze_sql . "
			  AND (
				" . $need_work . '
				OR ' . $stale_cond . "
			  )
			ORDER BY " . $order_prio . " " . $order_sql . "
			LIMIT " . $limit . "
		";
	}
	$rows = mysql_select($sql, 'rows') ?: array();
	$ids = array();
	foreach ($rows as $r) {
		$ids[] = (int)$r['id'];
	}
	return $ids;
}

/**
 * Recent draft/review/published rows with a source-language sibling, for metadata quality scan.
 *
 * @param int[] $exclude_entity_ids
 * @return array<int,array<string,mixed>>
 */
function translation_autopilot_meta_fix_select_rows($entity, $table, $dst_lang_id, $src_lang_id, $limit, array $exclude_entity_ids = array()) {
	if (!function_exists('translation_cluster_autopilot_freeze_exists_sql_for_ci')) {
		require_once ROOT_DIR . 'functions/translation_cluster.php';
	}
	$entity_esc = mysql_res($entity);
	$table_esc = mysql_res($table);
	$dst_lang_id = (int)$dst_lang_id;
	$src_lang_id = (int)$src_lang_id;
	$limit = max(1, min(120, (int)$limit));
	$dw = translation_autopilot_table_display_where($table);
	$not_in = '';
	if ($exclude_entity_ids !== array()) {
		$x = array_values(array_unique(array_map('intval', $exclude_entity_ids)));
		$x = array_values(array_filter($x, function ($v) { return $v > 0; }));
		if ($x !== array()) {
			$not_in = ' AND t.id NOT IN (' . implode(',', $x) . ') ';
		}
	}
	$freeze_ci_sql = function_exists('translation_cluster_autopilot_freeze_exists_sql_for_ci')
		? translation_cluster_autopilot_freeze_exists_sql_for_ci()
		: '';
	$ord_primary_child = "FIELD(c2.status,'published','review','draft','missing') ASC, c2.id DESC";
	$ord_primary_src = "FIELD(c3.status,'published','review','draft','missing') ASC, c3.id DESC";
	$sql = "
		SELECT ci.entity_id,
			ci.name, ci.title, ci.description, ci.extra,
			cisrc.name AS src_name, cisrc.title AS src_title, cisrc.description AS src_description
		FROM content_i18n ci
		INNER JOIN content_i18n cisrc ON cisrc.id = (
			SELECT c3.id FROM content_i18n c3
			WHERE c3.entity='" . $entity_esc . "'
			  AND c3.entity_id = ci.entity_id
			  AND c3.lang_id = " . $src_lang_id . "
			ORDER BY " . $ord_primary_src . "
			LIMIT 1
		)
		INNER JOIN `" . $table_esc . "` t ON t.id = ci.entity_id
		WHERE ci.entity='" . $entity_esc . "'
		  AND ci.lang_id = " . $dst_lang_id . "
		  AND ci.status IN ('draft','review','published')
		  AND ci.id = (
			SELECT c2.id FROM content_i18n c2
			WHERE c2.entity='" . $entity_esc . "'
			  AND c2.entity_id = ci.entity_id
			  AND c2.lang_id = ci.lang_id
			ORDER BY " . $ord_primary_child . "
			LIMIT 1
		  )
		" . $dw . $not_in . $freeze_ci_sql . "
		ORDER BY FIELD(ci.status,'draft','review','published') ASC, ci.updated_at DESC
		LIMIT " . $limit . "
	";
	return mysql_select($sql, 'rows') ?: array();
}

/**
 * Enqueue metadata_normalize jobs for draft/review/published rows (priority higher than full translate).
 *
 * @return int number of jobs enqueued in this pass
 */
function translation_autopilot_meta_fix_enqueue_pass(
	$meta_budget,
	$prio_meta,
	&$enq,
	$max_run,
	&$inflight,
	array $targets,
	array $entities_non_blog,
	$src,
	$chunk_max,
	$meta_scan,
	$cooldown_days,
	$max_life,
	$latin_min,
	array $entity_exclusions = array(),
	$include_blog = true
) {
	$meta_budget = (int)$meta_budget;
	$src = (int)$src;
	if ($meta_budget <= 0 || $src <= 0 || $enq >= $max_run) {
		return 0;
	}
	require_once ROOT_DIR . 'functions/translation_metadata_quality.php';
	$lang_urls = array();
	$lang_rows = mysql_select("SELECT id, url FROM languages WHERE display=1", 'rows') ?: array();
	foreach ($lang_rows as $lr) {
		$lang_urls[(int)$lr['id']] = isset($lr['url']) ? trim((string)$lr['url'], '/') : '';
	}
	$entities_all = $entities_non_blog;
	if (!empty($include_blog)) {
		$entities_all['blog'] = array('table' => 'blog');
	}
	$meta_enqueued = 0;
	foreach ($targets as $dst) {
		if ($meta_enqueued >= $meta_budget || $enq >= $max_run) {
			break;
		}
		$dst_url = isset($lang_urls[$dst]) ? $lang_urls[$dst] : '';
		foreach ($entities_all as $ent => $em) {
			if ($meta_enqueued >= $meta_budget || $enq >= $max_run) {
				break;
			}
			$xraw = isset($entity_exclusions[$ent]) ? $entity_exclusions[$ent] : array();
			$xids = is_array($xraw) ? array_values(array_filter(array_map('intval', $xraw))) : array();
			$xids = array_values(array_unique(array_filter($xids, function ($v) { return $v > 0; })));
			$rows = translation_autopilot_meta_fix_select_rows($ent, $em['table'], $dst, $src, $meta_scan, $xids);
			foreach ($rows as $r) {
				if ($meta_enqueued >= $meta_budget || $enq >= $max_run) {
					break;
				}
				$eid = (int)$r['entity_id'];
				$k = $ent . ':' . $eid . ':' . $dst;
				if (isset($inflight[$k])) {
					continue;
				}
				$ex = translation_metadata_extra_parse(isset($r['extra']) ? $r['extra'] : '');
				if (!empty($ex['autopilot_skip_meta_fix'])) {
					continue;
				}
				$bad = translation_metadata_fields_needing_fix(
					isset($r['name']) ? $r['name'] : '',
					isset($r['title']) ? $r['title'] : '',
					isset($r['description']) ? $r['description'] : '',
					$dst_url,
					$latin_min
				);
				foreach (translation_metadata_seo_fields_needing_fix(
					isset($r['name']) ? $r['name'] : '',
					isset($r['title']) ? $r['title'] : '',
					isset($r['description']) ? $r['description'] : ''
				) as $fk => $fv) {
					if ($fv) {
						$bad[$fk] = true;
					}
				}
				if (empty($bad)) {
					continue;
				}
				if ($cooldown_days > 0 && !empty($ex['metadata_autofix_last_at'])) {
					if ((time() - (int)$ex['metadata_autofix_last_at']) < $cooldown_days * 86400) {
						continue;
					}
				}
				if ($max_life > 0 && isset($ex['metadata_autofix_runs']) && (int)$ex['metadata_autofix_runs'] >= $max_life) {
					continue;
				}
				$cisrc_row = array(
					'name' => isset($r['src_name']) ? (string)$r['src_name'] : '',
					'title' => isset($r['src_title']) ? (string)$r['src_title'] : '',
					'description' => isset($r['src_description']) ? (string)$r['src_description'] : '',
				);
				$src_meta = translation_resolve_source_meta_for_entity($ent, $eid, $src, $cisrc_row);
				$fields = array();
				foreach (array_keys($bad) as $f) {
					if (trim((string)($src_meta[$f] ?? '')) !== '') {
						$fields[] = $f;
					}
				}
				if ($fields === array()) {
					continue;
				}
				sort($fields);
				$jid = admin_jobs_enqueue('translations', 'translate', array(
					'entity' => $ent,
					'entity_id' => $eid,
					'src_lang' => $src,
					'dst_lang' => (int)$dst,
					'fields' => $fields,
					'chunk_max_len' => $chunk_max,
					'order_id' => 0,
					'candidate_id' => 0,
					'autopilot' => 1,
					'metadata_normalize' => 1,
					'english_leak_min_words' => $latin_min,
				), array('priority' => (int)$prio_meta));
				if ($jid) {
					$inflight[$k] = true;
					$enq++;
					$meta_enqueued++;
				}
			}
		}
	}
	return $meta_enqueued;
}

/**
 * Enqueue meta-fix jobs up to min(meta cap, remaining tick budget).
 *
 * @param array<string,array<string,string>> $entities_non_blog
 * @param array<int,int> $entity_exclusions
 */
function translation_autopilot_meta_fix_enqueue_within_budget(
	$meta_cfg_cap,
	$max_run,
	&$enq,
	$prio_meta,
	&$inflight,
	array $targets,
	array $entities_non_blog,
	$src,
	$chunk_max,
	$meta_scan,
	$cooldown_days,
	$max_life,
	$latin_min,
	array $entity_exclusions,
	$include_blog
) {
	$budget = min((int)$meta_cfg_cap, (int)$max_run - (int)$enq);
	if ($budget <= 0 || (int)$src <= 0) {
		return;
	}
	translation_autopilot_meta_fix_enqueue_pass(
		$budget,
		$prio_meta,
		$enq,
		$max_run,
		$inflight,
		$targets,
		$entities_non_blog,
		$src,
		$chunk_max,
		$meta_scan,
		$cooldown_days,
		$max_life,
		$latin_min,
		$entity_exclusions,
		$include_blog
	);
}

/**
 * Enqueue metadata_normalize jobs only (SEO + quality meta-fix), same scan as autopilot tail.
 * Does not check autopilot_enabled — for API / telemetry when autopilot is off.
 *
 * @param array<string,mixed> $opts optional: max_jobs (int cap for this call, default from cfg)
 * @return array{ok:bool,enqueued:int,message:string}
 */
function translation_autopilot_meta_fix_enqueue_tick(array $opts = array()) {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	require_once ROOT_DIR . 'admin/modules/_i18n.php';
	require_once ROOT_DIR . 'functions/translation_cluster.php';

	$out = array('ok' => true, 'enqueued' => 0, 'message' => '');
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
		return array('ok' => false, 'enqueued' => 0, 'message' => 'content_i18n missing');
	}
	$cfg = translation_autopilot_load_cfg();
	$src = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
	$targets = translation_autopilot_target_lang_ids($cfg);
	if ($targets === array()) {
		return array('ok' => false, 'enqueued' => 0, 'message' => 'no target languages');
	}
	$max_run = isset($cfg['autopilot_max_jobs_per_run']) ? max(1, min(100, (int)$cfg['autopilot_max_jobs_per_run'])) : 20;
	if (isset($opts['max_jobs'])) {
		$max_run = max(1, min(100, (int)$opts['max_jobs']));
	}
	$chunk_max = isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500;
	if ($chunk_max <= 0) {
		$chunk_max = 2500;
	}
	$inflight = translation_autopilot_inflight_keys();
	$enq = 0;
	$prio_meta = -4;
	$entities_non_blog = array(
		'pages' => array('table' => 'pages', 'order' => 't.id ASC'),
		'guides' => array('table' => 'guides', 'order' => 't.id ASC'),
		'games' => array('table' => 'games', 'order' => 't.id ASC'),
		'casino_articles' => array('table' => 'casino_articles', 'order' => 't.id ASC'),
	);
	$ap_sections = translation_autopilot_normalize_include(isset($cfg['autopilot_include']) ? $cfg['autopilot_include'] : array());
	foreach (array_keys($entities_non_blog) as $_ap_ent) {
		if (!translation_autopilot_section_enabled($_ap_ent, $ap_sections)) {
			unset($entities_non_blog[$_ap_ent]);
		}
	}
	$entity_exclusions = array();
	foreach (array_keys(translation_autopilot_exclude_entity_whitelist()) as $_ap_ex_ent) {
		$entity_exclusions[$_ap_ex_ent] = translation_autopilot_exclude_ids_for_entity($cfg, $_ap_ex_ent);
	}
	$meta_cfg_cap = isset($cfg['autopilot_meta_fix_max_per_run']) ? max(0, min(50, (int)$cfg['autopilot_meta_fix_max_per_run'])) : 6;
	if (isset($opts['meta_cap'])) {
		$meta_cfg_cap = max(0, min(50, (int)$opts['meta_cap']));
	}
	$meta_scan = isset($cfg['autopilot_meta_fix_scan_per_table']) ? max(5, min(120, (int)$cfg['autopilot_meta_fix_scan_per_table'])) : 40;
	$cooldown_days = isset($cfg['autopilot_meta_fix_cooldown_days']) ? max(0, min(90, (int)$cfg['autopilot_meta_fix_cooldown_days'])) : 7;
	$max_life = isset($cfg['autopilot_meta_fix_max_lifetime_runs']) ? max(0, min(50, (int)$cfg['autopilot_meta_fix_max_lifetime_runs'])) : 5;
	$latin_min = isset($cfg['english_leak_min_words']) ? max(3, min(12, (int)$cfg['english_leak_min_words'])) : 4;
	translation_autopilot_meta_fix_enqueue_within_budget(
		$meta_cfg_cap,
		$max_run,
		$enq,
		$prio_meta,
		$inflight,
		$targets,
		$entities_non_blog,
		$src,
		$chunk_max,
		$meta_scan,
		$cooldown_days,
		$max_life,
		$latin_min,
		$entity_exclusions,
		!empty($ap_sections['blog'])
	);
	$out['enqueued'] = $enq;
	$out['message'] = 'meta_fix_tick enqueued=' . (int)$enq;
	return $out;
}

/**
 * Append Activity retention purge counts for log lines (only when something was deleted).
 *
 * @param array{activity_logs_deleted?:int,activity_jobs_deleted?:int} $out
 */
function translation_autopilot_run_activity_purge_suffix(array $out) {
	$l = isset($out['activity_logs_deleted']) ? (int)$out['activity_logs_deleted'] : 0;
	$j = isset($out['activity_jobs_deleted']) ? (int)$out['activity_jobs_deleted'] : 0;
	if ($l <= 0 && $j <= 0) {
		return '';
	}
	return ' Activity purged: -' . $l . ' log rows, -' . $j . ' job rows.';
}

/**
 * Remove data shown on Translations → Activity older than N days: system_logs (channel translations)
 * and completed admin_jobs with actions from translation_hub_activity_admin_job_actions().
 *
 * @param int $days
 * @return array{system_logs_deleted:int,admin_jobs_deleted:int}
 */
function translation_autopilot_activity_retention_purge($days = 7) {
	$out = array('system_logs_deleted' => 0, 'admin_jobs_deleted' => 0);
	$days = (int)$days;
	if ($days <= 0) {
		$days = 7;
	}
	if ($days > 365) {
		$days = 365;
	}
	if (!function_exists('mysql_fn') || !function_exists('mysql_select') || !function_exists('mysql_res')) {
		return $out;
	}
	$cut = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

	if (@mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0) {
		mysql_fn('query', "
			DELETE FROM system_logs
			WHERE channel = 'translations'
			  AND created_at < '" . mysql_res($cut) . "'
		");
		$r = @mysql_select("SELECT ROW_COUNT() AS c", 'row');
		$out['system_logs_deleted'] = $r && isset($r['c']) ? (int)$r['c'] : 0;
	}

	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
		require_once ROOT_DIR . 'functions/translation_hub.php';
		$in = translation_hub_activity_admin_job_actions_sql_in();
		mysql_fn('query', "
			DELETE FROM admin_jobs
			WHERE module = 'translations'
			  AND status = 'done'
			  AND action IN (" . $in . ")
			  AND COALESCE(
			      NULLIF(NULLIF(finished_at, ''), '0000-00-00 00:00:00'),
			      updated_at,
			      created_at
			    ) < '" . mysql_res($cut) . "'
		");
		$r = @mysql_select("SELECT ROW_COUNT() AS c", 'row');
		$out['admin_jobs_deleted'] = $r && isset($r['c']) ? (int)$r['c'] : 0;
	}

	return $out;
}

/**
 * One cron tick.
 *
 * @return array{ok:bool,message:string,reaped:int,enqueued:int,skipped:string,activity_logs_deleted?:int,activity_jobs_deleted?:int}
 */
function translation_autopilot_run() {
	require_once ROOT_DIR . 'functions/admin_jobs.php';
	require_once ROOT_DIR . 'admin/modules/_i18n.php';
	require_once ROOT_DIR . 'functions/translation_cluster.php';

	$cfg = translation_autopilot_load_cfg();
	$out = array(
		'ok' => true,
		'message' => '',
		'reaped' => 0,
		'enqueued' => 0,
		'skipped' => '',
		'activity_logs_deleted' => 0,
		'activity_jobs_deleted' => 0,
	);

	// Same as admin_jobs_lock_next: heartbeat first (chunk-level), then total wall-time cap.
	// Run even when autopilot is disabled so cron still clears stuck translation jobs.
	$out['reaped'] = admin_jobs_reap_stale_running_jobs(admin_jobs_translation_reap_heartbeat_seconds(), 30, array('basis' => 'heartbeat'));
	$out['reaped'] += admin_jobs_reap_stale_running_jobs(admin_jobs_translation_reap_total_seconds(), 30, array('basis' => 'started_at'));

	$ret_days = isset($cfg['autopilot_activity_retention_days']) ? (int)$cfg['autopilot_activity_retention_days'] : 7;
	if ($ret_days > 0) {
		$purged = translation_autopilot_activity_retention_purge($ret_days);
		$out['activity_logs_deleted'] = (int)$purged['system_logs_deleted'];
		$out['activity_jobs_deleted'] = (int)$purged['admin_jobs_deleted'];
	}

	if (empty($cfg['autopilot_enabled'])) {
		$out['skipped'] = 'disabled';
		$out['message'] = 'Autopilot disabled (reaped=' . (int)$out['reaped']
			. ', activity purged: logs=' . (int)$out['activity_logs_deleted'] . ', jobs=' . (int)$out['activity_jobs_deleted'] . ').';
		return $out;
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
		$out['ok'] = false;
		$out['message'] = 'content_i18n missing (reaped=' . (int)$out['reaped'] . ').'
			. translation_autopilot_run_activity_purge_suffix($out);
		return $out;
	}

	if (!empty($cfg['autopilot_respect_monitor']) && translation_autopilot_monitor_busy()) {
		$out['skipped'] = 'monitor_busy';
		$out['message'] = 'Monitor has queued/running candidates; autopilot did not enqueue (reaped=' . (int)$out['reaped'] . ').'
			. translation_autopilot_run_activity_purge_suffix($out);
		return $out;
	}

	$src = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
	$targets = translation_autopilot_target_lang_ids($cfg);
	if ($targets === array()) {
		$out['skipped'] = 'no_targets';
		$out['message'] = 'No target languages (reaped=' . (int)$out['reaped'] . ').'
			. translation_autopilot_run_activity_purge_suffix($out);
		return $out;
	}

	$max_run = isset($cfg['autopilot_max_jobs_per_run']) ? max(1, min(100, (int)$cfg['autopilot_max_jobs_per_run'])) : 20;
	$chunk_max = isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500;
	if ($chunk_max <= 0) {
		$chunk_max = 2500;
	}

	$inflight = translation_autopilot_inflight_keys();
	$enq = 0;
	$prio_normal = -5;
	$prio_meta = -4;
	$cluster_mode = !empty($cfg['autopilot_cluster_mode']);
	$pending_translations = translation_autopilot_pending_translation_jobs_count();
	$active_cluster = $cluster_mode ? translation_cluster_find_active_state() : null;

	$entities_non_blog = array(
		'pages' => array('table' => 'pages', 'order' => 't.id ASC'),
		'guides' => array('table' => 'guides', 'order' => 't.id ASC'),
		'games' => array('table' => 'games', 'order' => 't.id ASC'),
		'casino_articles' => array('table' => 'casino_articles', 'order' => 't.id ASC'),
	);

	$ap_sections = translation_autopilot_normalize_include(isset($cfg['autopilot_include']) ? $cfg['autopilot_include'] : array());
	foreach (array_keys($entities_non_blog) as $_ap_ent) {
		if (!translation_autopilot_section_enabled($_ap_ent, $ap_sections)) {
			unset($entities_non_blog[$_ap_ent]);
		}
	}

	$entity_exclusions = array();
	foreach (array_keys(translation_autopilot_exclude_entity_whitelist()) as $_ap_ex_ent) {
		$entity_exclusions[$_ap_ex_ent] = translation_autopilot_exclude_ids_for_entity($cfg, $_ap_ex_ent);
	}

	$meta_cfg_cap = isset($cfg['autopilot_meta_fix_max_per_run']) ? max(0, min(50, (int)$cfg['autopilot_meta_fix_max_per_run'])) : 6;
	$meta_scan = isset($cfg['autopilot_meta_fix_scan_per_table']) ? max(5, min(120, (int)$cfg['autopilot_meta_fix_scan_per_table'])) : 40;
	$cooldown_days = isset($cfg['autopilot_meta_fix_cooldown_days']) ? max(0, min(90, (int)$cfg['autopilot_meta_fix_cooldown_days'])) : 7;
	$max_life = isset($cfg['autopilot_meta_fix_max_lifetime_runs']) ? max(0, min(50, (int)$cfg['autopilot_meta_fix_max_lifetime_runs'])) : 5;
	$latin_min = isset($cfg['english_leak_min_words']) ? max(3, min(12, (int)$cfg['english_leak_min_words'])) : 4;

	$max_pending = isset($cfg['autopilot_max_pending_skip']) ? (int)$cfg['autopilot_max_pending_skip'] : 50;
	if ($max_pending > 0 && $pending_translations >= $max_pending) {
		$meta_cap_deep = min(3, (int)$meta_cfg_cap);
		$max_run_deep = min(5, (int)$max_run);
		if ($meta_cap_deep > 0 && $src > 0) {
			translation_autopilot_meta_fix_enqueue_within_budget(
				$meta_cap_deep,
				$max_run_deep,
				$enq,
				$prio_meta,
				$inflight,
				$targets,
				$entities_non_blog,
				$src,
				$chunk_max,
				$meta_scan,
				$cooldown_days,
				$max_life,
				$latin_min,
				$entity_exclusions,
				!empty($ap_sections['blog'])
			);
		}
		$out['enqueued'] = $enq;
		$out['skipped'] = 'queue_deep';
		$out['message'] = 'Translation job backlog >= ' . $max_pending . '; meta-fix only (enqueued=' . (int)$enq . ', reaped=' . (int)$out['reaped'] . ').'
			. translation_autopilot_run_activity_purge_suffix($out);
		if (function_exists('system_log_add')) {
			system_log_add('translations', 'info', $out['message'], array('autopilot' => 1));
		}
		return $out;
	}

	$normals_threshold = isset($cfg['autopilot_normal_jobs_before_draft_meta']) ? max(1, min(500, (int)$cfg['autopilot_normal_jobs_before_draft_meta'])) : 10;
	$counter_start = isset($cfg['autopilot_normals_since_draft_meta']) ? max(0, (int)$cfg['autopilot_normals_since_draft_meta']) : 0;
	$mandatory_draft_meta = ($counter_start >= $normals_threshold);
	$normals_this_run = 0;
	if ($mandatory_draft_meta && $pending_translations === 0 && $active_cluster === null) {
		translation_autopilot_meta_fix_enqueue_within_budget(
			$meta_cfg_cap,
			$max_run,
			$enq,
			$prio_meta,
			$inflight,
			$targets,
			$entities_non_blog,
			$src,
			$chunk_max,
			$meta_scan,
			$cooldown_days,
			$max_life,
			$latin_min,
			$entity_exclusions,
			!empty($ap_sections['blog'])
		);
		$counter_base = 0;
	} else {
		$counter_base = $counter_start;
	}

	$cluster_blocking = translation_autopilot_pending_cluster_blocking_jobs_count();
	if ($cluster_mode && $cluster_blocking > 0) {
		// Still enqueue meta-fix: short translate jobs do not block cluster work but SEO meta should progress.
		translation_autopilot_meta_fix_enqueue_within_budget(
			$meta_cfg_cap,
			$max_run,
			$enq,
			$prio_meta,
			$inflight,
			$targets,
			$entities_non_blog,
			$src,
			$chunk_max,
			$meta_scan,
			$cooldown_days,
			$max_life,
			$latin_min,
			$entity_exclusions,
			!empty($ap_sections['blog'])
		);
		translation_autopilot_save_normals_since_draft_meta($counter_base + $normals_this_run);
		$detail = translation_autopilot_cluster_blocking_detail();
		$out['enqueued'] = $enq;
		$out['skipped'] = 'queue_busy';
		$out['cluster_blocking_detail'] = $detail;
		$out['autopilot_build'] = TRANSLATION_AUTOPILOT_BUILD;
		$ba = isset($detail['by_action']) && is_array($detail['by_action']) ? $detail['by_action'] : array();
		$out['message'] = 'Autopilot: cluster pipeline busy (orchestrator pending=' . (int)$cluster_blocking
			. ' translate_cluster=' . (int)(isset($ba['translate_cluster']) ? $ba['translate_cluster'] : 0)
			. ' cluster_pipeline=' . (int)(isset($ba['cluster_pipeline']) ? $ba['cluster_pipeline'] : 0)
			. '; leaf cluster jobs=' . (int)(isset($detail['cluster_child_pending']) ? $detail['cluster_child_pending'] : 0)
			. ' translate=' . (int)(isset($detail['translate_pending']) ? $detail['translate_pending'] : 0)
			. '); meta-fix may still enqueue (enqueued=' . (int)$enq . ').'
			. translation_autopilot_run_activity_purge_suffix($out);
		if (function_exists('system_log_add')) {
			system_log_add('translations', 'info', $out['message'], array(
				'autopilot' => 1,
				'autopilot_build' => TRANSLATION_AUTOPILOT_BUILD,
				'cluster_blocking_detail' => $detail,
			));
		}
		return $out;
	}
	if ($cluster_mode && $active_cluster) {
		$ent = isset($active_cluster['entity']) ? (string)$active_cluster['entity'] : '';
		$eid = isset($active_cluster['entity_id']) ? (int)$active_cluster['entity_id'] : 0;
		$src_active = isset($active_cluster['source_lang_id']) ? (int)$active_cluster['source_lang_id'] : $src;
		$k = 'cluster:' . $ent . ':' . $eid;
		$use_pipe = !empty($cfg['autopilot_use_cluster_pipeline']);
		$pipe_max = isset($cfg['autopilot_cluster_pipeline_max_seconds']) ? (int)$cfg['autopilot_cluster_pipeline_max_seconds'] : 900;
		if ($pipe_max < 60) {
			$pipe_max = 60;
		}
		if ($pipe_max > 3600) {
			$pipe_max = 3600;
		}
		$pipe_prio = isset($cfg['autopilot_cluster_pipeline_priority']) ? (int)$cfg['autopilot_cluster_pipeline_priority'] : 2;
		$section_ok = translation_autopilot_section_enabled($ent, $ap_sections);
		if ($ent !== '' && $eid > 0 && $section_ok && !isset($inflight[$k]) && !translation_cluster_has_pending_job($ent, $eid, 'cluster_pipeline', 0)) {
			if ($use_pipe) {
				if (translation_cluster_has_pending_job($ent, $eid, 'validate_cluster', 0)) {
					admin_jobs_supersede_pending_validate_cluster_for_cluster($ent, $eid);
				}
				$jid = admin_jobs_enqueue('translations', 'cluster_pipeline', array(
					'entity' => $ent,
					'entity_id' => $eid,
					'src_lang' => $src_active > 0 ? $src_active : $src,
					'dst_langs' => $targets,
					'autopilot' => 1,
					'cluster_repair_round' => 0,
					'max_seconds' => $pipe_max,
				), array('priority' => $pipe_prio));
			} elseif (!translation_cluster_has_pending_job($ent, $eid, 'validate_cluster', 0)) {
				$jid = admin_jobs_enqueue('translations', 'validate_cluster', array(
					'entity' => $ent,
					'entity_id' => $eid,
					'src_lang' => $src_active > 0 ? $src_active : $src,
					'dst_langs' => $targets,
					'autopilot' => 1,
					'cluster_repair_round' => 0,
				), array('priority' => -3));
			} else {
				$jid = false;
			}
			if (!empty($jid)) {
				$enq++;
				$inflight[$k] = true;
			}
		}
		// Meta-fix is independent of cluster_pipeline; run tail pass so SEO meta keeps moving while a cluster validates.
		translation_autopilot_meta_fix_enqueue_within_budget(
			$meta_cfg_cap,
			$max_run,
			$enq,
			$prio_meta,
			$inflight,
			$targets,
			$entities_non_blog,
			$src,
			$chunk_max,
			$meta_scan,
			$cooldown_days,
			$max_life,
			$latin_min,
			$entity_exclusions,
			!empty($ap_sections['blog'])
		);
		translation_autopilot_save_normals_since_draft_meta($counter_base + $normals_this_run);
		$out['enqueued'] = $enq;
		$out['skipped'] = 'active_cluster';
		$out['message'] = $section_ok
			? ('Autopilot: continue active cluster ' . $ent . '#' . $eid . ' (enqueued=' . $enq . ').')
			: ('Autopilot: active cluster ' . $ent . '#' . $eid . ' not driven (section disabled); enqueued=' . $enq . '.')
			. translation_autopilot_run_activity_purge_suffix($out);
		if (function_exists('system_log_add')) {
			system_log_add('translations', 'info', $out['message'], array('autopilot' => 1));
		}
		return $out;
	}
	if ($cluster_mode) {
		$queued_cluster = false;
		foreach ($entities_non_blog as $ent => $meta) {
			if ($enq >= $max_run) {
				break;
			}
			$cluster_ids = array();
			$xlist = translation_autopilot_exclude_ids_for_entity($cfg, $ent);
			foreach ($targets as $dst) {
				$need = max(1, min(200, $max_run * 3));
				$ids = translation_autopilot_missing_entity_ids($ent, $meta['table'], $dst, $need, $meta['order'], $src, $xlist);
				foreach ($ids as $eid) {
					$cluster_ids[(int)$eid] = true;
				}
			}
			$cluster_ids = array_keys($cluster_ids);
			sort($cluster_ids, SORT_NUMERIC);
			foreach ($cluster_ids as $eid) {
				if ($enq >= $max_run) {
					break;
				}
				$k = 'cluster:' . $ent . ':' . (int)$eid;
				if (isset($inflight[$k])) {
					continue;
				}
				$jid = admin_jobs_enqueue('translations', 'translate_cluster', array(
					'entity' => $ent,
					'entity_id' => (int)$eid,
					'src_lang' => $src,
					'dst_langs' => $targets,
					'chunk_max_len' => $chunk_max,
					'autopilot' => 1,
				), array('priority' => $prio_normal));
				if ($jid) {
					$inflight[$k] = true;
					$enq++;
					$normals_this_run++;
					$queued_cluster = true;
					break;
				}
			}
			if ($queued_cluster) {
				break;
			}
		}
	} else {
		foreach ($targets as $dst) {
			if ($enq >= $max_run) {
				break;
			}
			foreach ($entities_non_blog as $ent => $meta) {
				if ($enq >= $max_run) {
					break;
				}
				$need = $max_run - $enq;
				$xlist = translation_autopilot_exclude_ids_for_entity($cfg, $ent);
				$ids = translation_autopilot_missing_entity_ids($ent, $meta['table'], $dst, $need, $meta['order'], $src, $xlist);
				foreach ($ids as $eid) {
					if ($enq >= $max_run) {
						break;
					}
					$k = $ent . ':' . $eid . ':' . $dst;
					if (isset($inflight[$k])) {
						continue;
					}
					$jid = admin_jobs_enqueue('translations', 'translate', array(
						'entity' => $ent,
						'entity_id' => (int)$eid,
						'src_lang' => $src,
						'dst_lang' => (int)$dst,
						'fields' => array('name', 'title', 'description', 'content'),
						'chunk_max_len' => $chunk_max,
						'order_id' => 0,
						'candidate_id' => 0,
						'autopilot' => 1,
					), array('priority' => $prio_normal));
					if ($jid) {
						$inflight[$k] = true;
						$enq++;
						$normals_this_run++;
					}
				}
			}
		}
	}

	if ($enq < $max_run && $src > 0 && !$cluster_mode) {
		$key_limit = min(24, $max_run - $enq);
		if ($key_limit > 0) {
			foreach ($targets as $dst) {
				if ($enq >= $max_run) {
					break;
				}
				if (isset($inflight['common_dict:' . $dst])) {
					continue;
				}
				$miss = array();
				$mon_keys = translation_autopilot_monitored_common_keys($src);
				$tgt_dict = admin_load_common_dict($dst);
				foreach ($mon_keys as $ck) {
					$tv = isset($tgt_dict[$ck]) ? trim((string)$tgt_dict[$ck]) : '';
					if ($tv === '') {
						$miss[] = $ck;
						if (count($miss) >= $key_limit) {
							break;
						}
					}
				}
				if ($miss !== array()) {
					$jid = admin_jobs_enqueue('translations', 'translate_common_dict', array(
						'src_lang' => $src,
						'dst_lang' => (int)$dst,
						'dict_keys' => $miss,
						'chunk_max_len' => $chunk_max,
						'autopilot' => 1,
					), array('priority' => $prio_normal));
					if ($jid) {
						$inflight['common_dict:' . $dst] = true;
						$enq++;
					}
				}
			}
		}
	}

	$blog_batch = isset($cfg['autopilot_blog_batch']) ? max(1, min(200, (int)$cfg['autopilot_blog_batch'])) : 10;
	if ($enq < $max_run && !empty($ap_sections['blog'])) {
		if ($cluster_mode) {
			$blog_cluster_ids = array();
			foreach ($targets as $dst) {
				$need = min(max($blog_batch, 1), 200);
				$ids = translation_autopilot_missing_entity_ids('blog', 'blog', $dst, $need, 't.date DESC, t.id DESC', 0, translation_autopilot_exclude_ids_for_entity($cfg, 'blog'));
				foreach ($ids as $eid) {
					$blog_cluster_ids[(int)$eid] = true;
				}
			}
			$blog_cluster_ids = array_keys($blog_cluster_ids);
			rsort($blog_cluster_ids, SORT_NUMERIC);
			foreach ($blog_cluster_ids as $eid) {
				if ($enq >= $max_run) {
					break;
				}
				$k = 'cluster:blog:' . (int)$eid;
				if (isset($inflight[$k])) {
					continue;
				}
				$jid = admin_jobs_enqueue('translations', 'translate_cluster', array(
					'entity' => 'blog',
					'entity_id' => (int)$eid,
					'src_lang' => $src,
					'dst_langs' => $targets,
					'chunk_max_len' => $chunk_max,
					'autopilot' => 1,
				), array('priority' => $prio_normal));
				if ($jid) {
					$inflight[$k] = true;
					$enq++;
					$normals_this_run++;
					break;
				}
			}
		} else {
			$cursor = isset($cfg['autopilot_blog_cursor_lang_id']) ? (int)$cfg['autopilot_blog_cursor_lang_id'] : 0;
			$idx = 0;
			if ($cursor > 0) {
				$pos = array_search($cursor, $targets, true);
				if ($pos !== false) {
					$idx = ($pos + 1) % count($targets);
				}
			}
			$tries = 0;
			$last_used = 0;
			while ($enq < $max_run && $tries < count($targets)) {
				$dst = $targets[$idx];
				$idx = ($idx + 1) % count($targets);
				$tries++;
				$need = min($blog_batch, $max_run - $enq);
				$ids = translation_autopilot_missing_entity_ids('blog', 'blog', $dst, $need, 't.date DESC, t.id DESC', 0, translation_autopilot_exclude_ids_for_entity($cfg, 'blog'));
				if ($ids === array()) {
					continue;
				}
				foreach ($ids as $eid) {
					if ($enq >= $max_run) {
						break;
					}
					$k = 'blog:' . $eid . ':' . $dst;
					if (isset($inflight[$k])) {
						continue;
					}
					$jid = admin_jobs_enqueue('translations', 'translate', array(
						'entity' => 'blog',
						'entity_id' => (int)$eid,
						'src_lang' => $src,
						'dst_lang' => (int)$dst,
						'fields' => array('name', 'title', 'description', 'content'),
						'chunk_max_len' => $chunk_max,
						'order_id' => 0,
						'candidate_id' => 0,
						'autopilot' => 1,
					), array('priority' => $prio_normal));
					if ($jid) {
						$inflight[$k] = true;
						$enq++;
						$normals_this_run++;
						$last_used = $dst;
					}
				}
				if ($last_used > 0) {
					translation_autopilot_save_blog_cursor($last_used);
					break;
				}
			}
		}
	}

	// Meta-fix (incl. SEO title/description limits for published locales) must run in cluster mode too;
	// otherwise cluster-first blog work never enqueues metadata_normalize jobs from autopilot.
	translation_autopilot_meta_fix_enqueue_within_budget(
		$meta_cfg_cap,
		$max_run,
		$enq,
		$prio_meta,
		$inflight,
		$targets,
		$entities_non_blog,
		$src,
		$chunk_max,
		$meta_scan,
		$cooldown_days,
		$max_life,
		$latin_min,
		$entity_exclusions,
		!empty($ap_sections['blog'])
	);

	translation_autopilot_save_normals_since_draft_meta($counter_base + $normals_this_run);

	$out['enqueued'] = $enq;
	$out['message'] = 'Autopilot: reaped=' . (int)$out['reaped'] . ', enqueued=' . $enq . '.'
		. translation_autopilot_run_activity_purge_suffix($out);
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', $out['message'], array('autopilot' => 1));
	}
	return $out;
}
