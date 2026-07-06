<?php
/**
 * Dismissible admin banner: indexing restrictions reminder.
 */
if (!function_exists('site_seo_admin_indexing_restrictions_active') || !site_seo_admin_indexing_restrictions_active()) {
	return;
}
$restrictions = site_seo_admin_indexing_restrictions();
$timing = site_seo_admin_indexing_guard_timing();
$page_limit = (int) $timing['page_limit'];
$minutes_limit = (int) $timing['minutes_limit'];
?>
<div id="ifish-admin-index-guard-banner" class="alert alert-warning alert-dismissible fade show mb-3 d-none" role="alert"
	data-page-limit="<?= (int) $page_limit ?>"
	data-minutes-limit="<?= (int) $minutes_limit ?>">
	<strong><i data-feather="alert-triangle" class="align-middle" style="width:16px;height:16px;"></i>
		Indexing restricted</strong>
	<ul class="mb-0 mt-2 pl-3">
		<?php foreach ($restrictions as $row) { ?>
		<li><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></li>
		<?php } ?>
	</ul>
	<button type="button" class="close" id="ifish-admin-index-guard-dismiss" aria-label="Dismiss">
		<span aria-hidden="true">&times;</span>
	</button>
</div>
<script>
(function () {
	var banner = document.getElementById('ifish-admin-index-guard-banner');
	if (!banner) return;
	var LS_DISMISS = 'ifish_admin_index_guard_dismissed_at';
	var SS_PAGES = 'ifish_admin_index_guard_pages_since_dismiss';
	var pageLimit = parseInt(banner.getAttribute('data-page-limit') || '30', 10);
	var msLimit = parseInt(banner.getAttribute('data-minutes-limit') || '30', 10) * 60 * 1000;

	function dismissedAt() {
		var ts = localStorage.getItem(LS_DISMISS);
		return ts ? parseInt(ts, 10) : 0;
	}

	function pagesSinceDismiss() {
		return parseInt(sessionStorage.getItem(SS_PAGES) || '0', 10);
	}

	function shouldShow() {
		var ts = dismissedAt();
		if (!ts) return true;
		if (pagesSinceDismiss() >= pageLimit) return true;
		if (Date.now() - ts >= msLimit) return true;
		return false;
	}

	function clearDismiss() {
		localStorage.removeItem(LS_DISMISS);
		sessionStorage.setItem(SS_PAGES, '0');
	}

	if (dismissedAt() > 0) {
		sessionStorage.setItem(SS_PAGES, String(pagesSinceDismiss() + 1));
	}

	if (shouldShow()) {
		clearDismiss();
		banner.classList.remove('d-none');
		if (window.feather && typeof window.feather.replace === 'function') {
			window.feather.replace();
		}
	}

	var btn = document.getElementById('ifish-admin-index-guard-dismiss');
	if (btn) {
		btn.addEventListener('click', function () {
			localStorage.setItem(LS_DISMISS, String(Date.now()));
			sessionStorage.setItem(SS_PAGES, '0');
			banner.classList.add('d-none');
		});
	}
})();
</script>
