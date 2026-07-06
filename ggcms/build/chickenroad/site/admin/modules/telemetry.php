<?php
/**
 * Site telemetry: settings + snapshot preview.
 */
$page_name = 'Telemetry';

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
if (!$variables_exists) {
	$content = '<div class="alert alert-warning">Table <code>variables</code> not found. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

require_once ROOT_DIR . 'functions/site_telemetry.php';

$cfg = site_telemetry_load_settings();
$saved = false;

if (!empty($_POST['save_site_telemetry'])) {
	$cfg['enabled'] = !empty($_POST['telemetry_enabled']) ? 1 : 0;
	$cfg['endpoint_enabled'] = !empty($_POST['telemetry_endpoint_enabled']) ? 1 : 0;
	$cfg['control_enabled'] = !empty($_POST['telemetry_control_enabled']) ? 1 : 0;
	$tok = isset($_POST['telemetry_token']) ? trim((string)$_POST['telemetry_token']) : '';
	if ($tok !== '') {
		$cfg['auth_token'] = $tok;
	}
	$cfg['retention_days'] = isset($_POST['telemetry_retention_days']) ? (int)$_POST['telemetry_retention_days'] : 7;
	$cfg['request_sample_pct'] = isset($_POST['telemetry_request_sample_pct']) ? (int)$_POST['telemetry_request_sample_pct'] : 10;
	$cfg['request_slow_ms'] = isset($_POST['telemetry_request_slow_ms']) ? (int)$_POST['telemetry_request_slow_ms'] : 2000;
	$cfg['snapshot_limit'] = isset($_POST['telemetry_snapshot_limit']) ? (int)$_POST['telemetry_snapshot_limit'] : 25;
	if (!empty($_POST['telemetry_regenerate_token'])) {
		$cfg['auth_token'] = site_telemetry_generate_token();
	}
	$cfg = site_telemetry_save_settings($cfg);
	$saved = true;
}

$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$snapshot_path = '/api/telemetry_snapshot';
$snapshot_url = $host !== '' ? ($scheme . '://' . $host . $snapshot_path) : $snapshot_path;
$token_masked = $cfg['auth_token'] !== '' ? (substr($cfg['auth_token'], 0, 6) . '…') : '(not set)';
$tlimit = 150;
$snapshot_query_example = 'token=YOUR_TOKEN&limit=' . (int)$cfg['snapshot_limit'] . '&translation_limit=' . $tlimit;
$snapshot_full_url = '';
if ($cfg['auth_token'] !== '') {
	$snapshot_full_url = $snapshot_url . '?' . http_build_query(array(
		'token' => $cfg['auth_token'],
		'limit' => (int)$cfg['snapshot_limit'],
		'translation_limit' => $tlimit,
	), '', '&', PHP_QUERY_RFC3986);
}

$snap = null;
if (!empty($cfg['enabled'])) {
	$snap = site_telemetry_collect_snapshot(array('limit' => (int)$cfg['snapshot_limit']));
}

$content = '<div class="card mb-4"><div class="card-body">';
$content .= '<h5 class="mb-3">Site telemetry</h5>';
if ($saved) {
	$content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
}
$content .= '<p class="text-muted small">Collects sampled HTTP requests, admin job runs, AI gateway HTTP calls, and cron tick summaries. Use the token URL for external diagnostics.</p>';
$content .= '<form method="post">';
$content .= '<input type="hidden" name="save_site_telemetry" value="1">';
$content .= '<div class="form-group"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="telemetry_enabled" id="telemetry_enabled" value="1"' . (!empty($cfg['enabled']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label font-weight-bold" for="telemetry_enabled">Telemetry enabled</label></div></div>';
$content .= '<div class="form-group"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="telemetry_endpoint_enabled" id="telemetry_endpoint_enabled" value="1"' . (!empty($cfg['endpoint_enabled']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="telemetry_endpoint_enabled">Allow JSON snapshot API (<code>/api/telemetry_snapshot</code>)</label></div></div>';
$content .= '<div class="form-group"><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="telemetry_control_enabled" id="telemetry_control_enabled" value="1"' . (!empty($cfg['control_enabled']) ? ' checked' : '') . '>';
$content .= '<label class="form-check-label" for="telemetry_control_enabled">Allow control API — remote autopilot tick &amp; job processing (<code>/api/telemetry_control</code>, same token)</label></div></div>';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-6"><label class="form-label" for="telemetry_token">Auth token (leave empty to keep current)</label>';
$content .= '<input class="form-control font-monospace small" type="text" name="telemetry_token" id="telemetry_token" value="" autocomplete="off" placeholder="Current: ' . htmlspecialchars($token_masked, ENT_QUOTES, 'UTF-8') . '"></div>';
$content .= '<div class="form-group col-md-6"><label class="form-label">&nbsp;</label><div class="form-check">';
$content .= '<input class="form-check-input" type="checkbox" name="telemetry_regenerate_token" id="telemetry_regenerate_token" value="1">';
$content .= '<label class="form-check-label" for="telemetry_regenerate_token">Regenerate token</label></div></div>';
$content .= '</div>';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="telemetry_retention_days">Retention (days)</label><input class="form-control" id="telemetry_retention_days" type="number" min="1" max="90" name="telemetry_retention_days" value="' . (int)$cfg['retention_days'] . '"></div>';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="telemetry_request_sample_pct">Request sample %</label><input class="form-control" id="telemetry_request_sample_pct" type="number" min="0" max="100" name="telemetry_request_sample_pct" value="' . (int)$cfg['request_sample_pct'] . '" title="0 = only slow/fatal requests"></div>';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="telemetry_request_slow_ms">Slow request (ms)</label><input class="form-control" id="telemetry_request_slow_ms" type="number" min="100" max="60000" name="telemetry_request_slow_ms" value="' . (int)$cfg['request_slow_ms'] . '"></div>';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="telemetry_snapshot_limit">Snapshot rows</label><input class="form-control" id="telemetry_snapshot_limit" type="number" min="5" max="100" name="telemetry_snapshot_limit" value="' . (int)$cfg['snapshot_limit'] . '"></div>';
$content .= '</div>';
$content .= '<button class="btn btn-primary" type="submit">Save</button>';
$content .= '</form>';
$content .= '</div></div>';

$content .= '<div class="card mb-4"><div class="card-body">';
$content .= '<h6 class="mb-2">Snapshot URL</h6>';
if ($snapshot_full_url !== '') {
	$content .= '<p class="small text-muted mb-2">Ready to use (token included). Copy and share with diagnostics tools.</p>';
	$content .= '<div class="input-group mb-2">';
	$content .= '<input type="text" class="form-control font-monospace small" id="telemetry_snapshot_url_full" readonly value="' . htmlspecialchars($snapshot_full_url, ENT_QUOTES, 'UTF-8') . '">';
	$content .= '<div class="input-group-append">';
	$content .= '<button class="btn btn-outline-secondary" type="button" id="telemetry_snapshot_copy_btn" data-copy-target="telemetry_snapshot_url_full">Copy</button>';
	$content .= '</div></div>';
	$content .= '<p class="small text-success mb-0 d-none" id="telemetry_snapshot_copied" role="status">Copied to clipboard.</p>';
} else {
	$content .= '<p class="small text-muted mb-2">Save an auth token above to generate a copyable URL with <code>token=…</code>.</p>';
	$content .= '<p class="small mb-1"><code>' . htmlspecialchars($snapshot_url . '?' . $snapshot_query_example, ENT_QUOTES, 'UTF-8') . '</code></p>';
}
	$page_seo_path = '/api/telemetry_page_seo';
	$page_seo_example = $host !== '' ? ($scheme . '://' . $host . $page_seo_path) : $page_seo_path;
	$page_seo_q = $page_seo_example . '?' . http_build_query(array(
		'token' => $cfg['auth_token'],
		'url' => ($host !== '' ? ($scheme . '://' . $host . '/en/casinos/') : 'https://example.com/en/casinos/'),
	), '', '&', PHP_QUERY_RFC3986);
	$content .= '<h6 class="mt-4 mb-2">Page SEO report (DB + export vs live HTML)</h6>';
	$content .= '<p class="small text-muted mb-2">Same token as snapshot. Use <code>url=</code> (full public URL) or <code>entity</code> + <code>entity_id</code> and optional <code>lang_id</code>. <code>fetch=0</code> skips HTTP. <code>normalize=1</code> runs admin-style list scan (may write trimmed meta).</p>';
	if ($cfg['auth_token'] !== '') {
		$content .= '<div class="input-group mb-2">';
		$content .= '<input type="text" class="form-control font-monospace small" id="telemetry_page_seo_url_full" readonly value="' . htmlspecialchars($page_seo_q, ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<div class="input-group-append"><button class="btn btn-outline-secondary" type="button" id="telemetry_page_seo_copy_btn" data-copy-target="telemetry_page_seo_url_full">Copy</button></div></div>';
	} else {
		$content .= '<p class="small mb-2"><code>' . htmlspecialchars($page_seo_example . '?token=…&amp;url=https://…', ENT_QUOTES, 'UTF-8') . '</code></p>';
	}
	$admin_list_path = '/api/telemetry_admin_list';
	$admin_list_example = $host !== '' ? ($scheme . '://' . $host . $admin_list_path) : $admin_list_path;
	$admin_list_q = $admin_list_example . '?' . http_build_query(array(
		'token' => $cfg['auth_token'],
		'section' => 'content_casinos',
		'sample_limit' => 20,
	), '', '&', PHP_QUERY_RFC3986);
	$content .= '<h6 class="mt-4 mb-2">Admin Content list (sort + SQL + DB sample)</h6>';
	$content .= '<p class="small text-muted mb-2">On-demand only, same token. <code>section</code>: <code>content_casinos</code>, <code>content_blog</code>, <code>content_guides</code>, <code>content_games</code>. Optional: <code>o</code>, <code>s</code>, <code>n</code>, <code>c</code>, <code>search</code>, <code>search_id</code>, <code>category</code>. Response: effective sort, full list SQL, first/last page ids, <code>admin_func_sha256_12</code>.</p>';
	if ($cfg['auth_token'] !== '') {
		$content .= '<div class="input-group mb-2">';
		$content .= '<input type="text" class="form-control font-monospace small" id="telemetry_admin_list_url_full" readonly value="' . htmlspecialchars($admin_list_q, ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<div class="input-group-append"><button class="btn btn-outline-secondary" type="button" id="telemetry_admin_list_copy_btn" data-copy-target="telemetry_admin_list_url_full">Copy</button></div></div>';
	} else {
		$content .= '<p class="small mb-2"><code>' . htmlspecialchars($admin_list_example . '?token=…&amp;section=content_casinos', ENT_QUOTES, 'UTF-8') . '</code></p>';
	}
	$content .= '<p class="small text-muted mb-0 mt-2">Optional <code>translation_limit</code> (50–300) widens the <code>translations</code> section (cluster state, jobs queue, manual monitor—use this URL for cluster / Mark review / SEO handoff diagnostics without server access). Example uses ' . (int)$tlimit . '. Header <code>X-Telemetry-Token</code> also works.</p>';
	$content .= '<p class="small text-muted mb-0 mt-2">Snapshot: <code>translations.jobs.queue_health</code>. Control: <code>enqueue_translation_job</code> (<code>job_action</code>: cluster_pipeline, validate_cluster, validate_locale, repair_locale, translate + <code>metadata_normalize</code>, translate_cluster), <code>cluster_drive</code>, <code>meta_fix_tick</code> (meta-only enqueue, works if autopilot off), <code>autopilot_tick</code>, <code>job_inspect</code>.</p>';
		if (!empty($cfg['control_enabled'])) {
		$control_base = str_replace('/api/telemetry_snapshot', '/api/telemetry_control', $snapshot_url);
		$content .= '<p class="small text-muted mt-3 mb-0"><strong>Control API</strong>: <code>cluster_drive</code> / <code>meta_fix_tick</code> / <code>cluster_simulate</code> / <code>cluster_snapshot</code> / <code>seo_page_meta_patch</code> (JSON body: <code>entity</code>, <code>entity_id</code>, <code>lang_id</code>, <code>fields</code>). Prompts: <code>ai_prompt_templates_get</code> / <code>ai_prompt_templates_set</code> / <code>ai_prompt_templates_reset</code>.</p>';
	}
$content .= '</div></div>';
if ($snapshot_full_url !== '') {
	$content .= '<script>(function(){var b=document.getElementById("telemetry_snapshot_copy_btn"),i=document.getElementById("telemetry_snapshot_url_full"),m=document.getElementById("telemetry_snapshot_copied");if(!b||!i)return;function showOk(){if(m){m.classList.remove("d-none");setTimeout(function(){m.classList.add("d-none");},2000);}b.textContent="Copied";setTimeout(function(){b.textContent="Copy";},1500);}b.addEventListener("click",function(){var t=i.value;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(showOk).catch(function(){i.select();document.execCommand("copy");showOk();});}else{i.select();document.execCommand("copy");showOk();}});})();</script>';
}
if (!empty($cfg['auth_token'])) {
	$content .= '<script>(function(){var b=document.getElementById("telemetry_page_seo_copy_btn"),i=document.getElementById("telemetry_page_seo_url_full");if(!b||!i)return;b.addEventListener("click",function(){var t=i.value;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(function(){b.textContent="Copied";setTimeout(function(){b.textContent="Copy";},1500);}).catch(function(){i.select();document.execCommand("copy");});}else{i.select();document.execCommand("copy");}});})();</script>';
	$content .= '<script>(function(){var b=document.getElementById("telemetry_admin_list_copy_btn"),i=document.getElementById("telemetry_admin_list_url_full");if(!b||!i)return;b.addEventListener("click",function(){var t=i.value;if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(function(){b.textContent="Copied";setTimeout(function(){b.textContent="Copy";},1500);}).catch(function(){i.select();document.execCommand("copy");});}else{i.select();document.execCommand("copy");}});})();</script>';
}

if (is_array($snap)) {
	$content .= '<div class="card"><div class="card-body">';
	$content .= '<h6 class="mb-2">Live preview (same payload as API)</h6>';
	$content .= '<pre class="bg-light p-3 small mb-0" style="max-height:480px;overflow:auto">' . htmlspecialchars(json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') . '</pre>';
	$content .= '</div></div>';
} elseif (!empty($cfg['enabled']) && !empty($cfg['endpoint_enabled']) && $cfg['auth_token'] === '') {
	$content .= '<div class="alert alert-warning">Set an auth token to use the snapshot API and preview.</div>';
}
