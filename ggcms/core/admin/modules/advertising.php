<?php
/**
 * Advertising: mode (Self-managed / External API), Banners & ads.
 * API mode: token + api_sources (backup URLs for backend). Same exchange logic as agent sites (oxd).
 */
$page_name = 'Advertising';

$ad_tab = isset($get['tab']) ? $get['tab'] : 'mode';
if (!in_array($ad_tab, array('mode', 'banners'), true)) $ad_tab = 'mode';

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
$ad_saved = false;
$ad_test_result = null;

// ----- Load advertising_api from variables (before test so we can override from POST)
$ad_config = array('mode' => 'self', 'token' => '', 'api_sources' => array(), 'api_sources_banners' => array(), 'api_sources_priority' => '', 'api_url' => '', 'banner_popup_delay_seconds' => 30);
if ($variables_exists) {
	$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'advertising_api' LIMIT 1", 'row');
	if ($row && $row['value'] !== '') {
		$dec = json_decode($row['value'], true);
		if (is_array($dec)) {
			$ad_config['mode'] = isset($dec['mode']) && $dec['mode'] === 'api' ? 'api' : 'self';
			$ad_config['token'] = isset($dec['token']) ? (string)$dec['token'] : '';
			$ad_config['api_sources'] = isset($dec['api_sources']) && is_array($dec['api_sources']) ? $dec['api_sources'] : array();
			$ad_config['api_sources_banners'] = isset($dec['api_sources_banners']) && is_array($dec['api_sources_banners']) ? $dec['api_sources_banners'] : array();
			$ad_config['api_sources_priority'] = isset($dec['api_sources_priority']) ? (string)$dec['api_sources_priority'] : '';
			$ad_config['api_url'] = isset($dec['api_url']) ? (string)$dec['api_url'] : '';
			$ad_config['banner_popup_delay_seconds'] = isset($dec['banner_popup_delay_seconds']) ? max(1, min(600, (int)$dec['banner_popup_delay_seconds'])) : 30;
			$ad_config['popup_enabled'] = !isset($dec['popup_enabled']) || !empty($dec['popup_enabled']) ? 1 : 0;
		}
	}
}

