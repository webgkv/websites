<?php

$category=mysql_select("SELECT id,name from news_category",'array');

$table = array(
	'id'		=>	'id name',
	'category'	=>	$category,
	'name'		=>	'',
);

//$delete['confirm'] = array('ads'=>'subcategory_id');
$tabs=array(
	1=>a18n('common'),
);

$form[1][] = array('select td6','category',array('value'=>array(true,$category,'')));
$form[1][] = array('input td6','name');
$form[1][] = array('textarea td12','text');
$form[1][] = array('seo','seo url title description');
