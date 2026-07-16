<?php
require_once ROOT_DIR . 'functions/site_home_lottery.php';

$pbj_hero_title = '';
if (isset($abc['page']['heading']) && trim((string) $abc['page']['heading']) !== '') {
	$pbj_hero_title = trim(strip_tags(html_entity_decode((string) $abc['page']['heading'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
} elseif (isset($abc['page_i18n']['title']) && trim((string) $abc['page_i18n']['title']) !== '') {
	$pbj_hero_title = trim(strip_tags(html_entity_decode((string) $abc['page_i18n']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
} elseif (isset($abc['page']['title'])) {
	$pbj_hero_title = trim(strip_tags(html_entity_decode((string) $abc['page']['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

$pbj_hero_desc = isset($abc['page']['description']) ? trim((string) $abc['page']['description']) : '';
$pbj_cta_url = site_home_lottery_cta_url($abc);
$pbj_lang_base = isset($_site_lang_base) ? (string) $_site_lang_base : '/';

if (!empty($abc['debug'])) {
	$abc['debug_info']['index_layout'] = 'home_lottery';
	$abc['debug_info']['index_hero_title_length'] = strlen($pbj_hero_title);
	$abc['debug_info']['index_hero_desc_length'] = strlen($pbj_hero_desc);
}
?>
<div class="pbj-home dark-ui">
<?php require __DIR__ . '/home/sections.php'; ?>
</div>
