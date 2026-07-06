<?php

$a18n['img_filename']='filename';
$a18n['img_alt']='alt';
$a18n['img_title']='title';

//$tags    =mysql_select("SELECT tags.id,concat(news_category.name,': ',tags.name) name from tags left join news_category on tags.category=news_category.id order by news_category.name,tags.name",'array');
//$tags    =mysql_select("SELECT tags.id,concat(news_category.name,': ',tags.name) name from tags left join news_category on tags.category=news_category.id order by news_category.name,tags.name",'array');
$tags    =mysql_select("SELECT id,name,category from news_tags",'rows_id');
$category=mysql_select("SELECT id,name from news_category",'array');

$filter[] = array('search');
$where ='';
if (isset($get['search'])&&$get['search']!='') $where.= " AND LOWER(name) like '%".strtolower($get['search'])."%'";

$query = "
	SELECT * FROM news
	WHERE 1 $where
";

$table = array(
	'id'		=>	'top:desc name date',
	'img'		=>	'img',
//	'gimg'		=>	'gallery',
	'name'		=>	'',
        'category'	=>	$category,
	'date'		=>	'date',
	'top'		=>	'boolean',
	'display'	=>	'boolean'
);

$tabs = array(
	1=>a18n('common'),
	2=>'main image',
	3=>a18n('images'),
	4=>'tags'
);

$form[1][] = array('input td6','name');
$form[1][] = array('input td4','date');
$form[1][] = array('checkbox td1','top');
$form[1][] = array('checkbox td1','display');

$form[1][] = array('input td6','name_2');
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
$form[2][] = array('file td6','img',array('sizes'=>array(''=>'')));

//$form[2][] = array('gallery td6','gimg');



//$form[3][] = array('gallery_multi','gimgs',array(
//	'name'=>a18n('help_imgs'),
//	'sizes'=>array(
//          ''=>'',
//        )
//));



$form[3][] = array('file_multi','imgs',array(
	'name'=>a18n('help_imgs'),
	'sizes'=>array(
          ''=>'',
//          '1000-'=>'resize 1000x1000'
        )
));

foreach($tags as $tagk=>$tagv) {
  $form[4][] = '<div class="form-group input col-xl-3 tags cat-'.$tagv['category'].((isset($post['category'])&&$post['category']!=$tagv['category'])?' d-none':'').'">
                  <div class="form-check">
                    <input type="hidden" name="tags['.$tagk.']" value="'.(((isset($post['tag1'])&&$post['tag1']==$tagk)||(isset($post['tag2'])&&$post['tag2']==$tagk)||(isset($post['tag3'])&&$post['tag3']==$tagk)||(isset($post['tag4'])&&$post['tag4']==$tagk))?'1':'0').'">
                    <label class="form-check-label">
                      <input class="form-check-input" type="checkbox" name="tags['.$tagk.']"'.(((isset($post['tag1'])&&$post['tag1']==$tagk)||(isset($post['tag2'])&&$post['tag2']==$tagk)||(isset($post['tag3'])&&$post['tag3']==$tagk)||(isset($post['tag4'])&&$post['tag4']==$tagk))?' checked':'').'>
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
				$path=ROOT_DIR.'files/news/'.$get['id'].'/imgs/'.$k.'/';
				rename($path.$v['file'],$path.$filename);
				rename($path.'a-'.$v['file'],$path.'a-'.$filename);
				foreach($form[3][0][2]['sizes'] as $k2=>$v2)
					if($k2) rename($path.$k2.$v['file'],$path.$k2.$filename);
			}
		}
	}
}

function event_change_news($q,$old=false) {
	global $get,$post;
	global $form;
	$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$post['img']);
	if($post['img']!=$post['img_filename'].$ext) {
		mysql_fn('update','news',array('id'=>$post['id'],'img'=>$post['img_filename'].$ext));
		$path=ROOT_DIR.'files/news/'.$post['id'].'/img/';
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