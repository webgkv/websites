<?php

// image handling functions
/**
 * v1.4.26 - перенес img_webp в functions/webp_func.php
 */


/**
 * генерация превью
 * @param $type - cut - обрезание, resize - уменьшение, bw - черно белый
 * @param $old - старый адрес
 * @param $size - размеры 100x200
 * @param string $new  - новый адрес, если не указан, то используется старый
 * @param int $quality - качество для jpg
 * @return bool - успешно или нет
 * v1.2.36 - добавлен в коментах код для оптимизации картинок
 * v1.3.14 - создание папки для новой картинки если такой нет
 */
function img_process ($type,$old,$size,$new = '',$quality = 90) {
	if (!is_file($old) || !preg_match('/resize|cut|fill/',$type)) return false;
	if ($new=='') $new = $old;
	//v1.3.14 создание папки если нет
	$dir = dirname($new);
	if (is_dir($dir) || mkdir ($dir,0755,true)) {
		$size_new = explode('x', $size);
		$size_old = getimagesize($old);
		if ($size_old === false) {
			// webp workaround for PHP < 7.1
			$version = phpversion();
			$config['php_version'] = $version[0] * 100 + $version[2] * 10 + $version[4];
			if ($config['php_version']<710) {
				$img = imagecreatefromwebp($old);
				$width = imagesx($img);
				$height = imagesy($img);
				if ($width AND $height) {
					$size_old = array($width,$height);
					$size_old['mime'] = 'image/webp';
				}
			}
			//unlink($old);
			if ($size_old === false) {
				log_add('img.txt', 'getimagesize ' . $old);
				return false;
			}
		}
		$format			= strtolower(substr($size_old['mime'], strpos($size_old['mime'], '/') + 1));
		$fn_img			= 'image'.$format;
		$fn_img_create	= 'imagecreatefrom'.$format;

		if (!function_exists($fn_img_create)) {
			log_add('img.txt','function_exists '.$fn_img_create);
			//unlink($old);
			return false;
		}
		$img1 = $fn_img_create($old);
		// convert to grayscale
		if ($bw = preg_match('/bw/',$type)) imagefilter($img1, IMG_FILTER_GRAYSCALE);

		$left = $top = $x = $y = 0;

		if(!$size_new[1]) $size_new[1]=round($size_old[1]/$size_old[0]*$size_new[0]);
		elseif(!$size_new[0]) $size_new[0]=round($size_old[0]/$size_old[1]*$size_new[1]);

		$size_calc = $size_new;

		//ресайз с сохранением пропорций
		if (preg_match('/resize/',$type)) {
			if (($size_new[0] < $size_old[0]) || ($size_new[1] < $size_old[1])) {
				if (($size_old[0]/$size_old[1])>($size_new[0]/$size_new[1])) $size_calc[1] = round($size_old[1]*$size_new[0]/$size_old[0]);
				else $size_calc[0] = round($size_old[0] * $size_new[1] / $size_old[1]);
			} else {
				$size_calc = $size_old;
			}
			$size_new[0] = $size_calc[0];
			$size_new[1] = $size_calc[1];
		}
		// resize to fit (crop excess)
		elseif (preg_match('/cut/',$type)) {
			if ($size_old[0]/$size_old[1] > $size_new[0]/$size_new[1]) {
				$scale = $size_old[1]/$size_new[1];
				$left = ($size_old[0] - $size_new[0]*$scale)/2;
				$size_old[0] = $size_new[0]*$scale;
			}
			else {
				$scale = $size_old[0]/$size_new[0];
				$top = ($size_old[1] - $size_new[1]*$scale)/2;
				$size_old[1] = $size_new[1]*$scale;
			}
		}
		//ресайз с подгонкой размера (заполнение пустого)
		elseif (preg_match('/fill/',$type)) {
			if (($size_new[0] < $size_old[0]) || ($size_new[1] < $size_old[1])) {
				if (($size_old[0]/$size_old[1])>($size_new[0]/$size_new[1])) $size_calc[1] = round($size_old[1]*$size_new[0]/$size_old[0]);
				else $size_calc[0] = round($size_old[0] * $size_new[1] / $size_old[1]);
			}
			else {
				$size_calc = $size_old;
			}
			$x = ($size_new[0] - $size_calc[0])/2;
			$y = ($size_new[1] - $size_calc[1])/2;
		}
		else return false;

		// crop part of image
		if (preg_match('/slice/',$type) && isset($size_new[2]) && isset($size_new[3])) {
			$scale = $size_old[0]/$size_calc[0];
			$size_old[2] = $scale*$size_new[2];
			$size_old[3] = $scale*$size_new[3];
			$left+= ($size_old[0] - $size_old[2])/2;
			$top+= ($size_old[1] - $size_old[3])/2;

			$size_old[0] = $size_old[2];
			$size_old[1] = $size_old[3];
			$size_new[0] = $size_new[2];
			$size_new[1] = $size_new[3];
			$size_calc = $size_new;
			$x = $y = 0;
		}

		//если размеры совпадают и не применяется фильтр, просто копируем изображение
		if ($size_new[0]==$size_old[0] && $size_new[1]==$size_old[1] && $left==0 && $top==0 && !$bw) {
			if ($old!=$new) copy($old,$new);
		}
		//если размеры отличаются или применяется фильтр, конвертируем
		else {
			$img2 = imageCreatetruecolor($size_new[0], $size_new[1]);
			//прозрачность для png
			if ($format == 'png') {
				//Добавляем постепенную загрузку
				imageInterlace($img2, 1);
				//Добавляем прозрачность
				$transparent = imagecolorallocatealpha($img2, 0, 0, 0, 127);
				imagefill($img2, 0, 0, $transparent);
				//Включаем обработку альфа канала
				imagesavealpha($img2, true);
			}
			//прозрачность для gif
			elseif ($format == 'gif') {
				//Добавляем постепенную загрузку
				imageInterlace($img2, 1);
				//Определяем прозрачный ли исходный рисунок
				$transparent_index = imagecolortransparent($img1);
				if ($transparent_index >= 0) {
					//Определяем прозрачные цвета
					$transparent_color = imagecolorsforindex($img1, $transparent_index);
					//Заполняем прозрачными цветами
					$transparent_index = imagecolorallocate($img2, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
					imagefill($img2, 0, 0, $transparent_index);
					imagecolortransparent($img2, $transparent_index);
				}
			}
			//заполнение цветом для fill
			elseif (preg_match('/fill/', $type)) {
				$rgb = ($pos = strpos($type, '#')) ? hex2rgb(substr($type, $pos)) : array(255, 255, 255);
				$color = imagecolorallocate($img2, $rgb[0], $rgb[1], $rgb[2]);
				imagefill($img2, 0, 0, $color);
			}
			imagecopyresampled($img2, $img1, $x, $y, $left, $top, $size_calc[0], $size_calc[1], $size_old[0], $size_old[1]);
			if ($format == 'jpeg') {
				$fn_img($img2, $new, $quality);
			}
			else {
				$fn_img($img2, $new);
			}
			imageDestroy($img1);
			imageDestroy($img2);
			//v1.2.36 оптимизация картинок, расскоментировать когда на сервере подключен jpegoptim
			/*
			if ($format=='jpeg') {
				//указать правильный путь к jpegoptim
				$command = 'jpegoptim --max=70 ' . $new;
				$command = '/usr/bin/jpegoptim --max=70 ' . $new;
				shell_exec($command);
			}
			*/
		}
	}
	else return false;
	return true;
}

/**
 * генерация водяного знака
 * поддерживает работу с файлами jpg,png,gif
 * @param $photo - старая картинка
 * @param $logo - водяной знак
 * @param $name - новая картинка
 * @param $position - позиция водяного знака
 * возможные значения параметра $position:
 * ##########	all		- замостить все
 * #1		2#	top		- вся строка по верху
 * #	5	 #	bottom	- вся нижняя строка
 * #3		4#
 * ##########
 * @return bool - успешно или нет
 * 06.12.09
 */
function img_watermark($photo,$logo,$name,$position){
	if (is_file($photo) and is_file($logo)){
		//photo
		$size = getimagesize($photo);
		$imagesW = $size[0];
		$imagesH = $size[1];
		$imagesType = $size['mime'];
		switch($imagesType){
			case 'image/jpeg':	$dest = imagecreatefromjpeg($photo);	break;
			case 'image/png':	$dest = imagecreatefrompng($photo);		break;
			case 'image/gif':	$dest = imagecreatefromgif ($photo);	break;
		}
		//logo
		$size2 = getimagesize($logo);
		$images2W = $size2[0];
		$images2H = $size2[1];
		$images2Type = $size2['mime'];
		switch($images2Type){
			case 'image/jpeg':	$src = imagecreatefromjpeg($logo);	break;
			case 'image/png':	$src = imagecreatefrompng($logo);	break;
			case 'image/gif':	$src = imagecreatefromgif($logo);	break;
		}

		//Общая ширина логотипов на картинке
		$KolLogoInWhImg = $imagesW/$images2W;
		$imagesGW = $imagesGH = 0;
		for($q = 1; $q<$KolLogoInWhImg; $q++) $imagesGW+= $images2W;

		//Общая высота логотипов на картинке
		$KolLogoInHeImg = $imagesH/$images2H;
		for($e = 1; $e<$KolLogoInHeImg; $e++) $imagesGH+= $images2H;

		$startW = $GlobalW = ($imagesW-$imagesGW)/2;
		$startH = ($imagesH-$imagesGH)/2;

		switch ($position){
			case 'top':
				for($i = 1; $i<$KolLogoInWhImg; $i++){
					imagecopy($dest, $src,$startW,0,0,0,$images2W,$images2H);
					$startW+= $images2W;
				}
			break;
			case 'bottom':
				$startH = $imagesH-$images2H;
				for($i = 1; $i<$KolLogoInWhImg; $i++){
					imagecopy($dest, $src,$startW,$startH,0,0,$images2W,$images2H);
					$startW+= $images2W;
				}
			break;
			case '1':
				imagecopy($dest, $src,0,0,0,0,$images2W,$images2H);
			break;
			case '2':
				$startW = $imagesW-$images2W;
				imagecopy($dest, $src,$startW,0,0,0,$images2W,$images2H);
			break;
			case '3':
				$startH = $imagesH-$images2H;
				imagecopy($dest, $src,0,$startH,0,0,$images2W,$images2H);
			break;
			case '4':
				$startH = $imagesH-$images2H;
				$startW = $imagesW-$images2W;
				imagecopy($dest, $src,$startW,$startH,0,0,$images2W,$images2H);
			break;
			case '5':
				$startH = round(($imagesH-$images2H)/2);
				$startW = round(($imagesW-$images2W)/2);
				imagecopy($dest, $src,$startW,$startH,0,0,$images2W,$images2H);
			break;
			default:
				for($a = 1; $a<$KolLogoInHeImg; $a++){
					for($i = 1; $i<$KolLogoInWhImg; $i++){
						imagecopy($dest, $src,$startW,$startH,0,0,$images2W,$images2H);
						$startW+= $images2W;
					}
					$startH+= $images2H;
					$startW = $GlobalW;
				}
			break;
		}
		$ext = strtolower(substr($name,strrpos($name,".")+1));
		switch ($ext){
			case "gif":			$f = 'imagegif';	break;
			case "jpg":
			case "jpeg":		$f = 'imagejpeg';	break;
			default:case "png":	$f = 'imagepng';	break;
   		}
		//чистим память
		if (!$f($dest,$name))return false;
		imagedestroy($dest);
		imagedestroy($src);
		return true;

	} else return false;
}

/**
 * конвертация hex в rgb
 * @param $color - цвет в hex
 * @return array - массив rgb
 */
function hex2rgb($color){
	$color = str_replace('#', '', $color);
	$rgb = array();
	if (strlen($color)==3) {
		for ($x=0; $x<3; $x++) $rgb[$x] = hexdec($color[$x].$color[$x]);
	}
	elseif (strlen($color)==6) {
		for ($x=0; $x<3; $x++) $rgb[$x] = hexdec($color[2*$x].$color[2*$x+1]);
	}
	else return array(0,0,0);
	return $rgb;
}