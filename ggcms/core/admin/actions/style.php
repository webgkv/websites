<?php
//ОФОРМЛЕНИЕ
if (isset($_GET['style']) AND in_array($_GET['style'],array('a','b','c','g'))) {
	setcookie("a_style",$_GET['style'], time()+60*60*24*30,'/');
}
if (isset($_GET['size']) AND in_array($_GET['size'],array('b','m','s'))) {
	setcookie("a_size",$_GET['size'], time()+60*60*24*30,'/');
}

