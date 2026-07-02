<?php
/**
 * On-demand SEO report for one URL or (entity, entity_id): DB/SEO Monitor vs live HTML.
 * Used by GET /api/telemetry_page_seo (same auth token as telemetry_snapshot).
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * @return string Absolute origin without trailing slash
 */
function site_telemetry_page_seo_public_origin() {
	global $config;
	$base = '';
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row_seo = mysql_select("SELECT value FROM `variables` WHERE `key`='seo_structured' LIMIT 1", 'row');
		if ($row_seo && isset($row_seo['value']) && (string)$row_seo['value'] !== '') {
			$dec = @json_decode((string)$row_seo['value'], true);
			if (is_array($dec) && !empty($dec['canonical_base'])) {
				$base = rtrim(trim((string)$dec['canonical_base']), '/');
			}
		}
	}
	if ($base === '') {
		$h = isset($config['http_domain']) ? rtrim((string)$config['http_domain'], '/') : '';
		if ($h !== '' && !preg_match('#^https?:$#i', $h) && !preg_match('#^https?://$#i', $h)) {
			$base = $h;
		}
	}
	if ($base === '') {
		$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
		if ($host !== '') {
			$base = 'https://' . $host;
		} else {
			$base = 'https://localhost';
		}
	}
	return $base;
}

/**
 * @param string $path Path starting with /, no query
 * @return string Full URL with trailing slash (site convention)
 */
function site_telemetry_page_seo_origin_url_for_path($path) {
	$origin = site_telemetry_page_seo_public_origin();
	$path = (string)$path;
	if ($path === '') {
		$path = '/';
	}
	if ($path[0] !== '/') {
		$path = '/' . $path;
	}
	$path = preg_replace('#/+#', '/', $path);
	if (substr($path, -1) !== '/') {
		$path .= '/';
	}
	return $origin . $path;
}

/**
 * HTTP GET for HTML (telemetry / diagnostics).
 *
 * @return array{ok:bool,http_code?:int,final_url?:string,body?:string,error?:string}
 */
function site_telemetry_page_seo_http_get_html($url, $timeout_sec = 18) {
	$url = trim((string)$url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return array('ok' => false, 'error' => 'bad_url');
	}
	$ua = 'Mozilla/5.0 (compatible; SiteTelemetryPageSeo/1.0; +' . site_telemetry_page_seo_public_origin() . ')';
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 6,
			CURLOPT_CONNECTTIMEOUT => min(10, (int)$timeout_sec),
			CURLOPT_TIMEOUT => (int)$timeout_sec,
			CURLOPT_USERAGENT => $ua,
			CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8'),
		));
		$body = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$err = curl_error($ch);
		curl_close($ch);
		if ($body === false) {
			return array('ok' => false, 'http_code' => $code, 'error' => $err !== '' ? $err : 'curl_exec_failed');
		}
		return array('ok' => true, 'http_code' => $code, 'final_url' => (string)$final, 'body' => (string)$body);
	}
	$ctx = stream_context_create(array(
		'http' => array(
			'method' => 'GET',
			'header' => "User-Agent: {$ua}\r\nAccept: text/html\r\n",
			'timeout' => (float)$timeout_sec,
			'follow_location' => 1,
			'max_redirects' => 6,
		),
		'ssl' => array(
			'verify_peer' => true,
			'verify_peer_name' => true,
		),
	));
	$body = @file_get_contents($url, false, $ctx);
	if ($body === false) {
		return array('ok' => false, 'http_code' => 0, 'error' => 'file_get_contents_failed');
	}
	return array('ok' => true, 'http_code' => 0, 'final_url' => $url, 'body' => (string)$body);
}

/**
 * @return array<string,mixed>
 */
