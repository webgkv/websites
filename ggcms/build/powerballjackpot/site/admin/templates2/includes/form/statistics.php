<?php
global $post;
?>
<div class="col-xl-12">
	<div class="statistics row">
		<div class="filter form-group col-xl-3 datepicker">
			<input name="statistics[from]" type = "text" class="form-control " value="<?=$config['date']?>">
		</div>
		<div class="filter form-group col-xl-3  datepicker">
			<input name="statistics[to]" type = "text" class="form-control" value="<?=$config['date']?>">
		</div>
		<div class="filter form-group col-xl-3">
			<select class="form-control" name="statistics[type]">
				<option value="1">Личная</option>
				<option value="2">Общая</option>
			</select>
		</div>
		<div class="filter form-group col-xl-3">
			<input type="hidden" name="statistics[user]" value="<?=$post['id']?>">
			<button class="btn btn-light js-statistics">Применить</button>
		</div>
	</div>
	<div class="row statistics_result">

	</div>
</div>

