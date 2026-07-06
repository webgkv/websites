<?php

// HTML helpers

/**
 * v1.2.63, v1.4.1 - admin paginator
 */

/**
 * Select options HTML.
 * @param mixed $key Selected key or array for multi
 * @param mixed $query Array of options or table/query name
 * @param string|null $default Default label
 * @param string $template Option template
 * @return string
 */
function select($key,$query,$default = NULL,$template = '{name}') {
	if (isset($default)) $content = $default ? '<option value="">'.$default.'</option>' : '<option value="">'.i18n('common|make_selection').'</option>';
	else $content = '';
	if (is_array($query)) foreach ($query as $k=>$v) {
		if (is_array($v) && !is_int($k)) {
			$content.= '<optgroup label="'.$k.'">';
			$content.= select($key,$v,$default,$template);
			$content.= '</optgroup>';
		}
		else {
			if (is_array($key)) $selected = in_array($k, $key) ? 'selected="selected"' : '';
			else $selected = ($k==$key AND (string)$key!='') ? 'selected="selected"' : '';
			$nbsp = '';
			if (is_array($v)) {
				if (isset($v['level'])) {
					for ($i = 1; $i<$v['level']; $i++) $nbsp.= '&nbsp; ';
					$nbsp.= ':.. ';
				}
			}
			$content.= '<option value="'.htmlspecialchars($k).'" '.$selected.'>'.$nbsp.(is_array($v) ? $v['name'] : $v).'</option>';
		}
	}
	else {
		if (!strpos($query, ' ')) $query = "SELECT id,name,level FROM `".$query."` ORDER BY left_key";
		if ($options = mysql_select($query,'rows')) {
			foreach ($options as $q) {
				$nbsp = '';
				if (isset($q['level'])) {
					for ($i = 1; $i < $q['level']; $i++) $nbsp .= '&nbsp; ';
					$nbsp .= ':.. ';
				}
				if (is_array($key)) $selected = in_array($q['id'], $key) ? 'selected="selected"' : '';
				else $selected = $q['id'] == $key ? 'selected="selected"' : '';
				if (isset($q['parent'])) $selected .= ' data-parent="' . $q['parent'] . '"';
				$str = $template;
				foreach ($q as $k => $v) $str = str_replace("{" . $k . "}", $q[$k], $str);
				$content .= '<option value="' . $q['id'] . '" ' . $selected . '>' . $nbsp . $str . '</option>';
			}
		}
	}
	return $content;
}


/**
 * Template: replace {i} with $data['i'], {brand|name} with $data['brand']['name'], etc.
 * @param string $template String with {placeholders}
 * @param array $data Values for replacement
 * @return string
 */
function template($template,$data) {
	preg_match_all('/{(.*?)}/',$template,$matches,PREG_PATTERN_ORDER);
	foreach($matches[1] as $k=>$v) {
		$keys = explode('|',$v);
		$replacement = $data;
		foreach ($keys as $i=>$key) {
			if (isset($replacement[$key])) {
				$replacement = $replacement[$key];
			} else {
				$replacement = '';
				break;
			}
		}
		$matches[1][$k] = is_array($replacement) ? '' : $replacement;
	}
	return str_replace($matches[0],$matches[1],$template);
}

/**
 * Replace {img} with image tags.
 * @param array $q Row from DB
 * @param string $table Table name
 * @param string $key_imgs Field name for images
 * @param string $key_text Field name for text containing {img} tags
 * @return string - processed text
 * @version v1.2.131
 * v1.2.101 - added
 * v1.2.131 - amp pages support
 */
