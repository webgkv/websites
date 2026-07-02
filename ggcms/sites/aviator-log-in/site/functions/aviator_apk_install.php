<?php

/**
 * Android APK install guide — /{lang}/{download-slug}/install-apk/
 * Mirrors PWA iOS page wiring (separate `pages` row for SEO, layout demo_apk_android).
 */

if (!defined('AVIATOR_APK_FILE_BASENAME')) {
	define('AVIATOR_APK_FILE_BASENAME', 'aviator.apk');
}

/**
 * Public URL to the APK in site/files/ (served as static file when deployed).
 */
function aviator_apk_file_href() {
	return '/files/' . AVIATOR_APK_FILE_BASENAME;
}

/**
 * Point DB/export HTML at the stable filename (e.g. legacy aviator_v1.apk → aviator.apk).
 *
 * @param string $html
 * @return string
 */
function aviator_apk_normalize_apk_link_in_content($html) {
	if ($html === '' || strpos($html, '/files/') === false) {
		return $html;
	}
	$href = aviator_apk_file_href();
	$name = AVIATOR_APK_FILE_BASENAME;
	$legacy = array('aviator_v1.apk');
	foreach ($legacy as $old) {
		$old_href = '/files/' . $old;
		$html = str_replace('href="' . $old_href . '"', 'href="' . $href . '"', $html);
		$html = str_replace("href='" . $old_href . "'", "href='" . $href . "'", $html);
		$html = str_replace('download="' . $old . '"', 'download="' . $name . '"', $html);
		$html = str_replace("download='" . $old . "'", "download='" . $name . "'", $html);
	}
	return $html;
}

/**
 * Replace legacy placeholder.svg (up to 3 figures) with real step screenshots.
 * DB content may still reference placeholder from early exports.
 *
 * @param string $html
 * @return string
 */
function aviator_apk_replace_placeholder_step_images($html) {
	if ($html === '' || strpos($html, 'apk-install-android') === false) {
		return $html;
	}
	$steps = array(
		'/assets/images/apk-install-android/step-1-download-apk.png',
		'/assets/images/apk-install-android/step-2-unknown-sources.png',
		'/assets/images/apk-install-android/step-3-install-open.png',
	);
	$v = 0;
	if (defined('ROOT_DIR')) {
		foreach ($steps as $rel) {
			$f = ROOT_DIR . ltrim($rel, '/');
			if (@file_exists($f)) {
				$v = max($v, (int) filemtime($f));
			}
		}
	}
	$q = $v > 0 ? ('?v=' . $v) : '';
	$i = 0;
	// Do not use \b before "/assets": after src=" both " and / are non-word chars, so \b never matches and no replacement runs.
	return preg_replace_callback(
		'#(?:https?://[^/\s"\'<>]+)?/assets/images/apk-install-android/placeholder\.svg(?:\?[^"\'>\s]*)?#i',
		function ($m) use ($steps, $q, &$i) {
			if ($i >= count($steps)) {
				return $m[0];
			}
			return $steps[$i++] . $q;
		},
		$html,
		count($steps)
	);
}

/**
 * @param array $lang
 * @return string
 */
function aviator_apk_lang_key($lang) {
	$u = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return $u === '' ? 'en' : $u;
}

/**
 * `pages` row for install-apk (SEO + content_i18n).
 *
 * @return array|null
 */
function aviator_apk_seo_child_row() {
	if (!function_exists('mysql_select')) {
		return null;
	}
	$row = mysql_select(
		"SELECT * FROM pages WHERE display=1 AND module='pages' AND url='install-apk' LIMIT 1",
		'row',
		0
	);
	return ($row && is_array($row)) ? $row : null;
}

/**
 * When layout is demo_apk_android: load SEO child row, replace page_i18n for head/meta, extend hreflang path.
 *
 * @param array $abc
 * @param array $lang
 */
function aviator_apk_merge_seo_child_into_abc(&$abc, $lang) {
	if (empty($abc['page']) || !is_array($abc['page'])) {
		return;
	}
	$child = aviator_apk_seo_child_row();
	if (!$child) {
		return;
	}
	$abc['apk_seo_page'] = $child;
	$lid = isset($lang['id']) ? (int) $lang['id'] : 1;
	if ($lid < 1) {
		$lid = 1;
	}
	if (function_exists('page_i18n_fields_current')) {
		$pi = page_i18n_fields_current($child, $lid);
		$raw = isset($pi['content']) ? trim((string) $pi['content']) : '';
		if (($raw === '' || $raw[0] !== '{') && isset($child['text'])) {
			$tx = trim((string) $child['text']);
			if ($tx !== '' && $tx[0] === '{') {
				$pi['content'] = $tx;
			}
		}
		$abc['page_i18n'] = $pi;
	}
	if (!empty($abc['links']) && is_array($abc['links'])) {
		foreach ($abc['links'] as $lk => $segs) {
			if (is_array($segs) && count($segs) >= 2) {
				$abc['links'][$lk][] = 'install-apk';
			}
		}
	}
}

/**
 * Localized child row fields for the current language.
 *
 * @param array $lang
 * @return array
 */
function aviator_apk_child_i18n($lang) {
	$child = aviator_apk_seo_child_row();
	if (!$child || !is_array($child)) {
		return array();
	}
	$lid = isset($lang['id']) ? (int) $lang['id'] : 1;
	if ($lid < 1) {
		$lid = 1;
	}
	if (function_exists('page_i18n_fields_current')) {
		return page_i18n_fields_current($child, $lid);
	}
	return array();
}

/**
 * Short label (e.g. demo shell banner) — from page i18n name/title.
 *
 * @param array $lang
 * @return string
 */
