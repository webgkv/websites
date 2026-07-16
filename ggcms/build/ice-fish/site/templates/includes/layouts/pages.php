<?php
// Text page (module pages): About Us, Terms, Privacy Policy, Responsible Gambling, Download, etc.
// Match demo.php / _template: prefer content_i18n name, then legacy name{lang}, then default name.
// If name is empty (e.g. FR row missing name), fall back to translated title so H1 is not blank.
$langid = isset($abc['langid']) ? (string)$abc['langid'] : '';
$page_name = '';
if (!empty($abc['page_i18n']['name']) && trim((string)$abc['page_i18n']['name']) !== '') {
	$page_name = trim((string)$abc['page_i18n']['name']);
}
if ($page_name === '' && $langid !== '' && isset($abc['page']['name' . $langid]) && trim((string)$abc['page']['name' . $langid]) !== '') {
	$page_name = trim((string)$abc['page']['name' . $langid]);
}
if ($page_name === '' && isset($abc['page']['name']) && trim((string)$abc['page']['name']) !== '') {
	$page_name = trim((string)$abc['page']['name']);
}
if ($page_name === '' && !empty($abc['page_i18n']['title']) && trim((string)$abc['page_i18n']['title']) !== '') {
	$page_name = trim(strip_tags(html_entity_decode((string)$abc['page_i18n']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}
if ($page_name === '' && $langid !== '' && isset($abc['page']['title' . $langid]) && trim((string)$abc['page']['title' . $langid]) !== '') {
	$page_name = trim(strip_tags(html_entity_decode((string)$abc['page']['title' . $langid], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}
if ($page_name === '' && isset($abc['page']['title']) && trim((string)$abc['page']['title']) !== '') {
	$page_name = trim(strip_tags(html_entity_decode((string)$abc['page']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

$page_slugs = array();
if ($langid !== '' && !empty($abc['page']['url' . $langid])) {
	$page_slugs[] = trim((string)$abc['page']['url' . $langid], '/');
}
if (!empty($abc['page_i18n']['url'])) {
	$page_slugs[] = trim((string)$abc['page_i18n']['url'], '/');
}
if (!empty($abc['page']['url'])) {
	$page_slugs[] = trim((string)$abc['page']['url'], '/');
}
if (!empty($abc['page']) && is_array($abc['page'])) {
	foreach ($abc['page'] as $pk => $pv) {
		if (preg_match('/^url\d*$/', (string)$pk) && trim((string)$pv) !== '') {
			$page_slugs[] = trim((string)$pv, '/');
		}
	}
}
$page_slugs = array_values(array_unique(array_filter($page_slugs)));
$page_slug = !empty($page_slugs) ? $page_slugs[0] : '';
// Legal/info pages: H1 matches footer labels from common dictionary (avoids English H1 when DB i18n name is missing).
$legal_h1_i18n = array(
	'about-us' => 'common|footer_about_us',
	'terms-and-conditions' => 'common|footer_terms',
	'privacy-policy' => 'common|footer_privacy',
	'responsible-gambling' => 'common|footer_responsible',
);
foreach ($legal_h1_i18n as $legal_slug => $i18n_key) {
	if (in_array($legal_slug, $page_slugs, true)) {
		$t = trim(i18n($i18n_key));
		if ($t !== '') {
			$page_name = $t;
		}
		break;
	}
}
if ($page_name === '' && !empty($abc['breadcrumb']) && is_array($abc['breadcrumb'])) {
	$last_crumb = end($abc['breadcrumb']);
	if (is_array($last_crumb) && !empty($last_crumb['name'])) {
		$page_name = trim((string)$last_crumb['name']);
	}
}

require_once(ROOT_DIR . 'functions/cta_inject.php');
require_once(ROOT_DIR . 'functions/site_quick_access.php');
require_once(ROOT_DIR . 'functions/author_func.php');
$legal_page_slugs = function_exists('site_legal_page_slugs') ? site_legal_page_slugs() : array('about-us', 'terms-and-conditions', 'privacy-policy', 'responsible-gambling');
// Legal pages: no mid-page CTA injection (terms/privacy/responsible; about-us keeps body CTAs from CMS only).
$skip_cta_slugs = array('terms-and-conditions', 'privacy-policy', 'responsible-gambling');
$page_html = isset($abc['content']) ? (string)$abc['content'] : '';

if ($page_html !== '' && $page_slug === 'download') {
	$page_html = site_download_apply_quick_access($page_html, $abc, $lang);
}

if ($page_html !== '' && $page_slug !== '' && !in_array($page_slug, $skip_cta_slugs, true)) {
	$offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
		$page_html = site_insert_cta_evenly_in_content($page_html, $offer_path);
}
// One H1 per page: raw CMS HTML may contain <h1> that site_seo_clean_content downgrades to <h2>.
$__page_html_for_h1 = ($page_html !== '' && function_exists('site_seo_clean_content'))
	? site_seo_clean_content($page_html)
	: $page_html;
$body_has_h1 = ($__page_html_for_h1 !== '' && preg_match('/<h1\\b/i', $__page_html_for_h1));
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <?php if ($page_name !== '' && !$body_has_h1): ?>
                <h1><?= htmlspecialchars($page_name) ?></h1>
                <?php endif; ?>
                <?php if ($page_slug !== '' && !in_array($page_slug, $legal_page_slugs, true) && function_exists('site_render_author_byline')) echo site_render_author_byline($abc); ?>
                <div class="text page-content-from-db">
                    <?= function_exists('site_seo_clean_content') ? site_seo_clean_content($page_html) : $page_html ?>
                </div>
            </div>
        </div>
    </div>
</section>
