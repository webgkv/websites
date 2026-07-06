<?php
//v1.2.70
$rand = rand(100000,999999);
//v1.2.77
$post = $q['value'];
?>
<div class="field <?=$q['class']?>">
	<?php
	echo form('input td4 yandex_map_search',$q['key'],@$post[$q['key']],array('name'=>'Поиск по карте'));
	?>
	<div class="field input td2"><br><a href="#" class="yandex_map_button">Поиск</a></div>
	<?=form('input td3 lat','lat',@$post['lat'])?>
	<?=form('input td3 lng','lng',@$post['lng'])?>
	<div class="clear"></div>
	<div
			class="yandex_map_box"
			id="<?=$rand?>"
			data-lat="<?=@$post['lat']?>"
			data-lng="<?=@$post['lng']?>"
			data-lat_default="<?=$config['map_lat']?>"
			data-lng_default="<?=$config['map_lng']?>"
	></div>
</div>
