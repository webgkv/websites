<?php
/**
 * Advertising API: fetch offer/banner from external backend (same contract as agent sites).
 * Uses advertising_api from $abc (variables). Caches last successful; on API failure uses cache.
 * Country: CF header first, then geo by IP (backend does not resolve IP itself).
 * Returns partner array: link_code, banner1, banner1_url, banner2, html (banner2 html).
 */

function aviator_ad_first_valid_ip($value) {
	if (!is_string($value) || trim($value) === '') return '';
	$parts = explode(',', $value);
	foreach ($parts as $part) {
		$ip = trim($part);
		if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
	}
	return '';
}

function aviator_ad_is_private_or_reserved_ip($ip) {
	if (!is_string($ip) || trim($ip) === '') return false;
	$ip = trim($ip);
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function aviator_ad_proxy_whitelist($ad_config = array()) {
	$raw = isset($ad_config['trusted_proxy_ips']) ? $ad_config['trusted_proxy_ips'] : array();
	$out = array();
	if (is_string($raw)) {
		$raw = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
	}
	if (is_array($raw)) {
		foreach ($raw as $ip) {
			$ip = trim((string)$ip);
			if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) $out[$ip] = true;
		}
	}
	return $out;
}

function aviator_ad_resolve_ip_context($ad_config = array()) {
	$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
	$cf_ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? aviator_ad_first_valid_ip((string)$_SERVER['HTTP_CF_CONNECTING_IP']) : '';
	$xff_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? aviator_ad_first_valid_ip((string)$_SERVER['HTTP_X_FORWARDED_FOR']) : '';
	$x_real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? aviator_ad_first_valid_ip((string)$_SERVER['HTTP_X_REAL_IP']) : '';
	$client_ip = isset($_SERVER['HTTP_CLIENT_IP']) ? aviator_ad_first_valid_ip((string)$_SERVER['HTTP_CLIENT_IP']) : '';
	$trusted_real_ip = '';
	$whitelist = aviator_ad_proxy_whitelist($ad_config);
	$is_trusted_proxy = $remote_addr !== '' && isset($whitelist[$remote_addr]);
	$auto_trust_forwarded = $remote_addr !== '' && aviator_ad_is_private_or_reserved_ip($remote_addr);
	if ($is_trusted_proxy || $auto_trust_forwarded || empty($whitelist)) {
		if ($cf_ip !== '') $trusted_real_ip = $cf_ip;
		elseif ($xff_ip !== '') $trusted_real_ip = $xff_ip;
		elseif ($x_real_ip !== '') $trusted_real_ip = $x_real_ip;
		elseif ($client_ip !== '') $trusted_real_ip = $client_ip;
	}
	$ip_sent = $trusted_real_ip !== '' ? $trusted_real_ip : $remote_addr;
	$ip_source = 'remote_addr';
	if ($trusted_real_ip !== '') {
		if ($trusted_real_ip === $cf_ip && $cf_ip !== '') $ip_source = 'xff_ip';
		elseif ($trusted_real_ip === $xff_ip && $xff_ip !== '') $ip_source = 'xff_ip';
		elseif ($trusted_real_ip === $x_real_ip && $x_real_ip !== '') $ip_source = 'xff_ip';
		elseif ($trusted_real_ip === $client_ip && $client_ip !== '') $ip_source = 'xff_ip';
	}
	return array(
		'remote_addr' => $remote_addr,
		'trusted_real_ip' => $trusted_real_ip,
		'ip_sent_to_backend' => $ip_sent,
		'trusted_proxy_match' => $is_trusted_proxy || $auto_trust_forwarded,
		'ip_source' => $ip_source,
	);
}

