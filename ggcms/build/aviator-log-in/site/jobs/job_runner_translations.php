<?php
/**
 * Job handlers: translations.
 */

require_once(ROOT_DIR . 'functions/ai_gateway.php');
require_once(ROOT_DIR . 'functions/system_log.php');
require_once(ROOT_DIR . 'functions/translation_metadata_quality.php');
require_once(ROOT_DIR . 'functions/translation_cluster.php');
require_once(ROOT_DIR . 'functions/translation_html_segment.php');
require_once(ROOT_DIR . 'functions/ai_prompt_templates.php');

function _translations_pick_key() {
	if (@mysql_select("SHOW TABLES LIKE 'ai_provider_keys'", 'num_rows') === 0) return null;
	$row = mysql_select("SELECT * FROM ai_provider_keys WHERE enabled=1 ORDER BY id ASC LIMIT 1", 'row');
	return $row ?: null;
}

function _translations_split_chunks($text, $max_len) {
	$text = (string)$text;
	if ($text === '') return array();
	if ($max_len <= 0) $max_len = 2500;
	$chunks = array();
	$parts = preg_split('/(\r?\n){2,}/', $text);
	$buf = '';
	foreach ($parts as $p) {
		$p = trim($p);
		if ($p === '') continue;
		$candidate = $buf === '' ? $p : ($buf . "\n\n" . $p);
		if (mb_strlen($candidate) <= $max_len) {
			$buf = $candidate;
			continue;
		}
		if ($buf !== '') $chunks[] = $buf;
		$buf = $p;
		if (mb_strlen($buf) > $max_len) {
			// hard split
			$start = 0;
			$len = mb_strlen($buf);
			while ($start < $len) {
				$chunks[] = mb_substr($buf, $start, $max_len);
				$start += $max_len;
			}
			$buf = '';
		}
	}
	if ($buf !== '') $chunks[] = $buf;
	return $chunks;
}

/**
 * Find a UTF-8 split point near $mid (prefer paragraph / line breaks).
 */
function _translations_find_split_offset($s, $mid) {
	$len = mb_strlen((string)$s);
	if ($len < 4) {
		return max(1, (int)floor($len / 2));
	}
	$mid = max(1, min((int)$mid, $len - 1));
	$lo = max(0, $mid - 500);
	$slice = mb_substr((string)$s, $lo, $mid - $lo);
	$pp = mb_strrpos($slice, "\n\n");
	if ($pp !== false) {
		return $lo + $pp + 2;
	}
	$p = mb_strrpos($slice, "\n");
	if ($p !== false && $lo + $p + 1 < $len - 80) {
		return $lo + $p + 1;
	}
	$sp = mb_strrpos($slice, ' ');
	if ($sp !== false && $lo + $sp + 1 < $len - 40) {
		return $lo + $sp + 1;
	}
	return $mid;
}

/**
 * Call AI for one segment; on repeated failure split and merge (bounded depth).
 * Last resort: return source segment (fallback=true) so the job can finish and you can fix in CMS.
 * max_depth=0: no recursive split (used when bisect would break HTML structure vs. validation signature).
 *
 * @return array{ok:bool,text:string,fallback?:bool,message?:string}
 */
function _translations_ai_translate_segment_bisect(
	$provider,
	$api_key,
	$model_used,
	$segment,
	$sys,
	$src_lang_name,
	$dst_lang_name,
	$src_lang,
	$dst_lang,
	$log_prefix,
	$field,
	$chunk_label,
	$depth,
	$max_depth,
	$min_piece
) {
	$segment = (string)$segment;
	$min_piece = max(80, (int)$min_piece);
	$max_depth = max(0, (int)$max_depth);
	$user = "Source language: {$src_lang_name} ({$src_lang}).\nTarget language: {$dst_lang_name} ({$dst_lang}).\n\nCONTENT:\n" . $segment;
	$max_tries = 2;
	for ($try = 1; $try <= $max_tries; $try++) {
		$res = ai_gateway_chat($provider, $api_key, $model_used, array(
			array('role' => 'system', 'content' => $sys),
			array('role' => 'user', 'content' => $user),
		));
		if (!empty($res['ok'])) {
			$t = trim((string)($res['reply_text'] ?? ''));
			if ($t !== '') {
				return array('ok' => true, 'text' => $t, 'fallback' => false);
			}
		}
	}
	$slen = mb_strlen($segment);
	if ($depth >= $max_depth || $slen < $min_piece * 2) {
		system_log_add('translations', 'warning', 'Translation segment fallback to source (' . $log_prefix . ") field={$field} {$chunk_label} depth={$depth}", array(
			'field' => $field,
			'chunk_label' => $chunk_label,
			'depth' => $depth,
			'slen' => $slen,
			'min_piece' => $min_piece,
		));
		return array('ok' => true, 'text' => $segment, 'fallback' => true, 'message' => 'source_passthrough');
	}
	$mid = (int)floor($slen / 2);
	$split_at = _translations_find_split_offset($segment, $mid);
	if ($split_at <= 0 || $split_at >= $slen) {
		$split_at = $mid;
	}
	$left = mb_substr($segment, 0, $split_at);
	$right = mb_substr($segment, $split_at);
	if (mb_strlen($left) < 1 || mb_strlen($right) < 1) {
		system_log_add('translations', 'warning', 'Translation segment unsplittable, source passthrough (' . $log_prefix . ") field={$field} {$chunk_label}", array(
			'field' => $field,
			'chunk_label' => $chunk_label,
			'split_at' => $split_at,
			'slen' => $slen,
		));
		return array('ok' => true, 'text' => $segment, 'fallback' => true);
	}
	system_log_add('translations', 'info', 'AI bisect split (' . $log_prefix . ") field={$field} {$chunk_label} depth={$depth}→" . ($depth + 1), array(
		'field' => $field,
		'chunk_label' => $chunk_label,
		'depth' => $depth,
		'left_len' => mb_strlen($left),
		'right_len' => mb_strlen($right),
	));
	$L = _translations_ai_translate_segment_bisect(
		$provider, $api_key, $model_used, $left, $sys,
		$src_lang_name, $dst_lang_name, $src_lang, $dst_lang,
		$log_prefix, $field, $chunk_label . '.L', $depth + 1, $max_depth, $min_piece
	);
	if (!$L['ok']) {
		return $L;
	}
	$R = _translations_ai_translate_segment_bisect(
		$provider, $api_key, $model_used, $right, $sys,
		$src_lang_name, $dst_lang_name, $src_lang, $dst_lang,
		$log_prefix, $field, $chunk_label . '.R', $depth + 1, $max_depth, $min_piece
	);
	if (!$R['ok']) {
		return $R;
	}
	$merged = trim((string)$L['text']) . "\n\n" . trim((string)$R['text']);
	return array(
		'ok' => true,
		'text' => $merged,
		'fallback' => !empty($L['fallback']) || !empty($R['fallback']),
	);
}

function _translations_examples_prompt($field, array $examples, $dst_lang_name) {
	if ($examples === array()) {
		return '';
	}
	$lines = array();
	$lines[] = "Use these approved {$dst_lang_name} examples only as style guidance for {$field}.";
	$lines[] = "Examples may come from other approved materials in the database, such as guides, games, casino articles, pages, or blog posts.";
	$lines[] = "Do not copy irrelevant details; preserve the current source facts.";
	$i = 0;
	foreach ($examples as $ex) {
		$i++;
		$srcEntity = trim((string)($ex['entity'] ?? ''));
		$src = trim((string)($ex['source_text'] ?? ''));
		$dst = trim((string)($ex['target_text'] ?? ''));
		if ($src === '' || $dst === '') {
			continue;
		}
		if ($srcEntity !== '') {
			$lines[] = "Example {$i} SOURCE_ENTITY: " . $srcEntity;
		}
		$lines[] = "Example {$i} SOURCE: " . $src;
		$lines[] = "Example {$i} TARGET: " . $dst;
	}
	return $lines ? ("\n\n" . implode("\n", $lines)) : '';
}

function _translations_pick_enabled_key() {
	$key = _translations_pick_key();
	return $key ?: null;
}

function _translations_ai_check_untranslated_tail($src_text, $dst_text, $src_lang_name, $dst_lang_name) {
	$key = _translations_pick_enabled_key();
	if (!$key) {
		return array('ok' => false, 'suspect' => false, 'message' => 'No AI key');
	}
	$src_plain = mb_substr(translation_html_probe_plain((string)$src_text), 0, 3500, 'UTF-8');
	$dst_plain = mb_substr(translation_html_probe_plain((string)$dst_text), 0, 3500, 'UTF-8');
	if ($src_plain === '' || $dst_plain === '') {
		return array('ok' => true, 'suspect' => false, 'message' => 'Empty plain text');
	}
	$model_used = isset($key['model_default']) ? trim((string)$key['model_default']) : '';
	if ($model_used === '' && function_exists('ai_gateway_default_model')) {
		$model_used = ai_gateway_default_model(isset($key['provider']) ? (string)$key['provider'] : '');
	}
	$sys = "You validate website translations.\n"
		. "Compare SOURCE ({$src_lang_name}) and TARGET ({$dst_lang_name}).\n"
		. "Check whether TARGET still contains untranslated source-language tails, copied sentences, or mixed-language leftovers.\n"
		. "Return ONLY valid JSON: {\"suspect\":true|false,\"reason\":\"...\"}.";
	$user = "SOURCE:\n" . $src_plain . "\n\nTARGET:\n" . $dst_plain;
	$res = ai_gateway_chat((string)$key['provider'], (string)$key['api_key'], $model_used, array(
		array('role' => 'system', 'content' => $sys),
		array('role' => 'user', 'content' => $user),
	));
	if (empty($res['ok'])) {
		return array('ok' => false, 'suspect' => false, 'message' => isset($res['message']) ? (string)$res['message'] : 'AI check failed');
	}
	$txt = trim((string)($res['reply_text'] ?? ''));
	$dec = @json_decode($txt, true);
	if (!is_array($dec)) {
		if (preg_match('/suspect\s*[:=]\s*true/i', $txt)) {
			return array('ok' => true, 'suspect' => true, 'reason' => 'ai_text_match');
		}
		return array('ok' => false, 'suspect' => false, 'message' => 'AI JSON parse failed');
	}
	return array(
		'ok' => true,
		'suspect' => !empty($dec['suspect']),
		'reason' => isset($dec['reason']) ? (string)$dec['reason'] : '',
	);
}

