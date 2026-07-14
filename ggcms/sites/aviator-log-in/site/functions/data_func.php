<?php

// Arrays used across the template

if (empty($abc)) $abc = array();

$abc['gcats']=mysql_select("select * from blog_category",'rows_id');

// Site menu (include level 3; always include page id=4 Demo if display=1 so it appears even when menu=0 in DB)
$menu = mysql_select("
	SELECT *
	FROM pages
	WHERE display=1 AND level <= 3 AND (`menu` = 1 OR id = 4)
	ORDER BY left_key
",'rows');

// Override name/url from content_i18n for current language when available (scalable i18n)
$menu_i18n = array();
$current_lang_id = ($langid === '' || $langid === '1') ? 1 : (int)$langid;
if ($current_lang_id > 1 && !empty($menu) && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
	$pids = array_map(function($m) { return (int)$m['id']; }, $menu);
	$rows = mysql_select("SELECT entity_id, name, url FROM content_i18n WHERE entity='pages' AND lang_id=" . $current_lang_id . " AND entity_id IN (" . implode(',', $pids) . ") AND (status='published' OR status='review' OR status='draft')", 'rows');
	if ($rows) {
		foreach ($rows as $r) {
			$menu_i18n[(int)$r['entity_id']] = array('name' => (string)$r['name'], 'url' => trim((string)$r['url'], '/'));
		}
	}
}

// Build tree
$abc['menu'] = array();
foreach ($menu as $k=>$v) {
	$base_name = isset($v['name']) ? (string)$v['name'] : '';
	if (isset($menu_i18n[(int)$v['id']])) {
		$v["name$langid"] = $menu_i18n[(int)$v['id']]['name'];
		$v["url$langid"] = $menu_i18n[(int)$v['id']]['url'];
	}
	// Fallback: some pages have name1/url1 but empty name/url for default language
	if (!isset($v["name$langid"]) || trim((string)$v["name$langid"]) === '') {
		$v["name$langid"] = isset($v['name1']) && trim((string)$v['name1']) !== '' ? $v['name1'] : (isset($v['name']) ? $v['name'] : '');
	}
	if (!isset($v["url$langid"]) || trim((string)$v["url$langid"]) === '') {
		$v["url$langid"] = isset($v['url1']) && trim((string)$v['url1']) !== '' ? $v['url1'] : (isset($v['url']) ? $v['url'] : '');
	}

	// Predictor menu fallback:
	// Some languages (e.g. FR) can have empty page menu names for predictor.
	// Ensure it never renders an empty menu item.
	$base_slug = (string)($v['url'] ?? '');
	if ($base_slug === '') $base_slug = (string)($v['url1'] ?? '');
	if ($base_slug === '') $base_slug = (string)($v['url2'] ?? '');
	if ($base_slug === '') $base_slug = (string)($v['url3'] ?? '');
	if (trim((string)($v["name$langid"] ?? '')) === '' && $base_slug === 'predictor') {
		$lid = (int)$langid;
		$dict_common = ROOT_DIR . 'files/languages/' . $lid . '/dictionary/common.php';
		if ($lid > 0 && is_file($dict_common)) {
			$fb = (string)i18n('common|predictor_menu');
			if (trim($fb) !== '') $v["name$langid"] = $fb;
		}
		if (trim((string)($v["name$langid"] ?? '')) === '') $v["name$langid"] = $base_name !== '' ? $base_name : 'Predictor';
	}
	$v['name'] = $v["name$langid"];

	// Betting-style URL only if that module exists (legacy pariuri IDs 4,15,18,19,20)
	if (in_array($v['id'], array(4,15,18,19,20)) && isset($abc['modules']['betting'])) {
		$v['_url'] = get_url('betting') . $v['url'.$langid] . '/';
	} else {
		$v['_url'] = get_url('page', $v);
	}

	$_req = preg_replace('#\?.*#', '', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
	$_req = rtrim($_req, '/') ?: '/';
	$_url_norm = rtrim($v['_url'], '/') ?: '/';
	$v['_active'] = ($_req === $_url_norm) ? 1 : 0;
	$v['_submenu'] = array();

//	Exclude betting pages (15,18,19,20) when showpariuri off; do not exclude id 4 (Demo)
	if(!$showpariuri&&in_array($v['id'],array(15,18,19,20))) {
	} else {
		if ($v['level']==1) {
			$abc['menu'][$v['id']] = $v;
		}
		if ($v['level']==2) {
			if (isset($abc['menu'][$v['parent']])) {
				$abc['menu'][$v['parent']]['_submenu'][] = $v;
			} else {
				// Parent not in menu (e.g. menu=0) — show as top-level so item still appears
				$abc['menu'][$v['id']] = $v;
			}
		}
		if ($v['level']==3) {
			$parent2_id = (int)$v['parent'];
			$found = false;
			foreach ($abc['menu'] as $top_id => $top_item) {
				$subs = isset($top_item['_submenu']) ? $top_item['_submenu'] : array();
				foreach ($subs as $sub_item) {
					if ((int)$sub_item['id'] === $parent2_id) {
						$abc['menu'][$top_id]['_submenu'][] = $v;
						$found = true;
						break 2;
					}
				}
			}
			if (!$found) {
				$abc['menu'][$v['id']] = $v;
			}
		}
		// Guides: no dropdown in menu; categories are chosen on the Guides page
	}
}

// Ensure Demo page appears in menu if it exists and is displayed (even if menu=0 was left unchecked)
$demo_in_menu = false;
foreach ($abc['menu'] as $item) {
	if ((int)$item['id'] === 4 || (isset($item['url']) && $item['url'] === 'demo') || (isset($item['url1']) && $item['url1'] === 'demo')) { $demo_in_menu = true; break; }
	if (!empty($item['_submenu'])) {
		foreach ($item['_submenu'] as $sub) {
			if ((int)$sub['id'] === 4 || (isset($sub['url']) && $sub['url'] === 'demo')) { $demo_in_menu = true; break 2; }
		}
	}
}
if (!$demo_in_menu) {
	$demo_row = mysql_select("SELECT * FROM pages WHERE display=1 AND (id=4 OR url='demo' OR url1='demo') LIMIT 1", 'row');
	if ($demo_row) {
		$v = $demo_row;
		$current_lang_id = ($langid === '' || $langid === '1') ? 1 : (int)$langid;
		if ($current_lang_id > 1 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$di = mysql_select("SELECT name, url FROM content_i18n WHERE entity='pages' AND entity_id=" . (int)$v['id'] . " AND lang_id=" . $current_lang_id . " LIMIT 1", 'row');
			if ($di) {
				$v["name$langid"] = (string)$di['name'];
				$v["url$langid"] = trim((string)$di['url'], '/');
			}
		}
		if (!isset($v["name$langid"]) || trim((string)$v["name$langid"]) === '') {
			$v["name$langid"] = isset($v['name1']) && trim((string)$v['name1']) !== '' ? $v['name1'] : (isset($v['name']) ? $v['name'] : 'Demo');
		}
		if (!isset($v["url$langid"]) || trim((string)$v["url$langid"]) === '') {
			$v["url$langid"] = isset($v['url1']) && trim((string)$v['url1']) !== '' ? $v['url1'] : (isset($v['url']) ? $v['url'] : 'demo');
		}
		$v['name'] = $v["name$langid"];
		$v['_url'] = get_url('page', $v);
		$v['_submenu'] = array();
		$_req = preg_replace('#\?.*#', '', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
		$_req = rtrim($_req, '/') ?: '/';
		$v['_active'] = (rtrim($v['_url'], '/') ?: '/') === $_req ? 1 : 0;
		$abc['menu'][$v['id']] = $v;
	}
}

// Optional: place Blog immediately before Guides in menu (Blog keeps its own URL)
$menu_order = array_values(array_keys($abc['menu']));
$blog_id = null;
$guides_id = null;
foreach ($abc['menu'] as $id => $item) {
	if (isset($item['module']) && $item['module'] === 'blog') $blog_id = $id;
	$item_url = isset($item['url']) ? $item['url'] : (isset($item['url1']) ? $item['url1'] : (isset($item['url2']) ? $item['url2'] : (isset($item['url3']) ? $item['url3'] : '')));
	if ($item_url === 'guides') $guides_id = $id;
}
if ($blog_id !== null && $guides_id !== null) {
	$pos_blog = array_search($blog_id, $menu_order);
	$pos_guides = array_search($guides_id, $menu_order);
	if ($pos_blog !== false && $pos_guides !== false && $pos_blog !== $pos_guides) {
		array_splice($menu_order, $pos_blog, 1);
		$pos_guides_new = array_search($guides_id, $menu_order);
		array_splice($menu_order, $pos_guides_new, 0, array($blog_id));
		$new_menu = array();
		foreach ($menu_order as $id) {
			if (isset($abc['menu'][$id])) $new_menu[$id] = $abc['menu'][$id];
		}
		$abc['menu'] = $new_menu;
	}
}

// Download: move to the end of the menu so it gets the red hex style (last item)
$menu_order = array_values(array_keys($abc['menu']));
$download_id = null;
foreach ($abc['menu'] as $id => $item) {
	$item_url = isset($item['url']) ? $item['url'] : (isset($item['url1']) ? $item['url1'] : (isset($item['url2']) ? $item['url2'] : (isset($item['url3']) ? $item['url3'] : '')));
	if ($item_url === 'download') {
		$download_id = $id;
		break;
	}
}
if ($download_id !== null && ($idx = array_search($download_id, $menu_order)) !== false) {
	array_splice($menu_order, $idx, 1);
	$menu_order[] = $download_id;
	$new_menu = array();
	foreach ($menu_order as $id) {
		if (isset($abc['menu'][$id])) $new_menu[$id] = $abc['menu'][$id];
	}
	$abc['menu'] = $new_menu;
}

// Statistics counters (Settings → Counters or files/reference/counters.json)
$abc['counters_head'] = array();
$abc['counters_body'] = array();
$abc['counters_footer'] = array();
if (!function_exists('site_counters_hydrate_abc')) {
	require_once dirname(__FILE__) . '/site_counters.php';
}
site_counters_hydrate_abc($abc);
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	// Advertising API mode (token + api_sources for external backend)
	$row_ad = mysql_select("SELECT value FROM `variables` WHERE `key` = 'advertising_api' LIMIT 1", 'row');
	$abc['advertising_api'] = array('mode' => 'self', 'token' => '', 'api_sources' => array(), 'api_sources_priority' => '', 'api_url' => '', 'debug_ip_check' => 0, 'manual_country' => '', 'trusted_proxy_ips' => array());
	if ($row_ad && $row_ad['value'] !== '') {
		$dec = json_decode($row_ad['value'], true);
		if (is_array($dec)) {
			$abc['advertising_api']['mode'] = isset($dec['mode']) && $dec['mode'] === 'api' ? 'api' : 'self';
			$abc['advertising_api']['token'] = isset($dec['token']) ? (string)$dec['token'] : '';
			$abc['advertising_api']['api_sources'] = isset($dec['api_sources']) && is_array($dec['api_sources']) ? $dec['api_sources'] : array();
			$abc['advertising_api']['api_sources_priority'] = isset($dec['api_sources_priority']) ? (string)$dec['api_sources_priority'] : '';
			$abc['advertising_api']['api_sources_banners'] = isset($dec['api_sources_banners']) && is_array($dec['api_sources_banners']) ? $dec['api_sources_banners'] : array();
			$abc['advertising_api']['api_url'] = isset($dec['api_url']) ? (string)$dec['api_url'] : '';
			$abc['advertising_api']['banner_popup_delay_seconds'] = isset($dec['banner_popup_delay_seconds']) ? max(1, min(600, (int)$dec['banner_popup_delay_seconds'])) : 30;
			$abc['advertising_api']['popup_enabled'] = !isset($dec['popup_enabled']) || !empty($dec['popup_enabled']) ? 1 : 0;
			$abc['advertising_api']['debug_ip_check'] = !empty($dec['debug_ip_check']) ? 1 : 0;
			$abc['advertising_api']['manual_country'] = isset($dec['manual_country']) ? (string)$dec['manual_country'] : '';
			$abc['advertising_api']['trusted_proxy_ips'] = isset($dec['trusted_proxy_ips']) && is_array($dec['trusted_proxy_ips']) ? $dec['trusted_proxy_ips'] : array();
		}
	}
	// SEO structured data (canonical, breadcrumbs, FAQ)
	$row_seo = mysql_select("SELECT value FROM `variables` WHERE `key` = 'seo_structured' LIMIT 1", 'row');
	$abc['seo_structured'] = array(
		'canonical_base' => '',
		'site_name' => '',
		'breadcrumbs' => array('home_label' => 'Home', 'use_site_tree' => 0),
		'faq' => array(),
	);
	if ($row_seo && $row_seo['value'] !== '') {
		$dec = json_decode($row_seo['value'], true);
		if (is_array($dec)) {
			if (isset($dec['canonical_base'])) $abc['seo_structured']['canonical_base'] = (string)$dec['canonical_base'];
			if (isset($dec['site_name'])) $abc['seo_structured']['site_name'] = (string)$dec['site_name'];
			if (isset($dec['breadcrumbs']) && is_array($dec['breadcrumbs'])) {
				$abc['seo_structured']['breadcrumbs'] = array_merge($abc['seo_structured']['breadcrumbs'], $dec['breadcrumbs']);
			}
			if (isset($dec['faq']) && is_array($dec['faq'])) {
				$abc['seo_structured']['faq'] = $dec['faq'];
			}
		}
	}
}
$abc['counters'] = $abc['counters_head'];

// Footer menu
$abc['menu_footer'] = mysql_select("
	SELECT name$langid name,url$langid url,module,level
	FROM pages
	WHERE display=1 AND level=1 AND menu2 = 1
	ORDER BY left_key",'rows','');

function date_ro($date) {
  global $langid;
  if(!$langid)
    $date=str_replace(
      array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
      array('Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie','Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'),
      $date
    );
  elseif($langid==3)
    $date=str_replace(
      array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
      array('janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'),
      $date
    );
  elseif($langid==9)
    $date=str_replace(
      array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'),
      array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'),
      $date
    );
  return $date;
}

function day_ro($date) {
  global $langid;
  if(!$langid)
    $date=str_replace(
      array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
      array('Luni','Marţi','Miercuri','Joi','Vineri','Sâmbătă','Duminică'),
      $date
    );
  elseif($langid==3)
    $date=str_replace(
      array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
      array('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'),
      $date
    );
  elseif($langid==9)
    $date=str_replace(
      array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
      array('понедельник','вторник','среда','четверг','пятница','суббота','воскресенье'),
      $date
    );
  return $date;
}

function imgstr($module,$id,$img,$dimensions,$subdir='img') {
  if(preg_match('#^(.*)\.([^\.]+)$#iu',$img,$m)) {
    $fn=$m[1];
    $fe=$m[2];
    return '/images/'.$module.'/'.$id.'/'.$subdir.'/'.$dimensions.'-'.$fe.'-'.$fn.'.'.($fe=='svg'||$fe=='gif'?$fe:'webp');
  } else {
    return false; //blank img
  }
}

function ad($a,$swiper,$sizes=array(),$langid='') {
  if(count($a)==1) {
    echo "<a href='".$a[0]['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$a[0]['id'],$a[0]['img'],$sizes[0],'img'.$langid)."'";
//    $imgs=array();foreach($sizes as $size) $imgs[]=imgstr('ads',$a[0]['id'],$a[0]['img'],$size).' '.preg_replace('#^([0-9]+).*$#','$1',$size).'w';
//    if(count($imgs)) echo " srcset='".implode(',',$imgs)."'";
    echo " alt='advertisement' title='advertisement'></a>";
  } else {
    echo "<div class='swiper $swiper'><div class='swiper-wrapper'>";
    foreach($a as $ad) {
      echo "<div class='swiper-slide'><a href='".$ad['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$ad['id'],$ad['img'],$sizes[0],'img'.$langid)."'";
//      $imgs=array();foreach($sizes as $size) $imgs[]=imgstr('ads',$ad['id'],$ad['img'],$size).' '.preg_replace('#^([0-9]+).*$#','$1',$size).'w';
//      if(count($imgs)) echo " srcset='".implode(',',$imgs)."'";
      echo " alt='advertisement' title='advertisement'></a></div>";
    }
    echo "</div><div class='swiper-button-next'></div><div class='swiper-button-prev'></div><div class='swiper-pagination'></div></div>";
  }
}

function ad1($a,$swiper,$size,$size_m,$langid='') {
  if(count($a)==1) {
    if($a[0]['img'])
      echo "<a class='dt-only' href='".$a[0]['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$a[0]['id'],$a[0]['img'],$size,'img'.$langid)."' alt='advertisement' title='advertisement'></a>";
    if($a[0]['img_2'])
      echo "<a class='mb-only' href='".$a[0]['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$a[0]['id'],$a[0]['img_2'],$size_m,'img_2'.$langid)."' alt='advertisement' title='advertisement'></a>";
  } else {
    $imgs=$imgs2=array();
    foreach($a as $ad) {
      if($ad['img'])   $imgs[] =array('id'=>$ad['id'],'url'=>$ad['url'],'img'=>$ad['img']);
      if($ad['img_2']) $imgs2[]=array('id'=>$ad['id'],'url'=>$ad['url'],'img'=>$ad['img_2']);
    }
    if(count($imgs)) {
      echo "<div class='dt-only swiper $swiper'><div class='swiper-wrapper'>";
      foreach($imgs as $ad)
        echo "<div class='swiper-slide'><a href='".$ad['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$ad['id'],$ad['img'],$size,'img'.$langid)."' alt='advertisement' title='advertisement'></a></div>";
      echo "</div><div class='swiper-button-next'></div><div class='swiper-button-prev'></div><div class='swiper-pagination'></div></div>";
    }
    if(count($imgs2)) {
      echo "<div class='mb-only swiper $swiper'><div class='swiper-wrapper'>";
      foreach($imgs2 as $ad)
        echo "<div class='swiper-slide'><a href='".$ad['url']."' target='_blank' rel='nofollow'><img class='w-100 rounded' src='".imgstr('ads',$ad['id'],$ad['img'],$size_m,'img_2'.$langid)."' alt='advertisement' title='advertisement'></a></div>";
      echo "</div><div class='swiper-button-next'></div><div class='swiper-button-prev'></div><div class='swiper-pagination'></div></div>";
    }
  }
}

/*
// Category menu
$abc['menu_categories'] = mysql_select("
	SELECT *
	FROM shop_categories
	WHERE display = 1
	ORDER BY left_key
",'rows',60*60);

// Random product
$abc['product_random'] = mysql_select("SELECT *
	FROM shop_products
	WHERE display = 1 AND img!=''
	ORDER BY RAND()
	LIMIT 1
",'rows');
*/