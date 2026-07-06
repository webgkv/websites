<?php
$rand = rand(100000,999999);
?>
<div class="form-group <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?php if ($q['help']) {?>
		<a href="#" class="sprite question" title="<?=$q['help']?>"></a>
		<?php } ?>
	</label>
	<div class="tinymce-editor-wrap" style="width:100%;max-width:100%;">
		<textarea id="<?=$rand?>" class="admin-wysiwyg" name="<?=$q['key']?>" <?=$q['attr']?> style="width:100%;min-height:300px;"><?=htmlspecialchars((string)$q['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></textarea>
	</div>
	<div class="clear"></div>
</div>