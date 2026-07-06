<?php
/**
 * Editable LLM prompt templates for translation jobs (admin + telemetry_control).
 * Stored in variables.key = ai_prompt_templates as JSON object (partial overrides of defaults).
 */

define('AI_PROMPT_TEMPLATES_VAR_KEY', 'ai_prompt_templates');

/**
 * @return array<string,string>
 */
function ai_prompt_templates_defaults() {
	return array(
		'translation_metadata' => "You are a professional website translator.\n"
			. "Translate the SOURCE text below from {src_lang_name} to {dst_lang_name}.\n"
			. "Return ONLY the translated string.\n"
			. "This is a short UI or SEO field: plain text suitable for a menu label, HTML title, or meta description.\n"
			. "Do NOT use hashtags (#). Do NOT use blockquotes, <font>, or bulky HTML.\n"
			. "Preserve brand names (Aviator, Spribe, RTP, etc.) and numbers; do not translate URL paths.\n"
			. "The product name \"Aviator\" is a proper noun: keep it exactly as \"Aviator\" in Latin script in titles and body (do not render as Aviateur, Aviador, Aviatore, Flieger, etc.).\n"
			. "Output must be fully in {dst_lang_name} (no mixed languages).\n"
			. "Do not add commentary."
			. '{examples_prompt}',
		'translation_content' => "You are a professional website translator.\n"
			. "Translate the provided content from {src_lang_name} to {dst_lang_name}.\n"
			. "Return ONLY the translated text.\n"
			. "Preserve HTML tags and all attributes exactly.\n"
			. "Keep the SAME number of each structural tag as in the source: h1, h2, h3, table, ol, ul, figure (do not merge sections, drop headings, or turn lists into paragraphs).\n"
			. "Do not duplicate or omit list items; each <li> in the source must map to exactly one <li> in the translation (same count for ordered and unordered lists).\n"
			. "The product name \"Aviator\" is a proper noun: keep it as \"Aviator\" in Latin script everywhere (do not translate it into a local word for pilot or plane).\n"
			. "Preserve placeholders like {img}, shortcodes, URLs, and numbers exactly.\n"
			. "DO NOT translate URL paths/query strings.\n"
			. "Output must be fully in {dst_lang_name} (no mixed languages).\n"
			. "Do not add commentary."
			. '{examples_prompt}',
		'translation_repair_suffix' => "\n\nREPAIR PASS:\n"
			. "A previous translation left untranslated tails, structure drift, or mixed-language leftovers.\n"
			. "Return a corrected final translation only.\n"
			. "Make sure every sentence is fully in {dst_lang_name}, while preserving all HTML and source facts exactly.",
		'translation_structure_lock' => "\n\nSTRUCTURE LOCK (required — cluster will reject otherwise):\n"
			. "The TARGET HTML must contain the SAME number of opening tags as the SOURCE for each kind: {structure_counts}.\n"
			. "Count tags in the full document: translate text inside tags only; do not remove, merge, or add h1/h2/h3, table, ol, ul, or figure vs. the source.\n"
			. "If the source uses lists and subheadings, the translation MUST still use the same list/heading structure (same tag counts).",
		'translation_meta_repair' => "\n\nMETA / SEO FIELD REPAIR:\n"
			. "Output plain text suitable for this field: no HTML tags, no #hashtags, no <font> blocks.\n"
			. "Keep length appropriate (title ~≤70 chars, description ~≤160 chars of readable snippet); fully {dst_lang_name}.",
		'translation_segment_json' => "You translate website copy for localization.\n"
			. "The `segments` array is built from the English page HTML: each entry is one DOM text node in reading order; the site keeps the original HTML skeleton and only substitutes your translated strings.\n"
			. "Therefore the merged page will keep the same headings, lists, and layout as English — you must not change the *meaning* of the DOM order (do not merge two segments into one string, do not split one segment into two, do not skip an entry).\n"
			. "You receive JSON with a `segments` array: each item is a plain text fragment from the source page (in document order).\n"
			. "Return ONLY valid JSON (no markdown fences, no commentary) with this exact shape:\n"
			. "{\"segments\":[\"...\",\"...\",...]}\n"
			. "Rules:\n"
			. "- `segments` MUST have the SAME number of strings as the input, in the SAME order.\n"
			. "- Each output string is the translation of the matching input string into {dst_lang_name}.\n"
			. "- Do NOT add HTML tags inside strings; fragments are plain text only.\n"
			. "- Preserve numbers, dates, brand names (Aviator, Spribe, RTP, etc.), and URL paths unchanged.\n"
			. "- Keep the word \"Aviator\" exactly as \"Aviator\" in Latin script in every segment where the English source says Aviator (do not substitute translated equivalents).\n"
			. "- Output must be fully in {dst_lang_name} (no mixed languages).",
	);
}

