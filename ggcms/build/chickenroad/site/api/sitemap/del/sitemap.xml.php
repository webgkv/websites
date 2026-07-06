<?php

// Generates sitemap XML from site pages. Available at /api/sitemap/del/sitemap.xml

header('Content-type: text/xml; charset=UTF-8');

$config['cache'] = false;
$cache = 0;
$file = ROOT_DIR . '/api/sitemap/sitemap.xml';

// Skip if cache still valid
if (file_exists($file) AND (time() - $cache) < filemtime($file)) {
	echo file_get_contents($file);
	die();
}

$content='<?xml version="1.0" encoding="UTF-8"?>'."\r\n".
'<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"'."\r\n".
'    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'."\r\n".
'    xmlns:xhtml="http://www.w3.org/1999/xhtml"'."\r\n".
'    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9'."\r\n".
'    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'."\r\n".
'    http://www.w3.org/1999/xhtml'."\r\n".
'    http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd">'."\r\n";

$languages = mysql_select("SELECT * FROM languages WHERE display=1 ORDER BY `rank` DESC", 'rows');

$temp=mysql_select("SELECT * FROM pages WHERE module!='pages' AND noindex=0 AND display=1", 'rows');
$modules=array();
foreach($temp as $v) $modules[$v['module']]=$v;

unset($modules['search']);

// Site tree

$content.="<url>\r\n    <loc>".$config['http_domain'].'/'.$languages[0]['url']."/</loc>\r\n";
foreach($languages as $langid=>$lang) if($langid>0) {
	$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/" />'."\r\n";
}
$content.="</url>\r\n";

