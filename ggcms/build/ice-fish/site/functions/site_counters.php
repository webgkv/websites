<?php
/**
 * Site counters: DB, JSON reference file (files/reference/counters.json), and runtime settings.
 */

if (!function_exists('site_counters_settings_defaults')) {
	function site_counters_settings_defaults() {
		return array(
			'source' => 'json',
			'onesignal_web_enabled' => 1,
		);
	}
}

if (!function_exists('site_counters_reference_path')) {
	function site_counters_reference_path() {
		global $config;
		if (!empty($config['counters_json_path'])) {
			return (string) $config['counters_json_path'];
		}
		$root = defined('ROOT_DIR') ? ROOT_DIR : '';
		return $root . 'files/reference/counters.json';
	}
}

if (!function_exists('site_counters_normalize_row')) {
	function site_counters_normalize_row(array $row) {
		$name = isset($row['name']) ? trim((string) $row['name']) : '';
		$kind = isset($row['kind']) ? trim((string) $row['kind']) : '';
		if ($kind === '' && stripos($name, 'onesignal') !== false) {
			$kind = 'onesignal';
		}
		$has_place = isset($row['place_head']) || isset($row['place_body']) || isset($row['place_footer']);
		$place_head = $has_place ? !empty($row['place_head']) : true;
		$place_body = !empty($row['place_body']);
		$place_footer = !empty($row['place_footer']);
		$split = array_key_exists('code_head', $row) || array_key_exists('code_body', $row) || array_key_exists('code_footer', $row);
		$legacy_code = isset($row['code']) ? (string) $row['code'] : '';
		if ($split) {
			$code_head = isset($row['code_head']) ? (string) $row['code_head'] : '';
			$code_body = isset($row['code_body']) ? (string) $row['code_body'] : '';
			$code_footer = isset($row['code_footer']) ? (string) $row['code_footer'] : '';
		} else {
			$code_head = ($place_head || !$has_place) ? $legacy_code : '';
			$code_body = $place_body ? $legacy_code : '';
			$code_footer = $place_footer ? $legacy_code : '';
		}
		return array(
			'name' => $name !== '' ? $name : 'Counter',
			'kind' => $kind,
			'code_head' => $code_head,
			'code_body' => $code_body,
			'code_footer' => $code_footer,
			'display' => !empty($row['display']) ? 1 : 0,
			'place_head' => $place_head ? 1 : 0,
			'place_body' => $place_body ? 1 : 0,
			'place_footer' => $place_footer ? 1 : 0,
		);
	}
}

if (!function_exists('site_counters_row_is_onesignal')) {
	function site_counters_row_is_onesignal(array $row) {
		if (!empty($row['kind']) && strtolower((string) $row['kind']) === 'onesignal') {
			return true;
		}
		foreach (array('code_head', 'code_body', 'code_footer', 'code') as $field) {
			if (empty($row[$field])) {
				continue;
			}
			if (function_exists('site_counter_snippet_is_onesignal_web')
				&& site_counter_snippet_is_onesignal_web($row[$field])) {
				return true;
			}
			if (stripos((string) $row[$field], 'onesignal') !== false) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('site_counters_load_settings')) {
	function site_counters_load_settings() {
		$settings = site_counters_settings_defaults();
		if (!function_exists('mysql_select')) {
			return $settings;
		}
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
			return $settings;
		}
		$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'counters_settings' LIMIT 1", 'row');
		if (!$row || $row['value'] === '') {
			return $settings;
		}
		$dec = json_decode($row['value'], true);
		if (!is_array($dec)) {
			return $settings;
		}
		if (isset($dec['source']) && in_array($dec['source'], array('json', 'db'), true)) {
			$settings['source'] = $dec['source'];
		}
		if (array_key_exists('onesignal_web_enabled', $dec)) {
			$settings['onesignal_web_enabled'] = !empty($dec['onesignal_web_enabled']) ? 1 : 0;
		}
		return $settings;
	}
}

if (!function_exists('site_counters_save_settings')) {
	function site_counters_save_settings(array $settings) {
		if (!function_exists('mysql_fn') || !function_exists('mysql_select')) {
			return false;
		}
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
			return false;
		}
		$base = site_counters_settings_defaults();
		$out = array(
			'source' => (isset($settings['source']) && $settings['source'] === 'db') ? 'db' : 'json',
			'onesignal_web_enabled' => !empty($settings['onesignal_web_enabled']) ? 1 : 0,
		);
		$json = json_encode($out, JSON_UNESCAPED_UNICODE);
		$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = 'counters_settings' LIMIT 1", 'row');
		if ($exists && !empty($exists['id'])) {
			mysql_fn('update', 'variables', array('id' => (int) $exists['id'], 'value' => $json));
		} else {
			mysql_fn('insert', 'variables', array('key' => 'counters_settings', 'value' => $json));
		}
		return true;
	}
}

