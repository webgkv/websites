<?php

$seo_site_css_dir = ROOT_DIR . 'assets/css/';
$seo_site_css_max_bytes = 2 * 1024 * 1024;

if (!function_exists('seo_site_css_files')) {
	function seo_site_css_dir_path() {
		return ROOT_DIR . 'assets/css/';
	}

	function seo_site_css_files() {
		$dir = seo_site_css_dir_path();
		$files = array();
		if (!is_dir($dir)) {
			return $files;
		}
		foreach (glob($dir . '*.css') as $path) {
			if (is_file($path)) {
				$files[] = basename($path);
			}
		}
		$prio = array(
			'style.css' => 0,
			'responsive.css' => 1,
			'ad-banner.css' => 2,
			'custom-overrides.css' => 99,
		);
		usort($files, function ($a, $b) use ($prio) {
			$pa = isset($prio[$a]) ? $prio[$a] : 50;
			$pb = isset($prio[$b]) ? $prio[$b] : 50;
			if ($pa !== $pb) {
				return $pa < $pb ? -1 : 1;
			}
			return strcmp($a, $b);
		});
		return $files;
	}

	function seo_site_css_resolve($basename) {
		$basename = basename((string)$basename);
		if (!preg_match('/^[a-zA-Z0-9._-]+\.css$/', $basename)) {
			return null;
		}
		$path = seo_site_css_dir_path() . $basename;
		$dir = realpath(seo_site_css_dir_path());
		if ($dir === false) {
			return null;
		}
		if (is_file($path)) {
			$real = realpath($path);
			if ($real === false || strpos($real, $dir . DIRECTORY_SEPARATOR) !== 0) {
				return null;
			}
			return $real;
		}
		$parent = realpath(dirname($path));
		if ($parent === false || $parent !== $dir) {
			return null;
		}
		return $path;
	}

	function seo_site_css_read($path, $basename = '') {
		if (is_file($path)) {
			$text = @file_get_contents($path);
			return $text === false ? '' : (string)$text;
		}
		$label = $basename !== '' ? $basename : basename((string)$path);
		return "/* " . $label . " — editable in admin: SEO → Site CSS */\n";
	}

	function seo_site_css_validate($text) {
		$text = (string)$text;
		if (strlen($text) > 2 * 1024 * 1024) {
			return 'File is too large (max 2 MB).';
		}
		if (preg_match('/<\s*script\b/i', $text) || stripos($text, '</style>') !== false || strpos($text, '<?') !== false) {
			return 'Invalid CSS content.';
		}
		return '';
	}

	function seo_site_css_backup($path) {
		if (!is_file($path)) {
			return;
		}
		$dir = ROOT_DIR . 'files/backups';
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			return;
		}
		$base = preg_replace('/\.css$/', '', basename($path));
		$backup = $dir . '/' . $base . '_' . date('Y-m-d_H-i-s') . '.css';
		@copy($path, $backup);
	}

	function seo_site_css_write($path, $text) {
		$err = seo_site_css_validate($text);
		if ($err !== '') {
			return $err;
		}
		$dir = dirname($path);
		if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
			return 'Could not create CSS directory.';
		}
		seo_site_css_backup($path);
		$ok = @file_put_contents($path, $text, LOCK_EX);
		if ($ok === false) {
			return 'Write error.';
		}
		return '';
	}
}

$seo_site_css_list = seo_site_css_files();

