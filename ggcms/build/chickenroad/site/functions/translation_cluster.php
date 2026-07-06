<?php
/**
 * Cluster-first translation helpers:
 * - cluster state storage
 * - source/meta normalization
 * - lightweight vector memory for translation examples
 * - locale validation helpers
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

require_once ROOT_DIR . 'functions/translation_metadata_quality.php';

function translation_cluster_setting_int($key, $default) {
	$key = trim((string)$key);
	$default = (int)$default;
	if ($key === '' || @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return $default;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') {
		return $default;
	}
	$dec = @json_decode((string)$row['value'], true);
	if (!is_array($dec) || !isset($dec[$key])) {
		return $default;
	}
	return (int)$dec[$key];
}

function translation_cluster_ensure_tables() {
	static $done = false;
	if ($done) {
		return;
	}
	if (@mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') === 0) {
		mysql_fn('query', "CREATE TABLE IF NOT EXISTS `translation_cluster_state` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`entity` varchar(64) NOT NULL,
			`entity_id` int unsigned NOT NULL,
			`source_lang_id` int unsigned NOT NULL DEFAULT 1,
			`source_mode` varchar(32) NOT NULL DEFAULT 'internal_en',
			`pipeline_stage` varchar(64) NOT NULL DEFAULT 'queued',
			`cluster_status` varchar(32) NOT NULL DEFAULT 'new',
			`ready_locales` int unsigned NOT NULL DEFAULT 0,
			`total_locales` int unsigned NOT NULL DEFAULT 0,
			`failed_locales` int unsigned NOT NULL DEFAULT 0,
			`blocker_count` int unsigned NOT NULL DEFAULT 0,
			`warning_count` int unsigned NOT NULL DEFAULT 0,
			`search_title` varchar(255) NOT NULL DEFAULT '',
			`search_slug` varchar(255) NOT NULL DEFAULT '',
			`validation_json` mediumtext DEFAULT NULL,
			`last_job_id` int unsigned NOT NULL DEFAULT 0,
			`last_error` text DEFAULT NULL,
			`seo_monitor_handoff` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`human_reviewed_at` datetime DEFAULT NULL,
			`updated_at` datetime NOT NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_entity_cluster` (`entity`,`entity_id`),
			KEY `idx_cluster_status` (`cluster_status`,`updated_at`),
			KEY `idx_stage` (`pipeline_stage`,`updated_at`),
			KEY `idx_title` (`search_title`),
			KEY `idx_slug` (`search_slug`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}
	if (@mysql_select("SHOW TABLES LIKE 'translation_vector_items'", 'num_rows') === 0) {
		mysql_fn('query', "CREATE TABLE IF NOT EXISTS `translation_vector_items` (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`entity` varchar(64) NOT NULL DEFAULT '',
			`entity_id` int unsigned NOT NULL DEFAULT 0,
			`src_lang_id` int unsigned NOT NULL DEFAULT 1,
			`dst_lang_id` int unsigned NOT NULL DEFAULT 0,
			`field_type` varchar(32) NOT NULL DEFAULT '',
			`source_hash` char(40) NOT NULL DEFAULT '',
			`source_norm` mediumtext NOT NULL,
			`source_text` mediumtext NOT NULL,
			`target_text` mediumtext NOT NULL,
			`vector_json` mediumtext DEFAULT NULL,
			`quality_status` varchar(16) NOT NULL DEFAULT 'auto',
			`usage_count` int unsigned NOT NULL DEFAULT 0,
			`last_used_at` datetime DEFAULT NULL,
			`updated_at` datetime NOT NULL,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_lookup` (`dst_lang_id`,`field_type`,`quality_status`),
			KEY `idx_entity` (`entity`,`entity_id`),
			KEY `idx_hash` (`source_hash`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	}
	if (@mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') > 0) {
		$col = @mysql_select("SHOW COLUMNS FROM translation_cluster_state LIKE 'seo_monitor_handoff'", 'num_rows');
		if ((int)$col <= 0) {
			mysql_fn('query', "ALTER TABLE `translation_cluster_state` ADD COLUMN `seo_monitor_handoff` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `last_error`");
		}
		$colHr = @mysql_select("SHOW COLUMNS FROM translation_cluster_state LIKE 'human_reviewed_at'", 'num_rows');
		if ((int)$colHr <= 0) {
			mysql_fn('query', "ALTER TABLE `translation_cluster_state` ADD COLUMN `human_reviewed_at` datetime DEFAULT NULL AFTER `seo_monitor_handoff`");
		}
	}
	$done = true;
}

function translation_cluster_source_mode($entity) {
	$entity = (string)$entity;
	if ($entity === 'blog') {
		return 'internal_en';
	}
	return 'donor_or_curated_en';
}

function translation_cluster_terminal_statuses() {
	return array('ready_to_publish', 'needs_review', 'published', 'blocked');
}

function translation_cluster_is_terminal_status($status) {
	static $set = null;
	if ($set === null) {
		$set = array_flip(translation_cluster_terminal_statuses());
	}
	$status = trim((string)$status);
	return isset($set[$status]);
}

function translation_cluster_plain_text($text) {
	$text = html_entity_decode((string)$text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = str_replace(array('＜', '＞'), array('<', '>'), $text);
	$text = strip_tags($text);
	$text = preg_replace('/\s+/u', ' ', $text);
	return trim((string)$text);
}

function translation_cluster_trim_seo_text($text, $max_chars) {
	$text = translation_cluster_plain_text($text);
	$max_chars = max(10, (int)$max_chars);
	if (mb_strlen($text, 'UTF-8') <= $max_chars) {
		return $text;
	}
	$ellipsis = '...';
	$el = mb_strlen($ellipsis, 'UTF-8');
	$budget = max(8, $max_chars - $el);
	$cut = trim((string)mb_substr($text, 0, $budget, 'UTF-8'));
	$pos = mb_strrpos($cut, ' ', 0, 'UTF-8');
	if ($pos !== false && $pos > (int)floor($budget * 0.6)) {
		$cut = trim((string)mb_substr($cut, 0, $pos, 'UTF-8'));
	}
	$out = rtrim($cut, " \t\n\r\0\x0B,;:-") . $ellipsis;
	if (mb_strlen($out, 'UTF-8') > $max_chars) {
		$out = (string)mb_substr($out, 0, $max_chars, 'UTF-8');
	}
	return $out;
}

function translation_cluster_normalize_source_meta(array $src, $entity) {
	$out = $src;
	foreach (array('name', 'title', 'description') as $field) {
		if (isset($out[$field])) {
			$out[$field] = translation_cluster_plain_text($out[$field]);
		}
	}
	if ((string)$entity === 'blog') {
		if (!empty($out['title'])) {
			$out['title'] = translation_cluster_trim_seo_text($out['title'], 70);
		}
		if (!empty($out['description'])) {
			$out['description'] = translation_cluster_trim_seo_text($out['description'], 160);
		}
	}
	return $out;
}

function translation_cluster_content_signature($html) {
	$html = (string)$html;
	$tags = array('h1', 'h2', 'h3', 'table', 'ol', 'ul', 'figure');
	$out = array();
	foreach ($tags as $tag) {
		$out[$tag] = (int)preg_match_all('#<' . $tag . '\b#iu', $html, $m);
	}
	return $out;
}

/**
 * Rename a DOM element (e.g. h1→h2) preserving children and attributes.
 *
 * @return DOMElement
 */
function translation_cluster_dom_rename_element(DOMElement $el, $newTag) {
	$newTag = strtolower(trim((string)$newTag));
	if ($newTag === '') {
		return $el;
	}
	$doc = $el->ownerDocument;
	if (!$doc) {
		return $el;
	}
	$new = $doc->createElement($newTag);
	while ($el->firstChild) {
		$new->appendChild($el->firstChild);
	}
	if ($el->hasAttributes()) {
		foreach (iterator_to_array($el->attributes) as $attr) {
			$new->setAttribute($attr->name, $attr->value);
		}
	}
	$parent = $el->parentNode;
	if ($parent) {
		$parent->replaceChild($new, $el);
	}
	return $new;
}

/**
 * Inner HTML of a DOM node (fragment).
 *
 * @return string
 */
function translation_cluster_dom_inner_html(DOMElement $root) {
	$html = '';
	$doc = $root->ownerDocument;
	if (!$doc) {
		return '';
	}
	for ($i = 0; $i < $root->childNodes->length; $i++) {
		$n = $root->childNodes->item($i);
		if ($n) {
			$html .= $doc->saveHTML($n);
		}
	}
	return $html;
}

/**
 * Align translated HTML block tag counts to the source cluster signature (h1–h3, table, ol, ul, figure).
 * Deterministic fix so repair_locale / LLM retries do not loop on structure_* alone.
 *
 * @return array{html:string,changed:bool}
 */