function aviator_ad_resolve_country_context($ad_config = array(), $ip_ctx = array()) {
	$manual_country = isset($ad_config['manual_country']) ? strtoupper(substr(trim((string)$ad_config['manual_country']), 0, 2)) : '';
	if (!preg_match('/^[A-Z]{2}$/', $manual_country)) $manual_country = '';
	$cf_country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(substr(trim((string)$_SERVER['HTTP_CF_IPCOUNTRY']), 0, 2)) : '';
	if (!preg_match('/^[A-Z]{2}$/', $cf_country) || $cf_country === 'T1') $cf_country = '';
	$geo_country = '';
	$source = '';
	$country = '';

	if ($manual_country !== '') {
		$country = $manual_country;
		$source = 'manual_country';
	}
	else {
		// Prefer local geo by resolved real IP for backend-facing country.
		$ip_for_geo = isset($ip_ctx['ip_sent_to_backend']) ? (string)$ip_ctx['ip_sent_to_backend'] : '';
		if ($ip_for_geo !== '') $geo_country = aviator_ad_country_by_ip($ip_for_geo);
		if ($geo_country !== '') {
			$country = $geo_country;
			$source = isset($ip_ctx['trusted_real_ip']) && (string)$ip_ctx['trusted_real_ip'] !== '' ? 'xff_ip' : 'remote_addr';
		}
		elseif ($cf_country !== '') {
			$country = $cf_country;
			$source = 'cf_header';
		}
	}

	if ($country === '') {
		$country = 'XX';
		$source = 'backend_geo';
	}

	return array(
		'country_header_cf' => $cf_country,
		'country_by_local_geo' => $geo_country,
		'country_sent_to_backend' => $country,
		'source_of_country' => $source,
	);
}

/**
 * Resolve country code by IP (when no Cloudflare or proxy header). Uses ip-api.com, cache in data/.
 */
function aviator_ad_country_by_ip($ip) {
	if (empty($ip) || !preg_match('/^[0-9a-f.:]{7,45}$/i', trim($ip))) return '';
	$ip = trim($ip);
	$cache_dir = defined('ROOT_DIR') ? (rtrim(ROOT_DIR, '/') . '/data') : (dirname(__DIR__) . '/data');
	$cache_file = $cache_dir . '/ad_geo_ip.json';
	$cache = array();
	if (is_file($cache_file) && is_readable($cache_file)) {
		$raw = @file_get_contents($cache_file);
		if ($raw !== false) {
			$dec = json_decode($raw, true);
			if (is_array($dec)) $cache = $dec;
		}
	}
	if (isset($cache[$ip])) {
		$c = $cache[$ip];
		if (is_array($c) && isset($c['ts'])) {
			$cached_country = isset($c['country']) ? (string)$c['country'] : '';
			$ttl = $cached_country !== '' ? 86400 : 600;
			if ((time() - (int)$c['ts']) < $ttl) return $cached_country;
		}
		if (is_string($c) && strlen($c) === 2) return $c;
	}
	$country = '';
	if (function_exists('curl_init')) {
		$providers = array(
			array('url' => 'https://ip-api.com/json/' . rawurlencode($ip) . '?fields=countryCode', 'field' => 'countryCode'),
			array('url' => 'https://ipwho.is/' . rawurlencode($ip), 'field' => 'country_code'),
			array('url' => 'https://ipapi.co/' . rawurlencode($ip) . '/json/', 'field' => 'country_code'),
		);
		foreach ($providers as $p) {
			$ch = curl_init($p['url']);
			curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 2, CURLOPT_CONNECTTIMEOUT => 1));
			$body = curl_exec($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($code === 200 && $body !== false) {
				$j = json_decode($body, true);
				$v = is_array($j) && isset($j[$p['field']]) ? (string)$j[$p['field']] : '';
				if ($v !== '' && preg_match('/^[A-Za-z]{2}$/', $v)) {
					$country = strtoupper($v);
					break;
				}
			}
		}
	} else {
		$providers = array(
			array('url' => 'https://ip-api.com/json/' . rawurlencode($ip) . '?fields=countryCode', 'field' => 'countryCode'),
			array('url' => 'https://ipwho.is/' . rawurlencode($ip), 'field' => 'country_code'),
			array('url' => 'https://ipapi.co/' . rawurlencode($ip) . '/json/', 'field' => 'country_code'),
		);
		foreach ($providers as $p) {
			$ctx = stream_context_create(array('http' => array('timeout' => 2)));
			$body = @file_get_contents($p['url'], false, $ctx);
			if ($body !== false) {
				$j = json_decode($body, true);
				$v = is_array($j) && isset($j[$p['field']]) ? (string)$j[$p['field']] : '';
				if ($v !== '' && preg_match('/^[A-Za-z]{2}$/', $v)) {
					$country = strtoupper($v);
					break;
				}
			}
		}
	}
	$cache[$ip] = array('country' => $country, 'ts' => time());
	if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
	if (is_dir($cache_dir) && is_writable($cache_dir)) @file_put_contents($cache_file, json_encode($cache, JSON_UNESCAPED_UNICODE));
	return $country;
}

