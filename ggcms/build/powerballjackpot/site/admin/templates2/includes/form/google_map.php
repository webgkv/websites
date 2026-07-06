<?php
//v1.2.73
$rand = rand(100000,999999);
//v1.2.77
$post = $q['value'];
?>
<div class="field <?=$q['class']?> form-row">
	<?=form('select td6 google_map_search col-12',$q['key'],array(
		'name'=>'Поиск по карте',
		'value'=>array(@$post[$q['key']],array())
	))?>
	<?=form('input td3 lat','lat')?>
	<?=form('input td3 lng','lng')?>
	<div class="clear"></div>
	<div
			class="google_map_box"
			id="<?=$rand?>"
			data-lat="<?=@$post['lat']?>"
			data-lng="<?=@$post['lng']?>"
			data-lat_default="<?=$config['map_lat']?>"
			data-lng_default="<?=$config['map_lng']?>"
	></div>
</div>
