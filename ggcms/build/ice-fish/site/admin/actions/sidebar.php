<?php

//показать/скрыть боковое меню
/*
 v1.4.7 - admin/template2
*/

if (@$_GET['action']=='open') {
	$_SESSION['sidebar'] = 'open';
}
else {
	$_SESSION['sidebar'] = 'close';
}
die();
