<?php
/**
 * Raster normalize (resize + WebP) and disk checks for media library uploads.
 */

/** Normalize DB/media path (no leading slash). Safe to load before media_library.php. */
if (!function_exists('media_library_normalize_db_path')) {
	function media_library_normalize_db_path($value) {
		return ltrim(str_replace('\\', '/', (string)$value), '/');
	}
}

/**
 * @return array<string, array{max_width:int, quality:int}>
 */
function media_image_profiles() {
	return array(
		'content'    => array('max_width' => 1536, 'quality' => 82),
		'card'       => array('max_width' => 800, 'quality' => 82),
		'screenshot' => array('max_width' => 1200, 'quality' => 82),
		'thumb'      => array('max_width' => 200, 'quality' => 80),
	);
}

function media_image_profile_options($profile = 'content') {
	$profiles = media_image_profiles();
	return isset($profiles[$profile]) ? $profiles[$profile] : $profiles['content'];
}

function media_image_is_raster_extension($ext) {
	$ext = strtolower((string)$ext);
	return in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true);
}

function media_image_passthrough_extension($ext) {
	$ext = strtolower((string)$ext);
	return $ext === 'svg';
}

/**
 * Absolute path on disk for a site-relative file path.
 */
function media_library_disk_path($rel_path) {
	$rel_path = media_library_normalize_db_path($rel_path);
	if ($rel_path === '' || strpos($rel_path, '..') !== false) {
		return '';
	}
	return ROOT_DIR . $rel_path;
}

function media_library_file_exists($rel_path) {
	$abs = media_library_disk_path($rel_path);
	return $abs !== '' && is_file($abs);
}

/**
 * Pickable path only if pattern matches AND file exists (for files/media/ and other local paths).
 */
function media_library_resolve_pickable_path($value) {
	if (!function_exists('media_library_is_pickable_image_path')
		|| !media_library_is_pickable_image_path($value)
	) {
		return '';
	}
	$rel = media_library_normalize_db_path($value);
	if ($rel === '') {
		return '';
	}
	if (strpos($rel, 'files/media/') === 0) {
		return media_image_resolve_disk_media_path($rel);
	}
	if (media_library_file_exists($rel)) {
		return $rel;
	}
	return '';
}

/**
 * Convert raster image to WebP with max width (never upscale).
 *
 * @return array{ok:bool, message:string, path?:string}
 */
function media_image_convert_to_webp($abs_in, $abs_out, $max_width, $quality) {
	if (!is_file($abs_in)) {
		return array('ok' => false, 'message' => 'Source file missing');
	}
	$max_width = max(1, (int)$max_width);
	$quality = max(1, min(100, (int)$quality));

	$dir = dirname($abs_out);
	if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
		return array('ok' => false, 'message' => 'Cannot create output directory');
	}

	if (function_exists('exec')) {
		$convert = trim((string)@shell_exec('command -v convert 2>/dev/null'));
		if ($convert !== '') {
			$resize = escapeshellarg($max_width . 'x' . $max_width . '>');
			$cmd = $convert . ' ' . escapeshellarg($abs_in)
				. ' -auto-orient -strip -resize ' . $resize
				. ' -quality ' . (int)$quality
				. ' ' . escapeshellarg($abs_out) . ' 2>&1';
			exec($cmd, $out_lines, $code);
			if ($code === 0 && is_file($abs_out) && filesize($abs_out) > 0) {
				return array('ok' => true, 'message' => 'Converted', 'path' => $abs_out);
			}
		}
	}

	if (!function_exists('imagewebp')) {
		return array('ok' => false, 'message' => 'WebP conversion unavailable');
	}

	$info = @getimagesize($abs_in);
	if ($info === false) {
		return array('ok' => false, 'message' => 'Unreadable image');
	}
	$mime = isset($info['mime']) ? (string)$info['mime'] : '';
	$loader = '';
	switch ($mime) {
		case 'image/jpeg': $loader = 'imagecreatefromjpeg'; break;
		case 'image/png':  $loader = 'imagecreatefrompng'; break;
		case 'image/gif':  $loader = 'imagecreatefromgif'; break;
		case 'image/webp': $loader = 'imagecreatefromwebp'; break;
	}
	if ($loader === '' || !function_exists($loader)) {
		return array('ok' => false, 'message' => 'Unsupported image type');
	}
	$src = @$loader($abs_in);
	if (!$src) {
		return array('ok' => false, 'message' => 'Failed to load image');
	}
	$w = imagesx($src);
	$h = imagesy($src);
	if ($w <= 0 || $h <= 0) {
		imagedestroy($src);
		return array('ok' => false, 'message' => 'Invalid image dimensions');
	}
	$new_w = $w;
	$new_h = $h;
	if ($w > $max_width || $h > $max_width) {
		if ($w >= $h) {
			$new_w = $max_width;
			$new_h = (int)round($h * ($max_width / $w));
		} else {
			$new_h = $max_width;
			$new_w = (int)round($w * ($max_width / $h));
		}
	}
	$dst = imagecreatetruecolor($new_w, $new_h);
	imagealphablending($dst, false);
	imagesavealpha($dst, true);
	$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
	imagefill($dst, 0, 0, $transparent);
	imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
	$ok = imagewebp($dst, $abs_out, $quality);
	imagedestroy($src);
	imagedestroy($dst);
	if (!$ok || !is_file($abs_out)) {
		return array('ok' => false, 'message' => 'WebP write failed');
	}
	return array('ok' => true, 'message' => 'Converted', 'path' => $abs_out);
}