/**
 * Server-side fetch must use JSON track API (/track/CODE/api or t.php?api=1), not browser redirect /track/CODE.
 */
function aviator_ad_normalize_track_api_url($base) {
	$base = trim(rtrim((string)$base, '/'));
	if ($base === '') {
		return '';
	}
	if (preg_match('#/track/([A-Za-z0-9]{5})$#i', $base, $m)) {
		return $base . '/api';
	}
	if (preg_match('#/track/([A-Za-z0-9]{5})/api$#i', $base)) {
		return $base;
	}
	if (stripos($base, 't.php') !== false && stripos($base, 'api=1') === false) {
		return $base . (strpos($base, '?') !== false ? '&' : '?') . 'api=1';
	}
	return $base;
}

/**
 * Extract partners_offers.code from track/t.php URLs in api_sources (for banner API parity with click).
 */
function aviator_ad_offer_code_from_sources($sources) {
	if (!is_array($sources)) {
		return '';
	}
	foreach ($sources as $base) {
		$base = trim((string)$base);
		if ($base === '') {
			continue;
		}
		if (preg_match('#/track/([A-Za-z0-9]{5})(?:/|$|\?)#', $base, $m)) {
			return $m[1];
		}
		if (preg_match('#[?&]o=([A-Za-z0-9]{5})(?:&|$)#', $base, $m)) {
			return $m[1];
		}
	}
	return '';
}

/**
 * Probe first api_sources URL (track) — same call as /go/ button click. For ?debug_ip_check=1.
 */
function aviator_ad_debug_track_click_preview($ad_config, $country_ctx, $ip_ctx) {
	$sources = isset($ad_config['api_sources']) && is_array($ad_config['api_sources']) ? $ad_config['api_sources'] : array();
	$token = isset($ad_config['token']) ? (string)$ad_config['token'] : '';
	$out = array(
		'note' => 'Button click uses api_sources (track), NOT b.php link_code. This probe mirrors /go/ handler.',
		'offer_code_from_api_sources' => aviator_ad_offer_code_from_sources($sources),
		'probes' => array(),
	);
	$country_to_backend = isset($country_ctx['country_sent_to_backend']) ? (string)$country_ctx['country_sent_to_backend'] : '';
	if ($country_to_backend === '') {
		$country_to_backend = 'XX';
	}
	foreach ($sources as $base) {
		$base = trim(rtrim((string)$base, '/'));
		if ($base === '') {
			continue;
		}
		$url = aviator_ad_normalize_track_api_url($base);
		$sep = (strpos($url, '?') !== false) ? '&' : '?';
		if (strpos($url, 'token=') === false && $token !== '') {
			$url .= $sep . 'token=' . rawurlencode($token);
			$sep = '&';
		}
		if (!preg_match('/[?&]country=/', $url)) {
			$url .= $sep . 'country=' . rawurlencode($country_to_backend);
			$sep = '&';
		}
		if (!empty($ip_ctx['ip_sent_to_backend']) && !preg_match('/[?&]ip=/', $url)) {
			$url .= $sep . 'ip=' . rawurlencode((string)$ip_ctx['ip_sent_to_backend']);
			$sep = '&';
		}
		if (!preg_match('/[?&]debug_routing=/', $url)) {
			$url .= $sep . 'debug_routing=1';
		}
		$probe = array(
			'track_url_configured' => preg_replace('#token=[^&]+#', 'token=***', $base),
			'track_url_called' => preg_replace('#token=[^&]+#', 'token=***', $url),
			'uses_track_api_json' => (stripos($url, '/api') !== false || stripos($url, 'api=1') !== false) ? 1 : 0,
			'offer_code_in_url' => aviator_ad_offer_code_from_sources(array($base)),
			'http_code' => 0,
			'error' => '',
			'response_parsed' => null,
			'affiliate_url' => null,
			'affiliate_host' => '',
		);
		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 8,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_SSL_VERIFYPEER => false,
			));
			$body = curl_exec($ch);
			$probe['http_code'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$probe['error'] = (string)curl_error($ch);
			curl_close($ch);
		} else {
			$ctx = stream_context_create(array('http' => array('timeout' => 5), 'ssl' => array('verify_peer' => false)));
			$body = @file_get_contents($url, false, $ctx);
		}
		if (!empty($body)) {
			$data = json_decode($body, true);
			$probe['response_parsed'] = is_array($data) ? $data : null;
			if (is_array($data) && !empty($data['url']) && preg_match('#^https?://#i', $data['url'])) {
				$probe['affiliate_url'] = $data['url'];
				$pu = parse_url($data['url']);
				$probe['affiliate_host'] = isset($pu['host']) ? (string)$pu['host'] : '';
			}
			if (is_array($data) && !empty($data['link_code'])) {
				$probe['resolved_link_code'] = $data['link_code'];
			}
			if (is_array($data) && !empty($data['routing']) && is_array($data['routing'])) {
				$probe['routing'] = $data['routing'];
			}
		}
		$out['probes'][] = $probe;
		break;
	}
	if (empty($out['probes'])) {
		$out['error'] = 'No api_sources URLs configured (Advertising → URLs, one per line)';
	}
	return $out;
}

