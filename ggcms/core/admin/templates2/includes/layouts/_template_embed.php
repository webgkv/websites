<!doctype html>
<html lang="<?=$config['admin_lang']?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=htmlspecialchars(@$page_name)?> — Settings</title>
	<?=html_sources('return','admin_top')?>
</head>
<body class="embed-admin">

<div class="container py-3">
	<div class="row">
		<div class="page-header">
			<h4><?=@$page_name?></h4>
		</div>
	</div>

	<?=$content?>

	<div class="row">
		<?php
		if (@$table) {
			?>
			<div class="card" style="width: 100%;">
				<div class="card-body">
					<?php if ($filter) { ?>
					<div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-2 border-bottom">
						<?php
						foreach ($filter as $k=>$v) {
							echo is_array($v) ? call_user_func_array('filter', $v) : $v;
						}
						?>
					</div>
					<?php } ?>
					<div class="table-responsive">
						<?=table($table, $query)?>
					</div>
				</div>
			</div>
			<?php
			if (isset($table['_check'])) {
				?>
			<div style="width:100%">
				<form method="post" class="table_check form-row" action="">
					<input type="hidden" name="_check[ids]" />
					<?php
					if (isset($table['_check']['buttons']) AND is_array($table['_check']['buttons'])) {
						foreach ($table['_check']['buttons'] as $k=>$v) {
							?>
						<div class="form-group col-xl-2">
							<input class="btn btn-secondary" type="submit" name="_check[<?=$k?>]" value="<?=$v?>" />
						</div>
							<?php
						}
					}
					if (isset($table['_check']['select']) AND is_array($table['_check']['select'])) {
						?>
						<div class="form-group col-xl-3">
							<select class="form-control" name="_check[select]"><?=select('',$table['_check']['select'],'')?></select>
						</div>
						<div class="form-group col-xl-3">
							<input class="btn btn-secondary" style="float:left" type="submit" value="Apply" />
						</div>
						<?php
					}
					?>
				</form>
			</div>
				<?php
			}
		}

		if (@$content_bottom) {
			echo $content_bottom;
		}

		if (isset($form) AND $module['one_form']==true) {
			?>
			<div class="card" style="width: 100%">
				<div class="card-body">
					<form id="form<?=$get['id']?>" class="form" method="post" enctype="multipart/form-data" action="<?=setUrlParams($_SERVER['REQUEST_URI'],array('u'=>'edit','id'=>false))?>">
						<?php
						require_once(ROOT_DIR.$config['style'].'/includes/layouts/form_body.php');
						?>
						<div class="modal-footer" style="padding: 15px 0 0">
							<?php
							require_once(ROOT_DIR.$config['style'].'/includes/layouts/form_footer.php');
							?>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</div>

<script src="/<?=$config['style']?>/vendors/bundle.js"></script>
<?=html_sources('return','admin_bottom')?>
<?=html_sources('footer')?>

<script>
(function(){
	function addEmbed(url) {
		if (!url || url.indexOf('embed=1') !== -1) return url;
		return url + (url.indexOf('?') !== -1 ? '&embed=1' : '?embed=1');
	}
	document.querySelectorAll('a[href*="admin.php"]').forEach(function(a){
		a.setAttribute('href', addEmbed(a.getAttribute('href')));
	});
	document.querySelectorAll('form[action*="admin.php"]').forEach(function(f){
		f.setAttribute('action', addEmbed(f.getAttribute('action')));
	});
})();
</script>
</body>
</html>