if (isset($get['u']) && (string)$get['u'] === 'export') {
	$file = isset($get['file']) ? (string)$get['file'] : '';
	$path = seo_site_css_resolve($file);
	if ($path === null) {
		header('HTTP/1.1 404 Not Found');
		echo 'File not found.';
		exit;
	}
	$text = seo_site_css_read($path, $file);
	header('Content-Type: text/css; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . basename($file) . '"');
	header('Content-Length: ' . strlen($text));
	echo $text;
	exit;
}

if (isset($get['u']) && (string)$get['u'] === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
	$file = isset($get['file']) ? (string)$get['file'] : '';
	$path = seo_site_css_resolve($file);
	$import_error = 'File not found.';
	if ($path !== null) {
		$import_error = '';
		if (!isset($_FILES['css_file']) || (int)$_FILES['css_file']['error'] !== UPLOAD_ERR_OK) {
			$import_error = 'Please select a CSS file.';
		} else {
			$name = strtolower((string)$_FILES['css_file']['name']);
			if (!preg_match('/\.css$/', $name)) {
				$import_error = 'Only .css files are allowed.';
			} else {
				$raw = @file_get_contents($_FILES['css_file']['tmp_name']);
				if ($raw === false) {
					$import_error = 'Could not read uploaded file.';
				} else {
					$import_error = seo_site_css_write($path, $raw);
				}
			}
		}
	}
	if ($import_error === '') {
		$_SESSION['admin_flash_success'] = basename($file) . ' imported successfully.';
	} else {
		$_SESSION['admin_flash_error'] = $import_error;
	}
	header('Location: /admin.php?m=seo_custom_css#css-' . rawurlencode($file));
	exit;
}

if (isset($_POST['text']) && isset($_POST['css_file'])) {
	$file = (string)$_POST['css_file'];
	$path = seo_site_css_resolve($file);
	$err = $path === null ? 'File not found.' : seo_site_css_write($path, stripslashes_smart($_POST['text']));
	$data = array('error' => $err);
	echo '<textarea>' . json_encode($data, JSON_HEX_AMP) . '</textarea>';
	die();
}

$page_name = 'Site CSS';

$content = '';

if (!$seo_site_css_list) {
	$content .= '<div class="alert alert-warning">No CSS files found in assets/css/.</div>';
} else {
	foreach ($seo_site_css_list as $css_file) {
		$css_path = seo_site_css_resolve($css_file);
		$css_rel = 'assets/css/' . $css_file;
		$anchor = 'css-' . rawurlencode($css_file);
		$size = ($css_path && is_file($css_path)) ? (int)filesize($css_path) : 0;
		$text = seo_site_css_read($css_path ?: (seo_site_css_dir_path() . $css_file), $css_file);
		$height = ($css_file === 'style.css') ? 520 : 300;

		$content .= '<div class="card mb-3" id="' . htmlspecialchars($anchor, ENT_QUOTES, 'UTF-8') . '">';
		$content .= '<div class="card-header d-flex flex-wrap align-items-center justify-content-between py-2">';
		$content .= '<strong><code>' . htmlspecialchars($css_rel, ENT_QUOTES, 'UTF-8') . '</code></strong>';
		if ($size > 0) {
			$content .= '<span class="text-muted small ml-2">' . number_format($size / 1024, 1) . ' KB</span>';
		}
		$content .= '<div class="d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">';
		$content .= '<a href="/admin.php?m=seo_custom_css&amp;u=export&amp;file=' . rawurlencode($css_file) . '" class="btn btn-sm btn-outline-primary"><i data-feather="download" class="mr-1"></i>Export</a>';
		$content .= '<form method="post" action="/admin.php?m=seo_custom_css&amp;u=import&amp;file=' . rawurlencode($css_file) . '" enctype="multipart/form-data" class="d-inline-flex flex-wrap align-items-center gap-2 mb-0">';
		$content .= '<input type="file" name="css_file" accept=".css,text/css" class="form-control form-control-sm" style="max-width:200px" required />';
		$content .= '<button type="submit" class="btn btn-sm btn-outline-secondary"><i data-feather="upload" class="mr-1"></i>Import</button>';
		$content .= '</form>';
		$content .= '</div>';
		$content .= '</div>';
		$content .= '<div class="card-body">';
		$content .= '<form class="form" method="post" enctype="multipart/form-data" action="/admin.php?m=seo_custom_css&amp;u=edit">';
		$content .= '<input type="hidden" name="css_file" value="' . htmlspecialchars($css_file, ENT_QUOTES, 'UTF-8') . '" />';
		$content .= '<textarea name="text" class="form-control" style="height:' . (int)$height . 'px;font-family:monospace;font-size:13px">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</textarea>';
		$content .= '<div class="modal-footer px-0 pb-0" style="padding-top:12px">';
		$content .= '<button type="button" class="btn btn-primary mr-auto">' . htmlspecialchars(a18n('save'), ENT_QUOTES, 'UTF-8') . '</button>';
		$content .= '</div>';
		$content .= '</form>';
		$content .= '</div>';
		$content .= '</div>';
	}
}

$module['one_form'] = false;
