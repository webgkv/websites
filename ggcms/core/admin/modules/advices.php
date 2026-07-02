<?php

$a18n['img_filename']='filename';
$a18n['img_alt']='alt';
$a18n['img_title']='title';

//$tags    =mysql_select("SELECT tags.id,concat(news_category.name,': ',tags.name) name from tags left join news_category on tags.category=news_category.id order by news_category.name,tags.name",'array');
//$category=mysql_select("SELECT id,name from news_category",'array');

$filter[] = array('search');
$where ='';
if (isset($get['search'])&&$get['search']!='') $where.= " AND LOWER(name) like '%".strtolower($get['search'])."%'";

$query = "
	SELECT * FROM `advices`
	WHERE 1 $where
";

$table = array(
	'id'		=>	'id:desc name date',
	'img'		=>	'img',
	'name'		=>	'',
//        'category'	=>	$category,
	'date'		=>	'date',
	'display'	=>	'boolean'
);

$tabs = array(
	1=>a18n('common'),
	2=>'main image',
	3=>a18n('images'),
);

$form[1][] = array('input td8','name');
$form[1][] = array('input td3','date');
$form[1][] = array('checkbox td1','display');
$form[1][] = array('input td12','name_2');

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
//		'372-'=>'cut 372x276',
//		'416-'=>'cut 416x300',
//		'768-'=>'cut 768x512',
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

//исключение при редактировании модуля
if ($get['u']=='edit') {
	foreach($post['imgs'] as $k=>$v) {
		if($v['filename']) {
			$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$v['file']);
			$filename=$v['filename'].$ext;
			if($filename!=$v['file']) {
				$path=ROOT_DIR.'files/advices/'.$get['id'].'/imgs/'.$k.'/';
				rename($path.$v['file'],$path.$filename);
				rename($path.'a-'.$v['file'],$path.'a-'.$filename);
				foreach($form[3][0][2]['sizes'] as $k2=>$v2)
					if($k2) rename($path.$k2.$v['file'],$path.$k2.$filename);
			}
		}
	}
}

function event_change_ghid_pariuri($q,$old=false) {
	global $get,$post;
	global $form;
	$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$post['img']);
	if($post['img']!=$post['img_filename'].$ext) {
		mysql_fn('update','advices',array('id'=>$post['id'],'img'=>$post['img_filename'].$ext));
		$path=ROOT_DIR.'files/advices/'.$post['id'].'/img/';
		rename($path.$post['img'],$path.$post['img_filename'].$ext);
		rename($path.'a-'.$post['img'],$path.'a-'.$post['img_filename'].$ext);
		foreach($form[2][3][2]['sizes'] as $k2=>$v2)
			if($k2) rename($path.$k2.$post['img'],$path.$k2.$post['img_filename'].$ext);
		$post['img']=$post['img_filename'].$ext;
	}
}
