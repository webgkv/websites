/*
 v1.4.4 - html_array for table
 v1.4.7 - admin/template2
 v1.4.18 - api_response
 v1.4.24 - error fix
 */

// v1.4.18 - API response handling
function api_response(response) {
	//console.log('2');
	if (response.data) {
		response.data.forEach(function (item) {
			if (item.method == 'append') {
				$(item.selector).append(item.content);
			}
			else if (item.method == 'show') {
				$(item.selector).show();
			}
			else if (item.method == 'hide') {
				$(item.selector).hide();
			}
			else if (item.method == 'remove') {
				$(item.selector).remove();
			}
			else if (item.method == 'html') {
				$(item.selector).html(item.content);
			}
			else if (item.method == 'text') {
				$(item.selector).text(item.content);
			}
			else if (item.method == 'replaceWith') {
				$(item.selector).replaceWith(item.content);
			}
			else if (item.method == 'alert') {
				alert(item.content);
			}
			// redirect
			else if (item.method == 'location') {
				window.location.href = item.content;
			}
			// reload
			else if (item.method == 'reload') {
				window.location.reload();
			}
			// modal; replace with your own if not Bootstrap
			else if (item.method == 'modal') {
				$(item.selector).modal();
			}
			else if (item.method == 'scroll') {
				// scroll
				$('html, body').animate({
					scrollTop: $(item.selector).offset().top
				}, 1000);
			}
			// run arbitrary script
			else if (item.method == 'script') {
				$('body').append(item.content);
			}
			else {
				alert(item.method);
			}
		});
	}
	// todo error code, show in popup
	if (response.error_text) {
		alert(response.error_text);
	}
	else {
		if (response._error) alert(response._error);
	}
}


