<?php

$a18n['img_filename'] = 'filename';
$a18n['img_alt'] = 'alt';
$a18n['img_title'] = 'title';

$tags = mysql_select("SELECT id,name,category from blog_tags", 'rows_id');
$category = mysql_select("SELECT id,name from blog_category", 'array');

// List sort uses `position` only when the column exists (migration may not be applied yet).
$blog_has_position = @mysql_select("SHOW COLUMNS FROM `blog` LIKE 'position'", 'num_rows') > 0;

$filter[] = array('search');
$where = '';
$search = isset($get['search']) ? trim((string)$get['search']) : '';
$search_id_raw = isset($get['search_id']) ? trim((string)$get['search_id']) : '';
$search_id = ($search_id_raw !== '' && ctype_digit($search_id_raw)) ? (int)$search_id_raw : 0;
if ($search !== '') {
	$search_l = mysql_res(strtolower($search));
	$where_search = "(LOWER(name) LIKE '%" . $search_l . "%' OR LOWER(name_2) LIKE '%" . $search_l . "%' OR LOWER(url) LIKE '%" . $search_l . "%')";
	if (ctype_digit($search)) {
		$where_search .= " OR id=" . (int)$search;
	}
	$where .= " AND (" . $where_search . ")";
}
if ($search_id > 0) {
	$where .= " AND id=" . (int)$search_id;
}
$filter[] = '<div class="form-group col-xl-2"><input class="form-control" type="number" min="1" step="1" name="search_id" value="' . htmlspecialchars($search_id_raw, ENT_QUOTES, 'UTF-8') . '" placeholder="ID"></div>';
$filter[] = '<div class="form-group col-xl-2"><button type="submit" class="btn btn-sm btn-primary">Search</button></div>';

$query = "
	SELECT * FROM blog
	WHERE 1 $where
";

$table = array(
	// Newest first: highest id on page 1 (same idea as casinos list).
	'id' => 'id:desc name date',
	'img' => 'img',
	'name' => '',
	'category' => $category,
	'date' => 'date',
	'top' => 'boolean',
	'display' => 'boolean'
);

$tabs = array(
	1 => a18n('common'),
	2 => 'main image',
	3 => a18n('images'),
	4 => 'tags'
);

$is_new = (!isset($get['id']) || $get['id'] === '' || $get['id'] === 'new');

if ($is_new) {
	$form[1][] = array('input td6', 'name');
}
$form[1][] = array('input td4', 'date');
if ($blog_has_position) {
	$form[1][] = array('input td2', 'position');
}
$form[1][] = array('checkbox td1', 'top');
$form[1][] = array('checkbox td1', 'display');

$form[1][] = array('input td6', 'name_2');
$form[1][] = array('select td3', 'category', array('value' => array(true, $category, '')));
$authors_list = @mysql_select("SELECT id, name FROM site_authors WHERE display=1 ORDER BY name ASC", 'array') ?: array();
$form[1][] = array('select td3', 'author_id', array('name' => 'Author (E-E-A-T)', 'value' => array(true, $authors_list, '--- Default ---')));

if ($is_new) {
	$form[1][] = array('tinymce td12', 'text', array('attr' => 'style="height:500px"'));
	$form[1][] = array('seo', 'seo url title description');
}

$form[2][] = array('input td4', 'img_filename');
$form[2][] = array('input td4', 'img_alt');
$form[2][] = array('input td4', 'img_title');
$form[2][] = array('file td6', 'img', array('sizes' => array('' => '')));

$form[3][] = array('file_multi', 'imgs', array(
	'name' => a18n('help_imgs'),
	'sizes' => array('' => '')
));

// Replace {{BLOG_ID}} / {{POST_ID}} placeholders so images render in TinyMCE during edit
if (isset($get['u']) && ($get['u'] === 'form' || $get['u'] === 'edit') && !empty($get['id']) && $get['id'] !== 'new' && isset($post['text'])) {
	$post['text'] = str_replace(array('{{BLOG_ID}}', '{{POST_ID}}', '{{ID}}'), (string)(int)$get['id'], (string)$post['text']);
}

