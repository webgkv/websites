<?php

//$abc['gallery']=mysql_select('select * from gallery','rows_id');

// AMP URL handling
if (@$_GET['view']=='amp') {
	$config['amp'] = 1;
}

if ($u[3]) {
	// 404 if $u[3] present
	$error++;
} elseif ($u[2]) {
	if ($news = mysql_select("
		SELECT *
		FROM news
		WHERE date<='".date('Y-m-d H:i:s')."' and url$langid = '".mysql_res($u[2])."' AND display = 1
	",'row')) {

		$abc['page'] = array_merge($abc['page'],$news);
		$abc['news_single'] = true;

		// Breadcrumb
		$abc['breadcrumb'][] = array(
			'name'=>$abc['page']["name$langid"],
			'url'=>get_url('news').$abc['gcats'][$news['category']]["url$langid"].'/'.$abc['page']["url$langid"].'/'
		);

		foreach($abc['languages'] as $i=>$v) {
			$abc['links'][$abc['languages'][$i]['url']][]=$abc['gcats'][$news['category']]['url'.($i>1?$i:'')];
			$abc['links'][$abc['languages'][$i]['url']][]=$abc['page']['url'.($i>1?$i:'')];
		}


		$abc['page']['text']=$abc['page']["text$langid"];

                $text=template_img('news',$abc['page']);

                if(preg_match('#^(.*?<p>.*?</p>.*?<p>.*?</p>)(.*)$#ius',$text,$m)) {
                  $abc['page']['text1']=$m[1];$abc['page']['text2']=$m[2];
                } else {
                  $abc['page']['text1']=$text;$abc['page']['text2']='';
                }

	} else $error++;

} else {
	// Record list
	$abc['news'] = mysql_data(
		"SELECT * FROM news WHERE date<='".date('Y-m-d H:i:s')."' AND url$langid!='' AND display = 1 ORDER BY date DESC",
		false,
		20,
		@$_GET['n']
	);
//	$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date desc LIMIT 3",'rows');
//	$abc['tags'] = mysql_select("select * from news_category order by id asc",'rows_id');

//	$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_list'   and url$langid!=''",'rows');

//        if(!isset($_COOKIE['popup'])) {
//          $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_list" and popup_places.display=1 order by rand() limit 1','string');
//          if(!$abc['popup']) $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1','string');
//        }
}