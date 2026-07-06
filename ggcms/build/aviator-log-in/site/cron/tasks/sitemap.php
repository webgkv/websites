<?php
/**
 * Rebuild common sitemap cache (see api/sitemap/common.xml.php).
 */
$_GET['rebuild'] = 1;
require_once ROOT_DIR . 'functions/common_func.php';
require ROOT_DIR . 'api/sitemap/common.xml.php';
if (defined('CRON_SCHEDULE_TICK') && CRON_SCHEDULE_TICK) {
	echo date('c') . " sitemap: cache task done\n";
}
