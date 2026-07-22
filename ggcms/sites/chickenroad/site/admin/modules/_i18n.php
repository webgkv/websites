<?php
/**
 * Admin helper: scalable translations stored in content_i18n.
 * Entity examples: pages, guides, games, casino_articles, blog
 */

// Ensure system_log_add is available for admin-side debugging.
// Some admin endpoints might not include system_log.php by default.
if (!function_exists('system_log_add')) {
	if (defined('ROOT_DIR')) {
		@require_once(ROOT_DIR . 'functions/system_log.php');
	} else {
		// /site/admin/modules/_i18n.php -> /site/functions/system_log.php
		@require_once(__DIR__ . '/../../functions/system_log.php');
	}
}

function admin_i18n_enabled_languages() {
	$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
	$enabled_ids = array();
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') {
			$dec = json_decode($row['value'], true);
			if (is_array($dec) && !empty($dec['enabled_lang_ids']) && is_array($dec['enabled_lang_ids'])) {
				$enabled_ids = array_values(array_filter(array_map('intval', $dec['enabled_lang_ids'])));
			}
		}
	}
	if (!empty($enabled_ids)) {
		$set = array_flip($enabled_ids);
		$langs = array_values(array_filter($langs, function($l) use ($set) { return isset($set[(int)$l['id']]); }));
	}
	return $langs;
}

/**
 * Public site path for a translated row (same structure as front modules: blog, guides, games, casinos, pages).
 *
 * @param string $entity
 * @param int $entity_id
 * @param string $lang_url languages.url (e.g. en), may be empty
 * @param string $ci_url content_i18n.url
 * @return string path starting with / or empty
 */
function admin_i18n_public_material_path($entity, $entity_id, $lang_url, $ci_url) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_url = trim((string)$lang_url, '/');
	$slug = trim((string)$ci_url, '/');
	if ($slug === '') {
		return '';
	}
	$path_slug = $slug;
	if (strpos($slug, '/') !== false) {
		$parts = explode('/', $slug);
		$path_slug = (string)end($parts);
	}
	$prefix = ($lang_url !== '') ? ('/' . $lang_url) : '';

	if ($entity === 'blog') {
		return $prefix . '/blog/' . $path_slug . '/';
	}
	if ($entity === 'games') {
		return $prefix . '/games/' . $path_slug . '/';
	}
	if ($entity === 'casino_articles') {
		return $prefix . '/casinos/' . $path_slug . '/';
	}
	if ($entity === 'guides') {
		$cat = '';
		if ($entity_id > 0 && @mysql_select("SHOW TABLES LIKE 'guides'", 'num_rows') > 0) {
			$gr = mysql_select("SELECT category FROM guides WHERE id=" . (int)$entity_id . " LIMIT 1", 'row');
			if ($gr && isset($gr['category'])) {
				$cat = trim((string)$gr['category'], '/');
			}
		}
		if ($cat === '') {
			return $prefix . '/guides/' . $path_slug . '/';
		}
		return $prefix . '/guides/' . $cat . '/' . $path_slug . '/';
	}
	if ($entity === 'pages') {
		return $prefix . '/' . $path_slug . '/';
	}
	return $prefix . '/' . $path_slug . '/';
}

/**
 * Storage-only slug for content_i18n.url (authors are not routed by slug).
 * Prevents uniq_lang_url (lang_id, entity, url) from treating every author as url ''.
 */
function admin_i18n_author_storage_url($entity_id) {
	$entity_id = (int)$entity_id;
	return $entity_id > 0 ? ('author-' . $entity_id) : '';
}

/**
 * One canonical row per (entity, entity_id, lang_id). Must be identical in get / save / verify / clear —
 * otherwise duplicates in DB cause "save" to update a different row than the form displays.
 */
function admin_i18n_sql_order_primary_row() {
	return "FIELD(status,'published','review','draft','missing') ASC, id DESC";
}

function admin_i18n_get($entity, $entity_id, $lang_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) return null;
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) return null;
	$row = mysql_select("
		SELECT *
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . (int)$entity_id . "
		  AND lang_id=" . (int)$lang_id . "
		ORDER BY " . admin_i18n_sql_order_primary_row() . "
		LIMIT 1
	", 'row');

	// Debug: show which content_i18n row is picked for rendering.
	// Needed when UI says "saved but not changed" but system_logs shows persisted.
	if (!empty($_GET['edit_debug']) && (string)$_GET['edit_debug'] === '1' && function_exists('system_log_add') && is_array($row)) {
		system_log_add('translations', 'info', 'admin_i18n_get pick', array(
			'entity' => $entity,
			'entity_id' => (int)$entity_id,
			'lang_id' => (int)$lang_id,
			'picked_id' => isset($row['id']) ? (int)$row['id'] : 0,
			'picked_status' => isset($row['status']) ? (string)$row['status'] : '',
		));
	}

	return $row;
}

