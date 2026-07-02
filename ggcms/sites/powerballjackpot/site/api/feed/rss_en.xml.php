<?php

// available at /api/feed/rss_en.xml

require(dirname(__FILE__).'/../../functions/data_func.php');

header('Content-type: text/xml; charset=UTF-8');

$config['cache'] = false;
$file = ROOT_DIR . '/api/feed/rss_en.xml';
$lang = 1;

if (file_exists($file) && (time() - 0) < filemtime($file)) {
	echo file_get_contents($file);
	die();
}

$cats = mysql_select('select * from blog_category', 'rows_id');
$lang = lang($lang);
$langid = $lang['id']; if ($langid == 1) $langid = '';

$modules = mysql_select("SELECT module id,url$langid name FROM pages WHERE module!='pages'", 'array');
$gallery = mysql_select('select * from gallery', 'rows_id');

$content = '<?xml version="1.0" encoding="UTF-8"?><rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0" xmlns:media="http://search.yahoo.com/mrss/">';
$rss_domain = (isset($config['http_domain']) && $config['http_domain']) ? $config['http_domain'] : ('https://'.($_SERVER['HTTP_HOST'] ?? ''));
$content .= '<channel><title><![CDATA[Chicken Road Blog]]></title><description><![CDATA[Chicken Road Blog]]></description><link>'.$rss_domain.'/'.$lang['url'].'/'.$modules['blog'].'/</link><image><url>'.$rss_domain.'/assets/logo.png</url><title>Chicken Road Blog</title><link>'.$rss_domain.'/'.$lang['url'].'/'.$modules['blog'].'/</link></image>';
$content .= '<generator>RSS for Node</generator><lastBuildDate>'.gmdate('D, j M Y H:i:s', time()-60*60).' GMT</lastBuildDate><atom:link href="'.$rss_domain.'/api/feed/rss_en.xml" rel="self" type="application/rss+xml"/><language><![CDATA[en-US]]></language>';

if ($news = mysql_select("
	SELECT *
	FROM blog
	WHERE url$langid!='' and display=1
	ORDER BY date DESC", 'rows')
) {
	foreach ($news as $q) {
		$content .= '<item><title><![CDATA['.$q["name$langid"].']]></title>';
		$content .= '<description><![CDATA['.$q["name_2$langid"].']]></description>';
		$content .= '<link>'.$rss_domain.'/'.$lang['url'].'/'.$modules['blog'].'/'.$cats[$q['category']]["url$langid"].'/'.$q["url$langid"].'/</link>';
		$content .= '<guid isPermaLink="false">'.$rss_domain.'/'.$lang['url'].'/'.$modules['blog'].'/'.$cats[$q['category']]["url$langid"].'/'.$q["url$langid"].'/</guid>';
		$content .= '<pubDate>'.date('D, d M Y H:i:s', strtotime($q['date'])).' GMT</pubDate>';
		$content .= '<media:thumbnail width="240" height="135" url="'.$rss_domain.($q['gimg'] ? imgstr('gallery',$q['gimg'],$gallery[$q['gimg']]['img'],'240x135') : imgstr('blog',$q['id'],$q['img'],'240x135')).'"/>';
		$content .= '</item>';
	}
}
$content .= '</channel></rss>';

echo $content;
die();
