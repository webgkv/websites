<?php
/**
 * Shared media library: list, upload, metadata, delete.
 * Files live under web-accessible paths (/files/media/, entity folders, etc.).
 */

if (!function_exists('media_library_normalize_db_path')) {
	require_once ROOT_DIR . 'functions/media_image.php';
}

/**
 * @return array<int, array<string, mixed>>
 */
function media_library_roots() {
	$roots = array(
		array(
			'id'       => 'media',
			'label'    => 'Media library',
			'path'     => 'files/media',
			'writable' => true,
			'url'      => '/files/media',
		),
		array(
			'id'       => 'assets',
			'label'    => 'Site assets',
			'path'     => 'assets/images',
			'writable' => false,
			'url'      => '/assets/images',
		),
		array(
			'id'       => 'games',
			'label'    => 'Games (catalog cards)',
			'path'     => 'images/games',
			'writable' => true,
			'url'      => '/images/games',
		),
	);
	$tables = media_library_entity_tables();
	foreach ($tables as $t) {
		$roots[] = array(
			'id'       => 'files_' . $t,
			'label'    => 'All ' . $t,
			'path'     => 'files/' . $t,
			'writable' => false,
			'url'      => '/files/' . $t,
			'scan'     => 'entity_imgs',
			'table'    => $t,
		);
	}
	return $roots;
}

/**
 * Tables that store per-record images under files/{table}/{id}/img/
 *
 * @return string[]
 */
function media_library_entity_tables() {
	return array(
		'pages',
		'guides',
		'games',
		'blog',
		'casino_articles',
		'promo',
		'casinos',
		'news',
		'advices',
		'recenzii',
		'gallery',
	);
}

/**
 * @return string[]
 */
function media_library_allowed_extensions() {
	return array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
}

function media_library_is_allowed_file($filename) {
	$ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
	return in_array($ext, media_library_allowed_extensions(), true);
}

function media_library_ensure_dir($rel_path) {
	$root = ROOT_DIR . str_replace('\\', '/', trim($rel_path, '/')) . '/';
	if (is_dir($root) || mkdir($root, 0755, true)) {
		return $root;
	}
	return false;
}

/**
 * Relative path of sidecar metadata JSON.
 */
function media_library_meta_path($file_rel) {
	$file_rel = str_replace('\\', '/', $file_rel);
	return $file_rel . '.meta.json';
}

/**
 * @return array{alt:string,title:string,caption:string}
 */
function media_library_read_meta($file_rel) {
	$path = ROOT_DIR . media_library_meta_path($file_rel);
	$out = array('alt' => '', 'title' => '', 'caption' => '');
	if (!is_file($path)) {
		return $out;
	}
	$raw = @file_get_contents($path);
	$data = $raw ? @json_decode($raw, true) : null;
	if (!is_array($data)) {
		return $out;
	}
	foreach (array_keys($out) as $k) {
		if (isset($data[$k])) {
			$out[$k] = (string)$data[$k];
		}
	}
	return $out;
}

/**
 * @param array{alt?:string,title?:string,caption?:string} $meta
 */
function media_library_write_meta($file_rel, array $meta) {
	$file_rel = str_replace('\\', '/', $file_rel);
	if (!is_file(ROOT_DIR . $file_rel)) {
		return array('ok' => false, 'message' => 'File not found');
	}
	$clean = array(
		'alt'     => isset($meta['alt']) ? (string)$meta['alt'] : '',
		'title'   => isset($meta['title']) ? (string)$meta['title'] : '',
		'caption' => isset($meta['caption']) ? (string)$meta['caption'] : '',
	);
	$path = ROOT_DIR . media_library_meta_path($file_rel);
	$dir = dirname($path);
	if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
		return array('ok' => false, 'message' => 'Cannot write metadata');
	}
	$ok = (bool)@file_put_contents($path, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	return array('ok' => $ok, 'message' => $ok ? 'Saved' : 'Write failed', 'meta' => $clean);
}

/**
 * Build public URL for a library file.
 */
function media_library_public_url($file_rel) {
	$file_rel = '/' . ltrim(str_replace('\\', '/', $file_rel), '/');
	return $file_rel;
}

/**
 * Upload target directory (relative, no trailing slash).
 * All editor uploads go to dated media folder (WordPress-style).
 */
function media_library_upload_dir($entity = '', $entity_id = 0, $folder = '') {
	$y = date('Y');
	$m = date('m');
	return 'files/media/' . $y . '/' . $m;
}

/**
 * @return array{ok:bool,message:string,file?:array}
 */
