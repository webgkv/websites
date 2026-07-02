<div class="field <?=$q['class']?>">
	<label<?=$q['title']?' title="'.$q['title'].'"':''?>>
		<span><?=$q['name']?></span>
		<?php if ($q['help']) {?>
			<a href="#" class="sprite question" title="<?=$q['help']?>"></a>
		<?php } ?>
	</label>
	<div <?=$q['attr']?>>
		<?php
		$value = isset($q['value']) ? $q['value'] : $user['id'];
		if ($value AND $usr = mysql_select("SELECT * FROM users WHERE id=".intval($value),'row')) {
			$name = $usr['email'];
			if ($name=='') $name = $usr['phone'];
			if ($name=='') $name = 'id:'.$usr['id'];
			?>
			<a href="?m=users&id=<?=$usr['id']?>"><?=$name?></a>
			<?php
		}
		else {
			?>не указан<?php
		}
		?>
		<input name="<?=$q['key']?>" type="hidden" value="<?=$value?>" />
	</div>
</div>