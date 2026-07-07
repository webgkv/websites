<?php
/**
 * SEO monitor helpers: cluster JSON export/import, HTML / meta checks.
 */

if (!function_exists('seo_monitor_entity_map')) {

	function seo_monitor_entity_map() {
		return array(
			'pages' => array('table' => 'pages', 'label' => 'Pages', 'src_content_col' => 'text'),
			// guides/games/casino_articles store body in `text` (see admin/modules/*.php CREATE TABLE), not `content`.
			'guides' => array('table' => 'guides', 'label' => 'Guides', 'src_content_col' => 'text'),
			'games' => array('table' => 'games', 'label' => 'Games', 'src_content_col' => 'text'),
			'casino_articles' => array('table' => 'casino_articles', 'label' => 'Casino articles', 'src_content_col' => 'text'),
			'blog' => array('table' => 'blog', 'label' => 'Blog', 'src_content_col' => 'text'),
			// E-E-A-T author cards: name / job_title / bio only — no URL slug, meta description, H1, or image audits.
			'authors' => array(
				'table' => 'site_authors',
				'label' => 'Authors',
				'src_content_col' => 'bio',
				'profile_only' => true,
			),
		);
	}

	/**
	 * Author profile clusters: translate card fields only (content_i18n name/title/content ↔ site_authors).
	 */
	function seo_monitor_entity_profile_only($entity) {
		$entity = trim((string)$entity);
		$map = seo_monitor_entity_map();
		return isset($map[$entity]) && !empty($map[$entity]['profile_only']);
	}

	/**
	 * SELECT list fragment for main-row fetch (maps site_authors columns into SEO monitor shape).
	 *
	 * @param array<string,mixed> $info seo_monitor_entity_map() entry
	 * @return string
	 */
	function seo_monitor_main_row_select_sql(array $info) {
		if (!empty($info['profile_only'])) {
			return "id, '' AS url, name, job_title AS title, '' AS description, bio AS body_html";
		}
		$col = isset($info['src_content_col']) ? (string)$info['src_content_col'] : 'content';
		if (!in_array($col, array('text', 'content', 'bio'), true)) {
			$col = 'content';
		}
		return "id, url, name, title, description, `" . mysql_res($col) . "` AS body_html";
	}

	/**
	 * Canonical SEO Monitor entity key: trim + case-insensitive match to seo_monitor_entity_map().
	 * Unknown strings are returned trimmed (unchanged casing) so callers can surface clear errors.
	 *
	 * @param mixed $raw
	 * @return string
	 */
	function seo_monitor_entity_key_canonical($raw) {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return '';
		}
		$map = seo_monitor_entity_map();
		if (isset($map[$raw])) {
			return $raw;
		}
		foreach (array_keys($map) as $k) {
			if (strcasecmp($k, $raw) === 0) {
				return $k;
			}
		}
		return $raw;
	}

	function seo_monitor_hub_settings_variable_key() {
		return 'seo_monitor_hub_settings';
	}

	/**
	 * Listing/hub CMS pages: HTML body lives in modules (cards), not in pages.text — skip body_empty in audits.
	 *
	 * @return array{page_slugs:array<int,string>,blog_listing_module:bool,page_ids_extra:array<int,int>}
	 */
	function seo_monitor_hub_settings_load($force_reload = false) {
		static $cached = null;
		static $loaded = false;
		if ($force_reload) {
			$loaded = false;
			$cached = null;
		}
		if ($loaded) {
			return $cached;
		}
		$defaults = array(
			'page_slugs' => array('casinos', 'games', 'guides'),
			'blog_listing_module' => true,
			'page_ids_extra' => array(),
		);
		$cached = $defaults;
		$loaded = true;
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return $cached;
		}
		$key = seo_monitor_hub_settings_variable_key();
		$row = mysql_select("SELECT value FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
		if (!$row || (string)$row['value'] === '') {
			return $cached;
		}
		$dec = @json_decode((string)$row['value'], true);
		if (!is_array($dec)) {
			return $cached;
		}
		if (isset($dec['page_slugs']) && is_array($dec['page_slugs'])) {
			$cached['page_slugs'] = array_values(array_filter(array_map(function ($s) {
				return strtolower(trim(preg_replace('#[^a-z0-9_-]+#i', '', (string)$s), '/'));
			}, $dec['page_slugs'])));
		}
		if (isset($dec['blog_listing_module'])) {
			$cached['blog_listing_module'] = !empty($dec['blog_listing_module']);
		}
		if (isset($dec['page_ids_extra']) && is_array($dec['page_ids_extra'])) {
			$cached['page_ids_extra'] = array_values(array_filter(array_map('intval', $dec['page_ids_extra']), function ($x) {
				return $x > 0;
			}));
		}
		return $cached;
	}

	/**
	 * Persist hub rules (Admin SEO Monitor form).
	 *
	 * @param array<string,mixed> $data page_slugs array, blog_listing_module bool, page_ids_extra int[]
	 * @return bool
	 */
	function seo_monitor_hub_settings_save(array $data) {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return false;
		}
		$cur = array(
			'page_slugs' => array('casinos', 'games', 'guides'),
			'blog_listing_module' => true,
			'page_ids_extra' => array(),
		);
		if (isset($data['page_slugs']) && is_array($data['page_slugs'])) {
			$cur['page_slugs'] = array_values(array_filter(array_map(function ($s) {
				return strtolower(trim(preg_replace('#[^a-z0-9_-]+#i', '', (string)$s), '/'));
			}, $data['page_slugs'])));
		}
		if (array_key_exists('blog_listing_module', $data)) {
			$cur['blog_listing_module'] = !empty($data['blog_listing_module']);
		}
		if (isset($data['page_ids_extra']) && is_array($data['page_ids_extra'])) {
			$cur['page_ids_extra'] = array_values(array_filter(array_map('intval', $data['page_ids_extra']), function ($x) {
				return $x > 0;
			}));
		}
		$key = seo_monitor_hub_settings_variable_key();
		$json = json_encode($cur, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$row = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
		$ok = false;
		if ($row && isset($row['id'])) {
			$ok = mysql_fn('update', 'variables', array('value' => $json), ' AND id=' . (int)$row['id'] . ' ') !== false;
		} else {
			$ok = mysql_fn('insert', 'variables', array('key' => $key, 'value' => $json)) !== false;
		}
		if ($ok) {
			seo_monitor_hub_settings_load(true);
		}
		return $ok;
	}

	/**
	 * @param array<string,mixed> $row pages table row (module, url, id)
	 */
	function seo_monitor_is_hub_pages_row(array $row) {
		$cfg = seo_monitor_hub_settings_load();
		$pid = isset($row['id']) ? (int)$row['id'] : 0;
		if ($pid > 0 && !empty($cfg['page_ids_extra']) && in_array($pid, $cfg['page_ids_extra'], true)) {
			return true;
		}
		$mod = strtolower(trim((string)($row['module'] ?? '')));
		$url = strtolower(trim((string)($row['url'] ?? ''), '/'));
		if (!empty($cfg['blog_listing_module']) && $mod === 'blog') {
			return true;
		}
		// Section rows may use module=casinos|games|guides (see bd_pages_upgrade / admin) while runtime switches layout in index.php.
		if (in_array($mod, array('casinos', 'games', 'guides'), true)) {
			return true;
		}
		if ($mod === 'pages' && $url !== '' && in_array($url, $cfg['page_slugs'], true)) {
			return true;
		}
		return false;
	}

	function seo_monitor_is_hub_page_entity($entity, $entity_id) {
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		if ($entity !== 'pages' || $entity_id <= 0) {
			return false;
		}
		$row = mysql_select('SELECT id, module, url FROM pages WHERE id=' . $entity_id . ' LIMIT 1', 'row');
		return is_array($row) && seo_monitor_is_hub_pages_row($row);
	}

	function seo_monitor_body_empty_exempt(array $loc) {
		$ctx = isset($loc['seo_monitor_ctx']) && is_array($loc['seo_monitor_ctx']) ? $loc['seo_monitor_ctx'] : array();
		$ent = isset($ctx['entity']) ? trim((string)$ctx['entity']) : '';
		$eid = isset($ctx['entity_id']) ? (int)$ctx['entity_id'] : 0;
		return $ent === 'pages' && seo_monitor_is_hub_page_entity('pages', $eid);
	}

	/**
	 * Apply title/description/name/content/url for one locale (source → main table; targets → content_i18n).
	 * Used by telemetry_control action seo_page_meta_patch (requires control API enabled).
	 *
	 * @param array<string,string|int> $fields
	 * @return array{ok:bool,message?:string,details?:array}
	 */
	function seo_monitor_apply_meta_patch($entity, $entity_id, $lang_id, array $fields, $dry_run = false) {
		if (!function_exists('admin_i18n_save')) {
			require_once __DIR__ . '/../admin/modules/_i18n.php';
		}
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		$lang_id = (int)$lang_id;
		$dry_run = !empty($dry_run);
		$map = seo_monitor_entity_map();
		if (!isset($map[$entity]) || $entity_id <= 0 || $lang_id <= 0) {
			return array('ok' => false, 'message' => 'Bad entity, entity_id, or lang_id');
		}
		$allowed_keys = array('title', 'description', 'name', 'content', 'url');
		$patch = array();
		foreach ($allowed_keys as $k) {
			if (!array_key_exists($k, $fields)) {
				continue;
			}
			$patch[$k] = is_string($fields[$k]) ? $fields[$k] : (string)$fields[$k];
		}
		if (empty($patch)) {
			return array('ok' => false, 'message' => 'No allowed fields in fields map (title, description, name, content, url)');
		}
		if (isset($patch['url'])) {
			$patch['url'] = trim(preg_replace('#[^a-z0-9/_-]+#i', '', (string)$patch['url']), '/');
		}
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$allowed_lang = seo_monitor_lang_id_allowed_map($source_lang_id);
		if (empty($allowed_lang[$lang_id])) {
			return array('ok' => false, 'message' => 'lang_id not in SEO cluster languages');
		}
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return array('ok' => false, 'message' => 'Record not found');
		}
		if ($dry_run) {
			return array('ok' => true, 'message' => 'Dry run: would apply keys ' . implode(', ', array_keys($patch)), 'details' => array('patch_keys' => array_keys($patch), 'lang_id' => $lang_id));
		}
		if ($lang_id === $source_lang_id) {
			$main_fields = array();
			foreach (array('name', 'title', 'description', 'content', 'url') as $k) {
				if (array_key_exists($k, $patch)) {
					$main_fields[$k] = $patch[$k];
				}
			}
			$r = seo_monitor_save_main_row($entity, $entity_id, $main_fields, $info);
			if (empty($r['ok'])) {
				return $r;
			}
			if ($entity === 'blog' && array_key_exists('description', $main_fields)) {
				$d = (string)$main_fields['description'];
				if (@mysql_select("SHOW COLUMNS FROM blog LIKE 'name_2'", 'num_rows') > 0) {
					mysql_fn('update', 'blog', array('name_2' => $d), ' AND id=' . $entity_id . ' LIMIT 1');
				}
			}
			return array('ok' => true, 'message' => 'Source row updated', 'details' => array('updated' => 'main', 'keys' => array_keys($main_fields)));
		}
		$i18n_data = array();
		foreach (array('name', 'title', 'description', 'content', 'url', 'status') as $k) {
			if ($k === 'status') {
				continue;
			}
			if (array_key_exists($k, $patch)) {
				$i18n_data[$k] = $patch[$k];
			}
		}
		if (isset($patch['status']) && in_array((string)$patch['status'], array('missing', 'draft', 'review', 'published'), true)) {
			$i18n_data['status'] = (string)$patch['status'];
		}
		if (empty($i18n_data)) {
			return array('ok' => false, 'message' => 'Nothing to update for i18n row');
		}
		$sv = admin_i18n_save($entity, $entity_id, $lang_id, $i18n_data);
		if (empty($sv['ok'])) {
			return array('ok' => false, 'message' => isset($sv['message']) ? (string)$sv['message'] : 'admin_i18n_save failed');
		}
		if (function_exists('admin_i18n_sync_canonical_row_to_base_table')) {
			admin_i18n_sync_canonical_row_to_base_table($entity, $entity_id, $lang_id);
		}
		return array('ok' => true, 'message' => 'content_i18n updated', 'details' => array('updated' => 'content_i18n', 'lang_id' => $lang_id, 'keys' => array_keys($i18n_data)));
	}

	function seo_monitor_schema_version() {
		return 'seo_cluster_v1';
	}

	function seo_monitor_display_where($table) {
		$cols = mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "`", 'rows');
		$has_display = false;
		if ($cols) {
			foreach ($cols as $c) {
				if (isset($c['Field']) && (string)$c['Field'] === 'display') {
					$has_display = true;
					break;
				}
			}
		}
		return $has_display ? ' AND display=1 ' : '';
	}

	function seo_monitor_validation_exclusions_variable_key() {
		return 'seo_monitor_validation_exclusions';
	}

	/**
	 * @return array<string, array<int, int>> entity => sorted unique ids
	 */
	function seo_monitor_exclusions_read_all() {
		$defaults = array();
		foreach (array_keys(seo_monitor_entity_map()) as $k) {
			$defaults[$k] = array();
		}
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return $defaults;
		}
		$key = seo_monitor_validation_exclusions_variable_key();
		$row = mysql_select("SELECT value FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
		if (!$row || $row['value'] === '') {
			return $defaults;
		}
		$dec = json_decode((string)$row['value'], true);
		if (!is_array($dec)) {
			return $defaults;
		}
		$out = $defaults;
		foreach ($dec as $k => $v) {
			if (!array_key_exists($k, $out) || !is_array($v)) {
				continue;
			}
			$uniq = array();
			foreach ($v as $id) {
				$i = (int)$id;
				if ($i > 0) {
					$uniq[$i] = true;
				}
			}
			$out[$k] = array_keys($uniq);
			sort($out[$k], SORT_NUMERIC);
		}
		return $out;
	}

	function seo_monitor_exclusions_write_all(array $all) {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return false;
		}
		$clean = array();
		foreach (array_keys(seo_monitor_entity_map()) as $k) {
			$clean[$k] = array();
			if (isset($all[$k]) && is_array($all[$k])) {
				foreach ($all[$k] as $id) {
					$i = (int)$id;
					if ($i > 0) {
						$clean[$k][$i] = true;
					}
				}
			}
			$clean[$k] = array_keys($clean[$k]);
			sort($clean[$k], SORT_NUMERIC);
		}
		$json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$key = seo_monitor_validation_exclusions_variable_key();
		$row = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res($key) . "' LIMIT 1", 'row');
		if ($row && isset($row['id'])) {
			return mysql_fn('update', 'variables', array('value' => $json), " AND id=" . (int)$row['id'] . " ") !== false;
		}
		return mysql_fn('insert', 'variables', array(
			'key' => $key,
			'value' => $json,
		)) !== false;
	}

	/**
	 * @return array<int, int> sorted ids excluded from SEO list/score validation
	 */
	function seo_monitor_exclusions_for_entity($entity) {
		$all = seo_monitor_exclusions_read_all();
		$entity = trim((string)$entity);
		return isset($all[$entity]) ? $all[$entity] : array();
	}

	function seo_monitor_row_is_excluded_from_validation($entity, $id) {
		return in_array((int)$id, seo_monitor_exclusions_for_entity($entity), true);
	}

	/**
	 * Re-scan one list row against current DB (cluster locales) — same rules as the list view.
	 *
	 * @return array{ok:bool,message?:string,excluded?:bool,issue_count?:int,issue_codes?:array<int,string>,issue_labels?:array<int,string>,all_ok?:bool}
	 */
	function seo_monitor_list_row_issue_scan($entity, $entity_id) {
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		if ($entity_id <= 0 || !isset(seo_monitor_entity_map()[$entity])) {
			return array('ok' => false, 'message' => 'Bad entity or id');
		}
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
			return array('ok' => false, 'message' => 'content_i18n not found');
		}
		if (seo_monitor_row_is_excluded_from_validation($entity, $entity_id)) {
			return array(
				'ok' => true,
				'excluded' => true,
				'issue_count' => 0,
				'issue_codes' => array(),
				'issue_labels' => array(),
				'all_ok' => false,
			);
		}
		$map = seo_monitor_entity_map();
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return array('ok' => false, 'message' => 'Record not found');
		}
		if (seo_monitor_normalize_canonical_main_meta($entity, $entity_id)) {
			$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
			if (!$main) {
				return array('ok' => false, 'message' => 'Record not found');
			}
		}
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$cluster_langs = seo_monitor_cluster_languages($source_lang_id);
		$cluster_lang_ids = array_map(function ($l) {
			return (int)$l['id'];
		}, $cluster_langs);
		$batch = seo_monitor_batch_i18n_rows($entity, array($entity_id), $cluster_lang_ids);
		$i18n_by = isset($batch[$entity_id]) ? $batch[$entity_id] : array();
		$worst = 0;
		$labels = array();
		foreach ($cluster_langs as $lm) {
			$lid = (int)$lm['id'];
			$i18n = isset($i18n_by[$lid]) ? $i18n_by[$lid] : null;
			$loc = seo_monitor_locale_payload($entity, $entity_id, $lm, $main, $i18n, $source_lang_id, false);
			foreach (seo_monitor_analyze_locale($loc) as $is) {
				$labels[$is['code']] = true;
				$worst++;
			}
		}
		$issue_codes = array_keys($labels);
		$issue_labels = array();
		foreach ($issue_codes as $ic) {
			$issue_labels[] = seo_monitor_issue_label($ic);
		}
		return array(
			'ok' => true,
			'excluded' => false,
			'issue_count' => $worst,
			'issue_codes' => $issue_codes,
			'issue_labels' => $issue_labels,
			'all_ok' => ($worst === 0),
		);
	}

	/**
	 * @param 'include'|'exclude' $action
	 * @param array<int, int> $ids
	 */
	function seo_monitor_exclusions_apply_bulk($entity, $action, array $ids) {
		$entity = trim((string)$entity);
		if (!isset(seo_monitor_entity_map()[$entity])) {
			return false;
		}
		if (!in_array($action, array('include', 'exclude'), true)) {
			return false;
		}
		$all = seo_monitor_exclusions_read_all();
		$set = array();
		foreach ($all[$entity] as $x) {
			$set[(int)$x] = true;
		}
		if ($action === 'exclude') {
			foreach ($ids as $id) {
				$i = (int)$id;
				if ($i > 0) {
					$set[$i] = true;
				}
			}
		} else {
			foreach ($ids as $id) {
				unset($set[(int)$id]);
			}
		}
		$all[$entity] = array_keys($set);
		sort($all[$entity], SORT_NUMERIC);
		return seo_monitor_exclusions_write_all($all);
	}

	/**
	 * SQL fragment: exclude rows omitted from validation (empty string if none).
	 */
	function seo_monitor_sql_not_in_excluded_ids($entity) {
		$ids = seo_monitor_exclusions_for_entity($entity);
		if ($ids === array()) {
			return '';
		}
		$ids = array_values(array_filter(array_map('intval', $ids), function ($x) {
			return $x > 0;
		}));
		if ($ids === array()) {
			return '';
		}
		return ' AND id NOT IN (' . implode(',', $ids) . ') ';
	}

	/**
	 * Published main rows that count toward SEO validation / overview score.
	 */
	function seo_monitor_validation_included_row_count($entity) {
		$map = seo_monitor_entity_map();
		$entity = trim((string)$entity);
		if (!isset($map[$entity])) {
			return 0;
		}
		$table = $map[$entity]['table'];
		if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
			return 0;
		}
		$where = seo_monitor_display_where($table);
		$x = seo_monitor_sql_not_in_excluded_ids($entity);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where . $x, 'row');
		return $r && isset($r['c']) ? (int)$r['c'] : 0;
	}

	function seo_monitor_translation_settings() {
		$cfg = array('source_lang_id' => 1, 'enabled_lang_ids' => array());
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
			$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
			if ($row && $row['value'] !== '') {
				$dec = json_decode($row['value'], true);
				if (is_array($dec)) {
					$cfg = array_merge($cfg, $dec);
				}
			}
		}
		return $cfg;
	}

	/**
	 * Languages included in a cluster workflow: enabled UI langs, source first.
	 *
	 * @return array<int, array<string, string>>
	 */
	function seo_monitor_cluster_languages($source_lang_id) {
		$source_lang_id = (int)$source_lang_id;
		$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
		$cfg = seo_monitor_translation_settings();
		$enabled_set = array();
		foreach ((array)@$cfg['enabled_lang_ids'] as $lid) {
			$lid = (int)$lid;
			if ($lid > 0) {
				$enabled_set[$lid] = true;
			}
		}
		$pick = array();
		if (empty($enabled_set)) {
			$pick = $langs;
		} else {
			foreach ($langs as $l) {
				if (isset($enabled_set[(int)$l['id']])) {
					$pick[] = $l;
				}
			}
			foreach (array_keys($enabled_set) as $wid) {
				$wid = (int)$wid;
				if ($wid <= 0) {
					continue;
				}
				$found = false;
				foreach ($pick as $pl) {
					if ((int)$pl['id'] === $wid) {
						$found = true;
						break;
					}
				}
				if ($found) {
					continue;
				}
				$row = mysql_select("SELECT id, url, name FROM languages WHERE id=" . $wid . " LIMIT 1", 'row');
				if ($row) {
					$pick[] = $row;
				}
			}
		}
		$by_id = array();
		foreach ($pick as $l) {
			$by_id[(int)$l['id']] = $l;
		}
		if (!isset($by_id[$source_lang_id])) {
			$row = mysql_select("SELECT id, url, name FROM languages WHERE id=" . $source_lang_id . " LIMIT 1", 'row');
			if ($row) {
				$by_id[$source_lang_id] = $row;
			}
		}
		$ordered = array();
		if (isset($by_id[$source_lang_id])) {
			$ordered[] = $by_id[$source_lang_id];
			unset($by_id[$source_lang_id]);
		}
		ksort($by_id);
		foreach ($by_id as $l) {
			$ordered[] = $l;
		}
		return $ordered;
	}

	function seo_monitor_lang_id_allowed_map($source_lang_id) {
		$map = array();
		foreach (seo_monitor_cluster_languages($source_lang_id) as $l) {
			$map[(int)$l['id']] = true;
		}
		return $map;
	}

	function seo_monitor_fetch_main_row($entity, $entity_id, array $info) {
		$table = $info['table'];
		$entity_id = (int)$entity_id;
		if ($entity_id <= 0) {
			return null;
		}
		$sql = "
			SELECT " . seo_monitor_main_row_select_sql($info) . "
			FROM `" . mysql_res($table) . "`
			WHERE id=" . $entity_id . "
			LIMIT 1
		";
		return mysql_select($sql, 'row');
	}

	/**
	 * Persist SEO limits on the canonical (main) table row: display title ≤70 UTF-8 chars, meta description ≤160.
	 * Mirrors trimmed fields into content_i18n for the source language when that row exists.
	 * Safe to call repeatedly; writes only when lengths exceed limits.
	 *
	 * @return bool true if any column was updated
	 */
	function seo_monitor_normalize_canonical_main_meta($entity, $entity_id) {
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		$map = seo_monitor_entity_map();
		if ($entity_id <= 0 || !isset($map[$entity])) {
			return false;
		}
		if (!function_exists('translation_cluster_trim_seo_text')) {
			require_once __DIR__ . '/translation_cluster.php';
		}
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return false;
		}
		$name = (string)($main['name'] ?? '');
		$title = (string)($main['title'] ?? '');
		$desc = (string)($main['description'] ?? '');
		$t_strip = trim(strip_tags($title));
		$d_strip = trim(strip_tags($desc));

		$up = array();
		if ($t_strip !== '') {
			if (seo_monitor_strlen_utf8($t_strip) > 70) {
				$nt = translation_cluster_trim_seo_text($title, 70);
				if ($nt !== $title) {
					$up['title'] = $nt;
				}
			}
		} else {
			$n_strip = trim(strip_tags($name));
			if ($n_strip !== '' && seo_monitor_strlen_utf8($n_strip) > 70) {
				$nn = translation_cluster_trim_seo_text($name, 70);
				if ($nn !== $name) {
					$up['name'] = $nn;
				}
			}
		}
		if ($d_strip !== '' && seo_monitor_strlen_utf8($d_strip) > 160) {
			$nd = translation_cluster_trim_seo_text($desc, 160);
			if ($nd !== $desc) {
				$up['description'] = $nd;
			}
		}
		if ($entity === 'blog' && isset($up['description'])) {
			$up['name_2'] = $up['description'];
		}
		if (empty($up)) {
			return false;
		}
		$table = $info['table'];
		if (!function_exists('mysql_fn')) {
			require_once __DIR__ . '/mysql_func.php';
		}
		$ok = mysql_fn('update', $table, $up, ' AND id=' . $entity_id . ' LIMIT 1') !== false;
		if (!$ok) {
			return false;
		}
		$cfg = seo_monitor_translation_settings();
		$src = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		if ($src > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$ci = mysql_select("
				SELECT id FROM content_i18n
				WHERE entity='" . mysql_res($entity) . "'
				  AND entity_id=" . $entity_id . "
				  AND lang_id=" . $src . "
				ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
				LIMIT 1
			", 'row');
			if ($ci && isset($ci['id'])) {
				$ciu = array();
				if (array_key_exists('name', $up)) {
					$ciu['name'] = $up['name'];
				}
				if (array_key_exists('title', $up)) {
					$ciu['title'] = $up['title'];
				}
				if (array_key_exists('description', $up)) {
					$ciu['description'] = $up['description'];
				}
				if (!empty($ciu)) {
					mysql_fn('update', 'content_i18n', $ciu, ' AND id=' . (int)$ci['id'] . ' ');
				}
			}
		}
		if (function_exists('system_log_add')) {
			system_log_add('translations', 'info', 'seo_monitor_normalize_canonical_main_meta', array(
				'entity' => $entity,
				'entity_id' => $entity_id,
				'fields' => array_keys($up),
			));
		}
		return true;
	}

	function seo_monitor_i18n_status_ok($status) {
		$s = (string)$status;
		return in_array($s, array('draft', 'review', 'published'), true);
	}

	/**
	 * @param bool $strip_content When true, omit HTML body (light export).
	 * @return array<string, mixed>
	 */
	function seo_monitor_locale_payload($entity, $entity_id, array $lang_meta, $main, $i18n, $source_lang_id, $strip_content) {
		$lang_id = (int)$lang_meta['id'];
		$lang_url = isset($lang_meta['url']) ? trim((string)$lang_meta['url'], '/') : '';
		$source_lang_id = (int)$source_lang_id;
		$out = array(
			'lang_id' => $lang_id,
			'lang_url' => $lang_url,
			'url' => '',
			'name' => '',
			'title' => '',
			'description' => '',
			'content' => '',
			'status' => 'missing',
			'source' => 'empty',
		);
		$page_module = isset($main['module']) ? trim((string)$main['module']) : '';
		if (!$main) {
			$out['seo_monitor_ctx'] = array('entity' => $entity, 'entity_id' => (int)$entity_id, 'module' => $page_module);
			return $out;
		}
		$main_url = isset($main['url']) ? trim((string)$main['url'], '/') : '';
		if ($lang_id === $source_lang_id) {
			$out['url'] = $main_url;
			$out['name'] = (string)($main['name'] ?? '');
			$out['title'] = (string)($main['title'] ?? '');
			$out['description'] = (string)($main['description'] ?? '');
			$body = $strip_content ? '' : (string)($main['body_html'] ?? '');
			// Source copy sometimes lives only in content_i18n while main `text` is empty — use i18n HTML for audits/export.
			if (!$strip_content && trim($body) === '' && $i18n && seo_monitor_i18n_status_ok($i18n['status'] ?? '')) {
				$ib = (string)($i18n['content'] ?? '');
				if ($ib !== '') {
					$body = $ib;
				}
			}
			$out['content'] = $body;
			$out['status'] = 'published';
			$out['source'] = 'main';
			$out['seo_monitor_ctx'] = array('entity' => $entity, 'entity_id' => (int)$entity_id, 'module' => $page_module);
			return $out;
		}
		if ($i18n && seo_monitor_i18n_status_ok($i18n['status'] ?? '')) {
			$iu = trim((string)($i18n['url'] ?? ''));
			$out['url'] = $iu !== '' ? trim($iu, '/') : $main_url;
			$out['name'] = (string)($i18n['name'] ?? '');
			$out['title'] = (string)($i18n['title'] ?? '');
			$out['description'] = (string)($i18n['description'] ?? '');
			$out['content'] = $strip_content ? '' : (string)($i18n['content'] ?? '');
			$out['status'] = (string)($i18n['status'] ?? 'draft');
			$out['source'] = 'content_i18n';
		} else {
			$out['url'] = $main_url;
			$out['source'] = 'missing';
		}
		$out['seo_monitor_ctx'] = array('entity' => $entity, 'entity_id' => (int)$entity_id, 'module' => $page_module);
		return $out;
	}

	/**
	 * @return array{h1_count:int,img_missing_alt:int}
	 */
	function seo_monitor_dom_analyze_html($html) {
		$html = (string)$html;
		$h1_count = 0;
		$img_missing_alt = 0;
		if ($html === '') {
			return array('h1_count' => 0, 'img_missing_alt' => 0);
		}
		if (!class_exists('DOMDocument')) {
			$h1_count = (int)preg_match_all('/<h1\\b[^>]*>/iu', $html);
			if (preg_match_all('/<img\\b[^>]*>/iu', $html, $tags)) {
				foreach ($tags[0] as $tag) {
					if (preg_match('~\salt\s*=\s*(["\'])(.*?)\1~ius', $tag, $am)) {
						if (trim((string)($am[2] ?? '')) === '') {
							$img_missing_alt++;
						}
					} else {
						$img_missing_alt++;
					}
				}
			}
			return array('h1_count' => $h1_count, 'img_missing_alt' => $img_missing_alt);
		}
		$prev = libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$wrapped = '<?xml encoding="UTF-8"?><div id="seo-monitor-root">' . $html . '</div>';
		@$dom->loadHTML($wrapped, LIBXML_HTML_NODEFDTD | LIBXML_COMPACT);
		libxml_clear_errors();
		libxml_use_internal_errors($prev);
		$xp = new DOMXPath($dom);
		$h1_count = (int)$xp->evaluate('count(//h1)');
		$imgs = $dom->getElementsByTagName('img');
		for ($i = 0; $i < $imgs->length; $i++) {
			$im = $imgs->item($i);
			if (!$im || !$im->hasAttribute('alt')) {
				$img_missing_alt++;
				continue;
			}
			if (trim((string)$im->getAttribute('alt')) === '') {
				$img_missing_alt++;
			}
		}
		return array('h1_count' => $h1_count, 'img_missing_alt' => $img_missing_alt);
	}

	function seo_monitor_display_title($title, $name) {
		$t = trim(strip_tags((string)$title));
		if ($t !== '') {
			return $t;
		}
		return trim(strip_tags((string)$name));
	}

	function seo_monitor_strlen_utf8($s) {
		if (function_exists('mb_strlen')) {
			return mb_strlen((string)$s, 'UTF-8');
		}
		return strlen((string)$s);
	}

	function seo_monitor_locale_has_audit_scope(array $loc) {
		$s = (string)($loc['source'] ?? '');
		return ($s === 'main' || $s === 'content_i18n');
	}

	/**
	 * How many H1 tags does the page template/layout add outside of DB content?
	 *
	 * Layouts that always render their own H1 (hero, page title) contribute 1.
	 * Layouts with conditional H1 (show H1 only if content lacks one) return
	 * a value that depends on $content_h1_count so the total is always 1.
	 *
	 * Returns the number of H1 tags the template adds for the given module.
	 */
	function seo_monitor_template_h1_count($module, $content_h1_count = 0) {
		$module = trim((string) $module);
		$always_h1 = array('index');
		if (in_array($module, $always_h1, true)) {
			return 1;
		}
		$conditional_h1 = array('page', 'demo', 'download', 'news');
		if (in_array($module, $conditional_h1, true)) {
			return ($content_h1_count >= 1) ? 0 : 1;
		}
		return 0;
	}

	/**
	 * HTML diagnostics for UI / report (null counts when there is no body).
	 *
	 * @return array{has_html:bool,h1_count:int|null,img_missing_alt:int|null}
	 */
	function seo_monitor_locale_html_metrics(array $loc) {
		$html = (string)($loc['content'] ?? '');
		if (trim($html) === '') {
			return array('has_html' => false, 'h1_count' => null, 'img_missing_alt' => null);
		}
		$dom = seo_monitor_dom_analyze_html($html);
		$content_h1 = (int)$dom['h1_count'];
		$ctx = isset($loc['seo_monitor_ctx']) && is_array($loc['seo_monitor_ctx']) ? $loc['seo_monitor_ctx'] : array();
		$module = isset($ctx['module']) ? (string)$ctx['module'] : '';
		$template_h1 = seo_monitor_template_h1_count($module, $content_h1);
		return array(
			'has_html' => true,
			'h1_count' => $content_h1 + $template_h1,
			'img_missing_alt' => (int)$dom['img_missing_alt'],
		);
	}

	/**
	 * @return array<int, array{code:string,detail:mixed}>
	 */
	/**
	 * @return array<int, array{code:string,detail:mixed}>
	 */
	function seo_monitor_analyze_author_profile_locale(array $loc) {
		$issues = array();
		if (!seo_monitor_locale_has_audit_scope($loc)) {
			return $issues;
		}
		foreach (array('name' => 'name', 'title' => 'job_title', 'content' => 'bio') as $key => $label) {
			if (trim((string)($loc[$key] ?? '')) === '') {
				$issues[] = array('code' => 'author_field_empty', 'detail' => $label);
			}
		}
		return $issues;
	}

	function seo_monitor_analyze_locale(array $loc) {
		$ctx = isset($loc['seo_monitor_ctx']) && is_array($loc['seo_monitor_ctx']) ? $loc['seo_monitor_ctx'] : array();
		$ent = isset($ctx['entity']) ? trim((string)$ctx['entity']) : '';
		if ($ent !== '' && seo_monitor_entity_profile_only($ent)) {
			return seo_monitor_analyze_author_profile_locale($loc);
		}
		$issues = array();
		$html = (string)($loc['content'] ?? '');
		$dt = seo_monitor_display_title($loc['title'] ?? '', $loc['name'] ?? '');
		if ($dt !== '' && seo_monitor_strlen_utf8($dt) > 70) {
			$issues[] = array('code' => 'title_too_long', 'detail' => seo_monitor_strlen_utf8($dt));
		}
		$desc = trim(strip_tags((string)($loc['description'] ?? '')));
		if ($desc !== '' && seo_monitor_strlen_utf8($desc) > 160) {
			$issues[] = array('code' => 'description_too_long', 'detail' => seo_monitor_strlen_utf8($desc));
		}
		if (seo_monitor_locale_has_audit_scope($loc) && trim($html) === '' && !seo_monitor_body_empty_exempt($loc)) {
			$issues[] = array('code' => 'body_empty', 'detail' => true);
		}
		if ($html !== '') {
			$dom = seo_monitor_dom_analyze_html($html);
			$content_h1 = (int)$dom['h1_count'];
			$module = isset($ctx['module']) ? (string)$ctx['module'] : '';
			$template_h1 = seo_monitor_template_h1_count($module, $content_h1);
			$page_h1 = $content_h1 + $template_h1;
			if ($page_h1 !== 1) {
				$issues[] = array('code' => 'h1_not_single', 'detail' => $page_h1);
			}
			if ((int)$dom['img_missing_alt'] > 0) {
				$issues[] = array('code' => 'img_missing_alt', 'detail' => (int)$dom['img_missing_alt']);
			}
		}
		return $issues;
	}

	function seo_monitor_issue_codes_for_filter() {
		return array('body_empty', 'title_too_long', 'description_too_long', 'h1_not_single', 'img_missing_alt', 'author_field_empty');
	}

	/**
	 * List view filter chips (authors: profile fields only, no HTML/SEO meta checks).
	 *
	 * @return array<string,string>
	 */
	function seo_monitor_issue_filter_labels($entity = '') {
		if (seo_monitor_entity_profile_only($entity)) {
			return array(
				'all' => 'All',
				'any_issue' => 'Any issue',
				'author_field_empty' => 'Missing name / role / bio',
			);
		}
		return array(
			'all' => 'All',
			'any_issue' => 'Any issue',
			'body_empty' => 'No HTML body',
			'title_too_long' => 'Title > 70',
			'description_too_long' => 'Meta > 160',
			'h1_not_single' => 'H1 ≠ 1',
			'img_missing_alt' => 'Images w/o alt',
		);
	}

	/**
	 * @param array<string,mixed> $info seo_monitor_entity_map() entry
	 */
	function seo_monitor_list_search_where($entity, $q, array $info) {
		$q = trim((string)$q);
		if ($q === '') {
			return '';
		}
		if (preg_match('/^\d+$/', $q)) {
			return ' AND id=' . (int)$q . ' ';
		}
		$q_like = mysql_res('%' . $q . '%');
		if (!empty($info['profile_only'])) {
			return " AND (
				name LIKE '" . $q_like . "'
				OR job_title LIKE '" . $q_like . "'
				OR bio LIKE '" . $q_like . "'
			) ";
		}
		return " AND (
			name LIKE '" . $q_like . "'
			OR title LIKE '" . $q_like . "'
			OR url LIKE '" . $q_like . "'
		) ";
	}

	/**
	 * @param array<string,mixed> $info
	 */
	function seo_monitor_list_order_sql($sort_by, $dir, array $info) {
		$dir = strtolower((string)$dir) === 'asc' ? 'ASC' : 'DESC';
		if ((string)$sort_by === 'title') {
			if (!empty($info['profile_only'])) {
				return ' ORDER BY COALESCE(NULLIF(job_title,\'\'), NULLIF(name,\'\')) ' . $dir . ', id DESC ';
			}
			return ' ORDER BY COALESCE(NULLIF(title,\'\'), NULLIF(name,\'\')) ' . $dir . ', id DESC ';
		}
		return ' ORDER BY id ' . $dir . ' ';
	}

	/**
	 * @return array{ok:bool,message?:string,data?:array}
	 */
	function seo_monitor_export_cluster_array($entity, $entity_id, $mode) {
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		$map = seo_monitor_entity_map();
		if (!isset($map[$entity]) || $entity_id <= 0) {
			return array('ok' => false, 'message' => 'Bad entity or id');
		}
		$mode = ($mode === 'full') ? 'full' : 'meta';
		$strip = ($mode !== 'full');
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$langs = seo_monitor_cluster_languages($source_lang_id);
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return array('ok' => false, 'message' => 'Record not found');
		}
		$lang_ids = array_map(function ($l) {
			return (int)$l['id'];
		}, $langs);
		$i18n_by_lang = array();
		if (!empty($lang_ids)) {
			$rows = mysql_select("
				SELECT lang_id, url, name, title, description, content, status
				FROM content_i18n
				WHERE entity='" . mysql_res($entity) . "'
				  AND entity_id=" . $entity_id . "
				  AND lang_id IN (" . implode(',', array_map('intval', $lang_ids)) . ")
			", 'rows') ?: array();
			foreach ($rows as $r) {
				$i18n_by_lang[(int)$r['lang_id']] = $r;
			}
		}
		$locales = array();
		foreach ($langs as $lm) {
			$lid = (int)$lm['id'];
			$i18n = isset($i18n_by_lang[$lid]) ? $i18n_by_lang[$lid] : null;
			$locales[] = seo_monitor_locale_payload($entity, $entity_id, $lm, $main, $i18n, $source_lang_id, $strip);
		}
		return array(
			'ok' => true,
			'data' => array(
				'schema' => seo_monitor_schema_version(),
				'exported_at' => gmdate('c'),
				'entity' => $entity,
				'entity_id' => $entity_id,
				'mode' => $mode,
				'locales' => $locales,
			),
		);
	}

	/**
	 * JSON export: diagnostics + issues only (no HTML body). Not importable.
	 *
	 * @return array{ok:bool,message?:string,data?:array}
	 */
	function seo_monitor_export_report_array($entity, $entity_id) {
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		$map = seo_monitor_entity_map();
		if (!isset($map[$entity]) || $entity_id <= 0) {
			return array('ok' => false, 'message' => 'Bad entity or id');
		}
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$langs = seo_monitor_cluster_languages($source_lang_id);
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return array('ok' => false, 'message' => 'Record not found');
		}
		$lang_ids = array_map(function ($l) {
			return (int)$l['id'];
		}, $langs);
		$i18n_by_lang = array();
		if (!empty($lang_ids)) {
			$rows = mysql_select("
				SELECT lang_id, url, name, title, description, content, status
				FROM content_i18n
				WHERE entity='" . mysql_res($entity) . "'
				  AND entity_id=" . $entity_id . "
				  AND lang_id IN (" . implode(',', array_map('intval', $lang_ids)) . ")
			", 'rows') ?: array();
			foreach ($rows as $r) {
				$i18n_by_lang[(int)$r['lang_id']] = $r;
			}
		}
		$locales = array();
		foreach ($langs as $lm) {
			$lid = (int)$lm['id'];
			$i18n = isset($i18n_by_lang[$lid]) ? $i18n_by_lang[$lid] : null;
			$loc = seo_monitor_locale_payload($entity, $entity_id, $lm, $main, $i18n, $source_lang_id, false);
			$issues = seo_monitor_analyze_locale($loc);
			$html = (string)($loc['content'] ?? '');
			$metrics = seo_monitor_locale_html_metrics($loc);
			$summaries = array();
			foreach ($issues as $i) {
				$summaries[] = array(
					'code' => (string)$i['code'],
					'label' => seo_monitor_issue_label($i['code']),
					'detail' => isset($i['detail']) ? $i['detail'] : null,
				);
			}
			$locales[] = array(
				'lang_id' => (int)$lm['id'],
				'lang_url' => isset($lm['url']) ? trim((string)$lm['url'], '/') : '',
				'url' => (string)($loc['url'] ?? ''),
				'status' => (string)($loc['status'] ?? ''),
				'source' => (string)($loc['source'] ?? ''),
				'metrics' => array(
					'title_display_len' => seo_monitor_strlen_utf8(seo_monitor_display_title($loc['title'] ?? '', $loc['name'] ?? '')),
					'description_plain_len' => seo_monitor_strlen_utf8(trim(strip_tags((string)($loc['description'] ?? '')))),
					'content_bytes' => strlen($html),
					'h1_count' => $metrics['h1_count'],
					'images_missing_alt' => $metrics['img_missing_alt'],
				),
				'issue_count' => count($issues),
				'issues' => $issues,
				'issue_summaries' => $summaries,
			);
		}
		return array(
			'ok' => true,
			'data' => array(
				'schema' => seo_monitor_schema_version(),
				'exported_at' => gmdate('c'),
				'entity' => $entity,
				'entity_id' => $entity_id,
				'mode' => 'report',
				'note' => 'Report only — not for import. Use mode=meta or full for round-trip JSON.',
				'locales' => $locales,
			),
		);
	}

	/**
	 * Batch-load content_i18n for many entity ids (list page).
	 *
	 * @return array<int, array<int, array<string, mixed>>>
	 */
	function seo_monitor_batch_i18n_rows($entity, array $entity_ids, array $lang_ids) {
		$out = array();
		$entity_ids = array_values(array_filter(array_map('intval', $entity_ids)));
		$lang_ids = array_values(array_filter(array_map('intval', $lang_ids)));
		if ($entity_ids === array() || $lang_ids === array()) {
			return $out;
		}
		$rows = mysql_select("
			SELECT entity_id, lang_id, url, name, title, description, content, status
			FROM content_i18n
			WHERE entity='" . mysql_res($entity) . "'
			  AND entity_id IN (" . implode(',', $entity_ids) . ")
			  AND lang_id IN (" . implode(',', $lang_ids) . ")
		", 'rows') ?: array();
		foreach ($rows as $r) {
			$eid = (int)$r['entity_id'];
			$lid = (int)$r['lang_id'];
			if (!isset($out[$eid])) {
				$out[$eid] = array();
			}
			$out[$eid][$lid] = $r;
		}
		return $out;
	}

	/**
	 * @return array{ok:bool,message?:string,payload?:array}
	 */
	function seo_monitor_decode_import_json($raw) {
		$raw = (string)$raw;
		if ($raw === '') {
			return array('ok' => false, 'message' => 'Empty file');
		}
		$j = json_decode($raw, true);
		if (!is_array($j)) {
			return array('ok' => false, 'message' => 'Invalid JSON');
		}
		return array('ok' => true, 'payload' => $j);
	}

	/**
	 * @return array{ok:bool,file_mode?:string,message?:string}
	 */
	function seo_monitor_validate_import_payload(array $j, $expect_entity, $expect_entity_id) {
		if (($j['schema'] ?? '') !== seo_monitor_schema_version()) {
			return array('ok' => false, 'message' => 'Wrong schema (expected ' . seo_monitor_schema_version() . ')');
		}
		$file_entity = seo_monitor_entity_key_canonical($j['entity'] ?? '');
		$expect_entity_c = seo_monitor_entity_key_canonical($expect_entity);
		if ($file_entity !== $expect_entity_c) {
			return array(
				'ok' => false,
				'message' => 'Entity mismatch: JSON entity is "' . (string)($j['entity'] ?? '')
					. '" (canonical: "' . $file_entity . '") but this cluster is "'
					. (string)$expect_entity . '" (canonical: "' . $expect_entity_c
					. '"). Open SEO Monitor → correct content type → same row ID, or fix entity in the file.',
			);
		}
		if ((int)($j['entity_id'] ?? 0) !== (int)$expect_entity_id) {
			return array('ok' => false, 'message' => 'entity_id mismatch');
		}
		$mode = isset($j['mode']) ? (string)$j['mode'] : 'meta';
		if (!in_array($mode, array('meta', 'full'), true)) {
			return array('ok' => false, 'message' => 'Invalid mode in file');
		}
		if (empty($j['locales']) || !is_array($j['locales'])) {
			return array('ok' => false, 'message' => 'No locales in file');
		}
		return array('ok' => true, 'file_mode' => $mode);
	}

	/**
	 * @return array{ok:bool,message:string}
	 */
	function seo_monitor_save_main_row($entity, $entity_id, array $fields, array $info) {
		$table = $info['table'];
		$col = isset($info['src_content_col']) ? (string)$info['src_content_col'] : 'content';
		if (!in_array($col, array('text', 'content', 'bio'), true)) {
			$col = 'content';
		}
		$entity_id = (int)$entity_id;
		$up = array();
		if (array_key_exists('name', $fields)) {
			$up['name'] = $fields['name'];
		}
		if (array_key_exists('title', $fields)) {
			$up[!empty($info['profile_only']) ? 'job_title' : 'title'] = $fields['title'];
		}
		if (array_key_exists('description', $fields) && empty($info['profile_only'])) {
			$up['description'] = $fields['description'];
		}
		if (array_key_exists('content', $fields)) {
			$up[$col] = $fields['content'];
		}
		if (array_key_exists('url', $fields)) {
			$up['url'] = $fields['url'];
		}
		if (empty($up)) {
			return array('ok' => true, 'message' => 'Nothing to update on source row');
		}
		// Do not use mysql_fn('update'): it returns false when affected_rows===0 (values unchanged).
		global $config;
		$set = array();
		foreach ($up as $k => $v) {
			if ($v === null && !empty($config['mysql_null'])) {
				$set[] = '`' . $k . '` = NULL';
			} else {
				$set[] = '`' . $k . "` = '" . mysql_res($v) . "'";
			}
		}
		$sql = 'UPDATE `' . mysql_res($table) . '` SET ' . implode(', ', $set) . ' WHERE id=' . $entity_id . ' LIMIT 1';
		$connect = mysql_connect_db();
		if ($connect === false) {
			return array('ok' => false, 'message' => 'Source row update failed (database)');
		}
		if (!mysqli_query($connect, $sql)) {
			return array('ok' => false, 'message' => 'Source row update failed: ' . mysqli_error($connect));
		}
		return array('ok' => true, 'message' => 'Source row updated');
	}

	/**
	 * @param string $apply_mode meta|full
	 * @return array{ok:bool,message:string,details?:array}
	 */
	function seo_monitor_import_cluster($entity, $entity_id, array $j, $apply_mode, $dry_run) {
		if (!function_exists('admin_i18n_get')) {
			require_once __DIR__ . '/../admin/modules/_i18n.php';
		}
		$entity = trim((string)$entity);
		$entity_id = (int)$entity_id;
		$map = seo_monitor_entity_map();
		if (!isset($map[$entity]) || $entity_id <= 0) {
			return array('ok' => false, 'message' => 'Bad entity or id');
		}
		$info = $map[$entity];
		$main = seo_monitor_fetch_main_row($entity, $entity_id, $info);
		if (!$main) {
			return array('ok' => false, 'message' => 'Record not found');
		}
		$val = seo_monitor_validate_import_payload($j, $entity, $entity_id);
		if (empty($val['ok'])) {
			return array('ok' => false, 'message' => isset($val['message']) ? (string)$val['message'] : 'Invalid file');
		}
		$apply_mode = ($apply_mode === 'full') ? 'full' : 'meta';
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$allowed = seo_monitor_lang_id_allowed_map($source_lang_id);
		$updated = 0;
		$skipped = 0;
		$errors = array();

		foreach ($j['locales'] as $idx => $loc) {
			if (!is_array($loc)) {
				$skipped++;
				continue;
			}
			$lang_id = isset($loc['lang_id']) ? (int)$loc['lang_id'] : 0;
			if ($lang_id <= 0 || empty($allowed[$lang_id])) {
				$skipped++;
				continue;
			}
			$fields = array();
			if (isset($loc['name'])) {
				$fields['name'] = (string)$loc['name'];
			}
			if (isset($loc['title'])) {
				$fields['title'] = (string)$loc['title'];
			}
			if (isset($loc['description'])) {
				$fields['description'] = (string)$loc['description'];
			}
			if (isset($loc['url'])) {
				$fields['url'] = trim((string)$loc['url']);
			}
			if ($apply_mode === 'full' && array_key_exists('content', $loc)) {
				$fields['content'] = (string)$loc['content'];
			}
			$st = isset($loc['status']) ? (string)$loc['status'] : '';
			if ($lang_id !== $source_lang_id) {
				$existing = admin_i18n_get($entity, $entity_id, $lang_id);
				if ($st !== '' && in_array($st, array('missing', 'draft', 'review', 'published'), true)) {
					$fields['status'] = $st;
				} elseif ($existing && isset($existing['status'])) {
					$fields['status'] = (string)$existing['status'];
				} else {
					$fields['status'] = 'draft';
				}
			}
			if ($lang_id === $source_lang_id) {
				$main_fields = array();
				if (isset($fields['name'])) {
					$main_fields['name'] = $fields['name'];
				}
				if (isset($fields['title'])) {
					$main_fields['title'] = $fields['title'];
				}
				if (isset($fields['description'])) {
					$main_fields['description'] = $fields['description'];
				}
				if (isset($fields['url'])) {
					$main_fields['url'] = $fields['url'];
				}
				if ($apply_mode === 'full' && isset($fields['content'])) {
					$main_fields['content'] = $fields['content'];
				}
				if (empty($main_fields)) {
					$skipped++;
					continue;
				}
				if ($dry_run) {
					$updated++;
					continue;
				}
				$has_ci = (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0);
				$full_html = ($apply_mode === 'full' && isset($main_fields['content']));
				// Live pages (modules/pages.php) prefer content_i18n HTML for EN when the row exists.
				if ($full_html && $has_ci) {
					$i18n_src = $main_fields;
					$i18n_src['status'] = 'published';
					$sv = admin_i18n_save($entity, $entity_id, $source_lang_id, $i18n_src);
					if (empty($sv['ok'])) {
						$errors[] = 'Source content_i18n (lang ' . $lang_id . '): '
							. (isset($sv['message']) ? (string)$sv['message'] : 'save failed');
					} else {
						$updated++;
						if (function_exists('admin_i18n_sync_canonical_row_to_base_table')) {
							admin_i18n_sync_canonical_row_to_base_table($entity, $entity_id, $source_lang_id);
						}
					}
				} else {
					$r = seo_monitor_save_main_row($entity, $entity_id, $main_fields, $info);
					if (empty($r['ok'])) {
						$errors[] = 'Source (lang ' . $lang_id . '): ' . $r['message'];
					} else {
						$updated++;
						if ($has_ci && $main_fields !== array()) {
							$i18n_src = $main_fields;
							$i18n_src['status'] = 'published';
							$sv = admin_i18n_save($entity, $entity_id, $source_lang_id, $i18n_src);
							if (empty($sv['ok'])) {
								$errors[] = 'Source content_i18n (lang ' . $lang_id . '): '
									. (isset($sv['message']) ? (string)$sv['message'] : 'save failed');
							}
						}
					}
				}
			} else {
				$i18n_data = array();
				if (isset($fields['name'])) {
					$i18n_data['name'] = $fields['name'];
				}
				if (isset($fields['title'])) {
					$i18n_data['title'] = $fields['title'];
				}
				if (isset($fields['description'])) {
					$i18n_data['description'] = $fields['description'];
				}
				if (isset($fields['url'])) {
					$i18n_data['url'] = $fields['url'];
				}
				if ($apply_mode === 'full' && isset($fields['content'])) {
					$i18n_data['content'] = $fields['content'];
				}
				if (isset($fields['status'])) {
					$i18n_data['status'] = $fields['status'];
				}
				if (empty($i18n_data)) {
					$skipped++;
					continue;
				}
				if ($dry_run) {
					$updated++;
					continue;
				}
				$sv = admin_i18n_save($entity, $entity_id, $lang_id, $i18n_data);
				if (empty($sv['ok'])) {
					$errors[] = 'Lang ' . $lang_id . ': ' . (isset($sv['message']) ? (string)$sv['message'] : 'save failed');
				} else {
					$updated++;
				}
			}
		}

		if ($errors !== array()) {
			return array(
				'ok' => false,
				'message' => implode('; ', $errors),
				'details' => array('updated' => $updated, 'skipped' => $skipped),
			);
		}
		if (!$dry_run && $updated > 0) {
			if (!function_exists('translation_cluster_refresh_state')) {
				require_once __DIR__ . '/translation_cluster.php';
			}
			$dst = array();
			foreach (seo_monitor_cluster_languages($source_lang_id) as $l) {
				$lid = isset($l['id']) ? (int)$l['id'] : 0;
				if ($lid > 0 && $lid !== $source_lang_id) {
					$dst[] = $lid;
				}
			}
			if ($dst !== array()) {
				translation_cluster_refresh_state($entity, $entity_id, $source_lang_id, $dst, 0);
			}
			if (function_exists('translation_cluster_has_full_scope_locales_in_ci') && translation_cluster_has_full_scope_locales_in_ci($entity, $entity_id, $source_lang_id)) {
				translation_cluster_upsert_state($entity, $entity_id, array(
					'seo_monitor_handoff' => 1,
				));
				if (function_exists('translation_vector_cluster_ingest_from_content_i18n')) {
					translation_vector_cluster_ingest_from_content_i18n($entity, $entity_id, 'approved');
				}
			}
		}
		$msg = $dry_run
			? ('Dry run: would apply ' . $updated . ' locale update(s), skipped ' . $skipped . '.')
			: ('Import complete: ' . $updated . ' locale(s) updated, skipped ' . $skipped . '.');
		if ($skipped > 0) {
			$msg .= ' Skipped locales are not in the cluster language list (check JSON lang_id vs. translation_settings enabled languages or empty = all display languages).';
		}
		return array(
			'ok' => true,
			'message' => $msg,
			'details' => array('updated' => $updated, 'skipped' => $skipped),
		);
	}

	function seo_monitor_issue_label($code) {
		switch ((string)$code) {
			case 'title_too_long':
				return 'Title > 70 chars';
			case 'description_too_long':
				return 'Meta description > 160';
			case 'h1_not_single':
				return 'H1 count ≠ 1 (page total)';
			case 'img_missing_alt':
				return 'Images without alt';
			case 'body_empty':
				return 'No HTML body (H1/alt not checked)';
			case 'author_field_empty':
				return 'Author name, job title, or bio empty';
			default:
				return (string)$code;
		}
	}

	/**
	 * Whether a locale cell counts toward dashboard SEO score (source always; targets only with real i18n row).
	 */
	function seo_monitor_locale_cell_in_scope(array $loc, $lang_id, $source_lang_id) {
		if ((int)$lang_id === (int)$source_lang_id) {
			return true;
		}
		return (($loc['source'] ?? '') === 'content_i18n');
	}

	/**
	 * Good / relevant locale cells for a slice of main-table rows (same rules as full aggregate).
	 *
	 * @param array<int,array<string,mixed>> $main_rows
	 * @return array{good:int,relevant:int}
	 */
	function seo_monitor_aggregate_stats_for_main_rows($entity, array $main_rows) {
		if ($main_rows === array()) {
			return array('good' => 0, 'relevant' => 0);
		}
		$entity = trim((string)$entity);
		$emap = seo_monitor_entity_map();
		if (!isset($emap[$entity])) {
			return array('good' => 0, 'relevant' => 0);
		}
		$cfg = seo_monitor_translation_settings();
		$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;
		$cluster_langs = seo_monitor_cluster_languages($source_lang_id);
		$cluster_lang_ids = array_map(function ($l) {
			return (int)$l['id'];
		}, $cluster_langs);
		$ids = array_map(function ($r) {
			return (int)$r['id'];
		}, $main_rows);
		$i18n_batch = array();
		if (!empty($cluster_lang_ids)) {
			$i18n_batch = seo_monitor_batch_i18n_rows($entity, $ids, $cluster_lang_ids);
		}
		$good = 0;
		$relevant = 0;
		foreach ($main_rows as $idx => $mr) {
			$eid = (int)$mr['id'];
			if (seo_monitor_normalize_canonical_main_meta($entity, $eid)) {
				$fresh = seo_monitor_fetch_main_row($entity, $eid, $emap[$entity]);
				if (is_array($fresh)) {
					$main_rows[$idx] = $fresh;
					$mr = $fresh;
				}
			}
			foreach ($cluster_langs as $lm) {
				$lid = (int)$lm['id'];
				$i18n = isset($i18n_batch[$eid][$lid]) ? $i18n_batch[$eid][$lid] : null;
				$loc = seo_monitor_locale_payload($entity, $eid, $lm, $mr, $i18n, $source_lang_id, false);
				if (!seo_monitor_locale_cell_in_scope($loc, $lid, $source_lang_id)) {
					continue;
				}
				$relevant++;
				if (empty(seo_monitor_analyze_locale($loc))) {
					$good++;
				}
			}
		}
		return array('good' => $good, 'relevant' => $relevant);
	}

	/**
	 * Keyset page of main rows for chunked rebuilds.
	 *
	 * @return array{rows:array<int,array<string,mixed>>,last_id:int}
	 */
	function seo_monitor_fetch_main_rows_chunk($entity, $after_id, $limit) {
		$map = seo_monitor_entity_map();
		$entity = trim((string)$entity);
		$after_id = (int)$after_id;
		$limit = max(1, min(500, (int)$limit));
		$empty = array('rows' => array(), 'last_id' => $after_id);
		if (!isset($map[$entity])) {
			return $empty;
		}
		$info = $map[$entity];
		$table = $info['table'];
		if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
			return $empty;
		}
		$where = seo_monitor_display_where($table);
		$exc = seo_monitor_sql_not_in_excluded_ids($entity);
		$rows = mysql_select("
			SELECT " . seo_monitor_main_row_select_sql($info) . "
			FROM `" . mysql_res($table) . "`
			WHERE 1 " . $where . $exc . ' AND id > ' . $after_id . '
			ORDER BY id ASC
			LIMIT ' . $limit . '
		', 'rows') ?: array();
		$last_id = $after_id;
		foreach ($rows as $r) {
			$last_id = max($last_id, (int)$r['id']);
		}
		return array('rows' => $rows, 'last_id' => $last_id);
	}

	function seo_monitor_rebuild_row_chunk_size() {
		return 40;
	}

	/**
	 * Recompute aggregate for one entity in row chunks; optional progress bucket for overview job (keys done, total).
	 *
	 * @param array{done:int,total:int}|null $progress_mut updated when set (row count across all segments)
	 * @return array{ok:bool,agg?:array{pct:float|null,good:int,relevant:int,rows:int},message?:string}
	 */
	function seo_monitor_rebuild_entity_chunked($entity, $job_id, &$progress_mut = null) {
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
			return array('ok' => false, 'message' => 'content_i18n not found');
		}
		$entity = trim((string)$entity);
		if (!isset(seo_monitor_entity_map()[$entity])) {
			return array('ok' => false, 'message' => 'Bad entity');
		}
		if ($job_id > 0 && !function_exists('admin_jobs_touch')) {
			require_once __DIR__ . '/admin_jobs.php';
		}
		$total = seo_monitor_validation_included_row_count($entity);
		if ($total <= 0) {
			$agg = array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => 0);
			seo_monitor_overview_cache_patch_entity($entity, $agg);
			if ($job_id > 0) {
				admin_jobs_touch($job_id, '[100%] ' . $entity . ' · 0 validation rows');
			}
			return array('ok' => true, 'agg' => $agg);
		}
		$chunk_n = seo_monitor_rebuild_row_chunk_size();
		$after = 0;
		$done = 0;
		$tot_good = 0;
		$tot_rel = 0;
		$use_global = (is_array($progress_mut) && array_key_exists('done', $progress_mut) && array_key_exists('total', $progress_mut));
		$g_total = $use_global ? max(1, (int)$progress_mut['total']) : 0;
		while (true) {
			$pack = seo_monitor_fetch_main_rows_chunk($entity, $after, $chunk_n);
			$rows = $pack['rows'];
			if ($rows === array()) {
				break;
			}
			$st = seo_monitor_aggregate_stats_for_main_rows($entity, $rows);
			$tot_good += (int)$st['good'];
			$tot_rel += (int)$st['relevant'];
			$n = count($rows);
			$done += $n;
			$after = (int)$pack['last_id'];
			if ($job_id > 0) {
				if ($use_global) {
					$progress_mut['done'] = (int)$progress_mut['done'] + $n;
					$g_done = (int)$progress_mut['done'];
					$pct = (int)min(99, floor(100 * $g_done / $g_total));
					admin_jobs_touch($job_id, '[' . $pct . '%] ' . $entity . ' ' . $done . '/' . $total . ' · Σ ' . $g_done . '/' . (int)$progress_mut['total']);
				} else {
					$pct = (int)min(99, floor(100 * $done / max(1, $total)));
					admin_jobs_touch($job_id, '[' . $pct . '%] ' . $entity . ' ' . $done . '/' . $total);
				}
			}
		}
		$pct_final = null;
		if ($tot_rel > 0) {
			$pct_final = round(100.0 * (float)$tot_good / (float)$tot_rel, 1);
		}
		$agg = array('pct' => $pct_final, 'good' => $tot_good, 'relevant' => $tot_rel, 'rows' => $total);
		seo_monitor_overview_cache_patch_entity($entity, $agg);
		return array('ok' => true, 'agg' => $agg);
	}

	/**
	 * Entity keys with at least one published main row and existing table.
	 *
	 * @return array<int,string>
	 */
	function seo_monitor_overview_rebuild_entity_keys() {
		$out = array();
		foreach (seo_monitor_entity_map() as $ent => $info) {
			$table = $info['table'];
			if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
				continue;
			}
			if (seo_monitor_published_row_count($ent) > 0) {
				$out[] = $ent;
			}
		}
		return $out;
	}

	function seo_monitor_pending_overview_rebuild_job_id() {
		if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') === 0) {
			return 0;
		}
		$row = mysql_select("
			SELECT id FROM admin_jobs
			WHERE module='seo_monitor'
			  AND action='rebuild_overview'
			  AND status IN ('pending','running')
			ORDER BY id DESC
			LIMIT 1
		", 'row');
		return ($row && isset($row['id'])) ? (int)$row['id'] : 0;
	}

	/**
	 * @param array<string,mixed>|null $row admin_jobs row
	 * @return array{percent:?int,message:string,status:string,action:string}
	 */
	function seo_monitor_job_progress_public($row) {
		if (!is_array($row)) {
			return array('percent' => null, 'message' => '', 'status' => '', 'action' => '');
		}
		$st = (string)($row['status'] ?? '');
		$msg = (string)($row['message'] ?? '');
		$act = (string)($row['action'] ?? '');
		$pct = null;
		if ($st === 'done') {
			$pct = 100;
		} elseif ($st === 'failed' || $st === 'cancelled') {
			$pct = null;
		} elseif (preg_match('/^\[(\d+)%\]/', $msg, $m)) {
			$pct = (int)$m[1];
		} else {
			$pct = ($st === 'pending') ? 0 : 0;
		}
		return array('percent' => $pct, 'message' => $msg, 'status' => $st, 'action' => $act);
	}

	/**
	 * Aggregated SEO health for one entity (all published/main rows × cluster languages in scope).
	 *
	 * @return array{pct:float|null,good:int,relevant:int,rows:int}
	 */
	function seo_monitor_aggregate_optimization($entity) {
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') <= 0) {
			return array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => 0);
		}
		$map = seo_monitor_entity_map();
		$entity = trim((string)$entity);
		if (!isset($map[$entity])) {
			return array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => 0);
		}
		$info = $map[$entity];
		$table = $info['table'];
		$exists = @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows');
		if ($exists === false || (int)$exists <= 0) {
			return array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => 0);
		}
		$where = seo_monitor_display_where($table);
		$exc = seo_monitor_sql_not_in_excluded_ids($entity);
		$rows = mysql_select("
			SELECT " . seo_monitor_main_row_select_sql($info) . "
			FROM `" . mysql_res($table) . "`
			WHERE 1 " . $where . $exc . "
		", 'rows') ?: array();
		$row_count = count($rows);
		if ($row_count === 0) {
			return array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => 0);
		}
		$st = seo_monitor_aggregate_stats_for_main_rows($entity, $rows);
		$good = (int)$st['good'];
		$relevant = (int)$st['relevant'];
		$pct = null;
		if ($relevant > 0) {
			$pct = round(100.0 * (float)$good / (float)$relevant, 1);
		}
		return array('pct' => $pct, 'good' => $good, 'relevant' => $relevant, 'rows' => $row_count);
	}

	/**
	 * Bootstrap text class for optimization % (<50 red, 50–80 yellow, >80 green).
	 *
	 * @param float|null $pct
	 * @return string
	 */
	function seo_monitor_opt_pct_color_class($pct) {
		if ($pct === null) {
			return 'text-muted';
		}
		if ($pct < 50.0) {
			return 'text-danger';
		}
		if ($pct <= 80.0) {
			return 'text-warning';
		}
		return 'text-success';
	}

	function seo_monitor_overview_cache_variable_key() {
		return 'seo_monitor_overview_cache';
	}

	/**
	 * Row count above this (or blog always) → recalculation via admin_jobs only.
	 */
	function seo_monitor_sync_row_threshold() {
		return 400;
	}

	function seo_monitor_entity_always_job($entity) {
		return ((string)$entity === 'blog');
	}

	function seo_monitor_entity_is_heavy($entity, $published_row_count) {
		$published_row_count = (int)$published_row_count;
		if (seo_monitor_entity_always_job($entity)) {
			return true;
		}
		return $published_row_count > seo_monitor_sync_row_threshold();
	}

	function seo_monitor_published_row_count($entity) {
		$map = seo_monitor_entity_map();
		$entity = trim((string)$entity);
		if (!isset($map[$entity])) {
			return 0;
		}
		$table = $map[$entity]['table'];
		if (@mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') <= 0) {
			return 0;
		}
		$where = seo_monitor_display_where($table);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where, 'row');
		return $r && isset($r['c']) ? (int)$r['c'] : 0;
	}

	/**
	 * @return array{entities:array<string,array>,updated_at?:string,version?:int}
	 */
	function seo_monitor_overview_cache_read() {
		$empty = array('version' => 1, 'entities' => array());
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return $empty;
		}
		$row = mysql_select("SELECT value FROM variables WHERE `key`='" . mysql_res(seo_monitor_overview_cache_variable_key()) . "' LIMIT 1", 'row');
		if (!$row || $row['value'] === '') {
			return $empty;
		}
		$dec = json_decode((string)$row['value'], true);
		if (!is_array($dec)) {
			return $empty;
		}
		if (!isset($dec['entities']) || !is_array($dec['entities'])) {
			$dec['entities'] = array();
		}
		return $dec;
	}

	function seo_monitor_overview_cache_write(array $data) {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
			return false;
		}
		$data['version'] = isset($data['version']) ? (int)$data['version'] : 1;
		if (!isset($data['entities']) || !is_array($data['entities'])) {
			$data['entities'] = array();
		}
		$data['updated_at'] = gmdate('c');
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$row = mysql_select("SELECT id FROM variables WHERE `key`='" . mysql_res(seo_monitor_overview_cache_variable_key()) . "' LIMIT 1", 'row');
		if ($row && isset($row['id'])) {
			return mysql_fn('update', 'variables', array('value' => $json), " AND id=" . (int)$row['id'] . " ") !== false;
		}
		return mysql_fn('insert', 'variables', array(
			'key' => seo_monitor_overview_cache_variable_key(),
			'value' => $json,
		)) !== false;
	}

	/**
	 * @param array{pct:?float,good:int,relevant:int,rows:int} $agg
	 */
	function seo_monitor_overview_cache_patch_entity($entity, array $agg) {
		$c = seo_monitor_overview_cache_read();
		$c['entities'][$entity] = array(
			'pct' => isset($agg['pct']) ? $agg['pct'] : null,
			'good' => (int)($agg['good'] ?? 0),
			'relevant' => (int)($agg['relevant'] ?? 0),
			'rows' => (int)($agg['rows'] ?? 0),
			'computed_at' => gmdate('c'),
		);
		return seo_monitor_overview_cache_write($c);
	}

	function seo_monitor_pending_rebuild_job_id($entity) {
		if (@mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') <= 0) {
			return 0;
		}
		$entity = (string)$entity;
		$rows = mysql_select("
			SELECT id, payload FROM admin_jobs
			WHERE module='seo_monitor'
			  AND action='rebuild_entity'
			  AND status IN ('pending','running')
			ORDER BY id DESC
			LIMIT 30
		", 'rows') ?: array();
		foreach ($rows as $row) {
			$p = json_decode((string)($row['payload'] ?? ''), true);
			if (is_array($p) && isset($p['entity']) && (string)$p['entity'] === $entity) {
				return (int)$row['id'];
			}
		}
		return 0;
	}

	/**
	 * @param array $cached slice from cache entities[entity]
	 * @return array{pct:?float,good:int,relevant:int,rows:int}
	 */
	function seo_monitor_agg_from_cached_entity($entity, $cached) {
		if (!is_array($cached)) {
			return array('pct' => null, 'good' => 0, 'relevant' => 0, 'rows' => seo_monitor_published_row_count($entity));
		}
		return array(
			'pct' => isset($cached['pct']) ? $cached['pct'] : null,
			'good' => (int)($cached['good'] ?? 0),
			'relevant' => (int)($cached['relevant'] ?? 0),
			'rows' => (int)($cached['rows'] ?? seo_monitor_published_row_count($entity)),
		);
	}

	/**
	 * Build JSON payload for ajax_entity endpoint.
	 *
	 * @return array<string, mixed>
	 */
	function seo_monitor_resolve_entity_score_state($entity, $refresh) {
		$entity = trim((string)$entity);
		$map = seo_monitor_entity_map();
		if (!isset($map[$entity])) {
			return array('ok' => false, 'message' => 'Bad entity');
		}
		$rc = seo_monitor_published_row_count($entity);
		$heavy = seo_monitor_entity_is_heavy($entity, $rc);
		$cache_blob = seo_monitor_overview_cache_read();
		$cached = isset($cache_blob['entities'][$entity]) ? $cache_blob['entities'][$entity] : null;

		$pack = function (array $agg, $source, $extra = array()) use ($entity) {
			$pct = isset($agg['pct']) ? $agg['pct'] : null;
			$rel = (int)($agg['relevant'] ?? 0);
			$show_pct = ($pct !== null && $rel > 0);
			$out = array_merge(array(
				'ok' => true,
				'entity' => $entity,
				'pct' => $pct,
				'good' => (int)($agg['good'] ?? 0),
				'relevant' => $rel,
				'rows' => (int)($agg['rows'] ?? 0),
				'heavy' => null,
				'pct_display' => $show_pct ? number_format((float)$pct, 1, ',', '') . '%' : '—',
				'color_class' => seo_monitor_opt_pct_color_class($pct),
				'source' => (string)$source,
			), $extra);
			return $out;
		};

		if (!$heavy) {
			if (!$refresh && $cached) {
				return $pack(seo_monitor_agg_from_cached_entity($entity, $cached), 'cache', array(
					'computed_at' => isset($cached['computed_at']) ? (string)$cached['computed_at'] : null,
					'heavy' => false,
				));
			}
			$agg = seo_monitor_aggregate_optimization($entity);
			$agg['rows'] = max((int)($agg['rows'] ?? 0), $rc);
			seo_monitor_overview_cache_patch_entity($entity, $agg);
			return $pack($agg, 'live', array(
				'computed_at' => gmdate('c'),
				'heavy' => false,
			));
		}

		// Heavy: blog or many rows — only refresh via job.
		$agg_cached = $cached ? seo_monitor_agg_from_cached_entity($entity, $cached) : array(
			'pct' => null,
			'good' => 0,
			'relevant' => 0,
			'rows' => $rc,
		);
		if (!$refresh) {
			if ($cached) {
				return $pack($agg_cached, 'cache', array(
					'computed_at' => isset($cached['computed_at']) ? (string)$cached['computed_at'] : null,
					'heavy' => true,
				));
			}
			return $pack($agg_cached, 'pending', array(
				'computed_at' => null,
				'heavy' => true,
				'message' => 'Large segment: use ↻ to queue background recalculation (cron / job runner).',
			));
		}

		$jid = seo_monitor_pending_rebuild_job_id($entity);
		if ($jid <= 0) {
			if (!function_exists('admin_jobs_enqueue')) {
				require_once __DIR__ . '/admin_jobs.php';
			}
			$jid = admin_jobs_enqueue('seo_monitor', 'rebuild_entity', array('entity' => $entity), array('priority' => 0));
			$jid = $jid ? (int)$jid : 0;
		}
		$msg = $jid > 0
			? ('Queued job #' . $jid . '. Run cron or translations Monitor → Queue.')
			: 'Could not enqueue job (admin_jobs missing?)';
		return $pack($agg_cached, 'queued', array(
			'computed_at' => $cached ? (isset($cached['computed_at']) ? (string)$cached['computed_at'] : null) : null,
			'heavy' => true,
			'job_id' => $jid > 0 ? $jid : null,
			'message' => $msg,
		));
	}

}