function run_translations_translate($payload = array(), $job = array()) {
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 0;
	$dst_lang = isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0;
	$fields = isset($payload['fields']) && is_array($payload['fields']) ? $payload['fields'] : array('title','description','content');
	$max_len = isset($payload['chunk_max_len']) ? (int)$payload['chunk_max_len'] : 2500;
	// Smaller HTML chunks + bisect-on-fail reduce provider stalls (same chunk hanging on every retry).
	$content_chunk_cap = isset($payload['content_chunk_cap']) ? (int)$payload['content_chunk_cap'] : 900;
	if ($content_chunk_cap < 350) {
		$content_chunk_cap = 350;
	}
	if ($content_chunk_cap > 4000) {
		$content_chunk_cap = 4000;
	}
	$bisect_max_depth = isset($payload['bisect_max_depth']) ? (int)$payload['bisect_max_depth'] : 6;
	if ($bisect_max_depth < 0) {
		$bisect_max_depth = 0;
	}
	if ($bisect_max_depth > 12) {
		$bisect_max_depth = 12;
	}
	$bisect_min_chars = isset($payload['bisect_min_chars']) ? (int)$payload['bisect_min_chars'] : 280;
	if ($bisect_min_chars < 80) {
		$bisect_min_chars = 80;
	}
	$english_leak_retry = array_key_exists('english_leak_retry', $payload) ? !empty($payload['english_leak_retry']) : true;
	$english_leak_min_words = isset($payload['english_leak_min_words']) ? max(3, min(12, (int)$payload['english_leak_min_words'])) : 4;
	$english_leak_max_retries = isset($payload['english_leak_max_retries']) ? max(0, min(3, (int)$payload['english_leak_max_retries'])) : 1;
	$metadata_normalize = !empty($payload['metadata_normalize']);
	$source_override = (isset($payload['source_override']) && is_array($payload['source_override'])) ? $payload['source_override'] : null;
	$repair_pass = !empty($payload['repair_pass']);
	$validation_blockers = isset($payload['validation_blockers']) && is_array($payload['validation_blockers']) ? $payload['validation_blockers'] : array();
	$order_id = isset($payload['order_id']) ? (int)$payload['order_id'] : 0;
	$candidate_id = isset($payload['candidate_id']) ? (int)$payload['candidate_id'] : 0;
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	$job_start_ts = time();
	// Watchdog: if translation for a candidate takes too long, fail candidate so queue can continue.
	$max_job_seconds = isset($payload['max_job_seconds']) ? (int)$payload['max_job_seconds'] : 900; // default 15 minutes
	if ($max_job_seconds <= 0) $max_job_seconds = 900;

	$diag = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'src_lang' => $src_lang,
		'dst_lang' => $dst_lang,
		'fields' => $fields,
		'order_id' => $order_id,
		'candidate_id' => $candidate_id,
		'max_job_seconds' => $max_job_seconds,
		'chunk_max_len' => (int)$max_len,
		'content_chunk_cap' => (int)$content_chunk_cap,
		'bisect_max_depth' => (int)$bisect_max_depth,
		'bisect_min_chars' => (int)$bisect_min_chars,
		'english_leak_retry' => !empty($english_leak_retry) ? 1 : 0,
		'english_leak_min_words' => (int)$english_leak_min_words,
		'english_leak_max_retries' => (int)$english_leak_max_retries,
		'metadata_normalize' => !empty($metadata_normalize) ? 1 : 0,
		'content_i18n_src_found' => false,
		'fallback_attempted' => false,
		'fallback_found' => false,
	);

	$log_prefix = 'job#' . (int)$job_id . ' cand#' . (int)$candidate_id . ' ' . (string)$entity . '#' . (int)$entity_id . ' ' . (int)$src_lang . '→' . (int)$dst_lang;
	$runtime_phase = 'boot';
	$runtime_meta = array('field' => '', 'chunk' => 0, 'chunk_total' => 0, 'try' => 0);
	if ($job_id > 0 && function_exists('admin_jobs_touch')) {
		admin_jobs_touch($job_id, 'Translate start: ' . (string)$entity . '#' . (int)$entity_id . ' ' . (int)$src_lang . '→' . (int)$dst_lang);
	}
	system_log_add('translations', 'info', 'Translate start (' . $log_prefix . ')', array_merge($diag, array(
		'job_id' => (int)$job_id,
		'payload_order_id' => (int)$order_id,
	)));

	$prompt_tpl = ai_prompt_templates_merged();

	// Resolve language meta for better prompt and link rewriting.
	$lang_src = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$src_lang . " LIMIT 1", 'row');
	$lang_dst = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$src_lang_name = !empty($lang_src['name']) ? (string)$lang_src['name'] : ('lang_id=' . (int)$src_lang);
	$dst_lang_name = !empty($lang_dst['name']) ? (string)$lang_dst['name'] : ('lang_id=' . (int)$dst_lang);
	$dst_lang_url = !empty($lang_dst['url']) ? trim((string)$lang_dst['url'], '/') : '';

	$update_candidate = function($new_status, $new_i18n_status, $err_message = null) use ($candidate_id, $order_id, $job_id) {
		if ($candidate_id <= 0) return;
		$now = date('Y-m-d H:i:s');
		$upd = array(
			'candidate_status' => $new_status,
			'i18n_status' => $new_i18n_status,
			'last_job_id' => $job_id > 0 ? (int)$job_id : null,
			'last_error' => $err_message !== null ? (string)$err_message : '',
			'updated_at' => $now,
		);
		$where = " AND id=" . (int)$candidate_id;
		if ($order_id > 0) $where .= " AND order_id=" . (int)$order_id;
		mysql_fn('update', 'translation_order_candidates', $upd, $where);
		// Update order counters and optionally set completed
		if ($order_id > 0 && in_array($new_status, array('done', 'failed'), true)) {
			$col = $new_status === 'done' ? 'translated_count' : 'failed_count';
			mysql_fn('query', "UPDATE translation_orders SET `{$col}` = `{$col}` + 1, updated_at = '" . mysql_res($now) . "' WHERE id = " . (int)$order_id);
			$ord = mysql_select("SELECT total_candidates, translated_count, failed_count FROM translation_orders WHERE id = " . (int)$order_id . " LIMIT 1", 'row');
			if ($ord && (int)$ord['total_candidates'] > 0) {
				$done = (int)$ord['translated_count'] + (int)$ord['failed_count'];
				if ($done >= (int)$ord['total_candidates']) {
					mysql_fn('update', 'translation_orders', array('status' => 'completed', 'updated_at' => $now), " AND id=" . (int)$order_id . " ");
				}
			}
		}
	};

	// Mark candidate as running as soon as handler starts.
	// This makes Live window reflect real state (not stuck at queued).
	if ($candidate_id > 0) {
		mysql_fn('update', 'translation_order_candidates', array(
			'candidate_status' => 'running',
			'last_job_id' => $job_id > 0 ? (int)$job_id : null,
			'updated_at' => date('Y-m-d H:i:s'),
		), " AND id=" . (int)$candidate_id . " ");
	}

	// Fatal/shutdown guard: if process dies between logs, emit the last known phase.
	register_shutdown_function(function() use (&$runtime_phase, &$runtime_meta, $log_prefix, $diag, $update_candidate) {
		$e = error_get_last();
		if (!$e) return;
		$fatal_types = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
		if (!in_array((int)$e['type'], $fatal_types, true)) return;
		$reason = 'Fatal at phase=' . (string)$runtime_phase
			. ' field=' . (string)($runtime_meta['field'] ?? '')
			. ' chunk=' . (int)($runtime_meta['chunk'] ?? 0) . '/' . (int)($runtime_meta['chunk_total'] ?? 0)
			. ' try=' . (int)($runtime_meta['try'] ?? 0)
			. ' message=' . (string)($e['message'] ?? 'unknown');
		system_log_add('translations', 'error', 'Translation fatal/shutdown (' . $log_prefix . ')', array_merge($diag, array(
			'phase' => (string)$runtime_phase,
			'runtime_meta' => $runtime_meta,
			'fatal' => $e,
		)));
		$update_candidate('failed', 'missing', $reason);
	});

	if ($entity === '' || $entity_id <= 0 || $dst_lang <= 0) {
		$update_candidate('failed', 'missing', 'Bad payload (entity/entity_id/dst_lang)');
		return array('ok' => false, 'message' => 'Bad payload (entity/entity_id/dst_lang)');
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
		$update_candidate('failed', 'missing', 'Table content_i18n not found (run migration)');
		return array('ok' => false, 'message' => 'Table content_i18n not found (run migration)');
	}

	$key = _translations_pick_key();
	if (!$key) {
		$update_candidate('failed', 'missing', 'No enabled AI keys in ai_provider_keys');
		return array('ok' => false, 'message' => 'No enabled AI keys in ai_provider_keys');
	}

	// Load source from content_i18n if exists, else fallback to entity table (minimal: pages)
	$src = mysql_select("SELECT * FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " AND lang_id=" . (int)$src_lang . " LIMIT 1", 'row');
	if (!empty($src)) $diag['content_i18n_src_found'] = true;

	// Field-by-field legacy fallback for games:
	// content_i18n row can exist for src_lang, but requested fields (especially `content`)
	// may be empty due to partial/incorrect sync. In this case translation will skip
	// empty fields and job can be stuck with "EN/base text" on frontend.
	if ($entity === 'games' && !empty($src)) {
		$requested = array_flip((array)$fields);
		$g = mysql_select("SELECT * FROM games WHERE id=" . (int)$entity_id . " LIMIT 1", 'row');
		if ($g) {
			$suffix = ($src_lang > 1) ? (string)$src_lang : '';
			$legacy_src = array(
				'url' => isset($g['url' . $suffix]) ? (string)$g['url' . $suffix] : (isset($g['url']) ? (string)$g['url'] : ''),
				'name' => isset($g['name' . $suffix]) ? (string)$g['name' . $suffix] : (isset($g['name']) ? (string)$g['name'] : ''),
				'title' => isset($g['title' . $suffix]) ? (string)$g['title' . $suffix] : (isset($g['title']) ? (string)$g['title'] : ''),
				'description' => isset($g['description' . $suffix]) ? (string)$g['description' . $suffix] : (isset($g['description']) ? (string)$g['description'] : ''),
				'content' => isset($g['text' . $suffix]) ? (string)$g['text' . $suffix] : (isset($g['text']) ? (string)$g['text'] : ''),
			);

			$diag['fallback_attempted'] = true;
			$diag['fallback_found'] = !empty($legacy_src);

			foreach (array('url','name','title','description','content') as $k) {
				if (!isset($requested[$k])) continue; // translate only requested fields
				$empty_in_src = !isset($src[$k]) || trim((string)$src[$k]) === '';
				$has_legacy = isset($legacy_src[$k]) && trim((string)$legacy_src[$k]) !== '';
				if ($empty_in_src && $has_legacy) $src[$k] = $legacy_src[$k];
			}
		}
	}
	if ($entity === 'pages') {
		// For pages we may have a content_i18n source row that exists but contains empty fields
		// (common-tab drift / partial sync). In that case we must fallback field-by-field
		// from legacy `pages` columns.
		$p = mysql_select("SELECT * FROM pages WHERE id=" . $entity_id . " LIMIT 1", 'row');
		if ($p) {
			$diag['fallback_attempted'] = true;
			$suffix = ($src_lang > 1) ? (string)$src_lang : '';
			$legacy_src = array(
				'url' => isset($p['url' . $suffix]) ? (string)$p['url' . $suffix] : (isset($p['url']) ? (string)$p['url'] : ''),
				'name' => isset($p['name' . $suffix]) ? (string)$p['name' . $suffix] : (isset($p['name']) ? (string)$p['name'] : ''),
				'title' => isset($p['title' . $suffix]) ? (string)$p['title' . $suffix] : (isset($p['title']) ? (string)$p['title'] : ''),
				'description' => isset($p['description' . $suffix]) ? (string)$p['description' . $suffix] : (isset($p['description']) ? (string)$p['description'] : ''),
				'content' => isset($p['text' . $suffix]) ? (string)$p['text' . $suffix] : (isset($p['text']) ? (string)$p['text'] : ''),
			);
			$diag['fallback_found'] = !empty($legacy_src);

			if (!$src) {
				$src = $legacy_src;
				$diag['content_i18n_src_found'] = false;
			} else {
				// Fill only the empty fields that we are going to translate.
				foreach (array('url','name','title','description','content') as $k) {
					$needs = isset($legacy_src[$k]) && trim((string)($legacy_src[$k])) !== '';
					$empty_in_src = !isset($src[$k]) || trim((string)$src[$k]) === '';
					if ($needs && $empty_in_src) $src[$k] = $legacy_src[$k];
				}
			}
		}
	}
	// Fallback for material tables when content_i18n source row is missing.
	// This allows translation jobs to run even if admin common tab was edited
	// but scalable i18n rows for src_lang were never created.
	if (!$src && $entity === 'guides') {
		$g = mysql_select("SELECT * FROM guides WHERE id=" . $entity_id . " LIMIT 1", 'row');
		if ($g) {
			$diag['fallback_attempted'] = true;
			$src = array(
				'url' => isset($g['url']) ? (string)$g['url'] : '',
				'name' => isset($g['name']) ? (string)$g['name'] : '',
				'title' => isset($g['title']) ? (string)$g['title'] : '',
				'description' => isset($g['description']) ? (string)$g['description'] : '',
				'content' => isset($g['text']) ? (string)$g['text'] : '',
			);
			$diag['fallback_found'] = !empty($src);
		}
	}
	if (!$src && $entity === 'games') {
		$g = mysql_select("SELECT * FROM games WHERE id=" . $entity_id . " LIMIT 1", 'row');
		if ($g) {
			$diag['fallback_attempted'] = true;
			$src = array(
				'url' => isset($g['url']) ? (string)$g['url'] : '',
				'name' => isset($g['name']) ? (string)$g['name'] : '',
				'title' => isset($g['title']) ? (string)$g['title'] : '',
				'description' => isset($g['description']) ? (string)$g['description'] : '',
				'content' => isset($g['text']) ? (string)$g['text'] : '',
			);
			$diag['fallback_found'] = !empty($src);
		}
	}
	if (!$src && $entity === 'casino_articles') {
		$c = mysql_select("SELECT * FROM casino_articles WHERE id=" . $entity_id . " LIMIT 1", 'row');
		if ($c) {
			$diag['fallback_attempted'] = true;
			$suffix = ($src_lang > 1) ? (string)$src_lang : '';
			$src = array(
				'url' => isset($c['url' . $suffix]) ? (string)$c['url' . $suffix] : (isset($c['url']) ? (string)$c['url'] : ''),
				'name' => isset($c['name' . $suffix]) ? (string)$c['name' . $suffix] : (isset($c['name']) ? (string)$c['name'] : ''),
				'title' => isset($c['title' . $suffix]) ? (string)$c['title' . $suffix] : (isset($c['title']) ? (string)$c['title'] : ''),
				'description' => isset($c['description' . $suffix]) ? (string)$c['description' . $suffix] : (isset($c['description']) ? (string)$c['description'] : ''),
				'content' => isset($c['text' . $suffix]) ? (string)$c['text' . $suffix] : (isset($c['text']) ? (string)$c['text'] : ''),
			);
			$diag['fallback_found'] = !empty($src);
		}
	}
	// Fallback for blog when content_i18n source row is missing.
	// Blog legacy table stores main content in `text`, while localized fields may use language suffixes.
	if (!$src && $entity === 'blog') {
		$b = mysql_select("SELECT * FROM blog WHERE id=" . (int)$entity_id . " LIMIT 1", 'row');
		if ($b) {
			$diag['fallback_attempted'] = true;
			$suffix = ($src_lang > 1) ? (string)$src_lang : '';
			$src = array(
				'url' => isset($b['url' . $suffix]) ? (string)$b['url' . $suffix] : (isset($b['url']) ? (string)$b['url'] : ''),
				'name' => isset($b['name' . $suffix]) ? (string)$b['name' . $suffix] : (isset($b['name']) ? (string)$b['name'] : ''),
				'title' => isset($b['title' . $suffix]) ? (string)$b['title' . $suffix] : (isset($b['title']) ? (string)$b['title'] : ''),
				'description' => isset($b['description' . $suffix]) ? (string)$b['description' . $suffix] : (isset($b['description']) ? (string)$b['description'] : ''),
				'content' => isset($b['text']) ? (string)$b['text'] : (isset($b['content']) ? (string)$b['content'] : ''),
			);
			$diag['fallback_found'] = !empty($src) && trim((string)($src['content'] ?? '')) !== '';
		}
	}
	if (is_array($source_override)) {
		if (!$src) {
			$src = array();
		}
		foreach (array('url', 'name', 'title', 'description', 'content') as $f) {
			if (array_key_exists($f, $source_override) && trim((string)$source_override[$f]) !== '') {
				$src[$f] = (string)$source_override[$f];
			}
		}
	}
	if (!$src) {
		// More context for debugging stuck jobs.
		system_log_add('translations', 'error', 'Source not found (detailed)', $diag);
		$update_candidate('failed', 'missing', 'Source not found');
		return array('ok' => false, 'message' => 'Source not found: entity=' . $entity . ' id=' . (int)$entity_id . ' src=' . (int)$src_lang . ' dst=' . (int)$dst_lang);
	}

	$dst = mysql_select("SELECT * FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " AND lang_id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$dst_row = $dst ?: array('entity' => $entity, 'entity_id' => $entity_id, 'lang_id' => $dst_lang);

	$out = array();
	$translated_non_empty_any = false;
	foreach ($fields as $f) {
		$runtime_phase = 'field_start';
		$runtime_meta = array('field' => (string)$f, 'chunk' => 0, 'chunk_total' => 0, 'try' => 0);
		// Timeout safeguard between fields (keeps jobs from running forever).
		if ((time() - $job_start_ts) > $max_job_seconds) {
			$elapsed = time() - $job_start_ts;
			$reason = 'Timeout total: exceeded ' . (int)$max_job_seconds . 's (elapsed ' . (int)$elapsed . 's).';
			$update_candidate('failed', 'missing', $reason);
			system_log_add('translations', 'error', 'Translation timeout (total) (' . $log_prefix . ')', array_merge($diag, array(
				'elapsed_seconds' => $elapsed,
				'phase' => 'before_field',
				'field' => (string)$f,
				'job' => array(
					'id' => (int)$job_id,
					'module' => isset($job['module']) ? (string)$job['module'] : 'translations',
					'action' => isset($job['action']) ? (string)$job['action'] : 'translate',
					'status' => isset($job['status']) ? (string)$job['status'] : '',
					'locked_at' => isset($job['locked_at']) ? (string)$job['locked_at'] : '',
					'started_at' => isset($job['started_at']) ? (string)$job['started_at'] : '',
					'payload' => (string)($job['payload'] ?? ''),
				),
			)));
			return array('ok' => false, 'message' => $reason);
		}
		$src_text = isset($src[$f]) ? (string)$src[$f] : '';
		if (trim($src_text) === '') continue;

		// Cluster / optional: translate HTML as JSON text segments, then re-inject into source markup (structure_* matches by construction).
		// For structure_* blockers on `content`, always rebuild from English source skeleton via segments — do not fall back to full-HTML LLM (can drift tags).
		$segment_structure_repair = ($f === 'content' && translation_html_segment_force_for_structure($payload));
		if ($f === 'content' && translation_html_segment_effective_should_use($payload, $f) && strpos($src_text, '<') !== false) {
			if ($job_id > 0 && function_exists('admin_jobs_touch')) {
				admin_jobs_touch($job_id, $segment_structure_repair ? 'segment mode (structure repair): extracting DOM from source' : 'segment mode: extracting DOM text nodes');
			}
			$ext = translation_html_segment_extract($src_text);
			if (!empty($ext['ok']) && !empty($ext['segments'])) {
				$model_used_seg = isset($key['model_default']) ? trim((string)$key['model_default']) : '';
				if ($model_used_seg === '' && function_exists('ai_gateway_default_model')) {
					$model_used_seg = ai_gateway_default_model(isset($key['provider']) ? (string)$key['provider'] : '');
				}
				$tr_seg = translation_html_segment_translate_full(
					$prompt_tpl,
					(string)$key['provider'],
					(string)$key['api_key'],
					$model_used_seg,
					$ext['segments'],
					$src_lang_name,
					$dst_lang_name,
					$src_lang,
					$dst_lang,
					$log_prefix,
					$job_id,
					$max_job_seconds,
					$job_start_ts
				);
				if (!empty($tr_seg['ok']) && !empty($tr_seg['translated']) && is_array($tr_seg['translated'])) {
					$out[$f] = translation_html_segment_apply($ext['template'], $ext['ids'], $tr_seg['translated']);
					if (isset($out[$f]) && trim((string)$out[$f]) !== '') {
						$translated_non_empty_any = true;
					}
					system_log_add('translations', 'info', 'Field translated via JSON segments (' . $log_prefix . ')', array(
						'field' => (string)$f,
						'segment_count' => count($ext['segments']),
						'translated_len' => isset($out[$f]) ? mb_strlen((string)$out[$f]) : 0,
					));
					if (isset($out[$f]) && trim((string)$out[$f]) !== '') {
						translation_vector_store_item(array(
							'entity' => (string)$entity,
							'entity_id' => (int)$entity_id,
							'src_lang_id' => (int)$src_lang,
							'dst_lang_id' => (int)$dst_lang,
							'field_type' => (string)$f,
							'source_text' => (string)$src_text,
							'target_text' => (string)$out[$f],
							'quality_status' => 'auto',
						));
					}
					continue;
				}
				if ($segment_structure_repair) {
					$em = isset($tr_seg['message']) ? (string)$tr_seg['message'] : 'segment JSON failed';
					system_log_add('translations', 'error', 'structure repair: segment JSON translate failed (no HTML fallback) (' . $log_prefix . ')', array('message' => $em));
					$update_candidate('failed', 'missing', 'structure repair: ' . $em);
					return array('ok' => false, 'message' => 'structure repair: ' . $em);
				}
				system_log_add('translations', 'warning', 'JSON segment translate failed; falling back to HTML path (' . $log_prefix . ')', array(
					'message' => isset($tr_seg['message']) ? (string)$tr_seg['message'] : '',
				));
			} elseif ($segment_structure_repair) {
				$em = isset($ext['message']) ? (string)$ext['message'] : 'extract failed';
				system_log_add('translations', 'error', 'structure repair: segment extract failed (' . $log_prefix . ')', array('message' => $em));
				$update_candidate('failed', 'missing', 'structure repair: cannot extract segments from source HTML — ' . $em);
				return array('ok' => false, 'message' => 'structure repair: segment extract failed — ' . $em);
			}
		}
		if ($segment_structure_repair && !isset($out[$f])) {
			$msg = strpos($src_text, '<') === false
				? 'structure repair: English source content has no HTML'
				: 'structure repair: segment path did not produce content';
			system_log_add('translations', 'error', $msg . ' (' . $log_prefix . ')', array());
			$update_candidate('failed', 'missing', $msg);
			return array('ok' => false, 'message' => $msg);
		}

		$retrieval_examples = translation_vector_retrieve_examples($src_text, array(
			'field_type' => (string)$f,
			'dst_lang_id' => (int)$dst_lang,
			'entity' => (string)$entity,
			'source_entities' => array('pages', 'guides', 'games', 'casino_articles', 'blog', (string)$entity),
			'limit' => ($f === 'content') ? 2 : 3,
		));
		$examples_prompt = _translations_examples_prompt((string)$f, $retrieval_examples, $dst_lang_name);

		// Content: cap chunk size (configurable) so each AI call stays short; bisect handles stubborn segments.
		$field_max_len = ($f === 'content') ? min((int)$max_len, (int)$content_chunk_cap) : (int)$max_len;
		$structure_repair_whole = false;
		if ($repair_pass && $f === 'content' && $validation_blockers !== array()) {
			foreach ($validation_blockers as $_vb) {
				if (strpos((string)$_vb, 'structure_') === 0) {
					$structure_repair_whole = true;
					break;
				}
			}
		}
		$bisect_max_depth_use = (int)$bisect_max_depth;
		if ($structure_repair_whole) {
			$slen_src = mb_strlen($src_text, 'UTF-8');
			// Single pass over full HTML: per-chunk repair loses global tag counts; bisect-split HTML breaks structure_* validation.
			$field_max_len = max((int)$field_max_len, min($slen_src, 200000));
			$bisect_max_depth_use = 0;
		}
		$chunks = _translations_split_chunks($src_text, $field_max_len);
		$translated_chunks = array();
		$field_failed = false;
		$chunk_total = count($chunks);
		$chunk_i = 0;
		foreach ($chunks as $c) {
			$chunk_i++;
			$runtime_phase = 'chunk_start';
			$runtime_meta = array('field' => (string)$f, 'chunk' => (int)$chunk_i, 'chunk_total' => (int)$chunk_total, 'try' => 0);
			// Timeout safeguard between chunks (AI calls can sum to hours on huge texts).
			if ((time() - $job_start_ts) > $max_job_seconds) {
				$elapsed = time() - $job_start_ts;
				$reason = 'Timeout total: exceeded ' . (int)$max_job_seconds . 's (elapsed ' . (int)$elapsed . 's) while translating ' . (string)$f . ' chunk ' . (int)$chunk_i . '/' . (int)count($chunks) . '.';
				$update_candidate('failed', 'missing', $reason);
				system_log_add('translations', 'error', 'Translation timeout (total) (' . $log_prefix . ')', array_merge($diag, array(
					'elapsed_seconds' => $elapsed,
					'phase' => 'before_chunk',
					'field' => (string)$f,
					'chunk_index' => (int)$chunk_i,
					'chunk_count' => (int)count($chunks),
					'job' => array(
						'id' => (int)$job_id,
						'module' => isset($job['module']) ? (string)$job['module'] : 'translations',
						'action' => isset($job['action']) ? (string)$job['action'] : 'translate',
						'status' => isset($job['status']) ? (string)$job['status'] : '',
						'locked_at' => isset($job['locked_at']) ? (string)$job['locked_at'] : '',
						'started_at' => isset($job['started_at']) ? (string)$job['started_at'] : '',
						'payload' => (string)($job['payload'] ?? ''),
					),
				)));
				return array('ok' => false, 'message' => $reason);
			}
			// Prompt must stay in English for the AI, but explicitly define target language by name.
			if ($metadata_normalize && in_array((string)$f, array('name', 'title', 'description'), true)) {
				$sys = ai_prompt_templates_render($prompt_tpl['translation_metadata'], array(
					'src_lang_name' => $src_lang_name,
					'dst_lang_name' => $dst_lang_name,
					'examples_prompt' => $examples_prompt,
				));
			} else {
				$sys = ai_prompt_templates_render($prompt_tpl['translation_content'], array(
					'src_lang_name' => $src_lang_name,
					'dst_lang_name' => $dst_lang_name,
					'examples_prompt' => $examples_prompt,
				));
			}
			if ($repair_pass) {
				$sys .= ai_prompt_templates_render($prompt_tpl['translation_repair_suffix'], array(
					'dst_lang_name' => $dst_lang_name,
				));
				if ($f === 'content' && $validation_blockers !== array()) {
					$need_structure = false;
					foreach ($validation_blockers as $vb) {
						if (strpos((string)$vb, 'structure_') === 0) {
							$need_structure = true;
							break;
						}
					}
					if ($need_structure && isset($src['content']) && trim((string)$src['content']) !== '' && function_exists('translation_cluster_content_signature')) {
						$sig = translation_cluster_content_signature((string)$src['content']);
						$parts = array();
						foreach ($sig as $tag => $n) {
							$parts[] = '<' . (string)$tag . '>: ' . (int)$n;
						}
						$sys .= ai_prompt_templates_render($prompt_tpl['translation_structure_lock'], array(
							'structure_counts' => implode(', ', $parts),
						));
					}
				}
				if ($metadata_normalize && in_array((string)$f, array('name', 'title', 'description'), true)) {
					$mk = 'meta_' . (string)$f;
					if (in_array($mk, $validation_blockers, true)) {
						$sys .= ai_prompt_templates_render($prompt_tpl['translation_meta_repair'], array(
							'dst_lang_name' => $dst_lang_name,
						));
					}
				}
			}
			$chunk_preview = mb_substr((string)$c, 0, 220, 'UTF-8');
			$chunk_preview = preg_replace('/\s+/u', ' ', (string)$chunk_preview);
			$model_used = isset($key['model_default']) ? trim((string)$key['model_default']) : '';
			if ($model_used === '' && function_exists('ai_gateway_default_model')) {
				$model_used = ai_gateway_default_model(isset($key['provider']) ? (string)$key['provider'] : '');
			}
			system_log_add('translations', 'info', 'AI request start (' . $log_prefix . ") field={$f} chunk={$chunk_i}/{$chunk_total}", array(
				'field' => (string)$f,
				'chunk' => $chunk_i,
				'chunk_total' => $chunk_total,
				'src_text_len' => mb_strlen((string)$c),
				'provider' => isset($key['provider']) ? (string)$key['provider'] : '',
				'model' => (string)$model_used,
				'chunk_preview' => (string)$chunk_preview,
			));
			if ($job_id > 0 && function_exists('admin_jobs_touch')) {
				admin_jobs_touch($job_id, "AI {$entity}#{$entity_id} {$src_lang}→{$dst_lang} {$f} chunk {$chunk_i}/{$chunk_total}");
			}
			$runtime_phase = 'ai_request';
			$runtime_meta = array('field' => (string)$f, 'chunk' => (int)$chunk_i, 'chunk_total' => (int)$chunk_total, 'try' => 0);
			$ai_ts0 = microtime(true);
			$seg_res = _translations_ai_translate_segment_bisect(
				(string)$key['provider'],
				(string)$key['api_key'],
				$model_used,
				$c,
				$sys,
				$src_lang_name,
				$dst_lang_name,
				$src_lang,
				$dst_lang,
				$log_prefix,
				(string)$f,
				"chunk {$chunk_i}/{$chunk_total}",
				0,
				$bisect_max_depth_use,
				$bisect_min_chars
			);
			$ai_elapsed_ms = (int)round((microtime(true) - $ai_ts0) * 1000);
			$runtime_phase = 'ai_response';
			// Long LLM calls can exceed MySQL wait_timeout; next query must use a live connection.
			if (function_exists('mysql_connect_db')) {
				mysql_connect_db();
			}
			$reply_preview_msg = isset($seg_res['text']) ? mb_substr(trim((string)$seg_res['text']), 0, 140, 'UTF-8') : '';
			$reply_preview_msg = preg_replace('/\s+/u', ' ', (string)$reply_preview_msg);
			if (!empty($seg_res['fallback'])) {
				$reply_preview_msg = '[fallback/source] ' . $reply_preview_msg;
			}
			system_log_add('translations', 'info', 'AI segment done (' . $log_prefix . ") field={$f} chunk={$chunk_i}/{$chunk_total} bisect reply_preview=" . (string)$reply_preview_msg, array(
				'field' => (string)$f,
				'chunk' => $chunk_i,
				'chunk_total' => $chunk_total,
				'reply_len' => isset($seg_res['text']) ? mb_strlen((string)$seg_res['text']) : 0,
				'reply_preview' => (string)$reply_preview_msg,
				'ok' => !empty($seg_res['ok']),
				'fallback' => !empty($seg_res['fallback']),
				'elapsed_ms' => $ai_elapsed_ms,
				'bisect_max_depth' => (int)$bisect_max_depth,
				'content_chunk_cap' => (int)$content_chunk_cap,
			));
			if (empty($seg_res['ok'])) {
				$field_failed = true;
				system_log_add('translations', 'error', 'AI segment failed after bisect (' . $log_prefix . ") field={$f} chunk={$chunk_i}/{$chunk_total}", array(
					'field' => (string)$f,
					'chunk' => $chunk_i,
					'message' => isset($seg_res['message']) ? (string)$seg_res['message'] : '',
				));
				continue;
			}
			$tchunk = trim((string)$seg_res['text']);
			if (
				$english_leak_retry
				&& $english_leak_max_retries > 0
				&& (int)$src_lang !== (int)$dst_lang
				&& translation_latin_leak_applies_to_target($dst_lang_url)
				&& in_array((string)$f, array('content', 'description', 'title', 'name'), true)
			) {
				$snippet = '';
				$probe = translation_html_probe_plain($tchunk);
				$leak_attempt = 0;
				while (
					$leak_attempt < $english_leak_max_retries
					&& translation_plain_has_leaking_latin_run_after_whitelist($probe, $english_leak_min_words, $snippet)
				) {
					$leak_attempt++;
					system_log_add('translations', 'warning', 'Latin/English leak retry (' . $log_prefix . ") field={$f} chunk={$chunk_i}/{$chunk_total} attempt={$leak_attempt}", array(
						'field' => (string)$f,
						'chunk' => $chunk_i,
						'min_words' => (int)$english_leak_min_words,
						'snippet' => (string)$snippet,
					));
					$sys2 = $sys . "\n\nIMPORTANT — SECOND PASS:\n"
						. "The text below should already be in {$dst_lang_name}, but a long run of English (Latin alphabet) words is still present.\n"
						. "Rewrite ALL remaining English sentences, headings, and list items into {$dst_lang_name}.\n"
						. "Keep every HTML tag and attribute exactly as-is (including img src= and href= paths).\n"
						. "You may keep well-known brand or product tokens (Aviator, Spribe, RTP, Provably Fair, iOS, Android, GitHub, and similar) untranslated.\n"
						. "Return ONLY the complete corrected text; no notes.";
					if ($job_id > 0 && function_exists('admin_jobs_touch')) {
						admin_jobs_touch($job_id, "AI leak-retry {$entity}#{$entity_id} {$f} chunk {$chunk_i}/{$chunk_total}");
					}
					$seg_res2 = _translations_ai_translate_segment_bisect(
						(string)$key['provider'],
						(string)$key['api_key'],
						$model_used,
						$tchunk,
						$sys2,
						$src_lang_name,
						$dst_lang_name,
						$src_lang,
						$dst_lang,
						$log_prefix,
						(string)$f,
						"chunk {$chunk_i}/{$chunk_total} leak-retry {$leak_attempt}",
						0,
						$bisect_max_depth_use,
						$bisect_min_chars
					);
					if (empty($seg_res2['ok']) || trim((string)($seg_res2['text'] ?? '')) === '') {
						break;
					}
					$tchunk = trim((string)$seg_res2['text']);
					$probe = translation_html_probe_plain($tchunk);
				}
			}
			$translated_chunks[] = $tchunk;
		}
		// Avoid overwriting destination fields with empty string when AI failed.
		if (!$field_failed || !empty($translated_chunks)) {
			$runtime_phase = 'field_finalize';
			$runtime_meta = array('field' => (string)$f, 'chunk' => (int)$chunk_i, 'chunk_total' => (int)$chunk_total, 'try' => 0);
			$out[$f] = implode("\n\n", $translated_chunks);
			if (isset($out[$f]) && trim((string)$out[$f]) !== '') {
				$translated_non_empty_any = true;
			}
			system_log_add('translations', 'info', 'Field translated (' . $log_prefix . ") field={$f}", array(
				'field' => (string)$f,
				'translated_len' => isset($out[$f]) ? mb_strlen((string)$out[$f]) : 0,
				'chunk_total' => $chunk_total,
			));
			if (isset($out[$f]) && trim((string)$out[$f]) !== '') {
				translation_vector_store_item(array(
					'entity' => (string)$entity,
					'entity_id' => (int)$entity_id,
					'src_lang_id' => (int)$src_lang,
					'dst_lang_id' => (int)$dst_lang,
					'field_type' => (string)$f,
					'source_text' => (string)$src_text,
					'target_text' => (string)$out[$f],
					'quality_status' => 'auto',
				));
			}
		}
	}
	if (!$translated_non_empty_any) {
		$runtime_phase = 'empty_output';
		$reason = 'All requested fields translated to empty output';
		$update_candidate('failed', 'missing', $reason);
		system_log_add('translations', 'error', 'Empty translation output (' . $log_prefix . ')', array_merge($diag, array(
			'fields' => $fields,
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src_lang,
			'dst_lang' => $dst_lang,
		)));
		return array('ok' => false, 'message' => $reason);
	}

	// Ensure url exists in dst (fallback to src url)
	if (empty($dst_row['url'])) $dst_row['url'] = isset($src['url']) ? (string)$src['url'] : '';
	foreach (array('url','name','title','description','content') as $f) {
		if (isset($out[$f])) $dst_row[$f] = $out[$f];
	}

	// Rewrite known internal absolute links to be language-aware (e.g. /casinos/ -> /fr/casinos/).
	// This fixes wrong material links inside translated guides/content.
	if (!empty($dst_lang_url) && !empty($dst_row['content'])) {
		$dst_row['content'] = preg_replace(
			'#(href\s*=\s*["\'])/(casinos|games|guides|demo|predictor|strategies|download|blog)/#iu',
			'$1/' . $dst_lang_url . '/$2/',
			(string)$dst_row['content']
		);
	}

	// Canonical image src rewrite:
	// AI sometimes keeps placeholders in img src or slightly alters paths.
	// To guarantee working images, copy exact canonical src URLs by filename
	// and overwrite matching src attributes in the translated content.
	if (!empty($dst_row['content']) && !empty($src['content'])) {
		$canonical = (string)$src['content'];
		$translated = (string)$dst_row['content'];

		$baseDir = '';
		if ($entity === 'guides') $baseDir = '/files/guides/';
		elseif ($entity === 'games') $baseDir = '/files/games/';
		elseif ($entity === 'casino_articles') $baseDir = '/files/casino_articles/';
		elseif ($entity === 'blog') $baseDir = '/files/blog/';

		if ($baseDir !== '') {
			$map = array(); // filename => exact canonical src
			preg_match_all("#src\\s*=\\s*(['\"])([^'\"\\n\\r]+)\\1#iu", $canonical, $m_urls);
			if (!empty($m_urls[2]) && is_array($m_urls[2])) {
				foreach ($m_urls[2] as $u) {
					$u = (string)$u;
					if (stripos($u, $baseDir) === false) continue;
					if (preg_match("#/img/([^/'\"\\?\\#]+)#iu", $u, $m2)) {
						$file = isset($m2[1]) ? (string)$m2[1] : '';
						if ($file !== '' && !isset($map[$file])) $map[$file] = $u;
					}
				}
			}

			if (!empty($map)) {
				$replaced = 0;
				$translated2 = preg_replace_callback(
					"#src\\s*=\\s*(['\"])([^'\"\\n\\r]+)\\1#iu",
					function ($mm) use ($map, &$replaced) {
						$quote = isset($mm[1]) ? (string)$mm[1] : '"';
						$src_url = isset($mm[2]) ? (string)$mm[2] : '';
						$file = '';
						if (preg_match("#/img/([^/'\"\\?\\#]+)#iu", $src_url, $m2)) {
							$file = isset($m2[1]) ? (string)$m2[1] : '';
						}
						if ($file !== '' && isset($map[$file])) {
							$replaced++;
							return 'src=' . $quote . $map[$file] . $quote;
						}
						return $mm[0];
					},
					$translated
				);

				if (is_string($translated2) && $translated2 !== '') {
					$dst_row['content'] = $translated2;
				}
				system_log_add('translations', 'info', 'Canonical image src rewrite', array(
					'entity' => (string)$entity,
					'entity_id' => (int)$entity_id,
					'map_size' => count($map),
					'replaced_src_attrs' => (int)$replaced,
				));
			}
		}
	}
	// Fix guide/game/casino image placeholders in translated content.
	// Some source texts use placeholders like {{GUIDE_ID}} and AI preserves them; convert to real IDs.
	if (!empty($dst_row['content'])) {
		$h = (string)$dst_row['content'];
		if ($entity === 'guides') {
			$h = str_replace(array('{{GUIDE_ID}}', '{{ID}}'), (string)$entity_id, $h);
			$h = preg_replace('#/files/guides/\{\{\s*GUIDE_ID\s*\}\}/img/#iu', '/files/guides/' . (int)$entity_id . '/img/', $h);
			$h = preg_replace('#/files/guides/\{\{\s*ID\s*\}\}/img/#iu', '/files/guides/' . (int)$entity_id . '/img/', $h);
		}
		if ($entity === 'games') {
			$h = str_replace(array('{{GAME_ID}}', '{{ID}}'), (string)$entity_id, $h);
		}
		if ($entity === 'casino_articles') {
			$h = str_replace(array('{{CASINO_ID}}', '{{ID}}'), (string)$entity_id, $h);
		}
		if ($entity === 'blog') {
			$h = str_replace(array('{{BLOG_ID}}', '{{POST_ID}}', '{{ID}}'), (string)$entity_id, $h);
		}
		if (stripos($h, '/files/media/') !== false) {
			require_once ROOT_DIR . 'functions/media_library.php';
			require_once ROOT_DIR . 'functions/media_image.php';
			$fin = media_image_finalize_html_media_refs($h);
			$h = $fin['html'];
		}
		$dst_row['content'] = $h;
	}

	$dst_row['status'] = 'draft';
	$dst_row['updated_at'] = date('Y-m-d H:i:s');
	if (empty($dst_row['created_at'])) $dst_row['created_at'] = date('Y-m-d H:i:s');

	// LLM often ignores soft length hints; enforce SEO Monitor limits after metadata_normalize.
	if ($metadata_normalize) {
		foreach (array('name' => 70, 'title' => 70, 'description' => 160) as $meta_field => $meta_lim) {
			if (isset($dst_row[$meta_field]) && trim((string)$dst_row[$meta_field]) !== '') {
				$dst_row[$meta_field] = translation_cluster_trim_seo_text($dst_row[$meta_field], $meta_lim);
			}
		}
	}

	if ($metadata_normalize && $dst) {
		$ex = translation_metadata_extra_parse(isset($dst['extra']) ? $dst['extra'] : '');
		$ex['metadata_autofix_last_at'] = time();
		$ex['metadata_autofix_runs'] = isset($ex['metadata_autofix_runs']) ? (int)$ex['metadata_autofix_runs'] + 1 : 1;
		$dst_row['extra'] = json_encode($ex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	if ($dst) {
		$runtime_phase = 'db_update';
		$id = (int)$dst['id'];
		unset($dst_row['id']);
		mysql_fn('update', 'content_i18n', $dst_row, " AND id=" . $id . " ");
	} else {
		$runtime_phase = 'db_insert';
		mysql_fn('insert', 'content_i18n', $dst_row);
	}

	$runtime_phase = 'done';
	$update_candidate('done', isset($dst_row['status']) ? (string)$dst_row['status'] : 'draft');

	system_log_add('translations', 'info', "Translated {$entity}#{$entity_id} {$src_lang}→{$dst_lang}", array('entity'=>$entity,'entity_id'=>$entity_id,'src_lang'=>$src_lang,'dst_lang'=>$dst_lang,'fields'=>$fields));
	if (!empty($payload['cluster_job'])) {
		$cluster_dst_langs = isset($payload['cluster_dst_langs']) && is_array($payload['cluster_dst_langs']) ? $payload['cluster_dst_langs'] : array();
		translation_cluster_refresh_state((string)$entity, (int)$entity_id, (int)$src_lang, $cluster_dst_langs, (int)$job_id);
		if (!translation_cluster_has_pending_job((string)$entity, (int)$entity_id, 'validate_locale', (int)$dst_lang)) {
			admin_jobs_enqueue('translations', 'validate_locale', array(
				'entity' => (string)$entity,
				'entity_id' => (int)$entity_id,
				'src_lang' => (int)$src_lang,
				'dst_lang' => (int)$dst_lang,
				'dst_langs' => $cluster_dst_langs,
				'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
				'repair_attempt' => isset($payload['repair_attempt']) ? (int)$payload['repair_attempt'] : 0,
			), array('priority' => isset($job['priority']) ? (int)$job['priority'] + 1 : -4));
		}
	}
	return array('ok' => true, 'message' => "Translated {$entity}#{$entity_id} to lang_id={$dst_lang} (" . implode(',', $fields) . ")");
}

function run_translations_translate_cluster($payload = array(), $job = array()) {
	translation_cluster_ensure_tables();
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 1;
	$dst_langs = isset($payload['dst_langs']) && is_array($payload['dst_langs']) ? $payload['dst_langs'] : array();
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	if ($entity === '' || $entity_id <= 0 || $src_lang <= 0) {
		return array('ok' => false, 'message' => 'Bad cluster payload');
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang, $dst_langs);
	if ($dst_langs === array()) {
		return array('ok' => false, 'message' => 'No cluster target languages');
	}

	$source = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang);
	$source = translation_cluster_normalize_source_meta(is_array($source) ? $source : array(), $entity);
	$search_title = isset($source['title']) && trim((string)$source['title']) !== '' ? (string)$source['title'] : (isset($source['name']) ? (string)$source['name'] : '');
	$search_slug = isset($source['url']) ? (string)$source['url'] : '';
	translation_cluster_upsert_state($entity, $entity_id, array(
		'source_lang_id' => (int)$src_lang,
		'source_mode' => translation_cluster_source_mode($entity),
		'pipeline_stage' => 'queued_locales',
		'cluster_status' => 'translating',
		'total_locales' => count($dst_langs),
		'search_title' => $search_title,
		'search_slug' => $search_slug,
		'last_job_id' => (int)$job_id,
		'last_error' => '',
	));
	$pending_map = translation_cluster_pending_locale_jobs($entity, $entity_id);
	$enqueued = 0;
	$already_pending = 0;
	$queue_budget = translation_cluster_translate_queue_budget();
	$child_cap = translation_cluster_child_enqueue_cap();
	$slots = min($queue_budget, $child_cap);
	foreach ($dst_langs as $dst_lang) {
		if ($slots <= 0) {
			break;
		}
		if ($job_id > 0 && function_exists('admin_jobs_touch')) {
			admin_jobs_touch($job_id, "Queue locale {$entity}#{$entity_id}: {$src_lang}→{$dst_lang}");
		}
		if (!empty($pending_map[(int)$dst_lang])) {
			$already_pending++;
			continue;
		}
		$child_id = admin_jobs_enqueue('translations', 'translate', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src_lang,
			'dst_lang' => (int)$dst_lang,
			'fields' => translation_cluster_translate_fields($entity),
			'chunk_max_len' => isset($payload['chunk_max_len']) ? (int)$payload['chunk_max_len'] : 2500,
			'content_chunk_cap' => isset($payload['content_chunk_cap']) ? (int)$payload['content_chunk_cap'] : 900,
			'bisect_max_depth' => isset($payload['bisect_max_depth']) ? (int)$payload['bisect_max_depth'] : 6,
			'bisect_min_chars' => isset($payload['bisect_min_chars']) ? (int)$payload['bisect_min_chars'] : 280,
			'english_leak_retry' => array_key_exists('english_leak_retry', $payload) ? !empty($payload['english_leak_retry']) : 1,
			'english_leak_min_words' => isset($payload['english_leak_min_words']) ? (int)$payload['english_leak_min_words'] : 4,
			'english_leak_max_retries' => isset($payload['english_leak_max_retries']) ? (int)$payload['english_leak_max_retries'] : 1,
			'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
			'source_override' => $source,
			'cluster_job' => 1,
			'cluster_dst_langs' => $dst_langs,
		), array('priority' => isset($job['priority']) ? (int)$job['priority'] : -5));
		if ($child_id) {
			$enqueued++;
			$pending_map[(int)$dst_lang] = true;
			$slots--;
		}
	}
	translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
	if (!translation_cluster_has_pending_job($entity, $entity_id, 'validate_cluster', 0)) {
		admin_jobs_enqueue('translations', 'validate_cluster', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src_lang,
			'dst_langs' => $dst_langs,
			'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
			'cluster_repair_round' => 0,
		), array('priority' => -3, 'scheduled_at' => function_exists('admin_jobs_mysql_schedule_delay_seconds') ? admin_jobs_mysql_schedule_delay_seconds(20) : date('Y-m-d H:i:s', time() + 20)));
	}
	return array(
		'ok' => true,
		'message' => "Cluster {$entity}#{$entity_id}: queued locale jobs={$enqueued}, already pending={$already_pending}, queue_budget={$queue_budget}, total=" . count($dst_langs),
	);
}

