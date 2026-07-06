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

if (!function_exists('content_is_internal_link_href')) {
	/**
	 * True for same-site paths (/en/… or https://current-host/…).
	 */
	function content_is_internal_link_href($href) {
		$href = trim(html_entity_decode((string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		if ($href === '' || $href === '#') {
			return false;
		}
		if ($href[0] === '#') {
			return false;
		}
		if (preg_match('#^\s*javascript:#i', $href)) {
			return false;
		}
		if (preg_match('#^(?:mailto|tel):#i', $href)) {
			return false;
		}
		if ($href[0] === '/') {
			return true;
		}
		if (!preg_match('#^https?://([^/?#]+)#i', $href, $m)) {
			return false;
		}
		$host = strtolower((string) $m[1]);
		$hosts = array();
		if (!empty($_SERVER['HTTP_HOST'])) {
			$h = strtolower((string) $_SERVER['HTTP_HOST']);
			$hosts[$h] = true;
			if (strpos($h, 'www.') === 0) {
				$hosts[substr($h, 4)] = true;
			} else {
				$hosts['www.' . $h] = true;
			}
		}
		global $config;
		if (!empty($config['http_domain'])) {
			$dom = strtolower(preg_replace('#^https?://#i', '', (string) $config['http_domain']));
			$dom = rtrim($dom, '/');
			if ($dom !== '') {
				$hosts[$dom] = true;
				$hosts['www.' . $dom] = true;
			}
		}
		return isset($hosts[$host]);
	}
}

if (!function_exists('content_normalize_noads_links')) {
	/**
	 * Wrap internal <a> in <noads>; strip <noads> around external links.
	 */
	function content_normalize_noads_links($html) {
		$html = (string) $html;
		if ($html === '' || stripos($html, '<a') === false) {
			return $html;
		}

		// External link wrongly wrapped in noads → keep anchor only.
		$html = preg_replace_callback(
			'/<noads\b[^>]*>\s*(<a\b[^>]*>.*?<\/a>)\s*<\/noads>/ius',
			function ($m) {
				if (!preg_match('/\bhref\s*=\s*(["\'])([^"\']*)\1/i', $m[1], $hm)) {
					return $m[0];
				}
				return content_is_internal_link_href($hm[2]) ? $m[0] : $m[1];
			},
			$html
		);

		// Internal links not yet in noads → wrap.
		$offset = 0;
		$len = strlen($html);
		$out = '';
		if (preg_match_all('/<a\b[^>]*>.*?<\/a>/ius', $html, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[0] as $m) {
				$tag = (string) $m[0];
				$pos = (int) $m[1];
				$out .= substr($html, $offset, $pos - $offset);
				$offset = $pos + strlen($tag);

				if (!preg_match('/\bhref\s*=\s*(["\'])([^"\']*)\1/i', $tag, $hm)
					|| !content_is_internal_link_href($hm[2])) {
					$out .= $tag;
					continue;
				}
				$pre = substr($html, max(0, $pos - 12), 12);
				if (preg_match('/<noads>\s*$/i', $pre)) {
					$out .= $tag;
					continue;
				}
				$out .= '<noads>' . $tag . '</noads>';
			}
			$out .= substr($html, $offset);
			$html = $out;
		}

		$html = preg_replace('/<noads>\s*<noads>/iu', '<noads>', $html);
		$html = preg_replace('/<\/noads>\s*<\/noads>/iu', '</noads>', $html);
		return $html;
	}
}

if (!function_exists('content_unwrap_exclude_tags')) {
	/**
	 * Remove <noinc>/<noads> wrappers for front output (inner HTML kept).
	 *
	 * @param string[] $tags
	 */
	function content_unwrap_exclude_tags($html, array $tags = null) {
		if ($tags === null) {
			$tags = content_exclude_tag_names();
		}
		list($masked, $protected) = content_exclude_extract_blocks((string) $html, $tags);
		return content_exclude_restore_blocks($masked, $protected);
	}
}
