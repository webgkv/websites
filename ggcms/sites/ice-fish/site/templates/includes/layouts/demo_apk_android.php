<?php
/**
 * /{lang}/{download-slug}/install-apk/ — same chrome as PWA iOS (centered copy from DB + shared styles).
 */
global $abc;
$page_html = isset($abc['content']) ? (string) $abc['content'] : '';
// Stable APK href + placeholder.svg → PNGs: mirror pages.php when this template runs with raw DB content.
if ($page_html !== '' && function_exists('apk_install_normalize_apk_link_in_content')) {
	$page_html = apk_install_normalize_apk_link_in_content($page_html);
}
if ($page_html !== '' && function_exists('apk_install_replace_placeholder_step_images')) {
	$page_html = apk_install_replace_placeholder_step_images($page_html);
}
if ($page_html !== '' && function_exists('apk_install_bust_android_image_cache')) {
	$page_html = apk_install_bust_android_image_cache($page_html);
}
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5 demo-pwa-ios-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-10 col-xl-9">
				<div class="text page-content-from-db pwa-install-from-db apk-install-from-db about_content">
					<?= $page_html ?>
				</div>
			</div>
		</div>
	</div>
</section>
