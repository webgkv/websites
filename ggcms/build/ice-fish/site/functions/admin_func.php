<?php

//функции админпанели
/*
 * v1.4.0 - html_render в админке
 * v1.4.1 - пагинатор в админке
 * v1.4.4 - html_array для таблицы
 * v1.4.5 - html_array для form_file
 * v1.4.7 - admin/template2
 * v1.4.10 - nested_sets - ошибки при многоязычности
 * v1.4.16 - $delete - удалил confirm
 * v1.4.17 - сокращение параметров form
 * v1.4.20 - form - select|multicheckbox|multiple пофиксил ошибку
 */

/**
 * кнопка удаления записи из БД и всех её файлов
 * @param string $delete
 * @return string
 * @see $delete
 * v1.4.16 - $delete - удалил confirm
 */
/**
 * Full-page edit URL for Content / Pages materials (works without JS).
 *
 * @param string $module table name from data-module (guides, games, casino_articles, pages, …)
 * @param int    $id     row id
 * @return string path+query or empty if unknown
 */
function admin_edit_form_url($module, $id) {
	global $get;
	$module = trim((string)$module);
	$id = (int)$id;
	if ($id <= 0 || $module === '') {
		return '';
	}
	if ($module === 'pages') {
		$url = '/admin.php?m=pages&u=form&id=' . $id . '&inline=1';
		if (!empty($get['tab'])) {
			$url .= '&tab=' . urlencode((string)$get['tab']);
		}
		return $url;
	}
	$tab_map = array(
		'guides'          => 'guides',
		'games'           => 'games',
		'casino_articles' => 'casinos',
		'promo'           => 'promo',
		'blog'            => 'blog',
	);
	if (!isset($tab_map[$module])) {
		return '';
	}
	$tab = $tab_map[$module];
	$url = '/admin.php?m=content&tab=' . urlencode($tab) . '&u=form&id=' . $id . '&inline=1';
	if ($tab === 'blog') {
		$stab = !empty($get['stab']) ? (string)$get['stab'] : 'blog';
		$url .= '&stab=' . urlencode($stab);
	}
	if ($tab === 'games') {
		$stab = !empty($get['stab']) ? (string)$get['stab'] : 'games';
		$url .= '&stab=' . urlencode($stab);
	}
	if (!empty($get['i18n_lang_id'])) {
		$url .= '&i18n_lang_id=' . (int)$get['i18n_lang_id'];
	}
	return $url;
}

function html_delete($delete='') {
	global $get,$a18n;
	$content = '';
	if ($get['id']>0 && $delete) {
		//если есть связанные записи
		foreach ($delete as $k=>$v) {
			if (strpos($v, ' ')) { //запрос
				if (mysql_select($v,'row')) $content.= '[related '.a18n($k).'] ';
			} else {
				$query = 'SELECT `id` FROM `'.$k.'` WHERE `'.$v.'` = '.$get['id'];
				if (mysql_select($query,'row')) {
					if (array_key_exists($k, $a18n))
						$content .= '<a href="admin.php?m='.$k.'&'.$v.'='.$get['id'].'">['.a18n($k).']</a> ';
					else $content .= 'relations';
				}
			}
		}
		//если есть связанные записи
		if ($content) return 'deletion is not possible: '.$content;
	}
}


/**
 * функция вывода строк таблицы
 * @param array $table - массив колонок таблицы
 * @param array $q - массив данных ряда
 * @param bool $head - вернуть шапку или ряд
 * @return string - ряд <tr>
 * @see $table
 * @version v1.2.122
 * v1.2.102 - добавлен второй аргумент в 'field' => '::function',
 * v1.2.122 - просмотр на сайте - _view
 */
