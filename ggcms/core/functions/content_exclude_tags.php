<?php
/**
 * Paired exclusion tags in stored HTML content.
 *
 * <noinc>…</noinc> — skip CTA injection between paragraphs (see cta_inject.php).
 * <noads>…</noads> — skip rewriting <a href> to the offer tracker (see advertising_api.php).
 *
 * Only properly closed pairs are processed; an unclosed opening tag is left as-is
 * (no masking, normal CTA / link replacement applies to that fragment).
 */

if (!function_exists('content_exclude_tag_names')) {
	function content_exclude_tag_names() {
		return array('noinc', 'noads');
	}
}

if (!function_exists('content_exclude_extract_blocks')) {
	/**
	 * Mask inner HTML of closed <tag>…</tag> pairs. Wrapper tags are omitted on restore.
	 *
	 * @param string $html
	 * @param string[] $tags e.g. array('noinc') or array('noads')
	 * @return array{0:string,1:array<string,string>} masked HTML, placeholder => inner HTML
	 */
	function content_exclude_extract_blocks($html, array $tags) {
		$html = (string) $html;
		$protected = array();
		if ($html === '' || empty($tags)) {
			return array($html, $protected);
		}

		$normalized = array();
		foreach ($tags as $tag) {
			$t = strtolower(preg_replace('/[^a-z]/', '', (string) $tag));
			if ($t !== '' && in_array($t, content_exclude_tag_names(), true)) {
				$normalized[$t] = true;
			}
		}
		foreach (array_keys($normalized) as $tag) {
			$html = content_exclude_extract_one_tag($html, $tag, $protected);
		}

		return array($html, $protected);
	}
}

if (!function_exists('content_exclude_extract_one_tag')) {
	/**
	 * @param array<string,string> $protected
	 */
	function content_exclude_extract_one_tag($html, $tag, array &$protected) {
		$open_re = '/<' . preg_quote($tag, '/') . '\b[^>]*>/iu';
		$close_re = '/<\/' . preg_quote($tag, '/') . '\s*>/iu';

		$offset = 0;
		$len = strlen($html);
		$out = '';

		while ($offset < $len) {
			if (!preg_match($open_re, $html, $om, PREG_OFFSET_CAPTURE, $offset)) {
				$out .= substr($html, $offset);
				break;
			}

			$open_pos = (int) $om[0][1];
			$open_len = strlen($om[0][0]);
			$inner_start = $open_pos + $open_len;

			$out .= substr($html, $offset, $open_pos - $offset);

			if (!preg_match($close_re, $html, $cm, PREG_OFFSET_CAPTURE, $inner_start)) {
				// Unclosed pair: do not mask; leave tag + remainder untouched.
				$out .= substr($html, $open_pos);
				break;
			}

			$close_pos = (int) $cm[0][1];
			$close_len = strlen($cm[0][0]);
			$inner = substr($html, $inner_start, $close_pos - $inner_start);

			$key = '__CONTENT_EXCL_' . strtoupper($tag) . '_' . count($protected) . '__';
			$protected[$key] = $inner;
			$out .= $key;

			$offset = $close_pos + $close_len;
		}

		return $out;
	}
}

if (!function_exists('content_exclude_restore_blocks')) {
	/**
	 * @param array<string,string> $protected
	 */
	function content_exclude_restore_blocks($html, array $protected) {
		if (empty($protected)) {
			return (string) $html;
		}
		return strtr((string) $html, $protected);
	}
}