function admin_i18n_save($entity, $entity_id, $lang_id, $data, $debug = false) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) return array('ok' => false, 'message' => 'Bad params');
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) return array('ok' => false, 'message' => 'Table content_i18n not found');
	if (!is_array($data)) $data = array();
	$debug = !empty($debug);

	// Replace common placeholders so images render in editor and frontend (e.g. /files/guides/{{GUIDE_ID}}/...)
	if (isset($data['content'])) {
		$h = (string)$data['content'];
		$id = (int)$entity_id;
		// Direct tokens (handle typical casing)
		$h = str_replace('{{ID}}', (string)$id, $h);
		$h = str_ireplace('{{GUIDE_ID}}', (string)$id, $h);
		$h = str_ireplace('{{GAME_ID}}', (string)$id, $h);
		$h = str_ireplace('{{CASINO_ID}}', (string)$id, $h);
		$h = str_ireplace('{{BLOG_ID}}', (string)$id, $h);
		$h = str_ireplace('{{POST_ID}}', (string)$id, $h);
		$h = str_ireplace('{{PAGE_ID}}', (string)$id, $h);

		// Also handle url-encoded braces (seen in browser console for TinyMCE content)
		$h = str_ireplace('%7b%7bid%7d%7d', (string)$id, $h);
		$h = str_ireplace('%7b%7bguide_id%7d%7d', (string)$id, $h);
		$h = str_ireplace('%7b%7bguid%7d%7d', (string)$id, $h); // extra safety for weird tokens

		// Guides: replace any placeholder segment in /files/guides/{{...}}/img/...
		if ($entity === 'guides') {
			$h = preg_replace(
				'#/files/guides/(?:\{\{[^}]+\}\}|%7b%7b[^%]+%7d%7d)/img/#iu',
				'/files/guides/' . (int)$id . '/img/',
				$h
			);
		} elseif ($entity === 'games') {
			$h = preg_replace(
				'#/files/games/(?:\{\{[^}]+\}\}|%7b%7b[^%]+%7d%7d)/img/#iu',
				'/files/games/' . (int)$id . '/img/',
				$h
			);
		} elseif ($entity === 'casino_articles') {
			$h = preg_replace(
				'#/files/casino_articles/(?:\{\{[^}]+\}\}|%7b%7b[^%]+%7d%7d)/img/#iu',
				'/files/casino_articles/' . (int)$id . '/img/',
				$h
			);
		}
		$data['content'] = $h;

		require_once ROOT_DIR . 'functions/media_library.php';
		require_once ROOT_DIR . 'functions/media_image.php';
		$purged = media_image_purge_missing_media_from_html($data['content']);
		$data['content'] = $purged['html'];
	}

	// SEO Monitor limits on UTF-8 display title (≤70) and meta description (≤160); source locale reads main table fields.
	if (in_array($entity, array('pages', 'guides', 'games', 'casino_articles', 'blog'), true)) {
		if (!function_exists('translation_cluster_trim_seo_text')) {
			$i18n_root = defined('ROOT_DIR') ? ROOT_DIR : dirname(dirname(__DIR__)) . '/';
			require_once $i18n_root . 'functions/translation_cluster.php';
		}
		foreach (array('name' => 70, 'title' => 70, 'description' => 160) as $mk => $lim) {
			if (array_key_exists($mk, $data) && trim((string)$data[$mk]) !== '') {
				$data[$mk] = translation_cluster_trim_seo_text($data[$mk], $lim);
			}
		}
	}

	$allowed = array('url','name','title','description','content','status','extra');
	$row = array(
		'entity' => $entity,
		'entity_id' => $entity_id,
		'lang_id' => $lang_id,
		'updated_at' => date('Y-m-d H:i:s'),
	);
	foreach ($allowed as $k) {
		if (array_key_exists($k, $data)) $row[$k] = $data[$k];
	}

	if ($entity === 'authors') {
		$slug = trim((string)($row['url'] ?? ''), '/');
		if ($slug === '' || strpos($slug, 'author-') === 0) {
			$slug = '';
		}
		$row['url'] = $slug !== '' ? $slug : admin_i18n_author_storage_url($entity_id);
	}

	// Auto-populate empty translated URL slug with canonical slug (or slugify name) to prevent duplicate empty string clash
	if (in_array($entity, array('pages', 'guides', 'games', 'casino_articles', 'blog'), true)) {
		if (!isset($row['url']) || trim((string)$row['url']) === '') {
			$canonical_row = mysql_select("SELECT url FROM `" . preg_replace('/[^a-z0-9_]/i', '', $entity) . "` WHERE id=" . (int)$entity_id . " LIMIT 1", 'row');
			if ($canonical_row && isset($canonical_row['url']) && trim((string)$canonical_row['url']) !== '') {
				$row['url'] = trim((string)$canonical_row['url'], '/');
			} else {
				$name_to_use = isset($row['name']) ? (string)$row['name'] : '';
				if ($name_to_use === '') {
					$canonical_name_row = mysql_select("SELECT name FROM `" . preg_replace('/[^a-z0-9_]/i', '', $entity) . "` WHERE id=" . (int)$entity_id . " LIMIT 1", 'row');
					if ($canonical_name_row && isset($canonical_name_row['name'])) $name_to_use = $canonical_name_row['name'];
				}
				if ($name_to_use !== '') {
					$row['url'] = trunslit($name_to_use);
				}
			}
		}
		if (isset($row['url'])) {
			$u = trim((string)$row['url'], '/');
			$u = str_replace('_', '-', $u);
			$u = trunslit($u);
			$u = mb_strtolower($u, 'UTF-8');
			$u = preg_replace('~[^a-z0-9-]+~u', '-', $u);
			$u = preg_replace('~-+~u', '-', $u);
			$row['url'] = trim($u, '-');
		}
	}

	if (empty($row['status'])) $row['status'] = 'draft';
	if (!in_array($row['status'], array('missing','draft','review','published'), true)) $row['status'] = 'draft';
	if (!isset($row['created_at'])) $row['created_at'] = date('Y-m-d H:i:s');

	$existing = mysql_select("
		SELECT id FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . $entity_id . "
		  AND lang_id=" . $lang_id . "
		ORDER BY " . admin_i18n_sql_order_primary_row() . "
		LIMIT 1
	", 'row');

	// Verify duplicate translated URL slug to prevent unique constraint DB crash
	if (isset($row['url']) && in_array($entity, array('pages', 'guides', 'games', 'casino_articles', 'blog'), true)) {
		$url_chk = trim((string)$row['url'], '/');
		if ($url_chk !== '') {
			$dup = mysql_select("
				SELECT id, entity_id
				FROM content_i18n
				WHERE entity='" . mysql_res($entity) . "'
				  AND lang_id=" . (int)$lang_id . "
				  AND url='" . mysql_res($url_chk) . "'
				LIMIT 1
			", 'row');
			if ($dup && isset($dup['id'], $dup['entity_id']) && (int)$dup['entity_id'] !== (int)$entity_id) {
				return array('ok' => false, 'message' => 'The URL slug "' . htmlspecialchars($url_chk) . '" is already used by another translation.');
			}
		}
	}

	// `uniq_lang_url` is (lang_id, entity, url) without entity_id (see migrate_BD_run). A row tied to another
	// entity_id with the same slug blocks INSERT — reclaim it for this entity_id so SEO/import saves work.
	if (!$existing && isset($row['url']) && in_array($entity, array('pages', 'guides', 'games', 'casino_articles', 'blog'), true)) {
		$url_chk = trim((string)$row['url'], '/');
		if ($url_chk !== '') {
			$dup = mysql_select("
				SELECT id, entity_id
				FROM content_i18n
				WHERE entity='" . mysql_res($entity) . "'
				  AND lang_id=" . (int)$lang_id . "
				  AND url='" . mysql_res($url_chk) . "'
				LIMIT 1
			", 'row');
			if ($dup && isset($dup['id'], $dup['entity_id']) && (int)$dup['entity_id'] !== (int)$entity_id) {
				if (function_exists('mysql_fn')) {
					$ok_re = mysql_fn('update', 'content_i18n', array(
						'entity_id' => (int)$entity_id,
						'updated_at' => date('Y-m-d H:i:s'),
					), ' AND id=' . (int)$dup['id'] . ' ');
					if ($ok_re !== false && function_exists('system_log_add')) {
						system_log_add('translations', 'warn', 'i18n_save reclaimed uniq_lang_url row', array(
							'entity' => $entity,
							'row_id' => (int)$dup['id'],
							'from_entity_id' => (int)$dup['entity_id'],
							'to_entity_id' => (int)$entity_id,
							'lang_id' => (int)$lang_id,
							'url' => $url_chk,
						));
					}
				}
				$existing = mysql_select("
					SELECT id FROM content_i18n
					WHERE entity='" . mysql_res($entity) . "'
					  AND entity_id=" . $entity_id . "
					  AND lang_id=" . $lang_id . "
					ORDER BY " . admin_i18n_sql_order_primary_row() . "
					LIMIT 1
				", 'row');
			}
		}
	}

	$expected = array();
	foreach (array('url','name','title','description','content','status') as $k) {
		if (array_key_exists($k, $row)) $expected[$k] = (string)$row[$k];
	}

	// Debug helper: avoid dumping huge HTML into response/UI.
	$make_preview = function ($s) {
		$s = (string)$s;
		$l = strlen($s);
		$head = $l > 0 ? substr($s, 0, 300) : '';
		$tail = $l > 0 ? substr($s, max(0, $l - 300)) : '';
		return array('len' => $l, 'head' => $head, 'tail' => $tail);
	};
	$debug_payload = array();

	// Always log an attempt so we can correlate "Save successful" with DB state.
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'i18n_save attempt', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'lang_id' => $lang_id,
			'existing_id' => $existing ? (int)$existing['id'] : 0,
			'expected_status' => isset($expected['status']) ? (string)$expected['status'] : '',
			'title_len' => isset($expected['title']) ? strlen((string)$expected['title']) : 0,
			'content_len' => isset($expected['content']) ? strlen((string)$expected['content']) : 0,
		));
	}

	if ($existing) {
		$id = (int)$existing['id'];
		unset($row['entity'], $row['entity_id'], $row['lang_id'], $row['created_at']);
		$updated = mysql_fn('update', 'content_i18n', $row, " AND id=" . $id . " ");
		if ($updated === false) {
			return array('ok' => false, 'message' => 'Translation update failed');
		}
	}

	if (!$existing) {
		$row['created_at'] = date('Y-m-d H:i:s');
		$inserted = mysql_fn('insert', 'content_i18n', $row);
		if ($inserted === false) return array('ok' => false, 'message' => 'Translation insert failed');
	}

	// Verify persisted values (helps debug "Saved but not changed").
	$check = mysql_select("
		SELECT url,name,title,description,content,status
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . (int)$entity_id . "
		  AND lang_id=" . (int)$lang_id . "
		ORDER BY " . admin_i18n_sql_order_primary_row() . "
		LIMIT 1
	", 'row');
	if (!$check) {
		if ($debug) {
			$debug_payload = array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'lang_id' => $lang_id,
				'expected' => array(
					'title' => $make_preview(isset($expected['title']) ? $expected['title'] : ''),
					'description' => $make_preview(isset($expected['description']) ? $expected['description'] : ''),
					'content' => $make_preview(isset($expected['content']) ? $expected['content'] : ''),
					'status' => isset($expected['status']) ? (string)$expected['status'] : '',
				),
			);
		}
		return array('ok' => false, 'message' => 'Translation save verification failed: row not found', 'debug' => $debug_payload);
	}

	$mismatch = array();
	foreach ($expected as $k => $v) {
		$got = isset($check[$k]) ? (string)$check[$k] : '';
		if (md5($got) !== md5($v)) $mismatch[] = $k;
	}
	if (!empty($mismatch)) {
		if (function_exists('system_log_add')) {
			system_log_add('translations', 'error', 'i18n_save verification mismatch', array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'lang_id' => $lang_id,
				'mismatch_fields' => $mismatch,
				'expected_md5' => array_map(function($x){ return md5((string)$x); }, $expected),
				'got_md5' => array_map(function($x){ return md5((string)$x); }, array_intersect_key($check, $expected)),
			));
		}
		if ($debug) {
			$debug_payload = array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'lang_id' => $lang_id,
				'expected' => array(
					'url' => isset($expected['url']) ? (string)$expected['url'] : '',
					'name' => isset($expected['name']) ? (string)$expected['name'] : '',
					'title' => $make_preview(isset($expected['title']) ? $expected['title'] : ''),
					'description' => $make_preview(isset($expected['description']) ? $expected['description'] : ''),
					'content' => $make_preview(isset($expected['content']) ? $expected['content'] : ''),
					'status' => isset($expected['status']) ? (string)$expected['status'] : '',
				),
				'got' => array(
					'url' => isset($check['url']) ? (string)$check['url'] : '',
					'name' => isset($check['name']) ? (string)$check['name'] : '',
					'title' => $make_preview(isset($check['title']) ? $check['title'] : ''),
					'description' => $make_preview(isset($check['description']) ? $check['description'] : ''),
					'content' => $make_preview(isset($check['content']) ? $check['content'] : ''),
					'status' => isset($check['status']) ? (string)$check['status'] : '',
				),
				'mismatch_fields' => $mismatch,
			);
		}
		return array('ok' => false, 'message' => 'Translation save verification failed (not persisted)', 'debug' => $debug_payload);
	}

	// Persisted: always write a concise info log for debugging "saved but not visible".
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'i18n_save persisted', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'lang_id' => $lang_id,
			'status' => isset($expected['status']) ? (string)$expected['status'] : '',
			'title_len' => isset($expected['title']) ? strlen((string)$expected['title']) : 0,
			'content_len' => isset($expected['content']) ? strlen((string)$expected['content']) : 0,
		));
	}

	if ($debug) {
		$debug_payload = array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'lang_id' => $lang_id,
			'expected' => array(
				'status' => isset($expected['status']) ? (string)$expected['status'] : '',
				'title' => $make_preview(isset($expected['title']) ? $expected['title'] : ''),
				'content' => $make_preview(isset($expected['content']) ? $expected['content'] : ''),
			),
			'got' => array(
				'status' => isset($check['status']) ? (string)$check['status'] : '',
				'title' => $make_preview(isset($check['title']) ? $check['title'] : ''),
				'content' => $make_preview(isset($check['content']) ? $check['content'] : ''),
			),
			'mismatch_fields' => array(),
		);
	}

	return array('ok' => true, 'message' => $existing ? 'Translation updated' : 'Translation created', 'debug' => $debug_payload);
}