function site_telemetry_page_seo_parse_head($html) {
	$html = (string)$html;
	$out = array(
		'title' => '',
		'meta_description' => '',
		'canonical_href' => '',
		'hreflang' => array(),
		'h1_texts' => array(),
	);
	if ($html === '') {
		return $out;
	}
	if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
		$out['title'] = html_entity_decode(trim(preg_replace('/\s+/u', ' ', strip_tags($m[1]))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
	if (preg_match_all('#<meta\s[^>]*name\s*=\s*["\']description["\'][^>]*>#i', $html, $tags)) {
		foreach ($tags[0] as $tag) {
			if (preg_match('#\bcontent\s*=\s*["\']([^"\']*)["\']#i', $tag, $cm)) {
				$out['meta_description'] = html_entity_decode((string)$cm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
				break;
			}
		}
	}
	if (preg_match_all('#<link\s[^>]*rel\s*=\s*["\']canonical["\'][^>]*>#i', $html, $lt)) {
		foreach ($lt[0] as $tag) {
			if (preg_match('#\bhref\s*=\s*["\']([^"\']+)["\']#i', $tag, $hm)) {
				$out['canonical_href'] = html_entity_decode(trim($hm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
				break;
			}
		}
	}
	if (preg_match_all('#<link\s[^>]*rel\s*=\s*["\']alternate["\'][^>]*>#i', $html, $lt2)) {
		foreach ($lt2[0] as $tag) {
			$h = '';
			$hl = '';
			if (preg_match('#\bhref\s*=\s*["\']([^"\']+)["\']#i', $tag, $hm)) {
				$h = trim($hm[1]);
			}
			if (preg_match('#\bhreflang\s*=\s*["\']([^"\']+)["\']#i', $tag, $hm2)) {
				$hl = strtolower(trim($hm2[1]));
			}
			if ($h !== '') {
				$out['hreflang'][] = array('hreflang' => $hl, 'href' => html_entity_decode($h, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
			}
		}
	}
	if (preg_match_all('#<h1[^>]*>(.*?)</h1>#is', $html, $hm)) {
		foreach ($hm[1] as $inner) {
			$t = html_entity_decode(trim(preg_replace('/\s+/u', ' ', strip_tags($inner))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($t !== '') {
				$out['h1_texts'][] = $t;
			}
			if (count($out['h1_texts']) >= 8) {
				break;
			}
		}
	}
	$out['html_bytes'] = strlen($html);
	return $out;
}

/**
 * Strip template suffix from rendered document title for comparison.
 *
 * @param string $rendered_title
 * @return string
 */
function site_telemetry_page_seo_title_inner_for_compare($rendered_title) {
	$s = trim((string)$rendered_title);
	$suf = ' | Aviator Log In';
	$l = strlen($suf);
	if ($l > 0 && strlen($s) >= $l && substr($s, -$l) === $suf) {
		return trim(substr($s, 0, -$l));
	}
	return $s;
}

/**
 * @return array{ok:bool,message?:string,entity?:string,entity_id?:int,lang_id?:int,fetch_url?:string}
 */
function site_telemetry_page_seo_resolve_public_url($raw_url) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	$raw_url = trim((string)$raw_url);
	if ($raw_url === '') {
		return array('ok' => false, 'message' => 'Empty url');
	}
	$parts = @parse_url($raw_url);
	if (!is_array($parts)) {
		return array('ok' => false, 'message' => 'Invalid URL');
	}
	$path = isset($parts['path']) ? (string)$parts['path'] : '/';
	$path = '/' . trim(preg_replace('#/+#', '/', $path), '/');
	if ($path === '/') {
		$path = '';
	}
	$segments = $path === '' ? array() : array_values(array_filter(explode('/', trim($path, '/'))));

	$langs = mysql_select("SELECT id, url FROM languages WHERE display=1 ORDER BY rank DESC", 'rows');
	if (!$langs) {
		$langs = array();
	}
	$lang_id = 0;
	$lang_url_seg = '';
	if ($segments !== array()) {
		$first = strtolower($segments[0]);
		foreach ($langs as $L) {
			$u = strtolower(trim((string)($L['url'] ?? ''), '/'));
			if ($u !== '' && $u === $first) {
				$lang_id = (int)$L['id'];
				$lang_url_seg = trim((string)$L['url'], '/');
				$segments = array_slice($segments, 1);
				break;
			}
		}
	}
	if ($lang_id <= 0) {
		$def = mysql_select("SELECT id, url FROM languages WHERE display=1 ORDER BY rank DESC LIMIT 1", 'row');
		$lang_id = $def ? (int)$def['id'] : 1;
	}

	$origin = site_telemetry_page_seo_public_origin();
	$path_for_fetch = '/' . ($lang_url_seg !== '' ? $lang_url_seg . '/' : '');
	if ($segments !== array()) {
		$path_for_fetch .= implode('/', $segments) . '/';
	} else {
		if ($lang_url_seg !== '') {
			$path_for_fetch = '/' . $lang_url_seg . '/';
		} else {
			$path_for_fetch = '/';
		}
	}
	$path_for_fetch = preg_replace('#/+#', '/', $path_for_fetch);
	if (substr($path_for_fetch, -1) !== '/') {
		$path_for_fetch .= '/';
	}
	$fetch_url = $origin . $path_for_fetch;

	// --- Home
	if ($segments === array()) {
		$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='index' LIMIT 1", 'row');
		if (!$row || (int)$row['id'] <= 0) {
			return array('ok' => false, 'message' => 'Could not resolve home page id (pages.module=index)', 'fetch_url' => $fetch_url);
		}
		return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	$seg0 = strtolower((string)$segments[0]);

	// --- Blog listing or article
	if ($seg0 === 'blog') {
		if (!isset($segments[1]) || $segments[1] === '') {
			$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='blog' LIMIT 1", 'row');
			if (!$row) {
				return array('ok' => false, 'message' => 'Blog listing page not found', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		$slug = mysql_res((string)$segments[1]);
		if ($lang_id > 1) {
			$ci = mysql_select("
				SELECT entity_id FROM content_i18n
				WHERE entity='blog' AND lang_id=" . (int)$lang_id . "
				  AND url='" . $slug . "'
				  AND status IN ('published','review','draft')
				LIMIT 1
			", 'row');
			if (!$ci) {
				return array('ok' => false, 'message' => 'Blog article not found for slug/lang', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'blog', 'entity_id' => (int)$ci['entity_id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		$suf = '';
		$col = 'url';
		if (mysql_select("SHOW COLUMNS FROM blog LIKE 'url'", 'num_rows') > 0) {
			$col = 'url';
		}
		$b = mysql_select("SELECT id FROM blog WHERE display=1 AND `" . $col . "`='" . $slug . "' LIMIT 1", 'row');
		if (!$b) {
			return array('ok' => false, 'message' => 'Blog article not found (canonical lang)', 'fetch_url' => $fetch_url);
		}
		return array('ok' => true, 'entity' => 'blog', 'entity_id' => (int)$b['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	// --- Casinos hub or single
	if ($seg0 === 'casinos') {
		if (!isset($segments[1]) || $segments[1] === '') {
			$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='pages' AND (url='casinos' OR url1='casinos' OR url2='casinos' OR url3='casinos') LIMIT 1", 'row');
			if (!$row) {
				$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='casinos' LIMIT 1", 'row');
			}
			if (!$row) {
				return array('ok' => false, 'message' => 'Casinos hub page not found', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		$slug = mysql_res(trim((string)$segments[1], '/'));
		$a = mysql_select("SELECT id FROM casino_articles WHERE display=1 AND url='" . $slug . "' LIMIT 1", 'row');
		if (!$a && $lang_id > 1) {
			$ci = mysql_select("
				SELECT entity_id FROM content_i18n
				WHERE entity='casino_articles' AND lang_id=" . (int)$lang_id . " AND url='" . $slug . "'
				ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
				LIMIT 1
			", 'row');
			if ($ci) {
				$a = mysql_select("SELECT id FROM casino_articles WHERE id=" . (int)$ci['entity_id'] . " AND display=1 LIMIT 1", 'row');
			}
		}
		if (!$a) {
			return array('ok' => false, 'message' => 'Casino article not found', 'fetch_url' => $fetch_url);
		}
		return array('ok' => true, 'entity' => 'casino_articles', 'entity_id' => (int)$a['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	// --- Games hub or single
	if ($seg0 === 'games') {
		if (!isset($segments[1]) || $segments[1] === '') {
			$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='pages' AND (url='games' OR url1='games' OR url2='games' OR url3='games') LIMIT 1", 'row');
			if (!$row) {
				return array('ok' => false, 'message' => 'Games hub page not found', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		$slug = mysql_res(trim((string)$segments[1], '/'));
		$g = mysql_select("SELECT id FROM games WHERE display=1 AND url='" . $slug . "' LIMIT 1", 'row');
		if (!$g && $lang_id > 1) {
			$ci = mysql_select("
				SELECT entity_id FROM content_i18n
				WHERE entity='games' AND lang_id=" . (int)$lang_id . " AND url='" . $slug . "'
				ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
				LIMIT 1
			", 'row');
			if ($ci) {
				$g = mysql_select("SELECT id FROM games WHERE id=" . (int)$ci['entity_id'] . " AND display=1 LIMIT 1", 'row');
			}
		}
		if (!$g) {
			return array('ok' => false, 'message' => 'Game not found', 'fetch_url' => $fetch_url);
		}
		return array('ok' => true, 'entity' => 'games', 'entity_id' => (int)$g['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	// --- Guides hub, category, or single
	if ($seg0 === 'guides') {
		if (!isset($segments[1])) {
			$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='pages' AND (url='guides' OR url1='guides' OR url2='guides' OR url3='guides') LIMIT 1", 'row');
			if (!$row) {
				return array('ok' => false, 'message' => 'Guides hub page not found', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		if (!isset($segments[2]) || $segments[2] === '') {
			$row = mysql_select("SELECT id FROM pages WHERE display=1 AND module='pages' AND (url='guides' OR url1='guides' OR url2='guides' OR url3='guides') LIMIT 1", 'row');
			if (!$row) {
				return array('ok' => false, 'message' => 'Guides hub page not found', 'fetch_url' => $fetch_url);
			}
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$row['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
		$cat = mysql_res((string)$segments[1]);
		$slug = mysql_res((string)$segments[2]);
		$guide = mysql_select("
			SELECT id FROM guides WHERE display=1 AND category='" . $cat . "' AND url='" . $slug . "' LIMIT 1
		", 'row');
		if (!$guide) {
			return array('ok' => false, 'message' => 'Guide not found', 'fetch_url' => $fetch_url);
		}
		return array('ok' => true, 'entity' => 'guides', 'entity_id' => (int)$guide['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	// --- Generic CMS page by slug (single segment after lang)
	if (count($segments) === 1) {
		$slug = mysql_res(trim((string)$segments[0], '/'));
		$lid = (int)$lang_id;
		$url_col = 'url';
		if ($lid > 1) {
			$cname = 'url' . $lid;
			if (preg_match('/^url\d*$/', $cname) && mysql_select("SHOW COLUMNS FROM pages LIKE '" . mysql_res($cname) . "'", 'num_rows') > 0) {
				$url_col = $cname;
			}
		}
		$col_sql = preg_match('/^url\d*$/', $url_col) ? '`' . $url_col . '`' : '`url`';
		$page = mysql_select("
			SELECT id FROM pages
			WHERE display=1 AND (`url`='" . $slug . "'" . ($url_col !== 'url' ? " OR " . $col_sql . "='" . $slug . "'" : '') . ")
			LIMIT 1
		", 'row');
		if (!$page && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$ci = mysql_select("
				SELECT entity_id FROM content_i18n
				WHERE entity='pages' AND lang_id=" . (int)$lid . " AND url='" . $slug . "' AND status='published'
				LIMIT 1
			", 'row');
			if ($ci) {
				$page = mysql_select("SELECT id FROM pages WHERE id=" . (int)$ci['entity_id'] . " AND display=1 LIMIT 1", 'row');
			}
		}
		if ($page) {
			return array('ok' => true, 'entity' => 'pages', 'entity_id' => (int)$page['id'], 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
		}
	}

	return array(
		'ok' => false,
		'message' => 'URL pattern not supported by resolver; pass entity, entity_id, and optional lang_id',
		'fetch_url' => $fetch_url,
		'hint_segments' => $segments,
	);
}

/**
 * Path after language segment for HTTP fetch when caller passes entity+id (not full public_url).
 *
 * @param array<string,mixed>|null $rep_pre Result of seo_monitor_export_report_array (already loaded) or null
 * @return string Relative path with trailing slash, no leading slash
 */
function site_telemetry_page_seo_path_suffix_for_entity($entity, $entity_id, $lang_id, $rep_pre) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$loc_slug = '';
	if (is_array($rep_pre) && !empty($rep_pre['ok']) && !empty($rep_pre['data']['locales'])) {
		foreach ($rep_pre['data']['locales'] as $loc) {
			if ((int)($loc['lang_id'] ?? 0) === $lang_id) {
				$loc_slug = trim((string)($loc['url'] ?? ''), '/');
				break;
			}
		}
	}
	if ($entity === 'casino_articles') {
		$row = mysql_select('SELECT url FROM casino_articles WHERE id=' . $entity_id . ' LIMIT 1', 'row');
		$su = $loc_slug !== '' ? $loc_slug : (isset($row['url']) ? trim((string)$row['url'], '/') : '');
		if ($su === '') {
			return '';
		}
		return 'casinos/' . $su . '/';
	}
	if ($entity === 'games') {
		$row = mysql_select('SELECT url FROM games WHERE id=' . $entity_id . ' LIMIT 1', 'row');
		$su = $loc_slug !== '' ? $loc_slug : (isset($row['url']) ? trim((string)$row['url'], '/') : '');
		if ($su === '') {
			return '';
		}
		return 'games/' . $su . '/';
	}
	if ($entity === 'guides') {
		$row = mysql_select('SELECT category, url FROM guides WHERE id=' . $entity_id . ' LIMIT 1', 'row');
		$cat = isset($row['category']) ? trim((string)$row['category'], '/') : '';
		$su = $loc_slug !== '' ? $loc_slug : (isset($row['url']) ? trim((string)$row['url'], '/') : '');
		if ($cat === '' || $su === '') {
			return '';
		}
		return 'guides/' . $cat . '/' . $su . '/';
	}
	if ($entity === 'blog') {
		$su = $loc_slug;
		if ($su === '') {
			$row = mysql_select('SELECT url FROM blog WHERE id=' . $entity_id . ' LIMIT 1', 'row');
			$su = isset($row['url']) ? trim((string)$row['url'], '/') : '';
		}
		if ($su === '') {
			return '';
		}
		return 'blog/' . $su . '/';
	}
	if ($loc_slug !== '') {
		return $loc_slug . '/';
	}
	$row = mysql_select('SELECT url, module FROM pages WHERE id=' . $entity_id . ' LIMIT 1', 'row');
	if (!$row) {
		return '';
	}
	if (isset($row['module']) && (string)$row['module'] === 'index') {
		return '';
	}
	$u = isset($row['url']) ? trim((string)$row['url'], '/') : '';
	return $u !== '' ? $u . '/' : '';
}

/**
 * Build diff between SEO Monitor locale row and parsed HTML head.
 *
 * @param array<string,mixed> $locale_row One element from seo_monitor_export_report_array locales
 * @param array<string,mixed> $parsed site_telemetry_page_seo_parse_head
 * @return array<int, array{code:string,severity:string,detail:mixed}>
 */
function site_telemetry_page_seo_build_diff($locale_row, $parsed) {
	$diff = array();
	if (!is_array($locale_row) || !is_array($parsed)) {
		return $diff;
	}
	$db_title = '';
	if (function_exists('seo_monitor_display_title')) {
		$db_title = trim((string)seo_monitor_display_title($locale_row['title'] ?? '', $locale_row['name'] ?? ''));
	}
	$db_desc = trim(strip_tags((string)($locale_row['description'] ?? '')));
	$rend_title_inner = site_telemetry_page_seo_title_inner_for_compare($parsed['title'] ?? '');
	$rend_desc = trim((string)($parsed['meta_description'] ?? ''));

	if ($db_title !== '' && $rend_title_inner !== '' && $db_title !== $rend_title_inner) {
		$diff[] = array(
			'code' => 'title_db_vs_rendered',
			'severity' => 'info',
			'detail' => array('db_display_title' => $db_title, 'rendered_title_inner' => $rend_title_inner, 'rendered_title_raw' => (string)($parsed['title'] ?? '')),
		);
	}
	if ($db_desc !== $rend_desc && ($db_desc !== '' || $rend_desc !== '')) {
		$diff[] = array(
			'code' => 'meta_description_db_vs_rendered',
			'severity' => 'warning',
			'detail' => array(
				'db_len' => function_exists('seo_monitor_strlen_utf8') ? seo_monitor_strlen_utf8($db_desc) : strlen($db_desc),
				'rendered_len' => function_exists('seo_monitor_strlen_utf8') ? seo_monitor_strlen_utf8($rend_desc) : strlen($rend_desc),
				'db_preview' => strlen($db_desc) > 220 ? (substr($db_desc, 0, 220) . '…') : $db_desc,
				'rendered_preview' => strlen($rend_desc) > 220 ? (substr($rend_desc, 0, 220) . '…') : $rend_desc,
			),
		);
	}
	$m_h1 = isset($locale_row['metrics']['h1_count']) ? $locale_row['metrics']['h1_count'] : null;
	$rh = isset($parsed['h1_texts']) && is_array($parsed['h1_texts']) ? count($parsed['h1_texts']) : 0;
	if ($m_h1 !== null && (int)$m_h1 !== (int)$rh) {
		$diff[] = array(
			'code' => 'h1_count_db_html_vs_rendered',
			'severity' => 'info',
			'detail' => array('seo_monitor_body_h1_count' => (int)$m_h1, 'rendered_h1_count' => (int)$rh),
		);
	}
	return $diff;
}

/**
 * Title/name/description + body metrics for one locale (for diff vs rendered head).
 *
 * @return array<string,mixed>
 */
function site_telemetry_page_seo_locale_head_fields($entity, $entity_id, $lang_id) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$map = seo_monitor_entity_map();
	if (!isset($map[$entity]) || $entity_id <= 0 || $lang_id <= 0) {
		return array();
	}
	$info = $map[$entity];
	$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
	if (!$main) {
		return array();
	}
	$cfg = seo_monitor_translation_settings();
	$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
	$lang_meta = null;
	foreach (seo_monitor_cluster_languages($source_lang_id) as $L) {
		if ((int)($L['id'] ?? 0) === $lang_id) {
			$lang_meta = $L;
			break;
		}
	}
	if (!$lang_meta) {
		return array();
	}
	$batch = seo_monitor_batch_i18n_rows($entity, array($entity_id), array($lang_id));
	$i18n = isset($batch[$entity_id][$lang_id]) ? $batch[$entity_id][$lang_id] : null;
	$loc = seo_monitor_locale_payload($entity, $entity_id, $lang_meta, $main, $i18n, $source_lang_id, false);
	return array(
		'title' => (string)($loc['title'] ?? ''),
		'name' => (string)($loc['name'] ?? ''),
		'description' => (string)($loc['description'] ?? ''),
		'url' => (string)($loc['url'] ?? ''),
		'source' => (string)($loc['source'] ?? ''),
		'metrics' => seo_monitor_locale_html_metrics($loc),
	);
}

/**
 * @param array $params url?, entity?, entity_id?, lang_id?, fetch (0|1), normalize (0|1) — if 1, runs seo_monitor_list_row_issue_scan (may trim DB like admin)
 * @return array<string,mixed>
 */
function site_telemetry_page_seo_collect(array $params) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';

	$fetch = !isset($params['fetch']) || (string)$params['fetch'] !== '0';
	$normalize = !empty($params['normalize']) && (string)$params['normalize'] === '1';
	$lang_override = isset($params['lang_id']) ? (int)$params['lang_id'] : 0;

	$resolve = array();
	$entity = isset($params['entity']) ? trim((string)$params['entity']) : '';
	$entity_id = isset($params['entity_id']) ? (int)$params['entity_id'] : 0;
	$url_in = isset($params['url']) ? trim((string)$params['url']) : '';

	if ($url_in !== '') {
		$r = site_telemetry_page_seo_resolve_public_url($url_in);
		$resolve['from_url'] = $r;
		if (empty($r['ok'])) {
			return array(
				'ok' => false,
				'error' => 'resolve_failed',
				'message' => isset($r['message']) ? (string)$r['message'] : 'resolve_failed',
				'resolve' => $resolve,
			);
		}
		$entity = (string)$r['entity'];
		$entity_id = (int)$r['entity_id'];
		if ($lang_override > 0) {
			$lang_id = $lang_override;
		} else {
			$lang_id = (int)$r['lang_id'];
		}
		$fetch_url = (string)$r['fetch_url'];
	} else {
		if ($entity === '' || $entity_id <= 0) {
			return array('ok' => false, 'error' => 'bad_params', 'message' => 'Provide url= or entity= + entity_id=');
		}
		if (!isset(seo_monitor_entity_map()[$entity])) {
			return array('ok' => false, 'error' => 'bad_entity', 'message' => 'Unknown entity');
		}
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		if ($lang_override > 0) {
			$lang_id = $lang_override;
		} else {
			$lang_id = $source_lang_id;
		}
		$lang_row = mysql_select("SELECT url FROM languages WHERE id=" . (int)$lang_id . " LIMIT 1", 'row');
		$seg = $lang_row && trim((string)$lang_row['url'], '/') !== '' ? trim((string)$lang_row['url'], '/') . '/' : '';
		$rep_pre = seo_monitor_export_report_array($entity, $entity_id);
		$slug_path = site_telemetry_page_seo_path_suffix_for_entity($entity, $entity_id, $lang_id, $rep_pre);
		$path = '/' . ($seg !== '' ? $seg : '') . $slug_path;
		$path = preg_replace('#/+#', '/', $path);
		if (substr($path, -1) !== '/') {
			$path .= '/';
		}
		$fetch_url = site_telemetry_page_seo_origin_url_for_path($path);
		$resolve['from_entity'] = array('entity' => $entity, 'entity_id' => $entity_id, 'lang_id' => $lang_id, 'fetch_url' => $fetch_url);
	}

	if ($entity_id <= 0 || !isset(seo_monitor_entity_map()[$entity])) {
		return array('ok' => false, 'error' => 'bad_entity', 'message' => 'Invalid entity after resolve');
	}

	if ($url_in === '' && isset($rep_pre) && is_array($rep_pre)) {
		$export = $rep_pre;
	} else {
		$export = seo_monitor_export_report_array($entity, $entity_id);
	}

	$issue_summary = array(
		'ok' => true,
		'read_only' => true,
		'note' => 'Aggregated from export report locales (no DB writes). Pass normalize=1 for seo_monitor_list_row_issue_scan (may persist title/description trims like admin row check).',
		'issue_codes' => array(),
		'issue_count' => 0,
		'all_ok' => true,
	);
	if (!empty($export['ok']) && !empty($export['data']['locales'])) {
		$codes = array();
		$cnt = 0;
		foreach ($export['data']['locales'] as $loc) {
			foreach ($loc['issues'] ?? array() as $iss) {
				$cnt++;
				$c = isset($iss['code']) ? (string)$iss['code'] : '';
				if ($c !== '') {
					$codes[$c] = true;
				}
			}
		}
		$issue_summary['issue_codes'] = array_keys($codes);
		$issue_summary['issue_count'] = $cnt;
		$issue_summary['all_ok'] = ($cnt === 0);
	}
	$admin_list_scan = null;
	if ($normalize) {
		$admin_list_scan = seo_monitor_list_row_issue_scan($entity, $entity_id);
	}

	$locale_match = null;
	if (!empty($export['ok']) && !empty($export['data']['locales'])) {
		foreach ($export['data']['locales'] as $loc) {
			if ((int)($loc['lang_id'] ?? 0) === (int)$lang_id) {
				$locale_match = $loc;
				break;
			}
		}
	}
	if ($locale_match) {
		$head = site_telemetry_page_seo_locale_head_fields($entity, $entity_id, $lang_id);
		if ($head !== array()) {
			$locale_match = array_merge($locale_match, $head);
		}
	}

	$rendered = array('skipped' => true);
	if ($fetch) {
		$http = site_telemetry_page_seo_http_get_html($fetch_url);
		$rendered = array(
			'fetch_url' => $fetch_url,
			'ok' => !empty($http['ok']),
			'http_code' => isset($http['http_code']) ? (int)$http['http_code'] : 0,
			'final_url' => isset($http['final_url']) ? (string)$http['final_url'] : '',
			'error' => isset($http['error']) ? (string)$http['error'] : '',
		);
		if (!empty($http['ok']) && isset($http['body'])) {
			$parsed = site_telemetry_page_seo_parse_head($http['body']);
			$rendered['parsed'] = $parsed;
			if ($locale_match) {
				$rendered['diff'] = site_telemetry_page_seo_build_diff($locale_match, $parsed);
			} else {
				$rendered['diff'] = array();
				$rendered['diff_note'] = 'No export locale row for lang_id; diff skipped';
			}
		}
	}

	$hub_page = ($entity === 'pages' && function_exists('seo_monitor_is_hub_page_entity') && seo_monitor_is_hub_page_entity('pages', (int)$entity_id));
	$hub_settings = function_exists('seo_monitor_hub_settings_load') ? seo_monitor_hub_settings_load() : array();

	return array(
		'ok' => true,
		'generated_at' => gmdate('c'),
		'entity' => $entity,
		'entity_id' => $entity_id,
		'lang_id' => $lang_id,
		'fetch_url' => $fetch_url,
		'hub_page' => $hub_page,
		'hub_settings' => $hub_settings,
		'resolve' => $resolve,
		'seo_monitor_issue_summary' => $issue_summary,
		'seo_monitor_admin_list_scan' => $admin_list_scan,
		'seo_monitor_export_report' => $export,
		'seo_monitor_locale' => $locale_match,
		'rendered' => $rendered,
	);
}
