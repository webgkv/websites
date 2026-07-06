<?php
/**
 * Content → Games → Categories
 */

require_once ROOT_DIR . 'functions/games_categories_func.php';
games_categories_ensure_table();

$page_name = 'Games — Categories';

$table = array(
	'id'       => 'position:asc slug name',
	'slug'     => '',
	'name'     => '',
	'position' => '',
	'display'  => 'boolean',
);

$tabs = array(1 => 'Category');

$form[1][] = array('input td4', 'slug', array('name' => 'Slug (URL filter, e.g. crash-p2e)'));
$form[1][] = array('input td4', 'name', array('name' => 'Name (default / EN)'));
$form[1][] = array('input td4', 'name2', array('name' => 'Name (lang column 2)'));
$form[1][] = array('input td4', 'name3', array('name' => 'Name (lang column 3)'));
$form[1][] = array('input td2', 'position', array('name' => 'Sort order'));
$form[1][] = array('checkbox td1', 'display', array('name' => 'Visible on site'));

// Block delete when games still use this category slug
if (!empty($get['id']) && (int)$get['id'] > 0) {
	$delete = array(
		'games' => "SELECT id FROM games WHERE category=(SELECT slug FROM games_categories WHERE id=" . (int)$get['id'] . " LIMIT 1) LIMIT 1",
	);
}

function event_change_games_categories($q, $old = false) {
	if (empty($q['id'])) {
		return;
	}
	$id = (int)$q['id'];
	$row = mysql_select('SELECT slug FROM games_categories WHERE id=' . $id . ' LIMIT 1', 'row');
	if (!$row) {
		return;
	}
	$new_slug = games_categories_normalize_slug(isset($q['slug']) ? $q['slug'] : $row['slug']);
	if ($new_slug === '') {
		return;
	}
	$old_slug = games_categories_normalize_slug($old && isset($old['slug']) ? $old['slug'] : $row['slug']);
	if ($new_slug !== $row['slug']) {
		mysql_fn('update', 'games_categories', array('id' => $id, 'slug' => $new_slug));
	}
	if ($old_slug !== '' && $new_slug !== $old_slug) {
		mysql_fn('query', "
			UPDATE games
			SET category='" . mysql_res($new_slug) . "'
			WHERE category='" . mysql_res($old_slug) . "'
		");
	}
}