if ($pages = mysql_select("
	SELECT * FROM pages
	WHERE noindex=0 AND display=1 AND not url like '%/%'
	ORDER BY left_key", "rows_id")
) {
	foreach ($pages as $q) {
//		if($q['module']!='search'&&$q['module']!='index') {
		if(in_array($q['id'],array(2,6))) {
			$pariuri=0;
//			if(in_array($q['id'],array(15,19,20))) $pariuri=1;
			$content.="<url>\r\n";
			$content.="    <loc>".$config['http_domain'].'/'.$languages[0]['url'].'/'.$q['url'].'/'."</loc>\r\n";
			foreach($languages as $langid=>$lang) if($q['url'.$lang['id']]) {
				$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/'.($pariuri?$pages[14]['url'].'/':'').$q['url'.$lang['id']].'/" />'."\r\n";
			}
			$content.="</url>\r\n";
		}
	}
}

//videos
if (isset($modules['videos']) AND $pages = mysql_select("
	SELECT *
	FROM videos
	WHERE display=1
	ORDER BY date DESC", 'rows')
) {
	foreach ($pages as $q) {
		$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');

		$content.="<url>\r\n";
		$content.="    <loc>".$config['http_domain'].'/'.$languages[0]['url'].'/'.$modules['videos']['url'].'/'.$q['url'].'/'."</loc>\r\n";
		foreach($languages as $langid=>$lang) if($q['url'.$lang['id']]) {
			$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/'.($pariuri?$modules['betting']['url'].'/':'').$modules['videos']['url'.$lang['id']].'/'.$q['url'.$lang['id']].'/" />'."\r\n";
		}
		$content.="    <lastmod>".date2($dt, '%Y-%m-%d')."</lastmod>\r\n";
		$content.="</url>\r\n";
	}
}

//blog
if (isset($modules['blog'])) {

	if($cats = mysql_select("
		SELECT *
		FROM blog_category
		ORDER BY id DESC", 'rows_id')
	) {
		foreach ($cats as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');

			$content.="<url>\r\n";
			$content.="    <loc>".$config['http_domain'].'/'.$languages[0]['url'].'/'.$modules['blog']['url'].'/'.$q['url'].'/'."</loc>\r\n";
			foreach($languages as $langid=>$lang) if($q['url'.$lang['id']]) {
				$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/'.($pariuri?$modules['betting']['url'].'/':'').$modules['blog']['url'.$lang['id']].'/'.$q['url'.$lang['id']].'/" />'."\r\n";
			}
			$content.="    <lastmod>".date2($dt, '%Y-%m-%d')."</lastmod>\r\n";
			$content.="</url>\r\n";

			if($tags = mysql_select("
				SELECT *
				FROM blog_tags WHERE category='".$q['id']."'
				ORDER BY id DESC", 'rows')
			) {
				foreach ($tags as $q1) {
					$dt=$q1['updated_at'];if(!$dt) $dt=date('Y-m-d');

					$content.="<url>\r\n";
					$content.="    <loc>".$config['http_domain'].'/'.$languages[0]['url'].'/'.$modules['blog']['url'].'/'.$q['url'].'/'.$q1['url']."/</loc>\r\n";
					foreach($languages as $langid=>$lang) if($q1['url'.$lang['id']]) {
						$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/'.($pariuri?$modules['betting']['url'].'/':'').$modules['blog']['url'.$lang['id']].'/'.$q['url'.$lang['id']].'/'.$q1['url'.$lang['id']].'/" />'."\r\n";
					}
					$content.="    <lastmod>".date2($dt, '%Y-%m-%d')."</lastmod>\r\n";
					$content.="</url>\r\n";
				}
			}
		}
	}

	if($pages = mysql_select("
		SELECT * FROM blog
		WHERE display=1
		ORDER BY date DESC", 'rows')
	) {
		foreach ($pages as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');

			$content.="<url>\r\n";
			$content.="    <loc>".$config['http_domain'].'/'.$languages[0]['url'].'/'.$modules['blog']['url'].'/'.$cats[$q['category']]['url'].'/'.$q['url']."/</loc>\r\n";
			foreach($languages as $langid=>$lang) if($q['url'.$lang['id']]) {
				$content.='    <xhtml:link rel="alternate" hreflang="'.$lang['url'].'" href="'.$config['http_domain'].'/'.$lang['url'].'/'.($pariuri?$modules['betting']['url'].'/':'').$modules['blog']['url'.$lang['id']].'/'.$cats[$q['category']]['url'.$lang['id']].'/'.$q['url'.$lang['id']].'/" />'."\r\n";
			}
			$content.="    <lastmod>".date2($dt, '%Y-%m-%d')."</lastmod>\r\n";
			$content.="</url>\r\n";
		}
	}

}





/*
	//sportsbooks
	if (isset($modules['sportsbooks']) AND $pages = mysql_select("
		SELECT id,url,updated_at
		FROM `sportsbooks`
		WHERE display=1
		ORDER BY id DESC", 'rows')
	) {
		foreach ($pages as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
			$urls[] = array(
				'loc' => $config['http_domain'] . get_url('sportsbooks', $q).$q['url'].'/',
				'lastmod' => date2($dt, '%Y-%m-%d'),
				//'changefreq'=>'monthly',
				//'priority'=>0.8
			);
		}
	}

	//casinos
	if (isset($modules['casinos']) AND $pages = mysql_select("
		SELECT id,url,updated_at
		FROM casinos
		WHERE display=1
		ORDER BY id DESC", 'rows')
	) {
		foreach ($pages as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
			$urls[] = array(
				'loc' => $config['http_domain'] . get_url('casinos', $q).$q['url'].'/',
				'lastmod' => date2($dt, '%Y-%m-%d'),
				//'changefreq'=>'monthly',
				//'priority'=>0.8
			);
		}
	}

	//biletul-zilei
	if (isset($modules['biletul-zilei']) AND $pages = mysql_select("
		SELECT id,date
		FROM `biletul-zilei`
		ORDER BY id DESC", 'rows')
	) {
		foreach ($pages as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
			$urls[] = array(
				'loc' => $config['http_domain'] . '/pariuri' . get_url('biletul-zilei', $q).substr($q['date'],0,10).'/',
				'lastmod' => date2($dt, '%Y-%m-%d'),
				//'changefreq'=>'monthly',
				//'priority'=>0.8
			);
		}
	}

	//ghid-pariuri
	if (isset($modules['ghid-pariuri']) AND $pages = mysql_select("
		SELECT id,url
		FROM `ghid-pariuri`
		WHERE display=1
		ORDER BY id DESC", 'rows')
	) {
		foreach ($pages as $q) {
			$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
			$urls[] = array(
				'loc' => $config['http_domain'] . '/pariuri' . get_url('ghid-pariuri', $q).$q['url'].'/',
				'lastmod' => date2($dt, '%Y-%m-%d'),
				//'changefreq'=>'monthly',
				//'priority'=>0.8
			);
		}
	}

	//blog
	if (isset($modules['blog'])) {

		if($pages = mysql_select("
			SELECT id,url,updated_at
			FROM blog_category
			ORDER BY id DESC", 'rows')
		) {
			foreach ($pages as $q) {
				$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
				$urls[] = array(
					'loc' => $config['http_domain'] . get_url('blog') . $q['url'] . '/',
					'lastmod' => date2($dt, '%Y-%m-%d'),
				);
				if($tags = mysql_select("
					SELECT id,url,updated_at
					FROM blog_tags WHERE category='".$q['id']."'
					ORDER BY id DESC", 'rows')
				) {
					foreach ($tags as $q1) {
						$dt=$q1['updated_at'];if(!$dt) $dt=date('Y-m-d');
						$urls[] = array(
							'loc' => $config['http_domain'] . get_url('blog') . $q['url'] . '/' . $q1['url'] . '/',
							'lastmod' => date2($dt, '%Y-%m-%d'),
						);
					}
				}
			}
		}

		if($pages = mysql_select("
			SELECT blog.id,blog.url,blog.updated_at,blog_category.url caturl
			FROM blog
			LEFT JOIN blog_category on blog_category.id=blog.category
			WHERE blog.display=1
			ORDER BY blog.date DESC", 'rows')
		) {
			foreach ($pages as $q) {
				$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
				$urls[] = array(
					'loc' => $config['http_domain'] . get_url('blog') . $q['caturl'] . '/' . $q['url'] . '/',
					'lastmod' => date2($dt, '%Y-%m-%d'),
				);
			}
		}
	}

	//recenzii
	if (isset($modules['recenzii'])) {

		if($pages = mysql_select("
			SELECT id,url,updated_at
			FROM recenzii_category
			ORDER BY id DESC", 'rows')
		) {
			foreach ($pages as $q) {
				$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
				$urls[] = array(
					'loc' => $config['http_domain'] . get_url('recenzii') . $q['url'] . '/',
					'lastmod' => date2($dt, '%Y-%m-%d'),
				);
				if($tags = mysql_select("
					SELECT id,url,updated_at
					FROM recenzii_tags WHERE category='".$q['id']."'
					ORDER BY id DESC", 'rows')
				) {
					foreach ($tags as $q1) {
						$dt=$q1['updated_at'];if(!$dt) $dt=date('Y-m-d');
						$urls[] = array(
							'loc' => $config['http_domain'] . get_url('recenzii') . $q['url'] . '/' . $q1['url'] . '/',
							'lastmod' => date2($dt, '%Y-%m-%d'),
							//'changefreq'=>'monthly',
							//'priority'=>0.8
						);
					}
				}

			}
		}

		if($pages = mysql_select("
			SELECT recenzii.id,recenzii.url,recenzii.updated_at,recenzii_category.url caturl
			FROM recenzii
			LEFT JOIN recenzii_category on recenzii_category.id=recenzii.category
			WHERE recenzii.display=1
			ORDER BY recenzii.date DESC", 'rows')
		) {
			foreach ($pages as $q) {
				$dt=$q['updated_at'];if(!$dt) $dt=date('Y-m-d');
				$urls[] = array(
					'loc' => $config['http_domain'] . get_url('recenzii') . $q['caturl'] . '/' . $q['url'] . '/',
					'lastmod' => date2($dt, '%Y-%m-%d'),
					//'changefreq'=>'monthly',
					//'priority'=>0.8
				);
			}
		}
	}
*/

// Generate only non-indexed
/*
elseif (@$config['sitemap_generation']==2) {
	$urls[] = sitemap("SELECT url FROM seo_pages WHERE exist=1 AND yandex_index=0 ORDER BY yandex_check",'{url}');
	foreach ($urls as $k=>$v) {
		$url = $xml->addChild('url');
		$url->addChild('loc', $config['http_domain'].$v);
	}
}
*/
/*$content.= '
</urlset>';*/

$content.='</urlset>';


// Write to file
$fp = fopen($file, 'w');
fwrite($fp, $content);
/**/

echo $content;

die();

// pariuri-sportive
// cazinouri
// stiri
// videos
