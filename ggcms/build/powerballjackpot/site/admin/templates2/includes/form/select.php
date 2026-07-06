<div class="form-group <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?=html_array('form/help',$q)?>
	</label>

		<select class="form-control" name="<?=$q['key']?>" <?=$q['attr']?>><?=select(
			$q['value'][0],
			isset($q['value'][1]) ? $q['value'][1] : '',
			isset($q['value'][2]) ? $q['value'][2] : NULL
)?></select>
</div>