// Translations (content_i18n) — directly on Tab 1
if (isset($get['u']) && $get['u'] === 'form' && isset($get['id']) && $get['id'] !== 'new' && ($bid = (int)$get['id']) > 0) {
	require_once(ROOT_DIR . 'admin/modules/_i18n.php');
	$i18n_lang_id = isset($get['i18n_lang_id']) ? (int)$get['i18n_lang_id'] : 0;
	// Full-page clear (from "Del translate" link) so form values are reloaded reliably.
	if (!empty($get['i18n_clear'])) {
		$clear_lang_id = $i18n_lang_id > 0 ? $i18n_lang_id : 0;
		$res = admin_i18n_clear('blog', $bid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . (int)$bid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id;
		header('Location: ' . $redirect);
		exit;
	}
	if (!empty($_POST['i18n_clear'])) {
		$clear_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$res = admin_i18n_clear('blog', $bid, $clear_lang_id);
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . (int)$bid . '&ftab=1&i18n_lang_id=' . (int)$clear_lang_id;
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}

	if (!empty($_POST['i18n_save'])) {
		$save_lang_id = isset($_POST['i18n_lang_id']) ? (int)$_POST['i18n_lang_id'] : $i18n_lang_id;
		$payload = array(
			'url' => isset($_POST['i18n_url']) ? trim((string)$_POST['i18n_url'], '/') : '',
			'name' => isset($_POST['i18n_name']) ? (string)$_POST['i18n_name'] : '',
			'title' => isset($_POST['i18n_title']) ? (string)$_POST['i18n_title'] : '',
			'description' => isset($_POST['i18n_description']) ? (string)$_POST['i18n_description'] : '',
			'content' => isset($_POST['i18n_content']) ? (string)$_POST['i18n_content'] : '',
			'status' => isset($_POST['i18n_status']) ? (string)$_POST['i18n_status'] : 'draft',
		);
		$res = admin_i18n_save('blog', $bid, $save_lang_id, $payload);
		if (!empty($res['ok'])) {
			admin_i18n_sync_canonical_row_to_base_table('blog', $bid, $save_lang_id);
		}
		$_SESSION['admin_flash_success'] = $res['ok'] ? $res['message'] : '';
		if (!$res['ok']) $_SESSION['admin_flash_error'] = $res['message'];
		$redirect = '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . (int)$bid . '&ftab=1&i18n_lang_id=' . (int)$save_lang_id;
		// When saving via admin/template2 iframe submit, return JSON redirect for instant reload.
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('redirect' => $redirect, 'error' => 0));
			exit;
		}
		header('Location: ' . $redirect);
		exit;
	}
	$base_url = '/admin.php?m=content&tab=blog&stab=blog&u=form&id=' . (int)$bid . '&ftab=1';
	$defaults = array(
		'url' => isset($post['url']) ? (string)$post['url'] : '',
		'name' => isset($post['name']) ? (string)$post['name'] : '',
		'title' => isset($post['title']) ? (string)$post['title'] : '',
		'description' => isset($post['description']) ? (string)$post['description'] : '',
		'content' => isset($post['text']) ? (string)$post['text'] : '',
	);
	$form[1][] = admin_i18n_render_form('blog', $bid, $i18n_lang_id, $base_url, $defaults);
}

foreach ($tags as $tagk => $tagv) {
	$form[4][] = '<div class="form-group input col-xl-3 tags cat-' . $tagv['category'] . ((isset($post['category']) && $post['category'] != $tagv['category']) ? ' d-none' : '') . '">
                  <div class="form-check">
                    <input type="hidden" name="tags[' . $tagk . ']" value="' . (((isset($post['tag1']) && $post['tag1'] == $tagk) || (isset($post['tag2']) && $post['tag2'] == $tagk) || (isset($post['tag3']) && $post['tag3'] == $tagk) || (isset($post['tag4']) && $post['tag4'] == $tagk)) ? '1' : '0') . '">
                    <label class="form-check-label">
                      <input class="form-check-input" type="checkbox" name="tags[' . $tagk . ']"' . (((isset($post['tag1']) && $post['tag1'] == $tagk) || (isset($post['tag2']) && $post['tag2'] == $tagk) || (isset($post['tag3']) && $post['tag3'] == $tagk) || (isset($post['tag4']) && $post['tag4'] == $tagk)) ? ' checked' : '') . '>
                      <span>' . $tagv['name'] . '</span>
                    </label>
                  </div>
                </div>';
}

