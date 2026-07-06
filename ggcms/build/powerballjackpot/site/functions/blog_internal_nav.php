<?php
/**
 * Blog article: previous / next post + related list for internal linking.
 * URLs are plain site paths (not rewritten to offer /go/...).
 */

/**
 * @param array<string,mixed> $post Current article row (merged blog + i18n fields).
 * @param string $blog_base e.g. /en/blog/
 * @param int $cur_lang_id Numeric language id (1 = canonical columns on blog).
 * @param string $langid Suffix for blog.url{name}, blog.name{name} (empty for lang 1).
 * @return array{prev:?array{href:string,title:string},next:?array{href:string,title:string},related:list<array{href:string,title:string}>}
 */
function blog_internal_links_for_article(array $post, $blog_base, $cur_lang_id, $langid) {
	$out = array('prev' => null, 'next' => null, 'related' => array());
	$id = isset($post['id']) ? (int) $post['id'] : 0;
	if ($id <= 0) {
		return $out;
	}
	$cur_lang_id = (int) $cur_lang_id;
	$langid = (string) $langid;
	$cat = isset($post['category']) ? (int) $post['category'] : 0;
	$date_raw = isset($post['date']) ? (string) $post['date'] : '';
	$cur_ts = $date_raw !== '' ? strtotime($date_raw) : false;
	$cur_date = ($cur_ts !== false) ? date('Y-m-d H:i:s', $cur_ts) : date('Y-m-d H:i:s');
	$now = date('Y-m-d H:i:s');
	$blog_base = (string) $blog_base;
	if ($blog_base === '') {
		$blog_base = '/blog/';
	}
	if (substr($blog_base, -1) !== '/') {
		$blog_base .= '/';
	}

	$url_col = 'url' . $langid;
	$name_col = 'name' . $langid;

	$map_row = function ($row, $blog_base) use ($langid, $cur_lang_id) {
		if (!is_array($row)) {
			return null;
		}
		$suf = $cur_lang_id > 1 ? (string) $cur_lang_id : '';
		$slug = '';
		if ($suf !== '' && isset($row['url' . $suf])) {
			$slug = trim((string) $row['url' . $suf], '/');
		} elseif (isset($row['u_slug'])) {
			$slug = trim((string) $row['u_slug'], '/');
		} elseif (isset($row['url' . $langid])) {
			$slug = trim((string) $row['url' . $langid], '/');
		} elseif (isset($row['url'])) {
			$slug = trim((string) $row['url'], '/');
		}
		if ($slug === '') {
			return null;
		}
		$title = '';
		if ($suf !== '' && isset($row['name' . $suf]) && trim((string) $row['name' . $suf]) !== '') {
			$title = (string) $row['name' . $suf];
		} elseif (isset($row['u_name']) && trim((string) $row['u_name']) !== '') {
			$title = (string) $row['u_name'];
		} elseif (isset($row['name' . $langid])) {
			$title = (string) $row['name' . $langid];
		} elseif (isset($row['name'])) {
			$title = (string) $row['name'];
		}
		if ($title === '' && $suf !== '' && isset($row['title' . $suf])) {
			$title = (string) $row['title' . $suf];
		}
		if ($title === '') {
			$title = $slug;
		}
		if (function_exists('content_year_macro')) {
			$title = content_year_macro($title);
		}
		return array(
			'href' => $blog_base . $slug . '/',
			'title' => $title,
		);
	};

	if ($cur_lang_id > 1) {
		$suf = (string) $cur_lang_id;
		$base_join = "
			FROM blog b
			JOIN content_i18n ci
				ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int) $cur_lang_id . "
			WHERE b.display=1
			  AND b.date<='" . mysql_res($now) . "'
			  AND ci.status IN ('published','review','draft')
			  AND ci.url!=''
			  AND ci.content!=''
		";
		$sqlp = "
			SELECT b.id, ci.url AS u_slug,
				COALESCE(NULLIF(ci.name,''), NULLIF(ci.title,''), '') AS u_name
			" . $base_join . "
			  AND b.id!=" . $id . "
			  AND (
				(b.date < '" . mysql_res($cur_date) . "')
				OR (b.date = '" . mysql_res($cur_date) . "' AND b.id < " . $id . ")
			  )
			ORDER BY b.date DESC, b.id DESC
			LIMIT 1
		";
		$sqln = "
			SELECT b.id, ci.url AS u_slug,
				COALESCE(NULLIF(ci.name,''), NULLIF(ci.title,''), '') AS u_name
			" . $base_join . "
			  AND b.id!=" . $id . "
			  AND (
				(b.date > '" . mysql_res($cur_date) . "')
				OR (b.date = '" . mysql_res($cur_date) . "' AND b.id > " . $id . ")
			  )
			ORDER BY b.date ASC, b.id ASC
			LIMIT 1
		";
		$sqlr = "
			SELECT b.id, ci.url AS u_slug,
				COALESCE(NULLIF(ci.name,''), NULLIF(ci.title,''), '') AS u_name
			" . $base_join . "
			  AND b.id!=" . $id . "
			ORDER BY (b.category = " . $cat . ") DESC, RAND()
			LIMIT 10
		";
		if ($rp = mysql_select($sqlp, 'row')) {
			$out['prev'] = $map_row($rp, $blog_base);
		}
		if ($rn = mysql_select($sqln, 'row')) {
			$out['next'] = $map_row($rn, $blog_base);
		}
		$rel = mysql_select($sqlr, 'rows');
		if (is_array($rel)) {
			foreach ($rel as $r) {
				$m = $map_row($r, $blog_base);
				if ($m !== null) {
					$out['related'][] = $m;
				}
			}
		}
	} else {
		$sqlp = "
			SELECT id, " . $url_col . " AS u_slug, " . $name_col . " AS u_name
			FROM blog
			WHERE display=1
			  AND date<='" . mysql_res($now) . "'
			  AND " . $url_col . "!=''
			  AND id!=" . $id . "
			  AND (
				(date < '" . mysql_res($cur_date) . "')
				OR (date = '" . mysql_res($cur_date) . "' AND id < " . $id . ")
			  )
			ORDER BY date DESC, id DESC
			LIMIT 1
		";
		$sqln = "
			SELECT id, " . $url_col . " AS u_slug, " . $name_col . " AS u_name
			FROM blog
			WHERE display=1
			  AND date<='" . mysql_res($now) . "'
			  AND " . $url_col . "!=''
			  AND id!=" . $id . "
			  AND (
				(date > '" . mysql_res($cur_date) . "')
				OR (date = '" . mysql_res($cur_date) . "' AND id > " . $id . ")
			  )
			ORDER BY date ASC, id ASC
			LIMIT 1
		";
		$sqlr = "
			SELECT id, " . $url_col . " AS u_slug, " . $name_col . " AS u_name
			FROM blog
			WHERE display=1
			  AND date<='" . mysql_res($now) . "'
			  AND " . $url_col . "!=''
			  AND id!=" . $id . "
			ORDER BY (category = " . $cat . ") DESC, RAND()
			LIMIT 10
		";
		if ($rp = mysql_select($sqlp, 'row')) {
			$out['prev'] = $map_row($rp, $blog_base);
		}
		if ($rn = mysql_select($sqln, 'row')) {
			$out['next'] = $map_row($rn, $blog_base);
		}
		$rel = mysql_select($sqlr, 'rows');
		if (is_array($rel)) {
			foreach ($rel as $r) {
				$m = $map_row($r, $blog_base);
				if ($m !== null) {
					$out['related'][] = $m;
				}
			}
		}
	}

	return $out;
}

/**
 * @param array<string,mixed> $page
 */
function blog_internal_links_apply(array &$page, $blog_base, $cur_lang_id, $langid) {
	$page['blog_internal'] = blog_internal_links_for_article($page, $blog_base, $cur_lang_id, $langid);
}