function aviator_ad_get_partner(&$api_debug = null) {
	global $abc;
	$config = isset($abc['advertising_api']) ? $abc['advertising_api'] : array();
	if (empty($config['mode']) || $config['mode'] !== 'api') {
		return null;
	}
	// Banner fetch: use api_sources_banners; if empty, fall back to api_sources (links)
	$api_sources_banners = isset($config['api_sources_banners']) && is_array($config['api_sources_banners']) ? $config['api_sources_banners'] : array();
	if (empty($api_sources_banners)) {
		$api_sources_banners = isset($config['api_sources']) && is_array($config['api_sources']) ? $config['api_sources'] : array();
	}
	if (empty($api_sources_banners)) {
		return null;
	}
	$collect_debug = is_array($api_debug);
	$ip_ctx = aviator_ad_resolve_ip_context($config);
	$ip_for_backend = isset($ip_ctx['ip_sent_to_backend']) ? trim((string)$ip_ctx['ip_sent_to_backend']) : '';
	$lang_sent = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url']) : '';
	if ($lang_sent === '') $lang_sent = 'en';
	if ($collect_debug) $api_debug['lang_sent'] = $lang_sent;
	$priority = isset($config['api_sources_priority']) ? trim(rtrim((string)$config['api_sources_priority'], '/')) : '';
	if ($priority !== '' && in_array($priority, $api_sources_banners)) {
		$api_sources_banners = array_merge(array($priority), array_diff($api_sources_banners, array($priority)));
	}
	$country_ctx = aviator_ad_resolve_country_context($config, $ip_ctx);
	$country = isset($country_ctx['country_sent_to_backend']) ? (string)$country_ctx['country_sent_to_backend'] : 'XX';
	$cache_ttl = 300; // 5 min
	$cache_dir = defined('ROOT_DIR') ? (rtrim(ROOT_DIR, '/') . '/data') : (dirname(__DIR__) . '/data');
	$cache_file = $cache_dir . '/ad_api_cache.json';
	$read_cache = function() use ($cache_file) {
		if (!is_file($cache_file) || !is_readable($cache_file)) return null;
		$raw = @file_get_contents($cache_file);
		return $raw !== false ? json_decode($raw, true) : null;
	};
	$write_cache = function($data) use ($cache_dir, $cache_file) {
		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
		if (is_dir($cache_dir) && is_writable($cache_dir)) {
			@file_put_contents($cache_file, json_encode($data, JSON_UNESCAPED_UNICODE));
		}
	};
	$cached = $read_cache();
	$last_success = isset($cached['last_success']) && is_array($cached['last_success']) ? $cached['last_success'] : null;
	$by_country = isset($cached['by_country']) && is_array($cached['by_country']) ? $cached['by_country'] : array();
	$now = time();
	if (isset($by_country[$country]['ts']) && ($now - (int)$by_country[$country]['ts']) < $cache_ttl && !empty($by_country[$country]['data'])) {
		if ($collect_debug) {
			$api_debug['from_cache'] = true;
			$api_debug['country'] = $country;
			$api_debug['cache_ttl_remaining'] = $cache_ttl - ($now - (int)$by_country[$country]['ts']);
			$api_debug['partner'] = $by_country[$country]['data'];
		}
		return $by_country[$country]['data'];
	}
	if ($collect_debug) {
		$api_debug['from_cache'] = false;
		$api_debug['country'] = $country;
		$api_debug['lang_sent'] = $lang_sent;
		$api_debug['tries'] = array();
	}
	$token = isset($config['token']) ? $config['token'] : '';
	$link_sources = isset($config['api_sources']) && is_array($config['api_sources']) ? $config['api_sources'] : array();
	$offer_code_auto = aviator_ad_offer_code_from_sources($link_sources);
	if ($collect_debug) {
		$api_debug['offer_code_auto_from_api_sources'] = $offer_code_auto;
	}
	$country_append = 'country=' . rawurlencode($country);
	$ip_append = ($ip_for_backend !== '' ? '&ip=' . rawurlencode($ip_for_backend) : '');
	$ctx = stream_context_create(array(
		'http' => array('timeout' => 5),
		'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
	));
	$partner = null;
	foreach ($api_sources_banners as $base) {
		$base = rtrim($base, '/');
		// Base may be https://api.tcdu1.live/b.php or https://api.tcdu1.live/b.php?token=XXX (token optional in field)
		if (strpos($base, '?') !== false) {
			$banner_url = $base;
			if (strpos($base, 'token=') === false && $token !== '') {
				$banner_url .= '&token=' . rawurlencode($token);
			}
			if (preg_match('/[?&]country=/', $base) === 0) {
				$banner_url .= '&country=' . rawurlencode($country);
			}
			if ($ip_for_backend !== '' && preg_match('/[?&]ip=/', $base) === 0) {
				$banner_url .= '&ip=' . rawurlencode($ip_for_backend);
			}
			if (preg_match('/[?&](lang|locale)=/', $base) === 0) {
				$banner_url .= '&lang=' . rawurlencode($lang_sent) . '&locale=' . rawurlencode($lang_sent);
			}
			if ($offer_code_auto !== '' && preg_match('/[?&](offer_code|o)=/', $banner_url) === 0) {
				$banner_url .= '&offer_code=' . rawurlencode($offer_code_auto);
			}
		} else {
			$query = ($token !== '' ? 'token=' . rawurlencode($token) . '&' : '')
				. $country_append
				. ($ip_for_backend !== '' ? '&ip=' . rawurlencode($ip_for_backend) : '')
				. '&lang=' . rawurlencode($lang_sent) . '&locale=' . rawurlencode($lang_sent);
			if ($offer_code_auto !== '') {
				$query .= '&offer_code=' . rawurlencode($offer_code_auto);
			}
			$banner_url = (strpos($base, 'b.php') !== false) ? $base . '?' . $query : $base . '/banner?' . $query;
		}
		$json = @file_get_contents($banner_url, false, $ctx);
		$data = $json ? json_decode($json, true) : null;
		if ($collect_debug) {
			$api_debug['tries'][] = array(
				'url' => preg_replace('#token=[^&]+#', 'token=***', $banner_url),
				'response_ok' => !empty($data['ok']),
				'has_link_code' => !empty($data['link_code']),
				'has_banner1' => !empty($data['banner1']),
				'has_banner2' => !empty($data['banner2']),
				'data' => $data,
			);
		}
		$accepted = !empty($data['ok']) && (!empty($data['link_code']) || !empty($data['banner1']) || !empty($data['banner2']));
		if ($accepted) {
			$banner_lang = isset($data['banner1']['banner_lang']) ? (string)$data['banner1']['banner_lang'] : '';
			$match_level = '';
			if (isset($data['banner_meta']['match_level'])) $match_level = (string)$data['banner_meta']['match_level'];
			elseif (isset($data['banner1']['match_level'])) $match_level = (string)$data['banner1']['match_level'];
			$fallback_reason = isset($data['banner_meta']['fallback_reason']) ? (string)$data['banner_meta']['fallback_reason'] : '';
			$fallback_suggested = !empty($data['banner_meta']['fallback_suggested']) ? 1 : 0;
			$partner = array(
				'code' => isset($data['link_code']) ? $data['link_code'] : '',
				'banner1' => isset($data['banner1']['code']) ? $data['banner1']['code'] : '',
				'banner1_url' => isset($data['banner1']['url']) ? $data['banner1']['url'] : '',
				'banner_lang' => $banner_lang,
				'match_level' => $match_level,
				'fallback_reason' => $fallback_reason,
				'fallback_suggested' => $fallback_suggested,
				'banner2' => isset($data['banner2']['code']) ? $data['banner2']['code'] : '',
				'html' => isset($data['banner2']['html']) ? $data['banner2']['html'] : '',
			);
			aviator_ad_partner_banner_proxy($partner);
			$by_country[$country] = array('ts' => $now, 'data' => $partner);
			$write_cache(array('last_success' => $partner, 'by_country' => $by_country));
			break;
		}
	}
	if ($partner === null && $last_success !== null) {
		$partner = $last_success;
		aviator_ad_partner_banner_proxy($partner);
		if ($collect_debug) {
			$api_debug['used_last_success'] = true;
			$api_debug['partner'] = $partner;
		}
	} elseif ($collect_debug && $partner !== null) {
		$api_debug['partner'] = $partner;
	}
	return $partner;
}

