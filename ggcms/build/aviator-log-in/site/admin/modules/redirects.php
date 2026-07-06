<?php

//редиректы
/*
 * v1.4.17 - сокращение параметров form
 */

$a18n['new_url'] = 'новый url';
$a18n['old_url'] = 'старый url';

$table = array(
	'id'		=>	'id',
	'old_url'		=>	'',
	'new_url'		=>	'',
	'display'	=>	'display'
);

$content = '<div style="margin:10px 0 0; padding:5px 10px; font:12px/14px Arial; background:#DFE0E0; border-radius:3px">Редиректы '.($config['redirects']==1 ? '<b style="color:green">включены</b>' : '<b style="color:darkred">выключены</b>').' <a  target="_blank" href="/admin.php?m=config">изменить</a></div>';

$form[] = array('input td5','old_url');
$form[] = array('input td5','new_url');
$form[] = array('checkbox','display');