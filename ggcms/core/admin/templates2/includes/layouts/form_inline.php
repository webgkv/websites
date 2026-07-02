<?php
/**
 * Inline form layout: form as full page content (card), not modal.
 * Used when opening edit form in new tab (e.g. Review from Translations Monitor).
 */
?>
<div class="card">
	<div class="card-body">
		<form id="form<?=$get['id']?>" class="form" method="post" enctype="multipart/form-data" action="<?=setUrlParams($_SERVER['REQUEST_URI'],array('u'=>'edit','id'=>false))?>" data-media-entity="<?=htmlspecialchars(!empty($module['table']) ? (string)$module['table'] : '', ENT_QUOTES, 'UTF-8')?>" data-media-entity-id="<?=(isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new') ? (int)$get['id'] : 0?>">
			<?php if (isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new') { ?><input type="hidden" name="id" value="<?=(int)$get['id']?>"><?php } ?>
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h5 class="mb-0">
					ID: <span data-name="id"><?=$get['id']?></span>
					<?php if (@$table['_view'] && $get['id']!='new') { ?>
						<a href="<?=get_url($table['_view'],$post)?>" class="btn btn-outline-secondary btn-sm ml-2"><?=a18n('view')?></a>
					<?php } ?>
					<?=html_delete($delete)?>
				</h5>
				<a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">Back</a>
			</div>
			<?php
			// Inline full-page editor controls:
			// - show only one Save button (right aligned)
			// - hide Save&Close (modal-only behavior)
			?>
			<div class="inline-form-actions d-flex justify-content-end mb-3">
				<button type="button" class="btn btn-primary js-inline-save"><?=a18n('save')?></button>
			</div>
			<div class="form-content">
				<?php require_once(ROOT_DIR.$config['style'].'/includes/layouts/form_body.php'); ?>
				<input name="nested_sets[on]" type="hidden" value="0" />
			</div>
		</form>
	</div>
</div>
