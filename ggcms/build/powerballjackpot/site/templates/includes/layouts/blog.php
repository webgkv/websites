<?php
// List view
if (@$abc['blog']) {
	if ($abc['blog']['list']) {
?>
    <?=html_render('common/breadcrumb',$abc['breadcrumb'])?>
    <section>
      <div class='container'>
        <h1><?=mb_strtoupper($abc['breadcrumb'][count($abc['breadcrumb'])-1]['name'])?></h1>
      </div>
    </section>
<?php
		echo html_render('blog/list', $abc['blog']['list']);
	}
	else echo "<div class='class-59'><div class='content'>".i18n('common|msg_no_results')."</div></div>";
}
// Item view
else {
	echo html_render('blog/text',$abc['page']);
}
