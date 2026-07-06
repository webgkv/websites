<?php

//функции для img_webp

//https://github.com/rosell-dk/webp-convert
//composer require rosell-dk/webp-convert
require ROOT_DIR . 'vendor/autoload.php';
use WebPConvert\WebPConvert;

/**
 * функция проверяет наличие файла webp из исходника
 * в случаи возможности его генерирует webp из исходника
 * и возвращает путь к файлу webp
 * @param $file_src - полный путь к файлу с корня сайта
 * @return bool
 * @version v1.3.28
 * v1.3.28 - добавлена
 * v1.4.26 - возвращает либо старый путь либо с webp
 */
function img_webp ($path){
	global $config;
	//для дебага
	if (@$_GET['_imgs_webp']) {
		return  '/debug/webp';
	}
	//проверяем есть ли $config['webp']
	if ($config['webp'] AND $path) {
		$file = pathinfo($path);
		//полный путь к исходному файлу на сервере
		$root_path = trim($path, '/');
		$root_path = ROOT_DIR . $root_path;
		//полный путь к файлу с корня сайта
		$webp = $file['dirname'].'/'.$file['filename'].'.webp';
		//полный путь к новому файлу на сервере
		$root_webp = trim($webp, '/');
		$root_webp = ROOT_DIR . $root_webp;
		// существует исходный файл
		if (is_file($root_path)) {
			if (is_file($root_webp)) {
				return $webp;
			}
			//jpeg
			if (in_array($file['extension'],array('jpg','jpeg'))) {
				WebPConvert::convert($root_path, $root_webp, [100]);
			}
			//png
			elseif ($file['extension'] == 'png') {
				exec("cwebp -q 0 " . $root_path . " -o " . $root_webp);
				exec("convert -colorspace RGB " . $root_path . " " . $root_webp. "  ");
			}
			if (is_file($root_webp)) {
				return $webp;
			}
		}
	}
	//возвращаем старый путь
	return $path;
}