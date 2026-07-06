<?php
/**
 * Centralized search-index rules (DB table seo_index_rules, not variables).
 *
 * Default: full indexing allowed (no meta tags). Admin sets what to block.
 * Enforcement: HTML meta robots/googlebot + X-Robots-Tag + sitemap trim only (not robots.txt).
 */

if (!function_exists('seo_index_rules_ensure_table')) {

	function seo_index_rules_table_name() {
		return 'seo_index_rules';
	}

	function seo_index_rules_ensure_table() {
		if (!function_exists('mysql_select') || @mysql_select("SHOW TABLES LIKE 'seo_index_rules'", 'num_rows') > 0) {
			return true;
		}
		$sql = "CREATE TABLE IF NOT EXISTS `seo_index_rules` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`scope` varchar(16) NOT NULL DEFAULT 'site',
			`scope_key` varchar(64) NOT NULL DEFAULT '',
			`entity_id` int(10) unsigned NOT NULL DEFAULT 0,
			`block` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`engines` varchar(32) NOT NULL DEFAULT 'inherit',
			`engines_list` text,
			`updated_at` datetime DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uq_seo_index_scope` (`scope`,`scope_key`,`entity_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		return (bool) @mysql_fn('query', $sql);
	}

	function seo_index_rules_entity_map() {
		if (function_exists('seo_monitor_entity_map')) {
			return seo_monitor_entity_map();
		}
		return array(
			'pages' => array('table' => 'pages', 'label' => 'Pages'),
			'guides' => array('table' => 'guides', 'label' => 'Guides'),
			'games' => array('table' => 'games', 'label' => 'Games'),
			'casino_articles' => array('table' => 'casino_articles', 'label' => 'Casino articles'),
			'blog' => array('table' => 'blog', 'label' => 'Blog'),
			'authors' => array('table' => 'site_authors', 'label' => 'Authors'),
		);
	}

	function seo_index_rules_route_map() {
		return array();
	}

	function seo_index_rules_engine_options() {
		return array(
			'inherit' => 'Inherit',
			'all' => 'All crawlers',
			'google' => 'Google',
			'yandex' => 'Yandex',
			'bing' => 'Bing',
			'custom' => 'Custom bot',
		);
	}

	function seo_index_rules_normalize_engines($engines) {
		$engines = strtolower(trim((string) $engines));
		$allowed = array('inherit', 'all', 'google', 'yandex', 'bing', 'custom');
		return in_array($engines, $allowed, true) ? $engines : 'inherit';
	}

	/** Meta name for a known engine key, or sanitized custom bot name. */
	function seo_index_rules_sanitize_custom_bot_name($name) {
		$name = strtolower(trim((string) $name));
		$name = preg_replace('/[^a-z0-9_-]+/', '', $name);
		return substr($name, 0, 64);
	}

	function seo_index_rules_engine_meta_name($engines, $engines_list = '') {
		switch (seo_index_rules_normalize_engines($engines)) {
			case 'all':
				return 'robots';
			case 'google':
				return 'googlebot';
			case 'yandex':
				return 'yandex';
			case 'bing':
				return 'bingbot';
			case 'custom':
				return seo_index_rules_sanitize_custom_bot_name($engines_list);
			default:
				return '';
		}
	}

	function seo_index_rules_engine_label($engines, $engines_list = '') {
		$opts = seo_index_rules_engine_options();
		$engines = seo_index_rules_normalize_engines($engines);
		if ($engines === 'custom') {
			$bot = seo_index_rules_sanitize_custom_bot_name($engines_list);
			return $bot !== '' ? ('Custom: ' . $bot) : 'Custom bot';
		}
		return isset($opts[$engines]) ? $opts[$engines] : $engines;
	}

	/**
	 * Meta tags when indexing is blocked.
	 *
	 * @return array<string,string> meta name => content
	 */
	function seo_index_rules_block_meta_tags($engines, $engines_list = '') {
		$engines = seo_index_rules_normalize_engines($engines);
		$tags = array();
		if ($engines === 'all') {
			$tags['robots'] = 'noindex, nofollow';
			$tags['googlebot'] = 'noindex, nofollow';
			return $tags;
		}
		$name = seo_index_rules_engine_meta_name($engines, $engines_list);
		if ($name !== '') {
			$tags[$name] = 'noindex, follow';
		}
		return $tags;
	}

	function seo_index_rules_row_key($scope, $scope_key = '', $entity_id = 0) {
		return strtolower(trim((string) $scope)) . '|' . trim((string) $scope_key) . '|' . (int) $entity_id;
	}

	/** @return array<string,array> keyed by scope|scope_key|entity_id */
	function seo_index_rules_load_all($force_reload = false) {
		static $cache = null;
		if ($force_reload) {
			$cache = null;
		}
		if (is_array($cache)) {
			return $cache;
		}
		$cache = array();
		if (!seo_index_rules_ensure_table()) {
			return $cache;
		}
		$rows = mysql_select("SELECT scope, scope_key, entity_id, block, engines, engines_list FROM seo_index_rules", 'rows');
		if (!$rows) {
			return $cache;
		}
		foreach ($rows as $row) {
			$key = seo_index_rules_row_key($row['scope'], $row['scope_key'], $row['entity_id']);
			$cache[$key] = array(
				'block' => !empty($row['block']) ? 1 : 0,
				'engines' => seo_index_rules_normalize_engines($row['engines'] ?? 'inherit'),
				'engines_list' => (string) ($row['engines_list'] ?? ''),
			);
		}
		return $cache;
	}

	function seo_index_rules_get($scope, $scope_key = '', $entity_id = 0) {
		$all = seo_index_rules_load_all();
		$key = seo_index_rules_row_key($scope, $scope_key, $entity_id);
		return isset($all[$key]) ? $all[$key] : null;
	}

	function seo_index_rules_save($scope, $scope_key, $entity_id, $block, $engines = 'inherit', $engines_list = null) {
		if (!seo_index_rules_ensure_table()) {
			return false;
		}
		$scope = trim((string) $scope);
		$scope_key = trim((string) $scope_key);
		$entity_id = (int) $entity_id;
		$block = !empty($block) ? 1 : 0;
		$engines = seo_index_rules_normalize_engines($engines);
		$exists = mysql_select("
			SELECT id FROM seo_index_rules
			WHERE scope='" . mysql_res($scope) . "'
			  AND scope_key='" . mysql_res($scope_key) . "'
			  AND entity_id=" . $entity_id . "
			LIMIT 1
		", 'row');
		$data = array(
			'block' => $block,
			'engines' => $engines,
			'updated_at' => date('Y-m-d H:i:s'),
		);
		if ($engines_list !== null) {
			$data['engines_list'] = trim((string) $engines_list);
		}
		if ($exists) {
			return (bool) mysql_fn('update', 'seo_index_rules', $data, " AND id=" . (int) $exists['id']);
		}
		$data['scope'] = $scope;
		$data['scope_key'] = $scope_key;
		$data['entity_id'] = $entity_id;
		return (bool) mysql_fn('insert', 'seo_index_rules', $data);
	}

	function seo_index_rules_delete($scope, $scope_key, $entity_id) {
		if (!seo_index_rules_ensure_table()) {
			return false;
		}
		return (bool) mysql_fn('query', "
			DELETE FROM seo_index_rules
			WHERE scope='" . mysql_res((string) $scope) . "'
			  AND scope_key='" . mysql_res((string) $scope_key) . "'
			  AND entity_id=" . (int) $entity_id . "
			LIMIT 1
		");
	}

	function seo_index_rules_site_blocked() {
		$site = seo_index_rules_get('site', 'site', 0);
		return $site && !empty($site['block']);
	}

	/** @deprecated Demo app is never indexable; kept for BC. */
	function seo_index_rules_demo_app_langs() {
		return array();
	}

	function seo_index_rules_resolved_for_context(array $ctx) {
		$site = seo_index_rules_get('site', 'site', 0);
		if (!$site) {
			$site = array('block' => 0, 'engines' => 'inherit');
		}

		$chain = array($site);

		if (!empty($ctx['route'])) {
			$route = seo_index_rules_get('route', (string) $ctx['route'], 0);
			if ($route) {
				$chain[] = $route;
			}
		}

		if (!empty($ctx['entity'])) {
			$ent = seo_index_rules_get('entity', (string) $ctx['entity'], 0);
			if ($ent) {
				$chain[] = $ent;
			}
		}

		if (!empty($ctx['entity']) && !empty($ctx['entity_id'])) {
			$item = seo_index_rules_get('item', (string) $ctx['entity'], (int) $ctx['entity_id']);
			if ($item) {
				$chain[] = $item;
			}
		}

		$block = 0;
		$engines = 'inherit';
		$engines_list = '';
		foreach ($chain as $row) {
			if (!is_array($row)) {
				continue;
			}
			if (array_key_exists('block', $row)) {
				$block = !empty($row['block']) ? 1 : 0;
			}
			if (!empty($row['engines']) && $row['engines'] !== 'inherit') {
				$engines = $row['engines'];
				$engines_list = (string) ($row['engines_list'] ?? '');
			}
		}
		if ($engines === 'inherit') {
			$engines = 'all';
		}
		return array('block' => $block, 'engines' => $engines, 'engines_list' => $engines_list);
	}

	function seo_index_rules_detect_context(?array $abc = null, ?array $u = null, ?array $lang = null) {
		if ($abc === null) {
			global $abc;
		}
		if ($u === null) {
			global $u;
		}
		if ($lang === null) {
			global $lang;
		}
		if (!is_array($abc)) {
			$abc = array();
		}
		if (!is_array($u)) {
			$u = array();
		}
		if (!is_array($lang)) {
			$lang = array();
		}

		$ctx = array();
		$mod = isset($abc['module']) ? (string) $abc['module'] : '';
		$page = isset($abc['page']) && is_array($abc['page']) ? $abc['page'] : array();
		$page_id = (int) ($page['id'] ?? 0);

		if ($mod === 'blog') {
			$ctx['entity'] = 'blog';
			$ctx['entity_id'] = $page_id;
		} elseif ($mod === 'guides') {
			$ctx['entity'] = 'guides';
			$ctx['entity_id'] = $page_id;
		} elseif ($mod === 'games') {
			$ctx['entity'] = 'games';
			$ctx['entity_id'] = $page_id;
		} elseif ($mod === 'authors') {
			$ctx['entity'] = 'authors';
			$ctx['entity_id'] = $page_id;
		} elseif ($mod === 'index' || ($mod === 'pages' && function_exists('site_seo_page_is_home') && site_seo_page_is_home($abc))) {
			$ctx['entity'] = 'pages';
			$ctx['entity_id'] = $page_id;
		} elseif ($mod === 'pages') {
			$ctx['entity'] = 'pages';
			$ctx['entity_id'] = $page_id;
		}

		return $ctx;
	}

	function seo_index_rules_allow_search_indexing(?array $abc = null, ?array $u = null, ?array $lang = null) {
		$ctx = seo_index_rules_detect_context($abc, $u, $lang);
		$resolved = seo_index_rules_resolved_for_context($ctx);
		return empty($resolved['block']);
	}

	/** @return array<string,string> */
	function seo_index_rules_robots_meta_tags(?array $abc = null, ?array $u = null, ?array $lang = null) {
		if (seo_index_rules_allow_search_indexing($abc, $u, $lang)) {
			return array();
		}
		$ctx = seo_index_rules_detect_context($abc, $u, $lang);
		$resolved = seo_index_rules_resolved_for_context($ctx);
		return seo_index_rules_block_meta_tags($resolved['engines'], $resolved['engines_list'] ?? '');
	}

	function seo_index_rules_echo_robots_meta_tags(?array $abc = null, ?array $u = null, ?array $lang = null) {
		$tags = seo_index_rules_robots_meta_tags($abc, $u, $lang);
		foreach ($tags as $name => $content) {
			if ($content === '') {
				continue;
			}
			echo '        <meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">' . "\n";
		}
	}

	function seo_index_rules_apply_robots_header(?array $abc = null, ?array $u = null, ?array $lang = null) {
		if (seo_index_rules_allow_search_indexing($abc, $u, $lang)) {
			return;
		}
		$ctx = seo_index_rules_detect_context($abc, $u, $lang);
		$resolved = seo_index_rules_resolved_for_context($ctx);
		if (seo_index_rules_normalize_engines($resolved['engines']) !== 'all') {
			return;
		}
		$tags = seo_index_rules_block_meta_tags($resolved['engines'], $resolved['engines_list'] ?? '');
		if (!empty($tags['robots']) && !headers_sent()) {
			header('X-Robots-Tag: ' . $tags['robots'], true);
		}
	}

	function seo_index_rules_sitemap_include_entity($entity) {
		$ent = seo_index_rules_get('entity', (string) $entity, 0);
		if ($ent && !empty($ent['block'])) {
			return false;
		}
		$site = seo_index_rules_get('site', 'site', 0);
		if ($site && !empty($site['block'])) {
			return false;
		}
		return true;
	}

	function seo_index_rules_admin_restrictions() {
		$items = array();
		$site = seo_index_rules_get('site', 'site', 0);
		if ($site && !empty($site['block'])) {
			$items[] = array(
				'id' => 'site_block',
				'label' => 'Site-wide block',
				'detail' => '',
			);
		}
		foreach (seo_index_rules_entity_map() as $ent => $info) {
			$row = seo_index_rules_get('entity', $ent, 0);
			if ($row && !empty($row['block'])) {
				$items[] = array(
					'id' => 'entity_' . $ent,
					'label' => $info['label'] . ' blocked',
					'detail' => '',
				);
			}
		}
		return $items;
	}

	function seo_index_rules_admin_restrictions_active() {
		return !empty(seo_index_rules_admin_restrictions());
	}
}
