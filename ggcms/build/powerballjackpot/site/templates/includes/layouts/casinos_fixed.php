<?php
// Casinos: list (9 cards + pagination) or single casino article (fixed template)
$langid = isset($abc['langid']) ? $abc['langid'] : '';
$lang_prefix = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';

$casinos_base = function_exists('site_section_public_base')
	? site_section_public_base('casinos', $abc)
	: preg_replace('#/+#', '/', ($lang_prefix !== '' ? '/' . $lang_prefix . '/casinos/' : '/casinos/'));

// Fallback: `common|read_more` may be missing for some language dictionaries.
$read_more = (string)i18n('common|read_more');
if ($read_more === '' || trim($read_more) === 'Read more') {
	if ($lang_prefix === 'de') $read_more = 'Mehr lesen';
}

require_once(ROOT_DIR . 'functions/cta_inject.php');
require_once(ROOT_DIR . 'functions/author_func.php');
$offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
$buttons_html = site_cta_buttons_html($offer_path);
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
	<div class="container">
		<?php if (!empty($abc['casino_single'])) : ?>
			<?php $article = $abc['casino_single']; ?>
			<?php if (function_exists('site_render_author_byline')) echo site_render_author_byline($abc, array('date' => $article['date'] ?? ($article['updated_at'] ?? ''))); ?>
			<div class="text page-content-from-db about_content casino-article-content">
				<?php
				$article_html = isset($article['text']) ? (string)$article['text'] : '';
				echo site_insert_cta_evenly_in_content(
					function_exists('site_seo_clean_content') ? site_seo_clean_content($article_html) : $article_html,
					$buttons_html,
					3
				);
				?>
			</div>
		<?php elseif (!empty($abc['casino_list']) || !empty($abc['casino_pagination'])) : ?>
			<h1><?= htmlspecialchars($abc['page']['name' . $langid] ?? $abc['page']['name'] ?? 'Casinos') ?></h1>
			<div class="row">
				<?php
				$list = isset($abc['casino_list']) ? $abc['casino_list'] : array();
				foreach ($list as $c) :
					$slug = trim((string)($c['url'] ?? ''), '/');
					$link = preg_replace('#/+#', '/', $casinos_base . $slug . '/');
					$img_src = '';
					if (!empty($c['img'])) {
						$img_src = get_img('casino_articles', $c, 'img');
						if (!$img_src || strpos($img_src, 'no_img') !== false) {
							$img_src = get_img('casinos', $c, 'img');
						}
						if ($img_src && strpos($img_src, 'no_img') === false) {
							$img_src = htmlspecialchars($img_src);
						} else {
							$img_src = '';
						}
					}
				?>
					<div class="col-md-6 col-lg-4 mb-4">
						<a href="<?= $link ?>" class="guide-card card h-100 text-decoration-none">
							<?php if ($img_src) : ?>
								<img src="<?= $img_src ?>" class="card-img-top" alt="">
							<?php endif; ?>
							<div class="card-body">
								<h5 class="card-title"><?= htmlspecialchars((string)$c['name']) ?></h5>
								<?php if (!empty($c['name_2'])) : ?>
									<p class="card-text guide-card-desc"><?= htmlspecialchars((string)$c['name_2']) ?></p>
								<?php endif; ?>
								<span class="guide-card-link">&rarr; <?= htmlspecialchars((string)$read_more) ?></span>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if (!empty($abc['casino_pagination']) && $abc['casino_pagination']['num_rows'] > $abc['casino_pagination']['limit']) : ?>
				<div class="mt-4">
					<?= html_render('pagination/default_front', $abc['casino_pagination']) ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p class="lead">No casino articles yet.</p>
		<?php endif; ?>
	</div>
</section>