/**
 * After admin_i18n_save, mirror the persisted content_i18n row into the entity base table for the canonical language.
 * Must use DB row (trimmed meta, etc.), not the raw POST payload — otherwise SEO fields in `blog` / `pages` drift from i18n.
 *
 * @return bool true if sync ran and DB update was attempted
 */
function admin_i18n_sync_canonical_row_to_base_table($entity, $entity_id, $lang_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) {
		return false;
	}
	$canonical_lang_id = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($vr && $vr['value'] !== '') {
			$dec = json_decode($vr['value'], true);
			if (is_array($dec) && isset($dec['source_lang_id'])) {
				$canonical_lang_id = (int)$dec['source_lang_id'];
			}
		}
	}
	if ($canonical_lang_id <= 0 || $lang_id !== $canonical_lang_id) {
		return false;
	}
	$row = admin_i18n_get($entity, $entity_id, $lang_id);
	if (!$row || !is_array($row)) {
		return false;
	}
	if (isset($row['status']) && (string)$row['status'] === 'missing') {
		return false;
	}

	if ($entity === 'pages') {
		$page_update = array(
			'name' => isset($row['name']) ? (string)$row['name'] : '',
			'url' => isset($row['url']) ? trim((string)$row['url'], '/') : '',
			'title' => isset($row['title']) ? (string)$row['title'] : '',
			'description' => isset($row['description']) ? (string)$row['description'] : '',
		);
		if (array_key_exists('content', $row)) {
			$page_update['text'] = (string)$row['content'];
		}
		return mysql_fn('update', 'pages', $page_update, ' AND id=' . (int)$entity_id . ' ') !== false;
	}

	if ($entity === 'authors') {
		$author_update = array();
		if (isset($row['name']) && trim((string)$row['name']) !== '') {
			$author_update['name'] = (string)$row['name'];
		}
		if (isset($row['title']) && trim((string)$row['title']) !== '') {
			$author_update['job_title'] = (string)$row['title'];
		}
		if (isset($row['description']) && trim((string)$row['description']) !== '') {
			$author_update['bio_short'] = (string)$row['description'];
		}
		if (isset($row['content']) && trim((string)$row['content']) !== '') {
			$author_update['bio'] = (string)$row['content'];
		}
		if (isset($row['url'])) {
			$slug = trim((string)$row['url'], '/');
			if ($slug !== '' && strpos($slug, 'author-') !== 0) {
				if (!function_exists('author_normalize_slug')) {
					require_once ROOT_DIR . 'functions/author_profiles.php';
				}
				$author_update['url'] = author_normalize_slug($slug);
			}
		}
		if (empty($author_update)) {
			return false;
		}
		return mysql_fn('update', 'site_authors', $author_update, ' AND id=' . (int)$entity_id . ' ') !== false;
	}

	if (!in_array($entity, array('guides', 'games', 'casino_articles', 'blog', 'promo'), true)) {
		return false;
	}

	$update_base = array();
	if (isset($row['url']) && trim((string)$row['url']) !== '') {
		$update_base['url'] = trim((string)$row['url'], '/');
	}
	if (isset($row['name']) && trim((string)$row['name']) !== '') {
		$update_base['name'] = (string)$row['name'];
	}
	if (isset($row['title']) && trim((string)$row['title']) !== '') {
		$update_base['title'] = (string)$row['title'];
	}
	if (isset($row['description']) && trim((string)$row['description']) !== '') {
		$update_base['description'] = (string)$row['description'];
		if ($entity === 'blog' || $entity === 'promo') {
			$update_base['name_2'] = (string)$row['description'];
		}
	}
	if (isset($row['content']) && trim((string)$row['content']) !== '') {
		$update_base['text'] = (string)$row['content'];
	}
	if (empty($update_base)) {
		return false;
	}
	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'i18n(canonical)->Common sync (from DB row)', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'lang_id' => $lang_id,
		));
	}
	return mysql_fn('update', $entity, $update_base, ' AND id=' . (int)$entity_id . ' ') !== false;
}

/**
 * Clear translation values for the selected language version.
 * Sets status='missing' and clears title/description/content (keeps url/name).
 *
 * Translation Monitor uses status='missing' to decide missing candidates.
 */
function admin_i18n_clear($entity, $entity_id, $lang_id, $debug = false) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) return array('ok' => false, 'message' => 'Bad params');
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) return array('ok' => false, 'message' => 'Table content_i18n not found');
	$debug = !empty($debug);

	$existing = mysql_select("
		SELECT id
		FROM content_i18n
		WHERE entity='" . mysql_res($entity) . "'
		  AND entity_id=" . (int)$entity_id . "
		  AND lang_id=" . (int)$lang_id . "
		ORDER BY " . admin_i18n_sql_order_primary_row() . "
		LIMIT 1
	", 'row');
	if (!$existing) {
		// If there is no translation row, it is already "missing" for monitor purposes.
		return array('ok' => true, 'message' => 'No translation row to clear (already missing)');
	}

	$id = (int)$existing['id'];

	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'i18n_clear attempt', array(
			'entity' => $entity,
			'entity_id' => $entity_id,
			'lang_id' => $lang_id,
			'existing_id' => $id,
		));
	}

	$upd = array(
		'status' => 'missing',
		'title' => '',
		'description' => '',
		'content' => '',
		'updated_at' => date('Y-m-d H:i:s'),
	);
	$updated = mysql_fn('update', 'content_i18n', $upd, " AND id=" . $id . " ");
	if ($updated === false) {
		return array('ok' => false, 'message' => 'Translation clear update failed (no affected rows)');
	}

	$check = mysql_select("
		SELECT status, title, description, content
		FROM content_i18n
		WHERE id=" . (int)$id . "
		LIMIT 1
	", 'row');
	$check_status = isset($check['status']) ? (string)$check['status'] : '';
	$content_len = isset($check['content']) ? strlen((string)$check['content']) : 0;

	if ($check_status !== 'missing') {
		return array(
			'ok' => false,
			'message' => 'Translation clear failed: status not set to missing',
			'debug' => $debug ? array('got_status' => $check_status, 'expected_status' => 'missing') : array()
		);
	}
	if ($content_len > 0) {
		return array(
			'ok' => false,
			'message' => 'Translation clear failed: content not empty after clear',
			'debug' => $debug ? array('content_len' => $content_len) : array()
		);
	}

	return array(
		'ok' => true,
		'message' => 'Translation cleared and marked missing',
		'debug' => $debug ? array('updated_id' => $id, 'content_len' => $content_len) : array()
	);
}

