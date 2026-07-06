<?php
//dd($q['value']);
$post = $q['value'];
$parameters = @$post ? unserialize($post):array();
//print_r ($parameters);
?>

<div class="table-responsive">
	<table class="table product_list">
		<tr data-i="0">
			<th>ID</th>
			<th>название</th>
			<th>показывать</th>
		</tr>
		<?php
		for ($i=1; $i<11; $i++) {
			$checked = @$parameters[$i]['display'] ? 'checked':'';
			?>
			<tr>
				<td><?=$i?></td>
				<td style="width: 50%"><input class="form-control" style="width:90%;" name="parameters[<?=$i?>][name]" value="<?=htmlspecialchars(@$parameters[$i]['name'])?>" /></td>
				<td>
					<input type="hidden" name="parameters[<?=$i?>][display]" value="0" />
					<input style="width: 25px; height: 25px" type="checkbox" name="parameters[<?=$i?>][display]" value="1" <?=$checked?> />
				</td>
			</tr>
			<?php
		}
		?>
	</table>
</div>