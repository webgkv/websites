<?php

/**
 * Language functions
 */

/**
 * Create $lang array
 * @param int|string $str - language ID or URL
 * @param string $type - url or ID [url|id]
 * @return array - language data array
 * @version v1.2.67
 * v1.2.67 - 404 for non-existent language
 */
function lang($str=false,$type='id') {
	global $config;
	$lang=false;
	// if not default language
	if ($str!=false) {
		if ($type == 'url') {
			$str = trim((string)$str);
			$str = trim($str, '/');
		}
		$where = $type=='id' ? "id = ".intval($str) : "url = '".mysql_res($str)."'";
		$lang = mysql_select("SELECT * FROM languages WHERE display=1 AND ".$where." LIMIT 1",'row',60*60);
		// DB may store a trailing slash in languages.url (e.g. fr/) — exact match would miss and fall back to wrong default.
		if ($lang==false && $type=='url' && $str!=='') {
			$lang = mysql_select("SELECT * FROM languages WHERE display=1 AND TRIM(TRAILING '/' FROM url) = '".mysql_res($str)."' LIMIT 1",'row',60*60);
		}
	}
	// language must always be set
	if ($lang==false) {
		$lang = mysql_select("SELECT * FROM languages WHERE display=1 ORDER BY `rank` DESC LIMIT 1", 'row', 60 * 60);
	}
	return $lang;
}

/**
 * Fetch word from dictionary by key, wraps in editable block if needed
 * Data is fetched from /files/languages/{ID}/dictionary/
 * @param string $str - word key (e.g. 'common|title')
 * @param string|array $editable - 'str'/'text' for inline editing or array of replacement values
 * @return string - translated word
 */
function i18n ($str,$editable=false) {
	global $lang;
	if (empty($lang)) $lang = lang();
	// auth functions
	require_once(ROOT_DIR.'functions/auth_func.php');
	$data = explode('|',$str);
	if (!isset($lang[$data[0]])) {
		if (file_exists(ROOT_DIR.'/files/languages/'.$lang['id'].'/dictionary/'.$data[0].'.php')) require (ROOT_DIR.'/files/languages/'.$lang['id'].'/dictionary/'.$data[0].'.php');
		else trigger_error('dictionary '.$str, E_USER_DEPRECATED);
	}
	// If user has admin access and $_GET['i18n'] is set, show keys instead of values for debugging
	if (isset($_GET['i18n']) && access('user admin')) {
		return str_replace('%s', $str, '{%s}');
	}
	else {
		// Replace {i} placeholders if $editable is an array
		if (is_array($editable)) {
			return (isset($lang[$data[0]][$data[1]])) ? template($lang[$data[0]][$data[1]],$editable) : '';
		}
		// Enable inline editing if user has permission
		elseif ($editable != false && access('editable dictionary')) {
			// HTML/editable helpers
			require_once(ROOT_DIR.'functions/html_func.php');
			if ($editable==true) $editable = 'str';
			$string = isset($lang[$data[0]][$data[1]]) ? $lang[$data[0]][$data[1]] : '';
			return '<span'.editable('dictionary|'.$str,$editable).'>'.$string.'</span>';
		}
		// Return plain dictionary value
		else return (isset($lang[$data[0]][$data[1]])) ? $lang[$data[0]][$data[1]] : '';
	}
}

/**
 * Fetch word from dictionary by key - admin panel only
 * @param string $str - word key
 * @return string - translated word
 */
function a18n ($str) {
	global $a18n;
	return (isset($a18n[$str])) ? $a18n[$str] : $str;
}

/**
 * Yandex Translator API integration
 * @param $translate string|array - text(s) to translate
 * @param string $lang - language pair (e.g. 'ru-en')
 * @return bool|string|array - translated text(s)
 * @version v1.2.62
 * v1.2.62 - added
 */
function translate_yandex ($translate,$lang='ru-en'){
	global $config;
	// free Yandex key - https://translate.yandex.ru/developers/keys
	$key = $config['yandex_translate'];
	// text to translate
	$translate_text = '';
	// translate API path
	$site = 'https://translate.yandex.net/api/v1.5/tr.json/translate';
	// request to Yandex
	$query = '';
	if(is_array($translate)){
		foreach($translate as $k => $v){
			$translate_text.= '&text='.urlencode($v);
		}
	}
	else{
		$translate_text = '&text='.urlencode($translate);
	}
	$query.= 'key='.$key;
	$query.= $translate_text;
	$query.= '&lang='.$lang;
	$query.= '&format=html';
	$context = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => 'Content-Type: application/x-www-form-urlencoded' . PHP_EOL,
			'content' => $query,
		),
	));
	if ($result = @file_get_contents($site, false, $context)) {
		$data = json_decode($result,true);
		if($data['code']==200){
			if(is_array($translate)){
				$array = array();
				$i = 0;
				foreach($translate as $k => $v){
					$array[$k]=$data['text'][$i];
					$i++;
				}
				return $array;
			}
			else {
				return $data['text'][0];
			}
		}
		else {
			return false;
		}
	}
	else {
		return false;
	}
}