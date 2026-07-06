<?php

$array_count	= $q['array_count'];
$count_max = 7; //максимальное количество страниц пагинатора для отображения
$n = $q['n']; //номер старницы пагинатора
if ($n==0) $n=1;
//$count - фактическое количество страниц пагинатора
//$q['limit'] - выводимое количество записей на одной странице
//$q['num_rows'] - количество записей итого
$list = array(); //массив страниц пагинатора

//пагинатор показываем только если есть больше 1 страницы
if ($q['limit']<$q['num_rows'] AND $q['limit']>0) {
	//фактическое количество страниц пагинатора
	$count = ceil($q['num_rows']/$q['limit']);
	//если фактическое количество страниц меньше максимального то показываем все
	if ($count <= $count_max) {
		for ($i = 1; $i <= $count; $i++) $list[] = array($i, $i);
	}
	//если страниц пагинатора больше $count_max, так как пагинатор расчитан только на $count_max ссылок
	else {
		for ($i=1; $i<$n; $i++) {
			if (
				$i==1
				OR //меньше 10
				($i+5>$n)
				OR //меньше 100
				($i+100>$n AND fmod($i,10)==0)
				OR //меньше 1000
				($i+1000>$n AND fmod($i,100)==0)
			) {
				$ii = $i*$count-$count;
				$list[] = array($i,$i);
			}
		}
		//больше текущей страницы
		for ($i=$n; $i<=$count; $i++) {
			if (
				$i==$count
				OR //больше 10
				($i-5<$n)
				OR //больше 100
				($i-100<$n AND fmod($i,10)==0)
				OR //больше 1000
				($i-1000<$n AND fmod($i,100)==0)
			) {
				$ii = $i*$count-$count;
				$list[] = array($i,$i);
			}
		}
	}
}
else {
	$list[] = array(1,1);
}
?>
<div class="pagination_count">
	<span></span>
	<div><select onchange="top.location='/admin.php?<?=build_query('c,u,id')?>&c='+this.value;"><?=select(@$_GET['c'],$array_count)?></select></div>
</div>
<div class="pagination_pages">
	<span><?=a18n('pagination_page')?></span>
	<ul>
		<?php
		//список страниц
		foreach ($list as $k=>$v) {
			$name = $v[1]==0 ? '..' : $v[0];
			$link = pagination_link ('n',$v[0],1);
			//текущая
			if ($v[0]==$n) echo '<li><a class="active" href="'.$link.'">'.$name.'</a></li>';
			//остальные
			else echo '<li><a href="'.$link.'">'.$name.'</a></li>';
		}
		?>
		</li>
	</ul>
</div>
<div class="clear"></div>
