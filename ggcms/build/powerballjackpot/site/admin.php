<?php

/*
changelog
v1.3.32 - SMS auth
v1.4.0 - html_render in admin
*/

define('ROOT_DIR', dirname(__FILE__).'/');
require_once(ROOT_DIR.'config/config.php');
require_once(ROOT_DIR.'functions/brand_profile.php');
require_once(ROOT_DIR.'admin/config.php');	// admin config

// HARD DEBUG BANNER (admin.php top) - visible even if access redirects to login.
// Enabled by GET/POST param: edit_debug=1
$__edit_debug_on = (!empty($_GET['edit_debug']) && (string)$_GET['edit_debug'] === '1')
	|| (!empty($_POST['edit_debug']) && (string)$_POST['edit_debug'] === '1');
if ($__edit_debug_on) {
	$__dbg_get = isset($_GET) && is_array($_GET) ? array_keys($_GET) : array();
	$__dbg_post = isset($_POST) && is_array($_POST) ? array_keys($_POST) : array();
	$__dbg_m = isset($_GET['m']) ? (string)$_GET['m'] : '';
	$__dbg_u = isset($_GET['u']) ? (string)$_GET['u'] : '';
	$__dbg_id = isset($_GET['id']) ? (string)$_GET['id'] : '';
	$__dbg_tab = isset($_GET['tab']) ? (string)$_GET['tab'] : '';
	$__dbg_inline = isset($_GET['inline']) ? (string)$_GET['inline'] : '';
	$__dbg_ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
	echo '<div style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#111;color:#f2f2f2;font:12px/1.3 monospace;padding:8px 10px;border-bottom:2px solid #ff5252;">';
	echo 'edit_debug=1 ACTIVE';
	echo ' | ip=' . htmlspecialchars($__dbg_ip);
	echo ' | m=' . htmlspecialchars($__dbg_m);
	echo ' | u=' . htmlspecialchars($__dbg_u);
	echo ' | id=' . htmlspecialchars($__dbg_id);
	echo ' | tab=' . htmlspecialchars($__dbg_tab);
	echo ' | inline=' . htmlspecialchars($__dbg_inline);
	echo ' | GET_keys=' . htmlspecialchars(implode(',', $__dbg_get));
	echo ' | POST_keys=' . htmlspecialchars(implode(',', $__dbg_post));
	echo '</div>';
}

// Load functions
require_once(ROOT_DIR.'functions/admin_func.php');	// admin
require_once(ROOT_DIR.'functions/auth_func.php');	// auth
require_once(ROOT_DIR.'functions/common_func.php');	// common
require_once(ROOT_DIR.'functions/file_func.php');	// files
require_once(ROOT_DIR.'functions/html_func.php');	// HTML
//require_once(ROOT_DIR.'functions/form_func.php');	// forms
//require_once(ROOT_DIR.'functions/image_func.php');	// images
require_once(ROOT_DIR.'functions/lang_func.php');	// dictionary
//require_once(ROOT_DIR.'functions/mail_func.php');	// mail
require_once(ROOT_DIR.'functions/mysql_func.php');	// DB
require_once(ROOT_DIR.'functions/site_brand.php');
require_once(ROOT_DIR.'functions/site_section_urls.php');
require_once(ROOT_DIR.'functions/site_seo.php');
require_once(ROOT_DIR.'functions/site_telemetry.php');
require_once(ROOT_DIR.'functions/string_func.php');	// strings
require_once(ROOT_DIR.'functions/event_func.php');	// events

require_once(ROOT_DIR.'admin/languages/'.$config['admin_lang'].'.php');	// admin language

// Default language (v1.2.122: via GET for get_url when viewing record)
$lang = lang(@$_GET['language'],'id');
// Multilingual settings
if ($config['multilingual']) {
	include(ROOT_DIR.'admin/config_multilingual.php');
	$config['languages'] = mysql_select("SELECT id,name FROM languages ORDER BY display DESC, `rank` DESC", 'rows_id');
}
// Auth: build user data array
$user = user('auth');

// Variable declarations
$url = $error = $success = $content = $where = $query = '';
$form = $delete = $filter = $template = $tabs = $table = array();

// Build GET array and full GET query string
$get = array('m'=>'','u'=>'','id'=>'','b'=>'','c'=>'','s'=>'','o'=>'','tab'=>'','stab'=>'');
foreach ($_GET as $k=>$v) {
	$get[$k] = $post[$k] = stripslashes_smart($v);	// build post from get
	$url.= "$k=$v&";			// full GET query string
}
if ($get['m']=='') $get['m']='index';

if (function_exists('site_telemetry_request_begin')) {
	site_telemetry_request_begin('admin', array('module' => (string)$get['m']));
}