function translation_cluster_align_html_structure_to_source($src_html, $dst_html) {
	$src_html = (string)$src_html;
	$dst_html = (string)$dst_html;
	$src_sig = translation_cluster_content_signature($src_html);
	$dst_sig = translation_cluster_content_signature($dst_html);
	if ($dst_sig === $src_sig) {
		return array('html' => $dst_html, 'changed' => false);
	}
	if (trim($dst_html) === '' || !class_exists('DOMDocument')) {
		return array('html' => $dst_html, 'changed' => false);
	}
	$orig_sig = $dst_sig;
	$prev = libxml_use_internal_errors(true);
	$dom = new DOMDocument();
	$wrapped = '<?xml encoding="UTF-8"?><div id="seo-cluster-structure-root">' . $dst_html . '</div>';
	@$dom->loadHTML($wrapped, LIBXML_HTML_NODEFDTD | LIBXML_COMPACT);
	libxml_clear_errors();
	libxml_use_internal_errors($prev);
	$xp = new DOMXPath($dom);
	$root = $xp->query('//*[@id="seo-cluster-structure-root"]')->item(0);
	if (!$root || !($root instanceof DOMElement)) {
		return array('html' => $dst_html, 'changed' => false);
	}
	$tags = array('h1', 'h2', 'h3', 'table', 'ol', 'ul', 'figure');
	$demote_map = array('h1' => 'h2', 'h2' => 'h3', 'h3' => 'h4');
	$promote_map = array('h1' => 'h2', 'h2' => 'h3', 'h3' => 'h4');
	for ($iter = 0; $iter < 24; $iter++) {
		$inner = translation_cluster_dom_inner_html($root);
		$cur = translation_cluster_content_signature($inner);
		if ($cur === $src_sig) {
			break;
		}
		$progress = false;
		foreach ($tags as $tag) {
			$inner = translation_cluster_dom_inner_html($root);
			$cur = translation_cluster_content_signature($inner);
			$ns = (int)$src_sig[$tag];
			$nd = (int)$cur[$tag];
			if ($nd <= $ns) {
				continue;
			}
			$excess = $nd - $ns;
			if (in_array($tag, array('h1', 'h2', 'h3'), true)) {
				$nodes = $xp->query('.//' . $tag, $root);
				$nlen = $nodes ? (int)$nodes->length : 0;
				for ($e = 0; $e < $excess && $nlen > 0; $e++) {
					$nodes = $xp->query('.//' . $tag, $root);
					$nlen = $nodes ? (int)$nodes->length : 0;
					if ($nlen <= $ns) {
						break;
					}
					$idx = $nlen - 1;
					$node = $nodes->item($idx);
					if ($node instanceof DOMElement) {
						$to = isset($demote_map[$tag]) ? $demote_map[$tag] : 'div';
						translation_cluster_dom_rename_element($node, $to);
						$progress = true;
					}
				}
			} else {
				$nodes = $xp->query('.//' . $tag, $root);
				$nlen = $nodes ? (int)$nodes->length : 0;
				for ($e = 0; $e < $excess && $nlen > 0; $e++) {
					$nodes = $xp->query('.//' . $tag, $root);
					$nlen = $nodes ? (int)$nodes->length : 0;
					if ($nlen <= $ns) {
						break;
					}
					$node = $nodes->item($nlen - 1);
					if ($node && $node->parentNode) {
						$node->parentNode->removeChild($node);
						$progress = true;
					}
				}
			}
		}
		foreach ($tags as $tag) {
			if (!in_array($tag, array('h1', 'h2', 'h3'), true)) {
				continue;
			}
			$inner = translation_cluster_dom_inner_html($root);
			$cur = translation_cluster_content_signature($inner);
			$ns = (int)$src_sig[$tag];
			$nd = (int)$cur[$tag];
			if ($nd >= $ns) {
				continue;
			}
			$need = $ns - $nd;
			$lower = isset($promote_map[$tag]) ? $promote_map[$tag] : '';
			if ($lower === '') {
				continue;
			}
			for ($p = 0; $p < $need; $p++) {
				$inner = translation_cluster_dom_inner_html($root);
				$cur = translation_cluster_content_signature($inner);
				if ((int)$cur[$tag] >= $ns) {
					break;
				}
				$lower_nodes = $xp->query('.//' . $lower, $root);
				if (!$lower_nodes || $lower_nodes->length === 0) {
					break;
				}
				$ln = $lower_nodes->item(0);
				if ($ln instanceof DOMElement) {
					translation_cluster_dom_rename_element($ln, $tag);
					$progress = true;
				}
			}
		}
		if (!$progress) {
			break;
		}
	}
	$out_html = translation_cluster_dom_inner_html($root);
	$final_sig = translation_cluster_content_signature($out_html);
	$changed = ($out_html !== $dst_html) || ($final_sig !== $orig_sig);
	return array('html' => $out_html, 'changed' => $changed);
}

/**
 * Align destination HTML tag counts to the source snapshot and persist to content_i18n when different.
 *
 * @param array<int,int> $dst_langs
 * @return array{changed:bool}
 */
function translation_cluster_maybe_persist_structure_alignment($entity, $entity_id, $src_lang_id, $dst_lang_id, $dst_langs = array(), $job_id = 0) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$src_lang_id = (int)$src_lang_id;
	$dst_lang_id = (int)$dst_lang_id;
	if ($entity === '' || $entity_id <= 0 || $src_lang_id <= 0 || $dst_lang_id <= 0) {
		return array('changed' => false);
	}
	$src = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang_id);
	$row = mysql_select("
		SELECT id, content FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . $entity_id . "
		  AND lang_id=" . $dst_lang_id . "
		ORDER BY id DESC
		LIMIT 1
	", 'row');
	if (!$row || empty($row['id'])) {
		return array('changed' => false);
	}
	$aligned = translation_cluster_align_html_structure_to_source(
		isset($src['content']) ? (string)$src['content'] : '',
		isset($row['content']) ? (string)$row['content'] : ''
	);
	if (empty($aligned['changed'])) {
		return array('changed' => false);
	}
	mysql_fn('update', 'content_i18n', array(
		'content' => $aligned['html'],
		'updated_at' => date('Y-m-d H:i:s'),
	), ' AND id=' . (int)$row['id'] . ' ');
	translation_cluster_refresh_state($entity, $entity_id, $src_lang_id, is_array($dst_langs) ? $dst_langs : array(), (int)$job_id);
	return array('changed' => true);
}

function translation_cluster_validation_status($ready_locales, $total_locales, $failed_locales, $blocker_count, $warning_count) {
	$ready_locales = (int)$ready_locales;
	$total_locales = (int)$total_locales;
	$failed_locales = (int)$failed_locales;
	$blocker_count = (int)$blocker_count;
	$warning_count = (int)$warning_count;
	if ($failed_locales > 0 || $blocker_count > 0) {
		return 'blocked';
	}
	if ($total_locales > 0 && $ready_locales >= $total_locales) {
		return $warning_count > 0 ? 'needs_review' : 'ready_to_publish';
	}
	if ($ready_locales > 0) {
		return 'translating';
	}
	return 'new';
}

function translation_cluster_upsert_state($entity, $entity_id, array $data) {
	translation_cluster_ensure_tables();
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return false;
	}
	$now = date('Y-m-d H:i:s');
	$row = mysql_select("SELECT id FROM translation_cluster_state WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " LIMIT 1", 'row');
	$prev = null;
	if ($row) {
		$prev = mysql_select("SELECT * FROM translation_cluster_state WHERE id=" . (int)$row['id'] . " LIMIT 1", 'row');
		if (is_array($prev)) {
			foreach (array(
				'source_lang_id', 'source_mode', 'pipeline_stage', 'cluster_status', 'ready_locales', 'total_locales',
				'failed_locales', 'blocker_count', 'warning_count', 'search_title', 'search_slug', 'validation_json',
				'last_job_id', 'last_error', 'seo_monitor_handoff', 'human_reviewed_at',
			) as $pk) {
				if (!array_key_exists($pk, $data) && array_key_exists($pk, $prev)) {
					$data[$pk] = $prev[$pk];
				}
			}
		}
	}
	$upd = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'source_lang_id' => isset($data['source_lang_id']) ? (int)$data['source_lang_id'] : 1,
		'source_mode' => isset($data['source_mode']) ? substr((string)$data['source_mode'], 0, 32) : translation_cluster_source_mode($entity),
		'pipeline_stage' => isset($data['pipeline_stage']) ? substr((string)$data['pipeline_stage'], 0, 64) : 'queued',
		'cluster_status' => isset($data['cluster_status']) ? substr((string)$data['cluster_status'], 0, 32) : 'new',
		'ready_locales' => isset($data['ready_locales']) ? (int)$data['ready_locales'] : 0,
		'total_locales' => isset($data['total_locales']) ? (int)$data['total_locales'] : 0,
		'failed_locales' => isset($data['failed_locales']) ? (int)$data['failed_locales'] : 0,
		'blocker_count' => isset($data['blocker_count']) ? (int)$data['blocker_count'] : 0,
		'warning_count' => isset($data['warning_count']) ? (int)$data['warning_count'] : 0,
		'search_title' => isset($data['search_title']) ? mb_substr((string)$data['search_title'], 0, 255, 'UTF-8') : '',
		'search_slug' => isset($data['search_slug']) ? mb_substr((string)$data['search_slug'], 0, 255, 'UTF-8') : '',
		'validation_json' => isset($data['validation_json']) ? (string)$data['validation_json'] : null,
		'last_job_id' => isset($data['last_job_id']) ? (int)$data['last_job_id'] : 0,
		'last_error' => isset($data['last_error']) ? (string)$data['last_error'] : null,
		'seo_monitor_handoff' => isset($data['seo_monitor_handoff']) ? ((int)$data['seo_monitor_handoff'] ? 1 : 0) : 0,
		'human_reviewed_at' => null,
		'updated_at' => $now,
	);
	if (array_key_exists('human_reviewed_at', $data)) {
		if ($data['human_reviewed_at'] === null) {
			$upd['human_reviewed_at'] = null;
		} else {
			$h = trim((string)$data['human_reviewed_at']);
			$upd['human_reviewed_at'] = ($h !== '' && $h !== '0000-00-00 00:00:00') ? substr($h, 0, 19) : null;
		}
	} elseif (is_array($prev) && array_key_exists('human_reviewed_at', $prev)) {
		$h = isset($prev['human_reviewed_at']) ? trim((string)$prev['human_reviewed_at']) : '';
		$upd['human_reviewed_at'] = ($h !== '' && $h !== '0000-00-00 00:00:00') ? substr($h, 0, 19) : null;
	}
	if ($row) {
		unset($upd['entity'], $upd['entity_id']);
		$clear_hr = array_key_exists('human_reviewed_at', $data) && $data['human_reviewed_at'] === null;
		if ($clear_hr) {
			unset($upd['human_reviewed_at']);
		} elseif (isset($upd['human_reviewed_at']) && $upd['human_reviewed_at'] === null) {
			unset($upd['human_reviewed_at']);
		}
		$ret = mysql_fn('update', 'translation_cluster_state', $upd, " AND id=" . (int)$row['id'] . " ");
		if ($clear_hr) {
			mysql_fn('query', "UPDATE `translation_cluster_state` SET `human_reviewed_at`=NULL WHERE id=" . (int)$row['id']);
		}
		return $ret;
	}
	$upd['created_at'] = $now;
	if (isset($upd['human_reviewed_at']) && $upd['human_reviewed_at'] === null) {
		unset($upd['human_reviewed_at']);
	}
	return mysql_fn('insert', 'translation_cluster_state', $upd);
}

