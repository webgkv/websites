#!/usr/bin/env php
<?php
/**
 * Emit the canonical seo_pages_34 full cluster JSON.
 * Source of truth: site/files/reference/seo-pages-34-full.json
 */
$path = dirname(__DIR__) . '/site/files/reference/seo-pages-34-full.json';
if (!is_file($path)) {
	fwrite(STDERR, "Missing source JSON: {$path}\n");
	exit(1);
}
$raw = file_get_contents($path);
if (!is_string($raw) || trim($raw) === '') {
	fwrite(STDERR, "Source JSON is empty: {$path}\n");
	exit(1);
}
$out = json_decode($raw, true);
if (!is_array($out)) {
	fwrite(STDERR, "Invalid JSON in {$path}\n");
	exit(1);
}
if (($out['schema'] ?? '') !== 'seo_cluster_v1') {
	fwrite(STDERR, "Unexpected schema in {$path}\n");
	exit(1);
}
if (($out['entity'] ?? '') !== 'pages' || (int)($out['entity_id'] ?? 0) !== 34) {
	fwrite(STDERR, "Unexpected entity in {$path}\n");
	exit(1);
}
if (($out['mode'] ?? '') !== 'full' || empty($out['locales']) || !is_array($out['locales'])) {
	fwrite(STDERR, "Unexpected mode/locales in {$path}\n");
	exit(1);
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
echo "\n";
