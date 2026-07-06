<button type="button" class="btn btn-primary mr-auto"><?=a18n('save')?></button>
<?php
if (@$module['save_as']==true) {
	?>
	<button type="button" class="btn btn-primary close_as"><?=a18n('save_as')?></button>
	<?php
}
?>
<?php
if (@$module['one_form']==false) {?>
	<button type="button" class="btn btn-primary close_form"><?=a18n('save&close')?></button>
<?php } ?>