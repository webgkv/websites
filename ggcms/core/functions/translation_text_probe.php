<?php
/**
 * Shared HTML→plain and Latin-leak heuristics for translation jobs and autopilot.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * Target language URL segments where leftover Latin prose is almost always a translation bug.
 */
function translation_latin_leak_target_urls() {
	return array(
		'hi', 'bn', 'ar', 'ru', 'uk', 'fa', 'ur', 'he', 'th', 'el', 'ka', 'am', 'my', 'km', 'lo',
		'sr', 'bg', 'mk', 'zh', 'ja', 'ko', 'ta', 'te', 'ml', 'kn', 'gu', 'pa', 'mr', 'ne', 'si',
		'ps', 'dv', 'sd', 'hy', 'kk', 'ky', 'mn', 'tg', 'bo', 'dz',
	);
}

function translation_latin_leak_applies_to_target($dst_lang_url) {
	$u = strtolower(trim((string)$dst_lang_url, '/'));
	if ($u === '') {
		return false;
	}
	static $set = null;
	if ($set === null) {
		$set = array_flip(translation_latin_leak_target_urls());
	}
	return isset($set[$u]);
}

/** Longer phrases first so multi-word brands are stripped before single tokens. */
function translation_latin_leak_whitelist_tokens() {
	return array(
		'Provably Fair',
		'Aviator', 'Spribe', 'UKGC', 'MGA', 'RTP', 'RNG',
		'iOS', 'Android', 'GitHub', 'Bitbucket', 'Reddit', 'Discord',
		'YouTube', 'Facebook', 'Twitter', 'Instagram', 'LinkedIn',
		'HTML', 'CSS', 'JSON', 'XML', 'HTTP', 'HTTPS', 'SSL', 'TLS', 'VPN',
		'API', 'URL', 'URI', 'FAQ', 'GPU', 'CPU', 'PDF', 'CSV', 'SEO', 'SaaS', 'IDE',
		'LCD', 'LED', 'USB', 'WiFi', 'Wi-Fi',
	);
}

function translation_html_probe_plain($html) {
	$h = (string)$html;
	$h = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/iu', ' ', $h);
	$h = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/iu', ' ', $h);
	$h = strip_tags($h);
	$h = html_entity_decode($h, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$h = preg_replace('#https?://\S+#u', ' ', $h);
	$h = preg_replace('/\s+/u', ' ', $h);
	return trim($h);
}

/**
 * @param string $snippet_out first ~200 chars of the matched run (for logs)
 */
function translation_plain_has_leaking_latin_run_after_whitelist($plain, $min_words, &$snippet_out) {
	$snippet_out = '';
	$plain = (string)$plain;
	if ($plain === '') {
		return false;
	}
	$min_words = max(3, min(12, (int)$min_words));
	$tokens = translation_latin_leak_whitelist_tokens();
	usort($tokens, function ($a, $b) {
		return mb_strlen((string)$b) - mb_strlen((string)$a);
	});
	foreach ($tokens as $tok) {
		$t = (string)$tok;
		if ($t === '') {
			continue;
		}
		$plain = preg_replace('/\b' . preg_quote($t, '/') . '\b/iu', ' ', $plain);
	}
	$plain = preg_replace('/\s+/u', ' ', $plain);
	$plain = trim($plain);
	$n = $min_words - 1;
	if ($n < 1) {
		$n = 1;
	}
	$pat = '/(?:\b[A-Za-z][A-Za-z\'-]*\s+){' . $n . ',}\b[A-Za-z][A-Za-z\'-]*\b/u';
	if (preg_match($pat, $plain, $m)) {
		$snippet_out = mb_substr(trim((string)$m[0]), 0, 200, 'UTF-8');
		return true;
	}
	return false;
}
