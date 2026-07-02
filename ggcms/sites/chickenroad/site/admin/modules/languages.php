<?php

/*
 * v1.4.14 - event_func
 * v1.4.16 - $delete удалил confirm
 * v1.4.17 - сокращение параметров form
 * v1.4.20 - значение в поле
 */

// Localization dropdown:
// show only locales that correspond to existing languages in DB (prevents legacy ru/uz/etc clutter).
$locales = array();
$langs_for_locales = mysql_select("SELECT url, name FROM languages ORDER BY rank DESC, name ASC", 'rows') ?: array();
$i18n_dir = ROOT_DIR . 'plugins/jquery/i18n/';
foreach ($langs_for_locales as $l) {
	$code = isset($l['url']) ? strtolower(trim((string)$l['url'])) : '';
	if (!preg_match('/^[a-z]{2}$/', $code)) continue;
	// If datepicker locale file exists, keep; otherwise still allow selecting it (admin scripts will just not load that file).
	$locales[$code] = isset($l['name']) && (string)$l['name'] !== '' ? (string)$l['name'] : strtoupper($code);
}
if (empty($locales)) {
	$locales = array('en' => 'English');
}
asort($locales, SORT_NATURAL | SORT_FLAG_CASE);

// delete language
function event_delete_languages ($q) {
	global $config;
	if ($q) {
		foreach ($config['lang_tables'] as $key => $val) {
			foreach ($val as $k => $v) {
				mysql_fn('query', "ALTER TABLE `" . $key . "` DROP `" . $k . $q['id'] . "`");
			}
		}
	}
}

function event_change_languages ($q) {
	global $config;
	if (is_dir(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary') || mkdir(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary', 0755, true)) {
		$post = stripslashes_smart($_POST);
		if (!empty($post['dictionary']) && is_array($post['dictionary'])) foreach ($post['dictionary'] as $key => $val) {
			$str = '<?php' . PHP_EOL;
			$str .= '$lang[\'' . $key . '\'] = array(' . PHP_EOL;
			foreach ($val as $k => $v) {
				$str .= "	'" . $k . "'=>'" . str_replace("'", "\'", $v) . "'," . PHP_EOL;
			}
			$str .= ');';
			$str .= '?>';
			$fp = fopen(ROOT_DIR . 'files/languages/' . $q['id'] . '/dictionary/' . $key . '.php', 'w');
			fwrite($fp, $str);
			fclose($fp);
		}
	}
	// When multilingual, add columns to lang tables
	if ($config['multilingual']) {
		if ($_GET['id'] == 'new') {
			foreach ($config['lang_tables'] as $key=>$val) {
				foreach ($val as $k=>$v) {
					mysql_fn('query',"ALTER TABLE `".$key."` ADD `".$k.$q['id']."` ".$v." AFTER `".$k."`");
				}
			}
		}
	}
}