/**
 * Normalize uploaded/stored raster to WebP; SVG unchanged.
 *
 * @return array{ok:bool, message:string, abs?:string, rel?:string, ext?:string}
 */
function media_image_normalize_absolute($abs_path, $profile = 'content') {
	if (!is_file($abs_path)) {
		return array('ok' => false, 'message' => 'File not found');
	}
	$ext = strtolower(pathinfo($abs_path, PATHINFO_EXTENSION));
	if (media_image_passthrough_extension($ext)) {
		$rel = ltrim(str_replace('\\', '/', substr($abs_path, strlen(ROOT_DIR))), '/');
		return array('ok' => true, 'message' => 'Skipped SVG', 'abs' => $abs_path, 'rel' => $rel, 'ext' => $ext);
	}
	if (!media_image_is_raster_extension($ext)) {
		return array('ok' => false, 'message' => 'Unsupported file type');
	}

	$opts = media_image_profile_options($profile);
	$dir = dirname($abs_path);
	$base = pathinfo($abs_path, PATHINFO_FILENAME);
	$abs_out = $dir . '/' . $base . '.webp';

	if ($ext === 'webp' && realpath($abs_path) === realpath($abs_out)) {
		$conv = media_image_convert_to_webp($abs_path, $abs_path . '.tmp.webp', $opts['max_width'], $opts['quality']);
		if (!$conv['ok']) {
			return array('ok' => false, 'message' => $conv['message']);
		}
		@rename($abs_path . '.tmp.webp', $abs_path);
		$rel = ltrim(str_replace('\\', '/', substr($abs_path, strlen(ROOT_DIR))), '/');
		return array('ok' => true, 'message' => 'Optimized WebP', 'abs' => $abs_path, 'rel' => $rel, 'ext' => 'webp');
	}

	$conv = media_image_convert_to_webp($abs_path, $abs_out, $opts['max_width'], $opts['quality']);
	if (!$conv['ok']) {
		return array('ok' => false, 'message' => $conv['message']);
	}
	if ($abs_out !== $abs_path && is_file($abs_path)) {
		@unlink($abs_path);
	}
	$rel = ltrim(str_replace('\\', '/', substr($abs_out, strlen(ROOT_DIR))), '/');
	return array('ok' => true, 'message' => 'Converted to WebP', 'abs' => $abs_out, 'rel' => $rel, 'ext' => 'webp');
}

/**
 * Create admin list thumbnail next to a stored media file.
 */
function media_image_write_admin_thumb($abs_file) {
	if (!is_file($abs_file)) {
		return false;
	}
	$dir = dirname($abs_file) . '/';
	$name = basename($abs_file);
	$thumb = $dir . 'a-' . $name;
	$opts = media_image_profile_options('thumb');
	$conv = media_image_convert_to_webp($abs_file, $thumb, $opts['max_width'], $opts['quality']);
	return $conv['ok'];
}

/**
 * Store temp/upload buffer into files/media with WebP normalize.
 *
 * @return array{ok:bool, message:string, rel?:string, file?:array}
 */
