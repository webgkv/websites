<?php
/**
 * Translate Stats: overall progress by entity and language.
 */
$page_name = 'Translate Stats';

$get = array_merge(array('u' => '', 'lang_id' => ''), (array)$get);
// POST request: $_GET may be empty if the query string was dropped from the POST URL (proxy/CDN) or
// the client posted to a bare path. admin.php only fills $get from $_GET, so recover routing fields from POST.
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	if (isset($_POST['lang_id']) && (string)$_POST['lang_id'] !== '') {
		$get['lang_id'] = (int)$_POST['lang_id'];
	}
	if (isset($_POST['u']) && (string)$_POST['u'] !== '') {
		$get['u'] = stripslashes_smart((string)$_POST['u']);
	}
	if (isset($_POST['filter'])) {
		$get['filter'] = stripslashes_smart((string)$_POST['filter']);
	}
	if (isset($_POST['entity']) && (string)$_POST['entity'] !== '') {
		$get['entity'] = stripslashes_smart((string)$_POST['entity']);
	}
	if ((!isset($get['filter']) || $get['filter'] === '') && isset($_POST['return_filter'])) {
		$get['filter'] = stripslashes_smart((string)$_POST['return_filter']);
	}
}

$table_content_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
if (!$table_content_ok) {
	$content = '<div class="alert alert-warning"><strong>Table content_i18n not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	if (!defined('TRANSLATIONS_HUB')) {
		require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
		exit;
	}
	return;
}

require_once ROOT_DIR . 'functions/translation_hub.php';
if (!defined('TRANSLATIONS_HUB')) {
	if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
		$q = $_GET;
		$q['m'] = 'translations';
		$q['tab'] = 'overview';
		header('Location: /admin.php?' . http_build_query($q));
		exit;
	}
}

require_once(ROOT_DIR . 'admin/modules/_i18n.php');

/**
 * Canonical header/footer menu rows (same rules as Languages / full pack).
 * @return array
 */
function translate_stats_canonical_menu_pages() {
	return mysql_select("SELECT id, module, level, name, url FROM pages WHERE display=1 AND menu=1 AND level<3 ORDER BY left_key", 'rows') ?: array();
}

/**
 * Keys to monitor for common.php: baseline = all keys from source language file,
 * plus any UI keys that must exist even if missing from an outdated source file.
 * @param int $source_lang_id
 * @return array list of unique string keys
 */
function translate_stats_monitored_common_keys($source_lang_id) {
	$source_lang_id = (int)$source_lang_id;
	$ref = admin_load_common_dict($source_lang_id);
	$keys = array_keys($ref);
	$extra = array(
		'read_guide', 'read_more', 'guides_cat_all', 'guides_title', 'games_title', 'games_cat_all',
		'games_cat_crash', 'games_cat_crash-p2e', 'games_cat_other', 'guides_cat_analysis', 'guides_cat_bonus',
		'guides_cat_how-to-win', 'guides_cat_signals', 'guides_cat_crash-gambling', 'hero_subtitle', 'hero_cta',
		'cta_play_aviator_now', 'cta_try_bonus', 'strategies_menu', 'predictor_menu', 'popup_special_offer', 'index_page', 'breadcrumb_index', 'breadcrumb_separator',
	);
	foreach ($extra as $k) {
		$keys[] = $k;
	}
	$keys = array_values(array_unique($keys));
	sort($keys);
	return $keys;
}

// Load translation settings (enabled languages for UI)
$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
$cfg = array('source_lang_id' => 1, 'enabled_lang_ids' => array(), 'chunk_max_len' => 2500);
if ($row && $row['value'] !== '') {
	$dec = json_decode($row['value'], true);
	if (is_array($dec)) $cfg = array_merge($cfg, $dec);
}

$langs = mysql_select("SELECT id, url, name FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array();
$enabled_set = array();
foreach ((array)@$cfg['enabled_lang_ids'] as $lid) {
	$lid = (int)$lid;
	if ($lid > 0) $enabled_set[$lid] = true;
}

$enabled_langs = array();
foreach ($langs as $l) {
	$lid = (int)$l['id'];
	if (empty($enabled_set)) {
		$enabled_langs[] = $l;
	} else {
		if (isset($enabled_set[$lid])) $enabled_langs[] = $l;
	}
}

// Source (canonical) language: content lives in main tables, not in content_i18n — so no "missing"
$source_lang_id = isset($cfg['source_lang_id']) ? (int)$cfg['source_lang_id'] : 1;

$tstats_u_route = isset($get['u']) ? (string)$get['u'] : '';
$requested_lang_id = isset($get['lang_id']) && (string)$get['lang_id'] !== '' ? (int)$get['lang_id'] : 0;
// Main table view without lang_id = overview cards (per-locale summary); any ?u=... is a drilldown route.
$overview_mode = ($tstats_u_route === '' && $requested_lang_id <= 0);

$target_lang_id = 0;
if (!$overview_mode) {
	$target_lang_id = $requested_lang_id;
	if ($target_lang_id <= 0 || empty($enabled_langs)) {
		$target_lang_id = !empty($enabled_langs) ? (int)$enabled_langs[0]['id'] : 0;
	}
	// If user picked a language that isn't enabled, fallback to first enabled
	if ($target_lang_id > 0 && !empty($enabled_langs)) {
		$ok = false;
		foreach ($enabled_langs as $l) {
			if ((int)$l['id'] === $target_lang_id) {
				$ok = true;
				break;
			}
		}
		if (!$ok) {
			$target_lang_id = (int)$enabled_langs[0]['id'];
		}
	}
}

$is_source_lang = ($target_lang_id > 0 && $target_lang_id === $source_lang_id);

if ($overview_mode) {
	$page_name = 'Translate Stats (overview)';
}

$entity_map = array(
	'pages' => array('table' => 'pages', 'label' => 'Pages', 'src_content_col' => 'text'),
	'guides' => array('table' => 'guides', 'label' => 'Guides', 'src_content_col' => 'text'),
	'games' => array('table' => 'games', 'label' => 'Games', 'src_content_col' => 'text'),
	'casino_articles' => array('table' => 'casino_articles', 'label' => 'Casino articles', 'src_content_col' => 'text'),
	'blog' => array('table' => 'blog', 'label' => 'Blog', 'src_content_col' => 'text'),
);

function _tstats_table_display_where($table) {
	$cols = mysql_select("SHOW COLUMNS FROM `" . mysql_res($table) . "`", 'rows');
	$hasDisplay = false;
	if ($cols) {
		foreach ($cols as $c) {
			if (isset($c['Field']) && (string)$c['Field'] === 'display') { $hasDisplay = true; break; }
		}
	}
	return $hasDisplay ? ' AND display=1 ' : '';
}

function _tstats_norm($v) {
	$s = (string)$v;
	$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$s = preg_replace('/\s+/u', ' ', $s);
	return trim((string)$s);
}

/**
 * Counts for the canonical (source) language baseline shown on the overview.
 *
 * @return array{content_units:int,dict_keys:int,menu_items:int}
 */
function translate_stats_source_baseline_totals($source_lang_id, array $entity_map) {
	$source_lang_id = (int)$source_lang_id;
	$n = 0;
	foreach ($entity_map as $info) {
		$table = $info['table'];
		$total_row = @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows');
		if ($total_row === false || (int)$total_row <= 0) {
			continue;
		}
		$where = _tstats_table_display_where($table);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where, 'row');
		$n += $r && isset($r['c']) ? (int)$r['c'] : 0;
	}
	return array(
		'content_units' => $n,
		'dict_keys' => count(translate_stats_monitored_common_keys($source_lang_id)),
		'menu_items' => count(translate_stats_canonical_menu_pages()),
	);
}

/**
 * Aggregate progress for one target language (same rules as the detail table + system rows).
 * @return array{pct:float,grand_total:int,grand_done:int,entities_pub:int,entities_miss:int,entities_tot:int,sc_ok:int,sc_total:int,menu_ok:int,menu_total:int}
 */