function template_img ($table,$q,$key_imgs='imgs',$key_text='text') {
	global $config;
	// build array from image HTML
	$data = [];
	$imgs = get_imgs($table,$q,$key_imgs);
	foreach ($imgs as $k=>$v) {
		$item = [
			'name' => $v['name'],
			'title' => htmlspecialchars(@$v['title']?:$v['name']),
			'alt' => htmlspecialchars(@$v['alt']?:$v['name']),
			'_'=>$v['_']
		];
		// v1.2.131 - AMP pages
		if ($config['amp']) {
			$size = getimagesize(ROOT_DIR.$item['_']);
			$data[] = '<amp-img style="width:100%" src="'.$item['_'].'" width="'.$size[0].'" height="'.$size[1].'" layout="responsive"></amp-img>';
		}
		else $data[] = '<img src="'.$item['_'].'" alt="'.$item['alt'].'" title="'.$item['title'].'">';
	}
	if ($data) {
		// replace {img} with images
		preg_match_all('/{img}/', $q[$key_text], $matches);
		$next = 0;
		if (!empty($matches[0]) && is_array($matches[0]) && ($cnt = count($matches[0])) && count($data)) {
			for ($i = 0; $i < $cnt; $i++) {
				if ($next > (count($data) - 1)) {
					$next = 0;
				}
				$q[$key_text] = preg_replace('/{img}/', $data[$next++], $q[$key_text], 1);
			}
		}
	}
	else $q[$key_text] = preg_replace('/{img}/', '', $q[$key_text]);
	return $q[$key_text];
}

/**
 * Replace {video} with video frames
 * @param string $text - html with {video} placeholders
 * @param mixed $videos - array of videos or newline-separated string
 * @return string - html with video frames
 * @version v1.2.131
 * v1.2.101 - added
 * v1.2.131 - amp pages support
 */
function template_video ($text,$videos) {
	global $config;
	// Build array of video frames
	$data = [];
	$videos = is_array($videos) ? $videos : explode("\r\n",$videos);
	foreach ($videos as $k=>$v) if ($v) {
		// v1.2.131 - AMP pages
		if ($config['amp']) {
			$data[] = '<amp-iframe width=300 height=300
			   layout="responsive"
			   sandbox="allow-scripts allow-same-origin"
			   frameborder="0"
			   src="'.video_iframe($v).'">
			</amp-iframe>';
		}
		else $data[] = '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="'.video_iframe($v).'" allowfullscreen></iframe></div>';
	}
	if ($data) {
		// replace {video} with frames
		preg_match_all('/{video}/', $text, $matches);
		$next = 0;
		if (!empty($matches[0]) && is_array($matches[0]) && ($cnt = count($matches[0])) && count($data)) {
			for ($i = 0; $i < $cnt; $i++) {
				if ($next > (count($videos) - 1)) {
					$next = 0;
				}
				$text = preg_replace('/{video}/', $data[$next++], $text, 1);
			}
		}
	}
	else $text = preg_replace('/{video}/', '', $text);
	return $text;
}


/**
 * Replace {page|text} with template content
 * @param string $text - html containing placeholders
 * @return string - processed html
 */
function html_template($text) {
	preg_match_all('/{(.*?)}/',$text,$matches,PREG_PATTERN_ORDER);
	foreach($matches[1] as $k=>$v) {
		$matches[1][$k] = is_file(ROOT_DIR.'templates/includes/'.$v.'.php') ? html_array($v) : '';
	}
	return str_replace($matches[0],$matches[1],$text);
}

/**
 * Randomly select one word from synonym set [option1|option2]
 * @param string $text - input text
 * @return string - randomized text
 * @version v1.2.24
 * v1.2.24 - added
 */
function synonymizer ($text) {
	$reg = "/\\[[^\[]*\\]/";
	preg_match_all ($reg,$text, $matches);
	$result_rand=array();
	foreach($matches[0] as $k=>$v){
		$v = trim(str_replace(array('[',']'), "", $v));
		$v = explode('|',$v);
		$v = $v[array_rand($v)];
		$result_rand[$k]=trim($v);
	};
	$reg_arr = array();
	foreach($result_rand as $k => $v){
		$reg_arr[$k]=$reg;
	};
	$text = preg_replace($reg_arr,$result_rand,$text,1);
	return $text;
}

/**
 * Include template and fill with data array
 * @param string $path - template path
 * @param mixed $q - data array or string
 * @return string - rendered html
 * @version v1.3.2
 */