// ----- Test request (POST, no save): run one request and show debug message
$ad_test_clicked = !empty($_POST['test_banners']) || !empty($_POST['test_links']) || !empty($_POST['test_backend']);
if ($ad_tab === 'mode' && !empty($_POST) && $ad_test_clicked) {
	$test_token = isset($_POST['ad_api_token']) ? trim((string)$_POST['ad_api_token']) : (string)$ad_config['token'];
	// Resolve country like frontend: Cloudflare header first, then geo by IP
	$test_country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(substr(trim($_SERVER['HTTP_CF_IPCOUNTRY']), 0, 2)) : '';
	if ($test_country === '' || $test_country === 'T1') $test_country = 'XX';
	if ($test_country === 'XX' && !empty($_SERVER['REMOTE_ADDR'])) {
		if (!function_exists('site_ad_country_by_ip')) {
			require_once(defined('ROOT_DIR') ? ROOT_DIR . 'functions/advertising_api.php' : dirname(__DIR__, 2) . '/functions/advertising_api.php');
		}
		$by_ip = site_ad_country_by_ip($_SERVER['REMOTE_ADDR']);
		if ($by_ip !== '') $test_country = $by_ip;
	}
	$build_banner_url = function($base, $token, $country) {
		$base = rtrim($base, '/');
		if (strpos($base, '?') !== false) {
			$url = $base;
			if (strpos($base, 'token=') === false && $token !== '') $url .= '&token=' . rawurlencode($token);
			if (preg_match('/[?&]country=/', $base) === 0) $url .= '&country=' . rawurlencode($country);
			return $url;
		}
		$query = ($token !== '' ? 'token=' . rawurlencode($token) . '&' : '') . 'country=' . rawurlencode($country);
		return (strpos($base, 'b.php') !== false) ? $base . '?' . $query : $base . '/banner?' . $query;
	};
	// Fetch URL and return [ 'body' => string, 'http_code' => int, 'error' => string ]
	$ad_test_fetch = function($url) {
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 10,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => 0,
			));
			$body = curl_exec($ch);
			$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$err = curl_error($ch);
			curl_close($ch);
			return array('body' => $body !== false ? $body : '', 'http_code' => $code, 'error' => $err ?: '');
		}
		$ctx = stream_context_create(array(
			'http' => array('timeout' => 10),
			'ssl' => array('verify_peer' => false, 'verify_peer_name' => false),
		));
		$body = @file_get_contents($url, false, $ctx);
		$code = 0;
		if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
			if (preg_match('/^HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m)) $code = (int)$m[1];
		}
		$err = ($body === false) ? (error_get_last()['message'] ?? 'Unknown error') : '';
		return array('body' => $body !== false ? $body : '', 'http_code' => $code, 'error' => $err);
	};

	if (!empty($_POST['test_banners'])) {
		$raw = isset($_POST['ad_api_sources_banners']) ? trim((string)$_POST['ad_api_sources_banners']) : '';
		$lines = preg_split('/[\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
		$first = $lines ? trim(rtrim($lines[0], '/')) : '';
		if ($first !== '') {
			$test_url = $build_banner_url($first, $test_token, $test_country);
			$fetch = $ad_test_fetch($test_url);
			$ok = $fetch['http_code'] >= 200 && $fetch['http_code'] < 300 && $fetch['body'] !== '';
			$body_display = $fetch['body'] !== '' ? $fetch['body'] : '';
			if ($body_display === '' && ($fetch['error'] !== '' || $fetch['http_code'] > 0)) {
				$body_display = 'HTTP ' . $fetch['http_code'];
				if ($fetch['error'] !== '') $body_display .= "\n" . $fetch['error'];
				if ($fetch['body'] === '' && $fetch['error'] === '' && $fetch['http_code'] >= 400) $body_display .= "\n(empty body)";
			}
			if ($body_display === '') $body_display = 'Request failed or empty response';
			$ad_test_result = array(
				'type' => 'banners',
				'url' => $test_url,
				'country' => $test_country,
				'ok' => $ok,
				'body' => $body_display,
			);
		} else {
			$ad_test_result = array('type' => 'banners', 'url' => '', 'country' => $test_country, 'ok' => false, 'body' => 'No URL in Banners field.');
		}
		// Keep form values from POST for display
		$ad_config['token'] = $test_token;
		$ad_config['api_sources_banners'] = array();
		foreach ($lines ? $lines : array() as $line) { $u = trim(rtrim($line, '/')); if ($u !== '') $ad_config['api_sources_banners'][] = $u; }
		$ad_config['api_sources'] = isset($_POST['ad_api_sources']) ? array_filter(array_map(function($l) { $u = trim(rtrim($l, '/')); return $u === '' ? null : $u; }, preg_split('/[\r\n]+/', trim((string)$_POST['ad_api_sources']), -1, PREG_SPLIT_NO_EMPTY))) : $ad_config['api_sources'];
	} elseif (!empty($_POST['test_links'])) {
		$raw = isset($_POST['ad_api_sources']) ? trim((string)$_POST['ad_api_sources']) : '';
		$lines = preg_split('/[\r\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
		$first = $lines ? trim(rtrim($lines[0], '/')) : '';
		if ($first !== '') {
			$sep = (strpos($first, '?') !== false) ? '&' : '?';
			$add = (strpos($first, 'country=') === false) ? $sep . 'country=' . rawurlencode($test_country) : '';
			if (strpos($first, 'token=') === false && $test_token !== '') $add = $sep . 'token=' . rawurlencode($test_token) . '&country=' . rawurlencode($test_country);
			$test_url = $first . $add;
			$fetch = $ad_test_fetch($test_url);
			$ok = $fetch['http_code'] >= 200 && $fetch['http_code'] < 300 && $fetch['body'] !== '';
			$body_display = $fetch['body'] !== '' ? $fetch['body'] : '';
			if ($body_display === '' && ($fetch['error'] !== '' || $fetch['http_code'] > 0)) {
				$body_display = 'HTTP ' . $fetch['http_code'];
				if ($fetch['error'] !== '') $body_display .= "\n" . $fetch['error'];
				if ($fetch['body'] === '' && $fetch['error'] === '' && $fetch['http_code'] >= 400) $body_display .= "\n(empty body)";
			}
			if ($body_display === '') $body_display = 'Request failed or empty response';
			$ad_test_result = array(
				'type' => 'links',
				'url' => $test_url,
				'country' => $test_country,
				'ok' => $ok,
				'body' => $body_display,
			);
		} else {
			$ad_test_result = array('type' => 'links', 'url' => '', 'country' => $test_country, 'ok' => false, 'body' => 'No URL in Links field.');
		}
		$ad_config['token'] = $test_token;
		$ad_config['api_sources'] = array();
		foreach ($lines ? $lines : array() as $line) { $u = trim(rtrim($line, '/')); if ($u !== '') $ad_config['api_sources'][] = $u; }
		$raw_b = isset($_POST['ad_api_sources_banners']) ? trim((string)$_POST['ad_api_sources_banners']) : '';
		$ad_config['api_sources_banners'] = array();
		foreach (preg_split('/[\r\n]+/', $raw_b, -1, PREG_SPLIT_NO_EMPTY) as $line) { $u = trim(rtrim($line, '/')); if ($u !== '') $ad_config['api_sources_banners'][] = $u; }
	} elseif (!empty($_POST['test_backend'])) {
		$base = isset($_POST['ad_api_url']) ? trim((string)$_POST['ad_api_url']) : (string)$ad_config['api_url'];
		$base = rtrim($base, '/');
		$test_link_code = isset($_POST['ad_api_test_link']) ? trim((string)$_POST['ad_api_test_link']) : '';
		$test_banner_code = isset($_POST['ad_api_test_banner']) ? trim((string)$_POST['ad_api_test_banner']) : '';
		if ($base !== '' && $test_link_code !== '') {
			// Emulate real redirect API call with provided link_code and optional banner.
			$query = ($test_token !== '' ? 'token=' . rawurlencode($test_token) . '&' : '') . 'link_code=' . rawurlencode($test_link_code);
			if ($test_banner_code !== '') {
				$query .= '&banner=' . rawurlencode($test_banner_code);
			}
			$test_url = $base . '/redirect?' . $query;
			$fetch = $ad_test_fetch($test_url);
			$ok = $fetch['http_code'] >= 200 && $fetch['http_code'] < 400 && $fetch['body'] !== '';
			$body_display = $fetch['body'] !== '' ? $fetch['body'] : '';
			if ($body_display === '' && ($fetch['error'] !== '' || $fetch['http_code'] > 0)) {
				$body_display = 'HTTP ' . $fetch['http_code'];
				if ($fetch['error'] !== '') $body_display .= "\n" . $fetch['error'];
				if ($fetch['body'] === '' && $fetch['error'] === '' && $fetch['http_code'] >= 400) $body_display .= "\n(empty body)";
			}
			if ($body_display === '') $body_display = 'Request failed or empty response';
			$ad_test_result = array(
				'type' => 'backend',
				'url' => $test_url,
				'country' => '',
				'ok' => $ok,
				'body' => $body_display,
			);
		} else {
			$msg = $base === '' ? 'No Backend API base URL.' : 'No link_code provided for Backend API test.';
			$ad_test_result = array('type' => 'backend', 'url' => '', 'country' => '', 'ok' => false, 'body' => $msg);
		}
		$ad_config['token'] = $test_token;
		$ad_config['api_url'] = isset($_POST['ad_api_url']) ? trim((string)$_POST['ad_api_url']) : $ad_config['api_url'];
	}
}

// ----- Save Mode tab (POST) — only when Save was clicked, not Test request
if ($ad_tab === 'mode' && $variables_exists && !empty($_POST['advertising_mode_save']) && !$ad_test_clicked) {
	$mode = isset($_POST['ad_mode']) && $_POST['ad_mode'] === 'api' ? 'api' : 'self';
	$token = isset($_POST['ad_api_token']) ? trim((string)$_POST['ad_api_token']) : '';
	$api_url = isset($_POST['ad_api_url']) ? trim((string)$_POST['ad_api_url']) : '';
	$sources_raw = isset($_POST['ad_api_sources']) ? trim((string)$_POST['ad_api_sources']) : '';
	$sources_banners_raw = isset($_POST['ad_api_sources_banners']) ? trim((string)$_POST['ad_api_sources_banners']) : '';
	$priority = isset($_POST['ad_api_sources_priority']) ? trim((string)$_POST['ad_api_sources_priority']) : '';
	$api_sources = array();
	foreach (preg_split('/[\r\n]+/', $sources_raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
		$u = trim(rtrim($line, '/'));
		if ($u !== '' && !in_array($u, $api_sources)) $api_sources[] = $u;
	}
	$api_sources_banners = array();
	foreach (preg_split('/[\r\n]+/', $sources_banners_raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
		$u = trim(rtrim($line, '/'));
		if ($u !== '' && !in_array($u, $api_sources_banners)) $api_sources_banners[] = $u;
	}
	$popup_delay = isset($_POST['ad_banner_popup_delay']) ? (int)$_POST['ad_banner_popup_delay'] : 30;
	if ($popup_delay < 1) $popup_delay = 1;
	if ($popup_delay > 600) $popup_delay = 600;
	$popup_enabled = !empty($_POST['ad_popup_enabled']) ? 1 : 0;
	if ($mode === 'self') {
		$token = '';
		$api_sources = array();
		$api_sources_banners = array();
		$priority = '';
		$api_url = '';
	}
	$payload = array(
		'mode'   => $mode,
		'token'  => $token,
		'api_sources' => $api_sources,
		'api_sources_banners' => $api_sources_banners,
		'api_sources_priority' => $priority,
		'api_url' => $api_url,
		'banner_popup_delay_seconds' => $popup_delay,
		'popup_enabled' => $popup_enabled,
	);
	$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
	$exists = mysql_select("SELECT id, value FROM `variables` WHERE `key` = 'advertising_api' LIMIT 1", 'row');
	if ($exists && !empty($exists['id'])) {
		mysql_fn('update', 'variables', array('id' => $exists['id'], 'value' => $json));
	} else {
		mysql_fn('insert', 'variables', array('key' => 'advertising_api', 'value' => $json));
	}
	$ad_saved = true;
	header('Location: /admin.php?m=advertising&tab=mode&saved=1');
	exit;
}

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">' . htmlspecialchars($page_name) . '</h5>';
$content .= '<ul class="nav nav-tabs mb-4">';
$content .= '<li class="nav-item"><a class="nav-link' . ($ad_tab === 'mode' ? ' active' : '') . '" href="/admin.php?m=advertising&amp;tab=mode">Mode</a></li>';
$content .= '<li class="nav-item"><a class="nav-link' . ($ad_tab === 'banners' ? ' active' : '') . '" href="/admin.php?m=advertising&amp;tab=banners">Banners &amp; ads</a></li>';
$content .= '</ul>';

if ($ad_tab === 'mode') {
	if (!empty($get['saved'])) {
		$content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
	}
	if ($ad_test_result !== null) {
		$cls = $ad_test_result['ok'] ? 'alert-info' : 'alert-warning';
		$label = ($ad_test_result['type'] === 'banners') ? 'Banners API' : (($ad_test_result['type'] === 'backend') ? 'Backend API (/redirect)' : 'Links API');
		$content .= '<div class="alert ' . $cls . ' mb-4"><strong>Test: ' . htmlspecialchars($label) . '</strong>';
		if (!empty($ad_test_result['country'])) {
			$content .= ' <small class="text-muted">Country <code>' . htmlspecialchars($ad_test_result['country']) . '</code></small>';
		}
		if ($ad_test_result['url'] !== '') {
			$content .= '<div class="small text-muted mt-1 mb-2 text-break">' . htmlspecialchars($ad_test_result['url']) . '</div>';
		}
		$content .= '<pre class="mb-0 mt-2 ad-api-debug bg-light border rounded p-2">' . htmlspecialchars($ad_test_result['body']) . '</pre></div>';
	}
	if (!$variables_exists) {
		$content .= '<div class="alert alert-warning">Table <code>variables</code> missing. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank" rel="noopener">run_migrate_BD.php</a>.</div>';
	} else {
		$api_card_hidden = ($ad_config['mode'] !== 'api') ? 'display:none' : '';
		$content .= '<form method="post" action="/admin.php?m=advertising&amp;tab=mode">';
		$content .= '<input type="hidden" name="advertising_mode_save" value="1">';

		$content .= '<div class="card mb-4">';
		$content .= '<div class="card-header bg-light"><strong>Mode</strong></div>';
		$content .= '<div class="card-body">';
		$content .= '<div class="form-group mb-0">';
		$content .= '<label class="form-label d-block mb-2">Source</label>';
		$content .= '<div class="form-check form-check-inline">';
		$content .= '<input class="form-check-input" type="radio" name="ad_mode" id="ad_mode_self" value="self" ' . ($ad_config['mode'] === 'self' ? 'checked' : '') . '>';
		$content .= '<label class="form-check-label" for="ad_mode_self">Self-managed</label></div>';
		$content .= '<div class="form-check form-check-inline">';
		$content .= '<input class="form-check-input" type="radio" name="ad_mode" id="ad_mode_api" value="api" ' . ($ad_config['mode'] === 'api' ? 'checked' : '') . '>';
		$content .= '<label class="form-check-label" for="ad_mode_api">External API</label></div>';
		$content .= '</div></div></div>';

		$content .= '<div id="ad-api-card" class="card mb-4 border-primary" style="border-width:2px;' . $api_card_hidden . '">';
		$content .= '<div class="card-header bg-white"><strong>API backend</strong> <span class="badge badge-primary align-middle ml-1">/go /redirect</span></div>';
		$content .= '<div class="card-body">';

		$content .= '<h6 class="text-uppercase text-muted small mb-3" style="letter-spacing:.04em;">Credentials</h6>';
		$content .= '<div class="form-group"><label class="form-label" for="ad_api_token">API token</label>';
		$content .= '<input type="text" class="form-control" name="ad_api_token" id="ad_api_token" value="' . htmlspecialchars($ad_config['token']) . '" placeholder="From backend Sites → API" maxlength="64"></div>';

		$content .= '<div class="form-group"><label class="form-label" for="ad_api_url">Backend base URL</label>';
		$content .= '<div class="form-row align-items-end">';
		$content .= '<div class="form-group col-md-8 mb-md-0"><input type="text" class="form-control" name="ad_api_url" id="ad_api_url" value="' . htmlspecialchars($ad_config['api_url']) . '" placeholder="https://example.com/api"></div>';
		$content .= '<div class="form-group col-md-4 mb-md-0"><button type="submit" name="test_backend" value="1" class="btn btn-outline-secondary btn-sm btn-block">Test /redirect</button></div>';
		$content .= '</div>';
		$content .= '<div class="form-row mt-2">';
		$content .= '<div class="form-group col-md-4 col-6 mb-0"><label class="form-label small text-muted" for="ad_api_test_link">link_code</label><input type="text" class="form-control form-control-sm" name="ad_api_test_link" id="ad_api_test_link" value="" placeholder="e.g. FT6Nh"></div>';
		$content .= '<div class="form-group col-md-4 col-6 mb-0"><label class="form-label small text-muted" for="ad_api_test_banner">banner (optional)</label><input type="text" class="form-control form-control-sm" name="ad_api_test_banner" id="ad_api_test_banner" value="" placeholder="e.g. KFLIB"></div>';
		$content .= '</div></div>';

		$content .= '<hr class="my-4">';
		$content .= '<h6 class="text-uppercase text-muted small mb-3" style="letter-spacing:.04em;">Links &amp; offers</h6>';
		$content .= '<div class="form-group"><label class="form-label" for="ad_api_sources">URLs, one per line</label>';
		$content .= '<div class="form-row">';
		$content .= '<div class="form-group col-md-9 mb-md-0"><textarea class="form-control" name="ad_api_sources" id="ad_api_sources" rows="4" placeholder="https://…">' . htmlspecialchars(implode("\n", $ad_config['api_sources'])) . '</textarea></div>';
		$content .= '<div class="form-group col-md-3 mb-md-0"><button type="submit" name="test_links" value="1" class="btn btn-outline-secondary btn-sm btn-block">Test first URL</button></div>';
		$content .= '</div></div>';

		$content .= '<div class="form-group mb-0"><label class="form-label" for="ad_api_sources_banners">Banner API URLs, one per line</label>';
		$content .= '<div class="form-row">';
		$content .= '<div class="form-group col-md-9 mb-md-0"><textarea class="form-control" name="ad_api_sources_banners" id="ad_api_sources_banners" rows="4" placeholder="https://…/b.php">' . htmlspecialchars(implode("\n", $ad_config['api_sources_banners'])) . '</textarea></div>';
		$content .= '<div class="form-group col-md-3 mb-md-0"><button type="submit" name="test_banners" value="1" class="btn btn-outline-secondary btn-sm btn-block">Test first URL</button></div>';
		$content .= '</div></div>';

		$content .= '<hr class="my-4">';
		$content .= '<h6 class="text-uppercase text-muted small mb-3" style="letter-spacing:.04em;">Frontend</h6>';
		$content .= '<div class="form-row">';
		$content .= '<div class="form-group col-md-8"><label class="form-label" for="ad_api_sources_priority">Priority link URL (optional)</label><input type="text" class="form-control" name="ad_api_sources_priority" id="ad_api_sources_priority" value="' . htmlspecialchars($ad_config['api_sources_priority']) . '" placeholder="Try before other link URLs"></div>';
		$content .= '<div class="form-group col-md-4"><label class="form-label" for="ad_banner_popup_delay">Popup delay (s)</label><input type="number" class="form-control" name="ad_banner_popup_delay" id="ad_banner_popup_delay" value="' . (int)$ad_config['banner_popup_delay_seconds'] . '" min="1" max="600" step="1"></div>';
		$content .= '<div class="form-group col-12">';
		$content .= '<div class="form-check form-switch">';
		$content .= '<input class="form-check-input" type="checkbox" name="ad_popup_enabled" id="ad_popup_enabled" value="1" ' . (!empty($ad_config['popup_enabled']) ? 'checked' : '') . '>';
		$content .= '<label class="form-check-label" for="ad_popup_enabled"><strong>Enable popup banner</strong> (partner promo)</label>';
		$content .= '</div></div>';
		$content .= '</div>';

		$content .= '</div></div>';

		$content .= '<button type="submit" class="btn btn-primary mb-2">Save settings</button>';
		$content .= '</form>';
		$content .= '<script>(function(){var card=document.getElementById("ad-api-card");if(!card)return;function sync(){var api=document.getElementById("ad_mode_api");card.style.display=api&&api.checked?"":"none";}document.querySelectorAll("input[name=ad_mode]").forEach(function(r){r.addEventListener("change",sync);});})();</script>';
	}
} else {
	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Banners &amp; ads</strong></div>';
	$content .= '<div class="card-body"><p class="text-muted mb-0">Banner zones and assignments are driven from API settings on the <strong>Mode</strong> tab.</p></div></div>';
}

$content .= '</div>';
