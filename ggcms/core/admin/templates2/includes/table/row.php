<?php
// Legacy list URL (avoid appending id/u to current query — that produced …&id=7&id=7 without u=form).
$__list_q = $_GET;
unset($__list_q['id'], $__list_q['u'], $__list_q['inline'], $__list_q['ftab'], $__list_q['i18n_lang_id'], $__list_q['i18n_clear']);
$__qs = http_build_query($__list_q);
$url = '/admin.php?' . ($__qs !== '' ? $__qs . '&' : '');
$content = '';
if (!isset($q['table']['_edit'])) $q['table'] = array_merge(array('_edit'=>true),$q['table']);
elseif ($q['table']['_edit']==false) unset($q['table']['_edit']);
if (!isset($q['table']['_delete'])) $q['table']['_delete'] = true;
elseif ($q['table']['_delete']==false) unset($q['table']['_delete']);

foreach ($q['list'] as $row) { ?>
<tr
	<?php
	// Tree
	if (array_key_exists('_tree',$q['table'])) {
		echo 'data-parent="'.$row['parent'].'" data-level="'.$row['level'].'"';
	}
	// Sort (not ready)
	elseif (array_key_exists('_sorting',$q['table'])) {
		echo 'data-sorting="'.$row[$q['table']['_sorting']].'" data-id="'.$row['id'].'"';
	}
	// New record
	if (@$_GET['u']=='edit' AND @$_GET['id']==$row['id']) echo 'class="is_open"';
	?> data-id="<?= $row['id'] ?>">
<?php
foreach ($q['table'] as $k=>$v) {
	if ($v && !is_array($v)) {
		preg_match_all('/{(.*?)}/',$v,$matches,PREG_PATTERN_ORDER);
		foreach($matches[1] as $key=>$val) $matches[1][$key] = isset($row[$val]) ? $row[$val] : '';
		$v = str_replace($matches[0],$matches[1],$v);
	}
	//v1.2.130 - чекбоксы для админки
	if ($k=='_check')		$content.= '<td class="table_checkbox"><input type="checkbox" name="_check" value="'.$row['id'].'"/></td>';
	elseif ($k=='_edit')	{
		$edit_href = function_exists('admin_edit_form_url') ? admin_edit_form_url($q['module'], $row['id']) : '';
		if ($edit_href === '') {
			$edit_href = $url . 'u=form&id=' . (int)$row['id'] . '&inline=1';
		}
		$content .= '<td class="text-nowrap"><a href="' . htmlspecialchars($edit_href, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm btn-outline-secondary open" data-toggle="tooltip" title="'.a18n('edit').'"><i data-feather="edit-2" class="mr-0"></i></a></td>';
	}
	elseif ($k=='_view') {
		$content.= '<td class="text-nowrap"><a target="_blank" href="'.get_url($v,$row).'" class="btn btn-sm btn-outline-secondary" data-toggle="tooltip" title="'.a18n('display').'"><i data-feather="search" class="mr-0"></i></a></td>';
	}
	elseif ($k=='_tree')	{
		$content.= '<td class="level">';
		for ($n=1; $n<=$row['level']; $n++) {
			$content.='<i data-feather="chevron-right"></i>';
		}
		$content.='</td>';
	}
	elseif ($k=='_sorting')	$content.= '<td><span class="sprite sorting"></span></td>';
	elseif ($k=='_delete')	{
		$content .= '<td class="text-nowrap"><a class="btn btn-sm btn-outline-danger delete" href="#" title="'.a18n('delete').'" data-toggle="tooltip"><i data-feather="x-circle" class="mr-0"></i></a></td>';
	}
	elseif ($k=='id')		$content.= '<td align="right">'.$row[$k].'</td>';
	elseif (is_array($v))	{
		if (substr($k,-1)==':') {
			$k = trim($k,':');
			//$content.= '<td><select name="'.$k.'">'.select($row[$k],$v).'</select></td>';
			$str = '';
			if (isset($row[$k]) AND isset($v[$row[$k]])) {
				$str = is_array($v[$row[$k]]) ? $v[$row[$k]]['name'] : $v[$row[$k]];
			}
			$content.= '<td class="select" data-id="'.$row[$k].'" data-name="'.$k.'">'.$str.'</td>';
		}
		else {
			$str = '';
			if (isset($row[$k]) AND isset($v[$row[$k]])) {
				$str = is_array($v[$row[$k]]) ? $v[$row[$k]]['name'] : $v[$row[$k]];
			}
			$content.= '<td>'.$str.'</td>';
		}
	}
	elseif ($v=='date')		$content.= '<td data-name="'.$k.'" class="post">'.$row[$k].'</td>';
	elseif ($v=='smart')		$content.= '<td>'.date2($row[$k],'smart').'</td>';
	elseif ($v=='boolean' OR $v=='display') {
		$content .= '<td data-name="' . $k . '">
			<div class="custom-control custom-switch custom-checkbox-warning" title="'.a18n($k).'" data-toggle="tooltip">
				<input type="checkbox" class="js_boolean custom-control-input" id="'.$k.$row['id'].'" ' .($row[$k] == 1 ? 'checked' : '0') . '>
				<label class="custom-control-label" for="'.$k.$row['id'].'"></label>
			</div>
		</td>';
	}
	elseif ($v=='right')	$content.= '<td data-name="'.$k.'" align="right" class="post">'.$row[$k].'</td>';
	elseif ($v=='text')		{
		$content.= '<td data-name="'.$k.'"><span>'.$row[$k].'</span></td>';
	}
	elseif ($v=='img')		{
		$img =  get_img($q['module'],$row,$k,'');
		$thumb = ($q['module'] === 'guides' || $q['module'] === 'games') ? $img : preg_replace('#/([^/]+\.[^/]+)$#','/a-$1',$img);
//		$content .= '<td align="center" data-name="' . $k . '">' . ($row[$k] ? '<a class="image-popup" href="' . $img . '"><img class="img" src="/_imgs/100x100' . $img . '" /></a>' : '') . '</td>';
		$content .= '<td align="center" data-name="' . $k . '">' . ($row[$k] ? '<a class="image-popup" href="' . $img . '"><img class="img" src="' . $thumb . '" alt="" /></a>' : '') . '</td>';

	}
	elseif ($v=='gallery')		{
		$row1=array();
		if(isset($row[$k])&&$row[$k]) $row1=mysql_select('select * from gallery where id='.$row[$k].' limit 1','row');

		$img=get_img('gallery',array('id'=>$row[$k],$k=>$row1['img']),$k,'');
		$img=str_replace("/$k/",'/img/',$img);
		$content .= '<td align="center" data-name="' . $k . '">' . ($row[$k] ? '<a class="image-popup" href="' . $img . '"><img class="img" src="' . preg_replace('#/([^/]+\.[^/]+)$#','/a-$1',$img) . '" /></a>' : '') . '</td>';
	}
	elseif ($v=='imgs')		{
		$imgs =  get_imgs($q['module'],$row,$k);
		$img = '';
		if ($imgs) foreach ($imgs as $i)  {
			$img = $i['_'];
		}
		$content .= '<td align="center" data-name="' . $k . '">' . ($row[$k] ? '<a class="image-popup" href="' . $img . '"><img class="img" src="/_imgs/100x100' . $img . '" /></a>' : '') . '</td>';
	}
	elseif ($v=='')			$content.= '<td data-name="'.$k.'" class="post">'.(isset($row[$k]) ? $row[$k] : '').'</td>';
	elseif (substr($v,0,2)=='::') {
		$function = substr($v,2);
		//v1.2.102 - добавлен второй аргумент в 'field' => '::function',
		if (function_exists($function)) $content.= $function($row,$k);
		else $content.= '<td>'.$function.'</td>';
	}
	else
		$content.= '<td>'.$v.'</td>';
}
//
echo $content;
$content = '';
?>
</tr>
<?php } ?>
