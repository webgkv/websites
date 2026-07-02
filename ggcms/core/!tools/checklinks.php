<?php

/*
define('ROOT_DIR', dirname(__FILE__).'/../');
require_once(ROOT_DIR.'config/config.php');
require_once(ROOT_DIR.'functions/mysql_func.php');	// DB
require_once(ROOT_DIR.'functions/curl_func.php');	// curl
*/

$_SERVER=array('SERVER_NAME'=>'','REQUEST_URI'=>'','REMOTE_ADDR'=>'','HTTP_HOST'=>'');
if(strtoupper(substr(PHP_OS,0,3))==='WIN') $_SERVER['HTTP_HOST']='localhost';

set_time_limit(0);
define('ROOT_DIR', dirname(__FILE__).'/../');
include(ROOT_DIR.'config/config.php');

include(ROOT_DIR.'/functions/mysql_func.php');
include(ROOT_DIR.'/functions/curl_func.php');
include(ROOT_DIR.'/functions/string_func.php');

//get tables
$tables=array('blog');

foreach($tables as $table) {
  echo $table."\r\n<br>";
  $rows=mysql_select("select id,url,title,description,display from `$table`",'rows');
  print(count($rows));
  foreach($rows as $row) {
    $url=str_replace('%20',' ',strtolower($row['url']));
    $url=preg_replace('#[^-_0-9a-z]#','-',$url);
#    $url=preg_replace('#[^\s-_0-9a-z]#','-',strtolower($row['url']));
    $url=preg_replace('#[-]{2,}#','-',$url);
#    $url=str_replace(' ','%20',$url);
    $url=trim($url,'-');
    if($url!=$row['url']) {
#    if(!preg_match('#^[-0-9a-z]+$#',$row['url'])) {
      print($row['id'].' '.$row['url'].' '.$url."\r\n<br>");
      if(!is_array(mysql_select("select * from $table where url='$url' limit 1",'row'))) {
        mysql_fn('update','blog',array('id'=>$row['id'],'url'=>$url),'',true);
#      exit;
      } else {
        if($row['display']==0) {
          mysql_fn('delete','blog',$row['id']);
        } else {
          mysql_fn('update','blog',array('id'=>$row['id'],'url'=>$url.'-2'),'',true);
        }
#        print('+'.$row['display'].'+');
      }
    }
  }
}

echo 'OK1';

//update news set url=replace(url,'?','')
//select * from news where url like 'how-to-play-aviator%'

?>