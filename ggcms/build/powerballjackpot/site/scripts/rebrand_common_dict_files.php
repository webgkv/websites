<?php
/**
 * Rebrand language dictionary common.php files for PowerBall Jackpot.
 * CLI: php scripts/rebrand_common_dict_files.php
 */
if (php_sapi_name() !== 'cli') {
	die("CLI only\n");
}

$root = dirname(__DIR__) . '/';
define('ROOT_DIR', $root);
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/site_brand.php';
require_once ROOT_DIR . 'admin/modules/_i18n.php';

$paths = glob(ROOT_DIR . 'files/languages/*/dictionary/common.php') ?: array();
if (!$paths) {
	echo "No common.php dictionaries found.\n";
	exit(1);
}

$updated = 0;
foreach ($paths as $path) {
	$lang_id = (int) basename(dirname(dirname($path)));
	$dict = admin_load_common_dict($lang_id);
	if (!$dict) {
		echo "SKIP lang {$lang_id} — empty\n";
		continue;
	}
	$changed = false;
	foreach ($dict as $k => $v) {
		$new = site_brand_rebrand_text((string) $v);
		if ($new !== (string) $v) {
			$dict[$k] = $new;
			$changed = true;
		}
	}
	if (!$changed) {
		echo "OK lang {$lang_id} — no changes\n";
		continue;
	}
	$res = admin_save_common_dict($lang_id, $dict);
	if (empty($res['ok'])) {
		echo "FAIL lang {$lang_id}: " . ($res['message'] ?? 'unknown') . "\n";
		exit(1);
	}
	$updated++;
	echo "UPDATED lang {$lang_id}\n";
}

echo "Done. Updated {$updated} language file(s).\n";
