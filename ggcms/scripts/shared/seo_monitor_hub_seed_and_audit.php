<?php
/**
 * CLI: persist SEO Monitor hub rules (variables) + run the same payload as GET /api/telemetry_page_seo.
 *
 * Usage (from repo root):
 *   php scripts/seo_monitor_hub_seed_and_audit.php
 *   php scripts/seo_monitor_hub_seed_and_audit.php --fetch=0
 *   php scripts/seo_monitor_hub_seed_and_audit.php --curl-base=https://example.com
 *   php scripts/seo_monitor_hub_seed_and_audit.php --remote-audit-only --curl-base=https://example.com
 *
 * Token: env TELEMETRY_TOKEN, else first non-comment line in scripts/telemetry_token.local.txt (never printed).
 */
$root = dirname(__DIR__);
define('ROOT_DIR', $root . '/site/');

$fetch = '1';
$curl_base = '';
$remote_audit_only = false;
$extra_paths = array();
$argv_rest = array_slice($argv, 1);
foreach ($argv_rest as $a) {
	if ($a === '--remote-audit-only') {
		$remote_audit_only = true;
	} elseif (preg_match('#^--fetch=(0|1)$#', $a, $m)) {
		$fetch = $m[1];
	} elseif (preg_match('#^--curl-base=(.+)$#', $a, $m)) {
		$curl_base = rtrim($m[1], '/');
	} elseif (preg_match('#^--path=(.+)$#', $a, $m)) {
		$p = '/' . trim(preg_replace('#/+#', '/', '/' . trim($m[1], '/')), '/') . '/';
		$extra_paths[] = $p;
	}
}

/**
 * @return string
 */
function seo_monitor_audit_read_token($repo_root) {
	$t = getenv('TELEMETRY_TOKEN');
	if (is_string($t) && trim($t) !== '') {
		return trim($t);
	}
	$path = $repo_root . '/scripts/telemetry_token.local.txt';
	if (!is_readable($path)) {
		return '';
	}
	foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
		$line = trim((string)$line);
		if ($line === '' || $line[0] === '#') {
			continue;
		}
		return $line;
	}
	return '';
}

/**
 * @return list<array{path:string,http:int,ok:bool,hub_page:?bool,issue_codes:array,issue_count:int,message?:string}>
 */
function seo_monitor_audit_remote_paths($curl_base, $token, $paths, $fetch) {
	$out = array();
	foreach ($paths as $path) {
		$path = (string)$path;
		if ($path === '' || $path[0] !== '/') {
			continue;
		}
		$origin = rtrim($curl_base, '/') . '/';
		$url = $origin . ltrim(preg_replace('#^/+#', '/', $path), '/');
		if (substr($url, -1) !== '/') {
			$url .= '/';
		}
		$api = rtrim($curl_base, '/') . '/api/telemetry_page_seo?fetch=' . rawurlencode((string)$fetch) . '&normalize=0&url=' . rawurlencode($url);
		$ch = curl_init($api);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_TIMEOUT => 28,
			CURLOPT_HTTPHEADER => array(
				'X-Telemetry-Token: ' . $token,
				'Accept: application/json',
			),
		));
		$body = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$j = @json_decode((string)$body, true);
		$row = array(
			'path' => $path,
			'http' => $code,
			'ok' => is_array($j) && !empty($j['ok']),
			'hub_page' => null,
			'issue_codes' => array(),
			'issue_count' => 0,
		);
		if (is_array($j)) {
			$row['hub_page'] = isset($j['hub_page']) ? (bool)$j['hub_page'] : null;
			$row['issue_codes'] = isset($j['seo_monitor_issue_summary']['issue_codes']) && is_array($j['seo_monitor_issue_summary']['issue_codes'])
				? $j['seo_monitor_issue_summary']['issue_codes']
				: array();
			$row['issue_count'] = isset($j['seo_monitor_issue_summary']['issue_count']) ? (int)$j['seo_monitor_issue_summary']['issue_count'] : 0;
			if (empty($j['ok']) && isset($j['message'])) {
				$row['message'] = (string)$j['message'];
			}
			if (empty($j['ok']) && isset($j['error'])) {
				$row['message'] = (string)$j['error'] . (isset($row['message']) ? (': ' . $row['message']) : '');
			}
		}
		$out[] = $row;
	}
	return $out;
}

