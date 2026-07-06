<?php
/**
 * Edit image in TinyMCE content — same overlay pattern as media_picker.php.
 */

require_once ROOT_DIR . 'functions/media_library.php';

if (!media_library_user_can_access()) {
	echo '<div class="p-4 text-danger">Access denied</div>';
	exit;
}
?>
<div class="modal show media-image-edit-modal" tabindex="-1" role="dialog" aria-modal="true" style="display:block;z-index:10600">
	<div class="modal-dialog modal-md" role="document" style="max-width:520px">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h5 class="modal-title mb-0">Edit image</h5>
				<button type="button" class="close image-edit-close" aria-label="Close"><span aria-hidden="true">×</span></button>
			</div>
			<div class="modal-body">
				<div class="text-center mb-3 border rounded p-2 bg-light">
					<img id="image-edit-preview" src="" alt="" class="img-fluid" style="max-height:180px">
				</div>
				<div class="form-group mb-2">
					<label class="small mb-0">Image URL</label>
					<input type="text" class="form-control form-control-sm" id="image-edit-src" autocomplete="off">
				</div>
				<p class="mb-3">
					<button type="button" class="btn btn-sm btn-outline-primary" id="image-edit-pick-library">Choose from media library</button>
				</p>
				<div class="form-group mb-2">
					<label class="small mb-0">Alt text (description)</label>
					<input type="text" class="form-control form-control-sm" id="image-edit-alt" autocomplete="off">
				</div>
				<div class="form-group mb-2">
					<label class="small mb-0">Title (optional)</label>
					<input type="text" class="form-control form-control-sm" id="image-edit-title" autocomplete="off">
				</div>
				<div class="form-row mb-2">
					<div class="col-6">
						<label class="small mb-0">Width (px)</label>
						<input type="number" min="0" class="form-control form-control-sm" id="image-edit-width" placeholder="auto">
					</div>
					<div class="col-6">
						<label class="small mb-0">Height (px)</label>
						<input type="number" min="0" class="form-control form-control-sm" id="image-edit-height" placeholder="auto">
					</div>
				</div>
				<div class="form-group mb-0">
					<label class="small mb-0">Display style</label>
					<select class="form-control form-control-sm" id="image-edit-class">
						<option value="img-fluid">Default (responsive)</option>
						<option value="img-fluid d-block mx-auto">Centered</option>
						<option value="img-fluid w-100">Full width</option>
						<option value="">No extra class</option>
					</select>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-outline-secondary image-edit-close">Cancel</button>
				<button type="button" class="btn btn-primary" id="image-edit-apply">Apply</button>
			</div>
		</div>
	</div>
</div>
<style>
.media-image-edit-modal { background: rgba(0,0,0,.5); }
</style>
