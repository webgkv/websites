<?php
/**
 * Run one job from admin_jobs (HTTP). Crontab: php cron/run.php tick (see Settings → Cron).
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/tasks/admin_jobs.php';
