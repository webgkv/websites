<?php

// FORM - ЗАГРУЗКА ФОРМЫ РЕДАКТИРОВАНИЯ
/*
 * v1.4.0 - html_render в админке
 */

if ($post = mysql_select("SELECT * FROM `".$module['table']."` WHERE id = '".intval($get['id'])."'",'row')) {
	foreach ($filter as $f) {
		if (isset($post[$f[0]])) $get[$f[0]] = $post[$f[0]];
	}
	//создание масива $post[depend]
	if (isset($config['depend'][$module['table']])) {
		foreach ($config['depend'][$module['table']] as $k=>$v) {
			$post['depend'][$v] =  mysql_select("SELECT parent FROM `$v` WHERE child = '".intval($get['id'])."'",'rows');
			/*$result = mysql_query("SELECT parent FROM `$v` WHERE child = '".intval($get['id'])."'");
			while ($q = mysql_fetch_assoc($result)) {
				$post['depend'][$v][] = $q['parent'];
			}*/
		}
	}
//значения по умолчанию для новой записи
} else {
	$post = $get;
	$post['date'] = $config['datetime'];
	$post['rank'] = $post['seo'] = $post['change'] = $post['display'] = $post['indexing'] = 1;
	$post['user'] = $user['id'];
}
require_once(ROOT_DIR.'admin/modules/'.$get['m'].'.php');
multilingual();

// If canonical i18n already exists, use it as the source of truth for Common tab.
// This avoids drift between base tables (guides/games/...) and content_i18n for EN.
if (!empty($get['u']) && (string)$get['u'] === 'form' && (int)($get['id'] ?? 0) > 0) {
	$activeProfileTab = !isset($get['ftab']) || (int)$get['ftab'] === 1;
	$preload_tables = array('guides', 'games', 'casino_articles', 'promo', 'blog');
	$is_author_form = ((string)($get['m'] ?? '') === 'authors' || (string)($module['table'] ?? '') === 'site_authors');
	// Authors: site_authors.bio is canonical; content_i18n must not overwrite the form from a stale short row.
	if ($activeProfileTab && !empty($module['table']) && !$is_author_form && in_array((string)$module['table'], $preload_tables, true)) {
		if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			require_once(ROOT_DIR . 'admin/modules/_i18n.php');

			$eid = (int)$get['id'];
			$preload_entity = admin_i18n_entity_key((string)($get['m'] ?? ''), (string)$module['table']);
			$canonical_lang_id = 1;
			if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
				$vr = mysql_select("SELECT value FROM variables WHERE `key`='translation_settings' LIMIT 1", 'row');
				if ($vr && $vr['value'] !== '') {
					$dec = json_decode($vr['value'], true);
					if (is_array($dec) && isset($dec['source_lang_id'])) $canonical_lang_id = (int)$dec['source_lang_id'];
				}
			}

			if ($canonical_lang_id > 0 && $preload_entity !== '') {
				$t = admin_i18n_get($preload_entity, $eid, (int)$canonical_lang_id);
				if (is_array($t)) {
					$hasFilled = (
						isset($t['content']) && trim((string)$t['content']) !== ''
						|| isset($t['url']) && trim((string)$t['url']) !== ''
						|| isset($t['name']) && trim((string)$t['name']) !== ''
						|| isset($t['title']) && trim((string)$t['title']) !== ''
						|| isset($t['description']) && trim((string)$t['description']) !== ''
					);
					if ($hasFilled) {
						if (isset($t['url']) && trim((string)$t['url']) !== '' && array_key_exists('url', $post)) $post['url'] = (string)$t['url'];
						if (isset($t['name']) && trim((string)$t['name']) !== '' && array_key_exists('name', $post)) $post['name'] = (string)$t['name'];
						if (isset($t['title']) && trim((string)$t['title']) !== '' && array_key_exists('title', $post)) $post['title'] = (string)$t['title'];
						if (isset($t['description']) && trim((string)$t['description']) !== '' && array_key_exists('description', $post)) $post['description'] = (string)$t['description'];
						if (isset($t['description']) && trim((string)$t['description']) !== '' && array_key_exists('name_2', $post)) $post['name_2'] = (string)$t['description'];
						if (isset($t['content']) && trim((string)$t['content']) !== '' && array_key_exists('text', $post)) $post['text'] = (string)$t['content'];
					}
				}
			}
		}
	}
}

// Full page with layout: inline=1 (e.g. Review from Translations Monitor) OR direct navigation (e.g. language dropdown) so user gets sidebar/CSS
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!empty($get['inline']) || !$is_ajax) {
	ob_start();
	require_once(ROOT_DIR . $config['style'].'/includes/layouts/form_inline.php');
	$form_html = ob_get_clean();
	// Full-page edit: list/filters belong on the index view only, not below the form.
	unset($table, $filter, $query);
	$content = $form_html;
	$page_name = isset($page_name) ? $page_name : 'Edit';
	if (!empty($get['embed']) && (string)$get['embed'] === '1') {
		require_once(ROOT_DIR . $config['style'].'/includes/layouts/_template_embed.php');
	} else {
		require_once(ROOT_DIR . $config['style'].'/includes/layouts/_template.php');
	}
	exit;
}

require_once(ROOT_DIR . $config['style'].'/includes/layouts/form.php');
