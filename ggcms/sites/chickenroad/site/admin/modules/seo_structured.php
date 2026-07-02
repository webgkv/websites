<?php

/**
 * SEO Structured Data settings (canonical, breadcrumbs, FAQ) with JSON presets.
 *
 * Stores configuration in `variables` table under key `seo_structured`.
 * Frontend reads it via site/functions/data_func.php and renders JSON-LD in templates/_template.php.
 */

$page_name = 'SEO: Structured data';

$variables_exists = @mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0;
$seo = array(
	'canonical_base' => '',
	'site_name' => '',
	'breadcrumbs' => array(
		'home_label' => 'Home',
		'use_site_tree' => 0,
	),
	'faq' => array(), // array of [ ['q' => '', 'a' => ''], ... ]
);
$seo_error = '';
$seo_saved = false;

// Load existing config
if ($variables_exists) {
	$row = mysql_select("SELECT value FROM `variables` WHERE `key` = 'seo_structured' LIMIT 1", 'row');
	if ($row && $row['value'] !== '') {
		$dec = json_decode($row['value'], true);
		if (is_array($dec)) {
			$seo = array_merge($seo, $dec);
			if (isset($dec['breadcrumbs']) && is_array($dec['breadcrumbs'])) {
				$seo['breadcrumbs'] = array_merge($seo['breadcrumbs'], $dec['breadcrumbs']);
			}
			if (isset($dec['faq']) && is_array($dec['faq'])) {
				$seo['faq'] = $dec['faq'];
			}
		}
	}
}

