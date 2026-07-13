<?php

/**
 * Spribe / mirror demo provider (aviator-log-in overlay only).
 */
require_once ROOT_DIR . 'functions/game_demo_embed.php';

function game_demo_spribe_currency_param(array $abc, array $config = array()) {
	if (!empty($_GET['country']) && preg_match('/^[A-Za-z]{2}$/', $_GET['country'])) {
		$country = strtoupper($_GET['country']);
		return game_demo_currency_normalize_for_launch(game_demo_currency_for_country($country));
	}
	if (!empty($_GET['currency']) && preg_match('/^[A-Za-z]{3}$/', $_GET['currency'])) {
		return strtoupper($_GET['currency']);
	}
	return game_demo_currency_param($abc, $config);
}

function game_demo_mirror_config_value(array $config, $name, $default = '') {
	$neutral = array(
		'game_demo_mirror_' . $name,
		'game_demo_' . $name,
	);
	foreach ($neutral as $key) {
		if (!empty($config[$key])) {
			return trim((string) $config[$key]);
		}
	}
	$legacy = 'aviator_demo_mirror_' . $name;
	if (!empty($config[$legacy])) {
		return trim((string) $config[$legacy]);
	}
	return $default;
}

function game_demo_mirror_resolve_params(array $abc, array $config) {
	$currency = game_demo_spribe_currency_param($abc, $config);
	$langSpribe = game_demo_lang_param($abc);
	$lang = strtolower($langSpribe);

	$apiUrl = game_demo_mirror_config_value($config, 'api_url', 'https://fwu21pcmpk1m14q.gmngdoor.link/');
	$gameId = game_demo_mirror_config_value($config, 'game_id', 'aviator_spribe');

	$bankGroupId = game_demo_mirror_config_value($config, 'bank_group_id', '');
	if ($bankGroupId === '') {
		$bankGroupId = 'GoldenBet_' . $currency;
	}

	if (!function_exists('site_ad_resolve_ip_context') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/advertising_api.php')) {
		require_once ROOT_DIR . 'functions/advertising_api.php';
	}
	$playerIp = '127.0.0.1';
	if (function_exists('site_ad_resolve_ip_context')) {
		$ad = (isset($abc['advertising_api']) && is_array($abc['advertising_api'])) ? $abc['advertising_api'] : array();
		$ip_ctx = site_ad_resolve_ip_context($ad);
		$playerIp = isset($ip_ctx['ip_sent_to_backend']) ? trim((string) $ip_ctx['ip_sent_to_backend']) : '127.0.0.1';
	}

	$spribeFallbackUrl = 'https://demo.spribe.io/launch/aviator?' . http_build_query(
		array('currency' => $currency, 'lang' => $langSpribe),
		'',
		'&',
		PHP_QUERY_RFC3986
	);

	return array(
		'mirror_api_url' => $apiUrl,
		'game_id' => $gameId,
		'bank_group_id' => $bankGroupId,
		'currency' => $currency,
		'lang_spribe' => $langSpribe,
		'lang' => $lang,
		'player_ip' => $playerIp,
		'spribe_fallback_url' => $spribeFallbackUrl,
	);
}

/**
 * Build Session.CreateDemo JSON-RPC payload (server-side: real PlayerIp).
 */
function game_demo_mirror_session_payload(array $params, $playerIpOverride = null) {
	$playerIp = ($playerIpOverride !== null) ? (string) $playerIpOverride : (string) $params['player_ip'];
	return array(
		'jsonrpc' => '2.0',
		'method' => 'Session.CreateDemo',
		'id' => (int) time(),
		'params' => array(
			'BankGroupId' => $params['bank_group_id'],
			'GameId' => $params['game_id'],
			'StartBalance' => 10000,
			'Params' => array(
				'language' => $params['lang'],
			),
			'PlayerIp' => $playerIp,
		),
	);
}