/**
 * @return array<int,string>
 */
function ai_prompt_templates_allowed_keys() {
	return array(
		'translation_metadata',
		'translation_content',
		'translation_repair_suffix',
		'translation_structure_lock',
		'translation_meta_repair',
		'translation_segment_json',
	);
}

/**
 * @param array<string,string> $vars
 */
function ai_prompt_templates_render($template, array $vars) {
	$out = (string)$template;
	foreach ($vars as $k => $v) {
		$out = str_replace('{' . $k . '}', (string)$v, $out);
	}
	return $out;
}

/**
 * Raw overrides from DB (may be partial).
 *
 * @return array<string,string>
 */
function ai_prompt_templates_stored_overrides() {
	$row = mysql_select("SELECT value FROM variables WHERE `key`='" . mysql_res(AI_PROMPT_TEMPLATES_VAR_KEY) . "' LIMIT 1", 'row');
	if (!$row || trim((string)$row['value']) === '') {
		return array();
	}
	$dec = @json_decode((string)$row['value'], true);
	return is_array($dec) ? $dec : array();
}

/**
 * Defaults merged with DB overrides (only allowed keys).
 *
 * @return array<string,string>
 */
function ai_prompt_templates_merged() {
	$def = ai_prompt_templates_defaults();
	$over = ai_prompt_templates_stored_overrides();
	$allow = array_flip(ai_prompt_templates_allowed_keys());
	foreach ($over as $k => $v) {
		if (!isset($allow[$k])) {
			continue;
		}
		if (!is_string($v)) {
			continue;
		}
		$def[$k] = $v;
	}
	return $def;
}

/**
 * Keys currently overridden (value differs from default).
 *
 * @return array<int,string>
 */
function ai_prompt_templates_custom_keys() {
	$d = ai_prompt_templates_defaults();
	$m = ai_prompt_templates_merged();
	$out = array();
	foreach (ai_prompt_templates_allowed_keys() as $k) {
		if (!isset($m[$k], $d[$k])) {
			continue;
		}
		if ((string)$m[$k] !== (string)$d[$k]) {
			$out[] = $k;
		}
	}
	return $out;
}

/**
 * Merge partial updates into variables (whitelist, length cap).
 *
 * @param array<string,mixed> $partial
 * @return array{ok:bool,message:string,saved_keys?:array}
 */
function ai_prompt_templates_save_partial(array $partial) {
	$allow = array_flip(ai_prompt_templates_allowed_keys());
	$max = 12000;
	$current = ai_prompt_templates_merged();
	$saved = array();
	foreach ($partial as $k => $v) {
		if (!isset($allow[$k])) {
			continue;
		}
		if (!is_string($v)) {
			continue;
		}
		if (strpos($v, "\0") !== false) {
			return array('ok' => false, 'message' => 'Invalid template: null byte');
		}
		if (strlen($v) > $max) {
			return array('ok' => false, 'message' => 'Template too long: ' . $k . ' (max ' . $max . ' bytes)');
		}
		$current[$k] = $v;
		$saved[] = $k;
	}
	if ($saved === array()) {
		return array('ok' => false, 'message' => 'No valid template keys to save');
	}
	$overrides = array();
	foreach (ai_prompt_templates_allowed_keys() as $k) {
		$def = ai_prompt_templates_defaults();
		if (isset($current[$k]) && isset($def[$k]) && (string)$current[$k] !== (string)$def[$k]) {
			$overrides[$k] = $current[$k];
		}
	}
	$json = json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	if ($json === false) {
		return array('ok' => false, 'message' => 'JSON encode failed');
	}
	$exists = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res(AI_PROMPT_TEMPLATES_VAR_KEY) . "' LIMIT 1", 'row');
	if ($exists) {
		mysql_fn('update', 'variables', array('value' => $json), " AND `key`='" . mysql_res(AI_PROMPT_TEMPLATES_VAR_KEY) . "' ");
	} else {
		mysql_fn('insert', 'variables', array('key' => AI_PROMPT_TEMPLATES_VAR_KEY, 'value' => $json));
	}
	return array('ok' => true, 'message' => 'saved', 'saved_keys' => $saved);
}

/**
 * Remove all overrides (revert to code defaults).
 *
 * @return array{ok:bool,message:string}
 */
function ai_prompt_templates_reset_all() {
	$exists = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res(AI_PROMPT_TEMPLATES_VAR_KEY) . "' LIMIT 1", 'row');
	if ($exists) {
		mysql_fn('delete', 'variables', array('key' => AI_PROMPT_TEMPLATES_VAR_KEY));
	}
	return array('ok' => true, 'message' => 'reset to defaults');
}
