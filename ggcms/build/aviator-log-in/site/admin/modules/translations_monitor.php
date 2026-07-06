<?php
/**
 * Translations Monitor: create Translation Orders and manage Candidates queue.
 */
$page_name = 'Translations: Monitor';

require_once ROOT_DIR . 'functions/translation_hub.php';

// Legacy URL → hub (keep JSON/live handlers below).
if (!defined('TRANSLATIONS_HUB')) {
	$u0 = isset($_GET['u']) ? (string)$_GET['u'] : '';
	if ($u0 !== 'candidate_live_poll' && $u0 !== 'candidate_live') {
		$q = $_GET;
		$prev_tab = isset($q['tab']) ? (string)$q['tab'] : 'orders';
		$q['m'] = 'translations';
		$q['tab'] = 'monitor';
		if (in_array($prev_tab, array('orders', 'candidates', 'jobs'), true)) {
			$q['mtab'] = $prev_tab;
		}
		header('Location: /admin.php?' . http_build_query($q));
		exit;
	}
}

$get = array_merge(array(
	'u' => '',
	'tab' => 'orders',
	'order_id' => '',
	'status' => 'pending',
	'limit' => '50',
	'mtab' => '',
), (array)$get);

$has_orders = @mysql_select("SHOW TABLES LIKE 'translation_orders'", 'num_rows') > 0;
$has_candidates = @mysql_select("SHOW TABLES LIKE 'translation_order_candidates'", 'num_rows') > 0;
$table_content_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
if (!$has_orders || !$has_candidates || !$table_content_ok) {
	$content = '<div class="alert alert-warning"><strong>Missing translation monitor tables.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	if (!defined('TRANSLATIONS_HUB')) {
		require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
		exit;
	}
	return;
}

require_once(ROOT_DIR . 'functions/admin_jobs.php');
require_once(ROOT_DIR . 'functions/system_log.php');

// Load translation settings (source language defaults, chunk size, enabled langs)
$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
$cfg = array('source_lang_id' => 1, 'enabled_lang_ids' => array(), 'chunk_max_len' => 2500);
if ($row && $row['value'] !== '') {
	$dec = json_decode($row['value'], true);
	if (is_array($dec)) $cfg = array_merge($cfg, $dec);
}

$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
$enabled_set = array();
foreach ((array)@$cfg['enabled_lang_ids'] as $lid) {
	$lid = (int)$lid;
	if ($lid > 0) $enabled_set[$lid] = true;
}
$enabled_langs = array();
foreach ($langs as $l) {
	$lid = (int)$l['id'];
	if (empty($enabled_set)) $enabled_langs[] = $l;
	else if (isset($enabled_set[$lid])) $enabled_langs[] = $l;
}

if (defined('TRANSLATIONS_HUB')) {
	$tab = isset($get['mtab']) && (string)$get['mtab'] !== '' ? (string)$get['mtab'] : 'orders';
} else {
	$tab = isset($get['tab']) && (string)$get['tab'] !== '' ? (string)$get['tab'] : 'orders';
}
if (!in_array($tab, array('orders', 'candidates', 'jobs'), true)) {
	$tab = 'orders';
}
$order_id = !empty($get['order_id']) ? (int)$get['order_id'] : 0;

