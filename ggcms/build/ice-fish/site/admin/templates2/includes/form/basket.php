<?php
//dd($q['value']);
$post = $q['value'];
$basket = @$post['basket'] ? unserialize($post['basket']):array();
//print_r ($basket);
?>

<div class="table-responsive">
	<table class="table product_list">
		<tr data-i="0">
			<th>ID</th>
			<th >название</th>
			<th>количество</th>
			<th>цена</th>
			<th><a href="#" data-template="#basket_product" class="js_table_plus"><i data-feather="plus-square"></i></a></th>
		</tr>
		<?php
		if (@$basket['products']) foreach ($basket['products'] as $key => $val) {
			$val['i'] = $key;
			echo html_array('common/basket_product', $val);
		}
		?>
	</table>
</div>
<?=form('select td4', 'basket[delivery][type]', array(
	'value'=>array(@$basket['delivery']['type'], "SELECT od.id,od.name FROM order_deliveries od WHERE display = 1 ORDER BY od.rank"),
	'name' => 'доставка'
))?>
<?=form('input td4 right', 'basket[delivery][cost]', array(
	'name' => 'стомость доставки',
	'value'=>@$basket['delivery']['cost']
));?>
<?=form('input td4 right', 'total')?>
<?=form('textarea td12', 'basket[text]', array(
	'name' => 'комментарий',
	'value'=>@$basket['text']
));?>


<h2>Данные клиента</h2>
<?=form('input td3', 'email')?>
<?php
if ($fields = mysql_select("SELECT * FROM user_fields WHERE display = 1 ORDER BY rank DESC", 'rows')) {
	foreach ($fields as $q) {
		$values = unserialize($q['values']);
		if (!isset($basket['user'][$q['id']][0])) $basket['user'][$q['id']][0] = '';
		if ($q['type'] == 1) {//input
			echo form('input td3', 'basket[user][' . $q['id'] . '][]', array(
				'name' => $q['name'],
				'value'=>$basket['user'][$q['id']][0]
			));
		}
		//select
		elseif ($q['type'] == 2) {
			echo form('select td3', 'basket[user][' . $q['id'] . '][]', array(
				'name' => $q['name'],
				'value'=>array($basket['user'][$q['id']][0], $values)
			));
		}
		//textarea
		else {
			echo form('textarea td12', 'basket[user][' . $q['id'] . '][]', array(
				'name' => $q['name'],
				'value'=>$basket['user'][$q['id']][0]
			));
		}
	}
}?>

<template id="basket_product">
	<?=html_array('common/basket_product', array(
		'i'=>'{i}'
	))?>
</template>
