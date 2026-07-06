<?php
global $post;
if (@$post['left_key']) {
	$num_rows = mysql_select("
		SELECT COUNT(id) 
		FROM users
		WHERE tree=".$post['tree']."
			AND left_key>".$post['left_key']."
			AND right_key<".$post['right_key']."		
	",'string');
	echo form('text td3','',array(
		'name'=>'Людей в структуре',
		'value'=>$num_rows.' <a href="?m=users&search=tree:'.$post['id'].'">посмотреть</a>'
	));
}