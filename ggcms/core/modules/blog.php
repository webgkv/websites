<?php

require_once ROOT_DIR . 'functions/blog_i18n_sql.php';

// AMP page URL handling
if (@$_GET['view'] == 'amp') {
	$config['amp'] = 1;
}

// Build blog base URL from public section slug (articles on PowerBall, blog on Chicken Road).
$blog_base = site_section_public_base('blog', $abc);

// Determine current numeric language id (index.php uses empty string for canonical lang_id=1)
$cur_lang_id = ($langid === '' || (string)$langid === '1') ? 1 : (int)$langid;
$cur_suffix = $cur_lang_id > 1 ? (string)$langid : '';

if ($u[3]) {
	// 404 if $u[3] is present
	$error++;
} elseif ($u[2]) {
	// Article page by slug
	if ($cur_lang_id > 1) {
		// For non-canonical languages use scalable i18n row (content_i18n) as source of truth.
		// Slug in URL may match canonical `blog.url`, English content_i18n (lang_id=1), or localized ci.url.
		$slug_sql = mysql_res($u[2]);
		$sql = "
			SELECT
				b.*,
				ci.url AS url{$cur_suffix},
				COALESCE(NULLIF(ci.name,''), NULLIF(ci.title,''), '') AS name{$cur_suffix},
				ci.title AS title{$cur_suffix},
				ci.description AS description{$cur_suffix},
				ci.description AS name_2{$cur_suffix},
				ci.content AS text{$cur_suffix}
			FROM blog b
			JOIN content_i18n ci
				ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int)$cur_lang_id . "
			WHERE b.date<='" . mysql_res(date('Y-m-d H:i:s')) . "'
			  AND b.display=1
			  AND ci.status " . blog_i18n_ci_status_in_sql() . "
			  AND TRIM(COALESCE(ci.content,''))!=''
			  AND " . blog_i18n_slug_match_where_sql($slug_sql, 'ci') . "
			LIMIT 1
		";
	} else {
		$sql = "
			SELECT *
			FROM blog
			WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' and url$langid = '" . mysql_res($u[2]) . "' AND display = 1
			LIMIT 1
		";
	}
	if ($news = mysql_select($sql, 'row')) {

		$abc['page'] = array_merge($abc['page'], $news);
		$abc['blog_single'] = true;

		// Breadcrumbs
		$abc['breadcrumb'][] = array(
			'name' => $abc['page']["name$langid"],
			'url' => $blog_base
				. trim((string)($abc['page']["url$langid"] ?? ''), '/')
				. '/'
		);

		// Public blog articles now live at /{lang}/blog/{slug}/ without category segments.
		// Build language switcher + canonical/hreflang links from scalable content_i18n slugs.
		$blog_slug_map = array();
		$canonical_slug = blog_i18n_slug($news['url'] ?? '');
		if ($canonical_slug !== '') {
			$blog_slug_map[blog_i18n_canonical_lang_id()] = $canonical_slug;
		}
		if (!empty($news['id'])) {
			foreach (blog_i18n_slug_map((int)$news['id']) as $lid => $slug) {
				$blog_slug_map[(int)$lid] = $slug;
			}
		}
		foreach ($abc['languages'] as $i => $v) {
			$lang_url = trim((string)($abc['languages'][$i]['url'] ?? ''), '/');
			$lang_row_id = isset($abc['languages'][$i]['id']) ? (int)$abc['languages'][$i]['id'] : (int)$i;
			if ($lang_url === '') continue;
			if (!empty($blog_slug_map[$lang_row_id])) {
				$abc['links'][$lang_url] = array($lang_url, site_section_link_segment('blog'), $blog_slug_map[$lang_row_id]);
			} else {
				$abc['links'][$lang_url] = array($lang_url, site_section_link_segment('blog'));
			}
		}

		$abc['page']['text'] = $abc['page']["text$langid"];

		$text = template_img('blog', $abc['page']);

		if (preg_match('#^(.*?<p>.*?</p>.*?<p>.*?</p>)(.*)$#ius', $text, $m)) {
			$abc['page']['text1'] = $m[1];
			$abc['page']['text2'] = $m[2];
		} else {
			$abc['page']['text1'] = $text;
			$abc['page']['text2'] = '';
		}
		require_once(ROOT_DIR . 'functions/blog_promo.php');
		$abc['page']['blog_promo'] = blog_promo_random();
		require_once ROOT_DIR . 'functions/blog_internal_nav.php';
		blog_internal_links_apply($abc['page'], $blog_base, $cur_lang_id, $langid);

	} else $error++;

} else {
	// List of posts (`/{lang}/blog/`): nail hreflang + language switcher paths (must not fall back to `/{lang}/` alone).
	foreach ($abc['languages'] as $i => $_v) {
		$lang_url = trim((string)($abc['languages'][$i]['url'] ?? ''), '/');
		if ($lang_url !== '') {
			$abc['links'][$lang_url] = array($lang_url, site_section_link_segment('blog'));
		}
	}

	if ($cur_lang_id > 1) {
		$sql = "
			SELECT
				b.*,
				ci.url AS url{$cur_suffix},
				COALESCE(NULLIF(ci.name,''), NULLIF(ci.title,''), '') AS name{$cur_suffix},
				ci.title AS title{$cur_suffix},
				ci.description AS description{$cur_suffix},
				ci.description AS name_2{$cur_suffix},
				ci.content AS text{$cur_suffix}
			FROM blog b
			JOIN content_i18n ci
				ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int)$cur_lang_id . "
			WHERE b.date<='" . mysql_res(date('Y-m-d H:i:s')) . "'
			  AND b.display=1
			  AND ci.status " . blog_i18n_ci_status_in_sql() . "
			  AND IFNULL(ci.url,'')!=''
			  AND TRIM(COALESCE(ci.content,''))!=''
			ORDER BY b.date DESC
		";
	} else {
		$sql = "SELECT * FROM blog WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND url$langid!='' AND display = 1 ORDER BY date DESC";
	}
	$abc['blog'] = mysql_data($sql, false, 20, @$_GET['n']);
}