function admin_i18n_render_form($entity, $entity_id, $lang_id, $base_url, $defaults = array(), $options = array()) {
	$langs = admin_i18n_enabled_languages();
	$lang_id = (int)$lang_id;
	if ($lang_id <= 0 && !empty($langs)) $lang_id = (int)$langs[0]['id'];
	$canonical_lang_id = isset($options['canonical_lang_id']) ? (int)$options['canonical_lang_id'] : 0;
	if (!empty($options['profile_only']) && (string)$entity === 'authors' && (int)$entity_id > 0) {
		$from_base = admin_i18n_fetch_base_translation('authors', (int)$entity_id);
		if (is_array($from_base)) {
			$defaults = is_array($defaults) ? array_merge($defaults, $from_base) : $from_base;
		}
	}
	$t = admin_i18n_get($entity, $entity_id, $lang_id);
	$val = array(
		'url' => '',
		'name' => '',
		'title' => '',
		'description' => '',
		'content' => '',
		'status' => 'draft',
	);
	// When canonical / source language is selected, show data from site_authors (defaults); status is always Published
	$is_canonical_view = ($canonical_lang_id > 0 && $lang_id === $canonical_lang_id);
	if (!$is_canonical_view && (string)$entity === 'authors' && $canonical_lang_id > 0 && $lang_id > 0) {
		$lr = mysql_select('SELECT url FROM languages WHERE id=' . (int)$lang_id . ' LIMIT 1', 'row');
		$sr = mysql_select('SELECT url FROM languages WHERE id=' . (int)$canonical_lang_id . ' LIMIT 1', 'row');
		if ($lr && $sr) {
			$lu = strtolower(trim((string)$lr['url'], '/'));
			$su = strtolower(trim((string)$sr['url'], '/'));
			if ($lu !== '' && $lu === $su) {
				$is_canonical_view = true;
			}
		}
	}
	if ($is_canonical_view && is_array($defaults) && !empty($defaults)) {
		foreach (array_keys($val) as $k) {
			if (isset($defaults[$k])) $val[$k] = (string)$defaults[$k];
		}
		$val['status'] = 'published';
	} elseif ($t) {
		foreach ($val as $k => $v) {
			if (isset($t[$k])) $val[$k] = (string)$t[$k];
		}
	} elseif (is_array($defaults)) {
		foreach (array_keys($val) as $k) {
			if (isset($defaults[$k])) $val[$k] = (string)$defaults[$k];
		}
		// New translation (no content_i18n row yet): always Draft
		$val['status'] = 'draft';
	}
	$content_managed_elsewhere = !empty($options['content_managed_elsewhere']);
	$profile_only = !empty($options['profile_only']);
	$h = '';
	$h .= '<div class="col-12">';
	$h .= '<div class="card shadow-sm border-light mb-4">';
	
	// Card Header: Language version info & switcher
	$h .= '<div class="card-header bg-light py-3 border-bottom-0">';
	$h .= '  <div class="row align-items-center g-3">';
	$h .= '    <div class="col-md-4">';
	$h .= '      <h5 class="m-0 font-weight-bold text-primary d-flex align-items-center">';
	$h .= '        <i data-feather="globe" class="mr-2 text-primary" style="width:20px;height:20px;"></i>';
		$h .= '        Language Version Settings';
	$h .= '      </h5>';
	$h .= '    </div>';
	$h .= '    <div class="col-md-4">';
	$h .= '      <div class="d-flex align-items-center">';
	$h .= '        <label class="form-label mb-0 mr-2 text-nowrap font-weight-bold text-secondary">Language:</label>';
	$h .= '        <select class="form-control form-control-sm js-i18n-lang-switch font-weight-bold" name="i18n_lang_id" data-base-url="' . htmlspecialchars($base_url) . '" style="border-radius: 4px; border: 1px solid #ced4da;">';
	foreach ($langs as $l) {
		$sel = ((int)$l['id'] === $lang_id) ? ' selected' : '';
		$h .= '          <option value="' . (int)$l['id'] . '"' . $sel . '>' . htmlspecialchars($l['name'] . ' (' . $l['url'] . ')') . '</option>';
	}
	$h .= '        </select>';
	$h .= '      </div>';
	$h .= '    </div>';
	$h .= '    <div class="col-md-4">';
	$h .= '      <div class="d-flex align-items-center">';
	$h .= '        <label class="form-label mb-0 mr-2 text-nowrap font-weight-bold text-secondary">Status:</label>';
	$h .= '        <select class="form-control form-control-sm font-weight-bold text-capitalize" name="i18n_status" style="border-radius: 4px; border: 1px solid #ced4da;">';
	foreach (array('missing'=>'Missing','draft'=>'Draft','review'=>'Review','published'=>'Published') as $k => $label) {
		$sel = ($val['status'] === $k) ? ' selected' : '';
		$h .= '          <option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';
	}
	$h .= '        </select>';
	$h .= '      </div>';
	$h .= '    </div>';
	$h .= '  </div>';
	$h .= '</div>';
	
	// Card Body
	$h .= '<div class="card-body border-top">';
	
	if ($profile_only) {
		$h .= '  <div class="row g-3 mb-3">';
		$h .= '    <div class="col-md-6">';
		$h .= '      <label class="form-label font-weight-bold text-secondary">Name <span class="text-danger">*</span></label>';
		$h .= '      <input class="form-control font-weight-bold" name="i18n_name" value="' . htmlspecialchars($val['name']) . '" placeholder="Author name" style="border-radius: 6px; border: 1px solid #cbd5e1;">';
		$h .= '    </div>';
		$h .= '    <div class="col-md-6">';
		$h .= '      <label class="form-label font-weight-bold text-secondary">Job title / role</label>';
		$h .= '      <input class="form-control" name="i18n_title" value="' . htmlspecialchars($val['title']) . '" placeholder="e.g. iGaming Operations Analyst" style="border-radius: 6px; border: 1px solid #cbd5e1;">';
		$h .= '    </div>';
		$h .= '  </div>';
		$h .= '  <div class="row g-3 mb-3">';
		$h .= '    <div class="col-md-6">';
		$h .= '      <label class="form-label font-weight-bold text-secondary">URL slug</label>';
		$h .= '      <input class="form-control" name="i18n_url" value="' . htmlspecialchars($val['url']) . '" placeholder="james-mitchell" style="border-radius: 6px; border: 1px solid #cbd5e1;">';
		$h .= '      <small class="text-muted">Public profile: /{lang}/authors/{slug}/</small>';
		$h .= '    </div>';
		$h .= '  </div>';
		$h .= '  <div class="mb-4">';
		$h .= '    <label class="form-label font-weight-bold text-secondary mb-2">Short bio (card excerpt)</label>';
		$h .= '    <textarea class="form-control" name="i18n_description" rows="3" style="border-radius: 6px; min-height: 90px;">' . htmlspecialchars($val['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</textarea>';
		$h .= '  </div>';
		$ta_id = 'i18n_content_' . (string)$entity . '_' . (int)$entity_id . '_' . (int)$lang_id;
		$h .= '  <div class="mb-4">';
		$h .= '    <label class="form-label font-weight-bold text-secondary mb-2">Full biography (profile page)</label>';
		$h .= '    <textarea class="form-control" id="' . $ta_id . '" name="i18n_content" rows="8" style="border-radius: 6px; min-height: 180px;">' . htmlspecialchars($val['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</textarea>';
		$h .= '  </div>';
	} else {
		// Row 1: Name and URL Slug
		$h .= '  <div class="row g-3 mb-4">';
		$h .= '    <div class="col-md-8">';
		$h .= '      <label class="form-label font-weight-bold text-secondary">Material Name <span class="text-danger">*</span></label>';
		$h .= '      <input class="form-control font-weight-bold" name="i18n_name" value="' . htmlspecialchars($val['name']) . '" placeholder="Enter localized material name" style="border-radius: 6px; border: 1px solid #cbd5e1; padding: 0.375rem 0.75rem;">';
		$h .= '    </div>';
		$h .= '    <div class="col-md-4">';
		$h .= '      <label class="form-label font-weight-bold text-secondary">URL Slug</label>';
		$h .= '      <div class="input-group">';
		$h .= '        <div class="input-group-prepend"><span class="input-group-text bg-light text-muted" style="border-radius: 6px 0 0 6px; border: 1px solid #cbd5e1; border-right: none;">/</span></div>';
		$h .= '        <input class="form-control" name="i18n_url" value="' . htmlspecialchars($val['url']) . '" placeholder="url-slug" style="border-radius: 0 6px 6px 0; border: 1px solid #cbd5e1;">';
		$h .= '      </div>';
		$h .= '    </div>';
		$h .= '  </div>';
		
		// SEO Fields Section inside a beautiful subtle sub-card
		$h .= '  <div class="p-3 bg-light rounded border mb-4" style="background-color: #f8fafc; border-color: #e2e8f0; border-radius: 8px;">';
		$h .= '    <h6 class="mb-3 font-weight-bold text-secondary d-flex align-items-center" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">';
		$h .= '      <i data-feather="search" class="mr-2 text-muted" style="width:16px;height:16px;"></i>';
		$h .= '      SEO Meta Tags (Search Engine Optimization)';
		$h .= '    </h6>';
		$h .= '    <div class="row g-3">';
		$h .= '      <div class="col-md-6">';
		$h .= '        <label class="form-label font-weight-bold text-secondary" style="font-size: 0.85rem;">SEO Title</label>';
		$h .= '        <input class="form-control" name="i18n_title" value="' . htmlspecialchars($val['title']) . '" placeholder="Meta title for search results" style="border-radius: 6px; border: 1px solid #cbd5e1;">';
		$h .= '      </div>';
		$h .= '      <div class="col-md-6">';
		$h .= '        <label class="form-label font-weight-bold text-secondary" style="font-size: 0.85rem;">Description (Meta Description)</label>';
		$h .= '        <input class="form-control" name="i18n_description" value="' . htmlspecialchars($val['description']) . '" placeholder="Meta description for search results" style="border-radius: 6px; border: 1px solid #cbd5e1;">';
		$h .= '      </div>';
		$h .= '    </div>';
		$h .= '  </div>';
		
		// Text Content Editor Section
		if (!$content_managed_elsewhere) {
			$ta_id = 'i18n_content_' . (string)$entity . '_' . (int)$entity_id . '_' . (int)$lang_id;
			$h .= '  <div class="mb-4 tinymce">';
			$h .= '    <label class="form-label font-weight-bold text-secondary mb-2 d-flex align-items-center">';
			$h .= '      <i data-feather="edit-3" class="mr-2 text-muted" style="width:16px;height:16px;"></i>';
			$h .= '      Content Body (Rich Text Editor)';
			$h .= '    </label>';
			$h .= '    <div class="tinymce-editor-wrap" style="width:100%;max-width:100%;">';
			$h .= '      <textarea class="form-control admin-wysiwyg" id="' . $ta_id . '" name="i18n_content" style="width:100%;min-height:500px;height:500px; border-radius: 6px;">' . htmlspecialchars($val['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</textarea>';
			$h .= '    </div>';
			$h .= '  </div>';
		}
	}
	
	// Footer Buttons
	$h .= '  <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">';
	$clear_href = $base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'i18n_clear=1&i18n_lang_id=' . (int)$lang_id;
	$h .= '    <a href="' . htmlspecialchars($clear_href) . '" target="_top" class="btn btn-outline-danger btn-sm px-3" onclick="return confirm(\'Clear this translation values and mark as missing?\')" style="border-radius: 6px; font-weight: 500;">';
	$h .= '      <i data-feather="trash-2" class="mr-1 icon-sm" style="width:14px;height:14px;"></i> Clear Translation';
	$h .= '    </a>';
	$h .= '    <button type="submit" name="i18n_save" value="1" class="btn btn-primary btn-sm px-4" style="border-radius: 6px; font-weight: 600;">';
	$h .= '      <i data-feather="save" class="mr-1 icon-sm" style="width:14px;height:14px;"></i> Save Translation';
	$h .= '    </button>';
	$h .= '  </div>';
	
	$h .= '</div>'; // End Card Body
	$h .= '</div>'; // End Card
	$h .= '</div>'; // End col-12
	if (empty($options['no_export_import'])) {
		$h .= admin_i18n_render_export_import_block($entity, $entity_id, $lang_id, $base_url);
	}
	return $h;
}

/** @return string[] */
function admin_i18n_translatable_entities() {
	return array('pages', 'guides', 'games', 'casino_articles', 'blog', 'authors');
}

/**
 * content_i18n.entity key for an admin route / DB table (mirrors: site_authors → authors).
 */
function admin_i18n_entity_key($route_module = '', $table = '') {
	$route_module = trim((string)$route_module);
	$table = trim((string)$table);
	if ($route_module === 'authors' || $table === 'site_authors') {
		return 'authors';
	}
	if (in_array($route_module, admin_i18n_translatable_entities(), true)) {
		return $route_module;
	}
	if (in_array($table, admin_i18n_translatable_entities(), true)) {
		return $table;
	}
	return $table !== '' ? $table : $route_module;
}

/**
 * Fields passed to translate_cluster child jobs per entity.
 *
 * @return string[]
 */
function admin_i18n_cluster_translate_fields($entity) {
	$entity = trim((string)$entity);
	if ($entity === 'authors') {
		return array('name', 'title', 'description', 'content', 'url');
	}
	return array('name', 'title', 'description', 'content');
}

function admin_i18n_canonical_lang_id() {
	return admin_i18n_source_lang_id();
}

function admin_i18n_content_tab_for_entity($entity) {
	$map = array(
		'guides' => 'guides',
		'games' => 'games',
		'casino_articles' => 'casinos',
		'blog' => 'blog',
	);
	$entity = trim((string)$entity);
	return isset($map[$entity]) ? $map[$entity] : '';
}

function admin_i18n_parse_base_url_query($base_url) {
	$q = array();
	if (strpos((string)$base_url, '?') !== false) {
		parse_str((string)parse_url($base_url, PHP_URL_QUERY), $q);
	}
	return is_array($q) ? $q : array();
}

function admin_i18n_fetch_base_translation($entity, $entity_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	if (!in_array($entity, admin_i18n_translatable_entities(), true) || $entity_id <= 0) {
		return null;
	}
	$table = $entity;
	if ($entity === 'authors') {
		$table = 'site_authors';
	}
	$row = mysql_select("SELECT * FROM `" . preg_replace('/[^a-z0-9_]/i', '', $table) . "` WHERE id=" . $entity_id . " LIMIT 1", 'row');
	if (!$row || !is_array($row)) {
		return null;
	}
	if ($entity === 'authors') {
		return array(
			'url' => isset($row['url']) ? trim((string)$row['url'], '/') : '',
			'name' => isset($row['name']) ? (string)$row['name'] : '',
			'title' => isset($row['job_title']) ? (string)$row['job_title'] : '',
			'description' => isset($row['bio_short']) ? (string)$row['bio_short'] : '',
			'content' => isset($row['bio']) ? (string)$row['bio'] : '',
			'status' => 'published',
		);
	}
	return array(
		'url' => isset($row['url']) ? trim((string)$row['url'], '/') : '',
		'name' => isset($row['name']) ? (string)$row['name'] : '',
		'title' => isset($row['title']) ? (string)$row['title'] : '',
		'description' => isset($row['description']) ? (string)$row['description'] : '',
		'content' => isset($row['text']) ? (string)$row['text'] : '',
		'status' => 'published',
	);
}

/**
 * Copy full site_authors profile into content_i18n for the source language when i18n bio is truncated vs base.
 *
 * @return bool true if content_i18n was updated
 */
function admin_i18n_repair_author_canonical_from_base($author_id) {
	$author_id = (int)$author_id;
	if ($author_id <= 0) {
		return false;
	}
	$base = admin_i18n_fetch_base_translation('authors', $author_id);
	if (!$base || trim((string)$base['content']) === '') {
		return false;
	}
	$src = admin_i18n_source_lang_id();
	$t = admin_i18n_get('authors', $author_id, $src);
	$base_len = function_exists('mb_strlen') ? mb_strlen((string)$base['content'], 'UTF-8') : strlen((string)$base['content']);
	$i18n_bio = ($t && isset($t['content'])) ? trim((string)$t['content']) : '';
	$i18n_len = function_exists('mb_strlen') ? mb_strlen($i18n_bio, 'UTF-8') : strlen($i18n_bio);
	if ($i18n_len >= $base_len || ($base_len - $i18n_len) < 40) {
		return false;
	}
	$res = admin_i18n_save('authors', $author_id, $src, array(
		'url' => '',
		'name' => (string)$base['name'],
		'title' => (string)$base['title'],
		'description' => '',
		'content' => (string)$base['content'],
		'status' => 'published',
	));
	return !empty($res['ok']);
}

function admin_i18n_locale_to_translation($loc) {
	if (!is_array($loc)) {
		return array();
	}
	$st = isset($loc['status']) ? (string)$loc['status'] : 'published';
	if (!in_array($st, array('draft', 'review', 'published', 'missing'), true)) {
		$st = 'published';
	}
	return array(
		'url' => isset($loc['url']) ? trim((string)$loc['url'], '/') : '',
		'name' => (string)($loc['name'] ?? ''),
		'title' => (string)($loc['title'] ?? ''),
		'description' => (string)($loc['description'] ?? ''),
		'content' => (string)($loc['content'] ?? ''),
		'status' => $st,
	);
}

function admin_i18n_row_to_translation($row, $prev = array()) {
	if (!is_array($row)) {
		return array();
	}
	$body = isset($row['text']) ? (string)$row['text'] : '';
	$u0 = isset($row['url']) ? trim((string)$row['url']) : '';
	$prev = is_array($prev) ? $prev : array();
	return array(
		'url' => $u0 !== '' ? trim($u0, '/') : (isset($prev['url']) ? trim((string)$prev['url'], '/') : ''),
		'name' => (isset($row['name']) && trim((string)$row['name']) !== '') ? (string)$row['name'] : (string)($prev['name'] ?? ''),
		'title' => (isset($row['title']) && trim((string)$row['title']) !== '') ? (string)$row['title'] : (string)($prev['title'] ?? ''),
		'description' => (isset($row['description']) && trim((string)$row['description']) !== '') ? (string)$row['description'] : (string)($prev['description'] ?? ''),
		'content' => $body,
		'status' => (isset($prev['status']) && in_array((string)$prev['status'], array('draft', 'review', 'published', 'missing'), true))
			? (string)$prev['status'] : 'published',
	);
}

/**
 * @param array $defaults optional form defaults (canonical tab) for export when lang is canonical
 */
function admin_i18n_export_single_payload($entity, $entity_id, $lang_id, $defaults = array()) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$canonical = admin_i18n_canonical_lang_id();
	$translation = admin_i18n_fetch_base_translation($entity, $entity_id);
	if (!is_array($translation)) {
		$translation = array(
			'url' => '', 'name' => '', 'title' => '', 'description' => '', 'content' => '', 'status' => 'draft',
		);
	}
	if (is_array($defaults) && !empty($defaults)) {
		foreach (array('url', 'name', 'title', 'description', 'content', 'status') as $k) {
			if (isset($defaults[$k]) && (string)$defaults[$k] !== '') {
				$translation[$k] = (string)$defaults[$k];
			}
		}
		if ($lang_id === $canonical) {
			$translation['status'] = 'published';
		}
	}
	if ($lang_id !== $canonical) {
		$ti = admin_i18n_get($entity, $entity_id, $lang_id);
		if ($ti) {
			$translation = array(
				'url' => isset($ti['url']) ? trim((string)$ti['url'], '/') : '',
				'name' => isset($ti['name']) ? (string)$ti['name'] : '',
				'title' => isset($ti['title']) ? (string)$ti['title'] : '',
				'description' => isset($ti['description']) ? (string)$ti['description'] : '',
				'content' => isset($ti['content']) ? (string)$ti['content'] : '',
				'status' => isset($ti['status']) ? (string)$ti['status'] : 'draft',
			);
		}
	}
	$out = array(
		'exported_at' => date('c'),
		'entity' => $entity,
		'entity_id' => $entity_id,
		'lang_id' => $lang_id,
		'translation' => $translation,
	);
	if ($entity === 'pages') {
		$out['page_id'] = $entity_id;
	}
	return $out;
}

function admin_i18n_normalize_import_json($entity, $entity_id, $lang_id, $data) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$canonical = admin_i18n_canonical_lang_id();
	if (!is_array($data)) {
		return array('ok' => false, 'message' => 'Invalid JSON.');
	}
	if ($entity === 'pages' && isset($data['page_id']) && !isset($data['entity_id'])) {
		$data['entity_id'] = (int)$data['page_id'];
	}
	if (!isset($data['translation']) || !is_array($data['translation'])) {
		if (isset($data['locales']) && is_array($data['locales'])
			&& isset($data['entity']) && strcasecmp(trim((string)$data['entity']), $entity) === 0
			&& (int)($data['entity_id'] ?? 0) === $entity_id) {
			foreach ($data['locales'] as $loc) {
				if (!is_array($loc) || (int)($loc['lang_id'] ?? 0) !== $lang_id) {
					continue;
				}
				$data['translation'] = admin_i18n_locale_to_translation($loc);
				break;
			}
		}
	}
	$table_ok = !isset($data['table']) || strcasecmp(trim((string)$data['table']), $entity) === 0;
	if ($lang_id === $canonical && isset($data['rows']) && is_array($data['rows']) && $table_ok) {
		foreach ($data['rows'] as $r0) {
			if (!is_array($r0)) {
				continue;
			}
			$rid = isset($r0['id']) ? (int)$r0['id'] : 0;
			if ($rid !== 0 && $rid !== $entity_id) {
				continue;
			}
			if ($rid === 0 && count($data['rows']) !== 1) {
				continue;
			}
			$body = isset($r0['text']) ? (string)$r0['text'] : '';
			if (trim($body) === '') {
				continue;
			}
			$prev = (isset($data['translation']) && is_array($data['translation'])) ? $data['translation'] : array();
			$t_empty = !isset($data['translation']) || !is_array($data['translation'])
				|| trim((string)($data['translation']['content'] ?? '')) === '';
			if ($t_empty) {
				$data['translation'] = admin_i18n_row_to_translation($r0, $prev);
			}
			break;
		}
	}
	if (!isset($data['translation']) || !is_array($data['translation'])) {
		return array(
			'ok' => false,
			'message' => 'Invalid JSON or missing "translation" object. Use "Download JSON (this language)", a bulk export with rows[].text for this id, or SEO Monitor JSON (entity_id=' . $entity_id . ', lang_id=' . $lang_id . ').',
		);
	}
	return array('ok' => true, 'translation' => $data['translation']);
}

function admin_i18n_import_single_apply($entity, $entity_id, $lang_id, $translation) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if (!in_array($entity, admin_i18n_translatable_entities(), true) || $entity_id <= 0 || $lang_id <= 0) {
		return array('ok' => false, 'message' => 'Bad params');
	}
	if (!is_array($translation)) {
		return array('ok' => false, 'message' => 'Invalid translation');
	}
	$canonical = admin_i18n_canonical_lang_id();
	$payload = array(
		'url' => isset($translation['url']) ? trim((string)$translation['url'], '/') : '',
		'name' => isset($translation['name']) ? (string)$translation['name'] : '',
		'title' => isset($translation['title']) ? (string)$translation['title'] : '',
		'description' => isset($translation['description']) ? (string)$translation['description'] : '',
		'content' => isset($translation['content']) ? (string)$translation['content'] : '',
		'status' => isset($translation['status']) && in_array($translation['status'], array('draft', 'review', 'published', 'missing'), true)
			? $translation['status'] : 'draft',
	);
	$res = admin_i18n_save($entity, $entity_id, $lang_id, $payload);
	if (!empty($res['ok']) && $lang_id === $canonical) {
		if ($entity === 'pages') {
			$page_update = array(
				'name' => $payload['name'],
				'url' => $payload['url'],
				'title' => $payload['title'],
				'description' => $payload['description'],
				'text' => $payload['content'],
			);
			mysql_fn('update', 'pages', $page_update, ' AND id=' . $entity_id . ' ');
		} else {
			admin_i18n_sync_canonical_row_to_base_table($entity, $entity_id, $lang_id);
		}
	}
	return $res;
}

function admin_i18n_redirect_url_after_import($entity, $entity_id, $lang_id, $ctx, $flash = array()) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	$ftab = isset($ctx['ftab']) ? (int)$ctx['ftab'] : 0;
	$ok_key = ($entity === 'pages') ? 'import_page_i18n_ok' : 'import_i18n_ok';
	$err_key = ($entity === 'pages') ? 'import_page_i18n_error' : 'import_i18n_error';
	if ($entity === 'pages') {
		$url = '/admin.php?m=pages&u=form&id=' . $entity_id . '&tab=' . $ftab . '&i18n_lang_id=' . $lang_id;
	} elseif ($entity === 'authors') {
		$url = '/admin.php?m=authors&u=form&id=' . $entity_id . '&ftab=1&i18n_lang_id=' . $lang_id;
	} else {
		$content_tab = isset($ctx['content_tab']) ? (string)$ctx['content_tab'] : admin_i18n_content_tab_for_entity($entity);
		$url = '/admin.php?m=content&tab=' . urlencode($content_tab) . '&u=form&id=' . $entity_id . '&ftab=' . $ftab . '&i18n_lang_id=' . $lang_id;
		if (!empty($ctx['stab'])) {
			$url .= '&stab=' . urlencode((string)$ctx['stab']);
		}
	}
	if (!empty($flash['ok'])) {
		$url .= '&' . $ok_key . '=1';
	}
	if (!empty($flash['error'])) {
		$url .= '&' . $err_key . '=' . urlencode((string)$flash['error']);
	}
	return $url;
}

function admin_i18n_render_export_import_block($entity, $entity_id, $lang_id, $base_url) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if (!in_array($entity, admin_i18n_translatable_entities(), true) || $entity_id <= 0 || $lang_id <= 0) {
		return '';
	}
	$q = admin_i18n_parse_base_url_query($base_url);
	$ftab = isset($q['ftab']) ? (int)$q['ftab'] : (isset($q['tab']) ? (int)$q['tab'] : 0);
	$content_tab = admin_i18n_content_tab_for_entity($entity);
	$stab = isset($q['stab']) ? (string)$q['stab'] : '';
	$flash_ok = (!empty($_GET['import_i18n_ok']) && (string)$_GET['import_i18n_ok'] === '1')
		|| (!empty($_GET['import_page_i18n_ok']) && (string)$_GET['import_page_i18n_ok'] === '1');
	$flash_err = '';
	if (!empty($_GET['import_i18n_error'])) {
		$flash_err = (string)$_GET['import_i18n_error'];
	} elseif (!empty($_GET['import_page_i18n_error'])) {
		$flash_err = (string)$_GET['import_page_i18n_error'];
	}
	$export_url = '/admin.php?u=export_i18n_single&entity=' . urlencode($entity) . '&entity_id=' . $entity_id . '&i18n_lang_id=' . $lang_id;
	$import_action = '/admin.php?u=import_i18n_single';
	$h = '<div class="col-12 mt-3"><div class="card"><div class="card-body">';
	$h .= '<h6 class="mb-2">Export / Import for this language</h6>';
	if ($flash_ok) {
		$h .= '<div class="alert alert-success mb-3">This language version has been updated from the imported JSON.</div>';
	}
	if ($flash_err !== '') {
		$h .= '<div class="alert alert-danger mb-3">' . htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8') . '</div>';
	}
	$h .= '<p class="mb-2"><a href="' . htmlspecialchars($export_url) . '" class="btn btn-primary btn-sm">Download JSON (this language)</a></p>';
	$h .= '<div class="js-import-i18n form-inline flex-wrap align-items-end" data-action="' . htmlspecialchars($import_action) . '">';
	$h .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity) . '">';
	$h .= '<input type="hidden" name="id" value="' . $entity_id . '">';
	$h .= '<input type="hidden" name="i18n_lang_id" value="' . $lang_id . '">';
	$h .= '<input type="hidden" name="ftab" value="' . $ftab . '">';
	if ($content_tab !== '') {
		$h .= '<input type="hidden" name="redirect_content_tab" value="' . htmlspecialchars($content_tab) . '">';
	}
	if ($stab !== '') {
		$h .= '<input type="hidden" name="redirect_stab" value="' . htmlspecialchars($stab) . '">';
	}
	$h .= '<div class="form-group mr-2 mb-2"><input type="file" name="json_file" accept=".json,application/json" class="form-control-file form-control-sm" /></div>';
	$h .= '<div class="form-group mb-2"><button type="button" class="js-import-i18n-submit btn btn-secondary btn-sm">Import JSON into this language</button></div>';
	$h .= '</div></div></div></div>';
	return $h;
}

