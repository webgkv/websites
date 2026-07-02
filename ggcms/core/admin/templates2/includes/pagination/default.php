<?php

$array_count	= $q['array_count'];
$count_max = 7; // max pagination pages to show
$n = $q['n']; // current page
$page_key = isset($q['page_key']) && (string)$q['page_key'] !== '' ? (string)$q['page_key'] : 'n';
if ($n==0) $n=1;
// $count = total pages; $q['limit'] = per page; $q['num_rows'] = total rows
$list = array();

if ($q['limit']<$q['num_rows'] AND $q['limit']>0) {
	$count = ceil($q['num_rows']/$q['limit']);
	if ($count <= $count_max) {
		for ($i = 1; $i <= $count; $i++) $list[] = array($i, $i);
	}
	else {
		if ($n < ($e = $count_max - 2)) {
			for ($i = 1; $i <= $e; $i++) $list[] = array($i, $i);
			$list[] = array(ceil(($count + $e) / 2), 0);
			$list[] = array($count, $count);
		}
		elseif ($n > ($s = $count - $count_max + 2 + 1)) {
			$list[] = array(1, 1);
			$list[] = array(ceil(($s + 1) / 2), 0);
			for ($i = $s; $i <= $count; $i++) $list[] = array($i, $i);
		}
		else {
			$s = $n - ceil(($count_max - 4 - 1)/2);
			$e = $n + floor(($count_max - 4 - 1)/2);
			$list[] = array(1,1);
			$list[] = array((ceil(($s + 1)/2)),0);
			for ($i = $s; $i<=$e; $i++) $list[] = array ($i,$i);
			$list[] = array(ceil(($count + $e)/2),0);
			$list[] = array($count,$count);
		}
	}
}
else {
	$list[] = array(1,1);
}
/*
?>

<div class="pagination_count">
	<span></span>
	<div><select onchange="top.location='/admin.php?<?=build_query('c,u,id')?>&c='+this.value;"><?=select(@$_GET['c'],$array_count)?></select></div>
</div>
*/?>
<nav class="pagination-bottom" aria-label="Pagination"><ul class="pagination pagination-sm pagination-rounded mb-0">
		<?php
		foreach ($list as $k=>$v) {
			$name = $v[1]==0 ? '…' : $v[0];
			$link = pagination_link ($page_key,$v[0],1);
			if ($v[0]==$n) echo '<li class="page-item active"><span class="page-link">'.$name.'</span></li>';
			else echo '<li class="page-item"><a class="page-link" href="'.$link.'">'.$name.'</a></li>';
		}
		?>
</ul>
</nav>

