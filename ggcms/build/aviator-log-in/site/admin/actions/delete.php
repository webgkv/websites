<?php

//удаление записей в БД и файлов
/*
 * v.1.2.0 - добавлен запрен на удаление админа
 * v1.4.14 - event_func
 * v1.4.16 - $delete удалил confirm
 * v1.4.18 - api_response
 */

$api = array();
//$api['error_text'] = 'error!';
if (access('admin delete')==false) {
	$api['error_text'] = "you don't have access to delete!";
	api_die($api);
}

//типы удаления
$array = array(
	//'file',	//удаление файла из подпапки (загрузка при помощи simple)
	'key',    //удаление ключа и файла  (загрузка при помощи mysql)
	'id'    //удаление записи и всех файлов
);
$get['type'] = isset($_GET['type']) ? $_GET['type'] : '';            //вид удаления
//$get['m'] = isset($_GET['m']) ? $_GET['m'] : '';					//модуль или папка
$get['id'] = isset($_GET['id']) ? abs(intval($_GET['id'])) : 0;    //индекс
$get['key'] = isset($_GET['key']) ? $_GET['key'] : '';                //ключ в БД
$file = isset($_GET['file']) ? $_GET['file'] : '';            //имя файла

if (!in_array($get['type'], $array)) $api['error_text'] = 'ошибка типа удаления!';
elseif ($get['id'] == 0) $api['error_text'] = 'ошибка индекса!';
elseif (!preg_match("/^[-a-z0-9_]+$/", $get['m'])) $api['error_text'] = 'ошибка модуля!';
elseif (!preg_match("/^[a-z0-9_]*$/", $get['key'])) $api['error_text'] = 'ошибка ключа!';

if (isset($api['error_text'])) api_die($api);

//if (!preg_match("/^[a-z0-9_.]*$/",$file))	die ('ошибка имени файла!');
if (file_exists(ROOT_DIR . 'admin/modules/' . $get['m'] . '.php')) {
	require_once(ROOT_DIR . 'admin/modules/' . $get['m'] . '.php');
}
else {
	$api['error_text'] = 'нет модуля';
	api_die($api);
}


