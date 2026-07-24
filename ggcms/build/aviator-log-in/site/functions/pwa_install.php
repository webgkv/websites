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
 * Load merged pwa-ios-install i18n bundle (en + locale overrides).
 *
 * @param array $lang
 * @return array
 */
function pwa_install_i18n_file_bundle($lang) {
	static $cache = array();
	$key = pwa_install_lang_key($lang);
	if (isset($cache[$key])) {
		return $cache[$key];
	}
	$en = array();
	$loc = array();
	if (defined('ROOT_DIR')) {
		$file = ROOT_DIR . 'files/i18n/pwa-ios-install.php';
		if (@is_file($file)) {
			$all = include $file;
			if (is_array($all)) {
				$en = isset($all['en']) && is_array($all['en']) ? $all['en'] : array();
				$loc = isset($all[$key]) && is_array($all[$key]) ? $all[$key] : array();
			}
		}
	}
	return $cache[$key] = array_merge($en, $loc);
}

/**
 * Default UI strings per locale (runtime fallback when i18n file has no key).
 *
 * @param string $key
 * @return array
 */
function pwa_install_ui_defaults($key) {
	$maps = array(
		'en' => array(
			'tour_cta' => 'How to install?',
			'tour_sub' => '3 taps in Safari · about 1 minute',
			'tour_next' => 'Next',
			'tour_back' => 'Back',
			'tour_done' => 'Done',
			'tour_step_of' => 'Step %d of 3',
			'tour_finish_title' => 'You\'re ready — open the demo in Safari',
			'tour_finish_lead' => 'Paste the link in Safari\'s address bar, then repeat the 3 steps above on the demo page.',
			'tour_finish_locked_hint' => 'Complete the 3-step tour — then copy the Safari link below',
			'copy_link' => 'Copy link for Safari',
			'copied' => 'Copied!',
			'copied_hint' => 'Open Safari and paste in the address bar',
			'open_demo' => 'Open demo',
		),
		'ru' => array(
			'tour_cta' => 'Как установить?',
			'tour_sub' => '3 нажатия в Safari · около 1 минуты',
			'tour_next' => 'Далее',
			'tour_back' => 'Назад',
			'tour_done' => 'Готово',
			'tour_step_of' => 'Шаг %d из 3',
			'tour_finish_title' => 'Готово — откройте демо в Safari',
			'tour_finish_lead' => 'Вставьте ссылку в адресную строку Safari и повторите 3 шага на странице демо.',
			'tour_finish_locked_hint' => 'Пройдите тур из 3 шагов — затем скопируйте ссылку для Safari',
			'copy_link' => 'Копировать ссылку для Safari',
			'copied' => 'Скопировано!',
			'copied_hint' => 'Откройте Safari и вставьте в адресную строку',
			'open_demo' => 'Открыть демо',
		),
		'fr' => array(
			'tour_cta' => 'Comment installer ?',
			'tour_sub' => '3 actions dans Safari · environ 1 minute',
			'tour_next' => 'Suivant',
			'tour_back' => 'Retour',
			'tour_done' => 'Terminé',
			'tour_step_of' => 'Étape %d sur 3',
			'tour_finish_title' => 'Prêt — ouvrez la démo dans Safari',
			'tour_finish_lead' => 'Collez le lien dans Safari, puis refaites les 3 étapes sur la page démo.',
			'copy_link' => 'Copier le lien pour Safari',
			'copied' => 'Copié !',
			'copied_hint' => 'Ouvrez Safari et collez dans la barre d\'adresse',
			'open_demo' => 'Ouvrir la démo',
		),
		'de' => array(
			'tour_cta' => 'Wie installieren?',
			'tour_sub' => '3 Schritte in Safari · etwa 1 Minute',
			'tour_next' => 'Weiter',
			'tour_back' => 'Zurück',
			'tour_done' => 'Fertig',
			'tour_step_of' => 'Schritt %d von 3',
			'tour_finish_title' => 'Bereit — Demo in Safari öffnen',
			'tour_finish_lead' => 'Link in Safari einfügen und die 3 Schritte auf der Demo-Seite wiederholen.',
			'copy_link' => 'Link für Safari kopieren',
			'copied' => 'Kopiert!',
			'copied_hint' => 'Safari öffnen und in die Adresszeile einfügen',
			'open_demo' => 'Demo öffnen',
		),
		'es' => array(
			'tour_cta' => '¿Cómo instalar?',
			'tour_sub' => '3 toques en Safari · alrededor de 1 minuto',
			'tour_next' => 'Siguiente',
			'tour_back' => 'Atrás',
			'tour_done' => 'Listo',
			'tour_step_of' => 'Paso %d de 3',
			'tour_finish_title' => 'Listo — abre la demo en Safari',
			'tour_finish_lead' => 'Pega el enlace en Safari y repite los 3 pasos en la página demo.',
			'copy_link' => 'Copiar enlace para Safari',
			'copied' => '¡Copiado!',
			'copied_hint' => 'Abre Safari y pega en la barra de direcciones',
			'open_demo' => 'Abrir demo',
		),
		'pt' => array(
			'tour_cta' => 'Como instalar?',
			'tour_sub' => '3 toques no Safari · cerca de 1 minuto',
			'tour_next' => 'Próximo',
			'tour_back' => 'Voltar',
			'tour_done' => 'Concluir',
			'tour_step_of' => 'Passo %d de 3',
			'tour_finish_title' => 'Pronto — abra a demo no Safari',
			'tour_finish_lead' => 'Cole o link no Safari e repita os 3 passos na página demo.',
			'copy_link' => 'Copiar link para o Safari',
			'copied' => 'Copiado!',
			'copied_hint' => 'Abra o Safari e cole na barra de endereços',
			'open_demo' => 'Abrir demo',
		),
		'it' => array(
			'tour_cta' => 'Come installare?',
			'tour_sub' => '3 passi in Safari · circa 1 minuto',
			'tour_next' => 'Avanti',
			'tour_back' => 'Indietro',
			'tour_done' => 'Fine',
			'tour_step_of' => 'Passo %d di 3',
			'tour_finish_title' => 'Pronto — apri la demo in Safari',
			'tour_finish_lead' => 'Incolla il link in Safari e ripeti i 3 passi sulla pagina demo.',
			'copy_link' => 'Copia link per Safari',
			'copied' => 'Copiato!',
			'copied_hint' => 'Apri Safari e incolla nella barra degli indirizzi',
			'open_demo' => 'Apri demo',
		),
		'pl' => array(
			'tour_cta' => 'Jak zainstalować?',
			'tour_sub' => '3 kroki w Safari · około 1 minuty',
			'tour_next' => 'Dalej',
			'tour_back' => 'Wstecz',
			'tour_done' => 'Gotowe',
			'tour_step_of' => 'Krok %d z 3',
			'tour_finish_title' => 'Gotowe — otwórz demo w Safari',
			'tour_finish_lead' => 'Wklej link w Safari i powtórz 3 kroki na stronie demo.',
			'copy_link' => 'Kopiuj link do Safari',
			'copied' => 'Skopiowano!',
			'copied_hint' => 'Otwórz Safari i wklej w pasku adresu',
			'open_demo' => 'Otwórz demo',
		),
		'uk' => array(
			'tour_cta' => 'Як установити?',
			'tour_sub' => '3 дії в Safari · близько 1 хвилини',
			'tour_next' => 'Далі',
			'tour_back' => 'Назад',
			'tour_done' => 'Готово',
			'tour_step_of' => 'Крок %d з 3',
			'tour_finish_title' => 'Готово — відкрийте демо в Safari',
			'tour_finish_lead' => 'Вставте посилання в Safari і повторіть 3 кроки на сторінці демо.',
			'copy_link' => 'Копіювати посилання для Safari',
			'copied' => 'Скопійовано!',
			'copied_hint' => 'Відкрийте Safari і вставте в адресний рядок',
			'open_demo' => 'Відкрити демо',
		),
		'nl' => array(
			'tour_cta' => 'Hoe installeren?',
			'tour_sub' => '3 stappen in Safari · ongeveer 1 minuut',
			'tour_next' => 'Volgende',
			'tour_back' => 'Terug',
			'tour_done' => 'Klaar',
			'tour_step_of' => 'Stap %d van 3',
			'tour_finish_title' => 'Klaar — open de demo in Safari',
			'tour_finish_lead' => 'Plak de link in Safari en herhaal de 3 stappen op de demopagina.',
			'copy_link' => 'Link kopiëren voor Safari',
			'copied' => 'Gekopieerd!',
			'copied_hint' => 'Open Safari en plak in de adresbalk',
			'open_demo' => 'Demo openen',
		),
		'ro' => array(
			'tour_cta' => 'Cum instalez?',
			'tour_sub' => '3 atingeri în Safari · circa 1 minut',
			'tour_next' => 'Următorul',
			'tour_back' => 'Înapoi',
			'tour_done' => 'Gata',
			'tour_step_of' => 'Pasul %d din 3',
			'tour_finish_title' => 'Gata — deschide demo în Safari',
			'tour_finish_lead' => 'Lipește linkul în Safari și repetă cei 3 pași pe pagina demo.',
			'copy_link' => 'Copiază linkul pentru Safari',
			'copied' => 'Copiat!',
			'copied_hint' => 'Deschide Safari și lipește în bara de adrese',
			'open_demo' => 'Deschide demo',
		),
		'hi' => array(
			'tour_cta' => 'इंस्टॉल कैसे करें?',
			'tour_sub' => 'Safari में 3 स्टेप · लगभग 1 मिनट',
			'tour_next' => 'आगे',
			'tour_back' => 'पीछे',
			'tour_done' => 'हो गया',
			'tour_step_of' => 'चरण %d / 3',
			'tour_finish_title' => 'तैयार — Safari में डेमो खोलें',
			'tour_finish_lead' => 'Safari में लिंक पेस्ट करें और डेमो पर 3 स्टेप दोहराएँ।',
			'copy_link' => 'Safari के लिए लिंक कॉपी करें',
			'copied' => 'कॉपी हो गया!',
			'copied_hint' => 'Safari खोलें और address bar में पेस्ट करें',
			'open_demo' => 'डेमो खोलें',
		),
		'ar' => array(
			'tour_cta' => 'كيف أثبّت؟',
			'tour_sub' => '3 خطوات في Safari · حوالي دقيقة',
			'tour_next' => 'التالي',
			'tour_back' => 'رجوع',
			'tour_done' => 'تم',
			'tour_step_of' => 'الخطوة %d من 3',
			'tour_finish_title' => 'جاهز — افتح العرض في Safari',
			'tour_finish_lead' => 'الصق الرابط في Safari ثم كرّر الخطوات الثلاث في صفحة العرض.',
			'copy_link' => 'نسخ الرابط لـ Safari',
			'copied' => 'تم النسخ!',
			'copied_hint' => 'افتح Safari والصق في شريط العناوين',
			'open_demo' => 'فتح العرض',
		),
		'bn' => array(
			'tour_cta' => 'কিভাবে ইনস্টল করবেন?',
			'tour_sub' => 'Safari-তে 3 ধাপ · প্রায় 1 মিনিট',
			'tour_next' => 'পরবর্তী',
			'tour_back' => 'পিছনে',
			'tour_done' => 'শেষ',
			'tour_step_of' => 'ধাপ %d / 3',
			'tour_finish_title' => 'প্রস্তুত — Safari-তে ডেমো খুলুন',
			'tour_finish_lead' => 'Safari-তে লিংক পেস্ট করুন এবং ডেমো পেজে 3 ধাপ পুনরায় করুন.',
			'copy_link' => 'Safari-র জন্য লিংক কপি করুন',
			'copied' => 'কপি হয়েছে!',
			'copied_hint' => 'Safari খুলে address bar-এ পেস্ট করুন',
			'open_demo' => 'ডেমো খুলুন',
		),
		'vi' => array(
			'tour_cta' => 'Cách cài đặt?',
			'tour_sub' => '3 bước trong Safari · khoảng 1 phút',
			'tour_next' => 'Tiếp',
			'tour_back' => 'Quay lại',
			'tour_done' => 'Xong',
			'tour_step_of' => 'Bước %d / 3',
			'tour_finish_title' => 'Sẵn sàng — mở demo trong Safari',
			'tour_finish_lead' => 'Dán liên kết vào Safari rồi lặp lại 3 bước trên trang demo.',
			'copy_link' => 'Sao chép liên kết cho Safari',
			'copied' => 'Đã sao chép!',
			'copied_hint' => 'Mở Safari và dán vào thanh địa chỉ',
			'open_demo' => 'Mở demo',
		),
		'az' => array(
			'tour_cta' => 'Necə quraşdırım?',
			'tour_sub' => 'Safari-də 3 addım · təxminən 1 dəqiqə',
			'tour_next' => 'Növbəti',
			'tour_back' => 'Geri',
			'tour_done' => 'Hazır',
			'tour_step_of' => 'Addım %d / 3',
			'tour_finish_title' => 'Hazırsınız — demoyu Safari-də açın',
			'tour_finish_lead' => 'Linki Safari-yə yapışdırın və demo səhifəsində 3 addımı təkrarlayın.',
			'copy_link' => 'Safari üçün linki kopyala',
			'copied' => 'Kopyalandı!',
			'copied_hint' => 'Safari açın və ünvan sətirinə yapışdırın',
			'open_demo' => 'Demonu aç',
		),
	);
	return isset($maps[$key]) ? $maps[$key] : $maps['en'];
}

