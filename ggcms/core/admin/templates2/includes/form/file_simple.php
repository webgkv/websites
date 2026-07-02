<div class="files simple" data-i="<?=$q['key']?>">
	<div class="name"><span><?=$q['name']?></span></div>
	<input type="file" name="<?=$q['key']?>[]" multiple="multiple" title="выбрать файл" />
	<div class="load"></div>
	<div class="file"></div>
	<div class="clear"></div>
	<?php if ($q['message']) {?>
	<div class="message"><?=$q['message']?></div>
	<?php } ?>
	<ul class="sortable">
	<?php
	if ($q['photos']) foreach ($q['photos'] as $n=>$v) {
		$img = get_img($q['module'],$q['item'],$q['key'].'/'.$n);
		$explode = explode('.',$v['file']);
		$exc = end($explode);
		if (in_array($exc,array('png','gif','svg','jpg','jpeg','bmp'))) {
			$preview =  '/_imgs/100x100'.$img;
		}
		else {
			$preview = '/admin/templates/icons/blank.png';
			if (in_array($exc,array('sql','txt','doc','docx')))	$preview = '/admin/templates/icons/doc.png';
			elseif (in_array($exc,array('xls','xlsx')))		$preview = '/admin/templates/icons/xls.png';
			elseif (in_array($exc,array('pdf')))			$preview = '/admin/templates/icons/pdf.png';
			elseif (in_array($exc,array('zip','rar')))		$preview = '/admin/templates/icons/zip.png';
		}
		?>
		<li data-i="<?=$n?>" title="для изменения последовательности картинок переместите блок в нужное место">
			<div class="img"><span><img src="<?=$preview?>" /></span></div>
			<a href="#" class="sprite delete"></a>
			<div><a
						onclick="hs.expand(this);return false;"
						href="<?=$img?>"
						alt="<?=$v['file']?>"><?=$v['file']?></a></div>
			<input name="<?=$q['key']?>[<?=$n?>][file]" type="hidden" value="<?=$v['file']?>" />
			<?php
			foreach ($q['fields'] as $fname => $ftype) {
				$pname = a18n($fname);
				if ($ftype=='checkbox') {
					$checked = (isset($v[$fname]) && $v[$fname] == 1) ? ' checked="checked"' : '';
					?>
					<br /><label><input name="<?=$q['key']?>[<?=$n?>][<?=$fname?>]" type="checkbox" value="1"<?=$checked?> /><span><?=$pname?></span></label>
					<?php
				}
				//v1.1.25 - добавил селект для file_multi и simple
				elseif (is_array($ftype)) {
					?>
					<br /><span class="select"><select name="<?=$q['key']?>[<?=$n?>][<?=$fname?>]"><?=select(@$v[$fname],$ftype)?></select></span>
					<?php
				}
				else {
					?>
				<input class="input" name="<?=$q['key']?>[<?=$n?>][<?=$fname?>]" value="<?=htmlspecialchars(@$v[$fname])?>" placeholder="<?=$pname?>" title="<?=$pname?>" />
					<?php
				}
			}
			?>
			</li>
		<?php
		}
		?>

	</ul>
<div class="clear"></div>
</div>