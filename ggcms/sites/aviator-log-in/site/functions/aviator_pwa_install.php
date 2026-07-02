<?php

/**
 * PWA install help — canonical path /{lang}/{download-slug}/install-pwa/ + dynamic manifest start_url.
 */

function aviator_pwa_manifest_start_path() {
	$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = parse_url($uri, PHP_URL_PATH);
	if (!is_string($path) || $path === '') {
		$path = '/';
	}
	$path = preg_replace('#/+#', '/', $path);
	if ($path === '' || $path[0] !== '/') {
		$path = '/' . ltrim($path, '/');
	}
	if ($path !== '/' && substr($path, -1) !== '/') {
		$path .= '/';
	}
	return $path;
}

/**
 * @param callable $getV filemtime helper from template
 * @param string $r ROOT_DIR with trailing slash
 */
function aviator_pwa_manifest_href($getV, $r) {
	$start = aviator_pwa_manifest_start_path();
	$q = array(
		'start' => $start,
		'v' => $getV($r . 'manifest.php'),
	);
	return '/manifest.php?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}

function aviator_pwa_ios_lang_key($lang) {
	$u = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return $u === '' ? 'en' : $u;
}

/**
 * `pages` row for SEO Monitor + content_i18n for the iOS PWA guide.
 * Public URL: /{lang}/{download-slug}/install-pwa/ — `pages` row for SEO/JSON: `url` = install-pwa (preferred) or ios-pwa (legacy).
 *
 * @param array $demo_page Current routed page row (Demo); kept for call-site compatibility, not used for SQL.
 * @return array|null
 */
function aviator_pwa_ios_seo_child_row(array $demo_page) {
	if (!function_exists('mysql_select')) {
		return null;
	}
	$row = mysql_select(
		"SELECT * FROM pages WHERE display=1 AND module='pages' AND url='install-pwa' LIMIT 1",
		'row',
		0
	);
	if (!$row || !is_array($row)) {
		$row = mysql_select(
			"SELECT * FROM pages WHERE display=1 AND module='pages' AND url='ios-pwa' LIMIT 1",
			'row',
			0
		);
	}

	return ($row && is_array($row)) ? $row : null;
}

/**
 * When layout is demo_pwa_ios: load SEO child row, replace page_i18n for head/meta, extend hreflang path.
 *
 * @param array $abc
 * @param array $lang
 */
function aviator_pwa_ios_merge_seo_child_into_abc(&$abc, $lang) {
	if (empty($abc['page']) || !is_array($abc['page'])) {
		return;
	}
	$child = aviator_pwa_ios_seo_child_row($abc['page']);
	if (!$child) {
		return;
	}
	$abc['pwa_ios_seo_page'] = $child;
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
				$abc['links'][$lk][] = 'install-pwa';
			}
		}
	}
}

/**
 * Localized install-pwa child row fields for the current language.
 *
 * @param array $lang
 * @return array
 */