function translate_stats_overview_metrics($source_lang_id, $target_lang_id, array $entity_map) {
	$source_lang_id = (int)$source_lang_id;
	$target_lang_id = (int)$target_lang_id;
	$ref_common_dict = admin_load_common_dict($source_lang_id);
	$monitored_common_keys = translate_stats_monitored_common_keys($source_lang_id);
	$tgt_common_dict = admin_load_common_dict($target_lang_id);
	$sc_ok = 0;
	$sc_miss = 0;
	$sc_same = 0;
	foreach ($monitored_common_keys as $ck) {
		$rv = isset($ref_common_dict[$ck]) ? trim((string)$ref_common_dict[$ck]) : '';
		$tv = isset($tgt_common_dict[$ck]) ? trim((string)$tgt_common_dict[$ck]) : '';
		if ($tv === '') {
			$sc_miss++;
		} elseif ($rv !== '' && _tstats_norm($tv) === _tstats_norm($rv)) {
			$sc_same++;
		} else {
			$sc_ok++;
		}
	}
	$sc_total = count($monitored_common_keys);

	$canonical_menu_pages = translate_stats_canonical_menu_pages();
	$menu_ok = 0;
	$menu_miss = 0;
	$menu_same = 0;
	$menu_ids = array_map(function ($p) { return (int)$p['id']; }, $canonical_menu_pages);
	$menu_i18n_by_id = array();
	if (!empty($menu_ids)) {
		$menu_rows = mysql_select("
			SELECT entity_id, name
			FROM content_i18n
			WHERE entity='pages'
			  AND lang_id=" . $target_lang_id . "
			  AND entity_id IN (" . implode(',', $menu_ids) . ")
		", 'rows') ?: array();
		foreach ($menu_rows as $mr) {
			$menu_i18n_by_id[(int)$mr['entity_id']] = $mr;
		}
	}
	foreach ($canonical_menu_pages as $mp) {
		$pid = (int)$mp['id'];
		$canon_name = _tstats_norm(isset($mp['name']) ? $mp['name'] : '');
		$tr_name = '';
		if (isset($menu_i18n_by_id[$pid]) && isset($menu_i18n_by_id[$pid]['name'])) {
			$tr_name = trim((string)$menu_i18n_by_id[$pid]['name']);
		}
		if ($tr_name === '') {
			$menu_miss++;
		} elseif ($canon_name !== '' && _tstats_norm($tr_name) === $canon_name) {
			$menu_same++;
		} else {
			$menu_ok++;
		}
	}
	$menu_total = count($canonical_menu_pages);

	$tot = 0;
	$pub = 0;
	$miss = 0;
	foreach ($entity_map as $entity => $info) {
		$table = $info['table'];
		$total_row = @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows');
		if ($total_row === false || (int)$total_row <= 0) {
			continue;
		}
		$where = _tstats_table_display_where($table);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where, 'row');
		$total = $r && isset($r['c']) ? (int)$r['c'] : 0;
		$counts = array('draft' => 0, 'review' => 0, 'published' => 0);
		$group = mysql_select("
			SELECT status, COUNT(*) AS c
			FROM content_i18n
			WHERE entity='" . mysql_res($entity) . "'
			  AND lang_id=" . $target_lang_id . "
			GROUP BY status
		", 'rows') ?: array();
		foreach ($group as $g) {
			$st = isset($g['status']) ? (string)$g['status'] : '';
			if (isset($counts[$st])) {
				$counts[$st] = (int)$g['c'];
			}
		}
		$translated_any = (int)$counts['draft'] + (int)$counts['review'] + (int)$counts['published'];
		$missing = max(0, $total - $translated_any);
		$tot += $total;
		$pub += (int)$counts['published'];
		$miss += $missing;
	}

	$grand_total = $tot + $sc_total + $menu_total;
	$grand_done = $pub + $sc_ok + $menu_ok;
	$pct = $grand_total > 0 ? round(100.0 * (float)$grand_done / (float)$grand_total, 1) : 0.0;

	return array(
		'pct' => $pct,
		'grand_total' => $grand_total,
		'grand_done' => $grand_done,
		'entities_pub' => $pub,
		'entities_miss' => $miss,
		'entities_tot' => $tot,
		'sc_ok' => $sc_ok,
		'sc_total' => $sc_total,
		'sc_miss' => $sc_miss,
		'menu_ok' => $menu_ok,
		'menu_total' => $menu_total,
		'menu_miss' => $menu_miss,
	);
}

// --- System i18n: common.php keys + canonical menu (content_i18n name per page) ---
$monitored_common_keys = translate_stats_monitored_common_keys($source_lang_id);
$ref_common_dict = admin_load_common_dict($source_lang_id);
$tgt_common_dict = array();
$sys_common_stats = array('total' => count($monitored_common_keys), 'missing' => 0, 'same_as_source' => 0, 'ok' => 0);
$sys_menu_stats = array('total' => 0, 'missing_name' => 0, 'same_as_default' => 0, 'ok' => 0);
$canonical_menu_pages = translate_stats_canonical_menu_pages();

if (!$overview_mode) {
	$tgt_common_dict = ($target_lang_id > 0) ? admin_load_common_dict($target_lang_id) : array();

	$sys_common_stats = array(
		'total' => count($monitored_common_keys),
		'missing' => 0,
		'same_as_source' => 0,
		'ok' => 0,
	);
	if ($is_source_lang) {
		$sys_common_stats['ok'] = count($monitored_common_keys);
	} elseif ($target_lang_id > 0) {
		foreach ($monitored_common_keys as $ck) {
			$rv = isset($ref_common_dict[$ck]) ? trim((string)$ref_common_dict[$ck]) : '';
			$tv = isset($tgt_common_dict[$ck]) ? trim((string)$tgt_common_dict[$ck]) : '';
			if ($tv === '') {
				$sys_common_stats['missing']++;
			} elseif ($rv !== '' && _tstats_norm($tv) === _tstats_norm($rv)) {
				$sys_common_stats['same_as_source']++;
			} else {
				$sys_common_stats['ok']++;
			}
		}
	}

	$sys_menu_stats = array(
		'total' => count($canonical_menu_pages),
		'missing_name' => 0,
		'same_as_default' => 0,
		'ok' => 0,
	);
	if ($is_source_lang) {
		$sys_menu_stats['ok'] = count($canonical_menu_pages);
	} elseif ($target_lang_id > 0 && !empty($canonical_menu_pages)) {
		$menu_ids = array_map(function ($p) { return (int)$p['id']; }, $canonical_menu_pages);
		$menu_i18n_by_id = array();
		if (!empty($menu_ids)) {
			$menu_rows = mysql_select("
				SELECT entity_id, name
				FROM content_i18n
				WHERE entity='pages'
				  AND lang_id=" . (int)$target_lang_id . "
				  AND entity_id IN (" . implode(',', $menu_ids) . ")
			", 'rows') ?: array();
			foreach ($menu_rows as $mr) {
				$menu_i18n_by_id[(int)$mr['entity_id']] = $mr;
			}
		}
		foreach ($canonical_menu_pages as $mp) {
			$pid = (int)$mp['id'];
			$canon_name = _tstats_norm(isset($mp['name']) ? $mp['name'] : '');
			$tr_name = '';
			if (isset($menu_i18n_by_id[$pid]) && isset($menu_i18n_by_id[$pid]['name'])) {
				$tr_name = trim((string)$menu_i18n_by_id[$pid]['name']);
			}
			if ($tr_name === '') {
				$sys_menu_stats['missing_name']++;
			} elseif ($canon_name !== '' && _tstats_norm($tr_name) === $canon_name) {
				$sys_menu_stats['same_as_default']++;
			} else {
				$sys_menu_stats['ok']++;
			}
		}
	}
}

// POST: system dictionary (sys_common): manual save, queue, or live AI translate
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
	&& (string)($get['u'] ?? '') === 'sys_common'
	&& !$is_source_lang
	&& $target_lang_id > 0
) {
	$return_filter = isset($_POST['return_filter']) ? (string)$_POST['return_filter'] : 'all';
	if (!in_array($return_filter, array('all', 'missing', 'same_as_source', 'translated'), true)) {
		$return_filter = 'all';
	}
	$redirect_sys_common = '/admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id . '&u=sys_common&filter=' . rawurlencode($return_filter);

	if (isset($_POST['sys_common_manual_save'])) {
		$allow = array_flip($monitored_common_keys);
		$canonical = admin_load_common_dict($source_lang_id);
		$current = admin_load_common_dict($target_lang_id);
		$new = array();
		foreach (array_keys($canonical) as $_ck) {
			$new[$_ck] = isset($current[$_ck]) ? (string)$current[$_ck] : '';
		}
		$n_posted = 0;
		if (isset($_POST['manual_common']) && is_array($_POST['manual_common'])) {
			foreach ($_POST['manual_common'] as $mk => $mv) {
				$mk = trim((string)$mk);
				if ($mk === '' || !isset($allow[$mk]) || !array_key_exists($mk, $new)) {
					continue;
				}
				$new[$mk] = stripslashes_smart((string)$mv);
				$n_posted++;
			}
		}
		$res = admin_save_common_dict($target_lang_id, $new);
		if (!empty($res['ok'])) {
			$_SESSION['admin_flash_success'] = $n_posted > 0
				? ('Saved target dictionary (' . (int)$n_posted . ' field(s) from this page).')
				: 'Dictionary file written (no editable fields posted).';
		} else {
			$_SESSION['admin_flash_error'] = isset($res['message']) ? (string)$res['message'] : 'Save failed.';
		}
		header('Location: ' . $redirect_sys_common);
		exit;
	}

	$do_queue = isset($_POST['queue_sys_common_translate']);
	$do_live = isset($_POST['sys_common_live_run']);
	if ($do_queue || $do_live) {
		require_once(ROOT_DIR . 'functions/admin_jobs.php');
		require_once(ROOT_DIR . 'jobs/job_runner_lib.php');
		$chunk_max_len = isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500;
		$posted = array();
		if (isset($_POST['dict_keys']) && is_array($_POST['dict_keys'])) {
			foreach ($_POST['dict_keys'] as $pk) {
				$pk = trim((string)$pk);
				if ($pk !== '') {
					$posted[] = $pk;
				}
			}
		}
		$allow = array_flip($monitored_common_keys);
		$keys_to_run = array();
		if ($posted !== array()) {
			foreach ($posted as $pk) {
				if (isset($allow[$pk])) {
					$keys_to_run[] = $pk;
				}
			}
			$keys_to_run = array_values(array_unique($keys_to_run));
		} else {
			foreach ($monitored_common_keys as $ck) {
				$tv = isset($tgt_common_dict[$ck]) ? trim((string)$tgt_common_dict[$ck]) : '';
				if ($tv === '') {
					$keys_to_run[] = $ck;
				}
			}
		}
		if ($keys_to_run === array()) {
			$_SESSION['admin_flash_info'] = 'Nothing to queue: no checkboxes selected and no missing/empty target strings.';
		} else {
			$payload = array(
				'src_lang' => (int)$source_lang_id,
				'dst_lang' => (int)$target_lang_id,
				'dict_keys' => $keys_to_run,
				'chunk_max_len' => $chunk_max_len,
			);
			$jid = admin_jobs_enqueue('translations', 'translate_common_dict', $payload, array('priority' => 0));
			if (!$jid) {
				$_SESSION['admin_flash_error'] = 'Could not enqueue dictionary translation job.';
			} elseif ($do_live) {
				if (function_exists('set_time_limit')) {
					@set_time_limit(0);
				}
				$res = run_admin_job_by_id((int)$jid);
				if (!empty($res['ok'])) {
					$_SESSION['admin_flash_success'] = 'Live run complete (job #' . (int)$jid . '): ' . (string)$res['message'];
				} else {
					$_SESSION['admin_flash_error'] = 'Live run failed (job #' . (int)$jid . '): ' . (string)$res['message'];
				}
			} else {
				$_SESSION['admin_flash_success'] = 'Queued dictionary job #' . (int)$jid . ' (' . count($keys_to_run) . ' keys). Processing via cron / job runner.';
			}
		}
		header('Location: ' . $redirect_sys_common);
		exit;
	}
}

// Validator: detect untranslated copies (same as canonical EN) and reset to missing.
if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && (string)($get['u'] ?? '') === 'validate_entity') {
	$entity = isset($_POST['entity']) ? (string)$_POST['entity'] : '';
	if (!isset($entity_map[$entity])) {
		$_SESSION['admin_flash_error'] = 'Bad entity for validation.';
		header('Location: /admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id);
		exit;
	}
	if ($is_source_lang) {
		$_SESSION['admin_flash_info'] = 'Validator is not applicable for source language.';
		header('Location: /admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id);
		exit;
	}

	$table = $entity_map[$entity]['table'];
	$srcContentCol = isset($entity_map[$entity]['src_content_col']) ? (string)$entity_map[$entity]['src_content_col'] : 'content';
	if (!in_array($srcContentCol, array('content', 'text'), true)) $srcContentCol = 'content';
	$display_where = _tstats_table_display_where($table);
	$checked = 0;
	$invalid = 0;
	$now = date('Y-m-d H:i:s');

	$rowsValidate = mysql_select("
		SELECT
			t.id,
			COALESCE(t.name,'') AS src_name,
			COALESCE(t.title,'') AS src_title,
			COALESCE(t.description,'') AS src_description,
			COALESCE(t.`" . mysql_res($srcContentCol) . "`,'') AS src_content,
			COALESCE(ci.name,'') AS tr_name,
			COALESCE(ci.title,'') AS tr_title,
			COALESCE(ci.description,'') AS tr_description,
			COALESCE(ci.content,'') AS tr_content
		FROM `" . mysql_res($table) . "` t
		JOIN content_i18n ci
			ON ci.entity='" . mysql_res($entity) . "'
		   AND ci.entity_id=t.id
		   AND ci.lang_id=" . (int)$target_lang_id . "
		WHERE 1 " . $display_where . "
		  AND COALESCE(ci.status,'') IN ('draft','review','published')
	", 'rows') ?: array();

	foreach ($rowsValidate as $rowV) {
		$checked++;
		$s_name = _tstats_norm($rowV['src_name']);
		$s_title = _tstats_norm($rowV['src_title']);
		$s_desc = _tstats_norm($rowV['src_description']);
		$s_content = _tstats_norm($rowV['src_content']);
		$t_name = _tstats_norm($rowV['tr_name']);
		$t_title = _tstats_norm($rowV['tr_title']);
		$t_desc = _tstats_norm($rowV['tr_description']);
		$t_content = _tstats_norm($rowV['tr_content']);

		// Consider invalid only when translated text fields are exact canonical copies.
		$isCopy = ($t_title !== '' && $t_content !== '')
			&& ($t_title === $s_title)
			&& ($t_desc === $s_desc)
			&& ($t_content === $s_content);
		// Additional strict check for optional name field if present.
		if ($isCopy && $t_name !== '' && $s_name !== '' && $t_name !== $s_name) {
			$isCopy = false;
		}

		if ($isCopy) {
			$invalid++;
			mysql_fn('update', 'content_i18n', array(
				'name' => '',
				'url' => '',
				'title' => '',
				'description' => '',
				'content' => '',
				'status' => 'missing',
				'updated_at' => $now,
			), " AND entity='" . mysql_res($entity) . "' AND entity_id=" . (int)$rowV['id'] . " AND lang_id=" . (int)$target_lang_id . " ");
		}
	}

	$_SESSION['admin_flash_success'] = 'Validation done for ' . $entity_map[$entity]['label'] . ': checked ' . (int)$checked . ', reset to missing ' . (int)$invalid . '.';
	header('Location: /admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id);
	exit;
}

$rows_entity = array();
if (!$overview_mode) {
foreach ($entity_map as $entity => $info) {
	$table = $info['table'];
	// total rows in source table
	$total = 0;
	$total_row = @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows');
	if ($total_row !== false && $total_row > 0) {
		$where = _tstats_table_display_where($table);
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . mysql_res($table) . "` WHERE 1 " . $where, 'row');
		$total = $r && isset($r['c']) ? (int)$r['c'] : 0;
	}

	$counts = array('draft' => 0, 'review' => 0, 'published' => 0);
	if ($is_source_lang) {
		// Canonical language: content is in main tables, not in content_i18n — all count as "published"
		$counts['published'] = (int)$total;
		$missing = 0;
	} else {
		$group = mysql_select("
			SELECT status, COUNT(*) AS c
			FROM content_i18n
			WHERE entity='" . mysql_res($entity) . "'
			  AND lang_id=" . (int)$target_lang_id . "
			GROUP BY status
		", 'rows') ?: array();
		foreach ($group as $g) {
			$st = isset($g['status']) ? (string)$g['status'] : '';
			if (isset($counts[$st])) $counts[$st] = (int)$g['c'];
		}
		$translated_any = (int)$counts['draft'] + (int)$counts['review'] + (int)$counts['published'];
		$missing = max(0, (int)$total - $translated_any);
	}

	$rows_entity[] = array(
		'entity' => $entity,
		'label' => $info['label'],
		'total' => $total,
		'draft' => $counts['draft'],
		'review' => $counts['review'],
		'published' => $counts['published'],
		'missing' => $missing,
	);
}
}

$content = '<div class="card"><div class="card-body">';
$content .= '<h5 class="mb-2">Translate Stats</h5>';

if ($overview_mode) {
	$baseline = translate_stats_source_baseline_totals($source_lang_id, $entity_map);
	$src_meta = null;
	foreach ($langs as $l) {
		if ((int)$l['id'] === (int)$source_lang_id) {
			$src_meta = $l;
			break;
		}
	}
	$src_name = $src_meta ? (string)$src_meta['name'] : ('Language #' . (int)$source_lang_id);
	$src_url = $src_meta ? trim((string)$src_meta['url'], '/') : '';
	$src_href = '/admin.php?m=translations&tab=overview&lang_id=' . (int)$source_lang_id;

	$content .= '<div class="tstats-overview">';
	$content .= '<div class="card mb-4 tstats-overview-source shadow-sm">';
	$content .= '<div class="card-header tstats-overview-source-head py-3 d-flex justify-content-between align-items-center flex-wrap">';
	$content .= '<div class="mb-2 mb-md-0">';
	$content .= '<span class="badge badge-info align-middle mr-2">Source</span> ';
	$content .= '<strong class="h5 mb-0 align-middle d-inline text-dark font-weight-bold">' . htmlspecialchars($src_name) . '</strong>';
	if ($src_url !== '') {
		$content .= ' <span class="badge badge-secondary align-middle ml-1">' . htmlspecialchars($src_url) . '</span>';
	}
	$content .= '</div>';
	$content .= '<a class="btn btn-primary btn-sm font-weight-bold tstats-source-breakdown-btn" href="' . htmlspecialchars($src_href, ENT_QUOTES, 'UTF-8') . '">Open breakdown</a>';
	$content .= '</div>';
	$content .= '<div class="card-body">';
	$content .= '<div class="row">';
	$src_boxes = array(
		array('label' => 'Content rows', 'key' => 'content_units'),
		array('label' => 'UI dictionary', 'key' => 'dict_keys'),
		array('label' => 'Menu labels', 'key' => 'menu_items'),
	);
	foreach ($src_boxes as $bi => $box) {
		$val = (int)$baseline[$box['key']];
		$content .= '<div class="col-md-4 mb-3 mb-md-0' . ($bi < 2 ? ' pr-md-2' : '') . '">';
		$content .= '<div class="tstats-metric h-100">';
		$content .= '<div class="tstats-metric-label">' . htmlspecialchars($box['label']) . '</div>';
		$content .= '<div class="tstats-metric-value text-dark">' . $val . '</div>';
		$content .= '</div></div>';
	}
	$content .= '</div></div></div>';

	$content .= '<h6 class="tstats-section-label mb-3">Target languages</h6>';
	$content .= '<div class="row">';
	$any_target_card = false;
	foreach ($enabled_langs as $l) {
		$lid = (int)$l['id'];
		if ($lid === (int)$source_lang_id) {
			continue;
		}
		$any_target_card = true;
		$m = translate_stats_overview_metrics($source_lang_id, $lid, $entity_map);
		$pct = (float)$m['pct'];
		$card_href = '/admin.php?m=translations&tab=overview&lang_id=' . $lid;
		$bar_w = max(0, min(100, $pct));
		$bar_class = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
		$accent = $pct >= 80 ? '#28a745' : ($pct >= 50 ? '#ffc107' : '#dc3545');
		$pct_band = $pct >= 80 ? 'high' : ($pct >= 50 ? 'mid' : 'low');
		$pct_display = number_format((float)$pct, 1, ',', '');

		$content .= '<div class="col-xl-4 col-md-6 mb-4">';
		$content .= '<a href="' . htmlspecialchars($card_href, ENT_QUOTES, 'UTF-8') . '" class="tstats-overview-target-link d-block h-100 text-dark">';
		$content .= '<div class="card h-100 tstats-overview-target-card shadow-sm border-0" style="border-top:4px solid ' . $accent . ' !important;border:1px solid rgba(0,0,0,.08);">';
		$content .= '<div class="card-body">';
		$content .= '<div class="d-flex justify-content-between align-items-center mb-2">';
		$content .= '<div><h5 class="mb-1 text-dark">' . htmlspecialchars((string)$l['name']) . '</h5>';
		$content .= '<span class="badge badge-secondary">' . htmlspecialchars((string)$l['url']) . '</span></div>';
		$content .= '<div class="tstats-pct-box tstats-pct-box--' . $pct_band . '" title="Weighted completion"><span class="tstats-pct-box-num">' . htmlspecialchars($pct_display) . '</span><span class="tstats-pct-box-pct">%</span></div>';
		$content .= '</div>';
		$content .= '<div class="progress mb-3 tstats-progress"><div class="progress-bar ' . $bar_class . '" style="width:' . $bar_w . '%"></div></div>';

		$content .= '<div class="row no-gutters mx-n1">';
		$content .= '<div class="col-4 px-1"><div class="tstats-mini tstats-mini--content text-center">';
		$content .= '<div class="tstats-mini-label">Content</div>';
		$content .= '<div class="tstats-mini-value"><span class="tstats-mini-num">' . (int)$m['entities_pub'] . '</span><span class="tstats-mini-den">/' . (int)$m['entities_tot'] . '</span></div>';
		$content .= '<div class="tstats-mini-hint">published</div></div></div>';
		$content .= '<div class="col-4 px-1"><div class="tstats-mini tstats-mini--dict text-center">';
		$content .= '<div class="tstats-mini-label">Dictionary</div>';
		$content .= '<div class="tstats-mini-value"><span class="tstats-mini-num">' . (int)$m['sc_ok'] . '</span><span class="tstats-mini-den">/' . (int)$m['sc_total'] . '</span></div>';
		$content .= '<div class="tstats-mini-hint">translated</div></div></div>';
		$content .= '<div class="col-4 px-1"><div class="tstats-mini tstats-mini--menu text-center">';
		$content .= '<div class="tstats-mini-label">Menu</div>';
		$content .= '<div class="tstats-mini-value"><span class="tstats-mini-num">' . (int)$m['menu_ok'] . '</span><span class="tstats-mini-den">/' . (int)$m['menu_total'] . '</span></div>';
		$content .= '<div class="tstats-mini-hint">labels OK</div></div></div>';
		$content .= '</div>';

		$content .= '<div class="mt-3 pt-2 border-top tstats-footline"><span class="text-muted">Weighted progress:</span> ';
		$content .= '<strong class="text-dark">' . (int)$m['grand_done'] . '</strong><span class="text-muted"> / </span>' . (int)$m['grand_total'] . '</div>';
		$content .= '</div></div></a></div>';
	}
	$content .= '</div>';

	if (!$any_target_card) {
		$content .= '<div class="alert alert-warning mb-0">No target languages besides the source are enabled. Add more under <a href="/admin.php?m=translations_settings">Translations → Settings &amp; autopilot</a>.</div>';
	}
	$content .= '</div>';
} else {
	$content .= '<p class="mb-3"><a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations&tab=overview">&larr; All languages</a></p>';
	$content .= '<div class="row g-2 align-items-end mb-3">';
	$content .= '<div class="col-md-4"><label class="form-label">Target language</label><form method="get" action="/admin.php" class="d-flex">';
	$content .= '<input type="hidden" name="m" value="translations">';
	$content .= '<input type="hidden" name="tab" value="overview">';
	$content .= '<select class="form-select" name="lang_id" onchange="this.form.submit()">';
	foreach ($enabled_langs as $l) {
		$lid = (int)$l['id'];
		$sel = $lid === (int)$target_lang_id ? ' selected' : '';
		$content .= '<option value="' . $lid . '"' . $sel . '>' . htmlspecialchars((string)$l['name']) . ' (' . htmlspecialchars((string)$l['url']) . ')</option>';
	}
	$content .= '</select></form></div>';
	$content .= '</div>';

	$content .= '<div class="table-responsive"><table class="table table-sm">';
$content .= '<thead><tr><th>Entity</th><th>Total</th><th>Draft</th><th>Review</th><th>Published</th><th>Missing</th><th>Validator</th></tr></thead><tbody>';
// Links to drilldown list view
$stats_base = '/admin.php?m=translations&tab=overview&u=list&lang_id=' . (int)$target_lang_id . '&entity=';
foreach ($rows_entity as $r) {
	$content .= '<tr>';
	$content .= '<td>' . htmlspecialchars((string)$r['label']) . '</td>';
	$entity_q = urlencode((string)$r['entity']);
	$content .= '<td><a href="' . htmlspecialchars($stats_base . $entity_q . '&filter=all&n=1&sort=updated_at&dir=desc', ENT_QUOTES, 'UTF-8') . '">' . (int)$r['total'] . '</a></td>';
	$content .= '<td><a href="' . htmlspecialchars($stats_base . $entity_q . '&filter=draft&n=1&sort=updated_at&dir=desc', ENT_QUOTES, 'UTF-8') . '">' . (int)$r['draft'] . '</a></td>';
	$content .= '<td><a href="' . htmlspecialchars($stats_base . $entity_q . '&filter=review&n=1&sort=updated_at&dir=desc', ENT_QUOTES, 'UTF-8') . '">' . (int)$r['review'] . '</a></td>';
	$content .= '<td><a href="' . htmlspecialchars($stats_base . $entity_q . '&filter=published&n=1&sort=updated_at&dir=desc', ENT_QUOTES, 'UTF-8') . '">' . (int)$r['published'] . '</a></td>';
	$content .= '<td><a href="' . htmlspecialchars($stats_base . $entity_q . '&filter=missing&n=1&sort=updated_at&dir=desc', ENT_QUOTES, 'UTF-8') . '"><span class="text-danger">' . (int)$r['missing'] . '</span></a></td>';
	if ($is_source_lang) {
		$content .= '<td><span class="text-muted small">n/a</span></td>';
	} else {
		$valAction = '/admin.php?m=translations&tab=overview&u=validate_entity&lang_id=' . (int)$target_lang_id;
		$content .= '<td>';
		$content .= '<form method="post" action="' . htmlspecialchars($valAction, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;margin:0;">';
		$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars((string)$r['entity'], ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm(&quot;Validate and reset exact English copies to missing?&quot;)">Validate</button>';
		$content .= '</form>';
		$content .= '</td>';
	}
	$content .= '</tr>';
}
$content .= '</tbody></table></div>';

// System i18n: common.php + menu names (content_i18n for pages in menu)
$sys_stats_base = '/admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id;
$content .= '<h6 class="mt-4 mb-2">System resources (UI dictionary + menu)</h6>';
$content .= '<div class="table-responsive"><table class="table table-sm table-bordered">';
$content .= '<thead><tr><th>Scope</th><th>Keys / items</th><th>Translated</th><th>Missing / empty</th><th>Same as source</th><th>Actions</th></tr></thead><tbody>';
if ($is_source_lang) {
	$content .= '<tr><td>Common dictionary</td><td>' . (int)$sys_common_stats['total'] . '</td><td colspan="3">—</td>';
	$content .= '<td><a class="btn btn-sm btn-outline-secondary" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Open editor</a></td></tr>';
	$content .= '<tr><td>Menu labels</td><td>' . (int)$sys_menu_stats['total'] . '</td><td colspan="3">—</td>';
	$content .= '<td><a class="btn btn-sm btn-outline-secondary" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Open editor</a></td></tr>';
} else {
	$content .= '<tr>';
	$content .= '<td>Common dictionary</td>';
	$content .= '<td>' . (int)$sys_common_stats['total'] . '</td>';
	$content .= '<td>' . (int)$sys_common_stats['ok'] . '</td>';
	$content .= '<td><a href="' . htmlspecialchars($sys_stats_base . '&u=sys_common&filter=missing', ENT_QUOTES, 'UTF-8') . '"><span class="text-danger">' . (int)$sys_common_stats['missing'] . '</span></a></td>';
	$content .= '<td><a href="' . htmlspecialchars($sys_stats_base . '&u=sys_common&filter=same_as_source', ENT_QUOTES, 'UTF-8') . '">' . (int)$sys_common_stats['same_as_source'] . '</a></td>';
	$content .= '<td><a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($sys_stats_base . '&u=sys_common&filter=all', ENT_QUOTES, 'UTF-8') . '">Browse keys</a> ';
	$content .= '<a class="btn btn-sm btn-outline-secondary" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Edit</a></td>';
	$content .= '</tr>';
	$content .= '<tr>';
	$content .= '<td>Menu (header)</td>';
	$content .= '<td>' . (int)$sys_menu_stats['total'] . '</td>';
	$content .= '<td>' . (int)$sys_menu_stats['ok'] . '</td>';
	$content .= '<td><a href="' . htmlspecialchars($sys_stats_base . '&u=sys_menu&filter=missing', ENT_QUOTES, 'UTF-8') . '"><span class="text-danger">' . (int)$sys_menu_stats['missing_name'] . '</span></a></td>';
	$content .= '<td><a href="' . htmlspecialchars($sys_stats_base . '&u=sys_menu&filter=same_as_default', ENT_QUOTES, 'UTF-8') . '">' . (int)$sys_menu_stats['same_as_default'] . '</a></td>';
	$content .= '<td><a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($sys_stats_base . '&u=sys_menu&filter=all', ENT_QUOTES, 'UTF-8') . '">Browse items</a> ';
	$content .= '<a class="btn btn-sm btn-outline-secondary" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Edit</a></td>';
	$content .= '</tr>';
}
$content .= '</tbody></table></div>';

// Quick overall summary
$tot = 0; $pub = 0; $miss = 0; $draft = 0; $review = 0;
foreach ($rows_entity as $r) {
	$tot += (int)$r['total'];
	$draft += (int)$r['draft'];
	$review += (int)$r['review'];
	$pub += (int)$r['published'];
	$miss += (int)$r['missing'];
}
$content .= '<div class="mt-3">';
$content .= '<div class="alert alert-info mb-0 py-2">';
$content .= 'Overall: total <strong>' . (int)$tot . '</strong>, draft <strong>' . (int)$draft . '</strong>, review <strong>' . (int)$review . '</strong>, published <strong>' . (int)$pub . '</strong>, missing <strong>' . (int)$miss . '</strong>.';
$content .= '</div>';
$content .= '</div>';

$content .= '</div></div>';
}

$tstats_u = (string)($get['u'] ?? '');

// Drilldown: system dictionary keys (common.php)
if ($tstats_u === 'sys_common') {
	$page_name = 'Translate Stats: system dictionary';
	$filter_sc = isset($get['filter']) ? (string)$get['filter'] : 'all';
	$allowed_sc = array('all', 'missing', 'same_as_source', 'translated');
	if (!in_array($filter_sc, $allowed_sc, true)) {
		$filter_sc = 'all';
	}
	$rows_sc = array();
	foreach ($monitored_common_keys as $ck) {
		$rv = isset($ref_common_dict[$ck]) ? trim((string)$ref_common_dict[$ck]) : '';
		$tv = isset($tgt_common_dict[$ck]) ? trim((string)$tgt_common_dict[$ck]) : '';
		$cat = 'translated';
		if ($is_source_lang) {
			$cat = 'source';
		} elseif ($tv === '') {
			$cat = 'missing';
		} elseif ($rv !== '' && _tstats_norm($tv) === _tstats_norm($rv)) {
			$cat = 'same_as_source';
		}
		if ($filter_sc === 'all'
			|| ($filter_sc === 'missing' && $cat === 'missing')
			|| ($filter_sc === 'same_as_source' && $cat === 'same_as_source')
			|| ($filter_sc === 'translated' && $cat === 'translated')
		) {
			$rows_sc[] = array('key' => $ck, 'ref' => $rv, 'tgt' => $tv, 'cat' => $cat);
		}
	}
	$trim_preview = function ($s, $max) {
		$s = (string)$s;
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
			return (mb_strlen($s) > $max) ? mb_substr($s, 0, $max) . '…' : $s;
		}
		return (strlen($s) > $max) ? substr($s, 0, $max) . '…' : $s;
	};
	$content = '<div class="card"><div class="card-body">';
	$content .= '<h5 class="mb-2">System dictionary (<code>common.php</code>)</h5>';
	$nav_sc = '';
	foreach (array('all' => 'All', 'missing' => 'Missing / empty', 'same_as_source' => 'Same as source', 'translated' => 'Different from source') as $fk => $lab) {
		$href = htmlspecialchars($sys_stats_base . '&u=sys_common&filter=' . $fk, ENT_QUOTES, 'UTF-8');
		$bold = ($filter_sc === $fk) ? ' fw-bold' : '';
		$nav_sc .= ' <a class="' . trim($bold) . '" href="' . $href . '">' . htmlspecialchars($lab) . '</a>';
	}
	$content .= '<div class="mb-3 small">' . $nav_sc . '</div>';
	if (!$is_source_lang) {
		$form_action = '/admin.php?m=translations&tab=overview&amp;lang_id=' . (int)$target_lang_id . '&amp;u=sys_common&amp;filter=' . rawurlencode($filter_sc);
		$content .= '<form method="post" action="' . htmlspecialchars($form_action, ENT_QUOTES, 'UTF-8') . '" id="sys-common-dict-form">';
		$content .= '<input type="hidden" name="m" value="translations" />';
		$content .= '<input type="hidden" name="tab" value="overview" />';
		$content .= '<input type="hidden" name="lang_id" value="' . (int)$target_lang_id . '" />';
		$content .= '<input type="hidden" name="u" value="sys_common" />';
		$content .= '<input type="hidden" name="filter" value="' . htmlspecialchars($filter_sc, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="return_filter" value="' . htmlspecialchars($filter_sc, ENT_QUOTES, 'UTF-8') . '" />';
	}
	$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr>';
	if (!$is_source_lang) {
		$content .= '<th style="width:40px;"><input type="checkbox" id="sysCommonSelAll" title="Select visible rows" /></th>';
	}
	$content .= '<th>Key</th><th>Source (lang ' . (int)$source_lang_id . ')</th>';
	$content .= $is_source_lang ? '' : '<th>Target (lang ' . (int)$target_lang_id . ')</th>';
	$content .= '<th>Status</th></tr></thead><tbody>';
	if (empty($rows_sc)) {
		$colspan = $is_source_lang ? 3 : 5;
		$content .= '<tr><td colspan="' . $colspan . '" class="text-muted">No keys in this filter.</td></tr>';
	} else {
		foreach ($rows_sc as $r) {
			$badge = 'secondary';
			if ($r['cat'] === 'missing') {
				$badge = 'danger';
			} elseif ($r['cat'] === 'same_as_source') {
				$badge = 'warning';
			} elseif ($r['cat'] === 'translated') {
				$badge = 'success';
			} elseif ($r['cat'] === 'source') {
				$badge = 'info';
			}
			$content .= '<tr>';
			if (!$is_source_lang) {
				$content .= '<td class="text-center"><input class="sys-common-cb" type="checkbox" name="dict_keys[]" value="' . htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') . '" /></td>';
			}
			$content .= '<td><code>' . htmlspecialchars($r['key']) . '</code></td>';
			$content .= '<td class="small text-break">' . htmlspecialchars($trim_preview($r['ref'], 200)) . '</td>';
			if (!$is_source_lang) {
				$ta_name = 'manual_common[' . htmlspecialchars($r['key'], ENT_QUOTES, 'UTF-8') . ']';
				$content .= '<td class="small p-1" style="min-width:220px;"><textarea name="' . $ta_name . '" rows="3" class="form-control form-control-sm">' . htmlspecialchars($r['tgt']) . '</textarea></td>';
			}
			$content .= '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($r['cat']) . '</span></td></tr>';
		}
	}
	$content .= '</tbody></table></div>';
	if (!$is_source_lang) {
		$content .= '<div class="d-flex flex-wrap gap-2 mt-3 mb-2 align-items-center">';
		$content .= '<button type="submit" name="sys_common_manual_save" value="1" class="btn btn-outline-primary btn-sm">Save manual edits</button>';
		$content .= '<button type="submit" name="queue_sys_common_translate" value="1" class="btn btn-primary btn-sm">Queue translate (selected or all missing)</button>';
		$content .= '<button type="submit" name="sys_common_live_run" value="1" class="btn btn-success btn-sm" onclick="return confirm(\'Run AI translation for these keys now in this request? This may take several minutes.\');">Live run now</button>';
		$content .= '</div>';
		$content .= '</form>';
		$content .= '<script>
			document.addEventListener("DOMContentLoaded", function(){
				var m = document.getElementById("sysCommonSelAll");
				if (!m) return;
				m.addEventListener("change", function(){
					document.querySelectorAll("input.sys-common-cb").forEach(function(cb){ cb.checked = m.checked; });
				});
			});
		</script>';
	}
	$content .= '<a class="btn btn-outline-secondary btn-sm mt-2" href="/admin.php?m=translations&tab=overview&amp;lang_id=' . (int)$target_lang_id . '">Back to stats</a> ';
	$content .= '<a class="btn btn-outline-primary btn-sm mt-2" href="/admin.php?m=translations&amp;tab=monitor&amp;mtab=jobs">Job queue</a> ';
	$content .= '<a class="btn btn-primary btn-sm mt-2" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Languages / i18n</a>';
	$content .= '</div></div>';
} elseif ($tstats_u === 'sys_menu') {
	$page_name = 'Translate Stats: menu labels';
	$filter_sm = isset($get['filter']) ? (string)$get['filter'] : 'all';
	if (!in_array($filter_sm, array('all', 'missing', 'same_as_default', 'ok'), true)) {
		$filter_sm = 'all';
	}
	$menu_ids_dr = array_map(function ($p) {
		return (int)$p['id'];
	}, $canonical_menu_pages);
	$menu_i18n_dr = array();
	if (!$is_source_lang && $target_lang_id > 0 && !empty($menu_ids_dr)) {
		$mr_dr = mysql_select("
			SELECT entity_id, name, url
			FROM content_i18n
			WHERE entity='pages'
			  AND lang_id=" . (int)$target_lang_id . "
			  AND entity_id IN (" . implode(',', $menu_ids_dr) . ")
		", 'rows') ?: array();
		foreach ($mr_dr as $mr) {
			$menu_i18n_dr[(int)$mr['entity_id']] = $mr;
		}
	}
	$rows_sm = array();
	foreach ($canonical_menu_pages as $mp) {
		$pid = (int)$mp['id'];
		$canon_name = isset($mp['name']) ? (string)$mp['name'] : '';
		$canon_url = isset($mp['url']) ? trim((string)$mp['url'], '/') : '';
		$tr_name = '';
		$tr_url = '';
		if (!$is_source_lang && isset($menu_i18n_dr[$pid])) {
			$tr_name = trim((string)$menu_i18n_dr[$pid]['name']);
			$tr_url = isset($menu_i18n_dr[$pid]['url']) ? trim((string)$menu_i18n_dr[$pid]['url'], '/') : '';
		}
		$cat = 'ok';
		if ($is_source_lang) {
			$cat = 'source';
		} elseif ($tr_name === '') {
			$cat = 'missing';
		} elseif (_tstats_norm($canon_name) !== '' && _tstats_norm($tr_name) === _tstats_norm($canon_name)) {
			$cat = 'same_as_default';
		}
		$include = ($filter_sm === 'all')
			|| ($filter_sm === 'missing' && $cat === 'missing')
			|| ($filter_sm === 'same_as_default' && $cat === 'same_as_default')
			|| ($filter_sm === 'ok' && $cat === 'ok');
		if ($include) {
			$rows_sm[] = array(
				'id' => $pid,
				'module' => isset($mp['module']) ? (string)$mp['module'] : '',
				'canon_name' => $canon_name,
				'canon_url' => $canon_url,
				'tr_name' => $tr_name,
				'tr_url' => $tr_url,
				'cat' => $cat,
			);
		}
	}
	$content = '<div class="card"><div class="card-body">';
	$content .= '<h5 class="mb-2">Menu labels (<code>content_i18n</code> / pages)</h5>';
	$nav_sm = '';
	foreach (array('all' => 'All', 'missing' => 'Missing (empty i18n name)', 'same_as_default' => 'Same as default name', 'ok' => 'Translated') as $fk => $lab) {
		$href = htmlspecialchars($sys_stats_base . '&u=sys_menu&filter=' . $fk, ENT_QUOTES, 'UTF-8');
		$bold = ($filter_sm === $fk) ? ' fw-bold' : '';
		$nav_sm .= ' <a class="' . trim($bold) . '" href="' . $href . '">' . htmlspecialchars($lab) . '</a>';
	}
	$content .= '<div class="mb-3 small">' . $nav_sm . '</div>';
	$content .= '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Module</th><th>Default name</th><th>URL</th>';
	$content .= $is_source_lang ? '' : '<th>i18n name</th><th>i18n URL</th>';
	$content .= '<th>Status</th></tr></thead><tbody>';
	if (empty($rows_sm)) {
		$colspan = $is_source_lang ? 5 : 7;
		$content .= '<tr><td colspan="' . $colspan . '" class="text-muted">No items in this filter.</td></tr>';
	} else {
		foreach ($rows_sm as $r) {
			$badge = 'secondary';
			if ($r['cat'] === 'missing') {
				$badge = 'danger';
			} elseif ($r['cat'] === 'same_as_default') {
				$badge = 'warning';
			} elseif ($r['cat'] === 'ok') {
				$badge = 'success';
			} elseif ($r['cat'] === 'source') {
				$badge = 'info';
			}
			$content .= '<tr><td>' . (int)$r['id'] . '</td><td class="small">' . htmlspecialchars($r['module']) . '</td>';
			$content .= '<td>' . htmlspecialchars($r['canon_name']) . '</td><td class="small text-muted">' . htmlspecialchars($r['canon_url']) . '</td>';
			if (!$is_source_lang) {
				$content .= '<td>' . htmlspecialchars($r['tr_name']) . '</td><td class="small text-muted">' . htmlspecialchars($r['tr_url']) . '</td>';
			}
			$content .= '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($r['cat']) . '</span></td></tr>';
		}
	}
	$content .= '</tbody></table></div>';
	$content .= '<a class="btn btn-outline-secondary btn-sm mt-2" href="/admin.php?m=translations&tab=overview&amp;lang_id=' . (int)$target_lang_id . '">Back to stats</a> ';
	$content .= '<a class="btn btn-primary btn-sm mt-2" href="/admin.php?m=languages_json&amp;lang_id=' . (int)$target_lang_id . '">Edit menu</a>';
	$content .= '</div></div>';
}

// Drilldown list mode (clickable stats numbers)
// Note: admin pagination helper drops `u` param from query string,
// so we also enable list mode when `entity` is provided.
if ((string)($get['u'] ?? '') === 'list' || !empty($get['entity'])) {
	$page_name = 'Translate Stats: drilldown';

	$entity = isset($get['entity']) ? (string)$get['entity'] : '';
	$filter = isset($get['filter']) ? (string)$get['filter'] : 'all';
	$n = isset($get['n']) ? (int)$get['n'] : 1;
	if ($n < 1) $n = 1;

	$allowedEntities = array_keys($entity_map);
	if (!in_array($entity, $allowedEntities, true)) {
		$content = '<div class="alert alert-danger">Bad entity.</div>';
	} else {
		if ($is_source_lang) {
			// Source language: content lives in main tables, so we disable bulk actions.
			$filter = 'all';
		}

		$allowedFilters = array('draft','review','published','missing','all');
		if (!in_array($filter, $allowedFilters, true)) $filter = 'all';

		$table = $entity_map[$entity]['table'];
		$entityLabel = $entity_map[$entity]['label'];

		// Sorting
		$sort_by = isset($get['sort']) ? (string)$get['sort'] : 'updated_at';
		$dir = strtolower(isset($get['dir']) ? (string)$get['dir'] : 'desc');
		if (!in_array($dir, array('asc','desc'), true)) $dir = 'desc';
		$allowedSort = array('updated_at','id','title');
		if (!in_array($sort_by, $allowedSort, true)) $sort_by = 'updated_at';

		$allowedPerPage = array(50, 100, 200, 500);
		$perPage = isset($get['per_page']) ? (int)$get['per_page'] : 50;
		if (!in_array($perPage, $allowedPerPage, true)) $perPage = 50;

		$base_url = '/admin.php?m=translations&tab=overview&u=list&lang_id=' . (int)$target_lang_id
			. '&entity=' . urlencode($entity)
			. '&filter=' . urlencode($filter)
			. '&sort=' . urlencode($sort_by)
			. '&dir=' . urlencode($dir)
			. '&per_page=' . (int)$perPage;

		$filterLabels = array(
			'all' => 'All',
			'draft' => 'Draft',
			'review' => 'Review',
			'published' => 'Published',
			'missing' => 'Missing',
		);

		$filtersNav = '';
		foreach ($filterLabels as $f => $lab) {
			$u = $base_url . '&filter=' . urlencode($f) . '&n=1';
			$active = ($filter === $f) ? ' style="opacity:1;"' : ' style="opacity:0.75;"';
			$filtersNav .= ' <a class="btn btn-outline-secondary btn-sm" href="' . htmlspecialchars($u, ENT_QUOTES, 'UTF-8') . '"' . $active . '>' . htmlspecialchars($lab) . '</a>';
		}

		// Queue settings
		$source_lang_id_for_queue = (int)$source_lang_id;
		$chunk_max_len = isset($cfg['chunk_max_len']) ? (int)$cfg['chunk_max_len'] : 2500;

		// Bulk actions handler
		if ((string)($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
			$action = isset($_POST['bulk_action']) ? (string)$_POST['bulk_action'] : '';
			$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_values(array_filter(array_map('intval', $_POST['ids']))) : array();

			if (!empty($ids) && in_array($action, array('publish','queue_translate','delete_translate'), true)) {
				$in = implode(',', $ids);
				if ($action === 'delete_translate') {
					if (!$is_source_lang) {
						// Mass reset translation to "missing": delete the content_i18n row for this language.
						mysql_fn('query', "DELETE FROM content_i18n
							WHERE entity='" . mysql_res($entity) . "'
							  AND lang_id=" . (int)$target_lang_id . "
							  AND entity_id IN (" . $in . ")
						");
						$_SESSION['admin_flash_success'] = 'Delete translate selected: OK (' . (int)count($ids) . ' item(s))';
					} else {
						$_SESSION['admin_flash_info'] = 'Source language: delete translate is not applicable.';
					}
					header('Location: ' . $base_url . '&n=1');
					exit;
				}
				if ($action === 'publish') {
					$now = date('Y-m-d H:i:s');
					if (!$is_source_lang) {
						mysql_fn('query', "UPDATE content_i18n
							SET status='published', updated_at='" . mysql_res($now) . "'
							WHERE entity='" . mysql_res($entity) . "'
							  AND lang_id=" . (int)$target_lang_id . "
							  AND entity_id IN (" . $in . ")
							  AND status IN ('draft','review')
						");
						$_SESSION['admin_flash_success'] = 'Publish selected translations: OK';
					} else {
						$_SESSION['admin_flash_info'] = 'Source language: publish is not applicable.';
					}
					header('Location: ' . $base_url . '&n=1');
					exit;
				}

				if ($action === 'queue_translate') {
					if (!$is_source_lang) {
						require_once(ROOT_DIR . 'functions/admin_jobs.php');

						// Queue translate for selected rows even if dst translation is currently missing.
						// job_runner_translations.php will insert/update the dst row and set status='draft'.
						$eligible_ids = $ids;

						$queued = 0;
						foreach ($eligible_ids as $eid) {
							$jid = admin_jobs_enqueue('translations', 'translate', array(
								'entity' => (string)$entity,
								'entity_id' => (int)$eid,
								'src_lang' => (int)$source_lang_id_for_queue,
								'dst_lang' => (int)$target_lang_id,
								'fields' => array('title','description','content'),
								'chunk_max_len' => (int)$chunk_max_len,
								'order_id' => 0,
								'candidate_id' => 0,
							), array('priority' => 0));
							if ($jid) $queued++;
						}

						$_SESSION['admin_flash_success'] = 'Queue translate selected: queued ' . (int)$queued . ' job(s).';
					} else {
						$_SESSION['admin_flash_info'] = 'Source language: queue translate is not applicable.';
					}

					header('Location: ' . $base_url . '&n=1');
					exit;
				}
			}
		}

		// Build list SQL
		$display_where = _tstats_table_display_where($table);
		$where_filter = '';
		if (!$is_source_lang) {
			if ($filter === 'draft') $where_filter = " AND ci.status='draft' ";
			elseif ($filter === 'review') $where_filter = " AND ci.status='review' ";
			elseif ($filter === 'published') $where_filter = " AND ci.status='published' ";
			elseif ($filter === 'missing') $where_filter = " AND (ci.status IS NULL OR ci.status='' OR ci.status='missing') ";
		}

		$total = 0;
		if ($is_source_lang) {
			$rowTotal = mysql_select("
				SELECT COUNT(*) AS c
				FROM `" . mysql_res($table) . "` t
				WHERE 1 " . $display_where . "
			", 'row');
			$total = $rowTotal && isset($rowTotal['c']) ? (int)$rowTotal['c'] : 0;
		} else {
			$rowTotal = mysql_select("
				SELECT COUNT(*) AS c
				FROM `" . mysql_res($table) . "` t
				LEFT JOIN content_i18n ci
					ON ci.entity='" . mysql_res($entity) . "'
				   AND ci.entity_id=t.id
				   AND ci.lang_id=" . (int)$target_lang_id . "
				WHERE 1 " . $display_where . " " . $where_filter . "
			", 'row');
			$total = $rowTotal && isset($rowTotal['c']) ? (int)$rowTotal['c'] : 0;
		}

		$maxPage = $perPage > 0 ? (int)max(1, (int)ceil((float)$total / (float)$perPage)) : 1;
		if ($n > $maxPage) $n = $maxPage;
		$offset = ($n - 1) * $perPage;

		// Query rows
		$order_sql = '';
		if ($sort_by === 'id') {
			$order_sql = ' ORDER BY t.id ' . ($dir === 'asc' ? 'ASC' : 'DESC') . ' ';
		} elseif ($sort_by === 'title') {
			// display_title alias is defined in select
			$order_sql = ' ORDER BY display_title ' . ($dir === 'asc' ? 'ASC' : 'DESC') . ', t.id DESC ';
		} else {
			// updated_at sort
			if ($dir === 'asc') {
				$order_sql = " ORDER BY (ci.updated_at IS NULL) ASC, ci.updated_at ASC, t.id ASC ";
			} else {
				$order_sql = " ORDER BY (ci.updated_at IS NULL) ASC, ci.updated_at DESC, t.id DESC ";
			}
		}

		$rows = array();
		if ($is_source_lang) {
			$sql = "
				SELECT
					t.id,
					t.url AS url_slug,
					COALESCE(NULLIF(t.title,''), NULLIF(t.name,'')) AS display_title,
					'published' AS i18n_status,
					(SELECT NULL) AS updated_at
				FROM `" . mysql_res($table) . "` t
				WHERE 1 " . $display_where . "
				" . ($sort_by === 'updated_at' ? " ORDER BY t.id DESC " : $order_sql) . "
				LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
			";
			$rows = mysql_select($sql, 'rows') ?: array();
		} else {
			// Select base+ci columns (use COALESCE to show something even when translation is missing)
			$sql = "
				SELECT
					t.id,
					COALESCE(NULLIF(ci.url,''), NULLIF(t.url,'')) AS url_slug,
					COALESCE(NULLIF(ci.title,''), NULLIF(ci.name,''), NULLIF(t.title,''), NULLIF(t.name,'')) AS display_title,
					COALESCE(NULLIF(ci.status,''),'missing') AS i18n_status,
					ci.updated_at AS updated_at
				FROM `" . mysql_res($table) . "` t
				LEFT JOIN content_i18n ci
					ON ci.entity='" . mysql_res($entity) . "'
				   AND ci.entity_id=t.id
				   AND ci.lang_id=" . (int)$target_lang_id . "
				WHERE 1 " . $display_where . " " . $where_filter . "
				" . $order_sql . "
				LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
			";
			$rows = mysql_select($sql, 'rows') ?: array();
		}

		// Render
		$content = '<div class="card"><div class="card-body">';
		$content .= '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">';
		$content .= '<div><h5 class="mb-0">Entity: ' . htmlspecialchars($entityLabel) . '</h5>';
		$content .= '<div class="text-muted small">Language: ' . htmlspecialchars((string)$target_lang_id) . ' • Filter: ' . htmlspecialchars((string)$filter) . '</div></div>';
		$content .= '<div>' . $filtersNav . '</div>';
		$content .= '</div>';

		$content .= '<form method="get" action="/admin.php" class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-3">';
		$content .= '<input type="hidden" name="m" value="translations" />';
		$content .= '<input type="hidden" name="tab" value="overview" />';
		$content .= '<input type="hidden" name="u" value="list" />';
		$content .= '<input type="hidden" name="lang_id" value="' . (int)$target_lang_id . '" />';
		$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="filter" value="' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="sort" value="' . htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="dir" value="' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="n" value="1" />';
		$content .= '<span class="text-muted small me-1">Rows per page:</span>';
		foreach ($allowedPerPage as $pp) {
			$pp = (int)$pp;
			$rid = 'tstats_per_page_' . $pp;
			$chk = ($perPage === $pp) ? ' checked' : '';
			$content .= '<div class="form-check form-check-inline mb-0">';
			$content .= '<input class="form-check-input" type="radio" name="per_page" id="' . htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') . '" value="' . $pp . '"' . $chk . ' onchange="this.form.submit()" />';
			$content .= '<label class="form-check-label" for="' . htmlspecialchars($rid, ENT_QUOTES, 'UTF-8') . '">' . $pp . '</label>';
			$content .= '</div>';
		}
		$content .= '</form>';

		if ($is_source_lang) {
		}

		$sortUrl = function($sb, $nd) use ($base_url, $n) {
			return $base_url . '&sort=' . urlencode($sb) . '&dir=' . urlencode($nd) . '&n=' . (int)$n;
		};

		$cap_updated = 'Updated';
		$cap_title = 'Title';

		$content .= '<form method="post" action="' . htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') . '&n=' . (int)$n . '">';
		$content .= '<input type="hidden" name="m" value="translations" />';
		$content .= '<input type="hidden" name="tab" value="overview" />';
		$content .= '<input type="hidden" name="u" value="list" />';
		$content .= '<input type="hidden" name="lang_id" value="' . (int)$target_lang_id . '" />';
		$content .= '<input type="hidden" name="entity" value="' . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="filter" value="' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="sort" value="' . htmlspecialchars($sort_by, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<input type="hidden" name="dir" value="' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '" />';

		$content .= '<div class="table-responsive"><table class="table table-sm align-middle">';
		$content .= '<thead><tr>';
		$content .= '<th style="width:40px;"><input type="checkbox" id="trSelAll" /></th>';
		$content .= '<th><a href="' . htmlspecialchars($sortUrl('id', $dir === 'asc' ? 'desc' : 'asc'), ENT_QUOTES, 'UTF-8') . '">ID</a></th>';
		$content .= '<th><a href="' . htmlspecialchars($sortUrl('title', $dir === 'asc' ? 'desc' : 'asc'), ENT_QUOTES, 'UTF-8') . '">Title</a></th>';
		$content .= '<th>URL</th>';
		$content .= '<th><a href="' . htmlspecialchars($sortUrl('updated_at', $dir === 'asc' ? 'desc' : 'asc'), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($cap_updated) . '</a></th>';
		$content .= '<th>Status</th>';
		$content .= '</tr></thead>';
		$content .= '<tbody>';

		if (empty($rows)) {
			$content .= '<tr><td colspan="6" class="text-muted">No items.</td></tr>';
		} else {
			foreach ($rows as $row) {
				$id = (int)($row['id'] ?? 0);
				$urlSlug = (string)($row['url_slug'] ?? '');
				$dt = (string)($row['updated_at'] ?? '');
				$status = (string)($row['i18n_status'] ?? 'missing');
				$displayTitle = (string)($row['display_title'] ?? '');

				$selectable = !$is_source_lang;

				$badgeClass = 'badge bg-secondary';
				if ($status === 'draft') $badgeClass = 'badge bg-warning text-dark';
				elseif ($status === 'review') $badgeClass = 'badge bg-primary';
				elseif ($status === 'published') $badgeClass = 'badge bg-success';
				elseif ($status === 'missing') $badgeClass = 'badge bg-danger';

				$content .= '<tr>';
				$content .= '<td><input class="tstats_cb" type="checkbox" name="ids[]" value="' . $id . '"' . ($selectable ? '' : ' disabled') . ' /></td>';
				$content .= '<td>' . $id . '</td>';
				$content .= '<td>' . htmlspecialchars($displayTitle) . '</td>';
				$content .= '<td><span class="text-muted small">' . htmlspecialchars($urlSlug) . '</span></td>';
				$content .= '<td>' . htmlspecialchars($dt) . '</td>';
				$content .= '<td><span class="' . htmlspecialchars($badgeClass) . '">' . htmlspecialchars($status) . '</span></td>';
				$content .= '</tr>';
			}
		}

		$content .= '</tbody></table></div>';

		if (!$is_source_lang) {
			$content .= '<div class="d-flex gap-2 flex-wrap mt-3">';
			// In "Missing" there is nothing to publish yet.
			if ($filter !== 'missing') {
				$content .= '<button type="submit" name="bulk_action" value="publish" class="btn btn-success btn-sm">Publish selected</button>';
			}
			$content .= '<button type="submit" name="bulk_action" value="queue_translate" class="btn btn-primary btn-sm">Queue translate selected</button>';
			$content .= '<button type="submit" name="bulk_action" value="delete_translate" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Delete translations for selected items in this language? This will reset them to missing.\');">Delete translate selected</button>';
			$content .= '</div>';
		}

		$content .= '</form>';

		// Select-all JS (skip disabled)
		$content .= '<script>
			document.addEventListener("DOMContentLoaded", function(){
				var master = document.getElementById("trSelAll");
				if(!master) return;
				master.addEventListener("change", function(){
					var boxes = document.querySelectorAll("input.tstats_cb");
					boxes.forEach(function(cb){ if(!cb.disabled) cb.checked = master.checked; });
				});
			});
		</script>';

		// Pagination (custom for drilldown)
		// We can't use html_render('pagination/default') here because its pagination_link() helper
		// unsets `u` from the query string, which breaks our drilldown view routing.
		if ($total > 0) {
			$count_max = 7;
			$per = (int)$perPage;
			$count = $per > 0 ? (int)ceil((float)$total / (float)$per) : 1;
			if ($count < 1) $count = 1;

			$list = array();
			if ($per > 0 && $per < $total) {
				if ($count <= $count_max) {
					for ($i = 1; $i <= $count; $i++) $list[] = array($i, $i);
				} else {
					if ($n < ($e = $count_max - 2)) {
						for ($i = 1; $i <= $e; $i++) $list[] = array($i, $i);
						$list[] = array(ceil(($count + $e) / 2), 0);
						$list[] = array($count, $count);
					} elseif ($n > ($s = $count - $count_max + 2 + 1)) {
						$list[] = array(1, 1);
						$list[] = array(ceil(($s + 1) / 2), 0);
						for ($i = $s; $i <= $count; $i++) $list[] = array($i, $i);
					} else {
						$s = $n - ceil(($count_max - 4 - 1) / 2);
						$e = $n + floor(($count_max - 4 - 1) / 2);
						$list[] = array(1, 1);
						$list[] = array((ceil(($s + 1) / 2)), 0);
						for ($i = $s; $i <= $e; $i++) $list[] = array($i, $i);
						$list[] = array(ceil(($count + $e) / 2), 0);
						$list[] = array($count, $count);
					}
				}
			} else {
				$list[] = array(1, 1);
			}

			$content .= '<div class="pagination pagination-bottom mt-3"><nav aria-label="Pagination"><ul class="pagination pagination-sm pagination-rounded mb-0">';
			foreach ($list as $v) {
				$page = (int)($v[0] ?? 1);
				$isEllipsis = !empty($v[1]) ? false : true;
				$isEllipsis = ((int)($v[1] ?? 1) === 0);
				if ($isEllipsis) {
					$label = '…';
					$content .= '<li class="page-item"><span class="page-link">' . $label . '</span></li>';
					continue;
				}
				$link = $base_url . '&n=' . $page;
				if ($page === (int)$n) $content .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
				else $content .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . $page . '</a></li>';
			}
			$content .= '</ul></nav></div>';
		}

		$content .= '<div class="mt-3">';
		$content .= '<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=translations&tab=overview&lang_id=' . (int)$target_lang_id . '">Back to stats</a>';
		$content .= '</div>';

		$content .= '</div></div>';
	}
}

