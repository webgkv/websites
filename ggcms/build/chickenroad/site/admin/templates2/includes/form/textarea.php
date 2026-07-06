<div class="form-group <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?=html_array('form/help',$q)?>
	</label>
	<textarea class="form-control" cols="1" rows="1" name="<?=$q['key']?>" <?=$q['attr']?>><?=htmlspecialchars($q['value'])?></textarea>
</div>