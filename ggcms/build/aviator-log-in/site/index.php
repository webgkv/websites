<?php
$debug_translit = !empty($_GET['debug_translit']) && $_GET['debug_translit'] === '1';
$abc['debug_translit'] = $debug_translit;
// Hard debug: first possible output, does not depend on rewrites or later code
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
	header('Content-Type: text/html; charset=utf-8');
	echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug</title></head><body>";
	echo "<pre style='background:#1e1e1e;color:#0f0;padding:16px;font-family:monospace;'>";
	echo "1. INDEX.PHP REACHED\n";
	echo "   SCRIPT_FILENAME: " . (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '-') . "\n";
	echo "   REQUEST_URI: " . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '-') . "\n";
	echo "   DOCUMENT_ROOT: " . (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '-') . "\n";
	flush();
}

define('ROOT_DIR', dirname(__FILE__).'/');

if(substr($_SERVER['REQUEST_URI'],0,8)=='/images/') {
  require(ROOT_DIR.'images/index.php');
  die();
} elseif(substr($_SERVER['REQUEST_URI'],0,5)=='/api/') {
  require(ROOT_DIR.'api/index.php');
  die();
} elseif (strpos($_SERVER['REQUEST_URI'], '/banner-img.php') !== false) {
  if (!defined('ROOT_DIR')) define('ROOT_DIR', dirname(__FILE__).'/');
  require(ROOT_DIR.'banner-img.php');
  die();
}

// Debug: ?debug=1 — show PHP errors and a small trace (remove or disable in production)
if ((!empty($_GET['debug']) && $_GET['debug'] === '1') || $debug_translit) {
	ini_set('display_errors', '1');
	error_reporting(E_ALL);
	$index_debug = array('step' => 'start', 'file' => __FILE__, 'root' => dirname(__FILE__) . '/');
}

$showpariuri=0; //show pariuri blocks
$googletag=1;   //include google tag manager
$abc['showpariuri']=$showpariuri;

/**
 * Main entry: handles all URLs for the site.
 */

//session_start();

// Load config
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "2. before config\n"; flush(); }
require_once(ROOT_DIR.'config/config.php');
require_once(ROOT_DIR.'functions/brand_profile.php');
site_apply_profile_static_redirects();
// www -> apex (301); works behind Cloudflare when CF Redirect Rule is absent or misconfigured.
if (empty($config['local'])) {
	$www_host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) : '';
	if (strpos($www_host, 'www.') === 0) {
		$apex_host = substr($www_host, 4);
		if ($apex_host !== '') {
			$uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: https://' . $apex_host . $uri);
			exit;
		}
	}
}
require_once(ROOT_DIR.'functions/site_brand.php');
require_once(ROOT_DIR.'functions/site_seo.php');
require_once(ROOT_DIR.'functions/site_section_urls.php');
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "3. after config\n"; flush(); }

// Load functions
//require_once(ROOT_DIR.'functions/admin_func.php');	// admin functions
require_once(ROOT_DIR.'functions/auth_func.php');	// auth
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "5. after auth_func\n"; flush(); }
require_once(ROOT_DIR.'functions/common_func.php');	// common
require_once(ROOT_DIR.'functions/html_func.php');	// HTML
//require_once(ROOT_DIR.'functions/form_func.php');	// forms
//require_once(ROOT_DIR.'functions/image_func.php');	// images
require_once(ROOT_DIR.'functions/lang_func.php');	// dictionary
//require_once(ROOT_DIR.'functions/mail_func.php');	// mail
require_once(ROOT_DIR.'functions/mysql_func.php');	// DB
require_once(ROOT_DIR.'functions/site_telemetry.php');
if (function_exists('site_telemetry_request_begin')) {
	site_telemetry_request_begin('frontend', array('module' => 'site'));
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "6. after mysql_func\n"; flush(); }
require_once(ROOT_DIR.'functions/string_func.php');	// strings
require_once(ROOT_DIR.'functions/pwa_install.php');	// PWA manifest start_url + /download/install-pwa/ guide
require_once(ROOT_DIR.'functions/apk_install.php');	// /download/install-apk/ Android guide
require_once(ROOT_DIR.'functions/site_language_agnostic_entry.php');	// /{path} without /{lang}/ → /{lang}/{path}/ (cookie, Accept-Language, geo IP)
require_once(ROOT_DIR.'functions/author_func.php'); // SEO: Author block
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "7. after all requires (boot ok)\n"; flush(); }

$error = 0;
$route_debug_request = !empty($_GET['debug_route']) && (string)$_GET['debug_route'] === '1';
$abc['route_debug'] = $route_debug_request ? array('steps' => array()) : null;
$route_debug_ok = false;
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['request_uri'] = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
}
if ((!empty($_GET['debug']) && $_GET['debug'] === '1') || $debug_translit) {
	$abc['debug'] = true;
	$abc['debug_info'] = array('REQUEST_URI' => $_SERVER['REQUEST_URI'], 'GET' => $_GET);
	if (isset($index_debug)) {
		$index_debug['step'] = 'boot done';
		$abc['debug_info']['index_trace'] = $index_debug;
	}
}
if (!empty($_GET['debug_ads'])) {
	$abc['debug_ads'] = true;
}

$request_url = explode('?', $_SERVER['REQUEST_URI'], 2);
// Collapse repeated slashes (/hi//page/) so routing works; 301 to canonical path (avoids 404 from "No double slash" guard).
$raw_path = isset($request_url[0]) ? (string) $request_url[0] : '/';
$norm_path = preg_replace('#/+#', '/', $raw_path);
if ($norm_path === '') {
	$norm_path = '/';
} elseif ($norm_path[0] !== '/') {
	$norm_path = '/' . $norm_path;
}
if ($raw_path !== $norm_path) {
	$qs = (isset($request_url[1]) && $request_url[1] !== '') ? '?' . $request_url[1] : '';
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $norm_path . $qs);
	exit;
}
$request_url[0] = $norm_path;
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['path'] = array('raw' => $raw_path, 'normalized' => $norm_path);
}
// Build $u array from URL
$u = explode('/',$request_url[0]);
if(!$u[count($u)-1]) unset($u[count($u)-1]);
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['u_after_explode'] = array_values($u);
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8. URL parsed u=" . json_encode($u) . " request_url[0]=" . $request_url[0] . "\n"; flush(); }

// Redirect root (/) to canonical URL with language segment (e.g. /en/)
if (!empty($config['multilingual']) && trim($request_url[0], '/') === '') {
	$default_lang = lang();
	$lang_url = isset($default_lang['url']) ? $default_lang['url'] : 'en';
	$redirect_to = '/' . $lang_url . '/';
	if (!empty($_SERVER['QUERY_STRING'])) $redirect_to .= '?' . $_SERVER['QUERY_STRING'];
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $redirect_to);
	exit;
}

if ($config['multilingual'] AND $config['multilingual_u0']) {
	// Shift $u left so $u[0] is the language URL
	foreach ($u as $k=>$v) {
		$k1 = $k+1;
		if (isset($u[$k1])) $u[$k]=$u[$k1];
		else unset($u[$k]);
	}
}
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['u_after_shift'] = array_values($u);
	$abc['route_debug']['config'] = array(
		'multilingual' => !empty($config['multilingual']),
		'multilingual_u0' => !empty($config['multilingual_u0']),
	);
}
// Up to 10 URL segments
//for ($i=0; $i<10; $i++) if (empty($u[$i])) $u[$i] = '';

if(preg_match('#^[0-9]+$#',$u[count($u)-1])) {
  $_GET['n']=$u[count($u)-1];
  unset($u[count($u)-1]);
}
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['u_final'] = array_values($u);
	if (isset($_GET['n'])) {
		$abc['route_debug']['pagination_n'] = $_GET['n'];
	}
}

// Paths without /{lang}/ prefix → 302 to /{resolved}/{path}/ (download, demo/app, …)
if (function_exists('site_language_agnostic_redirect_if_needed')) {
	site_language_agnostic_redirect_if_needed();
}

$abc['googletag']=$googletag;

// If no language in URL, use default language (trim segment so fr/ and fr both resolve)
$lang = (isset($u[0]) && $u[0] !== '') ? lang(trim((string)$u[0], '/'), 'url') : lang();
$langid=$lang['id'];if($langid==1) $langid='';