/**
 * Lightweight HTTP probe (HEAD, fallback GET) for debug pages.
 */
function game_demo_probe_http_url($url, $timeoutSec = 5) {
	$url = trim((string) $url);
	if ($url === '' || !preg_match('#^https?://#i', $url)) {
		return array('url' => $url, 'ok' => false, 'error' => 'invalid_url');
	}
	$started = microtime(true);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeoutSec);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(3, (int) $timeoutSec));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_exec($ch);
	$err = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	$elapsedMs = (int) round((microtime(true) - $started) * 1000);
	$httpCode = isset($info['http_code']) ? (int) $info['http_code'] : 0;
	if ($httpCode === 0 && $err !== '') {
		return array(
			'url' => $url,
			'ok' => false,
			'method' => 'HEAD',
			'http_code' => 0,
			'curl_error' => $err,
			'elapsed_ms' => $elapsedMs,
		);
	}
	return array(
		'url' => $url,
		'ok' => ($httpCode >= 200 && $httpCode < 400),
		'method' => 'HEAD',
		'http_code' => $httpCode,
		'effective_url' => isset($info['url']) ? (string) $info['url'] : $url,
		'elapsed_ms' => $elapsedMs,
	);
}

/**
 * Server-side mirror Session.CreateDemo probe with full request/response capture.
 */
function game_demo_mirror_session_probe(array $abc, array $config, array $opts = array()) {
	$params = game_demo_mirror_resolve_params($abc, $config);
	$timeout = isset($opts['timeout']) ? max(1, (int) $opts['timeout']) : 4;
	$connectTimeout = isset($opts['connect_timeout']) ? max(1, (int) $opts['connect_timeout']) : 3;
	$payload = game_demo_mirror_session_payload($params);

	$started = microtime(true);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $params['mirror_api_url']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$response = curl_exec($ch);
	$err = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	$data = ($response !== false && $response !== '') ? json_decode($response, true) : null;
	$launchUrl = (is_array($data) && isset($data['result']['Url'])) ? trim((string) $data['result']['Url']) : null;

	return array(
		'api_url_called' => $params['mirror_api_url'],
		'payload_sent' => $payload,
		'http_code' => isset($info['http_code']) ? (int) $info['http_code'] : 0,
		'curl_error' => $err !== '' ? $err : null,
		'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
		'response_raw' => $response !== false ? $response : null,
		'response_parsed' => $data,
		'launch_url' => ($launchUrl !== null && $launchUrl !== '') ? $launchUrl : null,
		'success' => ($launchUrl !== null && $launchUrl !== ''),
	);
}

/**
 * Full debug payload for /{lang}/demo/app/?debug_ip_check=1 (admin only).
 */