function run_translations_validate_locale($payload = array(), $job = array()) {
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 1;
	$dst_lang = isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0;
	$dst_langs = isset($payload['dst_langs']) && is_array($payload['dst_langs']) ? $payload['dst_langs'] : array();
	$repair_attempt = isset($payload['repair_attempt']) ? max(0, (int)$payload['repair_attempt']) : 0;
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	if ($entity === '' || $entity_id <= 0 || $src_lang <= 0 || $dst_lang <= 0) {
		return array('ok' => false, 'message' => 'Bad validate_locale payload');
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang, $dst_langs);
	$source = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang);
	$source = translation_cluster_normalize_source_meta(is_array($source) ? $source : array(), $entity);
	$lang_row = mysql_select("SELECT url, name FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$src_lang_row = mysql_select("SELECT name FROM languages WHERE id=" . (int)$src_lang . " LIMIT 1", 'row');
	$dst_row = mysql_select("
		SELECT id, name, title, description, content, status
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . (int)$entity_id . "
		  AND lang_id=" . (int)$dst_lang . "
		ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
		LIMIT 1
	", 'row');
	if (!$dst_row) {
		translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
		if (!translation_cluster_has_pending_job($entity, $entity_id, 'translate', $dst_lang)) {
			admin_jobs_enqueue('translations', 'translate', array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'src_lang' => $src_lang,
				'dst_lang' => $dst_lang,
				'fields' => translation_cluster_translate_fields($entity),
				'cluster_job' => 1,
				'cluster_dst_langs' => $dst_langs,
				'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
			), array('priority' => -5));
		}
		return array('ok' => true, 'message' => "Locale {$entity}#{$entity_id} {$dst_lang}: missing row, requeued translate");
	}
	$validation = translation_cluster_validate_locale($source, $dst_row, $lang_row ? (string)$lang_row['url'] : '', $entity);
	$signals = isset($validation['content_signals']) && is_array($validation['content_signals']) ? $validation['content_signals'] : array();
	$ai_tail_reason = '';
	if (
		empty($validation['blockers'])
		&& !empty($signals['warnings'])
		&& in_array('source_sentence_copy', $signals['warnings'], true)
	) {
		$aiCheck = _translations_ai_check_untranslated_tail(
			isset($source['content']) ? (string)$source['content'] : '',
			isset($dst_row['content']) ? (string)$dst_row['content'] : '',
			isset($src_lang_row['name']) ? (string)$src_lang_row['name'] : ('lang_id=' . $src_lang),
			isset($lang_row['name']) ? (string)$lang_row['name'] : ('lang_id=' . $dst_lang)
		);
		if (!empty($aiCheck['ok']) && !empty($aiCheck['suspect'])) {
			$validation['blockers'][] = 'ai_untranslated_tail';
			$ai_tail_reason = isset($aiCheck['reason']) ? (string)$aiCheck['reason'] : '';
		}
	}
	translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
	if (!empty($validation['blockers'])) {
		if ($repair_attempt < 2 && translation_cluster_translate_queue_budget() > 0 && !translation_cluster_has_pending_job($entity, $entity_id, 'repair_locale', $dst_lang)) {
			admin_jobs_enqueue('translations', 'repair_locale', array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'src_lang' => $src_lang,
				'dst_lang' => $dst_lang,
				'dst_langs' => $dst_langs,
				'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
				'repair_attempt' => $repair_attempt + 1,
				'validation_blockers' => $validation['blockers'],
				'validation_warnings' => $validation['warnings'],
				'ai_tail_reason' => $ai_tail_reason,
			), array('priority' => -4));
			return array('ok' => true, 'message' => "Locale {$entity}#{$entity_id} {$dst_lang}: blockers found, queued repair");
		}
	}
	if (!translation_cluster_has_pending_job($entity, $entity_id, 'validate_cluster', 0)) {
		admin_jobs_enqueue('translations', 'validate_cluster', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src_lang,
			'dst_langs' => $dst_langs,
			'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
			'cluster_repair_round' => 0,
		), array('priority' => -3));
	}
	return array('ok' => true, 'message' => "Locale {$entity}#{$entity_id} {$dst_lang}: validate complete");
}

