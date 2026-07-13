<?php
// Guides: landing (all cards + category filters), category list, or single guide
$langid = isset($abc['langid']) ? $abc['langid'] : '';
$lang_prefix = isset($abc['lang']['url']) ? trim((string)$abc['lang']['url'], '/') : '';
// Avoid get_url('page', ...) returning '/{lang}//' when url$langid is empty for the Guides landing page.
$guides_base = ($lang_prefix !== '' ? '/' . $lang_prefix . '/guides/' : '/guides/');
$guides_base = preg_replace('#/+#', '/', $guides_base);

require_once(ROOT_DIR . 'functions/cta_inject.php');
$offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
$buttons_html = site_cta_buttons_html($offer_path);
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
    <div class="container">
        <?php
        // Section title must come from i18n to avoid DB/content_i18n mismatches.
        $page_title = i18n('common|guides_title');
        ?>
        <?php
        // Fallbacks for DE: some deployments miss DE dictionary keys entirely.
        $guides_cat_all = (string)i18n('common|guides_cat_all');
        if ($guides_cat_all === '' || trim($guides_cat_all) === 'All') {
            if (!empty($abc['lang']['url']) && (string)$abc['lang']['url'] === 'de') $guides_cat_all = 'Alle';
        }
        $read_guide = (string)i18n('common|read_guide');
        if ($read_guide === '' || trim($read_guide) === 'Read guide') {
            if (!empty($abc['lang']['url']) && (string)$abc['lang']['url'] === 'de') $read_guide = 'Leitfaden lesen';
        }
        ?>
        <?php if (!empty($abc['guide_single'])) : ?>
            <?php $guide = $abc['guide_single']; ?>
            <?php if (function_exists('site_render_author_byline')) echo site_render_author_byline($abc, array('date' => $guide['date'] ?? ($guide['updated_at'] ?? ''))); ?>
            <div class="text page-content-from-db about_content">
                <?php
                $guide_html = isset($guide['text']) ? (string)$guide['text'] : '';
                // Ensure image placeholders work for both base guides.text and translated content_i18n.content.
                // Translations may keep {{GUIDE_ID}} / {{ID}} placeholders, so we replace them at render time.
                $guide_id = isset($guide['id']) ? (int)$guide['id'] : 0;
                if ($guide_id > 0) {
                    $guide_html = str_replace(array('{{GUIDE_ID}}', '{{ID}}'), (string)$guide_id, $guide_html);
					// Extra safety: if placeholders differ (spaces/casing/other token), fix any guide image src.
					$guide_html = preg_replace(
						'#(/files/guides/)\{\{[^}]+\}\}(/img/)#',
						'$1' . (string)$guide_id . '$2',
						$guide_html
					);
                }
                echo site_insert_cta_evenly_in_content(
                    function_exists('site_seo_clean_content') ? site_seo_clean_content($guide_html) : $guide_html,
                    $buttons_html,
                    3
                );
                ?>
            </div>
        <?php elseif (!empty($abc['guide_list'])) : ?>
            <?php
            $cat_name = isset($abc['guide_category_name']) ? trim((string)$abc['guide_category_name']) : '';
            $h1 = $cat_name !== '' ? $cat_name : ($page_title !== '' ? $page_title : 'Guides');
            ?>
            <h1><?= htmlspecialchars($h1) ?></h1>
            <?php if (!empty($abc['guide_landing']) && !empty($abc['guide_categories'])) : ?>
                <nav class="guide-filters mb-4" aria-label="Guide categories">
                    <a href="<?= $guides_base ?>" class="guide-filter-btn active"><?= htmlspecialchars($guides_cat_all) ?></a>
                    <?php foreach ($abc['guide_categories'] as $slug => $name) : ?>
                        <a href="<?= $guides_base . $slug . '/' ?>" class="guide-filter-btn"><?= htmlspecialchars($name) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php elseif (!empty($abc['guide_category']) && !empty($abc['guide_categories'])) : ?>
                <nav class="guide-filters mb-4" aria-label="Guide categories">
                    <a href="<?= $guides_base ?>" class="guide-filter-btn"><?= htmlspecialchars($guides_cat_all) ?></a>
                    <?php foreach ($abc['guide_categories'] as $slug => $name) : ?>
                        <a href="<?= $guides_base . $slug . '/' ?>" class="guide-filter-btn<?= ($slug === $abc['guide_category']) ? ' active' : '' ?>"><?= htmlspecialchars($name) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($abc['guide_list'] as $g) :
                    $cat = isset($g['category']) ? $g['category'] : (isset($abc['guide_category']) ? $abc['guide_category'] : '');
                    $link = $guides_base . $cat . '/' . $g['url'] . '/';
                    $cat_name = (!empty($abc['guide_categories'][$cat])) ? $abc['guide_categories'][$cat] : $cat;
                    $img_src = '';
                    if (!empty($g['img'])) {
                        $_guide_img = get_img('guides', $g, 'img');
                        if ($_guide_img && strpos($_guide_img, 'no_img') === false) {
                            $_guide_v = !empty($g['img_v']) ? (int)$g['img_v'] : 0;
                            if (!$_guide_v && function_exists('content_img_disk_path')) {
                                $_gdp = content_img_disk_path('guides', $g, 'img');
                                $_guide_v = ($_gdp && is_file($_gdp)) ? filemtime($_gdp) : 0;
                            }
                            $img_src = htmlspecialchars($_guide_img) . ($_guide_v ? '?v=' . $_guide_v : '');
                        }
                    }
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <a href="<?= $link ?>" class="guide-card card h-100 text-decoration-none">
                            <?php if ($img_src) : ?>
                                <img src="<?= $img_src ?>" class="card-img-top" alt="">
                            <?php endif; ?>
                            <div class="card-body">
                                <?php if (!empty($abc['guide_landing']) && $cat_name) : ?>
                                    <span class="guide-card-badge"><?= htmlspecialchars($cat_name) ?></span>
                                <?php endif; ?>
                                <h5 class="card-title"><?= htmlspecialchars($g['name']) ?></h5>
                                <?php if (!empty($g['name_2'])) : ?>
                                    <p class="card-text guide-card-desc"><?= htmlspecialchars($g['name_2']) ?></p>
                                <?php endif; ?>
                                <span class="guide-card-link">&rarr; <?= htmlspecialchars($read_guide) ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
