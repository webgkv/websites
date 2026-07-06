<div class="files mysql" data-i="<?=$q['key']?>">
	<div class="data">
		<div class="img" data-img="<?=$q['img']?>">
			<span>
				<input name="<?=$q['key']?>" type="hidden" value="<?=$q['file']?>" />
				<img src="<?=$q['preview']?>" />
			</span>
		</div>
		<div class="name"><?=$q['name']?></div>
		<div class="desc">
			<?php if ($q['is_file']) { ?>
			<a href="#" class="sprite delete"></a>
			<?php } ?>
			<div><a href="<?=$q['img']?>" onclick="return hs.expand(this)"><?=$q['file']?></a></div>
			<?php if ($q['message']) {?>
			<div class="message"><?=$q['message']?></div>
			<?php } ?>
		</div>
		<a class="add_file button green" title="Загрузить файл" style="display:none">
			<span><span class="sprite plus"></span>загрузить</span>
		</a><input type="file" name="<?=$q['key']?>" title="выбрать файл" />
		<div class="load"></div>
		<div class="clear"></div>
	</div>
</div>