function media_library_upload($tmp_path, $original_name, $entity = '', $entity_id = 0, $folder = '') {
	require_once ROOT_DIR . 'functions/media_image.php';
	$profile = ($entity === 'games' || $entity === 'guides' || $entity === 'casino_articles' || $entity === 'promo') ? 'card' : 'content';
	$res = media_library_store_uploaded_file($tmp_path, $original_name, $profile);
	if (empty($res['ok'])) {
		return array('ok' => false, 'message' => isset($res['message']) ? $res['message'] : 'Upload failed');
	}
	$rel = isset($res['rel']) ? media_library_normalize_db_path($res['rel']) : '';
	if ($rel === '' || !media_library_file_exists($rel)) {
		return array('ok' => false, 'message' => 'Upload saved path missing on disk');
	}
	$item = isset($res['file']) && is_array($res['file']) ? $res['file'] : media_library_item_from_path($rel);
	return array('ok' => true, 'message' => 'Uploaded', 'rel' => $rel, 'file' => $item);
}

/** Whether DB `img` value is a path under files/media/. */
function media_library_is_stored_path($value) {
	$v = media_library_normalize_db_path($value);
	return strpos($v, 'files/media/') === 0;
}

/**
 * Paths assignable from the media picker to a main `img` field (not a temp upload id).
 * Includes catalog folders (images/games, images/casinos) and files/media uploads.
 */
function media_library_is_pickable_image_path($value) {
	$v = media_library_normalize_db_path($value);
	if ($v === '' || ctype_digit($v)) {
		return false;
	}
	if (strpos($v, '/') === false) {
		return false;
	}
	static $prefixes = array(
		'files/media/',
		'images/games/',
		'images/casinos/',
		'assets/images/',
	);
	foreach ($prefixes as $prefix) {
		if (strpos($v, $prefix) === 0) {
			return true;
		}
	}
	return (bool)preg_match('#^files/[a-z0-9_]+/\d+/img/#', $v);
}

/**
 * Save main image (admin file field) into files/media/YYYY/MM/.
 *
 * @return string|false Relative path for DB (files/media/…) or false on failure
 */
function media_library_save_main_image($temp_file, $original_name, $sizes = array('' => '')) {
	if (!$temp_file || !is_file($temp_file)) {
		return false;
	}
	require_once ROOT_DIR . 'functions/media_image.php';
	$res = media_library_store_uploaded_file($temp_file, $original_name, 'card');
	if (empty($res['ok']) || empty($res['rel'])) {
		return false;
	}
	$rel = media_library_normalize_db_path($res['rel']);
	if ($rel === '' || !media_library_file_exists($rel)) {
		return false;
	}
	return $rel;
}

/**
 * @param array{alt?:string,title?:string,caption?:string} $meta
 * @return array<string, mixed>
 */
function media_library_item_from_path($file_rel, array $meta = null) {
	$file_rel = str_replace('\\', '/', ltrim((string)$file_rel, '/'));
	$abs = ROOT_DIR . $file_rel;
	$meta = is_array($meta) ? $meta : media_library_read_meta($file_rel);
	$ext = strtolower(pathinfo($file_rel, PATHINFO_EXTENSION));
	$thumb = $file_rel;
	if ($ext !== 'svg' && is_file($abs)) {
		$thumb = $file_rel; // full image in grid (acceptable for admin)
	}
	return array(
		'path'      => $file_rel,
		'url'       => media_library_public_url($file_rel),
		'thumb'     => media_library_public_url($thumb),
		'name'      => basename($file_rel),
		'alt'       => $meta['alt'],
		'title'     => $meta['title'],
		'caption'   => $meta['caption'],
		'size'      => is_file($abs) ? (int)filesize($abs) : 0,
		'mtime'     => is_file($abs) ? (int)filemtime($abs) : 0,
		'writable'  => media_library_path_writable($file_rel),
		'deletable' => media_library_can_delete($file_rel),
		'source'    => media_library_file_source($file_rel),
	);
}

function media_library_file_source($file_rel) {
	$file_rel = str_replace('\\', '/', ltrim((string)$file_rel, '/'));
	if (strpos($file_rel, 'files/media/') === 0) {
		return 'upload';
	}
	return 'library';
}

/** Only uploads in files/media/ may be removed from the media UI. */
function media_library_can_delete($file_rel) {
	$file_rel = str_replace('\\', '/', ltrim((string)$file_rel, '/'));
	return strpos($file_rel, 'files/media/') === 0;
}

function media_library_path_writable($file_rel) {
	return media_library_can_delete($file_rel);
}

/**
 * @return array{ok:bool,message:string}
 */
