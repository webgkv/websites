<?php

$a18n['page']='page type';

$pages=array(
  'all'=>'all',
/*
  'index'=>'index', //
  'news_list'=>'news (list)', //
  'news_item'=>'news (item)', //
  'casino_list'=>'casino (list)',
  'casino_item'=>'casino (item)',
  'sportsbook_list'=>'sportsbook (list)', //
  'sportsbook_item'=>'sportsbook (item)', //
  'tickets_list'=>'tickets (list)', //
  'tickets_item'=>'tickets (item)', //
  'cota2_list'=>'cota2 (list)', //
  'cota2_item'=>'cota2 (item)', //
  'videos_list'=>'videos (list)', //
  'videos_item'=>'videos (item)', //
  'pariuri'=>'pariuri', //
  'search'=>'search', //
  'mai-mult'=>'mai-mult', //
  'recenzii_list'=>'recenzii (list)', //
  'recenzii_item'=>'recenzii (item)', //
  'ghid_list'=>'ghid (list)', //
  'ghid_item'=>'ghid (item)', //

//  'advices_list'=>'advices (list)',
//  'advices_item'=>'advices (item)',
*/
);

$popups=mysql_select('select id,name from popups','array');

$filter[] = array('page',$pages,'-');
$filter[] = array('popup',$popups,'-');
$where ='';
if (isset($get['page']) &&$get['page']!='' ) $where.= " AND page='".$get['page']."'";
if (isset($get['popup'])&&$get['popup']!='') $where.= " AND popup='".$get['popup']."'";

$query = "
	SELECT * FROM `popup_places`
	WHERE 1 $where
";



$table = array(
	'id'		=>	'id:asc name',
//	'img'		=>	'img',
	'name'		=>	'',
//	'url'		=>	'',
	'page'		=>	'',
	'popup'		=>	$popups,
	'display'	=>	'boolean'
);

$form[] = array('input td11','name');
$form[] = array('checkbox','display');
$form[] = array('select td6','page',array('value'=>array(true,$pages,'')));
$form[] = array('select td6','popup',array('value'=>array(true,$popups,'')));

//$form[] = array('textarea td12','html',array('name'=>'html (will be used if filled, otherwise images will be used)'));
//$form[] = array('file td6','img',array(
//	'name'=>'desktop',
//	'sizes'=>array(
//		''=>'',
//                '358-'=>'resize 358x',
//                '812-'=>'resize 812x',
//	),
//));
//$form[] = array('file td6','img2',array(
//	'name'=>'mobile',
//	'sizes'=>array(
//		''=>'',
//                '358-'=>'resize 358x',
//                '812-'=>'resize 812x',
//	),
//));
//$form[] = array('input td6','url');



?>