function translation_cluster_get_state($entity, $entity_id) {
	translation_cluster_ensure_tables();
	return mysql_select("SELECT * FROM translation_cluster_state WHERE entity='" . mysql_res((string)$entity) . "' AND entity_id=" . (int)$entity_id . " LIMIT 1", 'row');
}

/**
 * When true, cluster validation uses full SEO Monitor rules on HTML body (H1, images, body empty), not only meta.
 * Stored in variables.translation_settings JSON: cluster_validation_seo_full.
 */
function translation_cluster_validation_seo_full() {
	static $v = null;
	if ($v !== null) {
		return $v;
	}
	$v = false;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
		return $v;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') {
		return $v;
	}
	$dec = json_decode((string)$row['value'], true);
	$v = is_array($dec) && !empty($dec['cluster_validation_seo_full']);
	return $v;
}

/**
 * Set every content_i18n row for this entity to published; mark cluster published (same as admin Translations review cluster_publish).
 *
 * @return bool true on successful update
 */
function translation_cluster_publish_all_content_i18n($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return false;
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
		return false;
	}
	$cnt = mysql_select("SELECT COUNT(*) AS c FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . (int)$entity_id, 'row');
	if (!$cnt || (int)($cnt['c'] ?? 0) < 1) {
		return false;
	}
	$now = date('Y-m-d H:i:s');
	// mysql_fn('query', $sql) returns false on success; use affected_rows or rely on die-on-error.
	mysql_fn('query', "UPDATE content_i18n SET status='published', updated_at='" . mysql_res($now) . "'
		WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id, 'affected_rows');
	translation_cluster_upsert_state($entity, $entity_id, array(
		'pipeline_stage' => 'published',
		'cluster_status' => 'published',
		'human_reviewed_at' => null,
	));
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'translation_cluster_publish_all_content_i18n', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
		));
	}
	return true;
}

/**
 * After validate_cluster completes with no remaining work: if autopilot job and setting enabled,
 * publish all content_i18n rows when cluster is ready_to_publish, or needs_review with no blockers
 * (warnings-only / residual SEO noise so public URLs are not stuck in draft).
 *
 * @return bool true if publish ran
 */
function translation_cluster_maybe_autopublish_after_validate($entity, $entity_id, array $payload) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return false;
	}
	if (empty($payload['autopilot'])) {
		return false;
	}
	if (!function_exists('translation_autopilot_load_cfg')) {
		require_once ROOT_DIR . 'functions/translation_autopilot.php';
	}
	$cfg = translation_autopilot_load_cfg();
	if (empty($cfg['autopilot_cluster_autopublish'])) {
		return false;
	}
	$st = translation_cluster_get_state($entity, $entity_id);
	if (!$st) {
		return false;
	}
	$status = isset($st['cluster_status']) ? (string)$st['cluster_status'] : '';
	$blockers = isset($st['blocker_count']) ? (int)$st['blocker_count'] : 0;
	if ($status === 'ready_to_publish') {
		return translation_cluster_publish_all_content_i18n($entity, $entity_id);
	}
	if ($status === 'needs_review' && $blockers === 0) {
		return translation_cluster_publish_all_content_i18n($entity, $entity_id);
	}
	return false;
}

/**
 * Warnings that should not enqueue endless repair_locale loops (heuristic noise).
 *
 * @param array<int,string> $warnings
 * @return array<int,string>
 */
function translation_cluster_warnings_for_repair_queue(array $warnings) {
	$skip = array('source_sentence_copy' => true);
	$out = array();
	foreach ($warnings as $w) {
		$w = (string)$w;
		if ($w === '' || isset($skip[$w])) {
			continue;
		}
		$out[] = $w;
	}
	return array_values(array_unique($out));
}

/**
 * Ingest EN→locale field pairs from content_i18n into translation_vector_items for RAG (NVIDIA / autopilot examples).
 * Call after human approves a cluster so approved pairs rank above auto-translated noise.
 *
 * @return int number of rows upserted
 */
function translation_vector_cluster_ingest_from_content_i18n($entity, $entity_id, $quality_status = 'approved') {
	if (!translation_vector_is_enabled()) {
		return 0;
	}
	translation_cluster_ensure_tables();
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$quality_status = in_array((string)$quality_status, array('approved', 'auto'), true) ? (string)$quality_status : 'approved';
	if ($entity === '' || $entity_id <= 0) {
		return 0;
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
		return 0;
	}
	$src_row = mysql_select("SELECT * FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " AND lang_id=1 LIMIT 1", 'row');
	if (!$src_row) {
		return 0;
	}
	$dst_rows = mysql_select("SELECT * FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id . " AND lang_id<>1", 'rows') ?: array();
	$n = 0;
	foreach ($dst_rows as $dr) {
		$dst_lang = isset($dr['lang_id']) ? (int)$dr['lang_id'] : 0;
		if ($dst_lang <= 0) {
			continue;
		}
		foreach (array('name', 'title', 'description', 'content') as $f) {
			$src_t = isset($src_row[$f]) ? trim((string)$src_row[$f]) : '';
			$dst_t = isset($dr[$f]) ? trim((string)$dr[$f]) : '';
			if ($src_t === '' || $dst_t === '') {
				continue;
			}
			if (translation_vector_store_item(array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'src_lang_id' => 1,
				'dst_lang_id' => $dst_lang,
				'field_type' => $f,
				'source_text' => $src_t,
				'target_text' => $dst_t,
				'quality_status' => $quality_status,
			))) {
				$n++;
			}
		}
	}
	return $n;
}

/**
 * Human "Mark review" on Translations review: no blockers, all locales present — mark cluster ready and feed vector DB.
 *
 * @return array ok (bool), message (string), vector_rows (int, optional)
 */
function translation_cluster_mark_human_reviewed($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return array('ok' => false, 'message' => 'invalid cluster');
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
		return array('ok' => false, 'message' => 'content_i18n missing');
	}
	$st = translation_cluster_get_state($entity, $entity_id);
	$src_lang_id = $st ? (int)($st['source_lang_id'] ?? 1) : 1;
	if ($src_lang_id <= 0) {
		$src_lang_id = 1;
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang_id, array());
	if ($dst_langs === array()) {
		return array('ok' => false, 'message' => 'no target languages configured');
	}
	translation_cluster_refresh_state($entity, $entity_id, $src_lang_id, $dst_langs, 0);
	$st = translation_cluster_get_state($entity, $entity_id);
	$blockers = $st ? (int)($st['blocker_count'] ?? 0) : 0;
	if ($blockers > 0) {
		return array('ok' => false, 'message' => 'cluster has blockers');
	}
	$scope = translation_cluster_scope_language_ids($src_lang_id);
	$present_rows = mysql_select("SELECT DISTINCT lang_id FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id, 'rows') ?: array();
	$have = array();
	foreach ($present_rows as $pr) {
		$have[(int)$pr['lang_id']] = true;
	}
	foreach ($scope as $lid) {
		if (!isset($have[(int)$lid])) {
			return array('ok' => false, 'message' => 'not all locales present');
		}
	}
	$now = date('Y-m-d H:i:s');
	// mysql_fn('query', $sql) returns false even on success; use affected_rows (third arg).
	$affected = mysql_fn('query', "UPDATE content_i18n SET status=IF(status='published','published','review'), updated_at='" . mysql_res($now) . "'
		WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id, 'affected_rows');
	$affected = is_numeric($affected) ? (int)$affected : 0;
	if ($affected < 1) {
		return array('ok' => false, 'message' => 'content_i18n update failed (no rows matched)');
	}
	$maxu = mysql_select("SELECT MAX(updated_at) AS m FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . (int)$entity_id, 'row');
	$hr_stamp = ($maxu && isset($maxu['m']) && trim((string)$maxu['m']) !== '') ? substr((string)$maxu['m'], 0, 19) : $now;
	translation_cluster_upsert_state($entity, $entity_id, array(
		'pipeline_stage' => 'publish_ready',
		'cluster_status' => 'ready_to_publish',
		'human_reviewed_at' => $hr_stamp,
	));
	$vec_n = (int)translation_vector_cluster_ingest_from_content_i18n($entity, $entity_id, 'approved');
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'translation_cluster_mark_human_reviewed', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'vector_rows' => $vec_n,
		));
	}
	return array('ok' => true, 'message' => 'ok', 'vector_rows' => $vec_n);
}