if (!function_exists('site_counters_load_from_db')) {
	function site_counters_load_from_db() {
		if (!function_exists('mysql_select')) {
			return array();
		}
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
			return array();
		}
		$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'counters' LIMIT 1", 'row');
		if (!$row || $row['value'] === '') {
			return array();
		}
		$dec = json_decode($row['value'], true);
		return is_array($dec) ? $dec : array();
	}
}

if (!function_exists('site_counters_save_to_db')) {
	function site_counters_save_to_db(array $counters) {
		if (!function_exists('mysql_fn') || !function_exists('mysql_select')) {
			return false;
		}
		$list = array();
		foreach ($counters as $row) {
			if (!is_array($row)) {
				continue;
			}
			$list[] = site_counters_normalize_row($row);
		}
		$json = json_encode($list, JSON_UNESCAPED_UNICODE);
		$exists = mysql_select("SELECT id FROM `variables` WHERE `key` = 'counters' LIMIT 1", 'row');
		if ($exists && !empty($exists['id'])) {
			mysql_fn('update', 'variables', array('id' => (int) $exists['id'], 'value' => $json));
		} else {
			mysql_fn('insert', 'variables', array('key' => 'counters', 'value' => $json));
		}
		return true;
	}
}

if (!function_exists('site_counters_load_json_file')) {
	/**
	 * @return array{counters:array,settings:array}|null
	 */
	function site_counters_load_json_file($path = null) {
		$path = $path !== null ? (string) $path : site_counters_reference_path();
		if ($path === '' || !is_file($path)) {
			return null;
		}
		$raw = file_get_contents($path);
		if ($raw === false || trim($raw) === '') {
			return null;
		}
		$dec = json_decode($raw, true);
		if (!is_array($dec)) {
			return null;
		}
		$settings = site_counters_settings_defaults();
		if (isset($dec['settings']) && is_array($dec['settings'])) {
			if (isset($dec['settings']['source']) && in_array($dec['settings']['source'], array('json', 'db'), true)) {
				$settings['source'] = $dec['settings']['source'];
			}
			if (array_key_exists('onesignal_web_enabled', $dec['settings'])) {
				$settings['onesignal_web_enabled'] = !empty($dec['settings']['onesignal_web_enabled']) ? 1 : 0;
			}
		}
		$counters = array();
		if (isset($dec['counters']) && is_array($dec['counters'])) {
			$counters = $dec['counters'];
		} elseif (isset($dec[0]) && is_array($dec[0])) {
			$counters = $dec;
		}
		return array('counters' => $counters, 'settings' => $settings);
	}
}

if (!function_exists('site_counters_build_pack')) {
	function site_counters_build_pack(array $counters, array $settings = array()) {
		$settings = array_merge(site_counters_settings_defaults(), $settings);
		$list = array();
		foreach ($counters as $row) {
			if (is_array($row)) {
				$list[] = site_counters_normalize_row($row);
			}
		}
		return array(
			'version' => 1,
			'settings' => $settings,
			'counters' => $list,
		);
	}
}

if (!function_exists('site_counters_effective_list')) {
	function site_counters_effective_list($settings = null) {
		$settings = is_array($settings) ? $settings : site_counters_load_settings();
		$counters = array();
		$json_path = site_counters_reference_path();
		$json_pack = site_counters_load_json_file($json_path);

		if ($settings['source'] === 'json' && $json_pack !== null) {
			$counters = $json_pack['counters'];
		} else {
			$counters = site_counters_load_from_db();
			if (empty($counters) && $json_pack !== null) {
				$counters = $json_pack['counters'];
			}
		}

		if (empty($settings['onesignal_web_enabled'])) {
			$filtered = array();
			foreach ($counters as $row) {
				if (!is_array($row) || site_counters_row_is_onesignal($row)) {
					continue;
				}
				$filtered[] = $row;
			}
			$counters = $filtered;
		}

		return $counters;
	}
}

if (!function_exists('site_counters_js_string')) {
	function site_counters_js_string($value) {
		return str_replace(
			array('\\', '"', "\n", "\r"),
			array('\\\\', '\\"', '\\n', ''),
			(string) $value
		);
	}
}

