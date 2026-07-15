<?php

/**
 * GTM dataLayer helpers for CTA click analytics.
 *
 * Event schema (pushed to window.dataLayer):
 *   event: cta_click | cta_page_view
 *   page_key, page_lang, page_path
 *   button_slot (001..), button_role, button_variant
 */

if (!function_exists('site_cta_normalize_slot')) {
	function site_cta_normalize_slot($slot): string {
		$digits = preg_replace('/\D+/', '', (string) $slot);
		if ($digits === '') {
			$digits = '1';
		}
		$digits = substr($digits, -3);
		return str_pad($digits, 3, '0', STR_PAD_LEFT);
	}
}

if (!function_exists('site_cta_normalize_role')) {
	function site_cta_normalize_role(string $role): string {
		$role = strtolower(trim($role));
		$role = preg_replace('/[^a-z0-9_]+/', '_', $role);
		$role = trim($role, '_');
		return $role !== '' ? $role : 'unknown';
	}
}

if (!function_exists('site_cta_normalize_variant')) {
	function site_cta_normalize_variant(string $variant): string {
		$variant = strtolower(trim($variant));
		$variant = preg_replace('/[^a-z0-9_]+/', '_', $variant);
		$variant = trim($variant, '_');
		return $variant !== '' ? $variant : 'text';
	}
}

if (!function_exists('site_cta_resolve_page_key')) {
	/**
	 * Stable page bucket for reporting (home, demo_app, blog, demo, guides, …).
	 */
	function site_cta_resolve_page_key(array $abc): string {
		$layout = isset($abc['layout']) ? strtolower(trim((string) $abc['layout'])) : '';
		$module = isset($abc['module']) ? strtolower(trim((string) $abc['module'])) : '';

		if ($layout === 'demo_app') {
			return 'demo_app';
		}
		if ($module === 'blog' || strpos($layout, 'blog') !== false) {
			return 'blog';
		}
		if ($module === 'guides' || $layout === 'guides') {
			return 'guides';
		}
		if ($layout === 'index' || (isset($abc['page']['module']) && (string) $abc['page']['module'] === 'index')) {
			return 'home';
		}
		if ($layout === 'demo') {
			return 'demo';
		}
		if ($layout !== '' && $layout !== 'page' && $layout !== 'default' && $layout !== 'error') {
			return preg_replace('/[^a-z0-9_-]+/', '_', $layout);
		}
		if ($module !== '') {
			return preg_replace('/[^a-z0-9_-]+/', '_', $module);
		}

		$url = isset($abc['page']['url']) ? strtolower(trim((string) $abc['page']['url'])) : '';
		if ($url !== '') {
			return preg_replace('/[^a-z0-9_-]+/', '_', $url);
		}

		return 'page';
	}
}

if (!function_exists('site_cta_resolve_page_lang')) {
	function site_cta_resolve_page_lang(array $abc): string {
		$lu = isset($abc['lang']['url']) ? trim((string) $abc['lang']['url'], '/') : '';
		return $lu !== '' ? $lu : 'en';
	}
}

if (!function_exists('site_cta_resolve_page_path')) {
	function site_cta_resolve_page_path(): string {
		$path = isset($_SERVER['REQUEST_URI']) ? preg_replace('#\?.*#', '', (string) $_SERVER['REQUEST_URI']) : '/';
		$path = preg_replace('#/+#', '/', $path === '' ? '/' : $path);
		return $path;
	}
}

if (!function_exists('site_cta_click_ref')) {
	/**
	 * Compact ref for ?cta= on /go/ links (not UTM).
	 */
	function site_cta_click_ref(string $page_key, string $slot, string $role): string {
		$page_key = preg_replace('/[^a-z0-9_-]+/i', '', strtolower($page_key));
		$slot = site_cta_normalize_slot($slot);
		$role = site_cta_normalize_role($role);
		if ($page_key === '') {
			$page_key = 'page';
		}
		return $page_key . '_' . $slot . '_' . $role;
	}
}

if (!function_exists('site_cta_append_url_param')) {
	function site_cta_append_url_param(string $url, string $name, string $value): string {
		$url = trim($url);
		$name = trim($name);
		$value = trim($value);
		if ($url === '' || $name === '' || $value === '') {
			return $url;
		}
		if (preg_match('/[?&]' . preg_quote($name, '/') . '=/', $url)) {
			return $url;
		}
		$sep = (strpos($url, '?') !== false) ? '&' : '?';
		return $url . $sep . rawurlencode($name) . '=' . rawurlencode($value);
	}
}

