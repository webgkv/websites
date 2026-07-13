<?php

// Common functions
/*
 * v1.4.4 - html_array for tables
 * v1.4.26 - added webp generation
 */

// Strip slashes from $_REQUEST data
function stripslashes_smart($post) {
	if (get_magic_quotes_gpc()) {
		if (is_array($post)) {
			foreach ($post as $k=>$v) {
				$q[$k] = stripslashes_smart($v);
			}
		}
		else $q = stripslashes($post);
	}
	else $q = $post;
	return $q;
}

// Build URL query from $_GET
function build_query($key = '') {
	$get = $_GET;
	if ($key) {
		$array = explode(',',$key);
		foreach ($array as $k=>$v) unset($get[$v]);
	}
	return http_build_query($get);
}

// Create log file in logs directory
/**
 * @param $file - filename in /logs/
 * @param $string - string or array of data to be logged
 * @param bool $debug - if true, logs are only written if $config['debug'] is true
 */
function log_add($file,$string,$debug=false) {
	global $config;
	// Debug logs are not created when $config['debug'] is off
	if ($debug==false OR $config['debug'] == true) {
		if (!is_dir(ROOT_DIR . 'logs')) mkdir(ROOT_DIR . 'logs');
		$fp = fopen(ROOT_DIR . 'logs/' . $file, 'a');
		// Convert array to string for logging
		if (is_array($string)) {
			$content = '';
			foreach ($string as $k=>$v) {
				if (is_array($v)) $content.= $k.':'.serialize($v)."\t";
				else $content.= $k.':'.$v."\t";
			}
			$string = $content;
		}
		fwrite($fp, $string . PHP_EOL);
		fclose($fp);
	}
}