$abc['lang'] = $lang;
$abc['langid']=$langid;
if ($abc['route_debug'] !== null) {
	$abc['route_debug']['language'] = array(
		'resolved_id' => isset($lang['id']) ? (int)$lang['id'] : null,
		'resolved_url' => isset($lang['url']) ? (string)$lang['url'] : '',
		'resolved_name' => isset($lang['name']) ? (string)$lang['name'] : '',
		'langid_for_pages_columns' => $langid,
	);
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8a. after lang langid=" . $langid . "\n"; flush(); }

// Template vars
$abc['template'] = '_template'; // main layout with header
$abc['layout'] = 'page'; // module layout
$abc['module'] = 'page'; // module

// Languages
$str='url,name';
if ($config['multilingual']) {
	$abc['languages'] = mysql_select("SELECT id,url,name,localization FROM languages WHERE display=1 ORDER BY `rank` DESC", 'rows_id');
	foreach($abc['languages'] as $i=>$v)
		if($i!=1) $str.=",url$i,name$i";
}
//$abc['modules'] = mysql_select("SELECT $str,module id FROM pages WHERE module!='pages' AND display=1",'rows_id',60*60);
$abc['modules'] = mysql_select("SELECT $str,module id FROM pages WHERE module!='pages'",'rows_id',60*60);
$abc['legal'] = mysql_select("SELECT id,$str FROM pages WHERE module='pages' and parent=8 and display=1 order by left_key asc",'rows_id',60*60);
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8b. after modules select\n"; flush(); }

require_once(ROOT_DIR.'functions/data_func.php');	// shared data (menu, etc.)
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8d. after data_func\n"; flush(); }

// Ensure advertising_api exists (data_func sets it only when table variables exists)
if (!isset($abc['advertising_api']) || !is_array($abc['advertising_api'])) {
	$abc['advertising_api'] = array('mode' => 'self', 'token' => '', 'api_sources' => array(), 'api_sources_priority' => '', 'api_url' => '', 'debug_ip_check' => 0, 'manual_country' => '', 'trusted_proxy_ips' => array());
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8e. after advertising_api default\n"; flush(); }

// Global IP/Country debug mode for ad backend diagnostics.
require_once(ROOT_DIR . 'functions/advertising_api.php');
$isAdmin = (function_exists('access') && access('user admin'));
$abc['debug_ip_check'] = $isAdmin && (
	(isset($_GET['debug_ip_check']) && (string)$_GET['debug_ip_check'] === '1')
	|| !empty($config['debug_ip_check'])
	|| !empty($abc['advertising_api']['debug_ip_check'])
);
if (!empty($abc['debug_ip_check'])) {
	$ip_ctx_dbg = aviator_ad_resolve_ip_context($abc['advertising_api']);
	$country_ctx_dbg = aviator_ad_resolve_country_context($abc['advertising_api'], $ip_ctx_dbg);
	$abc['debug_ip_check_info'] = array(
		'remote_addr' => (string)$ip_ctx_dbg['remote_addr'],
		'trusted_real_ip' => (string)$ip_ctx_dbg['trusted_real_ip'],
		'ip_sent_to_backend' => (string)$ip_ctx_dbg['ip_sent_to_backend'],
		'country_header_cf' => (string)$country_ctx_dbg['country_header_cf'],
		'country_by_local_geo' => (string)$country_ctx_dbg['country_by_local_geo'],
		'country_sent_to_backend' => (string)$country_ctx_dbg['country_sent_to_backend'],
		'source_of_country' => (string)$country_ctx_dbg['source_of_country'],
		'backend_url_called' => '',
		'backend_response' => '',
		'final_redirect_url' => '',
	);
}

// Advertising API: redirect /go/CODE (link) or /go/CODE1BANNER1/ (banner click, like agent sites)
$go_code = null;
$go_banner = null;
$go_segment = null;
if (!empty($u[0]) && $u[0] === 'go' && isset($u[1])) {
	$go_segment = trim($u[1]);
} elseif (isset($u[1]) && $u[1] === 'go' && isset($u[2])) {
	$go_segment = trim($u[2]);
}
if ($go_segment !== null && $go_segment !== '') {
	if (preg_match('/^([0-9A-Za-z]{5})1([0-9A-Za-z]{5})$/', $go_segment, $m)) {
		$go_code = $m[1];
		$go_banner = $m[2];
	} elseif (preg_match('/^[0-9A-Za-z]{5}$/', $go_segment)) {
		$go_code = $go_segment;
	}
}
// Debug info for /go handler
if (!empty($abc['debug'])) {
	$abc['debug_info']['go_handler'] = array(
		'go_segment' => $go_segment,
		'go_code' => $go_code,
		'go_banner' => $go_banner,
		'advertising_api_set' => isset($abc['advertising_api']),
		'advertising_api_mode' => isset($abc['advertising_api']['mode']) ? $abc['advertising_api']['mode'] : null,
	);
}

if ($go_code !== null && !empty($abc['advertising_api']['mode']) && $abc['advertising_api']['mode'] === 'api') {
	if (!empty($abc['debug'])) {
		$abc['debug_info']['go_handler']['entered_if'] = true;
	}

	// Banner partner payload is only loaded on normal pages, not on /go/ redirects.
	if ((isset($_GET['debug_ip_banner_check_full']) && (string)$_GET['debug_ip_banner_check_full'] === '1')
		|| (isset($_GET['debug_ip_banner_check']) && (string)$_GET['debug_ip_banner_check'] === '1')) {
		header('Content-Type: text/html; charset=utf-8');
		echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Banner API debug — /go/</title></head><body style="font-family:ui-monospace,monospace;padding:24px;background:#101827;color:#d1d5db;max-width:720px;">';
		echo '<h1 style="color:#60a5fa;margin-top:0;">Banner API debug is not available on /go/ links</h1>';
		echo '<p>This URL only runs the redirect backend (t.php / redirect). The <strong>/api/banner</strong> partner request is executed on regular pages (home, articles, etc.).</p>';
		echo '<p>Use for example: <code style="background:#1f2937;padding:2px 6px;">/en/?debug_ip_banner_check_full=1</code> or <code style="background:#1f2937;padding:2px 6px;">?debug_ip_banner_check=1</code> on any page that loads the layout with the banner.</p>';
		echo '<p>For redirect diagnostics here, use <code style="background:#1f2937;padding:2px 6px;">?debug_ip_check_full=1</code> instead.</p>';
		echo '</body></html>';
		exit;
	}

	$go_token = isset($abc['advertising_api']['token']) ? $abc['advertising_api']['token'] : '';
	$isAdmin = (function_exists('access') && access('user admin'));
	$debug_ip_check = $isAdmin && (
		(isset($_GET['debug_ip_check']) && (string)$_GET['debug_ip_check'] === '1')
		|| !empty($config['debug_ip_check'])
		|| !empty($abc['advertising_api']['debug_ip_check'])
	);
	$debug_ip_check_full = $isAdmin && (isset($_GET['debug_ip_check_full']) && (string)$_GET['debug_ip_check_full'] === '1');
	$ip_ctx = aviator_ad_resolve_ip_context($abc['advertising_api']);
	$country_ctx = aviator_ad_resolve_country_context($abc['advertising_api'], $ip_ctx);

	// Backend API for redirects: explicit /api base from config (api_url).
	$backend_api = isset($abc['advertising_api']['api_url']) ? trim((string)$abc['advertising_api']['api_url']) : '';
	$backend_api = $backend_api !== '' ? rtrim($backend_api, '/') : '';

	$api_redirect_url = '';
	$country_param_in_url = '';
	$ip_param_in_url = '';

	// LINK CLICK: /go/CODE/ — use Links API (first api_sources URL, e.g. t.php?o=...&api=1)
	if ($go_banner === null) {
		$sources = isset($abc['advertising_api']['api_sources']) && is_array($abc['advertising_api']['api_sources']) ? $abc['advertising_api']['api_sources'] : array();
		foreach ($sources as $base) {
			$base = trim(rtrim((string)$base, '/'));
			if ($base === '') continue;
			$api_redirect_url = function_exists('aviator_ad_normalize_track_api_url')
				? aviator_ad_normalize_track_api_url($base)
				: $base;
			$sep = (strpos($api_redirect_url, '?') !== false) ? '&' : '?';
			// Append token if not already present
			if (strpos($api_redirect_url, 'token=') === false && $go_token !== '') {
				$api_redirect_url .= $sep . 'token=' . rawurlencode($go_token);
				$sep = '&';
			}
			// Always pass resolved country when missing (including XX fallback).
			$country_to_backend = isset($country_ctx['country_sent_to_backend']) ? (string)$country_ctx['country_sent_to_backend'] : '';
			if ($country_to_backend === '') $country_to_backend = 'XX';
			if (!preg_match('/[?&]country=/', $api_redirect_url)) {
				$api_redirect_url .= $sep . 'country=' . rawurlencode($country_to_backend);
				$sep = '&';
			}
			// Pass resolved real IP to backend for offer decision/tracking.
			if (!empty($ip_ctx['ip_sent_to_backend']) && !preg_match('/[?&]ip=/', $api_redirect_url)) {
				$api_redirect_url .= $sep . 'ip=' . rawurlencode((string)$ip_ctx['ip_sent_to_backend']);
				$sep = '&';
			}
			// For link clicks we do not add link_code/banner here; backend resolves offer by o=... in t.php or track code.
			break;
		}
	}
	// BANNER CLICK: /go/CODE1BANNER/ — backend_api /redirect with banner parameter.
	if ($go_banner !== null && $go_banner !== '' && $backend_api !== '') {
		$api_base = rtrim($backend_api, '/') . '/redirect';
		$api_redirect_url = $api_base . '?' . ($go_token !== '' ? 'token=' . rawurlencode($go_token) . '&' : '') . 'link_code=' . rawurlencode($go_code) . '&banner=' . rawurlencode($go_banner)
			. (!empty($ip_ctx['ip_sent_to_backend']) ? '&ip=' . rawurlencode((string)$ip_ctx['ip_sent_to_backend']) : '');
	}

	if (!empty($abc['debug'])) {
		$abc['debug_info']['go_handler']['api_redirect_url'] = isset($api_redirect_url) ? $api_redirect_url : null;
	}
	if (!empty($api_redirect_url)) {
		$country_param_in_url = '';
		$ip_param_in_url = '';
		if (preg_match('/[?&]country=([^&]+)/', $api_redirect_url, $mC)) $country_param_in_url = rawurldecode((string)$mC[1]);
		if (preg_match('/[?&]ip=([^&]+)/', $api_redirect_url, $mI)) $ip_param_in_url = rawurldecode((string)$mI[1]);
	}

	if (!empty($api_redirect_url)) {
		// Fetch offer URL server-side and redirect user to it (so they don't see API JSON)
		$offer_url = null;
		$api_response_body = null;
		$data = null;
		$api_http_code = 0;
		$api_error = '';
		if (function_exists('curl_init')) {
			$ch = curl_init($api_redirect_url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 8,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_SSL_VERIFYPEER => false,
			));
			$body = curl_exec($ch);
			$api_http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$api_error = (string)curl_error($ch);
			$api_response_body = $body;
			curl_close($ch);
			if ($body !== false && $body !== '') {
				$data = json_decode($body, true);
				if (!empty($data['url']) && preg_match('#^https?://#i', $data['url'])) $offer_url = $data['url'];
			}
		} else {
			$ctx = stream_context_create(array('http' => array('timeout' => 5), 'ssl' => array('verify_peer' => false)));
			$body = @file_get_contents($api_redirect_url, false, $ctx);
			$api_response_body = $body;
			if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
				if (preg_match('/^HTTP\/\S+\s+(\d+)/', $http_response_header[0], $m)) $api_http_code = (int)$m[1];
			}
			if ($body === false) {
				$api_error = isset($php_errormsg) ? (string)$php_errormsg : '';
			}
			if ($body !== false && $body !== '') {
				$data = json_decode($body, true);
				if (!empty($data['url']) && preg_match('#^https?://#i', $data['url'])) $offer_url = $data['url'];
			}
		}

		$backend_response_truncated = $api_response_body !== null ? substr((string)$api_response_body, 0, 1500) : '';
		$final_redirect_url = $offer_url !== null ? $offer_url : $api_redirect_url;
		if ($debug_ip_check_full) {
			$abc['debug_ip_check_full'] = array(
				'request' => array(
					'path_segment' => $go_segment,
					'parsed_link_code' => $go_code,
					'parsed_banner' => $go_banner,
					'type' => $go_banner !== null && $go_banner !== '' ? 'banner_click' : 'link_click',
				),
				'ip_check' => array(
					'remote_addr' => (string)$ip_ctx['remote_addr'],
					'trusted_real_ip' => (string)$ip_ctx['trusted_real_ip'],
					'ip_sent_to_backend' => (string)$ip_ctx['ip_sent_to_backend'],
					'country_header_cf' => (string)$country_ctx['country_header_cf'],
					'country_by_local_geo' => (string)$country_ctx['country_by_local_geo'],
					'country_sent_to_backend' => (string)$country_ctx['country_sent_to_backend'],
					'source_of_country' => (string)$country_ctx['source_of_country'],
					'country_param_in_url' => (string)$country_param_in_url,
					'ip_param_in_url' => (string)$ip_param_in_url,
				),
				'backend' => array(
					'url_called' => preg_replace('#token=[^&]+#', 'token=***', (string)$api_redirect_url),
					'http_code' => (int)$api_http_code,
					'error' => (string)$api_error,
					'response_raw' => (string)$api_response_body,
					'response_raw_truncated' => $backend_response_truncated,
					'response_parsed' => is_array($data) ? $data : null,
				),
				'final_redirect_url' => $final_redirect_url,
				'would_redirect' => $offer_url !== null,
			);
			require(ROOT_DIR . 'templates/includes/layouts/_debug_ip_check_full.php');
			exit;
		}

		if ($debug_ip_check) {
			$payload_debug = array(
				'remote_addr' => (string)$ip_ctx['remote_addr'],
				'trusted_real_ip' => (string)$ip_ctx['trusted_real_ip'],
				'ip_sent_to_backend' => (string)$ip_ctx['ip_sent_to_backend'],
				'country_header_cf' => (string)$country_ctx['country_header_cf'],
				'country_by_local_geo' => (string)$country_ctx['country_by_local_geo'],
				'country_sent_to_backend' => (string)$country_ctx['country_sent_to_backend'],
				'source_of_country' => (string)$country_ctx['source_of_country'],
				'country_param_in_url' => (string)$country_param_in_url,
				'ip_param_in_url' => (string)$ip_param_in_url,
				'backend_url_called' => preg_replace('#token=[^&]+#', 'token=***', (string)$api_redirect_url),
				'backend_response' => $backend_response_truncated,
				'final_redirect_url' => $final_redirect_url,
			);
			$abc['debug_ip_check_info'] = $payload_debug;
			header('Content-Type: text/html; charset=utf-8');
			echo '<div style="font:13px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;max-width:980px;margin:20px auto;padding:12px 14px;border:1px solid #d9d9d9;border-radius:8px;background:#fff;">';
			echo '<div style="font-weight:600;margin-bottom:8px;">Debug IP Check (no redirect)</div>';
			echo '<pre style="margin:0;white-space:pre-wrap;word-break:break-word;background:#f7f7f7;padding:10px;border-radius:6px;overflow:auto;">' . htmlspecialchars(json_encode($payload_debug, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . '</pre>';
			echo '</div>';
			exit;
		}

		// debug_ads=1: show redirect debug page instead of redirecting
		if (!empty($abc['debug_ads'])) {
			$abc['debug_ads_redirect'] = array(
				'request' => array(
					'path_segment' => $go_segment,
					'parsed_link_code' => $go_code,
					'parsed_banner' => $go_banner,
					'type' => $go_banner !== null && $go_banner !== '' ? 'banner_click' : 'link_click',
				),
				'api_url_called' => preg_replace('#token=[^&]+#', 'token=***', $api_redirect_url),
				'api_response_raw' => $api_response_body !== null ? substr($api_response_body, 0, 2000) : null,
				'api_response_parsed' => isset($data) ? $data : null,
				'redirect_to_offer' => $offer_url,
				'would_redirect' => $offer_url !== null,
			);
			$abc['debug_ads_redirect']['offer_url_for_link'] = $offer_url;
			require(ROOT_DIR . 'templates/includes/layouts/_debug_ads_redirect.php');
			exit;
		}

		if ($offer_url !== null) {
			header('Location: ' . $offer_url, true, 302);
			exit;
		}

		// Fallback: redirect to API URL (user will see JSON if API itself redirects)
		header('Location: ' . $api_redirect_url, true, 302);
		exit;
	}
}

// Advertising API: fetch partner (offer + banners), cache fallback
$abc['ad_partner'] = null;
$abc['ad_offer_path'] = '';
$abc['debug_ads_banner_api'] = array();
$abc['ad_render_mode'] = 'banner';
if (!empty($abc['advertising_api']['mode']) && $abc['advertising_api']['mode'] === 'api') {
	$abc['ad_partner'] = aviator_ad_get_partner($abc['debug_ads_banner_api']);
	$lang_current = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url']) : 'en';
	if ($lang_current === '') {
		$lang_current = 'en';
	}
	if (!empty($abc['ad_partner']['code'])) {
		$lang_seg = ($config['multilingual'] && isset($abc['lang']['url'])) ? ($abc['lang']['url'] . '/') : '';
		$abc['ad_offer_path'] = '/' . $lang_seg . 'go/' . $abc['ad_partner']['code'] . '/';
		// Banner click URL (like agent sites: code + '1' + banner1) so backend gets &banner= for attribution
		$b1 = isset($abc['ad_partner']['banner1']) ? trim((string)$abc['ad_partner']['banner1']) : '';
		$abc['ad_banner_click_path'] = ($b1 !== '' && preg_match('/^[0-9A-Za-z]{5}$/', $b1))
			? '/' . $lang_seg . 'go/' . $abc['ad_partner']['code'] . '1' . $b1 . '/'
			: $abc['ad_offer_path'];
	}
	if (!isset($abc['ad_banner_click_path'])) {
		$abc['ad_banner_click_path'] = $abc['ad_offer_path'];
	}
	// Decide banner rendering mode from backend matching metadata (with safe fallbacks).
	if (!empty($abc['ad_partner']) && is_array($abc['ad_partner'])) {
		$banner_lang_received = isset($abc['ad_partner']['banner_lang']) ? trim((string)$abc['ad_partner']['banner_lang']) : '';
		$match_level = isset($abc['ad_partner']['match_level']) ? trim((string)$abc['ad_partner']['match_level']) : '';
		$fallback_reason = isset($abc['ad_partner']['fallback_reason']) ? trim((string)$abc['ad_partner']['fallback_reason']) : '';
		$fallback_suggested = !empty($abc['ad_partner']['fallback_suggested']) ? 1 : 0;

		$backend_reports_global = ($fallback_suggested === 1 || $match_level === 'global_any_lang'
			|| stripos($fallback_reason, 'global') !== false);

		// banner_lang empty: (a) old API — treat as match; (b) global/fallback — treat asset as English: match only on EN site.
		if ($banner_lang_received !== '') {
			$lang_match = (strtolower($banner_lang_received) === strtolower($lang_current));
		} elseif ($backend_reports_global) {
			$lang_match = (strtolower($lang_current) === 'en');
		} else {
			$lang_match = true;
		}
		$render_mode = 'banner';
		$placeholder_reason = '';
		// Fallback placeholder when backend marks fallback/global and locale does not match banner language (incl. global+empty on non-EN).
		if (($fallback_suggested === 1 || $match_level === 'global_any_lang') && !$lang_match) {
			$render_mode = 'placeholder';
			if ($banner_lang_received === '' && $backend_reports_global && strtolower($lang_current) !== 'en') {
				$placeholder_reason = 'global_fallback_empty_banner_lang_non_en';
			} else {
				$placeholder_reason = 'fallback_or_global_mismatch_lang';
			}
		}
		// If backend returns English banner for a non-English locale, force placeholder (no per-locale hardcoding).
		if ($render_mode === 'banner' && strtolower($lang_current) !== 'en' && $banner_lang_received === 'en') {
			$render_mode = 'placeholder';
			$placeholder_reason = 'non_en_locale_en_banner';
		}
		$creative_locale_note = '';
		if ($render_mode === 'placeholder' && $placeholder_reason === 'global_fallback_empty_banner_lang_non_en') {
			$creative_locale_note = 'Placeholder: backend reports global/fallback, banner_lang empty — global creative is treated as English; non-EN locales get localized placeholder instead of English HTML.';
		} elseif ($render_mode === 'banner' && $backend_reports_global && $banner_lang_received === '' && strtolower($lang_current) === 'en') {
			$creative_locale_note = 'EN site: global/fallback with empty banner_lang — full banner slot (global asset matches EN locale).';
		} elseif ($render_mode === 'banner' && $banner_lang_received === '' && !$backend_reports_global) {
			$creative_locale_note = 'Empty banner_lang without global/fallback flags — backward compat, full banner slot.';
		}
		$abc['ad_render_mode'] = $render_mode;
		$abc['ad_render_debug'] = array(
			'lang_sent' => isset($abc['debug_ads_banner_api']['lang_sent']) ? (string)$abc['debug_ads_banner_api']['lang_sent'] : $lang_current,
			'banner_lang_received' => $banner_lang_received,
			'match_level' => $match_level,
			'fallback_reason' => $fallback_reason,
			'fallback_suggested' => $fallback_suggested,
			'lang_match' => $lang_match,
			'backend_global_fallback_reported' => $backend_reports_global ? 1 : 0,
			'empty_banner_lang_compat_applied' => ($banner_lang_received === '' && !$backend_reports_global ? 1 : 0),
			'global_fallback_empty_lang_treated_as_en' => ($banner_lang_received === '' && $backend_reports_global ? 1 : 0),
			'creative_locale_note' => $creative_locale_note,
			'placeholder_reason' => ($render_mode === 'placeholder' ? $placeholder_reason : ''),
			'final_render_mode' => $render_mode,
		);
	}
	if (!empty($abc['debug_ip_check']) && !empty($abc['debug_ip_check_info']) && is_array($abc['debug_ip_check_info'])) {
		$backend_url_called = '';
		$backend_response = '';
		$country_param_in_url = '';
		$ip_param_in_url = '';
		if (!empty($abc['debug_ads_banner_api']['tries'][0]) && is_array($abc['debug_ads_banner_api']['tries'][0])) {
			$t0 = $abc['debug_ads_banner_api']['tries'][0];
			$backend_url_called = isset($t0['url']) ? (string)$t0['url'] : '';
			if (isset($t0['data'])) {
				$backend_response = substr((string)json_encode($t0['data'], JSON_UNESCAPED_UNICODE), 0, 1500);
			}
			if ($backend_url_called !== '') {
				if (preg_match('/[?&]country=([^&]+)/', $backend_url_called, $mC)) $country_param_in_url = rawurldecode((string)$mC[1]);
				if (preg_match('/[?&]ip=([^&]+)/', $backend_url_called, $mI)) $ip_param_in_url = rawurldecode((string)$mI[1]);
			}
		}
		$abc['debug_ip_check_info']['backend_url_called'] = $backend_url_called;
		$abc['debug_ip_check_info']['backend_response'] = $backend_response;
		$abc['debug_ip_check_info']['country_param_in_url'] = $country_param_in_url;
		$abc['debug_ip_check_info']['ip_param_in_url'] = $ip_param_in_url;
		$abc['debug_ip_check_info']['final_redirect_url'] = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
		if (!empty($abc['ad_render_debug']) && is_array($abc['ad_render_debug'])) {
			$abc['debug_ip_check_info'] = array_merge($abc['debug_ip_check_info'], $abc['ad_render_debug']);
		}
		$banner_routing = null;
		if (!empty($abc['debug_ads_banner_api']['tries'][0]['data']['routing']) && is_array($abc['debug_ads_banner_api']['tries'][0]['data']['routing'])) {
			$banner_routing = $abc['debug_ads_banner_api']['tries'][0]['data']['routing'];
		}
		$abc['debug_ip_check_info']['banner_routing'] = $banner_routing;
		$abc['debug_ip_check_info']['banner_link_code'] = !empty($abc['ad_partner']['code']) ? (string)$abc['ad_partner']['code'] : '';
		$ip_ctx_dbg2 = aviator_ad_resolve_ip_context($abc['advertising_api']);
		$country_ctx_dbg2 = aviator_ad_resolve_country_context($abc['advertising_api'], $ip_ctx_dbg2);
		$abc['debug_ip_check_info']['link_click_track'] = aviator_ad_debug_track_click_preview($abc['advertising_api'], $country_ctx_dbg2, $ip_ctx_dbg2);
		$tr0 = isset($abc['debug_ip_check_info']['link_click_track']['probes'][0]) ? $abc['debug_ip_check_info']['link_click_track']['probes'][0] : array();
		$abc['debug_ip_check_info']['important'] = array(
			'banner_api_picks_link_for_href_only' => true,
			'button_click_uses_track_not_banner_link_code' => true,
			'banner_link_code' => $abc['debug_ip_check_info']['banner_link_code'],
			'track_affiliate_host_on_click' => isset($tr0['affiliate_host']) ? $tr0['affiliate_host'] : '',
			'track_resolved_link_code' => isset($tr0['resolved_link_code']) ? $tr0['resolved_link_code'] : '',
			'mismatch_hint' => (
				!empty($abc['debug_ip_check_info']['banner_link_code'])
				&& !empty($tr0['resolved_link_code'])
				&& $abc['debug_ip_check_info']['banner_link_code'] !== $tr0['resolved_link_code']
			) ? 'Banner and track returned different link codes — check offer Priorities / countries per row' : '',
		);
	}
	// debug_ads: build full info (like agents: offer URL from links API for both link and banner; b.php only for banner data)
	if (!empty($abc['debug_ads'])) {
		$backend_api = isset($abc['advertising_api']['api_url']) ? trim((string)$abc['advertising_api']['api_url']) : '';
		$backend_api = $backend_api !== '' ? rtrim($backend_api, '/') : '';
		if ($backend_api === '') {
			$sources = isset($abc['advertising_api']['api_sources']) && is_array($abc['advertising_api']['api_sources']) ? $abc['advertising_api']['api_sources'] : array();
			foreach ($sources as $base) {
				$base = trim((string)$base);
				if ($base === '' || strpos($base, 'b.php') !== false) continue;
				if (preg_match('#^https?://[^/]+/api/?$#i', $base)) {
					$backend_api = rtrim($base, '/');
					break;
				}
				if (preg_match('#^(https?://[^/]+)/track/[^/]+/api/?$#i', $base, $m)) {
					$backend_api = rtrim($m[1], '/') . '/api';
					break;
				}
			}
		}
		$go_token = isset($abc['advertising_api']['token']) ? $abc['advertising_api']['token'] : '';
		$abc['debug_ads_info'] = array(
			'ad_partner' => $abc['ad_partner'],
			'ad_offer_path' => $abc['ad_offer_path'],
			'ad_banner_click_path' => isset($abc['ad_banner_click_path']) ? $abc['ad_banner_click_path'] : '',
			'banner_api_calls' => $abc['debug_ads_banner_api'],
		);
		if (!empty($abc['ad_render_debug']) && is_array($abc['ad_render_debug'])) {
			$abc['debug_ads_info']['render_decision'] = $abc['ad_render_debug'];
		}
		if (!empty($abc['ad_partner']['code']) && $backend_api !== '') {
			$base = $backend_api . '/redirect';
			$sep = '?';
			$abc['debug_ads_info']['redirect_api_url_link'] = $base . $sep . ($go_token !== '' ? 'token=***&' : '') . 'link_code=' . rawurlencode($abc['ad_partner']['code']);
			$b1 = isset($abc['ad_partner']['banner1']) ? trim((string)$abc['ad_partner']['banner1']) : '';
			if ($b1 !== '' && preg_match('/^[0-9A-Za-z]{5}$/', $b1)) {
				$abc['debug_ads_info']['redirect_api_url_banner'] = $base . $sep . ($go_token !== '' ? 'token=***&' : '') . 'link_code=' . rawurlencode($abc['ad_partner']['code']) . '&banner=' . rawurlencode($b1);
			} else {
				$abc['debug_ads_info']['redirect_api_url_banner'] = '(same as link — no banner1 code)';
			}
		} else {
			$abc['debug_ads_info']['redirect_api_url_link'] = $backend_api !== '' ? '(no partner)' : '(no backend api_url configured)';
			$abc['debug_ads_info']['redirect_api_url_banner'] = $abc['debug_ads_info']['redirect_api_url_link'];
		}
	}

	// Banner API debug: ?debug_ip_banner_check_full=1 (full page) or ?debug_ip_banner_check=1 (footer panel on normal layout).
	$want_banner_dbg_full = isset($_GET['debug_ip_banner_check_full']) && (string)$_GET['debug_ip_banner_check_full'] === '1';
	$want_banner_dbg_compact = isset($_GET['debug_ip_banner_check']) && (string)$_GET['debug_ip_banner_check'] === '1';
	if ($want_banner_dbg_full || $want_banner_dbg_compact) {
		$ip_ctx_b = aviator_ad_resolve_ip_context($abc['advertising_api']);
		$country_ctx_b = aviator_ad_resolve_country_context($abc['advertising_api'], $ip_ctx_b);
		$frm = (!empty($abc['ad_render_debug']['final_render_mode'])) ? (string)$abc['ad_render_debug']['final_render_mode'] : '';
		$pr = (!empty($abc['ad_render_debug']['placeholder_reason'])) ? (string)$abc['ad_render_debug']['placeholder_reason'] : '';
		$has_code = !empty($abc['ad_partner']) && is_array($abc['ad_partner']) && !empty($abc['ad_partner']['code']);
		if (!$has_code) {
			$human = 'No partner from banner API (no link_code). See banner_api for HTTP tries and responses.';
		} elseif ($frm === 'placeholder') {
			if ($pr === 'global_fallback_empty_banner_lang_non_en') {
				$human = 'Placeholder: global/fallback with empty banner_lang on non-EN site — global asset treated as English.';
			} elseif ($pr === 'fallback_or_global_mismatch_lang') {
				$human = 'Placeholder: fallback_suggested or match_level=global_any_lang, and banner_lang does not match site language.';
			} elseif ($pr === 'fr_de_en_banner') {
				$human = 'Placeholder: site is FR/DE but banner_lang from backend is en.';
			} else {
				$human = 'Placeholder (see render_decision.placeholder_reason).';
			}
		} else {
			if (!empty($abc['ad_render_debug']['creative_locale_note'])) {
				$human = (string)$abc['ad_render_debug']['creative_locale_note'];
			} else {
				$human = 'Full banner: language/metadata OK (or empty banner_lang treated as compatible).';
			}
		}
		$abc['debug_ip_banner_check_payload'] = array(
			'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
			'lang_current' => $lang_current,
			'ip_country' => array(
				'remote_addr' => (string)$ip_ctx_b['remote_addr'],
				'trusted_real_ip' => (string)$ip_ctx_b['trusted_real_ip'],
				'ip_sent_to_backend' => (string)$ip_ctx_b['ip_sent_to_backend'],
				'country_header_cf' => (string)$country_ctx_b['country_header_cf'],
				'country_by_local_geo' => (string)$country_ctx_b['country_by_local_geo'],
				'country_sent_to_backend' => (string)$country_ctx_b['country_sent_to_backend'],
				'source_of_country' => (string)$country_ctx_b['source_of_country'],
			),
			'banner_api' => $abc['debug_ads_banner_api'],
			'ad_partner' => $abc['ad_partner'],
			'render_decision' => isset($abc['ad_render_debug']) && is_array($abc['ad_render_debug']) ? $abc['ad_render_debug'] : array(),
			'human_summary' => $human,
		);
		if (!empty($abc['debug_ip_check_info']) && is_array($abc['debug_ip_check_info'])) {
			$abc['debug_ip_banner_check_payload']['merged_debug_ip_check'] = $abc['debug_ip_check_info'];
		}
		if ($want_banner_dbg_full) {
			$abc['debug_ip_banner_check_full'] = $abc['debug_ip_banner_check_payload'];
			require ROOT_DIR . 'templates/includes/layouts/_debug_ip_banner_check_full.php';
			exit;
		}
		$abc['debug_ip_banner_check'] = 1;
	}
} elseif (isset($_GET['debug_ip_banner_check_full']) && (string)$_GET['debug_ip_banner_check_full'] === '1') {
	header('Content-Type: text/html; charset=utf-8');
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Banner API debug</title></head><body style="font-family:monospace;padding:24px;background:#101827;color:#d1d5db;">';
	echo '<h1 style="color:#f87171;">Banner API debug unavailable</h1>';
	echo '<p><code>advertising_api.mode</code> is not <code>api</code>. Enable API mode so the site can call the banner endpoint.</p>';
	echo '</body></html>';
	exit;
}

// Auth: build user data array
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8f. before user(auth)\n"; flush(); }
$abc['user'] = $user = user('auth'); //print_r($user);
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8g. after user(auth)\n"; flush(); }

// Route debug: who may see ?debug_route=1 output (full SQL + DB hints)
if ($route_debug_request) {
	$route_dbg_key = isset($config['debug_route_key']) ? (string)$config['debug_route_key'] : '';
	$route_dbg_key_ok = $route_dbg_key !== '' && isset($_GET['debug_route_key']) && hash_equals($route_dbg_key, (string)$_GET['debug_route_key']);
	$route_debug_ok = !empty($config['local']) || $route_dbg_key_ok || access('user admin');
	if (!$route_debug_ok) {
		header('HTTP/1.0 403 Forbidden');
		header('Content-Type: text/plain; charset=utf-8');
		echo "debug_route: access denied.\n\n";
		echo "Use one of:\n";
		echo "  - localhost (\$config['local'] is true in config/config.php),\n";
		echo "  - admin session (access user admin),\n";
		echo "  - set \$config['debug_route_key'] in config/config.php and open: ?debug_route=1&debug_route_key=YOUR_SECRET\n";
		exit;
	}
}
// Force admin auth (demo)
//$_SESSION['user'] = $user = mysql_select("SELECT ut.*,u.*FROM users u LEFT JOIN user_types ut ON u.type = ut.id WHERE u.id=1 LIMIT 1",'row');

// v1.2.45 - redirect to main domain
if (@$config['domain_main_redirect'] AND @$config['domain_main']) {
	if ($config['local']==false AND $config['domain_main']!=$_SERVER['HTTP_HOST']) {
		header('HTTP/1.1 301 Moved Permanently');
		header('location: ' . $config['http_domain'] . $_SERVER['REQUEST_URI']);
		die();
	}
}

// v1.2.34 - HTTPS redirect
if (@$config['https']==1) {
	if(
		(isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS']!='on')
		OR (isset($_SERVER['HTTP_X_FORWARDED_PROTOCOL']) AND $_SERVER['HTTP_X_FORWARDED_PROTOCOL']!='https')
		OR (isset($_SERVER['REQUEST_SCHEME']) AND $_SERVER['REQUEST_SCHEME']=='http')
	) {
		header('HTTP/1.1 301 Moved Permanently');
		header('location: ' . $config['http_domain'] . $_SERVER['REQUEST_URI']);
		die();
	}
}

// Dummy page for non-admins or on DB error
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8h. before dummy/redirect check\n"; flush(); }
$show_dummy = (isset($config['dummy']) && $config['dummy']==1 && access('user admin')==false) OR (isset($config['mysql_error']) && $config['mysql_error']);
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8h0. show_dummy=" . ($show_dummy ? '1' : '0') . "\n"; flush(); }
if ($show_dummy) {
	if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8h0a. before html_render _dummy\n"; flush(); }
	echo html_render('layouts/_dummy');
	die();
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8h1. after dummy block\n"; flush(); }

// Redirects (from admin)
if (isset($config['redirects']) && $config['redirects']) {
	//$request_url = explode('?',$_SERVER['REQUEST_URI']); //print_r($request_url);
	if ($redirect = mysql_select("SELECT * FROM redirects WHERE old_url='".mysql_res($request_url[0])."'",'row')) {
		header('HTTP/1.1 301 Moved Permanently');
		header('location: '.$config['http_domain'].$redirect['new_url']);
		die();
	}
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8i. after redirects\n"; flush(); }

$abc['links']=array();
foreach($abc['languages'] as $i=>$v) {
	$abc['links'][$abc['languages'][$i]['url']][]=$abc['languages'][$i]['url'];
}
if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8j. after abc[links]\n"; flush(); }

/**
 * For scalable translations (14+ languages): get page slug from content_i18n when available.
 * Returns null when translation missing/unpublished.
 */
function page_i18n_slug($page_id, $lang_id) {
	$page_id = (int)$page_id;
	$lang_id = (int)$lang_id;
	if ($page_id <= 0 || $lang_id <= 0) return null;
	if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') === 0) return null;
	$row = mysql_select("
		SELECT url
		FROM content_i18n
		WHERE entity='pages'
		  AND entity_id={$page_id}
		  AND lang_id={$lang_id}
		  AND status='published'
		LIMIT 1
	", 'row');
	if (!$row) return null;
	$u = trim((string)$row['url'], '/');
	return $u !== '' ? $u : null;
}

/**
 * Translation “source” language id (canonical row in `pages` / SEO Monitor main). Cached per request.
 */
function page_i18n_source_lang_id() {
	static $id = null;
	if ($id !== null) {
		return $id;
	}
	$id = 1;
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
		if ($row && $row['value'] !== '') {
			$dec = json_decode((string)$row['value'], true);
			if (is_array($dec) && !empty($dec['source_lang_id'])) {
				$i = (int)$dec['source_lang_id'];
				if ($i > 0) {
					$id = $i;
				}
			}
		}
	}
	return $id;
}

function page_i18n_fields_current($page, $lang_id) {
	$lang_id = (int)$lang_id;
	$out = array('url'=>'','name'=>'','title'=>'','description'=>'','content'=>'');
	if (!is_array($page)) return $out;

	$suffix = ($lang_id > 1) ? (string)$lang_id : '';
	$fill_pages = function () use ($page, $suffix) {
		return array(
			'url' => isset($page['url' . $suffix]) ? (string)$page['url' . $suffix] : (isset($page['url']) ? (string)$page['url'] : ''),
			'name' => isset($page['name' . $suffix]) ? (string)$page['name' . $suffix] : (isset($page['name']) ? (string)$page['name'] : ''),
			'title' => isset($page['title' . $suffix]) ? (string)$page['title' . $suffix] : (isset($page['title']) ? (string)$page['title'] : ''),
			'description' => isset($page['description' . $suffix]) ? (string)$page['description' . $suffix] : (isset($page['description']) ? (string)$page['description'] : ''),
			'content' => isset($page['text' . $suffix]) ? (string)$page['text' . $suffix] : (isset($page['text']) ? (string)$page['text'] : ''),
		);
	};

	$canonical_lang = ($lang_id === page_i18n_source_lang_id());

	if ($canonical_lang) {
		// Main `pages` row is authoritative (SEO import updates it). Stale content_i18n for this lang_id must not override title/description.
		$out = $fill_pages();
	} else {
		// Prefer content_i18n, then legacy columns (url2/title2/…).
		if ($lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0 && !empty($page['id'])) {
			$row = mysql_select("
				SELECT url,name,title,description,content
				FROM content_i18n
				WHERE entity='pages'
				  AND entity_id=" . (int)$page['id'] . "
				  AND lang_id=" . (int)$lang_id . "
				ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
				LIMIT 1
			", 'row');
			if ($row) {
				foreach (array('url','name','title','description','content') as $k) {
					if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
						$out[$k] = (string)$row[$k];
					}
				}
			}
		}
		$base = $fill_pages();
		foreach (array('url','name','title','description','content') as $k) {
			if (trim((string)$out[$k]) === '') {
				$out[$k] = $base[$k];
			}
		}
	}

	if ($canonical_lang && $lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0 && !empty($page['id'])) {
		$row = mysql_select("
			SELECT url,name,title,description,content
			FROM content_i18n
			WHERE entity='pages'
			  AND entity_id=" . (int)$page['id'] . "
			  AND lang_id=" . (int)$lang_id . "
			ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
			LIMIT 1
		", 'row');
		if ($row) {
			foreach (array('url','name','title','description','content') as $k) {
				if (trim((string)$out[$k]) !== '') {
					continue;
				}
				if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
					$out[$k] = (string)$row[$k];
				}
			}
		}
	}

	return $out;
}

/**
 * Games section landing row: `pages` with module=pages and slug "games" in url/urlN OR in content_i18n (e.g. FR "jeux" only in i18n).
 * @return array{row: array, via: string}|null
 */
function aviator_find_games_landing_page_row($current_lang_id) {
	global $config;
	if (!empty($config['games_landing_page_id'])) {
		$gid = (int)$config['games_landing_page_id'];
		if ($gid > 0) {
			$row = mysql_select("SELECT * FROM `pages` WHERE id=" . $gid . " AND display=1 LIMIT 1", 'row', 0);
			if ($row && isset($row['module']) && (string)$row['module'] === 'pages') {
				return array('row' => $row, 'via' => 'config_games_landing_page_id');
			}
		}
	}
	$gl_or = array();
	$col_rows = mysql_select("SHOW COLUMNS FROM `pages` LIKE 'url%'", 'rows', 0);
	if (is_array($col_rows)) {
		foreach ($col_rows as $c) {
			if (empty($c['Field'])) {
				continue;
			}
			$f = (string)$c['Field'];
			if (!preg_match('/^url\\d*$/', $f)) {
				continue;
			}
			$gl_or[] = '`' . str_replace('`', '``', $f) . "`='games'";
		}
	}
	if (empty($gl_or)) {
		$gl_or[] = "`url`='games'";
	}
	$row = mysql_select(
		"SELECT * FROM `pages` WHERE display=1 AND module='pages' AND (" . implode(' OR ', $gl_or) . ") LIMIT 1",
		'row',
		0
	);
	if ($row) {
		return array('row' => $row, 'via' => 'pages_column_equals_games');
	}
	if ($current_lang_id > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$esc = mysql_res('games');
		$row = mysql_select("
			SELECT p.*
			FROM pages p
			INNER JOIN content_i18n ci ON ci.entity='pages' AND ci.entity_id=p.id AND ci.lang_id=" . (int)$current_lang_id . "
			WHERE p.display=1 AND p.module='pages'
			  AND ci.status IN ('published','review','draft','missing')
			  AND (
				ci.url='" . $esc . "'
				OR ci.url='/games'
				OR ci.url='/games/'
				OR ci.url='games/'
			  )
			ORDER BY FIELD(ci.status,'published','review','draft','missing') ASC, ci.id DESC
			LIMIT 1
		", 'row', 0);
		if ($row) {
			return array('row' => $row, 'via' => 'content_i18n_url_games');
		}
	}
	return null;
}

	// Homepage or module condition

//	$where = ($u[1] == '') ? "module='index'" : "url='" . mysql_res($u[1]) . "'";
	if (empty($u[1]) || $u[1] === 'index.php')	$where="module='index'";
//        elseif($u[1]=='biletul-zilei')	$where="module='biletul-zilei'";
//        elseif($u[1]=='cota2')		$where="module='cota2'";
        else {
			$slug_raw = trim((string)$u[1], '/');
			$slug = mysql_res($slug_raw);
			// If per-language URL slug is missing (e.g. url3=''), fall back to canonical pages.url.
			// This makes /{lang}/<slug>/ work even when url$langid column isn't populated.
			$where = "url='" . $slug . "'";
			// For scalable i18n installs, urlN columns may not exist for all languages.
			// If url$langid doesn't exist -> don't reference it (otherwise SQL fails and we get 404 for the whole language).
			if ($langid !== '') {
				static $url_langid_has_col = array();
				if (!array_key_exists($langid, $url_langid_has_col)) {
					$col = 'url' . $langid;
					$url_langid_has_col[$langid] = (mysql_select("SHOW COLUMNS FROM pages LIKE '" . mysql_res($col) . "'", 'num_rows') > 0);
				}
				if (!empty($url_langid_has_col[$langid])) {
					$where = "(url$langid='" . $slug . "' OR url='" . $slug . "')";
				}
				if ($abc['route_debug'] !== null) {
					$abc['route_debug']['page_lookup'] = array(
						'slug_from_path' => isset($slug_raw) ? $slug_raw : null,
						'pages_column_checked' => 'url' . $langid,
						'pages_column_exists' => !empty($url_langid_has_col[$langid]),
					);
				}
			} elseif ($abc['route_debug'] !== null && isset($slug_raw)) {
				$abc['route_debug']['page_lookup'] = array(
					'slug_from_path' => $slug_raw,
					'note' => 'langid empty (default lang): only pages.url is matched',
				);
			}
		}
	if ($abc['route_debug'] !== null && isset($where) && empty($abc['route_debug']['page_lookup'])) {
		$abc['route_debug']['page_lookup'] = array('where_sql_fragment' => $where);
	}
	if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8k. where=" . (isset($where) ? $where : '-') . "\n"; flush(); }

	// SQL query to pages table
	$query = "
		SELECT *, id AS pid
		FROM pages
		WHERE display=1 AND " . $where . "
		LIMIT 1
	"; //echo $query;
	if (!empty($abc['debug'])) {
		$abc['debug_info']['where'] = $where;
		$abc['debug_info']['query'] = $query;
		$abc['debug_info']['u'] = $u;
		$abc['debug_info']['error_before_select'] = $error;
	}
	// $abc['page'] holds initial page data; module may extend it
	$abc['page'] = ($error == 0) ? mysql_select($query, 'row', 0) : null;
	if ($abc['route_debug'] !== null) {
		$abc['route_debug']['primary_select'] = array(
			'sql' => $query,
			'found' => !empty($abc['page']) && !empty($abc['page']['id']),
			'page_id' => !empty($abc['page']['id']) ? (int)$abc['page']['id'] : null,
			'module' => !empty($abc['page']['module']) ? (string)$abc['page']['module'] : null,
			'pages_url' => isset($abc['page']['url']) ? (string)$abc['page']['url'] : null,
		);
	}
	if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "8m. page=" . ($abc['page'] ? $abc['page']['id'] : 'null') . "\n"; flush(); }

	// Slug may exist only in content_i18n (admin i18n) while pages.url differs per legacy columns — resolve for any language.
	$route_debug_ci_sql = '';
	if ($error == 0 && !$abc['page'] && isset($slug) && isset($where) && $where !== "module='index'" && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
		$lid = (int)(isset($lang['id']) ? $lang['id'] : 0);
		if ($lid > 0) {
			$route_debug_ci_sql = "
				SELECT p.*, p.id AS pid
				FROM pages p
				WHERE p.display=1
				  AND (
					p.url='" . $slug . "'
					OR EXISTS (
						SELECT 1 FROM content_i18n ci
						WHERE ci.entity='pages'
						  AND ci.entity_id=p.id
						  AND ci.lang_id=" . $lid . "
						  AND ci.status IN ('published','review','draft','missing')
						  AND (
							ci.url='" . $slug . "'
							OR ci.url='/" . $slug . "'
							OR ci.url='/" . $slug . "/'
							OR ci.url='" . $slug . "/'
						  )
						LIMIT 1
					)
				  )
				LIMIT 1
			";
			$abc['page'] = mysql_select($route_debug_ci_sql, 'row', 0);
			if ($abc['route_debug'] !== null) {
				$abc['route_debug']['content_i18n_fallback'] = array(
					'lang_id' => $lid,
					'sql' => preg_replace('/\s+/', ' ', trim($route_debug_ci_sql)),
					'found' => !empty($abc['page']) && !empty($abc['page']['id']),
					'page_id' => !empty($abc['page']['id']) ? (int)$abc['page']['id'] : null,
				);
			}
			if (isset($_GET['debug']) && $_GET['debug'] === '1') {
				echo "8m2. content_i18n page fallback lid={$lid} found=" . ($abc['page'] ? $abc['page']['id'] : 'null') . "\n";
				flush();
			}
		} elseif ($abc['route_debug'] !== null) {
			$abc['route_debug']['content_i18n_fallback'] = array('skipped' => true, 'reason' => 'lang id is 0 — cannot match content_i18n.lang_id');
		}
	}

	// Short game URL: /{lang}/{game-slug}/ — slug lives in `games`, not `pages`. Router only saw u[1]=slug; games module needs u[1]=section, u[2]=slug.
	if ($error == 0 && !$abc['page'] && isset($slug) && isset($slug_raw) && isset($where) && $where !== "module='index'"
		&& @mysql_select("SHOW TABLES LIKE 'games'", 'num_rows') > 0) {
		$current_lang_id = (int)(isset($lang['id']) ? $lang['id'] : 1);
		$ci_games_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
		$game_row = mysql_select("SELECT * FROM games WHERE display=1 AND url='" . $slug . "' LIMIT 1", 'row', 0);
		if (!$game_row && $ci_games_ok && $current_lang_id > 1) {
			$game_row = mysql_select("
				SELECT g.*
				FROM games g
				INNER JOIN content_i18n ci ON ci.entity='games' AND ci.entity_id=g.id
					AND ci.lang_id=" . $current_lang_id . "
					AND (
						ci.url='" . $slug . "'
						OR ci.url='/" . $slug . "'
						OR ci.url='/" . $slug . "/'
						OR ci.url='" . $slug . "/'
					)
				WHERE g.display=1
				ORDER BY FIELD(ci.status,'published','review','draft','missing') ASC, ci.id DESC
				LIMIT 1
			", 'row', 0);
		}
		if ($game_row) {
			$gl_found = aviator_find_games_landing_page_row($current_lang_id);
			$games_landing = $gl_found && !empty($gl_found['row']) ? $gl_found['row'] : null;
			if ($games_landing) {
				$pi = page_i18n_fields_current($games_landing, $current_lang_id);
				$gseg = trim(trim((string)($pi['url'] ?? '')), '/');
				if ($gseg === '' && $langid !== '' && isset($games_landing['url' . $langid])) {
					$gseg = trim((string)$games_landing['url' . $langid], '/');
				}
				if ($gseg === '' && isset($games_landing['url'])) {
					$gseg = trim((string)$games_landing['url'], '/');
				}
				if ($gseg === '') {
					$gseg = 'games';
				}
				$u[1] = $gseg;
				$u[2] = $slug_raw;
				$abc['page'] = $games_landing;
				if ($abc['route_debug'] !== null) {
					$abc['route_debug']['games_short_url'] = array(
						'matched_game_id' => (int)$game_row['id'],
						'games_landing_page_id' => (int)$games_landing['id'],
						'landing_found_via' => isset($gl_found['via']) ? $gl_found['via'] : null,
						'injected_section_segment' => $gseg,
						'injected_game_slug' => $slug_raw,
						'u_after_injection' => array_values($u),
					);
				}
			} elseif ($abc['route_debug'] !== null) {
				$abc['route_debug']['games_short_url'] = array(
					'matched_game_id' => (int)$game_row['id'],
					'error' => 'games landing page not found: add pages row with module=pages and some url* = games, OR content_i18n(pages) for this lang with url games',
				);
			}
		}
	}

	// Note: URL fallback for missing per-language slugs is handled by SQL WHERE above and content_i18n block.

	// Fallback: homepage requested but no row in DB (module='index', display=1) — use minimal page so index module still runs
	if ($error == 0 && !$abc['page'] && $where === "module='index'") {
		$abc['page'] = array(
			'id' => 0, 'module' => 'index', 'level' => 1, 'left_key' => 0, 'right_key' => 0, 'parent' => 0,
			'name' => 'Home', 'name1' => 'Home', 'name2' => 'Home', 'name3' => 'Home',
			'title' => '', 'title1' => '', 'title2' => '', 'title3' => '',
			'description' => '', 'description1' => '', 'description2' => '', 'description3' => '',
			'url' => '', 'url1' => '', 'url2' => '', 'url3' => '', 'display' => 1, 'menu' => 0, 'menu2' => 0,
		);
		if (!empty($abc['debug'])) $abc['debug_info']['page_fallback'] = true;
	}

	if ($error == 0 AND $abc['page']) {
		// SEO row for PWA install guide: if /{lang}/install-pwa/ or /{lang}/ios-pwa/ (single segment), 301 to /{lang}/{download}/install-pwa/
		$__pwa_slug = isset($abc['page']['url']) ? strtolower(trim((string) $abc['page']['url'], '/')) : '';
		if (($__pwa_slug === 'ios-pwa' || $__pwa_slug === 'install-pwa')
			&& isset($abc['page']['module']) && (string) $abc['page']['module'] === 'pages'
			&& isset($u[1]) && strtolower(trim((string) $u[1], '/')) === $__pwa_slug
			&& (!isset($u[2]) || trim((string) $u[2], '/') === '')) {
			if (function_exists('pwa_install_guide_path')) {
				$to = pwa_install_guide_path($lang);
			} else {
				$to = '';
			}
			if ($to !== '') {
				header('Location: ' . $to, true, 301);
				exit;
			}
		}
		if ($__pwa_slug === 'install-apk'
			&& isset($abc['page']['module']) && (string) $abc['page']['module'] === 'pages'
			&& isset($u[1]) && strtolower(trim((string) $u[1], '/')) === $__pwa_slug
			&& (!isset($u[2]) || trim((string) $u[2], '/') === '')) {
			$to = function_exists('apk_install_guide_path') ? apk_install_guide_path($lang) : '';
			if ($to !== '') {
				header('Location: ' . $to, true, 301);
				exit;
			}
		}
		if (!empty($abc['debug'])) {
			$abc['debug_info']['page_found'] = true;
			$abc['debug_info']['page_id'] = $abc['page']['id'];
			$abc['debug_info']['page_module'] = $abc['page']['module'];
			$abc['debug_info']['page_keys'] = array_keys($abc['page']);
			$sanitized = array();
			foreach ($abc['page'] as $k => $v) {
				$sanitized[$k] = is_string($v) && strlen($v) > 300 ? substr($v, 0, 300) . '... [' . strlen($v) . ' chars]' : $v;
			}
			$abc['debug_info']['page'] = $sanitized;
		}
		$abc['module'] = $abc['page']['module'];
		$abc['layout'] = $abc['page']['module'];
		// Guides page: use guides module if page url is guides (so dropdown and list work)
		if ($abc['page']['module'] === 'pages' && (isset($abc['page']['url']) && $abc['page']['url'] === 'guides' || isset($abc['page']["url$langid"]) && $abc['page']["url$langid"] === 'guides')) {
			$abc['module'] = $abc['layout'] = 'guides';
		}
		// Games page: use games module if page url is games
		if ($abc['page']['module'] === 'pages' && (isset($abc['page']['url']) && $abc['page']['url'] === 'games' || isset($abc['page']["url$langid"]) && $abc['page']["url$langid"] === 'games')) {
			$abc['module'] = $abc['layout'] = 'games';
		}
		// Casinos page: use casinos module if page url is casinos
		if ($abc['page']['module'] === 'pages' && (isset($abc['page']['url']) && $abc['page']['url'] === 'casinos' || isset($abc['page']["url$langid"]) && $abc['page']["url$langid"] === 'casinos')) {
			$abc['module'] = 'casinos';
			$abc['layout'] = 'casinos_fixed';
		}
		// Demo page: use demo layout (interactive game block + content)
		if ((int)$abc['page']['id'] === 4 || ($abc['page']['module'] === 'pages' && (isset($abc['page']['url']) && $abc['page']['url'] === 'demo' || isset($abc['page']["url$langid"]) && $abc['page']["url$langid"] === 'demo'))) {
			$abc['layout'] = 'demo';
		}
		// Fullscreen demo shell: /{lang}/demo/app/ (same page row as demo; extra path segment "app")
		if ($abc['layout'] === 'demo' && isset($u[2]) && strtolower(trim((string) $u[2], '/')) === 'app') {
			$abc['layout'] = 'demo_app';
		}
		// PWA install guide (legacy): /{lang}/demo/ios-pwa/ → 301 to /{lang}/{download}/install-pwa/
		if ($abc['layout'] === 'demo' && isset($u[2]) && strtolower(trim((string) $u[2], '/')) === 'ios-pwa') {
			if (function_exists('pwa_install_guide_path')) {
				$to = pwa_install_guide_path($lang);
				if ($to !== '') {
					header('Location: ' . $to, true, 301);
					exit;
				}
			}
		}
		// PWA install guide: /{lang}/{download-slug}/install-pwa/ (content from `pages` row install-pwa; routed page = Download)
		$__dl_match = ($abc['page']['module'] === 'pages' && (
			(isset($abc['page']['url']) && (string) $abc['page']['url'] === 'download')
			|| ($langid !== '' && isset($abc['page']['url' . $langid]) && (string) $abc['page']['url' . $langid] === 'download')
		));
		if ($__dl_match && isset($u[2])) {
			$__pwa_seg = strtolower(trim((string) $u[2], '/'));
			if ($__pwa_seg === 'ios-pwa' && function_exists('pwa_install_guide_path')) {
				$to = pwa_install_guide_path($lang);
				if ($to !== '') {
					header('Location: ' . $to, true, 301);
					exit;
				}
			} elseif ($__pwa_seg === 'install-pwa') {
				$abc['layout'] = 'demo_pwa_ios';
			} elseif ($__pwa_seg === 'install-apk') {
				$abc['layout'] = 'demo_apk_android';
			}
		}

		// For templates/modules: normalize current-language fields (scales beyond url1/url2/url3).
		// Modules may read $abc['page_i18n'], so this must run BEFORE loading module files.
		if (!isset($abc['page_i18n']) || !is_array($abc['page_i18n'])) {
			$abc['page_i18n'] = page_i18n_fields_current(isset($abc['page']) ? $abc['page'] : array(), (int)$lang['id']);
		}

		// Breadcrumb, start with home
		$abc['breadcrumb'] = array();
		$abc['breadcrumb'][] = array(
			'name'=>i18n('common|index_page'),
			'url'=>get_url('index')
		);

		if ($abc['page']['level'] > 1) {

/*
			$query = "
				SELECT name$langid name,url$langid url,module
				FROM pages
				WHERE left_key <= " . $abc['page']['left_key'] . "
					AND right_key >= " . $abc['page']['right_key'] . "
				ORDER BY left_key ASC
			";
*/
			$bcstr=get_url('index');
			$query = "
				SELECT *
				FROM pages
				WHERE left_key <= " . $abc['page']['left_key'] . "
					AND right_key >= " . $abc['page']['right_key'] . "
				ORDER BY left_key ASC
			";
			$rows=mysql_select($query,'rows');
			// Track if translation exists for each language along the path; if missing -> keep only /{lang}/
			$link_ok = array();
			foreach ($abc['languages'] as $i => $v) {
				$link_ok[$abc['languages'][$i]['url']] = true;
			}
			foreach($rows as $row) {

				$seg = '';
				if ($langid !== '' && isset($row['url' . $langid])) $seg = (string)$row['url' . $langid];
				if ($seg === '' && isset($row['url'])) $seg = (string)$row['url'];
				$seg = trim($seg, '/');
				if ($seg !== '') $bcstr .= $seg . '/';
				$abc['breadcrumb'][] = array(
					'name'=>(function() use ($row, $langid, $lang) {
						$nm = trim((string)($row["name".$langid] ?? ''));
						if ($nm !== '') return $nm;
						// Fallback to scalable i18n stored in content_i18n (page_i18n) when pages.name$langid is empty.
						$pid = isset($row['id']) ? (int)$row['id'] : 0;
						$lid = isset($lang['id']) ? (int)$lang['id'] : 0;
						if ($pid <= 0 || $lid <= 0) return '';
						$pi = page_i18n_fields_current($row, $lid);
						return trim((string)($pi['name'] ?? ''));
					})(),
					'url'=>$bcstr
				);

				foreach($abc['languages'] as $i=>$v) {
					$langUrl = $abc['languages'][$i]['url'];
					if (!$link_ok[$langUrl]) continue;
					$slug = null;
					$lid = (int)$abc['languages'][$i]['id'];
					$slug = page_i18n_slug((int)$row['id'], $lid);
					if ($slug === null) {
						// legacy fallback (only for small lang ids with urlN columns)
						$legacy = isset($row['url' . ($i>1?$i:'')]) ? trim((string)$row['url' . ($i>1?$i:'')], '/') : '';
						if ($legacy !== '') $slug = $legacy;
					}
					if ($slug === null || $slug === '') {
						// missing translation -> fallback to /{lang}/
						$abc['links'][$langUrl] = array($langUrl);
						$link_ok[$langUrl] = false;
						continue;
					}
					$abc['links'][$langUrl][] = $slug;
				}


			}


/*
//			$breadcrumb = breadcrumb($query, get_url('page',array('url'=>'{url}','module'=>'{module}')), 60 * 60);
			$abc['breadcrumb'][] = array(
				'name'=>$abc['page']["name$langid"],
				'url'=>get_url('page', $abc['page'])
			);
			$abc['breadcrumb'] = array_merge($abc['breadcrumb'],$breadcrumb);
*/

//print_r($abc['breadcrumb']);
//exit;

/*
for($i=1;$i<=count($abc['languages']);$i++) {
//	$abc['links'][$abc['languages'][$i]['url']][]=explode('/',get_url('page',array('url'=>$abc['page']['url'.($i>1?$i:'')])));
//	$abc['links'][$abc['languages'][$i]['url']][]=get_url('page',array('url'=>$abc['page']['url'.($i>1?$i:'')]),$i);
	$abc['links'][$abc['languages'][$i]['url']][]=$abc['languages'][$i]['url'];
	$abc['links'][$abc['languages'][$i]['url']][]=$abc['page']['url'.($i>1?$i:'')];
}
*/
		} elseif($abc['page']['module']!='index') {
			$abc['breadcrumb'][] = array(
				'name'=>(function() use ($abc, $langid, $lang) {
					// Stable section titles: avoid wrong content_i18n/pages.name values for canonical games/guides.
					$slug = isset($abc['page']['url']) ? (string)$abc['page']['url'] : '';
					if ($slug === 'games') return (string)i18n('common|games_title');
					if ($slug === 'guides') return (string)i18n('common|guides_title');
					if ($slug === 'authors') return (string)author_list_title();

					$nm = trim((string)($abc['page']["name".$langid] ?? ''));
					if ($nm !== '') return $nm;
					$nm2 = trim((string)($abc['page_i18n']['name'] ?? ''));
					return $nm2;
				})(),
				'url'=>get_url('page', $abc['page'])
			);
			foreach($abc['languages'] as $i=>$v) {
				$langUrl = $abc['languages'][$i]['url'];
				$lid = (int)$abc['languages'][$i]['id'];
				$slug = page_i18n_slug((int)$abc['page']['id'], $lid);
				if ($slug === null) {
					$legacy = isset($abc['page']['url' . ($i>1?$i:'')]) ? trim((string)$abc['page']['url' . ($i>1?$i:'')], '/') : '';
					if ($legacy !== '') $slug = $legacy;
				}
				if ($slug === null || $slug === '') {
					$abc['links'][$langUrl] = array($langUrl);
					continue;
				}
				$abc['links'][$langUrl][] = $slug;
			}
/*
			$abc['links']=array();
			for($i=1;$i<=count($abc['languages']);$i++) {
				$abc['links'][$abc['languages'][$i]['url']][]=$abc['languages'][$i]['url'];
				$abc['links'][$abc['languages'][$i]['url']][]=$abc['page']['url'.($i>1?$i:'')];
			}
*/
		}

		if (!empty($abc['layout']) && $abc['layout'] === 'demo_pwa_ios') {
			if (function_exists('pwa_install_merge_seo_child_into_abc')) {
				pwa_install_merge_seo_child_into_abc($abc, $lang);
			}
			pwa_install_apply_page_meta($abc, $lang);
		}
		if (!empty($abc['layout']) && $abc['layout'] === 'demo_apk_android') {
			if (function_exists('apk_install_merge_seo_child_into_abc')) {
				apk_install_merge_seo_child_into_abc($abc, $lang);
			}
			apk_install_apply_page_meta($abc, $lang);
		}

		// Load module (use blog_cat when blog page has category/tag/article in URL)
		$file_module = ROOT_DIR . 'modules/' . $abc['module'] . '.php';
		if ($abc['module'] === 'blog' && !empty($u[2])) {
			$file_module = ROOT_DIR . 'modules/blog_cat.php';
		}
		if (is_file($file_module)) {
			require_once($file_module);

			// Extra debug for i18n/content issues (predictor/fr blank page).
			if (!empty($debug_translit) && !empty($abc['debug_info'])) {
				$abc['debug_info']['debug_translit'] = array(
					'lang_url' => isset($lang['url']) ? $lang['url'] : null,
					'langid' => isset($langid) ? $langid : null,
					'page_id' => isset($abc['page']['id']) ? $abc['page']['id'] : null,
					'module' => isset($abc['module']) ? $abc['module'] : null,
					'layout' => isset($abc['layout']) ? $abc['layout'] : null,
					'page_i18n_has_content' => isset($abc['page_i18n']['content']) ? (trim((string)$abc['page_i18n']['content']) !== '') : false,
					'page_i18n_content_len' => isset($abc['page_i18n']['content']) ? strlen((string)$abc['page_i18n']['content']) : 0,
					'page_text_len' => isset($abc['page']['text']) ? strlen((string)$abc['page']['text']) : 0,
					'content_len' => isset($abc['content']) ? strlen((string)$abc['content']) : 0,
					'content_empty' => !isset($abc['content']) || trim((string)$abc['content']) === '',
					'content_preview_200' => isset($abc['content']) ? (function_exists('mb_substr') ? mb_substr(strip_tags((string)$abc['content']), 0, 200, 'UTF-8') : substr(strip_tags((string)$abc['content']), 0, 200)) : '',
				);
			}

			// If no layout file, use default (for $abc['content'])
			if (!file_exists(ROOT_DIR.$config['style'].'/includes/layouts/'.$abc['layout'].'.php')) {
				$abc['layout'] = 'default';
			}
		}
		else {
			trigger_error('file not exists ' . $file_module, E_USER_DEPRECATED);
			$error++;
			if ($abc['route_debug'] !== null) {
				$abc['route_debug']['module_file_missing'] = $file_module;
			}
		}
	}
	else {
		if (!empty($abc['debug'])) $abc['debug_info']['page_found'] = false;
		$error++;
		if ($abc['route_debug'] !== null) {
			$abc['route_debug']['page_block'] = array(
				'outcome' => 'no_row_or_module_failed',
				'had_page_before_block' => false,
			);
		}
	}

// Multilingual: URL segment u[0] must match resolved language (only when lang is first segment).
// Use strcasecmp + full trim: DB may store "FR", " fr ", or "fr/" — strict === caused false 404.
if (!empty($config['multilingual']) && !empty($config['multilingual_u0'])) {
	$u0 = trim(trim((string)(isset($u[0]) ? $u[0] : '')), '/');
	$lurl = trim(trim((string)(isset($lang['url']) ? $lang['url'] : '')), '/');
	if ($abc['route_debug'] !== null) {
		$abc['route_debug']['lang_segment_vs_language_row'] = array(
			'u0_from_path' => $u0,
			'languages_url_trimmed' => $lurl,
			'strcasecmp_equal' => strcasecmp($u0, $lurl) === 0,
		);
	}
	if (strcasecmp($u0, $lurl) !== 0) {
		$error++;
		if ($abc['route_debug'] !== null) {
			$abc['route_debug']['lang_segment_vs_language_row']['incremented_error_404'] = true;
		}
	}
}

// page_i18n is computed above (before module load)

if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "9. error=" . $error . " layout=" . (isset($abc['layout']) ? $abc['layout'] : '-') . " page_id=" . (isset($abc['page']['id']) ? $abc['page']['id'] : 'no') . "\n"; flush(); }

// Full route + SQL report: ?debug_route=1 (access: localhost, admin, or debug_route_key in config)
if ($route_debug_request && $route_debug_ok && $abc['route_debug'] !== null) {
	header('Content-Type: text/plain; charset=utf-8');
	http_response_code(200);
	$rd = &$abc['route_debug'];
	$rd['summary'] = array(
		'final_error_count' => $error,
		'will_render_404' => $error > 0,
		'layout_if_no_404' => isset($abc['layout']) ? $abc['layout'] : null,
		'hints' => array(),
	);
	if (isset($slug) && isset($where) && $where !== "module='index'") {
		$rd['db_probe_pages_matching_url_any_display'] = mysql_select("SELECT id, display, module, LEFT(url,160) AS url FROM pages WHERE url='" . $slug . "' LIMIT 8", 'rows', 0);
		$rd['db_probe_content_i18n_matching_url'] = mysql_select("SELECT entity_id, lang_id, LEFT(url,160) AS url, status FROM content_i18n WHERE entity='pages' AND (url='" . $slug . "' OR url='/" . $slug . "/' OR url='/" . $slug . "' OR url='" . $slug . "/') ORDER BY lang_id, id DESC LIMIT 24", 'rows', 0);
		if (@mysql_select("SHOW TABLES LIKE 'games'", 'num_rows') > 0) {
			$rd['db_probe_games_url'] = mysql_select("SELECT id, display, LEFT(url,160) AS url FROM games WHERE url='" . $slug . "' LIMIT 6", 'rows', 0);
		}
		if (!empty($rd['db_probe_pages_matching_url_any_display'])) {
			foreach ($rd['db_probe_pages_matching_url_any_display'] as $pr) {
				if (isset($pr['display']) && (int)$pr['display'] === 0) {
					$rd['summary']['hints'][] = 'Row exists in pages with this url but display=0 — page is hidden.';
					break;
				}
			}
		}
		if (empty($rd['db_probe_pages_matching_url_any_display']) && empty($rd['db_probe_content_i18n_matching_url'])) {
			if (!empty($rd['db_probe_games_url'])) {
				$rd['summary']['hints'][] = 'Slug is a game (`games` table), not a CMS page. Router maps /{lang}/{slug}/ to the Games section; ensure a `pages` row exists with url or urlN = games for the landing.';
			} else {
				$rd['summary']['hints'][] = 'No pages.url, no content_i18n (pages), and no games.url match — wrong slug or content missing.';
			}
		}
	}
	if ($error > 0 && !empty($rd['lang_segment_vs_language_row']['incremented_error_404'])) {
		$rd['summary']['hints'][] = 'Language segment check failed: first path segment after shift does not match languages.url for resolved row.';
	}
	$ps = isset($rd['primary_select']) ? $rd['primary_select'] : array();
	$fb = isset($rd['content_i18n_fallback']) ? $rd['content_i18n_fallback'] : array();
	if ($error > 0 && isset($slug) && empty($ps['found'])) {
		if (!empty($fb['skipped'])) {
			$rd['summary']['hints'][] = 'content_i18n fallback was not run: ' . (isset($fb['reason']) ? $fb['reason'] : 'see content_i18n_fallback key');
		} elseif (empty($fb['found'])) {
			$rd['summary']['hints'][] = 'Primary SELECT (display=1) and content_i18n fallback both returned no row — compare primary_select.sql with db_probe_* sections.';
		}
	}
	if (!empty($rd['module_file_missing'])) {
		$rd['summary']['hints'][] = 'Module PHP file missing: ' . $rd['module_file_missing'];
	}
	echo json_encode($rd, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

//404
if ($error>0) {

	header("HTTP/1.0 404 Not Found");
//	header("HTTP/1.0 410 Gone");
	if (!isset($abc['page'])) $abc['page'] = array();
	$abc['page']["title$langid"] = $abc['page']["name$langid"] = $abc['page']["description$langid"] = i18n('common|txt_no_page_text');
	$abc['layout'] = 'error';
}
if (!empty($abc['debug'])) {
	$abc['debug_info']['error'] = $error;
	$abc['debug_info']['layout'] = $abc['layout'];
	$abc['debug_info']['lang'] = isset($abc['lang']) ? $abc['lang'] : array();
	if (isset($abc['debug_info']['lang']['dictionary'])) {
		$abc['debug_info']['lang']['dictionary'] = '[serialized, ' . strlen($abc['debug_info']['lang']['dictionary']) . ' bytes]';
	}
	$abc['debug_info']['langid'] = isset($abc['langid']) ? $abc['langid'] : '—';
	// Front template and assets (to see why layout/CSS might not apply)
	$abc['debug_info']['config_style'] = isset($config['style']) ? $config['style'] : '—';
	$abc['debug_info']['root_dir'] = ROOT_DIR;
	$tpl_dir = ROOT_DIR . (isset($config['style']) ? $config['style'] : '') . '/includes/layouts/';
	$abc['debug_info']['template_wrapper'] = $tpl_dir . '_template.php';
	$abc['debug_info']['template_wrapper_exists'] = file_exists($tpl_dir . '_template.php');
	$abc['debug_info']['template_layout'] = $tpl_dir . $abc['layout'] . '.php';
	$abc['debug_info']['template_layout_exists'] = file_exists($tpl_dir . $abc['layout'] . '.php');
	$abc['debug_info']['menu_order'] = array();
	if (!empty($abc['menu']) && is_array($abc['menu'])) {
		foreach ($abc['menu'] as $id => $item) {
			$abc['debug_info']['menu_order'][] = array(
				'id' => $id,
				'name' => isset($item['name']) ? $item['name'] : '—',
				'module' => isset($item['module']) ? $item['module'] : '—',
				'_url' => isset($item['_url']) ? substr($item['_url'], 0, 80) . (strlen($item['_url']) > 80 ? '...' : '') : '—',
			);
		}
	}
	$v = function($f) { return @file_exists($f) ? filemtime($f) : null; };
	$r = ROOT_DIR;
	$abc['debug_info']['assets'] = array(
		'style.css' => array('path' => $r . 'assets/css/style.css', 'exists' => file_exists($r . 'assets/css/style.css'), 'v' => $v($r . 'assets/css/style.css')),
		'responsive.css' => array('path' => $r . 'assets/css/responsive.css', 'exists' => file_exists($r . 'assets/css/responsive.css'), 'v' => $v($r . 'assets/css/responsive.css')),
		'script.js' => array('path' => $r . 'assets/js/script.js', 'exists' => file_exists($r . 'assets/js/script.js'), 'v' => $v($r . 'assets/js/script.js')),
	);
}
// Redirects when not 404
else{
	// 1) 301 redirect for URL without trailing slash
	if($_SERVER['REQUEST_URI']) {
		//$request_url = explode('?',$_SERVER['REQUEST_URI']);
		if (substr($request_url[0], -1)!='/') {
			$url = isset($request_url[1]) ? '?'.$request_url[1] : '';
			header('HTTP/1.1 301 Moved Permanently');
			die(header('location: '.$request_url[0].'/'.$url));
		}
	}
	// 2) Redirect when request contains index.php
	if (strpos($_SERVER['REQUEST_URI'],'/index.php')!==false) {
		header('HTTP/1.1 301 Moved Permanently');
		// Redirect to URL without index.php
		die(header('location: '.$config['http_domain'].str_replace('/index.php','',$_SERVER['REQUEST_URI'])));
	}
	// 3) Redirect uppercase to lowercase (v1.2.30)
	if (@$config['redirect_uppercase']) {
		//$request_url = explode('?',$_SERVER['REQUEST_URI']); //print_r($request_url);
		$lowercase = mb_strtolower($request_url[0],'UTF-8');
		if ($lowercase!=$request_url[0]) {
			header('HTTP/1.1 301 Moved Permanently');
			header('location: '.$config['http_domain'].$lowercase);
			die();
		}
	}
}
// Output buffer: gzip commented out
if (@$config['html_minify']) {
	ob_start("html_minify"); // HTML minify in common_func
}

if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "10. before html_render layouts/_template\n"; flush(); }

// Load template
echo html_render('layouts/_template');

if (isset($_GET['debug']) && $_GET['debug'] === '1') { echo "\n11. after html_render (done)\n"; flush(); }

//debug queries
if (access('user admin') AND @$_GET['show_queries']) {
	dd($config['queries']);
}