<?php

$count_max = 5; // max pagination pages to show
$n = $q['n']; // current page
if ($n==0) $n=1;
// $count = total pages; $q['limit'] = per page; $q['num_rows'] = total rows
$list = array();

// Show pagination only when more than 1 page
if ($q['limit']<$q['num_rows']) {
	$count = ceil($q['num_rows']/$q['limit']);
	if ($count <= $count_max) {
		for ($i = 1; $i <= $count; $i++) $list[] = array($i, $i);
	}
	else {
		// Active at start [1][2][3][4][5][..][100]
		if ($n < ($e = $count_max - 2)) {
			for ($i = 1; $i <= $e; $i++) $list[] = array($i, $i);
			$list[] = array(ceil(($count + $e) / 2), 0);
			$list[] = array($count, $count);
		}
		// Active at end [1][..][96][97][98][99][100]
		elseif ($n > ($s = $count - $count_max + 2 + 1)) {
			$list[] = array(1, 1);
			$list[] = array(ceil(($s + 1) / 2), 0);
			for ($i = $s; $i <= $count; $i++) $list[] = array($i, $i);
		}
		// Active in middle [1][..][49][50][51][..][100]
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
	?>

          <div class='pagination'>
			<?php
			// Previous
			if ($n<=1) {
				?>
            <div><img src='/assets/images/chevron-left.svg' alt='previous page' title='previous page'></div>

				<?php
			}
			else {
				?>
            <a href='<?= pagination_link2('n', 1, 1) ?>'><img src='/assets/images/chevron-left.svg' alt='previous page' title='previous page'></a>
				<?php
			}
			// Page list
			foreach ($list as $k=>$v) {
				$name = $v[1]==0 ? '...' : $v[0];
				if ($v[0]==$n) echo "<div>$name</div>";
				else echo "<a href='".pagination_link2('n',$v[0],1)."'>$name</a>";
			}
			?>
			<?php
			// Next
			if ($n>=$count) {
				?>
            <div><img src='/assets/images/chevron-right.svg' alt='next page' title='next page'></div>
				<?php
			}
			else {
				?>
            <a href='<?=pagination_link2('n',$n+1,1)?>'><img src='/assets/images/chevron-right.svg' alt='next page' title='next page'></a>
				<?php
			}
			?>
          </div>
<?php } ?>
