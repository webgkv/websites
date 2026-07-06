<?php
/**
 * Public author profiles: URLs, locale overlay, cards, profile page markup.
 */

function author_public_base($abc = null) {
	if ($abc === null) {
		global $abc;
	}
	$lang_prefix = trim((string)($abc['lang']['url'] ?? ''), '/');
	$base = ($lang_prefix !== '' ? '/' . $lang_prefix . '/authors/' : '/authors/');
	return preg_replace('#/+#', '/', $base);
}

function author_normalize_slug($slug) {
	$slug = trim((string)$slug, '/');
	if ($slug === '') {
		return '';
	}
	$slug = str_replace('_', '-', $slug);
	if (function_exists('trunslit')) {
		$slug = trunslit($slug);
	}
	$slug = mb_strtolower($slug, 'UTF-8');
	$slug = preg_replace('~[^a-z0-9-]+~u', '-', $slug);
	$slug = preg_replace('~-+~u', '-', $slug);
	return trim($slug, '-');
}

function author_slug_from_name($name) {
	return author_normalize_slug((string)$name);
}

function author_current_lang_id() {
	global $lang;
	return (is_array($lang) && isset($lang['id'])) ? (int)$lang['id'] : 1;
}

function author_canonical_lang_id() {
	$canonical = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($vr && $vr['value'] !== '') {
			$dec = json_decode($vr['value'], true);
			if (is_array($dec) && isset($dec['source_lang_id'])) {
				$canonical = max(1, (int)$dec['source_lang_id']);
			}
		}
	}
	return $canonical;
}

/**
 * @return array<string,mixed>|null
 */
function author_row_by_id($author_id) {
	$author_id = (int)$author_id;
	if ($author_id <= 0) {
		return null;
	}
	$row = mysql_select("SELECT * FROM site_authors WHERE id=" . $author_id . " AND display=1 LIMIT 1", 'row');
	return $row ?: null;
}

/**
 * @return array<string,mixed>
 */
