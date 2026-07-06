<?php
$config['sender_name']='none';
$config['sender']='none@gmail.com';
$config['receiver']='none@gmail.com,none2@gmail.com';
$config['html_minify']='0';
$config['open_graph']='0';
$config['redirects']='0';
$config['redirect_uppercase']='1';
$config['https']='1';
// $config['domain_main']='dev.1x2.uz';
// $config['domain_main_redirect']='1';
$config['dummy']='0';
$config['uploader']='1';
$config['brevo']='';

// Brand (Ice Fish)
$config['site_brand_name'] = 'Ice Fish';
$config['site_title_suffix'] = ' | Ice Fish';
$config['site_apk_filename'] = 'ice-fish.apk';
$config['site_hero_image'] = '/assets/images/ice-fish-hero.webp';
$config['site_default_og_image'] = '/assets/images/ice-fish-hero.webp';
$config['popup_after']=10; //seconds
// After deploying new CSS/JS/images: bump so browsers and CDN pick up new static files.
$config['assets_version'] = 26;

// Game demo (neutral keys; legacy *_{brand}_inout_* still read by game_demo_embed.php)
$config['game_demo_provider'] = 'inout';
$config['inout_demo_host'] = 'https://ice-fish.inout.games';
$config['inout_demo_game_mode'] = 'ice-fish';
$config['inout_demo_operator_id'] = 'ee2013ed-e1f0-4d6e-97d2-f36619e2eb52';
$config['inout_demo_auth_token'] = '247d3637-c5dc-a67b-50a1-89df27733343';
$config['inout_demo_currency'] = 'USD';
$config['inout_demo_skin_id'] = 'playdash';
$config['inout_demo_use_launch_api'] = false;
$config['cms_version'] = '1.4.28';

// Search indexing: SEO → Index rules in admin (not config.php).

$config['multilingual'] = true; // multilingual site on/off
$config['multilingual_u0'] = true; // use or not for primary language in URL u[0]
// Optional secret for production: ?debug_route=1&debug_route_key=... (see index.php route debug)
$config['debug_route_key'] = '';
// If auto-detect fails: set to `pages.id` of the Games landing row (module=pages, Games section in admin)
$config['games_landing_page_id'] = 0;

$config['http'] = @$config['https']==1 ? 'https':'http';
$config['domain'] = @$config['domain_main'] ? $config['domain_main'] : $_SERVER['HTTP_HOST'];
//v1.2.99 - кросдоменная авторизация - $config['.main_domain']
$config['.main_domain'] = '.'.@$config['domain_main'];
$config['.main_domain'] = false;
if ($config['.main_domain']) {
	session_set_cookie_params(60 * 60 * 24 * 30, "/", $config['.main_domain']);
}
$config['http_domain'] = $config['http'].'://'.$config['domain'];

// default map values
$config['map_lat'] = '55.755826';
$config['map_lng'] = '37.617299';
// keys for Google and Yandex maps
$config['google_map_key'] = '';
$config['yandex_map_key'] = '';
//firebase
$config['firebase_key'] = '';
$config['firebase_project'] = '';
$config['firebase_sender'] = '';

// AMP pages disabled by default
$config['amp'] = 0;