function run_translations_repair_locale($payload = array(), $job = array()) {
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 1;
	$dst_lang = isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0;
	$dst_langs = isset($payload['dst_langs']) && is_array($payload['dst_langs']) ? $payload['dst_langs'] : array();
	$repair_attempt = isset($payload['repair_attempt']) ? max(1, (int)$payload['repair_attempt']) : 1;
	$blockers = isset($payload['validation_blockers']) && is_array($payload['validation_blockers']) ? $payload['validation_blockers'] : array();
	$warnings = isset($payload['validation_warnings']) && is_array($payload['validation_warnings']) ? $payload['validation_warnings'] : array();
	if ($entity === '' || $entity_id <= 0 || $src_lang <= 0 || $dst_lang <= 0) {
		return array('ok' => false, 'message' => 'Bad repair_locale payload');
	}
	$fields = array();
	foreach ($blockers as $b) {
		if (strpos((string)$b, 'meta_') === 0) {
			$fields[] = substr((string)$b, 5);
		}
	}
	// SEO Monitor / cluster warnings (no meta_* blocker): map to short fields so we do not fall through to full content.
	foreach ($warnings as $w) {
		$w = (string)$w;
		if ($w === 'title_too_long') {
			$fields[] = 'title';
			$fields[] = 'name';
		} elseif ($w === 'description_too_long') {
			$fields[] = 'description';
		}
	}
	$fields = array_values(array_unique(array_intersect($fields, array('name', 'title', 'description'))));
	$content_related = false;
	foreach (array_merge($blockers, $warnings) as $sig) {
		$s = (string)$sig;
		if (preg_match('/^(structure_|content_|english_tail|ai_untranslated_tail|source_sentence_copy|forbidden_promo_ui)/', $s)
			|| $s === 'h1_not_single' || $s === 'img_missing_alt') {
			$content_related = true;
			break;
		}
	}
	if ($content_related || $fields === array()) {
		$fields[] = 'content';
	}
	$fields = array_values(array_unique($fields));
	$job_id_for_cluster = isset($job['id']) ? (int)$job['id'] : 0;
	translation_cluster_maybe_persist_structure_alignment($entity, $entity_id, $src_lang, $dst_lang, $dst_langs, $job_id_for_cluster);

	// If deterministic alignment cleared structural blockers, do not run full content repair — it forces segment JSON
	// (structure repair) with no fallback and fails on transient AI errors ("segment JSON batch failed"), leaving the cluster stuck.
	$lang_row = mysql_select("SELECT url FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$lang_url = isset($lang_row['url']) ? trim((string)$lang_row['url'], '/') : '';
	$src_snap = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang);
	$dst_row_v = mysql_select("
		SELECT * FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . (int)$entity_id . "
		  AND lang_id=" . (int)$dst_lang . "
		ORDER BY id DESC
		LIMIT 1
	", 'row');
	$res = array('ok' => false, 'message' => 'repair_locale: not started');
	$translate_payload_base = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'src_lang' => $src_lang,
		'dst_lang' => $dst_lang,
		'chunk_max_len' => isset($payload['chunk_max_len']) ? (int)$payload['chunk_max_len'] : 2200,
		'content_chunk_cap' => isset($payload['content_chunk_cap']) ? (int)$payload['content_chunk_cap'] : 650,
		'bisect_max_depth' => isset($payload['bisect_max_depth']) ? (int)$payload['bisect_max_depth'] : 8,
		'bisect_min_chars' => isset($payload['bisect_min_chars']) ? (int)$payload['bisect_min_chars'] : 220,
		'english_leak_retry' => 1,
		'english_leak_min_words' => isset($payload['english_leak_min_words']) ? (int)$payload['english_leak_min_words'] : 4,
		'english_leak_max_retries' => 2,
		'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
		'cluster_job' => 1,
		'cluster_dst_langs' => $dst_langs,
		'metadata_normalize' => 1,
		'max_job_seconds' => 1200,
		'repair_attempt' => $repair_attempt,
		'repair_pass' => 1,
	);
	if (is_array($dst_row_v) && $lang_url !== '') {
		$v = translation_cluster_validate_locale($src_snap, $dst_row_v, $lang_url, $entity);
		if (empty($v['blockers'])) {
			$meta_only = array();
			$warn_list = (isset($v['warnings']) && is_array($v['warnings'])) ? $v['warnings'] : array();
			foreach ($warn_list as $w) {
				$w = (string)$w;
				if ($w === 'title_too_long') {
					$meta_only[] = 'title';
					$meta_only[] = 'name';
				} elseif ($w === 'description_too_long') {
					$meta_only[] = 'description';
				}
			}
			$meta_only = array_values(array_unique(array_intersect($meta_only, array('name', 'title', 'description'))));
			if ($meta_only === array()) {
				$res = array('ok' => true, 'message' => 'repair_locale: validation clear after structure alignment; skipped LLM');
			} else {
				$res = run_translations_translate(array_merge($translate_payload_base, array(
					'fields' => $meta_only,
					'validation_blockers' => array(),
					'validation_warnings' => isset($v['warnings']) && is_array($v['warnings']) ? $v['warnings'] : array(),
				)), $job);
			}
		} else {
			$res = run_translations_translate(array_merge($translate_payload_base, array(
				'fields' => $fields,
				'validation_blockers' => $blockers,
				'validation_warnings' => $warnings,
			)), $job);
		}
	} else {
		$res = run_translations_translate(array_merge($translate_payload_base, array(
			'fields' => $fields,
			'validation_blockers' => $blockers,
			'validation_warnings' => $warnings,
		)), $job);
	}
	translation_cluster_maybe_persist_structure_alignment($entity, $entity_id, $src_lang, $dst_lang, $dst_langs, $job_id_for_cluster);
	if (!translation_cluster_has_pending_job($entity, $entity_id, 'validate_locale', $dst_lang)) {
		admin_jobs_enqueue('translations', 'validate_locale', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'src_lang' => $src_lang,
			'dst_lang' => $dst_lang,
			'dst_langs' => $dst_langs,
			'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
			'repair_attempt' => $repair_attempt,
		), array('priority' => -3));
	}
	return array('ok' => !empty($res['ok']), 'message' => isset($res['message']) ? (string)$res['message'] : 'repair locale complete');
}