function author_apply_locale(array $author, $lang_id = 0) {
	if ($lang_id <= 0) {
		$lang_id = author_current_lang_id();
	}
	$author_id = isset($author['id']) ? (int)$author['id'] : 0;
	if ($author_id <= 0 || $lang_id <= 0 || @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) {
		return $author;
	}
	if ($lang_id === author_canonical_lang_id()) {
		return $author;
	}
	$ti = mysql_select("
		SELECT name, title, description, content, url, status
		FROM content_i18n
		WHERE entity='authors'
		  AND entity_id=" . $author_id . "
		  AND lang_id=" . (int)$lang_id . "
		ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
		LIMIT 1
	", 'row');
	if (!$ti) {
		return $author;
	}
	$st = isset($ti['status']) ? (string)$ti['status'] : '';
	if (!in_array($st, array('draft', 'review', 'published'), true)) {
		return $author;
	}
	if (!empty($ti['name'])) {
		$author['name'] = (string)$ti['name'];
	}
	if (!empty($ti['title'])) {
		$author['job_title'] = (string)$ti['title'];
	}
	if (trim((string)($ti['description'] ?? '')) !== '') {
		$author['bio_short'] = (string)$ti['description'];
	}
	if (trim((string)($ti['content'] ?? '')) !== '') {
		$author['bio'] = (string)$ti['content'];
	}
	$slug = trim((string)($ti['url'] ?? ''), '/');
	if ($slug !== '' && strpos($slug, 'author-') !== 0) {
		$author['url'] = $slug;
	}
	return $author;
}

function author_public_slug(array $author, $lang_id = 0) {
	if ($lang_id <= 0) {
		$lang_id = author_current_lang_id();
	}
	$author = author_apply_locale($author, $lang_id);
	$slug = author_normalize_slug($author['url'] ?? '');
	if ($slug === '' && !empty($author['name'])) {
		$slug = author_slug_from_name($author['name']);
	}
	if ($slug === '' && !empty($author['id'])) {
		$slug = 'author-' . (int)$author['id'];
	}
	return $slug;
}

function author_profile_url(array $author, $abc = null) {
	if ($abc === null) {
		global $abc;
	}
	$slug = author_public_slug($author);
	if ($slug === '') {
		return author_public_base($abc);
	}
	return author_public_base($abc) . $slug . '/';
}

function author_photo_url(array $author) {
	if (empty($author['id']) || empty($author['photo'])) {
		return '';
	}
	$photo_path = '/files/site_authors/' . (int)$author['id'] . '/photo/' . $author['photo'];
	if (file_exists(ROOT_DIR . ltrim($photo_path, '/'))) {
		return $photo_path;
	}
	return '';
}

/**
 * @return array<string,string>
 */
function author_parse_social_links($raw) {
	if (!function_exists('author_social_profiles_decode')) {
		require_once ROOT_DIR . 'functions/author_social.php';
	}
	if (is_array($raw)) {
		return author_social_profiles_decode($raw);
	}
	return author_parse_social_links_legacy($raw);
}

/**
 * Social + reference bundle from author row.
 *
 * @param array<string,mixed> $author
 * @return array{profiles:array<string,string>,references:array<int,array{label:string,url:string}>,same_as:string[]}
 */
function author_social_data(array $author) {
	if (!function_exists('author_social_bundle')) {
		require_once ROOT_DIR . 'functions/author_social.php';
	}
	return author_social_bundle($author);
}

function author_social_label($key) {
	$labels = array(
		'linkedin' => 'LinkedIn',
		'x' => 'X',
		'twitter' => 'X',
		'facebook' => 'Facebook',
		'instagram' => 'Instagram',
		'youtube' => 'YouTube',
		'tiktok' => 'TikTok',
		'threads' => 'Threads',
		'wikipedia' => 'Wikipedia',
		'google_scholar' => 'Google Scholar',
		'orcid' => 'ORCID',
		'website' => 'Website',
	);
	$key = strtolower((string)$key);
	return isset($labels[$key]) ? $labels[$key] : ucfirst(str_replace(array('-', '_'), ' ', $key));
}

function author_schema_person_id(array $author, $abc = null) {
	if ($abc === null) {
		global $abc;
	}
	$origin = function_exists('site_seo_public_origin') ? site_seo_public_origin() : ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
	return rtrim($origin, '/') . author_profile_url($author, $abc) . '#person';
}

/**
 * @return array<string,mixed>
 */
function author_schema_person(array $author, $abc = null, $reference_only = false) {
	if ($reference_only) {
		return array('@id' => author_schema_person_id($author, $abc));
	}
	if ($abc === null) {
		global $abc;
	}
	$origin = function_exists('site_seo_public_origin') ? site_seo_public_origin() : ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
	$person = array(
		'@type' => 'Person',
		'@id' => author_schema_person_id($author, $abc),
		'name' => (string)($author['name'] ?? ''),
		'url' => rtrim($origin, '/') . author_profile_url($author, $abc),
	);
	if (!empty($author['job_title'])) {
		$person['jobTitle'] = (string)$author['job_title'];
	}
	$photo = author_photo_url($author);
	if ($photo !== '') {
		$person['image'] = rtrim($origin, '/') . $photo;
	}
	if (trim(strip_tags((string)($author['bio_short'] ?? ''))) !== '') {
		$person['description'] = trim(strip_tags((string)$author['bio_short']));
	} elseif (trim(strip_tags((string)($author['bio'] ?? ''))) !== '') {
		$person['description'] = mb_substr(trim(strip_tags((string)$author['bio'])), 0, 300);
	}
	$social = author_social_data($author);
	if (!empty($social['same_as'])) {
		$person['sameAs'] = $social['same_as'];
	}
	return $person;
}

function author_byline_prefix_label() {
	$label = (string)(function_exists('i18n') ? i18n('common|author_byline_prefix') : '');
	if ($label === '' || $label === 'common|author_byline_prefix') {
		return 'By';
	}
	return $label;
}

function author_format_date_line($date_raw) {
	$date_raw = trim((string)$date_raw);
	if ($date_raw === '' || $date_raw === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($date_raw);
	if ($ts === false) {
		return '';
	}
	return date('M j, Y', $ts);
}

function author_excerpt(array $author, $max = 220) {
	$text = '';
	if (!empty($author['bio_short'])) {
		$text = trim(strip_tags((string)$author['bio_short']));
	} elseif (!empty($author['bio'])) {
		$text = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$author['bio'])));
	}
	if ($text === '') {
		return '';
	}
	if (function_exists('mb_strlen') && mb_strlen($text) > $max) {
		return mb_substr($text, 0, $max - 1) . '…';
	}
	if (strlen($text) > $max) {
		return substr($text, 0, $max - 1) . '…';
	}
	return $text;
}