function media_library_delete($file_rel) {
	$file_rel = str_replace('\\', '/', ltrim((string)$file_rel, '/'));
	if ($file_rel === '' || strpos($file_rel, '..') !== false) {
		return array('ok' => false, 'message' => 'Invalid path');
	}
	if (!media_library_can_delete($file_rel)) {
		return array('ok' => false, 'message' => 'Only images uploaded via Media library can be deleted. Site assets stay on the server.');
	}
	$abs = ROOT_DIR . $file_rel;
	if (!is_file($abs)) {
		return array('ok' => false, 'message' => 'File not found');
	}
	$meta = ROOT_DIR . media_library_meta_path($file_rel);
	@unlink($abs);
	if (is_file($meta)) {
		@unlink($meta);
	}
	media_library_invalidate_index();
	return array('ok' => true, 'message' => 'Deleted');
}

/**
 * Collect image files from a directory tree.
 *
 * @return array<int, array<string, mixed>>
 */
function media_library_scan_dir($rel_dir, $url_prefix, $writable, $max = 1500) {
	$items = array();
	$root = ROOT_DIR . str_replace('\\', '/', trim($rel_dir, '/'));
	if (!is_dir($root)) {
		return $items;
	}
	$iter = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	foreach ($iter as $file) {
		if (count($items) >= $max) {
			break;
		}
		if (!$file->isFile()) {
			continue;
		}
		$name = $file->getFilename();
		if (!media_library_is_allowed_file($name)) {
			continue;
		}
		if (substr($name, -9) === '.meta.json') {
			continue;
		}
		$abs = $file->getPathname();
		$rel = str_replace('\\', '/', substr($abs, strlen(ROOT_DIR)));
		$items[] = media_library_item_from_path($rel);
	}
	return $items;
}

/**
 * Scan files/{table}/{id}/img/ for all records (bounded).
 *
 * @return array<int, array<string, mixed>>
 */
function media_library_scan_entity_imgs($table, $max = 800) {
	$items = array();
	$table = preg_replace('/[^a-z0-9_]/', '', strtolower((string)$table));
	if ($table === '') {
		return $items;
	}
	$base = ROOT_DIR . 'files/' . $table . '/';
	if (!is_dir($base)) {
		return $items;
	}
	$dh = @opendir($base);
	if (!$dh) {
		return $items;
	}
	while (($id_dir = readdir($dh)) !== false) {
		if (count($items) >= $max) {
			break;
		}
		if ($id_dir === '.' || $id_dir === '..' || !ctype_digit($id_dir)) {
			continue;
		}
		$img_dir = $base . $id_dir . '/img/';
		if (!is_dir($img_dir)) {
			continue;
		}
		$fh = @opendir($img_dir);
		if (!$fh) {
			continue;
		}
		while (($f = readdir($fh)) !== false) {
			if (count($items) >= $max) {
				break;
			}
			if ($f === '.' || $f === '..' || $f === '') {
				continue;
			}
			if (strpos($f, 'a-') === 0) {
				continue;
			}
			if (!media_library_is_allowed_file($f)) {
				continue;
			}
			$rel = 'files/' . $table . '/' . $id_dir . '/img/' . $f;
			$items[] = media_library_item_from_path($rel);
		}
		closedir($fh);
	}
	closedir($dh);
	return $items;
}

/**
 * Scan all known site image locations (read-only catalog for editors).
 *
 * @return array<int, array<string, mixed>>
 */
function media_library_invalidate_index() {
	$GLOBALS['_media_library_index'] = null;
}

function media_library_collect_all() {
	if (isset($GLOBALS['_media_library_index']) && is_array($GLOBALS['_media_library_index'])) {
		return $GLOBALS['_media_library_index'];
	}
	$by_path = array();
	$merge = function ($items) use (&$by_path) {
		foreach ($items as $it) {
			if (empty($it['path'])) {
				continue;
			}
			$by_path[$it['path']] = $it;
		}
	};
	$merge(media_library_scan_dir('files/media', '/files/media', true, 4000));
	$merge(media_library_scan_dir('assets/images', '/assets/images', false, 2000));
	$merge(media_library_scan_dir('images/games', '/images/games', false, 1500));
	foreach (media_library_entity_tables() as $table) {
		$merge(media_library_scan_entity_imgs($table, 600));
	}
	$items = array_values($by_path);
	usort($items, function ($a, $b) {
		return (int)@$b['mtime'] - (int)@$a['mtime'];
	});
	$GLOBALS['_media_library_index'] = $items;
	return $items;
}

