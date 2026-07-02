<?php //print_r($q);
?>

<div class="form-group files col-xl-12 file_multi <?=$q['type']?>" data-i="<?=$q['key']?>">
	<div class="name"><?=$q['name']?></div>
	<ul class="sortable clearfix">
	<?php
	if ($q['photos']) foreach ($q['photos'] as $k=>$v) {
		if ($q['type']=='file_multi') {
//			$img = get_img('gallery', $q['item'], $q['key'] . '/' . $k);
			$img = get_img('gallery', $q['item'], 'img');
		}
		//file_multi_db
		else {
			$img = get_img($q['module'], $v, 'img');
		}
		if (@$v['file']) {
			$explode = explode('.',$v['file']);
			$exc = end($explode);
			if (in_array($exc,array('png','gif','svg','jpg','jpeg','bmp','webp'))) {
//				$preview =  '/_imgs/100x1001'.$img;
				$preview =  '/files/gallery/'.$v['id'].'/img/a-'.$v['file'];
			}
			else {
				$preview = '/admin/templates/icons/blank.png';
				if (in_array($exc,array('sql','txt','doc','docx')))	$preview = '/admin/templates/icons/doc.png';
				elseif (in_array($exc,array('xls','xlsx')))		$preview = '/admin/templates/icons/xls.png';
				elseif (in_array($exc,array('pdf')))			$preview = '/admin/templates/icons/pdf.png';
				elseif (in_array($exc,array('zip','rar')))		$preview = '/admin/templates/icons/zip.png';
			}
			$q['file'] = $v;
			$q['file']['i'] = $k;
			$q['file']['preview'] = $preview;
			$q['file']['img'] = $img;
			echo html_render('form/gallery_multi_item',$q);
		}
	}
	?>
	</ul>
	<div class="data">
		<a class="add_file btn btn-outline-secondary open" href="/admin.php?u=gallery&multi=1&id=<?=$q['key']?>">
			select from gallery
		</a>
<!--
		<a class="add_file" title="Select files">
			Drag and drop files here or click to download
			<input type="file" multiple="multiple" title="select file" />
		</a>
-->
	</div>
	<template>
		<?=html_render('form/gallery_multi_item',array(
			'file'=>array(
				'i'=>'{i}',
				'display'=>1,
				'img'=>'{img}',
				'preview'=>'{preview}',
				'file'=>'{file}',
			),
			'key'=>$q['key'],
			'fields'=>$q['fields']
		))?>
	</template>
</div>