// Auth
// v1.3.32 - SMS auth
if (access('admin module',$get['m'])==false AND $get['m']!='login_sms') {
	//die(header('location: /admin.php?m=_login'));
	include(ROOT_DIR.'admin/modules/login.php');
	die();
}

// Per-language translation JSON export/import (pages, guides, games, casinos, blog)
require_once(ROOT_DIR . 'admin/modules/_i18n.php');
if (function_exists('admin_i18n_dispatch_http') && admin_i18n_dispatch_http($get)) {
	exit;
}

// Check module exists
if (!file_exists(ROOT_DIR.'admin/modules/'.$get['m'].'.php')) die(header('location: /admin.php?m=index')); //$get[m]='index';

$module = array(
	'table' => isset($config['mirrors'][$get['m']]) ? $config['mirrors'][$get['m']] : $get['m'],
	'one_form' => false,
	'save_as' => false,
);

// Content section: tabs = site sections; sub-tabs for Blog (Articles, Categories, Tags) and Casinos (Casinos, Tags)
if ($get['m'] === 'content') {
	$content_tabs = array(
		'guides' => site_section_admin_label('guides', 'Guides'),
		'download' => 'Download',
		'predictor' => 'Predictor',
		'demo' => 'Demo',
		'games' => 'Games',
		'casinos' => 'Casinos',
		'blog' => site_section_admin_label('blog', 'Blog'),
	);
	$ct = isset($get['tab']) ? $get['tab'] : 'guides';
	if (!isset($content_tabs[$ct])) $ct = 'guides';
	$get['tab'] = $ct;
	$module['table'] = '';
	// Sub-tab: Blog → blog | blog_category | blog_tags; Casinos → casinos | casinos_tags
	if ($ct === 'blog') {
		$blog_stabs = array('blog'=>'Articles', 'blog_category'=>'Categories', 'blog_tags'=>'Tags');
		$stab = isset($get['stab']) ? $get['stab'] : 'blog';
		if (!isset($blog_stabs[$stab])) $stab = 'blog';
		$get['stab'] = $stab;
		$module['table'] = $stab;
	} elseif ($ct === 'guides') {
		$module['table'] = 'guides';
	} elseif ($ct === 'games') {
		$games_stabs = array('games' => 'Games', 'games_categories' => 'Categories');
		$gstab = isset($get['stab']) ? $get['stab'] : 'games';
		if (!isset($games_stabs[$gstab])) {
			$gstab = 'games';
		}
		$get['stab'] = $gstab;
		$module['table'] = $gstab;
	} elseif ($ct === 'casinos') {
		$module['table'] = 'casino_articles';
	}
}

// Load main action handler
if ($get['u'] AND file_exists(ROOT_DIR.'admin/actions/'.$get['u'].'.php')) {
	require_once(ROOT_DIR.'admin/actions/'.$get['u'].'.php');
}
// Default: show main template
else {
	// Stray id= on a list URL (no u=form) breaks edit links and preloads $post — strip it.
	if (empty($get['u']) && !empty($get['id']) && (int)$get['id'] > 0) {
		$clean_get = $_GET;
		unset($clean_get['id']);
		header('Location: /admin.php?' . http_build_query($clean_get));
		exit;
	}
	if ($get['id'] > 0 && !empty($module['table']) && in_array((string)$get['u'], array('form', 'edit'), true)) {
		$post = mysql_select("
			SELECT *
			FROM ".$module['table']."
			WHERE id = '".intval($get['id'])."'
		",'row');
	}
	if ($get['m'] === 'content') {
		require_once(ROOT_DIR.'admin/modules/content.php');
		$page_name = 'Content';
		$tabs_html = '<ul class="nav nav-tabs mb-3">';
		foreach ($content_tabs as $t => $label) {
			$tabs_html .= '<li class="nav-item"><a class="nav-link'.($get['tab']===$t?' active':'').'" href="/admin.php?m=content&tab='.urlencode($t).'">'.htmlspecialchars($label).'</a></li>';
		}
		$tabs_html .= '</ul>';
		$content = $tabs_html . (string)@$content_top_link . (string)@$content_sub_tabs . (string)@$content;
	} else {
		require_once(ROOT_DIR.'admin/modules/'.$get['m'].'.php');
	}
	multilingual();
	if (!empty($get['embed']) && $get['embed'] === '1' && in_array($get['m'], array('users', 'user_types'), true)) {
		require_once(ROOT_DIR . $config['style'].'/includes/layouts/_template_embed.php');
	} else {
		require_once(ROOT_DIR . $config['style'].'/includes/layouts/_template.php');
	}
}