<div class="seo-optimization col-xl-12"><a href="#"><?=a18n('seo_optimization')?></a></div>
<div class="col-xl-12" style="display:none">
	<div class="form-row">
		<?php
		echo form('checkbox td3','seo',array('name'=>a18n('seo_generate'),'value'=>@$q['value']['seo']));
		foreach (explode(' ',$q['key']) as $k) {
			switch ($k) {
				case 'url':			echo form('input td9','url',array('name'=>a18n('url'),'value'=>@$q['value']['url'])); break;
				case 'title':		echo form('input td12','title',array('name'=>a18n('title'),'value'=>@$q['value']['title'])); break;
				case 'description':	echo form('input td12','description',array('name'=>a18n('description'),'value'=>@$q['value']['description'])); break;
				case 'noindex':		echo form('checkbox td12 line','noindex',array('name'=>a18n('noindex'),'value'=>@$q['value']['noindex'])); break;
			}
		}?>
	</div>
</div>
