<?php
/**
 * Legacy endpoint: served sitemap_full.xml when it existed.
 * Current builds use /api/sitemap/index_hub.xml + sitemap_{lang}_{NNN}.xml (cron/run.php sitemap_build).
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__FILE__) . '/../');
}

if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
	header('Content-Type: text/xml; charset=utf-8');
}

$file = ROOT_DIR . 'api/sitemap/sitemap_full.xml';

if (!is_file($file)) {
	if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';
		exit;
	}
	return;
}

if (!defined('CRON_SCHEDULE_TICK') || !CRON_SCHEDULE_TICK) {
	readfile($file);
	exit;
}
