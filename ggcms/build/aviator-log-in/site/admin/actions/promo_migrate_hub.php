<?php
/**
 * Promo table + hub page (/promo/). Included from site migrate_BD_run.php.
 * Optional brand seed: site/admin/actions/promo_seed_*.php after this file.
 */

if (!isset($done) || !is_array($done)) {
	$done = array();
}

if (@mysql_select("SHOW TABLES LIKE 'promo'", 'num_rows') === 0) {
	mysql_fn('query', "
		CREATE TABLE `promo` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL DEFAULT '',
			`name_2` varchar(512) NOT NULL DEFAULT '',
			`url` varchar(255) NOT NULL DEFAULT '',
			`text` longtext NOT NULL,
			`img` varchar(255) NOT NULL DEFAULT '',
			`category` varchar(32) NOT NULL DEFAULT 'active',
			`promo_unlimited` tinyint(1) NOT NULL DEFAULT 1,
			`date_end` datetime DEFAULT NULL,
			`display` tinyint(1) NOT NULL DEFAULT 1,
			`position` int(11) NOT NULL DEFAULT 0,
			`date` datetime DEFAULT NULL,
			`author_id` int(11) unsigned NOT NULL DEFAULT 0,
			`title` varchar(255) NOT NULL DEFAULT '',
			`description` text,
			`created_at` datetime DEFAULT NULL,
			`updated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `url` (`url`),
			KEY `display` (`display`),
			KEY `category` (`category`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	");
	$done[] = 'promo table';
}

$promo_hub_desc = 'Bonuses and special offers';
if (function_exists('site_brand_name')) {
	$bn = trim((string) site_brand_name());
	if ($bn !== '') {
		$promo_hub_desc = 'Bonuses and special offers for ' . $bn . ' players.';
	}
}

if (mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
	$promo_page = mysql_select("SELECT id, module FROM pages WHERE module='promo' OR url='promo' LIMIT 1", 'row');
	if (!$promo_page) {
		$max_lk = mysql_select("SELECT MAX(right_key) AS m FROM pages", 'row');
		$lk = isset($max_lk['m']) ? (int)$max_lk['m'] + 1 : 1;
		$rk = $lk + 1;
		mysql_fn('insert', 'pages', array(
			'left_key' => $lk,
			'right_key' => $rk,
			'level' => 1,
			'parent' => 0,
			'module' => 'promo',
			'display' => 1,
			'menu' => 0,
			'menu2' => 0,
			'name' => 'Promo',
			'title' => 'Promo',
			'description' => $promo_hub_desc,
			'url' => 'promo',
			'language' => 1,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		));
		$done[] = 'pages module=promo';
	} elseif ((string)($promo_page['module'] ?? '') !== 'promo') {
		mysql_fn('update', 'pages', array(
			'id' => (int)$promo_page['id'],
			'module' => 'promo',
		));
		$done[] = 'pages module=promo (updated id=' . (int)$promo_page['id'] . ')';
	}
}
