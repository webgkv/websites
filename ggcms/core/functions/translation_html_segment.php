<?php
/**
 * Segment translation: extract text nodes from source HTML, translate JSON arrays, re-apply into same markup.
 * Keeps tag counts identical to source (structure_* validation) without asking the LLM to emit HTML.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * @return array{ok:bool,message?:string,template?:string,segments?:string[],ids?:string[]}
 */
function translation_html_segment_extract($html) {
	$html = (string)$html;
	if (trim($html) === '') {
		return array('ok' => false, 'message' => 'empty html');
	}
	if (!class_exists('DOMDocument')) {
		return array('ok' => false, 'message' => 'DOM extension missing');
	}
	libxml_use_internal_errors(true);
	$dom = new DOMDocument('1.0', 'UTF-8');
	$wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="translation-segment-root">' . $html . '</div></body></html>';
	if (!@$dom->loadHTML($wrapped)) {
		libxml_clear_errors();
		return array('ok' => false, 'message' => 'loadHTML failed');
	}
	libxml_clear_errors();
	$root = $dom->getElementById('translation-segment-root');
	if (!$root) {
		return array('ok' => false, 'message' => 'root missing');
	}
	$xp = new DOMXPath($dom);
	$segments = array();
	$ids = array();
	$n = 0;
	foreach ($xp->query('.//text()', $root) as $text) {
		if (!$text->parentNode) {
			continue;
		}
		$pn = strtolower($text->parentNode->nodeName);
		if ($pn === 'script' || $pn === 'style' || $pn === 'noscript') {
			continue;
		}
		$val = (string)$text->nodeValue;
		if (trim($val) === '') {
			continue;
		}
		$n++;
		$id = 'TSEG' . str_pad((string)$n, 8, '0', STR_PAD_LEFT);
		$marker = '__' . $id . '__';
		$ids[] = $id;
		$segments[] = $val;
		$text->nodeValue = $marker;
	}
	if ($n === 0) {
		return array('ok' => false, 'message' => 'no text nodes');
	}
	$inner = '';
	foreach ($root->childNodes as $ch) {
		$inner .= $dom->saveHTML($ch);
	}
	return array(
		'ok' => true,
		'template' => $inner,
		'segments' => $segments,
		'ids' => $ids,
	);
}

/**
 * @param string $template HTML with __TSEGxxxxxxxx__ placeholders
 * @param string[] $ids
 * @param string[] $translated Same count as ids
 */
function translation_html_segment_apply($template, array $ids, array $translated) {
	$out = (string)$template;
	$cnt = count($ids);
	for ($i = 0; $i < $cnt; $i++) {
		$marker = '__' . $ids[$i] . '__';
		$rep = isset($translated[$i]) ? (string)$translated[$i] : '';
		$out = str_replace($marker, $rep, $out);
	}
	return $out;
}

/**
 * @return bool
 */
function translation_html_segment_should_use(array $payload, $field) {
	if ((string)$field !== 'content') {
		return false;
	}
	if (isset($payload['content_json_segment_mode']) && (int)$payload['content_json_segment_mode'] === 0) {
		return false;
	}
	if (!empty($payload['content_json_segment_mode'])) {
		return true;
	}
	// Default: cluster pipeline jobs — skeleton from source, LLM only translates text segments.
	if (!empty($payload['cluster_job'])) {
		return true;
	}
	return false;
}

/**
 * Cluster validation reported structure_* mismatch — target HTML must be rebuilt from the English source skeleton.
 *
 * @return bool
 */
function translation_html_segment_force_for_structure(array $payload) {
	if (empty($payload['validation_blockers']) || !is_array($payload['validation_blockers'])) {
		return false;
	}
	foreach ($payload['validation_blockers'] as $b) {
		if (strpos((string)$b, 'structure_') === 0) {
			return true;
		}
	}
	return false;
}

/**
 * Whether to use segment JSON + re-inject for `content` (overrides content_json_segment_mode=0 when structure repair is required).
 *
 * @return bool
 */
function translation_html_segment_effective_should_use(array $payload, $field) {
	if ((string)$field !== 'content') {
		return false;
	}
	if (translation_html_segment_force_for_structure($payload)) {
		return true;
	}
	return translation_html_segment_should_use($payload, $field);
}

/**
 * Optional explicit list from variables.translation_settings JSON: segment_json_dense_lang_ids (int[]).
 * When non-empty, only these lang_id values use the smaller batch size.
 *
 * @return int[]
 */
function translation_html_segment_dense_lang_ids_from_settings() {
	static $ids = null;
	if ($ids !== null) {
		return $ids;
	}
	$ids = array();
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return $ids;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || trim((string)$row['value']) === '') {
		return $ids;
	}
	$dec = @json_decode((string)$row['value'], true);
	if (!is_array($dec) || empty($dec['segment_json_dense_lang_ids']) || !is_array($dec['segment_json_dense_lang_ids'])) {
		return $ids;
	}
	foreach ($dec['segment_json_dense_lang_ids'] as $x) {
		$i = (int)$x;
		if ($i > 0) {
			$ids[] = $i;
		}
	}
	$ids = array_values(array_unique($ids));
	return $ids;
}

/**
 * Bengali, Thai, Arabic, Indic scripts, etc. produce long JSON (\\u escapes) per segment — use smaller batches.
 *
 * @return bool
 */
