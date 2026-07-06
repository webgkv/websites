<?php

if (isset($_POST['text'])) {
	$text = stripslashes_smart($_POST['text']);
	$fp = fopen(ROOT_DIR.'config/config.php','w');
	$content.= fwrite($fp,$text)>=0 ? '<br />file updated!' : '<br />write error!';
	fclose($fp);
	$data = array();
	$data['error']	= '';
	echo '<textarea>'.json_encode($data, JSON_HEX_AMP).'</textarea>';
	die();
}

$handle = fopen(ROOT_DIR.'config/config.php', "r");
$text = '';
if ($handle) {
	while (($buffer = fgets($handle, 4096)) !== false) $text.= $buffer;
	fclose($handle);
}

$content = '<div style="margin:10px 0 0; padding:5px 10px; font:12px/14px Arial; background:#DFE0E0; border-radius:3px">
	<b>ATTENTION! This is a server configuration file. If you make any incorrect changes here, the site may shut down.</b>
</div>';

$module['one_form'] = true;

$form[] = array('textarea td12','text',array('value'=>$text,'name'=>' ','attr'=>'style="height:300px"'));