/*
function table_row($table,$q,$head = false) {
	global $config,$url,$module;
	if (!isset($table['_edit'])) $table = array_merge(array('_edit'=>true),$table);
	elseif ($table['_edit']==false) unset($table['_edit']);
	if (!isset($table['_delete'])) $table['_delete'] = true;
	elseif ($table['_delete']==false) unset($table['_delete']);
	$content = '';
	//ШАПКА ТАБЛИЦЫ
	if ($head) foreach ($table as $k=>$v) {
		//v1.2.130 - чекбоксы для админки
		if ($k=='_check')		$content.= '<th style="text-align:center; padding:0px"><input type="checkbox" name="_check" /></th>';
		elseif ($k=='_tree') $content.= '<th class="colspan" style="padding:0 0 0 10px"><span class="sprite tree" title="дерево вложенности"></span></th>';
		elseif ($k=='_sorting') $content.= '<th class="colspan"><span class="sprite sorting" title="сортировка"></span></th>';
		elseif ($k=='_edit') {
			if ($v==='edit') {
				$content.= '<th style="padding:0; text-align:center"></th>';
			}
			else {
				$content.= '<th style="padding:0; text-align:center"><a class="sprite plus2 open" href="/admin.php?'.$url.'id=new" title="добавить новую запись"></a></th>';
			}
		}
		elseif ($k=='_view') {
			$content.= '<th width="20px"></th>';
		}
		elseif ($k=='_delete') $content.= '<th width="20px"></th>';
		elseif ($k=='display') $content.= '<th></th>';
		elseif ($v=='boolean') $content.= '<th></th>';
		elseif ($v=='img') $content.= '<th></th>';
		else {
			global $get;

			//$fieldset[$k]  = isset($fieldset[$k]) ? $fieldset[$k] : $k; //если нет $fieldset называем ключом
			$content.= '<th>';
			//скрытый селект для быстрого редактирования
			if (is_array($v) AND substr($k,-1)==':') {
				$content.= '<select name="'.$k.'">'.select('',$v).'</select>';
			}
			$k = trim($k,':'); //удаляем двоеточие от селекта
			if (isset($q['sort_array']) && array_key_exists($k,$q['sort_array'])) {
				if ($q['order']==$k) {
					if ($get['s']) $s = ($get['s']=='desc') ? 'asc' : 'desc';
					else $s = $q['sort_array'][$k];
					$a = $s=='asc' ? ' desc' : ' asc';
				}
				else {
					$s = $q['sort_array'][$k];
					$a = ' none '.$s;
				}
				$content.= '<a class="sort'.($q['order']==$k ? ' active' : '').'" href="?'.$url.'o='.$k.'&s='.$s.'"><span class="sprite '.$a.'"></span>'.a18n($k).'</a>';
			}
			else $content.= a18n($k);
			$content.= '</th>';
		}
	}
	//РЯД ТАБЛИЦЫ
	else foreach ($table as $k=>$v) {
		if ($v && !is_array($v)) {
			preg_match_all('/{(.*?)}/',$v,$matches,PREG_PATTERN_ORDER);
			foreach($matches[1] as $key=>$val) $matches[1][$key] = isset($q[$val]) ? $q[$val] : '';
			$v = str_replace($matches[0],$matches[1],$v);
		}
		//v1.2.130 - чекбоксы для админки
		if ($k=='_check')		$content.= '<td><input type="checkbox" name="_check" value="'.$q['id'].'"/></td>';
		elseif ($k=='_edit')		$content.= '<td align="center"><a href="/admin.php?'.$url.'id='.$q['id'].'" class="sprite edit open"></a></td>';
		elseif ($k=='_view') {
			$content.= '<td><a class="sprite view" target="_blank" href="'.get_url($v,$q).'"></a></td>';
		}
		elseif ($k=='_tree')	$content.= '<td class="level"><span class="sprite level item"></span></td>';
		elseif ($k=='_sorting')	$content.= '<td><span class="sprite sorting"></span></td>';
		elseif ($k=='_delete')	$content.= '<td align="center"><a class="sprite delete" href="#"></a></td>';
		elseif ($k=='id')		$content.= '<td align="right"><b>'.$q[$k].'</b></td>';
		elseif (is_array($v))	{
			if (substr($k,-1)==':') {
				$k = trim($k,':');
				//$content.= '<td><select name="'.$k.'">'.select($q[$k],$v).'</select></td>';
				$str = '';
				if (isset($q[$k]) AND isset($v[$q[$k]])) {
					$str = is_array($v[$q[$k]]) ? $v[$q[$k]]['name'] : $v[$q[$k]];
				}
				$content.= '<td class="select" data-id="'.$q[$k].'" data-name="'.$k.'">'.$str.'</td>';
			}
			else {
				$str = '';
				if (isset($q[$k]) AND isset($v[$q[$k]])) {
					$str = is_array($v[$q[$k]]) ? $v[$q[$k]]['name'] : $v[$q[$k]];
				}
				$content.= '<td><b>'.$str.'</b></td>';
			}
		}
		elseif ($v=='date')		$content.= '<td data-name="'.$k.'" class="post">'.$q[$k].'</td>';
		elseif ($v=='boolean' OR $v=='display') {
			$key = in_array($k,$config['boolean']) ? $k : 'boolean';
			$content.= '<td align="center" data-name="'.$k.'" data-key="'.$key.'">';//key - клас спрайта для иконки
			$content.= '<a class="sprite '.$key.'_'.($q[$k]==1 ? '1' : '0').' js_boolean" href="#" title="'.a18n($k).'"></a>';
			$content.= '</td>';
		}
		elseif ($v=='right')	$content.= '<td data-name="'.$k.'" align="right" class="post">'.$q[$k].'</td>';
		elseif ($v=='text')		$content.= '<td data-name="'.$k.'"><b>'.$q[$k].'</b></td>';
		elseif ($v=='img')		{
			//v1.2.115 заменил пути на get_img
			$img =  get_img($module['table'],$q,$k,'');
			$preview = get_img($module['table'],$q,$k,'a-');
			$content.= '<td align="center" data-name="'.$k.'">'.($q[$k] ? '<a onclick="return hs.expand(this)" href="'.$img.'"><img class="img" src="'.$preview.'" /></a>' : '').'</td>';
		}
		elseif ($v=='')			$content.= '<td data-name="'.$k.'" class="post">'.(isset($q[$k]) ? $q[$k] : '').'</td>';
		elseif (substr($v,0,2)=='::') {
			$function = substr($v,2);
			//v1.2.102 - добавлен второй аргумент в 'field' => '::function',
			if (function_exists($function)) $content.= $function($q,$k);
			else $content.= '<td>'.$function.'</td>';
		}
		else					$content.= '<td>'.$v.'</td>';
	}
	return $content;
}
*/

/**
 * Resolve list sort (same rules as table()) without rendering HTML.
 * Used by telemetry and for a single source of truth for ORDER BY.
 *
 * @param array<string,mixed> $table admin $table config
 * @param array<string,mixed> $get_src typically $_GET (keys o, s)
 * @return array{sort_array:array<string,string>,order_key:string,order_sql_dir:string,effective_o:string,effective_s:string,order_from_get:bool,order_by_fragment:string}
 */
function admin_table_list_sort_meta(array $table, array $get_src) {
	$sorting = explode(' ', $table['id']);
	$sort_array = array();
	foreach ($sorting as $seg) {
		$seg = explode(':', $seg);
		$sort_array[$seg[0]] = (isset($seg[1]) && $seg[1] == 'desc') ? 'desc' : 'asc';
	}
	$o = isset($get_src['o']) ? (string) $get_src['o'] : '';
	$order_from_get = ($o !== '' && array_key_exists($o, $table));
	$order_key = $order_from_get ? $o : key($sort_array);
	$s = isset($get_src['s']) ? (string) $get_src['s'] : '';
	if (!$order_from_get || $s === '') {
		$s = $sort_array[$order_key];
	}
	$effective_o = $order_from_get ? $o : $order_key;
	$effective_s = $s;
	$sort_sql = ($s === 'desc') ? 'DESC' : 'ASC';
	$order_bt = str_replace('.', '`.`', $order_key);
	$frag = ' ORDER BY `' . $order_bt . '` ' . $sort_sql;
	if ($order_key !== 'id') {
		$id_sort = ($sort_sql === 'DESC') ? 'ASC' : (($sort_sql === 'ASC') ? 'DESC' : 'ASC');
		$frag .= ',`id` ' . $id_sort;
	}
	return array(
		'sort_array' => $sort_array,
		'order_key' => $order_key,
		'order_sql_dir' => $sort_sql,
		'effective_o' => $effective_o,
		'effective_s' => $effective_s,
		'order_from_get' => $order_from_get,
		'order_by_fragment' => $frag,
	);
}

/**
 * функция формирования нтмл кода таблицы в админке
 * @param array $table - массив колонок таблицы
 * @param string $query - запрос
 * @return string - нтмл код таблицы
 * @see $table, table_row()
 * v1.4.1 - пагинатор в админке
 */
