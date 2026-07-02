<?php

//session_start();

define('ROOT_DIR', dirname(__FILE__).'/../');

require_once(ROOT_DIR.'config/config.php');

// загрузка функций **********************************************************
//require_once(ROOT_DIR.'functions/admin_func.php');	//функции админки
require_once(ROOT_DIR.'functions/auth_func.php');	//функции авторизации
require_once(ROOT_DIR.'functions/common_func.php');	//общие функции
//require_once(ROOT_DIR.'functions/file_func.php');	//функции для работы с файлами
require_once(ROOT_DIR.'functions/html_func.php');	//функции для работы нтмл кодом
require_once(ROOT_DIR.'functions/form_func.php');	//функции для работы со формами
//require_once(ROOT_DIR.'functions/image_func.php');	//функции для работы с картинками
require_once(ROOT_DIR.'functions/lang_func.php');	//функции словаря
//require_once(ROOT_DIR.'functions/mail_func.php');	//функции почты
require_once(ROOT_DIR.'functions/mysql_func.php');	//функции для работы с БД
require_once(ROOT_DIR.'functions/string_func.php');	//функции для работы со строками

$request_url = explode('?',$_SERVER['REQUEST_URI'],2); //dd($request_url);
//создание массива $u
$u = explode('/',$request_url[0]);

$lang = @$_REQUEST['language'] ? lang($_REQUEST['language']) : false;

$api = array();

$debug = @$_REQUEST['_debug'];

if (@$u[2]) {
	//второй уровень вложенности
	if (@$u[3]) {
		$rel_static = $u[2] . '/' . $u[3];
		$static_api = ROOT_DIR . 'api/' . $rel_static;
		if (preg_match('/\.xml$/i', (string)$u[3]) && is_file($static_api) && is_readable($static_api)) {
			header('Content-type: text/xml; charset=UTF-8');
			readfile($static_api);
			exit;
		}
		//если в папке есть индексный файл то грузим его
		//нижнее подчеркивание только для того чтобы он был первым в списке
		$file = $u[2].'/_index.php';
		//если нет то указанный
		if (!file_exists($file)) {
			$file = $u[2].'/'.$u[3].'.php';
		}
	}
	//первый уровень вложенности
	else {
		//либо отдельный файл либо в папке common
		$file = $u[2].'.php';
		//если нет то в папке common
		if (!file_exists($file)) {
			$file = 'common/'.$u[2].'.php';
		}
	}

	if ($debug) $api['_file'] = $file;
	$file = ROOT_DIR.'api/'.$file;
	if (file_exists($file)) {
		include_once($file);
	}
	else {
		$api['_error'] = 'error #1';
	}
}

if ($debug) {
	dd($api);
}
else {
	header('Content-type: application/json; charset=UTF-8');
	echo json_encode($api);
}
