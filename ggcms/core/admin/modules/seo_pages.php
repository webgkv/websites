<?php

// todo: module unused - remove

/*
 * v1.4.0 - html_render в админке
 * v1.4.16 - $delete удалил confirm
 */

$a18n['links'] = 'ссылки';
$a18n['yandex_index'] = 'yandex_index';
$a18n['exist'] = 'присутсвует на сайте';

$config['depend'] = array(
	'seo_pages'=>array('links'=>'seo_links-pages'),
);

//v1.4.16 - $delete удалил confirm
function event_delete_seo_pages ($q) {
	mysql_fn('query',"DELETE FROM `seo_links-pages` WHERE child= '" . $q['id'] . "'");
}


// Show links
if ($get['u']=='show_links') {
	$id = intval(@$_GET['id']);
	if ($post = mysql_select("SELECT * FROM seo_pages WHERE id=".$id,'row')) {
		if ($post['links'] AND $links = mysql_select("SELECT * FROM seo_links WHERE id IN (".$post['links'].")",'rows')) {
			foreach($links as $k=>$v) {
				echo '<a target="_blank" href="'.$v['url'].'">'.$v['name'].'</a><br />';
			}
		}
	}
	die();
}

if ($get['u']=='post') {
	// Remove link relations when turning off
	if ($get['name']=='display' AND $get['value']==0) {
		mysql_fn('delete','seo_links-pages',false," AND child=".intval($get['id'])."");
		mysql_fn('update',$get['m'],array('links'=>'','id'=>$get['id']));
	}
}

// Search links
if ($get['u']=='similar_search') {
	$search = stripslashes_smart(@$_GET['value']);
	if ($i=intval($search)) $where = " id=".$i." ";
	else $where = " LOWER(name) LIKE '%".mysql_res(mb_strtolower($search,'UTF-8'))."%' OR LOWER(url) LIKE '%".mysql_res(mb_strtolower($search,'UTF-8'))."%' ";
	$query = "SELECT * FROM seo_links WHERE ".$where." LIMIT 10";
	if ($products = mysql_select($query,'rows')) {
		foreach ($products as $k=>$v) {
			echo '<li data-id="'.$v['id'].'" title="Перетащите в правую колонку для сохранения">';
			echo $v['img'] ? '<img src="'.$v['img'].'" />' : '<div></div>';
			echo '<b>'.$v['name'].'</b><br />';
			echo $v['url'];
			echo '</li>';
		}
	}
	die();
}
// Search articles
if ($get['u']=='articles_search') {
	$search = stripslashes_smart(@$_GET['value']);
	if ($i=intval($search)) $where = " id=".$i." ";
	else $where = " LOWER(name) LIKE '%".mysql_res(mb_strtolower($search,'UTF-8'))."%' OR LOWER(url) LIKE '%".mysql_res(mb_strtolower($search,'UTF-8'))."%' ";
	$query = "SELECT * FROM news WHERE ".$where." LIMIT 10";
	if ($products = mysql_select($query,'rows')) {
		foreach ($products as $k=>$v) {
			echo '<li data-id="'.$v['id'].'" title="Перетащите в правую колонку для сохранения">';
			echo '<b>'.$v['name'].'</b><br />';
			echo $v['url'];
			echo '</li>';
		}
	}
	die();
}

// Delete all pages
if ($get['u']=='delete_pages') {
	// Remove relations
	mysql_fn('query',"TRUNCATE `seo_links-pages`");
	mysql_fn('query',"TRUNCATE seo_pages");
}

