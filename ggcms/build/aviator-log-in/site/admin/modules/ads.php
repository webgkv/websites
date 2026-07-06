<?php

$a18n['page']='page type';

$pages=array(

  'index'=>'index 1312x320',
  'index_2'=>'index 2 640x568',

  'news_list'=>'news (list) 512x512',
  'news_item'=>'news (item) 480x576 480x240',
  'news_item_2'=>'news (item) 2 480x512',

  'videos_item'=>'videos (item) 480x576',

  'casinos_list'=>'casino (list) 1312x320',
  'casinos_list_2'=>'casino (list) 2 480x512',

  'sportsbooks_list'=>'sportsbook (list) 1312x320',
  'sportsbooks_list_2'=>'sportsbook (list) 2 480x512',

  'tickets_list'=>'tickets (list) 1312x320',
  'tickets_item'=>'tickets (item) 1312x320', //not used

  'bets_list'=>'bets (list) 1312x320',
  'bets_item'=>'bets (item) 1312x320',

  'advices_list'=>'advices (list) 1312x320',
  'advices_list_2'=>'advices (list) 2 512x512',
  'advices_item'=>'advices (item) 480x576',
  'advices_item_2'=>'advices (item) 2 480x512',

);

$filter[] = array('page',$pages,'-');
$where ='';
if (isset($get['page'])&&$get['page']!='') $where.= " AND page='".$get['page']."'";

$query = "
	SELECT * FROM `ads`
	WHERE 1 $where
";



$table = array(
	'id'		=>	'id:asc name',
	'img'		=>	'img',
	'name'		=>	'',
	'url'		=>	'',
	'page'		=>	'',
	'display'	=>	'boolean'
);

$tabs = array(
	1=>a18n('common'),
);

$form[1][] = array('input td6','name');
//$form[1][] = array('select td5','page',array('value'=>array(true,$pages,'')));
$form[1][] = array('select td5','page',array('value'=>array(true,$pages)));
$form[1][] = array('checkbox','display');
$form[1][] = array('textarea td12','html',array('name'=>'html (will be used if filled, otherwise images will be used)'));
$form[1][] = array('file td6','img',array(
	'name'=>'desktop',
	'sizes'=>array(
		''=>'',
//                '358-'=>'resize 358x',
//                '812-'=>'resize 812x',
	),
));
$form[1][] = array('file td6','img_2',array(
	'name'=>'mobile',
	'sizes'=>array(
		''=>'',
//                '358-'=>'resize 358x',
//                '812-'=>'resize 812x',
	),
));
$form[1][] = array('input td6','url');

?>