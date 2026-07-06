<div class="form-group col-xl-12 files <?=$q['type']?>" data-i="<?=$q['key']?>">
	<div class="data">
		<div class="img" data-img="<?=$q['img']?>" title="Move picture to this area">
			<?php
			// Legacy uploads: bare filename in files/{table}/{id}/img/ — admin thumb is a-{file}.
			// Media library / assets / images/games paths: use full image (no a- sibling).
			$file_val = isset($q['file']) ? (string)$q['file'] : '';
			$preview_src = (string)$q['img'];
			if ($file_val !== '' && strpos($file_val, '/') === false && strpos($preview_src, 'no_img') === false) {
				$preview_src = preg_replace('#/([^/]+\.[^/]+)$#', '/a-$1', $preview_src);
			}
			?>
			<img src="<?=htmlspecialchars($preview_src, ENT_QUOTES, 'UTF-8')?>" alt="" /><span>&nbsp;</span><input name="<?=$q['key']?>" type="hidden" value="<?=htmlspecialchars($file_val, ENT_QUOTES, 'UTF-8')?>" />
		</div>
		<div class="name"><?=$q['name']?></div>
		<div class="desc">
		<?php
		if ($q['is_file']) {
			?>
			<a href="#" class="delete"><i data-feather="trash-2"></i></a>
			<div><a href="<?=$q['img']?>" class="image-popup"><?=$q['file']?></a></div>
			<?php
		}
		?>
		</div>
		<a class="add_file btn btn-outline-secondary" title="Select a file">
			select
			<input type="file" title="select a file" />
		</a>
		<button type="button" class="btn btn-outline-primary btn-sm ml-2 js-media-pick-file" data-key="<?=$q['key']?>" title="Choose from Media Library"><i data-feather="image" class="mr-1"></i>Media</button>
	</div>
</div>
