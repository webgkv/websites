<!doctype html>
<html lang="<?=$config['admin_lang']?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>Site control panel</title>
	<?php $_admin_favicon = function_exists('site_admin_favicon_href') ? site_admin_favicon_href() : ('/' . $config['style'] . '/assets/media/image/favicon.png'); ?>
	<link rel="shortcut icon" href="<?= htmlspecialchars($_admin_favicon, ENT_QUOTES, 'UTF-8') ?>"/>
	<link rel="icon" type="image/png" href="<?= htmlspecialchars($_admin_favicon, ENT_QUOTES, 'UTF-8') ?>"/>
	<?=html_sources('return','admin_top')?>
</head>
<body class="<?=@$_SESSION['sidebar'] == 'close' ? 'small-navigation':''?>">
<!-- begin::navigation (first in DOM so it can sit left of #main) -->
<div class="navigation" id="admin-nav">
	<div class="navigation-menu-body">
		<ul>
			<?=html_array('layouts/menu',$modules_admin)?>
		</ul>
	</div>
</div>
<!-- end::navigation -->
<!-- begin::main -->
<div id="main">

	<!-- begin::header -->
	<div class="header">

		<!-- begin::header left -->
		<ul class="navbar-nav">

			<!-- begin::navigation-toggler -->
			<li class="nav-item navigation-toggler">
				<a href="#" class="nav-link">
					<i data-feather="menu"></i>
					<i data-feather="arrow-left"></i>
					<i data-feather="arrow-right"></i>
				</a>
			</li>
			<!-- end::navigation-toggler -->

			<!-- begin::header-logo -->
			<li class="nav-item" id="header-logo">
				<a href="/admin.php">
					<img class="logo" style="width: 50px" src="/<?=$config['style']?>/assets/media/image/logo2.png" alt="logo">
					<img class="logo-sm" src="/<?=$config['style']?>/assets/media/image/logo2.png" alt="small logo">
				</a>
			</li>
			<!-- end::header-logo -->
		</ul>
		<!-- end::header left -->

		<!-- begin::header-right -->
		<div class="header-right">
			<ul class="navbar-nav">


				<!-- begin::search-form -->
				<li class="nav-item search-form"></li>
				<!-- end::search-form -->


				<?=html_array('layouts/feedback_header')?>

				<?=html_array('layouts/notifications')?>

				<li class="nav-item dropdown">
					<?=$user['email']?>
				</li>

				<li class="nav-item dropdown">
					<a href="/admin.php?m=login&u=exit" class="btn nav-link bg-danger-bright" title="Logout" data-toggle="tooltip">
						<i data-feather="log-out"></i>
					</a>
				</li>
			</ul>

			<!-- begin::mobile header toggler -->
			<ul class="navbar-nav d-flex align-items-center">
				<li class="nav-item header-toggler">
					<a href="#" class="nav-link">
						<i data-feather="arrow-down"></i>
					</a>
				</li>
			</ul>
			<!-- end::mobile header toggler -->
		</div>
		<!-- end::header-right -->
	</div>
	<!-- end::header -->

	<!-- begin::main-content -->
	<div class="main-content">

		<!-- begin::container -->
		<div class="container">

			<div class="row">
				<div class="page-header">
					<h4><?=@$page_name?></h4>
				</div>
			</div>

			<?php if (!empty($page_header_extra ?? '')) { ?>
			<div class="row">
				<div class="col-12">
					<?=$page_header_extra?>
				</div>
			</div>
			<?php } ?>

			<?php
			// Unified Bootstrap alerts (flash messages) for all admin sections
			if (!empty($_SESSION['admin_flash_success'])) {
				?>
				<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
					<?= htmlspecialchars($_SESSION['admin_flash_success']) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php
				unset($_SESSION['admin_flash_success']);
			}
			if (!empty($_SESSION['admin_flash_error'])) {
				?>
				<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
					<?= htmlspecialchars($_SESSION['admin_flash_error']) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php
				unset($_SESSION['admin_flash_error']);
			}
			if (!empty($_SESSION['admin_flash_info'])) {
				?>
				<div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
					<?= htmlspecialchars($_SESSION['admin_flash_info']) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php
				unset($_SESSION['admin_flash_info']);
			}
			?>

			<?=$content?>

			<?php if ($filter) {
				?>
				<div class="form-row" id="filter">
					<?php
					foreach ($filter as $k=>$v) {
						echo is_array($v) ? call_user_func_array('filter', $v) : $v;

					}
					?>
				</div>
				<?php
			}?>

			<div class="row">
				<?php
				// class="col-md-12"
				if (@$table) {
					?>
					<div class="card" style="width: 100%;">
						<div class="card-body">
							<div class="table-responsive">
								<?=table($table, $query)?>
							</div>
						</div>
					</div>
					<?php
					// v1.2.130 - admin checkboxes
					if (isset($table['_check'])) {
						?>
					<div style="width:100%">
						<form method="post" class="table_check form-row" action="">
							<input type="hidden" name="_check[ids]" />
							<?php
							// Actions as buttons
							if (isset($table['_check']['buttons']) AND is_array($table['_check']['buttons'])) {
								foreach ($table['_check']['buttons'] as $k=>$v) {
									?>
								<div class="form-group col-xl-2">
									<input class="btn btn-secondary" type="submit" name="_check[<?=$k?>]" value="<?=$v?>" />
								</div>
								<?php
								}
							}
							// Actions as select + Apply button
							if (isset($table['_check']['select']) AND is_array($table['_check']['select'])) {
								?>
								<div class="form-group col-xl-3">
									<select class="form-control" name="_check[select]"><?=select('',$table['_check']['select'],'')?></select>
								</div>
								<div class="form-group col-xl-3">
									<input class="btn btn-secondary" style="float:left" type="submit" value="Применить" />
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

				// Load into table only when single form
				if (/*$get['id'] AND */isset($form) AND $module['one_form']==true) {
					?>
					<div class="card" style="width: 100%">
						<div class="card-body">
							<form id="form<?=$get['id']?>" class="form" method="post" enctype="multipart/form-data" action="<?=setUrlParams($_SERVER['REQUEST_URI'],array('u'=>'edit','id'=>false))?>" data-media-entity="<?=htmlspecialchars(!empty($module['table']) ? (string)$module['table'] : '', ENT_QUOTES, 'UTF-8')?>" data-media-entity-id="<?=(isset($get['id']) && $get['id'] !== '' && $get['id'] !== 'new') ? (int)$get['id'] : 0?>">

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
		<!-- end::container -->

	</div>
	<!-- end::main-content -->

	<!-- begin::footer -->
	<footer>
	</footer>
	<!-- end::footer -->

</div>
<!-- end::main -->

<script>
(function(){
	function adminLayoutFix(){
		var w = window.innerWidth;
		var body = document.body;
		var nav = document.getElementById('admin-nav');
		var main = document.getElementById('main');
		if (!nav || !main) return;
		if (w <= 1200) {
			body.style.setProperty('display','flex','important');
			body.style.setProperty('flex-direction','row','important');
			nav.style.setProperty('position','relative','important');
			nav.style.setProperty('left','0','important');
			nav.style.setProperty('opacity','1','important');
			nav.style.setProperty('width','215px','important');
			nav.style.setProperty('flex','0 0 215px','important');
			main.style.setProperty('width','auto','important');
			main.style.setProperty('flex','1 1 auto','important');
			main.style.setProperty('min-width','0','important');
		} else {
			body.style.removeProperty('display');
			body.style.removeProperty('flex-direction');
			nav.style.removeProperty('position');
			nav.style.removeProperty('left');
			nav.style.removeProperty('opacity');
			nav.style.removeProperty('width');
			nav.style.removeProperty('flex');
			main.style.removeProperty('width');
			main.style.removeProperty('flex');
			main.style.removeProperty('min-width');
		}
	}
	function run(){ adminLayoutFix(); }
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
		window.addEventListener('load', run);
	} else run();
	window.addEventListener('resize', adminLayoutFix);
})();
</script>
<!-- Plugin scripts -->

<?php /*
<!-- Chartjs -->
<script src="/<?=$config['style']?>/vendors/charts/chartjs/chart.min.js"></script>

<!-- Apex chart -->
<script src="/<?=$config['style']?>/vendors/charts/apex/apexcharts.min.js"></script>

<!-- Circle progress -->
<script src="/<?=$config['style']?>/vendors/circle-progress/circle-progress.min.js"></script>

<!-- Peity -->
<script src="/<?=$config['style']?>/vendors/charts/peity/jquery.peity.min.js"></script>
<script src="/<?=$config['style']?>/assets/js/examples/charts/peity.js"></script>

<!-- Datepicker -->
<script src="/<?=$config['style']?>/vendors/datepicker/daterangepicker.js"></script>

<!-- Slick -->
<script src="/<?=$config['style']?>/vendors/slick/slick.min.js"></script>

<!-- Vamp -->
<script src="/<?=$config['style']?>/vendors/vmap/jquery.vmap.min.js"></script>
<script src="/<?=$config['style']?>/vendors/vmap/maps/jquery.vmap.usa.js"></script>
<script src="/<?=$config['style']?>/assets/js/examples/vmap.js"></script>

<!-- Dashboard scripts -->
<script src="/<?=$config['style']?>/assets/js/examples/dashboard.js"></script>
 */?>

<script src="/<?=$config['style']?>/vendors/bundle.js"></script>

<script type="text/javascript">
	document.addEventListener("DOMContentLoaded", function () {
		$(document).on('click', '.navigation-toggler > a', function () {
			if ($(window).width() >= 1200) {
				if ($('body').hasClass('small-navigation')) {
					$.get('/admin.php', {'u': 'sidebar', 'action': 'close'});
				}
				else {
					$.get('/admin.php', {'u': 'sidebar', 'action': 'open'});
				}
			}
			else {

			}
		});
		// Avoid double-tap on mobile menu
		if ($(window).width() < 1200) {
			$('body.small-navigation').removeClass('small-navigation');
		}
	});
</script>

<?php
// After single-page import: redirect was to list; open edit modal and show success/error
if (isset($get['m']) && $get['m'] === 'pages' && isset($_GET['import_page_id']) && ($_import_page_id = (int)$_GET['import_page_id']) > 0) {
	$_import_ok = isset($_GET['import_page_ok']) && $_GET['import_page_ok'] === '1';
	$_import_err_js = isset($_GET['import_page_error']) ? $_GET['import_page_error'] : '';
?>
<script type="text/javascript">
(function(){
	var pageId = <?= (int)$_import_page_id ?>;
	var importOk = <?= $_import_ok ? 'true' : 'false' ?>;
	var importErr = <?= json_encode($_import_err_js) ?>;
	var params = { m: 'pages', u: 'form', id: pageId };
	if (importOk) params.import_page_ok = '1';
	if (importErr) params.import_page_error = importErr;
	$('#window').modal('hide');
	$.get('/admin.php', params, function(data) {
		$(data).appendTo('body').find('.form').trigger('form.open');
		$('#window').modal();
	});
	if (window.history && window.history.replaceState) {
		var u = new URL(window.location.href);
		u.searchParams.delete('import_page_id');
		u.searchParams.delete('import_page_ok');
		u.searchParams.delete('import_page_error');
		window.history.replaceState({}, '', u.toString());
	}
})();
</script>
<?php } ?>
<?php
// After single-casino import: redirect was to list; open edit modal and show success/error
if (isset($get['import_id']) && ($_import_id = (int)$get['import_id']) > 0 && (($get['m'] === 'content' && isset($get['tab']) && $get['tab'] === 'casinos') || $get['m'] === 'casino_articles')) {
	$_single_ok = isset($get['single_ok']) && $get['single_ok'] === '1';
	$_single_err = isset($get['single_error']) ? $get['single_error'] : '';
?>
<script type="text/javascript">
(function(){
	var cid = <?= (int)$_import_id ?>;
	var params = { m: 'content', tab: 'casinos', u: 'form', id: cid };
	if (<?= $_single_ok ? 'true' : 'false' ?>) params.single_ok = '1';
	var singleErr = <?= json_encode($_single_err) ?>;
	if (singleErr) params.single_error = singleErr;
	$('#window').modal('hide');
	$.get('/admin.php', params, function(data) {
		$(data).appendTo('body').find('.form').trigger('form.open');
		$('#window').modal();
	});
	if (window.history && window.history.replaceState) {
		var u = new URL(window.location.href);
		u.searchParams.delete('import_id');
		u.searchParams.delete('single_ok');
		u.searchParams.delete('single_error');
		window.history.replaceState({}, '', u.toString());
	}
})();
</script>
<?php } ?>

<?php
// After full language pack import: redirect was to languages list; open edit modal and show success/error
if (isset($get['m']) && $get['m'] === 'languages' && (isset($_GET['import_lang_id']) || isset($_GET['import_lang_error']))) {
	$_lang_id = isset($_GET['import_lang_id']) ? (int)$_GET['import_lang_id'] : (isset($get['import_lang_id']) ? (int)$get['import_lang_id'] : 0);
	$_lang_ok = isset($_GET['import_lang_ok']) && $_GET['import_lang_ok'] === '1';
	$_lang_err = isset($_GET['import_lang_error']) ? $_GET['import_lang_error'] : '';
	if ($_lang_id > 0) {
?>
<script type="text/javascript">
(function(){
	var langId = <?= (int)$_lang_id ?>;
	var ok = <?= $_lang_ok ? 'true' : 'false' ?>;
	var err = <?= json_encode($_lang_err) ?>;
	var params = { m: 'languages', u: 'form', id: langId };
	if (ok) params.import_lang_ok = '1';
	if (err) params.import_lang_error = err;
	$('#window').modal('hide');
	$.get('/admin.php', params, function(data) {
		$(data).appendTo('body').find('.form').trigger('form.open');
		$('#window').modal();
	});
	if (window.history && window.history.replaceState) {
		var u = new URL(window.location.href);
		u.searchParams.delete('import_lang_id');
		u.searchParams.delete('import_lang_ok');
		u.searchParams.delete('import_lang_error');
		window.history.replaceState({}, '', u.toString());
	}
})();
</script>
<?php
	}
}
?>

<?=html_sources('return','admin_bottom')?>
<script>
(function () {
	function bootEditors() {
		if (typeof adminInitTinymceInScope === 'function') {
			adminInitTinymceInScope(window.jQuery ? window.jQuery('.form') : document.querySelector('.form'));
		}
	}
	if (window.jQuery) {
		window.jQuery(bootEditors);
		window.jQuery(window).on('load', function () { window.setTimeout(bootEditors, 200); });
	} else {
		window.addEventListener('load', function () { window.setTimeout(bootEditors, 200); });
	}
})();
</script>
<?=html_sources('footer')?>


<div class="colors"> <!-- To use theme colors with Javascript -->
	<div class="bg-primary"></div>
	<div class="bg-primary-bright"></div>
	<div class="bg-secondary"></div>
	<div class="bg-secondary-bright"></div>
	<div class="bg-info"></div>
	<div class="bg-info-bright"></div>
	<div class="bg-success"></div>
	<div class="bg-success-bright"></div>
	<div class="bg-danger"></div>
	<div class="bg-danger-bright"></div>
	<div class="bg-warning"></div>
	<div class="bg-warning-bright"></div>
</div>


</body>
</html>