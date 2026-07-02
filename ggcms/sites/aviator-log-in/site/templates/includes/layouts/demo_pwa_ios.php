<?php
/**
 * /{lang}/{download-slug}/install-pwa/ — render the page body from pages/content_i18n like other content pages.
 */
global $abc;
$page_html = isset($abc['content']) ? (string) $abc['content'] : '';
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5 demo-pwa-ios-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-10 col-xl-9">
				<div class="text page-content-from-db pwa-install-from-db about_content">
					<?= $page_html ?>
				</div>
			</div>
		</div>
	</div>
</section>
