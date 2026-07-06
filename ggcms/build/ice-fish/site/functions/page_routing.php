<?php
/**
 * Flat URL routing for nested `pages` rows (e.g. guides children at /{lang}/{slug}/).
 */

if (!function_exists('aviator_guide_category_slugs')) {
	function aviator_guide_category_slugs() {
		return array('analysis', 'bonus', 'how-to-win', 'signals', 'crash-gambling');
	}
}

/**
 * True when the current page was loaded via /{lang}/{slug}/ (no extra path segments).
 */
if (!function_exists('aviator_page_resolved_via_flat_url')) {
	function aviator_page_resolved_via_flat_url($abc, $u) {
		if (empty($abc['page']) || !is_array($abc['page'])) {
			return false;
		}
		if ((string)($abc['page']['module'] ?? '') !== 'pages') {
			return false;
		}
		if ((int)($abc['page']['level'] ?? 0) <= 1) {
			return false;
		}
		$seg2 = isset($u[2]) ? trim((string)$u[2], '/') : '';
		return $seg2 === '';
	}
}

/**
 * 301 /{lang}/guides/{page-slug}/ → /{lang}/{page-slug}/ for nested pages (not guide categories).
 */
if (!function_exists('aviator_guides_flat_page_redirect_if_needed')) {
	function aviator_guides_flat_page_redirect_if_needed($u, $lang, $request_url) {
		global $config, $langid;

		$seg1 = isset($u[1]) ? trim((string)$u[1], '/') : '';
		$seg2 = isset($u[2]) ? trim((string)$u[2], '/') : '';
		$seg3 = isset($u[3]) ? trim((string)$u[3], '/') : '';
		if ($seg1 !== 'guides' || $seg2 === '' || $seg3 !== '') {
			return;
		}
		if (in_array($seg2, aviator_guide_category_slugs(), true)) {
			return;
		}

		$current_lang_id = isset($lang['id']) ? (int)$lang['id'] : 1;
		$slug_esc = mysql_res($seg2);
		$page = null;

		if ($current_lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$page = mysql_select("
				SELECT p.id, p.level, p.left_key, p.right_key
				FROM pages p
				WHERE p.display=1 AND p.module='pages'
				  AND (
					p.url='{$slug_esc}'
					OR EXISTS (
						SELECT 1 FROM content_i18n ci
						WHERE ci.entity='pages'
						  AND ci.entity_id=p.id
						  AND ci.lang_id={$current_lang_id}
						  AND ci.status IN ('published','review','draft','missing')
						  AND (
							ci.url='{$slug_esc}'
							OR ci.url='/{$slug_esc}'
							OR ci.url='/{$slug_esc}/'
							OR ci.url='{$slug_esc}/'
						  )
						LIMIT 1
					)
				  )
				LIMIT 1
			", 'row', 0);
		} else {
			$where = "url='{$slug_esc}'";
			if ($langid !== '') {
				$col = 'url' . $langid;
				if (mysql_select("SHOW COLUMNS FROM pages LIKE '" . mysql_res($col) . "'", 'num_rows') > 0) {
					$where = "({$col}='{$slug_esc}' OR url='{$slug_esc}')";
				}
			}
			$page = mysql_select("
				SELECT id, level, left_key, right_key
				FROM pages
				WHERE display=1 AND module='pages' AND {$where}
				LIMIT 1
			", 'row', 0);
		}

		if (!$page) {
			return;
		}

		$guides_landing = mysql_select("
			SELECT id
			FROM pages
			WHERE display=1 AND module='pages'
			  AND (url='guides' OR url1='guides' OR url2='guides' OR url3='guides')
			LIMIT 1
		", 'row', 0);
		if ($guides_landing && (int)$page['id'] === (int)$guides_landing['id']) {
			return;
		}

		$lang_seg = isset($lang['url']) ? trim((string)$lang['url'], '/') : '';
		$to = ($lang_seg !== '' ? '/' . $lang_seg : '') . '/' . $seg2 . '/';
		$qs = (isset($request_url[1]) && $request_url[1] !== '') ? '?' . $request_url[1] : '';
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' . (isset($config['http_domain']) ? $config['http_domain'] : '') . $to . $qs);
		exit;
	}
}

/**
 * hreflang + language switcher: nested pages under guides use flat /{lang}/{slug}/, not /{lang}/guides/{slug}/.
 */
if (!function_exists('aviator_apply_flat_page_seo_links')) {
	function aviator_apply_flat_page_seo_links(&$abc, $u) {
		if (!aviator_page_resolved_via_flat_url($abc, $u)) {
			return;
		}
		if (empty($abc['languages']) || !is_array($abc['languages'])) {
			return;
		}

		$page_id = (int)$abc['page']['id'];
		foreach ($abc['languages'] as $i => $v) {
			$langUrl = $abc['languages'][$i]['url'];
			$lid = (int)$abc['languages'][$i]['id'];
			$slug = function_exists('page_i18n_slug') ? page_i18n_slug($page_id, $lid) : null;
			if ($slug === null || $slug === '') {
				$legacy = isset($abc['page']['url' . ($i > 1 ? $i : '')])
					? trim((string)$abc['page']['url' . ($i > 1 ? $i : '')], '/')
					: '';
				if ($legacy !== '') {
					$slug = $legacy;
				}
			}
			if ($slug === null || $slug === '') {
				$abc['links'][$langUrl] = array($langUrl);
			} else {
				$abc['links'][$langUrl] = array($langUrl, $slug);
			}
		}

		if (!empty($abc['breadcrumb']) && function_exists('page_i18n_fields_current')) {
			$last_idx = count($abc['breadcrumb']) - 1;
			if ($last_idx >= 0) {
				$pi = page_i18n_fields_current($abc['page'], (int)$abc['lang']['id']);
				$leaf = trim((string)($pi['url'] ?? ''), '/');
				if ($leaf === '' && isset($abc['page']['url'])) {
					$leaf = trim((string)$abc['page']['url'], '/');
				}
				if ($leaf !== '') {
					$abc['breadcrumb'][$last_idx]['url'] = get_url('index') . $leaf . '/';
				}
			}
		}
	}
}
