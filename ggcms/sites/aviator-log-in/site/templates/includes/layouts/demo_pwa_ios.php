<?php
/**
 * /{lang}/{download-slug}/install-pwa/ — render the page body from pages/content_i18n like other content pages.
 */
global $abc, $lang;
$page_html = isset($abc['content']) ? (string) $abc['content'] : '';
if ($page_html !== '' && function_exists('pwa_install_normalize_demo_links_in_content')) {
	$page_html = pwa_install_normalize_demo_links_in_content($page_html, $abc, $lang);
}
if ($page_html !== '' && function_exists('pwa_install_bust_ios_image_cache')) {
	$page_html = pwa_install_bust_ios_image_cache($page_html);
}
if ($page_html !== '' && function_exists('pwa_install_enhance_quick_path')) {
	$page_html = pwa_install_enhance_quick_path($page_html, $abc, $lang);
}
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
<script>
(function () {
	function copyText(url, done) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(done).catch(fallback);
			return;
		}
		fallback();
		function fallback() {
			var ta = document.createElement('textarea');
			ta.value = url;
			ta.setAttribute('readonly', '');
			ta.style.position = 'fixed';
			ta.style.left = '-9999px';
			document.body.appendChild(ta);
			ta.select();
			try {
				document.execCommand('copy');
				done();
			} catch (e) {}
			document.body.removeChild(ta);
		}
	}
	document.querySelectorAll('.pwa-ios-quick__copy').forEach(function (btn) {
		if (btn.dataset.copyBound === '1') {
			return;
		}
		btn.dataset.copyBound = '1';
		btn.addEventListener('click', function () {
			var url = btn.getAttribute('data-copy-url') || '';
			if (!url) {
				return;
			}
			var label = btn.querySelector('.pwa-ios-quick__copy-label');
			var copyLabel = btn.getAttribute('data-copy-label') || 'Copy link';
			var copiedLabel = btn.getAttribute('data-copied-label') || 'Copied!';
			copyText(url, function () {
				btn.classList.add('is-copied');
				if (label) {
					label.textContent = copiedLabel;
				}
				window.setTimeout(function () {
					btn.classList.remove('is-copied');
					if (label) {
						label.textContent = copyLabel;
					}
				}, 2200);
			});
		});
	});
})();
</script>
