/**
 * Admin WYSIWYG — Summernote (Bootstrap 4).
 * Uses window.jQuery after bundle.js (jQuery 3.x). Do not capture $ at script parse time.
 */
(function (window) {
	'use strict';

	function $jq() {
		return window.jQuery;
	}

	function textareaInVisibleTab(ta) {
		var $ = $jq();
		if (!$) {
			return true;
		}
		var $pane = $(ta).closest('.tab-pane');
		if (!$pane.length) {
			return true;
		}
		return $pane.hasClass('active');
	}

	function editorHeight($ta) {
		var h = parseInt($ta.css('min-height'), 10) || parseInt($ta.css('height'), 10);
		if (!h || h < 120) {
			h = 400;
		}
		return h;
	}

	function hasEditor($ta) {
		return $ta.next('.note-editor').length > 0;
	}

	function buildOptions($form, $ta) {
		return {
			height: editorHeight($ta),
			width: '100%',
			disableDragAndDrop: true,
			styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote'],
			toolbar: [
				['style', ['style']],
				['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['insert', ['link', 'picture', 'table']],
				['view', ['fullscreen', 'codeview']]
			],
			callbacks: {
				onChange: function () {
					$form.data('changed', true);
				},
				onImageUpload: function (files) {
					if (!files || !files.length || !window.MediaLibraryPicker) {
						return;
					}
					var $f = $form;
					window.MediaLibraryPicker.open(
						{
							entity: String($f.data('mediaEntity') || ''),
							entity_id: parseInt($f.data('mediaEntityId'), 10) || 0
						},
						function (data) {
							var url = data && data.url ? data.url : '';
							if (!url) {
								return;
							}
							var $img = $('<img>').attr({src: url, alt: data.alt || '', class: 'img-fluid'});
							$ta.summernote('insertNode', $img[0]);
						}
					);
				}
			}
		};
	}

	function findTextareas($root) {
		var $ = $jq();
		return $root.find('.tinymce textarea, textarea.admin-wysiwyg');
	}

	function initTextarea(ta, $form) {
		var $ = $jq();
		if (!$ || !$.fn.summernote) {
			console.error('[AdminEditor] Summernote not available. Check that bundle.js and summernote-bs4.min.js load.');
			return false;
		}
		var $ta = $(ta);
		if (!textareaInVisibleTab(ta)) {
			return false;
		}
		if (hasEditor($ta)) {
			return true;
		}
		if (!ta.id) {
			ta.id = 'wysiwyg_' + String(Date.now()) + '_' + String(Math.floor(Math.random() * 10000));
		}
		try {
			$ta.summernote(buildOptions($form, $ta));
			return true;
		} catch (err) {
			console.error('[AdminEditor] init failed', ta.id, err);
			return false;
		}
	}

	window.AdminEditor = {
		sync: function ($form) {
			var $ = $jq();
			if (!$) {
				return;
			}
			findTextareas($form).each(function () {
				var $ta = $(this);
				if (hasEditor($ta)) {
					try {
						$ta.val($ta.summernote('code'));
					} catch (e) {}
				}
			});
		},

		destroyInForm: function ($form) {
			var $ = $jq();
			if (!$) {
				return;
			}
			findTextareas($form).each(function () {
				var $ta = $(this);
				if (hasEditor($ta)) {
					try {
						$ta.summernote('destroy');
					} catch (e) {}
				}
			});
		},

		initInScope: function ($scope, $form) {
			var $ = $jq();
			if (!$ || !$scope || !$scope.length) {
				return;
			}
			$form = $form || $scope.closest('.form');
			findTextareas($scope).each(function () {
				initTextarea(this, $form);
			});
		},

		open: function ($form) {
			var $ = $jq();
			if (!$ || !$form || !$form.length) {
				return;
			}
			this.destroyInForm($form);
			var $scope = $form.find('.tab-pane.active');
			if (!$scope.length) {
				$scope = $form;
			}
			var self = this;
			window.setTimeout(function () {
				self.initInScope($scope, $form);
			}, 100);
		},

		initPane: function ($pane, $form) {
			var self = this;
			window.setTimeout(function () {
				self.initInScope($pane, $form);
			}, 100);
		},

		bootAllForms: function () {
			var $ = $jq();
			if (!$ || !$.fn.summernote) {
				return;
			}
			$('.form').each(function () {
				var $f = $(this);
				if (findTextareas($f).length) {
					window.AdminEditor.open($f);
				}
			});
		}
	};

	function scheduleBoot() {
		window.setTimeout(function () {
			if (window.AdminEditor) {
				window.AdminEditor.bootAllForms();
			}
		}, 150);
	}

	if ($jq()) {
		$jq()(scheduleBoot);
		$jq()(window).on('load', scheduleBoot);
	} else {
		document.addEventListener('DOMContentLoaded', scheduleBoot);
		window.addEventListener('load', scheduleBoot);
	}
})(window);
