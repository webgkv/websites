<?php

require_once ROOT_DIR . 'functions/blog_i18n_sql.php';
require_once ROOT_DIR . 'functions/content_year_macro.php';

$abc['gallery'] = mysql_select('select * from gallery', 'rows_id');

// AMP page URL handling
if (@$_GET['view'] == 'amp') {
	$config['amp'] = 1;
}

// Build blog base URL from public section slug (articles on PowerBall, blog on Chicken Road).
$blog_base = site_section_public_base('blog', $abc);
$cur_lang_id = ($langid === '' || (string)$langid === '1') ? 1 : (int)$langid;
$cur_suffix = $cur_lang_id > 1 ? (string)$langid : '';

if ($u[4]) {
	// 404 if $u[4] is present
	$error++;
} elseif ($u[3]) {
	// Subcategory/tag: /blog/cat/tag/
	$category = '';
	foreach ($abc['gcats'] as $ucat) if ($ucat["url$langid"] == $u[2]) $category = $ucat['id'];
	if ($category) {

		$abc['tags'] = mysql_select("select * from blog_tags where category=$category order by id asc", 'rows_id');

		$tag = '';
		foreach ($abc['tags'] as $utag) if ($utag["url$langid"] == $u[3]) $tag = $utag['id'];
		if ($tag) {
			$abc['blog'] = mysql_data(
				"SELECT * FROM blog WHERE date<='".date('Y-m-d H:i:s')."' and category=".$category." and (tag1=$tag or tag2=$tag or tag3=$tag or tag4=$tag) and display = 1 ORDER BY date DESC",
				false,
				3,
				@$_GET['n']
			);
			$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date desc LIMIT 3", 'rows');

			$abc['breadcrumb'][] = array(
				'name' => $abc['gcats'][$category]["name$langid"],
				'url' => $blog_base . trim((string)($abc['gcats'][$category]["url$langid"] ?? ''), '/') . '/'
			);
			$abc['breadcrumb'][] = array(
				'name' => $abc['tags'][$tag]["name$langid"],
				'url' => $blog_base
					. trim((string)($abc['gcats'][$category]["url$langid"] ?? ''), '/') . '/'
					. trim((string)($abc['tags'][$tag]["url$langid"] ?? ''), '/') . '/'
			);

			foreach ($abc['languages'] as $i => $v) {
				$abc['links'][$abc['languages'][$i]['url']][] = $abc['gcats'][$category]['url'.($i > 1 ? $i : '')];
				$abc['links'][$abc['languages'][$i]['url']][] = $abc['tags'][$tag]['url'.($i > 1 ? $i : '')];
			}

			$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_list' and url$langid!=''", 'rows');

			if (!isset($_COOKIE['popup'])) {
				$abc['popup'] = mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_list" and popup_places.display=1 order by rand() limit 1', 'string');
				if (!$abc['popup']) $abc['popup'] = mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1', 'string');
			}

		} else {

			// Legacy /blog/{category}/{article-slug}/ → 301 /blog/{article-slug}/ (categories no longer in public URLs)
			$legacy_slug = trim((string) $u[3], '/');
			if ($legacy_slug !== '') {
				if ($cur_lang_id > 1) {
					$leg_sql = mysql_res($legacy_slug);
					$sql_legacy_canon = "
						SELECT b.id
						FROM blog b
						JOIN content_i18n ci
							ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int) $cur_lang_id . "
						WHERE b.date<='" . mysql_res(date('Y-m-d H:i:s')) . "'
						  AND b.display=1
						  AND ci.status " . blog_i18n_ci_status_in_sql() . "
						  AND TRIM(COALESCE(ci.content,''))!=''
						  AND " . blog_i18n_slug_match_where_sql($leg_sql, 'ci') . "
						LIMIT 1
					";
				} else {
					$sql_legacy_canon = "
						SELECT id
						FROM blog
						WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND url$langid = '" . mysql_res($legacy_slug) . "' AND display = 1
						LIMIT 1
					";
				}
				if (mysql_select($sql_legacy_canon, 'row')) {
					$qs = (!empty($_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : '';
					header('HTTP/1.1 301 Moved Permanently');
					header('Location: ' . $blog_base . $legacy_slug . '/' . $qs);
					exit;
				}
			}

			// Article page: /blog/cat/article-url/
			if ($cur_lang_id > 1) {
				$art_sql = mysql_res($u[3]);
				$sqlArticleCat = "
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
					  AND b.category=" . (int)$category . "
					  AND b.display=1
					  AND ci.status " . blog_i18n_ci_status_in_sql() . "
					  AND TRIM(COALESCE(ci.content,''))!=''
					  AND " . blog_i18n_slug_match_where_sql($art_sql, 'ci') . "
					LIMIT 1
				";
			} else {
				$sqlArticleCat = "
					SELECT *
					FROM blog
					WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND category=" . (int)$category . " and url$langid = '" . mysql_res($u[3]) . "' AND display = 1
					LIMIT 1
				";
			}
			if ($news = mysql_select($sqlArticleCat, 'row')) {
				content_year_macro_apply_row($news, $langid);

				$abc['page'] = array_merge($abc['page'], $news);
				$abc['blog_single'] = true;

				$abc['breadcrumb'][] = array(
					'name' => $abc['gcats'][$news['category']]["name$langid"],
					'url' => $blog_base . trim((string)($abc['gcats'][$news['category']]["url$langid"] ?? ''), '/') . '/'
				);
				$abc['breadcrumb'][] = array(
					'name' => $abc['page']["name$langid"],
					'url' => $blog_base
						. trim((string)($abc['gcats'][$news['category']]["url$langid"] ?? ''), '/') . '/'
						. trim((string)($abc['page']["url$langid"] ?? ''), '/') . '/'
				);

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
				require_once ROOT_DIR . 'functions/blog_promo_guard.php';
				if (blog_promo_should_autoinsert_images($abc['page'])) {
					require_once(ROOT_DIR . 'functions/blog_promo.php');
					$abc['page']['blog_promo'] = blog_promo_random();
				}
				require_once ROOT_DIR . 'functions/blog_internal_nav.php';
				blog_internal_links_apply($abc['page'], $blog_base, $cur_lang_id, $langid);

			} else $error++;
		}
	} else $error++;

} elseif ($u[2]) {

	$category = '';
	foreach ($abc['gcats'] as $ucat) if ($ucat["url$langid"] == $u[2]) $category = $ucat['id'];
	if ($category) {

		// Category list: /blog/cat/
		if ($cur_lang_id > 1) {
			$sql_cat_list = "
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
					ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int) $cur_lang_id . "
				WHERE b.date<='" . mysql_res(date('Y-m-d H:i:s')) . "'
				  AND b.display=1
				  AND b.category=" . (int) $category . "
				  AND ci.status " . blog_i18n_ci_status_in_sql() . "
				  AND IFNULL(ci.url,'')!=''
				  AND TRIM(COALESCE(ci.content,''))!=''
				ORDER BY b.date DESC
			";
			$abc['blog'] = mysql_data($sql_cat_list, false, 3, @$_GET['n']);
		} else {
			$abc['blog'] = mysql_data(
				"SELECT * FROM blog WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND category=" . (int) $category . " and display = 1 ORDER BY date DESC",
				false,
				3,
				@$_GET['n']
			);
		}

		$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date desc LIMIT 3", 'rows');
		$abc['tags'] = mysql_select("select * from blog_tags where category=$category order by id asc", 'rows_id');

		$abc['breadcrumb'][] = array(
			'name' => $abc['gcats'][$category]["name$langid"],
			// Category URL must use `url$langid` (not `name$langid`) to match router.
			'url' => $blog_base . trim((string)($abc['gcats'][$category]["url$langid"] ?? ''), '/') . '/'
		);
		foreach ($abc['languages'] as $i => $v) {
			$abc['links'][$abc['languages'][$i]['url']][] = $abc['gcats'][$category]['url'.($i > 1 ? $i : '')];
		}

		$abc['page'] = array_merge($abc['page'], mysql_select("select * from blog_category where id=$category limit 1", 'row'));

		$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_list' and url$langid!=''", 'rows');

		if (!isset($_COOKIE['popup'])) {
			$abc['popup'] = mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_list" and popup_places.display=1 order by rand() limit 1', 'string');
			if (!$abc['popup']) $abc['popup'] = mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1', 'string');
		}

	} else {
		// Article by slug only: /blog/article-slug/ (no category in URL)
		// Router always loads this file (see index.php) — keep slug resolution in sync with modules/blog.php.
		if ($cur_lang_id > 1) {
			$slug_sql = mysql_res($u[2]);
			$sqlArticleSlug = "
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
			$sqlArticleSlug = "
				SELECT *
				FROM blog
				WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND url$langid = '" . mysql_res($u[2]) . "' AND display = 1
				LIMIT 1
			";
		}
		$news = mysql_select($sqlArticleSlug, 'row');
		if ($news) {
			content_year_macro_apply_row($news, $langid);
			$abc['page'] = array_merge($abc['page'], $news);
			$abc['blog_single'] = true;
			$abc['breadcrumb'][] = array(
				'name' => $abc['page']["name$langid"],
				// Article page by slug only: /{lang}/blog/<article-slug>/
				'url' => $blog_base . trim((string)($abc['page']["url$langid"] ?? ''), '/') . '/'
			);
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
			require_once ROOT_DIR . 'functions/blog_promo_guard.php';
			if (blog_promo_should_autoinsert_images($abc['page'])) {
				require_once(ROOT_DIR . 'functions/blog_promo.php');
				$abc['page']['blog_promo'] = blog_promo_random();
			}
			require_once ROOT_DIR . 'functions/blog_internal_nav.php';
			blog_internal_links_apply($abc['page'], $blog_base, $cur_lang_id, $langid);
		} else {
			$error++;
		}
	}
} else {
	// List of posts (blog index)
	if ($cur_lang_id > 1) {
		$sql_blog_home = "
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
				ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int) $cur_lang_id . "
			WHERE b.date<='" . mysql_res(date('Y-m-d H:i:s')) . "'
			  AND b.display=1
			  AND ci.status " . blog_i18n_ci_status_in_sql() . "
			  AND IFNULL(ci.url,'')!=''
			  AND TRIM(COALESCE(ci.content,''))!=''
			ORDER BY b.date DESC
		";
		$abc['blog'] = mysql_data($sql_blog_home, false, 20, @$_GET['n']);
	} else {
		$abc['blog'] = mysql_data(
			"SELECT * FROM blog WHERE date<='" . mysql_res(date('Y-m-d H:i:s')) . "' AND url$langid!='' AND display = 1 ORDER BY date DESC",
			false,
			20,
			@$_GET['n']
		);
	}
	if (!empty($abc['blog']['list']) && is_array($abc['blog']['list'])) {
		foreach ($abc['blog']['list'] as $i => $_row) {
			if (is_array($_row)) {
				content_year_macro_apply_row($abc['blog']['list'][$i], $langid);
			}
		}
	}
}
