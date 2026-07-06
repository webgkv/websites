<?php
/**
 * DB migration steps (shared by site/scripts/run_migrate_BD.php CLI and URL).
 * Expects: config and mysql_func loaded. Sets $done (array of applied steps).
 */
if (!function_exists('mysql_select')) {
	$root = dirname(__FILE__) . '/../../';
	if (!is_file($root . 'config/config.php')) {
		$root = dirname(__FILE__) . '/../../../';
	}
	define('ROOT_DIR', $root);
	require_once(ROOT_DIR . 'config/config.php');
	require_once(ROOT_DIR . 'functions/mysql_func.php');
}
$done = array();

// --- news → blog (tables rename) ---
$has_blog = mysql_select("SHOW TABLES LIKE 'blog'", 'num_rows') > 0;
if (!$has_blog && mysql_select("SHOW TABLES LIKE 'news'", 'num_rows') > 0) {
	mysql_fn('query', "RENAME TABLE `news` TO `blog`");
	$done[] = 'rename table news → blog';
}
$has_blog_cat = mysql_select("SHOW TABLES LIKE 'blog_category'", 'num_rows') > 0;
if (!$has_blog_cat && mysql_select("SHOW TABLES LIKE 'news_category'", 'num_rows') > 0) {
	mysql_fn('query', "RENAME TABLE `news_category` TO `blog_category`");
	$done[] = 'rename table news_category → blog_category';
}
$has_blog_tags = mysql_select("SHOW TABLES LIKE 'blog_tags'", 'num_rows') > 0;
if (!$has_blog_tags && mysql_select("SHOW TABLES LIKE 'news_tags'", 'num_rows') > 0) {
	mysql_fn('query', "RENAME TABLE `news_tags` TO `blog_tags`");
	$done[] = 'rename table news_tags → blog_tags';
}

// --- blog.position (admin list default sort aligned with Games / Guides) ---
if ($has_blog && mysql_select("SHOW COLUMNS FROM `blog` LIKE 'position'", 'num_rows') === 0) {
	mysql_fn('query', "ALTER TABLE `blog` ADD COLUMN `position` INT NOT NULL DEFAULT 0");
	$done[] = 'blog.position';
}

