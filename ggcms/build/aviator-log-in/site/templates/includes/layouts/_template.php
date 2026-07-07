<?php
// Use normalized i18n fields when available (supports 14+ languages via content_i18n)
$page_title_raw = '';
$page_desc_raw = '';

// Single game / guide / casino article: page_i18n is still the *section* listing row, so it must not
// override the record's SEO title (would show e.g. "Games" for /games/jetx/).
$entity_single = null;
if (!empty($abc['game_single']) && is_array($abc['game_single'])) {
	$entity_single = $abc['game_single'];
} elseif (!empty($abc['guide_single']) && is_array($abc['guide_single'])) {
	$entity_single = $abc['guide_single'];
} elseif (!empty($abc['casino_single']) && is_array($abc['casino_single'])) {
	$entity_single = $abc['casino_single'];
}
if ($entity_single !== null) {
	if (isset($entity_single['title']) && trim((string)$entity_single['title']) !== '') {
		$page_title_raw = trim((string)$entity_single['title']);
	}
	if ($page_title_raw === '' && isset($entity_single['name']) && trim((string)$entity_single['name']) !== '') {
		$page_title_raw = trim((string)$entity_single['name']);
	}
	if (isset($entity_single['description']) && trim((string)$entity_single['description']) !== '') {
		$page_desc_raw = trim((string)$entity_single['description']);
	}
	if ($page_desc_raw === '' && isset($entity_single['name_2']) && trim((string)$entity_single['name_2']) !== '') {
		$page_desc_raw = trim((string)$entity_single['name_2']);
	}
}

// Blog / news article: SEO fields are on the merged row as title{langid} etc.; page_i18n is still the section listing.
if (!empty($abc['blog_single']) || !empty($abc['news_single'])) {
	$lid = isset($abc['langid']) ? $abc['langid'] : '';
	$p = isset($abc['page']) && is_array($abc['page']) ? $abc['page'] : array();
	if (isset($p['title' . $lid]) && trim((string)$p['title' . $lid]) !== '') {
		$page_title_raw = trim((string)$p['title' . $lid]);
	} elseif (isset($p['title']) && trim((string)$p['title']) !== '') {
		$page_title_raw = trim((string)$p['title']);
	}
	if ($page_title_raw === '') {
		if (isset($p['name' . $lid]) && trim((string)$p['name' . $lid]) !== '') {
			$page_title_raw = trim((string)$p['name' . $lid]);
		} elseif (isset($p['name']) && trim((string)$p['name']) !== '') {
			$page_title_raw = trim((string)$p['name']);
		}
	}
	if (isset($p['description' . $lid]) && trim((string)$p['description' . $lid]) !== '') {
		$page_desc_raw = trim((string)$p['description' . $lid]);
	} elseif (isset($p['description']) && trim((string)$p['description']) !== '') {
		$page_desc_raw = trim((string)$p['description']);
	}
	if ($page_desc_raw === '') {
		if (isset($p['name_2' . $lid]) && trim((string)$p['name_2' . $lid]) !== '') {
			$page_desc_raw = trim((string)$p['name_2' . $lid]);
		} elseif (isset($p['name_2']) && trim((string)$p['name_2']) !== '') {
			$page_desc_raw = trim((string)$p['name_2']);
		}
	}
}

