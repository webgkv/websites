<?php
/**
 * Universal job runner entry point (runs one job).
 * Library functions live in job_runner_lib.php.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}
require_once __DIR__ . '/job_runner_lib.php';

$result = process_one_admin_job();
if (php_sapi_name() !== 'cli') header('Content-Type: text/plain; charset=utf-8');
echo ($result['processed'] ? ($result['ok'] ? "OK: " : "FAIL: ") : "") . $result['message'] . "\n";
exit(($result['processed'] && !$result['ok']) ? 1 : 0);

