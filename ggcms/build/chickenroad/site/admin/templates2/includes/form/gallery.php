<?php //print_r($q);
?>
<div class="form-group col-xl-12 files file <?=$q['class']?>" data-i="<?=$q['key']?>">
	<div class="data">
		<div class="img">
			<img id="<?=$q['key']?>-img" src="<?=preg_replace('#/([^/]+\.[^/]+)$#','/a-$1',$q['img'])?>" /><span>&nbsp;</span><input name="<?=$q['key']?>" type="hidden" value="<?=$q['value']?>" />
		</div>
		<!--div class="name"><?=$row1['img']?></div-->
		<div class="desc">
			<a href="#" class="delete"><i data-feather="trash-2"></i></a>
			<div id="<?=$q['key']?>-name" class="name"><a href="<?=$q['img']?>" class="image-popup"><?=$q['name']?></a></div>
			<div id="<?=$q['key']?>-alt" class="alt"><?=$q['alt']?></div>
			<div id="<?=$q['key']?>-title" class="title"><?=$q['title']?></div>

		</div>
		<a class="add_file btn btn-outline-secondary open" href="/admin.php?u=gallery&id=<?=$q['key']?>">
			select from gallery
		</a>
	</div>
</div>