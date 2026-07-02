<?php
/**
 * One-time legacy content cleanup (v1): Aviator site structure vs old sports/news CMS rudiments.
 *
 * - Pages: keep canonical URLs + home (module=index); others are hidden (display=0, menu=0).
 * - content_i18n for hidden pages: rows removed so they do not appear in translation monitor.
 * - Guides: DELETE rows whose category is not in the Aviator guide slug set; purge their content_i18n.
 *
 * Does NOT touch blog / games / casino_articles (too risky without a manual inventory).
 * Pages are soft-hidden (display=0), not DROP — safe for nested-set trees. Extend $allowed_urls for custom slugs.
 * Run separately: php site/scripts/run_legacy_cleanup_v1.php (not tied to run_migrate_BD.php).
 * Re-run: DELETE FROM variables WHERE `key`='migration_legacy_cleanup_v1'; or php run_legacy_cleanup_v1.php --force
 *
 * @return array{ok:bool, summary:string, hidden_pages:int, deleted_guides:int, removed_i18n_pages:int, removed_i18n_guides:int, keep_page_count:int, at:string}
 */
function migrate_legacy_cleanup_v1_apply() {
	$allowed_urls = array(
		'',
		'index',
		'blog',
		'casinos',
		'casino',
		'demo',
		'download',
		'predictor',
		'games',
		'guides',
		'jeux',
		'about-us',
		'terms-and-conditions',
		'privacy-policy',
		'responsible-gambling',
		// bd_pages_upgrade donor slugs
		'game-analysis',
		'how-to-win',
		'signals',
		'bonus',
		// Current guides landing children (slug = page url segment)
		'analysis',
		'bonus',
		'signals',
		'crash-gambling',
	);
	$norm = function ($u) {
		$u = strtolower(trim((string)$u, "/ \t\n\r\0\x0B"));
		return $u;
	};
	$allowed = array();
	foreach ($allowed_urls as $u) {
		$allowed[$norm($u)] = true;
	}

	$page_cols = array();
	$pc = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
	if (is_array($pc)) {
		foreach ($pc as $c) {
			if (!empty($c['Field'])) {
				$page_cols[(string)$c['Field']] = true;
			}
		}
	}
	$has_url1 = isset($page_cols['url1']);
	$has_menu2 = isset($page_cols['menu2']);

	$sql_pages = $has_url1
		? "SELECT id, module, url, url1 FROM `pages`"
		: "SELECT id, module, url FROM `pages`";

	$keep_page_ids = array();
	$pages = mysql_select($sql_pages, 'rows');
	if (!is_array($pages)) {
		$pages = array();
	}
	foreach ($pages as $p) {
		$id = (int)$p['id'];
		if ($id <= 0) {
			continue;
		}
		$mod = isset($p['module']) ? (string)$p['module'] : '';
		if ($mod === 'index') {
			$keep_page_ids[$id] = true;
			continue;
		}
		$u = isset($p['url']) ? $norm($p['url']) : '';
		$u1 = ($has_url1 && isset($p['url1'])) ? $norm($p['url1']) : '';
		$match_u = isset($allowed[$u]);
		$match_u1 = ($u1 !== '' && isset($allowed[$u1]));
		if ($match_u || $match_u1) {
			$keep_page_ids[$id] = true;
		}
	}

	$all_ids = array();
	foreach ($pages as $p) {
		if (!empty($p['id'])) {
			$all_ids[] = (int)$p['id'];
		}
	}
	$drop_page_ids = array();
	foreach ($all_ids as $pid) {
		if ($pid > 0 && !isset($keep_page_ids[$pid])) {
			$drop_page_ids[] = $pid;
		}
	}

	$hidden = 0;
	$rm_i18n_pages = 0;
	if ($drop_page_ids !== array()) {
		$in = implode(',', array_map('intval', $drop_page_ids));
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$cpre = mysql_select("SELECT COUNT(*) AS c FROM `content_i18n` WHERE entity='pages' AND entity_id IN (" . $in . ")", 'row');
			$rm_i18n_pages = $cpre && isset($cpre['c']) ? (int)$cpre['c'] : 0;
			mysql_fn('query', "DELETE FROM `content_i18n` WHERE entity='pages' AND entity_id IN (" . $in . ")");
		}
		$hide_set = 'display=0, menu=0';
		if ($has_menu2) {
			$hide_set .= ', menu2=0';
		}
		mysql_fn('query', "UPDATE `pages` SET " . $hide_set . " WHERE id IN (" . $in . ")");
		$hidden = count($drop_page_ids);
	}

	$allowed_guide_cats = array('analysis', 'bonus', 'how-to-win', 'signals', 'crash-gambling');
	$cat_in = "'" . implode("','", array_map('mysql_res', $allowed_guide_cats)) . "'";
	$bad_guides = array();
	if (@mysql_select("SHOW TABLES LIKE 'guides'", 'num_rows') > 0) {
		$bad_guides = mysql_select("
			SELECT id FROM `guides`
			WHERE category NOT IN (" . $cat_in . ")
			   OR category IS NULL
			   OR category = ''
		", 'rows') ?: array();
	}
	$drop_guide_ids = array();
	foreach ($bad_guides as $g) {
		if (!empty($g['id'])) {
			$drop_guide_ids[] = (int)$g['id'];
		}
	}
	$deleted_g = 0;
	$rm_i18n_g = 0;
	if ($drop_guide_ids !== array()) {
		$gin = implode(',', array_map('intval', $drop_guide_ids));
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$cg = mysql_select("SELECT COUNT(*) AS c FROM `content_i18n` WHERE entity='guides' AND entity_id IN (" . $gin . ")", 'row');
			$rm_i18n_g = $cg && isset($cg['c']) ? (int)$cg['c'] : 0;
			mysql_fn('query', "DELETE FROM `content_i18n` WHERE entity='guides' AND entity_id IN (" . $gin . ")");
		}
		mysql_fn('query', "DELETE FROM `guides` WHERE id IN (" . $gin . ")");
		$deleted_g = count($drop_guide_ids);
	}

	$summary = 'hidden_pages=' . (int)$hidden . ' deleted_guides=' . (int)$deleted_g
		. ' removed_i18n_pages=' . (int)$rm_i18n_pages . ' removed_i18n_guides=' . (int)$rm_i18n_g;

	return array(
		'ok' => true,
		'summary' => $summary,
		'hidden_pages' => (int)$hidden,
		'deleted_guides' => (int)$deleted_g,
		'removed_i18n_pages' => (int)$rm_i18n_pages,
		'removed_i18n_guides' => (int)$rm_i18n_g,
		'keep_page_count' => count($keep_page_ids),
		'at' => date('c'),
	);
}