function admin_i18n_http_export_single($get) {
	$entity = isset($get['entity']) ? trim((string)$get['entity']) : '';
	if ($entity === '' && isset($get['u']) && (string)$get['u'] === 'export_page_single_i18n') {
		$entity = 'pages';
	}
	$entity_id = isset($get['entity_id']) ? (int)$get['entity_id'] : (isset($get['id']) ? (int)$get['id'] : 0);
	$lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	if (!in_array($entity, admin_i18n_translatable_entities(), true) || $entity_id <= 0 || $lang_id <= 0) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'Invalid entity, id, or language'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
	if (!admin_i18n_fetch_base_translation($entity, $entity_id)) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'Record not found'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
	$out = admin_i18n_export_single_payload($entity, $entity_id, $lang_id);
	$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($out['translation']['url'] ?: $entity));
	$filename = $entity . '-' . $entity_id . '-lang' . $lang_id . ($slug ? '-' . $slug : '') . '.json';
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

function admin_i18n_http_import_single($get) {
	$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	$import_error = '';
	$entity = isset($_POST['entity']) ? trim((string)$_POST['entity']) : '';
	if ($entity === '' && isset($get['u']) && (string)$get['u'] === 'import_page_single_i18n') {
		$entity = 'pages';
	}
	$entity_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
	$lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : 0;
	$ftab = isset($_POST['ftab']) ? (int)$_POST['ftab'] : (isset($_POST['tab']) ? (int)$_POST['tab'] : 0);
	$ctx = array(
		'ftab' => $ftab,
		'content_tab' => isset($_POST['redirect_content_tab']) ? trim((string)$_POST['redirect_content_tab']) : admin_i18n_content_tab_for_entity($entity),
		'stab' => isset($_POST['redirect_stab']) ? trim((string)$_POST['redirect_stab']) : '',
	);
	$file = isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK ? $_FILES['json_file'] : null;
	if (!in_array($entity, admin_i18n_translatable_entities(), true) || $entity_id <= 0 || $lang_id <= 0) {
		$import_error = 'Invalid record or language.';
	} elseif (!$file) {
		$import_error = 'Please select a JSON file.';
	} else {
		$raw = file_get_contents($file['tmp_name']);
		if ($raw === false) {
			$import_error = 'Could not read file.';
		} else {
			$data = @json_decode($raw, true);
			$norm = admin_i18n_normalize_import_json($entity, $entity_id, $lang_id, $data);
			if (empty($norm['ok'])) {
				$import_error = isset($norm['message']) ? (string)$norm['message'] : 'Invalid JSON.';
			} elseif (!admin_i18n_fetch_base_translation($entity, $entity_id)) {
				$import_error = 'Record not found.';
			} else {
				$res = admin_i18n_import_single_apply($entity, $entity_id, $lang_id, $norm['translation']);
				if (!empty($res['ok'])) {
					$form_url = admin_i18n_redirect_url_after_import($entity, $entity_id, $lang_id, $ctx, array('ok' => true));
					if ($is_ajax) {
						header('Content-Type: application/json; charset=utf-8');
						echo json_encode(array(
							'ok' => true,
							'message' => $res['message'],
							'form_url' => $form_url,
						), JSON_UNESCAPED_UNICODE);
						exit;
					}
					header('Location: ' . $form_url);
					exit;
				}
				$import_error = isset($res['message']) ? (string)$res['message'] : 'Import failed.';
			}
		}
	}
	if ($is_ajax) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('ok' => false, 'message' => $import_error), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$form_url = admin_i18n_redirect_url_after_import($entity, $entity_id, $lang_id, $ctx, array('error' => $import_error));
	header('Location: ' . $form_url);
	exit;
}

