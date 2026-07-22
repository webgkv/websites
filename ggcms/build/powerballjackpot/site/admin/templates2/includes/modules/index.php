<?php
$q = array();

// Site-wide dashboard stats (content + SEO)
$q['site']['pages']   = (int) @mysql_select("SELECT COUNT(*) FROM pages WHERE display=1", 'string');
$q['site']['blog']    = (int) @mysql_select("SELECT COUNT(*) FROM blog WHERE display=1", 'string');
$q['site']['guides']  = (int) @mysql_select("SELECT COUNT(*) FROM guides WHERE display=1", 'string');
$q['site']['games']   = (int) @mysql_select("SELECT COUNT(*) FROM games WHERE display=1", 'string');
$q['site']['casinos'] = (int) @mysql_select("SELECT COUNT(*) FROM casino_articles WHERE display=1", 'string');
$q['site']['promo'] = (int) @mysql_select("SELECT COUNT(*) FROM promo WHERE display=1", 'string');
$q['site']['langs']   = (int) @mysql_select("SELECT COUNT(*) FROM languages WHERE display=1", 'string');

$q['seo_monitor_ajax'] = false;
$q['seo_monitor_thr'] = 400;
if (file_exists(ROOT_DIR . 'functions/seo_monitor.php')) {
	require_once ROOT_DIR . 'functions/seo_monitor.php';
	if (function_exists('seo_monitor_sync_row_threshold')) {
		$q['seo_monitor_thr'] = (int)seo_monitor_sync_row_threshold();
	}
}
if (function_exists('access') && access('admin module', 'seo_monitor')) {
	$q['seo_monitor_ajax'] = true;
}

$dash_content_kpis = array(
	array('href' => 'admin.php?m=pages', 'label' => 'Pages', 'v' => (int)@$q['site']['pages'], 'accent' => '#007bff'),
	array('href' => 'admin.php?m=content&tab=blog', 'label' => site_section_admin_label('blog', 'Blog'), 'v' => (int)@$q['site']['blog'], 'accent' => '#28a745'),
	array('href' => 'admin.php?m=content&tab=guides', 'label' => site_section_admin_label('guides', 'Guides'), 'v' => (int)@$q['site']['guides'], 'accent' => '#17a2b8'),
	array('href' => 'admin.php?m=content&tab=games', 'label' => 'Games', 'v' => (int)@$q['site']['games'], 'accent' => '#ffc107'),
	array('href' => 'admin.php?m=content&tab=casinos', 'label' => 'Casinos', 'v' => (int)@$q['site']['casinos'], 'accent' => '#6c757d'),
	array('href' => 'admin.php?m=content&tab=promo', 'label' => 'Promo', 'v' => (int)@$q['site']['promo'], 'accent' => '#e83e8c'),
	array('href' => 'admin.php?m=languages', 'label' => 'Languages', 'v' => (int)@$q['site']['langs'], 'accent' => '#343a40'),
);
?>

<style>
	.brands img {width: 50px}
</style>

