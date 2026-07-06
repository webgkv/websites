<?php
/**
 * Deterministic file manifest: relative path + sha256 + size + mtime.
 *
 * Browser:
 *   /scripts/run_manifest_hashes.php?run=1
 *   /scripts/run_manifest_hashes.php?run=1&format=json
 *   /scripts/run_manifest_hashes.php?run=1&ext=php,css,js
 *   /scripts/run_manifest_hashes.php?run=1&path=site&max_bytes=5242880
 *
 * CLI:
 *   php run_manifest_hashes.php --path=site --ext=php,css,js --format=txt
 */

// --- Bootstrap (ROOT_DIR = /site/) ---
$is_cli = (php_sapi_name() === 'cli');
if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

// ---- Params (GET or argv) ----
$p = array(
	'path' => '',
	'ext' => '',
	'format' => 'txt',
	'max_bytes' => '0',
);

if ($is_cli) {
	// Parse argv like --key=value
	global $argv;
	if (is_array($argv)) {
		foreach ($argv as $a) {
			if (!is_string($a)) continue;
			if (strpos($a, '--') !== 0) continue;
			$kv = explode('=', substr($a, 2), 2);
			$k = isset($kv[0]) ? trim((string)$kv[0]) : '';
			$v = isset($kv[1]) ? (string)$kv[1] : '';
			if ($k !== '' && array_key_exists($k, $p)) $p[$k] = $v;
		}
	}
} else {
	if (empty($_GET['run']) || (string)$_GET['run'] !== '1') {
		header('Content-Type: text/plain; charset=utf-8');
		echo "Run manifest is disabled by default.\n\nAdd ?run=1\n";
		exit;
	}
	foreach (array_keys($p) as $k) {
		if (isset($_GET[$k])) $p[$k] = (string)$_GET[$k];
	}
}

$format = strtolower(trim((string)$p['format']));
if (!in_array($format, array('txt', 'json'), true)) $format = 'txt';

$max_bytes = (int)$p['max_bytes'];
if ($max_bytes < 0) $max_bytes = 0;

// Restrict scanning to ROOT_DIR to avoid accidental leaks.
$base = realpath(ROOT_DIR);
if ($base === false) $base = ROOT_DIR;

$scan_rel = trim((string)$p['path']);
$scan_rel = str_replace("\0", '', $scan_rel);
$scan_rel = ltrim($scan_rel, '/');
$scan_abs = $scan_rel !== '' ? (ROOT_DIR . $scan_rel) : ROOT_DIR;
$scan_abs = realpath($scan_abs);
if ($scan_abs === false) {
	$out = array('ok' => false, 'message' => 'Path not found', 'path' => $scan_rel);
	if ($format === 'json') {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	} else {
		header('Content-Type: text/plain; charset=utf-8');
		echo "Error: path not found: " . $scan_rel . "\n";
	}
	exit(1);
}

// Ensure scan path is within base
if (strpos($scan_abs, $base) !== 0) {
	$out = array('ok' => false, 'message' => 'Refusing to scan outside ROOT_DIR', 'scan_abs' => $scan_abs, 'root' => $base);
	if ($format === 'json') {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	} else {
		header('Content-Type: text/plain; charset=utf-8');
		echo "Error: refusing to scan outside ROOT_DIR\n";
		echo "scan=" . $scan_abs . "\nroot=" . $base . "\n";
	}
	exit(1);
}

// Extensions filter
$exts = array();
if (trim((string)$p['ext']) !== '') {
	foreach (explode(',', (string)$p['ext']) as $e) {
		$e = strtolower(trim((string)$e));
		$e = ltrim($e, '.');
		if ($e !== '') $exts[$e] = true;
	}
}

// ---- Scan ----
$rows = array();
$count_files = 0;
$count_skipped = 0;
$bytes_total = 0;

$it = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($scan_abs, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $fileInfo) {
	/** @var SplFileInfo $fileInfo */
	if (!$fileInfo->isFile()) continue;
	$abs = $fileInfo->getPathname();

	// Skip git + cache + vendor-like dirs (common noisy paths)
	$rel = ltrim(str_replace($base, '', $abs), '/');
	if ($rel === '' || strpos($rel, '../') !== false) continue;
	if (preg_match('#(^|/)\.git(/|$)#', $rel)) continue;
	if (preg_match('#(^|/)(cache|logs|tmp)(/|$)#', $rel)) continue;
	if (preg_match('#(^|/)(node_modules|vendor)(/|$)#', $rel)) continue;

	$size = (int)$fileInfo->getSize();
	$bytes_total += $size;

	if ($max_bytes > 0 && $size > $max_bytes) {
		$count_skipped++;
		continue;
	}

	if (!empty($exts)) {
		$e = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
		if ($e === '' || !isset($exts[$e])) {
			$count_skipped++;
			continue;
		}
	}

	$hash = @hash_file('sha256', $abs);
	if (!is_string($hash) || $hash === '') {
		$count_skipped++;
		continue;
	}

	$mtime = @filemtime($abs);
	$rows[] = array(
		'path' => $rel,
		'sha256' => $hash,
		'size' => $size,
		'mtime' => $mtime ? (int)$mtime : null,
	);
	$count_files++;
}

// Deterministic ordering
usort($rows, function($a, $b) {
	return strcmp((string)$a['path'], (string)$b['path']);
});

// ---- Output ----
$meta = array(
	'ok' => true,
	'root' => $base,
	'scan' => $scan_abs,
	'path' => $scan_rel,
	'ext' => array_keys($exts),
	'max_bytes' => $max_bytes,
	'files' => $count_files,
	'skipped' => $count_skipped,
	'bytes_total' => $bytes_total,
	'generated_at' => date('c'),
);

if ($format === 'json') {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('meta' => $meta, 'rows' => $rows), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo "manifest_sha256 v1\n";
echo "generated_at: " . $meta['generated_at'] . "\n";
echo "root: " . $meta['root'] . "\n";
echo "scan: " . $meta['scan'] . "\n";
echo "filters: ext=" . (empty($meta['ext']) ? '*' : implode(',', $meta['ext'])) . " max_bytes=" . (int)$meta['max_bytes'] . "\n";
echo "files: " . (int)$meta['files'] . " skipped: " . (int)$meta['skipped'] . " bytes_total: " . (int)$meta['bytes_total'] . "\n";
echo "\n";
foreach ($rows as $r) {
	echo $r['sha256'] . "  " . $r['size'] . "  " . ($r['mtime'] !== null ? $r['mtime'] : '-') . "  " . $r['path'] . "\n";
}

