/**
 * Media library — WordPress-style (all images visible, uploads to files/media/YYYY/MM/).
 */
(function ($) {
	'use strict';

	var listState = { page: 1, append: false };

	function apiList(params) {
		return $.getJSON('/admin.php?m=media&json=1', params);
	}

	function apiUpload(formData) {
		return $.ajax({
			url: '/admin.php?m=media&upload=1',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json'
		});
	}

	function listParams($root, extra) {
		var isPicker = $root.is('#media-picker-root');
		return $.extend({
			search: isPicker ? $('#media-picker-search').val() : $('#media-lib-search').val(),
			month: isPicker ? $('#media-picker-month').val() : $('#media-lib-month').val(),
			page: 1,
			per_page: 40
		}, extra || {});
	}

	function bindGrid($grid, onSelect) {
		$grid.off('click.media', '.media-lib-item').on('click.media', '.media-lib-item', function () {
			$grid.find('.media-lib-item').removeClass('media-lib-active');
			$(this).addClass('media-lib-active');
			onSelect({
				url: $(this).data('url'),
				path: $(this).data('path'),
				alt: $(this).data('alt') || '',
				title: $(this).data('title') || '',
				deletable: $(this).data('deletable') === 1 || $(this).data('deletable') === '1'
			}, $(this));
		});
	}

	function loadList($root, $grid, $countEl, $loadMore, onSelect, reset) {
		if (reset) {
			listState.page = 1;
			listState.append = false;
		}
		var params = listParams($root, { page: listState.page });
		if (!listState.append) {
			$grid.html('<div class="col-12 text-muted py-4">Loading…</div>');
		}
		apiList(params).done(function (data) {
			var html = data.html || '';
			if (listState.append) {
				$grid.append(html);
			} else {
				$grid.html(html);
			}
			if ($countEl && $countEl.length) {
				$countEl.text(data.count_label || '');
			}
			if ($loadMore && $loadMore.length) {
				if (data.has_more) {
					$loadMore.removeClass('d-none');
				} else {
					$loadMore.addClass('d-none');
				}
			}
			bindGrid($grid, onSelect);
		}).fail(function () {
			if (!listState.append) {
				$grid.html('<div class="col-12 text-danger">Could not load images.</div>');
			}
		});
	}

	function uploadFiles(files, $status, onDone) {
		if (!files || !files.length) {
			return;
		}
		var i = 0;
		var last = null;
		function next() {
			if (i >= files.length) {
				if ($status) {
					$status.text(last && last.ok ? 'Uploaded ' + files.length + ' file(s).' : (last && last.message) || 'Done');
				}
				if (typeof onDone === 'function') {
					onDone(last);
				}
				return;
			}
			var fd = new FormData();
			fd.append('file', files[i]);
			if ($status) {
				$status.text('Uploading ' + (i + 1) + ' of ' + files.length + '…');
			}
			apiUpload(fd).done(function (r) {
				last = r;
				i++;
				next();
			}).fail(function () {
				last = { ok: false, message: 'Upload failed' };
				i++;
				next();
			});
		}
		next();
	}

	// --- Admin page ---
	function initMediaPage() {
		var $page = $('.media-library-page');
		if (!$page.length) {
			return;
		}
		var $grid = $('#media-lib-grid');
		var $count = $('#media-lib-count');
		var $loadMore = $('#media-lib-load-more');
		var selected = null;

		function showDetail(data) {
			selected = data;
			$('#media-lib-sidebar-empty').addClass('d-none');
			$('#media-lib-sidebar-detail').removeClass('d-none');
			$('#media-lib-preview').attr('src', data.url);
			$('#media-lib-detail-alt').val(data.alt || '');
			$('#media-lib-detail-title').val(data.title || '');
			$('#media-lib-detail-path').text(data.path || '');
			$('#media-lib-open-url').attr('href', data.url);
			$('#media-lib-delete').toggle(!!data.deletable);
			$('#media-lib-delete-hint').toggle(!data.deletable);
		}

		function reload(reset) {
			loadList($page, $grid, $count, $loadMore, showDetail, reset !== false);
		}

		reload(true);

		$('#media-lib-search-btn, #media-lib-month').on('click change', function () {
			listState.append = false;
			reload(true);
		});
		$('#media-lib-search').on('keypress', function (e) {
			if (e.which === 13) {
				listState.append = false;
				reload(true);
			}
		});
		$('#media-lib-load-more').on('click', function () {
			listState.page++;
			listState.append = true;
			reload(false);
		});

		$('#media-lib-save-meta').on('click', function () {
			if (!selected || !selected.path) {
				return;
			}
			$.post('/admin.php?m=media', {
				save_meta: 1,
				path: selected.path,
				alt: $('#media-lib-detail-alt').val(),
				title: $('#media-lib-detail-title').val()
			}, null, 'json').done(function (r) {
				if (r.ok) {
					reload(true);
				} else {
					alert(r.message || 'Could not save');
				}
			});
		});

		$('#media-lib-delete').on('click', function () {
			if (!selected || !selected.deletable) {
				alert('This file cannot be deleted from here.');
				return;
			}
			if (!confirm('Delete this image permanently?')) {
				return;
			}
			$.post('/admin.php?m=media', { delete: 1, path: selected.path }, null, 'json').done(function (r) {
				if (r.ok) {
					$('#media-lib-sidebar-detail').addClass('d-none');
					$('#media-lib-sidebar-empty').removeClass('d-none');
					reload(true);
				} else {
					alert(r.message || 'Could not delete');
				}
			});
		});

		$('#media-lib-choose-file, #media-lib-drop').on('click', function (e) {
			if (e.target.id === 'media-lib-drop' || $(e.target).closest('#media-lib-choose-file').length) {
				$('#media-lib-file-input').trigger('click');
			}
		});
		$('#media-lib-file-input').on('change', function () {
			var files = this.files;
			uploadFiles(files, $('#media-lib-upload-status'), function () {
				$('a[href="#ml-library"]').tab('show');
				reload(true);
			});
			this.value = '';
		});
	}

	var IMAGE_EDIT_MODAL_HTML = ''
		+ '<div class="modal show media-image-edit-modal" tabindex="-1" role="dialog" aria-modal="true" style="display:block;z-index:10600">'
		+ '<div class="modal-dialog modal-md" role="document" style="max-width:520px">'
		+ '<div class="modal-content">'
		+ '<div class="modal-header py-2">'
		+ '<h5 class="modal-title mb-0">Edit image</h5>'
		+ '<button type="button" class="close image-edit-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
		+ '</div>'
		+ '<div class="modal-body">'
		+ '<div class="text-center mb-3 border rounded p-2 bg-light">'
		+ '<img id="image-edit-preview" src="" alt="" class="img-fluid" style="max-height:180px">'
		+ '</div>'
		+ '<div class="form-group mb-2"><label class="small mb-0">Image URL</label>'
		+ '<input type="text" class="form-control form-control-sm" id="image-edit-src" autocomplete="off"></div>'
		+ '<p class="mb-3"><button type="button" class="btn btn-sm btn-outline-primary" id="image-edit-pick-library">Choose from media library</button></p>'
		+ '<div class="form-group mb-2"><label class="small mb-0">Alt text (description)</label>'
		+ '<input type="text" class="form-control form-control-sm" id="image-edit-alt" autocomplete="off"></div>'
		+ '<div class="form-group mb-2"><label class="small mb-0">Title (optional)</label>'
		+ '<input type="text" class="form-control form-control-sm" id="image-edit-title" autocomplete="off"></div>'
		+ '<div class="form-row mb-2">'
		+ '<div class="col-6"><label class="small mb-0">Width (px)</label>'
		+ '<input type="number" min="0" class="form-control form-control-sm" id="image-edit-width" placeholder="auto"></div>'
		+ '<div class="col-6"><label class="small mb-0">Height (px)</label>'
		+ '<input type="number" min="0" class="form-control form-control-sm" id="image-edit-height" placeholder="auto"></div>'
		+ '</div>'
		+ '<div class="form-group mb-0"><label class="small mb-0">Display style</label>'
		+ '<select class="form-control form-control-sm" id="image-edit-class">'
		+ '<option value="img-fluid">Default (responsive)</option>'
		+ '<option value="img-fluid d-block mx-auto">Centered</option>'
		+ '<option value="img-fluid w-100">Full width</option>'
		+ '<option value="">No extra class</option>'
		+ '</select></div>'
		+ '</div>'
		+ '<div class="modal-footer py-2">'
		+ '<button type="button" class="btn btn-outline-secondary image-edit-close">Cancel</button>'
		+ '<button type="button" class="btn btn-primary" id="image-edit-apply">Apply</button>'
		+ '</div></div></div></div>'
		+ '<div class="media-image-edit-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10599"></div>';

	function adminRestoreParentModal() {
		if ($('#image-editor-wrap').length || window.__adminMediaPickerInline) {
			return;
		}
		if ($('#window').length) {
			$('#window').modal('show');
		}
	}

	function resolveContextImg(ctx) {
		if (!ctx || !ctx.ed) {
			return null;
		}
		var ed = ctx.ed;
		var body = ed.getBody();
		if (!body) {
			return null;
		}
		if (ctx.img && body.contains(ctx.img)) {
			return ctx.img;
		}
		if (ctx.imgId) {
			return body.querySelector('img[data-admin-img-edit="' + ctx.imgId + '"]');
		}
		return null;
	}

	function readImageEditForm() {
		return {
			src: String($('#image-edit-src').val() || '').trim(),
			alt: String($('#image-edit-alt').val() || ''),
			title: String($('#image-edit-title').val() || ''),
			width: String($('#image-edit-width').val() || '').trim(),
			height: String($('#image-edit-height').val() || '').trim(),
			imgClass: String($('#image-edit-class').val() || '')
		};
	}

	function fillImageEditForm(data) {
		$('#image-edit-src').val(data.src || '');
		$('#image-edit-alt').val(data.alt || '');
		$('#image-edit-title').val(data.title || '');
		$('#image-edit-width').val(data.width || '');
		$('#image-edit-height').val(data.height || '');
		$('#image-edit-class').val(data.imgClass || 'img-fluid');
		$('#image-edit-preview').attr({src: data.src || '', alt: data.alt || ''});
	}

	function initImageEditor() {
		var ctx = window.__imageEditorContext;
		if (!ctx || !ctx.ed) {
			return;
		}
		var img = resolveContextImg(ctx);
		if (!img) {
			alert('Could not find the image in the editor. Click the image again and retry.');
			window.ImageEditor.close();
			return;
		}
		ctx.img = img;
		var dom = ctx.ed.dom;
		var w = parseInt(dom.getAttrib(img, 'width'), 10) || 0;
		var h = parseInt(dom.getAttrib(img, 'height'), 10) || 0;
		if ((!w || !h) && img.naturalWidth > 0) {
			w = img.naturalWidth;
			h = img.naturalHeight || 0;
		}
		fillImageEditForm({
			src: dom.getAttrib(img, 'src') || '',
			alt: dom.getAttrib(img, 'alt') || '',
			title: dom.getAttrib(img, 'title') || '',
			width: w > 0 ? String(w) : '',
			height: h > 0 ? String(h) : '',
			imgClass: dom.getAttrib(img, 'class') || 'img-fluid'
		});

		var $wrap = $('#image-editor-wrap');
		$wrap.off('click.imgEdit');
		$wrap.on('click.imgEdit', '.image-edit-close', function (e) {
			e.preventDefault();
			window.ImageEditor.close();
		});
		$wrap.on('click.imgEdit', '#image-edit-pick-library', function (e) {
			e.preventDefault();
			var $f = $(ctx.ed.getContainer()).closest('.form');
			window.MediaLibraryPicker.open(
				{entity: String($f.data('mediaEntity') || ''), entity_id: parseInt($f.data('mediaEntityId'), 10) || 0},
				function (data) {
					fillImageEditForm({
						src: data.url,
						alt: data.alt || $('#image-edit-alt').val(),
						title: data.title || $('#image-edit-title').val(),
						width: $('#image-edit-width').val(),
						height: $('#image-edit-height').val(),
						imgClass: $('#image-edit-class').val()
					});
				},
				{zIndex: 10700}
			);
		});
		$wrap.on('click.imgEdit', '#image-edit-apply', function () {
			img = resolveContextImg(ctx);
			if (!img) {
				alert('Image node was lost. Close and click the image again.');
				window.ImageEditor.close();
				return;
			}
			var d = readImageEditForm();
			if (!d.src) {
				alert('Image URL is required.');
				return;
			}
			var attribs = {
				src: d.src,
				alt: d.alt,
				title: d.title,
				'class': d.imgClass
			};
			if (d.width) {
				attribs.width = String(parseInt(d.width, 10) || d.width);
			} else {
				dom.setAttrib(img, 'width', null);
			}
			if (d.height) {
				attribs.height = String(parseInt(d.height, 10) || d.height);
			} else {
				dom.setAttrib(img, 'height', null);
			}
			dom.setAttribs(img, attribs);
			ctx.ed.selection.select(img);
			ctx.ed.fire('change');
			ctx.ed.nodeChanged();
			var $f = $(ctx.ed.getContainer()).closest('.form');
			if ($f.length) {
				$f.data('changed', true);
			}
			window.ImageEditor.close();
		});
	}

	// --- Image editor (TinyMCE) — inline overlay (no AJAX; same UX as media picker) ---
	window.ImageEditor = {
		open: function (ed, img) {
			if (!ed || !img) {
				return;
			}
			var imgId = img.getAttribute('data-admin-img-edit');
			if (!imgId) {
				imgId = 'aie-' + String(Date.now()) + '-' + String(Math.floor(Math.random() * 10000));
				img.setAttribute('data-admin-img-edit', imgId);
			}
			if ($('#image-editor-wrap').length) {
				$('#image-editor-wrap').remove();
			}
			window.__imageEditorContext = {ed: ed, img: img, imgId: imgId};
			if ($('#window').length) {
				$('#window').modal('hide');
			}
			$('body').append('<div id="image-editor-wrap">' + IMAGE_EDIT_MODAL_HTML + '</div>');
			initImageEditor();
		},
		close: function () {
			$('#image-editor-wrap').remove();
			window.__imageEditorContext = null;
			adminRestoreParentModal();
		}
	};

	// --- Picker (TinyMCE) ---
	window.MediaLibraryPicker = {
		open: function (ctx, callback, options) {
			options = options || {};
			window.__mediaPickerCallback = callback;
			window.__adminMediaPickerOpen = true;
			// Inline full-page editor: form is not inside #window — do not hide a modal.
			window.__adminMediaPickerInline = $('.form').filter(function () {
				return $(this).closest('#window').length === 0;
			}).length > 0;
			if ($('#media-picker-wrap').length) {
				$('#media-picker-wrap').remove();
			}
			$('body').append('<div id="media-picker-wrap"></div>');
			$('#media-picker-wrap').load('/admin.php?m=media&u=media_picker', function () {
				if (!window.__adminMediaPickerInline && $('#window').length && !$('#image-editor-wrap').length) {
					$('#window').modal('hide');
				}
				if (options.zIndex) {
					$('#media-picker-wrap .media-picker-modal').css('z-index', options.zIndex);
				}
				initPicker();
			});
		},
		close: function () {
			$(document).off('click.mediaPicker');
			$('#media-picker-wrap').remove();
			window.__adminMediaPickerOpen = false;
			adminRestoreParentModal();
		}
	};

	function initPicker() {
		var $root = $('#media-picker-root');
		if (!$root.length) {
			return;
		}
		var $grid = $('#media-picker-grid');
		var $count = $('#media-picker-count');
		var $loadMore = $('#media-picker-load-more');
		var selected = null;

		function showDetail(data) {
			selected = data;
			$('#media-picker-sidebar-empty').addClass('d-none');
			$('#media-picker-sidebar-detail').removeClass('d-none');
			$('#media-picker-preview').attr('src', data.url);
			$('#media-picker-alt').val(data.alt || '');
			$('#media-picker-title').val(data.title || '');
			$('#media-picker-path-label').text(data.path || '');
			$('#media-picker-insert').prop('disabled', !data.url);
		}

		function reload(reset) {
			loadList($root, $grid, $count, $loadMore, showDetail, reset !== false);
		}

		reload(true);

		$('#media-picker-search-btn, #media-picker-month').on('click change', function () {
			listState.append = false;
			reload(true);
		});
		$('#media-picker-search').on('keypress', function (e) {
			if (e.which === 13) {
				listState.append = false;
				reload(true);
			}
		});
		$('#media-picker-load-more').on('click', function () {
			listState.page++;
			listState.append = true;
			reload(false);
		});

		$('.media-picker-close').on('click', function (e) {
			e.preventDefault();
			window.MediaLibraryPicker.close();
		});

		$('#media-picker-choose-file, #media-picker-drop').on('click', function (e) {
			if (e.target.id === 'media-picker-drop' || $(e.target).closest('#media-picker-choose-file').length) {
				$('#media-picker-file').trigger('click');
			}
		});
		$('#media-picker-file').on('change', function () {
			uploadFiles(this.files, $('#media-picker-upload-status'), function (r) {
				if (r && r.ok && r.file) {
					showDetail(r.file);
				}
				$('a[href="#mp-library"]').tab('show');
				reload(true);
			});
			this.value = '';
		});

		$('#media-picker-insert').on('click', function () {
			if (!selected || !selected.url) {
				return;
			}
			var out = {
				url: selected.url,
				path: selected.path || '',
				alt: $('#media-picker-alt').val() || '',
				title: $('#media-picker-title').val() || '',
				responsive: true
			};
			if (typeof window.__mediaPickerCallback === 'function') {
				window.__mediaPickerCallback(out);
			}
			window.MediaLibraryPicker.close();
		});
	}

	$(function () {
		initMediaPage();
	});

})(jQuery);
