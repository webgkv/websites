<?php

//быстрое редактирование
/*
 *  v1.4.14 - event_func
 */

require_once(ROOT_DIR.'admin/modules/'.$get['m'].'.php');

if (mysql_fn('update',$module['table'],array($get['name']=>$get['value'],'id'=>$get['id']))) {
	//логирование действия
	mysql_fn('insert','logs',array(
		'user'		=>	$user['id'],
		'date'		=>	date('Y-m-d H:i:s'),
		'parent'	=>	$get['id'],
		'module'	=>	$module['table'],
		'type'		=>	2,
		'ip'        => get_ip(),
		'fields'    => $get['name']
	));

	//v1.4.14 - event_func
	$event_function = 'event_change_'.$module['table'];
	if (function_exists($event_function)) {
		$item = mysql_select("SELECT * FROM ".$module['table']." WHERE id=".intval($get['id']),'row');
		$event_function($item);
	}
}

