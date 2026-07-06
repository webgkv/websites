<?php
/**
 * One-time script: add 4 footer text pages to the site tree.
 * Run: php site/scripts/add_footer_pages.php  or  /scripts/add_footer_pages.php?run=1 in browser
 *
 * Pages: About Us, Terms and Conditions, Privacy Policy, Responsible Gambling
 * URLs: about-us, terms-and-conditions, privacy-policy, responsible-gambling
 * Module: pages (Text page)
 */
define('ROOT_DIR', dirname(__DIR__) . '/');
if (!is_file(ROOT_DIR . 'config/config.php')) {
    die("Config not found. Copy to site/config/config.php or run this script on the server.\n");
}
if (php_sapi_name() === 'cli') {
    if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST'] = 'localhost';
    if (!isset($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'functions/mysql_func.php');

$run = (php_sapi_name() === 'cli') || !empty($_GET['run']);

$pages = array(
    array('name' => 'About Us', 'url' => 'about-us', 'title' => 'About Us', 'description' => 'About us'),
    array('name' => 'Terms and Conditions', 'url' => 'terms-and-conditions', 'title' => 'Terms and Conditions', 'description' => 'Terms and Conditions'),
    array('name' => 'Privacy Policy', 'url' => 'privacy-policy', 'title' => 'Privacy Policy', 'description' => 'Privacy Policy'),
    array('name' => 'Responsible Gambling', 'url' => 'responsible-gambling', 'title' => 'Responsible Gambling', 'description' => 'Responsible Gambling'),
);

if (!$run) {
    echo "Add 4 footer pages to DB. Run: php site/scripts/add_footer_pages.php  or  /scripts/add_footer_pages.php?run=1\n";
    exit(0);
}

$added = 0;
foreach ($pages as $p) {
    $exists = mysql_select("SELECT id FROM `pages` WHERE `url` = '" . mysql_res($p['url']) . "'", 'row');
    if ($exists) {
        echo "Skip (already exists): {$p['url']}\n";
        continue;
    }
    $max = (int) mysql_select("SELECT COALESCE(MAX(right_key), 0) AS m FROM `pages`", 'row')['m'];
    $row = array(
        'name'        => $p['name'],
        'url'         => $p['url'],
        'title'       => $p['title'],
        'description' => $p['description'],
        'text'        => '',
        'module'      => 'pages',
        'display'     => 1,
        'menu'        => 0,
        'menu2'       => 1,
        'parent'      => 0,
        'level'       => 1,
    );
    $id = mysql_fn('insert', 'pages', $row);
    if ($id && mysql_fn('update', 'pages', array('left_key' => $max + 1, 'right_key' => $max + 2, 'id' => $id))) {
        $added++;
        echo "Added: {$p['url']} (id=$id)\n";
    } else {
        echo "Error adding: {$p['url']}\n";
    }
}

echo "\nDone. Added $added page(s).\n";