<div class="dashboard-overview mb-4">
	<h1 class="dashboard-page-title">Dashboard</h1>

	<ul class="nav nav-tabs mb-3 flex-wrap" role="tablist">
		<li class="nav-item"><span class="nav-link active" role="tab">Overview</span></li>
	</ul>

	<div class="mb-3 dashboard-quick-links">
		<a href="admin.php?m=pages" class="btn btn-outline-primary btn-sm">Pages</a>
		<a href="admin.php?m=content" class="btn btn-outline-secondary btn-sm">Content</a>
		<a href="admin.php?m=seo_sitemap" class="btn btn-outline-secondary btn-sm">Sitemap</a>
		<a href="admin.php?m=seo_structured" class="btn btn-outline-secondary btn-sm">Structured data</a>
		<a href="admin.php?m=seo_monitor" class="btn btn-outline-secondary btn-sm">SEO Monitor</a>
		<a href="admin.php?m=languages" class="btn btn-outline-secondary btn-sm">Languages</a>
		<a href="admin.php?m=settings" class="btn btn-outline-secondary btn-sm">Settings</a>
	</div>

	<h6 class="text-muted text-uppercase small mb-3">Published content</h6>
	<div class="row mb-4 dash-kpi-section">
		<?php foreach ($dash_content_kpis as $dk) {
			$href = htmlspecialchars($dk['href'], ENT_QUOTES, 'UTF-8');
			$lab = htmlspecialchars($dk['label'], ENT_QUOTES, 'UTF-8');
			$ac = htmlspecialchars($dk['accent'], ENT_QUOTES, 'UTF-8');
			$vv = (int) $dk['v'];
		?>
		<div class="col-6 col-md-4 col-lg-3 mb-3">
			<a href="<?= $href ?>" class="text-reset text-decoration-none d-block h-100">
				<div class="card h-100 shadow-sm border-0" style="border-top:3px solid <?= $ac ?>!important">
					<div class="card-body py-3 px-2 text-center">
						<div class="text-muted small text-uppercase"><?= $lab ?></div>
						<div class="h4 mb-0 text-dark font-weight-bold"><?= $vv ?></div>
					</div>
				</div>
			</a>
		</div>
		<?php } ?>
	</div>

	<?php if (!empty($q['seo_monitor_ajax'])) { ?>
	<div class="dashboard-ajax-section mb-4">
		<h6 class="text-muted text-uppercase small mb-2">SEO optimization</h6>
		<div class="small text-muted mb-3">
			Same checks as <a href="admin.php?m=seo_monitor">SEO Monitor</a> — loads via AJAX; blog / &gt; <?= (int)$q['seo_monitor_thr'] ?> rows use a
			<a href="admin.php?m=translations&amp;tab=monitor&amp;mtab=jobs">background job</a> (↻ on SEO Monitor).
		</div>
		<div class="row dash-kpi-section">
			<?php
			$seo_dashboard_labels = array(
				'pages' => 'Pages',
				'blog' => 'Blog',
				'guides' => 'Guides',
				'games' => 'Games',
				'casino_articles' => 'Casinos',
			);
			$seo_accents = array('#007bff', '#28a745', '#17a2b8', '#ffc107', '#6c757d');
			$ai = 0;
			foreach ($seo_dashboard_labels as $seo_ent => $seo_lab) {
				$list_href = 'admin.php?m=seo_monitor&u=list&entity=' . rawurlencode($seo_ent);
				$accent = $seo_accents[$ai % count($seo_accents)];
				$ai++;
			?>
			<div class="col-6 col-md-4 col-lg-3 mb-3">
				<div class="card h-100 shadow-sm border-0" style="border-top:3px solid <?= htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') ?>!important">
					<div class="card-body py-3 px-2 text-center position-relative">
						<div class="seo-mon-entity-score" data-entity="<?= htmlspecialchars($seo_ent, ENT_QUOTES, 'UTF-8') ?>">
							<a href="<?= htmlspecialchars($list_href, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-body d-block">
								<div class="kpi-value fw-bold text-muted seo-mon-score-value">…</div>
								<div class="kpi-label small text-muted"><?= htmlspecialchars($seo_lab, ENT_QUOTES, 'UTF-8') ?></div>
							</a>
							<button type="button" class="btn btn-link btn-sm p-0 seo-mon-refresh" title="Recalculate">↻</button>
							<div class="small text-muted seo-mon-score-hint" style="min-height:1.1em;"></div>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
	</div>
	<script>
	(function(){
		function fallbackColor(pct){
			if (pct === null || pct === undefined) return "text-muted";
			if (pct < 50) return "text-danger";
			if (pct <= 80) return "text-warning";
			return "text-success";
		}
		function applySlot(el, data){
			if (!data || !data.ok) return;
			var v = el.querySelector(".seo-mon-score-value");
			var hint = el.querySelector(".seo-mon-score-hint");
			if (!v) return;
			v.className = "h4 mb-0 font-weight-bold " + (data.color_class || fallbackColor(data.pct));
			v.textContent = (data.pct_display != null && data.pct_display !== "") ? data.pct_display : "—";
			if (data.good != null && data.relevant != null) el.setAttribute("title", data.good + " / " + data.relevant + " cells OK");
			if (hint) {
				hint.textContent = "";
				if (data.source === "queued" && data.message) hint.textContent = data.message;
				else if (data.source === "pending" && data.message) hint.textContent = data.message;
				else if (data.source === "cache" && data.computed_at) hint.textContent = "Cached · " + data.computed_at;
			}
		}
		function load(ent, refresh, el){
			var v = el.querySelector(".seo-mon-score-value");
			if (v && refresh) v.textContent = "…";
			var url = "admin.php?m=seo_monitor&u=ajax_entity&entity=" + encodeURIComponent(ent) + (refresh ? "&refresh=1" : "");
			fetch(url, { credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest" } })
				.then(function(r){ return r.json(); })
				.then(function(d){ applySlot(el, d); })
				.catch(function(){ if (v) v.textContent = "?"; });
		}
		document.querySelectorAll(".dashboard-overview .seo-mon-entity-score").forEach(function(el){
			var ent = el.getAttribute("data-entity");
			if (!ent) return;
			load(ent, false, el);
			var btn = el.querySelector(".seo-mon-refresh");
			if (btn) btn.addEventListener("click", function(e){ e.preventDefault(); e.stopPropagation(); load(ent, true, el); });
		});
	})();
	</script>
	<?php } ?>

	<div class="card shadow-sm border-0 mb-4">
		<div class="card-body py-2 px-3">
			<span class="small text-muted">Sitemap:</span>
			<a href="/api/sitemap/index_hub.xml" target="_blank" class="small">index_hub.xml</a>
			<span class="text-muted small"> · </span>
			<a href="admin.php?m=seo_sitemap" class="small">parts</a>
		</div>
	</div>
</div>