if (!function_exists('site_counters_localize_onesignal_html')) {
	/**
	 * Replace OneSignal slidedown copy in init with current locale (common dictionary).
	 */
	function site_counters_localize_onesignal_html($html) {
		$html = (string) $html;
		if ($html === '' || stripos($html, 'OneSignal.init') === false || !function_exists('i18n')) {
			return $html;
		}
		$brand = function_exists('site_brand_name') ? site_brand_name() : '';
		$action = trim((string) i18n('common|demo_app_push_soft_body'));
		if ($action === '' || strpos($action, 'common|') === 0) {
			$action = 'Get updates and alerts from ' . $brand . '. You can turn this off anytime in Settings.';
		} else {
			$action = str_replace('{brand}', $brand, $action);
		}
		$accept = trim((string) i18n('common|demo_app_push_soft_allow'));
		if ($accept === '' || strpos($accept, 'common|') === 0) {
			$accept = 'Allow notifications';
		}
		$cancel = trim((string) i18n('common|demo_app_push_soft_cancel'));
		if ($cancel === '' || strpos($cancel, 'common|') === 0) {
			$cancel = 'Not now';
		}
		$replacements = array(
			'/actionMessage\s*:\s*"[^"]*"/' => 'actionMessage: "' . site_counters_js_string($action) . '"',
			'/acceptButton\s*:\s*"[^"]*"/' => 'acceptButton: "' . site_counters_js_string($accept) . '"',
			'/cancelButton\s*:\s*"[^"]*"/' => 'cancelButton: "' . site_counters_js_string($cancel) . '"',
		);
		foreach ($replacements as $pattern => $replacement) {
			$html = preg_replace($pattern, $replacement, $html, 1);
		}
		return $html;
	}
}

if (!function_exists('site_counters_hydrate_abc')) {
	function site_counters_hydrate_abc(array &$abc) {
		$abc['counters_head'] = array();
		$abc['counters_body'] = array();
		$abc['counters_footer'] = array();
		$abc['counters_settings'] = site_counters_load_settings();

		$counters = site_counters_effective_list($abc['counters_settings']);
		foreach ($counters as $c) {
			if (empty($c['display'])) {
				continue;
			}
			$row = site_counters_normalize_row($c);
			if ($row['place_head'] && trim($row['code_head']) !== '') {
				$abc['counters_head'][] = site_counters_localize_onesignal_html(trim($row['code_head']));
			}
			if ($row['place_body'] && trim($row['code_body']) !== '') {
				$abc['counters_body'][] = trim($row['code_body']);
			}
			if ($row['place_footer'] && trim($row['code_footer']) !== '') {
				$abc['counters_footer'][] = trim($row['code_footer']);
			}
		}

		if (empty($abc['counters_head']) && empty($abc['counters_body']) && empty($abc['counters_footer'])) {
			if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') === 0) {
				$abc['counters_head'][] = '<script src="https://cdn.counter.dev/script.js" data-id="a555a78e-c2d3-41eb-95d4-8c319224b944" data-utcoffset="3"></script>';
			}
		}

		if (!function_exists('site_counters_strip_onesignal_web_for_native_shell')) {
			if (is_file((defined('ROOT_DIR') ? ROOT_DIR : '') . 'functions/site_median_shell.php')) {
				require_once (defined('ROOT_DIR') ? ROOT_DIR : '') . 'functions/site_median_shell.php';
			}
		}
		if (function_exists('site_counters_strip_onesignal_web_for_native_shell')) {
			site_counters_strip_onesignal_web_for_native_shell($abc['counters_head'], $abc['counters_body'], $abc['counters_footer']);
		}

		$abc['counters'] = $abc['counters_head'];
	}
}

if (!function_exists('site_counters_export_pack')) {
	function site_counters_export_pack($source = 'db') {
		$settings = site_counters_load_settings();
		if ($source === 'json') {
			$json_pack = site_counters_load_json_file();
			if ($json_pack !== null) {
				return site_counters_build_pack($json_pack['counters'], array_merge($settings, $json_pack['settings']));
			}
		}
		return site_counters_build_pack(site_counters_load_from_db(), $settings);
	}
}

if (!function_exists('site_counters_import_pack')) {
	/**
	 * @param array $pack from JSON decode
	 * @param string $target db|file|both
	 * @return array{ok:bool,message:string}
	 */
	function site_counters_import_pack(array $pack, $target = 'db') {
		$counters = array();
		if (isset($pack['counters']) && is_array($pack['counters'])) {
			$counters = $pack['counters'];
		} elseif (isset($pack[0])) {
			$counters = $pack;
		}
		if (empty($counters)) {
			return array('ok' => false, 'message' => 'No counters in pack');
		}
		$settings = site_counters_load_settings();
		if (isset($pack['settings']) && is_array($pack['settings'])) {
			$settings = array_merge($settings, $pack['settings']);
		}
		if ($target === 'db' || $target === 'both') {
			site_counters_save_to_db($counters);
			site_counters_save_settings($settings);
		}
		if ($target === 'file' || $target === 'both') {
			$path = site_counters_reference_path();
			$dir = dirname($path);
			if (!is_dir($dir)) {
				@mkdir($dir, 0755, true);
			}
			$json = json_encode(site_counters_build_pack($counters, $settings), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			if (@file_put_contents($path, $json . "\n") === false) {
				return array('ok' => false, 'message' => 'Failed to write ' . $path);
			}
		}
		return array('ok' => true, 'message' => 'Counters imported (' . count($counters) . ' row(s))');
	}
}
