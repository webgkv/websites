<?php
/**
 * Games categories storage in DB (instead of hardcoded arrays in code).
 * Table: games_categories (slug + localized names by legacy language columns)
 */

function games_categories_ensure_table() {
	if (@mysql_select("SHOW TABLES LIKE 'games_categories'", 'num_rows') > 0) {
		return;
	}

	mysql_fn('query', "
		CREATE TABLE `games_categories` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`slug` varchar(64) NOT NULL DEFAULT '',
			`name` varchar(255) NOT NULL DEFAULT '',
			`name2` varchar(255) NOT NULL DEFAULT '',
			`name3` varchar(255) NOT NULL DEFAULT '',
			`position` int(11) NOT NULL DEFAULT 0,
			`display` tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`),
			UNIQUE KEY `slug` (`slug`),
			KEY `display` (`display`),
			KEY `position` (`position`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	");

	$seed = array(
		array('slug' => 'crash',     'name' => 'Crash',      'name2' => 'Crash',      'name3' => 'Crash',      'position' => 10, 'display' => 1),
		array('slug' => 'crash-p2e', 'name' => 'Crash P2E',  'name2' => 'Crash P2E',  'name3' => 'Crash P2E',  'position' => 20, 'display' => 1),
		array('slug' => 'other',     'name' => 'Other',      'name2' => 'Other',      'name3' => 'Other',      'position' => 30, 'display' => 1),
	);
	foreach ($seed as $row) {
		mysql_fn('insert', 'games_categories', $row);
	}
}

/**
 * @param string|int $langid Legacy suffix: '' | 2 | 3
 * @param bool $only_display Only categories with display=1
 * @return array slug => localized name
 */
function games_categories_get_map($langid = '', $only_display = true) {
	games_categories_ensure_table();
	$where = $only_display ? 'WHERE display=1' : '';
	$rows = mysql_select("
		SELECT slug,name,name2,name3
		FROM games_categories
		$where
		ORDER BY position ASC, id ASC
	", 'rows');
	if (!$rows) {
		return array();
	}

	$suffix = ($langid === '' || $langid === null) ? '' : (string)$langid;
	$key = 'name' . $suffix;
	$out = array();
	foreach ($rows as $r) {
		$slug = trim((string)$r['slug']);
		if ($slug === '') {
			continue;
		}
		$label = isset($r[$key]) && trim((string)$r[$key]) !== '' ? (string)$r[$key] : (string)$r['name'];
		$out[$slug] = $label;
	}
	return $out;
}

/** Fallback map when table is empty (install edge case). */
function games_categories_fallback_map() {
	return array(
		'crash'     => 'Crash',
		'crash-p2e' => 'Crash P2E',
		'other'     => 'Other',
	);
}

/**
 * @param string|int $langid
 * @param bool $only_display
 * @return array slug => name
 */
function games_categories_get_map_or_fallback($langid = '', $only_display = true) {
	$map = games_categories_get_map($langid, $only_display);
	return $map ?: games_categories_fallback_map();
}

function games_categories_normalize_slug($slug) {
	$slug = strtolower(trim((string)$slug));
	$slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug);
	return trim($slug, '-');
}
