<?php
/**
 * Rebuild common sitemap cache (HTTP). Crontab: php cron/run.php tick (see Settings → Cron).
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/tasks/sitemap.php';
