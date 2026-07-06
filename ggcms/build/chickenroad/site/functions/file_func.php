<?php

/**
 * File operations helpers.
 */

/**
 * Recursively delete directory and its contents.
 * For safe deletion, pass $dir with trailing slash.
 * @param string $dir Full path to directory
 * @param bool $i Whether to remove the directory itself
 * @return bool
 * @version v1.1.22
 */
function delete_all($dir,$i = true) {
	$dir = str_replace('\\','/',$dir);
	if (strpos($dir, '//')) return false;
	if (substr($dir, -1)=='/') $dir = substr($dir, 0, -1);
	if (is_file($dir)) return unlink($dir);
	if (!is_dir($dir)) return false;
	$dh = opendir($dir);
	while (false!==($file = readdir($dh))) {
		if ($file=='.' || $file=='..') continue;
		delete_all($dir.'/'.$file);
	}
	closedir($dh);
	if ($i==true) return rmdir($dir);
}


/**
 * Copy directory with files.
 * @param string $src Source path
 * @param string $dst Destination path
 */
function rcopy($src, $dst) {
	if (file_exists($dst)) delete_all($dst);
	if (is_dir($src)) {
		mkdir($dst);
		$files = scandir($src);
		foreach ($files as $file)
			if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file");
	}
	else if (file_exists($src)) copy($src, $dst);
}

/**
 * Copy file with optional preview generation.
 * @param string $temp_file Full path to temp file
 * @param string $root Root images path, always with trailing slash
 * @param string $file Filename
 * @param array $param Image params
 * @return bool
 * @version v1.1.49, v1.2.49 - SVG upload
 */
function copy2 ($temp_file,$root,$file,$param=array()) {
	if (strpos($root, '//') OR strpos($root, '\\\\')) return false;
	if (is_dir($root)) delete_all($root,false);
	if ($temp_file && (is_dir($root) || mkdir ($root,0755,true))) {
		include_once(ROOT_DIR . 'functions/image_func.php');
		if (is_array($param)) {
			$param['a-'] = 'resize 100x100'; // admin preview
			$exb = substr($file,-3);
			foreach ($param as $k => $v) {
				if ($exb=='svg') $v = '';
				if ($v) {
					$prm = explode(' ', $v);
					img_process($prm[0], $temp_file, $prm[1], $root . $k . $file);
					if (isset($prm[2])) img_watermark($root . $k . $file, ROOT_DIR . 'templates/images/' . $prm[2], $root . $k . $file, isset($prm[3]) ? $prm[3] : '');
				}
				else copy($temp_file, $root . $k . $file);
			}
		}
		else {
			img_process('resize', $temp_file, '100x100', $root . 'a-' . $file);    // admin preview
			copy($temp_file, $root . $file);
		}
		if (is_file($root.$file)) {
			return true;
		}
	}
	return false;
}

/**
 * чтение содержимого папки
 * @param $dir - путь к папке
 * @return array - массив с файлами
 * @version v1.2.36
 * v1.2.36 - добавлена
 */
function scandir2 ($dir,$recurcive=false) {
	$dir = str_replace('\\','/',$dir);
	if (strpos($dir, '//')) return false;
	if (substr($dir, -1)=='/') $dir = substr($dir, 0, -1);
	$files = array();
	$array = scandir($dir,1);
	//dd($array,true);
	foreach ($array as $k=>$v) {
		if ($v!='.' AND $v!='..') {
			$file = $dir.'/'.$v;
			if ($recurcive AND is_dir($file)) {
				$file.= '/';
				// nested arrays variant: //$files[] = scandir2 ($file,true);
				// flat list variant
				$files = array_merge($files,scandir2 ($file,true));
			}
			else $files[] = $file;
		}
	}
	return $files;
}
