<?php

$countries=mysql_select('select id,name from countries order by name asc','array');
$sports=mysql_select('select id,name from sports order by name asc','array');

$table = array(
	'id'		=>	'id:asc name',
	'img'		=>	'img',
	'name'		=>	'',
	'country'	=>	$countries,
	'sport'		=>	$sports,
);

$tabs = array(
	1=>a18n('common'),
);

$form[1][] = array('input td6','name');

$form[1][] = array('select td3','country',array('value'=>array(true,$countries,'')));
$form[1][] = array('select td3','sport',array('value'=>array(true,$sports,'')));

$form[1][] = array('file td6','img',array(
	'sizes'=>array(
		''=>'',
//		'48-'=>'resize 48x48',
	)
));