function html_array($path,$q = array()) {
	global $config,$modules,$u,$user,$lang,$page,$html,$abc;
	require_once(ROOT_DIR.'functions/callback_func.php');
	if (@$config['amp']) $path = 'amp/'.$path;
	$i = $num_rows = 0;
	$function = '_'.str_replace('/','_',$path);
	if (function_exists($function)) {
		$q = $function($q);
	}
	ob_start(); // echo to buffer, not screen
	include (ROOT_DIR.$config['style'].'/includes/'.$path.'.php');
	return ob_get_clean(); // get buffer contents
}

/**
 * Fill template with results from DB query
 * @param string $path - template path (optionally followed by space and paginator path)
 * @param mixed $query - SQL query string or data array
 * @param mixed $no_results - message to show when no results (default i18n('common|msg_no_results'))
 * @param int $cache - cache TTL in seconds
 * @param string $cache_type - 'html' or 'json'
 * @return string - rendered content
 * @version v1.3.2
 */
function html_query($path, $query, $no_results = false, $cache = 0, $cache_type = 'html') {
	global $config,$lang,$modules,$user,$u,$page,$html,$abc;
	require_once(ROOT_DIR.'functions/callback_func.php');
	if ($config['amp']) $path = 'amp/'.$path;
	$content	= false;
	$data		= array();
	$m			= explode(' ',$path);
	$function = '_'.str_replace('/','_',$m[0]);
	$time		= time() - $cache;
	$file_template = ROOT_DIR.$config['style'].'/includes/'.$m[0].'.php';
	// Include paginator if specified
	if (isset($m[1])) {
		$file_pagination = ROOT_DIR.$config['style'].'/includes/pagination/'.$m[1].'.php';
		if (file_exists($file_pagination)) include ($file_pagination);
		else trigger_error('file not exists '.$file_pagination, E_USER_DEPRECATED);
	}
	// Cache logic
	if (@$config['cache'] && $cache && is_string($query)) {
		if ($cache_type=='json') $file	= md5($query).'.php';
		else $file	= md5($query).'.html';
		$config['queries'][$file] = $query;
		$file = ROOT_DIR.'cache/'.$file;
		if (file_exists($file) && $time<filemtime($file)) {
			if ($cache_type=='json') {
				$content = '';
				$data = json_decode(file_get_contents($file),true);
				if (is_array($data)) {
					$num_rows = count($data);
					$i = 1;
					foreach ($data as $q) {
						ob_start();
						include (ROOT_DIR.$config['style'].'/includes/'.$m[0].'.php');
						$content.= ob_get_clean();
						$i++;
					}
				}
			}
			else $content.= file_get_contents($file);
		}
	}
	// Fetch from DB if no cache
	if ($content===false) {
		if (file_exists($file_template)) {
			if (is_array($query)) {
				if ($num_rows = count($query)) {
					$i = 1;
					foreach ($query as $k => $q) {
						$data[] = $q;
						if (function_exists($function)) {
							$q = $function($q);
						}
						ob_start();
						include($file_template);
						$content .= ob_get_clean();
						$i++;
					}
				}
			}
			else {
				if (mysql_connect_db()) {
					if ($data = mysql_select($query, 'rows')) {
						$num_rows = count($data);
						$i = 1;
						foreach ($data as $q) {
							if (function_exists($function)) {
								$q = $function($q);
							}
							ob_start();
							include($file_template);
							$content .= ob_get_clean();
							$i++;
						}
					}
					// Write cache
					if (@$config['cache'] && $cache && (is_dir(ROOT_DIR . 'cache') || mkdir(ROOT_DIR . 'cache', 0755, true))) {
						$f = fopen($file, 'w');
						if ($cache_type == 'json') fwrite($f, json_encode($data));
						else fwrite($f, $content);
						fclose($f);
					}
				}
			}
		}
		else trigger_error('file not exists '.$file_template, E_USER_DEPRECATED);
	}
	// No results handling
	if ($content=='') {
		if ($no_results===false) {
			$no_results = i18n('common|msg_no_results');
		}
		$content = $no_results ? '<div class="no_results">'.$no_results.'</div>' : '';
	}
	// Wrap in paginator if present
	if (isset($pagination)) $content = str_replace('{content}',$content,$pagination);
	return $content;
}

