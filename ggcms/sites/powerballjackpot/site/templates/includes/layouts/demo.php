<?php
// Demo page: interactive game block (iframe) + text content. Same structure as donor (aviatorgameonline.game/aviator-game-demo/).
global $lang;
$page_name = isset($abc['page_i18n']['name']) && $abc['page_i18n']['name'] !== '' ? $abc['page_i18n']['name'] : (isset($abc['page']['name' . (isset($abc['langid']) ? $abc['langid'] : '')]) ? $abc['page']['name' . (isset($abc['langid']) ? $abc['langid'] : '')] : $abc['page']['name']);
$demo_content = (isset($abc['page_i18n']['content']) && (string)$abc['page_i18n']['content'] !== '') ? $abc['page_i18n']['content'] : (isset($abc['content']) ? $abc['content'] : '');
$demo_iframe_url = function_exists('site_game_demo_iframe_url') ? site_game_demo_iframe_url($config) : '';

require_once(ROOT_DIR . 'functions/cta_inject.php');
require_once(ROOT_DIR . 'functions/site_quick_access.php');
$offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
$buttons_html = aviator_cta_buttons_html($offer_path);
$demo_content = site_demo_apply_quick_access($demo_content, $abc, $lang);
$demo_content = aviator_insert_cta_after_paragraphs(
	$demo_content,
	$buttons_html,
	[1, 5, 10]
);
$demo_content = site_demo_render_with_quick_access($demo_content, $abc, $lang);
$demo_content_has_h1 = preg_match('/<h1\b/i', $demo_content) === 1;
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php if (!$demo_content_has_h1): ?>
                    <h1><?= htmlspecialchars($page_name) ?></h1>
                <?php endif; ?>
                <div class="text page-content-from-db">
                    <?= $demo_content ?>
                </div>
            </div>
        </div>
    </div>
</section>
