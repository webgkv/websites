<?php
$admin_form_modal = in_array(@$get['m'], array('users', 'user_types'), true) ? 'modal-xl' : 'modal-lg';
?>
<div id="window" class="modal" role="dialog">
	<div class="modal-dialog <?=$admin_form_modal?>" role="document">
		<div class="modal-content">
			<form id="form<?=$get['id']?>" class="form" method="post" enctype="multipart/form-data" action="<?=setUrlParams($_SERVER['REQUEST_URI'],array('u'=>'edit','id'=>false))?>" data-media-entity="<?=htmlspecialchars(!empty($module['table']) ? (string)$module['table'] : '', ENT_QUOTES, 'UTF-8')?>" data-media-entity-id="<?=(isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new') ? (int)$get['id'] : 0?>">
				<?php if (isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new') { ?><input type="hidden" name="id" value="<?=(int)$get['id']?>"><?php } ?>
				<div class="modal-header">
					<h5 class="modal-title">
						ID:<span data-name="id"><?=$get['id']?></span>
						<?php
						//v1.2.122 просмотр на сайте - _view
						if (@$table['_view'] AND $get['id']!='new') {?>
							<a href="<?=get_url($table['_view'],$post)?>"><?=a18n('view')?></a>
						<?php } ?>
						<?=html_delete($delete)?>
					</h5>
					<?php if ($module['one_form']==false) {?>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">×</span>
						</button>
					<?php } ?>
				</div>
				<div class="modal-body">
					<?php
					require_once(ROOT_DIR.$config['style'].'/includes/layouts/form_body.php');
					?>
					<input name="nested_sets[on]" type="hidden" value="0" />
				</div>
				<div class="modal-footer">
					<?php
					require_once(ROOT_DIR.$config['style'].'/includes/layouts/form_footer.php');
					?>
				</div>
			</form>
		</div>
	</div>
</div>