/**
 * Month keys for filter dropdown (YYYY-MM).
 *
 * @param array<int, array<string, mixed>> $items
 * @return string[]
 */
function media_library_month_options(array $items) {
	$months = array();
	foreach ($items as $it) {
		$t = (int)@$it['mtime'];
		if ($t <= 0) {
			continue;
		}
		$key = date('Y-m', $t);
		$months[$key] = date('F Y', $t);
	}
	krsort($months);
	return $months;
}

/**
 * List files for UI/API.
 *
 * @param array<string, mixed> $opts
 * @return array<string, mixed>
 */
function media_library_list(array $opts = array()) {
	$search = isset($opts['search']) ? trim((string)$opts['search']) : '';
	$month = isset($opts['month']) ? trim((string)$opts['month']) : '';
	$page = isset($opts['page']) ? max(1, (int)$opts['page']) : 1;
	$per_page = isset($opts['per_page']) ? min(80, max(20, (int)$opts['per_page'])) : 40;

	$items = media_library_collect_all();

	if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
		$items = array_values(array_filter($items, function ($it) use ($month) {
			$t = (int)@$it['mtime'];
			return $t > 0 && date('Y-m', $t) === $month;
		}));
	}

	if ($search !== '') {
		$q = mb_strtolower($search, 'UTF-8');
		$items = array_values(array_filter($items, function ($it) use ($q) {
			$hay = mb_strtolower(
				(string)@$it['name'] . ' ' . (string)@$it['alt'] . ' ' . (string)@$it['title'] . ' ' . (string)@$it['path'],
				'UTF-8'
			);
			return (strpos($hay, $q) !== false);
		}));
	}

	$total = count($items);
	$pages = $total > 0 ? (int)ceil($total / $per_page) : 1;
	if ($page > $pages) {
		$page = $pages;
	}
	$offset = ($page - 1) * $per_page;
	$page_items = array_slice($items, $offset, $per_page);
	$shown = min($offset + count($page_items), $total);

	return array(
		'ok'          => true,
		'items'       => $page_items,
		'total'       => $total,
		'shown'       => $shown,
		'page'        => $page,
		'pages'       => $pages,
		'per_page'    => $per_page,
		'has_more'    => $page < $pages,
		'months'      => media_library_month_options(media_library_collect_all()),
	);
}

/**
 * Render grid HTML for admin picker/page.
 *
 * @param array<int, array<string, mixed>> $items
 */
function media_library_render_grid_html(array $items) {
	if (empty($items)) {
		return '<div class="col-12 text-muted py-4">No images yet. Switch to <strong>Upload files</strong> or try another search.</div>';
	}
	$html = '';
	foreach ($items as $it) {
		$url = htmlspecialchars((string)$it['url'], ENT_QUOTES, 'UTF-8');
		$thumb = htmlspecialchars((string)$it['thumb'], ENT_QUOTES, 'UTF-8');
		$name = htmlspecialchars((string)$it['name'], ENT_QUOTES, 'UTF-8');
		$path = htmlspecialchars((string)$it['path'], ENT_QUOTES, 'UTF-8');
		$alt = htmlspecialchars((string)$it['alt'], ENT_QUOTES, 'UTF-8');
		$title = htmlspecialchars((string)$it['title'], ENT_QUOTES, 'UTF-8');
		$deletable = !empty($it['deletable']) ? '1' : '0';
		$html .= '<div class="col-6 col-sm-4 col-md-3 col-xl-2 mb-2 px-1">';
		$html .= '<button type="button" class="media-lib-item btn btn-light btn-block p-1 border" data-url="' . $url . '" data-path="' . $path . '" data-alt="' . $alt . '" data-title="' . $title . '" data-deletable="' . $deletable . '">';
		$html .= '<span class="media-lib-thumb d-block rounded overflow-hidden bg-white">';
		$html .= '<img src="' . $thumb . '" alt="" class="w-100" style="height:92px;object-fit:cover" loading="lazy">';
		$html .= '</span></button></div>';
	}
	return $html;
}

function media_library_render_count_html($shown, $total) {
	if ($total <= 0) {
		return '';
	}
	return 'Showing ' . (int)$shown . ' of ' . (int)$total . ' images';
}

/**
 * Access: media module or any content/site-tree editor.
 */
function media_library_user_can_access() {
	if (access('admin module', 'media')) {
		return true;
	}
	$gates = array('pages', 'content', 'guides', 'games', 'blog', 'casino_articles', 'promo', 'casinos', 'news', 'settings');
	foreach ($gates as $g) {
		if (access('admin module', $g)) {
			return true;
		}
	}
	return false;
}
