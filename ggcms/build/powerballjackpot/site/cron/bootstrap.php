<?php
/**
 * Shared bootstrap for site/cron/tasks (ROOT_DIR = site/ root).
 */
if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}
@chdir(ROOT_DIR);
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
