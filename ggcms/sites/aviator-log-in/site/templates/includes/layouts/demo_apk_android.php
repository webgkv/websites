<?php
/**
 * /{lang}/{download-slug}/install-apk/ — same chrome as PWA iOS (centered copy from DB + shared styles).
 */
global $abc, $lang;
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
if ($page_html !== '' && function_exists('apk_install_enhance_page')) {
	$page_html = apk_install_enhance_page($page_html, $abc, $lang);
} elseif ($page_html !== '' && function_exists('apk_install_enhance_download_ctas')) {
	$page_html = apk_install_enhance_download_ctas($page_html);
}
$apk_ui = function_exists('apk_install_ui_strings') ? apk_install_ui_strings($lang) : array();
$apk_ui_json = htmlspecialchars(json_encode($apk_ui, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5 demo-pwa-ios-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-10 col-xl-9">
				<div class="text page-content-from-db pwa-install-from-db apk-install-from-db about_content" data-apk-ui="<?= $apk_ui_json ?>">
					<?= $page_html ?>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
(function () {
	var root = document.querySelector('.apk-install-from-db');
	if (!root) {
		return;
	}

	var tourBar = root.querySelector('.pwa-ios-tour-bar');
	var finishBlock = root.querySelector('#apk-android-finish');
	var heroDownload = root.querySelector('#apk-download-hero');
	var steps = [1, 2, 3];
	var current = -1;
	var autoTimer = null;
	var stepMs = 9000;

	function stepsHeadingEl() {
		var h2s = root.querySelectorAll('h2');
		return h2s.length > 1 ? h2s[1] : (h2s[0] || null);
	}

	function placeTourBar(fig) {
		if (!tourBar || !fig) {
			return;
		}
		fig.insertAdjacentElement('afterend', tourBar);
	}

	function clearActive() {
		root.querySelectorAll('.pwa-ios-tour-step--active').forEach(function (el) {
			el.classList.remove('pwa-ios-tour-step--active');
		});
		root.querySelectorAll('.pwa-ios-tour-fig--active').forEach(function (el) {
			el.classList.remove('pwa-ios-tour-fig--active');
		});
		root.querySelectorAll('.pwa-ios-tour-text--active').forEach(function (el) {
			el.classList.remove('pwa-ios-tour-text--active');
		});
	}

	function stepLabel(n) {
		if (!tourBar) {
			return '';
		}
		var tpl = tourBar.getAttribute('data-step-template') || 'Step %d of 3';
		return tpl.replace('%d', String(n));
	}

	function updateBar() {
		if (!tourBar) {
			return;
		}
		var progress = tourBar.querySelector('.pwa-ios-tour-bar__progress');
		var backBtn = tourBar.querySelector('.pwa-ios-tour-back');
		var nextBtn = tourBar.querySelector('.pwa-ios-tour-next');
		var doneBtn = tourBar.querySelector('.pwa-ios-tour-done');
		var onLast = current >= steps.length - 1;
		if (progress) {
			progress.textContent = onLast ? '' : stepLabel(current + 1);
		}
		if (backBtn) {
			backBtn.disabled = current <= 0;
		}
		if (nextBtn) {
			nextBtn.hidden = onLast;
		}
		if (doneBtn) {
			doneBtn.hidden = !onLast;
		}
	}

	function revealFinish() {
		var target = heroDownload || finishBlock;
		if (target) {
			window.setTimeout(function () {
				target.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}, 120);
		}
	}

	function showStep(index) {
		if (index < 0 || index >= steps.length) {
			return;
		}
		current = index;
		clearActive();
		var n = steps[index];
		var h = root.querySelector('[data-pwa-tour-step="' + n + '"]');
		var p = root.querySelector('[data-pwa-tour-text="' + n + '"]');
		var fig = root.querySelector('[data-pwa-tour-fig="' + n + '"]');
		if (h) {
			h.classList.add('pwa-ios-tour-step--active');
		}
		if (p) {
			p.classList.add('pwa-ios-tour-text--active');
		}
		if (fig) {
			fig.classList.add('pwa-ios-tour-fig--active');
			placeTourBar(fig);
			fig.scrollIntoView({ behavior: 'smooth', block: 'center' });
			window.setTimeout(function () {
				if (tourBar) {
					tourBar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
			}, 320);
		}
		updateBar();
		window.clearTimeout(autoTimer);
		if (index < steps.length - 1) {
			autoTimer = window.setTimeout(function () {
				showStep(index + 1);
			}, stepMs);
		}
	}

	function endTour() {
		window.clearTimeout(autoTimer);
		document.body.classList.remove('pwa-ios-tour-running');
		clearActive();
		if (tourBar) {
			tourBar.hidden = true;
			tourBar.setAttribute('aria-hidden', 'true');
		}
		revealFinish();
	}

	function startTour() {
		if (!tourBar) {
			revealFinish();
			return;
		}
		document.body.classList.add('pwa-ios-tour-running');
		tourBar.hidden = false;
		tourBar.setAttribute('aria-hidden', 'false');
		var stepsHeading = stepsHeadingEl();
		if (stepsHeading) {
			stepsHeading.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
		window.setTimeout(function () {
			showStep(0);
		}, 280);
	}

	root.querySelectorAll('.pwa-ios-tour-start').forEach(function (btn) {
		if (btn.dataset.tourStartBound === '1') {
			return;
		}
		btn.dataset.tourStartBound = '1';
		btn.addEventListener('click', startTour);
	});
	if (tourBar) {
		var backBtn = tourBar.querySelector('.pwa-ios-tour-back');
		var nextBtn = tourBar.querySelector('.pwa-ios-tour-next');
		var doneBtn = tourBar.querySelector('.pwa-ios-tour-done');
		if (backBtn) {
			backBtn.addEventListener('click', function () {
				if (current > 0) {
					showStep(current - 1);
				}
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				if (current < steps.length - 1) {
					showStep(current + 1);
				}
			});
		}
		if (doneBtn) {
			doneBtn.addEventListener('click', endTour);
		}
	}
})();
</script>