function game_demo_app_build_debug_payload(array $abc, array $config) {
	if (!function_exists('site_ad_resolve_ip_context') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/advertising_api.php')) {
		require_once ROOT_DIR . 'functions/advertising_api.php';
	}

	$params = game_demo_mirror_resolve_params($abc, $config);
	$ad = (isset($abc['advertising_api']) && is_array($abc['advertising_api'])) ? $abc['advertising_api'] : array();
	$ip_ctx = function_exists('site_ad_resolve_ip_context') ? site_ad_resolve_ip_context($ad) : array();
	$country_ctx = function_exists('site_ad_resolve_country_context') ? site_ad_resolve_country_context($ad, $ip_ctx) : array();

	$serverProbe = game_demo_mirror_session_probe($abc, $config);
	$clientPayload = game_demo_mirror_session_payload($params, '');

	$launchSource = 'spribe_fallback';
	$iframeUrl = $params['spribe_fallback_url'];
	if (!empty($serverProbe['launch_url'])) {
		$launchSource = 'mirror';
		$iframeUrl = $serverProbe['launch_url'];
	}

	$spribeProbe = game_demo_probe_http_url($params['spribe_fallback_url'], 5);
	$mirrorLaunchProbe = null;
	if (!empty($serverProbe['launch_url'])) {
		$mirrorLaunchProbe = game_demo_probe_http_url($serverProbe['launch_url'], 5);
	}

	return array(
		'page' => 'demo_app',
		'note' => 'Game iframe is not loaded in this mode. Server-side probes only.',
		'request' => array(
			'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
			'query' => isset($_GET) && is_array($_GET) ? $_GET : array(),
			'layout' => isset($abc['layout']) ? (string) $abc['layout'] : '',
		),
		'ip_country' => array(
			'remote_addr' => isset($ip_ctx['remote_addr']) ? (string) $ip_ctx['remote_addr'] : '',
			'trusted_real_ip' => isset($ip_ctx['trusted_real_ip']) ? (string) $ip_ctx['trusted_real_ip'] : '',
			'ip_sent_to_backend' => isset($ip_ctx['ip_sent_to_backend']) ? (string) $ip_ctx['ip_sent_to_backend'] : '',
			'country_header_cf' => isset($country_ctx['country_header_cf']) ? (string) $country_ctx['country_header_cf'] : '',
			'country_by_local_geo' => isset($country_ctx['country_by_local_geo']) ? (string) $country_ctx['country_by_local_geo'] : '',
			'country_sent_to_backend' => isset($country_ctx['country_sent_to_backend']) ? (string) $country_ctx['country_sent_to_backend'] : '',
			'source_of_country' => isset($country_ctx['source_of_country']) ? (string) $country_ctx['source_of_country'] : '',
		),
		'resolved_params' => $params,
		'production_flow' => array(
			'step_1' => 'Browser fetch() POST to mirror_api_url with Session.CreateDemo (timeout ~2800ms, PlayerIp empty string in JS).',
			'step_2' => 'If result.Url present → iframe.src = mirror launch URL.',
			'step_3' => 'On error/timeout → iframe.src = spribe_fallback_url.',
			'client_payload' => $clientPayload,
			'client_timeout_ms' => 2800,
			'server_side_note' => 'Server-side probe below uses real PlayerIp; browser JS currently sends PlayerIp as empty string.',
		),
		'mirror_api_probe_server' => $serverProbe,
		'spribe_fallback_probe' => $spribeProbe,
		'mirror_launch_url_probe' => $mirrorLaunchProbe,
		'launch_decision' => array(
			'source' => $launchSource,
			'iframe_url_would_be' => $iframeUrl,
			'mirror_session_ok' => !empty($serverProbe['success']),
		),
	);
}

/**
 * Attempt to request a dynamic session from the mirror game aggregator API (e.g., gmngdoor.link).
 *
 * @param array $abc Template globals.
 * @param array $config Site config.
 * @return string|null Resolved game launch URL, or null on failure/timeout.
 */
function game_demo_load_mirror_url(array $abc, array $config) {
	$probe = game_demo_mirror_session_probe($abc, $config);

	if (!empty($GLOBALS['abc']['debug_ip_check'])) {
		$GLOBALS['abc']['debug_ip_check_info']['mirror_api_debug'] = array(
			'api_url_called' => $probe['api_url_called'],
			'payload_sent' => $probe['payload_sent'],
			'http_code' => $probe['http_code'],
			'curl_error' => $probe['curl_error'],
			'response_raw' => $probe['response_raw'],
			'response_parsed' => $probe['response_parsed'],
		);
	}

	if (!empty($probe['curl_error']) && empty($probe['response_raw'])) {
		error_log('Aviator mirror demo session cURL error: ' . $probe['curl_error']);
		return null;
	}

	if (!empty($probe['launch_url'])) {
		return $probe['launch_url'];
	}

	if (is_array($probe['response_parsed']) && isset($probe['response_parsed']['error'])) {
		error_log('Aviator mirror demo session API error: ' . json_encode($probe['response_parsed']['error']));
	}

	return null;
}