// Get user IP
function get_ip(){
	$ip = '';
	if(!empty($_SERVER['HTTP_X_REAL_IP'])) {//check ip from share internet
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	}
	elseif(!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

// Determine city by IP
/**
 * @param $ip - user IP
 * @param int $level 1 - country, 2 - region, 3 - city
 * @return array geo - geo base array, country - country, region - region, city - city
 * @version v1.2.128
 * added v.1.2.128
 */
function geo_data ($ip,$level=3) {
	if (!isset($_SESSION)) session_start();
	if (!isset($_SESSION['geo']) OR $_SESSION['geo']==false) {
		require_once(ROOT_DIR . 'functions/mysql_func.php');    // DB functions
		require_once(ROOT_DIR . 'functions/string_func.php');    // String functions
		require_once(ROOT_DIR . 'plugins/SypexGeo_2.2/SxGeo.php');
		// CMS doesn't store city base by default, download from here:
		// https://sypexgeo.net/ru/download/
		// Sypex Geo City UTF-8
		// https://sypexgeo.net/files/SxGeoCity_utf8.zip
		$SxGeo = new SxGeo(ROOT_DIR . 'plugins/SypexGeo_2.2/SxGeoCity.dat');
		//$SxGeo = new SxGeo(ROOT_DIR.'plugins/SypexGeo_2.2/SxGeoCity.dat', SXGEO_BATCH | SXGEO_MEMORY); // Most performant mode for bulk IP processing

		// Default display status for countries, regions, cities
		$display = 1;

		// Return array
		$data = array(
			'geo' => false,
			'country' => false,
		);
		if ($level > 1) $data['region'] = false;
		if ($level > 2) $data['city'] = false;

		if ($supex_geo = $SxGeo->getCityFull($ip)) {
			$data['geo'] = $supex_geo;
			// 1. Select country
			$data['country'] = mysql_select("
				SELECT * FROM geo_countries 
				WHERE iso='" . $supex_geo['country']['iso'] . "'
				ORDER BY rank DESC,name LIMIT 1
			", 'row');

			// 2. Add country if missing
			if ($data['country']==false) {
				$data['country'] = array(
					'uid'       => $supex_geo['country']['id'],
					'iso'       => $supex_geo['country']['iso'],
					'lat'       => $supex_geo['country']['lat'],
					'lng'       => $supex_geo['country']['lon'],
					'name'      => $supex_geo['country']['name_ru'],
					'name2'     => $supex_geo['country']['name_en'],
					'url'       => trunslit($supex_geo['country']['name_ru']),
					'display'   => $display // Off by default
				);
				$data['country']['id'] = mysql_fn('insert', 'geo_countries', $data['country']);
			}

			// 3. Update country data
			$country = array();
			if ($data['country']['lat'] == '') {
				$country['lat'] = $supex_geo['country']['lat'];
				$country['lng'] = $supex_geo['country']['lon'];
			}
			if ($data['country']['uid'] == 0) {
				$country['uid'] = $supex_geo['country']['id'];
			}
			if ($country) {
				$country['id'] = $data['country']['id'];
				mysql_fn('update', 'geo_countries', $country);
				$data['country'] = array_merge($data['country'], $country);
			}

			// 4. Resolve region if country is enabled
			if ($supex_geo['region'] AND $data['country']['display'] == 1 AND $level > 1) {
				// 1. Select region
				$data['region'] = mysql_select("
					SELECT * FROM geo_regions 
					WHERE iso='" . $supex_geo['region']['iso'] . "'
						OR uid = '" . $supex_geo['region']['id'] . "'
						OR LOWER(name) = '" . mysql_res(mb_strtolower($supex_geo['region']['name_ru'], 'UTF-8')) . "'
					ORDER BY rank DESC,name LIMIT 1
				", 'row');

				// 2. Add region if missing
				if ($data['region']==false) {
					$data['region'] = array(
						'uid'       => $supex_geo['region']['id'],
						'country'   =>$data['country']['id'],
						'iso'       => $supex_geo['region']['iso'],
						'name'      => $supex_geo['region']['name_ru'],
						'name2'     => $supex_geo['region']['name_en'],
						'url'       => trunslit($supex_geo['region']['name_ru']),
						'display'   => $display // Off by default
					);
					$data['region']['id'] = mysql_fn('insert', 'geo_regions', $data['region']);
				}

				// 3. Update region data
				$region = array();
				if ($data['region']['iso'] == '') {
					$region['iso'] = $supex_geo['region']['iso'];
				}
				if ($data['region']['uid'] == 0) {
					$region['uid'] = $supex_geo['region']['id'];
				}
				if ($region) {
					$region['id'] = $data['region']['id'];
					mysql_fn('update', 'geo_regions', $region);
					$data['region'] = array_merge($data['region'], $region);
				}

				// 4. Resolve city if region is enabled
				if ($supex_geo['city'] AND $data['region']['display'] == 1 AND $level > 2) {
					// 1. Select city
					$data['city'] = mysql_select("
						SELECT * FROM geo_cities 
						WHERE uid='" . $supex_geo['city']['id'] . "'
							OR LOWER(name) = '" . mysql_res(mb_strtolower($supex_geo['city']['name_ru'], 'UTF-8')) . "'
						ORDER BY rank DESC,name LIMIT 1
					", 'row');

					// 2. Add city if missing
					if ($data['city']==false) {
						$data['city'] = array(
							'uid'       => $supex_geo['city']['id'],
							'region'    =>$data['region']['id'],
							'country'   =>$data['country']['id'],
							'lat'       => $supex_geo['city']['lat'],
							'lng'       => $supex_geo['city']['lon'],
							'name'      => $supex_geo['city']['name_ru'],
							'name2'     => $supex_geo['city']['name_en'],
							'url'       => trunslit($supex_geo['city']['name_ru']),
							'display'   => $display // Off by default
						);
						$data['city']['id'] = mysql_fn('insert', 'geo_cities', $data['city']);
					}

					// 3. Update city data
					$city = array();
					if ($data['city']['lat'] == '') {
						$city['lat'] = $supex_geo['city']['lat'];
						$city['lng'] = $supex_geo['city']['lon'];
					}
					if ($data['city']['uid'] == 0) {
						$city['uid'] = $supex_geo['city']['id'];
					}
					if ($city) {
						$city['id'] = $data['city']['id'];
						mysql_fn('update', 'geo_cities', $city);
						$data['city'] = array_merge($data['city'], $city);
					}

					// 4. Clear city if disabled
					if ($data['city']['display'] == 0) $data['city'] = false;
				}

				// 5. Clear region if disabled
				if ($data['region']['display'] == 0) $data['region'] = false;
			}

			// 5. Clear country if disabled
			if ($data['country']['display'] == 0) $data['country'] = false;
		}
		// Fallback to defaults if resolution fails
		if ($data['country'] == false) {
			$data['country'] = mysql_select("
				SELECT * FROM geo_countries 
				WHERE display=1
				ORDER BY rank DESC,name LIMIT 1
			", 'row');
		}
		if ($level > 1 AND $data['region'] == false AND $data['country']) {
			$data['region'] = mysql_select("
				SELECT * FROM geo_regions 
				WHERE country=" . $data['country']['id'] . " AND display=1
				ORDER BY rank DESC,name LIMIT 1
			", 'row');
		}
		if ($level > 2 AND $data['city'] == false AND $data['region']) {
			$data['city'] = mysql_select("
				SELECT * FROM geo_cities 
				WHERE region=" . $data['region']['id'] . " AND display=1
				ORDER BY rank DESC,name LIMIT 1
			", 'row');
		}
		$_SESSION['geo'] = $data;
	}
	else $data = $_SESSION['geo'];
	return $data;
}

/**
 * Get value from config and return default value if empty.
 * Ex. config('mysql_server', 'localhost');
 * Or config('mysql.server', 'localhost') for get multidimensional array value
 * 
 * @global array $config
 * @param string $key
 * @param mixed $default
 * @return mixed
 * added v.1.1.21
 */
function config($key, $default = NULL) {
	global $config;

	if(strpos($key, '.')) 
	{
	    $array = $config;            
	    foreach (explode('.', $key) as $segment) {                
		if (isset($array[$segment])) {
		    $array = $array[$segment];
		} else {
		    return $default;
		}
	    }

	    return $array;
	} 
	else 
	{
	    return (isset($config[$key])) ? $config[$key] : $default;
	}                                
}

/*
 * HTML minification function
 * @param $body - raw HTML code
 * @return mixed - minified HTML code
 * @version v1.2.11
 * v.1.1.8 - added
 * v.1.2.11 - fully updated
*/
function html_minify ($body) {
	// remove redundant (white-space) characters
	$replace = array(
		// remove tabs before and after HTML tags
		'/\>[^\S ]+/s'   => '>',
		'/[^\S ]+\</s'   => '<',
		// shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
		'/([\t ])+/s'  => ' ',
		// remove leading and trailing spaces
		'/^([\t ])+/m' => '',
		'/([\t ])+$/m' => '',
		// remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
		'~//[a-zA-Z0-9 ]+$~m' => '',
		// remove empty lines (sequence of line-end and white-space characters)
		'/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
		// remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
		'/\>[\r\n\t]+\</s'    => '><',
		// keep single space between tags when present
		'/\>[ ]+\</s'    => '> <',
		// remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
		'/}[\r\n\t ]+/s'  => '}',
		'/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
		// remove new-line after JS's function or condition start; join with next line
		'/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
		'/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
		// remove new-line after JS's line end (only most obvious and safe cases)
		'/\),[\r\n\t ]+/s'  => '),',
		// remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
		'~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
	);
	$body = preg_replace(array_keys($replace), array_values($replace), $body);

	// remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
	$remove = array(
		'</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
	);
	$body = str_ireplace($remove, '', $body);
	return $body;
}

/**
 * Debugging function to print data in a readable format
 * @param $data - values to output
 * @param bool $die - whether to terminate script execution
 * @version v1.1.30
 * v.1.1.30 - added
 */
function dd($data,$die=false) {
	echo '<pre>';
	print_r($data);
	echo '</pre>';
	if ($die) die();
}

/*
 * Build image URL
 * @param string $table - table name
 * @param int $id - record ID
 * @param string $key - image key
 * @param string $img - image filename
 * @param string $p - preview type
 * @return string
 * @version v1.2.101
 * v.1.1.23 - added
 * v1.2.101 - multiple images support
 * v1.2.115 - multi-image improvements
 * v1.4.26 - added webp generation
 */
function get_img($table,$q,$key='img',$p='') {
	global $config;
	// Debug for _imgs
	if (@$_GET['_imgs']) {
		return '/debug/'.$q[$key];
	}
	$img = ''; // Image path
	// Full domain path can be specified here
	$site = '';
	$val = isset($q[$key]) ? (string)$q[$key] : '';
	// Path in DB (files/media/… or legacy files/…)
	if ($val !== '' && strpos($val, '/') !== false) {
		$base = '/' . ltrim(str_replace('\\', '/', $val), '/');
		if ($p === 'a-') {
			$dir = dirname($base);
			$fn = basename($base);
			$preview = $dir . '/' . $p . $fn;
			$img = is_file(ROOT_DIR . ltrim($preview, '/')) ? $preview : $base;
		} else {
			$img = $base;
		}
	}
	// games: legacy filename only → /images/games/
	elseif ($table === 'games' && $val !== '' && strpos($val, '/') === false) {
		$img = '/images/games/'.$p.$val;
	}
	// Single image in per-record folder (legacy)
	elseif ($val !== '' && !empty($q['id'])) {
		$img = '/files/'.$table.'/'.$q['id'].'/'.$key.'/'.$p.$val;
	}
	// v1.2.101 - multiple images
	elseif (strpos($key, '/')) {
		$exp = explode('/',$key);
		if (isset($exp[1]) AND isset($q[$exp[0]])) {
			if (is_array($q[$exp[0]])) $imgs = $q[$exp[0]];
			else $imgs = unserialize($q[$exp[0]]);
			if (isset($imgs[$exp[1]])) {
				$img = '/files/' . $table . '/' . $q['id'] . '/' . $key . '/' . $p . $imgs[$exp[1]]['file'];
			}
		}
		return '';
	}
	// If image exists
	if ($img) {
		if ($config['webp']) {
			require_once(ROOT_DIR . 'functions/webp_func.php');
			$img = img_webp($img);
		}
		return $site.$img;
	}
	// Placeholder
	else {
		// Default for admin
		if ($p=='a-') return $site.'/admin/templates/imgs/no_img.png';
		// Default for site
		else return $site.'/templates/images/no_img.svg';
	}
}

/**
 * Absolute path on disk for a main image field (cache bust, exists checks).
 */
function content_img_disk_path($table, $q, $key = 'img') {
	if (empty($q[$key])) {
		return '';
	}
	$v = str_replace('\\', '/', ltrim((string)$q[$key], '/'));
	if (strpos($v, '/') !== false) {
		return ROOT_DIR . $v;
	}
	if ($table === 'games') {
		return ROOT_DIR . 'images/games/' . $v;
	}
	if ($table === 'casino_articles' || $table === 'casinos') {
		$p = ROOT_DIR . 'images/casinos/' . $v;
		if (is_file($p)) {
			return $p;
		}
	}
	if (!empty($q['id'])) {
		return ROOT_DIR . 'files/' . $table . '/' . (int)$q['id'] . '/' . $key . '/' . $v;
	}
	return '';
}

// Get array of images with full paths
/**
 * @param string $table - material table
 * @param array $q - data array
 * @param string $key - images field
 * @param string $p - preview suffix
 * @return array - array of images with full path (_) and preview
 * @version v1.2.101
 * v.1.2.101 - added
 */
function get_imgs ($table = '', $q, $key = 'imgs',$p=''){
	$images = array();
	$data = $q[$key] ? unserialize($q[$key]) : array();
	$path = '/images/'.$table.'/'.$q['id'].'/'.$key.'/';
	if(is_array($data)) {
		foreach ($data as $k=>$v) {
			if(!empty($v['display'])) {
				$images[$k] = $v;
				// Full path
				if(preg_match('#^(.*)\.([^\.]+)$#iu',$v['file'],$m)) {
					$fn=$m[1];
					$fe=$m[2];
					$images[$k]['_'] = $path . $k . '/768x-'.$fe.'-'.$fn.'.webp';
					// Preview path
					if ($p) {
						$images[$k]['_' . $p] = $path . $k . '/' . $p . $v['file'];
					}
				}
			}
		}
	}
	return $images;
}

/**
 * Inline SVG insertion
 * Useful for UI icons
 * @param $img - svg filename
 * @param $path - path to svg folder (default templates/images/)
 * @return string svg content
 * @version v1.1.26
 * v.1.1.26 - added
 */
function get_svg ($img,$path='templates/images/'){
	return file_get_contents(ROOT_DIR.$path.$img);
}

/**
 * Pre-cache data arrays to avoid repeated DB queries
 * @param $table - table name
 * @param $id - record ID
 * @param string $label - space-separated fields for string output
 * @return string|array
 * @version v1.2.23
 * v.1.2.23 - added
 */
function get_data($table,$id=true,$label='') {
	global $config;
	// Returns entire table as array
	if ($id===true) {
		if (!isset($config['_'.$table])) {
			$config['_' . $table] = mysql_select("SELECT * FROM `" . $table . "` WHERE 1", 'rows_id');
		}
		return $config['_' . $table];
	}
	// Returns specific record by ID
	if (!isset($config['_'.$table][$id])) {
		if ($config['_'.$table][$id] = mysql_select("SELECT * FROM `".$table."` WHERE id=".intval($id),'row')) {
			// Default value
			$config['_'.$table][0] = array(
				'name'=>'',
			);
		}
		else return false;
	}
	if ($label) {
		$array = explode(' ',$label);
		$content = '';
		foreach ($array as $k=>$v) {
			$content.= $config['_'.$table][$id][$v].' ';
		}
		return trim($content);
	}
	else return $config['_'.$table][$id];
}

// Array utility functions

/**
 * Sort multidimensional array by key
 * @param $array - input array
 * @param $key - sorting key
 * @param string $sort - direction (ASC/DESC)
 * @return array - sorted array
 */
function array_sort($array,$key,$sort = 'ASC') {
	usort($array, function($a,$b) use ($key){
		return strnatcasecmp($a[$key], $b[$key]);
	});
	if ($sort == 'DESC') return array_reverse($array);
	else return $array;
}

/**
 * Transpose input arrays and save input keys.
 *
 * Example inputs:
 *
 * <code>
 * <input name="name[]" value="Alex"><br>
 * <input name="post[]" value="Actor"><br>
 * <input name="email[]" value="alex_actor@mail.dev"><br>
 * </code>
 *
 * input as
 *
 * <code>
 * [
 *  'name' => ['Alex', 'Born', 'Cindal'],
 *  'post' => ['Actor', 'Banker', 'Conductor'],
 *  'email' => ['alex_actor@mail.dev', 'born_banker@mail.dev', 'cindal_conductor.dev']
 * ];
 * </code>
 * output as
 * <code>
 * [
 *  0 => [
 *      'name'  => 'Alex',
 *      'post'  => 'Actor',
 *      'email' => 'alex_actor@mail.dev'
 *  ],
 *       1 => [
 *           'name'  => 'Born',
 *           'post'  => 'Banker',
 *           'email' => 'born_banker@mail.dev'
 *       ],
 *       2 => [
 *           'name'  => 'Cindal',
 *           'post'  => 'Conductor',
 *           'email' => 'cindal_conductor.dev'
 *       ],
 *   ];
 * </code>
 *
 * @param array $inputArray
 * @return array
 */
function array_transpose(array $inputArray){
	$outputArray = array();
	foreach ($inputArray as $dataKey=>$dataValues) {
		foreach ($dataValues as $k=>$v) {
			$outputArray[$k][$dataKey] = $v;
		}
	}
	return $outputArray;
}
