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
if ($page_html !== '' && function_exists('pwa_install_enhance_page')) {
	$page_html = pwa_install_enhance_page($page_html, $abc, $lang);
} elseif ($page_html !== '' && function_exists('pwa_install_enhance_quick_path')) {
	$page_html = pwa_install_enhance_quick_path($page_html, $abc, $lang);
}
$pwa_ui = function_exists('pwa_install_ui_strings') ? pwa_install_ui_strings($lang) : array();
$pwa_ui_json = htmlspecialchars(json_encode($pwa_ui, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5 demo-pwa-ios-page">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-10 col-xl-9">
				<div class="text page-content-from-db pwa-install-from-db about_content" data-pwa-ui="<?= $pwa_ui_json ?>">
					<?= $page_html ?>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
(function () {
	var root = document.querySelector('.pwa-install-from-db');
	if (!root) {
		return;
	}

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

	root.querySelectorAll('.pwa-ios-quick__copy').forEach(function (btn) {
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
			var copiedHint = btn.getAttribute('data-copied-hint') || '';
			var hintEl = btn.closest('.pwa-ios-quick') && btn.closest('.pwa-ios-quick').querySelector('.pwa-ios-quick__copied-hint');
			copyText(url, function () {
				btn.classList.add('is-copied');
				if (label) {
					label.textContent = copiedLabel;
				}
				if (hintEl && copiedHint) {
					hintEl.textContent = copiedHint;
					hintEl.hidden = false;
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

	var tourBar = root.querySelector('.pwa-ios-tour-bar');
	var finishBlock = root.querySelector('.pwa-ios-quick--finish');
	var steps = [1, 2, 3];
	var current = -1;
	var autoTimer = null;
	var stepMs = 6000;

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
		document.body.classList.add('pwa-ios-tour-finished');
		if (finishBlock) {
			finishBlock.classList.add('is-revealed');
			window.setTimeout(function () {
				finishBlock.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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
		var stepsHeading = root.querySelector('h2');
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
