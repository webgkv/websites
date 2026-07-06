<?php
$content=$bottom='';
$parent = $child = 0;
//$modules2 = array_merge_recursive(array('<span class="sprite home"></span>'=>'index'),$q);
$modules2 = $q;
$m = @$_GET['m'];
if ($m=='') $m='index';
foreach ($modules2 as $key => $value) {
	$value['name'] = isset($value['name']) ? $value['name'] : $value['module'];
	$value['icon'] = @$value['icon'] ? $value['icon'] : 'bar-chart-2';
	if (is_array($value['module'])) {
		$i=0;
		$bottom2 = '';
		$first_url = '';
		$parent_active = '';
		foreach ($value['module'] as $k=>$v) {
			if (access('admin module',$v['module'])) {
				$v['name'] = isset($v['name']) ? $v['name'] : $v['module'];
				$sub_href = '/admin.php?m=' . $v['module'];
				if (!empty($v['href'])) {
					$sub_href = (string)$v['href'];
				} elseif (!empty($v['hash'])) {
					$sub_href = '/admin.php?m=' . $v['module'] . '#' . ltrim((string)$v['hash'], '#');
				}
				$sub_href_esc = htmlspecialchars($sub_href, ENT_QUOTES, 'UTF-8');
				$parent++;
				$child++;
				$i++;
				$link = $m==$v['module'] ? ' class="a"' : '';
				$bottom2.='<li><a '.($v['module']==$m?'class="active"':'').' href="'.$sub_href_esc.'">'.a18n($v['name']).'</a></li>
							';
				// first sub-item URL for parent link
				if ($i==1) {
					$first_url = $v['module'];
				}
				// active sub-section
				if ($m==$v['module']) {
					$parent_active = 1;
				}
			}

		}
		// if at least one sub-section exists
		if ($first_url) {
			//$content.='<a href="/admin.php?m='.$first_url.'" >'.a18n($value['name']).'</a>';
			$content.='
				<li '.($parent_active?'class="open"':'').'>
					<a href="/admin.php?m='.$first_url.'">
						<i class="nav-link-icon" data-feather="'.$value['icon'].'"></i>
						<span>'.a18n($value['name']).'</span>
					</a>
					<ul>'.$bottom2.'</ul>
				</li>';
		}
	}
	elseif (access('admin module',$value['module'])) {
		$parent++;
		$link = $m==$value ? ' class="a"' : '';
		$content.='<li>
			<a '.($value['module']==$m?'class="active"':'').' href="/admin.php?m='.$value['module'].'">
				<i class="nav-link-icon" data-feather="'.$value['icon'].'"></i>
				<span>'.a18n($value['name']).'</span>
			</a>
		</li>';
	}
}

echo $content;
