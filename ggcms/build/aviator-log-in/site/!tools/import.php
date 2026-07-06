<?php

define('ROOT_DIR', dirname(__FILE__).'/../');
require_once(ROOT_DIR.'config/config.php');
require_once(ROOT_DIR.'functions/mysql_func.php');	// DB
//exit;

$files=array(
  'google_ke_aviator_matching-terms_2025-06-21_09-32-23.csv',
  'google_ke_aviator_matching-terms_2025-06-21_09-40-10.csv',
  'google_tz_aviator_matching-terms_2025-06-21_09-34-31.csv'
);

foreach($files as $file) {
  $f=fopen("./$file",'r');

  $s=trim(fgets($f));
  $names = str_getcsv($s,',');

  while(!feof($f)) {
    $s=trim(fgets($f));
    if($s=='') continue;
    $line =str_getcsv($s,',');
    $line1=array();
    foreach($names as $k=>$v)
      $line1[$v]=$line[$k];
    if(count($line1)!=15) die('wrong csv line');
//    echo $line1['Keyword'].' '.$line1['Country']."\r\n";
    $url=strtolower(trim(preg_replace('#\s+#ius',' ',$line1['Keyword'])));

    $a=array(
      'url'=>str_replace(' ','-',$url),
      'name'=>$url,
      'title'=>$url,
      'description'=>$url
    );
//    print_r($a);

//    exit;
    mysql_fn('insert','blog',$a);
    echo '+';

  }
  fclose($f);
}
echo 'OK';

?>