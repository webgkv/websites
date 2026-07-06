<?php
/**
 * Safe frontend performance helpers.
 *
 * Defers non-layout CSS (Font Awesome, Google Fonts) so they do not block
 * First Contentful Paint. Layout-critical CSS (Bootstrap, style.css,
 * responsive.css) stays render-blocking.
 */

if (!function_exists('site_template_preconnect_hints')) {
	function site_template_preconnect_hints() {
		static $done = false;
		if ($done) {
			return '';
		}
		$done = true;
		$hints = array(
			array('href' => 'https://cdn.jsdelivr.net', 'crossorigin' => true),
			array('href' => 'https://cdnjs.cloudflare.com', 'crossorigin' => true),
			array('href' => 'https://fonts.googleapis.com', 'crossorigin' => false),
			array('href' => 'https://fonts.gstatic.com', 'crossorigin' => true),
		);
		$out = '';
		foreach ($hints as $h) {
			$out .= '        <link rel="preconnect" href="' . htmlspecialchars($h['href'], ENT_QUOTES, 'UTF-8') . '"';
			if (!empty($h['crossorigin'])) {
				$out .= ' crossorigin';
			}
			$out .= '>' . "\n";
		}
		return $out;
	}
}

if (!function_exists('site_template_deferred_stylesheet')) {
	/**
	 * Non-blocking stylesheet: loads without blocking render, applies on load.
	 * Uses media="print" swap — broadest browser support, no FOUC for icons/fonts.
	 */
	function site_template_deferred_stylesheet($href) {
		$href = (string) $href;
		if ($href === '') {
			return '';
		}
		$eh = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
		return '        <link rel="stylesheet" href="' . $eh . '" media="print" onload="this.media=\'all\'">' . "\n"
			. '        <noscript><link rel="stylesheet" href="' . $eh . '"></noscript>' . "\n";
	}
}

if (!function_exists('site_template_fontawesome_stylesheets')) {
	/**
	 * Font Awesome subset (solid + brands) loaded non-blocking.
	 * Icons appear ~200ms after first paint; no layout shift since
	 * FA uses :before pseudo-elements with zero dimensions until styled.
	 */
	function site_template_fontawesome_stylesheets() {
		$base = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/';
		$out = '';
		foreach (array('fontawesome.min.css', 'solid.min.css', 'brands.min.css') as $file) {
			$out .= site_template_deferred_stylesheet($base . $file);
		}
		return $out;
	}
}

if (!function_exists('site_template_async_counter')) {
	/**
	 * Add async to <script src="..."> tags that lack async/defer (e.g. DB counters).
	 * Does NOT touch inline scripts or scripts that already have async/defer.
	 */
	function site_template_async_counter($html) {
		$html = (string) $html;
		if (strpos($html, '<script') === false) {
			return $html;
		}
		return preg_replace_callback(
			'/<script(\s[^>]*?)src=(["\x27])([^"\']+)\2([^>]*)>/i',
			function ($m) {
				$before = $m[1]; $after = $m[4];
				if (stripos($before . $after, 'async') !== false || stripos($before . $after, 'defer') !== false) {
					return $m[0];
				}
				return '<script' . $before . 'src=' . $m[2] . $m[3] . $m[2] . $after . ' async>';
			},
			$html
		);
	}
}

if (!function_exists('site_template_google_font')) {
	/**
	 * Non-blocking Google Font with display=swap (system font shows instantly, swaps on load).
	 */
	function site_template_google_font($family_query) {
		$href = 'https://fonts.googleapis.com/css2?family=' . $family_query . '&display=swap';
		return site_template_deferred_stylesheet($href);
	}
}