/** TTL for banner cache: re-check remote (by hash) when file is older than this (seconds). */
if (!defined('AVIATOR_AD_BANNER_CACHE_TTL')) {
	define('AVIATOR_AD_BANNER_CACHE_TTL', 3600);
}

/**
 * Fetch image from backend URL, cache under data/banner-cache/{id}, return our proxy URL.
 * Same logic as agent sites: store content hash (.hash); when cache is stale, re-fetch and compare hash —
 * if unchanged keep cache (just touch), if changed save new image.
 * Frontend never sees backend domain. On fetch failure still returns proxy URL (image may 404).
 */
function aviator_ad_banner_proxy_url($image_url) {
	if (!is_string($image_url) || $image_url === '') return '';
	if (!preg_match('#^https?://#i', $image_url)) return $image_url;
	// Already our proxy URL — return as-is
	if (strpos($image_url, 'banner-img.php') !== false) return $image_url;

	$cache_dir = defined('ROOT_DIR') ? (rtrim(ROOT_DIR, '/') . '/data/banner-cache') : (dirname(__DIR__) . '/data/banner-cache');
	$id = md5($image_url);
	$file = $cache_dir . '/' . $id;
	$ct_file = $file . '.ct';
	$hash_file = $file . '.hash';
	$ttl = defined('AVIATOR_AD_BANNER_CACHE_TTL') ? (int)AVIATOR_AD_BANNER_CACHE_TTL : 3600;

	$need_fetch = false;
	if (!is_file($file) || !is_readable($file)) {
		$need_fetch = true;
	} elseif ($ttl > 0 && (time() - filemtime($file)) >= $ttl) {
		// Cache stale: re-check remote by hash (like agent sites)
		$ctx = stream_context_create(array(
			'http' => array('timeout' => 10),
			'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
		));
		$body = @file_get_contents($image_url, false, $ctx);
		if ($body !== false && strlen($body) > 0) {
			$new_hash = md5($body);
			$old_hash = (is_file($hash_file) && is_readable($hash_file)) ? trim((string)file_get_contents($hash_file)) : '';
			if ($old_hash !== '' && $new_hash === $old_hash) {
				// Image unchanged — keep cache, refresh mtime
				@touch($file);
				@touch($hash_file);
			} else {
				// Image changed or no hash yet — save new
				$ct = 'image/jpeg';
				if (isset($http_response_header) && is_array($http_response_header)) {
					foreach ($http_response_header as $h) {
						if (preg_match('/^Content-Type:\s*([^\s;]+)/i', $h, $m)) {
							$ct = trim($m[1]);
							break;
						}
					}
				}
				if (is_dir($cache_dir) && is_writable($cache_dir)) {
					@file_put_contents($file, $body);
					@file_put_contents($ct_file, $ct);
					@file_put_contents($hash_file, $new_hash);
				}
			}
		}
		$need_fetch = false; // we already handled (fetch + compare or touch)
	}

	if ($need_fetch && (!is_dir($cache_dir) || is_writable($cache_dir))) {
		if (!is_dir($cache_dir)) @mkdir($cache_dir, 0755, true);
		$ctx = stream_context_create(array(
			'http' => array('timeout' => 10),
			'ssl'  => array('verify_peer' => false, 'verify_peer_name' => false),
		));
		$body = @file_get_contents($image_url, false, $ctx);
		if ($body !== false && strlen($body) > 0) {
			$ct = 'image/jpeg';
			if (isset($http_response_header) && is_array($http_response_header)) {
				foreach ($http_response_header as $h) {
					if (preg_match('/^Content-Type:\s*([^\s;]+)/i', $h, $m)) {
						$ct = trim($m[1]);
						break;
					}
				}
			}
			@file_put_contents($file, $body);
			@file_put_contents($ct_file, $ct);
			@file_put_contents($hash_file, md5($body));
		}
	}

	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if ($host === '') return $image_url;
	return $scheme . '://' . $host . '/banner-img.php?id=' . $id;
}