function translation_html_segment_lang_looks_dense_script($dst_lang) {
	$dst_lang = (int)$dst_lang;
	if ($dst_lang <= 0) {
		return false;
	}
	$explicit = translation_html_segment_dense_lang_ids_from_settings();
	if ($explicit !== array()) {
		return in_array($dst_lang, $explicit, true);
	}
	$row = mysql_select("SELECT url, name FROM languages WHERE id=" . $dst_lang . " LIMIT 1", 'row');
	if (!$row) {
		return false;
	}
	$hay = strtolower(trim((string)($row['url'] ?? '') . ' ' . ($row['name'] ?? '')));
	return (bool)preg_match(
		'/\b(bn|bengali|bangla|bn-bd|hi|hindi|th|thai|arab|hebrew|he\b|myanmar|burmese|tamil|telugu|urdu|khmer|gujarati|punjabi|pa\b|marathi|malayalam|nepali|sinhala|oriya|odia|kannada|kannad)\b/u',
		$hay
	);
}

/**
 * Normal batch size (default 28) vs dense (default 10) from translation_settings.
 *
 * @return int
 */
function translation_html_segment_batch_size_for_dst_lang($dst_lang) {
	require_once ROOT_DIR . 'functions/translation_cluster.php';
	$normal = translation_cluster_setting_int('segment_json_batch_size', 28);
	$dense = translation_cluster_setting_int('segment_json_batch_size_dense', 10);
	$normal = max(4, min(48, $normal));
	$dense = max(4, min(24, $dense));
	if (translation_html_segment_lang_looks_dense_script((int)$dst_lang)) {
		return $dense;
	}
	return $normal;
}

/**
 * @param array<string,string> $prompt_tpl merged templates
 * @return array{ok:bool,message?:string,html?:string}
 */
function translation_html_segment_translate_full(
	array $prompt_tpl,
	$provider,
	$api_key,
	$model_used,
	array $segments,
	$src_lang_name,
	$dst_lang_name,
	$src_lang,
	$dst_lang,
	$log_prefix,
	$job_id,
	$max_job_seconds,
	$job_start_ts
) {
	require_once ROOT_DIR . 'functions/ai_gateway.php';
	require_once ROOT_DIR . 'functions/ai_prompt_templates.php';
	$max_seg_chars = 120000;
	$total_chars = 0;
	foreach ($segments as $s) {
		$total_chars += mb_strlen((string)$s, 'UTF-8');
	}
	if ($total_chars > $max_seg_chars) {
		return array('ok' => false, 'message' => 'segment mode skipped: total text too large');
	}
	$batch_size = translation_html_segment_batch_size_for_dst_lang((int)$dst_lang);
	$out = array();
	$batches = array_chunk($segments, $batch_size);
	$bi = 0;
	foreach ($batches as $batch) {
		if ((time() - (int)$job_start_ts) > (int)$max_job_seconds) {
			return array('ok' => false, 'message' => 'segment JSON: timeout');
		}
		$bi++;
		$sys = isset($prompt_tpl['translation_segment_json']) ? (string)$prompt_tpl['translation_segment_json'] : '';
		if ($sys === '') {
			return array('ok' => false, 'message' => 'translation_segment_json template missing');
		}
		$sys = ai_prompt_templates_render($sys, array(
			'src_lang_name' => (string)$src_lang_name,
			'dst_lang_name' => (string)$dst_lang_name,
		));
		$user_payload = array(
			'source_lang' => (string)$src_lang_name,
			'target_lang' => (string)$dst_lang_name,
			'source_lang_id' => (int)$src_lang,
			'target_lang_id' => (int)$dst_lang,
			'segments' => array_values($batch),
		);
		$user = "Translate each string in `segments` to {$dst_lang_name}. Return JSON only.\n\nINPUT:\n" . json_encode($user_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$ok_batch = false;
		for ($try = 1; $try <= 2; $try++) {
			if ($job_id > 0 && function_exists('admin_jobs_touch')) {
				admin_jobs_touch((int)$job_id, 'segment JSON: AI batch ' . $bi . '/' . count($batches) . ' try ' . $try);
			}
			$res = ai_gateway_chat((string)$provider, (string)$api_key, (string)$model_used, array(
				array('role' => 'system', 'content' => $sys),
				array('role' => 'user', 'content' => $user),
			));
			if (empty($res['ok'])) {
				continue;
			}
			$txt = trim((string)($res['reply_text'] ?? ''));
			$dec = translation_html_segment_parse_json_reply($txt);
			if (!is_array($dec) || !isset($dec['segments']) || !is_array($dec['segments'])) {
				continue;
			}
			$got = array_values($dec['segments']);
			if (count($got) !== count($batch)) {
				system_log_add('translations', 'warning', 'segment JSON length mismatch (' . $log_prefix . ') batch=' . $bi, array(
					'expected' => count($batch),
					'got' => count($got),
				));
				continue;
			}
			foreach ($got as $g) {
				$out[] = (string)$g;
			}
			$ok_batch = true;
			if ($job_id > 0 && function_exists('admin_jobs_touch')) {
				admin_jobs_touch((int)$job_id, 'segment JSON: batch ' . $bi . '/' . count($batches) . ' done');
			}
			break;
		}
		if (!$ok_batch) {
			return array('ok' => false, 'message' => 'segment JSON batch failed: ' . $bi);
		}
	}
	if (count($out) !== count($segments)) {
		return array('ok' => false, 'message' => 'segment JSON: merged length mismatch');
	}
	return array('ok' => true, 'translated' => $out);
}

/**
 * @return mixed
 */
function translation_html_segment_parse_json_reply($txt) {
	$txt = trim((string)$txt);
	if ($txt === '') {
		return null;
	}
	if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/u', $txt, $m)) {
		$txt = $m[1];
	}
	$dec = json_decode($txt, true);
	return is_array($dec) ? $dec : null;
}
