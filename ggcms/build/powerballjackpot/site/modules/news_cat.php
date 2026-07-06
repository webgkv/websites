<?php

$abc['gallery']=mysql_select('select * from gallery','rows_id');

// AMP URL handling
if (@$_GET['view']=='amp') {
	$config['amp'] = 1;
}

if ($u[4]) {
	// 404 if $u[4] present
	$error++;
} elseif ($u[3]) {
	//subcategory/tag	/news/tenis/tenis-tag/
	$category='';foreach($abc['gcats'] as $ucat) if($ucat["url$langid"]==$u[2]) $category=$ucat['id'];
	if($category) {

		$abc['tags'] = mysql_select("select * from news_tags where category=$category order by id asc",'rows_id');

		$tag='';foreach($abc['tags'] as $utag) if($utag["url$langid"]==$u[3]) $tag=$utag['id'];
		if($tag) {
			$abc['news'] = mysql_data(
				"SELECT * FROM news WHERE date<='".date('Y-m-d H:i:s')."' and category=".$category." and (tag1=$tag or tag2=$tag or tag3=$tag or tag4=$tag) and display = 1 ORDER BY date DESC",
				false,
				3,
				@$_GET['n']
			);
			$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date desc LIMIT 3",'rows');


			$abc['breadcrumb'][] = array(
				'name'=>$abc['gcats'][$category]["name$langid"],
				'url'=>get_url('news').$abc['gcats'][$category]["url$langid"].'/'
			);
			$abc['breadcrumb'][] = array(
				'name'=>$abc['tags'][$tag]["name$langid"],
				'url'=>get_url('news').$abc['gcats'][$category]["url$langid"].'/'.$abc['tags'][$tag]["url$langid"].'/'
			);

			foreach($abc['languages'] as $i=>$v) {
				$abc['links'][$abc['languages'][$i]['url']][]=$abc['gcats'][$category]['url'.($i>1?$i:'')];
				$abc['links'][$abc['languages'][$i]['url']][]=$abc['tags'][$tag]['url'.($i>1?$i:'')];
			}


//???			$abc['ncats'][$category]=$abc['tags'][$tag]["name$langid"];	//hint for labels	//???
//???			$abc['page'] = array_merge($abc['page'],$abc['tags'][$tag]);	//seo & text		//???

//		        $abc['ads1'] = mysql_select("SELECT * FROM ads where display=1 and page='news_list'   ORDER BY RAND()",'rows');
			$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_list'   and url$langid!=''",'rows');

                        if(!isset($_COOKIE['popup'])) {
                          $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_list" and popup_places.display=1 order by rand() limit 1','string');
                          if(!$abc['popup']) $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1','string');
                        }

		} else {

			//page		/news/fotbal/we-re-not-going-for-a-picnic/
			if ($news = mysql_select("
				SELECT *
				FROM news
				WHERE date<='".date('Y-m-d H:i:s')."' AND category='$category' and url$langid = '".mysql_res($u[3])."' AND display = 1
			",'row')) {

				$abc['page'] = array_merge($abc['page'],$news);
				$abc['news_single'] = true;

				// Breadcrumb
				$abc['breadcrumb'][] = array(
					'name'=>$abc['gcats'][$news['category']]["name$langid"],
					'url'=>get_url('news').$abc['gcats'][$news['category']]["url$langid"].'/'
				);
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
//			        $abc['page']['showpariuri']=$showpariuri;

//				$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_item'   and url$langid!=''",'rows');
//				$abc['ads2'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_item_2' and url$langid!=''",'rows');

//				$limit = mysql_select('select count from partners where page="news (item) sportsbooks" limit 1','string');if($limit==0) $limit=5;
//				$abc['sportsbooks'] = mysql_select("select id,img,website,name$langid name,name_2$langid name_2 from `sportsbooks` where display=1 order by top desc,position desc limit $limit",'rows');

//				$abc['teams'] = mysql_select('SELECT * FROM `teams`','rows_id');
////				$abc['ticket']=mysql_select("select id,date,img,name$langid name from `ticketoftheday` where date<='".date('Y-m-d H:i:s')."' and display=1 order by date desc limit 1",'row');
//				$abc['ticket']=mysql_select("select * from `ticketoftheday` where date<='".date('Y-m-d H:i:s')."' and display=1 order by date desc limit 1",'row');

//                                if(!isset($_COOKIE['popup'])) {
//                                  $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_item" and popup_places.display=1 order by rand() limit 1','string');
//                                  if(!$abc['popup']) $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1','string');
//                                }
//				$abc['page']['newslist'] = mysql_select("SELECT id,date,category,img,img_alt,img_title,author,url$langid url,name$langid name,name_2$langid name_2,gimg FROM news WHERE date<='".date('Y-m-d H:i:s')."' AND category=$category and id!=".$news['id']." and display=1 ORDER BY top desc,date DESC limit 6",'rows');

			} else $error++;
///
		}
	} else $error++;


} elseif ($u[2]) {

	$category='';foreach($abc['gcats'] as $ucat) if($ucat["url$langid"]==$u[2]) $category=$ucat['id'];
	if($category) {

		//category	/news/tenis/
		// Record list
		$abc['news'] = mysql_data(
			"SELECT * FROM news WHERE date<='".date('Y-m-d H:i:s')."' AND category=".$category." and display = 1 ORDER BY date DESC",
			false,
			3,
			@$_GET['n']
		);

		$abc['video'] = mysql_select("SELECT id,date,img,url$langid url,name$langid name,name_2$langid name_2 FROM videos WHERE display = 1 and url$langid!='' ORDER BY date desc LIMIT 3",'rows');
		$abc['tags'] = mysql_select("select * from news_tags where category=$category order by id asc",'rows_id');


		$abc['breadcrumb'][] = array(
			'name'=>$abc['gcats'][$category]["name$langid"],
			'url'=>get_url('news').$abc['gcats'][$category]["name$langid"].'/'
		);
		foreach($abc['languages'] as $i=>$v) {
			$abc['links'][$abc['languages'][$i]['url']][]=$abc['gcats'][$category]['url'.($i>1?$i:'')];
		}


		$abc['page'] = array_merge($abc['page'],mysql_select("select * from news_category where id=$category limit 1",'row')); //seo & text

		$abc['ads1'] = mysql_select("SELECT id,img$langid img,img_2$langid img_2,html$langid html,url$langid url FROM ads where display=1 and page='news_list'   and url$langid!=''",'rows');

                if(!isset($_COOKIE['popup'])) {
                  $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="news_list" and popup_places.display=1 order by rand() limit 1','string');
                  if(!$abc['popup']) $abc['popup']=mysql_select('select html'.$langid.' from popup_places left join popups on popup_places.popup=popups.id where popup_places.page="all" and popup_places.display=1 order by rand() limit 1','string');
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