function table ($table,$query='') {
	global $get,$filter,$module;
	$array_count	= array(20=>20,50=>50,100=>100,'all'=>'all');
	$count			= array_key_exists(@$_GET['c'],$array_count) ? $_GET['c'] : key($array_count);
	if ($count=='all') $count = 0;
	$sorting		= explode(' ',$table['id']);
	$sort_array = array();
	foreach ($sorting as $s) {
		$s = explode(':',$s);
		$sort_array[$s[0]] = (isset($s[1]) && $s[1]=='desc') ? 'desc' : 'asc';
	}
	$tree = array_key_exists('_tree',$table);
	$sorting = array_key_exists('_sorting',$table);
	//ГЕНЕРАЦИЯ $query
	if ($query=='') {
		$query = "SELECT ";
		if ($tree) $query.= $module['table'].'.level,'.$module['table'].'.parent,';
		foreach ($table as $k=>$v) if ($k[0]!='_') $query.= '`'.$k.'`,';
		$query = substr($query,0, -1);
		$query.= " FROM ".$module['table']." WHERE 1";
		//если есть фильтр (например, для языка)
		if (isset($filter) && is_array($filter)) foreach ($filter as $k=>$v) if (isset($_GET[$v[0]])){
			$query.= " AND ".$module['table'].".".$v[0]." = ".intval($_GET[$v[0]]);
		}
	}
	//НАСТРОЙКА СОРТИРОВКИ
	//деревовидный список
	$th = array();
	if ($tree) {
		$order = $module['table'].".left_key";
		$sort  = '';
	}
	//сортировка
	elseif ($sorting) {
		$order = $module['table'].'.'.$table['_sorting'];
		$sort  = '';
	}
	//обычный список
	else {
		$meta = admin_table_list_sort_meta($table, $_GET);
		$_GET['o'] = $meta['effective_o'];
		$_GET['s'] = $meta['effective_s'];
		$get['o'] = $_GET['o'];
		$get['s'] = $_GET['s'];
		$th['order'] = $meta['order_key'];
		$th['sort_array'] = $meta['sort_array'];
		$query .= $meta['order_by_fragment'];
	}

	if (!$tree && !$sorting) {
		// ORDER BY already appended for normal lists (see above).
	} else {
		$order = str_replace('.','`.`',$order);
		$query .= ' ORDER BY `'.$order.'` '.$sort;
		// Tie-breaker on id (skip when primary is already id — avoids ORDER BY id ASC, id DESC).
		if ($order !== 'id') {
			$id_sort = ($sort === 'DESC') ? 'ASC' : (($sort === 'ASC') ? 'DESC' : 'ASC');
			$query .= ',`id` '.$id_sort;
		}
	}

	$data = mysql_data(
		$query,
		false,
		$count,
		@$_GET['n']
	);
	$data['array_count'] = $array_count;
	$data['type'] = $tree ? 'tree':'';
	$data['type'] = $sorting ? 'sorting':$data['type'];
	$data['table'] = $table;
	// data-module is used by admin JS for opening forms and quick edits.
	// For wrapper module "content" we must expose the concrete child module (guides/games/...).
	// For mirrored modules (e.g. authors -> site_authors) we must expose the route module key.
	$data['module'] = ((string)($get['m'] ?? '') === 'content') ? $module['table'] : (string)($get['m'] ?? $module['table']);
	$data = array_merge($data,$th);
	//v1.4.4
	return html_render('table/table',$data);
}

/**
 * фильтр, ситнаксис аналогичен select()
 * @param $key - ключ $_GET
 * @param string|array $query - название таблицы | SQL запрос | массив
 * @param string $default - значение по умолчанию
 * @param bool $clear - соединять значения других фильтров либо сбрасывать
 * @return html - html код фильтра
 * v1.4.7 - admin/template2
 */

function filter ($key,$query='',$default='',$clear=false) {
	global $get,$config;
	if ($clear==false) $url=build_query($key);
	else $url = 'm='.$_GET['m'];
	if ($query!='') {
		$content = select (isset($get[$key]) ? $get[$key] : '',$query,$default);
		//v1.4.7 - admin/template2
		$content = '<div class="filter form-group col-xl-2"><select class="form-control" name="'.$key.'" onchange="top.location=\'admin.php?'.$url.'&'.$key.'=\'+this.value;">'.$content.'</select></div>';
	}
	else {
		if ($config['style']=='admin/templates') {
			$content = '<div class="filter" style="float:right"><input placeholder="'.a18n('search').'" name="'.$key.'" value="'.htmlspecialchars(stripslashes_smart(isset($_GET[$key]) ? $_GET[$key] : '')).'" /><a class="sprite search" href="admin.php?'.$url.'&'.$key.'="></a></div>';
		}
		//v1.4.7 - admin/template2
		else {
			$content = '
				<div class="filter form-group col-xl-3">
                    <input name="'.$key.'" type = "text" class="form-control" placeholder = "'.a18n('search').'"  value="'.htmlspecialchars(stripslashes_smart(isset($_GET[$key]) ? $_GET[$key] : '')).'">
                    <a href="admin.php?'.$url.'&'.$key.'=" class="sprite search" >
                        <i data-feather="search"></i>
                    </a>
                </div >';
		}
	}
	return $content;
}

/**
 * конструктор полей формы
 * @param string $class - тип и класс поля
 * @param string $key - ключ $_GET
 * @param array $param array(
 *  'attr'=>'id="field"',
 *  'value'=>'значение поля'
 *  'name'=>'название поля',
 *  'help'=>'всплывающая подсказка',
 *  'select'=>array() - массив для селекта
 * )
 * @return string
 * @version v1.4.20
 * v1.1.32 - замена iconv на mb
 * v1.2.3 - убрал $lang['i'] в сеополях
 * v1.2.19 - оптимизировал multicheckbox
 * v1.2.70 - карта яндекс
 * v1.2.73 - карта гугл
 * v1.2.77 - правки в картах
 * v1.2.79 - правка в user
 * v1.2.126 - правки в инициализации переменных
 * v1.3.11 - hypertext
 * v1.4.0 - html_render в админке
 * v1.4.15 - multiple
 * v1.4.17 - сокращение параметров form
 * v1.4.20 - select|multicheckbox|multiple пофиксил ошибку
 */
