<?php

$category=mysql_select("SELECT id,name from recenzii_category",'array');

$table = array(
	'id'		=>	'id name',
	'category'	=>	$category,
	'name'		=>	'',
);

//$delete['confirm'] = array('ads'=>'subcategory_id');

$form[] = array('select td6','category',array('value'=>array(true,$category,'')));
$form[] = array('input td6','name');
$form[] = array('textarea td12','text');
$form[] = array('seo','seo url title description');
