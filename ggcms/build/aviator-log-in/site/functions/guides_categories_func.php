<?php
/**
 * Guides categories storage in DB (instead of hardcoded arrays in code).
 * Table: guides_categories (slug + localized names by legacy language columns)
 */

function guides_categories_ensure_table() {
	if (@mysql_select("SHOW TABLES LIKE 'guides_categories'", 'num_rows') > 0) return;

	mysql_fn('query', "
		CREATE TABLE `guides_categories` (
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
		array('slug' => 'analysis',       'name' => 'Analysis',      'name3' => 'Analysis',        'position' => 10, 'display' => 1),
		array('slug' => 'bonus',          'name' => 'Bonus',         'name3' => 'Bonus',           'position' => 20, 'display' => 1),
		array('slug' => 'how-to-win',     'name' => 'How to Win',    'name3' => 'How to Win',      'position' => 30, 'display' => 1),
		array('slug' => 'signals',        'name' => 'Signals',       'name3' => 'Signals',         'position' => 40, 'display' => 1),
		array('slug' => 'crash-gambling', 'name' => 'Crash Gambling','name3' => 'Crash Gambling',  'position' => 50, 'display' => 1),
	);
	foreach ($seed as $row) {
		mysql_fn('insert', 'guides_categories', $row);
	}
}

/**
 * @param string|int $langid Legacy suffix: '' | 2 | 3
 * @param bool $only_display Only categories with display=1
 * @return array slug => localized name
 */
function guides_categories_get_map($langid = '', $only_display = true) {
	guides_categories_ensure_table();
	$where = $only_display ? "WHERE display=1" : "";
	$rows = mysql_select("
		SELECT slug,name,name2,name3
		FROM guides_categories
		$where
		ORDER BY position ASC, id ASC
	", 'rows');
	if (!$rows) return array();

	$suffix = ($langid === '' || $langid === null) ? '' : (string)$langid;
	$key = 'name' . $suffix;
	$out = array();
	foreach ($rows as $r) {
		$slug = trim((string)$r['slug']);
		if ($slug === '') continue;
		$label = isset($r[$key]) && trim((string)$r[$key]) !== '' ? (string)$r[$key] : (string)$r['name'];
		$out[$slug] = $label;
	}
	return $out;
}

