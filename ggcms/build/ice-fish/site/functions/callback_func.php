<?php

// v.1.2.133 — Callbacks for html_render templates (/templates/includes/).

function _news_text ($q) {
	$q['_date'] = date2($q['date'],'%d.%m.%y');
	$q['_text'] = template_img('blog', $q);
	$q['_text'] = template_video($q['_text'], $q['video']);
	return $q;
}
function _blog_text ($q) {
	$q['_date'] = date2($q['date'],'%d.%m.%y');
	$q['_text'] = template_img('blog', $q);
	$q['_text'] = template_video($q['_text'], $q['video']);
	return $q;
}
