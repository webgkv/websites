<?php

// сортировка
$id = intval($_GET['id']);
$n = intval($_GET['n']);
$prev = intval($_GET['prev']);
$next = intval($_GET['next']);
require_once(ROOT_DIR.'admin/'.$get['m'].'.php');
//print_r($_GET);
$queries = array();
//вставка перед
if ($prev>0 AND $n<$prev) {
	$queries[] = "UPDATE ".$module['table']." SET ".$table['_sorting']." = ".$table['_sorting']." - 1 WHERE ".$table['_sorting'].">".$n." AND ".$table['_sorting']."<=".$prev;
	$queries[] = "UPDATE ".$module['table']." SET ".$table['_sorting']." = ".$prev." WHERE id=".$id;
}
//вставка после
elseif ($next>0 AND $n>$next) {
	$queries[] = "UPDATE ".$module['table']." SET ".$table['_sorting']." = ".$table['_sorting']." + 1 WHERE ".$table['_sorting']."<".$n." AND ".$table['_sorting'].">=".$next;
	$queries[] = "UPDATE ".$module['table']." SET ".$table['_sorting']." = ".$next." WHERE id=".$id;
}
foreach ($queries as $k=>$v) {
	mysql_fn('query',$v);
}