// --- Live candidate window (real-time-ish via polling) ---
// JSON poll endpoint
if (isset($get['u']) && (string)$get['u'] === 'candidate_live_poll') {
	$candidate_id = !empty($get['candidate_id']) ? (int)$get['candidate_id'] : (!empty($get['id']) ? (int)$get['id'] : 0);
	if ($candidate_id <= 0) {
		header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
			header('Pragma: no-cache');
		echo json_encode(array('ok' => false, 'message' => 'Bad candidate_id'));
		exit;
	}
	$cand = mysql_select("SELECT * FROM translation_order_candidates WHERE id=" . (int)$candidate_id . " LIMIT 1", 'row');

	// Active job for this candidate (pending/running)
	$job = mysql_select("
		SELECT id, status, message, locked_at, started_at, finished_at
		FROM admin_jobs
		WHERE module='translations' AND action='translate'
		  AND (
			 payload LIKE '%\"candidate_id\":' . (int)$candidate_id . '%'
			 OR payload LIKE '%\"candidate_id\":\"' . (int)$candidate_id . '%'
		  )
		  AND status IN ('pending','running')
		ORDER BY id DESC
		LIMIT 1
	", 'row');
	// If payload matching didn't find it, fall back to stored last_job_id.
	if ((!$job || empty($job['id'])) && !empty($cand['last_job_id'])) {
		$jid = (int)$cand['last_job_id'];
		if ($jid > 0) {
			$job = mysql_select("
				SELECT id, status, message, locked_at, started_at, finished_at
				FROM admin_jobs
				WHERE id=" . (int)$jid . "
				LIMIT 1
			", 'row');
		}
	}

	$job_id_for_logs = (int)($job['id'] ?? ($cand['last_job_id'] ?? 0));
	$where_logs = " channel='translations' AND ("
		. "message LIKE '%cand#" . (int)$candidate_id . "%'"
		. " OR context LIKE '%\"candidate_id\":" . (int)$candidate_id . "%'"
		. " OR context LIKE '%\"candidate_id\":\"" . (int)$candidate_id . "\"%'";
	if ($job_id_for_logs > 0) {
		$where_logs .= " OR message LIKE '%job#" . (int)$job_id_for_logs . "%'"
			. " OR context LIKE '%\"job_id\":" . (int)$job_id_for_logs . "%'"
			. " OR context LIKE '%\"job_id\":\"" . (int)$job_id_for_logs . "\"%'";
	}
	$where_logs .= ") ";
	$logs = mysql_select("
		SELECT id, created_at, level, message, context
		FROM system_logs
		WHERE {$where_logs}
		ORDER BY id DESC
		LIMIT 30
	", 'rows') ?: array();
	$debug_tail = array();
	if (empty($logs)) {
		// Diagnostic tail: helps distinguish "no logs exist" vs "filter doesn't match".
		$debug_tail = mysql_select("
			SELECT id, created_at, level, message
			FROM system_logs
			WHERE channel='translations'
			ORDER BY id DESC
			LIMIT 5
		", 'rows') ?: array();
	}
	// Make JSON response safe: system_logs.context may contain invalid UTF-8 or huge blobs.
	// Live monitor only needs a preview for diagnostics.
	if (!empty($logs)) {
		foreach ($logs as $k => $l) {
			if (!is_array($l)) continue;
			$msg = isset($l['message']) ? (string)$l['message'] : '';
			$ctx = isset($l['context']) ? (string)$l['context'] : '';
			// Limit context payload to keep poll fast and JSON stable.
			if ($ctx !== '' && strlen($ctx) > 12000) {
				$ctx = substr($ctx, 0, 12000) . "\n…(truncated)…";
			}
			// Strip invalid UTF-8 (json_encode can fail otherwise).
			if (function_exists('iconv')) {
				$msg = iconv('UTF-8', 'UTF-8//IGNORE', $msg);
				$ctx = iconv('UTF-8', 'UTF-8//IGNORE', $ctx);
				if ($msg === false) $msg = '';
				if ($ctx === false) $ctx = '';
			}
			$logs[$k]['message'] = $msg;
			$logs[$k]['context'] = $ctx;
		}
	}

	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	$payload = array(
		'ok' => true,
		'candidate' => array(
			'id' => (int)($cand['id'] ?? 0),
			'order_id' => (int)($cand['order_id'] ?? 0),
			'entity' => (string)($cand['entity'] ?? ''),
			'entity_id' => (int)($cand['entity_id'] ?? 0),
			'candidate_status' => (string)($cand['candidate_status'] ?? ''),
			'i18n_status' => (string)($cand['i18n_status'] ?? ''),
			'last_error' => (string)($cand['last_error'] ?? ''),
			'last_job_id' => (int)($cand['last_job_id'] ?? 0),
			'updated_at' => (string)($cand['updated_at'] ?? ''),
		),
		'job' => array(
			'id' => (int)($job['id'] ?? ($cand['last_job_id'] ?? 0)),
			'status' => (string)($job['status'] ?? ''),
			'message' => (string)($job['message'] ?? ''),
			'locked_at' => (string)($job['locked_at'] ?? ''),
			'started_at' => (string)($job['started_at'] ?? ''),
			'finished_at' => (string)($job['finished_at'] ?? ''),
		),
		'logs' => $logs,
		'debug_tail' => $debug_tail,
	);
	$j = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | (defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0));
	if ($j === false) {
		// Last-resort fallback: drop contexts entirely.
		foreach ((array)$payload['logs'] as $i => $l) {
			$payload['logs'][$i]['context'] = '';
		}
		$j = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
	echo $j;
	exit;
}

// Full HTML live window
if (isset($get['u']) && (string)$get['u'] === 'candidate_live') {
	$candidate_id = !empty($get['candidate_id']) ? (int)$get['candidate_id'] : (!empty($get['id']) ? (int)$get['id'] : 0);
	$order_id_live = !empty($get['order_id']) ? (int)$get['order_id'] : 0;
	if ($candidate_id <= 0) {
		$content = '<div class="alert alert-danger">Bad candidate_id.</div>';
		if (!defined('TRANSLATIONS_HUB')) {
			require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
			exit;
		}
		return;
	}
	// CSP on some admin setups blocks inline JS, which can break polling.
	// Provide a JS-free fallback: auto-refresh the whole page periodically.
	$autorefresh = isset($get['autorefresh']) ? (string)$get['autorefresh'] : '1';
	if ($autorefresh !== '0') {
		// Refresh every 5 seconds to simulate "live" updates.
		// NOTE: This is a response header (not affected by CSP).
		header('Refresh: 5');
	}
	$pollUrl = '/admin.php?m=translations&tab=monitor&u=candidate_live_poll&candidate_id=' . (int)$candidate_id . '&order_id=' . (int)$order_id_live;

	// Server-side first paint: show current candidate/job/logs even if JS/polling is blocked.
	$cand_ssr = mysql_select("SELECT * FROM translation_order_candidates WHERE id=" . (int)$candidate_id . " LIMIT 1", 'row');
	$job_ssr = mysql_select("
		SELECT id, status, message, locked_at, started_at, finished_at
		FROM admin_jobs
		WHERE module='translations' AND action='translate'
		  AND (
			 payload LIKE '%\"candidate_id\":' . (int)$candidate_id . '%'
			 OR payload LIKE '%\"candidate_id\":\"' . (int)$candidate_id . '%'
		  )
		  AND status IN ('pending','running')
		ORDER BY id DESC
		LIMIT 1
	", 'row');
	if ((!$job_ssr || empty($job_ssr['id'])) && !empty($cand_ssr['last_job_id'])) {
		$jid = (int)$cand_ssr['last_job_id'];
		if ($jid > 0) {
			$job_ssr = mysql_select("SELECT id, status, message, locked_at, started_at, finished_at FROM admin_jobs WHERE id=" . (int)$jid . " LIMIT 1", 'row');
		}
	}
	$job_id_ssr = (int)($job_ssr['id'] ?? ($cand_ssr['last_job_id'] ?? 0));
	$where_logs_ssr = " channel='translations' AND ("
		. "message LIKE '%cand#" . (int)$candidate_id . "%'"
		. " OR context LIKE '%\"candidate_id\":" . (int)$candidate_id . "%'"
		. " OR context LIKE '%\"candidate_id\":\"" . (int)$candidate_id . "\"%'";
	if ($job_id_ssr > 0) {
		$where_logs_ssr .= " OR message LIKE '%job#" . (int)$job_id_ssr . "%'"
			. " OR context LIKE '%\"job_id\":" . (int)$job_id_ssr . "%'"
			. " OR context LIKE '%\"job_id\":\"" . (int)$job_id_ssr . "\"%'";
	}
	$where_logs_ssr .= ") ";
	$logs_ssr = mysql_select("
		SELECT id, created_at, level, message, context
		FROM system_logs
		WHERE {$where_logs_ssr}
		ORDER BY id DESC
		LIMIT 30
	", 'rows') ?: array();
	$ssr_lines = array();
	if (!empty($logs_ssr)) {
		// display from older to newer
		for ($i = count($logs_ssr) - 1; $i >= 0; $i--) {
			$l = $logs_ssr[$i];
			if (!is_array($l)) continue;
			$lvl = strtoupper((string)($l['level'] ?? ''));
			$msg = '[' . (string)($l['created_at'] ?? '') . '] ' . $lvl . ' ' . (string)($l['message'] ?? '');
			$ctx = isset($l['context']) ? (string)$l['context'] : '';
			if ($ctx !== '') {
				if (strlen($ctx) > 12000) $ctx = substr($ctx, 0, 12000) . "\n…(truncated)…";
				$msg .= "\n" . preg_replace('/^/m', '  ', $ctx);
			}
			$ssr_lines[] = $msg;
		}
	}
	if (empty($ssr_lines)) {
		$ssr_lines[] = 'No logs yet. This is expected while the job is still pending/queued. Once it starts, you should see lines like "Translate start" and "AI request start".';
	}

	$ssr_cand_label = '…';
	if (is_array($cand_ssr)) {
		$ssr_cand_label = (string)($cand_ssr['entity'] ?? '') . '#' . (int)($cand_ssr['entity_id'] ?? 0)
			. ' cand_status=' . (string)($cand_ssr['candidate_status'] ?? '')
			. ' i18n=' . (string)($cand_ssr['i18n_status'] ?? '');
	}
	$ssr_job_label = '…';
	if (is_array($job_ssr)) {
		$ssr_job_label = '#' . (int)($job_ssr['id'] ?? 0) . ' ' . (string)($job_ssr['status'] ?? '');
		if (!empty($job_ssr['message'])) $ssr_job_label .= ' — ' . (string)$job_ssr['message'];
		if (!empty($job_ssr['locked_at'])) $ssr_job_label .= ' [locked ' . (string)$job_ssr['locked_at'] . ']';
		if (!empty($job_ssr['started_at'])) $ssr_job_label .= ' [started ' . (string)$job_ssr['started_at'] . ']';
	}
	$content = '<div class="card mb-3"><div class="card-body">';
	$content .= '<h5 class="mb-2">Live translation candidate #' . (int)$candidate_id . '</h5>';
	if ($autorefresh !== '0') {
		$content .= '<div class="text-muted small mb-2">Auto-refresh: ON (every 5s). <a class="text-muted" href="/admin.php?m=translations&tab=monitor&amp;u=candidate_live&amp;candidate_id=' . (int)$candidate_id . '&amp;order_id=' . (int)$order_id_live . '&amp;autorefresh=0">turn off</a></div>';
	} else {
		$content .= '<div class="text-muted small mb-2">Auto-refresh: OFF. <a class="text-muted" href="/admin.php?m=translations&tab=monitor&amp;u=candidate_live&amp;candidate_id=' . (int)$candidate_id . '&amp;order_id=' . (int)$order_id_live . '&amp;autorefresh=1">turn on</a></div>';
	}
	$content .= '<div><strong>Candidate</strong>: <span id="lc_cand">' . htmlspecialchars($ssr_cand_label, ENT_QUOTES, 'UTF-8') . '</span></div>';
	$content .= '<div><strong>Job</strong>: <span id="lc_job">' . htmlspecialchars($ssr_job_label, ENT_QUOTES, 'UTF-8') . '</span></div>';
	$content .= '<div class="small mt-1"><strong>Poll</strong>: <span id="lc_poll" class="text-muted">waiting…</span></div>';
	$content .= '<div class="border rounded p-2 mt-2" style="height:420px; overflow:auto; background:#0b0b0b; color:#ffffff;">';
	$content .= '<pre id="lc_logs" style="margin:0; white-space:pre-wrap; color:#ffffff; font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,\\\"Liberation Mono\\\",\\\"Courier New\\\",monospace; font-size:12.5px; line-height:1.35;">' . htmlspecialchars(implode("\n\n", $ssr_lines), ENT_QUOTES, 'UTF-8') . '</pre>';
	$content .= '</div>';
	$content .= '</div></div>';
	$content .= '<script>
	(function(){
		try {
			var pollUrl = ' . json_encode($pollUrl, JSON_UNESCAPED_SLASHES) . ';
			var $cand = document.getElementById("lc_cand");
			var $job = document.getElementById("lc_job");
			var $poll = document.getElementById("lc_poll");
			var $logs = document.getElementById("lc_logs");

			function render(d){
				if (!d || !d.ok) return;
				var c = d.candidate || {};
				var j = d.job || {};
				if ($cand) $cand.textContent = (c.entity ? (c.entity + "#" + c.entity_id + " ") : "") + ("cand_status=" + (c.candidate_status||"") + " i18n=" + (c.i18n_status||""));
				var js = j.status || "";
				if (j.id && !js) js = "waiting cron";
				var jobLine = (j.id ? ("#" + j.id + " ") : "") + js;
				if (j.message) jobLine += " — " + j.message;
				if (j.locked_at) jobLine += " [locked " + j.locked_at + "]";
				if (j.started_at) jobLine += " [started " + j.started_at + "]";
				if ($job) $job.textContent = jobLine;
				var lines = [];
				var lg = d.logs || [];
				var dbg = d.debug_tail || [];
				// server returns DESC by id; display from older to newer
				for (var i=lg.length-1;i>=0;i--){
					var l = lg[i];
					var lvl = (l.level || "").toLowerCase();
					var msg = "[" + (l.created_at || "") + "] " + (lvl ? lvl.toUpperCase() : "") + " " + (l.message || "");
					// Show structured context (JSON) if present — critical for AI diagnostics (timeouts, curl_info, code_debug).
					if (l.context) {
						var ctx = "" + l.context;
						// pretty-print JSON if possible
						try { ctx = JSON.stringify(JSON.parse(ctx), null, 2); } catch (e) {}
						msg += "\\n" + ctx.split("\\n").map(function(s){ return "  " + s; }).join("\\n");
					}
					lines.push(msg);
				}
				if (lines.length === 0 && dbg && dbg.length) {
					lines.push("No matched logs. Latest translations logs:");
					for (var di=dbg.length-1; di>=0; di--) {
						var dl = dbg[di];
						var dlvl = (dl.level || "").toLowerCase();
						lines.push("[" + (dl.created_at || "") + "] " + (dlvl ? dlvl.toUpperCase() : "") + " " + (dl.message || ""));
					}
				}
				// For better readability: colorize by level using HTML.
				var htmlLines = [];
				for (var k=0;k<lines.length;k++){
					var s = lines[k];
					var color = "#f2f2f2";
					if (s.indexOf("ERROR") === 1 || s.toUpperCase().indexOf("ERROR") !== -1) color = "#ff6b6b";
					else if (s.indexOf("WARNING") !== -1 || s.toUpperCase().indexOf("WARNING") !== -1) color = "#ffd166";
					else if (s.indexOf("INFO") !== -1 || s.toUpperCase().indexOf("INFO") !== -1) color = "#eaeaea";
					htmlLines.push("<span style=\\"color:" + color + ";\\">" + s.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\\n/g, \"<br>\") + "</span>");
				}
				if ($logs) $logs.innerHTML = htmlLines.join("<br>");
			}

			function tick(){
				if ($poll) $poll.textContent = "request… " + (new Date()).toLocaleTimeString();
				fetch(pollUrl + (pollUrl.indexOf("?") !== -1 ? "&" : "?") + "_ts=" + Date.now(), { cache: "no-store" })
					.then(function(r){ return r.text(); })
					.then(function(t){
						try {
							var d = JSON.parse(t);
							if ($poll) $poll.textContent = "ok " + (new Date()).toLocaleTimeString() + " (" + (t ? t.length : 0) + " bytes)";
							render(d);
						}
						catch (e) {
							if ($poll) $poll.textContent = "parse error " + (new Date()).toLocaleTimeString();
							if ($logs) $logs.textContent = "Poll JSON parse error: " + (e && e.message ? e.message : e) + "\\n\\nRaw response (first 1000 chars):\\n" + (t || \"\").slice(0, 1000);
						}
					})
					.catch(function(e){
						if ($poll) $poll.textContent = "request failed " + (new Date()).toLocaleTimeString();
						if ($logs) $logs.textContent = "Poll request failed: " + (e && e.message ? e.message : e);
					});
			}
			tick();
			setInterval(tick, 5000);
		} catch (e) {
			var el = document.getElementById("lc_logs");
			if (el) el.textContent = "Live monitor JS error: " + (e && e.message ? e.message : e);
		}
	})();
	</script>';
	if (!defined('TRANSLATIONS_HUB')) {
		require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
		exit;
	}
	return;
}

// Run one candidate now (enqueue only if no active job exists)
if (!empty($_POST['run_one_now']) && !empty($_POST['candidate_ids']) && is_array($_POST['candidate_ids'])) {
	$cids = array_values(array_filter(array_map('intval', $_POST['candidate_ids'])));
	if (count($cids) === 1) {
		$cid = (int)$cids[0];
		$cand = mysql_select("SELECT * FROM translation_order_candidates WHERE id=" . $cid . " LIMIT 1", 'row');
		if ($cand) {
			$entity = (string)$cand['entity'];
			$entity_id = (int)$cand['entity_id'];
			$cid_order_id = (int)$cand['order_id'];
			$order = mysql_select("SELECT * FROM translation_orders WHERE id=" . $cid_order_id . " LIMIT 1", 'row');
			if ($order) {
				$dst_lang = (int)$order['target_lang_id'];
				$src_lang = (int)$order['source_lang_id'];
				$chunk_max_len = (int)($order['chunk_max_len'] ?? 2500);
				$priority = (int)($order['priority'] ?? 0);

				$active_job = mysql_select("
					SELECT id FROM admin_jobs
					WHERE module='translations' AND action='translate'
					  AND status IN ('pending','running')
					  AND (
						 payload LIKE '%\"candidate_id\":' . (int)$cid . '%'
						 OR payload LIKE '%\"candidate_id\":\"' . (int)$cid . '%'
					  )
					ORDER BY id DESC
					LIMIT 1
				", 'row');
				$enqueued = false;
				$jid_enq = 0;
				if (!$active_job) {
					$now = date('Y-m-d H:i:s');
					mysql_fn('update', 'translation_order_candidates', array('candidate_status' => 'queued', 'updated_at' => $now), " AND id=" . $cid . " ");
					$jid = admin_jobs_enqueue('translations', 'translate', array(
						'entity' => $entity,
						'entity_id' => $entity_id,
						'src_lang' => $src_lang,
						'dst_lang' => $dst_lang,
						'fields' => array('title','description','content'),
						'chunk_max_len' => $chunk_max_len,
						'order_id' => (int)$cid_order_id,
						'candidate_id' => $cid,
					), array('priority' => $priority));
					if ($jid) {
						// Link job id back so the live window can show it even before payload match.
						$jid_enq = (int)$jid;
						mysql_fn('update', 'translation_order_candidates', array('last_job_id' => (int)$jid), " AND id=" . (int)$cid . " ");
						if (function_exists('system_log_add')) {
							system_log_add('translations', 'info', 'Run now enqueued (job#' . (int)$jid_enq . ' cand#' . (int)$cid . ' ' . (string)$entity . '#' . (int)$entity_id . ' ' . (int)$src_lang . '→' . (int)$dst_lang . ')', array(
								'job_id' => (int)$jid_enq,
								'candidate_id' => (int)$cid,
								'order_id' => (int)$cid_order_id,
								'entity' => (string)$entity,
								'entity_id' => (int)$entity_id,
								'src_lang' => (int)$src_lang,
								'dst_lang' => (int)$dst_lang,
								'chunk_max_len' => (int)$chunk_max_len,
							));
						}
					}
					$enqueued = true;
				}
				$_SESSION['admin_flash_success'] = $enqueued
					? 'Run now enqueued (candidate #' . $cid . ')' . ($jid_enq > 0 ? ' job#' . $jid_enq : '') . '.'
					: 'Job already exists for candidate #' . $cid . ' (see live window).';
				header('Location: /admin.php?m=translations&tab=monitor&u=candidate_live&candidate_id=' . (int)$cid . '&order_id=' . (int)$cid_order_id);
				exit;
			}
		}
	}
	$_SESSION['admin_flash_error'] = 'Select exactly one candidate to run now.';
	header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates' . ($order_id>0 ? '&order_id=' . (int)$order_id : ''));
	exit;
}

function _tmon_get_columns($table) {
	$cols = mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "`", 'rows');
	$out = array();
	foreach ((array)$cols as $c) {
		if (isset($c['Field'])) $out[(string)$c['Field']] = true;
	}
	return $out;
}

function _tmon_table_has($cols, $name) {
	return isset($cols[$name]);
}

function _tmon_games_categories_map() {
	require_once ROOT_DIR . 'functions/games_categories_func.php';
	$map = games_categories_get_map('', false);
	return $map ?: games_categories_fallback_map();
}

function _tmon_get_entity_meta($entity) {
	$meta = array(
		'pages' => array('table' => 'pages', 'label' => 'Pages', 'has_category' => false),
		'guides' => array('table' => 'guides', 'label' => 'Guides', 'has_category' => true, 'categories' => array(
			'analysis' => 'Analysis',
			'bonus' => 'Bonus',
			'how-to-win' => 'How to Win',
			'signals' => 'Signals',
			'crash-gambling' => 'Crash Gambling',
		)),
		'games' => array('table' => 'games', 'label' => 'Games', 'has_category' => true, 'categories' => _tmon_games_categories_map()),
		'casino_articles' => array('table' => 'casino_articles', 'label' => 'Casino articles', 'has_category' => false),
		'blog' => array('table' => 'blog', 'label' => 'Blog', 'has_category' => true),
	);
	if (!isset($meta[$entity])) $entity = 'pages';
	return $meta[$entity];
}

function _tmon_compute_date_where($col_map, $date_from, $date_to) {
	// date_from/date_to expected as YYYY-MM-DD
	$where = '';
	if (!empty($date_from) && !empty($col_map['from'])) {
		$where .= " AND DATE(`" . mysql_res($col_map['from']) . "`) >= '" . mysql_res($date_from) . "'";
	}
	if (!empty($date_to) && !empty($col_map['to'])) {
		$where .= " AND DATE(`" . mysql_res($col_map['to']) . "`) <= '" . mysql_res($date_to) . "'";
	}
	return $where;
}

$messages = '';
// Delete translation order (and its candidates)
if (!empty($_POST['delete_order']) && !empty($_POST['order_id'])) {
	$del_order_id = (int)$_POST['order_id'];
	if ($del_order_id > 0) {
		mysql_fn('query', "DELETE FROM translation_order_candidates WHERE order_id=" . $del_order_id);
		mysql_fn('delete', 'translation_orders', $del_order_id);
		$_SESSION['admin_flash_success'] = 'Order #' . $del_order_id . ' deleted.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=orders');
		exit;
	}
}
if (!empty($_POST['create_order'])) {
	$entity = isset($_POST['entity']) ? trim((string)$_POST['entity']) : 'pages';
	$entity_meta = _tmon_get_entity_meta($entity);
	$table = $entity_meta['table'];

	$src = isset($_POST['src_lang_id']) ? (int)$_POST['src_lang_id'] : (int)$cfg['source_lang_id'];
	$dst = isset($_POST['dst_lang_id']) ? (int)$_POST['dst_lang_id'] : 0;
	$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
	$chunk_max_len = isset($_POST['chunk_max_len']) ? (int)$_POST['chunk_max_len'] : (int)$cfg['chunk_max_len'];
	if ($chunk_max_len <= 0) $chunk_max_len = 2500;

	$id_from = isset($_POST['id_from']) && $_POST['id_from'] !== '' ? (int)$_POST['id_from'] : 0;
	$id_to = isset($_POST['id_to']) && $_POST['id_to'] !== '' ? (int)$_POST['id_to'] : 0;
	$date_from = isset($_POST['date_from']) ? trim((string)$_POST['date_from']) : '';
	$date_to = isset($_POST['date_to']) ? trim((string)$_POST['date_to']) : '';

	$missing_mode = isset($_POST['missing_mode']) ? trim((string)$_POST['missing_mode']) : 'missing_published';
	$max_candidates = isset($_POST['max_candidates']) ? (int)$_POST['max_candidates'] : 500;
	if ($max_candidates <= 0) $max_candidates = 500;

	$category = isset($_POST['category']) ? trim((string)$_POST['category']) : '';
	if (!$entity_meta['has_category']) $category = '';

	if ($src <= 0 || $dst <= 0 || $dst === $src) {
		$_SESSION['admin_flash_error'] = 'Bad source/target language.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=orders');
		exit;
	}

	// Insert order
	$now = date('Y-m-d H:i:s');
	$order_name = trim((string)($_POST['order_name'] ?? ''));
	if ($order_name === '') {
		$order_name = ucfirst($entity) . ' ' . (int)$src . '→' . (int)$dst;
	}
	$new_order_id = mysql_fn('insert', 'translation_orders', array(
		'name' => $order_name,
		'source_lang_id' => $src,
		'target_lang_id' => $dst,
		'entity' => $entity,
		'filters_json' => json_encode(array(
			'id_from' => $id_from,
			'id_to' => $id_to,
			'date_from' => $date_from,
			'date_to' => $date_to,
			'category' => $category,
			'missing_mode' => $missing_mode,
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'status' => 'draft',
		'priority' => $priority,
		'chunk_max_len' => $chunk_max_len,
		'created_at' => $now,
		'updated_at' => $now,
	));
	$new_order_id = $new_order_id ? (int)$new_order_id : 0;
	if ($new_order_id <= 0) {
		$_SESSION['admin_flash_error'] = 'Failed to create translation order.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=orders');
		exit;
	}

	$cols = _tmon_get_columns($table);
	$where = '';
	if (_tmon_table_has($cols, 'display')) $where .= ' AND display=1 ';
	if ($id_from > 0) $where .= ' AND id >= ' . (int)$id_from . ' ';
	if ($id_to > 0) $where .= ' AND id <= ' . (int)$id_to . ' ';

	// Date column preference
	$date_col = '';
	if (_tmon_table_has($cols, 'created_at')) $date_col = 'created_at';
	elseif (_tmon_table_has($cols, 'date')) $date_col = 'date';
	if ($date_col !== '') {
		$where .= _tmon_compute_date_where(array('from' => $date_col, 'to' => $date_col), $date_from, $date_to);
	}

	// Category (only for tables with category column)
	if ($category !== '' && _tmon_table_has($cols, 'category')) {
		$where .= " AND category='" . mysql_res($category) . "' ";
	}

	// Load source candidates IDs (limit to keep request fast)
	$limit = (int)$max_candidates;
	$select = 'id';
	if (_tmon_table_has($cols, 'name')) $select .= ', name';
	if (_tmon_table_has($cols, 'url')) $select .= ', url';

	$sql = "SELECT {$select} FROM `" . mysql_res($table) . "` WHERE 1 {$where} ORDER BY id ASC LIMIT {$limit}";
	$srcRows = mysql_select($sql, 'rows') ?: array();
	if (empty($srcRows)) {
		mysql_fn('update', 'translation_orders', array('total_candidates' => 0, 'updated_at' => $now), " AND id=" . (int)$new_order_id . " ");
		$_SESSION['admin_flash_info'] = 'No candidates found for this order filters.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$new_order_id);
		exit;
	}

	$entity_ids = array_map(function($r){ return (int)$r['id']; }, $srcRows);
	$in = implode(',', array_filter($entity_ids));
	$i18nById = array();
	if (!empty($in)) {
		$i18nRows = mysql_select("
			SELECT entity_id, status, url, name, title, description, content
			FROM content_i18n
			WHERE entity='" . mysql_res($entity) . "'
			  AND lang_id=" . (int)$dst . "
			  AND entity_id IN ({$in})
		", 'rows') ?: array();
		foreach ($i18nRows as $ir) {
			$eid = isset($ir['entity_id']) ? (int)$ir['entity_id'] : 0;
			if ($eid > 0) {
				$st = isset($ir['status']) ? (string)$ir['status'] : 'draft';
				$filled = false;
				foreach (array('url','name','title','description','content') as $k) {
					if (isset($ir[$k]) && trim((string)$ir[$k]) !== '') { $filled = true; break; }
				}
				// If translation row exists but is completely empty, treat it as missing (cannot be Published).
				if (!$filled) $st = 'missing';
				$i18nById[$eid] = $st;
			}
		}
	}

	$inserted = 0;
	foreach ($srcRows as $sr) {
		$eid = (int)$sr['id'];
		$i18n_status = isset($i18nById[$eid]) ? (string)$i18nById[$eid] : 'missing';

		// missing_mode:
		// - missing_any: create only if there is no translation record
		// - missing_published: create if no published translation (draft/review allowed)
		// - all: include everything
		if ($missing_mode === 'missing_any' && $i18n_status !== 'missing') continue;
		if ($missing_mode === 'missing_published' && $i18n_status === 'published') continue;

		$c_src_name = isset($sr['name']) ? (string)$sr['name'] : null;
		$c_src_url = isset($sr['url']) ? (string)$sr['url'] : null;

		mysql_fn('insert', 'translation_order_candidates', array(
			'order_id' => $new_order_id,
			'entity' => $entity,
			'entity_id' => $eid,
			'candidate_status' => 'pending',
			'i18n_status' => $i18n_status,
			'source_name' => $c_src_name,
			'source_url' => $c_src_url,
			'created_at' => $now,
			'updated_at' => $now,
		));
		$inserted++;
	}

	mysql_fn('update', 'translation_orders', array(
		'total_candidates' => (int)$inserted,
		'updated_at' => $now,
	), " AND id=" . (int)$new_order_id . " ");

	$_SESSION['admin_flash_success'] = 'Order created: ' . (int)$new_order_id . '. Candidates: ' . (int)$inserted . '.';
	header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$new_order_id);
	exit;
}

// Queue selected candidates
if (!empty($_POST['queue_candidates']) && $order_id > 0) {
	$selected = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) ? array_map('intval', $_POST['candidate_ids']) : array();
	$selected = array_values(array_filter($selected));
	if (empty($selected)) {
		$_SESSION['admin_flash_info'] = 'Select candidates to queue.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id);
		exit;
	}

	$order = mysql_select("SELECT * FROM translation_orders WHERE id=" . (int)$order_id . " LIMIT 1", 'row');
	if (!$order) {
		$_SESSION['admin_flash_error'] = 'Order not found.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id);
		exit;
	}

	$cands = mysql_select("
		SELECT id, entity, entity_id, candidate_status
		FROM translation_order_candidates
		WHERE order_id=" . (int)$order_id . "
		  AND id IN (" . implode(',', $selected) . ")
	", 'rows') ?: array();

	$queue_count = 0;
	$priority = isset($order['priority']) ? (int)$order['priority'] : 0;
	$chunk_max_len = isset($order['chunk_max_len']) ? (int)$order['chunk_max_len'] : 2500;

	$now = date('Y-m-d H:i:s');
	foreach ($cands as $c) {
		$cid = (int)$c['id'];
		$cstatus = isset($c['candidate_status']) ? (string)$c['candidate_status'] : 'pending';
		if (!in_array($cstatus, array('pending','failed'), true)) continue;
		mysql_fn('update', 'translation_order_candidates', array(
			'candidate_status' => 'queued',
			'updated_at' => $now,
		), " AND id=" . (int)$cid . " ");

		$payload = array(
			'entity' => (string)$c['entity'],
			'entity_id' => (int)$c['entity_id'],
			'src_lang' => (int)$order['source_lang_id'],
			'dst_lang' => (int)$order['target_lang_id'],
			'fields' => array('title','description','content'),
			'chunk_max_len' => $chunk_max_len,
			'order_id' => (int)$order_id,
			'candidate_id' => (int)$cid,
		);
		$jid = admin_jobs_enqueue('translations', 'translate', $payload, array('priority' => $priority));
		if ($jid) {
			$queue_count++;
			mysql_fn('update', 'translation_order_candidates', array('last_job_id' => (int)$jid), " AND id=" . (int)$cid . " ");
			if (function_exists('system_log_add')) {
				system_log_add('translations', 'info', 'Queued (job#' . (int)$jid . ' cand#' . (int)$cid . ' ' . (string)$payload['entity'] . '#' . (int)$payload['entity_id'] . ' ' . (int)$payload['src_lang'] . '→' . (int)$payload['dst_lang'] . ')', array(
					'job_id' => (int)$jid,
					'candidate_id' => (int)$cid,
					'order_id' => (int)$order_id,
				));
			}
		}
	}

	mysql_fn('update', 'translation_orders', array(
		'status' => 'running',
		'updated_at' => $now,
	), " AND id=" . (int)$order_id . " ");

	$_SESSION['admin_flash_success'] = 'Queued candidates: ' . (int)$queue_count;
	header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id);
	exit;
}

// Queue all pending/failed in order
if (!empty($_POST['queue_all_pending']) && $order_id > 0) {
	$order = mysql_select("SELECT * FROM translation_orders WHERE id=" . (int)$order_id . " LIMIT 1", 'row');
	if (!$order) {
		$_SESSION['admin_flash_error'] = 'Order not found.';
		header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id);
		exit;
	}
	$cands = mysql_select("
		SELECT id, entity, entity_id, candidate_status
		FROM translation_order_candidates
		WHERE order_id=" . (int)$order_id . "
		  AND candidate_status IN ('pending','failed')
	", 'rows') ?: array();
	$priority = (int)($order['priority'] ?? 0);
	$chunk_max_len = (int)($order['chunk_max_len'] ?? 2500);
	$now = date('Y-m-d H:i:s');
	$queue_count = 0;
	foreach ($cands as $c) {
		mysql_fn('update', 'translation_order_candidates', array('candidate_status' => 'queued', 'updated_at' => $now), " AND id=" . (int)$c['id'] . " ");
		$payload = array(
			'entity' => (string)$c['entity'],
			'entity_id' => (int)$c['entity_id'],
			'src_lang' => (int)$order['source_lang_id'],
			'dst_lang' => (int)$order['target_lang_id'],
			'fields' => array('title','description','content'),
			'chunk_max_len' => $chunk_max_len,
			'order_id' => (int)$order_id,
			'candidate_id' => (int)$c['id'],
		);
		$jid = admin_jobs_enqueue('translations', 'translate', $payload, array('priority' => $priority));
		if ($jid) {
			$queue_count++;
			mysql_fn('update', 'translation_order_candidates', array('last_job_id' => (int)$jid), " AND id=" . (int)$c['id'] . " ");
			if (function_exists('system_log_add')) {
				system_log_add('translations', 'info', 'Queued (job#' . (int)$jid . ' cand#' . (int)$c['id'] . ' ' . (string)$payload['entity'] . '#' . (int)$payload['entity_id'] . ' ' . (int)$payload['src_lang'] . '→' . (int)$payload['dst_lang'] . ')', array(
					'job_id' => (int)$jid,
					'candidate_id' => (int)$c['id'],
					'order_id' => (int)$order_id,
				));
			}
		}
	}
	mysql_fn('update', 'translation_orders', array('status' => 'running', 'updated_at' => $now), " AND id=" . (int)$order_id . " ");
	$_SESSION['admin_flash_success'] = 'Queued all pending: ' . (int)$queue_count . ' job(s).';
	header('Location: /admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id);
	exit;
}

// Helper: edit URL for entity+entity_id (opens content form; user can switch to Translations tab)
function _tmon_edit_url($entity, $entity_id, $target_lang_id) {
	$entity_id = (int)$entity_id;
	$tid = (int)$target_lang_id;
	$lang = $tid > 0 ? '&i18n_lang_id=' . $tid : '';
	$inline = '&inline=1';
	// Internal form tab key for "Translations" pane in content modules is typically 3.
	// We use ftab to avoid conflict with content section selector tab=guides/games/...
	$ftab = '&ftab=3';
	if ($entity === 'pages') return '/admin.php?m=pages&u=form&id=' . $entity_id . $lang . $inline;
	if ($entity === 'guides') return '/admin.php?m=content&tab=guides&u=form&id=' . $entity_id . $lang . $ftab . $inline;
	if ($entity === 'games') return '/admin.php?m=content&tab=games&u=form&id=' . $entity_id . $lang . $ftab . $inline;
	if ($entity === 'casino_articles') return '/admin.php?m=content&tab=casinos&u=form&id=' . $entity_id . $lang . $ftab . $inline;
	if ($entity === 'blog') return '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . $entity_id . $lang . $ftab . $inline;
	return '';
}

// Render tabs
$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">Translation monitor</h5>';
$content .= '<div class="card mb-4"><div class="card-body">';
$tabs_html = '<ul class="nav nav-tabs mb-4">';
$active_orders = $tab === 'orders' ? ' active' : '';
$active_candidates = $tab === 'candidates' ? ' active' : '';
$active_jobs = $tab === 'jobs' ? ' active' : '';
$tabs_html .= '<li class="nav-item"><a class="nav-link' . $active_orders . '" href="/admin.php?m=translations&tab=monitor&mtab=orders">Orders</a></li>';
$tabs_html .= '<li class="nav-item"><a class="nav-link' . $active_candidates . '" href="/admin.php?m=translations&tab=monitor&mtab=candidates' . ($order_id>0 ? '&order_id='.(int)$order_id : '') . '">Candidates</a></li>';
$tabs_html .= '<li class="nav-item"><a class="nav-link' . $active_jobs . '" href="/admin.php?m=translations&tab=monitor&mtab=jobs' . ($order_id>0 ? '&order_id='.(int)$order_id : '') . '">Queue</a></li>';
$tabs_html .= '</ul>';
$content .= $tabs_html;

if ($tab === 'orders') {
	$content .= '<div class="mb-3">';
	$content .= '<h5 class="mb-2">Create Translation Order</h5>';

	$entity_list = array('pages' => 'Pages', 'guides' => 'Guides', 'games' => 'Games', 'casino_articles' => 'Casino articles', 'blog' => 'Blog', 'authors' => 'Authors');

	$content .= '<form method="post">';
	$content .= '<input type="hidden" name="create_order" value="1">';
	$content .= '<div class="row g-2">';
	$content .= '<div class="col-md-3"><label class="form-label">Entity</label><select name="entity" class="form-select">';
	foreach ($entity_list as $k => $v) {
		$content .= '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v) . '</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-3"><label class="form-label">From language</label><select name="src_lang_id" class="form-select">';
	foreach ($enabled_langs as $l) {
		$lid = (int)$l['id'];
		$sel = $lid === (int)$cfg['source_lang_id'] ? ' selected' : '';
		$content .= '<option value="' . $lid . '"' . $sel . '>' . htmlspecialchars((string)$l['name']) . ' (' . htmlspecialchars((string)$l['url']) . ')</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-3"><label class="form-label">To language</label><select name="dst_lang_id" class="form-select">';
	foreach ($enabled_langs as $l) {
		$lid = (int)$l['id'];
		$content .= '<option value="' . $lid . '">' . htmlspecialchars((string)$l['name']) . ' (' . htmlspecialchars((string)$l['url']) . ')</option>';
	}
	$content .= '</select></div>';

	$content .= '<div class="col-md-3"><label class="form-label">Priority</label><input class="form-control" type="number" name="priority" value="0"></div>';
	$content .= '<div class="col-md-3"><label class="form-label">Chunk max len</label><input class="form-control" type="number" min="500" name="chunk_max_len" value="' . (int)@$cfg['chunk_max_len'] . '"></div>';
	$content .= '<div class="col-md-3"><label class="form-label">Max candidates</label><input class="form-control" type="number" min="10" name="max_candidates" value="500"></div>';
	$content .= '<div class="col-md-3"><label class="form-label">Order name (optional)</label><input class="form-control" type="text" name="order_name" placeholder="e.g. FR missing pages"></div>';

	$content .= '<div class="col-md-3"><label class="form-label">ID from</label><input class="form-control" type="number" name="id_from" value=""></div>';
	$content .= '<div class="col-md-3"><label class="form-label">ID to</label><input class="form-control" type="number" name="id_to" value=""></div>';
	$content .= '<div class="col-md-3"><label class="form-label">Date from</label><input class="form-control" type="date" name="date_from" value=""></div>';
	$content .= '<div class="col-md-3"><label class="form-label">Date to</label><input class="form-control" type="date" name="date_to" value=""></div>';

	// Category (simple generic UI; server will ignore if entity doesn't support)
	$content .= '<div class="col-md-6"><label class="form-label">Category (only for entities with category)</label><select class="form-select" name="category">';
	$content .= '<option value="">All</option>';
	// union of categories for convenience
	foreach (_tmon_get_entity_meta('guides')['categories'] as $ck => $cv) {
		$content .= '<option value="' . htmlspecialchars($ck) . '">' . htmlspecialchars('Guides: ' . $cv) . '</option>';
	}
	foreach (_tmon_get_entity_meta('games')['categories'] as $ck => $cv) {
		$content .= '<option value="' . htmlspecialchars($ck) . '">' . htmlspecialchars('Games: ' . $cv) . '</option>';
	}
	// blog categories from DB
	$blogCats = mysql_select("SELECT id, name FROM blog_category", 'rows') ?: array();
	foreach ($blogCats as $bc) {
		$catName = isset($bc['name']) ? (string)$bc['name'] : '';
		$catKey = isset($bc['id']) ? (int)$bc['id'] : 0;
		if ($catKey <= 0) continue;
		$content .= '<option value="' . (int)$catKey . '">' . htmlspecialchars('Blog: ' . $catName) . '</option>';
	}
	$content .= '</select></div>';

	$content .= '<div class="col-md-6"><label class="form-label">Missing mode</label><select class="form-select" name="missing_mode">';
	$content .= '<option value="missing_published" selected>Only if no Published translation (draft/review allowed)</option>';
	$content .= '<option value="missing_any">Only if translation record is missing (any status)</option>';
	$content .= '<option value="all">All candidates (no missing filter)</option>';
	$content .= '</select></div>';

	$content .= '<div class="col-12 mt-3"><button class="btn btn-primary btn-sm" type="submit">Create order & generate candidates</button></div>';
	$content .= '</div>';
	$content .= '</form>';
	$content .= '</div>';

	// Existing orders (with done/failed from table; synced by job runner)
	$orders = mysql_select("
		SELECT o.id, o.name, o.entity, o.source_lang_id, o.target_lang_id, o.status, o.priority,
		       o.total_candidates, o.translated_count, o.failed_count, o.created_at, o.updated_at
		FROM translation_orders o
		ORDER BY o.id DESC
		LIMIT 50
	", 'rows') ?: array();

	$content .= '<div class="card mt-2"><div class="card-body">';
	$content .= '<h6 class="mb-2">Orders</h6>';
	$content .= '<div class="table-responsive"><table class="table table-sm">';
	$content .= '<thead><tr><th>ID</th><th>Name</th><th>Entity</th><th>From→To</th><th>Status</th><th>Progress</th><th>Done</th><th>Failed</th><th>Actions</th></tr></thead><tbody>';
	foreach ($orders as $o) {
		$from = (int)$o['source_lang_id'];
		$to = (int)$o['target_lang_id'];
		$total = (int)$o['total_candidates'];
		$done = (int)($o['translated_count'] ?? 0);
		$failed = (int)($o['failed_count'] ?? 0);
		$progress = $total > 0 ? ($done + $failed) . ' / ' . $total : '0';
		$content .= '<tr>';
		$content .= '<td>' . (int)$o['id'] . '</td>';
		$content .= '<td style="max-width:260px;white-space:normal;">' . htmlspecialchars((string)$o['name']) . '</td>';
		$content .= '<td>' . htmlspecialchars((string)$o['entity']) . '</td>';
		$content .= '<td class="text-muted small">' . $from . '→' . $to . '</td>';
		$content .= '<td><span class="badge badge-' . (string)($o['status']==='completed'?'success':($o['status']==='running'?'info':'secondary')) . '">' . htmlspecialchars((string)$o['status']) . '</span></td>';
		$content .= '<td class="text-muted small">' . $progress . '</td>';
		$content .= '<td>' . $done . '</td>';
		$content .= '<td>' . $failed . '</td>';
		$orderLink = (int)$o['id'];
		$content .= '<td>';
		$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . $orderLink . '">Open</a> ';
		$content .= '<form method="post" class="d-inline" onsubmit="return confirm(\'Delete order #' . $orderLink . ' and all its candidates?\');">';
		$content .= '<input type="hidden" name="order_id" value="' . $orderLink . '">';
		$content .= '<button type="submit" name="delete_order" value="1" class="btn btn-outline-danger btn-sm">Delete</button>';
		$content .= '</form>';
		$content .= '</td>';
		$content .= '</tr>';
	}
	if (empty($orders)) $content .= '<tr><td colspan="9" class="text-muted">No orders yet.</td></tr>';
	$content .= '</tbody></table></div>';
	$content .= '</div></div>';
}

if ($tab === 'candidates') {
	if ($order_id <= 0) {
		$content .= '<div class="alert alert-info">Pick an order from the Orders tab.</div>';
		$content .= '</div></div>';
		if (!defined('TRANSLATIONS_HUB')) {
			require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
			exit;
		}
		return;
	}

	$order = mysql_select("SELECT * FROM translation_orders WHERE id=" . (int)$order_id . " LIMIT 1", 'row');
	if (!$order) {
		$content .= '<div class="alert alert-danger">Order not found.</div>';
		$content .= '</div></div>';
		if (!defined('TRANSLATIONS_HUB')) {
			require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
			exit;
		}
		return;
	}

	$status_filter = isset($get['status']) && in_array($get['status'], array('pending','queued','running','done','failed'), true) ? (string)$get['status'] : 'pending';
	$limit = isset($get['limit']) ? (int)$get['limit'] : 50;
	if ($limit <= 0) $limit = 50;

	$page = isset($get['page']) ? (int)$get['page'] : 1;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $limit;

	$total_where = 'order_id=' . (int)$order_id;
	if ($status_filter !== '') $total_where .= " AND candidate_status='" . mysql_res($status_filter) . "'";

	$total = mysql_select("SELECT COUNT(*) AS c FROM translation_order_candidates WHERE {$total_where}", 'row');
	$total_candidates = $total && isset($total['c']) ? (int)$total['c'] : 0;
	$total_pages = $limit > 0 ? (int)ceil((float)$total_candidates / (float)$limit) : 1;
	if ($total_pages < 1) $total_pages = 1;

	$stats = mysql_select("
		SELECT candidate_status, COUNT(*) AS c
		FROM translation_order_candidates
		WHERE order_id=" . (int)$order_id . "
		GROUP BY candidate_status
	", 'rows') ?: array();
	$by = array('pending'=>0,'queued'=>0,'running'=>0,'done'=>0,'failed'=>0);
	foreach ($stats as $s) {
		$st = isset($s['candidate_status']) ? (string)$s['candidate_status'] : '';
		if (isset($by[$st])) $by[$st] = (int)$s['c'];
	}

	$content .= '<div class="mb-2">';
	$content .= '<h5 class="mb-2">Candidates for order #' . (int)$order_id . '</h5>';
	$content .= '<div class="small text-muted mb-2">Total: ' . (int)$total_candidates . ' | pending: ' . (int)$by['pending'] . ' | queued: ' . (int)$by['queued'] . ' | done: ' . (int)$by['done'] . ' | failed: ' . (int)$by['failed'] . '</div>';
	$content .= '<form method="post" class="d-inline mb-2" onsubmit="return confirm(\'Delete this order and all its candidates? You can create a new order from the Orders tab.\');">';
	$content .= '<input type="hidden" name="order_id" value="' . (int)$order_id . '">';
	$content .= '<button type="submit" name="delete_order" value="1" class="btn btn-outline-danger btn-sm">Delete order</button>';
	$content .= '</form> ';
	$content .= '<a href="/admin.php?m=translations&tab=monitor&mtab=orders" class="btn btn-outline-secondary btn-sm">Back to Orders</a>';

	$content .= '<form method="post" class="mt-2" id="candidates-form">';
	$content .= '<input type="hidden" name="order_id" value="' . (int)$order_id . '">';
	$content .= '<div class="row g-2 align-items-end mb-2">';
	$base = '/admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id;
	$content .= '<div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status" onchange="location.href=\'' . $base . '&status=\'+this.value+\'&limit=' . (int)$limit . '&page=1\'">';
	$opts = array('pending'=>'Pending','queued'=>'Queued','running'=>'Running','done'=>'Done','failed'=>'Failed');
	foreach ($opts as $v => $label) {
		$sel = $status_filter === $v ? ' selected' : '';
		$content .= '<option value="' . htmlspecialchars($v) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
	}
	$content .= '</select></div>';
	$content .= '<div class="col-md-2"><label class="form-label">Limit</label><input class="form-control" type="number" name="limit" min="10" value="' . (int)$limit . '" onchange="location.href=\'' . $base . '&status=' . htmlspecialchars($status_filter) . '&limit=\'+this.value+\'&page=1\'"></div>';
	$content .= '<div class="col-md-8 text-end align-bottom">';
	$content .= '<button type="submit" name="queue_candidates" value="1" class="btn btn-primary btn-sm">Queue selected</button> ';
	$content .= '<button type="submit" name="queue_all_pending" value="1" class="btn btn-outline-primary btn-sm">Queue all pending</button>';
	$content .= ' <button type="submit" name="run_one_now" value="1" class="btn btn-outline-success btn-sm" disabled>Run now (live)</button>';
	$content .= '</div></div>';

	$content .= '<div class="table-responsive"><table class="table table-sm">';
	$content .= '<thead><tr><th style="width:38px;"></th><th>ID</th><th>Entity</th><th>Name</th><th>i18n status</th><th>Status</th><th>Last error</th><th>Actions</th></tr></thead><tbody>';

	// Fetch candidates
	$where = 'order_id=' . (int)$order_id;
	if ($status_filter !== '') $where .= " AND candidate_status='" . mysql_res($status_filter) . "'";
	$candRows = mysql_select("
		SELECT id, entity, entity_id, source_name, i18n_status, candidate_status, last_error, updated_at
		FROM translation_order_candidates
		WHERE {$where}
		ORDER BY id DESC
		LIMIT " . (int)$limit . " OFFSET " . (int)$offset, 'rows') ?: array();

	$target_lang_id = (int)($order['target_lang_id'] ?? 0);
	foreach ($candRows as $c) {
		$cid = (int)$c['id'];
		$cstatus = (string)($c['candidate_status'] ?? '');
		$queueable = in_array($cstatus, array('pending','failed'), true);
		$badge = $cstatus === 'done' ? 'success' : ($cstatus === 'failed' ? 'danger' : ($cstatus === 'running' ? 'info' : 'secondary'));
		$editUrl = _tmon_edit_url((string)$c['entity'], (int)$c['entity_id'], $target_lang_id);
		$content .= '<tr>';
		// Enable selection for "Run now (live)" even if candidate is currently stalled/running.
		$content .= '<td><input type="checkbox" name="candidate_ids[]" value="' . $cid . '"></td>';
		$content .= '<td>' . (int)$cid . '</td>';
		$content .= '<td>' . htmlspecialchars((string)$c['entity']) . '#' . (int)$c['entity_id'] . '</td>';
		$content .= '<td style="max-width:260px;white-space:normal;">' . htmlspecialchars((string)$c['source_name']) . '</td>';
		$content .= '<td class="text-muted small">' . htmlspecialchars((string)$c['i18n_status']) . '</td>';
		$content .= '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($cstatus) . '</span></td>';
		$content .= '<td style="max-width:340px;white-space:normal;">' . htmlspecialchars((string)($c['last_error'] ?? '')) . '</td>';
		$content .= '<td>' . ($editUrl !== '' ? '<a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($editUrl) . '" target="_blank">Review</a>' : '') . '</td>';
		$content .= '</tr>';
	}
	if (empty($candRows)) $content .= '<tr><td colspan="8" class="text-muted">No candidates for this filter.</td></tr>';
	$content .= '</tbody></table></div>';

	// Pagination UI
	if ($total_pages > 1) {
		$pageBase = '/admin.php?m=translations&tab=monitor&mtab=candidates&order_id=' . (int)$order_id
			. '&status=' . urlencode($status_filter)
			. '&limit=' . (int)$limit;

		$pages = array();
		if ($total_pages <= 7) {
			for ($i = 1; $i <= (int)$total_pages; $i++) $pages[] = $i;
		} else {
			$pages[] = 1;
			$start = max(2, (int)$page - 1);
			$end = min((int)$total_pages - 1, (int)$page + 1);
			if ($start > 2) $pages[] = 0;
			for ($i = $start; $i <= $end; $i++) $pages[] = $i;
			if ($end < (int)$total_pages - 1) $pages[] = 0;
			$pages[] = (int)$total_pages;
		}

		$content .= '<div class="pagination pagination-bottom mt-3">';
		$content .= '<nav aria-label="Pagination"><ul class="pagination pagination-sm pagination-rounded mb-0">';
		foreach ($pages as $p) {
			if ((int)$p === 0) {
				$content .= '<li class="page-item"><span class="page-link">…</span></li>';
				continue;
			}
			$isActive = ((int)$p === (int)$page);
			if ($isActive) {
				$content .= '<li class="page-item active"><span class="page-link">' . (int)$p . '</span></li>';
			} else {
				$link = $pageBase . '&page=' . (int)$p;
				$content .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . (int)$p . '</a></li>';
			}
		}
		$content .= '</ul></nav></div>';
	}
	$content .= '<script>
	(function(){
		var form = document.getElementById("candidates-form");
		if (!form) return;
		var btn = form.querySelector("button[name=\\"run_one_now\\"]");
		function update(){
			var checked = form.querySelectorAll("input[name=\\"candidate_ids[]\\"]:checked").length;
			if (btn) btn.disabled = (checked !== 1);
		}
		form.querySelectorAll("input[name=\\"candidate_ids[]\\"]").forEach(function(cb){
			cb.addEventListener("change", update);
		});
		update();
	})();
	</script>';

	$content .= '</form>';
	$content .= '</div>';

}

if ($tab === 'jobs') {
	if ($order_id <= 0) {
		$content .= '<div class="alert alert-info">Pick an order from the Orders tab.</div>';
	} else {
		$content .= '<div class="mb-2">';
		$content .= '<h5 class="mb-2">Queue jobs for order #' . (int)$order_id . '</h5>';
		// Fetch recent translation jobs and filter by payload order_id (decode JSON)
		$allJobs = mysql_select("
			SELECT id, created_at, status, priority, message, payload
			FROM admin_jobs
			WHERE module='translations' AND action='translate'
			ORDER BY id DESC
			LIMIT 300
		", 'rows') ?: array();
		$jobRows = array();
		foreach ($allJobs as $j) {
			$pl = isset($j['payload']) ? @json_decode((string)$j['payload'], true) : null;
			if (is_array($pl) && isset($pl['order_id']) && (int)$pl['order_id'] === (int)$order_id) {
				$jobRows[] = $j;
			}
		}
		$jobRows = array_slice($jobRows, 0, 100);
		$content .= '<div class="table-responsive"><table class="table table-sm">';
		$content .= '<thead><tr><th>ID</th><th>Status</th><th>Priority</th><th>Created</th><th>Message</th></tr></thead><tbody>';
		foreach ($jobRows as $j) {
			$status = (string)$j['status'];
			$badge = $status === 'done' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'running' ? 'info' : 'secondary'));
			$content .= '<tr>';
			$content .= '<td>' . (int)$j['id'] . '</td>';
			$content .= '<td><span class="badge badge-' . $badge . '">' . htmlspecialchars($status) . '</span></td>';
			$content .= '<td>' . (int)$j['priority'] . '</td>';
			$content .= '<td class="text-muted small">' . htmlspecialchars((string)$j['created_at']) . '</td>';
			$content .= '<td style="max-width:520px;white-space:normal;">' . htmlspecialchars((string)($j['message'] ?? '')) . '</td>';
			$content .= '</tr>';
		}
		if (empty($jobRows)) $content .= '<tr><td colspan="5" class="text-muted">No jobs found for this order yet.</td></tr>';
		$content .= '</tbody></table></div>';
		$content .= '</div>';
	}
}

$content .= '</div></div></div>';