/**
 * Runtime UI strings for install-pwa guided tour + finish block.
 *
 * @param array $lang
 * @return array
 */
function pwa_install_ui_strings($lang) {
	$lk = pwa_install_lang_key($lang);
	$defaults = pwa_install_ui_defaults($lk);
	$from_file = array();
	if (defined('ROOT_DIR')) {
		$file = ROOT_DIR . 'files/i18n/pwa-ios-install.php';
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
		'tour_finish_title', 'tour_finish_lead', 'tour_finish_locked_hint', 'copy_link', 'copied', 'copied_hint', 'open_demo',
	);
	$en_defaults = pwa_install_ui_defaults('en');
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
 * Localized copy-link button labels for the PWA finish block.
 *
 * @param array $lang
 * @return array{copy:string,copied:string,copied_hint:string}
 */
function pwa_install_copy_button_labels($lang) {
	$ui = pwa_install_ui_strings($lang);
	return array(
		'copy' => $ui['copy_link'] !== '' ? $ui['copy_link'] : 'Copy link for Safari',
		'copied' => $ui['copied'] !== '' ? $ui['copied'] : 'Copied!',
		'copied_hint' => $ui['copied_hint'] !== '' ? $ui['copied_hint'] : 'Open Safari and paste in the address bar',
	);
}

/**
 * Shared "How to install?" button markup.
 *
 * @param array $ui
 * @param string $extra_class
 * @return string
 */
function pwa_install_tour_start_button_markup(array $ui, $extra_class = '') {
	$cta = trim((string) $ui['tour_cta']);
	if ($cta === '') {
		$cta = 'How to install?';
	}
	$cls = 'pwa-ios-tour-start main_btn';
	if ($extra_class !== '') {
		$cls .= ' ' . trim((string) $extra_class);
	}
	return '<button type="button" class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '">'
		. htmlspecialchars($cta, ENT_QUOTES, 'UTF-8') . '</button>';
}

/**
 * Hero card with guided-tour start button — injected right after H1.
 *
 * @param array $ui
 * @return string
 */
function pwa_install_hero_markup(array $ui) {
	$sub = trim((string) $ui['tour_sub']);
	$out = '<div class="pwa-ios-quick pwa-ios-quick--hero">';
	$out .= pwa_install_tour_start_button_markup($ui);
	if ($sub !== '') {
		$out .= '<p class="pwa-ios-quick__sub">' . htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	$out .= '</div>';
	return $out;
}

/**
 * URL + copy block shown after the guided tour.
 *
 * @param string $demo_url
 * @param array $ui
 * @param array $copy_labels
 * @return string
 */
function pwa_install_finish_markup($demo_url, array $ui, array $copy_labels) {
	$demo_url = trim((string) $demo_url);
	if ($demo_url === '') {
		return '';
	}
	$title = trim((string) $ui['tour_finish_title']);
	$lead = trim((string) $ui['tour_finish_lead']);
	$locked_hint = trim((string) $ui['tour_finish_locked_hint']);
	$open = trim((string) $ui['open_demo']);
	$esc_url = htmlspecialchars($demo_url, ENT_QUOTES, 'UTF-8');
	$copy_l = htmlspecialchars((string) $copy_labels['copy'], ENT_QUOTES, 'UTF-8');
	$copied_l = htmlspecialchars((string) $copy_labels['copied'], ENT_QUOTES, 'UTF-8');
	$hint_l = htmlspecialchars((string) $copy_labels['copied_hint'], ENT_QUOTES, 'UTF-8');
	$out = '<div class="pwa-ios-quick pwa-ios-quick--finish" id="pwa-ios-finish">';
	$out .= '<div class="pwa-ios-quick__finish-start">';
	$out .= pwa_install_tour_start_button_markup($ui, 'pwa-ios-tour-start--finish');
	if ($locked_hint !== '') {
		$out .= '<p class="pwa-ios-quick__sub pwa-ios-quick__finish-locked-hint">'
			. htmlspecialchars($locked_hint, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	$out .= '</div>';
	$out .= '<div class="pwa-ios-quick__finish-body">';
	if ($title !== '') {
		$out .= '<h2 class="pwa-ios-quick__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
	}
	if ($lead !== '') {
		$out .= '<p class="pwa-ios-quick__hint">' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</p>';
	}
	$out .= '<div class="pwa-ios-quick__url-block">';
	$out .= '<a class="pwa-ios-quick__url" href="' . $esc_url . '">' . $esc_url . '</a>';
	$out .= '<button type="button" class="pwa-ios-quick__copy" data-copy-url="' . $esc_url . '"'
		. ' data-copy-label="' . $copy_l . '" data-copied-label="' . $copied_l . '"'
		. ' data-copied-hint="' . $hint_l . '" aria-label="' . $copy_l . '">';
	$out .= '<span class="pwa-ios-quick__copy-icon" aria-hidden="true"></span>';
	$out .= '<span class="pwa-ios-quick__copy-label">' . $copy_l . '</span>';
	$out .= '</button></div>';
	$out .= '<p class="pwa-ios-quick__copied-hint" hidden>' . $hint_l . '</p>';
	if ($open !== '') {
		$out .= '<p class="pwa-ios-quick__open-demo"><a class="pwa-ios-quick__open-link" href="' . $esc_url . '">'
			. htmlspecialchars($open, ENT_QUOTES, 'UTF-8') . '</a></p>';
	}
	$out .= '</div></div>';
	return $out;
}

/**
 * Floating tour controls under the "Step by step" heading.
 *
 * @param array $ui
 * @return string
 */
function pwa_install_tour_bar_markup(array $ui) {
	$step_tpl = trim((string) $ui['tour_step_of']);
	if ($step_tpl === '') {
		$step_tpl = 'Step %d of 3';
	}
	$back = trim((string) $ui['tour_back']);
	$next = trim((string) $ui['tour_next']);
	$done = trim((string) $ui['tour_done']);
	if ($back === '') {
		$back = 'Back';
	}
	if ($next === '') {
		$next = 'Next';
	}
	if ($done === '') {
		$done = 'Done';
	}
	return '<div class="pwa-ios-tour-bar" hidden aria-hidden="true" data-step-template="'
		. htmlspecialchars($step_tpl, ENT_QUOTES, 'UTF-8') . '">'
		. '<p class="pwa-ios-tour-bar__progress"></p>'
		. '<div class="pwa-ios-tour-bar__actions">'
		. '<button type="button" class="pwa-ios-tour-back">' . htmlspecialchars($back, ENT_QUOTES, 'UTF-8') . '</button>'
		. '<button type="button" class="pwa-ios-tour-next main_btn">' . htmlspecialchars($next, ENT_QUOTES, 'UTF-8') . '</button>'
		. '<button type="button" class="pwa-ios-tour-done main_btn" hidden>' . htmlspecialchars($done, ENT_QUOTES, 'UTF-8') . '</button>'
		. '</div></div>';
}

/**
 * Mark step headings/figures for the guided tour.
 *
 * @param string $html
 * @return string
 */
function pwa_install_mark_tour_steps($html) {
	if ($html === '' || strpos($html, 'data-pwa-tour-step') !== false) {
		return $html;
	}
	$step = 0;
	return preg_replace_callback(
		'#<h3\b([^>]*)>(.*?)</h3>\s*<p\b([^>]*)>(.*?)</p>\s*<figure\b([^>]*)>#is',
		function ($m) use (&$step) {
			$step++;
			if ($step > 3) {
				return $m[0];
			}
			$h3attrs = $m[1];
			if (stripos($h3attrs, 'class=') !== false) {
				$h3attrs = preg_replace('#class=(["\'])([^"\']*)\1#i', 'class=$1$2 pwa-ios-tour-step$1', $h3attrs, 1);
			} else {
				$h3attrs .= ' class="pwa-ios-tour-step"';
			}
			$figattrs = $m[5];
			if (stripos($figattrs, 'class=') !== false) {
				$figattrs = preg_replace('#class=(["\'])([^"\']*)\1#i', 'class=$1$2 pwa-ios-tour-fig$1', $figattrs, 1);
			} else {
				$figattrs .= ' class="pwa-ios-tour-fig"';
			}
			return '<h3' . $h3attrs . ' data-pwa-tour-step="' . $step . '">' . $m[2] . '</h3>'
				. '<p' . $m[3] . ' data-pwa-tour-text="' . $step . '">' . $m[4] . '</p>'
				. '<figure' . $figattrs . ' data-pwa-tour-fig="' . $step . '">';
		},
		$html,
		3
	);
}

/**
 * Inject tour control bar after the first h2 (Step by step).
 *
 * @param string $html
 * @param array $ui
 * @return string
 */
function pwa_install_inject_tour_bar($html, array $ui) {
	if ($html === '' || strpos($html, 'pwa-ios-tour-bar') !== false) {
		return $html;
	}
	$bar = pwa_install_tour_bar_markup($ui);
	return preg_replace('#(<h2\b[^>]*>.*?</h2>)#is', '$1' . $bar, (string) $html, 1);
}

/**
 * Replace or append the finish link block on install-pwa pages.
 *
 * @param string $html
 * @param string $finish
 * @return string
 */
function pwa_install_apply_finish_block($html, $finish) {
	$html = (string) $html;
	$finish = (string) $finish;
	if ($finish === '') {
		return $html;
	}
	$needle = '<div class="pwa-ios-quick pwa-ios-quick--finish" id="pwa-ios-finish">';
	$pos = strpos($html, $needle);
	if ($pos === false) {
		return rtrim($html) . $finish;
	}
	$depth = 0;
	$i = $pos;
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
				return substr($html, 0, $pos) . $finish . substr($html, $i);
			}
			continue;
		}
		$i++;
	}
	return $html;
}

/**
 * Enhance install-pwa page: hero tour CTA, step markers, finish link block.
 *
 * @param string $html
 * @param array $abc
 * @param array $lang
 * @return string
 */
function pwa_install_enhance_page($html, $abc, $lang) {
	$html = (string) $html;
	if ($html === '') {
		return $html;
	}
	$ui = pwa_install_ui_strings($lang);
	$demo_url = pwa_install_demo_app_absolute_url($abc, $lang);
	$html = preg_replace(
		'#<h2\b[^>]*>.*?</h2>\s*<p\b[^>]*>((?:(?!</p>).)*demo/app(?:(?!</p>).)*)</p>#is',
		'',
		$html,
		1
	);
	if (stripos($html, 'pwa-ios-quick--hero') === false && preg_match('#<h1\b[^>]*>.*?</h1>#is', $html, $h1m, PREG_OFFSET_CAPTURE)) {
		$hero = pwa_install_hero_markup($ui);
		$pos = $h1m[0][1] + strlen($h1m[0][0]);
		$html = substr($html, 0, $pos) . $hero . substr($html, $pos);
	} elseif (stripos($html, 'pwa-ios-tour-start') === false && stripos($html, 'pwa-ios-quick--hero') !== false) {
		$html = preg_replace(
			'#<div class="pwa-ios-quick pwa-ios-quick--hero">.*?</div>#is',
			pwa_install_hero_markup($ui),
			$html,
			1
		);
	}
	$html = pwa_install_mark_tour_steps($html);
	$html = pwa_install_inject_tour_bar($html, $ui);
	$finish = pwa_install_finish_markup($demo_url, $ui, pwa_install_copy_button_labels($lang));
	$html = pwa_install_apply_finish_block($html, $finish);
	return $html;
}

/** @deprecated Use pwa_install_enhance_page() */
function pwa_install_enhance_quick_path($html, $abc, $lang) {
	return pwa_install_enhance_page($html, $abc, $lang);
}

/**
 * Prominent quick-path card — legacy alias; use pwa_install_hero_markup().
 *
 * @deprecated
 */
function pwa_install_quick_path_hero_markup($title, $hint, $demo_url, $copy_labels = null) {
	$ui = array(
		'tour_cta' => 'How to install?',
		'tour_sub' => is_string($hint) ? $hint : '',
	);
	return pwa_install_hero_markup($ui);
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
	$ui = pwa_install_ui_defaults('en');
	if (!empty($b['tour_cta'])) {
		$ui['tour_cta'] = (string) $b['tour_cta'];
	}
	if (!empty($b['tour_sub'])) {
		$ui['tour_sub'] = (string) $b['tour_sub'];
	}
	$hero = pwa_install_hero_markup($ui);
	$finish = pwa_install_finish_markup($demo_url, array_merge($ui, pwa_install_ui_defaults('en')), array(
		'copy' => isset($b['copy_link']) ? (string) $b['copy_link'] : 'Copy link for Safari',
		'copied' => isset($b['copied']) ? (string) $b['copied'] : 'Copied!',
		'copied_hint' => isset($b['copied_hint']) ? (string) $b['copied_hint'] : 'Open Safari and paste in the address bar',
	));
	return '<h1>' . $b['h1'] . '</h1>' . $sep
		. $hero . $sep
		. '<p class="pwa-ios-trust-intro">' . $b['intro'] . '</p>' . $sep
		. '<h2>' . $sbs . '</h2>' . $sep
		. pwa_install_tour_bar_markup($ui) . $sep
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
		. '<figure class="text-center my-3 pwa-ios-tour-fig" data-pwa-tour-fig="3"><img style="max-width: 100%; height: auto;" src="' . $bs . 'step-3-open-as-web-app.webp' . $q . '" border="0" alt="' . $a3 . '" width="600" height="400" />' . $sep
		. '<figcaption class="small text-muted mt-1">' . $fc3 . '</figcaption>' . $sep
		. '</figure>' . $sep
		. $finish . $sep;
}
