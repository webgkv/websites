<?php

/*
 * скрипт для загрузки файлов через нтмл5
 * создает временную папку на севрере /files/temp/*
 * что позволяет делать предзагрузку файла на сервер до отправки формы
 */

sleep(1);
require_once(ROOT_DIR . 'functions/common_func.php');
require_once(ROOT_DIR . 'functions/string_func.php');
require_once(ROOT_DIR . 'functions/file_func.php');	//функции для работы с файлами

//загузка файла во временную директорию
$file = @$_FILES['temp'];
if ($file AND is_array($file)) {
	$file['temp'] = rand(1000000,9999999);
	$pathinfo = pathinfo($file['name']);
	//очищаем имя файла
	$file['name'] = strtolower(trunslit($pathinfo['filename'])); //название файла
	//если имя файла пустое то делаем md5
	if ($file['name']=='') {
		$file['name'] =  substr(md5($file['temp']), 0, 10);
	}
	//полное название файла с расширением
	$file['name'].= '.'.strtolower($pathinfo['extension']);
	$path = 'files/temp/'.$file['temp']; //папка от корня основной папки
	$root = ROOT_DIR.$path.'/';
	if (is_dir($root) || mkdir ($root,0755,true)) { //создание папок для файла
		copy($file['tmp_name'],$root.$file['name']);
		echo $file['temp'];
	}
}

//удаление старых файлов
$root = ROOT_DIR.'files/temp/';
$time = 60*60*24; //сутки
if ($handle = opendir($root)) {
	while (false !== ($dir = readdir($handle))) {
		if (strlen($dir)>2 && is_dir($root.$dir)) {
			if ((time() - $time) > filemtime($root.$dir)) delete_all($root.$dir.'/',true);
		}
	}
}
die();