// local version (SERVER_ADDR is absent in CLI — avoid Undefined index)
$server_addr = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
$config['local'] = (
	(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1' && $server_addr === '127.0.0.1')
	|| (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost')
) ? true : false;

$config['smartoptimizer'] = false; //поставить true если nginx отдает статику и не работает смартоптимайзер

// v1.4.26 - WEBP check
$config['webp'] = false;
if(isset($_SERVER['HTTP_ACCEPT'])&&strpos($_SERVER['HTTP_ACCEPT'],'image/webp')!==false&&isset($_SERVER['HTTP_USER_AGENT'])&&strpos($_SERVER['HTTP_USER_AGENT'],' Chrome/')!==false) {
	$config['webp'] = true;
}
// to enable, install webp-convert
//https://github.com/rosell-dk/webp-convert
//composer require rosell-dk/webp-convert
$config['webp'] = false;

// image sizes for auto resize
$config['_imgs'] = array(
	'shop_products'=>array('150x150','_200x200','_300x300','400x400')
);

$config['mysql_server']		= 'localhost';
$config['mysql_username']	= 'dikodo_icefish';
$config['mysql_password']	= 'lN8joU4pbK2sgM6';
$config['mysql_database']	= 'dikodo_icefish';

//if(isset($_ENV['CI_COMMIT_REF_SLUG'])&&$_ENV['CI_COMMIT_REF_SLUG']=='main') {
//	$config['mysql_username']	= '1x2md_dev_user';
//	$config['mysql_password']	= 'lN8joU4pbK2sgM6';
//	$config['mysql_database']	= '1x2md_dev';
//}

/*
// override for local version
if ($config['local']) {
	$config['mysql_server'] = 'localhost';
	$config['mysql_username'] = 'root';
	$config['mysql_password']	= 'lN8joU4pbK2sgM6';
	$config['mysql_database'] = '1x2md';
}
*/

$config['mysql_charset']	= 'UTF8';
$config['mysql_connect']	= false; // DB not connected by default
$config['mysql_error']		= false; // DB connection error
$config['mysql_null'] = false; // for mysql_fn function

//timezone
$config['timezone'] = 'Europe/Bucharest';
date_default_timezone_set($config['timezone']);
$config['date'] = date('Y-m-d');
$config['datetime'] = date('Y-m-d H:i:s');

// styles folder
$config['style'] = 'templates';

// Demo page: iframe URL for Ice Fish (InOut Games). Leave empty to hide the block. Uses iDev.Games free embed.
// $config['game_demo_iframe_url'] = '';

//charset
$config['charset']			= 'UTF-8';

//debug
$config['debug'] = false; // set true to write all log_add logs

// payment methods (merchants) - commented out by default
/**/
$config['payments'] = array(
	// no payment
	1=> 'безналичный рассчет',
	//robokassa
	//100 => 'robokassa',
	//101 => 'robokassa [terminal]',
	//102 => 'robokassa [qiwi]',
	//103 => 'robokassa [card]',
	//104 => 'robokassa [wmr]',
	//105 => 'robokassa [yandex]',
	//yandex
	//200 => 'yandex',
	//201 => 'yandex [yandexmoney]',
	//202 => 'yandex [card]',
	//203 => 'yandex [webmoney]',
	//204 => 'yandex [qiwi]',
	//yandex2
	//250 => 'yandex2',
	//251 => 'yandex2 [yandexmoney]',
	//252 => 'yandex2 [card]',
	//253 => 'yandex2 [webmoney]',
	//254 => 'yandex2 [qiwi]',
	//todo qiwi
	//300 => 'qiwi',
	//alfabank
	//400 => 'alfabanki',
	//liqpay privatbank
	//500 => 'liqpay',//v.1.1.2
	//paypal
	//600 => 'paypal',
	//todo 2checkout
	//700 => '2checkout',
	//800=>'tinkoff'
    //900 => 'sberbank'
);
/**/

// social profiles
$config['user_socials'] = array(
	'genders'=>array(
		1=>'мужской',
		2=>'женский'
	),
	'types'=>array(
		1=>'vk',
		2=>'facebook',
		3=>'google',
		4=>'yandex',
		5=>'mailru'
	)
);

// array of all included css and js files
// {localization} - replaced with $lang['localization']
// ? replaced with site creation time get param
$config['sources'] = array(
	'bootstrap.css'             => '/plugins/bootstrap/css/bootstrap.min.css',
	'bootstrap.js'              => '/plugins/bootstrap/js/bootstrap.min.js',
	'common.css'				=> '/templates/css/common.css?',
	'common.js'				    => '/templates/scripts/common.js?',
	'editable.js'				=> '/templates/scripts/editable.js',
	'font.css'				    => '/templates/css/font.css',
	'lazysizes.js'				=> '/templates/scripts/lazysizes.min.js',
	'highslide'					=> array(
		'/plugins/highslide/highslide.packed.js',
		'/plugins/highslide/highslide.css',
	),
	'highslide_gallery' 		=> array(
		'/plugins/highslide/highslide-with-gallery.js',
		'/templates/scripts/highslide.js',
		'/plugins/highslide/highslide.css',
	),
	'jquery.js'					=> '/plugins/jquery/jquery-1.11.3.min.js',
	'jquery_cookie.js'			=> '/plugins/jquery/jquery.cookie.js',
	'jquery_ui.js'				=> '/plugins/jquery/jquery-ui-1.11.4.custom/jquery-ui.min.js',
	'jquery_ui.css'			    => '/plugins/jquery/jquery-ui-1.11.4.custom/jquery-ui.min.css',
	'jquery_localization.js'	=> '/plugins/jquery/i18n/jquery.ui.datepicker-{localization}.js',
	//'jquery_form.js'			=> '/plugins/jquery/jquery.form.min.js',
	'jquery_uploader.js'		=> '/plugins/jquery/jquery.uploader.js',
	'jquery_validate.js'		=> array(
		'/plugins/jquery/jquery-validation-1.8.1/jquery.validate.min.js',
		'/plugins/jquery/jquery-validation-1.8.1/additional-methods.min.js',
		'/plugins/jquery/jquery-validation-1.8.1/localization/messages_{localization}.js',
	),
	'jquery_multidatespicker.js'=> '/plugins/jquery/jquery-ui.multidatespicker.js',
	'reset.css'					=> '/templates/css/reset.css',
	'tinymce.js'				=> '/plugins/tinymce/tinymce.min.js', // legacy tinymce
	'tinymce.js'				=> '/plugins/tinymce_4.3.11/tinymce.min.js',
	//v1.3.35
	'yandex_map'=>'<script src="https://api-maps.yandex.ru/2.1/?apikey='.$config['yandex_map_key'].'&lang=ru_RU" type="text/javascript"></script>',
	//v1.2.71
	'google_map'=>'<script src="https://maps.googleapis.com/maps/api/js?language={localization}&key='.$config['google_map_key'].'" type="text/javascript"></script>',
	'google_markerclusterer'=>'/templates/scripts/markerclusterer.js',
);

error_reporting(E_ALL);
//error_reporting(0);
// override for local version
if ($config['local']) {
	//set_error_handler('error_handler');
	Error_Reporting(E_ALL & ~E_NOTICE);
}
else {
	set_error_handler('error_handler');
}

ini_set('session.cookie_lifetime', 0);
ini_set('magic_quotes_gpc', 0);

header('Content-type: text/html; charset='.$config['charset']);
header('X-UA-Compatible: IE=edge');

session_start();

// error handler
function error_handler($errno,$errmsg,$file,$line) {

	// This error code is not included in error_reporting
	if (in_array($errno,array(8192,8))) return;
	//if (!(error_reporting() & $errno)) return;
	// do not log simple notices
	if ($errno==E_USER_NOTICE) return true;
	// logs folder
	$dir = ROOT_DIR.'logs';
	// log file
	$log_file_name = $dir.'/error_'.date('Y-m').'.txt';
	// append or overwrite file
	$write = 'a'; // append to file
	// log file size
	$size = 0;
	// max log file size
	$max_size = 100000; // 1 megabyte
	if (file_exists($log_file_name)) {
		$size = filesize($log_file_name);
		if ($size>$max_size) $write = 'w';
	}
	// log line
	$err_str = date('d H:i');
	$err_str.= "\t".$errno;
	$err_str.= "\tfile://".$file;
	$err_str.= "\t".$line;
	$err_str.= "\thttp://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$err_str.= "\t".$errmsg;
	$err_str.= "\r\n";
	// create folder
	if (!is_dir($dir)) mkdir($dir);
	// write to file
	$fp = fopen($log_file_name, $write);
	fwrite($fp,$err_str);
	fclose($fp);
	// fatal error
	if ($errno==E_USER_ERROR) exit(1);
	// do not run PHP internal error handler
	return true;

}

/**
 * @param $type - URL type
 * @param bool $q - values array
 * @return string - URL
 * @version v1.2.59
 * v1.1.0 - added
 * v1.2.20 - module URL handling
 * v1.2.46 - order URL fix
 * v1.2.56 - index, orders, subscribe
 * v1.2.59 - lang
 */
function get_url($type='',$q=false,$lngid=''){
	global $abc,$config,$langid,$lang;
	if($lngid=='') $lngid=$langid;
        if($lngid=='1') $lngid='';

	$url = '/';
	if ($type === 'index') {
		$url = '/';
	} elseif (is_array($q)) {
		// Homepage (module=index) is always the language root — never /home/ from pages.url or content_i18n.
		if ($type === 'page' && isset($q['module']) && (string)$q['module'] === 'index') {
			$url = '/';
		} else {
		// Empty url$langid must not win over isset() — otherwise path becomes "//" → with lang prefix only /{lang}/ (home).
		$seg = '';
		if (isset($q["url$lngid"])) {
			$seg = trim((string)$q["url$lngid"], '/');
		}
		if ($seg === '' && $type === 'page' && isset($q['url'])) {
			$seg = trim((string)$q['url'], '/');
		}
		if ($seg === '' && $type === 'page' && !empty($q['id']) && function_exists('page_i18n_slug')) {
			$lid = isset($lang['id']) ? (int)$lang['id'] : (isset($abc['lang']['id']) ? (int)$abc['lang']['id'] : 0);
			if ($lid > 0) {
				$slug = page_i18n_slug((int)$q['id'], $lid);
				if ($slug !== null && $slug !== '') {
					$seg = $slug;
				}
			}
		}
		if ($seg !== '') {
			$url = '/' . $seg . '/';
		} else {
			$url = '/'.@$abc['modules'][$type]['url'.$lngid].'/';
		}
		}
	} else {
		$url = $type == 'index'?'/':'/'.@$abc['modules'][$type]['url'.$lngid].'/';
	}
	if ($config['multilingual']) {
		// u[0] for language in URL
		if ($config['multilingual_u0']==true) {
			$langSeg = trim((string) @$abc['languages'][($lngid?$lngid:'1')]['url'], '/');
			$pathRest = trim($url, '/');
			if ($langSeg === '') {
				$url = '/' . ($pathRest !== '' ? $pathRest . '/' : '');
			} else {
				$url = '/' . $langSeg . '/' . ($pathRest !== '' ? $pathRest . '/' : '');
			}
		}
	}
	return preg_replace('#/+#', '/', $url);
}

function get_url1($type='',$q=false,$param=''){
	global $modules,$config,$lang;
	//v1.2.59
	if (!isset($lang)) {
		$lang = lang();
	}
	if (!isset($modules)) {
		$modules = mysql_select("
			SELECT url name,module id
			FROM pages
			WHERE module!='pages' AND language=".$lang['id']." AND display=1
	  	",'array',60*60);
	}
	$url = '';
	// v1.2.56 - index (1.2.74 - fixed paginator index URL)
	if ($type=='index') {
		$url = '/';
		// v1.2.125 - index for language
		if ($q AND $q['id']!=1) {
			$url = '/' . $q['url'] . '/';
		}
		return $url;
	}
	// pages
	elseif ($type=='page') {
		$url = $q['module'] == 'index' ? '/' : '/' . $q['url'] . '/';
	}
	// products
	elseif ($type=='shop_product') {
		if (@$modules['shop']) {
			get_data('shop_categories');
			$category = get_data('shop_categories',$q['category']);
			$url = get_url('shop_category',$category);
			$url.= $q['id'] . '-' . $q['url'.$lang['i']] . '/';
			return $url;
		}
	}
	// categories
	elseif ($type=='shop_category') {
		if (@$modules['shop']) {
			$url = '/' . $modules['shop'] . '/' . $q['id'] . '-' . $q['url' .$lang['i']]. '/';
		}
	}
	// news
	elseif ($type=='news') {
		if (@$modules['news']) {
			$url = '/' . $modules['news'] . '/';
			// news item page
			if ($q) {
				$url.= $q['url'] . '/';
			}
		}
	}
	//галлерея
	elseif ($type=='gallery') {
		if (@$modules['gallery']) {
			$url = '/' . $modules['gallery'] . '/' .  $q['id'] . '-'.$q['url']. '/';
		}
	}
	// basket
	elseif ($type=='basket') {
		if (@$modules['basket']) {
			$url = '/' . $modules['basket'] . '/';
			if ($q) {
				//v1.2.46
				if (is_array($q)) {
					$url.= $q['id'] . '/' . md5($q['id'] . $q['created_at']) . '/';
				}
				else {
					$url.= $q.'/';
				}
			}
		}
		else return false;
	}
	// v1.2.56 - orders
	elseif ($type=='orders') {
		if (@$modules['profile']) {
			$url = get_url('profile','orders').  $q['id'].'/';
			return $url;
		}
	}
	// v1.2.56 - subscribe
	elseif ($type=='subscribe') {
		if (@$modules['subscribe']) {
			$url = '/'.$modules['subscribe'].'/';
			if ($q=='unsubscribe') {
				$url.= 'unsubscribe/'.@$param['receiver'].'/'.md5(@$param['receiver'].md5(@$param['date'])).'/';
			}
		}
	}
	// if $type equals module v.1.2.20
	elseif ($type) {
		if (isset($modules[$type])) {
			$url = '/' . $modules[$type] . '/';
			if ($q AND is_string($q)) {
				$url .= $q . '/';
			}
		}
	}
	// return empty if URL was not built v.1.2.20
	if ($type AND $url=='') return false;
	// append language at end
//	if ($config['multilingual']) {
//		// u[0] for language in URL
//		if ($lang['id']!=1 OR $config['multilingual_u0']==true) {
//			$url = '/' . $lang['url'] . $url;
//		}
//	}
	// v1.2.131 - AMP pages
	if ($param=='amp') $url.= '?view=amp';
	return $url;
}