<?php
/**
 * Translations: settings (source lang, enabled langs for UI, defaults).
 * Stored in variables.translation_settings (JSON).
 */
$page_name = 'Translations: settings';

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
if (!$variables_exists) {
	$content = '<div class="alert alert-warning">Table <code>variables</code> not found. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

require_once ROOT_DIR . 'functions/translation_autopilot.php';
require_once ROOT_DIR . 'functions/translation_cluster.php';
require_once ROOT_DIR . 'functions/admin_jobs.php';

$key = 'translation_settings';
$row = mysql_select("SELECT value FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
$cfg = array();
if ($row && $row['value'] !== '') {
	$dec = json_decode($row['value'], true);
	if (is_array($dec)) $cfg = $dec;
}

$defaults = array(
	'source_lang_id' => 1,
	'enabled_lang_ids' => array(), // empty = all display=1
	'chunk_max_len' => 2500,
	// HTML `content` field: max characters per AI request (smaller = fewer stalls).
	'content_chunk_cap' => 700,
	// How deep to split a failing segment (halving) before source-language fallback.
	'bisect_max_depth' => 6,
	'bisect_min_chars' => 280,
	// After translation: if output still has N+ Latin words in a row (for non-Latin target langs), re-send chunk to AI.
	'english_leak_retry' => 1,
	'english_leak_min_words' => 4,
	'english_leak_max_retries' => 1,
	'autopilot_enabled' => 0,
	'autopilot_stuck_seconds' => 900,
	'translation_reap_heartbeat_seconds' => 120,
	'translation_reap_total_seconds' => 3600,
	'autopilot_max_jobs_per_run' => 20,
	'autopilot_process_jobs_per_tick' => 6,
	'autopilot_cron_max_wall_seconds' => 360,
	'autopilot_max_pending_skip' => 50,
	'autopilot_blog_batch' => 10,
	'autopilot_respect_monitor' => 1,
	'autopilot_blog_cursor_lang_id' => 0,
	'autopilot_locale_ids' => array(),
	'autopilot_meta_fix_max_per_run' => 6,
	'autopilot_meta_fix_scan_per_table' => 40,
	'autopilot_meta_fix_cooldown_days' => 7,
	'autopilot_meta_fix_max_lifetime_runs' => 5,
	'autopilot_normal_jobs_before_draft_meta' => 10,
	'autopilot_cluster_autopublish' => 1,
	'autopilot_activity_retention_days' => 7,
	'cluster_validation_seo_full' => 0,
	'translation_vector_enabled' => 0,
	'autopilot_exclude' => array(),
	'autopilot_include' => array(
		'pages' => 1,
		'blog' => 1,
		'guides' => 1,
		'games' => 1,
		'casinos' => 1,
	),
);
$cfg = array_merge($defaults, $cfg);
$cfg['autopilot_include'] = translation_autopilot_normalize_include(isset($cfg['autopilot_include']) ? $cfg['autopilot_include'] : array());

$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();

$vector_stats = translation_vector_table_stats();
$vector_clear_enqueued = false;
$vector_clear_error = '';

if (!empty($_POST['enqueue_clear_translation_vector'])) {
	$pending = mysql_select("
		SELECT id, status FROM admin_jobs
		WHERE module='translations' AND action='clear_vector_db'
		  AND status IN ('pending','running')
		ORDER BY id DESC LIMIT 1
	", 'row');
	if ($pending && !empty($pending['id'])) {
		$vector_clear_error = 'Clear job already queued (job #' . (int)$pending['id'] . ', status ' . htmlspecialchars((string)$pending['status']) . ').';
	} else {
		$jid = admin_jobs_enqueue('translations', 'clear_vector_db', array(
			'chunk' => 500,
			'max_chunks' => 500,
			'pause_ms' => 100,
		), array('priority' => 3));
		if ($jid) {
			$vector_clear_enqueued = true;
			if (function_exists('system_log_add')) {
				system_log_add('translations', 'info', 'Enqueued translation vector DB clear', array('job_id' => (int)$jid));
			}
		} else {
			$vector_clear_error = 'Could not enqueue clear_vector_db job.';
		}
	}
}

$saved = false;
if (!empty($_POST['save_translation_settings'])) {
	$prev = $cfg;
	$src = isset($_POST['source_lang_id']) ? (int)$_POST['source_lang_id'] : 1;
	$chunk = isset($_POST['chunk_max_len']) ? (int)$_POST['chunk_max_len'] : 2500;
	if ($chunk <= 0) $chunk = 2500;
	$ccc = isset($_POST['content_chunk_cap']) ? (int)$_POST['content_chunk_cap'] : 900;
	if ($ccc < 350) {
		$ccc = 350;
	}
	if ($ccc > 4000) {
		$ccc = 4000;
	}
	$bmd = isset($_POST['bisect_max_depth']) ? (int)$_POST['bisect_max_depth'] : 6;
	if ($bmd < 2) {
		$bmd = 2;
	}
	if ($bmd > 12) {
		$bmd = 12;
	}
	$bmc = isset($_POST['bisect_min_chars']) ? (int)$_POST['bisect_min_chars'] : 280;
	if ($bmc < 80) {
		$bmc = 80;
	}
	$el_retry = !empty($_POST['english_leak_retry']) ? 1 : 0;
	$el_min = isset($_POST['english_leak_min_words']) ? (int)$_POST['english_leak_min_words'] : 4;
	if ($el_min < 3) {
		$el_min = 3;
	}
	if ($el_min > 12) {
		$el_min = 12;
	}
	$el_maxr = isset($_POST['english_leak_max_retries']) ? (int)$_POST['english_leak_max_retries'] : 1;
	if ($el_maxr < 0) {
		$el_maxr = 0;
	}
	if ($el_maxr > 3) {
		$el_maxr = 3;
	}
	$enabled = isset($_POST['enabled_lang_ids']) && is_array($_POST['enabled_lang_ids']) ? array_map('intval', $_POST['enabled_lang_ids']) : array();
	$enabled = array_values(array_filter($enabled));
	$ap_on = !empty($_POST['autopilot_enabled']) ? 1 : 0;
	$ap_stuck = isset($_POST['autopilot_stuck_seconds']) ? (int)$_POST['autopilot_stuck_seconds'] : 900;
	if ($ap_stuck < 120) {
		$ap_stuck = 120;
	}
	if ($ap_stuck > 86400) {
		$ap_stuck = 86400;
	}
	$tr_hb = isset($_POST['translation_reap_heartbeat_seconds']) ? (int)$_POST['translation_reap_heartbeat_seconds'] : 120;
	if ($tr_hb < 60) {
		$tr_hb = 60;
	}
	if ($tr_hb > 1200) {
		$tr_hb = 1200;
	}
	$tr_tot = isset($_POST['translation_reap_total_seconds']) ? (int)$_POST['translation_reap_total_seconds'] : 3600;
	if ($tr_tot < 600) {
		$tr_tot = 600;
	}
	if ($tr_tot > 86400) {
		$tr_tot = 86400;
	}
	$ap_max_run = isset($_POST['autopilot_max_jobs_per_run']) ? (int)$_POST['autopilot_max_jobs_per_run'] : 20;
	if ($ap_max_run < 1) {
		$ap_max_run = 1;
	}
	if ($ap_max_run > 100) {
		$ap_max_run = 100;
	}
	$ap_process = isset($_POST['autopilot_process_jobs_per_tick']) ? (int)$_POST['autopilot_process_jobs_per_tick'] : 6;
	if ($ap_process < 0) {
		$ap_process = 0;
	}
	if ($ap_process > 20) {
		$ap_process = 20;
	}
	$ap_cron_wall = isset($_POST['autopilot_cron_max_wall_seconds']) ? (int)$_POST['autopilot_cron_max_wall_seconds'] : 360;
	if ($ap_cron_wall < 60) {
		$ap_cron_wall = 60;
	}
	if ($ap_cron_wall > 840) {
		$ap_cron_wall = 840;
	}
	$ap_max_pend = isset($_POST['autopilot_max_pending_skip']) ? (int)$_POST['autopilot_max_pending_skip'] : 50;
	if ($ap_max_pend < 0) {
		$ap_max_pend = 0;
	}
	if ($ap_max_pend > 500) {
		$ap_max_pend = 500;
	}
	$ap_blog_batch = isset($_POST['autopilot_blog_batch']) ? (int)$_POST['autopilot_blog_batch'] : 10;
	if ($ap_blog_batch < 1) {
		$ap_blog_batch = 1;
	}
	if ($ap_blog_batch > 200) {
		$ap_blog_batch = 200;
	}
	$ap_respect = !empty($_POST['autopilot_respect_monitor']) ? 1 : 0;
	$ap_locales = isset($_POST['autopilot_locale_ids']) && is_array($_POST['autopilot_locale_ids']) ? array_map('intval', $_POST['autopilot_locale_ids']) : array();
	$ap_locales = array_values(array_filter($ap_locales));
	$ap_meta_max = isset($_POST['autopilot_meta_fix_max_per_run']) ? (int)$_POST['autopilot_meta_fix_max_per_run'] : 6;
	if ($ap_meta_max < 0) {
		$ap_meta_max = 0;
	}
	if ($ap_meta_max > 50) {
		$ap_meta_max = 50;
	}
	$ap_meta_scan = isset($_POST['autopilot_meta_fix_scan_per_table']) ? (int)$_POST['autopilot_meta_fix_scan_per_table'] : 40;
	if ($ap_meta_scan < 5) {
		$ap_meta_scan = 5;
	}
	if ($ap_meta_scan > 120) {
		$ap_meta_scan = 120;
	}
	$ap_meta_cd = isset($_POST['autopilot_meta_fix_cooldown_days']) ? (int)$_POST['autopilot_meta_fix_cooldown_days'] : 7;
	if ($ap_meta_cd < 0) {
		$ap_meta_cd = 0;
	}
	if ($ap_meta_cd > 90) {
		$ap_meta_cd = 90;
	}
	$ap_meta_life = isset($_POST['autopilot_meta_fix_max_lifetime_runs']) ? (int)$_POST['autopilot_meta_fix_max_lifetime_runs'] : 5;
	if ($ap_meta_life < 0) {
		$ap_meta_life = 0;
	}
	if ($ap_meta_life > 50) {
		$ap_meta_life = 50;
	}
	$ap_norm_before_meta = isset($_POST['autopilot_normal_jobs_before_draft_meta']) ? (int)$_POST['autopilot_normal_jobs_before_draft_meta'] : 10;
	if ($ap_norm_before_meta < 1) {
		$ap_norm_before_meta = 1;
	}
	if ($ap_norm_before_meta > 500) {
		$ap_norm_before_meta = 500;
	}
	$ap_cluster_autopublish = !empty($_POST['autopilot_cluster_autopublish']) ? 1 : 0;
	$ap_activity_ret = isset($_POST['autopilot_activity_retention_days']) ? (int)$_POST['autopilot_activity_retention_days'] : 7;
	if ($ap_activity_ret < 0) {
		$ap_activity_ret = 0;
	}
	if ($ap_activity_ret > 365) {
		$ap_activity_ret = 365;
	}
	$cluster_seo_full = !empty($_POST['cluster_validation_seo_full']) ? 1 : 0;
	$vector_enabled = !empty($_POST['translation_vector_enabled']) ? 1 : 0;
	$eligible_ap = array();
	foreach ($langs as $_l) {
		if ((int)$_l['id'] === ($src > 0 ? $src : 1)) {
			continue;
		}
		$eligible_ap[] = (int)$_l['id'];
	}
	sort($eligible_ap);
	sort($ap_locales);
	if ($eligible_ap !== array() && $ap_locales === $eligible_ap) {
		$ap_locales = array();
	}

	$ap_exclude = translation_autopilot_parse_exclude_text(isset($_POST['autopilot_exclude_raw']) ? (string)$_POST['autopilot_exclude_raw'] : '');

	$ap_include_post = translation_autopilot_normalize_include(isset($prev['autopilot_include']) ? $prev['autopilot_include'] : array());
	if (isset($_POST['autopilot_include']) && is_array($_POST['autopilot_include'])) {
		foreach (array('pages', 'blog', 'guides', 'games', 'casinos') as $_ap_inc_k) {
			$ap_include_post[$_ap_inc_k] = !empty($_POST['autopilot_include'][$_ap_inc_k]) ? 1 : 0;
		}
		$ap_include_post = translation_autopilot_normalize_include($ap_include_post);
	}

	$cfg = array_merge($prev, array(
		'source_lang_id' => $src > 0 ? $src : 1,
		'enabled_lang_ids' => $enabled,
		'chunk_max_len' => $chunk,
		'content_chunk_cap' => $ccc,
		'bisect_max_depth' => $bmd,
		'bisect_min_chars' => $bmc,
		'english_leak_retry' => $el_retry,
		'english_leak_min_words' => $el_min,
		'english_leak_max_retries' => $el_maxr,
		'autopilot_enabled' => $ap_on,
		'autopilot_stuck_seconds' => $ap_stuck,
		'translation_reap_heartbeat_seconds' => $tr_hb,
		'translation_reap_total_seconds' => $tr_tot,
		'autopilot_max_jobs_per_run' => $ap_max_run,
		'autopilot_process_jobs_per_tick' => $ap_process,
		'autopilot_cron_max_wall_seconds' => $ap_cron_wall,
		'autopilot_max_pending_skip' => $ap_max_pend,
		'autopilot_blog_batch' => $ap_blog_batch,
		'autopilot_respect_monitor' => $ap_respect,
		'autopilot_locale_ids' => $ap_locales,
		'autopilot_meta_fix_max_per_run' => $ap_meta_max,
		'autopilot_meta_fix_scan_per_table' => $ap_meta_scan,
		'autopilot_meta_fix_cooldown_days' => $ap_meta_cd,
		'autopilot_meta_fix_max_lifetime_runs' => $ap_meta_life,
		'autopilot_normal_jobs_before_draft_meta' => $ap_norm_before_meta,
		'autopilot_cluster_autopublish' => $ap_cluster_autopublish,
		'autopilot_activity_retention_days' => $ap_activity_ret,
		'cluster_validation_seo_full' => $cluster_seo_full,
		'translation_vector_enabled' => $vector_enabled,
		'autopilot_exclude' => $ap_exclude,
		'autopilot_include' => $ap_include_post,
	));
	$json = json_encode($cfg, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	$exists = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
	if ($exists) mysql_fn('update', 'variables', array('value' => $json), " AND `key`='" . mysql_res($key) . "' ");
	else mysql_fn('insert', 'variables', array('key' => $key, 'value' => $json));
	$saved = true;
}

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">Translations settings</h5>';
if ($saved) {
	$content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
}
$content .= '<form method="post">';
$content .= '<input type="hidden" name="save_translation_settings" value="1">';

// —— Source & chunking ——
$content .= '<div class="card mb-4">';
$content .= '<div class="card-header bg-light"><strong>Source language &amp; chunking</strong></div>';
$content .= '<div class="card-body">';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-6 col-lg-4"><label class="form-label" for="source_lang_id">Source language</label><select class="form-control" id="source_lang_id" name="source_lang_id">';
foreach ($langs as $l) {
	$sel = ((int)$l['id'] === (int)$cfg['source_lang_id']) ? ' selected' : '';
	$content .= '<option value="' . (int)$l['id'] . '"' . $sel . '>' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</option>';
}
$content .= '</select></div>';
$content .= '<div class="form-group col-md-6 col-lg-2"><label class="form-label" for="chunk_max_len">Chunk max length</label><input class="form-control" id="chunk_max_len" type="number" min="500" name="chunk_max_len" value="' . (int)$cfg['chunk_max_len'] . '"></div>';
$content .= '<div class="form-group col-md-6 col-lg-2"><label class="form-label" for="content_chunk_cap">Content chunk cap</label><input class="form-control" id="content_chunk_cap" type="number" min="350" max="4000" name="content_chunk_cap" value="' . (int)(isset($cfg['content_chunk_cap']) ? $cfg['content_chunk_cap'] : 900) . '" title="Max UTF-8 length per HTML content chunk (smaller reduces API stalls)."></div>';
$content .= '<div class="form-group col-md-6 col-lg-2"><label class="form-label" for="bisect_max_depth">Bisect max depth</label><input class="form-control" id="bisect_max_depth" type="number" min="2" max="12" name="bisect_max_depth" value="' . (int)(isset($cfg['bisect_max_depth']) ? $cfg['bisect_max_depth'] : 6) . '" title="On failure, split segment in half up to this many levels."></div>';
$content .= '<div class="form-group col-md-6 col-lg-2"><label class="form-label" for="bisect_min_chars">Bisect min chars</label><input class="form-control" id="bisect_min_chars" type="number" min="80" name="bisect_min_chars" value="' . (int)(isset($cfg['bisect_min_chars']) ? $cfg['bisect_min_chars'] : 280) . '" title="Stop splitting below ~2× this size; then use source fallback."></div>';
$content .= '</div>';
$content .= '</div></div>';

// —— English leak ——
$content .= '<div class="card mb-4">';
$content .= '<div class="card-header bg-light"><strong>English / Latin leak retry</strong></div>';
$content .= '<div class="card-body">';
$content .= '<div class="form-row align-items-end">';
$content .= '<div class="form-group col-md-5 mb-md-0"><div class="form-check mt-1">';
$content .= '<input class="form-check-input" type="checkbox" name="english_leak_retry" id="english_leak_retry" value="1"' . (!empty($cfg['english_leak_retry']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="english_leak_retry">Retry chunk when a long Latin run is detected</label></div></div>';
$content .= '<div class="form-group col-md-3 col-6"><label class="form-label" for="english_leak_min_words">Min words in a run</label><input class="form-control" id="english_leak_min_words" type="number" min="3" max="12" name="english_leak_min_words" value="' . (int)(isset($cfg['english_leak_min_words']) ? $cfg['english_leak_min_words'] : 4) . '" title="If this many Latin-letter words appear in a row (after stripping brands), the chunk is sent again."></div>';
$content .= '<div class="form-group col-md-2 col-6"><label class="form-label" for="english_leak_max_retries">Max retries / chunk</label><input class="form-control" id="english_leak_max_retries" type="number" min="0" max="3" name="english_leak_max_retries" value="' . (int)(isset($cfg['english_leak_max_retries']) ? $cfg['english_leak_max_retries'] : 1) . '"></div>';
$content .= '</div>';
$content .= '</div></div>';

// —— Translation vector (RAG) ——
$vector_stats = translation_vector_table_stats();
$content .= '<div class="card mb-4">';
$content .= '<div class="card-header bg-light"><strong>Translation vector memory (RAG)</strong></div>';
$content .= '<div class="card-body">';
$content .= '<p class="small text-muted mb-3">Examples for AI prompts live in <code>translation_vector_items</code> (currently ~'
	. number_format((int)$vector_stats['rows']) . ' rows, ~' . htmlspecialchars((string)$vector_stats['total_human']) . '). '
	. 'When disabled, translate jobs do not read or write this table (less disk use, no low-quality RAG examples). '
	. '<strong>Default: off.</strong></p>';
$content .= '<div class="form-group mb-3"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="translation_vector_enabled" id="translation_vector_enabled" value="1"'
	. (!empty($cfg['translation_vector_enabled']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="translation_vector_enabled">Use translation vector examples during AI translate jobs</label>';
$content .= '</div></div>';
$content .= '<div class="d-flex flex-wrap align-items-center gap-2">';
$content .= '<button type="submit" name="enqueue_clear_translation_vector" value="1" class="btn btn-outline-danger btn-sm"'
	. ' onclick="return confirm(\'Delete ALL rows from translation_vector_items? This cannot be undone. A background job will run in chunks.\');">'
	. 'Clear vector database (background job)</button>';
$content .= '<span class="small text-muted">Job: <code>translations / clear_vector_db</code> — see Admin → Jobs or wait for cron.</span>';
$content .= '</div>';
if ($vector_clear_enqueued) {
	$content .= '<div class="alert alert-success py-2 mt-3 mb-0">Vector clear job enqueued. Rows are deleted in chunks; table is OPTIMIZED when empty.</div>';
}
if ($vector_clear_error !== '') {
	$content .= '<div class="alert alert-warning py-2 mt-3 mb-0">' . $vector_clear_error . '</div>';
}
$content .= '</div></div>';

// —— Autopilot ——
$ap_loc_set = array_flip(array_map('intval', (array)(isset($cfg['autopilot_locale_ids']) ? $cfg['autopilot_locale_ids'] : array())));
$content .= '<div class="card mb-4 border-primary" id="autopilot" style="border-width: 2px;">';
$content .= '<div class="card-header bg-white"><strong>Translation autopilot</strong> <span class="badge badge-primary align-middle">cron</span></div>';
$content .= '<div class="card-body">';
$content .= '<div class="form-group"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="autopilot_enabled" id="autopilot_enabled" value="1"' . (!empty($cfg['autopilot_enabled']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label font-weight-bold" for="autopilot_enabled">Autopilot enabled</label></div></div>';
$content .= '<p class="small text-muted mb-0 mt-2">When off, autopilot does not enqueue new jobs (cluster, translate, meta-fix). The translation cron still reaps stuck jobs, purges old <strong>Translations → Activity</strong> data (see retention below), and runs <code>process_one_admin_job_filtered</code> on pending work; jobs from admin or telemetry still execute.</p>';

$content .= '<h6 class="text-uppercase text-muted small mt-3 mb-2" style="letter-spacing:.04em;">Sections</h6>';
$content .= '<div class="form-row">';
$ap_inc = isset($cfg['autopilot_include']) && is_array($cfg['autopilot_include']) ? $cfg['autopilot_include'] : translation_autopilot_normalize_include(array());
$ap_sections_labels = array(
	'pages' => 'Pages',
	'blog' => 'Blog',
	'guides' => 'Guides',
	'games' => 'Games',
	'casinos' => 'Casinos',
);
foreach ($ap_sections_labels as $sk => $slab) {
	$cid = 'ap_inc_' . $sk;
	$content .= '<div class="form-group col-6 col-md-4 mb-md-0"><div class="form-check">';
	$content .= '<input type="checkbox" class="form-check-input" name="autopilot_include[' . htmlspecialchars($sk) . ']" id="' . htmlspecialchars($cid) . '" value="1"' . (!empty($ap_inc[$sk]) ? ' checked' : '') . '>';
	$content .= '<label class="form-check-label" for="' . htmlspecialchars($cid) . '">' . htmlspecialchars($slab) . '</label></div></div>';
}
$content .= '</div>';

$content .= '<h6 class="text-uppercase text-muted small mt-2 mb-3" style="letter-spacing:.04em;">Limits &amp; blog batch</h6>';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_stuck_seconds">Stuck base (sec)</label><input class="form-control" id="autopilot_stuck_seconds" type="number" min="120" name="autopilot_stuck_seconds" value="' . (int)(isset($cfg['autopilot_stuck_seconds']) ? $cfg['autopilot_stuck_seconds'] : 900) . '" title="Legacy tuning field; reap thresholds are below (heartbeat + total)."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="translation_reap_heartbeat_seconds">Reap if no heartbeat (sec)</label><input class="form-control" id="translation_reap_heartbeat_seconds" type="number" min="60" max="1200" name="translation_reap_heartbeat_seconds" value="' . (int)(isset($cfg['translation_reap_heartbeat_seconds']) ? $cfg['translation_reap_heartbeat_seconds'] : 120) . '" title="No admin_jobs touch for this long ⇒ job failed. Keep above one LLM HTTP timeout (~60s+). Default 120."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="translation_reap_total_seconds">Max job run time (sec)</label><input class="form-control" id="translation_reap_total_seconds" type="number" min="600" max="86400" name="translation_reap_total_seconds" value="' . (int)(isset($cfg['translation_reap_total_seconds']) ? $cfg['translation_reap_total_seconds'] : 3600) . '" title="Absolute cap on wall time since job start (safety net). Default 3600."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_max_jobs_per_run">Max jobs to enqueue / tick</label><input class="form-control" id="autopilot_max_jobs_per_run" type="number" min="1" max="100" name="autopilot_max_jobs_per_run" value="' . (int)(isset($cfg['autopilot_max_jobs_per_run']) ? $cfg['autopilot_max_jobs_per_run'] : 20) . '"></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_process_jobs_per_tick">Jobs to process / tick</label><input class="form-control" id="autopilot_process_jobs_per_tick" type="number" min="0" max="20" name="autopilot_process_jobs_per_tick" value="' . (int)(isset($cfg['autopilot_process_jobs_per_tick']) ? $cfg['autopilot_process_jobs_per_tick'] : 6) . '" title="How many pending translation jobs this cron run executes after enqueue (also limited by max wall time below)."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_cron_max_wall_seconds">Cron job loop max (sec)</label><input class="form-control" id="autopilot_cron_max_wall_seconds" type="number" min="60" max="840" name="autopilot_cron_max_wall_seconds" value="' . (int)(isset($cfg['autopilot_cron_max_wall_seconds']) ? $cfg['autopilot_cron_max_wall_seconds'] : 360) . '" title="Stop after this wall time in one cron run (avoids overlapping crons and long single process). Default 360."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_max_pending_skip">Skip if pending jobs ≥</label><input class="form-control" id="autopilot_max_pending_skip" type="number" min="0" max="500" name="autopilot_max_pending_skip" value="' . (int)(isset($cfg['autopilot_max_pending_skip']) ? $cfg['autopilot_max_pending_skip'] : 50) . '" title="0 = never skip for backlog size"></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_blog_batch">Blog posts / language / tick</label><input class="form-control" id="autopilot_blog_batch" type="number" min="1" max="200" name="autopilot_blog_batch" value="' . (int)(isset($cfg['autopilot_blog_batch']) ? $cfg['autopilot_blog_batch'] : 10) . '"></div>';
$content .= '<div class="form-group col-md-6 col-lg-4"><label class="form-label" for="autopilot_activity_retention_days">Activity log retention (days)</label><input class="form-control" id="autopilot_activity_retention_days" type="number" min="0" max="365" name="autopilot_activity_retention_days" value="' . (int)(isset($cfg['autopilot_activity_retention_days']) ? $cfg['autopilot_activity_retention_days'] : 7) . '" title="Delete translation system_logs and completed Activity-tab jobs older than this. 0 = disable. Each translation cron tick."></div>';
$content .= '</div>';

$content .= '<h6 class="text-uppercase text-muted small mt-3 mb-2" style="letter-spacing:.04em;">Draft metadata fix (name / title / meta)</h6>';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_meta_fix_max_per_run">Meta-fix jobs max / tick</label><input class="form-control" id="autopilot_meta_fix_max_per_run" type="number" min="0" max="50" name="autopilot_meta_fix_max_per_run" value="' . (int)(isset($cfg['autopilot_meta_fix_max_per_run']) ? $cfg['autopilot_meta_fix_max_per_run'] : 6) . '" title="0 = disable meta-fix pass. Capped by max jobs / tick."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_meta_fix_scan_per_table">Rows scanned / entity / lang</label><input class="form-control" id="autopilot_meta_fix_scan_per_table" type="number" min="5" max="120" name="autopilot_meta_fix_scan_per_table" value="' . (int)(isset($cfg['autopilot_meta_fix_scan_per_table']) ? $cfg['autopilot_meta_fix_scan_per_table'] : 40) . '"></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_meta_fix_cooldown_days">Cooldown (days)</label><input class="form-control" id="autopilot_meta_fix_cooldown_days" type="number" min="0" max="90" name="autopilot_meta_fix_cooldown_days" value="' . (int)(isset($cfg['autopilot_meta_fix_cooldown_days']) ? $cfg['autopilot_meta_fix_cooldown_days'] : 7) . '" title="0 = no cooldown between auto meta-fixes for the same row."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_meta_fix_max_lifetime_runs">Max auto meta-fixes / row</label><input class="form-control" id="autopilot_meta_fix_max_lifetime_runs" type="number" min="0" max="50" name="autopilot_meta_fix_max_lifetime_runs" value="' . (int)(isset($cfg['autopilot_meta_fix_max_lifetime_runs']) ? $cfg['autopilot_meta_fix_max_lifetime_runs'] : 5) . '" title="0 = unlimited (not recommended)."></div>';
$content .= '<div class="form-group col-md-6 col-lg-3"><label class="form-label" for="autopilot_normal_jobs_before_draft_meta">Full translates before draft meta pass</label><input class="form-control" id="autopilot_normal_jobs_before_draft_meta" type="number" min="1" max="500" name="autopilot_normal_jobs_before_draft_meta" value="' . (int)(isset($cfg['autopilot_normal_jobs_before_draft_meta']) ? $cfg['autopilot_normal_jobs_before_draft_meta'] : 10) . '" title="After this many entity/blog translate jobs (cumulative), the next tick runs draft meta-fix first. Counter stored in settings JSON."></div>';
$content .= '</div>';

$content .= '<div class="form-group mb-0"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="autopilot_respect_monitor" id="autopilot_respect_monitor" value="1"' . (!empty($cfg['autopilot_respect_monitor']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="autopilot_respect_monitor">Pause autopilot while Translation monitor has queued or running candidates</label></div></div>';

$content .= '<h6 class="text-uppercase text-muted small mt-4 mb-2" style="letter-spacing:.04em;">Cluster validation &amp; publish</h6>';
$content .= '<div class="form-group mb-2"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="cluster_validation_seo_full" id="cluster_validation_seo_full" value="1"' . (!empty($cfg['cluster_validation_seo_full']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="cluster_validation_seo_full"><strong>Full SEO Monitor on cluster HTML</strong> — require single H1, image alt, non-empty body (same rules as SEO Monitor), not only meta title/description length. Stricter; may keep clusters in <code>needs_review</code> until fixed.</label></div></div>';
$content .= '<div class="form-group mb-0"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="autopilot_cluster_autopublish" id="autopilot_cluster_autopublish" value="1"' . (!empty($cfg['autopilot_cluster_autopublish']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="autopilot_cluster_autopublish"><strong>Autopublish after validate</strong> — when autopilot <code>validate_cluster</code> / <code>cluster_pipeline</code> finishes (autopilot jobs only), set all <code>content_i18n</code> rows for that entity to <code>published</code> if cluster status is <code>ready_to_publish</code> (no blockers, no warnings), or <code>needs_review</code> with <strong>no blockers</strong> (warnings-only: e.g. leftover SEO noise). Also runs at repair-round cap.</label></div></div>';

$content .= '<hr class="my-4">';
$content .= '<h6 class="text-uppercase text-muted small mb-3" style="letter-spacing:.04em;">Autopilot target locales</h6>';
$content .= '<div class="border rounded p-3 bg-light">';
$content .= '<div class="form-row">';
foreach ($langs as $l) {
	$lid = (int)$l['id'];
	if ((int)$l['id'] === (int)$cfg['source_lang_id']) {
		continue;
	}
	$checked = empty($cfg['autopilot_locale_ids']) ? true : isset($ap_loc_set[$lid]);
	$content .= '<div class="col-md-4 col-lg-3 mb-2"><div class="form-check">';
	$content .= '<input class="form-check-input" type="checkbox" name="autopilot_locale_ids[]" id="ap_loc_' . $lid . '" value="' . $lid . '"' . ($checked ? ' checked' : '') . '>';
	$content .= '<label class="form-check-label" for="ap_loc_' . $lid . '">' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</label>';
	$content .= '</div></div>';
}
$content .= '</div></div>';

$excl_disp = translation_autopilot_exclude_text_from_array(
	isset($cfg['autopilot_exclude']) && is_array($cfg['autopilot_exclude']) ? $cfg['autopilot_exclude'] : array()
);
$content .= '<hr class="my-4">';
$content .= '<h6 class="text-uppercase text-muted small mb-2" style="letter-spacing:.04em;">Autopilot exclusions</h6>';
$content .= '<div class="form-group mb-0"><label class="form-label" for="autopilot_exclude_raw">Excluded entity rows</label>';
$content .= '<textarea class="form-control font-monospace small" id="autopilot_exclude_raw" name="autopilot_exclude_raw" rows="6" spellcheck="false" placeholder="pages:1">' . htmlspecialchars($excl_disp, ENT_QUOTES, 'UTF-8') . '</textarea></div>';

$content .= '</div></div>';

// —— Enabled languages ——
$content .= '<div class="card mb-4">';
$content .= '<div class="card-header bg-light"><strong>Enabled languages</strong></div>';
$content .= '<div class="card-body">';
$enabled_set = array_flip(array_map('intval', (array)$cfg['enabled_lang_ids']));
$content .= '<div class="border rounded p-3 bg-white">';
$content .= '<div class="form-row">';
foreach ($langs as $l) {
	$lid = (int)$l['id'];
	$checked = empty($cfg['enabled_lang_ids']) ? true : isset($enabled_set[$lid]);
	$content .= '<div class="col-md-4 col-lg-3 mb-2"><div class="form-check">';
	$content .= '<input class="form-check-input" type="checkbox" name="enabled_lang_ids[]" id="tl_' . $lid . '" value="' . $lid . '"' . ($checked ? ' checked' : '') . '>';
	$content .= '<label class="form-check-label" for="tl_' . $lid . '">' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</label>';
	$content .= '</div></div>';
}
$content .= '</div></div>';
$content .= '</div></div>';

$content .= '<div class="mb-2"><button class="btn btn-primary" type="submit">Save all settings</button></div>';
$content .= '</form>';
$content .= '</div>';

