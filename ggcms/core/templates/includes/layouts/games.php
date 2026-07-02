<?php
// Games: landing (all cards + category filters) or single game
$langid = isset($abc['langid']) ? $abc['langid'] : '';
$games_base = isset($abc['page']) ? get_url('page', $abc['page']) : '';

require_once(ROOT_DIR . 'functions/cta_inject.php');
$offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
$buttons_html = aviator_cta_buttons_html($offer_path);
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
    <div class="container">
        <?php
        // Section title must come from i18n to avoid DB/content_i18n mismatches.
        $page_title = i18n('common|games_title');
        ?>
        <?php if (!empty($abc['game_single'])) : ?>
            <?php
            $game = $abc['game_single'];
            $game_name = '';
            if ($langid !== '' && !empty($game['name' . $langid])) {
                $game_name = trim((string)$game['name' . $langid]);
            }
            if ($game_name === '' && !empty($game['name'])) {
                $game_name = trim((string)$game['name']);
            }
            $game_html = isset($game['text']) ? (string)$game['text'] : '';
            $game_body_has_h1 = ($game_html !== '' && preg_match('/<h1\b/i', $game_html));
            ?>
            <?php if ($game_name !== '' && !$game_body_has_h1) : ?>
            <h1><?= htmlspecialchars($game_name, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php endif; ?>
            <?php if (function_exists('aviator_render_author_byline')) echo aviator_render_author_byline($abc, array('date' => $game['date'] ?? ($game['updated_at'] ?? ''))); ?>
            <div class="text page-content-from-db about_content">
                <?php
                $g_html = isset($game['text']) ? (string)$game['text'] : '';
                echo aviator_insert_cta_after_paragraphs(
                    function_exists('aviator_seo_clean_content') ? aviator_seo_clean_content($g_html) : $g_html,
                    $buttons_html,
                    [1, 5, 10]
                );
                ?>
            </div>
        <?php elseif (!empty($abc['game_list'])) : ?>
            <h1><?= htmlspecialchars($page_title !== '' ? $page_title : 'Games') ?></h1>
            <?php if (!empty($abc['game_landing']) && !empty($abc['game_categories'])) : ?>
                <nav class="guide-filters mb-4" aria-label="Game categories">
					<?php
					$games_cat_all = (string)i18n('common|games_cat_all');
					// Safety fallback: some dictionaries may miss DE keys for "All"
					if (!empty($abc['lang']['url']) && (string)$abc['lang']['url'] === 'de' && ($games_cat_all === '' || trim($games_cat_all) === 'All')) {
						$games_cat_all = 'Alle';
					}
					?>
                    <a href="<?= $games_base ?>" class="guide-filter-btn<?= empty($abc['game_category_filter']) ? ' active' : '' ?>"><?= htmlspecialchars($games_cat_all) ?></a>
                    <?php foreach ($abc['game_categories'] as $slug => $name) : ?>
                        <a href="<?= $games_base ?>?category=<?= urlencode($slug) ?>" class="guide-filter-btn<?= (isset($abc['game_category_filter']) && $abc['game_category_filter'] === $slug) ? ' active' : '' ?>"><?= htmlspecialchars($name) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <div class="row">
                <?php foreach ($abc['game_list'] as $g) {
                    $link = preg_replace('#/+#', '/', $games_base . trim((string)$g['url'], '/') . '/');
                    $img_src = '';
                    if (!empty($g['img'])) {
                        $img_src = get_img('games', $g, 'img');
                        if ($img_src && strpos($img_src, 'no_img') === false) {
                            $img_v = !empty($g['img_v']) ? (int)$g['img_v'] : 0;
                            if (!$img_v && function_exists('content_img_disk_path')) {
                                $dp = content_img_disk_path('games', $g, 'img');
                                $img_v = ($dp && is_file($dp)) ? filemtime($dp) : 0;
                            }
                            $img_src = htmlspecialchars($img_src) . ($img_v ? '?v=' . $img_v : '');
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
                                <?php if (!empty($abc['game_landing']) && !empty($g['category']) && !empty($abc['game_categories'][$g['category']])) : ?>
                                    <span class="guide-card-badge"><?= htmlspecialchars($abc['game_categories'][$g['category']]) ?></span>
                                <?php endif; ?>
                                <h5 class="card-title"><?= htmlspecialchars($g['name']) ?></h5>
                                <?php if (!empty($g['name_2'])) : ?>
                                    <p class="card-text guide-card-desc"><?= htmlspecialchars($g['name_2']) ?></p>
                                <?php endif; ?>
								<?php
								$read_more = (string)(i18n('common|read_more') ?: 'Read more');
								// Safety fallback for DE: avoid showing English "Read more"
								if (!empty($abc['lang']['url']) && (string)$abc['lang']['url'] === 'de' && $read_more === 'Read more') {
									$read_more = 'Mehr lesen';
								}
								?>
                                <span class="guide-card-link">&rarr; <?= htmlspecialchars($read_more) ?></span>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
        <?php endif; ?>
    </div>
</section>
