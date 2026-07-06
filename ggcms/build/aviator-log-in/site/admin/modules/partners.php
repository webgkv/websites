<?php

$a18n['page']='page type';
$a18n['count']='limit';

$pages=array(
  'index casinos'=>'index casinos',
  'index sportsbooks'=>'index sportsbooks',
  'news (item) sportsbooks'=>'news (item) sportsbooks',
  'pariuri sportsbooks'=>'pariuri sportsbooks',
  'tickets (item) sportsbooks'=>'tickets (item) sportsbooks',
  'cota2 (item) sportsbooks'=>'cota2 (item) sportsbooks',
);

$table = array(
	'id'		=>	'id:asc name',
	'page'		=>	'',
	'count'		=>	''
);

$form[] = array('select td6','page',array('value'=>array(true,$pages,'')));
$form[] = array('input td6','count');

?>