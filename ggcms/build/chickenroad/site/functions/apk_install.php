<?php

/**
 * Android APK install guide — /{lang}/{download-slug}/install-apk/
 * Mirrors PWA iOS page wiring (separate `pages` row for SEO, layout demo_apk_android).
 */

function apk_install_file_basename() {
	return function_exists('site_brand_apk_basename') ? site_brand_apk_basename() : 'app.apk';
}

/**
 * Public URL to the APK in site/files/ (served as static file when deployed).
 */
function apk_install_file_href() {
	return '/files/' . apk_install_file_basename();
}

/**
 * Point DB/export HTML at the stable filename (e.g. legacy aviator_v1.apk → aviator.apk).
 *
 * @param string $html
 * @return string
 */
function apk_install_normalize_apk_link_in_content($html) {
	if ($html === '' || strpos($html, '/files/') === false) {
		return $html;
	}
	$href = apk_install_file_href();
	$name = apk_install_file_basename();
	$legacy = array('aviator_v1.apk', 'aviator.apk', 'chickenroad.apk', 'ice-fish.apk', 'powerballjackpot.apk');
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
function apk_install_replace_placeholder_step_images($html) {
	if ($html === '' || strpos($html, 'apk-install-android') === false) {
		return $html;
	}
	$steps = array(
		'/assets/images/apk-install-android/step-1-download-apk.webp',
		'/assets/images/apk-install-android/step-2-unknown-sources.webp',
		'/assets/images/apk-install-android/step-3-install-open.webp',
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
 * Cache-bust install-apk step screenshots after replacing files on disk.
 * DB exports may still reference legacy .png paths.
 *
 * @param string $html
 * @return string
 */
function apk_install_bust_android_image_cache($html) {
	if ($html === '' || strpos($html, 'apk-install-android') === false) {
		return $html;
	}
	$stems = array(
		'step-1-download-apk',
		'step-2-unknown-sources',
		'step-3-install-open',
	);
	$v = 0;
	if (defined('ROOT_DIR')) {
		foreach ($stems as $stem) {
			$f = ROOT_DIR . 'assets/images/apk-install-android/' . $stem . '.webp';
			if (@file_exists($f)) {
				$v = max($v, (int) filemtime($f));
			}
		}
	}
	if ($v <= 0) {
		return $html;
	}
	$q = '?v=' . $v;
	$base = '/assets/images/apk-install-android/';
	foreach ($stems as $stem) {
		$target = $base . $stem . '.webp';
		foreach (array('.png', '.webp') as $ext) {
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
 * @param array $lang
 * @return string
 */
function apk_install_lang_key($lang) {
	$u = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	if ($u === 'ua') {
		return 'uk';
	}
	return $u === '' ? 'en' : $u;
}

/**
 * `pages` row for install-apk (SEO + content_i18n).
 *
 * @return array|null
 */
function apk_install_seo_child_row() {
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
function apk_install_merge_seo_child_into_abc(&$abc, $lang) {
	if (empty($abc['page']) || !is_array($abc['page'])) {
		return;
	}
	$child = apk_install_seo_child_row();
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
function apk_install_child_i18n($lang) {
	$child = apk_install_seo_child_row();
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
function apk_install_label($lang) {
	$pi = apk_install_child_i18n($lang);
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
function apk_install_apply_page_meta(&$abc, $lang) {
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
function apk_install_guide_path($lang) {
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
function apk_install_guide_url($abc, $lang) {
	$p = function_exists('apk_install_guide_path') ? apk_install_guide_path($lang) : '';
	if ($p !== '') {
		return $p;
	}
	$lu = isset($lang['url']) ? trim((string) $lang['url'], '/') : 'en';
	return '/' . ($lu === '' ? 'en' : $lu) . '/download/install-apk/';
}

/**
 * Branded APK download CTA (uses site `.main_btn` styles).
 *
 * @param string $label
 * @param string $modifier extra class, e.g. apk-install-inline-cta
 * @return string
 */
function apk_install_hero_cta_markup($label, $modifier = '', $id = '') {
	$label = trim((string) $label);
	if ($label === '') {
		$label = 'Download APK';
	}
	$href = apk_install_file_href();
	$name = apk_install_file_basename();
	$cls = 'apk-install-hero-cta main_btn';
	if ($modifier === 'apk-install-inline-cta') {
		$cls = 'apk-install-inline-cta main_btn';
	} elseif ($modifier !== '') {
		$cls .= ' ' . trim((string) $modifier);
	}
	$id_attr = trim((string) $id) !== ''
		? (' id="' . htmlspecialchars(trim((string) $id), ENT_QUOTES, 'UTF-8') . '"')
		: '';
	return '<div class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '"' . $id_attr . '>'
		. '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" download="'
		. htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">'
		. htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></div>';
}

/**
 * Extract localized download label from page HTML (first APK link).
 *
 * @param string $html
 * @return string
 */
function apk_install_download_button_label($html) {
	$href = preg_quote(apk_install_file_href(), '#');
	if (preg_match('#<a[^>]+href=["\']' . $href . '["\'][^>]*>(.*?)</a>#is', (string) $html, $m)) {
		$t = trim(strip_tags($m[1]));
		if ($t !== '') {
			return $t;
		}
	}
	return 'Download APK';
}

/**
 * Inject prominent download CTA right after H1; restyle legacy Bootstrap buttons.
 *
 * @param string $html
 * @return string
 */
function apk_install_enhance_download_ctas($html) {
	$html = (string) $html;
	if ($html === '') {
		return $html;
	}
	$html = apk_install_style_legacy_download_buttons($html);
	if (stripos($html, 'apk-install-hero-cta') === false && preg_match('#<h1\b[^>]*>.*?</h1>#is', $html, $m, PREG_OFFSET_CAPTURE)) {
		$cta = apk_install_hero_cta_markup(apk_install_download_button_label($html), '', 'apk-download-hero');
		$pos = $m[0][1] + strlen($m[0][0]);
		$html = substr($html, 0, $pos) . $cta . substr($html, $pos);
	} elseif (stripos($html, 'id="apk-download-hero"') === false && preg_match('#<div class="apk-install-hero-cta\b#', $html)) {
		$html = preg_replace(
			'#(<div class="apk-install-hero-cta\b[^"]*")#',
			'$1 id="apk-download-hero"',
			$html,
			1
		);
	}
	return $html;
}

/**
 * Replace small Bootstrap `.btn-primary` APK links with branded `.main_btn` blocks.
 *
 * @param string $html
 * @return string
 */
function apk_install_style_legacy_download_buttons($html) {
	$href = preg_quote(apk_install_file_href(), '#');
	$name = preg_quote(apk_install_file_basename(), '#');
	return preg_replace_callback(
		'#<p class="mt-3">\s*<a class="btn btn-primary" href="(' . $href . ')" download="(' . $name . ')">([^<]+)</a>\s*</p>#i',
		function ($m) {
			return apk_install_hero_cta_markup(trim($m[3]), 'apk-install-inline-cta');
		},
		$html
	);
}

/**
 * Long-form HTML for SEO Monitor `content` (seo_cluster_v1) — same section pattern as PWA install.
 *
 * @param array $b merged bundle
 * @return string
 */
function apk_install_seo_cluster_content_html(array $b) {
	$imgV = (string) (isset($b['img_version']) ? $b['img_version'] : '1');
	$q = '?v=' . rawurlencode($imgV);
	$bs = '/assets/images/apk-install-android/';
	$apk = isset($b['apk_href']) ? (string) $b['apk_href'] : apk_install_file_href();
	$sep = "\r\n";
	$sbs = isset($b['section_steps_h2']) ? (string) $b['section_steps_h2'] : 'Step by step';
	$fc1 = !empty($b['fig1_cap']) ? (string) $b['fig1_cap'] : (string) (isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '');
	$fc2 = !empty($b['fig2_cap']) ? (string) $b['fig2_cap'] : (string) (isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '');
	$fc3 = !empty($b['fig3_cap']) ? (string) $b['fig3_cap'] : (string) (isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '');
	$a1 = isset($b['step1_img_alt']) ? $b['step1_img_alt'] : '';
	$a2 = isset($b['step2_img_alt']) ? $b['step2_img_alt'] : '';
	$a3 = isset($b['step3_img_alt']) ? $b['step3_img_alt'] : '';
	$dl = isset($b['download_button']) ? $b['download_button'] : 'Download APK';
	$im1 = $bs . 'step-1-download-apk.webp';
	$im2 = $bs . 'step-2-unknown-sources.webp';
	$im3 = $bs . 'step-3-install-open.webp';
	$out = '<h1>' . $b['h1'] . '</h1>' . $sep
		. apk_install_hero_cta_markup($dl) . $sep
		. '<p>' . $b['intro'] . '</p>' . $sep
		. '<h2>' . $b['quick_h2'] . '</h2>' . $sep
		. '<p>' . $b['quick_lead'] . '</p>' . $sep
		. apk_install_hero_cta_markup($dl, 'apk-install-inline-cta') . $sep
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

/**
 * Default guided-tour UI strings for install-apk pages (runtime fallback).
 *
 * @param string $key
 * @return array
 */
function apk_install_ui_defaults($key) {
	$nav = function_exists('pwa_install_ui_defaults') ? pwa_install_ui_defaults($key) : array();
	$maps = array(
		'en' => array(
			'tour_cta' => 'How to install APK?',
			'tour_sub' => '3 steps · about 1 minute',
			'tour_finish_title' => 'Ready — download the APK',
			'tour_finish_lead' => 'Use the Download APK button at the top of this page to save the file.',
			'tour_finish_locked_hint' => 'Complete the 3-step tour — then download the APK above',
		),
		'ru' => array(
			'tour_cta' => 'Как установить APK?',
			'tour_sub' => '3 шага · около 1 минуты',
			'tour_finish_title' => 'Готово — скачайте APK',
			'tour_finish_lead' => 'Нажмите «Скачать APK» вверху страницы, чтобы сохранить файл.',
			'tour_finish_locked_hint' => 'Пройдите тур из 3 шагов — затем скачайте APK выше',
		),
		'fr' => array(
			'tour_cta' => 'Comment installer l\'APK ?',
			'tour_sub' => '3 étapes · environ 1 minute',
			'tour_finish_title' => 'Prêt — téléchargez l\'APK',
			'tour_finish_lead' => 'Utilisez le bouton Télécharger l\'APK en haut de cette page.',
			'tour_finish_locked_hint' => 'Terminez le tour en 3 étapes — puis téléchargez l\'APK ci-dessus',
		),
		'de' => array(
			'tour_cta' => 'APK installieren?',
			'tour_sub' => '3 Schritte · etwa 1 Minute',
			'tour_finish_title' => 'Bereit — APK herunterladen',
			'tour_finish_lead' => 'Nutzen Sie oben auf der Seite die Schaltfläche APK herunterladen.',
			'tour_finish_locked_hint' => 'Schließen Sie die 3-Schritte-Tour ab — dann APK oben laden',
		),
		'es' => array(
			'tour_cta' => '¿Cómo instalar el APK?',
			'tour_sub' => '3 pasos · alrededor de 1 minuto',
			'tour_finish_title' => 'Listo — descarga el APK',
			'tour_finish_lead' => 'Usa el botón Descargar APK en la parte superior de esta página.',
			'tour_finish_locked_hint' => 'Completa el tour de 3 pasos — luego descarga el APK arriba',
		),
		'pt' => array(
			'tour_cta' => 'Como instalar o APK?',
			'tour_sub' => '3 passos · cerca de 1 minuto',
			'tour_finish_title' => 'Pronto — baixe o APK',
			'tour_finish_lead' => 'Use o botão Baixar APK no topo desta página.',
			'tour_finish_locked_hint' => 'Conclua o tour de 3 passos — depois baixe o APK acima',
		),
		'it' => array(
			'tour_cta' => 'Come installare l\'APK?',
			'tour_sub' => '3 passaggi · circa 1 minuto',
			'tour_finish_title' => 'Pronto — scarica l\'APK',
			'tour_finish_lead' => 'Usa il pulsante Scarica APK in cima a questa pagina.',
			'tour_finish_locked_hint' => 'Completa il tour in 3 passaggi — poi scarica l\'APK sopra',
		),
		'pl' => array(
			'tour_cta' => 'Jak zainstalować APK?',
			'tour_sub' => '3 kroki · około 1 minuty',
			'tour_finish_title' => 'Gotowe — pobierz APK',
			'tour_finish_lead' => 'Użyj przycisku Pobierz APK u góry tej strony.',
			'tour_finish_locked_hint' => 'Ukończ 3-krokową trasę — potem pobierz APK powyżej',
		),
		'uk' => array(
			'tour_cta' => 'Як установити APK?',
			'tour_sub' => '3 кроки · близько 1 хвилини',
			'tour_finish_title' => 'Готово — завантажте APK',
			'tour_finish_lead' => 'Натисніть «Завантажити APK» у верхній частині сторінки.',
			'tour_finish_locked_hint' => 'Пройдіть тур із 3 кроків — потім завантажте APK вище',
		),
		'nl' => array(
			'tour_cta' => 'APK installeren?',
			'tour_sub' => '3 stappen · ongeveer 1 minuut',
			'tour_finish_title' => 'Klaar — download de APK',
			'tour_finish_lead' => 'Gebruik de knop APK downloaden bovenaan deze pagina.',
			'tour_finish_locked_hint' => 'Voltooi de tour van 3 stappen — download daarna de APK hierboven',
		),
		'ro' => array(
			'tour_cta' => 'Cum instalez APK-ul?',
			'tour_sub' => '3 pași · circa 1 minut',
			'tour_finish_title' => 'Gata — descarcă APK-ul',
			'tour_finish_lead' => 'Folosește butonul Descarcă APK de sus pe această pagină.',
			'tour_finish_locked_hint' => 'Finalizează turul în 3 pași — apoi descarcă APK-ul de mai sus',
		),
		'hi' => array(
			'tour_cta' => 'APK कैसे इंस्टॉल करें?',
			'tour_sub' => '3 स्टेप · लगभग 1 मिनट',
			'tour_finish_title' => 'तैयार — APK डाउनलोड करें',
			'tour_finish_lead' => 'फ़ाइल सेव करने के लिए पेज के ऊपर Download APK बटन दबाएँ।',
			'tour_finish_locked_hint' => '3-स्टेप टूर पूरा करें — फिर ऊपर APK डाउनलोड करें',
		),
		'ar' => array(
			'tour_cta' => 'كيف أثبّت APK؟',
			'tour_sub' => '3 خطوات · حوالي دقيقة',
			'tour_finish_title' => 'جاهز — حمّل APK',
			'tour_finish_lead' => 'استخدم زر تنزيل APK في أعلى هذه الصفحة.',
			'tour_finish_locked_hint' => 'أكمل الجولة من 3 خطوات — ثم حمّل APK أعلاه',
		),
		'bn' => array(
			'tour_cta' => 'APK কীভাবে ইনস্টল করবেন?',
			'tour_sub' => '3 ধাপ · প্রায় 1 মিনিট',
			'tour_finish_title' => 'প্রস্তুত — APK ডাউনলোড করুন',
			'tour_finish_lead' => 'ফাইল সেভ করতে পেজের উপরে Download APK বোতামে ট্যাপ করুন।',
			'tour_finish_locked_hint' => '3-ধাপের ট্যুর শেষ করুন — তারপর উপরে APK ডাউনলোড করুন',
		),
		'vi' => array(
			'tour_cta' => 'Cách cài APK?',
			'tour_sub' => '3 bước · khoảng 1 phút',
			'tour_finish_title' => 'Sẵn sàng — tải APK',
			'tour_finish_lead' => 'Dùng nút Tải APK ở đầu trang để lưu tệp.',
			'tour_finish_locked_hint' => 'Hoàn thành tour 3 bước — rồi tải APK phía trên',
		),
		'az' => array(
			'tour_cta' => 'APK necə quraşdırım?',
			'tour_sub' => '3 addım · təxminən 1 dəqiqə',
			'tour_finish_title' => 'Hazırsınız — APK endirin',
			'tour_finish_lead' => 'Faylı saxlamaq üçün səhifənin yuxarısındakı APK endir düyməsindən istifadə edin.',
			'tour_finish_locked_hint' => '3 addımlı turu bitirin — sonra yuxarıdakı APK-nı endirin',
		),
	);
	$over = isset($maps[$key]) ? $maps[$key] : $maps['en'];
	return array_merge($nav, $over);
}

/**
 * Runtime UI strings for install-apk guided tour.
 *
 * @param array $lang
 * @return array
 */
function apk_install_ui_strings($lang) {
	$lk = apk_install_lang_key($lang);
	$defaults = apk_install_ui_defaults($lk);
	$from_file = array();
	if (defined('ROOT_DIR')) {
		$file = ROOT_DIR . 'files/i18n/apk-android-install.php';
		if (@is_file($file)) {
			$all = include $file;
			if (is_array($all)) {
				if ($lk === 'en' && isset($all['en']) && is_array($all['en'])) {
					$from_file = $all['en'];
				} elseif (isset($all[$lk]) && is_array($all[$lk])) {
					$from_file = $all[$lk];
				}
			}
		}
	}
	$keys = array(
		'tour_cta', 'tour_sub', 'tour_next', 'tour_back', 'tour_done', 'tour_step_of',
		'tour_finish_title', 'tour_finish_lead', 'tour_finish_locked_hint',
	);
	$en_defaults = apk_install_ui_defaults('en');
	$out = array();
	foreach ($keys as $k) {
		if (!empty($from_file[$k])) {
			$out[$k] = (string) $from_file[$k];
		} elseif (isset($defaults[$k]) && (string) $defaults[$k] !== '') {
			$out[$k] = (string) $defaults[$k];
		} elseif (isset($en_defaults[$k])) {
			$out[$k] = (string) $en_defaults[$k];
		} else {
			$out[$k] = '';
		}
	}
	return $out;
}

/**
 * Inline tour-start block (replaces the smaller Download APK button in the quick section).
 *
 * @param array $ui
 * @return string
 */
function apk_install_inline_tour_markup(array $ui) {
	if (!function_exists('pwa_install_tour_start_button_markup')) {
		return '';
	}
	$sub = trim((string) $ui['tour_sub']);
	$out = '<div class="apk-install-inline-cta pwa-ios-quick pwa-ios-quick--inline-tour">';
	$out .= pwa_install_tour_start_button_markup($ui);
	if ($sub !== '') {
		$out .= '<p class="pwa-ios-quick__sub">' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * Finish block — download CTA after the guided tour (mirrors PWA finish layout).
 *
 * @param array $ui
 * @param string $download_label
 * @return string
 */
function apk_install_finish_markup(array $ui, $download_label) {
	$title = trim((string) $ui['tour_finish_title']);
	$lead = trim((string) $ui['tour_finish_lead']);
	$out = '<div class="pwa-ios-quick pwa-ios-quick--finish apk-android-finish--open" id="apk-android-finish">';
	$out .= '<div class="pwa-ios-quick__finish-body">';
	if ($title !== '') {
		$out .= '<h2 class="pwa-ios-quick__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
	}
	if ($lead !== '') {
		$out .= '<p class="pwa-ios-quick__hint">' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	$out .= apk_install_hero_cta_markup($download_label);
	if (function_exists('pwa_install_tour_start_button_markup')) {
		$out .= pwa_install_tour_start_button_markup($ui, 'pwa-ios-tour-start--finish');
		$sub = trim((string) $ui['tour_sub']);
		if ($sub !== '') {
			$out .= '<p class="pwa-ios-quick__sub">' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</p>';
		}
	}
	$out .= '</div></div>';
	return $out;
}

/**
 * Replace inline download CTA with the guided-tour start button.
 *
 * @param string $html
 * @param string $inline
 * @return string
 */
function apk_install_apply_inline_tour($html, $inline) {
	$html = (string) $html;
	$inline = (string) $inline;
	if ($inline === '') {
		return $html;
	}
	if (strpos($html, 'pwa-ios-quick--inline-tour') !== false) {
		return function_exists('pwa_install_replace_div_block')
			? pwa_install_replace_div_block($html, '<div class="apk-install-inline-cta pwa-ios-quick pwa-ios-quick--inline-tour"', $inline)
			: $html;
	}
	if (strpos($html, 'apk-install-inline-cta') !== false && function_exists('pwa_install_replace_div_block')) {
		return pwa_install_replace_div_block($html, '<div class="apk-install-inline-cta', $inline);
	}
	return preg_replace(
		'#(<h2\b[^>]*>.*?</h2>\s*<p\b[^>]*>.*?</p>)#is',
		'$1' . $inline,
		$html,
		1
	);
}

/**
 * Inject tour bar after the step-by-step H2 (the one immediately before step H3s).
 *
 * @param string $html
 * @param string $bar
 * @return string
 */
function apk_install_apply_tour_bar($html, $bar) {
	$html = (string) $html;
	$bar = (string) $bar;
	if ($bar === '' || !function_exists('pwa_install_replace_div_block')) {
		return $html;
	}
	while (strpos($html, '<div class="pwa-ios-tour-bar"') !== false) {
		$html = pwa_install_replace_div_block($html, '<div class="pwa-ios-tour-bar"', '');
	}
	return preg_replace('#(<h2\b[^>]*>.*?</h2>)(?=\s*<h3\b)#is', '$1' . $bar, (string) $html, 1);
}

/**
 * Remove one finish block by id="apk-android-finish".
 *
 * @param string $html
 * @return string
 */
function apk_install_remove_finish_block($html) {
	$html = (string) $html;
	$id_pos = strpos($html, 'id="apk-android-finish"');
	if ($id_pos === false) {
		return $html;
	}
	$start = strrpos(substr($html, 0, $id_pos), '<div');
	if ($start === false) {
		return $html;
	}
	$depth = 0;
	$i = $start;
	$len = strlen($html);
	while ($i < $len) {
		if ($i + 4 <= $len && substr($html, $i, 4) === '<div') {
			$depth++;
			$i += 4;
			continue;
		}
		if ($i + 6 <= $len && substr($html, $i, 6) === '</div>') {
			$depth--;
			$i += 6;
			if ($depth === 0) {
				return substr($html, 0, $start) . substr($html, $i);
			}
			continue;
		}
		$i++;
	}
	return $html;
}

/**
 * Replace or append the APK finish block.
 *
 * @param string $html
 * @param string $finish
 * @return string
 */
function apk_install_apply_finish_block($html, $finish) {
	$html = (string) $html;
	$finish = (string) $finish;
	if ($finish === '') {
		return $html;
	}
	while (strpos($html, 'id="apk-android-finish"') !== false) {
		$next = apk_install_remove_finish_block($html);
		if ($next === $html) {
			break;
		}
		$html = $next;
	}
	return rtrim($html) . $finish;
}

/**
 * Enhance install-apk page: download CTAs, inline tour start, step markers, finish block.
 *
 * @param string $html
 * @param array $abc
 * @param array $lang
 * @return string
 */
function apk_install_enhance_page($html, $abc, $lang) {
	$html = (string) $html;
	if ($html === '') {
		return $html;
	}
	$html = apk_install_enhance_download_ctas($html);
	$ui = apk_install_ui_strings($lang);
	$download_label = apk_install_download_button_label($html);
	$html = apk_install_apply_inline_tour($html, apk_install_inline_tour_markup($ui));
	if (function_exists('pwa_install_mark_tour_steps')) {
		$html = pwa_install_mark_tour_steps($html);
	}
	if (function_exists('pwa_install_tour_bar_markup')) {
		$html = apk_install_apply_tour_bar($html, pwa_install_tour_bar_markup($ui));
	}
	$html = apk_install_apply_finish_block($html, apk_install_finish_markup($ui, $download_label));
	return $html;
}
