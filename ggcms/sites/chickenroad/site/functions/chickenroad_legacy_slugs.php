<?php
/**
 * Legacy Aviator URL slugs on chickenroad.run → canonical Chicken Road slugs.
 */

if (!function_exists('chickenroad_legacy_slug_map')) {
	/**
	 * @return array<string,string> old slug => new slug
	 */
	function chickenroad_legacy_slug_map() {
		return array(
			'aviator-game-analysis' => 'game-analysis',
			'aviator-signals'       => 'signals',
			'casino-bonus-aviator'  => 'bonus',
		);
	}
}

if (!function_exists('chickenroad_legacy_slug_redirect_if_needed')) {
	/**
	 * 301 legacy Aviator slugs (flat or /guides/) to Chicken Road slugs.
	 */
	function chickenroad_legacy_slug_redirect_if_needed($norm_path, $request_url) {
		global $config;

		$map = chickenroad_legacy_slug_map();
		$old = implode('|', array_map(function ($s) {
			return preg_quote($s, '#');
		}, array_keys($map)));

		if (!preg_match('#^/([a-z]{2,3})/(?:guides/)?(' . $old . ')/?$#i', $norm_path, $m)) {
			return;
		}

		$lang = strtolower($m[1]);
		$legacy = strtolower($m[2]);
		if (!isset($map[$legacy])) {
			return;
		}

		$to = '/' . $lang . '/' . $map[$legacy] . '/';
		$qs = (isset($request_url[1]) && $request_url[1] !== '') ? '?' . $request_url[1] : '';
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . (isset($config['http_domain']) ? $config['http_domain'] : '') . $to . $qs);
		exit;
	}
}

if (!function_exists('chickenroad_casino_slug_to_chickenroad')) {
	/**
	 * Map legacy casino article slug *-aviator / aviator-* → *-chicken-road when pattern matches.
	 */
	function chickenroad_casino_slug_to_chickenroad($slug) {
		$slug = trim((string) $slug, '/');
		if ($slug === '' || stripos($slug, 'aviator') === false) {
			return $slug;
		}
		if (preg_match('/^aviator-(.+)$/i', $slug, $m)) {
			return trim($m[1], '-') . '-chicken-road';
		}
		if (preg_match('/^(.+)-aviator$/i', $slug, $m)) {
			return trim($m[1], '-') . '-chicken-road';
		}
		return preg_replace('/aviator/i', 'chicken-road', $slug);
	}
}

if (!function_exists('chickenroad_casino_article_exists')) {
	function chickenroad_casino_article_exists($slug) {
		$slug = trim((string) $slug, '/');
		if ($slug === '' || @mysql_select("SHOW TABLES LIKE 'casino_articles'", 'num_rows') <= 0) {
			return false;
		}
		$row = mysql_select("
			SELECT id FROM casino_articles
			WHERE display=1 AND url='" . mysql_res($slug) . "'
			LIMIT 1
		", 'row', 0);
		return !empty($row['id']);
	}
}

if (!function_exists('chickenroad_casino_legacy_redirect_if_needed')) {
	/**
	 * 301 /{lang}/casinos/{legacy-aviator-slug}/ → active chicken-road article or casinos landing.
	 */
	function chickenroad_casino_legacy_redirect_if_needed($norm_path, $request_url) {
		global $config;

		if (!preg_match('#^/([a-z]{2,3})/casinos/([^/]+)/?$#i', $norm_path, $m)) {
			return;
		}
		$slug = strtolower($m[2]);
		if (stripos($slug, 'aviator') === false) {
			return;
		}

		$candidate = chickenroad_casino_slug_to_chickenroad($slug);
		$lang = strtolower($m[1]);
		if ($candidate !== '' && chickenroad_casino_article_exists($candidate)) {
			$to = '/' . $lang . '/casinos/' . $candidate . '/';
		} else {
			$to = '/' . $lang . '/casinos/';
		}

		$qs = (isset($request_url[1]) && $request_url[1] !== '') ? '?' . $request_url[1] : '';
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . (isset($config['http_domain']) ? $config['http_domain'] : '') . $to . $qs);
		exit;
	}
}

if (!function_exists('chickenroad_normalize_legacy_slug_urls_in_text')) {
	/**
	 * Fix internal links still pointing at Aviator slugs in HTML/JSON copy.
	 */
	function chickenroad_normalize_legacy_slug_urls_in_text($text) {
		if ($text === null || $text === '') {
			return (string) $text;
		}
		$text = (string) $text;

		$text = str_ireplace(
			array('#aviator-app', 'aviator-log-in.com', 'www.aviator-log-in.com'),
			array('#demo', 'chickenroad.run', 'chickenroad.run'),
			$text
		);

		foreach (chickenroad_legacy_slug_map() as $old => $new) {
			$patterns = array(
				'/' . $old . '/',
				'/' . $old,
				'/guides/' . $old . '/',
				'/guides/' . $old,
			);
			$repls = array(
				'/' . $new . '/',
				'/' . $new,
				'/' . $new . '/',
				'/' . $new,
			);
			$text = str_replace($patterns, $repls, $text);
		}

		if (preg_match_all('#/casinos/([a-z0-9\-]*aviator[a-z0-9\-]*)/?#i', $text, $matches)) {
			$seen = array();
			foreach ($matches[1] as $legacy_slug) {
				$key = strtolower($legacy_slug);
				if (isset($seen[$key])) {
					continue;
				}
				$seen[$key] = true;
				$candidate = chickenroad_casino_slug_to_chickenroad($legacy_slug);
				if ($candidate !== '' && chickenroad_casino_article_exists($candidate)) {
					$text = preg_replace(
						'#(/casinos/)' . preg_quote($legacy_slug, '#') . '(/?)#i',
						'$1' . $candidate . '$2',
						$text
					);
				} else {
					$text = preg_replace(
						'#(/[a-z]{2,3})?/casinos/' . preg_quote($legacy_slug, '#') . '/?#i',
						'$1/casinos/',
						$text
					);
				}
			}
		}

		return $text;
	}
}