// Multilingual
if ($config['multilingual']) {
	$module['save_as'] = true;
	$table = array(
		'id'			=>	'rank:desc name id',
		'name'			=>	'',
		'rank'			=>	'',
		'url'			=>	'',
		'localization'	=>	'',
		'display'		=>	'display'
	);
	$form[0][] = array('input td4','name');
	$form[0][] = array('input td2','rank');
	$form[0][] = array('input td2','url');
	$form[0][] = array('input td2','localization', array('attr' => 'list="localization_list" placeholder="e.g. en, de, fr, es"'));
	$form[0][] = array('checkbox td2','display');
}
// Single language
else {
	$module['one_form'] = true;
	$get['id'] = 1;
	if ($get['u']!='edit') {
		$post = mysql_select("
			SELECT *
			FROM languages
			WHERE id = 1
			LIMIT 1
		",'row');
	}
}

$a18n['localization'] = 'localization';

// v1.4.16 - $delete removed confirm
$delete = array('pages' => 'language');

// Tabs: keep only language metadata here.
// System i18n editing is centralized in Pages → i18n_sys.
$tabs = array(0 => 'Main');

$lang_id_for_links = (isset($get['id']) && is_numeric($get['id'])) ? (int)$get['id'] : 0;
$form[0][] = array('text td12', '', array(
	'name' => 'Dictionary / i18n',
	'value' =>
		'<div class="mb-3"><a class="btn btn-sm btn-outline-primary" href="/admin.php?m=languages_json' . ($lang_id_for_links > 0 ? '&lang_id=' . $lang_id_for_links : '') . '">Languages / i18n</a></div>'
		. '<div class="d-flex flex-wrap align-items-end gap-2 js-langpack-import" data-action="/admin.php?m=languages_json&u=import_full' . ($lang_id_for_links > 0 ? '&lang_id=' . $lang_id_for_links : '') . '">'
		. '<input type="file" name="json_file" accept=".json,application/json" class="form-control-file js-langpack-file">'
		. '<button type="button" class="btn btn-sm btn-secondary js-langpack-import-btn">Import JSON</button>'
		. '</div>'
		. ($lang_id_for_links > 0
			? ('<div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="/admin.php?m=languages_json&u=export_full&lang_id=' . $lang_id_for_links . '">Export full pack for this language</a></div>')
			: ''
		)
		. '<datalist id="localization_list">'
		. (function() {
			$s = '';
			// suggestions from datepicker locales (plugins/jquery/i18n/jquery.ui.datepicker-*.js)
			$dir = ROOT_DIR . 'plugins/jquery/i18n/';
			$opts = array();
			if (is_dir($dir)) {
				$files = glob($dir . 'jquery.ui.datepicker-*.js');
				if (is_array($files)) foreach ($files as $f) {
					if (preg_match('~datepicker-([a-z]{2})\\.js$~i', (string)$f, $m)) $opts[] = strtolower($m[1]);
				}
			}
			$opts = array_values(array_unique(array_filter($opts)));
			sort($opts);
			foreach ($opts as $o) $s .= '<option value="' . htmlspecialchars($o, ENT_QUOTES, 'UTF-8') . '"></option>';
			return $s;
		})()
		. '</datalist>',
));

// Drop legacy dictionary tabs/forms if they were defined below in older versions
$form = array(0 => $form[0]);

// Stop here: legacy hardcoded dictionary editor below is deprecated and must not run.
return;

$form[1][] = lang_form('input td12','common|casino_bonuses','бонусы казино');
$form[1][] = lang_form('input td12','common|sportsbooks_bonuses','бонусы спортбуков');
$form[1][] = lang_form('input td12','common|last_update','последнее обновление');
$form[1][] = lang_form('input td12','common|top_bonuses','топ бонусов');
$form[1][] = lang_form('input td12','common|all_casinos','все казино');
$form[1][] = lang_form('input td12','common|all_sportsbooks','все спортбуки');
$form[1][] = lang_form('input td12','common|by_rating','по рейтингу');
$form[1][] = lang_form('input td12','common|name','название');
$form[1][] = lang_form('input td12','common|bonus','бонус');
$form[1][] = lang_form('input td12','common|reviews','обзор');
$form[1][] = lang_form('input td12','common|rating','рейтинг');
$form[1][] = lang_form('input td12','common|get_your_bonus','получите свой бонус');
$form[1][] = lang_form('input td12','common|minimum_deposit','минимальный депозит');
$form[1][] = lang_form('input td12','common|accepted_currency','принимаемая валюта');
$form[1][] = lang_form('input td12','common|bonuses','бонусы');
$form[1][] = lang_form('input td12','common|faq','часто задаваемые вопросы');
$form[1][] = lang_form('input td12','common|terms_conditions','условия и положения');
$form[1][] = lang_form('input td12','common|terms_conditions2','условия и положения');
$form[1][] = lang_form('input td12','common|play_now','играть сейчас');
$form[1][] = lang_form('input td12','common|advantages_disadvantages','преимущества и недостатки');
$form[1][] = lang_form('input td12','common|our_review','наш обзор');
$form[1][] = lang_form('input td12','common|all_reviews','все отзывы');
$form[1][] = lang_form('input td12','common|total_rating','общий рейтинг');
$form[1][] = lang_form('input td12','common|bonuses_offers','бонусы и предложения');
$form[1][] = lang_form('input td12','common|odds','шансы');
$form[1][] = lang_form('input td12','common|authentication_procedure','процедура аутентификации');
$form[1][] = lang_form('input td12','common|deposits_withdrawals','депозиты и снятия');
$form[1][] = lang_form('input td12','common|reliability_rating','рейтинг надежности');
$form[1][] = lang_form('input td12','common|transmission','трансмиссия');
$form[1][] = lang_form('input td12','common|customer_support','поддержка клиентов');
$form[1][] = lang_form('input td12','common|easy_to_use','простота использования');
$form[1][] = lang_form('input td12','common|signup_bonuses','бонусы за регистрацию');
$form[1][] = lang_form('input td12','common|ios_app','приложение ios');
$form[1][] = lang_form('input td12','common|android_app','приложение android');
$form[1][] = lang_form('input td12','common|about','о');
$form[1][] = lang_form('input td12','common|sports','спорт');
$form[1][] = lang_form('input td12','common|draw_limit','лимит ничьей');
$form[1][] = lang_form('input td12','common|platform','платформа');
$form[1][] = lang_form('input td12','common|currency','валюта');
$form[1][] = lang_form('input td12','common|payment_methods','способы оплаты');
$form[1][] = lang_form('input td12','common|languages','языки');
$form[1][] = lang_form('input td12','common|license','лицензия');
$form[1][] = lang_form('input td12','common|countries','страны');
$form[1][] = lang_form('input td12','common|products','продукты');
$form[1][] = lang_form('input td12','common|live_chat','лайвчат');
$form[1][] = lang_form('input td12','common|live_bet','лайвбет');
$form[1][] = lang_form('input td12','common|address','адрес');

$form[2][] = lang_form('input td12','common|cookie1','Setări Cookie');
$form[2][] = lang_form('input td12','common|cookie2','Noi și partenerii noștri stocăm și/sau accesăm informații pe dispozitivul dvs...');
$form[2][] = lang_form('input td12','common|cookie3','Mai multe informații');
$form[2][] = lang_form('input td12','common|cookie4','Acceptă toate');
$form[2][] = lang_form('input td12','common|cookie5','Modifică setările');
$form[2][] = lang_form('input td12','common|cookie6','Prin clic pe Acceptă toate cookie-urile');
$form[2][] = lang_form('input td12','common|cookie7','Necesare (Mereu Active)');
$form[2][] = lang_form('input td12','common|cookie8','Cookie-uri necesare pentru asigurarea funcționării bune a website-ului.');
$form[2][] = lang_form('input td12','common|cookie9','Marketing');
$form[2][] = lang_form('input td12','common|cookie10','Cookie-uri utilizate pentru a îți afișa publicitate care este mai relevantă pentru tine și interesele tale.');
$form[2][] = lang_form('input td12','common|cookie11','Personalizare');
$form[2][] = lang_form('input td12','common|cookie12','Cookie-uri care permit site-ului să rețină alegerile pe care le faci (cum ar fi limba sau regiunea în care te afli).');
$form[2][] = lang_form('input td12','common|cookie13','Analiză');
$form[2][] = lang_form('input td12','common|cookie14','Cookie-uri care ajută la înțelegerea modului în care funcționează acest site, cum interacționează vizitatorii cu site-ul și dacă există probleme tehnice.');
$form[2][] = lang_form('input td12','common|cookie15','Acceptă toate cookie-urile');
$form[2][] = lang_form('input td12','common|cookie16','Salvează setările');

$form[3][] = lang_form('input td12','common|name2','имя');
$form[3][] = lang_form('input td12','common|email','e-mail адрес');
$form[3][] = lang_form('input td12','common|phone','телефон');
$form[3][] = lang_form('input td12','common|agree','я согласен на обработку персональных данных');
$form[3][] = lang_form('input td12','common|agree1','вы можете получить еще больше сюрпризов');
$form[3][] = lang_form('input td12','common|agree2','я подтверждаю, что мне исполнилось 18 лет');
$form[3][] = lang_form('input td12','common|register','принять предложение и зарегистрироваться');
$form[3][] = lang_form('input td12','common|friends_share','не забудьте рассказать об этом своим друзьям');
$form[3][] = lang_form('input td12','common|noregister_bonus','только бонус, без регистрации');















//$form[0][] = lang_form('input td12','common|site_name','название сайта');
/*
$form[0][] = lang_form('textarea td12','common|script_head','metatag (внутри тега head)');
$form[0][] = lang_form('textarea td12','common|script_body_start','после открывающегося тега body');
$form[0][] = lang_form('textarea td12','common|script_body_end','перед закрывающимся тегом body');
$form[0][] = lang_form('textarea td12','common|txt_head','текст в шапке');
$form[0][] = lang_form('textarea td12','common|txt_index','текст на главной');
$form[0][] = lang_form('input td12','common|info','информация');
$form[0][] = lang_form('textarea td12','common|social','социальные кнопки');
$form[0][] = lang_form('textarea td12','common|txt_footer','текст в подвале');
$form[0][] = lang_form('input td12','common|str_no_page_name','название страницы 404');
$form[0][] = lang_form('textarea td12','common|txt_no_page_text','текст страницы 404');
$form[0][] = lang_form('input td12','common|msg_no_results','нет результатов');
$form[0][] = lang_form('input td4','common|breadcrumb_index','хлебные крошки: на главную');
$form[0][] = lang_form('input td4','common|breadcrumb_separator','хлебные крошки: разделитель');
$form[0][] = lang_form('input td4','common|make_selection','сделайте выбор');
$form[0][] = lang_form('input td4','common|pagination_prev','&#171;');
$form[0][] = lang_form('input td4','common|pagination_next','&#187;');
$form[0][] = lang_form('input td4','common|pagination_count_all','все');

$form[1][] = '<h2>Форма обратной связи</h2>';
$form[1][] = lang_form('input td12','feedback|name','имя');
$form[1][] = lang_form('input td12','feedback|email','еmail');
$form[1][] = lang_form('input td12','feedback|text','сообщение');
$form[1][] = lang_form('input td12','feedback|send','отправить');
$form[1][] = lang_form('input td12','feedback|attach','прикрепить файл');
$form[1][] = lang_form('input td12','feedback|message_is_sent','сообщение отправлено');
$form[1][] = '<h2>Сообщения в формах</h2>';
$form[1][] = lang_form('input td12','validate|no_required_fields','не заполнены обязательные поля');
$form[1][] = lang_form('input td12','validate|short_login','короткий логин');
$form[1][] = lang_form('input td12','validate|not_valid_login','некорректный логин');
$form[1][] = lang_form('input td12','validate|not_valid_email','некорректный email');
$form[1][] = lang_form('input td12','validate|not_valid_password','некорректный пароль');
$form[1][] = lang_form('input td12','validate|not_valid_captcha','некорректный защитный код');
$form[1][] = lang_form('input td12','validate|not_valid_captcha2','отключены скрипты');
$form[1][] = lang_form('input td12','validate|error_email','ошибка при отправке письма');
$form[1][] = lang_form('input td12','validate|no_email','в базе нету такого email');
$form[1][] = lang_form('input td12','validate|duplicate_login','дублирование логина');
$form[1][] = lang_form('input td12','validate|duplicate_email','дублирование email');
$form[1][] = lang_form('input td12','validate|duplicate_phone','дублирование телефона');
$form[1][] = lang_form('input td12','validate|not_match_passwords','пароли не совпадают');

$form[2][] = lang_form('input td12','profile|hello','здравствуйте');
$form[2][] = lang_form('input td12','profile|link','личный кабинет');
$form[2][] = lang_form('input td12','profile|exit','выйти');
$form[2][] = '<h2>Меню личного кабинета</h2>';
$form[2][] = lang_form('input td12','profile|user_edit','личные данные');
$form[2][] = lang_form('input td12','profile|password_change','изменить пароль');
$form[2][] = lang_form('input td12','profile|socials','социальные профили');
$form[2][] = '<h2>Форма авторизации/регистрации/редактирования</h2>';
$form[2][] = lang_form('input td3','profile|email','еmail');
$form[2][] = lang_form('input td3','profile|password','пароль');
$form[2][] = lang_form('input td3','profile|password2','подтв. пароль');
$form[2][] = lang_form('input td3','profile|old_password','старый пароль');
$form[2][] = lang_form('input td3','profile|new_password','новый пароль');
$form[2][] = lang_form('input td3','profile|save','сохранить');
$form[2][] = lang_form('input td3','profile|registration','регистрация');
$form[2][] = lang_form('input td3','profile|enter','войти');
$form[2][] = lang_form('input td3','profile|remember_me','запомнить меня');
$form[2][] = lang_form('input td3','profile|auth','авторизация');
$form[2][] = lang_form('input td3','profile|remind','забыли пароль');
$form[2][] = lang_form('input td12','profile|successful_registration','успешная регистрация');
$form[2][] = lang_form('input td12','profile|successful_auth','успешная авторизация');
$form[2][] = lang_form('input td12','profile|error_auth','ошибка авторизации');
$form[2][] = lang_form('input td12','profile|error_auth_social','ошибка авторизации через соцсеть');
$form[2][] = lang_form('input td12','profile|error_password','неправильный пароль');
$form[2][] = lang_form('input td12','profile|msg_exit','Вы вышли!');
$form[2][] = lang_form('input td12','profile|go_to_profile','перейти в профиль');
$form[2][] = lang_form('input td12','profile|saved_success','Измененения успешно сохранены');
$form[2][] = '<h2>Восстановление пароля</h2>';
$form[2][] = lang_form('input td12','profile|remind_button','отправить письмо по восстановлению пароля');
$form[2][] = lang_form('input td12','profile|successful_remind','отправлено письмо по восстановлению пароля');
$form[2][] = '<h2>Социальные профили</h2>';
$form[2][] = lang_form('input td3','socials|1','Вконтакте');
$form[2][] = lang_form('input td3','socials|2','Facebook');
$form[2][] = lang_form('input td3','socials|3','Google');
$form[2][] = lang_form('input td3','socials|4','Yandex');
$form[2][] = lang_form('input td3','socials|5','Mail.ru');
$form[2][] = lang_form('input td3','socials|on','Подключить');
$form[2][] = lang_form('input td3','socials|off','Отключить');
$form[2][] = lang_form('input td3','socials|confirm_delete','Подтвердить удаление');
$form[2][] = lang_form('input td6','socials|uid_error','Данный социальный профиль уже привязан к другому пользователю');

$form[3][] = lang_form('input td3','shop|catalog','каталог');
$form[3][] = lang_form('input td3','shop|new','новинки');
$form[3][] = lang_form('input td3','shop|brand','производитель');
$form[3][] = lang_form('input td3','shop|article','артикул');
$form[3][] = lang_form('input td3','shop|parameters','параметры');
$form[3][] = lang_form('input td3','shop|price','цена');
$form[3][] = lang_form('input td3','shop|currency','валюта');
$form[3][] = lang_form('input td3','shop|product_random','случайный товар');
$form[3][] = lang_form('input td3','shop|filter_button','искать');
$form[3][] = '<h2>Отзывы</h2>';
$form[3][] = lang_form('input td3','shop|reviews','Отзывы');
$form[3][] = lang_form('input td3','shop|review_add','Оставить отзыв');
$form[3][] = lang_form('input td3','shop|review_name','имя');
$form[3][] = lang_form('input td3','shop|review_email','еmail');
$form[3][] = lang_form('input td3','shop|review_text','сообщение');
$form[3][] = lang_form('input td3','shop|review_send','отправить');
$form[3][] = lang_form('input td12','shop|review_is_sent','отзыв добавлен');

$form[4][] = lang_form('input td3','basket|buy','купить');
$form[4][] = lang_form('input td3','basket|basket','корзина');
$form[4][] = lang_form('input td12','basket|empty','пустая корзина');
$form[4][] = lang_form('input td12','basket|go_basket','перейти в корзину');
$form[4][] = lang_form('input td12','basket|go_next','продолжить покупки');
$form[4][] = lang_form('input td12','basket|product_added','товар добавлен');
$form[4][] = '<h2>Оплата</h2>';
$form[4][] = lang_form('input td12','order|payments','оплата');
$form[4][] = lang_form('input td12','order|pay','оплатить');
$form[4][] = lang_form('input td12','order|paid','оплачен');
$form[4][] = lang_form('input td12','order|not_paid','не плачен');
$form[4][] = lang_form('textarea td12','order|success','успешная оплата');
$form[4][] = lang_form('textarea td12','order|fail','отказ оплаты');

$form[4][] = '<h2>Таблица товаров</h2>';
$form[4][] = lang_form('input td3','basket|product_id','id товара');
$form[4][] = lang_form('input td3','basket|product_name','название товара');
$form[4][] = lang_form('input td3','basket|product_price','цена');
$form[4][] = lang_form('input td3','basket|product_count','количество');
$form[4][] = lang_form('input td3','basket|product_summ','сумма');
$form[4][] = lang_form('input td3','basket|product_cost','стоимость');
$form[4][] = lang_form('input td3','basket|product_delete','удалить');
$form[4][] = lang_form('input td3','basket|total','итого');
$form[4][] = '<h2>Параметры заказа</h2>';
$form[4][] = lang_form('input td3','basket|profile','личные данные');
$form[4][] = lang_form('input td3','basket|delivery','доставка');
$form[4][] = lang_form('input td3','basket|delivery_cost','стоимость доставки');
$form[4][] = lang_form('input td3','basket|comment','коммен к заказу');
$form[4][] = lang_form('input td3','basket|order','оформить заказ');
$form[4][] = '<h2>Статистика заказов</h2>';
$form[4][] = lang_form('input td3','basket|orders','статистика заказов');
$form[4][] = lang_form('input td3','basket|order_name','заказ');
$form[4][] = lang_form('input td3','basket|order_from','от');
$form[4][] = lang_form('input td3','basket|order_status','статус');
$form[4][] = lang_form('input td3','basket|order_date','дата');
$form[4][] = lang_form('input td3','basket|view_order','просмотр заказа');

$form[5][] = 'Полное описание можно найти на странице <a target="_balnk" href="http://help.yandex.ru/partnermarket/shop.xml">http://help.yandex.ru/partnermarket/shop.xml</a><br /><br />';
$form[5][] = lang_form('input td12','market|name','Короткое название магазина');
$form[5][] = lang_form('input td12','market|company','Полное наименование компании');
$form[5][] = lang_form('input td12','market|currency','Валюта магазина');

$form[6][] = '<h2>Основной шаблон автоматического письма</h2>';
$form[6][] = lang_form('textarea td12','common|letter_top','Текст в шапке письма');
$form[6][] = lang_form('textarea td12','common|letter_footer','Текст в подвале письма');
$form[6][] = '<h2>Основной шаблон письма рассылки</h2>';
$form[6][] = lang_form('textarea td12','subscribe|top','Текст в шапке рассылки');
$form[6][] = lang_form('textarea td12','subscribe|bottom','Текст в подвале рассылки');
$form[6][] = lang_form('input td8','subscribe|letter_failure_str','Если вы хотите отписаться от рассылки нажмите на');
$form[6][] = lang_form('input td4','subscribe|letter_failure_link','ссылку');
$form[6][] = '<h2>Подписка</h2>';
$form[6][] = lang_form('input td12','subscribe|on_button','Подписаться');
$form[6][] = lang_form('input td12','subscribe|on_success','Вы успешно подписаны');
$form[6][] = lang_form('input td12','subscribe|failure_text','Подтвердите, что хотите отписаться');
$form[6][] = lang_form('input td12','subscribe|failure_button','Отписаться');
$form[6][] = lang_form('input td12','subscribe|failure_success','Вы отписаны');

$form[7][] = lang_form('input td3','calendar|year','год');
$form[7][] = lang_form('input td3','calendar|y','г.');
$form[7][] = lang_form('input td3','calendar|month','месяц');
$form[7][] = lang_form('input td3','calendar|m','m.');
$form[7][] = lang_form('input td3','calendar|day','день');
$form[7][] = lang_form('input td3','calendar|d','д.');
$form[7][] = '<h2>Полные названия месяцев</h2>';
$form[7][] = lang_form('input td3','calendar|month_01','январь');
$form[7][] = lang_form('input td3','calendar|month_02','февраль');
$form[7][] = lang_form('input td3','calendar|month_03','март');
$form[7][] = lang_form('input td3','calendar|month_04','апрель');
$form[7][] = lang_form('input td3','calendar|month_05','май');
$form[7][] = lang_form('input td3','calendar|month_06','июнь');
$form[7][] = lang_form('input td3','calendar|month_07','июль');
$form[7][] = lang_form('input td3','calendar|month_08','август');
$form[7][] = lang_form('input td3','calendar|month_09','сентябрь');
$form[7][] = lang_form('input td3','calendar|month_10','октябрь');
$form[7][] = lang_form('input td3','calendar|month_11','ноябрь');
$form[7][] = lang_form('input td3','calendar|month_12','декабрь');
$form[7][] = '<h2>Полные названия месяцев в родительном падеже</h2>';
$form[7][] = lang_form('input td3','calendar|month2_01','января');
$form[7][] = lang_form('input td3','calendar|month2_02','февраля');
$form[7][] = lang_form('input td3','calendar|month2_03','марта');
$form[7][] = lang_form('input td3','calendar|month2_04','апреля');
$form[7][] = lang_form('input td3','calendar|month2_05','мая');
$form[7][] = lang_form('input td3','calendar|month2_06','июня');
$form[7][] = lang_form('input td3','calendar|month2_07','июля');
$form[7][] = lang_form('input td3','calendar|month2_08','августа');
$form[7][] = lang_form('input td3','calendar|month2_09','сентября');
$form[7][] = lang_form('input td3','calendar|month2_10','октября');
$form[7][] = lang_form('input td3','calendar|month2_11','ноября');
$form[7][] = lang_form('input td3','calendar|month2_12','декабря');
$form[7][] = '<h2>Короткие названия месяцев</h2>';
$form[7][] = lang_form('input td3','calendar|mth_01','янв');
$form[7][] = lang_form('input td3','calendar|mth_02','фев');
$form[7][] = lang_form('input td3','calendar|mth_03','мар');
$form[7][] = lang_form('input td3','calendar|mth_04','апр');
$form[7][] = lang_form('input td3','calendar|mth_05','май');
$form[7][] = lang_form('input td3','calendar|mth_06','июн');
$form[7][] = lang_form('input td3','calendar|mth_07','июл');
$form[7][] = lang_form('input td3','calendar|mth_08','авг');
$form[7][] = lang_form('input td3','calendar|mth_09','сен');
$form[7][] = lang_form('input td3','calendar|mth_10','окт');
$form[7][] = lang_form('input td3','calendar|mth_11','ноя');
$form[7][] = lang_form('input td3','calendar|mth_12','дек');
$form[7][] = '<h2>Полные дней недели</h2>';
$form[7][] = lang_form('input td3','calendar|day_1','понедельник');
$form[7][] = lang_form('input td3','calendar|day_2','вторник');
$form[7][] = lang_form('input td3','calendar|day_3','среда');
$form[7][] = lang_form('input td3','calendar|day_4','четверг');
$form[7][] = lang_form('input td3','calendar|day_5','пятница');
$form[7][] = lang_form('input td3','calendar|day_6','субота');
$form[7][] = lang_form('input td3','calendar|day_7','воскресенье');
$form[7][] = '<h2>Короткие дней недели</h2>';
$form[7][] = lang_form('input td3','calendar|d_1','пн');
$form[7][] = lang_form('input td3','calendar|d_2','вт');
$form[7][] = lang_form('input td3','calendar|d_3','ср');
$form[7][] = lang_form('input td3','calendar|d_4','чт');
$form[7][] = lang_form('input td3','calendar|d_5','пт');
$form[7][] = lang_form('input td3','calendar|d_6','сб');
$form[7][] = lang_form('input td3','calendar|d_7','вс');
*/

/*
$form[8][] = array('yandex_map','',@$lang['map']);
html_sources('footer','yandex_map');
*/
$form[8][] = array('google_map','',@$lang['map']);
html_sources('footer','google_map');


function lang_form($type,$key,$name) {
	global $lang, $lang_base;
	$key = explode('|',$key);
	// Auto-fill empty fields
	if (/*@$_GET['fuel'] AND */!isset($lang[$key[0]][$key[1]])) {
		if (!empty($_GET['id']) && $_GET['id'] === 'new' && isset($lang_base[$key[0]]) && is_array($lang_base[$key[0]]) && array_key_exists($key[1], $lang_base[$key[0]])) {
			$lang[$key[0]][$key[1]] = $lang_base[$key[0]][$key[1]];
		} else {
			$lang[$key[0]][$key[1]] = $name;
		}
	}
	return array ($type,'dictionary['.$key[0].']['.$key[1].']',array(
		'name'=>$name.' {'.$key[0].'|'.$key[1].'}',
		'title'=>$key[0].'|'.$key[1],
		//v1.4.20 - значение в поле
		'value'=>$lang[$key[0]][$key[1]]
	));
}