/**
 * Early admin.php handler for per-language JSON export/import (all translatable entities).
 *
 * @param array $get
 * @return bool true if request was handled and script should exit
 */
function admin_i18n_dispatch_http($get) {
	if (!is_array($get)) {
		return false;
	}
	$u = isset($get['u']) ? trim((string)$get['u']) : '';
	if ($u === 'export_i18n_single' || $u === 'export_page_single_i18n') {
		admin_i18n_http_export_single($get);
		return true;
	}
	if (($u === 'import_i18n_single' || $u === 'import_page_single_i18n') && $_SERVER['REQUEST_METHOD'] === 'POST') {
		admin_i18n_http_import_single($get);
		return true;
	}
	return false;
}

/**
 * Deep link to the entity edit form with Translations tab + language (for Translations → Review "Edit").
 * Tab indexes must match pages.php / guides.php / games.php / casino_articles.php / blog.php.
 *
 * @param string $entity content_i18n.entity
 * @param int    $entity_id
 * @param int    $lang_id  target language row being edited
 * @return string path+query starting with /admin.php or empty if unsupported
 */
function admin_i18n_review_edit_url($entity, $entity_id, $lang_id) {
	$entity = trim((string)$entity);
	$entity_id = (int)$entity_id;
	$lang_id = (int)$lang_id;
	if ($entity === '' || $entity_id <= 0 || $lang_id <= 0) {
		return '';
	}
	switch ($entity) {
		case 'pages':
			return '/admin.php?m=pages&u=form&id=' . $entity_id . '&tab=4&i18n_lang_id=' . $lang_id;
		case 'guides':
			return '/admin.php?m=content&tab=guides&u=form&id=' . $entity_id . '&ftab=3&i18n_lang_id=' . $lang_id;
		case 'games':
			return '/admin.php?m=content&tab=games&u=form&id=' . $entity_id . '&ftab=3&i18n_lang_id=' . $lang_id;
		case 'casino_articles':
			return '/admin.php?m=content&tab=casinos&u=form&id=' . $entity_id . '&ftab=4&i18n_lang_id=' . $lang_id;
		case 'blog':
			return '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . $entity_id . '&ftab=5&i18n_lang_id=' . $lang_id;
		case 'authors':
			return '/admin.php?m=authors&u=form&id=' . $entity_id . '&ftab=1&i18n_lang_id=' . $lang_id;
		default:
			return '';
	}
}