/**
 * Replace all img src="https?://..." in HTML with our proxy URLs so backend is never exposed.
 */
function aviator_ad_replace_html_banner_urls($html) {
	if (!is_string($html) || $html === '') return $html;
	// Remove agent-specific close icon that references /promo/close.svg (no such asset on Aviator).
	$html = preg_replace('#<img[^>]+src=(["\'])/promo/close\.svg\1[^>]*>#i', '', $html);
	return preg_replace_callback(
		'#<img\s+([^>]*?)src=(["\'])(https?://[^"\']+)\2#i',
		function ($m) {
			$proxy = aviator_ad_banner_proxy_url($m[3]);
			return '<img ' . $m[1] . 'src=' . $m[2] . $proxy . $m[2];
		},
		$html
	);
}

/**
 * Replace partner banner1_url and banner2 html image URLs with our proxy so backend URL is never exposed.
 */
function aviator_ad_partner_banner_proxy(&$partner) {
	if (!is_array($partner)) return;
	if (!empty($partner['banner1_url'])) {
		$url = $partner['banner1_url'];
		if (preg_match('#^https?://#i', $url)) {
			$partner['banner1_url'] = aviator_ad_banner_proxy_url($url);
		}
	}
	if (!empty($partner['html'])) {
		$partner['html'] = aviator_ad_replace_html_banner_urls($partner['html']);
	}
}

