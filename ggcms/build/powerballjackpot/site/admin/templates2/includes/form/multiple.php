<div class="form-group <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?=html_array('form/help',$q)?>
	</label>
		<select multiple class="select2" name="<?=$q['key']?>[]" <?=$q['attr']?>><?=select(
			explode(',',$q['value'][0]),
			isset($q['value'][1]) ? $q['value'][1] : '',
			isset($q['value'][2]) ? $q['value'][2] : NULL
)?></select>
</div>


