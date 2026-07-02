$(document).ready(function(){
	//изменение состояния файлового инпута file
	$(document).on('change','.files.file .add_file input',function(){
		var box = $(this).closest('.data').find('.img');
		//удаляем инпут с подгруженным файлом и заменяем на пустой чтобы картинка не отправлялась при отправке формы
		this.outerHTML = this.outerHTML;
		upload(box,this.files[0]);

	});
	//изменение состояния общего файлового инпута file_multi
	$(document).on('change','.files.file_multi .add_file input, .files.file_multi_db .add_file input',function(){
		upload_multi ($(this).closest('.files'),this.files);

	});

	//удаление file_multi_db
	$(document).on('click','.files.file_multi .delete, .files.file_multi_db .delete, .files.simple .delete',function(){
		$(this).closest('li').remove();
		return false;
	});
	//удаление file_multi
	$(document).on('click','.files.file .delete',function(){
		var box = $(this).closest('.files');
		$('img',box).prop('src','/admin/templates2/imgs/no_img.png');
		$('.img input',box).val('');
		return false;
	});

	//загрузка перемещением file
	$(document).delegate('.files.file .img, .files.file_multi .img, .files.file_multi_db .img',{
		dragenter: function() {
			//$(this).addClass('highlighted');
			return false;
		},
		dragover: function() {
			return false;
		},
		dragleave: function() {
			//$(this).removeClass('highlighted');
			return false;
		},
		drop: function(e) {
			var img = $(this),
				box = img.closest('.files'),
				dt = e.originalEvent.dataTransfer;
			if (box.hasClass('file')) {
				upload(img,dt.files[0]);
			} else {
				upload_multi(box,dt.files);
			}
			return false;
		}
	});
	//загрузка картинки
	function upload(uploadItem,file) {
		console.log(uploadItem);
		if (file) {
			var img = $('img',uploadItem).prop({src:''}),
				reader = new FileReader(),
				//отключаем возможность отправки формы до загрузки всех изображений
				form = uploadItem.addClass('loading').trigger('form.disable');
			$('.progress',uploadItem).remove();
			//var bar = $('<div class="progress" rel="0">загрузка</div>').appendTo(uploadItem);
			var bar = $('<div class="progress"><div class="progress-bar bg-success" role="progressbar" style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">loading</div></div>').appendTo(uploadItem);
			//$(reader).load(function(e){
			reader.onload = function(e) {
				var path = '';
				// Отсеиваем не картинки
				if (!file.type.match(/image.*/)) {
					if (path=='' && file.type.match(/text.*/))	path = '/admin/templates/icons/doc.png';
					if (path=='' && file.type.match(/.*word/))	path = '/admin/templates/icons/doc.png';
					if (path=='' && file.type.match(/.*excel/))	path = '/admin/templates/icons/xls.png';
					if (path=='' && file.type.match(/.*pdf/))	path = '/admin/templates/icons/pdf.png';
					if (path=='' && file.name.match(/.*zip/))	path = '/admin/templates/icons/zip.png';
					if (path=='' && file.name.match(/.*rar/))	path = '/admin/templates/icons/zip.png';
					if (path=='') path = '/admin/templates/icons/blank.png';
				}
				else path = e.target.result;
				//alert(file.type+' '+path);
				img.prop({src:path});
				uploadItem.prop({file: file});
				new uploaderObject({
					file:		file,
					url:		'/api/uploader/',
					fieldName:	'temp',
					onprogress: function(percents) {
						//var value = bar.width() * (percents/100 - 1);
						//bar.attr('rel', percents).text(percents+'%').css('background-position', value+'px center');
						$('.progress-bar',bar).attr('rel', percents).text(percents+'%').width(percents+'px');
					},
					oncomplete: function(done, data) {
						if (done && data) {
							$('input[type="hidden"]',uploadItem).val(data);
							bar.remove();
						}
						else {
							alert(this.lastError.text);
						}
						//убираем с картинки статус загружаемой
						uploadItem.removeClass('loading');
						//проверяем все ли картинки загрузились
						if (uploadItem.closest('form').find('.loading').length==0) {
							uploadItem.trigger('form.enable');
						}
					}
				});
			};
			reader.readAsDataURL(file);
		}
	}
	function upload_multi (box,files) {
		var n = 0,
			ul = $('ul',box),
			key = box.data('i');
		$('li',ul).each(function(){
			var i = $(this).data('i');
			if (i > n) n = i;
		});
		$.each(files, function(i, file) {
			n++;
			var name = file.name.split('.',file.name.split('.').length-1),
				template = $('template',box),
				li = $(template).html();
			li = li.replace(/{i}/g,n);
			li = li.replace(/{file}/g,file.name);
			li = li.replace(/{[\w]*}/g,"");

			$(ul).append(li);
			//v1.4.7 - admin/template2 - заменяем иконки
			feather.replace();


/*
			//console.log($(template).html());

				li = $('<li/>').data('i',n).appendTo(ul),
				img = '<div class="file_item clearfix"><div class="img"><span>&nbsp;</span><img src="" /><input type="hidden" name="'+key+'['+n+'][temp]" /></div>';
			img+='<div class="file_parameters"><a href="#" class="delete"><i data-feather="trash-2"></i></a>';
			img+='<div class="file_name">'+file.name+'</div></div>';
			//<input class="input" name="'+key+'['+n+'][name]" value="'+name+'"/><br/><label><input name="'+key+'['+n+'][display]" type="checkbox" checked="checked" value="1" /><span>показывать</span></label>';
			$(img).appendTo(li);
*/
			var uploadItem = $('li[data-i="'+n+'"] .img');
			console.log($(uploadItem).html());
			upload(uploadItem,file);
		});
		$('.sortable',box).sortable();
		$('.data input',box).replaceWith('<input type="file" multiple="multiple" title="choose file" />');
	}

});