// Export preset as JSON
if (isset($get['u']) && $get['u'] === 'export') {
	if (!$variables_exists) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('error' => 'variables table does not exist'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$payload = $seo;
	header('Content-Type: application/json; charset=utf-8');
	header('Content-Disposition: attachment; filename="seo-structured-' . date('Y-m-d-His') . '.json"');
	echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	exit;
}

// Import preset from JSON textarea or uploaded file
if ($variables_exists && !empty($_POST['seo_structured_import'])) {
	$json = '';
	// Prefer uploaded file if present
	if (isset($_FILES['seo_preset_file']) && is_array($_FILES['seo_preset_file']) && $_FILES['seo_preset_file']['error'] === UPLOAD_ERR_OK) {
		$raw = @file_get_contents($_FILES['seo_preset_file']['tmp_name']);
		if ($raw !== false) $json = trim((string)$raw);
	}
	// Fallback to textarea
	if ($json === '' && isset($_POST['seo_preset_json'])) {
		$json = trim((string)$_POST['seo_preset_json']);
	}

	if ($json === '') {
		$seo_error = 'Preset JSON is empty (no file and no text).';
	} else {
		$dec = json_decode($json, true);
		if (!is_array($dec)) {
			$seo_error = 'Preset JSON is invalid (cannot decode).';
		} else {
			// Merge into default structure to avoid missing keys
			$new = $seo;
			if (isset($dec['canonical_base'])) $new['canonical_base'] = (string)$dec['canonical_base'];
			if (isset($dec['site_name'])) $new['site_name'] = (string)$dec['site_name'];
			if (isset($dec['breadcrumbs']) && is_array($dec['breadcrumbs'])) {
				$new['breadcrumbs'] = array_merge($new['breadcrumbs'], $dec['breadcrumbs']);
			}
			if (isset($dec['faq']) && is_array($dec['faq'])) {
				$new['faq'] = $dec['faq'];
			}
			$seo = $new;
			$payload = json_encode($seo, JSON_UNESCAPED_UNICODE);
			$exists = mysql_select("SELECT id, value FROM `variables` WHERE `key` = 'seo_structured' LIMIT 1", 'row');
			if ($exists && !empty($exists['id'])) {
				mysql_fn('update', 'variables', array('id' => $exists['id'], 'value' => $payload));
			} else {
				mysql_fn('insert', 'variables', array('key' => 'seo_structured', 'value' => $payload));
			}
			header('Location: /admin.php?m=seo_structured&imported=1');
			exit;
		}
	}
}

// Save settings (canonical, breadcrumbs, FAQ)
if ($variables_exists && !empty($_POST['seo_structured_save'])) {
	$canonical_base = isset($_POST['seo_canonical_base']) ? trim((string)$_POST['seo_canonical_base']) : '';
	$site_name = isset($_POST['seo_site_name']) ? trim((string)$_POST['seo_site_name']) : '';
	$home_label = isset($_POST['seo_breadcrumb_home']) ? trim((string)$_POST['seo_breadcrumb_home']) : 'Home';
	$use_tree = !empty($_POST['seo_breadcrumb_use_tree']) ? 1 : 0;
	$faq = array();
	if (isset($_POST['seo_faq_q']) && is_array($_POST['seo_faq_q']) && isset($_POST['seo_faq_a']) && is_array($_POST['seo_faq_a'])) {
		foreach ($_POST['seo_faq_q'] as $idx => $q) {
			$q = trim((string)$q);
			$a = isset($_POST['seo_faq_a'][$idx]) ? trim((string)$_POST['seo_faq_a'][$idx]) : '';
			if ($q === '' || $a === '') continue;
			$faq[] = array('q' => $q, 'a' => $a);
		}
	}
	$seo['canonical_base'] = $canonical_base;
	$seo['site_name'] = $site_name;
	$seo['breadcrumbs']['home_label'] = $home_label;
	$seo['breadcrumbs']['use_site_tree'] = $use_tree;
	$seo['faq'] = $faq;

	$payload = json_encode($seo, JSON_UNESCAPED_UNICODE);
	$exists = mysql_select("SELECT id, value FROM `variables` WHERE `key` = 'seo_structured' LIMIT 1", 'row');
	if ($exists && !empty($exists['id'])) {
		mysql_fn('update', 'variables', array('id' => $exists['id'], 'value' => $payload));
	} else {
		mysql_fn('insert', 'variables', array('key' => 'seo_structured', 'value' => $payload));
	}
	$seo_saved = true;
	header('Location: /admin.php?m=seo_structured&saved=1');
	exit;
}

// Render form
$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">' . htmlspecialchars($page_name) . '</h5>';

if (!$variables_exists) {
	$content .= '<div class="alert alert-warning mb-0">Table <code>variables</code> is required. Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank" rel="noopener">run_migrate_BD.php</a>.</div>';
	$content .= '</div>';
} else {
	if (!empty($get['saved'])) {
		$content .= '<div class="alert alert-success py-2 mb-3">Saved.</div>';
	}
	if (!empty($get['imported'])) {
		$content .= '<div class="alert alert-success py-2 mb-3">Preset imported.</div>';
	}
	if ($seo_error !== '') {
		$content .= '<div class="alert alert-warning py-2 mb-3">' . htmlspecialchars($seo_error) . '</div>';
	}

	// —— Preset import / export ——
	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Preset (JSON)</strong></div><div class="card-body">';
	$content .= '<a href="/admin.php?m=seo_structured&amp;u=export" class="btn btn-outline-secondary btn-sm mb-3">Download preset</a>';
	$content .= '<form method="post" action="/admin.php?m=seo_structured" enctype="multipart/form-data">';
	$content .= '<input type="hidden" name="seo_structured_import" value="1" />';
	$content .= '<div class="form-group"><label class="form-label" for="seo_preset_file">Import from file</label>';
	$content .= '<input type="file" name="seo_preset_file" id="seo_preset_file" accept="application/json,.json" class="form-control-file" /></div>';
	$content .= '<div class="form-group"><label class="form-label" for="seo_preset_json">Or paste JSON</label>';
	$content .= '<textarea class="form-control" name="seo_preset_json" id="seo_preset_json" rows="4" placeholder="Paste exported preset JSON"></textarea></div>';
	$content .= '<button type="submit" class="btn btn-outline-primary btn-sm">Import</button>';
	$content .= '</form></div></div>';

	// —— Main settings ——
	$content .= '<form method="post" action="/admin.php?m=seo_structured">';
	$content .= '<input type="hidden" name="seo_structured_save" value="1" />';

	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Canonical &amp; branding</strong></div><div class="card-body">';
	$content .= '<div class="form-row">';
	$content .= '<div class="form-group col-md-6"><label class="form-label" for="seo_canonical_base">Canonical base URL</label>';
	$content .= '<input type="text" class="form-control" name="seo_canonical_base" id="seo_canonical_base" value="' . htmlspecialchars($seo['canonical_base']) . '" placeholder="https://chickenroad.run" /></div>';
	$content .= '<p class="small text-muted col-12">Must match this site’s public host (not a legacy Aviator domain). Preset: <code>site/files/reference/seo_structured-chickenroad-preset.json</code></p>';
	$content .= '<div class="form-group col-md-6"><label class="form-label" for="seo_site_name">Site name</label>';
	$content .= '<input type="text" class="form-control" name="seo_site_name" id="seo_site_name" value="' . htmlspecialchars($seo['site_name']) . '" placeholder="Chicken Road" /></div>';
	$content .= '</div></div></div>';

	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Breadcrumbs</strong></div><div class="card-body">';
	$content .= '<div class="form-row align-items-end">';
	$content .= '<div class="form-group col-md-6"><label class="form-label" for="seo_breadcrumb_home">Home crumb label</label>';
	$content .= '<input type="text" class="form-control" name="seo_breadcrumb_home" id="seo_breadcrumb_home" value="' . htmlspecialchars($seo['breadcrumbs']['home_label']) . '" placeholder="Home" /></div>';
	$checked = !empty($seo['breadcrumbs']['use_site_tree']) ? ' checked' : '';
	$content .= '<div class="form-group col-md-6"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="seo_breadcrumb_use_tree" id="seo_breadcrumb_use_tree" value="1"' . $checked . ' />';
	$content .= '<label class="form-check-label" for="seo_breadcrumb_use_tree">Use site tree for deeper crumbs when available</label></div></div>';
	$content .= '</div></div></div>';

	$max_faq = max(3, count($seo['faq']) + 1);
	$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Default FAQ</strong> <span class="badge badge-secondary align-middle">FAQPage JSON-LD</span></div><div class="card-body">';
	for ($i = 0; $i < $max_faq; $i++) {
		$q = isset($seo['faq'][$i]['q']) ? $seo['faq'][$i]['q'] : '';
		$a = isset($seo['faq'][$i]['a']) ? $seo['faq'][$i]['a'] : '';
		$content .= '<div class="form-row mb-3">';
		$content .= '<div class="form-group col-md-6 mb-md-0"><label class="form-label small text-muted" for="seo_faq_q_' . $i . '">Question ' . ($i + 1) . '</label>';
		$content .= '<input type="text" class="form-control" name="seo_faq_q[' . $i . ']" id="seo_faq_q_' . $i . '" value="' . htmlspecialchars($q) . '" placeholder="Question" /></div>';
		$content .= '<div class="form-group col-md-6 mb-0"><label class="form-label small text-muted" for="seo_faq_a_' . $i . '">Answer</label>';
		$content .= '<textarea class="form-control" name="seo_faq_a[' . $i . ']" id="seo_faq_a_' . $i . '" rows="2" placeholder="Answer">' . htmlspecialchars($a) . '</textarea></div>';
		$content .= '</div>';
	}
	$content .= '</div></div>';

	$content .= '<button type="submit" class="btn btn-primary">Save settings</button>';
	$content .= '</form>';

	$content .= '</div>';
}

