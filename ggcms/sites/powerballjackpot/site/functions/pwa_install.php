<?php

/**
 * PWA install help — canonical path /{lang}/{download-slug}/install-pwa/ + dynamic manifest start_url.
 */

function pwa_install_manifest_start_path() {
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
function pwa_install_manifest_href($getV, $r) {
	$start = pwa_install_manifest_start_path();
	$q = array(
		'start' => $start,
		'v' => $getV($r . 'manifest.php'),
	);
	return '/manifest.php?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
}

function pwa_install_lang_key($lang) {
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
function pwa_install_seo_child_row(array $demo_page) {
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
function pwa_install_merge_seo_child_into_abc(&$abc, $lang) {
	if (empty($abc['page']) || !is_array($abc['page'])) {
		return;
	}
	$child = pwa_install_seo_child_row($abc['page']);
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
function pwa_install_child_i18n($lang) {
	$child = pwa_install_seo_child_row(array());
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
function pwa_install_label($lang) {
	$pi = pwa_install_child_i18n($lang);
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
function pwa_install_apply_page_meta(&$abc, $lang) {
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
function demo_app_shell_url($abc, $lang) {
	$base = '';
	if (function_exists('get_url') && !empty($abc['page']) && is_array($abc['page'])) {
		$demo_row = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND url='demo' LIMIT 1", 'row', 0);
		if ($demo_row && is_array($demo_row)) {
			$base = rtrim((string) get_url('page', $demo_row), '/');
		}
	}
	if ($base === '' && function_exists('get_url') && !empty($abc['page']) && is_array($abc['page'])) {
		$base = rtrim((string) get_url('page', $abc['page']), '/');
	}
	if ($base === '') {
		$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
		$base = '/' . ($lu === '' ? 'en' : $lu) . '/demo';
	}
	return $base . '/app/';
}

/**
 * Absolute URL to /{lang}/demo/app/ on this site (install-pwa quick path link).
 */
function pwa_install_demo_app_absolute_url($abc, $lang) {
	$path = demo_app_shell_url($abc, $lang);
	if (function_exists('site_seo_public_origin')) {
		$origin = site_seo_public_origin();
		if ($origin !== '') {
			return rtrim($origin, '/') . $path;
		}
	}
	$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	if ($host === '') {
		return $path;
	}
	return $scheme . '://' . $host . $path;
}

/**
 * DB copy may still point at aviator-log-in.com/demo/app — rewrite to the current host + locale.
 *
 * @param string $html
 * @return string
 */
/**
 * Cache-bust install-pwa step screenshots after replacing files on disk.
 * DB exports may still reference legacy .jpg paths.
 *
 * @param string $html
 * @return string
 */
function pwa_install_bust_ios_image_cache($html) {
	if ($html === '' || strpos($html, 'pwa-install-ios') === false) {
		return $html;
	}
	$stems = array(
		'step-1-share-sheet',
		'step-2-add-to-home-screen',
		'step-3-open-as-web-app',
	);
	$v = 0;
	if (defined('ROOT_DIR')) {
		foreach ($stems as $stem) {
			$f = ROOT_DIR . 'assets/images/pwa-install-ios/' . $stem . '.webp';
			if (@file_exists($f)) {
				$v = max($v, (int) filemtime($f));
			}
		}
	}
	if ($v <= 0) {
		return $html;
	}
	$q = '?v=' . $v;
	$base = '/assets/images/pwa-install-ios/';
	foreach ($stems as $stem) {
		$target = $base . $stem . '.webp';
		foreach (array('.jpg', '.jpeg', '.webp') as $ext) {
			$path = $base . $stem . $ext;
			$html = preg_replace(
				'#' . preg_quote($path, '#') . '(?:\?v=[^"\'>\s]*)?#',
				$target . $q,
				$html
			);
		}
	}
	return $html;
}

/**
 * CTA label from quick_lead — text before the demo/app link.
 *
 * @param string $lead_html
 * @return string
 */
function pwa_install_quick_path_cta_label($lead_html) {
	$html = (string) $lead_html;
	if ($html !== '' && preg_match('#^(.+?)<a\b#is', $html, $m)) {
		$t = trim(strip_tags($m[1]));
		$t = rtrim($t, " \t\n\r\0\x0B—–-.,;");
		if ($t !== '') {
			return $t;
		}
	}
	$text = trim(strip_tags($html));
	if ($text !== '' && preg_match('/^(.+?)\s+[—–\-]\s+/u', $text, $m)) {
		return trim($m[1]);
	}
	return 'Open demo in Safari';
}

/**
 * Short hint after the demo link (e.g. "Then follow the steps below…").
 *
 * @param string $lead_html
 * @return string
 */
function pwa_install_quick_path_hint($lead_html) {
	if (preg_match('#</a>(.+)$#is', (string) $lead_html, $m)) {
		$t = trim(strip_tags($m[1]));
		$t = ltrim($t, '. ');
		return $t;
	}
	return '';
}

/**
 * Demo shell URL from quick_lead anchor or runtime helpers.
 *
 * @param string $lead_html
 * @param array $abc
 * @param array $lang
 * @return string
 */
function pwa_install_extract_demo_url_from_lead($lead_html, $abc, $lang) {
	if (preg_match('#href=["\']([^"\']*demo/app/?[^"\']*)["\']#i', (string) $lead_html, $m)) {
		return $m[1];
	}
	return pwa_install_demo_app_absolute_url($abc, $lang);
}

/**
 * Localized copy-link button labels for the PWA quick-path hero.
 *
 * @param array $lang
 * @return array{copy:string,copied:string}
 */
function pwa_install_copy_button_labels($lang) {
	$key = pwa_install_lang_key($lang);
	$map = array(
		'en' => array('copy' => 'Copy link', 'copied' => 'Copied!'),
		'fr' => array('copy' => 'Copier le lien', 'copied' => 'Copié !'),
		'de' => array('copy' => 'Link kopieren', 'copied' => 'Kopiert!'),
		'es' => array('copy' => 'Copiar enlace', 'copied' => '¡Copiado!'),
		'pt' => array('copy' => 'Copiar link', 'copied' => 'Copiado!'),
		'ru' => array('copy' => 'Копировать ссылку', 'copied' => 'Скопировано!'),
		'it' => array('copy' => 'Copia link', 'copied' => 'Copiato!'),
		'pl' => array('copy' => 'Kopiuj link', 'copied' => 'Skopiowano!'),
		'uk' => array('copy' => 'Копіювати посилання', 'copied' => 'Скопійовано!'),
		'nl' => array('copy' => 'Link kopiëren', 'copied' => 'Gekopieerd!'),
		'ro' => array('copy' => 'Copiază linkul', 'copied' => 'Copiat!'),
		'hi' => array('copy' => 'लिंक कॉपी करें', 'copied' => 'कॉपी हो गया!'),
		'ar' => array('copy' => 'نسخ الرابط', 'copied' => 'تم النسخ!'),
		'bn' => array('copy' => 'লিংক কপি করুন', 'copied' => 'কপি হয়েছে!'),
		'vi' => array('copy' => 'Sao chép liên kết', 'copied' => 'Đã sao chép!'),
		'az' => array('copy' => 'Linki kopyala', 'copied' => 'Kopyalandı!'),
	);
	return isset($map[$key]) ? $map[$key] : $map['en'];
}

/**
 * Prominent quick-path card — injected right after H1.
 *
 * @param string $title
 * @param string $hint
 * @param string $demo_url
 * @param array|null $copy_labels
 * @return string
 */
function pwa_install_quick_path_hero_markup($title, $hint, $demo_url, $copy_labels = null) {
	$title = trim((string) $title);
	$hint = trim((string) $hint);
	$demo_url = trim((string) $demo_url);
	if (!is_array($copy_labels)) {
		$copy_labels = array('copy' => 'Copy link', 'copied' => 'Copied!');
	}
	$out = '<div class="pwa-ios-quick pwa-ios-quick--hero">';
	if ($title !== '') {
		$out .= '<h2 class="pwa-ios-quick__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
	}
	if ($hint !== '') {
		$out .= '<p class="pwa-ios-quick__hint">' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	if ($demo_url !== '') {
		$esc_url = htmlspecialchars($demo_url, ENT_QUOTES, 'UTF-8');
		$copy_l = htmlspecialchars((string) $copy_labels['copy'], ENT_QUOTES, 'UTF-8');
		$copied_l = htmlspecialchars((string) $copy_labels['copied'], ENT_QUOTES, 'UTF-8');
		$out .= '<div class="pwa-ios-quick__url-block">';
		$out .= '<a class="pwa-ios-quick__url" href="' . $esc_url . '">' . $esc_url . '</a>';
		$out .= '<button type="button" class="pwa-ios-quick__copy" data-copy-url="' . $esc_url . '"'
			. ' data-copy-label="' . $copy_l . '" data-copied-label="' . $copied_l . '"'
			. ' aria-label="' . $copy_l . '">';
		$out .= '<span class="pwa-ios-quick__copy-icon" aria-hidden="true"></span>';
		$out .= '<span class="pwa-ios-quick__copy-label">' . $copy_l . '</span>';
		$out .= '</button></div>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Move "Fastest path" (quick_h2 + quick_lead) to a hero card right after H1.
 *
 * @param string $html
 * @param array $abc
 * @param array $lang
 * @return string
 */
function pwa_install_enhance_quick_path($html, $abc, $lang) {
	$html = (string) $html;
	if ($html === '' || stripos($html, 'pwa-ios-quick--hero') !== false) {
		return $html;
	}
	if (!preg_match('#<h1\b[^>]*>.*?</h1>#is', $html, $h1m, PREG_OFFSET_CAPTURE)) {
		return $html;
	}
	$quick_pattern = '#<h2\b[^>]*>(.*?)</h2>\s*<p\b[^>]*>((?:(?!</p>).)*demo/app(?:(?!</p>).)*)</p>#is';
	$title = 'Fastest path';
	$hint = '';
	$demo_url = pwa_install_demo_app_absolute_url($abc, $lang);
	if (preg_match($quick_pattern, $html, $qm)) {
		$title = trim(strip_tags($qm[1]));
		if ($title === '') {
			$title = 'Fastest path';
		}
		$lead = $qm[2];
		$hint = pwa_install_quick_path_hint($lead);
		$demo_url = pwa_install_extract_demo_url_from_lead($lead, $abc, $lang);
		$html = preg_replace($quick_pattern, '', $html, 1);
	}
	$hero = pwa_install_quick_path_hero_markup($title, $hint, $demo_url, pwa_install_copy_button_labels($lang));
	$pos = $h1m[0][1] + strlen($h1m[0][0]);
	return substr($html, 0, $pos) . $hero . substr($html, $pos);
}

function pwa_install_normalize_demo_links_in_content($html, $abc, $lang) {
	if ($html === '' || stripos($html, 'demo/app') === false) {
		return $html;
	}
	$url = pwa_install_demo_app_absolute_url($abc, $lang);
	$esc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
	$hosts = array();
	if (function_exists('site_brand_profile_value')) {
		$from_hosts = site_brand_profile_value('rebrand_from_hosts', array());
		if (is_array($from_hosts)) {
			foreach (array_keys($from_hosts) as $h) {
				$h = (string) $h;
				if ($h !== '') {
					$hosts[] = $h;
					if (strpos($h, 'www.') !== 0) {
						$hosts[] = 'www.' . $h;
					}
				}
			}
		}
	}
	$hosts = array_values(array_unique(array_filter($hosts)));
	if (!$hosts) {
		$hosts = array('aviator-log-in.com', 'www.aviator-log-in.com');
	}
	foreach ($hosts as $host) {
		$html = preg_replace(
			'#https?://' . preg_quote($host, '#') . '/[a-z]{2}(?:-[a-z]{2})?/demo/app/?#i',
			$esc,
			$html
		);
	}
	return $html;
}

/**
 * Canonical path to the install guide, e.g. /en/download/install-pwa/ (trailing slash).
 *
 * @param array $lang
 * @return string
 */
function pwa_install_guide_path($lang) {
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
function pwa_install_guide_url($abc, $lang) {
	$p = function_exists('pwa_install_guide_path') ? pwa_install_guide_path($lang) : '';
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
function pwa_install_seo_cluster_content_html(array $b) {
	$imgV = 0;
	$names = array('step-1-share-sheet.webp', 'step-2-add-to-home-screen.webp', 'step-3-open-as-web-app.webp');
	if (defined('ROOT_DIR')) {
		foreach ($names as $name) {
			$f = ROOT_DIR . 'assets/images/pwa-install-ios/' . $name;
			if (@file_exists($f)) {
				$imgV = max($imgV, (int) filemtime($f));
			}
		}
	}
	if ($imgV <= 0) {
		$imgV = time();
	}
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
	$quick_lead = isset($b['quick_lead']) ? (string) $b['quick_lead'] : '';
	$quick_h2 = isset($b['quick_h2']) ? (string) $b['quick_h2'] : 'Fastest path';
	$demo_url = pwa_install_extract_demo_url_from_lead($quick_lead, array(), array('url' => 'en'));
	$hero = pwa_install_quick_path_hero_markup(
		$quick_h2,
		pwa_install_quick_path_hint($quick_lead),
		$demo_url
	);
	return '<h1>' . $b['h1'] . '</h1>' . $sep
		. $hero . $sep
		. '<p class="pwa-ios-trust-intro">' . $b['intro'] . '</p>' . $sep
		. '<h2>' . $sbs . '</h2>' . $sep
		. '<h3>' . $b['step1_title'] . '</h3>' . $sep
		. '<p>' . $b['step1_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-1-share-sheet.webp' . $q . '" border="0" alt="' . $a1 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc1 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step2_title'] . '</h3>' . $sep
		. '<p>' . $b['step2_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-2-add-to-home-screen.webp' . $q . '" border="0" alt="' . $a2 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc2 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. '<h3>' . $b['step3_title'] . '</h3>' . $sep
		. '<p>' . $b['step3_body'] . '</p>' . $sep
		. '<figure class="text-center my-3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-3-open-as-web-app.webp' . $q . '" border="0" alt="' . $a3 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc3 . '</figcaption>' . $sep
		. '</figure>' . $sep;
}
