<?php

/**
 * функции для работы с формами
 */

/**
 * функция возвращает массив обработанных данных
 * @param array $fields - массив - конструктор значений ({key}->{value})
 * допустимые значение value:
 * text - текст без html тегов
 * int - целое число больше 0
 * ceil - целое число
 * decimal - дробное число 12.02
 * date - дата
 * email - email
 * boolean - 1 или 0
 * string_int - числа через запятую 1,2,3
 * min_max - два числа через дефис - 12-56
 * @param array $post - массив данных $_POST или $_GET
 * @return mixed
 */
function form_smart($fields,$post) {
	foreach ($fields as $key=>$value) {
		if (is_array($value)) {
			$data[$key] = form_smart($value,isset($post[$key]) ? $post[$key] : '');
		}
		else {
			$param = explode(' ',$value);
			foreach ($param as $k=>$v) {
				if (isset($data[$key])) $post[$key] = $data[$key]; //если много разных случаев
				if ($v=='text')		$data[$key] = htmlspecialchars(@$post[$key]);
				elseif ($v=='html')		$data[$key] = htmlspecialchars_decode(htmlspecialchars(@$post[$key]));
				elseif ($v=='int')	$data[$key] = isset($post[$key]) ? abs(intval($post[$key])) : '';
				elseif ($v=='ceil')	$data[$key] = isset($post[$key]) ? intval($post[$key]) : 0;
				elseif ($v=='decimal') {
					$data[$key] = isset($post[$key]) ? preg_replace('/,/','.',$post[$key]) : '';
					$data[$key] = number_format((double)$data[$key], 2, '.', '');
				}
				//elseif ($v=='date')	$data[$key] = filter_var(@$post[$key], FILTER_SANITIZE_STRING);
				elseif ($v=='email') $data[$key] = isset($post[$key]) ? strtolower(filter_var(trim($post[$key]), FILTER_SANITIZE_EMAIL)) : '';
				elseif ($v=='phone') $data[$key] = isset($post[$key]) ? preg_replace('~[^+0-9]+~u', ' ', $post[$key]):'';
				elseif ($v=='boolean') {
					if (isset($post[$key])) $data[$key] = $post[$key]==1 ? 1 : 0;
					else $data[$key] = '';
				}
				//строка с цифрами через запятую или массив цифр
				elseif ($v=='string_int') {
					if (isset($post[$key]) AND $post[$key]!='') {
						$array = is_array($post[$key]) ? $post[$key] : explode(',',$post[$key]);
						$array2 = array();
						foreach ($array as $k=>$v) $array2[] = intval($v);
						$array2 = array_unique($array2);
						$data[$key] = implode(',',$array2);
					}
					else $data[$key] = '';
				}
				elseif ($v=='min_max') {
					$array = explode('-',isset($post[$key]) ? $post[$key] : '',2);
					$data[$key] = intval($array[0]).'-'.(isset($array[1]) ? intval($array[1]) : 0);
				}
				else $data[$key] = @$post[$key];
				//else $data[$key] = filter_var(@$post[$key], FILTER_SANITIZE_STRING);
			}
		}
	}
	return $data;
}

/**
 * @param array $fields -
 * @param array $fields - массив - конструктор значений ({key}->{value})
 * допустимые значение value:
 * required - обязательное поле
 * login - [-A-Za-z0-9_]
 * email - валидный емейл
 * password - валидный пароль - больше 5 символов
 * captcha - видимая каптча
 * captcha2 - скрытая каптча
 * @return array
 */
function form_validate($fields,$post) {
	$message = array();
	$required = 0;
	foreach ($fields as $key=>$value) {
		if (is_array($value)) {
			if ($i = form_smart($value,$post[$key])) $message = array_merge($message,form_validate($value,$post[$key]));
		}
		else {
			$param = explode(' ',$value);
			foreach ($param as $k=>$v) {
				if ($v=='required' && $required==0) {
					if ($post[$key]==='') {
						$required++;
						$message[] = i18n('validate|no_required_fields');
						//$message[] = $key;
					}
				}
				elseif ($v=='login') {
					if (strlen($post[$key])<3) $message[] = i18n('validate|short_login');
					if (!preg_match('/^[-A-Za-z0-9_]+$/',$post[$key])) $message[] = i18n('validate|not_valid_login');
				}
				elseif ($v=='email') {
					if ($post[$key] && !filter_var($post[$key],FILTER_VALIDATE_EMAIL)) $message[] = i18n('validate|not_valid_email');
				}
				elseif ($v=='password') {
					if (strlen($post[$key])<5) $message[] = i18n('validate|not_valid_password');
				}
				//видимая капча
				elseif ($v=='captcha') {
					if (mb_strtolower($post['captcha'],'UTF-8')!=$_SESSION['captcha'] OR mb_strtolower($post['captcha'],'UTF-8')=='') $message[] = i18n('validate|not_valid_captcha');
				}
				//невидимая капча
				elseif ($v=='captcha2') {
					if (empty($post[$key]) OR $post[$key]=='' OR empty($_SESSION[$key]) OR $post[$key]!=$_SESSION[$key])
						$message[] = i18n('validate|not_valid_captcha2');
					//if (isset($_SESSION[$key])) unset($_SESSION[$key]);
				}
			}
		}
	}
	return array_unique($message);
}