function run_translations_validate_cluster($payload = array(), $job = array()) {
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 1;
	$dst_langs = isset($payload['dst_langs']) && is_array($payload['dst_langs']) ? $payload['dst_langs'] : array();
	$round = isset($payload['cluster_repair_round']) ? max(0, (int)$payload['cluster_repair_round']) : 0;
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	if ($entity === '' || $entity_id <= 0 || $src_lang <= 0) {
		return array('ok' => false, 'message' => 'Bad validate_cluster payload');
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang, $dst_langs);
	$pending = translation_cluster_pending_translation_jobs($entity, $entity_id);
	foreach ($pending as $meta) {
		$action = isset($meta['action']) ? (string)$meta['action'] : '';
		if (in_array($action, array('translate', 'validate_locale', 'repair_locale'), true)) {
			translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
			if ($round < 6) {
				admin_jobs_enqueue('translations', 'validate_cluster', array(
					'entity' => $entity,
					'entity_id' => $entity_id,
					'src_lang' => $src_lang,
					'dst_langs' => $dst_langs,
					'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
					'cluster_repair_round' => $round + 1,
				), array('priority' => -3, 'scheduled_at' => function_exists('admin_jobs_mysql_schedule_delay_seconds') ? admin_jobs_mysql_schedule_delay_seconds(20) : date('Y-m-d H:i:s', time() + 20)));
			}
			return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: waiting for child jobs");
		}
	}
	translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
	$state = translation_cluster_get_state($entity, $entity_id);
	$report = (!empty($state['validation_json'])) ? @json_decode((string)$state['validation_json'], true) : null;
	$queued = 0;
	$queue_budget = translation_cluster_translate_queue_budget();
	$child_cap = translation_cluster_child_enqueue_cap();
	$slots = min($queue_budget, $child_cap);
	$remaining_work = false;
	// Align with validate_cluster requeue cap when child jobs are pending (round < 6): meta/SEO repairs may need several passes.
	if (is_array($report) && !empty($report['locales']) && $round < 6) {
		foreach ($report['locales'] as $loc) {
			$loc_missing = !empty($loc['missing']);
			$loc_blockers = isset($loc['blockers']) && is_array($loc['blockers']) ? $loc['blockers'] : array();
			$loc_warnings = isset($loc['warnings']) && is_array($loc['warnings']) ? $loc['warnings'] : array();
			$warnings_repair = function_exists('translation_cluster_warnings_for_repair_queue')
				? translation_cluster_warnings_for_repair_queue($loc_warnings)
				: $loc_warnings;
			if ($loc_missing || $loc_blockers !== array() || $warnings_repair !== array()) {
				$remaining_work = true;
			}
			if ($slots <= 0) {
				break;
			}
			$dst_lang = isset($loc['lang_id']) ? (int)$loc['lang_id'] : 0;
			if ($dst_lang <= 0) {
				continue;
			}
			if ($loc_missing) {
				if (!translation_cluster_has_pending_job($entity, $entity_id, 'translate', $dst_lang)) {
					admin_jobs_enqueue('translations', 'translate', array(
						'entity' => $entity,
						'entity_id' => $entity_id,
						'src_lang' => $src_lang,
						'dst_lang' => $dst_lang,
						'fields' => translation_cluster_translate_fields($entity),
						'cluster_job' => 1,
						'cluster_dst_langs' => $dst_langs,
						'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
					), array('priority' => -5));
					$queued++;
					$slots--;
				}
				continue;
			}
			$blockers = $loc_blockers;
			$warnings = $warnings_repair;
			if (($blockers !== array() || $warnings !== array()) && !translation_cluster_has_pending_job($entity, $entity_id, 'repair_locale', $dst_lang)) {
				admin_jobs_enqueue('translations', 'repair_locale', array(
					'entity' => $entity,
					'entity_id' => $entity_id,
					'src_lang' => $src_lang,
					'dst_lang' => $dst_lang,
					'dst_langs' => $dst_langs,
					'autopilot' => !empty($payload['autopilot']) ? 1 : 0,
					'repair_attempt' => $round + 1,
					'validation_blockers' => $blockers,
					'validation_warnings' => $loc_warnings,
				), array('priority' => -4));
				$queued++;
				$slots--;
			}
		}
	}
	if ($queued > 0) {
		return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: queued repair jobs={$queued}, queue_budget={$queue_budget}");
	}
	if ($remaining_work && $slots <= 0) {
		return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: queue saturated, waiting for free slots");
	}
	if ($queued === 0 && $remaining_work && $round >= 6) {
		$pub_suffix = '';
		if (!empty($payload['autopilot'])) {
			if (translation_cluster_maybe_autopublish_after_validate($entity, $entity_id, $payload)) {
				$pub_suffix = ' · autopublished all locales';
			}
		}
		return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: validation finalized (cluster_repair_round cap; issues may remain — check validation_json){$pub_suffix}");
	}
	$pub_suffix = '';
	if (!empty($payload['autopilot'])) {
		if (translation_cluster_maybe_autopublish_after_validate($entity, $entity_id, $payload)) {
			$pub_suffix = ' · autopublished all locales';
		}
	}
	return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: validation finalized{$pub_suffix}");
}