//удаление файла из подпапки (загрузка при помощи simple) - похоже уже не используется
/*if ($get['type']=='file') {
	$dir	= 'files/'.$module['table'].'/'.$get['id'].'/'.$get['key'].'/';
	$path	= ROOT_DIR.$dir.$file;
	echo $path;
	if (is_file($path)) {
		if (unlink($path)) {
			$message = '1';
			if (is_dir(ROOT_DIR.$dir) && $handle = opendir(ROOT_DIR.$dir)) {
				while (false !== ($folder = readdir($handle))) {
					if ($folder!='.' && $folder!='..') {
						if (is_dir(ROOT_DIR.$dir.$folder))
							if (is_file(ROOT_DIR.$dir.$folder.'/'.$file))
								unlink(ROOT_DIR.$dir.$folder.'/'.$file);
					}
				}
				closedir($handle);
			}
		}
		else $message = "не удалось удалить файл!";
	}
	else $message = "нет такого файла!";//."files/$get['m']/$get['id']/$get['key']/$file";
}*/
//удаление ключа и файла  (загрузка при помощи mysql)
if ($get['type'] == 'key') {
	$relative = 'files/' . $module['table'] . '/' . $get['id'] . '/' . $get['key'] . '/';
	$path = ROOT_DIR . $relative;
	if (is_dir($path)) {
		delete_all($path);
	}
	if (!is_dir($path)) {
		if (mysql_fn('update', $module['table'], array($get['key'] => '', 'id' => $get['id']))) {

		}
		else $api['error_text'] = 'не удалось удалить записть о файле';
		//v1.3.17 - удаление превью
		if (isset($config['_imgs'][$module['table']])) {
			foreach ($config['_imgs'][$module['table']] as $k => $v) {
				$path = ROOT_DIR . '_imgs/' . $v . '/' . $relative;
				delete_all($path);
			}
		}
	}
	else $api['error_text'] = "не удалось удалить файл!";

	//логирование
	$logs = array(
		'user' => $user['id'],
		'date' => date('Y-m-d H:i:s'),
		'parent' => $get['id'],
		'module' => $module['table'],
		'type' => 2,
		'ip' => get_ip(),
		'fields' => $get['key']
	);
	mysql_fn('insert', 'logs', $logs);
}
//удаление записи и всех файлов
elseif ($get['type'] == 'id') {
	//v.1.2.0 - запрет удаления админа
	if ($module['table'] == 'users' AND $get['id'] == 1) {
		$api['error_text'] = 'нельзя удалить администратора';
	}
	// v1.2.3 — do not allow deleting the first language
	if ($module['table'] == 'languages' AND $get['id'] == 1) {
		$api['error_text'] = 'cannot delete the first language';
	}
	if (isset($api['error_text'])) api_die($api);

	if ($delete) {
		if ($api['error_text'] = html_delete($delete)) {
			//die(strip_tags($content));
			api_die($api);
		}
	}

	$item = mysql_select("SELECT * FROM `" . $module['table'] . "` WHERE id = '" . $get['id'] . "'", 'row');
	if ($item == false) {
		$api['error_text'] = 'нет такой записи';
		api_die($api);
	}
	mysql_fn('delete', $module['table'], $get['id']);

	//v1.4.14 - event_func
	$event_function = 'event_delete_' . $module['table'];
	if (function_exists($event_function)) {
		$event_function($item);
	}

	//nested sets - пересортировка
	if (array_key_exists('level', $item) AND array_key_exists('left_key', $item)) {
		$where = '';
		if ($module['table']=='users') {
			$filter = array(array('tree'));
		}
		if (isset($filter) && is_array($filter)) foreach ($filter as $k => $v) {
			$where .= " AND `" . $v[0] . "` = " . intval($item[$v[0]]);
		}
		mysql_fn('query', "
	        UPDATE `" . $module['table'] . "`
			SET left_key = CASE WHEN left_key > " . $item['left_key'] . "
								THEN left_key - 2
								ELSE left_key END,
				right_key = right_key-2
			WHERE right_key > " . $item['right_key'] . " AND level>0" . $where
		);
	}
	//depend - удаление связей
	if (isset($config['depend'][$module['table']])) {
		foreach ($config['depend'][$module['table']] as $k => $v) {
			mysql_fn('delete', $v, array(), " AND child = '" . intval($get['id']) . "'");
		}
	}
	//проверка удаления
	$num_rows = mysql_select("SELECT id FROM `" . $module['table'] . "` WHERE `id` = " . $get['id'] . " LIMIT 1", 'num_rows');
	if ($num_rows == 0) {
		$relative = 'files/' . $module['table'] . '/' . $get['id'] . '/';
		$path = ROOT_DIR . $relative;
		delete_all($path);
		if (is_dir($path)) $api['error_text'] = 'не удалось удалить папку';

		//v1.3.17 - удаление превью
		if (isset($config['_imgs'][$module['table']])) {
			foreach ($config['_imgs'][$module['table']] as $k => $v) {
				$path = ROOT_DIR . '_imgs/' . $v . '/' . $relative;
				delete_all($path);
			}
		}

		//удаление file_multi_db
		if ($tabs) {
			foreach ($form as $k => $v) {
				foreach ($v as $k1 => $v1) {
					if (is_array($v1) && $v1[0] == 'file_multi_db') {
						if ($file_multi_db = mysql_select("SELECT id FROM `" . $v1[1] . "` WHERE parent=" . $get['id'], 'rows')) {
							mysql_fn('delete', $v1[1], $get['id']);
							foreach ($file_multi_db as $row) {
								delete_all(ROOT_DIR . 'files/' . $v1[1] . '/' . $row['id'] . '/', true);
							}
						}
					}
				}
			}
		}
		else {
			foreach ($form as $k => $v) {
				if (is_array($v) && $v[0] == 'file_multi_db') {
					if ($file_multi_db = mysql_select("SELECT id FROM `" . $v[1] . "` WHERE parent=" . $get['id'], 'rows')) {
						mysql_fn('delete', $v[1], $get['id']);
						foreach ($file_multi_db as $row) {
							delete_all(ROOT_DIR . 'files/' . $v[1] . '/' . $row['id'] . '/', true);
						}
					}
				}
			}
		}
		//логирование
		$logs = array(
			'user' => $user['id'],
			'date' => date('Y-m-d H:i:s'),
			'parent' => $get['id'],
			'module' => $module['table'],
			'type' => 3,
			'ip' => get_ip(),
		);
		mysql_fn('insert', 'logs', $logs);
	}
	else $api['error_text'] = 'удаление не удалось!';
}
else $api['error_text'] = 'ошибка!';

api_die($api);

function api_die($api) {
	header('Content-type: application/json; charset=UTF-8');
	if (!isset($api['error_text'])) {
		$api['success'] = 1;
	}
	echo json_encode($api);
	die();
}
