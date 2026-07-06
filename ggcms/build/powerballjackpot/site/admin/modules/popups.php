<?php

$table = array(
	'id'		=> 'id name',
	'name'		=> '',
//	'display'	=> 'display'
);

$tabs = array(
	1=>a18n('common'),
);

$form[1][] = array('input td12','name');
//$form[] = array('checkbox','display');
$form[1][] = array('textarea td12','html',array('attr'=>'style="height:500px"'));

?>