/**
 * Whether cluster is frozen for autopilot: SEO Monitor handoff, or manual total approve with no content_i18n edits since human_reviewed_at.
 *
 * @param string $entity
 * @param string $main_table_alias main row alias (e.g. t in missing-entity queries)
 */
function translation_cluster_autopilot_freeze_exists_sql($entity, $main_table_alias = 't') {
	$entity = trim((string)$entity);
	$main_table_alias = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', (string)$main_table_alias) ? (string)$main_table_alias : 't';
	if ($entity === '' || @mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') <= 0) {
		return '';
	}
	$e = mysql_res($entity);
	return " AND NOT EXISTS (
		SELECT 1 FROM translation_cluster_state cs
		WHERE cs.entity='" . $e . "' AND cs.entity_id=" . $main_table_alias . ".id
		  AND (
			COALESCE(cs.seo_monitor_handoff,0)=1
			OR (
				cs.human_reviewed_at IS NOT NULL
				AND TRIM(cs.human_reviewed_at) <> ''
				AND cs.human_reviewed_at <> '0000-00-00 00:00:00'
				AND (
					SELECT COALESCE(MAX(ci.updated_at), '1970-01-01 00:00:00')
					FROM content_i18n ci
					WHERE ci.entity = cs.entity AND ci.entity_id = cs.entity_id
				) <= cs.human_reviewed_at
			)
		  )
	)";
}

/**
 * Same freeze rule for queries joined on content_i18n AS ci (meta-fix scan).
 */
function translation_cluster_autopilot_freeze_exists_sql_for_ci() {
	if (@mysql_select("SHOW TABLES LIKE 'translation_cluster_state'", 'num_rows') <= 0) {
		return '';
	}
	return " AND NOT EXISTS (
		SELECT 1 FROM translation_cluster_state cs
		WHERE cs.entity=ci.entity AND cs.entity_id=ci.entity_id
		  AND (
			COALESCE(cs.seo_monitor_handoff,0)=1
			OR (
				cs.human_reviewed_at IS NOT NULL
				AND TRIM(cs.human_reviewed_at) <> ''
				AND cs.human_reviewed_at <> '0000-00-00 00:00:00'
				AND (
					SELECT COALESCE(MAX(ci2.updated_at), '1970-01-01 00:00:00')
					FROM content_i18n ci2
					WHERE ci2.entity = cs.entity AND ci2.entity_id = cs.entity_id
				) <= cs.human_reviewed_at
			)
		  )
	)";
}

/**
 * Force cluster to ready_to_publish, record manual review time, cancel pending pipeline jobs.
 * Autopilot skips this material while max(content_i18n.updated_at) <= human_reviewed_at (same as SEO handoff semantics for enqueue).
 *
 * @return array{ok:bool,message:string,jobs_cancelled?:int}
 */
function translation_cluster_manual_total_approve($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if ($entity === '' || $entity_id <= 0) {
		return array('ok' => false, 'message' => 'invalid cluster');
	}
	translation_cluster_ensure_tables();
	$st = translation_cluster_get_state($entity, $entity_id);
	$src_lang_id = $st ? (int)($st['source_lang_id'] ?? 1) : 1;
	if ($src_lang_id <= 0) {
		$src_lang_id = 1;
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang_id, array());
	if ($dst_langs === array()) {
		return array('ok' => false, 'message' => 'no target languages configured');
	}
	$search_title = is_array($st) ? trim((string)($st['search_title'] ?? '')) : '';
	$search_slug = is_array($st) ? trim((string)($st['search_slug'] ?? '')) : '';
	if ($search_title === '' || $search_slug === '') {
		$snap = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang_id);
		if ($search_title === '') {
			$search_title = mb_substr(trim((string)($snap['title'] ?? $snap['name'] ?? '')), 0, 255, 'UTF-8');
		}
		if ($search_slug === '') {
			$search_slug = mb_substr(trim((string)($snap['slug'] ?? $snap['url'] ?? '')), 0, 255, 'UTF-8');
		}
	}
	$now = date('Y-m-d H:i:s');
	$maxu = mysql_select("SELECT MAX(updated_at) AS m FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . (int)$entity_id, 'row');
	$hr_stamp = $now;
	if ($maxu && isset($maxu['m']) && trim((string)$maxu['m']) !== '' && (string)$maxu['m'] !== '0000-00-00 00:00:00') {
		$mstr = substr((string)$maxu['m'], 0, 19);
		if ($mstr !== '' && strcmp($mstr, $hr_stamp) > 0) {
			$hr_stamp = $mstr;
		}
	}
	$report = array(
		'manual_total_approve' => true,
		'at' => $hr_stamp,
		'locales' => array(),
	);
	foreach ($dst_langs as $lid) {
		$report['locales'][] = array('lang_id' => (int)$lid, 'ok' => true, 'missing' => false, 'blockers' => array(), 'warnings' => array());
	}
	translation_cluster_upsert_state($entity, $entity_id, array(
		'source_lang_id' => $src_lang_id,
		'source_mode' => translation_cluster_source_mode($entity),
		'search_title' => $search_title,
		'search_slug' => $search_slug,
		'pipeline_stage' => 'publish_ready',
		'cluster_status' => 'ready_to_publish',
		'ready_locales' => count($dst_langs),
		'total_locales' => count($dst_langs),
		'failed_locales' => 0,
		'blocker_count' => 0,
		'warning_count' => 0,
		'validation_json' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'last_error' => '',
		'human_reviewed_at' => $hr_stamp,
	));
	$jobs_n = 0;
	if (function_exists('admin_jobs_fail_pending_translations_for_cluster')) {
		require_once ROOT_DIR . 'functions/admin_jobs.php';
		$jobs_n = admin_jobs_fail_pending_translations_for_cluster($entity, $entity_id, 'Cancelled: manual total approve');
	}
	$vec_n = (int)translation_vector_cluster_ingest_from_content_i18n($entity, $entity_id, 'approved');
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'translation_cluster_manual_total_approve', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'jobs_cancelled' => $jobs_n,
			'vector_rows' => $vec_n,
		));
	}
	return array('ok' => true, 'message' => 'ok', 'jobs_cancelled' => $jobs_n, 'vector_rows' => $vec_n);
}

/**
 * Language ids that belong to the translation cluster (same rules as SEO Monitor import):
 * `languages.display=1`, optionally restricted by `variables.translation_settings.enabled_lang_ids` JSON,
 * always including `source_lang_id` if missing from the filtered list.
 *
 * @return array<int,int>
 */
function translation_cluster_scope_language_ids($source_lang_id = 1) {
	$source_lang_id = (int)$source_lang_id;
	$cfg = array('source_lang_id' => 1, 'enabled_lang_ids' => array());
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && trim((string)$row['value']) !== '') {
			$dec = @json_decode((string)$row['value'], true);
			if (is_array($dec)) {
				$cfg = array_merge($cfg, $dec);
			}
		}
	}
	$langs = mysql_select("SELECT id FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
	$enabled_set = array();
	foreach ((array)@$cfg['enabled_lang_ids'] as $lid) {
		$lid = (int)$lid;
		if ($lid > 0) {
			$enabled_set[$lid] = true;
		}
	}
	$pick = array();
	if ($enabled_set === array()) {
		foreach ($langs as $l) {
			$pick[] = (int)$l['id'];
		}
	} else {
		foreach ($langs as $l) {
			$id = (int)$l['id'];
			if (isset($enabled_set[$id])) {
				$pick[] = $id;
			}
		}
		// Whitelisted lang IDs must still count even if languages.display=0 (hidden / SEO-only locales).
		foreach (array_keys($enabled_set) as $wid) {
			$wid = (int)$wid;
			if ($wid <= 0 || in_array($wid, $pick, true)) {
				continue;
			}
			$row = mysql_select("SELECT id FROM languages WHERE id=" . $wid . " LIMIT 1", 'row');
			if ($row) {
				$pick[] = $wid;
			}
		}
	}
	if (!in_array($source_lang_id, $pick, true)) {
		$row = mysql_select("SELECT id FROM languages WHERE id=" . (int)$source_lang_id . " LIMIT 1", 'row');
		if ($row) {
			$pick[] = $source_lang_id;
		}
	}
	return array_values(array_unique($pick));
}

/**
 * True if content_i18n has at least one row per language in the cluster scope (same as SEO Monitor import).
 */
