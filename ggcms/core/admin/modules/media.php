<?php
/**
 * Media library — WordPress-style UI.
 * JSON: /admin.php?m=media&json=1
 */

require_once ROOT_DIR . 'functions/media_library.php';

if (!media_library_user_can_access()) {
	$content = '<div class="alert alert-danger">Access denied.</div>';
	return;
}

// --- API: list (JSON) ---
if (!empty($_GET['json'])) {
	header('Content-Type: application/json; charset=utf-8');
	$res = media_library_list(array(
		'search'   => isset($_GET['search']) ? (string)$_GET['search'] : '',
		'month'    => isset($_GET['month']) ? (string)$_GET['month'] : '',
		'page'     => isset($_GET['page']) ? (int)$_GET['page'] : 1,
		'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 40,
	));
	$res['html'] = media_library_render_grid_html($res['items']);
	$res['count_label'] = media_library_render_count_html($res['shown'], $res['total']);
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}

// --- API: upload (always files/media/YYYY/MM/) ---
if (!empty($_GET['upload']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
	header('Content-Type: application/json; charset=utf-8');
	$file = isset($_FILES['file']) ? $_FILES['file'] : (isset($_FILES['temp']) ? $_FILES['temp'] : null);
	if (!$file || empty($file['tmp_name'])) {
		echo json_encode(array('ok' => false, 'message' => 'No file'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	$res = media_library_upload($file['tmp_name'], $file['name']);
	if (empty($res['ok']) && function_exists('system_log_add')) {
		system_log_add('media', 'error', 'Media library upload failed', array(
			'message' => isset($res['message']) ? (string)$res['message'] : '',
			'name' => isset($file['name']) ? (string)$file['name'] : '',
			'size' => isset($file['size']) ? (int)$file['size'] : 0,
		));
	}
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}

// --- API: save metadata ---
if (!empty($_POST['save_meta']) && !empty($_POST['path'])) {
	header('Content-Type: application/json; charset=utf-8');
	$res = media_library_write_meta((string)$_POST['path'], array(
		'alt'     => isset($_POST['alt']) ? (string)$_POST['alt'] : '',
		'title'   => isset($_POST['title']) ? (string)$_POST['title'] : '',
		'caption' => isset($_POST['caption']) ? (string)$_POST['caption'] : '',
	));
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}

// --- API: delete (files/media/ only) ---
if (!empty($_POST['delete']) && !empty($_POST['path'])) {
	header('Content-Type: application/json; charset=utf-8');
	$res = media_library_delete((string)$_POST['path']);
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}

$page_name = 'Media library';
$months = media_library_month_options(media_library_collect_all());

$content = '<div class="card border-0"><div class="card-body p-0 media-library-page">';
$content .= '<ul class="nav nav-tabs px-3 pt-2" role="tablist">';
$content .= '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ml-upload" role="tab">Upload files</a></li>';
$content .= '<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ml-library" role="tab">Media library</a></li>';
$content .= '</ul>';

$content .= '<div class="tab-content">';
$content .= '<div class="tab-pane fade" id="ml-upload" role="tabpanel">';
$content .= '<div class="p-4">';
$content .= '<p class="text-muted small">Upload new images here. They are stored in <code>/files/media/' . date('Y/m') . '/</code>. Older images from pages, Download, Games, etc. appear automatically in the <strong>Media library</strong> tab — no folder selection needed.</p>';
$content .= '<div class="media-picker-drop border rounded p-5 text-center bg-light" id="media-lib-drop">';
$content .= '<p class="mb-2">Drop files here</p>';
$content .= '<button type="button" class="btn btn-primary" id="media-lib-choose-file">Select files</button>';
$content .= '<input type="file" class="d-none" id="media-lib-file-input" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" multiple>';
$content .= '<div class="small text-muted mt-3" id="media-lib-upload-status"></div>';
$content .= '</div></div></div>';

$content .= '<div class="tab-pane fade show active" id="ml-library" role="tabpanel">';
$content .= '<div class="media-lib-toolbar d-flex flex-wrap align-items-center px-3 py-2 border-top border-bottom bg-light">';
$content .= '<select class="form-control form-control-sm mr-2 mb-1" id="media-lib-month" style="width:auto;min-width:140px"><option value="">All dates</option>';
foreach ($months as $mk => $ml) {
	$content .= '<option value="' . htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($ml) . '</option>';
}
$content .= '</select>';
$content .= '<input type="search" class="form-control form-control-sm mr-2 mb-1" id="media-lib-search" placeholder="Search media…" style="max-width:240px">';
$content .= '<button type="button" class="btn btn-sm btn-outline-secondary mb-1" id="media-lib-search-btn">Search</button>';
$content .= '</div>';

$content .= '<div class="media-lib-layout d-flex" style="min-height:480px">';
$content .= '<div class="media-lib-grid-wrap flex-grow-1 p-2" style="overflow:auto">';
$content .= '<div class="form-row mx-0" id="media-lib-grid"><div class="col-12 text-muted py-4">Loading…</div></div>';
$content .= '<div class="text-center py-3"><div class="small text-muted mb-2" id="media-lib-count"></div>';
$content .= '<button type="button" class="btn btn-outline-primary btn-sm d-none" id="media-lib-load-more">Load more</button></div>';
$content .= '</div>';

$content .= '<div class="media-lib-sidebar border-left bg-white p-3" style="width:300px;flex-shrink:0">';
$content .= '<div id="media-lib-sidebar-empty" class="text-muted small">Click an image to edit alt text or delete uploads.</div>';
$content .= '<div id="media-lib-sidebar-detail" class="d-none">';
$content .= '<div class="text-center mb-3 border rounded p-2 bg-light"><img id="media-lib-preview" src="" alt="" class="img-fluid" style="max-height:180px"></div>';
$content .= '<div class="form-group mb-2"><label class="small mb-0">Alt text</label><input type="text" class="form-control form-control-sm" id="media-lib-detail-alt"></div>';
$content .= '<div class="form-group mb-2"><label class="small mb-0">Title</label><input type="text" class="form-control form-control-sm" id="media-lib-detail-title"></div>';
$content .= '<div class="form-group mb-2"><label class="small mb-0 text-muted">File</label><div class="small text-break text-muted" id="media-lib-detail-path"></div></div>';
$content .= '<div class="d-flex flex-wrap">';
$content .= '<button type="button" class="btn btn-primary btn-sm mr-2 mb-1" id="media-lib-save-meta">Save</button>';
$content .= '<button type="button" class="btn btn-outline-danger btn-sm mb-1" id="media-lib-delete">Delete</button>';
$content .= '<a href="#" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm mb-1 ml-auto" id="media-lib-open-url">Open</a>';
$content .= '</div>';
$content .= '<p class="small text-muted mt-2 mb-0" id="media-lib-delete-hint">Only files uploaded here can be deleted. Site assets are read-only.</p>';
$content .= '</div></div></div></div></div>';

$content .= '</div></div></div>';
