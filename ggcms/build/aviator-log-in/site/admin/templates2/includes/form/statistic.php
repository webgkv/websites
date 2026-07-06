<?php
global $post;
$statistics = array(
	'today'=>mysql_select("
			SELECT * FROM statistics
			WHERE user='".$post['id']."' AND date='".$config['date']."'
			",'row'),
	'yesterday'=>mysql_select("
			SELECT * FROM statistics
			WHERE user='".$post['id']."' AND date='".date("Y-m-d", time() - 60 * 60 * 24)."'
			",'row'),
	'week'=>mysql_select("
			SELECT SUM(invitations) invitations, SUM(1st) 1st, SUM(1st_yes) 1st_yes,
				SUM(2nd) 2nd, SUM(2nd_yes) 2nd_yes, SUM(total) total
			FROM statistics
			WHERE user='".$post['id']."' AND date>'".date("Y-m-d", time() - 60 * 60 * 24 * 8)."'
			",'row'),
	'month'=>mysql_select("
			SELECT SUM(invitations) invitations, SUM(1st) 1st, SUM(1st_yes) 1st_yes,
				SUM(2nd) 2nd, SUM(2nd_yes) 2nd_yes, SUM(total) total
			FROM statistics
			WHERE user='".$post['id']."' AND date>'".date("Y-m-d", time() - 60 * 60 * 24 * 31)."'
			",'row'),
);
$array = array(
	'invitations'=>'Приглашений',
	'1st_yes'=>'Да на 1 встречу',
	'1st'=>'1-я встреча',
	'total'=>'Сумма сделок',
	'2nd_yes'=>'Да на 2 встречу',
	'2nd'=>'2-я встреча',
);
$array2 = array(
	'today'=>'Сегодня',
	'yesterday'=>'Вчера',
	'week'=>'За неделю',
	'month'=>'За месяц',
);

foreach ($statistics as $key=>$val) {
	$labels = $values = array();
	?>
	<div class="form-group col-lg-6" style="display: none">
		<div class="form-row">
			<div class="form-group col-lg-12"><strong><?=$array2[$key]?></strong></div>
			<?php foreach ($array as $k=>$v) {
				$value = intval(@$val[$k]);
				if ($k=='total') {
					$value = number_format($value,0,'',' ').' руб.';
				}
				else {
					$labels[] = $v. ' ('.$value.')';
					$values[] = $value;
				}
				?>
				<div class="form-group col-lg-4">
					<?=$v?>: <strong><?=$value?></strong>
				</div>
			<?php } ?>
		</div>
	</div>

	<div class="form-group col-lg-12">
		<div style="float:right"><strong><?=$array2[$key]?></strong></div>
		Сумма сделок: <strong><?=number_format(@$val['total'],0,'',' ').' руб.'?></strong>
	</div>
	<div class="form-group col-lg-12">
			<canvas class="js_chart" id="chartjs_user_<?=$key?>"
					data-labels="<?=implode(',',$labels)?>"
					data-values="[<?=implode(',',$values)?>]"
			></canvas>
	</div>
<?php } ?>