/**
 * Load common dictionary for a language (files/languages/{id}/dictionary/common.php) without touching global $lang.
 * @param int $lang_id
 * @return array
 */
function admin_load_common_dict($lang_id) {
	$lang_id = (int)$lang_id;
	if ($lang_id <= 0) return array();
	$path = ROOT_DIR . 'files/languages/' . $lang_id . '/dictionary/common.php';
	if (!is_file($path)) return array();
	$lang = array();
	include $path;
	return isset($lang['common']) && is_array($lang['common']) ? $lang['common'] : array();
}

/**
 * Save common dictionary for a language (overwrites files/languages/{id}/dictionary/common.php).
 * @param int $lang_id
 * @param array $dict
 * @return array ['ok' => bool, 'message' => string]
 */
function admin_save_common_dict($lang_id, $dict) {
	$lang_id = (int)$lang_id;
	if ($lang_id <= 0 || !is_array($dict)) return array('ok' => false, 'message' => 'Bad params');
	$dir = ROOT_DIR . 'files/languages/' . $lang_id . '/dictionary';
	if (!is_dir($dir)) {
		if (!@mkdir($dir, 0755, true)) return array('ok' => false, 'message' => 'Cannot create directory');
	}
	$out = "<?php\n\$lang['common'] = array(\n";
	foreach ($dict as $k => $v) {
		$out .= "\t" . var_export((string)$k, true) . ' => ' . var_export((string)$v, true) . ",\n";
	}
	$out .= ");?>";
	$path = $dir . '/common.php';
	if (@file_put_contents($path, $out) === false) return array('ok' => false, 'message' => 'Cannot write file');
	return array('ok' => true, 'message' => 'Dictionary saved');
}

