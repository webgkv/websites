<?php

//todo удалить модуль
/*
 * v1.4.16 - $delete удалил confirm
 */

//$a18n['limit']	= 'лимит';
//$a18n['img']		= 'картинка';
//$a18n['keyword']	= 'ключевой запрос';

//удаление всех страниц
if ($get['u']=='delete_links') {
	//удаляем связи
	mysql_fn('query',"TRUNCATE `seo_links-pages`");
	mysql_fn('query',"TRUNCATE seo_links");
}

//v1.4.16 - $delete удалил confirm
function event_delete_seo_links ($q) {
	mysql_fn('query',"DELETE FROM `seo_links-pages` WHERE parent = '" . $q['id'] . "'");
}

$filter[] = array('search');

$table = array(
	'id'		=> 'id:desc name url limit',
	'name'		=> '<a href="{url}" target="_blank">{name}</a>',
	'keyword'	=> '',
	'url'		=> '',
	'img'		=> '',
	'limit'		=> '',
	'count'		=> 'text'
);

$where = '';
if (isset($get['search']) && $get['search']!='') $where.= "
	AND (
		LOWER(seo_links.name) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		OR LOWER(seo_links.keyword) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
		OR LOWER(seo_links.url) like '%".mysql_res(mb_strtolower($get['search'],'UTF-8'))."%'
	)
";

$query = "
	SELECT seo_links.*,COUNT(d.id) count
	FROM seo_links
	LEFT JOIN `seo_links-pages` d ON d.parent=seo_links.id
	WHERE 1 $where
	GROUP BY seo_links.id
";

$filter[] = '<a href="/admin.php?m=seo_links&u=delete_links" onclick="if(confirm(\'confirm deletion!\')) {} else return">remove all links</a>';

$form[] = array('input td4','name',true);
$form[] = array('input td4','keyword',true);
$form[] = array('input td4','url',true);
$form[] = array('input td4','img',true);
$form[] = array('input td1','limit',true);
