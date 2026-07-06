<div id="table">
	<table class="table table-sm table-responsive-stack <?=$q['type']?>" data-module="<?=$q['module']?>">
		<?=html_array('table/thead',$q) ?>
		<tbody>
			<?=html_array('table/row',$q) ?>
		</tbody>
	</table>
	<div class="pagination pagination-bottom mt-3"><?=html_render('pagination/default',$q)?></div>
</div>