if (!function_exists('site_cta_offer_href')) {
	/**
	 * Offer /go/ href with optional ?cta= for server-side attribution.
	 */
	function site_cta_offer_href(string $offer_path, string $page_key, string $slot, string $role): string {
		$offer_path = trim($offer_path);
		if ($offer_path === '' || $offer_path[0] === '#') {
			return $offer_path;
		}
		return site_cta_append_url_param(
			$offer_path,
			'cta',
			site_cta_click_ref($page_key, $slot, $role)
		);
	}
}

if (!function_exists('site_cta_data_attrs')) {
	function site_cta_data_attrs(string $slot, string $role, string $variant = 'text'): string {
		$slot = site_cta_normalize_slot($slot);
		$role = site_cta_normalize_role($role);
		$variant = site_cta_normalize_variant($variant);
		return ' data-cta-slot="' . htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') . '"'
			. ' data-cta-role="' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '"'
			. ' data-cta-variant="' . htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') . '"';
	}
}

if (!function_exists('site_cta_guess_role_from_label')) {
	function site_cta_guess_role_from_label(string $text): string {
		$text = strtolower(trim($text));
		if ($text === '') {
			return 'cta';
		}
		$play = function_exists('i18n') ? strtolower(trim((string) i18n('common|cta_play_now'))) : 'play now';
		$bonus = function_exists('i18n') ? strtolower(trim((string) i18n('common|cta_try_bonus'))) : 'try bonus';
		if ($play !== '' && $text === $play) {
			return 'play_now';
		}
		if ($bonus !== '' && $text === $bonus) {
			return 'bonus';
		}
		if (strpos($text, 'download') !== false) {
			return 'download_nav';
		}
		if (strpos($text, 'game') !== false) {
			return 'games_nav';
		}
		return 'cta';
	}
}

if (!function_exists('site_cta_promo_button_html')) {
	function site_cta_promo_button_html(string $href, string $text, string $page_key, string $slot, string $role = '', string $variant = 'text'): string {
		$role = $role !== '' ? $role : site_cta_guess_role_from_label($text);
		$tracked_href = site_cta_is_trackable_href($href)
			? site_cta_offer_href($href, $page_key, $slot, $role)
			: $href;
		return '<div class="main_btn"><a href="' . htmlspecialchars($tracked_href, ENT_QUOTES, 'UTF-8') . '"'
			. site_cta_data_attrs($slot, $role, $variant) . '>'
			. htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a></div>';
	}
}

if (!function_exists('site_cta_is_trackable_href')) {
	function site_cta_is_trackable_href(string $href): bool {
		$href = trim($href);
		if ($href === '' || $href[0] === '#') {
			return false;
		}
		return (bool) preg_match('#/(?:[a-z]{2}/)?go/[0-9A-Za-z]{5}(?:1[0-9A-Za-z]{5})?/?#i', $href);
	}
}

if (!function_exists('site_cta_go_request_ref')) {
	function site_cta_go_request_ref(): string {
		if (!isset($_GET['cta'])) {
			return '';
		}
		$ref = trim((string) $_GET['cta']);
		if ($ref === '' || !preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/i', $ref)) {
			return '';
		}
		return $ref;
	}
}

if (!function_exists('site_cta_analytics_bootstrap_script')) {
	/**
	 * Init dataLayer page context + delegated click tracking for [data-cta-slot][data-cta-role].
	 */
	function site_cta_analytics_bootstrap_script(array $abc): string {
		$ctx = array(
			'page_key' => site_cta_resolve_page_key($abc),
			'page_lang' => site_cta_resolve_page_lang($abc),
			'page_path' => site_cta_resolve_page_path(),
		);
		$json = json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json === false) {
			$json = '{}';
		}

		return '<script>(function(){'
			. 'window.dataLayer=window.dataLayer||[];'
			. 'var ctx=' . $json . ';'
			. 'function pushCtx(extra){window.dataLayer.push(Object.assign({},ctx,extra||{}));}'
			. 'pushCtx({event:"cta_page_view"});'
			. 'document.addEventListener("click",function(e){'
			. 'var el=e.target&&e.target.closest?e.target.closest("[data-cta-slot][data-cta-role]"):null;'
			. 'if(!el)return;'
			. 'var slot=(el.getAttribute("data-cta-slot")||"").trim();'
			. 'var role=(el.getAttribute("data-cta-role")||"").trim();'
			. 'var variant=(el.getAttribute("data-cta-variant")||"text").trim()||"text";'
			. 'if(!slot||!role)return;'
			. 'pushCtx({event:"cta_click",button_slot:slot,button_role:role,button_variant:variant});'
			. '},true);'
			. '})();</script>' . "\n";
	}
}
