#!/usr/bin/env php
<?php
/**
 * Generate Swahili (20) and Lingala (21) common.php dictionaries for all brands.
 *
 * Usage: php scripts/seed_addon_languages.php
 */
if (php_sapi_name() !== 'cli') {
	exit(1);
}

$root = dirname(__DIR__);
$ref = $root . '/files/reference';
$brands = array('chickenroad', 'ice-fish', 'aviator-log-in', 'powerballjackpot');
$locales = array(
	20 => $ref . '/locale_ui_sw.json',
	21 => $ref . '/locale_ui_ln.json',
);

foreach ($locales as $lang_id => $locale_file) {
	if (!is_file($locale_file)) {
		fwrite(STDERR, "Missing locale file: $locale_file\n");
		exit(1);
	}
	$ui = json_decode(file_get_contents($locale_file), true);
	if (!is_array($ui)) {
		fwrite(STDERR, "Invalid locale JSON: $locale_file\n");
		exit(1);
	}

	foreach ($brands as $brand) {
		$en_file = dirname($root) . "/sites/$brand/site/files/languages/1/dictionary/common.php";
		if (!is_file($en_file)) {
			fwrite(STDERR, "Missing EN dict: $en_file\n");
			exit(1);
		}
		$en = array();
		include $en_file;
		if (empty($lang['common']) || !is_array($lang['common'])) {
			fwrite(STDERR, "Invalid EN dict: $en_file\n");
			exit(1);
		}
		$brand_name = isset($lang['common']['sitename']) ? (string) $lang['common']['sitename'] : $brand;
		$out = array();
		foreach ($lang['common'] as $key => $value) {
			if ($key === 'sitename') {
				$out[$key] = $brand_name;
				continue;
			}
			if ($key === 'footer_copyright') {
				$out[$key] = $brand_name . ' © {year}. All rights reserved.';
				if ($lang_id === 20) {
					$out[$key] = $brand_name . ' © {year}. Haki zote zimehifadhiwa.';
				} elseif ($lang_id === 21) {
					$out[$key] = $brand_name . ' © {year}. Makoki nyonso ezali ya biso.';
				}
				continue;
			}
			if (isset($ui[$key])) {
				$out[$key] = str_replace('{brand}', $brand_name, (string) $ui[$key]);
				continue;
			}
			$out[$key] = str_replace('Chicken Road', $brand_name, str_replace(array('PowerBall Jackpot', 'Ice Fish', 'Aviator Log In'), $brand_name, (string) $value));
		}

		$dir = dirname($root) . "/sites/$brand/site/files/languages/$lang_id/dictionary";
		if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
			fwrite(STDERR, "Failed to create $dir\n");
			exit(1);
		}
		$target = $dir . '/common.php';
		$php = "<?php\n\$lang['common'] = array(\n";
		foreach ($out as $key => $value) {
			$php .= "\t'" . str_replace("'", "\\'", $key) . "' => '" . str_replace(array("\\", "'"), array("\\\\", "\\'"), $value) . "',\n";
		}
		$php .= ");\n";
		file_put_contents($target, $php);
		echo "Wrote $target\n";
	}
}

echo "Done.\n";
