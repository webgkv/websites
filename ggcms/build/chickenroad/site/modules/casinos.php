<?php
/**
 * Casinos: listing (9 cards per page, pagination) or single casino article.
 * Uses table casino_articles. URL: /casinos/, /casinos/slug/
 */

$content_i18n_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
$current_lang_id = isset($abc['lang']['id']) ? (int)$abc['lang']['id'] : 1;
$casinos_base_lang_url = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';

// Bypass a potentially broken legacy layout file and use a fixed template.
$abc['layout'] = 'casinos_fixed';

function casinos_i18n_slug($u) {
	$u = trim((string)$u);
	if ($u === '') return '';
	$u = trim($u, '/');
	if ($u === '') return '';
	if (strpos($u, '/') !== false) {
		$parts = explode('/', $u);
		$u = (string)end($parts);
	}
	return trim($u);
}

function casinos_i18n_map($ids, $lang_id) {
	$ids = array_values(array_filter(array_map('intval', (array)$ids)));
	$lang_id = (int)$lang_id;
	if (empty($ids) || $lang_id <= 0) return array();
	$rows = mysql_select("
		SELECT entity_id, url, name, title, description, content, status
		FROM content_i18n
		WHERE entity='casino_articles'
		  AND lang_id=" . $lang_id . "
		  AND entity_id IN (" . implode(',', $ids) . ")
		ORDER BY
			(CASE WHEN CHAR_LENGTH(COALESCE(content,'')) > 0 THEN 0 ELSE 1 END) ASC,
			FIELD(status,'published','review','draft','missing') ASC,
			id DESC
	", 'rows') ?: array();
	$map = array();
	foreach ($rows as $r) {
		$eid = (int)$r['entity_id'];
		if (!isset($map[$eid])) $map[$eid] = $r;
	}
	return $map;
}

$casinos_base = function_exists('site_section_public_base')
	? site_section_public_base('casinos', $abc)
	: preg_replace('#/+#', '/', ($casinos_base_lang_url !== '' ? '/' . $casinos_base_lang_url . '/casinos/' : '/casinos/'));

if (!empty($u[3])) {
	$error++;
} elseif (!empty($u[2])) {
	// Single casino article: /casinos/slug/
	$slug = trim((string)$u[2], '/');

	$article = mysql_select("
		SELECT * FROM casino_articles
		WHERE display = 1 AND url = '" . mysql_res($slug) . "'
		LIMIT 1
	", 'row');

	// If not found by base url, try translated url (ci.url) for current language.
	if (!$article && $content_i18n_ok && $current_lang_id > 1) {
		$ci = mysql_select("
			SELECT entity_id, url, name, title, description, content, status
			FROM content_i18n
			WHERE entity='casino_articles'
			  AND lang_id=" . (int)$current_lang_id . "
			  AND url='" . mysql_res($slug) . "'
			ORDER BY
				(CASE WHEN CHAR_LENGTH(COALESCE(content,'')) > 0 THEN 0 ELSE 1 END) ASC,
				FIELD(status,'published','review','draft','missing') ASC,
				id DESC
			LIMIT 1
		", 'row');
		if ($ci) {
			$article = mysql_select("SELECT * FROM casino_articles WHERE id=" . (int)$ci['entity_id'] . " AND display=1 LIMIT 1", 'row');
			if ($article) {
				$article['_ci'] = $ci;
			}
		}
	}

	if ($article) {
		// Load translated fields by entity_id for current language (even if ci.url is empty).
		if ($content_i18n_ok && $current_lang_id > 1) {
			$gi = isset($article['_ci']) ? $article['_ci'] : mysql_select("
				SELECT url, name, title, description, content, status
				FROM content_i18n
				WHERE entity='casino_articles'
				  AND entity_id=" . (int)$article['id'] . "
				  AND lang_id=" . (int)$current_lang_id . "
				ORDER BY
					(CASE WHEN CHAR_LENGTH(COALESCE(content,'')) > 0 THEN 0 ELSE 1 END) ASC,
					FIELD(status,'published','review','draft','missing') ASC,
					id DESC
				LIMIT 1
			", 'row');
			if ($gi) {
				if (!empty($gi['url'])) {
					$slug2 = casinos_i18n_slug($gi['url']);
					if ($slug2 !== '') $article['url'] = $slug2;
				}
				if (!empty($gi['name'])) $article['name'] = (string)$gi['name'];
				if (!empty($gi['title'])) $article['title'] = (string)$gi['title'];
				if (!empty($gi['description'])) {
					$article['name_2'] = (string)$gi['description'];
					$article['description'] = (string)$gi['description'];
				}
				if (!empty($gi['content'])) $article['text'] = (string)$gi['content'];
			}
		}

		$abc['breadcrumb'][] = array('name' => $article['name'], 'url' => preg_replace('#/+#', '/', $casinos_base . trim((string)$article['url'], '/') . '/'));
		$abc['page'] = array_merge($abc['page'], $article);
		$abc['casino_single'] = $article;

		$abc['casino_single']['text'] = isset($article['text']) ? $article['text'] : '';
		if (!empty($abc['ad_offer_path']) && function_exists('aviator_ad_replace_content_links')) {
			$abc['casino_single']['text'] = aviator_ad_replace_content_links($abc['casino_single']['text'], $abc['ad_offer_path']);
		}

		foreach ($abc['languages'] as $i => $v) {
			$abc['links'][$abc['languages'][$i]['url']][] = $article['url'];
		}
	} else {
		$error++;
	}
} else {
	// List: 9 per page, pagination via $_GET['n'] (URL segment /casinos/2/ etc.)
	$abc['casino_list_data'] = mysql_data(
		"SELECT * FROM casino_articles WHERE display = 1 ORDER BY position DESC, date DESC, id DESC",
		false,
		9,
		isset($_GET['n']) ? (int)$_GET['n'] : 1
	);
	$abc['casino_list'] = isset($abc['casino_list_data']['list']) ? $abc['casino_list_data']['list'] : array();
	$abc['casino_pagination'] = $abc['casino_list_data'];

	// English listing (/en/…): overlay card snippet from content_i18n.description for the *current* language row.
	// Use URL segment (en), not lang id === 1 — on some DBs English is not id 1, and gating on
	// translation_settings.source_lang_id also broke overlays when source ≠ EN id.
	$lang_url = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';
	$is_en_listing = ($lang_url === 'en');
	if (!empty($abc['casino_list']) && $content_i18n_ok && $is_en_listing) {
		$ids = array();
		foreach ($abc['casino_list'] as $c) {
			$ids[] = (int)$c['id'];
		}
		$i18n_map = casinos_i18n_map($ids, $current_lang_id);
		foreach ($abc['casino_list'] as &$c) {
			$cid = (int)$c['id'];
			if (isset($i18n_map[$cid])) {
				$ci = $i18n_map[$cid];
				$desc = isset($ci['description']) ? trim((string)$ci['description']) : '';
				if ($desc !== '') {
					$c['name_2'] = $desc;
				}
			}
			// Presentation: USD policy — stale casino_articles.name_2 may still contain ₦.
			if (!empty($c['name_2']) && is_string($c['name_2']) && strpos($c['name_2'], '₦') !== false) {
				$c['name_2'] = str_replace('₦', '$', $c['name_2']);
			}
		}
		unset($c);
	}

	// Multilingual: for non-canonical languages show only translated cards.
	if (!empty($abc['casino_list']) && $content_i18n_ok && $current_lang_id > 1) {
		$ids = array();
		foreach ($abc['casino_list'] as $c) $ids[] = (int)$c['id'];
		$i18n_map = casinos_i18n_map($ids, $current_lang_id);

		$translated = array();
		foreach ($abc['casino_list'] as $c) {
			$cid = (int)$c['id'];
			if (!isset($i18n_map[$cid])) continue;
			$ci = $i18n_map[$cid];
			$has_content = !empty($ci['content']) && trim((string)$ci['content']) !== '';
			if (!$has_content) continue;

			// Override preview fields for the current language.
			if (!empty($ci['url'])) {
				$slug2 = casinos_i18n_slug($ci['url']);
				if ($slug2 !== '') $c['url'] = $slug2;
			}
			if (!empty($ci['name'])) $c['name'] = (string)$ci['name'];
			if (!empty($ci['description'])) $c['name_2'] = (string)$ci['description'];
			// Keep base text; card doesn't display it.

			$translated[] = $c;
		}

		$abc['casino_list'] = $translated; // if empty => show "No casinos"
	}
}