function aviator_apk_install_label($lang) {
	$pi = aviator_apk_child_i18n($lang);
	if (isset($pi['name']) && trim((string) $pi['name']) !== '') {
		return trim((string) $pi['name']);
	}
	if (isset($pi['title']) && trim((string) $pi['title']) !== '') {
		return trim((string) $pi['title']);
	}
	return 'Install APK';
}

/**
 * SEO + breadcrumb tail for layout demo_apk_android.
 *
 * @param array $abc
 * @param array $lang
 */
function aviator_apk_apply_page_meta(&$abc, $lang) {
	$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
	$path = preg_replace('#/+#', '/', $path === '' ? '/' : $path);

	if (!isset($abc['page_i18n']) || !is_array($abc['page_i18n'])) {
		$abc['page_i18n'] = array();
	}
	$from_child = isset($abc['apk_seo_page']) && is_array($abc['apk_seo_page']);
	$has_title = $from_child && isset($abc['page_i18n']['title']) && trim((string) $abc['page_i18n']['title']) !== '';
	if ($has_title) {
		$abc['page']['title'] = trim((string) $abc['page_i18n']['title']);
	}
	$has_desc = $from_child && isset($abc['page_i18n']['description']) && trim((string) $abc['page_i18n']['description']) !== '';
	if ($has_desc) {
		$abc['page']['description'] = trim((string) $abc['page_i18n']['description']);
	}
	$tail = '';
	if ($from_child && isset($abc['page_i18n']['name']) && trim((string) $abc['page_i18n']['name']) !== '') {
		$tail = trim((string) $abc['page_i18n']['name']);
	} elseif ($from_child && $has_title) {
		$tail = trim((string) $abc['page_i18n']['title']);
	}
	if ($tail !== '' && isset($abc['breadcrumb']) && is_array($abc['breadcrumb'])) {
		$abc['breadcrumb'][] = array(
			'name' => $tail,
			'url' => $path,
		);
	}
}

/**
 * @param array $lang
 * @return string e.g. /en/download/install-apk/
 */
function aviator_apk_install_guide_path($lang) {
	$row = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND url='download' LIMIT 1", 'row', 0);
	if (!$row || !is_array($row)) {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$path = '/' . ($lu === '' ? 'en' : $lu) . '/download/install-apk/';
		return preg_replace('#/+#', '/', $path);
	}
	$base = function_exists('get_url') ? rtrim((string) get_url('page', $row), '/') : '';
	if ($base === '') {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$base = '/' . ($lu === '' ? 'en' : $lu) . '/download';
	}
	$out = $base . '/install-apk/';
	return preg_replace('#/+#', '/', $out);
}

/**
 * @param array $abc
 * @param array $lang
 * @return string
 */
function aviator_apk_guide_url($abc, $lang) {
	$p = function_exists('aviator_apk_install_guide_path') ? aviator_apk_install_guide_path($lang) : '';
	if ($p !== '') {
		return $p;
	}
	$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return '/' . ($lu === '' ? 'en' : $lu) . '/download/install-apk/';
}

/**
 * Long-form HTML for SEO Monitor `content` (seo_cluster_v1) — same section pattern as PWA install.
 *
 * @param array $b merged bundle
 * @return string
 */
function aviator_apk_seo_cluster_content_html(array $b) {
	$imgV = (string) (isset($b['img_version']) ? $b['img_version'] : '1');
	$q = '?v=' . rawurlencode($imgV);
	$bs = '/assets/images/apk-install-android/';
	$apk = isset($b['apk_href']) ? (string) $b['apk_href'] : aviator_apk_file_href();
	$sep = "\r\n";
	$sbs = isset($b['section_steps_h2']) ? (string) $b['section_steps_h2'] : 'Step by step';
	$fc1 = !empty($b['fig1_cap']) ? (string) $b['fig1_cap'] : (string) (isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '');
	$fc2 = !empty($b['fig2_cap']) ? (string) $b['fig2_cap'] : (string) (isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '');
	$fc3 = !empty($b['fig3_cap']) ? (string) $b['fig3_cap'] : (string) (isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '');
	$a1 = isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '';
	$a2 = isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '';
	$a3 = isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '';
	$dl = isset($b['download_button']) ? $b['download_button'] : 'Download APK';
	$im1 = $bs . 'step-1-download-apk.png';
	$im2 = $bs . 'step-2-unknown-sources.png';
	$im3 = $bs . 'step-3-install-open.png';
	$out = '<h1>' . $b['h1'] . '</h1>' . $sep
		. '<p>' . $b['intro'] . '</p>' . $sep
		. '<h2>' . $b['quick_h2'] . '</h2>' . $sep
		. '<p>' . $b['quick_lead'] . '</p>' . $sep
		. '<p class="mt-3"><a class="btn btn-primary" href="' . htmlspecialchars($apk, ENT_QUOTES, 'UTF-8') . '" download="' . htmlspecialchars(AVIATOR_APK_FILE_BASENAME, ENT_QUOTES, 'UTF-8') . '">'
		. htmlspecialchars($dl, ENT_QUOTES, 'UTF-8') . '</a></p>' . $sep
		. '<h2>' . $sbs . '</h2>' . $sep
		. '<h3>' . $b['step1_title'] . '</h3>' . $sep
		. '<p>' . $b['step1_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $im1 . $q . '" border="0" alt="' . $a1 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc1 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step2_title'] . '</h3>' . $sep
		. '<p>' . $b['step2_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $im2 . $q . '" border="0" alt="' . $a2 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc2 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step3_title'] . '</h3>' . $sep
		. '<p>' . $b['step3_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $im3 . $q . '" border="0" alt="' . $a3 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc3 . '</figcaption>' . $sep
		. '</figure>' . $sep;
	return $out;
}