// When editing: sync tags and allow image filename rename
if ($get['u'] == 'edit') {

	$post['tag1'] = $post['tag2'] = $post['tag3'] = $post['tag4'] = 0;
	$i = 1;
	if (!empty($post['tags']) && is_array($post['tags'])) {
	foreach ($post['tags'] as $k => $v) {
		if ($v != '0') {
			$post['tag' . $i] = $k;
			$i++;
		}
	}
	unset($post['tags']);
	}

	if (!empty($post['imgs']) && is_array($post['imgs'])) {
	foreach ($post['imgs'] as $k => $v) {
		if ($v['filename']) {
			$ext = preg_replace('#^.*(\.[^\.]+)$#iu', '$1', $v['file']);
			$filename = $v['filename'] . $ext;
			if ($filename != $v['file']) {
				$path = ROOT_DIR . 'files/blog/' . $get['id'] . '/imgs/' . $k . '/';
				rename($path . $v['file'], $path . $filename);
				rename($path . 'a-' . $v['file'], $path . 'a-' . $filename);
			foreach ($form[3][0][2]['sizes'] as $k2 => $v2)
					if ($k2) rename($path . $k2 . $v['file'], $path . $k2 . $filename);
		}
	}
	}
}
}

function event_change_blog($q, $old = false) {
	global $get, $post;
	global $form;
	$img = isset($post['img']) ? trim((string)$post['img']) : '';
	if ($img === '') {
		return;
	}
	// Media library paths are saved in form_file(); legacy rename would corrupt them (e.g. → ".webp").
	if (strpos($img, '/') !== false) {
		if (!function_exists('media_library_is_pickable_image_path')) {
			require_once ROOT_DIR . 'functions/media_library.php';
		}
		if (media_library_is_pickable_image_path($img)) {
			return;
		}
	}
	$img_filename = isset($post['img_filename']) ? trim((string)$post['img_filename']) : '';
	if ($img_filename === '') {
		return;
	}
	$ext = preg_replace('#^.*(\.[^\.]+)$#iu', '$1', $img);
	if ($img === $img_filename . $ext) {
		return;
	}
	mysql_fn('update', 'blog', array('id' => $post['id'], 'img' => $img_filename . $ext));
	$path = ROOT_DIR . 'files/blog/' . $post['id'] . '/img/';
	rename($path . $post['img'], $path . $post['img_filename'] . $ext);
	rename($path . 'a-' . $post['img'], $path . 'a-' . $post['img_filename'] . $ext);
	foreach ($form[2][3][2]['sizes'] as $k2 => $v2) {
		if ($k2) {
			rename($path . $k2 . $post['img'], $path . $k2 . $post['img_filename'] . $ext);
		}
	}
	$post['img'] = $post['img_filename'] . $ext;
}

$form[4][] = "
<script>

  $('body').on('change','select[name=category]',function(){
    var cat=$(this).val();
    $('.tags').each(function(i,obj){
      if($(obj).hasClass('cat-'+cat)) {
        $(obj).removeClass('d-none');
      } else {
        $(obj).find('input[type=checkbox]').prop('checked',false);
        $(obj).find('input[type=hidden]').val('0');
        $(obj).addClass('d-none');
      }
    });
  });

  $('body').on('change','.tags',function(){
    if($('.tags input[type=checkbox]:checked').length>4) {
      $(this).find('input[type=checkbox]').prop('checked',false);
      $(this).find('input[type=hidden]').val('0');
    } else {
      if($(this).find('input[type=checkbox]').is(':checked')) {
        $(this).find('input[type=hidden]').val('1');
      } else {
        $(this).find('input[type=hidden]').val('0');
      }
    }
  });

</script>";
