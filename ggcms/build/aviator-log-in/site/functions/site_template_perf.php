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

if (!function_exists('site_template_lang_flag_img')) {
	/**
	 * Language switcher flag. Non-current flags use data-src (loaded on menu/dropdown open).
	 */
	function site_template_lang_flag_img($flag_cc, $label, $defer = false) {
		$cc = strtolower((string) $flag_cc);
		if ($cc === '') {
			return '';
		}
		$url = 'https://flagcdn.com/24x18/' . $cc . '.png';
		$alt = htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8');
		$eh = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		if ($defer) {
			return '<img class="aviator-lang-flag aviator-lang-flag--deferred" data-src="' . $eh . '" width="24" height="18" alt="' . $alt . '" decoding="async">';
		}
		return '<img class="aviator-lang-flag" src="' . $eh . '" width="24" height="18" alt="' . $alt . '" decoding="async">';
	}
}

if (!function_exists('site_template_counters_include_onesignal')) {
	function site_template_counters_include_onesignal($counters) {
		if (empty($counters) || !is_array($counters)) {
			return false;
		}
		foreach ($counters as $html) {
			$h = (string) $html;
			if (stripos($h, 'OneSignal') !== false || stripos($h, 'onesignal.com') !== false) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('site_template_deferred_counters_script')) {
	/**
	 * Inject DB counters (GTM, analytics, OneSignal, …) after window load so a slow
	 * third-party (e.g. Microsoft Clarity via GTM) cannot block the tab spinner or LCP.
	 */
	function site_template_deferred_counters_script($counters) {
		if (empty($counters) || !is_array($counters)) {
			return '';
		}
		$html = '';
		foreach ($counters as $counter) {
			$html .= site_template_async_counter((string) $counter) . "\n";
		}
		if (trim($html) === '') {
			return '';
		}
		$json = json_encode($html, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		if ($json === false) {
			return $html;
		}
		return '        <script>
        window.addEventListener("load", function () {
          var html = ' . $json . ';
          var wrap = document.createElement("div");
          wrap.innerHTML = html;
          Array.prototype.slice.call(wrap.querySelectorAll("script")).forEach(function (old) {
            var s = document.createElement("script");
            Array.prototype.slice.call(old.attributes).forEach(function (a) {
              s.setAttribute(a.name, a.value);
            });
            s.textContent = old.textContent;
            document.head.appendChild(s);
          });
        });
        </script>' . "\n";
	}
}

if (!function_exists('site_template_service_worker_bootstrap_script')) {
	/**
	 * Register combined PWA SW only when OneSignal is absent (OneSignal.init registers /sw.js itself).
	 */
	function site_template_service_worker_bootstrap_script($median_native_shell, $counters_head) {
		$median = $median_native_shell ? 'true' : 'false';
		$skip_register = site_template_counters_include_onesignal($counters_head) ? 'true' : 'false';
		return '        <script>
        (function () {
          if (!(\'serviceWorker\' in navigator)) return;
          if (' . $median . ') {
            navigator.serviceWorker.getRegistrations().then(function (regs) {
              regs.forEach(function (r) { r.unregister(); });
            }).catch(function () {});
            return;
          }
          if (' . $skip_register . ') return;
          window.addEventListener(\'load\', function () {
            navigator.serviceWorker.register(\'/sw.js\').catch(function () {});
          });
        })();
        </script>';
	}
}
