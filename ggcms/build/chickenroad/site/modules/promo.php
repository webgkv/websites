<?php
/**
 * Promo: listing (active / archive tabs) or single push landing.
 * Table: promo. URLs: /promo/, /promo/{slug}/
 */

$content_i18n_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
$current_lang_id = isset($abc['lang']['id']) ? (int)$abc['lang']['id'] : 1;

if (!function_exists('promo_is_expired')) {
	function promo_is_expired($row) {
		if (!is_array($row)) {
			return false;
		}
		if (!empty($row['promo_unlimited'])) {
			return false;
		}
		$end = isset($row['date_end']) ? trim((string)$row['date_end']) : '';
		if ($end === '' || $end === '0000-00-00 00:00:00') {
			return false;
		}
		return strtotime($end) < time();
	}
}

if (!function_exists('promo_i18n_slug')) {
	function promo_i18n_slug($u) {
		$u = trim((string)$u, '/');
		if ($u === '') {
			return '';
		}
		if (strpos($u, '/') !== false) {
			$parts = explode('/', $u);
			$u = (string)end($parts);
		}
		return trim($u, '/');
	}
}

if (!function_exists('promo_apply_i18n_row')) {
	function promo_apply_i18n_row(array &$row, $lang_id, $content_i18n_ok) {
		$lang_id = (int)$lang_id;
		if (!$content_i18n_ok || $lang_id < 1) {
			return;
		}
		$gi = isset($row['_ci']) ? $row['_ci'] : mysql_select("
			SELECT url, name, title, description, content, status
			FROM content_i18n
			WHERE entity='promo'
			  AND entity_id=" . (int)$row['id'] . "
			  AND lang_id=" . $lang_id . "
			ORDER BY
				(CASE WHEN CHAR_LENGTH(COALESCE(content,'')) > 0 THEN 0 ELSE 1 END) ASC,
				FIELD(status,'published','review','draft','missing') ASC,
				id DESC
			LIMIT 1
		", 'row');
		if (!$gi) {
			return;
		}
		if (!empty($gi['url'])) {
			$slug2 = promo_i18n_slug($gi['url']);
			if ($slug2 !== '') {
				$row['url'] = $slug2;
			}
		}
		if (!empty($gi['name'])) {
			$row['name'] = (string)$gi['name'];
		}
		if (!empty($gi['title'])) {
			$row['title'] = (string)$gi['title'];
		}
		if (!empty($gi['description'])) {
			$row['name_2'] = (string)$gi['description'];
			$row['description'] = (string)$gi['description'];
		}
		if (!empty($gi['content'])) {
			$row['text'] = (string)$gi['content'];
		}
	}
}

if (!function_exists('promo_render_body_html')) {
	function promo_render_body_html($html) {
		$html = (string)$html;
		if ($html === '') {
			return '';
		}
		if (!function_exists('content_unwrap_exclude_tags')) {
			require_once ROOT_DIR . 'functions/content_exclude_tags.php';
		}
		if (function_exists('content_unwrap_exclude_tags')) {
			$html = content_unwrap_exclude_tags($html);
		}
		if (function_exists('site_seo_clean_content')) {
			$html = site_seo_clean_content($html);
		}
		return $html;
	}
}

$promo_base = function_exists('site_section_public_base')
	? site_section_public_base('promo', $abc)
	: preg_replace('#/+#', '/', (isset($abc['lang']['url']) && trim((string)$abc['lang']['url'], '/') !== ''
		? '/' . trim((string)$abc['lang']['url'], '/') . '/promo/'
		: '/promo/'));

if (!empty($u[3])) {
	$error++;
} elseif (!empty($u[2])) {
	$slug = trim((string)$u[2], '/');

	$article = mysql_select("
		SELECT * FROM promo
		WHERE display = 1 AND url = '" . mysql_res($slug) . "'
		LIMIT 1
	", 'row');

	if (!$article && $content_i18n_ok && $current_lang_id > 1) {
		$ci = mysql_select("
			SELECT entity_id, url, name, title, description, content, status
			FROM content_i18n
			WHERE entity='promo'
			  AND lang_id=" . (int)$current_lang_id . "
			  AND url='" . mysql_res($slug) . "'
			ORDER BY
				(CASE WHEN CHAR_LENGTH(COALESCE(content,'')) > 0 THEN 0 ELSE 1 END) ASC,
				FIELD(status,'published','review','draft','missing') ASC,
				id DESC
			LIMIT 1
		", 'row');
		if ($ci) {
			$article = mysql_select("SELECT * FROM promo WHERE id=" . (int)$ci['entity_id'] . " AND display=1 LIMIT 1", 'row');
			if ($article) {
				$article['_ci'] = $ci;
			}
		}
	}

	if ($article) {
		$article_id_for_links = (int)$article['id'];
		$promo_slug_map = array();
		if ($article_id_for_links > 0) {
			$canonical_row = mysql_select("SELECT url FROM promo WHERE id=" . $article_id_for_links . " LIMIT 1", 'row');
			if ($canonical_row) {
				$canonical_slug = promo_i18n_slug($canonical_row['url'] ?? '');
				if ($canonical_slug !== '') {
					$promo_slug_map[1] = $canonical_slug;
				}
			}
			if ($content_i18n_ok) {
				$slug_rows = mysql_select("
					SELECT lang_id, url
					FROM content_i18n
					WHERE entity='promo'
					  AND entity_id=" . $article_id_for_links . "
					  AND IFNULL(url,'') != ''
				", 'rows', 0);
				if ($slug_rows) {
					foreach ($slug_rows as $sr) {
						$slug2 = promo_i18n_slug($sr['url'] ?? '');
						if ($slug2 !== '') {
							$promo_slug_map[(int)$sr['lang_id']] = $slug2;
						}
					}
				}
			}
		}

		promo_apply_i18n_row($article, $current_lang_id, $content_i18n_ok);
		$article_id = (int)$article['id'];
		if ($article_id > 0) {
			$article['text'] = str_replace(array('{{PROMO_ID}}', '{{ID}}'), (string)$article_id, (string)($article['text'] ?? ''));
		}

		$abc['breadcrumb'][] = array(
			'name' => $article['name'],
			'url' => preg_replace('#/+#', '/', $promo_base . trim((string)$article['url'], '/') . '/'),
		);
		$abc['page'] = array_merge($abc['page'], $article);
		$abc['promo_single'] = $article;
		$abc['promo_single']['ended'] = promo_is_expired($article) || (isset($article['category']) && (string)$article['category'] === 'archive');

		$promo_section_seg = function_exists('site_section_link_segment')
			? site_section_link_segment('promo')
			: 'promo';
		foreach ($abc['languages'] as $i => $v) {
			$lang_url = trim((string)($abc['languages'][$i]['url'] ?? ''), '/');
			$lang_row_id = isset($abc['languages'][$i]['id']) ? (int)$abc['languages'][$i]['id'] : 0;
			if ($lang_url === '') {
				continue;
			}
			if (!empty($promo_slug_map[$lang_row_id])) {
				$abc['links'][$lang_url] = array($lang_url, $promo_section_seg, $promo_slug_map[$lang_row_id]);
			} else {
				$abc['links'][$lang_url] = array($lang_url, $promo_section_seg);
			}
		}
	} else {
		$error++;
	}
} else {
	$list_cat = isset($_GET['cat']) ? strtolower(trim((string)$_GET['cat'])) : 'active';
	if ($list_cat !== 'archive') {
		$list_cat = 'active';
	}
	$abc['promo_list_cat'] = $list_cat;

	if ($list_cat === 'archive') {
		$list_sql = "
			SELECT * FROM promo
			WHERE display = 1
			  AND (
			    category = 'archive'
			    OR (
			      IFNULL(promo_unlimited, 0) = 0
			      AND date_end IS NOT NULL
			      AND date_end != '0000-00-00 00:00:00'
			      AND date_end < NOW()
			    )
			  )
			ORDER BY position DESC, date DESC, id DESC
		";
	} else {
		$list_sql = "
			SELECT * FROM promo
			WHERE display = 1
			  AND category = 'active'
			  AND (
			    IFNULL(promo_unlimited, 0) = 1
			    OR date_end IS NULL
			    OR date_end = '0000-00-00 00:00:00'
			    OR date_end >= NOW()
			  )
			ORDER BY position DESC, date DESC, id DESC
		";
	}

	$abc['promo_list_data'] = mysql_data($list_sql, false, 9, isset($_GET['n']) ? (int)$_GET['n'] : 1);
	$abc['promo_list'] = isset($abc['promo_list_data']['list']) ? $abc['promo_list_data']['list'] : array();
	$abc['promo_pagination'] = $abc['promo_list_data'];

	$promo_section_seg = function_exists('site_section_link_segment')
		? site_section_link_segment('promo')
		: 'promo';
	foreach ($abc['languages'] as $i => $_v) {
		$lang_url = trim((string)($abc['languages'][$i]['url'] ?? ''), '/');
		if ($lang_url !== '') {
			$abc['links'][$lang_url] = array($lang_url, $promo_section_seg);
		}
	}

	if (!empty($abc['promo_list']) && $content_i18n_ok && $current_lang_id > 1) {
		foreach ($abc['promo_list'] as $idx => $item) {
			promo_apply_i18n_row($abc['promo_list'][$idx], $current_lang_id, $content_i18n_ok);
		}
	}
}
