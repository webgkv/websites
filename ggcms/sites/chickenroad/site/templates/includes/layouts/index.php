<?php
$svg_line = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" class="heading-icon" style="enable-background:new 0 0 100 100"><style>.wdt-right-dot-icon{fill:none;stroke:currentColor}</style><g><path d="M57.1,48.9H2.8v2.2h54.3V48.9z"/><rect x="63.6" y="42.4" width="15.2" height="15.2"/><rect x="84.2" y="43.5" class="wdt-right-dot-icon" width="13" height="13"/></g></svg>';
// Prefer content_i18n (page_i18n) for current language; fallback to legacy text/text2/text3 from pages
$page_text_key = 'text' . (isset($abc['langid']) ? $abc['langid'] : '');
$page_text = (isset($abc['page_i18n']['content']) && (string)$abc['page_i18n']['content'] !== '') ? (string)$abc['page_i18n']['content'] : (isset($abc['page'][$page_text_key]) ? (string)$abc['page'][$page_text_key] : '');
if (!empty($abc['debug'])) {
	$abc['debug_info']['index_text_key'] = $page_text_key;
	$abc['debug_info']['index_text_length'] = strlen($page_text);
	$abc['debug_info']['index_show_from_db'] = ($page_text !== '');
	$abc['debug_info']['index_text_preview'] = $page_text !== '' ? substr(strip_tags($page_text), 0, 200) . '...' : '(empty)';
}
// Hero: title/description already set from page_i18n in _template.php; use them for display
$hero_title = '';
if (isset($abc['page']['heading']) && trim((string)$abc['page']['heading']) !== '') {
	$hero_title = (string)$abc['page']['heading'];
} elseif (isset($abc['page_i18n']['title']) && trim((string)$abc['page_i18n']['title']) !== '') {
	$hero_title = trim(strip_tags(html_entity_decode((string)$abc['page_i18n']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
} elseif (isset($abc['page']['title'])) {
	$hero_title = (string)$abc['page']['title'];
}
$hero_desc  = isset($abc['page']['description']) ? (string)$abc['page']['description'] : '';
?>
        <!-- hero section start -->
        <section class="hero_section" id="index">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="hero_content">
                            <h1 class="hero_content__title"><?= $hero_title !== '' ? htmlspecialchars($hero_title) : '' ?></h1>
                            <?php if ($hero_desc !== ''): ?><p><?= htmlspecialchars($hero_desc) ?></p><?php endif; ?>
                            <div class="main_btn mt-5">
                                <a href="<?= !empty($abc['ad_offer_path']) ? htmlspecialchars($abc['ad_offer_path']) : '#demo' ?>"><?=htmlspecialchars(i18n('common|hero_cta'))?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="chickenroad-hero-img">
                            <img src="<?= htmlspecialchars(site_brand_hero_image_url(), ENT_QUOTES, 'UTF-8') ?>" alt="Chicken Road game — cross the road and win" width="1024" height="836" loading="eager" decoding="async" fetchpriority="high">
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- hero section end -->

<?php
if ($page_text !== '') {
	if (function_exists('site_strip_page_content_lead')) {
		$page_text = site_strip_page_content_lead($page_text);
	}
	if (!empty($abc['ad_offer_path']) && function_exists('site_ad_replace_content_links')) {
		$page_text = site_ad_replace_content_links($page_text, $abc['ad_offer_path']);
	}
}
if ($page_text !== ''): ?>
        <section class="container py-5 page-content-from-db"><div class="row"><div class="col-12"><?= function_exists('site_seo_clean_content') ? site_seo_clean_content($page_text, true) : $page_text ?></div></div></section>
<?php else: ?>
        <section class="container py-5"><div class="row"><div class="col-12"><p><?=htmlspecialchars(i18n('common|no_content'))?></p></div></div></section>
<?php endif; ?>