// --- variables (Settings counters, Advertising API config) ---
$has_variables = mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
if (!$has_variables) {
	mysql_fn('query', "CREATE TABLE `variables` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`key` VARCHAR(191) NOT NULL,
		`value` MEDIUMTEXT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_key` (`key`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table variables';
}

// --- admin_jobs (background queue) ---
$has_admin_jobs = mysql_select("SHOW TABLES LIKE 'admin_jobs'", 'num_rows') > 0;
if (!$has_admin_jobs) {
	mysql_fn('query', "CREATE TABLE `admin_jobs` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`module` VARCHAR(64) NOT NULL,
		`action` VARCHAR(64) NOT NULL,
		`payload` MEDIUMTEXT NULL,
		`status` ENUM('pending','running','done','failed','cancelled') NOT NULL DEFAULT 'pending',
		`priority` INT NOT NULL DEFAULT 0,
		`scheduled_at` DATETIME NULL,
		`attempts` INT NOT NULL DEFAULT 0,
		`max_attempts` INT NOT NULL DEFAULT 3,
		`locked_at` DATETIME NULL,
		`started_at` DATETIME NULL,
		`finished_at` DATETIME NULL,
		`message` TEXT NULL,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		KEY `idx_status_sched_prio` (`status`, `scheduled_at`, `priority`),
		KEY `idx_module_action` (`module`, `action`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table admin_jobs';
}

// --- AI provider keys (OpenRouter / Gemini / NVIDIA) ---
$has_ai_keys = mysql_select("SHOW TABLES LIKE 'ai_provider_keys'", 'num_rows') > 0;
if (!$has_ai_keys) {
	mysql_fn('query', "CREATE TABLE `ai_provider_keys` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`provider` VARCHAR(32) NOT NULL,
		`name` VARCHAR(191) NULL,
		`api_key` VARCHAR(512) NOT NULL,
		`model_default` VARCHAR(191) NULL,
		`enabled` TINYINT(1) NOT NULL DEFAULT 1,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		KEY `idx_provider_enabled` (`provider`, `enabled`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table ai_provider_keys';
}

// --- Data source API keys (lottery feeds for homepage) ---
$has_data_source_keys = mysql_select("SHOW TABLES LIKE 'data_source_keys'", 'num_rows') > 0;
if (!$has_data_source_keys) {
	mysql_fn('query', "CREATE TABLE `data_source_keys` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`provider` VARCHAR(64) NOT NULL,
		`name` VARCHAR(191) NULL,
		`api_key` VARCHAR(512) NOT NULL DEFAULT '',
		`api_secret` VARCHAR(512) NOT NULL DEFAULT '',
		`notes` VARCHAR(255) NULL,
		`enabled` TINYINT(1) NOT NULL DEFAULT 1,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		KEY `idx_provider_enabled` (`provider`, `enabled`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table data_source_keys';
} elseif (mysql_select("SHOW COLUMNS FROM `data_source_keys` LIKE 'api_secret'", 'num_rows') === 0) {
	mysql_fn('query', "ALTER TABLE `data_source_keys` ADD COLUMN `api_secret` VARCHAR(512) NOT NULL DEFAULT '' AFTER `api_key`");
	$done[] = 'data_source_keys.api_secret';
}

// --- content_i18n (scalable translations store) ---
$has_content_i18n = mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
if (!$has_content_i18n) {
	mysql_fn('query', "CREATE TABLE `content_i18n` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`entity` VARCHAR(32) NOT NULL,
		`entity_id` INT UNSIGNED NOT NULL,
		`lang_id` INT UNSIGNED NOT NULL,
		`url` VARCHAR(255) NOT NULL,
		`name` VARCHAR(255) NULL,
		`title` VARCHAR(255) NULL,
		`description` TEXT NULL,
		`content` MEDIUMTEXT NULL,
		`extra` MEDIUMTEXT NULL,
		`status` ENUM('missing','draft','review','published') NOT NULL DEFAULT 'draft',
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_entity_lang` (`entity`, `entity_id`, `lang_id`),
		UNIQUE KEY `uniq_lang_url` (`lang_id`, `entity`, `url`),
		KEY `idx_entity` (`entity`, `entity_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table content_i18n';
}

// --- translation_orders (translation monitor orders) ---
$has_translation_orders = mysql_select("SHOW TABLES LIKE 'translation_orders'", 'num_rows') > 0;
if (!$has_translation_orders) {
	mysql_fn('query', "CREATE TABLE `translation_orders` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(255) NOT NULL DEFAULT '',
		`source_lang_id` INT UNSIGNED NOT NULL,
		`target_lang_id` INT UNSIGNED NOT NULL,
		`entity` VARCHAR(32) NOT NULL,
		`filters_json` MEDIUMTEXT NULL,
		`status` ENUM('draft','running','completed','cancelled') NOT NULL DEFAULT 'draft',
		`priority` INT NOT NULL DEFAULT 0,
		`chunk_max_len` INT NOT NULL DEFAULT 2500,
		`translated_count` INT NOT NULL DEFAULT 0,
		`failed_count` INT NOT NULL DEFAULT 0,
		`total_candidates` INT NOT NULL DEFAULT 0,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		KEY `idx_entity_target` (`entity`, `target_lang_id`),
		KEY `idx_status_updated` (`status`, `updated_at`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$done[] = 'create table translation_orders';
}

// --- translation_order_candidates (monitor candidates) ---
$has_translation_order_candidates = mysql_select("SHOW TABLES LIKE 'translation_order_candidates'", 'num_rows') > 0;
if (!$has_translation_order_candidates) {
	mysql_fn('query', "CREATE TABLE `translation_order_candidates` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`order_id` INT UNSIGNED NOT NULL,
		`entity` VARCHAR(32) NOT NULL,
		`entity_id` INT UNSIGNED NOT NULL,
		`candidate_status` ENUM('pending','queued','running','done','failed') NOT NULL DEFAULT 'pending',
		`i18n_status` ENUM('missing','draft','review','published') NOT NULL DEFAULT 'missing',
		`source_name` VARCHAR(255) NULL,
		`source_url` VARCHAR(255) NULL,
		`last_job_id` INT UNSIGNED NULL,
		`last_error` TEXT NULL,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `uniq_order_entity` (`order_id`, `entity`, `entity_id`),
		KEY `idx_order_status` (`order_id`, `candidate_status`),
		KEY `idx_entity_entityid` (`entity`, `entity_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$done[] = 'create table translation_order_candidates';
}

// --- system_logs (admin-visible logs with cleanup settings) ---
$has_system_logs = mysql_select("SHOW TABLES LIKE 'system_logs'", 'num_rows') > 0;
if (!$has_system_logs) {
	mysql_fn('query', "CREATE TABLE `system_logs` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`channel` VARCHAR(64) NOT NULL,
		`level` ENUM('debug','info','warning','error') NOT NULL DEFAULT 'info',
		`message` TEXT NOT NULL,
		`context` MEDIUMTEXT NULL,
		`created_at` DATETIME NOT NULL,
		PRIMARY KEY (`id`),
		KEY `idx_channel_created` (`channel`, `created_at`),
		KEY `idx_level_created` (`level`, `created_at`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$done[] = 'create table system_logs';
}

// --- site_authors (multi-author E-E-A-T) ---
$has_authors = mysql_select("SHOW TABLES LIKE 'site_authors'", 'num_rows') > 0;
if (!$has_authors) {
	mysql_fn('query', "CREATE TABLE `site_authors` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(255) NOT NULL,
		`job_title` VARCHAR(255) NULL,
		`bio` TEXT NULL,
		`photo` VARCHAR(255) NULL,
		`social_links` MEDIUMTEXT NULL,
		`display` TINYINT(1) NOT NULL DEFAULT 1,
		`created_at` DATETIME NOT NULL,
		`updated_at` DATETIME NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	// Seed default author (James Mitchell)
	mysql_fn('insert', 'site_authors', array(
		'name' => 'James Mitchell',
		'job_title' => 'iGaming Strategy Expert',
		'bio' => 'James is a seasoned analyst with over 10 years of experience in the iGaming industry, specializing in crash games and casino strategies.',
		'display' => 1
	));
	$done[] = 'create table site_authors';
}

// --- site_authors profile URL + SEO columns ---
if (mysql_select("SHOW TABLES LIKE 'site_authors'", 'num_rows') > 0) {
	$author_profile_cols = array(
		'url' => "VARCHAR(255) NULL DEFAULT NULL AFTER `name`",
		'bio_short' => "TEXT NULL AFTER `bio`",
		'meta_title' => "VARCHAR(255) NULL DEFAULT NULL",
		'meta_description' => "VARCHAR(512) NULL DEFAULT NULL",
		'photo_alt' => "VARCHAR(255) NULL DEFAULT NULL",
		'social_profiles' => "MEDIUMTEXT NULL COMMENT 'JSON: fixed social network URLs (sameAs)'",
		'reference_links' => "MEDIUMTEXT NULL COMMENT 'JSON: [{label,url}] editorial references'",
	);
	foreach ($author_profile_cols as $col => $def) {
		if (mysql_select("SHOW COLUMNS FROM `site_authors` LIKE '" . mysql_res($col) . "'", 'num_rows') === 0) {
			mysql_fn('query', "ALTER TABLE `site_authors` ADD COLUMN `$col` $def");
			$done[] = 'site_authors.' . $col;
		}
	}
	$author_rows = mysql_select("SELECT id, name, url FROM site_authors", 'rows');
	if ($author_rows) {
		foreach ($author_rows as $ar) {
			if (trim((string)($ar['url'] ?? '')) !== '') {
				continue;
			}
			$slug = strtolower(trim((string)($ar['name'] ?? '')));
			if (function_exists('trunslit')) {
				$slug = trunslit($slug);
			}
			$slug = preg_replace('~[^a-z0-9-]+~', '-', $slug);
			$slug = trim(preg_replace('~-+~', '-', $slug), '-');
			if ($slug === '' && !empty($ar['id'])) {
				$slug = 'author-' . (int)$ar['id'];
			}
			if ($slug !== '') {
				mysql_fn('update', 'site_authors', array('url' => $slug), ' AND id=' . (int)$ar['id'] . ' ');
				$done[] = 'site_authors.url backfill id=' . (int)$ar['id'];
			}
		}
	}
	if (mysql_select("SHOW COLUMNS FROM `site_authors` LIKE 'social_profiles'", 'num_rows') > 0) {
		require_once ROOT_DIR . 'functions/author_social.php';
		$legacy_rows = mysql_select("SELECT id, social_links, social_profiles FROM site_authors", 'rows');
		if ($legacy_rows) {
			foreach ($legacy_rows as $lr) {
				if (trim((string)($lr['social_profiles'] ?? '')) !== '') {
					continue;
				}
				$legacy = trim((string)($lr['social_links'] ?? ''));
				if ($legacy === '') {
					continue;
				}
				$profiles = author_parse_social_links_legacy($legacy);
				if (empty($profiles)) {
					continue;
				}
				$json = json_encode($profiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				mysql_fn('update', 'site_authors', array(
					'social_profiles' => $json,
					'social_links' => $json,
				), ' AND id=' . (int)$lr['id'] . ' ');
				$done[] = 'site_authors.social_profiles migrate id=' . (int)$lr['id'];
			}
		}
	}
}

// --- Authors public section page (module=authors, /authors/) ---
if (mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
	$authors_page = mysql_select("SELECT id FROM pages WHERE module='authors' LIMIT 1", 'row');
	if (!$authors_page) {
		$max_lk = mysql_select("SELECT MAX(right_key) AS m FROM pages", 'row');
		$lk = isset($max_lk['m']) ? (int)$max_lk['m'] + 1 : 1;
		$rk = $lk + 1;
		mysql_fn('insert', 'pages', array(
			'left_key' => $lk,
			'right_key' => $rk,
			'level' => 1,
			'parent' => 0,
			'module' => 'authors',
			'display' => 1,
			'menu' => 0,
			'menu2' => 0,
			'name' => 'Authors',
			'title' => 'Authors',
			'description' => 'Meet the writers and experts behind our guides and articles.',
			'url' => 'authors',
			'language' => 1,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		));
		$done[] = 'pages module=authors';
	}
}

// --- Add author_id to content tables ---
$content_tables = array('blog', 'guides', 'games', 'casino_articles', 'pages');
foreach ($content_tables as $tbl) {
	if (mysql_select("SHOW TABLES LIKE '$tbl'", 'num_rows') > 0) {
		if (mysql_select("SHOW COLUMNS FROM `$tbl` LIKE 'author_id'", 'num_rows') === 0) {
			mysql_fn('query', "ALTER TABLE `$tbl` ADD COLUMN `author_id` INT UNSIGNED NOT NULL DEFAULT 0");
			// Set default author ID 1 for all existing records
			mysql_fn('query', "UPDATE `$tbl` SET `author_id` = 1");
			$done[] = "$tbl.author_id";
		}
	}
}

// --- Fix missing i18n columns in pages table (url2, name2, etc) ---
if (mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
	$langs = mysql_select("SELECT id FROM languages WHERE id > 1", 'rows');
	if ($langs) {
		$cols = mysql_select("SHOW COLUMNS FROM `pages`", 'rows');
		$col_names = array_column($cols, 'Field');
		foreach ($langs as $l) {
			$id = $l['id'];
			$to_add = array(
				"name$id" => "VARCHAR(255) NOT NULL DEFAULT ''",
				"text$id" => "MEDIUMTEXT NULL",
				"url$id" => "VARCHAR(255) NOT NULL DEFAULT ''",
				"title$id" => "VARCHAR(255) NOT NULL DEFAULT ''",
				"description$id" => "TEXT NULL"
			);
			foreach ($to_add as $col => $def) {
				if (!in_array($col, $col_names)) {
					mysql_fn('query', "ALTER TABLE `pages` ADD COLUMN `$col` $def");
					$done[] = "pages.$col";
				}
			}
		}
	}
}

// --- Fix missing columns in users table ---
if (mysql_select("SHOW TABLES LIKE 'users'", 'num_rows') > 0) {
	$cols = mysql_select("SHOW COLUMNS FROM `users`", 'rows');
	$col_names = array_column($cols, 'Field');
	$to_add = array(
		'tree' => "INT UNSIGNED NOT NULL DEFAULT 0",
		'parent' => "INT UNSIGNED NOT NULL DEFAULT 0",
		'level' => "INT UNSIGNED NOT NULL DEFAULT 0",
		'left_key' => "INT UNSIGNED NOT NULL DEFAULT 0",
		'right_key' => "INT UNSIGNED NOT NULL DEFAULT 0",
		'fields' => "TEXT NULL",
		'parameters' => "TEXT NULL",
		'last_visit' => "DATETIME NULL",
		'avatar' => "VARCHAR(255) NOT NULL DEFAULT ''",
		'birthday' => "DATE NULL"
	);
	foreach ($to_add as $col => $def) {
		if (!in_array($col, $col_names)) {
			mysql_fn('query', "ALTER TABLE `users` ADD COLUMN `$col` $def");
			$done[] = "users.$col";
		}
	}
}

// --- Fix missing columns in blog table ---
if (mysql_select("SHOW TABLES LIKE 'blog'", 'num_rows') > 0) {
	$cols = mysql_select("SHOW COLUMNS FROM `blog`", 'rows');
	$col_names = array_column($cols, 'Field');
	if (!in_array('gimg', $col_names)) {
		mysql_fn('query', "ALTER TABLE `blog` ADD COLUMN `gimg` VARCHAR(255) NOT NULL DEFAULT ''");
		$done[] = "blog.gimg";
	}
	if (!in_array('skip_random_images', $col_names)) {
		mysql_fn('query', "ALTER TABLE `blog` ADD COLUMN `skip_random_images` TINYINT(1) NOT NULL DEFAULT 0");
		$done[] = "blog.skip_random_images";
	}
}

// --- Homepage URL: must be empty (/{lang}/), not /home/ from legacy imports ---
if (mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
	$home_rows = function_exists('mysql_query_affected_rows')
		? mysql_query_affected_rows("UPDATE `pages` SET `url`='' WHERE `module`='index' AND TRIM(`url`) IN ('home', '/home', '/home/')")
		: 0;
	if ($home_rows > 0) {
		$done[] = 'pages.index url cleared (' . (int)$home_rows . ' row(s))';
	}
}
if (mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0
	&& mysql_select("SHOW TABLES LIKE 'pages'", 'num_rows') > 0) {
	$ci_home_rows = function_exists('mysql_query_affected_rows')
		? mysql_query_affected_rows("
			UPDATE `content_i18n` ci
			INNER JOIN `pages` p ON p.id = ci.entity_id AND ci.entity = 'pages'
			SET ci.url = ''
			WHERE p.module = 'index'
			  AND TRIM(BOTH '/' FROM ci.url) = 'home'
		")
		: 0;
	if ($ci_home_rows > 0) {
		$done[] = 'content_i18n home slug cleared for index pages (' . (int)$ci_home_rows . ' row(s))';
	}
}

// --- Fix missing columns in ads table ---
if (mysql_select("SHOW TABLES LIKE 'ads'", 'num_rows') > 0) {
	$cols = mysql_select("SHOW COLUMNS FROM `ads`", 'rows');
	$col_names = array_column($cols, 'Field');
	if (!in_array('img_2', $col_names)) {
		mysql_fn('query', "ALTER TABLE `ads` ADD COLUMN `img_2` VARCHAR(255) NOT NULL DEFAULT ''");
		$done[] = "ads.img_2";
	}
}

// --- seo_index_rules (SEO → Index rules) ---
if (is_file(ROOT_DIR . 'functions/seo_index_rules.php')) {
	require_once ROOT_DIR . 'functions/seo_index_rules.php';
	if (function_exists('seo_index_rules_ensure_table') && seo_index_rules_ensure_table()) {
		if (@mysql_select("SHOW TABLES LIKE 'seo_index_rules'", 'num_rows') > 0) {
			$done[] = 'seo_index_rules table';
		}
	}
}