if ($page_title_raw === '') {
	$page_title_raw = isset($abc['page_i18n']['title']) && $abc['page_i18n']['title'] !== '' ? $abc['page_i18n']['title'] : (isset($abc['page_i18n']['name']) ? $abc['page_i18n']['name'] : '');
}
if ($page_title_raw === '') {
	$page_title_raw = isset($abc['page']['title'.$abc['langid']]) ? $abc['page']['title'.$abc['langid']] : (isset($abc['page']['name'.$abc['langid']]) ? $abc['page']['name'.$abc['langid']] : '');
}
if ($page_title_raw === '') {
	$page_title_raw = isset($abc['page']['title']) ? trim((string)$abc['page']['title']) : '';
}
if ($page_desc_raw === '') {
	$page_desc_raw = isset($abc['page_i18n']['description']) && $abc['page_i18n']['description'] !== '' ? $abc['page_i18n']['description'] : '';
}
if ($page_desc_raw === '') {
	$page_desc_raw = isset($abc['page']['description'.$abc['langid']]) ? $abc['page']['description'.$abc['langid']] : '';
}
if ($page_desc_raw === '') {
	$page_desc_raw = isset($abc['page']['description']) ? trim((string)$abc['page']['description']) : '';
}
// Normalize legacy HTML entities from DB (e.g. &#39;) so frontend escapes only once.
$page_title_raw = html_entity_decode((string)$page_title_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$page_desc_raw = html_entity_decode((string)$page_desc_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
// Avoid FILTER_SANITIZE_STRING here: it can re-encode apostrophes as &#39;.
$abc['page']['title'] = trim(strip_tags((string)$page_title_raw));
$abc['page']['description'] = trim(strip_tags((string)($page_desc_raw !== '' ? $page_desc_raw : $abc['page']['title'])));
// Template always appends site name below; strip if import already included it (EN SEO exports often do).
$aviator_site_title_suffix = ' | Aviator Log In';
$_suf_len = strlen($aviator_site_title_suffix);
$_t = $abc['page']['title'];
while ($_suf_len > 0 && strlen($_t) >= $_suf_len && substr($_t, -$_suf_len) === $aviator_site_title_suffix) {
	$_t = rtrim(substr($_t, 0, -$_suf_len));
}
$abc['page']['title'] = $_t;
// Avoid "… | Aviator | Aviator Log In": middle "| Aviator" is redundant with the template suffix.
$_aviator_mid_brand = ' | Aviator';
$_bml = strlen($_aviator_mid_brand);
if ($abc['page']['title'] !== '' && strlen($abc['page']['title']) >= $_bml && substr($abc['page']['title'], -$_bml) === $_aviator_mid_brand) {
	$abc['page']['title'] = rtrim(substr($abc['page']['title'], 0, -$_bml));
}
// Avoid & in <title>: validators count literal HTML (&amp; = 5 chars). Prefer “and” for readable, shorter encoded output.
$abc['page']['title'] = trim(preg_replace('/\s*&\s*/u', ' and ', $abc['page']['title']));
// Bing flags title length **greater than** 70; cap escaped <title> inner text at 70 code points.
$_aviator_title_max_inner = 70;
$_aviator_title_inner_len = function ($base) use ($aviator_site_title_suffix) {
	$inner = htmlspecialchars((string)$base, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
		. htmlspecialchars($aviator_site_title_suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	if (function_exists('mb_strlen')) {
		return (int)mb_strlen($inner, 'UTF-8');
	}
	return strlen($inner);
};
$_aviator_title_shrink_one = function ($base) {
	$base = (string)$base;
	if ($base === '') {
		return '';
	}
	if (function_exists('mb_substr') && function_exists('mb_strlen')) {
		$L = mb_strlen($base, 'UTF-8');
		if ($L < 1) {
			return '';
		}
		return rtrim(mb_substr($base, 0, $L - 1, 'UTF-8'));
	}
	return rtrim(substr($base, 0, -1));
};
while ($_aviator_title_inner_len($abc['page']['title']) > $_aviator_title_max_inner) {
	$prev = $abc['page']['title'];
	$abc['page']['title'] = $_aviator_title_shrink_one($abc['page']['title']);
	if ($abc['page']['title'] === $prev || $abc['page']['title'] === '') {
		break;
	}
}
if (trim($abc['page']['title']) === '') {
	$abc['page']['title'] = 'Aviator';
}
unset($_aviator_mid_brand, $_bml, $_aviator_title_max_inner, $_aviator_title_inner_len, $_aviator_title_shrink_one);
$_aviator_doc_title_esc = htmlspecialchars($abc['page']['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$_aviator_doc_suffix_esc = htmlspecialchars($aviator_site_title_suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$_aviator_meta_desc_esc = htmlspecialchars((string)$abc['page']['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
if ($_aviator_meta_desc_esc === '') {
	$_aviator_meta_desc_esc = htmlspecialchars($abc['page']['title'] . $aviator_site_title_suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
// Normalized language segment + home href (matches get_url / footer; avoids /hi// from trailing slashes in DB).
$_aviator_lang_seg = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';
$_aviator_lang_base = ($_aviator_lang_seg !== '') ? '/' . $_aviator_lang_seg . '/' : '/';
if (!function_exists('aviator_template_links_for_lang')) {
	function aviator_template_links_for_lang($abc, $lang_url_raw) {
		$links = isset($abc['links']) && is_array($abc['links']) ? $abc['links'] : array();
		$candidates = array((string)$lang_url_raw, trim((string)$lang_url_raw, '/'));
		$seen = array();
		foreach ($candidates as $k) {
			if ($k === '' || isset($seen[$k])) {
				continue;
			}
			$seen[$k] = true;
			if (isset($links[$k]) && is_array($links[$k]) && count($links[$k]) > 0) {
				return $links[$k];
			}
		}
		return null;
	}
}
if (!function_exists('aviator_seo_public_origin')) {
	/**
	 * Absolute origin for canonical/hreflang (SEO → Structured canonical_base, else https://HTTP_HOST).
	 */
	function aviator_seo_public_origin() {
		global $abc;
		$cfg = isset($abc['seo_structured']) && is_array($abc['seo_structured']) ? $abc['seo_structured'] : array();
		$b = isset($cfg['canonical_base']) ? trim((string)$cfg['canonical_base']) : '';
		if ($b !== '') {
			return rtrim($b, '/');
		}
		$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
		if ($host === '') {
			return 'https://localhost';
		}
		return 'https://' . $host;
	}
}
if (!function_exists('aviator_hreflang_code')) {
	/**
	 * BCP47 hreflang value: prefer languages.localization (e.g. uk, en-gb), not path segment (ua ≠ language).
	 */
	function aviator_hreflang_code($abc, $lang_url_segment) {
		$u = strtolower(trim((string)$lang_url_segment, '/'));
		if (!isset($abc['languages']) || !is_array($abc['languages'])) {
			return ($u === 'ua') ? 'uk' : $u;
		}
		foreach ($abc['languages'] as $row) {
			if (!is_array($row)) {
				continue;
			}
			$row_u = strtolower(trim((string)($row['url'] ?? ''), '/'));
			if ($row_u !== $u) {
				continue;
			}
			$loc = isset($row['localization']) ? trim((string)$row['localization']) : '';
			$loc = str_replace('_', '-', strtolower($loc));
			if ($loc !== '' && preg_match('/^[a-z]{2,3}(-[a-z0-9]+)*$/i', $loc)) {
				// BCP47: Ukrainian is `uk`; DB sometimes stores `ua` (country / legacy).
				return ($loc === 'ua') ? 'uk' : $loc;
			}
			break;
		}
		if ($u === 'ua') {
			return 'uk';
		}
		return $u;
	}
}
if (!function_exists('aviator_lang_flag_country_code')) {
	/**
	 * Map languages.url segment (often ISO 639-1) to a flagcdn.com country code (ISO 3166-1 alpha-2).
	 */
	function aviator_lang_flag_country_code($url_seg) {
		$u = strtolower(trim((string)$url_seg, '/'));
		$map = array(
			'en' => 'gb',
			'fr' => 'fr',
			'de' => 'de',
			'es' => 'es',
			'it' => 'it',
			'pl' => 'pl',
			'pt' => 'br',
			'hi' => 'in',
			'bn' => 'bd',
			'ar' => 'sa',
			'vi' => 'vn',
			'az' => 'az',
			'ru' => 'ru',
			'nl' => 'nl',
			'ua' => 'ua',
			'tr' => 'tr',
			'ja' => 'jp',
			'zh' => 'cn',
			'ko' => 'kr',
			'cs' => 'cz',
			'sv' => 'se',
			'da' => 'dk',
			'fi' => 'fi',
			'no' => 'no',
			'nb' => 'no',
			'nn' => 'no',
			'ro' => 'ro',
			'el' => 'gr',
			'he' => 'il',
			'th' => 'th',
			'id' => 'id',
			'ms' => 'my',
			'tl' => 'ph',
			'fa' => 'ir',
			'ur' => 'pk',
			'sk' => 'sk',
			'hu' => 'hu',
			'bg' => 'bg',
			'hr' => 'hr',
			'sr' => 'rs',
			'sl' => 'si',
			'et' => 'ee',
			'lv' => 'lv',
			'lt' => 'lt',
			'sq' => 'al',
			'bs' => 'ba',
			'mk' => 'mk',
			'is' => 'is',
			'ga' => 'ie',
			'cy' => 'gb',
			'eu' => 'es',
			'ca' => 'es',
			'gl' => 'es',
			'lb' => 'lu',
			'mt' => 'mt',
			'hy' => 'am',
			'ka' => 'ge',
			'kk' => 'kz',
			'uz' => 'uz',
			'tg' => 'tj',
			'ky' => 'kg',
			'mn' => 'mn',
			'ne' => 'np',
			'si' => 'lk',
			'my' => 'mm',
			'km' => 'kh',
			'lo' => 'la',
			'fil' => 'ph',
		);
		if (isset($map[$u])) {
			return $map[$u];
		}
		if (preg_match('/^[a-z]{2}$/', $u)) {
			return $u;
		}
		return null;
	}
}
if (!function_exists('aviator_lang_switcher_label')) {
	/**
	 * Native-style label for switcher (Polylang-like); falls back to DB languages.name.
	 */
	function aviator_lang_switcher_label($url_seg, $db_name = '') {
		$d = trim((string)$db_name);
		if ($d !== '') {
			return $d;
		}
		$u = strtolower(trim((string)$url_seg, '/'));
		$labels = array(
			'en' => 'English',
			'fr' => 'Français',
			'de' => 'Deutsch',
			'es' => 'Español',
			'it' => 'Italiano',
			'pl' => 'Polski',
			'pt' => 'Português',
			'hi' => 'हिन्दी',
			'bn' => 'বাংলা',
			'ar' => 'العربية',
			'vi' => 'Tiếng Việt',
			'az' => 'Azərbaycan',
			'ru' => 'Русский',
			'nl' => 'Nederlands',
			'ua' => 'Українська',
			'tr' => 'Türkçe',
			'ja' => '日本語',
			'zh' => '中文',
			'ko' => '한국어',
			'cs' => 'Čeština',
			'sv' => 'Svenska',
			'da' => 'Dansk',
			'fi' => 'Suomi',
			'no' => 'Norsk',
			'nb' => 'Norsk',
			'nn' => 'Norsk',
			'ro' => 'Română',
			'el' => 'Ελληνικά',
			'he' => 'עברית',
			'th' => 'ไทย',
			'id' => 'Bahasa Indonesia',
			'ms' => 'Bahasa Melayu',
			'fa' => 'فارسی',
			'ur' => 'اردو',
			'sk' => 'Slovenčina',
			'hu' => 'Magyar',
			'bg' => 'Български',
			'hr' => 'Hrvatski',
			'sr' => 'Српски',
			'sl' => 'Slovenščina',
			'et' => 'Eesti',
			'lv' => 'Latviešu',
			'lt' => 'Lietuvių',
		);
		if (isset($labels[$u])) {
			return $labels[$u];
		}
		return strtoupper($u);
	}
}
// <html lang>: BCP47 from DB localization when possible (e.g. path /ua/ → uk)
$_aviator_html_lang = $_aviator_lang_seg !== '' ? aviator_hreflang_code($abc, $_aviator_lang_seg) : 'en';

// Minimal chrome + iframe only (no site header/footer/popups): /{lang}/demo/app/
if (!empty($abc['layout']) && $abc['layout'] === 'demo_app') {
	$__demo_app_debug = !empty($abc['debug_ip_check'])
		&& isset($_GET['debug_ip_check'])
		&& (string) $_GET['debug_ip_check'] === '1';
	if ($__demo_app_debug) {
		$__r = defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__) . '/../../../../';
		require_once $__r . 'functions/game_demo_embed.php';
		game_demo_ensure_spribe_provider();
		$abc['debug_demo_app'] = game_demo_app_build_debug_payload($abc, $config);
		require __DIR__ . '/_debug_demo_app_full.php';
		return;
	}
	$r = defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__) . '/../../../../';
	$getV = function ($f) {
		global $config;
		$mtime = @file_exists($f) ? (string) filemtime($f) : (string) time();
		if (!empty($config['assets_version'])) {
			return (string) $config['assets_version'] . '.' . $mtime;
		}
		return $mtime;
	};
	require __DIR__ . '/_template_demo_app.php';
	return;
}
?>
<!DOCTYPE html>
<!-- layout v2: overflow/batting/burger fixes -->
<html lang="<?= htmlspecialchars($_aviator_html_lang, ENT_QUOTES, 'UTF-8') ?>">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$_preload_hero = '';
if (!empty($abc['layout']) && (string)$abc['layout'] === 'index') {
	if (function_exists('site_brand_hero_image_url')) {
		$_preload_hero = site_brand_hero_image_url();
	} elseif (function_exists('site_brand_asset_url') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'assets/images/aviator-main.webp')) {
		$_preload_hero = site_brand_asset_url('/assets/images/aviator-main.webp');
	} elseif (function_exists('site_home_lottery_img')) {
		$_preload_hero = site_home_lottery_img('index-illus.png');
	}
}
if ($_preload_hero !== '') {
	echo '        <link rel="preload" as="image" href="' . htmlspecialchars($_preload_hero, ENT_QUOTES, 'UTF-8') . '" fetchpriority="high">' . "\n";
}
?>
        <title><?=$_aviator_doc_title_esc?><?=$_aviator_doc_suffix_esc?></title>
        <meta name="description" content="<?=$_aviator_meta_desc_esc?>">
<?php if (function_exists('site_seo_echo_robots_meta_tags')) { site_seo_echo_robots_meta_tags(); } ?>
        <meta name="theme-color" content="#151b24">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Aviator">
        <!-- PWA icons + manifest after $getV below (cache-bust; iOS needs 180 + /apple-touch-icon.png) -->
        <!-- Place favicon.ico in the root directory -->
        <?php
        if (!function_exists('site_template_preconnect_hints')) {
            require_once (defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__) . '/../../../../') . 'functions/site_template_perf.php';
        }
        echo site_template_preconnect_hints();
        ?>
        <!-- boostrap css links -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <!-- font awesome cdn links (subset: solid + brands) -->
<?= site_template_fontawesome_stylesheets() ?>
        <!-- google font (non-blocking: system font renders first, swaps on load) -->
<?= site_template_google_font('Open+Sans:wght@400;600;700') ?>
<?php /*
        <!-- swipper slider cdn links -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
        <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
*/ ?>
        <!-- All stylesheet and icons css (v=assets_version or file mtime) -->
        <?php
        $r = defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__).'/../../../../';
        $getV = function($f) {
            global $config;
            $mtime = @file_exists($f) ? (string)filemtime($f) : (string)time();
            if (!empty($config['assets_version'])) {
                // Keep manual versioning but always append file mtime for hard cache-busting.
                return (string)$config['assets_version'] . '.' . $mtime;
            }
            return $mtime;
        };
        $_pwa180 = $r . 'assets/images/pwa-icon-180.png';
        $_pwa192 = $r . 'assets/images/pwa-icon-192.png';
        $_atRoot = $r . 'apple-touch-icon.png';
        $_favicon = $r . 'assets/images/favicon.png';
        ?>
        <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/pwa-icon-180.png?v=<?= htmlspecialchars($getV($_pwa180), ENT_QUOTES, 'UTF-8') ?>">
        <link rel="apple-touch-icon" sizes="192x192" href="/assets/images/pwa-icon-192.png?v=<?= htmlspecialchars($getV($_pwa192), ENT_QUOTES, 'UTF-8') ?>">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=<?= htmlspecialchars($getV($_atRoot), ENT_QUOTES, 'UTF-8') ?>">
        <link rel="manifest" href="<?= htmlspecialchars(function_exists('pwa_install_manifest_href') ? pwa_install_manifest_href($getV, $r) : ('/manifest.php?start=%2F&v=' . $getV($r . 'manifest.php')), ENT_QUOTES, 'UTF-8') ?>">
        <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=<?= htmlspecialchars($getV($_favicon), ENT_QUOTES, 'UTF-8') ?>">
        <link rel="stylesheet" href="/assets/css/style.css?v=<?= $getV($r.'assets/css/style.css') ?>">
        <link rel="stylesheet" href="/assets/css/responsive.css?v=<?= $getV($r.'assets/css/responsive.css') ?>">
        <link rel="stylesheet" href="/assets/css/custom-overrides.css?v=<?= $getV($r.'assets/css/custom-overrides.css') ?>">
        <script>
        (function(){
          window._burgerDebug = window._burgerDebug || { inited: false, toggleClicks: 0, toggleTouches: 0 };
          document.addEventListener('DOMContentLoaded', function() {
            var nav = document.getElementById('navbarNav');
            var t = document.querySelector('.menu-toggle');
            if (!nav || !t) return;
            window._burgerDebug.inited = true;
            function openClose(e) {
              if (e && e._aviatorHandled) return;
              if (e) e._aviatorHandled = true;
              if (e && e.type === 'touchend') window._burgerDebug.toggleTouches++;
              else window._burgerDebug.toggleClicks++;
              if (e) { e.preventDefault(); e.stopPropagation(); }
              nav.classList.toggle('active');
              var isOpen = nav.classList.contains('active');
              t.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
              document.body.classList.toggle('menu-open', isOpen);
            }
            t.addEventListener('click', openClose, true);
            t.addEventListener('touchend', openClose, { capture: true, passive: false });
            nav.addEventListener('click', function(e) {
              var link = e.target && e.target.closest ? e.target.closest('a') : null;
              if (!link) return;
              if (link.classList.contains('dropdown-toggle') || link.getAttribute('data-bs-toggle') === 'dropdown') return;
              nav.classList.remove('active');
              t.setAttribute('aria-expanded', 'false');
              document.body.classList.remove('menu-open');
            });
          });
        })();
        </script>
        <?php
        if (!function_exists('site_template_service_worker_bootstrap_script')) {
            require_once (defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__) . '/../../../../') . 'functions/site_template_perf.php';
        }
        echo site_template_service_worker_bootstrap_script(false, !empty($abc['counters_head']) ? $abc['counters_head'] : array());
        ?>
        <?php if (!empty($abc['counters_head'])) { foreach ($abc['counters_head'] as $_counter) { echo site_template_async_counter($_counter) . "\n        "; } } ?>
<?php
      // Canonical + hreflang (Google: canonical = this locale's URL; alternates + reciprocal set; x-default = primary/source locale same page)
      $ts_settings = null;
      if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
          $row_ts = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
          if ($row_ts && $row_ts['value'] !== '') {
              $ts_settings = json_decode($row_ts['value'], true);
          }
      }
      $enabled_lang_urls = array();
      if (is_array($ts_settings) && !empty($ts_settings['enabled_lang_ids']) && is_array($ts_settings['enabled_lang_ids'])) {
          $set = array_flip(array_values(array_filter(array_map('intval', $ts_settings['enabled_lang_ids']))));
          foreach ($abc['languages'] as $lid => $ldata) {
              if (isset($set[(int)$lid])) {
                  $enabled_lang_urls[] = (string)$ldata['url'];
              }
          }
      }
      if (empty($enabled_lang_urls)) {
          foreach ($abc['languages'] as $lid => $ldata) {
              $enabled_lang_urls[] = (string)$ldata['url'];
          }
      }
      $enabled_lang_urls = array_values(array_filter(array_unique($enabled_lang_urls)));

      /** @var array<string,true> Dedupe alternate hreflangs (fixes duplicate `<link>` when two DB rows collapse to same code). */
      $aviator_seen_hreflang_codes = array();

      $seo_origin = aviator_seo_public_origin();
      $canon_path = $_aviator_lang_base;
      $_canon_ln = aviator_template_links_for_lang($abc, isset($abc['lang']['url']) ? $abc['lang']['url'] : '');
      if ($_canon_ln !== null) {
          $canon_path = preg_replace('#/+#', '/', '/' . implode('/', $_canon_ln) . '/');
      }
      $canonical_href = $seo_origin . $canon_path;

      // x-default → same logical page in source (translation_settings) / highest-rank language — not the current locale URL on every version
      $xdef_lang_url = '';
      $src_id = (is_array($ts_settings) && !empty($ts_settings['source_lang_id'])) ? (int)$ts_settings['source_lang_id'] : 0;
      if ($src_id > 0 && isset($abc['languages'][$src_id]['url'])) {
          $xdef_lang_url = (string)$abc['languages'][$src_id]['url'];
      }
      if ($xdef_lang_url === '' && !empty($abc['languages'])) {
          $first_lang = reset($abc['languages']);
          $xdef_lang_url = is_array($first_lang) && isset($first_lang['url']) ? (string)$first_lang['url'] : '';
      }
      $xdef_path = '/' . trim($xdef_lang_url, '/') . '/';
      $_xdef_ln = aviator_template_links_for_lang($abc, $xdef_lang_url);
      if ($_xdef_ln !== null) {
          $xdef_path = preg_replace('#/+#', '/', '/' . implode('/', $_xdef_ln) . '/');
      }
      $x_default_href = $seo_origin . $xdef_path;
?>
        <link rel='canonical' href='<?=htmlspecialchars($canonical_href)?>'>
        <link rel='alternate' hreflang='x-default' href='<?=htmlspecialchars($x_default_href)?>'>
<?php foreach ($enabled_lang_urls as $_hreflang_raw):
          $lu = trim((string)$_hreflang_raw, '/');
          if ($lu === '') continue;
          $path = '/' . $lu . '/';
          $_alt_ln = aviator_template_links_for_lang($abc, $_hreflang_raw);
          if ($_alt_ln !== null) {
              $path = preg_replace('#/+#', '/', '/' . implode('/', $_alt_ln) . '/');
          }
          $href = $seo_origin . $path;
          $hreflang = aviator_hreflang_code($abc, $_hreflang_raw);
          $hreflang_lc = strtolower($hreflang);
          if ($hreflang_lc !== '' && isset($aviator_seen_hreflang_codes[$hreflang_lc])) {
              continue;
          }
          $aviator_seen_hreflang_codes[$hreflang_lc] = true;
?>
        <link rel='alternate' hreflang='<?=htmlspecialchars($hreflang)?>' href='<?=htmlspecialchars($href)?>'>
<?php endforeach; ?>
<?php
      $_aviator_og_type = (!empty($abc['blog_single']) || !empty($abc['news_single']) || !empty($abc['game_single']) || !empty($abc['guide_single']) || !empty($abc['casino_single'])) ? 'article' : 'website';
      $_aviator_og_title = htmlspecialchars(($abc['page']['title'] ?? '') . $aviator_site_title_suffix, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      $_aviator_og_image_abs = htmlspecialchars(rtrim($seo_origin, '/') . '/assets/images/aviator-main.webp', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
        <meta property="og:type" content="<?=$_aviator_og_type?>">
        <meta property="og:url" content="<?=htmlspecialchars($canonical_href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?>">
        <meta property="og:title" content="<?=$_aviator_og_title?>">
        <meta property="og:description" content="<?=$_aviator_meta_desc_esc?>">
        <meta property="og:image" content="<?=$_aviator_og_image_abs?>">
        <meta property="og:site_name" content="<?= htmlspecialchars(!empty($abc['seo_structured']['site_name']) ? (string)$abc['seo_structured']['site_name'] : 'Aviator Log In', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?=$_aviator_og_title?>">
        <meta name="twitter:description" content="<?=$_aviator_meta_desc_esc?>">
        <meta name="twitter:image" content="<?=$_aviator_og_image_abs?>">
<?php
      // Structured data: WebPage + BreadcrumbList + FAQPage (from seo_structured)
      $canonical_url = $canonical_href;
      $seo_cfg = isset($abc['seo_structured']) && is_array($abc['seo_structured']) ? $abc['seo_structured'] : array();
      $site_name = !empty($seo_cfg['site_name']) ? $seo_cfg['site_name'] : 'Aviator Log In';
      $home_label = isset($seo_cfg['breadcrumbs']['home_label']) && $seo_cfg['breadcrumbs']['home_label'] !== '' ? $seo_cfg['breadcrumbs']['home_label'] : $site_name;
      if ($canonical_url) {
          $page_title = $abc['page']['title'];
          $page_desc = $abc['page']['description'];
          $breadcrumb_items = array(
              array(
                  '@type' => 'ListItem',
                  'position' => 1,
                  'name' => $home_label,
                  'item' => (isset($seo_cfg['canonical_base']) && $seo_cfg['canonical_base'] !== '' ? rtrim($seo_cfg['canonical_base'], '/') : ('https://' . $_SERVER['HTTP_HOST'])) . '/'
              ),
              array(
                  '@type' => 'ListItem',
                  'position' => 2,
                  'name' => $page_title,
                  'item' => $canonical_url,
              ),
          );
          $graph = array(
              array(
                  '@context' => 'https://schema.org',
                  '@type' => 'WebPage',
                  'name' => $page_title,
                  'description' => $page_desc,
                  'url' => $canonical_url,
              ),
              array(
                  '@context' => 'https://schema.org',
                  '@type' => 'BreadcrumbList',
                  'itemListElement' => $breadcrumb_items,
              ),
          );
          $faq_entities = array();
          if (!empty($seo_cfg['faq']) && is_array($seo_cfg['faq'])) {
              foreach ($seo_cfg['faq'] as $row) {
                  if (empty($row['q']) || empty($row['a'])) continue;
                  $faq_entities[] = array(
                      '@type' => 'Question',
                      'name' => $row['q'],
                      'acceptedAnswer' => array(
                          '@type' => 'Answer',
                          'text' => $row['a'],
                      ),
                  );
              }
          }
          if (!empty($faq_entities)) {
              $faq_schema = array(
                  '@context' => 'https://schema.org',
                  '@type' => 'FAQPage',
                  'mainEntity' => $faq_entities,
              );
          } else {
              $faq_schema = null;
          }
?>
        <script type="application/ld+json"><?=
            htmlspecialchars(json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_NOQUOTES, 'UTF-8');
        ?></script>
<?php if ($faq_schema): ?>
        <script type="application/ld+json"><?=
            htmlspecialchars(json_encode($faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_NOQUOTES, 'UTF-8');
        ?></script>
<?php endif; ?>
<?php } ?>
        <?php if (!empty($abc['ad_partner'])): ?>
<?= site_template_deferred_stylesheet('/assets/css/ad-banner.css?v=' . (isset($getV) && isset($r) ? $getV($r.'assets/css/ad-banner.css') : time())) ?>
        <?php endif; ?>
    </head>
    <body class="layout-<?= htmlspecialchars($abc['layout'] ?? 'default') ?>">
<?php
if (!empty($abc['counters_body'])) {
	foreach ($abc['counters_body'] as $_counter) {
		echo $_counter . "\n";
	}
}
?>
<?php
// Language switcher: show only enabled languages; if no translation for current page -> /{lang}/
$enabled_lang_urls = array();
$enabled_lang_ids = array();
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$row_ts = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
	if ($row_ts && $row_ts['value'] !== '') {
		$ts = json_decode($row_ts['value'], true);
		if (is_array($ts) && !empty($ts['enabled_lang_ids']) && is_array($ts['enabled_lang_ids'])) {
			$enabled_lang_ids = array_values(array_filter(array_map('intval', $ts['enabled_lang_ids'])));
			$set = array_flip($enabled_lang_ids);
			foreach ($abc['languages'] as $lid => $ldata) {
				if (isset($set[(int)$lid])) $enabled_lang_urls[] = (string)$ldata['url'];
			}
		}
	}
}
if (empty($enabled_lang_urls)) {
	foreach ($abc['languages'] as $lid => $ldata) $enabled_lang_urls[] = (string)$ldata['url'];
}
$enabled_lang_urls = array_values(array_filter(array_unique($enabled_lang_urls)));

// Language switcher rows: href, native label, flag country code (flagcdn)
$aviator_lang_switcher_items = array();
foreach ($enabled_lang_urls as $_switch_raw) {
	$lu = trim((string)$_switch_raw, '/');
	if ($lu === '') {
		continue;
	}
	$dbname = '';
	foreach ($abc['languages'] as $ldata) {
		if (trim((string)($ldata['url'] ?? ''), '/') === $lu) {
			$dbname = isset($ldata['name']) ? (string)$ldata['name'] : '';
			break;
		}
	}
	$path = '/' . $lu . '/';
	$_switch_ln = aviator_template_links_for_lang($abc, $_switch_raw);
	if ($_switch_ln !== null) {
		$path = preg_replace('#/+#', '/', '/' . implode('/', $_switch_ln) . '/');
	}
	$aviator_lang_switcher_items[$lu] = array(
		'href' => $path,
		'label' => aviator_lang_switcher_label($lu, $dbname),
		'flag_cc' => aviator_lang_flag_country_code($lu),
	);
}
$_aviator_cur_lu = $_aviator_lang_seg !== '' ? $_aviator_lang_seg : trim((string)($abc['lang']['url'] ?? ''), '/');
$_aviator_cur_switch = ($_aviator_cur_lu !== '' && isset($aviator_lang_switcher_items[$_aviator_cur_lu]))
	? $aviator_lang_switcher_items[$_aviator_cur_lu]
	: (count($aviator_lang_switcher_items) ? reset($aviator_lang_switcher_items) : null);
?>

        <!-- header section start -->
        <header id="header">
            <nav class="navbar navbar-expand-lg navbar-light ">
                <div class="container">
                    <a class="navbar-brand" href="<?= htmlspecialchars($_aviator_lang_base, ENT_QUOTES, 'UTF-8') ?>">
                        <?php
                        $logo_v = isset($r, $getV) ? $getV($r.'assets/images/logo.png') : (defined('ROOT_DIR') && file_exists(ROOT_DIR.'assets/images/logo.png') ? filemtime(ROOT_DIR.'assets/images/logo.png') : time());
                        ?>
                        <img src="/assets/images/logo.png?v=<?= $logo_v ?>" alt="Aviator Logo" title="Aviator Logo">
                    </a>
                    <div class="menu-toggle" role="button" tabindex="0" aria-label="Toggle navigation" id="navbarToggler" onclick="window.aviatorBurgerTap&amp;&amp;window.aviatorBurgerTap(event)" ontouchend="window.aviatorBurgerTap&amp;&amp;window.aviatorBurgerTap(event)"><i class="fa fa-bars"></i></div>
                    <div class="navbar-collapse justify-content-end navbarNav" id="navbarNav">
<?=html_render('menu/list',$abc['menu'])?>
                        <div class="aviator-lang-switcher-mobile">
                            <div class="aviator-lang-mobile-title">Language</div>
                            <ul class="aviator-lang-mobile-list">
<?php foreach ($aviator_lang_switcher_items as $lu => $sw):
	$href = $sw['href'];
	$lbl = $sw['label'];
	$fcc = isset($sw['flag_cc']) ? $sw['flag_cc'] : null;
?>
                                <li>
                                    <a class="aviator-lang-mobile-link aviator-lang-item<?=($lu === $_aviator_lang_seg) ? ' active' : ''?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($fcc !== null && $fcc !== ''): ?>
                                        <?= site_template_lang_flag_img($fcc, $lbl, $lu !== $_aviator_lang_seg) ?>
<?php endif; ?>
                                        <span><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></span>
                                    </a>
                                </li>
<?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="aviator-lang-switcher desktop-only dropdown" style="margin-left:16px;">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle aviator-lang-toggle" type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true"<?php if ($_aviator_cur_switch): ?> aria-label="<?= htmlspecialchars('Language: ' . $_aviator_cur_switch['label'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
<?php if ($_aviator_cur_switch):
	$_cf = isset($_aviator_cur_switch['flag_cc']) ? $_aviator_cur_switch['flag_cc'] : null;
	if ($_cf !== null && $_cf !== ''): ?>
                            <?= site_template_lang_flag_img($_cf, $_aviator_cur_switch['label'], false) ?>
<?php endif; ?>
                            <span class="aviator-lang-toggle-text"><?= htmlspecialchars($_aviator_cur_switch['label'], ENT_QUOTES, 'UTF-8') ?></span>
<?php else: ?>
                            <?= htmlspecialchars(mb_strtoupper($_aviator_cur_lu !== '' ? $_aviator_cur_lu : 'EN'), ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end aviator-lang-dropdown" aria-labelledby="langDropdown">
<?php foreach ($aviator_lang_switcher_items as $lu => $sw):
	$href = $sw['href'];
	$lbl = $sw['label'];
	$fcc = isset($sw['flag_cc']) ? $sw['flag_cc'] : null;
?>
                            <li><a class="dropdown-item aviator-lang-item<?=($lu === $_aviator_lang_seg) ? ' active' : ''?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?php if ($fcc !== null && $fcc !== ''): ?><?= site_template_lang_flag_img($fcc, $lbl, $lu !== $_aviator_lang_seg) ?><?php endif; ?><span><?= htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8') ?></span></a></li>
<?php endforeach; ?>
                        </ul>
                    </div>

                </div>
            </nav>
        </header>
        <!-- header section end -->

        <main>
<?=html_render('layouts/'.$abc['layout'])?>
        </main>

        <!-- footer section start -->
        <footer>
            <div class="container">
                <nav class="footer-nav">
                    <a href="<?= htmlspecialchars($_aviator_lang_base, ENT_QUOTES, 'UTF-8') ?>about-us/"><?=i18n('common|footer_about_us')?></a>
                    <a href="<?= htmlspecialchars($_aviator_lang_base, ENT_QUOTES, 'UTF-8') ?>terms-and-conditions/"><?=i18n('common|footer_terms')?></a>
                    <a href="<?= htmlspecialchars($_aviator_lang_base, ENT_QUOTES, 'UTF-8') ?>privacy-policy/"><?=i18n('common|footer_privacy')?></a>
                    <a href="<?= htmlspecialchars($_aviator_lang_base, ENT_QUOTES, 'UTF-8') ?>responsible-gambling/"><?=i18n('common|footer_responsible')?></a>
                    <a href="<?= htmlspecialchars(rtrim($_aviator_lang_base, '/') . '/authors/', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(function_exists('author_list_title') ? author_list_title() : 'Authors', ENT_QUOTES, 'UTF-8') ?></a>
                </nav>
                <div class="footer-responsible">
                    <p><strong><?=i18n('common|footer_responsible')?>:</strong> <?=i18n('common|footer_responsible_text')?></p>
                    <p><strong><?=i18n('common|footer_play_label')?>:</strong> <?=i18n('common|footer_play_responsibly')?></p>
                </div>
                <h5><?=str_replace('{year}', date('Y'), i18n('common|footer_copyright'))?></h5>
            </div>
        </footer>
        <!-- footer section end -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous" defer></script>
        <button onclick="topFunction()" id="myBtn" title="<?=htmlspecialchars(i18n('common|go_to_top'))?>"><i class="fa-solid fa-jet-fighter-up"></i></button>
        <script src="/assets/js/script.js?v=<?= $getV($r.'assets/js/script.js') ?>" defer></script>
<?php
// Show popup when we have partner and something to show (banner and/or offer link)
$ad_has_popup = !empty($abc['advertising_api']['popup_enabled']) && !empty($abc['ad_partner']) && (!empty($abc['ad_offer_path']) || !empty($abc['ad_partner']['banner1_url']) || !empty($abc['ad_partner']['html']));
if ($ad_has_popup):
	$ad_caption = i18n('common|popup_join').'|'.i18n('common|popup_partner');
	$ad_caption_html = strpos($ad_caption, '|') !== false
		? '<p class="wd-banner-caption"><span class="wd-cap-part">' . htmlspecialchars(trim(explode('|', $ad_caption, 2)[0])) . '</span> <span class="wd-cap-highlight">' . htmlspecialchars(trim(explode('|', $ad_caption, 2)[1] ?? '')) . '</span></p>'
		: '<p class="wd-banner-caption"><span class="wd-cap-part">' . htmlspecialchars($ad_caption) . '</span></p>';
	$ad_click_url = !empty($abc['ad_offer_path']) ? $abc['ad_offer_path'] : '';
	// For banner image click use banner path (/go/CODE1BANNER1/) so backend gets &banner= for attribution; else offer path (link)
	$ad_banner_click_url = !empty($abc['ad_banner_click_path']) ? $abc['ad_banner_click_path'] : $ad_click_url;
	// banner1_url from API is often the image URL (e.g. promoimg?f=winwin.jpg), not the offer — use for click only if it doesn't look like an image
	$b1_url = isset($abc['ad_partner']['banner1_url']) ? (string)$abc['ad_partner']['banner1_url'] : '';
	// Our proxy URL (banner-img.php) or backend image URL → use banner click URL so backend counts as banner, not link
	$b1_looks_like_image = $b1_url !== '' && (strpos($b1_url, 'banner-img.php') !== false || preg_match('#/promoimg\b|\.(jpe?g|png|gif|webp)(\?|$)#i', $b1_url));
	$ad_popup_url = ($b1_url !== '' && !$b1_looks_like_image) ? $b1_url : $ad_banner_click_url;
	$ad_img_src = $b1_url !== '' ? htmlspecialchars($b1_url) : '';
	// Preserve ?debug_ads=1 on /go/ links so redirect debug page is shown
	$ad_popup_href = $ad_popup_url;
	if (!empty($abc['debug_ads']) && $ad_popup_url !== '' && strpos($ad_popup_url, 'banner-img.php') === false) {
		$ad_popup_href .= (strpos($ad_popup_url, '?') !== false ? '&' : '?') . 'debug_ads=1';
	}
	$ad_retention_html = isset($abc['ad_partner']['html']) ? str_replace('{link}', $ad_popup_url, $abc['ad_partner']['html']) : '';
	$ad_render_mode = isset($abc['ad_render_mode']) ? (string)$abc['ad_render_mode'] : 'banner';
	$placeholder_cta = i18n('common|cta_try_bonus');
	if ($placeholder_cta === '') {
		$placeholder_cta = 'Try Bonus';
	}
	$placeholder_title = i18n('common|popup_special_offer');
	if ($placeholder_title === '') {
		$placeholder_title = 'Take your special offer';
	}
	if ($ad_render_mode === 'placeholder') {
		$ad_img_src = '';
		$ad_retention_html = '<div class="wd-banner-placeholder"><p class="wd-banner-placeholder-title">' . htmlspecialchars($placeholder_title, ENT_QUOTES, 'UTF-8') . '</p><p><a href="' . htmlspecialchars($ad_popup_url, ENT_QUOTES, 'UTF-8') . '" class="wd-banner-placeholder-cta">' . htmlspecialchars($placeholder_cta, ENT_QUOTES, 'UTF-8') . '</a></p></div>';
	}
?>
        <div id="wd-banner-popup" class="wd-banner-popup" style="position:fixed;left:0;top:0;right:0;bottom:0;z-index:99998;background:rgba(0,0,0,0.5);display:grid;justify-content:center;align-items:center;opacity:0;pointer-events:none;transition:opacity 0.3s;">
            <div class="wd-banner-popup-inner">
                <?= $ad_caption_html ?>
                <?php if ($ad_img_src): ?><a href="<?= htmlspecialchars($ad_popup_href) ?>" class="wd-banner-popup-link"><img class="wd-banner-img" src="<?= $ad_img_src ?>" alt="<?= htmlspecialchars($placeholder_title, ENT_QUOTES, 'UTF-8') ?>" loading="lazy"></a><?php endif; ?>
                <?php if (!$ad_img_src && $ad_render_mode === 'placeholder'): ?><div class="wd-banner-retention-inner"><?= $ad_retention_html ?></div><?php endif; ?>
                <a href="#" class="wd-banner-popup-close" id="wd-banner-close" aria-label="<?=htmlspecialchars(i18n('common|aria_close'))?>"><i class="fa fa-times"></i></a>
            </div>
        </div>
        <div id="wd-banner-retention" class="wd-banner-retention" style="position:fixed;left:0;top:0;right:0;bottom:0;z-index:99999;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;padding:20px;">
            <a href="#" class="wd-banner-retention-close" id="wd-retention-close" aria-label="<?=htmlspecialchars(i18n('common|aria_close'))?>"><i class="fa fa-times"></i></a>
            <div class="wd-banner-retention-inner"><?= $ad_retention_html ?></div>
        </div>
        <script>
        (function(){
            var pop = document.getElementById('wd-banner-popup');
            var ret = document.getElementById('wd-banner-retention');
            var closeBtn = document.getElementById('wd-banner-close');
            var retClose = document.getElementById('wd-retention-close');
            var DELAY_MS = <?= (int)(isset($abc['advertising_api']['banner_popup_delay_seconds']) ? $abc['advertising_api']['banner_popup_delay_seconds'] : 30) ?> * 1000;
            function openPop() {
                if (pop) { pop.style.opacity = '1'; pop.style.pointerEvents = 'auto'; }
            }
            function closePop() {
                if (pop) { pop.style.opacity = '0'; pop.style.pointerEvents = 'none'; }
                if (ret) { ret.style.display = 'flex'; }
            }
            function closeRet() { if (ret) ret.style.display = 'none'; }
            if (closeBtn) closeBtn.addEventListener('click', function(e) { e.preventDefault(); closePop(); });
            if (retClose) retClose.addEventListener('click', function(e) { e.preventDefault(); closeRet(); });
            setTimeout(openPop, DELAY_MS);
        })();
        </script>
<?php endif; ?>
<?php if (!empty($abc['debug_info']) && is_array($abc['debug_info'])): ?>
        <div id="debug-panel" style="position:fixed;bottom:0;left:0;right:0;max-height:70vh;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:12px;font-family:monospace;font-size:12px;z-index:99999;border-top:2px solid #0e639c;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <strong style="color:#0e639c;">DEBUG ?debug=1</strong>
                <button type="button" onclick="document.getElementById('debug-panel').style.display='none'" style="background:#444;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Close</button>
            </div>
            <pre style="margin:0;white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(print_r($abc['debug_info'], true), ENT_QUOTES, 'UTF-8') ?></pre>
            <div style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #0e639c;border-radius:4px;">
                <strong style="color:#0e639c;">Burger / mobile menu (client-side)</strong>
                <pre id="debug-burger" style="margin:8px 0 0;white-space:pre-wrap;word-break:break-all;">Collecting...</pre>
                <button type="button" id="debug-burger-refresh" style="margin-top:8px;background:#0e639c;color:#fff;border:none;padding:4px 12px;cursor:pointer;font-size:12px;">Refresh</button>
                <button type="button" id="debug-burger-test" style="margin-top:8px;margin-left:8px;background:#0e639c;color:#fff;border:none;padding:4px 12px;cursor:pointer;font-size:12px;">Open menu (test)</button>
            </div>
        </div>
        <script>
        (function(){
            function debugBurger() {
                var out = [];
                var nav = document.getElementById('navbarNav');
                var toggle = document.querySelector('.menu-toggle');
                var header = document.getElementById('header');
                var main = document.querySelector('main');
                var vw = window.innerWidth || document.documentElement.clientWidth;

                out.push('Viewport width: ' + vw + 'px (mobile menu CSS applies when < 992px)');
                out.push('');

                var db = window._burgerDebug || {};
                out.push('Burger script state (script.js):');
                out.push('  inited: ' + (db.inited ? 'YES' : 'NO'));
                out.push('  toggle clicks: ' + (db.toggleClicks || 0));
                out.push('  toggle touchends: ' + (db.toggleTouches || 0));
                out.push('  → If you tapped burger but both stay 0, click is not reaching .menu-toggle.');
                out.push('');

                if (toggle && toggle.getBoundingClientRect) {
                    var tr = toggle.getBoundingClientRect();
                    var cx = Math.round(tr.left + tr.width / 2);
                    var cy = Math.round(tr.top + tr.height / 2);
                    var topEl = document.elementFromPoint(cx, cy);
                    var isToggle = topEl === toggle;
                    var isIcon = topEl && topEl.tagName === 'I' && topEl.closest && topEl.closest('.menu-toggle') === toggle;
                    out.push('Element under burger center (' + cx + ',' + cy + '):');
                    out.push('  tag: ' + (topEl ? topEl.tagName : 'null'));
                    out.push('  class: ' + (topEl && topEl.className ? topEl.className : '—'));
                    out.push('  is .menu-toggle: ' + (isToggle ? 'YES' : (isIcon ? 'icon inside toggle (OK)' : 'other — may block tap')));
                    out.push('');
                }

                out.push('Elements:');
                out.push('  #navbarNav: ' + (nav ? 'found' : 'NOT FOUND'));
                out.push('  .menu-toggle: ' + (toggle ? 'found' : 'NOT FOUND'));
                out.push('  #header: ' + (header ? 'found' : 'NOT FOUND'));
                out.push('  main: ' + (main ? 'found' : 'NOT FOUND'));
                out.push('');

                if (nav) {
                    var cn = nav.className || '';
                    var hasShow = cn.indexOf('show') !== -1;
                    var hasActive = cn.indexOf('active') !== -1;
                    var cs = window.getComputedStyle(nav);
                    out.push('#navbarNav:');
                    out.push('  classes: ' + cn);
                    out.push('  has .active: ' + hasActive + ' (needed for menu to slide in)');
                    out.push('  has .show: ' + hasShow);
                    out.push('  computed position: ' + cs.position);
                    out.push('  computed left: ' + cs.left);
                    out.push('  computed z-index: ' + cs.zIndex);
                    out.push('  computed display: ' + cs.display);
                    out.push('  computed visibility: ' + cs.visibility);
                    out.push('  getBoundingClientRect().left: ' + (nav.getBoundingClientRect ? nav.getBoundingClientRect().left : 'N/A'));
                }
                if (header) {
                    var hcs = window.getComputedStyle(header);
                    out.push('');
                    out.push('#header computed z-index: ' + hcs.zIndex);
                }
                if (main) {
                    var mcs = window.getComputedStyle(main);
                    out.push('main computed z-index: ' + mcs.zIndex + ' (stacking: ' + mcs.position + ')');
                }

                out.push('');
                out.push('Diagnosis:');
                if (!nav) {
                    out.push('  ERROR: #navbarNav not found — menu cannot work.');
                } else if (!toggle) {
                    out.push('  ERROR: .menu-toggle not found — burger button missing.');
                } else {
                    var cs = window.getComputedStyle(nav);
                    var navZ = parseInt(cs.zIndex, 10) || 0;
                    var mainZ = main ? (parseInt(window.getComputedStyle(main).zIndex, 10) || 0) : 0;
                    if (cs.position === 'fixed' && navZ > 0) {
                        if (mainZ >= navZ) {
                            out.push('  LIKELY: Menu is BEHIND main content (main z-index ' + mainZ + ' >= nav ' + navZ + '). Raise nav z-index or lower main.');
                        } else {
                            out.push('  Menu has position:fixed and z-index ' + navZ + '. If still not visible, check left (off-screen when closed) or display/visibility.');
                        }
                    } else if (cs.display === 'none') {
                        out.push('  LIKELY: Menu is display:none (Bootstrap collapse). Add .show by clicking burger or force display in CSS.');
                    } else if (parseFloat(cs.left) < -10) {
                        out.push('  Menu is off-screen (left: ' + cs.left + '). Tapping burger should add .active to #navbarNav. If toggle clicks/touches stay 0, tap is not reaching the button.');
                    } else {
                        out.push('  Nav visible (left on screen). If you do not see it, another element may be covering (check z-index above).');
                    }
                }

                var el = document.getElementById('debug-burger');
                if (el) el.textContent = out.join('\n');
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { setTimeout(debugBurger, 100); });
            } else {
                setTimeout(debugBurger, 100);
            }
            document.getElementById('debug-burger-refresh') && document.getElementById('debug-burger-refresh').addEventListener('click', debugBurger);
            document.getElementById('debug-burger-test') && document.getElementById('debug-burger-test').addEventListener('click', function() {
                if (window.aviatorBurgerTap) window.aviatorBurgerTap({ type: 'click', preventDefault: function(){}, stopPropagation: function(){} });
                setTimeout(debugBurger, 50);
            });
        })();
        </script>
<?php endif; ?>
<?php if (!empty($abc['debug_ip_banner_check']) && !empty($abc['debug_ip_banner_check_payload']) && is_array($abc['debug_ip_banner_check_payload'])): ?>
        <div id="debug-ip-banner-check-panel" style="position:fixed;bottom:0;left:0;right:0;max-height:55vh;overflow:auto;background:#0f172a;color:#cbd5e1;padding:10px 12px;font:12px/1.45 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;z-index:99996;border-top:2px solid #a855f7;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;gap:8px;flex-wrap:wrap;">
                <strong style="color:#c4b5fd;">DEBUG BANNER API (?debug_ip_banner_check=1)</strong>
                <span style="color:#94a3b8;font-weight:normal;">Full page: <code style="background:#1e293b;padding:2px 6px;">?debug_ip_banner_check_full=1</code></span>
                <button type="button" onclick="document.getElementById('debug-ip-banner-check-panel').style.display='none'" style="background:#334155;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Close</button>
            </div>
            <pre style="margin:0;white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode($abc['debug_ip_banner_check_payload'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
<?php endif; ?>
<?php if (!empty($abc['debug_ip_check']) && !empty($abc['debug_ip_check_info']) && is_array($abc['debug_ip_check_info'])): ?>
        <div id="debug-ip-check-panel" style="position:fixed;bottom:0;left:0;right:0;max-height:48vh;overflow:auto;background:#101827;color:#d1d5db;padding:10px 12px;font:12px/1.45 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;z-index:99997;border-top:2px solid #2563eb;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <strong style="color:#60a5fa;">DEBUG IP CHECK (?debug_ip_check=1)</strong>
                <button type="button" onclick="document.getElementById('debug-ip-check-panel').style.display='none'" style="background:#334155;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Close</button>
            </div>
            <pre style="margin:0;white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode($abc['debug_ip_check_info'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
<?php endif; ?>
<?php if (!empty($abc['debug_ads']) && !empty($abc['debug_ads_info']) && is_array($abc['debug_ads_info'])): ?>
        <div id="debug-ads-panel" style="position:fixed;bottom:0;left:0;right:0;max-height:70vh;overflow:auto;background:#1a2a1a;color:#d4d4d4;padding:12px;font-family:monospace;font-size:12px;z-index:99998;border-top:2px solid #2d7d46;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <strong style="color:#2d7d46;">DEBUG ADS ?debug_ads=1</strong>
                <button type="button" onclick="document.getElementById('debug-ads-panel').style.display='none'" style="background:#444;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Close</button>
            </div>
            <p style="margin:0 0 8px;color:#888;">Partner, paths, and API URLs (token masked). Click on banner/link with ?debug_ads=1 to see redirect debug page.</p>
            <pre style="margin:0;white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(print_r($abc['debug_ads_info'], true), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
<?php endif; ?>
<?php
if (!empty($abc['counters_footer'])) {
	foreach ($abc['counters_footer'] as $_counter) {
		echo $_counter . "\n";
	}
}
?>
    </body>
</html>