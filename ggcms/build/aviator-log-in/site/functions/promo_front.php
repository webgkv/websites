<?php
/**
 * Promo front helpers: active list for demo gift badge, localStorage-friendly payload.
 */

if (!function_exists('promo_front_sql_active_where')) {
	function promo_front_sql_active_where() {
		return "
			display = 1
			AND category = 'active'
			AND (
				IFNULL(promo_unlimited, 0) = 1
				OR date_end IS NULL
				OR date_end = '0000-00-00 00:00:00'
				OR date_end >= NOW()
			)
		";
	}
}

if (!function_exists('promo_front_active_rows')) {
	/**
	 * @return array<int,array<string,mixed>>
	 */
	function promo_front_active_rows($limit = 20) {
		if (@mysql_select("SHOW TABLES LIKE 'promo'", 'num_rows') <= 0) {
			return array();
		}
		$limit = max(1, min(50, (int)$limit));
		$rows = mysql_select("
			SELECT id, url, name, updated_at
			FROM promo
			WHERE " . promo_front_sql_active_where() . "
			ORDER BY position DESC, date DESC, id DESC
			LIMIT " . $limit . "
		", 'rows');
		return is_array($rows) ? $rows : array();
	}
}

if (!function_exists('promo_front_demo_badge_data')) {
	/**
	 * Payload for demo-app gift icon (hub URL + active promo items).
	 *
	 * @return array{hub:string,items:array<int,array{id:int,url:string}>}
	 */
	function promo_front_demo_badge_data(array $abc = array()) {
		$hub = function_exists('site_section_public_base')
			? site_section_public_base('promo', $abc)
			: '/promo/';
		$hub = preg_replace('#/+#', '/', $hub);
		$items = array();
		foreach (promo_front_active_rows(20) as $row) {
			$id = (int)($row['id'] ?? 0);
			$slug = trim((string)($row['url'] ?? ''), '/');
			if ($id <= 0 || $slug === '') {
				continue;
			}
			$url = preg_replace('#/+#', '/', rtrim($hub, '/') . '/' . $slug . '/');
			$items[] = array('id' => $id, 'url' => $url);
		}
		return array(
			'hub' => $hub,
			'items' => $items,
		);
	}
}

if (!function_exists('promo_front_has_active')) {
	function promo_front_has_active() {
		return !empty(promo_front_active_rows(1));
	}
}

if (!function_exists('promo_front_seen_storage_key')) {
	function promo_front_seen_storage_key() {
		global $config;
		$sid = isset($config['site_id']) ? preg_replace('/[^a-z0-9_-]+/i', '', (string)$config['site_id']) : '';
		return $sid !== '' ? 'promo_seen_' . $sid : 'promo_seen_v1';
	}
}