// Generate all site pages
if ($get['u']=='greate_pages') {
	$modules = mysql_select("SELECT url name,module id FROM pages WHERE module!='pages' AND language=1 AND display=1",'array');
	$urls = array();
	$urls['pages'] = sitemap("SELECT name,url FROM pages WHERE display=1 AND module!='index' ORDER BY left_key",'/{url}/');
	if (isset($modules['blog']))
		$urls['blog'] = sitemap("SELECT id,name,url FROM blog WHERE display=1 ORDER BY date DESC",'/'.$modules['blog'].'/{id}-{url}/');
	if (isset($modules['gallery']))
		$urls['gallery'] = sitemap("SELECT id,name,url FROM gallery WHERE display=1 ORDER BY `rank` DESC",'/'.$modules['gallery'].'/{id}-{url}/');
	if (isset($modules['shop'])) {
		$urls['shop_products'] = sitemap("
			SELECT sp.url,sp.id,sp.name, sc.url category_url,sc.id category_id
			FROM shop_products sp, shop_categories sc
			WHERE sp.display=1 AND sc.display=1
			ORDER BY sc.left_key,sp.id
		",'/'.$modules['shop'].'/{category_id}-{category_url}/{id}-{url}/');
		$urls['shop_categories'] = sitemap("SELECT id,name,url FROM shop_categories WHERE display=1 ORDER BY left_key",'/'.$modules['shop'].'/{id}-{url}/');
	}
	// Mark all pages as non-existent
	mysql_fn('update','seo_pages',array('exist'=>0));
	$content = '<table class="table">';
	foreach ($urls as $key=>$val) {
		if (is_array($val)) {
			foreach ($val as $k=>$v) {
				$content.= '<tr><td>'.$v[0].'</td><td><a href="http://'.$_SERVER['HTTP_HOST'].$v[1].'">http://'.$_SERVER['HTTP_HOST'].$v[1].'</a></td></tr>';
				if ($id = mysql_select("SELECT id FROM seo_pages WHERE url='".mysql_res($v[1])."'",'row')) {
					mysql_fn('update','seo_pages',array('exist'=>1,'id'=>$id,'name'=>$v[0]));
				}
				else {
					$seo_page = array(
						'display'	=> 1,
						'exist'		=> 1,
						'name'		=> $v[0],
						'url'		=> $v[1]
					);
					$seo_page['id'] = mysql_fn('insert','seo_pages',$seo_page);
				}
			}
		}
	}
	$content.= '</table>';
	require_once(ROOT_DIR . $config['style'].'/includes/layouts/_template.php');
	die();
}



$filter[] = array('search');
$filter[] = array('type',array(
	1=>'в индексе яндекса',
	2=>'нет в индексе яндекса',
	3=>'выключена',
	4=>'404 - нет на сайте',
),'-все-');

$table = array(
	'id'			=> 'id:desc name',
	'name'			=> '<a target="_blank" href="{url}">{name}</a>',
	'url'			=> '',
	'links'			=> '<a class="show_links" href="#">показать ссылки</a>',
	'exist'			=> 'boolean',
	'yandex_index'	=> 'boolean',
	'display'		=> 'boolean'
);

$where = '';
if (isset($get['search']) && $get['search']!='') $where.= "
	AND (
		LOWER(seo_pages.name) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		OR LOWER(seo_pages.url) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
	)
";
if (@$get['type']>0) {
	if ($get['type']==1) $where.= " AND yandex_index=1";
	if ($get['type']==2) $where.= " AND yandex_index=0";
	if ($get['type']==3) $where.= " AND display=0";
	if ($get['type']==4) $where.= " AND exist=0";
}

$query = "
	SELECT seo_pages.*
	FROM seo_pages
	WHERE 1 $where
";

$content = '<div style="margin:10px 0 0; padding:5px 10px; font:12px/14px Arial; background:#DFE0E0; border-radius:3px">
	<a href="/admin.php?m=seo_pages&u=greate_pages" style="float:right; padding:0 0 0 10px">сгенерировать все страницы</a>
	<a href="/admin.php?m=seo_pages&u=delete_pages" style="float:right" onclick="if(confirm(\'подтвердите удаление!\')) {} else return">удалить все страницы</a>
	Индексация Яндекса <a target="_blank" href="/admin.php?m=config#3">настроить</a>
	&nbsp;||&nbsp;
	Генерация Sitemap <a target="_blank" href="/admin.php?m=config#4">настроить</a>
</div>';

$form[] = array('input td4','name',true);
$form[] = array('input td4','url',true);
$form[] = array('checkbox td4','display',true,array('help'=>'перелинковка показывается на сайте'));
$form[] = array('input td4','yandex_check',true,array('name'=>'время проверки в индексе yandex'));
$form[] = array('checkbox td4','yandex_index',true,array('name'=>'наличие в индексе yandex'));
$form[] = array('checkbox td4','exist',true,array('help'=>'страница есть на сайте - 202'));

$form[] = '<div style="clear:both"><br /><b>ПРИВЯЗКА ССЫЛОК</b></div>';
$form[] = array('input td6','','',array('name'=>'Поиск ссылок по названию, url, ID','attr'=>'id="similar_search"'));
$form[] = array('input td6','links',true,array('name'=>'ID ссылок через запятую'));
$form[] = '<ul id="similar_results" class="product_list"></ul>';
$form[] = '<ul id="similar" class="product_list">';
if (@$post['links']) {
	$query2 = "SELECT * FROM seo_links WHERE id IN (".$post['links'].") LIMIT 10";
	if ($products = mysql_select($query2,'rows_id')) {
		$similar = explode(',',$post['links']);
		foreach ($similar as $k=>$v) if (isset($products[$v])) {
			$form[] = '<li data-id="'.$products[$v]['id'].'" title="Перетащите в правую колонку для сохранения">';
			$form[] = $products[$v]['img'] ? '<img src="'.$products[$v]['img'].'" />' : '<div></div>';
			$form[] = '<b>'.$products[$v]['name'].'</b><br />';
			$form[] = $products[$v]['url'];
			$form[] = '</li>';
		}
	}
}
$form[] = '</ul>';
$form[] = '<div style="clear:both"><br /><b>ПРИВЯЗКА СТАТТЕЙ</b></div>';
$form[] = array('input td6','','',array('name'=>'Поиск статтей по названию, url, ID','attr'=>'id="articles_search"'));
$form[] = array('input td6','articles',true,array('name'=>'ID статтей через запятую'));
$form[] = '<ul id="articles_results" class="product_list"></ul>';
$form[] = '<ul id="articles" class="product_list">';
if (@$post['articles']) {
	$query2 = "SELECT * FROM news WHERE id IN (".$post['articles'].") LIMIT 10";
	if ($products = mysql_select($query2,'rows_id')) {
		$similar = explode(',',$post['articles']);
		foreach ($similar as $k=>$v) if (isset($products[$v])) {
			$form[] = '<li data-id="'.$products[$v]['id'].'" title="Перетащите в правую колонку для сохранения">';
			$form[] = '<b>'.$products[$v]['name'].'</b><br />';
			$form[] = $products[$v]['url'];
			$form[] = '</li>';
		}
	}
}
$form[] = '</ul>';

function sitemap ($query,$url) {
	preg_match_all('/{(.*?)}/',$url,$matches,PREG_PATTERN_ORDER);
	$data = array();
	if ($sitemap = mysql_select($query,'rows')) {
		foreach ($sitemap as $q) {
			foreach ($matches[1] as $k => $v) {
				$matches2[1][$k] = isset($q[$v]) ? $q[$v] : '';
			}
			$data[] = array(
				$q['name'],
				str_replace($matches[0], $matches2[1], $url)
			);
		}
	}
	return $data;
}

$content.= '
<style>
.product_list {float:left; min-height:50px; width:431px; background:#d6d6d6;}
#similar_results {margin:0 13px 0 0;}
#articles_results {margin:0 13px 0 0;}
.product_list li {clear:both; padding:5px; cursor:move}
.product_list li img,
.product_list li div {width:50px; height:50px; float:left; margin:0 5px 0 0}
.product_list li:hover {background:#FFFEDF}
</style>

<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function () {
	// Show links
	$(document).on("click",".show_links",function() {
		var id	= $(this).closest("tr").data("id"),
			box = $(this).closest("td");
		$.get(
			"/admin.php?m=seo_pages&u=show_links",
			{"id":id},
			function(data){
				$(box).html(data);
			}
		).fail(function() {
			alert("Нет соединения!");
		});
		return false;
	});

	// Search links
	$(document).on("keyup","#similar_search",function(e) {
		var value	= $(this).val();
		$.get(
			"/admin.php?m=seo_pages&u=similar_search",
			{"value":value},
			function(data){
				$("#similar_results").html(data);
			}
		).fail(function() {
			alert("Нет соединения!");
		});
	});
	// Search articles
	$(document).on("keyup","#articles_search",function(e) {
		var value	= $(this).val();
		$.get(
			"/admin.php?m=seo_pages&u=articles_search",
			{"value":value},
			function(data){
				$("#articles_results").html(data);
			}
		).fail(function() {
			alert("Нет соединения!");
		});
	});

	similar_results();
	articles_results();
	$(document).on("form.open",".form",function(){
		similar_results();
		articles_results();
	});

	// Sort links
	function similar_results () {
		$("#similar_results, #similar" ).sortable({
			connectWith: ".product_list",
			stop: function() {
				var similar = [];
				$("#similar li").each(function(){
					similar.push($(this).data("id"));
				});
				$("input[name=links]").val(similar);
			}
		}).disableSelection();
	}
	// Sort articles
	function articles_results () {
		$("#articles_results, #articles" ).sortable({
			connectWith: ".product_list",
			stop: function() {
				var similar = [];
				$("#articles li").each(function(){
					similar.push($(this).data("id"));
				});
				$("input[name=articles]").val(similar);
			}
		}).disableSelection();
	}

});
</script>';