$(document).ready(function(){
	var table = $('table.table'),
		doc = $(this);

	// Override Bootstrap modal enforceFocus globally to prevent focus conflict with TinyMCE native dialogs
	if ($.fn.modal && $.fn.modal.Constructor) {
		$.fn.modal.Constructor.prototype.enforceFocus = function () {};
	}
	$(document).on('focusin', function (e) {
		if ($(e.target).closest(".mce-window, .mce-container, .tox-tinymce-aux").length) {
			e.stopImmediatePropagation();
		}
	});

	// v1.4.7 - admin/template2 - saved/error
	toastr.options = {
		timeOut: 3000,
		progressBar: true,
		showMethod: "slideDown",
		hideMethod: "slideUp",
		showDuration: 200,
		hideDuration: 200,
		positionClass: "toast-top-left"
	};

	//tooltip
	$('td a.edit',table).attr('title','edit entry');
	$('td a.delete',table).attr('title','delete entry');
	$('td span.level',table).attr('title','click and drag to the desired location to move');
	$('td span.sorting',table).attr('title','click and drag to the desired location to move');
	$('td a.js_display',table).attr('title','show/hide on site');
	$('td.post',table).attr('title','double click to edit');
	$('td img.img',table).attr('title','view picture');

	init();

	// v1.4.7 - admin/template2
	function init() {
		// v1.4.7 - admin/template2 tooltip
		try { $('[data-toggle="tooltip"]').tooltip({container:"body"}); } catch (e) {}

		// sortable
		try {
			if ($.fn.sortable) {
				$('.sortable').sortable();
			}
		} catch (e) {}

		// v1.4.7 - admin/template2 - replace icons
		try { if (typeof feather !== 'undefined') feather.replace(); } catch (e) {}

		// v1.4.7 - admin/template2
		try {
			if ($.fn.select2) {
				$('.select2').select2({
					//placeholder: 'Select'
				});
			}
		} catch (e) {}
		// v1.4.7 - admin/template2
		$('.clockpicker').clockpicker({
			donetext: 'Done',
			autoclose: true
		});
		//v1.4.7 - admin/template2
		$('.datepicker input').daterangepicker({
			singleDatePicker: true,
			showDropdowns: true,
			locale: {
				format: 'YYYY-MM-DD',
				"applyLabel": "Принять",
				"cancelLabel": "Отклонить",
				"fromLabel": "От",
				"toLabel": "До",
				"customRangeLabel": "Custom",
				"daysOfWeek": [
					"Вс",
					"Пн",
					"Вт",
					"Ср",
					"Чт",
					"Пт",
					"Сб"
				],
				"monthNames": [
					"Январь",
					"Февраль",
					"Март",
					"Апрель",
					"Май",
					"Июнь",
					"Июль",
					"Август",
					"Сентябрь",
					"Октябрь",
					"Ноябрь",
					"Декабрь"
				],
				"firstDay": 1
			}
		});
		$('.datepicker .clear').click(function(){
			var input = $(this).closest('.datepicker').find('input');
			if ($(input).data('daterangepicker')) {
				$(input).data('daterangepicker').remove();
			}
			$(input).val('');
			$(this).hide();
			return false;
		});
		//v1.4.7 - admin/template2
		$('.datetimepicker input').daterangepicker({
			timePicker: true,
			timePicker24Hour: true,
			timePickerSeconds: true,
			singleDatePicker: true,
			//startDate: moment().startOf('hour'),
			//endDate: moment().startOf('hour').add(32, 'hour'),
			locale: {
				format: 'YYYY-MM-DD HH:mm:ss',
				"applyLabel": "Принять",
				"cancelLabel": "Отклонить",
				"fromLabel": "От",
				"toLabel": "До",
				"customRangeLabel": "Custom",
				"daysOfWeek": [
					"Вс",
					"Пн",
					"Вт",
					"Ср",
					"Чт",
					"Пт",
					"Сб"
				],
				"monthNames": [
					"Январь",
					"Февраль",
					"Март",
					"Апрель",
					"Май",
					"Июнь",
					"Июль",
					"Август",
					"Сентябрь",
					"Октябрь",
					"Ноябрь",
					"Декабрь"
				],
				"firstDay": 1
			}
		});
		//v1.4.7 - admin/template2
		$('.image-popup').magnificPopup({
			type: 'image',
			zoom: {
				enabled: true,
				duration: 300,
				easing: 'ease-in-out',
				opener: function(openerElement) {
					return openerElement.is('img') ? openerElement : openerElement.find('img');
				}
			}
		});
	}

	// table row operations
	// header checkbox click
	$(table).on('change','tr th.table_checkbox input[type=checkbox]',function(){
		var checked = $(this).prop('checked');
		$('tr td.table_checkbox input[type=checkbox]',table).prop('checked',checked);
		table_check();
	});
	// row checkbox click
	$(table).on('change','tr td.table_checkbox input[type=checkbox]',function(){
		table_check();
	});
	// count checked checkboxes
	function table_check () {
		var ids = [];
		$('tr td.table_checkbox input[type=checkbox]:checked',table).each(function(){
			var id = $(this).val();
			ids.push(id);
		});
		// set all ids comma-separated
		$('.table_check input[name="_check[ids]"]').val(ids);
	}


	// nested_sets change in form
	$(document).on('change','.form select[name^="nested_sets"]',function(){
		var s = $(this),
			form = s.closest('.form');
		if (s.attr('name')=='nested_sets[parent]') {
			var parent = this.value || 0;
			$('select[name="nested_sets[previous]"]',form).html('<option value="0">At the end of the list</option>').append(s.find('option[data-parent='+parent+']').clone());
		}
		$('input[name="nested_sets[on]"]',form).val(1);
		return false;
	});

	// show SEO fields
	$(document).on('click','.seo-optimization a',function(){
		$(this).parent('div').next('div').slideToggle('fast');
		return false;
	});

	//multicheckbox
	$(document).on('change','.multicheckbox input',function(){
		$(this).closest('li').find('input').prop('checked',this.checked);
	});

	function adminResolveTableModule($tbl) {
		var m = $tbl.attr('data-module') || $tbl.data('module') || '';
		var params = new URLSearchParams(window.location.search || '');
		var tab = params.get('tab') || '';
		if (m === 'casinos') {
			m = 'casino_articles';
		} else if ((!m || m === 'content') && tab) {
			if (tab === 'casinos') {
				m = 'casino_articles';
			} else if (tab === 'blog') {
				m = 'blog';
			} else {
				m = tab;
			}
		}
		return m;
	}

	// open edit form
	$(document).on('click','.table .open',function(e){
		e.preventDefault();
		e.stopPropagation();
		// close modal
		$('#window').modal('hide');
		var opener = $(this),
			$tbl = opener.closest('table.table'),
			m = adminResolveTableModule($tbl.length ? $tbl : table),
			tr = opener.closest('tr'),
			id = tr.data('id'),
			url = opener.attr('href') || '';

		// Prefer server-built href (admin_edit_form_url) when present.
		if (url.indexOf('u=form') !== -1) {
			window.location.href = url;
			return false;
		}

		// Settings embed (iframe): open Users/Roles editor as an inline page inside iframe,
		// not as a nested modal (avoids duplicate UI/backdrops).
		try {
			if ($('body').hasClass('embed-admin') && (m === 'users' || m === 'user_types')) {
				window.location.href = '/admin.php?m=' + encodeURIComponent(m) + '&u=form&id=' + encodeURIComponent(id) + '&inline=1&embed=1';
				return false;
			}
		} catch (e) {}

		// For pages + Content modules, open editor as a normal page (not modal).
		if (m === 'pages' || m === 'guides' || m === 'games' || m === 'casino_articles' || m === 'blog') {
			var params = new URLSearchParams(window.location.search || '');
			var i18n_lang_id = params.get('i18n_lang_id');
			if (m === 'pages') {
				var ptab = params.get('tab');
				var target = '/admin.php?m=pages&u=form&id=' + encodeURIComponent(id) + '&inline=1';
				if (ptab) target += '&tab=' + encodeURIComponent(ptab);
				if (i18n_lang_id) target += '&i18n_lang_id=' + encodeURIComponent(i18n_lang_id);
				window.location.href = target;
			} else {
				var tab = params.get('tab') || (m === 'casino_articles' ? 'casinos' : m);
				var stab = params.get('stab');
				var target = '/admin.php?m=content&tab=' + encodeURIComponent(tab) + '&u=form&id=' + encodeURIComponent(id) + '&inline=1';
				if (stab) target += '&stab=' + encodeURIComponent(stab);
				if (i18n_lang_id) target += '&i18n_lang_id=' + encodeURIComponent(i18n_lang_id);
				window.location.href = target;
			}
			return false;
		}
		// highlight row
		$('.is_open',table).removeClass('is_open');
		$(tr,table).addClass('is_open');
		// v1.4.20 - remove highlight from header row
		$('thead tr',table).removeClass('is_open');

		$.get(
			url,
			{'m':m,'u':'form','id':id},
			function(data){ //alert(data);
				$(data).appendTo('body').find('.form').trigger('form.open');
				// open modal
				$('#window').modal();
			}
		);
		return false;
	});

	// i18n language switch in Translations tab: inside modal replace form content via AJAX (keep modal open); else full page navigation
	$(document).on('change', '.js-i18n-lang-switch', function () {
		var $sel = $(this);
		var baseUrl = $sel.data('base-url');
		if (!baseUrl) return;
		var url = baseUrl + '&i18n_lang_id=' + encodeURIComponent($sel.val());
		var $win = $('#window');
		var inModal = $win.length && $win.find($sel).length;
		if (inModal) {
			$win.find('.modal-footer button').prop('disabled', true);
			if (typeof adminRemoveTinymceInForm === 'function') {
				adminRemoveTinymceInForm($win.find('.form'));
			}
			fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (r) { return r.text(); })
				.then(function (html) {
					var $new = $(html);
					// replace modal content in place so modal stays open and backdrop is unchanged
					$win.find('.modal-content').html($new.find('.modal-content').html());
					$win.find('.form').trigger('form.open');
				})
				.catch(function () { alert('Error loading form'); })
				.finally(function () {
					if ($('#window').length) $('#window').find('.modal-footer button').prop('disabled', false);
				});
		} else {
			window.location.href = url;
		}
	});

	// Import JSON for current language (Translations tab): submit via fetch; in modal stay in modal by reloading form content
	$(document).on('click', '.js-import-i18n-submit, .js-import-page-i18n-submit', function () {
		var $block = $(this).closest('.js-import-i18n, .js-import-page-i18n');
		var action = $block.data('action');
		var $file = $block.find('input[name="json_file"]');
		if (!action || !$file.length) return;
		if (!$file[0].files || !$file[0].files[0]) {
			alert('Please select a JSON file.');
			return;
		}
		var fd = new FormData();
		$block.find('input[type="hidden"]').each(function () {
			fd.append(this.name, this.value);
		});
		fd.append('json_file', $file[0].files[0]);
		var $btn = $(this);
		var inModal = $('#window').length && $('#window').find($block).length;
		$btn.prop('disabled', true);
		fetch(action, {
			method: 'POST',
			body: fd,
			headers: inModal ? { 'X-Requested-With': 'XMLHttpRequest' } : {}
		})
			.then(function (r) {
				var ct = r.headers.get('Content-Type') || '';
				if (ct.indexOf('application/json') !== -1) {
					return r.json().then(function (data) {
						if (data.ok && data.form_url && inModal) {
							return fetch(data.form_url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
								.then(function (fr) { return fr.text(); })
								.then(function (html) {
									var $new = $(html);
									var $win = $('#window');
									if (typeof adminRemoveTinymceInForm === 'function') {
										adminRemoveTinymceInForm($win.find('.form'));
									}
									$win.find('.modal-content').html($new.find('.modal-content').html());
									$win.find('.form').trigger('form.open');
									$win.find('.modal-body').scrollTop(0);
								});
						}
						if (data.ok && data.form_url) {
							window.location.href = data.form_url;
						} else if (!data.ok && data.message) {
							alert(data.message);
						}
					});
				}
				if (r.redirected && r.url) {
					window.location.href = r.url;
				}
			})
			.catch(function () {
				alert('Import request failed.');
			})
			.finally(function () {
				$block.find('.js-import-i18n-submit, .js-import-page-i18n-submit').prop('disabled', false);
			});
	});

	// close modal (form)
	$(document).on('hide.bs.modal','#window',function(e){
		console.log('hide.bs.modal');
		// if form was changed
		if ($('#window .form').data('changed') && !window.__adminMediaPickerOpen) {
			e.preventDefault();
			if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
			swal({
				title: "You haven't saved your data!",
				text: "When closing the form, the entered data will be lost!",
				icon: "warning",
				//buttons: true,
				buttons: ["Cancel", "OK"],
				dangerMode: true
			}).then((willDelete) => {
				if (willDelete) {
					console.log('closeForm');
					// clear changed flag
					$('#window .form').data('changed',false);
					// close
					$('#window').modal('hide');
				}
			});
			return false;
		}
		else if (typeof adminRemoveTinymceInForm === 'function') {
			adminRemoveTinymceInForm($('#window .form'));
		}
	// after modal closed
	}).on('hidden.bs.modal', '#window',function () {
		// remove modal
		$('#window').remove();
	});

	function adminNormalizeMediaImgPath(out) {
		if (!out) {
			return '';
		}
		if (out.path) {
			return String(out.path).replace(/^\/+/, '');
		}
		var u = String(out.url || '');
		u = u.replace(/^https?:\/\/[^\/]+/i, '');
		return u.replace(/^\/+/, '');
	}

	function adminMediaFormContext($form) {
		var $f = ($form && $form.length) ? $form : $('.form').first();
		return {
			entity: String($f.data('mediaEntity') || ''),
			entity_id: parseInt($f.data('mediaEntityId'), 10) || 0
		};
	}

	function adminOpenMediaLibrary($form, callback) {
		if (window.MediaLibraryPicker && typeof window.MediaLibraryPicker.open === 'function') {
			window.MediaLibraryPicker.open(adminMediaFormContext($form), callback);
			return;
		}
		alert('Media library is not loaded. Refresh the page.');
	}

	function adminBuildMediaImgHtml(data) {
		var alt = String(data.alt || '').replace(/"/g, '&quot;');
		var title = String(data.title || '').replace(/"/g, '&quot;');
		var cls = 'img-fluid';
		if (data.imgClass) {
			cls += ' ' + String(data.imgClass).replace(/"/g, '');
		}
		var attrs = ' src="' + data.url + '" alt="' + alt + '" class="' + cls + '"';
		if (title) {
			attrs += ' title="' + title + '"';
		}
		if (data.width) {
			attrs += ' width="' + parseInt(data.width, 10) + '"';
		}
		if (data.height) {
			attrs += ' height="' + parseInt(data.height, 10) + '"';
		}
		if (data.responsive && !data.width) {
			attrs += ' style="max-width:100%;height:auto;"';
		}
		return '<img' + attrs + ' />';
	}

	function adminGetSelectedImage(ed) {
		if (!ed) {
			return null;
		}
		var img = ed.selection.getNode();
		if (img && img.nodeName !== 'IMG') {
			img = ed.dom.getParent(img, 'img');
		}
		if (!img || img.nodeName !== 'IMG' || img.getAttribute('data-mce-object') || img.getAttribute('data-mce-placeholder')) {
			return null;
		}
		return img;
	}

	function adminFindImageByEditId(ed, imgId) {
		if (!ed || !imgId || !ed.getBody) {
			return null;
		}
		var body = ed.getBody();
		if (!body) {
			return null;
		}
		return body.querySelector('img[data-admin-img-edit="' + imgId + '"]');
	}

	function adminResolveImageForEditButton(ed) {
		if (!ed) {
			return null;
		}
		var img = adminGetSelectedImage(ed);
		if (img) {
			return img;
		}
		if (ed._selectedImg) {
			return ed._selectedImg;
		}
		if (ed._adminEditImgId) {
			return adminFindImageByEditId(ed, ed._adminEditImgId);
		}
		return null;
	}

	function adminOpenImageDialog(ed, img) {
		if (!ed) {
			return;
		}
		if (!img) {
			img = adminResolveImageForEditButton(ed);
		}
		if (!img) {
			alert('Please click on the image in the editor first, then click "Edit".');
			return;
		}

		// Remove any existing overlay first
		$('#custom-image-edit-overlay').remove();

		var currentAlt = img.getAttribute('alt') || '';
		var currentWidth = img.getAttribute('width') || '';
		var currentHeight = img.getAttribute('height') || '';
		var currentClass = img.getAttribute('class') || 'img-fluid';

		var html = 
			'<div id="custom-image-edit-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999999; display:flex; align-items:center; justify-content:center;">' +
			'	<div style="background:white; padding:25px; border-radius:8px; width:400px; box-shadow:0 4px 15px rgba(0,0,0,0.3); font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif; box-sizing:border-box;">' +
			'		<h4 style="margin-top:0; margin-bottom:20px; font-size:18px; font-weight:bold; color:#333;">Edit Image</h4>' +
			'		<div style="margin-bottom:15px;">' +
			'			<label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#555;">Alternative description (Alt text):</label>' +
			'			<input type="text" id="img-edit-alt" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-size:14px;" />' +
			'		</div>' +
			'		<div style="display:flex; gap:15px; margin-bottom:15px;">' +
			'			<div style="flex:1;">' +
			'				<label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#555;">Width (px):</label>' +
			'				<input type="text" id="img-edit-width" placeholder="e.g., 600" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-size:14px;" />' +
			'			</div>' +
			'			<div style="flex:1;">' +
			'				<label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#555;">Height (px):</label>' +
			'				<input type="text" id="img-edit-height" placeholder="auto" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-size:14px;" />' +
			'			</div>' +
			'		</div>' +
			'		<div style="margin-bottom:20px;">' +
			'			<label style="display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:#555;">CSS class:</label>' +
			'			<input type="text" id="img-edit-class" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-size:14px;" />' +
			'		</div>' +
			'		<div style="display:flex; justify-content:flex-end; gap:10px;">' +
			'			<button type="button" id="img-edit-cancel" style="padding:8px 16px; border:1px solid #ccc; background:#f5f5f5; border-radius:4px; cursor:pointer; font-size:14px; color:#333;">Cancel</button>' +
			'			<button type="button" id="img-edit-save" style="padding:8px 16px; border:none; background:#1e7e34; color:white; border-radius:4px; cursor:pointer; font-size:14px; font-weight:bold;">Save</button>' +
			'		</div>' +
			'	</div>' +
			'</div>';

		$('body').append(html);

		// Populate inputs with current image attributes
		$('#img-edit-alt').val(currentAlt);
		$('#img-edit-width').val(currentWidth);
		$('#img-edit-height').val(currentHeight);
		$('#img-edit-class').val(currentClass);

		// Focus the Alt text input first
		$('#img-edit-alt').focus();

		// Bind event handlers
		$('#img-edit-cancel').on('click', function () {
			$('#custom-image-edit-overlay').remove();
		});

		$('#img-edit-save').on('click', function () {
			var newAlt = $('#img-edit-alt').val();
			var newWidth = $('#img-edit-width').val();
			var newHeight = $('#img-edit-height').val();
			var newClass = $('#img-edit-class').val();

			img.setAttribute('alt', newAlt);
			
			if (newWidth.trim()) {
				img.setAttribute('width', newWidth.trim());
			} else {
				img.removeAttribute('width');
			}
			
			if (newHeight.trim()) {
				img.setAttribute('height', newHeight.trim());
			} else {
				img.removeAttribute('height');
			}
			
			if (newClass.trim()) {
				img.setAttribute('class', newClass.trim());
			} else {
				img.removeAttribute('class');
			}

			ed.fire('change');
			ed.nodeChanged();

			var $f = $(ed.getContainer()).closest('.form');
			if ($f.length) {
				$f.data('changed', true);
			}

			$('#custom-image-edit-overlay').remove();
		});

		// Close on Escape key press
		$(document).on('keyup.imgedit', function (e) {
			if (e.key === 'Escape') {
				$('#custom-image-edit-overlay').remove();
				$(document).off('keyup.imgedit');
			}
		});
	}

	function adminBindImageEditing(ed) {
		// Disabled double-click to prevent event conflicts, as requested by user.
	}

	function adminTinymceImageOptions($form) {
		return {
			object_resizing: true,
			image_advtab: true,
			image_dimensions: true,
			image_description: true,
			image_title: true,
			contextmenu: 'link inserttable | cell row column deletetable',
			image_class_list: [
				{title: 'Default (responsive)', value: 'img-fluid'},
				{title: 'Centered', value: 'img-fluid d-block mx-auto'},
				{title: 'Full width', value: 'img-fluid w-100'},
				{title: 'No extra class', value: ''}
			],
			file_picker_types: 'image',
			file_picker_callback: function (callback, value, meta) {
				if (!meta || meta.filetype !== 'image') {
					return;
				}
				var ed = tinymce.activeEditor;
				var $f = ed ? $(ed.getContainer()).closest('.form') : $form;
				adminOpenMediaLibrary($f, function (data) {
					callback(data.url, {alt: data.alt || '', title: data.title || ''});
				});
			},
			content_style: 'img { max-width:100%; height:auto; vertical-align:middle; } ' +
				'img[data-mce-selected] { outline:2px solid #3e72c6; outline-offset:2px; }'
		};
	}

	function adminTinymceBaseOptions($form) {
		return $.extend(true, {
			language: 'en',
			plugins: [
				'advlist autolink lists link image charmap anchor',
				'visualblocks code',
				'media table contextmenu paste textcolor',
				'hr'
			],
			browser_spellcheck: true,
			// noads (inline) / noinc (block): content-exclusion wrappers must survive the editor.
			// See functions/content_exclude_tags.php, advertising_api.php, cta_inject.php.
			custom_elements: '~noads,noinc',
			valid_children: '+p[noads],+body[noinc],+noads[a|span|strong|em|b|i|u|#text],+noinc[p|div|ul|ol|li|a|span|strong|em|h2|h3|h4|br|#text]',
			extended_valid_elements: 'div[itemtype|itemscope|itemprop|style|class|id],span[itemtype|itemscope|itemprop|style|class],@[itemtype|itemscope|itemprop|id|class|style|title|dir<ltr?rtl|lang|xml::lang|onclick|ondblclick|onmousedown|onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|onkeyup],hr[id|title|alt|class|width|size|noshade|style],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],a[id|class|name|href|target|title|onclick|rel|style],script[type|src],noads[id|class|style|title],noinc[id|class|style|title]',
			toolbar1: 'bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | styleselect fontselect fontsizeselect | bullist numlist ',
			toolbar2: 'undo redo | hr | style-h2 style-h3 | link unlink anchor medialibrary editimage media code | table | removeformat | subscript superscript | charmap emoticons | visualchars visualblocks nonbreaking | outdent indent blockquote',
			menubar: false,
			content_css: '/templates/css/tinymce.css?',
			relative_urls: false,
			setup: function (ed) {
				adminBindImageEditing(ed);
				ed.on('click', function (e) {
					var img = e.target.nodeName === 'IMG' ? e.target : null;
					if (img) {
						ed._selectedImg = img;
						var iid = img.getAttribute('data-admin-img-edit');
						if (!iid) {
							iid = 'aie-' + String(Date.now());
							img.setAttribute('data-admin-img-edit', iid);
						}
						ed._adminEditImgId = iid;
						try {
							ed._adminEditBookmark = ed.selection.getBookmark(2, true);
						} catch (errBm) {}
					}
				});
				ed.on('NodeChange', function (e) {
					var img = e.element && e.element.nodeName === 'IMG' ? e.element : null;
					if (img) {
						ed._selectedImg = img;
						var iid = img.getAttribute('data-admin-img-edit');
						if (!iid) {
							iid = 'aie-' + String(Date.now());
							img.setAttribute('data-admin-img-edit', iid);
						}
						ed._adminEditImgId = iid;
						try {
							ed._adminEditBookmark = ed.selection.getBookmark(2, true);
						} catch (errBm) {}
					}
				});
				ed.addButton('medialibrary', {
					icon: 'browse',
					classes: 'medialibrary-btn',
					tooltip: 'Insert image from media library',
					onclick: function () {
						var $f = $(ed.getContainer()).closest('.form');
						adminOpenMediaLibrary($f, function (data) {
							var html = adminBuildMediaImgHtml(data);
							ed.insertContent(html);
							ed.fire('change');
							var imgs = ed.dom.select('img'), pick = null, u = data.url, i;
							for (i = imgs.length - 1; i >= 0; i--) {
								if (imgs[i].getAttribute('src') === u) {
									pick = imgs[i];
									break;
								}
							}
							if (pick) {
								ed.selection.select(pick);
							}
						});
					}
				});
				ed.addButton('editimage', {
					icon: 'resize',
					text: 'Edit',
					classes: 'editimage-btn',
					tooltip: 'Edit image: alt text, size, CSS class',
					onclick: function () {
						var img = adminResolveImageForEditButton(ed);
						adminOpenImageDialog(ed, img);
					}
				});
				ed.on('change', function () {
					var $f = $(ed.getContainer()).closest('.form');
					if ($f.length) {
						$f.data('changed', true);
					}
				});
			}
		}, adminTinymceImageOptions($form));
	}

	function adminTextareaInVisibleTab(ta) {
		var $pane = $(ta).closest('.tab-pane');
		if (!$pane.length) {
			return true;
		}
		return $pane.hasClass('show') && $pane.hasClass('active');
	}

	/** Init TinyMCE only for textareas inside $scope (visible tab). Hidden tabs init on shown.bs.tab. */
	function adminInitTinymceInScope($scope, $form) {
		if (typeof tinymce === 'undefined') {
			console.warn('TinyMCE not loaded');
			return;
		}
		$form = $form || $scope.closest('.form');
		if (!$scope || !$scope.length) {
			return;
		}
		$scope.find('.tinymce textarea').each(function (idx) {
			var ta = this;
			if (!adminTextareaInVisibleTab(ta)) {
				return;
			}
			if (!ta.id) {
				ta.id = 'tinymce_' + ($form.attr('id') || 'form') + '_' + idx + '_' + String(Date.now());
			}
			if (tinymce.get(ta.id)) {
				return;
			}
			var h = parseInt(ta.style.height, 10) || 500;
			try {
				tinymce.init($.extend(true, adminTinymceBaseOptions($form), {
					selector: '#' + ta.id,
					width: '100%',
					height: h,
					init_instance_callback: function (ed) {
						window.setTimeout(function () {
							try {
								if (ed.theme && typeof ed.theme.resizeTo === 'function') {
									var $wrap = $(ed.getContainer()).closest('.tinymce-editor-wrap, .tinymce, .form-group');
									var w = $wrap.length ? $wrap.innerWidth() : $(ed.getContainer()).parent().innerWidth();
									if (w && w > 100) {
										ed.theme.resizeTo(w, h);
									}
								}
							} catch (errR) {}
						}, 80);
					}
				}));
			} catch (errTmc) {
				console.error('TinyMCE init failed for', ta.id, errTmc);
			}
		});
	}

	function adminRemoveTinymceInForm($form) {
		if (typeof tinymce === 'undefined') {
			return;
		}
		$form.find('.tinymce textarea').each(function () {
			if (this.id) {
				var ed = tinymce.get(this.id);
				if (ed) {
					ed.remove();
				}
			}
		});
	}

	// form open
	$(document).on('form.open','.form',function(){
		//v1.4.7 - admin/template2
		// Always reset "changed" flag when form is (re)opened (e.g. after browser Back).
		// Otherwise hide.bs.modal may warn even though latest save already happened.
		var $currentForm = $(this);
		$currentForm.data('changed', false);
		init();

		$('a.delete',this).attr('title','delete');

		if (typeof adminInitTinymceInScope === 'function') {
			adminInitTinymceInScope($currentForm);
		}
		// open tab; does not work on Bootstrap
		if (location.hash) {
			//$('.nav-tabs a.nav-link[href='+location.hash+']',this).triggerHandler('click');
		}

		// v1.2.70 - Yandex map
		if ($('.yandex_map_box',this).length>0) {
			ymaps.ready(yandex_map);
		}
		//v1.2.72 - google карта
		if ($('.google_map_box',this).length>0) {
			google_map();
			//v1.4.7 - admin/template2
			$('.google_map_search select').select2({
				//placeholder: 'Enter address',
				language: "ru",
				ajax: {
					url: "/api/google_autocomplete/",
					type: "get",
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							search: params.term
						};
					},
					processResults: function (response) {
						return {
							results: response.list
						};
					},
					cache: true
				}
			});
			//меняем город
			$('.google_map_search select').on("select2:selecting", function(e) {
				console.log(e.params.args.data.text);
			});
		}

	// form data change handler
	}).on('input','.form',function() {
		$(this).data('changed', true);
		console.log('form.input');
	});

	// v1.4.x - intercept inline submits inside the form (e.g. "Save translation")
	// Otherwise the browser navigates to admin/actions/edit.php which returns JSON inside <textarea>,
	// appearing as a "raw window" in the UI.
	// For reliability we also remember which i18n button was clicked.
	$(document).off('click._i18nBtn').on('click._i18nBtn','button[name="i18n_save"], input[name="i18n_save"]',function(){
		var $f = $(this).closest('form.form');
		if ($f && $f.length) $f.data('i18n_last_action','save');
	});
	$(document).off('click._i18nClearBtn').on('click._i18nClearBtn','button[name="i18n_clear"], input[name="i18n_clear"]',function(){
		var $f = $(this).closest('form.form');
		if ($f && $f.length) $f.data('i18n_last_action','clear');
	});

	$(document).on('submit','.form',function(e){
		// Skip this native submit interceptor when the event is triggered
		// programmatically via .trigger('form.submit') — otherwise it can
		// interfere with the ajaxSubmit pipeline in the 'form.submit' handler.
		if (e.isTrigger) return;
		var $form = $(this);
		var $btn = $(document.activeElement);
		var submitter = (e && e.originalEvent && e.originalEvent.submitter) ? $(e.originalEvent.submitter) : null;
		var $submitter = submitter && submitter.length ? submitter : $btn;
		var editDebugOn = (window.location.search.indexOf('edit_debug=1') !== -1);
		var lastAction = $form.data('i18n_last_action') || '';
		var $saveBtn = $submitter && $submitter.length ? $submitter.closest('button[name="i18n_save"], input[name="i18n_save"]') : $();
		var $clearBtn = $submitter && $submitter.length ? $submitter.closest('button[name="i18n_clear"], input[name="i18n_clear"]') : $();

		if (lastAction === 'save' || $saveBtn.length > 0) {
			e.preventDefault();
			var $realBtn = ($saveBtn && $saveBtn.length ? $saveBtn : $btn);
			var submitVal = ($realBtn.val() !== undefined && $realBtn.val() !== null) ? String($realBtn.val()) : ($realBtn.attr('value') ? String($realBtn.attr('value')) : '1');
			// ajaxSubmit can omit the submitter control when we intercept native submit.
			// Force i18n_save into POST so backend always receives it.
			var $forced = $('input[name="i18n_save"][data-forced="1"]', this);
			var $dbgForced = null;
			if (editDebugOn) {
				$dbgForced = $('input[name="edit_debug"][data-forced="1"]', this);
				if (!$dbgForced.length) {
					$dbgForced = $('<input type="hidden" name="edit_debug" data-forced="1">').appendTo(this);
				}
				$dbgForced.val('1');
			}
			if (!$forced.length) {
				$forced = $('<input type="hidden" name="i18n_save" data-forced="1">').appendTo(this);
			}
			$forced.val(submitVal);

			// Trigger existing ajax submit pipeline (keeps modal open).
			$(this).trigger('form.submit', [{ 'yep': false, 'sa': false }]);
			// Cleanup after ajaxSubmit call is dispatched.
			setTimeout(function(){
				$forced.remove();
				if ($dbgForced) $dbgForced.remove();
				$form.data('i18n_last_action','');
			}, 0);
			return false;
		}
		// Same intercept pipeline for "Del translate" inside i18n forms.
		if (lastAction === 'clear' || $clearBtn.length > 0) {
			e.preventDefault();
			var $realBtn = ($clearBtn && $clearBtn.length ? $clearBtn : $btn);
			var submitVal = ($realBtn.val() !== undefined && $realBtn.val() !== null) ? String($realBtn.val()) : ($realBtn.attr('value') ? String($realBtn.attr('value')) : '1');
			// Force i18n_clear into POST so backend always receives it.
			var $forced = $('input[name="i18n_clear"][data-forced="1"]', this);
		var $dbgForced = null;
		if (editDebugOn) {
			$dbgForced = $('input[name="edit_debug"][data-forced="1"]', this);
			if (!$dbgForced.length) {
				$dbgForced = $('<input type="hidden" name="edit_debug" data-forced="1">').appendTo(this);
			}
			$dbgForced.val('1');
		}
			if (!$forced.length) {
				$forced = $('<input type="hidden" name="i18n_clear" data-forced="1">').appendTo(this);
			}
			$forced.val(submitVal);

			// Trigger existing ajax submit pipeline (keeps modal open).
			$(this).trigger('form.submit', [{ 'yep': false, 'sa': false }]);
			// Cleanup after ajaxSubmit call is dispatched.
		setTimeout(function(){
			$forced.remove();
			if ($dbgForced) $dbgForced.remove();
				$form.data('i18n_last_action','');
		}, 0);
			return false;
		}
	// edit form submit
	}).on('form.submit','.form',function(e,close){
		close = close || { 'yep': false, 'sa': false };
		var form = $(this).trigger('form.disable'),
			// Prefer hidden input "id" value; fallback to numeric suffix of form id (form123)
			idVal = form.find('input[name=\"id\"]').val(),
			id = idVal ? idVal : (String(form.prop('id') || '').replace(/^form/, '')),
			url = form.attr('action');
		if (close.sa) $('input[name*="nested_sets"]',form).val(1); // account nesting on Save As
		
		// update textarea content before submit safely (TinyMCE)
		form.find('.tinymce textarea').each(function(){
			var id = $(this).attr('id');
			var ed = id && typeof tinymce !== 'undefined' ? tinymce.get(id) : null;
			if (ed) {
				try {
					$(this).val(ed.getContent());
				} catch (err) {
					console.log('tinymce.getContent failed', err);
					try {
						if (ed.getBody) {
							var body = ed.getBody();
							if (body && typeof body.innerHTML === 'string') {
								$(this).val(body.innerHTML);
							}
						}
					} catch (e2) {}
				}
			}
		});
		form.ajaxSubmit({
			iframe:		true,
			url			: url+'&id='+(close.sa ? 'new&save_as='+id : id), // simulate new record on Save As
			dataType:	'json',
			success:	function (data){
				// If server returned <textarea>{"error":0,...}</textarea>, plugin may pass string — extract JSON
				if (typeof data === 'string') {
					var m = data.match(/<textarea[^>]*>([\s\S]*?)<\/textarea>/i);
					if (m) try { data = JSON.parse(m[1]); } catch (e) { data = null; }
					else try { data = JSON.parse(data); } catch (e) { data = null; }
				}
				if (data) { //alert(data);
					var saveOk = (data.error === 0 || data.error === '0' || Number(data.error) === 0);
					// import single casino: server returns redirect, go there (full page)
					if (data.redirect) {
						// Prevent "unsaved data" warning: redirect happens before normal success branch
						form.data('changed', false);
						$('#window .form').data('changed', false);
						window.location.href = data.redirect;
						return;
					}
					// refresh uploaded files
					if (data.files) {
						for (var key in data.files) form.find('.files[data-i="'+key+'"]').replaceWith(data.files[key]);
						$('.files.simple ul').sortable();
					}
					// generate SEO fields
					if (data.seo) {
						form.find('input[name="seo"]').prop('checked',false);
						for (var i in data.seo) form.find('input[name="'+i+'"]').val(data.seo[i]);
					}
					// success (accept 0 as number or string — iframe/JSON quirks)
					if (saveOk) {
						form.data('changed', false);
						$('#window .form').data('changed', false);
						// v1.4.7 - admin/template2
						toastr.success('changes successfully saved');
						if (data.debug || data.debug_received) {
							swal({
								title: 'i18n debug',
								text: JSON.stringify(data.debug ? data.debug : data.debug_received, null, 2),
								icon: 'info',
								buttons: { ok: 'OK' }
							});
						}
						// replace full table when tree
						if (data.table) {
							$('#table').replaceWith(data.table);
						}
						// replace single row
						else if (data.tr) {
							// update
							if (id > 0 && !close.sa) {
								$('tr[data-id=' + id + ']', table).replaceWith(data.tr);
							}
							// add new
							else {
								table.append(data.tr);
								form.attr('id', 'form' + data.id).find('span[data-name="id"]').text(data.id);
							}
						}
						if (close && close.yep) {
							//form.trigger('form.close');
							$('#window').modal('hide');
						}
						//v1.4.7 - admin/template2
						init();
					}
					// request error
					else {
						// v1.4.7 - admin/template2
						toastr.error(data.error);
						if (data.debug || data.debug_received) {
							swal({
								title: 'i18n debug',
								text: JSON.stringify(data.debug ? data.debug : data.debug_received, null, 2),
								icon: 'error',
								buttons: { ok: 'OK' }
							});
						}
						// v1.4.20 - commented out unused code
						//form.find('.button input').removeAttr('disabled').parent(".button").removeClass('disabled');
					}
				}
				else alert('form submission error');
				// v1.4.20 - enable form
				form.trigger('form.enable');
			},
			error:	function(xhr,txt,err){
				alert('Error ('+txt+(err&&err.message ? '/'+err.message : '')+')');
				// v1.4.20 - enable form
				form.trigger('form.enable');
			}
		});

	// disable form submit
	}).on('form.disable','.form',function(){
		console.log('form.disable');
		$(this).find('.modal-footer button').prop('disabled',true);
		// Also disable inline save button (full-page editor)
		$(this).find('.js-inline-save').prop('disabled',true).html('<i class="spinner-border spinner-border-sm mr-1" role="status"></i> Saving...');

	// enable form submit
	}).on('form.enable','.form',function(){
		console.log('form.enable');
		$(this).find('.modal-footer button').prop('disabled',false);
		// Also re-enable inline save button
		$(this).find('.js-inline-save').prop('disabled',false).text(typeof a18n_save_label !== 'undefined' ? a18n_save_label : 'Save');

	// form submit button click
	}).on('click','.form .modal-footer button',function() {
		var submit = $(this),
			close = {
				'yep': submit.hasClass('close_form'),
				'sa': submit.hasClass('save_as')
			};
		!submit.prop('disabled') && submit.closest('form').trigger('form.submit',[close]);
		return false;
	// inline full-page save button (not modal footer)
	}).on('click', '.form .js-inline-save', function() {
		var submit = $(this);
		!submit.prop('disabled') && submit.closest('form').trigger('form.submit', [{'yep': false, 'sa': false}]);
		return false;
	});

	// Lazy-init TinyMCE when user switches to a tab that was hidden on load (Translations, etc.)
	$(document).on('shown.bs.tab', '.form .nav-tabs a[data-toggle="tab"]', function (e) {
		var href = $(e.target).attr('href');
		if (!href || href.charAt(0) !== '#') {
			return;
		}
		var $pane = $(href);
		var $form = $(e.target).closest('.form');
		if ($pane.length && $form.length && typeof adminInitTinymceInScope === 'function') {
			adminInitTinymceInScope($pane, $form);
		}
	});

	// hypertext focus loss (legacy TinyMCE inline fields, if any)
	$(document).on('focusout','.hypertext_html',function(){
		if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
			try {
				$(this).next().val(tinymce.activeEditor.getContent());
			} catch (e) {}
		}
	});
	// v1.4.24 workaround for TinyMCE, Bootstrap modal steals focus
	$(document).on('click','.mce-textbox',function(){
		$(this).focus();
	});

	// if form loaded with page (inline editor only — not hidden modal shells)
	$('.form').filter(function () {
		return $(this).find('.tinymce textarea').length > 0;
	}).trigger('form.open');


	// QUICK EDIT ===================================================
	table.on('dblclick','td.post',function(){
		sendRequest = true;
		var td = $(this);
		if (!td.has('input').length) {
			var m = table.data('module'),
				id = td.parent('tr').data('id'),
				width = td.width(),
				name = td.data('name'),
				value = td.html();
			var i = td.width(width).html('<input type="text" value="'+value.replace(/["]/g,'&quot;')+'" />').find('input').focus().width(width-6).data('value',value).get(0);
			i.setSelectionRange && i.setSelectionRange(0,value.length);
		}
	// keydown
	}).on('keydown','td input',function(e) {
		var i = $(this);
		// Enter or Tab
		if (e.which==13 || e.which==9) {
			sendRequest = false;
			e.preventDefault();
			var td = i.closest('td'),
				tr = td.closest('tr'),
				eq = td.index(),
				next;
			switch (e.which) {
				case 9:
					next = e.shiftKey ? td.prevAll('.post').first() : td.nextAll('.post').first();
					if (next.length == 0) next = e.shiftKey ? tr.prev().find('.post').last() : tr.next().find('.post').first();
					break;
				case 13:
					next = e.shiftKey ? tr.prev().children().eq(eq) : tr.next().children().eq(eq);
					break;
			}
			applyChanges(i);
			next.trigger('dblclick');
			return false;
		//Esc
		} else if (e.keyCode==27) {
			sendRequest = false;
			e.preventDefault();
			i.closest('td').html(i.data('value')).width('auto');
			return false;
		}
	//потеря фокуса инпутом
	}).on('blur','td input[type=text]',function() {
		console.log('td input');
		if (sendRequest) applyChanges($(this));
	// edit in select
	}).on('dblclick','td.select',function(){
		var i = $(this),
			id = i.data('id'),
			name = i.data('name'),
			value = i.text(),
			width = i.closest('td').width(),
			select = table.find('th select[name="'+name+':"]').val(id).html();
		i.html('<select>'+select+ '</select>');
		i.find('select').val(id);
		//i.find('select').triggerHandler('click');
	}).on('change','td select',function() {
		var i = $(this),
			m = table.data('module'),
			name = i.closest('td').data('name'),
			id = i.closest('tr').data('id'),
			value = i.val(),
			str = i.find('option:selected').text();
		i.closest('td').html(str).data('id',value);
		$.get(
			'/admin.php',
			{'m':m,'u':'post','id':id,'name':name,'value':value},
			function(data) {
				//показываем ошибку
				if (data) {
					alert(data);
				}
			}
		);
	});

	// v1.2.70 - Yandex map init
	function yandex_map() {
		$.each($('.yandex_map_box'), function (i, el) {
			map_box = $(this).closest('.yandex_map');
			map_id = $(el).attr('id');
			map_lat = $(el).data('lat');
			map_lng = $(el).data('lng');
			map_lat_default = $(el).data('lat_default');
			map_lng_default = $(el).data('lng_default');
			var uluru = map_lat ? [map_lat, map_lng] : [map_lat_default, map_lng_default];
			myMap = new ymaps.Map(map_id, {
				center: uluru, //[47.271975074248026,39.69305799999998],
				zoom: 16,
				controls: []
			});

			myMap.behaviors.disable('scrollZoom');

			myMap.controls.add("zoomControl", {
				position: {top: 15, left: 15}
			});
			// add point to map
			if (map_lat) {
				// create point
				myPlacemark = new ymaps.Placemark(uluru, {
					//preset: 'islands#icon',
					//iconColor: '#0095b6'
				});
				myMap.geoObjects.add(myPlacemark);
			}
			// move point
			myMap.events.add("click", function (e) {
				$('.lat input',map_box).val(e.get("coords")[0]);
				$('.lng input',map_box).val(e.get("coords")[1]);
				if (typeof myPlacemark == 'undefined') {
					// create point
					myPlacemark = new ymaps.Placemark(uluru, {
						//preset: 'islands#icon',
						//iconColor: '#0095b6'
					});
					myMap.geoObjects.add(myPlacemark);
				}
				myPlacemark.geometry.setCoordinates(e.get("coords"));
			});
			// map search
			$(this).closest('.yandex_map').find('.yandex_map_button').click(function() {
				var str = $(this).closest('.yandex_map').find('.yandex_map_search input').val();
				var myGeocoder = ymaps.geocode(str);
				myGeocoder.then(
					function (res) {
						// alert('Object coordinates: ' + res.geoObjects.get(0).geometry.getCoordinates());
						$('.lat input',map_box).val(res.geoObjects.get(0).geometry.getCoordinates()[0]);
						$('.lng input',map_box).val(res.geoObjects.get(0).geometry.getCoordinates()[1]);
						myMap.panTo(res.geoObjects.get(0).geometry.getCoordinates(), {duration: 1000});
						// create point
						if (typeof myPlacemark == 'undefined') {
							myPlacemark = new ymaps.Placemark(uluru, {
								//preset: 'islands#icon',
								//iconColor: '#0095b6'
							});
							myMap.geoObjects.add(myPlacemark);
						}
						myPlacemark.geometry.setCoordinates(res.geoObjects.get(0).geometry.getCoordinates());
					},
					function (err) {
						alert('Error');
					}
				);
				return false;
			});
		});
	}

	// v1.2.73 - Google map init
	function google_map() {
		$.each($('.google_map_box'), function (i, el) {
			map_box = $(this).closest('.google_map');
			map_id = $(el).attr('id');
			map_lat = $(el).data('lat');
			map_lng = $(el).data('lng');
			map_lat_default = $(el).data('lat_default');
			map_lng_default = $(el).data('lng_default');
			var uluru = map_lat ? {lat: map_lat, lng: map_lng} : {lat: map_lat_default, lng: map_lng_default} ;
			var map = new google.maps.Map(document.getElementById(map_id), {
				zoom: 15,
				center: uluru
			});
			// markers array
			markersArray = [];
			// if coords exist set cursor
			if (map_lat) {
				var marker = new google.maps.Marker({position: uluru, map: map});
				//marker.setMap(map);
				markersArray.push(marker);
			}
			// map click
			google.maps.event.addListener(map, 'click', function(event) {
				// clear all markers
				for (i in markersArray) markersArray[i].setMap(null);
				markersArray.length = 0;
				// add marker at new coords
				var marker = new google.maps.Marker({
					position:event.latLng,
					map:map
				});
				markersArray.push(marker);
				// put data on page
				$('.lat input',map_box).val(event.latLng.lat);
				$('.lng input',map_box).val(event.latLng.lng);
			});
			// map search
			//$(this).closest('.google_map').find('.google_map_button').click(function() {
			//	var str = $(this).closest('.google_map').find('.google_map_search input').val();
			$(this).closest('.google_map').find('.google_map_search select').change(function() {
				var str = $(this).val();
				//alert(str);
				geocoder = new google.maps.Geocoder();
				geocoder.geocode( { 'address': str}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						// clear all markers
						for (i in markersArray) markersArray[i].setMap(null);
						// center map on new coords
						map.setCenter(results[0].geometry.location);
						// add marker at new coords
						var marker = new google.maps.Marker({
							position: results[0].geometry.location,
							map: map
						});
						markersArray.push(marker);
						// put data on page
						$('.lat input',map_box).val(results[0].geometry.location.lat);
						$('.lng input',map_box).val(results[0].geometry.location.lng);
					}
					else {
						alert('Geocode was not successful for the following reason: ' + status);
					}
				});
				return false;
			});
		});
	}

	// apply quick-edit changes
	function applyChanges(i) {
		var td = i.closest('td'),
			m = table.data('module'),
			name = td.data('name'),
			id = td.closest('tr').data('id'),
			value = i.val(),
			oldVal = i.data('value');
		td.html(value).width('auto');
		if (value!=oldVal) {
			$.get(
				'/admin.php',
				{'m':m,'u':'post','id':id,'name':name,'value':value},
				function(data) {
					//показываем ошибку
					if (data) {
						td.html(oldVal);
						alert(data);
					}
				}
			);
		}
	}

	// TOGGLES ============================================================
	table.on('change','.js_boolean',function(){
		var a = $(this),
			m = table.data('module'),
			id = a.closest('tr').data('id'),
			name = a.closest('td').data('name'),
			value = $(a).prop('checked') ? 1:0,
			wasChecked = !value;
		$.get('/admin.php', {'m':m,'u':'post','id':id,'name':name,'value':value}, function (data) {
			if (data && String(data).replace(/\s+/g, '').length > 0) {
				a.prop('checked', wasChecked);
				alert(data);
			}
		}).fail(function () {
			a.prop('checked', wasChecked);
			alert('Could not save. Check your permissions or try again.');
		});
	});

	// Enter or Esc key handler
	/*
	doc.on('keydown',function(e){
		if (e.keyCode==13 || e.keyCode==27) {
			// Esc with form open
			if ($('.form').length) {
				//Esc
				if (e.keyCode==27) {
					$('.form').trigger('form.close');
				}
			}
		}
	});
	*/

	// DELETE id
	// v1.4.7 - admin/template2
	table.on('click','tr td .delete',function(){
		swal({
			title: "Are you sure?",
			text: "Information cannot be restored!",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		})
			.then((willDelete) => {
			if (willDelete) {
				var tr = $(this).closest('tr'),
					m = table.data('module'),
					id = tr.data('id'),
					path = '/admin.php?u=delete&type=id&m='+m+'&id='+id;
				$.getJSON(path, {},
					function (data) {
						if (data.error_text) {
							// v1.4.24 - error fix
							swal("ERROR", data.error_text, "error");
						}
						else {
							if (data.success) $(tr).remove();
							else {
								swal("ERROR", 'unknown error', "error");
							}
							/*swal("Poof! Item has been deleted!", {
								icon: "success",
							});*/
						}
					}
				);
			}
		});
		return false;
	});



	// SEARCH ====================================================================
	$('#filter .sprite.search').click(function(){
		var url = $(this).attr('href'),
			wrap = $(this).closest('#filter'),
			search = $(this).parent('div').find('input[name=search]').blur().val();
		search = encodeURIComponent(search);
		// Additional content filters (e.g. separate ID field).
		var searchId = '';
		if (wrap && wrap.length) {
			searchId = $.trim(wrap.find('input[name=search_id]').val() || '');
		}
		var target = url + search;
		if (searchId !== '') {
			target += '&search_id=' + encodeURIComponent(searchId);
		}
		top.location = target;
		return false;
	});
	// Search buttons in modules that render custom filter fields.
	$('#filter').on('click', 'button[type=submit]', function(e){
		e.preventDefault();
		$('#filter .sprite.search').first().trigger('click');
		return false;
	});
	// submit on Enter
	$('#filter input[name=search]').keyup(function(e){
		if (e.which==13) {
			$(this).next('a').trigger('click');
		}
	});
	$('#filter input[name=search_id]').keyup(function(e){
		if (e.which==13) {
			$('#filter .sprite.search').first().trigger('click');
		}
	});

	// v1.4.9 template insert
	// add row to current table
	$(document).on("click",".js_table_plus",function(){
		var i = $(this).parents("table").find("tr:last").data("i"),
			template = $(this).data('template');
		i++;
		var content = $(template).html();
		content = content.replace(/{i}/g,i);
		content = content.replace(/{[\w]*}/g,"");
		$(this).parents("table").append(content);
		return false;
	});
	// remove row from current table
	$(document).on("click",".js_table_del",function(){
		$(this).parents("tr").remove();
		return false;
	});

	// Full language pack import (Languages modal): submit via fetch + redirect
	$(document).on('click', '.js-langpack-import-btn', function () {
		var $wrap = $(this).closest('.js-langpack-import');
		if (!$wrap.length) return;
		var action = $wrap.data('action');
		var $file = $wrap.find('.js-langpack-file');
		if (!action || !$file.length) return;
		if (!$file[0].files || !$file[0].files[0]) {
			alert('Please select a JSON file.');
			return;
		}
		var fd = new FormData();
		fd.append('json_file', $file[0].files[0]);
		var btn = this;
		btn.disabled = true;
		fetch(action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) {
				var ct = (r.headers.get('content-type') || '').toLowerCase();
				if (ct.indexOf('application/json') !== -1) return r.json();
				return r.text().then(function (t) { throw new Error(t || 'Bad response'); });
			})
			.then(function (data) {
				if (data && data.redirect) {
					window.location.href = data.redirect;
					return;
				}
				alert((data && data.message) ? data.message : 'Import failed');
			})
			.catch(function (e) {
				alert('Import failed');
				try { console.log(e); } catch (e2) {}
			})
			.finally(function () { btn.disabled = false; });
	});

	$(document).on("click",".add_file",function(){
		gimg=0;
		if($(this).prop('href')) {
			$('body').append('<div id="gallery"></div>');
			$('#gallery').load($(this).prop('href'),function(){
				$('#window').hide();
			});
			return false;
		}
	});
	$(document).on("click","#galleryclose",function(){
		$('#gallery').remove();
		$('#window').show();
		return false;
	});
	$(document).on("click",".gimg",function(){
		$(".gimg").removeClass("gimg_active");
		$(this).addClass("gimg_active");
		return false;
	});

	// Main image: "Choose from Media Library" button
	$(document).on('click', '.js-media-pick-file', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var fieldKey = $btn.data('key');
		var $wrap = $btn.closest('.files');
		if (typeof window.MediaLibraryPicker === 'undefined') {
			alert('Media library is not available');
			return;
		}
		var $form = $wrap.closest('.form');
		window.MediaLibraryPicker.open(
			{
				entity: String($form.data('mediaEntity') || ''),
				entity_id: parseInt($form.data('mediaEntityId'), 10) || 0
			},
			function (out) {
			if (!out || (!out.url && !out.path)) return;
			var relPath = adminNormalizeMediaImgPath(out);
			if (!relPath) return;
			var $hidden = $wrap.find('input[name="' + fieldKey + '"]');
			if ($hidden.length) {
				$hidden.val(relPath);
			}
			var previewUrl = out.url || ('/' + relPath);
			var $img = $wrap.find('.img img');
			if ($img.length) {
				$img.attr('src', previewUrl);
			}
			// Update file name label
			var $name = $wrap.find('.name');
			if ($name.length) {
				$name.text(relPath.split('/').pop());
			}
			// Mark form as changed
			$wrap.closest('form.form').data('changed', true);
			// Show toastr feedback
			if (typeof toastr !== 'undefined') {
				toastr.info('Image selected from media library. Click Save to apply.');
			}
		}, { zIndex: 10700 });
	});

	// ----- Dynamic slugification and sanitization -----
	function slugify(text) {
		var cyrillicMap = {
			'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd', 'е': 'e', 'ё': 'yo', 'ж': 'zh',
			'з': 'z', 'и': 'i', 'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
			'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts',
			'ч': 'ch', 'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '', 'э': 'e', 'ю': 'yu', 'я': 'ya',
			'є': 'ye', 'і': 'i', 'ї': 'yi', 'ґ': 'g'
		};
		text = text.toString();
		if (text.indexOf('|') !== -1) {
			text = text.split('|')[0];
		}
		text = text.toLowerCase();
		var result = '';
		for (var i = 0; i < text.length; i++) {
			var char = text.charAt(i);
			result += cyrillicMap[char] !== undefined ? cyrillicMap[char] : char;
		}
		// Replace all non-alphanumeric characters, underscores, spaces, dots, colons, etc. with hyphens
		result = result.replace(/[^a-z0-9]+/g, '-');
		// Trim leading/trailing hyphens and multiple hyphens
		result = result.replace(/^-+|-+$/g, '').replace(/-+/g, '-');
		return result;
	}

	// 1. Dynamic translit from Name to Slug
	$(document).on('input', 'input[name="i18n_name"]', function() {
		var $form = $(this).closest('form');
		var $urlInput = $form.find('input[name="i18n_url"]');
		if ($urlInput.length) {
			var isManual = $urlInput.data('manual') === 'true';
			if (!isManual || $urlInput.val() === '') {
				$urlInput.val(slugify($(this).val()));
			}
		}
	});

	$(document).on('input', 'input[name="name"]', function() {
		var $form = $(this).closest('form');
		var $urlInput = $form.find('input[name="url"]');
		if ($urlInput.length) {
			var isManual = $urlInput.data('manual') === 'true';
			if (!isManual || $urlInput.val() === '') {
				$urlInput.val(slugify($(this).val()));
			}
		}
	});

	// 2. Format manual slug entries on blur / change / input & track manual edits
	$(document).on('input change', 'input[name="i18n_url"], input[name="url"]', function() {
		var val = $(this).val();
		if (val.trim() === '') {
			$(this).data('manual', 'false');
		} else {
			$(this).data('manual', 'true');
		}
		var cleaned = slugify(val);
		if (val !== cleaned) {
			$(this).val(cleaned);
		}
	});

	$(document).on('blur', 'input[name="i18n_url"], input[name="url"]', function() {
		var val = $(this).val();
		var cleaned = slugify(val);
		if (val !== cleaned) {
			$(this).val(cleaned);
		}
	});

});