function media_library_store_uploaded_file($tmp_path, $original_name, $profile = 'content') {
	if (!function_exists('media_library_upload_dir')) {
		require_once ROOT_DIR . 'functions/media_library.php';
	}
	require_once ROOT_DIR . 'functions/string_func.php';

	if (!is_uploaded_file($tmp_path) && !is_file($tmp_path)) {
		return array('ok' => false, 'message' => 'Invalid upload');
	}

	$pathinfo = pathinfo((string)$original_name);
	$ext = strtolower((string)@$pathinfo['extension']);
	if (!media_library_is_allowed_file((string)@$pathinfo['basename'])) {
		return array('ok' => false, 'message' => 'File type not allowed');
	}

	$base = strtolower(trunslit((string)@$pathinfo['filename']));
	if ($base === '') {
		$base = substr(md5((string)microtime(true)), 0, 12);
	}

	$rel_dir = media_library_upload_dir();
	$root = media_library_ensure_dir($rel_dir);
	if ($root === false) {
		return array('ok' => false, 'message' => 'Cannot create folder');
	}

	$staging_ext = $ext;
	$name = $base . '.' . $staging_ext;
	$n = 0;
	while (is_file($root . $name) || is_file($root . $base . ($n ? '-' . $n : '') . '.webp')) {
		$n++;
		$name = $base . '-' . $n . '.' . $staging_ext;
	}

	if (!@copy($tmp_path, $root . $name)) {
		return array('ok' => false, 'message' => 'Upload failed');
	}
	@chmod($root . $name, 0644);

	$abs = $root . $name;
	if (media_image_is_raster_extension($staging_ext)) {
		$norm = media_image_normalize_absolute($abs, $profile);
		if (!$norm['ok']) {
			@unlink($abs);
			return array('ok' => false, 'message' => $norm['message']);
		}
		$abs = $norm['abs'];
		$file_rel = $norm['rel'];
	} else {
		$file_rel = $rel_dir . '/' . $name;
	}

	if (media_image_is_raster_extension(pathinfo($abs, PATHINFO_EXTENSION))) {
		media_image_write_admin_thumb($abs);
	}

	if (!media_library_file_exists($file_rel)) {
		return array('ok' => false, 'message' => 'Upload saved path missing on disk');
	}

	$meta = media_library_read_meta($file_rel);
	$item = media_library_item_from_path($file_rel, $meta);
	media_library_invalidate_index();

	return array('ok' => true, 'message' => 'Uploaded', 'rel' => $file_rel, 'file' => $item);
}

/**
 * Resolve a files/media path to the best on-disk variant (prefer WebP sibling).
 *
 * @return string Relative path without leading slash, or '' if missing.
 */
function media_image_resolve_disk_media_path($rel_path) {
	$rel = media_library_normalize_db_path($rel_path);
	if ($rel === '' || strpos($rel, 'files/media/') !== 0) {
		return '';
	}
	if (media_library_file_exists($rel)) {
		return $rel;
	}
	$ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
	if (in_array($ext, array('png', 'jpg', 'jpeg', 'gif'), true)) {
		$webp = pathinfo($rel, PATHINFO_DIRNAME) . '/' . pathinfo($rel, PATHINFO_FILENAME) . '.webp';
		if (media_library_file_exists($webp)) {
			return $webp;
		}
	}
	return '';
}

/**
 * Normalize DB/img field to an existing files/media path (upgrade .png → .webp when needed).
 */
function media_image_resolve_db_media_path($rel_path) {
	return media_image_resolve_disk_media_path($rel_path);
}

/**
 * Rewrite <img src="/files/media/..."> to existing disk paths; drop tags with no file.
 *
 * @return array{html:string, removed:int, rewritten:int}
 */
function media_image_finalize_html_media_refs($html) {
	$html = (string)$html;
	if ($html === '' || stripos($html, '/files/media/') === false) {
		return array('html' => $html, 'removed' => 0, 'rewritten' => 0);
	}
	$removed = 0;
	$rewritten = 0;
	$out = preg_replace_callback(
		'#(<img\b[^>]*\ssrc=)(["\'])(/files/media/[^"\']+)\2([^>]*>)#iu',
		function ($m) use (&$removed, &$rewritten) {
			$rel = ltrim((string)$m[3], '/');
			$resolved = media_image_resolve_disk_media_path($rel);
			if ($resolved === '') {
				$removed++;
				return '';
			}
			if ($resolved !== $rel) {
				$rewritten++;
				return $m[1] . $m[2] . '/' . $resolved . $m[2] . $m[4];
			}
			return $m[0];
		},
		$html
	);
	if (!is_string($out)) {
		return array('html' => $html, 'removed' => 0, 'rewritten' => 0);
	}
	$out = preg_replace('#<figure\b[^>]*>\s*</figure>#iu', '', $out);
	return array('html' => $out, 'removed' => $removed, 'rewritten' => $rewritten);
}

/**
 * Remove <img> tags pointing at missing files/media/ paths from HTML.
 *
 * @return array{html:string, removed:int}
 */
function media_image_purge_missing_media_from_html($html) {
	$fin = media_image_finalize_html_media_refs($html);
	return array('html' => $fin['html'], 'removed' => $fin['removed']);
}
