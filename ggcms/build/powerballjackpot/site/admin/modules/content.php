<?php
/**
 * Content section: tabs = site sections (§3.4.2). Sub-tabs for Blog (Articles, Categories, Tags) and Casinos (Casinos, Tags) — like Drop Monitor candidates.
 */

$page_name = 'Content';
$tab = isset($get['tab']) ? $get['tab'] : 'guides';
$content_sub_tabs = '';
if (!isset($content_media_link_added)) {
	$content_media_link_added = true;
	$content_top_link = '<p class="mb-2"><a href="/admin.php?m=media" class="btn btn-sm btn-outline-primary"><i data-feather="image" class="mr-1"></i> Media library</a></p>';
} else {
	$content_top_link = '';
}

switch ($tab) {
	case 'casinos':
		require_once(ROOT_DIR . 'admin/modules/casino_articles.php');
		if (!isset($content)) $content = '';
		break;
	case 'blog':
		$blog_stabs = array('blog'=>'Articles', 'blog_category'=>'Categories', 'blog_tags'=>'Tags');
		$stab = isset($get['stab']) ? $get['stab'] : 'blog';
		if (!isset($blog_stabs[$stab])) $stab = 'blog';
		$content_sub_tabs = '<ul class="nav nav-tabs nav-tabs-sm mb-2" role="tablist" style="border-bottom:1px solid #dee2e6">';
		foreach ($blog_stabs as $s => $label) {
			$content_sub_tabs .= '<li class="nav-item"><a class="nav-link'.($stab===$s?' active':'').'" href="/admin.php?m=content&tab=blog&stab='.urlencode($s).'">'.htmlspecialchars($label).'</a></li>';
		}
		$content_sub_tabs .= '</ul>';
		require_once(ROOT_DIR.'admin/modules/'.$stab.'.php');
		break;
	case 'guides':
		require_once(ROOT_DIR . 'admin/modules/guides.php');
		if (!isset($content)) {
			$content = '';
		}
		break;
	case 'promo':
		require_once(ROOT_DIR . 'admin/modules/promo.php');
		if (!isset($content)) {
			$content = '';
		}
		break;
	case 'download':
		// Find page with URL "download" (schema has url, url2, url3 — no url1)
		$download_page = mysql_select("
			SELECT id, name FROM `pages`
			WHERE level = 1 AND (menu = 1 OR menu = '1')
			AND (url = 'download' OR url2 = 'download' OR url3 = 'download')
			LIMIT 1
		", 'row');
		$content = '<div class="card"><div class="card-body">';
		$content .= '<h5 class="mb-3">Download page</h5>';
		if ($download_page) {
			$content .= '<p class="mb-2">Edit the site page <strong>Download</strong> (name, text, SEO, images).</p>';
			$content .= '<a href="/admin.php?m=pages&u=form&id=' . (int)$download_page['id'] . '" class="btn btn-primary mb-3">Edit Download page</a>';
		} else {
			$content .= '<p class="mb-2 text-muted">No page with URL <code>download</code> found.</p>';
			$content .= '<a href="/admin.php?m=pages" class="btn btn-outline-primary">Open Pages</a>';
		}
		$content .= '</div></div>';
		break;
	case 'predictor':
		$predictor_page = mysql_select("
			SELECT id, name FROM `pages`
			WHERE level = 1 AND (menu = 1 OR menu = '1')
			AND (url = 'predictor' OR url2 = 'predictor' OR url3 = 'predictor')
			LIMIT 1
		", 'row');
		$content = '<div class="card"><div class="card-body">';
		$content .= '<h5 class="mb-3">Predictor page</h5>';
		if ($predictor_page) {
			$content .= '<p class="mb-2">Edit the site page <strong>Predictor</strong> (name, text, SEO, images).</p>';
			$content .= '<a href="/admin.php?m=pages&u=form&id=' . (int)$predictor_page['id'] . '" class="btn btn-primary mb-3">Edit Predictor page</a>';
		} else {
			$content .= '<p class="mb-2 text-muted">No page with URL <code>predictor</code> found.</p>';
			$content .= '<a href="/admin.php?m=pages" class="btn btn-outline-primary">Open Pages</a>';
		}
		$content .= '</div></div>';
		break;
	case 'demo':
		$content = '<div class="card"><div class="card-body"><p class="mb-0"><strong>Demo.</strong> Content for Demo section. To be added.</p></div></div>';
		break;
	case 'games':
		$games_stabs = array('games' => 'Games', 'games_categories' => 'Categories');
		$gstab = isset($get['stab']) ? $get['stab'] : 'games';
		if (!isset($games_stabs[$gstab])) {
			$gstab = 'games';
		}
		$content_sub_tabs = '<ul class="nav nav-tabs nav-tabs-sm mb-2" role="tablist" style="border-bottom:1px solid #dee2e6">';
		foreach ($games_stabs as $s => $label) {
			$content_sub_tabs .= '<li class="nav-item"><a class="nav-link' . ($gstab === $s ? ' active' : '') . '" href="/admin.php?m=content&tab=games&stab=' . urlencode($s) . '">' . htmlspecialchars($label) . '</a></li>';
		}
		$content_sub_tabs .= '</ul>';
		require_once(ROOT_DIR . 'admin/modules/' . $gstab . '.php');
		break;
	default:
		$content = '<div class="card"><div class="card-body"><p class="mb-0">Content section.</p></div></div>';
}
