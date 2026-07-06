<?php

function img_process2 ($type,$old,$size,$new = '',$quality = 90) {

	if (!is_file($old) || !preg_match('/resize|cut|fill/',$type)) return false;
	if ($new=='') $new = $old;
	$dir = dirname($new);
	if(is_dir($dir)||@mkdir($dir,0755,true)) {

		if(preg_match('#([^\.]+)$#iu',$old,$m))	$ext=$m[1];
		if($ext=='svg'||$ext=='gif') {
                        $new=preg_replace('#([^\.]+)$#iu',$ext,$new);
//			if ($old!=$new) 
			copy($old,$new);
			return true;
		} else {

			$size_new = explode('x', $size);
			$size_old = getimagesize($old);

			if ($size_old === false) {
				// WebP workaround for PHP < 7.1
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
//					log_add('img.txt', 'getimagesize ' . $old);
					return false;
				}
			}
		}

		$format	= strtolower(substr($size_old['mime'], strpos($size_old['mime'], '/') + 1));
		$oldformat	= $format;
		$fn_img		= 'image'.$format;
		$fn_img_create	= 'imagecreatefrom'.$format;

$format='webp';
$fn_img='image'.$format;

		if (!function_exists($fn_img_create)) {
//			log_add('img.txt','function_exists '.$fn_img_create);
			//unlink($old);
			return false;
		}
		$img1 = $fn_img_create($old);
		// Grayscale
		if ($bw = preg_match('/bw/',$type)) imagefilter($img1, IMG_FILTER_GRAYSCALE);

		$left = $top = $x = $y = 0;

		if(!$size_new[0]&&!$size_new[1]) {
			$size_new[0]=$size_old[0];
			$size_new[1]=$size_old[1];
		} elseif(!$size_new[1]) $size_new[1]=round($size_old[1]/$size_old[0]*$size_new[0]);
		elseif(!$size_new[0]) $size_new[0]=round($size_old[0]/$size_old[1]*$size_new[1]);

		$size_calc = $size_new;
		// Resize keeping aspect ratio
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
		// Resize to fit (crop excess)
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
		// Resize to fill (fill empty area)
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

		// Crop image region
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

		// Same size and no filter: copy as-is
//		if ($size_new[0]==$size_old[0] && $size_new[1]==$size_old[1] && $left==0 && $top==0 && !$bw) {
		if (5==4) {
			if ($old!=$new) copy($old,$new);
			exit;
		}
		// Different size or filter: convert
		else {
			$img2 = imageCreatetruecolor($size_new[0], $size_new[1]);
			// PNG/WebP transparency
			if ($format == 'png' || $format == 'webp') {
				// Enable progressive
				imageInterlace($img2, 1);
				// Preserve transparency
				$transparent = imagecolorallocatealpha($img2, 0, 0, 0, 127);
				imagefill($img2, 0, 0, $transparent);
				// Alpha blending
				imagesavealpha($img2, true);
			}
			// GIF transparency
			elseif ($format == 'gif') {
				// Enable progressive
				imageInterlace($img2, 1);
				// Check if source has transparency
				$transparent_index = imagecolortransparent($img1);
				if ($transparent_index >= 0) {
					// Get transparent color index
					$transparent_color = imagecolorsforindex($img1, $transparent_index);
					// Fill with transparent
					$transparent_index = imagecolorallocate($img2, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
					imagefill($img2, 0, 0, $transparent_index);
					imagecolortransparent($img2, $transparent_index);
				}
			}
			// Color fill for fill mode
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
			// v1.2.36 image optimization - uncomment when jpegoptim is available
			/*
			if ($format=='jpeg') {
				// Set path to jpegoptim
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

$request_url = explode('?',$_SERVER['REQUEST_URI'],2);
$req = $request_url[0];

// Static files for Download page (e.g. /images/download/Aviator-App-for-Header.webp)
if (preg_match('#^/images/download/([a-zA-Z0-9_.-]+\.(webp|png|jpg|jpeg|gif|svg))/?$#', $req, $sm)) {
	$basename = basename($sm[1]);
	$dir = ROOT_DIR . 'images/download' . DIRECTORY_SEPARATOR;
	$file = $dir . $basename;
	$realDir = realpath($dir);
if (file_exists($file) && is_file($file) && $realDir !== false && strpos(realpath($file), $realDir) === 0) {
		$ext = strtolower($sm[2]);
		$mimes = array('webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml');
		header('Content-Type: ' . (isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream'));
		readfile($file);
		exit;
	}
}

// Static files for Predictor page (e.g. /images/predictor/Aviator-Predictor.webp)
if (preg_match('#^/images/predictor/([a-zA-Z0-9_.-]+\.(webp|png|jpg|jpeg|gif|svg))/?$#', $req, $sm)) {
	$basename = basename($sm[1]);
	$dir = ROOT_DIR . 'images/predictor' . DIRECTORY_SEPARATOR;
	$file = $dir . $basename;
	$realDir = realpath($dir);
	if (file_exists($file) && is_file($file) && $realDir !== false && strpos(realpath($file), $realDir) === 0) {
		$ext = strtolower($sm[2]);
		$mimes = array('webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml');
		header('Content-Type: ' . (isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream'));
		readfile($file);
		exit;
	}
}

// Static files for Casinos page (e.g. /images/casinos/battery-aviator.webp)
if (preg_match('#^/images/casinos/([a-zA-Z0-9_.-]+\.(webp|png|jpg|jpeg|gif|svg))/?$#', $req, $sm)) {
	$basename = basename($sm[1]);
	$dir = ROOT_DIR . 'images/casinos' . DIRECTORY_SEPARATOR;
	$file = $dir . $basename;
	$realDir = realpath($dir);
	if (file_exists($file) && is_file($file) && $realDir !== false && strpos(realpath($file), $realDir) === 0) {
		$ext = strtolower($sm[2]);
		$mimes = array('webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml');
		header('Content-Type: ' . (isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream'));
		readfile($file);
		exit;
	}
}

// Static files for Games page (e.g. /images/games/Aviatrix-Header.webp)
if (preg_match('#^/images/games/([a-zA-Z0-9_.-]+\.(webp|png|jpg|jpeg|gif|svg))/?$#', $req, $sm)) {
	$basename = basename($sm[1]);
	$dir = ROOT_DIR . 'images/games' . DIRECTORY_SEPARATOR;
	$file = $dir . $basename;
	$realDir = realpath($dir);
	if (file_exists($file) && is_file($file) && $realDir !== false && strpos(realpath($file), $realDir) === 0) {
		$ext = strtolower($sm[2]);
		$mimes = array('webp'=>'image/webp','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','svg'=>'image/svg+xml');
		header('Content-Type: ' . (isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream'));
		readfile($file);
		exit;
	}
	if (preg_match('/^aviatrix-header\.webp$/i', $basename)) {
		if (!function_exists('site_brand_hero_image_path')) {
			@require_once ROOT_DIR . 'functions/site_brand.php';
		}
		$fallbacks = array();
		if (function_exists('site_brand_hero_image_path')) {
			$fallbacks[] = site_brand_hero_image_path();
		}
		$fallbacks[] = '/assets/images/aviator-main.webp';
		$fallbacks[] = '/assets/images/chickenroad-hero.webp';
		foreach ($fallbacks as $rel) {
			$abs = ROOT_DIR . ltrim($rel, '/');
			if (is_file($abs)) {
				$url = function_exists('site_brand_asset_url') ? site_brand_asset_url($rel) : $rel;
				header('Location: ' . $url, true, 302);
				exit;
			}
		}
	}
}

if(preg_match('#^/images/(.*/)(\d*)x(\d*)-([^-]+)-(.*)\.(webp|svg|gif)$#iu',$req,$m)) {
  $path  =$m[1];
  $width =$m[2];
  $height=$m[3];
  $format=$m[4];
  $file  =$m[5];
  $ext   =$m[6];
  $oldf=ROOT_DIR.'files/'.$path.$file.'.'.$format;

//$oldf=str_replace(
//array('/ticketoftheday/','/sportsbooks/','/casinos/'),
//array('/biletul-zilei/','/pariuri-sportive/','/cazinouri/'),
//$oldf);

  if(file_exists($oldf)) {
    $newf=ROOT_DIR.'images/'.$path.$width.'x'.$height.'-'.$format.'-'.$file.'.'.$ext;
    if(file_exists($newf)) {
      //already exists
      echo 'WTF';exit;
    } else {
      //need to create
      img_process2('cut',$oldf,$width.'x'.$height,$newf);
    }
    header('Content-type: image/'.$ext);
    echo file_get_contents($newf);
  } else {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
  }
} else {
  header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
}

?>