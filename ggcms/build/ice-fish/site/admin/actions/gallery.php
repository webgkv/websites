<?php

require_once(ROOT_DIR.'functions/data_func.php');
require_once(ROOT_DIR.'functions/mysql_func.php');

function img2str($img,$dimensions) {
  if(preg_match('#^(.*)\.([^\.]+)$#iu',$img,$m)) {
    $fn=$m[1];
    $fe=$m[2];
    return '/images/gallery/0/img/'.$dimensions.'-'.$fe.'-'.$fn.'.'.($fe=='svg'||$fe=='gif'?$fe:'webp');
  } else {
    return false; //blank img
  }
}

?>

<div class="modal show" tabindex="-1" role="dialog" aria-modal="true" style="display:block">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<form id="form95" class="form" method="post" enctype="multipart/form-data" action="">
				<div class="modal-header">
					<h5 class="modal-title"><span >Gallery</span></h5>
					<button type="button" id="galleryclose" class="close" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body">
					<ul class="nav nav-tabs mb-3" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" data-toggle="tab" id="g1-tab" href="#g1" role="tab" aria-controls="g1" aria-selected="true">gallery</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-toggle="tab" id="g2-tab" href="#g2" role="tab" aria-controls="g2" aria-selected="false">new image</a>
						</li>
					</ul>
<style>
.gimg-cover{border:3px solid #FFF}
.gimg {background:#EEE;padding:4px;border-radius:4px;margin:0;display:block;width:100%;height:100%}
.gimg div {overflow:hidden}
.gimg:hover,.gimg_active {background:#3e72c6;color:#fff}
.gimg:hover div,.gimg_active div {color:#fff}
</style>

<script>

function getgimgs(str='',page=1,id=0) {
	$.getJSON('/admin.php?m=gallery&json=1&id='+id+'&page='+page+'&search='+str,function(data){
		$('#gimgs').html(data.html);
		$('#gimgs-pages').html(data.pagination);
	});
}

$('.gsearch').click(function(){
  getgimgs($('#gsearchstr').val(),1);
  return false;
});

$('.set_image').click(function(){
  if($('.gimg_active').length) {

<?php if(isset($_GET['multi'])&&$_GET['multi']) { ?>

    $.ajax({
      url:'/admin/templates2/includes/form/gallery_multi_item.php',
      type:'POST',
      data:{'key':'<?=$_GET['id']?>',  'file':{'i':$('.gallery_multi[data-i="<?=$_GET['id']?>"]').find('ul li').length ,'file':'


gg'},  'fields':{'a':'b'}},
    }).done(function(data) {
//      alert(data);
//      $('.gallery_multi[data-i="<?=$_GET['id']?>"]').find('ul').append('<li class="clearfix ui-sortable-handle" data-i="0" title="">gg</li>');
      $('.gallery_multi[data-i="<?=$_GET['id']?>"]').find('ul').append(data);
    });



//    $('.gallery_multi[data-i="<?=$_GET['id']?>"]').find('ul').append('<li class="clearfix ui-sortable-handle" data-i="0" title="">gg</li>');


<?php } else { ?>
    $('input[name=<?=$_GET['id'];?>]').val($('.gimg_active').data('id'));

    $('#<?=$_GET['id'];?>-img'  ).attr('src','/files/gallery/'+$('.gimg_active').data('id')+'/img/a-'+$('.gimg_active .img').html());
    $('#<?=$_GET['id'];?>-name' ).html( $('.gimg_active .name').html() );
    $('#<?=$_GET['id'];?>-alt'  ).html( $('.gimg_active .alt').html() );
    $('#<?=$_GET['id'];?>-title').html( $('.gimg_active .title').html() );

<?php } ?>

  } else {
    alert('image not selected');
  }
  $('#gallery').remove();
  $('#window').show();
  return false;
});

$('form').submit(function(e){
  var myform=$(this);
  $.ajax({
    url:'/admin.php?m=gallery&new_img=1',
    type:'POST',
    data:$(this).closest('form').serialize(),
  }).done(function(data) {
    myform.find('.form-control').val('');
    myform.find('.img img').prop('src','/templates/images/no_img.svg');
    if(data>0) {
      $('#gimgs').html('<div><img src="/admin/templates/icons/loader.gif"></div>');
      $('#g1-tab').click();
      getgimgs($('#gsearchstr').val(),1,data);
    } else {
      alert('Error loading image');
    }
  });
  return false;
});

$(document).on('click','.page-link',function(){
  getgimgs($('#gsearchstr').val(),$(this).html());
  return false;
});

getgimgs();

</script>
					<div class="tab-content">
						<div class="tab-pane fade show active" id="g1" role="tabpanel" aria-labelledby="g1-tab">
							<div class="form-row">
								<div class="filter form-group col-xl-10">
									<input id="gsearchstr" type="text" class="form-control" placeholder="Search" value="">
									<a href="#" class="sprite search gsearch">
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
									</a>
								</div>
								<div class="form-group col-xl-2 files">
									<a href="#" class="btn btn-outline-secondary gsearch" title="Search">
										search
									</a>
								</div>
							</div>
							<div class="form-row" id="gimgs">
								<div><img src="/admin/templates/icons/loader.gif"></div>
							</div>
							<div class="form-row pagination mt-3" id="gimgs-pages"></div>


							<div class="modal-footer mt-3">
								<button type="button" class="btn btn-primary set_image">Set image</button>
							</div>


						</div>
						<div class="tab-pane fade" id="g2" role="tabpanel" aria-labelledby="g2-tab">
							<form id='addimg' enctype='multipart/form-data'>
<!-- begin -->
								<div class='form-row'>
<!-- -->
									<div class="form-group col-xl-3 files file" data-i="img">
										<div class="data">
											<div class="img" data-img="/templates/images/no_img.svg" title="Move picture to this area">
												<img src="/templates/images/a-no_img.svg"><span>&nbsp;</span><input name="img" type="hidden" value="">
											</div>
											<div class="name">large</div>
											<div class="desc">
											</div>
											<a class="add_file btn btn-outline-secondary" title="Select a file">
												select
												<input type="file" name="file" title="select a file">
											</a>
										</div>
									</div>
<!-- -->
									<div class="form-group input col-xl-3">
										<label><span>filename</span></label>
										<input class="form-control" name="filename" value="">
									</div>
									<div class="form-group input col-xl-3">
										<label><span>alt</span></label>
										<input class="form-control" name="alt" value="">
									</div>
									<div class="form-group input col-xl-3">
										<label><span>title</span></label>
										<input class="form-control" name="title" value="">
									</div>
<!-- -->
<?php foreach($languages as $langid=>$lang) if($langid!=1) { ?>
								</div>
								<div class='form-row'>
									<div class="col-xl-12"><b><?=$lang?></b></div>
								</div>
								<div class='form-row'>
									<div class="form-group input col-xl-6">
										<label><span>alt</span></label>
										<input class="form-control" name="alt<?=$langid?>" value="">
									</div>
									<div class="form-group input col-xl-6">
										<label><span>title</span></label>
										<input class="form-control" name="title<?=$langid?>" value="">
									</div>
<?php } ?>
								</div>
								<div class="modal-footer mt-3">
<!--
									<button type="button" class="btn btn-primary add_image">Add image</button>
-->
									<input type="submit" class="btn btn-primary add_image" value='Add image'>
								</div>
<!-- end -->
							</form>
						</div>


					</div>


				</div>
			</form>
		</div>
	</div>
</div>