/**
 * One long-running job: drain pending translation jobs for this cluster (chunked by child job),
 * then run validate_cluster when the queue is empty. Heartbeat on parent; reap uses admin_jobs_touch.
 *
 * Payload: entity, entity_id, src_lang, dst_langs, cluster_repair_round?, autopilot?,
 *   max_seconds (60–3600, default 900), max_steps (0 = unlimited within time, max 500), max_idle_rounds (1–50, default 4).
 */
function run_translations_cluster_pipeline($payload = array(), $job = array()) {
	$entity = isset($payload['entity']) ? trim((string)$payload['entity']) : '';
	$entity_id = isset($payload['entity_id']) ? (int)$payload['entity_id'] : 0;
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 1;
	$dst_langs = isset($payload['dst_langs']) && is_array($payload['dst_langs']) ? $payload['dst_langs'] : array();
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	if ($entity === '' || $entity_id <= 0 || $src_lang <= 0) {
		return array('ok' => false, 'message' => 'Bad cluster_pipeline payload');
	}
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang, $dst_langs);

	$max_seconds = isset($payload['max_seconds']) ? (int)$payload['max_seconds'] : 900;
	if ($max_seconds < 60) {
		$max_seconds = 60;
	}
	if ($max_seconds > 3600) {
		$max_seconds = 3600;
	}
	$max_steps = isset($payload['max_steps']) ? (int)$payload['max_steps'] : 0;
	if ($max_steps < 0) {
		$max_steps = 0;
	}
	if ($max_steps > 500) {
		$max_steps = 500;
	}
	$max_idle_rounds = isset($payload['max_idle_rounds']) ? (int)$payload['max_idle_rounds'] : 4;
	if ($max_idle_rounds < 1) {
		$max_idle_rounds = 1;
	}
	if ($max_idle_rounds > 50) {
		$max_idle_rounds = 50;
	}

	$child_filters = array(
		'module' => 'translations',
		'cluster_entity' => $entity,
		'cluster_entity_id' => $entity_id,
		'cluster_actions' => array('translate', 'repair_locale', 'validate_locale', 'translate_cluster', 'validate_cluster'),
	);

	$t0 = microtime(true);
	$steps = 0;
	$idle_rounds = 0;
	$last_msg = '';

	while ((microtime(true) - $t0) < $max_seconds) {
		if ($job_id > 0) {
			admin_jobs_touch($job_id, "cluster_pipeline: steps={$steps} idle={$idle_rounds}");
		}
		if (function_exists('mysql_connect_db')) {
			mysql_connect_db();
		}
		$r = process_one_admin_job_filtered($child_filters);
		if (!empty($r['processed'])) {
			$steps++;
			$idle_rounds = 0;
			$last_msg = isset($r['message']) ? (string)$r['message'] : '';
			if ($max_steps > 0 && $steps >= $max_steps) {
				return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: cluster_pipeline max_steps={$max_steps} steps={$steps} last={$last_msg}");
			}
			continue;
		}

		// No due pending jobs for this cluster — advance validation / enqueue next batch.
		$vc = run_translations_validate_cluster($payload, $job);
		$msg = isset($vc['message']) ? (string)$vc['message'] : '';
		$last_msg = $msg;

		if (strpos($msg, 'waiting for child jobs') !== false) {
			$idle_rounds = 0;
			usleep(800000);
			continue;
		}
		if (strpos($msg, 'queued') !== false || strpos($msg, 'queue saturated') !== false) {
			$idle_rounds = 0;
			continue;
		}
		if (strpos($msg, 'validation finalized') !== false) {
			translation_cluster_refresh_state($entity, $entity_id, $src_lang, $dst_langs, $job_id);
			return array('ok' => !empty($vc['ok']), 'message' => "Cluster {$entity}#{$entity_id}: cluster_pipeline done — {$msg}");
		}
		$idle_rounds++;
		if ($idle_rounds >= $max_idle_rounds) {
			return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: cluster_pipeline idle stop (rounds={$idle_rounds}) last={$last_msg}");
		}
		usleep(300000);
	}

	return array('ok' => true, 'message' => "Cluster {$entity}#{$entity_id}: cluster_pipeline time budget max_seconds={$max_seconds} steps={$steps} last={$last_msg}");
}

