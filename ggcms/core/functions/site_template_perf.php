<?php
/**
 * Safe frontend performance helpers.
 *
 * Only for optimizations that do not change render-blocking layout CSS
 * (Bootstrap, style.css, responsive.css, fonts, Font Awesome must stay blocking).
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
	 * Non-blocking stylesheet for below-the-fold / optional UI only (e.g. ad popup).
	 */
	function site_template_deferred_stylesheet($href) {
		$href = (string) $href;
		if ($href === '') {
			return '';
		}
		$eh = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
		return '        <link rel="preload" as="style" href="' . $eh . '" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n"
			. '        <noscript><link rel="stylesheet" href="' . $eh . '"></noscript>' . "\n";
	}
}

if (!function_exists('site_template_fontawesome_stylesheets')) {
	/** Font Awesome subset (solid + brands) instead of full all.min.css. */
	function site_template_fontawesome_stylesheets() {
		$base = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/';
		$out = '';
		foreach (array('fontawesome.min.css', 'solid.min.css', 'brands.min.css') as $file) {
			$href = $base . $file;
			$out .= '        <link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
		}
		return $out;
	}
}
