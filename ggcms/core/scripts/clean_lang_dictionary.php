<?php
/**
 * One-time script: remove LiveInternet counter and "copiright abc-cms.com" from
 * languages.dictionary (txt_footer) in the database.
 * Run: php site/scripts/clean_lang_dictionary.php
 */

define('ROOT_DIR', dirname(__DIR__) . '/');
if (php_sapi_name() !== 'cli') {
	die('Run from command line: php site/scripts/clean_lang_dictionary.php');
}

require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');

$rows = mysql_select("SELECT id, name, dictionary FROM languages", 'rows');
if (!$rows) {
	echo "No languages or DB error.\n";
	exit(1);
}

foreach ($rows as $row) {
	$dict = @unserialize($row['dictionary']);
	if (!is_array($dict)) {
		echo "Language id={$row['id']} ({$row['name']}): dictionary not serialized, skip.\n";
		continue;
	}
	$changed = false;
	if (isset($dict['txt_footer']) && (strpos($dict['txt_footer'], 'LiveInternet') !== false || strpos($dict['txt_footer'], 'abc-cms.com') !== false)) {
		$dict['txt_footer'] = '';
		$changed = true;
	}
	if ($changed) {
		$new_dict = serialize($dict);
		mysql_fn('query', "UPDATE languages SET dictionary = '" . mysql_res($new_dict) . "' WHERE id = " . (int)$row['id']);
		echo "Language id={$row['id']} ({$row['name']}): txt_footer cleared.\n";
	} else {
		echo "Language id={$row['id']} ({$row['name']}): nothing to clean.\n";
	}
}
echo "Done.\n";