function form ($class,$key,$param=array()) {
	global $get,$filter,$config,$module,$post; //массив с названиями блоков
	//v1.4.7 - admin/template2
	if ($config['style']!='admin/templates') {
		$class = str_replace('td','col-xl-',$class);
	}
	//атрибуты поля, стили
	$param['attr'] = isset($param['attr']) ? $param['attr'] : '';
	//title
	$param['title'] = isset($param['title']) ? $param['title'] : '';
	//подсказка
	$param['help'] = isset($param['help']) ? $param['help'] : '';
	//название поля, по умолчанию указано в массиве $fieldset
	$param['name'] = isset($param['name']) ? $param['name'] : a18n($key);
	//значение поля - v1.4.17
	$value = isset($param['value']) ? $param['value'] : @$post[$key];
	$type	= current(explode(' ',$class));

	//v1.4.15 - multiple
	if (in_array($type,array('select','multicheckbox','multiple'))) {
		//$value[0] = isset($post[$key]) ? $post[$key] : '';
		//v1.4.20 - select|multicheckbox|multiple пофиксил ошибку
		$value[0] = @$param['value'][0]===true ? @$post[$key] : @$param['value'][0];

	}
	elseif(in_array($type,array('parent','seo','basket'))) {
		$value = isset($post) ? $post : array();
	}

	//v1.4.7 - admin/template2
	$explode	= explode(' ',$class);
	if (in_array('datepicker',$explode)) {
		if ($value=='0000-00-00') $value = '';
	}
	if (in_array('datetimepicker',$explode)) {
		if ($value=='0000-00-00 00:00:00') $value = '';
	}
	$data = array(
		'title'=>$param['title'],
		'help'=>$param['help'],
		'name'=>$param['name'],
		'class'=>$class,
		'attr'=>$param['attr'],
		'value'=>$value,
		'key'=>$key
	);

	if ($type=='multicheckbox') {
		//dd($value);
		$data['value'] = is_array($value[0]) ? $value[0] : ($value[0] ? explode(',',$value[0]) : array());
		//dd($val);
		$data['data'] = is_array($value[1]) ? $value[1] : ($value[1] ? mysql_select($value[1],'rows') : array());
		if ($data['data']) {
			//переделка простого массива в многоуровневый
			foreach ($data['data'] as $k=>$v) {
				if (!is_array($v)) {
					$data2 = array();
					foreach ($data['data'] as $k1=>$v1) {
						$data2[] = array('id'=>$k1,'name'=>$v1);
					}
					$data['data'] = $data2;
				}
				break;
			}
		}
	}

	if ($type=='parent') {
		$cl = explode(' ',$class);
		$cl[1] = isset($cl[1]) ? $cl[1] : 'td4';
		$cl[2] = isset($cl[2]) ? $cl[2] : 'td4';
		$previos = 0;
		$parent_array = $previos_array = array();
		if (isset($_GET['id']) && $_GET['id']=='new') {
			$value['left_key'] = $value['right_key'] = $value['parent'] = 0;
			$value['level'] = 1;
			if (isset($filter) && is_array($filter)) foreach ($filter as $k=>$v) {
				$value[$v[0]] = isset($get[$v[0]]) ? $get[$v[0]] : '';
			}
		}
		if (isset($value['left_key'])) {
			//если есть фильтр (например, для языка)
			$where = '';
			if (isset($filter) && is_array($filter)) foreach ($filter as $k=>$v) {
				if (isset($value[$v[0]]))
					$where.= " AND ".$v[0]." = '".$value[$v[0]]."'";
			}
			$previos = mysql_select("SELECT id FROM `".$module['table']."` WHERE left_key>".$value['left_key']." AND level=".$value['level']." $where ORDER BY left_key LIMIT 1",'string');
			if ($previos==false) $previos=0;
			$parent_array = "
				SELECT id,name,level,parent
				FROM `".$module['table']."`
				WHERE (left_key<'".$value['left_key']."' OR left_key>'".$value['right_key']."') $where
				ORDER BY left_key
			";
			$previos_array = mysql_select("
				SELECT id,name,level,parent
				FROM `".$module['table']."`
				WHERE parent='".$value['parent']."' AND id!='".$value['id']."' $where
				ORDER BY left_key
			",'array');
			if ($previos_array==false) $previos_array = array();
		}
		$previos_array = array(0=>'At the end of the list') + $previos_array;
		$content = form('select '.$cl[1],'nested_sets[parent]',array(
			'value'=>array(isset($value['parent']) ? $value['parent'] : '',$parent_array,'List root'),
			'name'=>'Parent',
			'help'=>'The entry will be located at the root of the list or inside the selected element'
		));
		$content.= form('select '.$cl[2],'nested_sets[previous]',array(
			'value'=>array($previos,$previos_array),
			'name'=>'Position inside parent before',
			'help'=>'The entry will be at the beginning of the list or before the selected element'
		));
		return $content;
	}
	else {
		return html_render('form/'.$type,$data);
	}
}

/**
 * загрузка файлов
 * @param $type - тип загрузки (mysql|simple|file|file_multi|file_milti_db)
 * @param $key - поле в таблице где будут хранится названия файлов
 * @param $name - название блока загрузки
 * @param string $param = array(
 *  'name'=>'имя поля',
 *  'sizes'=>array(''=>'1000x1000'),
 *  'fields'=>array('name'=>'input','title'=>'input','display'=>'checkbox')
 * )
 *  размеров картинки
 * @param array $fields - настройки доп полей для мультизагрузки файлов
 * @return string
 * @version v1.4.17
 * v1.1.16 - функция copy2 для загрузки файлов с генерацией превью
 * v1.1.25 - добавил селект для file_multi и simple
 * v1.2.42 - поправил ошибку с версии v1.1.25
 * v1.3.17 - удаление _imgs
 * v1.4.5 - html_array
 * v1.4.17 - сокращение параметров form
 */

/**
 * File fields handled by form_file after the main row UPDATE (img must not be written without disk file).
 *
 * @param array $form
 * @param array $tabs
 * @return string[]
 */
function admin_deferred_file_field_keys($form, $tabs = array()) {
	$rows = array();
	if (is_array($tabs) && count($tabs) > 0 && is_array($form)) {
		foreach ($form as $tab_rows) {
			if (is_array($tab_rows)) {
				$rows = array_merge($rows, $tab_rows);
			}
		}
	} elseif (is_array($form)) {
		$rows = $form;
	}
	$keys = array();
	foreach ($rows as $v) {
		if (!is_array($v) || count($v) < 2) {
			continue;
		}
		if (preg_match('/\bfile\b/', (string)$v[0])) {
			$keys[] = (string)$v[1];
		}
	}
	return array_values(array_unique($keys));
}

function form_file ($type,$key, $param = array()) {
	global $get,$config,$post,$module,$modules;
	//имя поля
	$name = isset($param['name']) ? $param['name'] : a18n($key);
	//размеры картинок
	$param['sizes'] = @$param['sizes'] ? $param['sizes'] : '';
	//доп поля для мультиселекта
	$fields = isset($param['fields']) ? $param['fields'] : array('filename'=>'input','alt'=>'input','title'=>'input','display'=>'checkbox');
	$message = ''; //сообщение с ошибкой
	$t = current(explode(' ',$type));
	$use_media_library = ($t === 'file' && $key === 'img');
	if ($use_media_library) {
		require_once ROOT_DIR . 'functions/media_library.php';
		require_once ROOT_DIR . 'functions/media_image.php';
	}
	$relative = 'files/'.$module['table'].'/'.$get['id'].'/'.$key.'/'; //v1.3.17 относительный путь папки
	if($t=='gallery') {
		$relative = 'files/gallery/'.$post[$key].'/img/';
	}
	$root = ROOT_DIR.$relative; //папка от корня основной папки
	//обычная загрузка файлов если нет нтмл5
	if ($config['uploader']==0) {
		if ($t=='file') $t = 'mysql';
		if ($t=='file_multi') $t = 'simple';
	}
	//обычная загрузка
	if ($t=='simple') {
		$photos = (isset($post[$key]) && $post[$key]) ? unserialize($post[$key]) : array();
		$n = $photos ? max(array_keys($photos)) : 0; //порядковый номер в массиве
		//данные объекта
		$q = array(
			'id' => $get['id'],
			$key => $photos
		);
		//удаление лишнего
		if ($get['id']!='new' && is_dir($root) && $handle = opendir($root)) {
			while (false !== ($dir = readdir($handle))) {
				if ($dir!= '.' AND $dir!= '..') {
					//удаление масива если нет картинки
					if (!is_dir($root.$dir)) {
						if (isset($photos[$dir])) unset($photos[$dir]);
					}
					//удаление картинки, если нет масива
					elseif (!array_key_exists($dir,$photos)) {
						delete_all($root.$dir.'/',true);
						//v1.3.17 - удаление превью
						if (isset($config['_imgs'][$module['table']])) {
							foreach ($config['_imgs'][$module['table']] as $k=>$v) {
								$path = ROOT_DIR.'_imgs/'.$v.'/'.$relative.$dir.'/';
								delete_all($path,true);
							}
						}
					}
				}
			}
			closedir($handle);
		}
		//загрузка файлов
		if ($get['u']=='edit') {
			if (is_dir($root) || mkdir($root,0755,true)) { //создание папки
				$temp = isset($_FILES[$key]['tmp_name']) ? $_FILES[$key]['tmp_name'] : ''; //массив файлов
				if (is_array($temp)) {
					//формируем массив
					foreach($temp as $k1=>$v1) {
						if (is_uploaded_file($v1)) {//проверка записался ли файл на сервер во временную папку
							$n++;
							$file = strtolower(trunslit($_FILES[$key]['name'][$k1])); //название файла
							//успешное копирование файла
							if (copy2 ($v1,$root.$n.'/',$file,$param['sizes'])) {
								$photos[$n] = array(
									'file' => $file,
									'name' => current(explode('.',$_FILES[$key]['name'][$k1],2)),
									'display' => 1,
								);
							}
							else $message.= $file.' ошибка загрузки!<br />';
						}
					}
					$q[$key] = serialize($photos);
					mysql_fn('update',$module['table'],$q);
				}
			}
			else $message = 'ошибка создания каталога!';
		}

		$data = array(
			'key'=>$key,
			'name'=>$name,
			'message'=>$message,
			'photos'=>$photos,
			'module'=>$module['table'],
			'item'=>$q,
			'fields'=>$fields
		);
		return html_array('form/file_simple',$data);
	}
	//загрузка с записью в БД
	elseif ($t=='mysql') {
		$file = isset($post[$key]) ? $post[$key] : ''; //название файла
		$root = ROOT_DIR.'files/'.$module['table'].'/'.$get['id'].'/'.$key.'/'; //папка от корня основной папки
		$temp = isset($_FILES[$key]['tmp_name']) ? $_FILES[$key]['tmp_name'] : ''; //error_handler(1,2,3,'-'.serialize($_FILES).'-');
		//данные объекта
		$q = array(
			'id' => $get['id'],
			$key => $file
		);
		$message = '';//сообщение с ошибкой
		if ($get['u']=='edit') {
			if (is_uploaded_file($temp)) {//проверка записался ли файл на сервер во временную папку
				if (is_dir($root)) delete_all($root,false); //удаляем без слеша в конце
				if (is_dir($root) || mkdir ($root,0755,true)) { //создание папок для файла
					$file = strtolower(trunslit($_FILES[$key]['name'])); //название файла
					//успешное копирование файла
					if (copy2 ($temp,$root,$file,$param['sizes'])) {
						$q[$key] = $file;
						$message = 'файл загружен!';
					} else {
						$q[$key] = '';
						$message = 'ошибка загрузки!';
					}
					mysql_fn('update',$module['table'],$q);
				}
				else $message = 'ошибка создания каталога!';
			}
		}
		//шаблон
		$img = get_img($module['table'],$q,$key,'');
		$data = array(
			'key'=>$key,
			'img'=>$img,
			'preview'=>'/_imgs/100x100'.$img,
			'is_file'=>is_file($root.$file),
			'file'=>$file,
			'message'=>$message,
			'name'=>$name
		);
		return html_array('form/file_mysql',$data);
	}
	//загрузка с записью в БД (HTML5)
	elseif ($t=='file') {
		$file = $post[$key] = isset($post[$key]) ? $post[$key] : ''; // filename or files/media/… path
		//данные объекта
		$q = array(
			'id' => $get['id'],
			$key => $file
		);
		if ($get['u']=='edit') {
			//ручное удаление картинки
			if ($file=='') {
				$old_img = '';
				if ($get['id'] > 0) {
					$old_row = mysql_select('SELECT `' . mysql_res($key) . '` FROM `' . mysql_res($module['table']) . '` WHERE id=' . (int)$get['id'] . ' LIMIT 1', 'row');
					$old_img = $old_row ? (string)$old_row[$key] : '';
				}
				if ($use_media_library && $old_img !== '' && function_exists('media_library_is_stored_path') && media_library_is_stored_path($old_img)) {
					media_library_delete($old_img);
				} else {
					delete_all($root,true);
					//v1.3.17 - удаление превью
					if (isset($config['_imgs'][$module['table']])) {
						foreach ($config['_imgs'][$module['table']] as $k=>$v) {
							$path = ROOT_DIR.'_imgs/'.$v.'/'.$relative;
							delete_all($path,true);
						}
					}
				}
			}
			// Path from media picker — only if file exists on disk.
			$file_norm = function_exists('media_library_normalize_db_path')
				? media_library_normalize_db_path($file)
				: ltrim(str_replace('\\', '/', (string)$file), '/');
			if ($use_media_library && $file_norm !== '' && !ctype_digit($file_norm)) {
				$resolved = media_library_resolve_pickable_path($file_norm);
				if ($resolved !== '') {
					if ($get['id'] > 0) {
						$old_row = mysql_select('SELECT `' . mysql_res($key) . '` FROM `' . mysql_res($module['table']) . '` WHERE id=' . (int)$get['id'] . ' LIMIT 1', 'row');
						$old_img = $old_row ? (string)$old_row[$key] : '';
						if ($old_img !== '' && $old_img !== $resolved && media_library_is_stored_path($old_img)) {
							media_library_delete($old_img);
						}
					}
					$q[$key] = $resolved;
					$file = $resolved;
					$post[$key] = $resolved;
					mysql_fn('update', $module['table'], $q);
				} elseif ($file_norm !== '' && function_exists('media_library_is_pickable_image_path')
					&& media_library_is_pickable_image_path($file_norm)
				) {
					$message = 'Image file not found on server: ' . $file_norm;
					$q[$key] = '';
					$file = '';
					$post[$key] = '';
					mysql_fn('update', $module['table'], array('id' => $get['id'], $key => ''));
				}
			}
			$temp = ROOT_DIR.'files/temp/'.$file.'/'; //временная папка на сервере
			//если название файла целое число и есть временная папка, значит происходит загрузка нового файла
			if (is_numeric($post[$key]) AND is_dir($temp) AND $handle = opendir($temp)) {
				$temp_file = ''; //название временного файла на сервере
				$temp_name = '';
				while (false !== ($f = readdir($handle))) {
					if (strlen($f)>2 && is_file($temp.$f)) {
						$temp_name = $f;
						$temp_file = $temp.$f;
						break;
					}
				}
				closedir($handle);
				$stored = false;
				if ($use_media_library && $temp_file) {
					$stored = media_library_save_main_image($temp_file, $temp_name, $param['sizes']);
					if ($stored) {
						// Remove previous media-library file for this record
						if ($get['id'] > 0) {
							$old_row = mysql_select('SELECT `' . mysql_res($key) . '` FROM `' . mysql_res($module['table']) . '` WHERE id=' . (int)$get['id'] . ' LIMIT 1', 'row');
							$old_img = $old_row ? (string)$old_row[$key] : '';
							if ($old_img !== '' && $old_img !== $stored && media_library_is_stored_path($old_img)) {
								media_library_delete($old_img);
							}
						}
						$q[$key] = $stored;
						$file = $stored;
					}
				} elseif ($temp_file) {
					$file = strtolower(trunslit($temp_name));
					if (copy2($temp_file, $root, $file, $param['sizes'])) {
						$q[$key] = $file;
					} else {
						$q[$key] = '';
						$file = '';
					}
				} else {
					$q[$key] = '';
					$file = '';
				}
				$post[$key] = $q[$key];
				mysql_fn('update',$module['table'],$q);
				delete_all($temp,true);
			}
		}
		$disk_path = '';
		if ($use_media_library && $file !== ''
			&& function_exists('media_library_is_pickable_image_path')
			&& media_library_is_pickable_image_path($file)
		) {
			$disk_path = ROOT_DIR . media_library_normalize_db_path($file);
		} elseif ($file !== '') {
			$disk_path = $root . (strpos($file, '/') !== false ? basename($file) : $file);
		}
		$data = array(
			'name'=>$name,
			'type'=>$type,
			'is_file'=>($disk_path !== '' && is_file($disk_path)),
			'key'=>$key,
			'item'=>$q,
			'sizes'=>$param['sizes'],
			'module'=>$module['table'],
			'file'=>$file,
			'img'=>get_img($module['table'],$q,$key)
		);
//		return html_array('form/file',$data);
		return html_array("form/$t",$data);
	}



























	elseif ($t=='gallery') {
		$file = (isset($post[$key]) && $post[$key]) ? mysql_select('select * from gallery where id='.$post[$key].' limit 1','row') : '';
		//данные объекта
		$q = array(
			'id' => $post[$key],
			'img' => isset($file['img'])?$file['img']:'',
		);
//		if ($get['u']=='edit') {
//			//ручное удаление картинки
//			if ($file=='') {
//				delete_all($root,true);
//				//v1.3.17 - удаление превью
//				if (isset($config['_imgs'][$module['table']])) {
//					foreach ($config['_imgs'][$module['table']] as $k=>$v) {
//						$path = ROOT_DIR.'_imgs/'.$v.'/'.$relative;
//						delete_all($path,true);
//					}
//				}
//			}
//			$temp = ROOT_DIR.'files/temp/'.$file.'/'; //временная папка на сервере
//			//если название файла целое число и есть временная папка, значит происходит загрузка нового файла
//			if (is_numeric($post[$key]) AND is_dir($temp) AND $handle = opendir($temp)) {
//				$temp_file = ''; //название временного файла на сервере
//				while (false !== ($f = readdir($handle))) {
//					if (strlen($f)>2 && is_file($temp.$f)) {
//						$file = strtolower(trunslit($f)); //
//						$temp_file = $temp.$f;
//						break;
//					}
//				}
//				//успешное копирование файла
//				if (copy2 ($temp_file,$root,$file,$param['sizes'])) {
//					$q[$key] = $file;
//				}
//				//ошибка
//				else {
//					$q[$key] = '';
//				}
//				$post[$key] = $q[$key];
//				mysql_fn('update',$module['table'],$q);
//				//удаляем временный файл
//				delete_all($temp,true);
//			}
//		}
		$data = array(
			'name'=>$name,
			'type'=>$type,
			'is_file'=>is_file($root.$file),
			'key'=>$key,
			'item'=>$q,
			'sizes'=>$param['sizes'],
			'module'=>$module['table'],
			'file'=>isset($file['img'])?$file['img']:'',
			'alt' => isset($file['alt'])?$file['alt']:'',
			'title' => isset($file['title'])?$file['title']:'',
			'img'=>get_img('gallery',$q,'img')
		);
		return html_array("form/$t",$data);
	}





	//обычная загрузка (HTML5)
	if ($t=='file_multi') {
		//error_handler(1,serialize($_FILES),1,1);
		$photos = (isset($post[$key]) && $post[$key]) ? unserialize($post[$key]) : array();

//x3
		foreach($photos as $k=>$v) {
			if($v['filename']) {
				$ext=preg_replace('#^.*(\.[^\.]+)$#iu','$1',$v['file']);
				$photos[$k]['file']=$v['filename'].$ext;
			}
		}
//x3

		//загрузка файлов
		if ($get['u']=='edit' AND $photos) {
			if ($photos) {
				$update = 0;
				if (is_dir($root) || mkdir($root,0755,true)) { //создание папки
					foreach ($photos as $n=>$val) {
						$temp = ROOT_DIR.'files/temp/'.@$val['temp'].'/';
						//если есть временная папка, то копируем картинку
						if (@$val['temp'] AND $handle = opendir($temp)) {
							$update++;
							$temp_file = ''; //название временного файла на сервере
							while (false !== ($f = readdir($handle))) {
								if (strlen($f)>2 && is_file($temp.$f)) {
//x3
//									$file = strtolower(trunslit($f));
									$file = strtolower($val['file']);
//x3
									$temp_file = $temp.$f;
									break;
								}
							}
							//успешное копирование файла
							if (copy2 ($temp_file,$root.$n.'/',$file,$param['sizes'])) {
								$photos[$n]['file'] = $file;
								unset($photos[$n]['temp']);
							}
							else unset($photos[$n]);
							//удаляем временную папку
							delete_all(ROOT_DIR.'files/temp/'.$val['temp'].'/',true);
						}
						//v1.3.8 удаляем значение временной папки
						unset($photos[$n]['temp']);
					}
				}
				if ($update>0) mysql_fn('update',$module['table'],array('id'=>$get['id'],$key=>$photos ? serialize($photos) : ''));
			}
		}
		//список загруженых файлов
		if ($get['id']!='new' && is_dir($root)) {
			if ($handle = opendir($root)) {
				while (false !== ($dir = readdir($handle))) {
					if ($dir!= '.' AND $dir!= '..') {
						//удаление масива если нет картинки
						if (!is_dir($root.$dir)) {
							if (isset($photos[$dir])) unset($photos[$dir]);
						}
						//удаление картинки, если нет масива
						elseif (!array_key_exists($dir,$photos)) {
							delete_all($root.$dir.'/',true);
							//v1.3.17 - удаление превью
							if (isset($config['_imgs'][$module['table']])) {
								foreach ($config['_imgs'][$module['table']] as $k=>$v) {
									$path = ROOT_DIR.'_imgs/'.$v.'/'.$relative.$dir.'/';
									delete_all($path,true);
								}
							}
						}
					}
				}
				closedir($handle);
			}
		}
		$data = array(
			'type'=>$type,
			'key'=>$key,
			'name'=>$name,
			'photos'=>$photos,
			'fields'=>$fields,
			'module'=>$module['table'],
			'item'=>array(
				'id' => $get['id'],
				$key => $photos
			),
		);
//		return html_array('form/file_multi',$data);
		return html_array("form/$t",$data);
	}


	//обычная загрузка (HTML5)
	if ($t=='gallery_multi') {
//		$photos = (isset($post[$key]) && $post[$key]) ? unserialize(mysql_select('select * from gallery where id='.$post[$key].' limit 1','row')) : '';
		$photos = (isset($post[$key]) && $post[$key]) ? unserialize($post[$key]) : array();

$photos=array(
  array('id'=>1,'display'=>1/*,'file'=>'fn23.jpg'*/),
  array('id'=>1,'display'=>1/*,'file'=>'fn23.jpg'*/),
  array('id'=>6,'display'=>0/*,'file'=>'microphone.jpg'*/)
);

//print_r($photos);

		foreach($photos as $k=>$v) {
			$photo=mysql_select('select * from gallery where id='.$v['id'].' limit 1','row');
			$photos[$k]['file']=$photo['img'];
		}

		//загрузка файлов
		if ($get['u']=='edit' AND $photos) {
			mysql_fn('update',$module['table'],array('id'=>$get['id'],$key=>$photos ? serialize($photos) : ''));
		}

		$data = array(
			'type'=>$type,
			'key'=>$key,
			'name'=>$name,
			'photos'=>$photos,
			'fields'=>$fields,
			'module'=>$module['table'],
			'item'=>array(
				'id' => $get['id'],
				$key => $photos
			),
		);
		return html_array("form/$t",$data);
	}


















	//закгрузка многих файлов с записью в другую таблицу (HTML5)
	if ($t=='file_multi_db') {
		//error_handler(1,serialize($post),1,1);
		$photos = false;
		if ($get['id']!='new' OR @$_GET['save_as']>0) {
			$photos = mysql_select("SELECT * FROM `" . $key . "` WHERE `parent`=" . $post['id'] . " ORDER BY n", 'rows');
		}
		$path = 'files/'.$key.'/'; //папка от корня основной папки
		$root = ROOT_DIR.$path; //папка от корня сервера

		//загрузка файлов
		if ($get['u']=='edit') {
			$uploads = isset($_POST[$key]) ? stripslashes_smart($_POST[$key]) : array();
			$i = 1; //сортировка для mysql
			foreach ($uploads as $k=>$v) {
				$uploads[$k]['n'] = $i++;
			}
			if ($photos) foreach ($photos as $k=>$v) {
				//удаление отсутсвующих записей
				if (!isset($uploads[$v['n']])) {
					mysql_fn('delete',$key,$v['id']);
					//удаляем файлы
					delete_all($root.$v['id'].'/', true);
					unset($photos[$k]);
				}
				//обновление существующих
				else {
					$photos[$k]['name'] = $uploads[$v['n']]['name'];
					$photos[$k]['display'] = $uploads[$v['n']]['display'];
					$photos[$k]['n'] = $uploads[$v['n']]['n'];
					unset($uploads[$v['n']]);
					mysql_fn('update',$key,$photos[$k]);
				}
			}
			//error_handler(1,serialize($post),1,1);
			if ($uploads) foreach ($uploads as $n=>$val) {
				//загрузка нового файла
				if (@$val['temp']) {
					$temp = ROOT_DIR . 'files/temp/' . @$val['temp'] . '/';
					//если есть временная папка, то копируем картинку
					if ($handle = opendir($temp)) {
						$temp_file = ''; //название временного файла на сервере
						while (false !== ($f = readdir($handle))) {
							if (strlen($f) > 2 && is_file($temp . $f)) {
								$file = strtolower(trunslit($f));
								$temp_file = $temp . $f;
								break;
							}
						}
						//есть временный файл
						if ($temp_file) {
							$photos[$val['n']] = array(
								'parent'=>$get['id'],
								'n'=>$val['n'],
								'name'=>$val['name'],
								'display'=>$val['display'],
								'img'=>$file
							);
							$photos[$val['n']]['id'] = mysql_fn('insert',$key,$photos[$val['n']]);
							$path2 = $photos[$val['n']]['id'].'/img';
							//успешное копирование файла
							copy2 ($temp_file,$root.$path2.'/',$file,$param['sizes']);
						}
						//удаляем временную папку
						delete_all(ROOT_DIR . 'files/temp/' . $val['temp'].'/' , true);
					}
				}
			}
		}
		$photos2 = array();
		if ($photos) {
			foreach ($photos as $k => $v) {
				$photos2[$v['n']] = $v;
				$photos2[$v['n']]['file'] = $v['img'];
			}
			ksort($photos2);
		}
		$data = array(
			'type'=>$type,
			'key'=>$key,
			'name'=>$name,
			'photos'=>$photos2,
			//todo - доделать поля
			'fields'=>array('name'=>'input','display'=>'checkbox'),
			'module'=>$key,
			'item'=>array(
				'id' => $get['id'],
				$key => $photos
			),
		);
		return html_array('form/file_multi',$data);
	}
}

//верхнее меню модулей
/*
function head ($modules,$m='') {
	global $user;
	$top=$bottom='';
	$parent = $child = 0;
	$modules = array_merge_recursive(array('<span class="sprite home"></span>'=>'index'),$modules);
	foreach ($modules as $key => $value) {
		if (is_array($value)) {
			$i=0;
			if (in_array($m, $value)) {
				foreach ($value as $k=>$v) {
					if (access('admin module',$v)) {
						$parent++;
						$child++;
						$i++;
						if ($i==1) $top.='<a href="/admin.php?m='.$v.'" class="a">'.a18n($key).'</a>';
						$link = $m==$v ? ' class="a"' : '';
						$bottom.='<a href="/admin.php?m='.$v.'"'.$link.'>'.a18n($k).'</a>';
					}
				}
			}
			else {
				foreach ($value as $k=>$v) {
					if (access('admin module',$v)) {
						$parent++;
						$top.='<a href="/admin.php?m='.$v.'">'.a18n($key).'</a> ';
						break;
					}
				}
			}
		}
		elseif (access('admin module',$value)) {
			$parent++;
			$link = $m==$value ? ' class="a"' : '';
			$top.='<a href="/admin.php?m='.$value.'"'.$link.'>'.a18n($key).'</a>';
		}
	}
	if ($parent>1)
		return '<div class="menu_parent gradient">'.$top.'</div>'.(($bottom AND $child>1) ? '<div class="menu_child corner_bottom">'.$bottom.'<div class="clear"></div></div>' : '');
}
*/

/**
 * дерево вложенности
 * @param $m - название таблицы
 * @param $id - ИД принимающей ветки
 * @param $selected - ИД перемещаемой ветки
 * @param $insert - тип вставки (prev - вставка перед веткой $id, parent - вставка в ветку $id в конец)
 * @param array $filter - фильтр для дерева, например язык, для каждого свое дерево будет
 * @return bool|string
 * @version v1.2.103
 * v1.2.103 - InnoDB и трансакции
 * v1.4.10 - ошибки при многоязычности
 */
function nested_sets($m,$id,$selected,$insert,$filter=array()) {
	//v1.2.103 старт трансакции
	if (mysql_transaction('start')) {
		//перемещаемый
		$selected = mysql_select("
			SELECT *
			FROM " . $m . "
			WHERE id = '" . intval($selected) . "'
		", 'row');
		if ($selected==false) return 'ошибка родителя!';

		//если дерево многослойное и есть фильтр
		$where = '';
		if (isset($filter) && is_array($filter)) foreach ($filter as $k => $v) {
			$where .= " AND `" . $v[0] . "` = " . $selected[$v[0]];
		}
		log_add('tree.txt',$where);
		//принимающий
		if ($id) {
			$id = mysql_select("
				SELECT *
				FROM " . $m . "
				WHERE id = '" . intval($id) . "' $where
			", 'row');
			if ($id==false) return 'ошибка выборки родителя!';
		}
		//если ид равно нулю, так для parent
		else {
			if ($insert == 'prev') {
				return 'не указан предыдущий!';
			}
		}

		//количество переносимых записей * 2
		$dbl_count = $selected['right_key'] - $selected['left_key'] + 1;
		//имитация удаления узла - level делаем минусовым для отличия
		$query = "
			UPDATE " . $m . "
			SET level = (0 - level),
				left_key = (left_key - " . $selected['left_key'] . " + 1),
				right_key = (right_key - " . $selected['left_key'] . " + 1)
			WHERE left_key>=" . $selected['left_key'] . "
				AND right_key<=" . $selected['right_key'] .
			$where; //echo $query.'<br />';
		mysql_fn('query', $query);
		//пересортировка после псевдоудаления для всеx у кого level>0 (те что level<0 считаются удаленными)
		$query = "
	        UPDATE " . $m . "
			SET left_key = CASE WHEN left_key > " . $selected['left_key'] . "
								THEN left_key - " . $dbl_count . "
								ELSE left_key END,
				right_key = right_key - " . $dbl_count . "
			WHERE right_key > " . $selected['right_key'] . "
				AND level > 0" .
			$where; //echo $query.'<br />';
		mysql_fn('query', $query);

		//обновляем принимающий, т.к. была произведена пересортировка шагом ранее
		if (is_array($id))
			$id = mysql_select("
				SELECT *
				FROM " . $m . "
				WHERE id = '" . $id['id'] . "'
			", 'row');
		else
			$id = array(
				'id' => 0,
				'right_key' => intval(mysql_select("SELECT IFNULL(MAX(right_key),0) FROM " . $m . " WHERE " . $m . ".level>0 " . $where, 'string')) + 1,
				'level' => 0
			);
		//вставка в конец узла ======================
		if ($insert == 'parent') {
			//подготовка для создания создания нового узла
			//пересортировка - освобождение места для нового узла
			if ($id['id'] > 0) {
				$query = "
					UPDATE " . $m . "
					SET right_key = right_key + " . $dbl_count . ",
						left_key = CASE WHEN left_key > " . $id['right_key'] . "
										THEN left_key + " . $dbl_count . "
										ELSE left_key END
					WHERE right_key >= " . $id['right_key'] . "
						AND level > 0" .
					$where; //echo $query.'<br />';
				mysql_fn('query', $query);
			}
			//имитация создания нового узла
			$shift = $id['right_key'] - 1;
			$level = $id['level'] + 1 - $selected['level'];
			$query = "
				UPDATE " . $m . "
				SET level = (0 - level + " . $level . "),
					left_key = (left_key + " . $shift . "),
					right_key = (right_key + " . $shift . ")
				WHERE level < 0" .
				$where; //echo $query.'<br />';
			mysql_fn('query', $query);
			//обновление родителя
			$query = "
				UPDATE " . $m . "
				SET parent = " . $id['id'] . "
				WHERE id = " . $selected['id'] .
				$where; //echo $query.'<br />';
			mysql_fn('query', $query);
			//вставка перед узлом ======================
		} elseif ($insert == 'prev') {
			//подготовка для создания создания нового узла
			//пересортировка - освобождение места для нового узла
			mysql_fn('query', "
				UPDATE " . $m . "
				SET right_key = right_key + " . $dbl_count . ",
					left_key = CASE WHEN left_key >= " . $id['left_key'] . "
								THEN left_key + " . $dbl_count . "
								ELSE left_key END
				WHERE right_key > " . $id['left_key'] . "
					AND level > 0" .
				$where
			);
			//имитация создания нового узла
			$shift = $id['left_key'] - 1;
			$level = $id['level'] - $selected['level'];
			mysql_fn('query', "
				UPDATE " . $m . "
				SET level = (0 - level + " . $level . "),
					left_key = (left_key + " . $shift . "),
					right_key = (right_key + " . $shift . ")
				WHERE level < 0" .
				$where
			);
			//обновление родителя
			mysql_fn('query', "
				UPDATE " . $m . "
				SET parent = " . $id['parent'] . "
				WHERE id = " . $selected['id'] .
				$where
			);
		}
		//проверка
		$where = '';
		if (isset($filter) && is_array($filter)) foreach ($filter as $k => $v) {
			if (isset($id[$v[0]])) $where .= " AND t1." . $v[0] . " = " . $selected[$v[0]] . " AND t2." . $v[0] . " = " . $selected[$v[0]] . "";
		}
		$num_rows = mysql_select("
			SELECT t1.*,t2.*
			FROM " . $m . " AS t1, " . $m . " AS t2
			WHERE (t1.left_key = t2.left_key OR t1.right_key = t2.right_key)
				AND t1.id!=t2.id " .
			$where . "
		", 'num_rows');
		if ($num_rows > 0) {
			//v1.2.103 откат трансакции
			mysql_transaction('rollback');
			//log_add('nested_sets',array($m,$id,$selected,$insert));
			return 'error nested sets!';
		}
		else {
			//v1.2.103 завершение трансакции
			mysql_transaction('commit');
		}
		return true;
	}
	return 'transaction already started!';
}

/**
 * добавляем в форму поля и вкладки
 * version v1.2.3
 * v1.2.3 - добавлена
 */
function multilingual() {
	global $config,$tabs,$form,$get;
	if ($config['multilingual']) {
		// Pages: language versions are edited via the "Translations" tab (content_i18n), not per-lang tabs
		if ($get['m'] === 'pages') {
			return;
		}
		if (isset($config['lang_fields'][$get['m']])) {
			foreach ($config['languages'] as $lang) if ($lang['id']!=1) {
				//вкладки
				$tabs['lang' . $lang['id']] = $lang['name'];
				//поля
				foreach ($config['lang_fields'][$get['m']] as $k=>$v) {
					//добавляем ИД к имени поля
					$v[1].= $lang['id'];
					$form['lang' . $lang['id']][] = $v;
				}
			}
		}
	}
}