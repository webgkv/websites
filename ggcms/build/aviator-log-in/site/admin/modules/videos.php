<?php

$a18n['video']='youtube link (https://www.youtube.com/embed/###)';

$tags    =mysql_select("SELECT blog_tags.id,concat(blog_category.name,': ',blog_tags.name) name from blog_tags left join blog_category on blog_tags.category=blog_category.id order by blog_category.name,blog_tags.name",'array');
$category=mysql_select("SELECT id,name from blog_category",'array');

$filter[] = array('search');
$where ='';
if (isset($get['search'])&&$get['search']!='') $where.= " AND LOWER(name) like '%".strtolower($get['search'])."%'";

$query = "
	SELECT * FROM videos
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

function after_save() {
	global $get,$post;
//	if ($get['id'] != 'new') {
//	        if(!preg_match('#-id'.$get['id'].'$#iu',$post['url'])) $post['url'].='-id'.$get['id'];
//		$connect = mysql_connect_db();
//		mysqli_query($connect,'update news set url="'.$post['url'].'" where id='.$get['id']);
//	}
}

$tabs = array(
	1=>a18n('common'),
//	2=>a18n('media'),
);

$form[1][] = array('input td6','video');
$form[1][] = array('select td3','category',array('value'=>array(true,$category,'')));
$form[1][] = array('input td2','date');
$form[1][] = array('checkbox','display');
$form[1][] = array('input td6','name');
$form[1][] = array('input td6','name2');

//$form[1][] = array('input td3','author');
//$form[1][] = array('select td3','tag1',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag2',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag3',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag4',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag5',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag6',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag7',array('value'=>array(true,$tags,'')));
//$form[1][] = array('select td3','tag8',array('value'=>array(true,$tags,'')));
//$form[1][] = array('tinymce td12','text');
//$form[1][] = array('tinymce td12','text',array('attr'=>'style="height:500px"'));
//$form[1][] = array('textarea td12','text2');
$form[1][] = array('file td6','img',array(
	'sizes'=>array(
		''=>'',
//		'416-'=>'cut 416x300',
	)
));
//$form[1][] = array('file td6','img',array(
//	'sizes'=>array(
//		''=>'',
//		'636-'=>'cut 636x760',
//                '310-'=>'cut 310x252',
//                '416-'=>'cut 416x300',
//                '768-'=>'cut 768x512',
//                '769-'=>'cut 768x300',
//		'800-'=>'cut 800x512',
//	)
//));
//$form[1][] = array('seo','seo url title');

//$form[2][] = array('file_multi','imgs',array(
//	'name'=>a18n('help_imgs'),
//	'sizes'=>array(
//          ''=>'',
//          '1000-'=>'resize 1000x1000'
//        )
//));

$form[1][] = array('seo','seo url title description');