function author_resolve_id_by_slug($slug, $lang_id = 0) {
	$slug = author_normalize_slug($slug);
	if ($slug === '') {
		return 0;
	}
	if ($lang_id <= 0) {
		$lang_id = author_current_lang_id();
	}
	$row = mysql_select("
		SELECT id FROM site_authors
		WHERE display=1 AND url='" . mysql_res($slug) . "'
		LIMIT 1
	", 'row');
	if ($row) {
		return (int)$row['id'];
	}
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$ci = mysql_select("
			SELECT entity_id
			FROM content_i18n
			WHERE entity='authors'
			  AND lang_id=" . (int)$lang_id . "
			  AND url='" . mysql_res($slug) . "'
			  AND status IN ('draft','review','published')
			ORDER BY FIELD(status,'published','review','draft') ASC, id DESC
			LIMIT 1
		", 'row');
		if ($ci) {
			return (int)$ci['entity_id'];
		}
	}
	if (preg_match('/^author-(\d+)$/', $slug, $m)) {
		return (int)$m[1];
	}
	return 0;
}

function author_id_for_abc($abc) {
	$author_id = 0;
	if (isset($abc['page']['author_id']) && (int)$abc['page']['author_id'] > 0) {
		$author_id = (int)$abc['page']['author_id'];
	} elseif (isset($abc['guide_single']['author_id']) && (int)$abc['guide_single']['author_id'] > 0) {
		$author_id = (int)$abc['guide_single']['author_id'];
	} elseif (isset($abc['game_single']['author_id']) && (int)$abc['game_single']['author_id'] > 0) {
		$author_id = (int)$abc['game_single']['author_id'];
	} elseif (isset($abc['casino_single']['author_id']) && (int)$abc['casino_single']['author_id'] > 0) {
		$author_id = (int)$abc['casino_single']['author_id'];
	}
	if ($author_id === 0) {
		$settings = array('randomize_missing' => 0);
		$vr = @mysql_select("SELECT value FROM variables WHERE `key`='authors_settings' LIMIT 1", 'row');
		if ($vr && $vr['value'] !== '') {
			$dec = json_decode($vr['value'], true);
			if (is_array($dec)) {
				$settings = array_merge($settings, $dec);
			}
		}
		if (!empty($settings['randomize_missing'])) {
			$rand = mysql_select("SELECT id FROM site_authors WHERE display=1 ORDER BY RAND() LIMIT 1", 'row');
			if ($rand) {
				$author_id = (int)$rand['id'];
			}
		}
	}
	if ($author_id === 0) {
		$author_id = 1;
	}
	return $author_id;
}

/**
 * @return array<string,mixed>|null
 */
function author_for_abc($abc) {
	$author_id = author_id_for_abc($abc);
	$author = author_row_by_id($author_id);
	if (!$author) {
		return array(
			'id' => 0,
			'name' => 'James Mitchell',
			'job_title' => 'iGaming Strategy Expert & Professional Player',
			'bio' => 'James has over 10 years of experience in the online gambling industry.',
			'bio_short' => '',
		);
	}
	return author_apply_locale($author);
}

/**
 * @return array<string,mixed>
 */
function author_schema_person_full(array $author, $abc = null) {
	return author_schema_person($author, $abc, false);
}

/**
 * @return array<string,mixed>
 */
function author_schema_person_ref(array $author, $abc = null) {
	return author_schema_person($author, $abc, true);
}

// Legacy name kept for callers expecting full inline Person object.
function author_schema_person_legacy_inline(array $author, $abc = null) {
	return author_schema_person_full($author, $abc);
}

function author_about_link_label() {
	$label = (string)(function_exists('i18n') ? i18n('common|author_about_link') : '');
	if ($label === '' || $label === 'common|author_about_link') {
		return 'About the author';
	}
	return $label;
}

function author_read_more_label() {
	$label = (string)(function_exists('i18n') ? i18n('common|read_more') : 'Read more');
	if ($label === '' || $label === 'Read more') {
		return 'Read more';
	}
	return $label;
}

function author_list_title() {
	$label = (string)(function_exists('i18n') ? i18n('common|authors_title') : '');
	if ($label === '' || $label === 'common|authors_title') {
		return 'Authors';
	}
	return $label;
}