/**
 * Render HTML from array, alternative to html_array and html_query
 * @param string $path - template path
 * @param mixed $data - data array
 * @return string - rendered html
 * @version v1.3.19
 */
function html_render($path,$data=true) {
	global $config,$abc,$lang,$user;
	require_once(ROOT_DIR.'functions/callback_func.php');
	if ($config['amp']) {
		if (file_exists(ROOT_DIR.$config['style'].'/includes/amp/'.$path.'.php')) {
			$path = 'amp/' . $path;
		}
	}
	$function = '_'.str_replace('/','_',$path);
	$content	= '';
	$file_template = ROOT_DIR.$config['style'].'/includes/'.$path.'.php';
	// If call is without data or with a simple associative array
	if ($data===true OR is_string(key($data))) {
		$q = array();
		$num_rows = 1;
		$i = 1;
		if ($data!=false) {
			$q = $data;
			if (function_exists($function)) {
				$q = $function($q);
			}
		}
		ob_start();
		include ($file_template);
		$content.= ob_get_clean();

	}
	// Iterate if data is indexed array
	elseif(is_array($data)) {
		$num_rows = count($data);
		$i = 1;
		foreach ($data as $q) {
			if (function_exists($function)) {
				$q = $function($q);
			}
			ob_start();
			include ($file_template);
			$content.= ob_get_clean();
			$i++;
		}
	}

	return $content;
}

/**
 * Fill template with results from JSON file
 * @param string $include - template path
 * @param string $data - data filename in /data/
 * @param int $limit - max items
 * @return string - rendered html
 * added v.1.1.38
 */
function html_json ($include,$data,$limit=0) {
	global $config;
	$path = ROOT_DIR.$config['style'].'/data/'.$data.'.txt';
	$content = '';
	if (file_exists($path)) {
		$data = file_get_contents($path);
		$data = json_decode($data,true);
		if (is_array($data)) {
			$num_rows = $limit ? $limit : count($data);
			$i = 1;
			foreach ($data as $q) {
				ob_start();
				include (ROOT_DIR.$config['style'].'/includes/'.$include.'.php');
				$content.= ob_get_clean();
				if ($i==$limit) return $content;
				$i++;
			}
		}
	}
	else {
		return 'error';
	}
	return $content;

}

/**
 * Batch replacement for optimized queries $config['optimize']
 * @param string $text - text to process
 * @return string - processed text
 * @version v1.2.48
 */
function html_optimize ($text) {
	global $config;
	if (isset($config['optimize'])) {
		$data = array();
		foreach ($config['optimize'] as $k=>$v) {
			$query = "SELECT * FROM `".$k."` WHERE id IN (" . $v . ")";
			$data[$k] = mysql_select($query, 'rows_id');
		}
		return template($text,$data);
	}
	return $text;
}

/**
 * Register IDs for batch optimization
 * @param string $table - table name
 * @param string $ids - comma-separated IDs
 * @version v1.2.48
 */
function html_optimize_data ($table,$ids) {
	global $config;
	if ($ids) {
		if (isset($config['optimize'][$table]) AND $config['optimize'][$table]) {
			$data1 = explode(',', $config['optimize'][$table]);
			$data2 = explode(',', $ids);
			$data = array_merge($data1,$data2);
			$config['optimize'][$table] = implode(',',$data);
		}
		else {
			$config['optimize'][$table] = $ids;
		}
	}
}

/**
 * Generate breadcrumb array
 * @param string $query - SQL query
 * @param string $template - URL template (e.g. '/shop/{url}/')
 * @param int $cache - cache TTL
 * @return array
 * @version 1.3.2
 */
function breadcrumb($query,$template = '/{url}/',$cache = false) {
	$data = mysql_select($query,'rows',$cache);
	if (is_array($data)) {
		foreach ($data as $key=>$value) {
			$str = $template;
			foreach ($value as $k=>$v) $str = str_replace ("{".$k."}", $value[$k], $str);
			$breadcrumb[] = array(
				'name'=>$value['name'],
				'url'=>$str);
		}
		return $breadcrumb;
	}
}


