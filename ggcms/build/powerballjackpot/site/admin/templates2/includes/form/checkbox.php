<?php
$checked = $q['value']==1 ? 'checked="checked"' : '';
?>
<div class="form-group <?=$q['class']?>">
	<div class="form-check">
		<input type="hidden" name="<?=@$q['key']?>" value="0" />
		<label class="form-check-label">
			<input class="form-check-input" type="checkbox" name="<?=@$q['key']?>" value="1" <?=$checked?> <?=$q['attr']?> />
			<span><?=$q['name']?></span>
			<?=html_array('form/help',$q)?>
		</label>
	</div>
</div>