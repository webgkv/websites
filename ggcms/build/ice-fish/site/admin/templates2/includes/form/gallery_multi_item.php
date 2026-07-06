<?php if(is_array($_POST)&&count($_POST)) $q=$_POST; ?>

<li class="clearfix" data-i="<?=$q['file']['i']?>" title="для изменения последовательности картинок переместите блок в нужное место">
	<div class="file_item clearfix">
		<div class="img"><span>&nbsp;</span><img src="<?=$q['file']['preview']?>">
			<input name="<?=$q['key']?>[<?=$q['file']['i']?>][id]" type="hidden" value="<?=$q['file']['id']?>" />
		</div>
		<div class="file_parameters">
			<a href="#" class="delete"><i data-feather="trash-2"></i></a>
			<div class="file_name"><?=$q['file']['file']?></div>
<?php
			foreach ($q['fields'] as $fname=>$ftype) {
//				$n = a18n($fname);
				$n = $fname;
				if ($ftype=='checkbox') {
					$checked = (isset($q['file'][$fname]) && $q['file'][$fname] == 1) ? ' checked="checked"' : '';
					?>
					<label><input name="<?=$q['key']?>[<?=$q['file']['i']?>][<?=$fname?>]" type="checkbox" value="1" <?=$checked?> /><span><?=$n?></span></label>
					<?php
				}
				//v1.1.25 - добавил селект для file_multi и simple
				elseif (is_array($ftype)) {
					?>
					<span class="select"><select name="<?=$q['key']?>[<?=$q['file']['i']?>][<?=$fname?>]"><?=select(@$q['file'][$fname],$ftype)?></select></span>
					<?php
				}
				else {
					?>
					<!--input class="input" name="<?=$q['key']?>[<?=$q['file']['i']?>][<?=$fname?>]" value="<?=htmlspecialchars(@$q['file'][$fname])?>" placeholder="<?=$n?>" title="<?=$n?>" /-->
					<?php
				}
			}
?>
		</div>
	</div>
</li>