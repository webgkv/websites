<?php
//list
if (@$abc['news']) {
	if ($abc['news']['list']) {
?>
    <?=html_render('common/breadcrumb',$abc['breadcrumb'])?>
    <section>
      <div class='container'>
        <h1><?=mb_strtoupper($abc['breadcrumb'][count($abc['breadcrumb'])-1]['name'])?></h1>
<?php/*echo html_render('news/tags', $abc['tags']);*/?>
      </div>
    </section>
<?php
		echo html_render('news/list', $abc['news']['list']);
	}
	else echo "<div class='class-59'><div class='content'>".i18n('common|msg_no_results')."</div></div>";
}
//item
else {
	echo html_render('news/text',$abc['page']);
}