/**
 * Translate selected keys in files/languages/{dst}/dictionary/common.php via AI.
 * Payload: src_lang, dst_lang, dict_keys (array of key names), chunk_max_len?, max_job_seconds?
 */
function run_translations_translate_common_dict($payload = array(), $job = array()) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$src_lang = isset($payload['src_lang']) ? (int)$payload['src_lang'] : 0;
	$dst_lang = isset($payload['dst_lang']) ? (int)$payload['dst_lang'] : 0;
	$keys = isset($payload['dict_keys']) && is_array($payload['dict_keys']) ? $payload['dict_keys'] : array();
	$max_len = isset($payload['chunk_max_len']) ? (int)$payload['chunk_max_len'] : 2500;
	$job_id = isset($job['id']) ? (int)$job['id'] : 0;
	$max_job_seconds = isset($payload['max_job_seconds']) ? (int)$payload['max_job_seconds'] : 1200;
	if ($max_job_seconds <= 0) {
		$max_job_seconds = 1200;
	}

	$norm_keys = array();
	foreach ($keys as $k) {
		$k = trim((string)$k);
		if ($k !== '') {
			$norm_keys[$k] = true;
		}
	}
	$keys = array_keys($norm_keys);

	if ($src_lang <= 0 || $dst_lang <= 0 || $src_lang === $dst_lang) {
		return array('ok' => false, 'message' => 'Bad src/dst language for dictionary job');
	}
	if ($keys === array()) {
		return array('ok' => false, 'message' => 'No dict_keys in payload');
	}

	$key = _translations_pick_key();
	if (!$key) {
		return array('ok' => false, 'message' => 'No enabled AI keys in ai_provider_keys');
	}

	$lang_src = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$src_lang . " LIMIT 1", 'row');
	$lang_dst = mysql_select("SELECT id,url,name FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
	$src_lang_name = !empty($lang_src['name']) ? (string)$lang_src['name'] : ('lang_id=' . (int)$src_lang);
	$dst_lang_name = !empty($lang_dst['name']) ? (string)$lang_dst['name'] : ('lang_id=' . (int)$dst_lang);

	$src_dict = admin_load_common_dict($src_lang);
	if (empty($src_dict)) {
		return array('ok' => false, 'message' => 'Source common.php empty or missing for lang_id=' . (int)$src_lang);
	}
	$dst_existing = admin_load_common_dict($dst_lang);
	// Never write a stripped file: keep one row per canonical source key (same as prune / languages_json).
	$full = array();
	foreach (array_keys($src_dict) as $_k) {
		$full[$_k] = isset($dst_existing[$_k]) ? (string)$dst_existing[$_k] : '';
	}
	$job_start_ts = time();
	$log_prefix = 'dict_job#' . (int)$job_id . ' ' . (int)$src_lang . '→' . (int)$dst_lang;
	$done = 0;
	$failed = 0;
	$skipped_empty_src = 0;

	system_log_add('translations', 'info', 'translate_common_dict start (' . $log_prefix . ')', array(
		'keys' => count($keys),
		'job_id' => (int)$job_id,
	));

	foreach ($keys as $dict_key) {
		if ((time() - $job_start_ts) > $max_job_seconds) {
			$save = admin_save_common_dict($dst_lang, $full);
			system_log_add('translations', 'warning', 'translate_common_dict timeout (' . $log_prefix . ')', array(
				'done' => $done,
				'failed' => $failed,
				'saved' => !empty($save['ok']),
			));
			return array(
				'ok' => false,
				'message' => 'Timeout after ' . $done . ' key(s); partial results ' . (!empty($save['ok']) ? 'saved' : 'not saved') . '.',
			);
		}

		$src_text = isset($src_dict[$dict_key]) ? (string)$src_dict[$dict_key] : '';
		if (trim($src_text) === '') {
			$skipped_empty_src++;
			continue;
		}

		$chunks = _translations_split_chunks($src_text, $max_len);
		$translated_chunks = array();
		$field_failed = false;

		foreach ($chunks as $c) {
			$sys = "You are a professional website translator.\n"
				. "Translate the provided UI string from {$src_lang_name} to {$dst_lang_name}.\n"
				. "Return ONLY the translated text.\n"
				. "Preserve HTML tags and all attributes exactly.\n"
				. "Preserve placeholders like {year}, {img}, shortcodes, and numbers where appropriate.\n"
				. "DO NOT translate URL paths or query strings.\n"
				. "Output must be fully in {$dst_lang_name} (no mixed languages).\n"
				. "Do not add commentary.";
			$user = "UI dictionary key: {$dict_key}\nSource: {$src_lang_name}\nTarget: {$dst_lang_name}\n\nTEXT:\n" . $c;

			$max_ai_tries = 2;
			$ai_try = 0;
			$chunk_ok = false;
			while ($ai_try < $max_ai_tries) {
				$ai_try++;
				$res = ai_gateway_chat($key['provider'], $key['api_key'], $key['model_default'], array(
					array('role' => 'system', 'content' => $sys),
					array('role' => 'user', 'content' => $user),
				));
				if (!empty($res['ok'])) {
					$translated_chunks[] = trim((string)$res['reply_text']);
					$chunk_ok = true;
					break;
				}
			}
			if (!$chunk_ok) {
				$field_failed = true;
				break;
			}
		}

		if (!$field_failed && $translated_chunks !== array()) {
			$merged = trim(implode("\n\n", $translated_chunks));
			if ($merged === '') {
				$failed++;
				system_log_add('translations', 'warning', 'translate_common_dict empty AI output (' . $log_prefix . ')', array('key' => $dict_key));
			} else {
				$full[$dict_key] = $merged;
				$done++;
				$save = admin_save_common_dict($dst_lang, $full);
				if (empty($save['ok'])) {
					return array('ok' => false, 'message' => 'Failed saving dictionary: ' . (isset($save['message']) ? (string)$save['message'] : 'unknown'));
				}
				system_log_add('translations', 'info', 'translate_common_dict key OK (' . $log_prefix . ')', array('key' => $dict_key));
			}
		} else {
			$failed++;
			system_log_add('translations', 'error', 'translate_common_dict key failed (' . $log_prefix . ')', array('key' => $dict_key));
		}
	}

	if ($done === 0 && $failed > 0) {
		return array('ok' => false, 'message' => 'All ' . $failed . ' key(s) failed AI translation.');
	}
	if ($done === 0) {
		return array('ok' => false, 'message' => 'No translatable source text for selection (' . (int)$skipped_empty_src . ' empty in source).');
	}
	return array(
		'ok' => true,
		'message' => 'Updated ' . $done . ' dictionary key(s)' . ($failed ? ('; ' . $failed . ' failed') : '') . '.',
	);
}

