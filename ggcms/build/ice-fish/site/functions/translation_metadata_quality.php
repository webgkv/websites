<?php
/**
 * Detect low-quality i18n metadata (name / title / description) for autopilot meta-fix jobs.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

require_once ROOT_DIR . 'functions/translation_text_probe.php';

/**
 * Social-style #word in plain text (Unicode letters); ignores isolated hex colors #rgb.
 *
 * @param string $plain already stripped (e.g. from translation_html_probe_plain)
 */
function translation_metadata_plain_has_social_hashtag($plain) {
	$plain = (string)$plain;
	if ($plain === '' || strpos($plain, '#') === false) {
		return false;
	}
	return (bool)preg_match('/#\p{L}[\p{L}\p{N}_]*/u', $plain);
}

/**
 * SEO/name fields should be plain text. Detect any markup: entities, ASCII tags, fullwidth ＜／＞,
 * then compare to strip_tags (regex-only missed some PHP/PCRE edge cases with \\b + Unicode).
 *
 * @param string $field name|title|description
 */
function translation_metadata_raw_has_noisy_short_field_html($field, $raw) {
	if (!in_array($field, array('name', 'title', 'description'), true)) {
		return false;
	}
	$raw = (string)$raw;
	// Fullwidth less-than/greater-than (Word etc.) → ASCII so strip_tags / decode see real tags.
	$raw = str_replace(array('＜', '＞'), array('<', '>'), $raw);
	$raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (trim($raw) === '') {
		return false;
	}
	$without_tags = strip_tags($raw);
	return $without_tags !== $raw;
}

/**
 * @return array<string,bool> map field => true if that field should be re-translated from source
 */
function translation_metadata_fields_needing_fix($name, $title, $description, $dst_lang_url, $latin_min_words = 4) {
	$out = array();
	$latin_min_words = max(3, min(12, (int)$latin_min_words));
	$use_latin = translation_latin_leak_applies_to_target($dst_lang_url);

	foreach (array('name' => $name, 'title' => $title, 'description' => $description) as $field => $raw) {
		$raw = (string)$raw;
		if (trim($raw) === '') {
			continue;
		}
		$raw_dec = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$plain = translation_html_probe_plain($raw_dec);
		if (translation_metadata_plain_has_social_hashtag($plain)) {
			$out[$field] = true;
			continue;
		}
		if (translation_metadata_raw_has_noisy_short_field_html($field, $raw)) {
			$out[$field] = true;
			continue;
		}
		if ($use_latin) {
			$snippet = '';
			if (translation_plain_has_leaking_latin_run_after_whitelist($plain, $latin_min_words, $snippet)) {
				$out[$field] = true;
			}
		}
	}
	return $out;
}

/**
 * SEO Monitor uses the same limits as seo_monitor_analyze_locale() (title/display ≤70, meta description ≤160).
 * Used by autopilot meta-fix so published locales with long meta still get metadata_normalize jobs.
 *
 * @return array<string,bool> field => true (name|title|description)
 */
function translation_metadata_seo_fields_needing_fix($name, $title, $description) {
	$out = array();
	$t = trim(strip_tags((string)$title));
	if ($t !== '') {
		$len = function_exists('mb_strlen') ? mb_strlen($t, 'UTF-8') : strlen($t);
		if ($len > 70) {
			$out['title'] = true;
		}
	} else {
		$n = trim(strip_tags((string)$name));
		if ($n !== '') {
			$len = function_exists('mb_strlen') ? mb_strlen($n, 'UTF-8') : strlen($n);
			if ($len > 70) {
				$out['name'] = true;
			}
		}
	}
	$desc = trim(strip_tags((string)$description));
	if ($desc !== '') {
		$dlen = function_exists('mb_strlen') ? mb_strlen($desc, 'UTF-8') : strlen($desc);
		if ($dlen > 160) {
			$out['description'] = true;
		}
	}
	return $out;
}

/**
 * @return array<string,mixed>
 */
function translation_metadata_extra_parse($extra) {
	$dec = json_decode((string)$extra, true);
	return is_array($dec) ? $dec : array();
}

/**
 * name/title/description for source language: prefer content_i18n row, then legacy entity table
 * (same idea as job_runner_translations.php so meta-fix can enqueue when i18n source field is empty).
 *
 * @param array<string,mixed>|null $cisrc one content_i18n row for src lang (name/title/description) or null
 * @return array{name:string,title:string,description:string}
 */
function translation_resolve_source_meta_for_entity($entity, $entity_id, $src_lang, $cisrc) {
	$entity = (string)$entity;
	$entity_id = (int)$entity_id;
	$src_lang = (int)$src_lang;
	$out = array('name' => '', 'title' => '', 'description' => '');
	if (is_array($cisrc)) {
		foreach (array('name', 'title', 'description') as $f) {
			if (isset($cisrc[$f]) && trim((string)$cisrc[$f]) !== '') {
				$out[$f] = trim((string)$cisrc[$f]);
			}
		}
	}
	$suffix = ($src_lang > 1) ? (string)$src_lang : '';
	$pick = function ($row, $candidates) {
		if (!is_array($row)) {
			return '';
		}
		foreach ($candidates as $col) {
			if (!isset($row[$col])) {
				continue;
			}
			$v = trim((string)$row[$col]);
			if ($v !== '') {
				return $v;
			}
		}
		return '';
	};
	$cols_pages = array(
		'name' => array('name' . $suffix, 'name'),
		'title' => array('title' . $suffix, 'title'),
		'description' => array('description' . $suffix, 'description'),
	);
	$legacy = null;
	switch ($entity) {
		case 'pages':
			$legacy = mysql_select("SELECT * FROM pages WHERE id=" . $entity_id . " LIMIT 1", 'row');
			foreach (array('name', 'title', 'description') as $f) {
				if ($out[$f] === '') {
					$out[$f] = $pick($legacy, $cols_pages[$f]);
				}
			}
			break;
		case 'guides':
			$legacy = mysql_select("SELECT * FROM guides WHERE id=" . $entity_id . " LIMIT 1", 'row');
			foreach (array('name', 'title', 'description') as $f) {
				if ($out[$f] === '') {
					$out[$f] = $pick($legacy, array($f));
				}
			}
			break;
		case 'games':
			$legacy = mysql_select("SELECT * FROM games WHERE id=" . $entity_id . " LIMIT 1", 'row');
			foreach (array('name', 'title', 'description') as $f) {
				if ($out[$f] === '') {
					$out[$f] = $pick($legacy, array($f));
				}
			}
			break;
		case 'casino_articles':
			$legacy = mysql_select("SELECT * FROM casino_articles WHERE id=" . $entity_id . " LIMIT 1", 'row');
			foreach (array('name', 'title', 'description') as $f) {
				if ($out[$f] === '') {
					$out[$f] = $pick($legacy, $cols_pages[$f]);
				}
			}
			break;
		case 'blog':
			$legacy = mysql_select("SELECT * FROM blog WHERE id=" . $entity_id . " LIMIT 1", 'row');
			foreach (array('name', 'title', 'description') as $f) {
				if ($out[$f] === '') {
					$out[$f] = $pick($legacy, $cols_pages[$f]);
				}
			}
			break;
		case 'authors':
			$legacy = mysql_select("SELECT * FROM site_authors WHERE id=" . $entity_id . " LIMIT 1", 'row');
			if ($out['name'] === '') {
				$out['name'] = $pick($legacy, array('name'));
			}
			if ($out['title'] === '') {
				$out['title'] = $pick($legacy, array('job_title'));
			}
			break;
		default:
			break;
	}
	return $out;
}
