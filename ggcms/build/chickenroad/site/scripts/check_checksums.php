#!/usr/bin/env php
<?php
/**
 * Outputs a list of files in a directory and their MD5 checksums in JSON.
 * For comparing local copy and server after deploy.
 *
 * CLI:
 *   php site/scripts/check_checksums.php              # whole project
 *   php site/scripts/check_checksums.php assets       # assets folder only
 *
 * Browser:
 *   https://site.com/scripts/check_checksums.php
 *   https://site.com/scripts/check_checksums.php?dir=assets
 *   Only subdirectories of the project are allowed (no .. or paths outside).
 *
 * On production, restrict access to this file (.htaccess or place above DocumentRoot).
 */
$base = realpath(dirname(__DIR__));
if ($base === false) {
	$base = dirname(__DIR__);
}
$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    $root = isset($argv[1]) ? $argv[1] : $base;
    if ($root !== $base && !preg_match('#^/#', $root)) {
        $root = $base . '/' . trim($root, '/');
    }
} else {
    header('Content-Type: application/json; charset=utf-8');
    $dir = isset($_GET['dir']) ? trim($_GET['dir'], '/') : '';
    if ($dir !== '' && preg_match('#\.\.|/#', $dir)) {
        echo json_encode(array('error' => 'Invalid dir'));
        exit;
    }
    $root = $dir === '' ? $base : $base . '/' . $dir;
}

$root = realpath($root);
if (!$root || !is_dir($root)) {
    if ($isCli) {
        fwrite(STDERR, "Directory not found.\n");
        exit(1);
    }
    echo json_encode(array('error' => 'Directory not found', 'requested' => isset($dir) ? $dir : ''));
    exit;
}
// In browser allow only directories inside the project
if (!$isCli && strpos($root, $base) !== 0) {
    echo json_encode(array('error' => 'Access denied'));
    exit;
}

$skipDirs = array('.git', 'node_modules', '.idea', 'vendor');
$out = array(
    'root'   => $root,
    'time'   => date('c'),
    'files'  => array(),
);

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $rel = substr($path, strlen($root) + 1);
    $rel = str_replace('\\', '/', $rel);
    $skip = false;
    foreach (explode('/', $rel) as $part) {
        if (in_array($part, $skipDirs, true)) {
            $skip = true;
            break;
        }
    }
    if ($skip) {
        continue;
    }
    $out['files'][$rel] = md5_file($path);
}

$out['count'] = count($out['files']);
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
