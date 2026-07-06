<?php

// NESTED SETS - ИЗМЕНЕНИЕ ДЕРЕВА
require_once(ROOT_DIR.'admin/modules/'.$get['m'].'.php');
$result = nested_sets($get['m'],intval($_GET['id']),intval($_GET['select']),$_GET['insert'],$filter);
if ($result!==true) echo $result;
