<?php
/**
 * CLI: 301 redirects from legacy fansport-15-free-spins slug to fansport-free-spins (all langs).
 * Usage: php scripts/promo_fansport_slug_redirect.php
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

define('ROOT_DIR', dirname(__DIR__) . '/');
foreach (array('HTTP_HOST', 'REMOTE_ADDR', 'SERVER_ADDR', 'SERVER_NAME', 'REQUEST_URI') as $k) {
	if (!isset($_SERVER[$k])) {
		$_SERVER[$k] = ($k === 'HTTP_HOST') ? 'localhost' : '127.0.0.1';
	}
}
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';

$old = 'fansport-15-free-spins';
$new = 'fansport-free-spins';

if (@mysql_select("SHOW TABLES LIKE 'redirects'", 'num_rows') <= 0) {
	echo "no redirects table\n";
	exit(0);
}

$langs = mysql_select("SELECT url FROM languages WHERE display=1 ORDER BY id", 'rows', 0);
if (!$langs) {
	$langs = array(array('url' => 'en'));
}

$added = 0;
foreach ($langs as $l) {
	$lu = trim((string)($l['url'] ?? ''), '/');
	if ($lu === '') {
		continue;
	}
	$old_path = '/' . $lu . '/promo/' . $old . '/';
	$new_path = '/' . $lu . '/promo/' . $new . '/';
	$exists = mysql_select("SELECT id FROM redirects WHERE old_url='" . mysql_res($old_path) . "' LIMIT 1", 'row', 0);
	if ($exists) {
		continue;
	}
	mysql_fn('insert', 'redirects', array('old_url' => $old_path, 'new_url' => $new_path));
	$added++;
}

echo "redirects added: {$added}\n";
