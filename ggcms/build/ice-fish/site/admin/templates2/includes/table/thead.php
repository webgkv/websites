<?php
$__qs = http_build_query($_GET);
$url = '/admin.php?' . ($__qs !== '' ? $__qs . '&' : '');
?>

<thead>
<tr data-id="new" class="head">
<?php
if (!isset($q['table']['_edit'])) $q['table'] = array_merge(array('_edit'=>true),$q['table']);
elseif ($q['table']['_edit']==false) unset($q['table']['_edit']);
if (!isset($q['table']['_delete'])) $q['table']['_delete'] = true;
elseif ($q['table']['_delete']==false) unset($q['table']['_delete']);


$content = '';
foreach ($q['table'] as $k=>$v) {
//v1.2.130 - чекбоксы для админки
	if ($k == '_check') $content .= '<th class="table_checkbox" style="text-align:center; padding:0px"><input type="checkbox" name="_check" /></th>';
	elseif ($k == '_tree') $content .= '<th class="level"><i data-feather="align-right" title="дерево вложенности"></i></th>';
	elseif ($k == '_sorting') $content .= '<th><span class="sprite sorting" title="сортировка"></span></th>';
	elseif ($k == '_edit') {
		if ($v === 'edit') {
			$content .= '<th style="padding:0; text-align:center"></th>';
		}
		else {
			$content .= '<th class="text-nowrap" style="text-align:center"><a class="btn btn-sm btn-outline-primary open" href="' . $url . 'id=new" title="'.a18n('add').'" data-toggle="tooltip"><i data-feather="plus-circle" class="mr-0"></i></a></th>';
		}
	}
	elseif ($k == '_view') {
		$content .= '<th width="20px"></th>';
	}
	elseif ($k == '_delete') $content .= '<th class="text-nowrap" style="width:1%"></th>';
	elseif ($k == 'display') $content .= '<th></th>';
//	elseif ($v == 'boolean') $content .= '<th></th>';
	elseif ($v == 'img')     $content .= '<th></th>';
	elseif ($v == 'gallery') $content .= '<th></th>';
	else {
		global $get;

		// $fieldset[$k] = key when not set
		$kTrim = trim($k, ':');
		$content .= '<th' . ($kTrim === 'id' ? ' style="text-align:right"' : '') . '>';
		// Hidden select for quick edit
		if (is_array($v) AND substr($k, -1) == ':') {
			$content .= '<select name="' . $k . '">' . select('', $v) . '</select>';
		}
		$k = $kTrim; // strip colon from select key
		if (isset($q['sort_array']) && array_key_exists($k, $q['sort_array'])) {
			if ($q['order'] == $k) {
				if ($get['s']) $s = ($get['s'] == 'desc') ? 'asc' : 'desc';
				else $s = $q['sort_array'][$k];
				$a = $s == 'asc' ? ' desc' : ' asc';
			}
			else {
				$s = $q['sort_array'][$k];
				$a = ' none ' . $s;
			}
			$content.= '<a class="sort' . ($q['order'] == $k ? ' active' : '') . '" href="' . $url . 'o=' . $k . '&s=' . $s . '">' . a18n($k);
			if ($a==' asc') {
				$content.= '<i data-feather="chevron-down"></i>';
			}
			elseif ($a==' desc') {
				$content.= '<i data-feather="chevron-up"></i>';
			}
			else {
				$content.= '<i data-feather="bar-chart-2"></i>';
			}
			$content .= '</a>';
		}
		else $content .= a18n($k);
		$content .= '</th>';
	}
}
//
echo $content;
$content = '';
?>
</tr>
</thead>
