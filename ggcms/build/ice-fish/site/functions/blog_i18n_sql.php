<?php

/**
 * Shared SQL fragments for blog + content_i18n slug resolution.
 * URLs in DB may be stored as "slug", "slug/", "blog/slug", or full site paths — the public router only has the final segment.
 */

if (!function_exists('blog_i18n_ci_status_in_sql')) {
	/**
	 * Allowed content_i18n.status values (align with pages router / translation pipeline).
	 *
	 * @return string SQL fragment without leading AND, e.g. IN ('published',...)
	 */
	function blog_i18n_ci_status_in_sql() {
		return "IN ('published','review','draft','missing')";
	}
}

if (!function_exists('blog_i18n_ci_url_matches_sql')) {
	/**
	 * SQL boolean expression: column equals slug or ends with that path segment.
	 * NULL-safe (NULL url would otherwise break SUBSTRING / equality chains in MySQL).
	 *
	 * @param string $col SQL column or expression, e.g. "ci.url" or "b.url"
	 * @param string $slug_mysql_res output of mysql_res() for use inside quotes
	 * @return string
	 */
	function blog_i18n_ci_url_matches_sql($col, $slug_mysql_res) {
		$s = (string) $slug_mysql_res;
		$c = 'IFNULL(' . $col . ",'')";
		return '('
			. $c . "='" . $s . "'"
			. " OR " . $c . "='" . $s . "/'"
			. " OR SUBSTRING_INDEX(TRIM(TRAILING '/' FROM " . $c . "), '/', -1)='" . $s . "'"
			. ')';
	}
}

if (!function_exists('blog_i18n_slug_match_where_sql')) {
	/**
	 * Match request slug against localized row (ci), canonical blog row (b.url),
	 * translation source language i18n (translation_settings.source_lang_id), or any other locale row.
	 *
	 * @param string $slug_mysql_res mysql_res() of path segment from URL
	 * @param string $ci_alias       main i18n row alias, usually "ci"
	 * @return string SQL fragment for AND ( ... )
	 */
	function blog_i18n_slug_match_where_sql($slug_mysql_res, $ci_alias = 'ci') {
		$st = blog_i18n_ci_status_in_sql();
		$src = 1;
		if (function_exists('page_i18n_source_lang_id')) {
			$src = (int) page_i18n_source_lang_id();
			if ($src < 1) {
				$src = 1;
			}
		}
		$match_ci = blog_i18n_ci_url_matches_sql($ci_alias . '.url', $slug_mysql_res);
		$match_b = blog_i18n_ci_url_matches_sql('b.url', $slug_mysql_res);
		$match_cis = blog_i18n_ci_url_matches_sql('cis.url', $slug_mysql_res);
		$match_cix = blog_i18n_ci_url_matches_sql('cix.url', $slug_mysql_res);
		return '('
			. $match_ci
			. ' OR ' . $match_b
			. ' OR EXISTS ('
			. 'SELECT 1 FROM content_i18n cis '
			. "WHERE cis.entity='blog' AND cis.entity_id=b.id AND cis.lang_id=" . $src . ' '
			. 'AND cis.status ' . $st . ' '
			. "AND TRIM(COALESCE(cis.content,''))!='' "
			. 'AND ' . $match_cis
			. ')'
			. ' OR EXISTS ('
			. 'SELECT 1 FROM content_i18n cix '
			. "WHERE cix.entity='blog' AND cix.entity_id=b.id "
			. 'AND cix.status ' . $st . ' '
			. "AND TRIM(COALESCE(cix.content,''))!='' "
			. 'AND ' . $match_cix
			. ')'
			. ')';
	}
}

if (!function_exists('blog_i18n_canonical_lang_id')) {
	/**
	 * Language id for canonical `blog.url` in language switcher (translation source, default 1).
	 *
	 * @return int
	 */
	function blog_i18n_canonical_lang_id() {
		$src = 1;
		if (function_exists('page_i18n_source_lang_id')) {
			$src = (int) page_i18n_source_lang_id();
		}
		return ($src < 1) ? 1 : $src;
	}
}

if (!function_exists('blog_i18n_slug')) {
	function blog_i18n_slug($u) {
		$u = trim((string) $u);
		if ($u === '') {
			return '';
		}
		$u = trim($u, '/');
		if ($u === '') {
			return '';
		}
		if (strpos($u, '/') !== false) {
			$parts = explode('/', $u);
			$u = (string) end($parts);
		}
		return trim($u);
	}
}

if (!function_exists('blog_i18n_slug_map')) {
	function blog_i18n_slug_map($entity_id) {
		$entity_id = (int) $entity_id;
		if ($entity_id <= 0) {
			return array();
		}
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
			return array();
		}
		$st = blog_i18n_ci_status_in_sql();
		$rows = mysql_select("
			SELECT lang_id, url
			FROM content_i18n
			WHERE entity='blog'
			  AND entity_id=" . $entity_id . "
			  AND status " . $st . "
			  AND IFNULL(url,'')!=''
			  AND TRIM(COALESCE(content,''))!=''
			ORDER BY lang_id ASC, FIELD(status,'published','review','draft','missing') ASC, id DESC
		", 'rows') ?: array();
		$map = array();
		foreach ($rows as $row) {
			$lid = (int) ($row['lang_id'] ?? 0);
			if ($lid <= 0 || isset($map[$lid])) {
				continue;
			}
			$slug = blog_i18n_slug($row['url'] ?? '');
			if ($slug !== '') {
				$map[$lid] = $slug;
			}
		}
		return $map;
	}
}
