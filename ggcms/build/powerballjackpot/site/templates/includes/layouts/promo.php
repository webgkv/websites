<?php
$langid = isset($abc['langid']) ? $abc['langid'] : '';
$lang_prefix = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';
$promo_base = preg_replace('#/+#', '/', ($lang_prefix !== '' ? '/' . $lang_prefix . '/promo/' : '/promo/'));
if (function_exists('site_section_public_base')) {
	$promo_base = site_section_public_base('promo', $abc);
}

$promo_title = (string)i18n('common|promo_title');
$promo_cat_active = (string)i18n('common|promo_cat_active');
$promo_cat_archive = (string)i18n('common|promo_cat_archive');
$promo_ended = (string)i18n('common|promo_ended');
$promo_active_until = (string)i18n('common|promo_active_until');
$read_more = (string)i18n('common|read_more');
if ($read_more === '') {
	$read_more = 'Read more';
}

$list_cat = isset($abc['promo_list_cat']) ? (string)$abc['promo_list_cat'] : 'active';
$archive_href = $promo_base . '?cat=archive';
$active_href = $promo_base;
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5 promo-section">
	<div class="container">
		<?php if (!empty($abc['promo_single'])) : ?>
			<?php
			$article = $abc['promo_single'];
			$is_ended = !empty($article['ended']);
			?>
			<?php if ($is_ended && $promo_ended !== '') : ?>
				<p class="promo-badge-ended mb-3"><?= htmlspecialchars($promo_ended) ?></p>
			<?php endif; ?>
			<?php if (function_exists('site_render_author_byline')) {
				echo site_render_author_byline($abc, array('date' => $article['date'] ?? ($article['updated_at'] ?? '')));
			} ?>
			<div class="text page-content-from-db about_content promo-article-content">
				<?php
				$article_html = isset($article['text']) ? (string)$article['text'] : '';
				if (!function_exists('content_unwrap_exclude_tags')) {
					require_once ROOT_DIR . 'functions/content_exclude_tags.php';
				}
				if (function_exists('promo_render_body_html')) {
					echo promo_render_body_html($article_html);
				} elseif (function_exists('content_unwrap_exclude_tags')) {
					$article_html = content_unwrap_exclude_tags($article_html);
					echo function_exists('site_seo_clean_content') ? site_seo_clean_content($article_html) : $article_html;
				} else {
					echo $article_html;
				}
				?>
			</div>
		<?php elseif (!empty($abc['promo_list']) || !empty($abc['promo_pagination'])) : ?>
			<h1><?= htmlspecialchars($abc['page']['name' . $langid] ?? $abc['page']['name'] ?? ($promo_title !== '' ? $promo_title : 'Promo')) ?></h1>
			<nav class="guide-filters mb-4 promo-filters" aria-label="Promo categories">
				<a href="<?= htmlspecialchars($active_href) ?>" class="guide-filter-btn<?= $list_cat === 'active' ? ' active' : '' ?>"><?= htmlspecialchars($promo_cat_active !== '' ? $promo_cat_active : 'Active') ?></a>
				<a href="<?= htmlspecialchars($archive_href) ?>" class="guide-filter-btn<?= $list_cat === 'archive' ? ' active' : '' ?>"><?= htmlspecialchars($promo_cat_archive !== '' ? $promo_cat_archive : 'Archive') ?></a>
			</nav>
			<div class="row">
				<?php
				$list = isset($abc['promo_list']) ? $abc['promo_list'] : array();
				foreach ($list as $p) :
					$slug = trim((string)($p['url'] ?? ''), '/');
					$link = preg_replace('#/+#', '/', $promo_base . $slug . '/');
					$ended = function_exists('promo_is_expired') ? promo_is_expired($p) : false;
					if (!$ended && isset($p['category']) && (string)$p['category'] === 'archive') {
						$ended = true;
					}
					$img_src = '';
					if (!empty($p['img'])) {
						$img_src = get_img('promo', $p, 'img');
						if ($img_src && strpos($img_src, 'no_img') === false) {
							$img_src = htmlspecialchars($img_src);
						} else {
							$img_src = '';
						}
					}
				?>
					<div class="col-md-6 col-lg-4 mb-4">
						<a href="<?= $link ?>" class="guide-card card h-100 text-decoration-none promo-card<?= $ended ? ' promo-card--ended' : '' ?>">
							<?php if ($img_src) : ?>
								<img src="<?= $img_src ?>" class="card-img-top" alt="">
							<?php endif; ?>
							<div class="card-body">
								<?php if ($ended && $promo_ended !== '') : ?>
									<span class="promo-badge-ended"><?= htmlspecialchars($promo_ended) ?></span>
								<?php elseif (!$ended && empty($p['promo_unlimited']) && !empty($p['date_end']) && $p['date_end'] !== '0000-00-00 00:00:00' && $promo_active_until !== '') : ?>
									<span class="promo-badge-until"><?= htmlspecialchars($promo_active_until) ?> <?= htmlspecialchars(date('Y-m-d', strtotime((string)$p['date_end']))) ?></span>
								<?php endif; ?>
								<h5 class="card-title"><?= htmlspecialchars((string)$p['name']) ?></h5>
								<?php if (!empty($p['name_2'])) : ?>
									<p class="card-text guide-card-desc"><?= htmlspecialchars((string)$p['name_2']) ?></p>
								<?php endif; ?>
								<span class="guide-card-link">&rarr; <?= htmlspecialchars($read_more) ?></span>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if (!empty($abc['promo_pagination']) && $abc['promo_pagination']['num_rows'] > $abc['promo_pagination']['limit']) : ?>
				<div class="mt-4">
					<?= html_render('pagination/default_front', $abc['promo_pagination']) ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<h1><?= htmlspecialchars($promo_title !== '' ? $promo_title : 'Promo') ?></h1>
			<nav class="guide-filters mb-4 promo-filters" aria-label="Promo categories">
				<a href="<?= htmlspecialchars($active_href) ?>" class="guide-filter-btn<?= $list_cat === 'active' ? ' active' : '' ?>"><?= htmlspecialchars($promo_cat_active !== '' ? $promo_cat_active : 'Active') ?></a>
				<a href="<?= htmlspecialchars($archive_href) ?>" class="guide-filter-btn<?= $list_cat === 'archive' ? ' active' : '' ?>"><?= htmlspecialchars($promo_cat_archive !== '' ? $promo_cat_archive : 'Archive') ?></a>
			</nav>
			<p class="lead"><?= htmlspecialchars((string)i18n('common|msg_no_results')) ?></p>
		<?php endif; ?>
	</div>
</section>
<?php if (!empty($abc['promo_single']['id'])) : ?>
<script>
(function () {
	var id = <?= (int)$abc['promo_single']['id'] ?>;
	if (!id) return;
	var key = <?= json_encode(function_exists('promo_front_seen_storage_key') ? promo_front_seen_storage_key() : 'promo_seen_v1', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
	try {
		var seen = JSON.parse(localStorage.getItem(key) || '{}');
		if (!seen || typeof seen !== 'object') seen = {};
		seen[String(id)] = Date.now();
		localStorage.setItem(key, JSON.stringify(seen));
	} catch (e) { /* ignore */ }
})();
</script>
<?php endif; ?>
