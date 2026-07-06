<?php

$a18n['img_filename']='filename';
$a18n['img_alt']='alt';
$a18n['img_title']='title';

$tags    =mysql_select("SELECT id,name,category from recenzii_tags",'rows_id');
$category=mysql_select("SELECT id,name from recenzii_category",'array');

$filter[] = array('search');
$where ='';
if (isset($get['search'])&&$get['search']!='') $where.= " AND LOWER(name) like '%".strtolower($get['search'])."%'";

$query = "
	SELECT * FROM recenzii
	WHERE 1 $where
";

$table = array(
	'id'		=>	'id:desc name date',
	'img'		=>	'img',
	'name'		=>	'',
        'category'	=>	$category,
	'date'		=>	'date',
	'display'	=>	'boolean'
);

$tabs = array(
	1=>a18n('common'),
//	2=>a18n('image'),
	2=>'main image',
	3=>a18n('images'),
	4=>'tags'
);

$form[1][] = array('input td6','name');
$form[1][] = array('input td3','date');
$form[1][] = array('checkbox td3','display');
$form[1][] = array('input td6','name2');
$form[1][] = array('select td3','category',array('value'=>array(true,$category,'')));
$form[1][] = array('input td3','author');
//$form[1][] = array('select td3','tag1',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag2',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag3',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag4',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag5',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag6',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag7',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag8',array('value'=>array(true,$tags,'')));
//$form[1][] = array('tinymce td12','text');
$form[1][] = array('tinymce td12','text',array('attr'=>'style="height:500px"'));
//$form[1][] = array('textarea td12','text2');
//$form[1][] = array('file','img','main photo',array(''=>'','p-'=>'resize 636x760','t-'=>'resize 800x512'));
$form[1][] = array('seo','seo url title description');

$form[2][] = array('input td4','img_filename');
$form[2][] = array('input td4','img_alt');
$form[2][] = array('input td4','img_title');
$form[2][] = array('file td6','img',array(
	'sizes'=>array(
		''=>'',
//		'636-'=>'cut 636x760',
//                '310-'=>'cut 310x252',
//                '416-'=>'cut 416x300',
//                '768-'=>'cut 768x512',
//                '769-'=>'cut 768x300',
//		'800-'=>'cut 800x512',
	)
));




$form[3][] = array('file_multi','imgs',array(
	'name'=>a18n('help_imgs'),
	'sizes'=>array(
          ''=>'',
//          '1000-'=>'resize 1000x1000'
        )
));

foreach($tags as $tagk=>$tagv) {
  $form[4][] = '<div class="form-group input col-xl-3 tags cat-'.$tagv['category'].(($post['category']!=$tagv['category'])?' d-none':'').'">
                  <div class="form-check">
                    <input type="hidden" name="tags['.$tagk.']" value="'.(($post['tag1']==$tagk||$post['tag2']==$tagk||$post['tag3']==$tagk||$post['tag4']==$tagk)?'1':'0').'">
                    <label class="form-check-label">
                      <input class="form-check-input" type="checkbox" name="tags['.$tagk.']"'.(($post['tag1']==$tagk||$post['tag2']==$tagk||$post['tag3']==$tagk||$post['tag4']==$tagk)?' checked':'').'>
                      <span>'.$tagv['name'].'</span>
                    </label>
                  </div>
                </div>';
}

//исключение при редактировании модуля
if ($get['u']=='edit') {

	$post['tag1']=$post['tag2']=$post['tag3']=$post['tag4']=0;
	$i=1;
	foreach($post['tags'] as $k=>$v) {
		if($v!='0') {
			$post['tag'.$i]=$k;
			$i++;
		}
	}
	unset($post['tags']);

	foreach($post['imgs'] as $k=>$v) {
		if($v['filename']) {
			$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$v['file']);
			$filename=$v['filename'].$ext;
			if($filename!=$v['file']) {
				$path=ROOT_DIR.'files/recenzii/'.$get['id'].'/imgs/'.$k.'/';
				rename($path.$v['file'],$path.$filename);
				rename($path.'a-'.$v['file'],$path.'a-'.$filename);
				foreach($form[3][0][2]['sizes'] as $k2=>$v2)
					if($k2) rename($path.$k2.$v['file'],$path.$k2.$filename);
			}
		}
	}
}

function event_change_recenzii($q,$old=false) {
	global $get,$post;
	global $form;
	$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$post['img']);
	if($post['img']!=$post['img_filename'].$ext) {
		mysql_fn('update','recenzii',array('id'=>$post['id'],'img'=>$post['img_filename'].$ext));
		$path=ROOT_DIR.'files/recenzii/'.$post['id'].'/img/';
		rename($path.$post['img'],$path.$post['img_filename'].$ext);
		rename($path.'a-'.$post['img'],$path.'a-'.$post['img_filename'].$ext);
		foreach($form[2][3][2]['sizes'] as $k2=>$v2)
			if($k2) rename($path.$k2.$post['img'],$path.$k2.$post['img_filename'].$ext);
		$post['img']=$post['img_filename'].$ext;
	}


}

$form[4][] = "
<script>

  $('body').on('change','select[name=category]',function(){
    var cat=$(this).val();
    $('.tags').each(function(i,obj){
      if($(obj).hasClass('cat-'+cat)) {
        $(obj).removeClass('d-none');
      } else {
        $(obj).find('input[type=checkbox]').prop('checked',false);
        $(obj).find('input[type=hidden]').val('0');
        $(obj).addClass('d-none');
      }
    });
  });

  $('body').on('change','.tags',function(){
    if($('.tags input[type=checkbox]:checked').length>4) {
      $(this).find('input[type=checkbox]').prop('checked',false);
      $(this).find('input[type=hidden]').val('0');
    } else {
      if($(this).find('input[type=checkbox]').is(':checked')) {
        $(this).find('input[type=hidden]').val('1');
      } else {
        $(this).find('input[type=hidden]').val('0');
      }
    }
  });

</script>";