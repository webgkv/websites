<?php
$parameters = isset($q['value']) ? unserialize($q['value']) : array();
$shop_parameters = mysql_select("SELECT id,name FROM shop_parameters ORDER BY rank DESC",'array');
foreach ($parameters as $k=>$v) {
	if (array_key_exists($k,$shop_parameters)) {
		$parameters[$k]['name'] = $shop_parameters[$k];
		unset($shop_parameters[$k]);
	}
	else unset($parameters[$k]);
}
foreach ($shop_parameters as $k=>$v) $parameters[$k] = array('name'=>$v);
?>

<div class="col-xl-12">
	Параметры добавляются и редактируются в разделе <a href="?m=shop_parameters" xmlns="http://www.w3.org/1999/html"><u>параметры</u></a>
	<br />
	Здесь настраивается только сортировка и отображдение параметров на сайте
	<br />
</div>
<?php /*
	<div style="width:150px; float:left">&nbsp</div>
	<div style="width:100px; float:left">в фильтре поиска <a href="#" title="показывать поле поиска по параметру на сайте в фильтре поиска товаров" class="sprite question"></a></div>
	<div style="width:100px; float:left">на странице товара <a href="#" title="показывать параметр на странице товара" class="sprite question"></a></div>
	<div style="width:100px; float:left">показывать <a href="#" title="включить/отключить показ везде" class="sprite question"></a></div>
</div>
<ul class="sortable">
<?php
foreach ($parameters as $k=>$v) {
	?>
		<li title="для изменения сортировки переместите в нужное место и сохраните">
			<div style="width:150px; float:left"><?=$v['name']?></div>
			<?php foreach (array('filter','product','display') as $k1=>$v1) {?>
			<div style="width:100px; float:left">
				<input type="checkbox" name="parameters[<?=$k?>][<?=$v1?>]" <?=@$parameters[$k][$v1]?'checked':''?>/>
			</div>
			<?php } ?>
		</li>
	<?php
}
?>
</ul>
*/?>
<table class="table">
	<thead>
		<th>&nbsp</th>
		<th>в фильтре поиска <a href="#" title="показывать поле поиска по параметру на сайте в фильтре поиска товаров" class="sprite question"></a></th>
		<th>на странице товара <a href="#" title="показывать параметр на странице товара" class="sprite question"></a></th>
		<th>показывать <a href="#" title="включить/отключить показ везде" class="sprite question"></a></th>
	</thead>
	<tbody class="sortable">
	<?php
	foreach ($parameters as $k=>$v) {
		?>
		<tr style= "width:600px!important;" title="для изменения сортировки переместите в нужное место и сохраните">
			<td><?=$v['name']?></td>
			<?php foreach (array('filter','product','display') as $k1=>$v1) {?>
				<td>
					<input type="checkbox" name="parameters[<?=$k?>][<?=$v1?>]" <?=@$parameters[$k][$v1]?'checked':''?>/>
				</td>
			<?php } ?>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>