if ($remote_audit_only) {
	if ($curl_base === '') {
		fwrite(STDERR, "Use --curl-base=https://your-host with --remote-audit-only\n");
		exit(1);
	}
	$token = seo_monitor_audit_read_token($root);
	if ($token === '') {
		fwrite(STDERR, "Set TELEMETRY_TOKEN or add token line to scripts/telemetry_token.local.txt\n");
		exit(1);
	}
	$paths = array_unique(array_merge(
		array('/en/', '/en/casinos/', '/en/games/', '/en/guides/', '/en/blog/'),
		$extra_paths
	));
	$rep = seo_monitor_audit_remote_paths($curl_base, $token, $paths, $fetch);
	echo json_encode(array('mode' => 'remote_audit_only', 'paths' => $rep), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
	exit(0);
}

if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'cli.local';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
if (!isset($_SERVER['SERVER_NAME'])) {
	$_SERVER['SERVER_NAME'] = 'cli.local';
}
if (!isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = '/';
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/seo_monitor.php';
require_once ROOT_DIR . 'functions/site_telemetry_page_seo.php';

if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') <= 0) {
	fwrite(STDERR, "variables table missing; cannot save hub settings.\n");
	exit(1);
}

$hub_payload = array(
	'page_slugs' => array('casinos', 'games', 'guides'),
	'blog_listing_module' => true,
	'page_ids_extra' => array(),
);

if (!seo_monitor_hub_settings_save($hub_payload)) {
	fwrite(STDERR, "seo_monitor_hub_settings_save failed.\n");
	exit(1);
}

$cfg = seo_monitor_hub_settings_load(true);
echo "Hub settings saved / loaded:\n";
echo json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

// --- Inventory: pages rows vs hub flag (body_empty exemption)
$rows = mysql_select("SELECT id, module, url, CHAR_LENGTH(IFNULL(`text`,'')) AS text_len FROM pages WHERE display=1 ORDER BY id ASC", 'rows');
if (!$rows) {
	$rows = array();
}
echo "pages (display=1) hub classification:\n";
echo str_pad('id', 6) . str_pad('module', 12) . str_pad('url', 20) . str_pad('text_len', 10) . "hub\n";
foreach ($rows as $r) {
	$row = array('id' => (int)$r['id'], 'module' => (string)$r['module'], 'url' => (string)$r['url']);
	$hub = seo_monitor_is_hub_pages_row($row) ? 'yes' : 'no';
	echo str_pad((string)$r['id'], 6) . str_pad((string)$r['module'], 12) . str_pad((string)$r['url'], 20) . str_pad((string)$r['text_len'], 10) . $hub . "\n";
}
echo "\nIf a listing row shows hub=no but should skip body_empty, add its pages.id to Extra page IDs or its url to URL slugs (module=pages), or fix module (casinos/games/guides/blog).\n\n";

// --- Same analysis as telemetry_page_seo (per URL)
$lang = mysql_select("SELECT id, url FROM languages WHERE display=1 ORDER BY `rank` DESC LIMIT 1", 'row');
$lang_seg = $lang && trim((string)$lang['url'], '/') !== '' ? trim((string)$lang['url'], '/') : 'en';
$origin = site_telemetry_page_seo_public_origin();

$paths = array(
	'/' . $lang_seg . '/',
	'/' . $lang_seg . '/casinos/',
	'/' . $lang_seg . '/games/',
	'/' . $lang_seg . '/guides/',
	'/' . $lang_seg . '/blog/',
);

$reports = array();
foreach ($paths as $path) {
	$url = site_telemetry_page_seo_origin_url_for_path($path);
	$rep = site_telemetry_page_seo_collect(array(
		'url' => $url,
		'fetch' => $fetch,
		'normalize' => '0',
	));
	$reports[] = array(
		'path' => $path,
		'fetch_url' => isset($rep['fetch_url']) ? $rep['fetch_url'] : $url,
		'hub_page' => isset($rep['hub_page']) ? $rep['hub_page'] : null,
		'issue_codes' => isset($rep['seo_monitor_issue_summary']['issue_codes']) ? $rep['seo_monitor_issue_summary']['issue_codes'] : array(),
		'issue_count' => isset($rep['seo_monitor_issue_summary']['issue_count']) ? $rep['seo_monitor_issue_summary']['issue_count'] : 0,
		'http_code' => isset($rep['rendered']['http_code']) ? $rep['rendered']['http_code'] : null,
		'ok' => !empty($rep['ok']),
	);
}

echo "telemetry_page_seo-equivalent audit (fetch={$fetch}, first display language segment: {$lang_seg}):\n";
echo json_encode($reports, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

$token = seo_monitor_audit_read_token($root);
if ($curl_base !== '') {
	if ($token === '') {
		fwrite(STDERR, "Skip HTTP API mirror: set TELEMETRY_TOKEN or scripts/telemetry_token.local.txt for --curl-base.\n");
	} else {
		echo "\nHTTP GET /api/telemetry_page_seo (same paths, prod mirror):\n";
		$curl_paths = array();
		foreach ($paths as $path) {
			$curl_paths[] = $path;
		}
		foreach (seo_monitor_audit_remote_paths($curl_base, $token, $curl_paths, $fetch) as $line) {
			echo json_encode($line, JSON_UNESCAPED_UNICODE) . "\n";
		}
	}
}