/**
 * Resolve source (canonical) language id from variables.translation_settings.
 * @return int
 */
function admin_i18n_source_lang_id() {
	$source = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') {
			$dec = json_decode($row['value'], true);
			if (is_array($dec) && !empty($dec['source_lang_id'])) {
				$source = (int)$dec['source_lang_id'];
			}
		}
	}
	return $source > 0 ? $source : 1;
}

/**
 * Keep only keys present in the source language common.php; drop legacy keys on the target file.
 *
 * @param int $target_lang_id
 * @param int|null $source_lang_id null = use admin_i18n_source_lang_id()
 * @param bool $dry_run if true, do not write file (CLI / preview)
 * @return array ['ok'=>bool,'message'=>string,'removed'=>int,'kept'=>int,'removed_keys'=>string[]]
 */
function admin_prune_common_dict_to_canonical($target_lang_id, $source_lang_id = null, $dry_run = false) {
	$target_lang_id = (int)$target_lang_id;
	if ($target_lang_id <= 0) return array('ok' => false, 'message' => 'Bad target language id');

	if ($source_lang_id === null) {
		$source_lang_id = admin_i18n_source_lang_id();
	} else {
		$source_lang_id = (int)$source_lang_id;
		if ($source_lang_id <= 0) $source_lang_id = admin_i18n_source_lang_id();
	}

	if ($target_lang_id === $source_lang_id) {
		return array(
			'ok' => false,
			'message' => 'Cannot prune the canonical (source) language',
			'removed' => 0,
			'kept' => 0,
			'removed_keys' => array(),
		);
	}

	$canonical = admin_load_common_dict($source_lang_id);
	if (empty($canonical)) {
		return array(
			'ok' => false,
			'message' => 'Source common dictionary is empty or missing (files/languages/' . $source_lang_id . '/dictionary/common.php)',
			'removed' => 0,
			'kept' => 0,
			'removed_keys' => array(),
		);
	}

	$target = admin_load_common_dict($target_lang_id);
	$removed_keys = array();
	foreach ($target as $k => $_) {
		if (!array_key_exists($k, $canonical)) {
			$removed_keys[] = (string)$k;
		}
	}

	$new = array();
	foreach (array_keys($canonical) as $k) {
		$new[$k] = array_key_exists($k, $target) ? (string)$target[$k] : '';
	}

	if ($dry_run) {
		return array(
			'ok' => true,
			'message' => 'Dry run: would remove ' . count($removed_keys) . ' key(s); ' . count($new) . ' would remain.',
			'removed' => count($removed_keys),
			'kept' => count($new),
			'removed_keys' => $removed_keys,
		);
	}

	if (function_exists('system_log_add')) {
		system_log_add('translations', 'info', 'prune_common_dict save', array(
			'target_lang_id' => $target_lang_id,
			'source_lang_id' => $source_lang_id,
			'removed_count' => count($removed_keys),
			'kept_count' => count($new),
		));
	}

	$res = admin_save_common_dict($target_lang_id, $new);
	if (empty($res['ok'])) {
		return array(
			'ok' => false,
			'message' => isset($res['message']) ? (string)$res['message'] : 'Save failed',
			'removed' => count($removed_keys),
			'kept' => count($new),
			'removed_keys' => $removed_keys,
		);
	}

	return array(
		'ok' => true,
		'message' => 'Pruned ' . count($removed_keys) . ' key(s); ' . count($new) . ' key(s) now match canonical.',
		'removed' => count($removed_keys),
		'kept' => count($new),
		'removed_keys' => $removed_keys,
	);
}