/**
 * External / special href values that must not be rewritten to the offer tracker.
 */
function aviator_ad_preserve_content_link_href($href) {
	$href = trim((string) $href);
	if ($href !== '' && $href[0] === '#' && $href !== '#') {
		return true;
	}
	if (preg_match('#^\s*javascript:#i', $href)) {
		return true;
	}
	$normalized = html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	if (preg_match('~^(?:https?://[^/]+)?/[a-z]{2}/demo/app/?(?:[?#].*)?$~iu', $normalized)) {
		return true;
	}
	if (preg_match('~^(?:https?://[^/]+)?/files/[^?\#\s]+\.apk(?:[?#].*)?$~i', $normalized)) {
		return true;
	}
	if (preg_match('~^mailto:~i', $normalized) || preg_match('~^tel:~i', $normalized)) {
		return true;
	}
	$preserve_hosts = array(
		'begambleaware.org',
		'www.begambleaware.org',
		'gamcare.org.uk',
		'www.gamcare.org.uk',
		'gamblingtherapy.org',
		'www.gamblingtherapy.org',
		'ncpgambling.org',
		'www.ncpgambling.org',
	);
	if (preg_match('~^https?://([^/?#]+)~i', $normalized, $hm)) {
		$host = strtolower((string) $hm[1]);
		if (in_array($host, $preserve_hosts, true)) {
			return true;
		}
	}
	return false;
}

/**
 * Replace all content links (a[href] not anchor-only) with offer path. Used in page content and index text.
 * Closed <noads>...</noads> blocks keep original hrefs; unclosed <noads> is ignored.
 */
function aviator_ad_replace_content_links($html, $offer_path) {
	if ($offer_path === '' || $html === '') {
		return $html;
	}
	require_once (defined('ROOT_DIR') ? ROOT_DIR : dirname(__FILE__) . '/') . 'functions/content_exclude_tags.php';
	list($masked, $protected) = content_exclude_extract_blocks($html, array('noads'));

	$offer_path_esc = htmlspecialchars($offer_path, ENT_QUOTES, 'UTF-8');
	$masked = preg_replace_callback(
		'/<a\s([^>]*?)href=(["\'])([^"\']*)\2([^>]*)>/i',
		function ($m) use ($offer_path_esc) {
			$href = trim((string) $m[3]);
			if (aviator_ad_preserve_content_link_href($href)) {
				return $m[0];
			}
			return '<a ' . $m[1] . 'href="' . $offer_path_esc . '"' . $m[4] . '>';
		},
		$masked
	);

	return content_exclude_restore_blocks($masked, $protected);
}
