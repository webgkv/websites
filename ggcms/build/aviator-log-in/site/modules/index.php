<?php
// Home page: no redirect; show index layout with content from templates/includes/layouts/index.php

$abc['gallery'] = @mysql_select('select * from gallery', 'rows_id') ?: array();

$abc['breadcrumb'] = array();

//$abc['video'] = mysql_select("SELECT * FROM videos WHERE display = 1 ORDER BY rand() LIMIT 3",'rows');
//$abc['video'] = mysql_select("SELECT * FROM videos WHERE display = 1 ORDER BY date DESC LIMIT 3",'rows');

$abc['ticket']=mysql_select("select id,date,img,name$langid name from `ticketoftheday` where date<='".date('Y-m-d H:i:s')."' and display=1 order by date desc limit 1",'row');
$abc['bet']   =mysql_select("select id,date,img,name$langid name from `betoftheday`    where date<='".date('Y-m-d H:i:s')."' and display=1 order by date desc limit 1",'row');

$abc['blog'] =  mysql_select("select id,date,category,img,img_alt,img_title,author,url$langid url,name$langid name,name_2$langid name_2,gimg from blog where date<='".date('Y-m-d H:i:s')."' and display=1 order by top desc,date desc limit 5",'rows');
$abc['blog_1'] =mysql_select("select id,date,category,img,img_alt,img_title,author,url$langid url,name$langid name,name_2$langid name_2,gimg from blog where date<='".date('Y-m-d H:i:s')."' and display=1 and category=20 order by top desc,date desc limit 3",'rows');
$abc['blog_2'] =mysql_select("select id,date,category,img,img_alt,img_title,author,url$langid url,name$langid name,name_2$langid name_2,gimg from blog where date<='".date('Y-m-d H:i:s')."' and display=1 and category=1 order by top desc,date desc limit 3",'rows');
$abc['blog_3'] =mysql_select("select id,date,category,img,img_alt,img_title,author,url$langid url,name$langid name,name_2$langid name_2,gimg from blog where date<='".date('Y-m-d H:i:s')."' and display=1 and category=18 order by top desc,date desc limit 3",'rows');

$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date DESC LIMIT 3",'rows');


$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='index'   and url$langid!=''",'rows');
$abc['ads2'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='index_2' and url$langid!=''",'rows');

$abc['showpariuri']=$showpariuri;

$limit = mysql_select('select count from partners where page="index casinos" limit 1','string');if($limit==0) $limit=5;
$abc['casinos'] = mysql_select("select id,img,website,name$langid name,name_2$langid name_2 from `casinos` where display=1 order by top desc,position desc limit $limit",'rows');

$limit = mysql_select('select count from partners where page="index sportsbooks" limit 1','string');if($limit==0) $limit=5;
$abc['sportsbooks'] = mysql_select("select id,img,website,name$langid name,name_2$langid name_2 from `sportsbooks` where display=1 order by top desc,position desc limit $limit",'rows');

if(!isset($_COOKIE['popup'])) {
  $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="index" and popup_places.display=1 order by rand() limit 1','string');
  if(!$abc['popup']) $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1','string');
}