function translation_cluster_has_full_scope_locales_in_ci($entity, $entity_id, $src_lang_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$src_lang_id = (int)$src_lang_id;
	if ($entity === '' || $entity_id <= 0) {
		return false;
	}
	$scope = translation_cluster_scope_language_ids($src_lang_id);
	if ($scope === array()) {
		return false;
	}
	$present_rows = mysql_select("SELECT DISTINCT lang_id FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . $entity_id, 'rows') ?: array();
	$have = array();
	foreach ($present_rows as $pr) {
		$have[(int)$pr['lang_id']] = true;
	}
	foreach ($scope as $lid) {
		if (!isset($have[(int)$lid])) {
			return false;
		}
	}
	return true;
}

function translation_cluster_normalize_target_lang_ids($src_lang_id, $dst_langs = array()) {
	$src_lang_id = (int)$src_lang_id;
	$norm = array();
	if (is_array($dst_langs)) {
		foreach ($dst_langs as $lid) {
			$lid = (int)$lid;
			if ($lid > 0 && $lid !== $src_lang_id) {
				$norm[$lid] = true;
			}
		}
	}
	if ($norm === array()) {
		foreach (translation_cluster_scope_language_ids($src_lang_id) as $lid) {
			$lid = (int)$lid;
			if ($lid > 0 && $lid !== $src_lang_id) {
				$norm[$lid] = true;
			}
		}
	}
	return array_keys($norm);
}

function translation_cluster_pending_locale_jobs($entity, $entity_id) {
	$all = translation_cluster_pending_translation_jobs($entity, $entity_id);
	$out = array();
	foreach ($all as $key => $meta) {
		if (strpos((string)$key, 'translate:') === 0) {
			$dst = isset($meta['dst_lang']) ? (int)$meta['dst_lang'] : 0;
			if ($dst > 0) {
				$out[$dst] = true;
			}
		}
	}
	return $out;
}

function translation_cluster_pending_translation_jobs($entity, $entity_id) {
	translation_cluster_ensure_tables();
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$out = array();
	if ($entity === '' || $entity_id <= 0 || @mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return $out;
	}
	$rows = mysql_select("
		SELECT payload, action
		FROM admin_jobs
		WHERE module='translations'
		  AND action IN ('translate','translate_cluster','validate_locale','repair_locale','validate_cluster','cluster_pipeline')
		  AND status IN ('pending','running')
		LIMIT 1000
	", 'rows') ?: array();
	foreach ($rows as $r) {
		$p = isset($r['payload']) ? @json_decode((string)$r['payload'], true) : null;
		if (!is_array($p)) {
			continue;
		}
		$ent = isset($p['entity']) ? (string)$p['entity'] : '';
		$eid = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
		$dst = isset($p['dst_lang']) ? (int)$p['dst_lang'] : 0;
		$action = isset($r['action']) ? (string)$r['action'] : '';
		if ($ent === $entity && $eid === $entity_id) {
			$key = $action . ':' . (int)$dst;
			$out[$key] = array(
				'action' => $action,
				'dst_lang' => $dst,
			);
		}
	}
	return $out;
}

function translation_cluster_has_pending_job($entity, $entity_id, $action, $dst_lang = 0) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$action = trim((string)$action);
	$dst_lang = (int)$dst_lang;
	if ($entity === '' || $entity_id <= 0 || $action === '') {
		return false;
	}
	$key = $action . ':' . $dst_lang;
	$map = translation_cluster_pending_translation_jobs($entity, $entity_id);
	return isset($map[$key]);
}

function translation_cluster_pending_jobs_count($actions = array(), $statuses = array('pending', 'running')) {
	translation_cluster_ensure_tables();
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
		return 0;
	}
	$actions = is_array($actions) ? array_values(array_unique(array_filter(array_map('strval', $actions)))) : array();
	$statuses = is_array($statuses) ? array_values(array_unique(array_filter(array_map('strval', $statuses)))) : array('pending', 'running');
	if ($statuses === array()) {
		$statuses = array('pending', 'running');
	}
	$where = "module='translations'";
	if ($actions !== array()) {
		$esc = array();
		foreach ($actions as $a) {
			$esc[] = "'" . mysql_res($a) . "'";
		}
		$where .= " AND action IN (" . implode(',', $esc) . ")";
	}
	if ($statuses !== array()) {
		$esc = array();
		foreach ($statuses as $s) {
			$esc[] = "'" . mysql_res($s) . "'";
		}
		$where .= " AND status IN (" . implode(',', $esc) . ")";
	}
	$row = mysql_select("SELECT COUNT(*) AS c FROM admin_jobs WHERE " . $where, 'row');
	return $row ? (int)$row['c'] : 0;
}

function translation_cluster_translate_queue_budget() {
	$cap = translation_cluster_setting_int('autopilot_translate_pending_cap', 24);
	if ($cap <= 0) {
		$cap = 24;
	}
	$current = translation_cluster_pending_jobs_count(array('translate', 'repair_locale'), array('pending', 'running'));
	return max(0, $cap - $current);
}

function translation_cluster_child_enqueue_cap() {
	$cap = translation_cluster_setting_int('autopilot_cluster_child_batch', 6);
	if ($cap <= 0) {
		$cap = 6;
	}
	return max(1, min(50, $cap));
}

function translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang_id) {
	$entity = (string)$entity;
	$entity_id = (int)$entity_id;
	$src_lang_id = (int)$src_lang_id;
	$row = mysql_select("
		SELECT url, name, title, description, content
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . $entity_id . "
		  AND lang_id=" . $src_lang_id . "
		ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
		LIMIT 1
	", 'row');
	if ($row) {
		return $row;
	}
	$out = translation_resolve_source_meta_for_entity($entity, $entity_id, $src_lang_id, null);
	$suffix = ($src_lang_id > 1) ? (string)$src_lang_id : '';
	$legacy = null;
	if ($entity === 'pages') {
		$legacy = mysql_select("SELECT * FROM pages WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url' . $suffix]) ? (string)$legacy['url' . $suffix] : (isset($legacy['url']) ? (string)$legacy['url'] : '');
		$out['content'] = isset($legacy['text' . $suffix]) ? (string)$legacy['text' . $suffix] : (isset($legacy['text']) ? (string)$legacy['text'] : '');
	}
	if ($entity === 'guides') {
		$legacy = mysql_select("SELECT * FROM guides WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url']) ? (string)$legacy['url'] : '';
		$out['content'] = isset($legacy['text']) ? (string)$legacy['text'] : '';
	}
	if ($entity === 'games') {
		$legacy = mysql_select("SELECT * FROM games WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url' . $suffix]) ? (string)$legacy['url' . $suffix] : (isset($legacy['url']) ? (string)$legacy['url'] : '');
		$out['content'] = isset($legacy['text' . $suffix]) ? (string)$legacy['text' . $suffix] : (isset($legacy['text']) ? (string)$legacy['text'] : '');
	}
	if ($entity === 'casino_articles') {
		$legacy = mysql_select("SELECT * FROM casino_articles WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url' . $suffix]) ? (string)$legacy['url' . $suffix] : (isset($legacy['url']) ? (string)$legacy['url'] : '');
		$out['content'] = isset($legacy['text' . $suffix]) ? (string)$legacy['text' . $suffix] : (isset($legacy['text']) ? (string)$legacy['text'] : '');
	}
	if ($entity === 'blog') {
		$legacy = mysql_select("SELECT * FROM blog WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url' . $suffix]) ? (string)$legacy['url' . $suffix] : (isset($legacy['url']) ? (string)$legacy['url'] : '');
		$out['content'] = isset($legacy['text']) ? (string)$legacy['text'] : (isset($legacy['content']) ? (string)$legacy['content'] : '');
	}
	if ($entity === 'authors') {
		$legacy = mysql_select("SELECT * FROM site_authors WHERE id=" . $entity_id . " LIMIT 1", 'row');
		$out['url'] = isset($legacy['url']) ? (string)$legacy['url'] : '';
		$out['name'] = isset($legacy['name']) ? (string)$legacy['name'] : '';
		$out['title'] = isset($legacy['job_title']) ? (string)$legacy['job_title'] : '';
		$out['description'] = isset($legacy['bio_short']) ? (string)$legacy['bio_short'] : '';
		$out['content'] = isset($legacy['bio']) ? (string)$legacy['bio'] : '';
	}
	return $out;
}

/**
 * @return string[]
 */
function translation_cluster_translate_fields($entity) {
	if (function_exists('admin_i18n_cluster_translate_fields')) {
		return admin_i18n_cluster_translate_fields($entity);
	}
	$entity = trim((string)$entity);
	if ($entity === 'authors') {
		return array('name', 'title', 'description', 'content', 'url');
	}
	return array('name', 'title', 'description', 'content');
}

function translation_cluster_validate_locale(array $src, array $dst, $dst_lang_url, $entity = '') {
	$entity = trim((string)$entity);
	$dst_lang_url = trim((string)$dst_lang_url, '/');
	$blockers = array();
	$warnings = array();
	if ($entity === 'authors' || (function_exists('seo_monitor_entity_profile_only') && seo_monitor_entity_profile_only($entity))) {
		foreach (array('name', 'title', 'content') as $f) {
			if (trim((string)($dst[$f] ?? '')) === '') {
				$blockers[] = 'missing_' . $f;
			}
		}
		$bad_meta = translation_metadata_fields_needing_fix(
			isset($dst['name']) ? (string)$dst['name'] : '',
			isset($dst['title']) ? (string)$dst['title'] : '',
			'',
			$dst_lang_url,
			4
		);
		foreach (array_keys($bad_meta) as $f) {
			$blockers[] = 'meta_' . $f;
		}
		$content_signals = translation_cluster_detect_untranslated_tail_signals(
			isset($src['content']) ? (string)$src['content'] : '',
			isset($dst['content']) ? (string)$dst['content'] : '',
			$dst_lang_url
		);
		foreach ($content_signals['blockers'] as $b) {
			$blockers[] = $b;
		}
		foreach ($content_signals['warnings'] as $w) {
			$warnings[] = $w;
		}
		return array(
			'blockers' => array_values(array_unique($blockers)),
			'warnings' => array_values(array_unique($warnings)),
			'content_signals' => $content_signals,
		);
	}
	$bad_meta = translation_metadata_fields_needing_fix(
		isset($dst['name']) ? (string)$dst['name'] : '',
		isset($dst['title']) ? (string)$dst['title'] : '',
		isset($dst['description']) ? (string)$dst['description'] : '',
		$dst_lang_url,
		4
	);
	foreach (array_keys($bad_meta) as $f) {
		$blockers[] = 'meta_' . $f;
	}
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	$seo_full = translation_cluster_validation_seo_full();
	$seo_loc = array(
		'title' => isset($dst['title']) ? $dst['title'] : '',
		'name' => isset($dst['name']) ? $dst['name'] : '',
		'description' => isset($dst['description']) ? $dst['description'] : '',
		'content' => '',
	);
	if ($seo_full) {
		$seo_loc['content'] = isset($dst['content']) ? (string)$dst['content'] : '';
		$seo_loc['source'] = 'content_i18n';
	}
	foreach (seo_monitor_analyze_locale($seo_loc) as $iss) {
		$c = isset($iss['code']) ? (string)$iss['code'] : '';
		if ($c === 'title_too_long' || $c === 'description_too_long') {
			$warnings[] = $c;
		}
		if ($seo_full && in_array($c, array('body_empty', 'h1_not_single', 'img_missing_alt'), true)) {
			$warnings[] = $c;
		}
	}
	$src_sig = translation_cluster_content_signature(isset($src['content']) ? $src['content'] : '');
	$dst_sig = translation_cluster_content_signature(isset($dst['content']) ? $dst['content'] : '');
	foreach ($src_sig as $tag => $count) {
		if ((int)$count !== (int)($dst_sig[$tag] ?? 0)) {
			$blockers[] = 'structure_' . $tag;
		}
	}
	if (!empty($dst['content']) && preg_match('#promocode-container|copy-btn|input[^>]+promocode#iu', (string)$dst['content'])) {
		$blockers[] = 'forbidden_promo_ui';
	}
	$content_signals = translation_cluster_detect_untranslated_tail_signals(
		isset($src['content']) ? (string)$src['content'] : '',
		isset($dst['content']) ? (string)$dst['content'] : '',
		$dst_lang_url
	);
	foreach ($content_signals['blockers'] as $b) {
		$blockers[] = $b;
	}
	foreach ($content_signals['warnings'] as $w) {
		$warnings[] = $w;
	}
	return array(
		'blockers' => array_values(array_unique($blockers)),
		'warnings' => array_values(array_unique($warnings)),
		'source_signature' => $src_sig,
		'target_signature' => $dst_sig,
		'content_signals' => $content_signals,
	);
}

function translation_cluster_extract_source_sentence_matches($src_html, $dst_html, $min_words = 6, $max_hits = 2) {
	$src_plain = translation_html_probe_plain((string)$src_html);
	$dst_plain = translation_html_probe_plain((string)$dst_html);
	$out = array();
	if ($src_plain === '' || $dst_plain === '') {
		return $out;
	}
	$parts = preg_split('/(?<=[\.\!\?\:])\s+/u', $src_plain);
	if (!is_array($parts)) {
		return $out;
	}
	foreach ($parts as $part) {
		$part = trim((string)$part);
		if ($part === '') {
			continue;
		}
		$word_count = preg_match_all('/\b[\p{L}\p{N}\'-]+\b/u', $part, $m);
		if ((int)$word_count < (int)$min_words || mb_strlen($part, 'UTF-8') < 40) {
			continue;
		}
		if (mb_stripos($dst_plain, $part, 0, 'UTF-8') !== false) {
			$out[] = mb_substr($part, 0, 200, 'UTF-8');
			if (count($out) >= $max_hits) {
				break;
			}
		}
	}
	return $out;
}

function translation_cluster_detect_untranslated_tail_signals($src_html, $dst_html, $dst_lang_url) {
	$src_plain = translation_html_probe_plain((string)$src_html);
	$dst_plain = translation_html_probe_plain((string)$dst_html);
	$blockers = array();
	$warnings = array();
	$snippets = array();
	if ($dst_plain === '') {
		$blockers[] = 'content_empty';
		return array('blockers' => $blockers, 'warnings' => $warnings, 'snippets' => $snippets);
	}
	$src_norm = translation_vector_normalize_text($src_plain);
	$dst_norm = translation_vector_normalize_text($dst_plain);
	if ($src_norm !== '' && $src_norm === $dst_norm && mb_strlen($src_norm, 'UTF-8') > 80) {
		$blockers[] = 'content_source_copy';
	}
	$snippet = '';
	if (translation_latin_leak_applies_to_target($dst_lang_url) && translation_plain_has_leaking_latin_run_after_whitelist($dst_plain, 4, $snippet)) {
		$blockers[] = 'english_tail';
		if ($snippet !== '') {
			$snippets[] = $snippet;
		}
	}
	$matches = translation_cluster_extract_source_sentence_matches($src_html, $dst_html, 6, 2);
	if ($matches !== array()) {
		$warnings[] = 'source_sentence_copy';
		foreach ($matches as $m) {
			$snippets[] = $m;
		}
	}
	return array(
		'blockers' => array_values(array_unique($blockers)),
		'warnings' => array_values(array_unique($warnings)),
		'snippets' => array_values(array_unique($snippets)),
	);
}

function translation_cluster_refresh_state($entity, $entity_id, $src_lang_id, $dst_langs = array(), $job_id = 0) {
	translation_cluster_ensure_tables();
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$src_lang_id = (int)$src_lang_id;
	if ($entity === '' || $entity_id <= 0 || $src_lang_id <= 0) {
		return false;
	}
	$dst_langs = translation_cluster_normalize_target_lang_ids($src_lang_id, $dst_langs);
	if ($dst_langs === array()) {
		return false;
	}
	$source = translation_cluster_get_source_snapshot($entity, $entity_id, $src_lang_id);
	$source = translation_cluster_normalize_source_meta(is_array($source) ? $source : array(), $entity);
	$search_title = isset($source['title']) && trim((string)$source['title']) !== '' ? (string)$source['title'] : (isset($source['name']) ? (string)$source['name'] : '');
	$search_slug = isset($source['url']) ? (string)$source['url'] : '';
	$ready = 0;
	$failed = 0;
	$blockers = 0;
	$warnings = 0;
	$present = 0;
	$report = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'source_lang_id' => $src_lang_id,
		'source_mode' => translation_cluster_source_mode($entity),
		'locales' => array(),
	);
	foreach ($dst_langs as $dst_lang) {
		$lang_row = mysql_select("SELECT url FROM languages WHERE id=" . (int)$dst_lang . " LIMIT 1", 'row');
		$dst_row = mysql_select("
			SELECT name, title, description, content, status
			FROM content_i18n
			WHERE entity='" . mysql_res($entity) . "'
			  AND entity_id=" . (int)$entity_id . "
			  AND lang_id=" . (int)$dst_lang . "
			ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
			LIMIT 1
		", 'row');
		if (!$dst_row) {
			$report['locales'][] = array(
				'lang_id' => (int)$dst_lang,
				'ok' => false,
				'missing' => true,
				'blockers' => array(),
				'warnings' => array(),
			);
			continue;
		}
		$present++;
		$validation = translation_cluster_validate_locale($source, $dst_row, $lang_row ? (string)$lang_row['url'] : '', $entity);
		$locale_ok = empty($validation['blockers']);
		if ($locale_ok) {
			$ready++;
		} else {
			$failed++;
		}
		$blockers += count($validation['blockers']);
		$warnings += count($validation['warnings']);
		$report['locales'][] = array(
			'lang_id' => (int)$dst_lang,
			'ok' => $locale_ok,
			'missing' => false,
			'blockers' => $validation['blockers'],
			'warnings' => $validation['warnings'],
			'source_signature' => $validation['source_signature'],
			'target_signature' => $validation['target_signature'],
			'status' => isset($dst_row['status']) ? (string)$dst_row['status'] : '',
		);
	}
	$cluster_status = translation_cluster_validation_status($ready, count($dst_langs), $failed, $blockers, $warnings);
	$pipeline_stage = 'queued_locales';
	if ($ready > 0 || $present > 0) {
		$pipeline_stage = ($cluster_status === 'ready_to_publish') ? 'publish_ready' : (($failed > 0 || $blockers > 0) ? 'cluster_validate' : 'translating');
	}
	$upd = array(
		'source_lang_id' => $src_lang_id,
		'source_mode' => translation_cluster_source_mode($entity),
		'pipeline_stage' => $pipeline_stage,
		'cluster_status' => $cluster_status,
		'ready_locales' => $ready,
		'total_locales' => count($dst_langs),
		'failed_locales' => $failed,
		'blocker_count' => $blockers,
		'warning_count' => $warnings,
		'search_title' => $search_title,
		'search_slug' => $search_slug,
		'validation_json' => json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'last_job_id' => (int)$job_id,
		'last_error' => ($failed > 0 || $blockers > 0) ? 'cluster_has_validation_issues' : '',
	);
	$prev_st = translation_cluster_get_state($entity, $entity_id);
	if (is_array($prev_st) && !empty($prev_st['seo_monitor_handoff']) && !translation_cluster_has_full_scope_locales_in_ci($entity, $entity_id, $src_lang_id)) {
		$upd['seo_monitor_handoff'] = 0;
	}
	if (is_array($prev_st) && !empty($prev_st['human_reviewed_at'])) {
		$hr_raw = trim((string)$prev_st['human_reviewed_at']);
		if ($hr_raw !== '' && $hr_raw !== '0000-00-00 00:00:00') {
			$mx = mysql_select("SELECT MAX(updated_at) AS m FROM content_i18n WHERE entity='" . mysql_res($entity) . "' AND entity_id=" . (int)$entity_id, 'row');
			if ($mx && isset($mx['m']) && trim((string)$mx['m']) !== '') {
				$mt = strtotime((string)$mx['m']);
				$ht = strtotime($hr_raw);
				if ($mt !== false && $ht !== false && $mt > $ht) {
					$upd['human_reviewed_at'] = null;
				}
			}
		}
	}
	return translation_cluster_upsert_state($entity, $entity_id, $upd);
}

function translation_cluster_find_active_state() {
	translation_cluster_ensure_tables();
	$rows = mysql_select("
		SELECT *
		FROM translation_cluster_state tcs
		WHERE tcs.cluster_status NOT IN ('ready_to_publish','needs_review','published','blocked')
		  AND COALESCE(tcs.seo_monitor_handoff,0)=0
		  AND NOT (
			tcs.human_reviewed_at IS NOT NULL
			AND TRIM(tcs.human_reviewed_at) <> ''
			AND tcs.human_reviewed_at <> '0000-00-00 00:00:00'
			AND (
				SELECT COALESCE(MAX(ci.updated_at), '1970-01-01 00:00:00')
				FROM content_i18n ci
				WHERE ci.entity = tcs.entity AND ci.entity_id = tcs.entity_id
			) <= tcs.human_reviewed_at
		  )
		ORDER BY tcs.updated_at ASC, tcs.created_at ASC, tcs.id ASC
		LIMIT 10
	", 'rows') ?: array();
	if ($rows === array()) {
		return null;
	}
	foreach ($rows as $row) {
		$ent = isset($row['entity']) ? (string)$row['entity'] : '';
		$eid = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
		if ($ent === '' || $eid <= 0) {
			continue;
		}
		return $row;
	}
	return null;
}

/**
 * Cluster to show in Translations review header: running job → pending job → active state → latest blocked/translating.
 *
 * @return array{entity:string,entity_id:int,state:array|null,source:string}|null
 */
function translation_cluster_parse_job_payload_entity($payload) {
	if ($payload === null || $payload === '') {
		return null;
	}
	$p = is_array($payload) ? $payload : @json_decode((string)$payload, true);
	if (!is_array($p)) {
		return null;
	}
	$ent = isset($p['entity']) ? trim((string)$p['entity']) : '';
	$eid = isset($p['entity_id']) ? (int)$p['entity_id'] : 0;
	if ($ent === '' || $eid <= 0) {
		return null;
	}
	return array('entity' => $ent, 'entity_id' => $eid);
}

function translation_cluster_find_header_focus() {
	translation_cluster_ensure_tables();
	if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0) {
		$rows = mysql_select("
			SELECT payload FROM admin_jobs
			WHERE module='translations' AND status='running'
			ORDER BY id DESC
			LIMIT 30
		", 'rows') ?: array();
		foreach ($rows as $row) {
			$pair = translation_cluster_parse_job_payload_entity(isset($row['payload']) ? $row['payload'] : '');
			if ($pair !== null) {
				$st = translation_cluster_get_state($pair['entity'], $pair['entity_id']);
				return array(
					'entity' => $pair['entity'],
					'entity_id' => $pair['entity_id'],
					'state' => $st,
					'source' => 'running_job',
				);
			}
		}
		$rows = mysql_select("
			SELECT payload FROM admin_jobs
			WHERE module='translations' AND status='pending'
			ORDER BY id ASC
			LIMIT 50
		", 'rows') ?: array();
		foreach ($rows as $row) {
			$pair = translation_cluster_parse_job_payload_entity(isset($row['payload']) ? $row['payload'] : '');
			if ($pair !== null) {
				$st = translation_cluster_get_state($pair['entity'], $pair['entity_id']);
				return array(
					'entity' => $pair['entity'],
					'entity_id' => $pair['entity_id'],
					'state' => $st,
					'source' => 'pending_job',
				);
			}
		}
	}
	$ac = translation_cluster_find_active_state();
	if ($ac) {
		$ent = isset($ac['entity']) ? (string)$ac['entity'] : '';
		$eid = isset($ac['entity_id']) ? (int)$ac['entity_id'] : 0;
		if ($ent !== '' && $eid > 0) {
			return array(
				'entity' => $ent,
				'entity_id' => $eid,
				'state' => $ac,
				'source' => 'active_state',
			);
		}
	}
	$row = mysql_select("
		SELECT *
		FROM translation_cluster_state
		WHERE cluster_status IN ('blocked','translating','new','needs_review')
		  AND COALESCE(seo_monitor_handoff,0)=0
		ORDER BY updated_at DESC
		LIMIT 1
	", 'row');
	if ($row) {
		$ent = isset($row['entity']) ? (string)$row['entity'] : '';
		$eid = isset($row['entity_id']) ? (int)$row['entity_id'] : 0;
		if ($ent !== '' && $eid > 0) {
			return array(
				'entity' => $ent,
				'entity_id' => $eid,
				'state' => $row,
				'source' => 'fallback_state',
			);
		}
	}
	return null;
}

/**
 * RAG vector memory for translation examples (translation_vector_items).
 * Off by default — see translation_settings.translation_vector_enabled.
 */
function translation_vector_is_enabled() {
	static $cached = null;
	if ($cached !== null) {
		return $cached;
	}
	$cached = false;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
		return false;
	}
	$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if (!$row || $row['value'] === '') {
		return false;
	}
	$dec = @json_decode((string)$row['value'], true);
	if (is_array($dec) && !empty($dec['translation_vector_enabled'])) {
		$cached = true;
	}
	return $cached;
}

/**
 * @return array{rows:int,total_bytes:int,total_human:string}
 */
function translation_vector_table_stats() {
	translation_cluster_ensure_tables();
	$out = array('rows' => 0, 'total_bytes' => 0, 'total_human' => '0 B');
	if (@mysql_select("SHOW TABLES LIKE 'translation_vector_items'", 'num_rows') === 0) {
		return $out;
	}
	$row = mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'row');
	$out['rows'] = $row && isset($row['c']) ? (int)$row['c'] : 0;
	global $config;
	$db = isset($config['mysql_database']) ? (string)$config['mysql_database'] : '';
	if ($db !== '') {
		$sz = mysql_select("
			SELECT (data_length + index_length) AS total_bytes
			FROM information_schema.TABLES
			WHERE table_schema='" . mysql_res($db) . "'
			  AND table_name='translation_vector_items'
			LIMIT 1
		", 'row');
		if ($sz && isset($sz['total_bytes'])) {
			$out['total_bytes'] = (int)$sz['total_bytes'];
			if (function_exists('db_maintenance_format_bytes')) {
				$out['total_human'] = db_maintenance_format_bytes($out['total_bytes']);
			} else {
				$out['total_human'] = round($out['total_bytes'] / (1024 * 1024), 1) . ' MB';
			}
		}
	}
	return $out;
}

/**
 * Delete all rows from translation_vector_items in chunks; OPTIMIZE when empty.
 *
 * @return array{ok:bool,message:string,deleted:int,chunks:int,remaining:int,dry_run?:bool}
 */
function translation_vector_clear_all(array $opts = array()) {
	translation_cluster_ensure_tables();
	if (@mysql_select("SHOW TABLES LIKE 'translation_vector_items'", 'num_rows') === 0) {
		return array('ok' => false, 'message' => 'Table translation_vector_items not found.', 'deleted' => 0, 'chunks' => 0, 'remaining' => 0);
	}
	$chunk = isset($opts['chunk']) ? max(100, min(2000, (int)$opts['chunk'])) : 500;
	$max_chunks = isset($opts['max_chunks']) ? max(1, min(5000, (int)$opts['max_chunks'])) : 500;
	$pause_ms = isset($opts['pause_ms']) ? max(0, min(5000, (int)$opts['pause_ms'])) : 100;
	$dry_run = !empty($opts['dry_run']);

	$remaining_before = (int)mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'string');
	if ($dry_run) {
		return array(
			'ok' => true,
			'message' => '[dry run] Would delete ' . $remaining_before . ' row(s).',
			'deleted' => $remaining_before,
			'chunks' => 0,
			'remaining' => $remaining_before,
			'dry_run' => true,
		);
	}

	$deleted = 0;
	$chunks = 0;
	for ($i = 0; $i < $max_chunks; $i++) {
		$rows = mysql_select(
			"SELECT id FROM translation_vector_items ORDER BY id ASC LIMIT " . (int)$chunk,
			'rows'
		) ?: array();
		if (empty($rows)) {
			break;
		}
		$ids = array();
		foreach ($rows as $r) {
			if (!empty($r['id'])) {
				$ids[] = (int)$r['id'];
			}
		}
		if (empty($ids)) {
			break;
		}
		$count_before = (int)mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'string');
		$sql = "DELETE FROM translation_vector_items WHERE id IN (" . implode(',', $ids) . ")";
		mysql_fn('query', $sql);
		$count_after = (int)mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'string');
		$n = $count_before - $count_after;
		if ($n <= 0 && function_exists('mysql_query_affected_rows')) {
			$n = mysql_query_affected_rows($sql);
		}
		if ($n <= 0) {
			break;
		}
		$deleted += $n;
		$chunks++;
		if ($n < count($ids)) {
			break;
		}
		if ($pause_ms > 0) {
			usleep($pause_ms * 1000);
		}
	}

	$remaining = (int)mysql_select("SELECT COUNT(*) AS c FROM translation_vector_items", 'string');
	if ($remaining === 0) {
		mysql_fn('query', "OPTIMIZE TABLE translation_vector_items");
	}
	return array(
		'ok' => true,
		'message' => $deleted > 0
			? ('Deleted ' . $deleted . ' vector row(s) in ' . $chunks . ' chunk(s); ' . $remaining . ' remaining.')
			: ('Nothing to delete (' . $remaining . ' row(s) remain).'),
		'deleted' => $deleted,
		'chunks' => $chunks,
		'remaining' => $remaining,
		'remaining_before' => $remaining_before,
	);
}

function translation_vector_normalize_text($text) {
	$text = mb_strtolower(translation_cluster_plain_text($text), 'UTF-8');
	$text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', (string)$text);
	$text = preg_replace('/\s+/u', ' ', (string)$text);
	return trim((string)$text);
}

function translation_vector_allowed_source_entities($entity, array $opts = array()) {
	$entity = trim((string)$entity);
	if (!empty($opts['source_entities']) && is_array($opts['source_entities'])) {
		$list = array_values(array_unique(array_filter(array_map(function ($v) {
			return trim((string)$v);
		}, $opts['source_entities']))));
		if ($list !== array()) {
			return $list;
		}
	}
	$base = array('pages', 'guides', 'games', 'casino_articles', 'blog', 'authors');
	if ($entity !== '' && !in_array($entity, $base, true)) {
		$base[] = $entity;
	}
	return $base;
}

function translation_vector_local_embed($text, $dims = 64) {
	$text = translation_vector_normalize_text($text);
	$dims = max(16, min(256, (int)$dims));
	$vec = array_fill(0, $dims, 0.0);
	if ($text === '') {
		return $vec;
	}
	$parts = preg_split('/\s+/u', $text);
	if (!is_array($parts)) {
		return $vec;
	}
	foreach ($parts as $token) {
		$token = trim((string)$token);
		if ($token === '') {
			continue;
		}
		$hash = sprintf('%u', crc32($token));
		$idx = (int)$hash % $dims;
		$vec[$idx] += 1.0;
	}
	$norm = 0.0;
	foreach ($vec as $v) {
		$norm += $v * $v;
	}
	$norm = sqrt($norm);
	if ($norm > 0.0) {
		foreach ($vec as $i => $v) {
			$vec[$i] = $v / $norm;
		}
	}
	return $vec;
}

function translation_vector_cosine(array $a, array $b) {
	$n = min(count($a), count($b));
	if ($n <= 0) {
		return 0.0;
	}
	$sum = 0.0;
	for ($i = 0; $i < $n; $i++) {
		$sum += ((float)$a[$i]) * ((float)$b[$i]);
	}
	return (float)$sum;
}

function translation_vector_store_item(array $item) {
	if (!translation_vector_is_enabled()) {
		return false;
	}
	translation_cluster_ensure_tables();
	$src_text = isset($item['source_text']) ? trim((string)$item['source_text']) : '';
	$target_text = isset($item['target_text']) ? trim((string)$item['target_text']) : '';
	$field_type = isset($item['field_type']) ? trim((string)$item['field_type']) : '';
	$dst_lang_id = isset($item['dst_lang_id']) ? (int)$item['dst_lang_id'] : 0;
	if ($src_text === '' || $target_text === '' || $field_type === '' || $dst_lang_id <= 0) {
		return false;
	}
	$now = date('Y-m-d H:i:s');
	$source_norm = translation_vector_normalize_text($src_text);
	$source_hash = sha1($source_norm . '|' . $field_type . '|' . $dst_lang_id);
	$vec_json = json_encode(translation_vector_local_embed($source_norm), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$existing = mysql_select("
		SELECT id
		FROM translation_vector_items
		WHERE source_hash='" . mysql_res($source_hash) . "'
		  AND dst_lang_id=" . $dst_lang_id . "
		  AND field_type='" . mysql_res($field_type) . "'
		LIMIT 1
	", 'row');
	$upd = array(
		'entity' => isset($item['entity']) ? substr((string)$item['entity'], 0, 64) : '',
		'entity_id' => isset($item['entity_id']) ? (int)$item['entity_id'] : 0,
		'src_lang_id' => isset($item['src_lang_id']) ? (int)$item['src_lang_id'] : 1,
		'dst_lang_id' => $dst_lang_id,
		'field_type' => substr($field_type, 0, 32),
		'source_hash' => $source_hash,
		'source_norm' => $source_norm,
		'source_text' => $src_text,
		'target_text' => $target_text,
		'vector_json' => $vec_json,
		'quality_status' => isset($item['quality_status']) ? substr((string)$item['quality_status'], 0, 16) : 'auto',
		'updated_at' => $now,
	);
	if ($existing) {
		return mysql_fn('update', 'translation_vector_items', $upd, " AND id=" . (int)$existing['id'] . " ");
	}
	$upd['created_at'] = $now;
	$upd['usage_count'] = 0;
	return mysql_fn('insert', 'translation_vector_items', $upd);
}

function translation_vector_mark_used(array $ids) {
	if (!translation_vector_is_enabled()) {
		return;
	}
	translation_cluster_ensure_tables();
	$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
	if ($ids === array()) {
		return;
	}
	mysql_fn('query', "UPDATE translation_vector_items
		SET usage_count = usage_count + 1,
			last_used_at = '" . mysql_res(date('Y-m-d H:i:s')) . "',
			updated_at = '" . mysql_res(date('Y-m-d H:i:s')) . "'
		WHERE id IN (" . implode(',', $ids) . ")");
}

function translation_vector_retrieve_examples($source_text, array $opts = array()) {
	if (!translation_vector_is_enabled()) {
		return array();
	}
	translation_cluster_ensure_tables();
	$field_type = isset($opts['field_type']) ? trim((string)$opts['field_type']) : '';
	$dst_lang_id = isset($opts['dst_lang_id']) ? (int)$opts['dst_lang_id'] : 0;
	if ($field_type === '' || $dst_lang_id <= 0) {
		return array();
	}
	$entity = isset($opts['entity']) ? trim((string)$opts['entity']) : '';
	$limit = isset($opts['limit']) ? max(1, min(5, (int)$opts['limit'])) : 3;
	$allowed_entities = translation_vector_allowed_source_entities($entity, $opts);
	$where_entity = '';
	if ($allowed_entities !== array()) {
		$esc = array();
		foreach ($allowed_entities as $ent) {
			$ent = trim((string)$ent);
			if ($ent !== '') {
				$esc[] = "'" . mysql_res($ent) . "'";
			}
		}
		if ($esc !== array()) {
			$where_entity = " AND (entity='' OR entity IN (" . implode(',', $esc) . "))";
		}
	}
	$rows = mysql_select("
		SELECT id, entity, source_text, target_text, vector_json, quality_status
		FROM translation_vector_items
		WHERE dst_lang_id=" . $dst_lang_id . "
		  AND field_type='" . mysql_res($field_type) . "'
		  AND quality_status IN ('approved','auto')
		  " . $where_entity . "
		ORDER BY quality_status='approved' DESC, usage_count DESC, updated_at DESC
		LIMIT 60
	", 'rows') ?: array();
	if ($rows === array()) {
		return array();
	}
	$qvec = translation_vector_local_embed($source_text);
	$scored = array();
	foreach ($rows as $r) {
		$vec = isset($r['vector_json']) ? json_decode((string)$r['vector_json'], true) : null;
		if (!is_array($vec)) {
			$vec = translation_vector_local_embed(isset($r['source_text']) ? (string)$r['source_text'] : '');
		}
		$score = translation_vector_cosine($qvec, $vec);
		if ($score <= 0.18) {
			continue;
		}
		$scored[] = array(
			'id' => (int)$r['id'],
			'entity' => isset($r['entity']) ? (string)$r['entity'] : '',
			'source_text' => (string)$r['source_text'],
			'target_text' => (string)$r['target_text'],
			'score' => $score,
		);
	}
	usort($scored, function ($a, $b) {
		if ($a['score'] === $b['score']) {
			return $a['id'] <=> $b['id'];
		}
		return ($a['score'] > $b['score']) ? -1 : 1;
	});
	$scored = array_slice($scored, 0, $limit);
	translation_vector_mark_used(array_map(function ($row) {
		return (int)$row['id'];
	}, $scored));
	return $scored;
}