/**
 * Wipe translation_vector_items (chunked DELETE + OPTIMIZE when empty).
 * Payload: chunk?, max_chunks?, pause_ms?
 */
function run_translations_clear_vector_db($payload = array(), $job = array()) {
	@set_time_limit(0);
	if (function_exists('ini_set')) {
		@ini_set('memory_limit', '256M');
	}
	$res = translation_vector_clear_all(array(
		'chunk' => isset($payload['chunk']) ? (int)$payload['chunk'] : 500,
		'max_chunks' => isset($payload['max_chunks']) ? (int)$payload['max_chunks'] : 500,
		'pause_ms' => isset($payload['pause_ms']) ? (int)$payload['pause_ms'] : 100,
	));
	$ok = !empty($res['ok']);
	if ($ok && isset($res['remaining']) && (int)$res['remaining'] > 0) {
		$ok = false;
		$res['message'] = (isset($res['message']) ? (string)$res['message'] : '')
			. ' Re-run clear_vector_db job or increase max_chunks.';
	}
	if ($ok && function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'Translation vector DB cleared', array(
			'deleted' => isset($res['deleted']) ? (int)$res['deleted'] : 0,
			'chunks' => isset($res['chunks']) ? (int)$res['chunks'] : 0,
		));
	}
	return array(
		'ok' => $ok,
		'message' => isset($res['message']) ? (string)$res['message'] : ($ok ? 'Vector DB cleared.' : 'Vector clear failed.'),
	);
}

