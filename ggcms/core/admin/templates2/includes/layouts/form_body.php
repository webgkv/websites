<?php
if (count($tabs)>0) {
	$active_tab = (isset($get['ftab']) && array_key_exists($get['ftab'], $tabs)) ? $get['ftab'] : ((isset($get['tab']) && array_key_exists($get['tab'], $tabs)) ? $get['tab'] : key($tabs));
	?>
	<ul class="nav nav-tabs mb-3" role="tablist">
		<?php
		foreach ($tabs as $k=>$v) {
			?>
			<li class="nav-item">
				<a class="nav-link <?=($active_tab==$k ? ' active' : '')?>"
				   data-toggle="tab"
				   id="t<?=$k?>-tab"
				   href="#t<?=$k?>"
				   role="tab"
				   aria-controls="t<?=$k?>"
				   aria-selected="<?=($active_tab==$k ? 'true' : 'false')?>"><?=$v?></a>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
}
?>
<?php
if (is_array($form)) {
	if (count($tabs)>0) {
		$active_tab = (isset($get['tab']) && array_key_exists($get['tab'], $tabs)) ? $get['tab'] : key($tabs);
	$active_tab = (isset($get['ftab']) && array_key_exists($get['ftab'], $tabs)) ? $get['ftab'] : $active_tab;
		?>
		<div class="tab-content">
		<?php
		foreach ($tabs as $k=>$v) if (isset($form[$k]) && is_array($form[$k])) {
			$is_active = ($active_tab == $k);
			?>
			<div class="tab-pane fade <?=$is_active ? 'show active' : ''?>" id="t<?=$k?>" role="tabpanel" aria-labelledby="t<?=$k?>-tab">
				<div class="form-row">
				<?php
			foreach ($form[$k] as $k2=>$v2) {
				if (is_array($v2)) echo call_user_func_array(preg_match('/mysql|simple|file|file_multi|gallery|gallery_multi/',$v2[0]) ? 'form_file' : 'form', $v2);
				else {
					if ($v2=='clear') echo '<div class="clear"></div>';
					else echo $v2;
				}
			}
			?>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<?php
	} else {
		?>
		<div class="form-row">
		<?php
		foreach ($form as $k=>$v) {
			//if (is_array($v) AND preg_match('/mysql|simple|file|file_multi/',$v[0])) dd($v);
			if (is_array($v)) echo call_user_func_array(preg_match('/mysql|simple|file|file_multi|gallery|gallery_multi/',$v[0]) ? 'form_file' : 'form', $v);
			else {
				if ($v=='clear') echo '<div class="clear"></div>';
				else echo $v;
			}
		}
		?>
		</div>
		<?php
	}
}
?>



