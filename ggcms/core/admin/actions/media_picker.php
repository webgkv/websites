<?php
/**
 * TinyMCE modal — WordPress-style: Upload files | Media library.
 */

require_once ROOT_DIR . 'functions/media_library.php';

if (!media_library_user_can_access()) {
	echo '<div class="p-4 text-danger">Access denied</div>';
	exit;
}

$months = media_library_month_options(media_library_collect_all());
?>
<div class="modal show media-picker-modal" tabindex="-1" role="dialog" aria-modal="true" style="display:block;z-index:10600">
	<div class="modal-dialog modal-xl" role="document" style="max-width:1100px">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h5 class="modal-title mb-0">Select or upload image</h5>
				<button type="button" class="close media-picker-close" aria-label="Close"><span aria-hidden="true">×</span></button>
			</div>
			<div class="modal-body p-0" id="media-picker-root">
				<ul class="nav nav-tabs px-3 pt-2 border-bottom-0" role="tablist">
					<li class="nav-item">
						<a class="nav-link" data-toggle="tab" href="#mp-upload" role="tab">Upload files</a>
					</li>
					<li class="nav-item">
						<a class="nav-link active" data-toggle="tab" href="#mp-library" role="tab">Media library</a>
					</li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane fade" id="mp-upload" role="tabpanel">
						<div class="p-4">
							<p class="text-muted small mb-3">New files are saved to <code>/files/media/<?= date('Y/m') ?>/</code>. Existing site images stay where they are — you can pick them in the Media library tab.</p>
							<div class="media-picker-drop border rounded p-5 text-center bg-light" id="media-picker-drop">
								<p class="mb-2 font-weight-medium">Drop files here</p>
								<button type="button" class="btn btn-primary" id="media-picker-choose-file">Select files</button>
								<input type="file" class="d-none" id="media-picker-file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" multiple>
								<div class="small text-muted mt-3" id="media-picker-upload-status"></div>
							</div>
						</div>
					</div>
					<div class="tab-pane fade show active" id="mp-library" role="tabpanel">
						<div class="media-lib-toolbar d-flex flex-wrap align-items-center px-3 py-2 border-top border-bottom bg-light">
							<select class="form-control form-control-sm mr-2 mb-1" id="media-picker-month" style="width:auto;min-width:140px">
								<option value="">All dates</option>
								<?php foreach ($months as $mk => $ml) { ?>
								<option value="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($ml, ENT_QUOTES, 'UTF-8') ?></option>
								<?php } ?>
							</select>
							<input type="search" class="form-control form-control-sm mr-2 mb-1" id="media-picker-search" placeholder="Search media…" style="max-width:220px">
							<button type="button" class="btn btn-sm btn-outline-secondary mb-1" id="media-picker-search-btn">Search</button>
						</div>
						<div class="media-lib-layout d-flex" style="min-height:360px">
							<div class="media-lib-grid-wrap flex-grow-1 p-2" style="overflow:auto;max-height:420px">
								<div class="form-row mx-0" id="media-picker-grid">
									<div class="col-12 text-muted py-4">Loading…</div>
								</div>
								<div class="text-center py-2">
									<div class="small text-muted mb-2" id="media-picker-count"></div>
									<button type="button" class="btn btn-outline-primary btn-sm d-none" id="media-picker-load-more">Load more</button>
								</div>
							</div>
							<div class="media-lib-sidebar border-left bg-white p-3" style="width:280px;flex-shrink:0">
								<div id="media-picker-sidebar-empty" class="text-muted small">Select an image to see details and insert it into the page.</div>
								<div id="media-picker-sidebar-detail" class="d-none">
									<div class="text-center mb-3 border rounded p-2 bg-light">
										<img id="media-picker-preview" src="" alt="" class="img-fluid" style="max-height:160px">
									</div>
									<div class="form-group mb-2">
										<label class="small mb-0">Alt text</label>
										<input type="text" class="form-control form-control-sm" id="media-picker-alt">
									</div>
									<div class="form-group mb-2">
										<label class="small mb-0">Title</label>
										<input type="text" class="form-control form-control-sm" id="media-picker-title">
									</div>
									<div class="form-group mb-0">
										<label class="small mb-0 text-muted">File</label>
										<div class="small text-break text-muted" id="media-picker-path-label"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-outline-secondary media-picker-close">Cancel</button>
				<button type="button" class="btn btn-primary" id="media-picker-insert" disabled>Select</button>
			</div>
		</div>
	</div>
</div>
<style>
.media-picker-modal { background: rgba(0,0,0,.5); }
.media-lib-item { cursor: pointer; }
.media-lib-item.media-lib-active .media-lib-thumb { box-shadow: 0 0 0 3px #3e72c6; }
.media-lib-item:focus { outline: none; box-shadow: 0 0 0 2px #3e72c6; }
</style>