function aviator_pwa_ios_child_i18n($lang) {
	$child = aviator_pwa_ios_seo_child_row(array());
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
 * Short label for the install guide button in the demo-app shell.
 *
 * @param array $lang
 * @return string
 */
function aviator_pwa_ios_install_label($lang) {
	$pi = aviator_pwa_ios_child_i18n($lang);
	if (isset($pi['name']) && trim((string) $pi['name']) !== '') {
		return trim((string) $pi['name']);
	}
	if (isset($pi['title']) && trim((string) $pi['title']) !== '') {
		return trim((string) $pi['title']);
	}
	return 'Install on iPhone';
}

/**
 * SEO + breadcrumb tail for layout demo_pwa_ios.
 *
 * @param array $abc
 * @param array $lang
 */
function aviator_pwa_ios_apply_page_meta(&$abc, $lang) {
	$pi = isset($abc['page_i18n']) && is_array($abc['page_i18n']) ? $abc['page_i18n'] : null;
	$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
	$path = preg_replace('#/+#', '/', $path === '' ? '/' : $path);

	if (!isset($abc['page_i18n']) || !is_array($abc['page_i18n'])) {
		$abc['page_i18n'] = array();
	}
	$from_child = isset($abc['pwa_ios_seo_page']) && is_array($abc['pwa_ios_seo_page']);
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
 * URL for fullscreen demo app shell (/{lang}/demo/app/).
 */
function aviator_demo_app_shell_url($abc, $lang) {
	$base = '';
	if (function_exists('get_url') && !empty($abc['page']) && is_array($abc['page'])) {
		$base = rtrim((string) get_url('page', $abc['page']), '/');
	}
	if ($base === '') {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$base = '/' . ($lu === '' ? 'en' : $lu) . '/demo';
	}
	return $base . '/app/';
}

/**
 * Canonical path to the install guide, e.g. /en/download/install-pwa/ (trailing slash).
 *
 * @param array $lang
 * @return string
 */
function aviator_pwa_install_guide_path($lang) {
	$row = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND url='download' LIMIT 1", 'row', 0);
	if (!$row || !is_array($row)) {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$path = '/' . ($lu === '' ? 'en' : $lu) . '/download/install-pwa/';
		return preg_replace('#/+#', '/', $path);
	}
	$base = function_exists('get_url') ? rtrim((string) get_url('page', $row), '/') : '';
	if ($base === '') {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$base = '/' . ($lu === '' ? 'en' : $lu) . '/download';
	}
	$out = $base . '/install-pwa/';
	return preg_replace('#/+#', '/', $out);
}

/**
 * URL for install guide (/{lang}/{download-slug}/install-pwa/).
 *
 * @param array $abc
 * @param array $lang
 * @return string
 */
function aviator_pwa_ios_guide_url($abc, $lang) {
	$p = function_exists('aviator_pwa_install_guide_path') ? aviator_pwa_install_guide_path($lang) : '';
	if ($p !== '') {
		return $p;
	}
	$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return '/' . ($lu === '' ? 'en' : $lu) . '/download/install-pwa/';
}

/**
 * Long-form HTML for SEO Monitor `content` (seo_cluster_v1) — same DOM skeleton as the EN install-pwa page.
 * $b = merged bundle (en + locale overrides) including section_steps_h2, stepN_img_alt, cta_*, h1, intro, etc.
 *
 * @param array $b
 * @return string
 */
function aviator_pwa_seo_cluster_content_html(array $b) {
	$imgV = '1776941839';
	$q = '?v=' . $imgV;
	$bs = '/assets/images/pwa-install-ios/';
	$sep = "\r\n";
	$sbs = isset($b['section_steps_h2']) ? (string) $b['section_steps_h2'] : 'Step by step';
	$fc1 = !empty($b['fig1_cap']) ? (string) $b['fig1_cap'] : (string) (isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '');
	$fc2 = !empty($b['fig2_cap']) ? (string) $b['fig2_cap'] : (string) (isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '');
	$fc3 = !empty($b['fig3_cap']) ? (string) $b['fig3_cap'] : (string) (isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '');
	$a1 = isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '';
	$a2 = isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '';
	$a3 = isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '';
	return '<h1>' . $b['h1'] . '</h1>' . $sep
		. '<p>' . $b['intro'] . '</p>' . $sep
		. '<h2>' . $b['quick_h2'] . '</h2>' . $sep
		. '<p>' . $b['quick_lead'] . '</p>' . $sep
		. '<h2>' . $sbs . '</h2>' . $sep
		. '<h3>' . $b['step1_title'] . '</h3>' . $sep
		. '<p>' . $b['step1_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-1-share-sheet.jpg' . $q . '" border="0" alt="' . $a1 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc1 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step2_title'] . '</h3>' . $sep
		. '<p>' . $b['step2_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-2-add-to-home-screen.jpg' . $q . '" border="0" alt="' . $a2 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc2 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step3_title'] . '</h3>' . $sep
		. '<p>' . $b['step3_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-3-open-as-web-app.jpg' . $q . '" border="0" alt="' . $a3 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc3 . '</figcaption>' . $sep
		. '</figure>' . $sep;
}