/**
 * Returns attributes for editable blocks
 * @param string $edit - entity key (e.g. 'pages|4|text')
 * @param string $editable - editor type ['str', 'text']
 * @return string - data attributes for inline editing
 */
function editable($edit,$editable='str') {
	global $lang;
	$array = explode('|',$edit);
	if (access('editable '.$array[0]) && !isset($_GET['i18n'])) {
		return ' data-editable_type="'.$editable.'" data-editable_module="'.$lang['id'].'|'.$edit.'"';
	}
}

/**
 * Script/Style inclusion with label support
 * @param string $label - 'head', 'footer', or 'return'
 * @param string $source - space-separated source keys from $config['sources']
 * @return string - rendered tags
 * @version 1.2.70
 */
function html_sources($label='',$source='') {
	global $config, $lang;
	$content = array();
	if ($source) {
		$sources = explode(' ', $source);
		foreach ($sources as $k=>$v) if ($v!='') {
			if (isset($config['sources'][$v])) {
				$config['html_sources'][$label][$v] = $config['sources'][$v];
				if ($label == 'return') $content[] = $config['sources'][$v];
			}
			else {
				trigger_error('script key not found in $config[\'sources\']: '.$v,E_USER_DEPRECATED);
			}
		}
	}
	else {
		$content = isset($config['html_sources'][$label]) ? $config['html_sources'][$label] : array();
	}
	// Render tags if content exists
	if (count($content)>0) {
		$text = '';
		foreach ($content as $key=>$val) {
			if (is_array($val)) {
				foreach ($val as $k=>$v) {
					$str = template($v, $lang);
					$clean_str = strtok($str, '?');
					if (file_exists(ROOT_DIR . $clean_str)) {
						if (substr($v, -1) == '?') {
							$str .= filemtime(ROOT_DIR . $clean_str);
						}
						if ($config['smartoptimizer'] == true) $str = '/smartoptimizer/?'.$str;
						$text .= strpos($clean_str, '.js') ? '<script type="text/javascript" src="' . $str . '"></script>' : '<link href="' . $str . '" rel="stylesheet" type="text/css" />';
						$text .= PHP_EOL;
					}
					else {
						trigger_error('file not found: '.$v,E_USER_DEPRECATED);
					}
				}
			}
			else {
				$str = template($val, $lang);
				// External link or raw tag
				if ($str[0]=='<') {
					$text .=  $str;
				}
				// Local file exists
				else {
					$clean_str = strtok($str, '?');
					if (file_exists(ROOT_DIR . $clean_str)) {
						if (substr($val, -1) == '?') {
							$str .= filemtime(ROOT_DIR . $clean_str);
						}
						if ($config['smartoptimizer'] == true) $str = '/smartoptimizer/?'.$str;
						$text .= strpos($clean_str, '.js') ? '<script type="text/javascript" src="' . $str . '"></script>' : '<link href="' . $str . '" rel="stylesheet" type="text/css" />';
						$text .= PHP_EOL;
					}
					else {
						trigger_error('file not found: '.$val,E_USER_DEPRECATED);
					}
				}
			}
		}
		return $text;
	}
}

/**
 * Generate paginator link
 * @param string $key - GET parameter key
 * @param string $value - required value
 * @param int $default - default value
 * @return string - generated URL
 */
function pagination_link ($key,$value,$default=1) {
	global $u;
	$get = $_GET;
	unset($get['u'],$get[$key]);
	$link =  '/';
	if ($u) {
		foreach ($u as $k=>$v) if ($v) $link.= $v.'/';
	}
	else {
		$link = '/admin.php';
	}
	if ($value!=$default) {
		$get[$key] = $value;
	}
	$url = http_build_query($get);
	$link.= $url ? '?' . $url : '';
	return $link;
}

/**
 * Alternative paginator link generator
 */
function pagination_link2($key,$value,$default=1) {
	global $u;
	$link =  '/';
	if($u) foreach ($u as $k=>$v) if ($v) $link.= $v.'/';
	if($value>1) $link.="$value/";
	return $link;
}