/**
 * Returns dynamic mirror URL if available, falling back to official Spribe URL.
 */
function game_demo_spribe_official_url(array $abc, array $config = array()) {
	// Fallback to official Spribe launch URL directly on the server-side
	// to ensure instantaneous page loads and prevent cURL timeout hangs.
	// The mirror session is generated dynamically on the client-side instead.
	$currency = game_demo_spribe_currency_param($abc, $config);
	$lang = game_demo_lang_param($abc);
	return 'https://demo.spribe.io/launch/aviator?' . http_build_query(
		array('currency' => $currency, 'lang' => $lang),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
}

/**
 * Optional full-URL override ($config['game_demo_iframe_url']); otherwise same as game_demo_spribe_official_url().
 *
 * @param array $abc Template globals (expects $abc['lang'] from router).
 * @param array $config Site config.
 */
function game_demo_spribe_launch_url(array $abc, array $config) {
	if (!empty($config['game_demo_iframe_url']) && trim((string) $config['game_demo_iframe_url']) !== '') {
		return trim((string) $config['game_demo_iframe_url']);
	}
	return game_demo_spribe_official_url($abc, $config);
}

/**
 * Filter DB HTML: replace static provider iframes with mirror loader on /demo/app/ only.
 */
function game_demo_filter_content_iframe($html, array $abc, array $config) {
	if (empty($html) || !is_string($html)) {
		return $html;
	}
	if (isset($_SERVER['REQUEST_URI']) && stripos((string) $_SERVER['REQUEST_URI'], '/demo/app') === false) {
		$html = preg_replace('/<iframe\b[^>]*?src=["\']https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?["\'][^>]*?>.*?<\/iframe>/is', '', $html);
		$html = preg_replace('/<iframe\b[^>]*?src=["\']https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?["\'][^>]*?>/is', '', $html);
		return $html;
	}
	$pattern = '/<iframe([^>]*?)src=(["\'])(https?:\/\/(?:demo\.spribe\.io|[^"\']*?gmngdoor\.link)[^"\']*?)\2([^>]*?)>/i';
	$demo_title = (function_exists('site_brand_name') ? site_brand_name() : 'Game') . ' Demo';
	$replaced = preg_replace_callback($pattern, function ($m) use ($abc, $config, $demo_title) {
		$attrs = $m[1] . $m[4];
		$width = '100%';
		$height = '500px';
		$class = 'app-demo-iframe';
		$style = '';
		if (preg_match('/width=(["\'])(.*?)\1/i', $attrs, $w_match)) {
			$width = $w_match[2];
		}
		if (preg_match('/height=(["\'])(.*?)\1/i', $attrs, $h_match)) {
			$height = $h_match[2];
		}
		if (preg_match('/class=(["\'])(.*?)\1/i', $attrs, $c_match)) {
			$class = $c_match[2];
		}
		if (preg_match('/style=(["\'])(.*?)\1/i', $attrs, $s_match)) {
			$style = $s_match[2];
		}
		$iframe_attrs = preg_replace('/\b(width|height|class|style|src)=["\'].*?["\']/i', '', $attrs);
		$currency = game_demo_spribe_currency_param($abc, $config);
		$lang = strtolower(game_demo_lang_param($abc));
		$mirrorApiUrl = game_demo_mirror_config_value($config, 'api_url', 'https://fwu21pcmpk1m14q.gmngdoor.link/');
		$mirrorGameId = game_demo_mirror_config_value($config, 'game_id', 'aviator_spribe');
		$mirrorBankGroupId = game_demo_mirror_config_value($config, 'bank_group_id', '');
		if ($mirrorBankGroupId === '') {
			$mirrorBankGroupId = 'GoldenBet_' . $currency;
		}
		$spribeFallbackUrl = 'https://demo.spribe.io/launch/aviator?currency=' . rawurlencode($currency) . '&lang=' . rawurlencode($lang);
		if (!function_exists('access') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/auth_func.php')) {
			require_once ROOT_DIR . 'functions/auth_func.php';
		}
		$isDebugAllowed = function_exists('access') && access('user admin') && !empty($_GET['debug_ip_check']);
		$uniqueId = 'demo_' . rand(1000, 9999);
		return '
<div class="app-demo-container-outer" style="position: relative; width: ' . $width . '; height: ' . $height . '; min-height: 350px; background: #151b24; overflow: hidden; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); ' . $style . '">
    <div id="loader_' . $uniqueId . '" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at center, #1b2430 0%, #0f141c 100%); z-index: 10; transition: opacity 0.6s ease;">
        <p id="status_' . $uniqueId . '" style="color: #8a99ad; font-size: 0.8rem; margin: 0 0 16px 0;">Connecting to game server...</p>
        <div style="width: 160px; height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden; position: relative;">
            <div id="bar_' . $uniqueId . '" style="position: absolute; left: 0; top: 0; height: 100%; width: 10%; background: linear-gradient(90deg, #ff2e5f, #e50539); border-radius: 2px;"></div>
        </div>
    </div>
    <iframe id="iframe_' . $uniqueId . '" class="' . $class . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; opacity: 0; transition: opacity 0.8s ease;" title="' . htmlspecialchars($demo_title, ENT_QUOTES, 'UTF-8') . '" ' . $iframe_attrs . ' allowfullscreen></iframe>
</div>
<script>
(function() {
    var iframe = document.getElementById("iframe_' . $uniqueId . '");
    var loader = document.getElementById("loader_' . $uniqueId . '");
    var bar = document.getElementById("bar_' . $uniqueId . '");
    var status = document.getElementById("status_' . $uniqueId . '");
    var currency = ' . json_encode($currency) . ';
    var lang = ' . json_encode($lang) . ';
    var mirrorApiUrl = ' . json_encode($mirrorApiUrl) . ';
    var mirrorGameId = ' . json_encode($mirrorGameId) . ';
    var mirrorBankGroupId = ' . json_encode($mirrorBankGroupId) . ';
    var spribeFallbackUrl = ' . json_encode($spribeFallbackUrl) . ';
    var isLoaded = false;
    function setProgress(pct, statusText) {
        if (bar) bar.style.width = pct + "%";
        if (status && statusText) status.textContent = statusText;
    }
    function loadGame(url) {
        if (isLoaded) return;
        isLoaded = true;
        setProgress(100, "Launching game...");
        iframe.src = url;
        iframe.onload = function() {
            setTimeout(function() {
                loader.style.opacity = "0";
                iframe.style.opacity = "1";
                setTimeout(function() { loader.style.display = "none"; }, 600);
            }, 500);
        };
    }
    setProgress(30, "Connecting to game server...");
    var controller = new AbortController();
    var timeoutId = setTimeout(function() { controller.abort(); }, 2800);
    fetch(mirrorApiUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        signal: controller.signal,
        body: JSON.stringify({
            jsonrpc: "2.0",
            method: "Session.CreateDemo",
            id: Date.now(),
            params: {
                BankGroupId: mirrorBankGroupId,
                GameId: mirrorGameId,
                StartBalance: 10000,
                Params: { language: lang },
                PlayerIp: ""
            }
        })
    }).then(function(res) {
        clearTimeout(timeoutId);
        setProgress(70, "Securing dynamic session...");
        return res.json();
    }).then(function(data) {
        if (data.result && data.result.Url) {
            loadGame(data.result.Url);
        } else {
            setProgress(85, "Switching to backup game server...");
            loadGame(spribeFallbackUrl);
        }
    }).catch(function() {
        clearTimeout(timeoutId);
        setProgress(85, "Switching to backup game server...");
        loadGame(spribeFallbackUrl);
    });
})();
</script>';
	}, $html);
	return $replaced !== null ? $replaced : $html;
}
