<?php
$langid = isset($abc['langid']) ? $abc['langid'] : '';
$blog_lang_prefix = trim((string)($abc['lang']['url'] ?? ''), '/');
$blog_base = $blog_lang_prefix !== '' ? '/' . $blog_lang_prefix . '/blog/' : '/blog/';
$url_key = 'url' . $langid;
$name_key = 'name' . $langid;
$name2_key = 'name_2' . $langid;
$text_key = 'text' . $langid;
$read_more = (string)(i18n('common|read_more') ?: 'Read more');
if (!empty($abc['lang']['url']) && (string)$abc['lang']['url'] === 'de' && $read_more === 'Read more') {
	$read_more = 'Mehr lesen';
}

if ($i == 1) { ?>
<section class="py-5">
  <div class="container">
    <div class="row">
<?php }
	$link = $blog_base . trim((string)($q[$url_key] ?? ''), '/') . '/';
	$title = (string)($q[$name_key] ?? '');
	$desc = '';
	if (!empty($q[$name2_key])) {
		$desc = trim(strip_tags((string)$q[$name2_key]));
	} elseif (!empty($q[$text_key])) {
		$desc = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$q[$text_key])));
	}
	if ($desc !== '' && mb_strlen($desc) > 220) {
		$desc = mb_substr($desc, 0, 217) . '…';
	}
	$img_src = '';
	if (!empty($q['img'])) {
		$_blog_img = get_img('blog', $q, 'img');
		if ($_blog_img && strpos($_blog_img, 'no_img') === false) {
			$_blog_v = 0;
			if (function_exists('content_img_disk_path')) {
				$_gdp = content_img_disk_path('blog', $q, 'img');
				$_blog_v = ($_gdp && is_file($_gdp)) ? filemtime($_gdp) : 0;
			}
			$img_src = htmlspecialchars($_blog_img, ENT_QUOTES, 'UTF-8') . ($_blog_v ? '?v=' . (int)$_blog_v : '');
		}
	}
?>
      <div class="col-md-6 col-lg-4 mb-4">
        <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" class="guide-card card h-100 text-decoration-none">
          <?php if ($img_src !== '') : ?>
            <img src="<?= $img_src ?>" class="card-img-top" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
          <?php endif; ?>
          <div class="card-body">
            <?php if ($title !== '') : ?>
              <h5 class="card-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h5>
            <?php endif; ?>
            <?php if ($desc !== '') : ?>
              <p class="card-text guide-card-desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <span class="guide-card-link">&rarr; <?= htmlspecialchars($read_more, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        </a>
      </div>
<?php if ($i == $num_rows) { ?>
    </div>
<?= html_render('pagination/default_front', $abc['blog']); ?>
  </div>
</section>
<?php } ?>
