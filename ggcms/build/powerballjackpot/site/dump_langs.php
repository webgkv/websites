<?php
define('ROOT_DIR', __DIR__ . '/');
require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');

$langs = mysql_select("SELECT * FROM languages", 'rows');
echo json_encode($langs);
