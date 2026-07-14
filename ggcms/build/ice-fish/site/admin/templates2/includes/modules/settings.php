<?php
$tab = isset($q['tab']) ? $q['tab'] : 'main';
$settings = isset($q['settings']) ? $q['settings'] : array();
$saved = !empty($q['saved']);
$users_count = (int)@$q['users_count'];
$roles_count = (int)@$q['roles_count'];
$variables_exists = !empty($q['variables_exists']);
$tab_url = 'admin.php?m=settings&tab=';
?>
<?php if ($saved) { ?>
<div class="alert alert-success mb-3">Settings saved.</div>
<?php } ?>

<ul class="nav nav-tabs mb-4">
	<li class="nav-item">
		<a class="nav-link <?= $tab === 'main' ? 'active' : '' ?>" href="/<?= $tab_url ?>main">Main</a>
	</li>
	<li class="nav-item">
		<a class="nav-link <?= $tab === 'variables' ? 'active' : '' ?>" href="/<?= $tab_url ?>variables">Variables</a>
	</li>
	<li class="nav-item">
		<a class="nav-link <?= $tab === 'counters' ? 'active' : '' ?>" href="/<?= $tab_url ?>counters">Counters</a>
	</li>
	<li class="nav-item">
		<a class="nav-link <?= $tab === 'cron' ? 'active' : '' ?>" href="/<?= $tab_url ?>cron">Cron</a>
	</li>
</ul>

<?php if ($tab === 'main') { ?>
<div class="row">
	<div class="col-12 col-md-4 mb-3">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="card-title">Users</h6>
				<p class="mb-2"><strong><?= $users_count ?></strong> user(s)</p>
				<button type="button" class="btn btn-primary btn-sm settings-embed-open" data-src="/admin.php?m=users&embed=1" data-title="Users">Edit</button>
			</div>
		</div>
	</div>
	<div class="col-12 col-md-4 mb-3">
		<div class="card h-100">
			<div class="card-body">
				<h6 class="card-title">Roles</h6>
				<p class="mb-2"><strong><?= $roles_count ?></strong> role(s)</p>
				<button type="button" class="btn btn-primary btn-sm settings-embed-open" data-src="/admin.php?m=user_types&embed=1" data-title="Roles">Edit</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="settingsEmbedModal" tabindex="-1" role="dialog" aria-labelledby="settingsEmbedModalTitle" style="z-index: 1060;">
	<div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 95%; height: 90vh;" role="document">
		<div class="modal-content" style="height: 90vh;">
			<div class="modal-header py-2">
				<h5 class="modal-title" id="settingsEmbedModalTitle">Edit</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body p-0 flex-grow-1 overflow-hidden" style="min-height: 0;">
				<iframe id="settingsEmbedIframe" style="width:100%;height:100%;min-height:70vh;border:0;"></iframe>
			</div>
		</div>
	</div>
</div>
<script>
(function(){
	document.querySelectorAll('.settings-embed-open').forEach(function(btn){
		btn.addEventListener('click', function(){
			var src = btn.getAttribute('data-src');
			var title = btn.getAttribute('data-title') || 'Edit';
			var modal = document.getElementById('settingsEmbedModal');
			var iframe = document.getElementById('settingsEmbedIframe');
			if (modal && iframe) {
				document.getElementById('settingsEmbedModalTitle').textContent = title;
				iframe.src = src;
				if (typeof $ !== 'undefined' && $.fn.modal) $('#settingsEmbedModal').modal('show');
				else { modal.classList.add('show'); modal.style.display = 'block'; }
			}
		});
	});
})();
</script>
<?php } ?>

<?php if ($tab === 'variables') { ?>
<?php if (!$variables_exists) { ?>
<div class="alert alert-info">Create table <code>variables</code> (id, <code>key</code>, value) to use this tab.</div>
<?php } else {
	$variables_list = isset($q['variables_list']) && is_array($q['variables_list']) ? $q['variables_list'] : array();
	$variables_map_json = isset($q['variables_map_json']) ? (string)$q['variables_map_json'] : '{}';
	$logs_cleanup_cfg = isset($q['logs_cleanup_cfg']) && is_array($q['logs_cleanup_cfg']) ? $q['logs_cleanup_cfg'] : array('days' => 30, 'interval_hours' => 24, 'last_run' => '');
	$jobs_cleanup_cfg = isset($q['jobs_cleanup_cfg']) && is_array($q['jobs_cleanup_cfg']) ? $q['jobs_cleanup_cfg'] : array('days' => 30, 'interval_hours' => 24, 'last_run' => '', 'statuses' => array('done', 'failed'));
	$logs_stats = isset($q['logs_stats']) && is_array($q['logs_stats']) ? $q['logs_stats'] : array('exists' => false, 'rows' => 0, 'size_mb' => 0);
	$jobs_stats = isset($q['jobs_stats']) && is_array($q['jobs_stats']) ? $q['jobs_stats'] : array('exists' => false, 'rows' => 0, 'size_mb' => 0);
	$logs_cleanup_cron_path = isset($q['logs_cleanup_cron_path']) ? (string)$q['logs_cleanup_cron_path'] : '';
	$logs_cleanup_saved = !empty($q['logs_cleanup_saved']);
	$jobs_cleanup_saved = !empty($q['jobs_cleanup_saved']);
	$logs_cleanup_msg = isset($q['logs_cleanup_msg']) ? trim((string)$q['logs_cleanup_msg']) : '';
	$jobs_cleanup_msg = isset($q['jobs_cleanup_msg']) ? trim((string)$q['jobs_cleanup_msg']) : '';
	$lcd = isset($logs_cleanup_cfg['days']) ? (int)$logs_cleanup_cfg['days'] : 30;
	$lci = isset($logs_cleanup_cfg['interval_hours']) ? (int)$logs_cleanup_cfg['interval_hours'] : 24;
	$lcl = isset($logs_cleanup_cfg['last_run']) ? trim((string)$logs_cleanup_cfg['last_run']) : '';
	$jcd = isset($jobs_cleanup_cfg['days']) ? (int)$jobs_cleanup_cfg['days'] : 30;
	$jci = isset($jobs_cleanup_cfg['interval_hours']) ? (int)$jobs_cleanup_cfg['interval_hours'] : 24;
	$jcl = isset($jobs_cleanup_cfg['last_run']) ? trim((string)$jobs_cleanup_cfg['last_run']) : '';
	$jst = isset($jobs_cleanup_cfg['statuses']) && is_array($jobs_cleanup_cfg['statuses']) ? $jobs_cleanup_cfg['statuses'] : array('done', 'failed');
	$aj_done = in_array('done', $jst, true);
	$aj_failed = in_array('failed', $jst, true);
	$aj_cancelled = in_array('cancelled', $jst, true);
?>
<?php if ($logs_cleanup_saved) { ?>
<div class="alert alert-success mb-3">System logs cleanup settings saved.</div>
<?php } ?>
<?php if ($jobs_cleanup_saved) { ?>
<div class="alert alert-success mb-3">Job queue cleanup settings saved.</div>
<?php } ?>
<?php if ($saved && !$logs_cleanup_saved && !$jobs_cleanup_saved) { ?>
<div class="alert alert-success mb-3">Settings saved.</div>
<?php } ?>
<?php if ($logs_cleanup_msg !== '') { ?>
<div class="alert alert-info mb-3"><?= htmlspecialchars($logs_cleanup_msg) ?></div>
<?php } ?>
<?php if ($jobs_cleanup_msg !== '') { ?>
<div class="alert alert-info mb-3"><?= htmlspecialchars($jobs_cleanup_msg) ?></div>
<?php } ?>

<h6 class="text-uppercase text-muted small font-weight-bold mb-2">Logs and retention</h6>

<div class="row">
	<div class="col-12 col-lg-6 mb-3">
		<div class="card h-100 border-primary">
			<div class="card-body">
				<h6 class="card-title">System logs (<code>system_logs</code>)</h6>
				<?php if (!empty($logs_stats['exists'])) { ?>
				<p class="small mb-2"><strong><?= (int)$logs_stats['rows'] ?></strong> row(s)<?php if (!empty($logs_stats['size_mb'])) { ?>, ~<strong><?= htmlspecialchars((string)$logs_stats['size_mb']) ?></strong> MB<?php } ?>. <a href="/admin.php?m=logs">Open Logs</a></p>
				<?php } else { ?>
				<p class="small text-muted mb-2">Table <code>system_logs</code> not found.</p>
				<?php } ?>
				<form method="post" action="/admin.php?m=settings&tab=variables" class="mb-3">
					<input type="hidden" name="system_logs_cleanup_save" value="1" />
					<div class="form-row align-items-end">
						<div class="form-group col-md-6 mb-2 mb-md-0">
							<label class="small font-weight-bold mb-1">Keep entries (days)</label>
							<input type="number" class="form-control form-control-sm" name="system_logs_cleanup_days" min="1" max="3650" value="<?= (int)$lcd ?>" />
						</div>
						<div class="form-group col-md-6 mb-2 mb-md-0">
							<label class="small font-weight-bold mb-1">Min interval (hours)</label>
							<input type="number" class="form-control form-control-sm" name="system_logs_cleanup_interval_hours" min="1" max="720" value="<?= (int)$lci ?>" title="At most once per this many hours (unless Run now / --force)" />
						</div>
					</div>
					<button type="submit" class="btn btn-primary btn-sm mt-2">Save</button>
				</form>
				<?php if ($lcl !== '') { ?><p class="small text-muted mb-2">Last cleanup: <code><?= htmlspecialchars($lcl) ?></code></p><?php } ?>
				<form method="post" action="/admin.php?m=settings&tab=variables" class="form-inline flex-wrap align-items-center">
					<input type="hidden" name="system_logs_cleanup_run_now" value="1" />
					<label class="small mb-0 mr-2"><input type="checkbox" name="system_logs_cleanup_dry_run" value="1" /> Dry run</label>
					<button type="submit" class="btn btn-outline-success btn-sm">Run now</button>
				</form>
			</div>
		</div>
	</div>
	<div class="col-12 col-lg-6 mb-3">
		<div class="card h-100 border-primary">
			<div class="card-body">
				<h6 class="card-title">Job queue (<code>admin_jobs</code>)</h6>
				<?php if (!empty($jobs_stats['exists'])) { ?>
				<p class="small mb-2"><strong><?= (int)$jobs_stats['rows'] ?></strong> row(s)<?php if (!empty($jobs_stats['size_mb'])) { ?>, ~<strong><?= htmlspecialchars((string)$jobs_stats['size_mb']) ?></strong> MB<?php } ?>. <a href="/admin.php?m=jobs">Open Jobs</a></p>
				<?php } else { ?>
				<p class="small text-muted mb-2">Table <code>admin_jobs</code> not found.</p>
				<?php } ?>
				<form method="post" action="/admin.php?m=settings&tab=variables" class="mb-3">
					<input type="hidden" name="admin_jobs_cleanup_save" value="1" />
					<div class="form-row align-items-end">
						<div class="form-group col-md-4 mb-2 mb-md-0">
							<label class="small font-weight-bold mb-1">Keep rows (days)</label>
							<input type="number" class="form-control form-control-sm" name="admin_jobs_cleanup_days" min="1" max="3650" value="<?= (int)$jcd ?>" />
						</div>
						<div class="form-group col-md-4 mb-2 mb-md-0">
							<label class="small font-weight-bold mb-1">Min interval (h)</label>
							<input type="number" class="form-control form-control-sm" name="admin_jobs_cleanup_interval_hours" min="1" max="720" value="<?= (int)$jci ?>" />
						</div>
						<div class="form-group col-md-12 mb-0 mt-2">
							<span class="small font-weight-bold d-block mb-1">Statuses to clean</span>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" name="aj_st_done" value="1" id="aj-st-done" <?= $aj_done ? 'checked' : '' ?> />
								<label class="form-check-label small" for="aj-st-done">done</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" name="aj_st_failed" value="1" id="aj-st-failed" <?= $aj_failed ? 'checked' : '' ?> />
								<label class="form-check-label small" for="aj-st-failed">failed</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" name="aj_st_cancelled" value="1" id="aj-st-cancelled" <?= $aj_cancelled ? 'checked' : '' ?> />
								<label class="form-check-label small" for="aj-st-cancelled">cancelled</label>
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary btn-sm mt-2">Save</button>
				</form>
				<?php if ($jcl !== '') { ?><p class="small text-muted mb-2">Last jobs cleanup: <code><?= htmlspecialchars($jcl) ?></code></p><?php } ?>
				<form method="post" action="/admin.php?m=settings&tab=variables" class="form-inline flex-wrap align-items-center">
					<input type="hidden" name="admin_jobs_cleanup_run_now" value="1" />
					<label class="small mb-0 mr-2"><input type="checkbox" name="admin_jobs_cleanup_dry_run" value="1" /> Dry run</label>
					<button type="submit" class="btn btn-outline-success btn-sm">Run now</button>
				</form>
			</div>
		</div>
	</div>
</div>

<h6 class="text-uppercase text-muted small font-weight-bold mb-2 mt-2">Other variables</h6>

<div class="row" id="settings-variables-cards">
	<?php foreach ($variables_list as $v) {
		$pv = (string)$v['value'];
		$prev = function_exists('mb_strlen') && function_exists('mb_substr')
			? (mb_strlen($pv) > 100 ? mb_substr($pv, 0, 100) . '…' : $pv)
			: (strlen($pv) > 100 ? substr($pv, 0, 100) . '…' : $pv);
	?>
	<div class="col-12 col-md-6 col-xl-4 mb-3">
		<div class="card h-100 settings-var-card">
			<div class="card-body d-flex flex-column">
				<h6 class="card-title mb-1"><code class="small"><?= htmlspecialchars($v['key']) ?></code></h6>
				<p class="small text-muted flex-grow-1 mb-2 settings-var-preview" style="max-height:4.5rem;overflow:hidden;"><?= nl2br(htmlspecialchars($prev)) ?></p>
				<button type="button" class="btn btn-sm btn-outline-primary settings-var-edit" data-toggle="modal" data-target="#settingsVariableModal" data-key="<?= htmlspecialchars($v['key']) ?>">Edit</button>
			</div>
		</div>
	</div>
	<?php } ?>
</div>
<?php if (empty($variables_list)) { ?>
<?php } ?>

<div class="modal fade" id="settingsVariableModal" tabindex="-1" role="dialog" aria-labelledby="settingsVariableModalTitle">
	<div class="modal-dialog modal-lg" role="document">
		<form method="post" action="/admin.php?m=settings&tab=variables" class="modal-content">
			<div class="modal-header py-2">
				<h5 class="modal-title" id="settingsVariableModalTitle">Variable</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<input type="hidden" name="settings_save" value="1" />
				<label class="small font-weight-bold d-block mb-1">Value</label>
				<textarea class="form-control form-control-sm" id="settingsModalTextarea" rows="10" style="min-height:12rem;"></textarea>
				<div class="form-check mt-3">
					<input class="form-check-input" type="checkbox" id="settingsModalDelete" value="1" />
					<label class="form-check-label small text-danger" for="settingsModalDelete">Delete this variable</label>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-primary btn-sm">Save</button>
			</div>
		</form>
	</div>
</div>

<div class="card mb-4">
	<div class="card-body">
		<h6 class="card-title">Add variable</h6>
		<form method="post" action="/admin.php?m=settings&tab=variables">
			<input type="hidden" name="settings_save" value="1" />
			<div class="row align-items-end">
				<div class="col-12 col-md-3 mb-2 mb-md-0">
					<label class="small font-weight-bold">New key</label>
					<input type="text" class="form-control form-control-sm" name="new_key" value="" placeholder="e.g. site_name" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, underscore only" />
				</div>
				<div class="col-12 col-md-7 mb-2 mb-md-0">
					<label class="small font-weight-bold">New value</label>
					<input type="text" class="form-control form-control-sm" name="new_value" value="" placeholder="Value" />
				</div>
				<div class="col-12 col-md-2">
					<button type="submit" class="btn btn-primary btn-sm">Add</button>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
(function(){
	var map = <?= $variables_map_json ?>;
	if (typeof map !== 'object' || map === null) map = {};
	function bindModal() {
		var $m = typeof jQuery !== 'undefined' ? jQuery('#settingsVariableModal') : null;
		if (!$m || !$m.on) return;
		$m.on('show.bs.modal', function(e){
			var btn = e.relatedTarget;
			var key = btn && btn.getAttribute ? (btn.getAttribute('data-key') || '') : '';
			var ta = document.getElementById('settingsModalTextarea');
			var del = document.getElementById('settingsModalDelete');
			var titleEl = document.getElementById('settingsVariableModalTitle');
			if (titleEl) titleEl.textContent = key ? ('Edit: ' + key) : 'Variable';
			if (ta) {
				ta.setAttribute('name', key ? ('settings[' + key + ']') : 'settings[_invalid]');
				ta.value = (key && map[key] !== undefined) ? String(map[key]) : '';
			}
			if (del) {
				del.checked = false;
				if (key) del.setAttribute('name', 'settings_delete[' + key + ']');
				else del.removeAttribute('name');
			}
		});
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindModal);
	else bindModal();
})();
</script>
<?php }
} ?>

<?php if ($tab === 'counters') { ?>
<?php
$counters = isset($q['counters']) && is_array($q['counters']) ? $q['counters'] : array();
$counters_settings = isset($q['counters_settings']) && is_array($q['counters_settings']) ? $q['counters_settings'] : array('source' => 'json', 'onesignal_web_enabled' => 1);
$counters_json_path = isset($q['counters_json_path']) ? (string)$q['counters_json_path'] : '';
$counters_json_exists = !empty($q['counters_json_exists']);
$import_msg = isset($_GET['import_msg']) ? (string)$_GET['import_msg'] : '';
?>
<?php if (!$variables_exists) { ?>
<div class="alert alert-info">Create table <code>variables</code> (id, <code>key</code>, value) to save counters. Until then, the default Counter.dev script is used on the site.</div>
<?php } ?>
<div class="card mb-4">
	<div class="card-body">
		<h6 class="card-title mb-3">Counters source &amp; OneSignal</h6>
		<p class="small text-muted mb-3">Reference file (git): <code>files/reference/counters.json</code><?php if ($counters_json_path !== '') { ?> — <?php if ($counters_json_exists) { ?><span class="text-success">found</span><?php } else { ?><span class="text-warning">missing on server</span><?php } ?><?php } ?>. CLI: <code>php scripts/export_counters_cli.php db</code>, <code>php scripts/import_counters_cli.php counters.json both</code>.</p>
		<?php if ($import_msg !== '') { ?><div class="alert alert-info mb-3"><?= htmlspecialchars($import_msg) ?></div><?php } ?>
		<form method="post" action="/admin.php?m=settings&tab=counters" class="mb-0">
			<input type="hidden" name="counters_save" value="1" />
			<div class="form-row mb-3">
				<div class="col-md-4">
					<label class="small font-weight-bold">Load counters from</label>
					<select class="form-control form-control-sm" name="counters_source">
						<option value="json" <?= (isset($counters_settings['source']) && $counters_settings['source'] === 'json') ? 'selected' : '' ?>>JSON file (reference)</option>
						<option value="db" <?= (isset($counters_settings['source']) && $counters_settings['source'] === 'db') ? 'selected' : '' ?>>Database (admin edits below)</option>
					</select>
				</div>
				<div class="col-md-4 pt-md-4">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" name="onesignal_web_enabled" value="1" id="onesignalWebEnabled" <?= !empty($counters_settings['onesignal_web_enabled']) ? 'checked' : '' ?> />
						<label class="form-check-label small" for="onesignalWebEnabled">OneSignal web push (custom counter code)</label>
					</div>
				</div>
				<div class="col-md-4 pt-md-3 text-md-right">
					<a class="btn btn-outline-secondary btn-sm" href="/admin.php?m=settings&tab=counters&counters_export=1">Export DB → JSON</a>
				</div>
			</div>
			<div class="form-row mb-3 align-items-end">
				<div class="col-md-8">
					<label class="small font-weight-bold">Import JSON → database</label>
				</div>
				<div class="col-md-4">
				</div>
			</div>
			<?php if ($saved) { ?><div class="alert alert-success mb-3">Counters saved.</div><?php } ?>
			<div id="counters-list">
				<?php foreach ($counters as $i => $c) {
					$name = isset($c['name']) ? $c['name'] : '';
					$kind = isset($c['kind']) ? $c['kind'] : '';
					$display = !empty($c['display']);
					$has_place = isset($c['place_head']) || isset($c['place_body']) || isset($c['place_footer']);
					$place_head = $has_place ? !empty($c['place_head']) : true;
					$place_body = !empty($c['place_body']);
					$place_footer = !empty($c['place_footer']);
					$split_row = array_key_exists('code_head', $c) || array_key_exists('code_body', $c) || array_key_exists('code_footer', $c);
					$legacy_code = isset($c['code']) ? (string)$c['code'] : '';
					if ($split_row) {
						$code_head = isset($c['code_head']) ? $c['code_head'] : '';
						$code_body = isset($c['code_body']) ? $c['code_body'] : '';
						$code_footer = isset($c['code_footer']) ? $c['code_footer'] : '';
					} else {
						$code_head = ($place_head || !$has_place) ? $legacy_code : '';
						$code_body = $place_body ? $legacy_code : '';
						$code_footer = $place_footer ? $legacy_code : '';
					}
				?>
				<div class="counter-row border rounded p-3 mb-3 position-relative" data-index="<?= (int)$i ?>">
					<button type="button" class="btn btn-outline-danger btn-sm position-absolute" style="top: 0.5rem; right: 0.5rem;" title="Remove" aria-label="Remove counter">&times;</button>
					<div class="row align-items-start">
						<div class="col-12 col-md-4 mb-2 mb-md-0">
							<label class="small font-weight-bold">Name</label>
							<input type="text" class="form-control form-control-sm" name="counters[<?= (int)$i ?>][name]" value="<?= htmlspecialchars($name) ?>" placeholder="e.g. GTM" />
							<input type="hidden" name="counters[<?= (int)$i ?>][kind]" value="<?= htmlspecialchars($kind) ?>" />
						</div>
						<div class="col-12 col-md-2 mb-2 mb-md-0 pt-md-4">
							<label class="small d-block">On</label>
							<input type="checkbox" name="counters[<?= (int)$i ?>][display]" value="1" <?= $display ? 'checked' : '' ?> />
						</div>
					</div>
					<div class="row mt-2">
						<div class="col-12">
							<span class="small font-weight-bold d-block mb-1">Placement</span>
							<div class="form-check form-check-inline">
								<input class="form-check-input counter-place-toggle" type="checkbox" name="counters[<?= (int)$i ?>][place_head]" value="1" data-zone="head" id="cph-<?= (int)$i ?>" <?= $place_head ? 'checked' : '' ?> />
								<label class="form-check-label small" for="cph-<?= (int)$i ?>">Head</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input counter-place-toggle" type="checkbox" name="counters[<?= (int)$i ?>][place_body]" value="1" data-zone="body" id="cpb-<?= (int)$i ?>" <?= $place_body ? 'checked' : '' ?> />
								<label class="form-check-label small" for="cpb-<?= (int)$i ?>">Body (after &lt;body&gt;)</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input counter-place-toggle" type="checkbox" name="counters[<?= (int)$i ?>][place_footer]" value="1" data-zone="footer" id="cpf-<?= (int)$i ?>" <?= $place_footer ? 'checked' : '' ?> />
								<label class="form-check-label small" for="cpf-<?= (int)$i ?>">Footer (before &lt;/body&gt;)</label>
							</div>
						</div>
					</div>
					<div class="mt-3">
						<label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">inside &lt;head&gt;</span></label>
						<textarea class="form-control form-control-sm counter-code-zone<?= $place_head ? '' : ' bg-light text-muted' ?>" data-zone="head" name="counters[<?= (int)$i ?>][code_head]" rows="3" placeholder="&lt;script&gt;…&lt;/script&gt;"<?= $place_head ? '' : ' readonly' ?>><?= htmlspecialchars($code_head) ?></textarea>
					</div>
					<div class="mt-2">
						<label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">right after &lt;body&gt;</span></label>
						<textarea class="form-control form-control-sm counter-code-zone<?= $place_body ? '' : ' bg-light text-muted' ?>" data-zone="body" name="counters[<?= (int)$i ?>][code_body]" rows="3" placeholder="&lt;noscript&gt;…&lt;/noscript&gt;"<?= $place_body ? '' : ' readonly' ?>><?= htmlspecialchars($code_body) ?></textarea>
					</div>
					<div class="mt-2">
						<label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">before &lt;/body&gt;</span></label>
						<textarea class="form-control form-control-sm counter-code-zone<?= $place_footer ? '' : ' bg-light text-muted' ?>" data-zone="footer" name="counters[<?= (int)$i ?>][code_footer]" rows="3" placeholder="Optional late scripts"<?= $place_footer ? '' : ' readonly' ?>><?= htmlspecialchars($code_footer) ?></textarea>
					</div>
				</div>
				<?php } ?>
			</div>
			<div class="mb-3">
				<button type="button" class="btn btn-outline-secondary btn-sm" id="counters-add">+ Add counter</button>
			</div>
			<button type="submit" class="btn btn-primary">Save counters &amp; settings</button>
		</form>
		<form method="post" action="/admin.php?m=settings&tab=counters" enctype="multipart/form-data" class="mt-3 pt-3 border-top">
			<div class="form-row align-items-end">
				<div class="col-md-8">
					<label class="small font-weight-bold">Import JSON file → database</label>
					<input type="file" class="form-control-file form-control-sm" name="counters_json_file" accept=".json,application/json" required />
				</div>
				<div class="col-md-4">
					<button type="submit" name="counters_import_json" value="1" class="btn btn-outline-primary btn-sm">Import to DB</button>
				</div>
			</div>
		</form>
	</div>
</div>
<style>.counter-code-zone[readonly]{background-color:#f8f9fa;color:#6c757d;}</style>
<script>
(function(){
	var index = <?= count($counters) ?>;
	function syncRow(row) {
		row.querySelectorAll('.counter-place-toggle').forEach(function(cb) {
			var z = cb.getAttribute('data-zone');
			var ta = row.querySelector('textarea[name*="[code_' + z + ']"]');
			if (!ta) return;
			ta.readOnly = !cb.checked;
			ta.classList.toggle('bg-light', !cb.checked);
			ta.classList.toggle('text-muted', !cb.checked);
		});
	}
	function counterRowHtml(i, placeHead, placeBody, placeFooter) {
		var roHead = placeHead ? '' : ' readonly';
		var roBody = placeBody ? '' : ' readonly';
		var roFoot = placeFooter ? '' : ' readonly';
		return '<button type="button" class="btn btn-outline-danger btn-sm position-absolute" style="top: 0.5rem; right: 0.5rem;" title="Remove" aria-label="Remove">&times;</button>' +
			'<div class="row align-items-start">' +
			'<div class="col-12 col-md-4 mb-2 mb-md-0"><label class="small font-weight-bold">Name</label><input type="text" class="form-control form-control-sm" name="counters[' + i + '][name]" value="" placeholder="e.g. GTM" /></div>' +
			'<div class="col-12 col-md-2 mb-2 mb-md-0 pt-md-4"><label class="small d-block">On</label><input type="checkbox" name="counters[' + i + '][display]" value="1" checked /></div></div>' +
			'<div class="row mt-2"><div class="col-12"><span class="small font-weight-bold d-block mb-1">Placement</span>' +
			'<div class="form-check form-check-inline"><input class="form-check-input counter-place-toggle" type="checkbox" name="counters[' + i + '][place_head]" value="1" data-zone="head" id="cph-' + i + '"' + (placeHead ? ' checked' : '') + ' /><label class="form-check-label small" for="cph-' + i + '">Head</label></div>' +
			'<div class="form-check form-check-inline"><input class="form-check-input counter-place-toggle" type="checkbox" name="counters[' + i + '][place_body]" value="1" data-zone="body" id="cpb-' + i + '"' + (placeBody ? ' checked' : '') + ' /><label class="form-check-label small" for="cpb-' + i + '">Body (after &lt;body&gt;)</label></div>' +
			'<div class="form-check form-check-inline"><input class="form-check-input counter-place-toggle" type="checkbox" name="counters[' + i + '][place_footer]" value="1" data-zone="footer" id="cpf-' + i + '"' + (placeFooter ? ' checked' : '') + ' /><label class="form-check-label small" for="cpf-' + i + '">Footer (before &lt;/body&gt;)</label></div></div></div>' +
			'<div class="mt-3"><label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">inside &lt;head&gt;</span></label><textarea class="form-control form-control-sm counter-code-zone" name="counters[' + i + '][code_head]" rows="3" placeholder="&lt;script&gt;…&lt;/script&gt;"' + roHead + '></textarea></div>' +
			'<div class="mt-2"><label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">right after &lt;body&gt;</span></label><textarea class="form-control form-control-sm counter-code-zone" name="counters[' + i + '][code_body]" rows="3" placeholder="&lt;noscript&gt;…&lt;/noscript&gt;"' + roBody + '></textarea></div>' +
			'<div class="mt-2"><label class="small font-weight-bold d-block mb-1">Code — <span class="text-muted font-weight-normal">before &lt;/body&gt;</span></label><textarea class="form-control form-control-sm counter-code-zone" name="counters[' + i + '][code_footer]" rows="3" placeholder="Optional late scripts"' + roFoot + '></textarea></div>';
	}
	document.getElementById('counters-list').addEventListener('change', function(e) {
		if (e.target && e.target.classList.contains('counter-place-toggle')) {
			syncRow(e.target.closest('.counter-row'));
		}
	});
	document.getElementById('counters-add').addEventListener('click', function(){
		var list = document.getElementById('counters-list');
		var div = document.createElement('div');
		div.className = 'counter-row border rounded p-3 mb-3 position-relative';
		div.setAttribute('data-index', index);
		div.innerHTML = counterRowHtml(index, true, false, false);
		list.appendChild(div);
		syncRow(div);
		div.querySelector('button').addEventListener('click', function(){ div.remove(); });
		index++;
	});
	document.querySelectorAll('#counters-list .counter-row').forEach(syncRow);
	document.querySelectorAll('#counters-list .counter-row > button[type="button"]').forEach(function(btn){
		btn.addEventListener('click', function(){ btn.closest('.counter-row').remove(); });
	});
})();
</script>
<?php } ?>

<?php if ($tab === 'cron') { ?>
<?php
	$cron_schedule = isset($q['cron_schedule']) && is_array($q['cron_schedule']) ? $q['cron_schedule'] : array();
	$cron_tasks = isset($cron_schedule['tasks']) && is_array($cron_schedule['tasks']) ? $cron_schedule['tasks'] : array();
	$cron_tick_last = isset($cron_schedule['tick_last_run']) ? trim((string)$cron_schedule['tick_last_run']) : '';
	$cron_crontab_line = isset($cron_schedule['crontab_line']) ? trim((string)$cron_schedule['crontab_line']) : '';
	$cron_msg = isset($q['cron_msg']) ? trim((string)$q['cron_msg']) : '';
	$variables_exists_cron = !empty($q['variables_exists']);
?>
<?php if ($saved) { ?>
<div class="alert alert-success mb-3">Cron schedule saved.</div>
<?php } ?>
<?php if ($cron_msg !== '') { ?>
<div class="alert alert-info mb-3"><pre class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($cron_msg) ?></pre></div>
<?php } ?>
<?php if (!$variables_exists_cron) { ?>
<div class="alert alert-warning">Table <code>variables</code> is required. Create it to use cron scheduling.</div>
<?php } else { ?>
<div class="card border-primary mb-4">
	<div class="card-body">
		<h6 class="card-title">Server crontab (one line per site)</h6>
		<p class="small text-muted mb-2">Add this line on the server; task intervals are configured below (not in crontab).</p>
		<pre class="bg-light p-2 small mb-0"><code><?= htmlspecialchars($cron_crontab_line !== '' ? $cron_crontab_line : 'php /path/to/public_html/cron/run.php tick') ?></code></pre>
		<?php if ($cron_tick_last !== '') { ?>
		<p class="small text-muted mt-2 mb-0">Last tick: <code><?= htmlspecialchars($cron_tick_last) ?></code></p>
		<?php } ?>
	</div>
</div>

<form method="post" action="/admin.php?m=settings&tab=cron" class="mb-3">
	<input type="hidden" name="cron_schedule_save" value="1" />
	<div class="table-responsive">
		<table class="table table-sm table-bordered bg-white">
			<thead class="thead-light">
				<tr>
					<th>On</th>
					<th>Task</th>
					<th>Every (min)</th>
					<th>Last run</th>
					<th>Status</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($cron_tasks as $tid => $trow) {
				$en = !empty($trow['enabled']);
				$iv = isset($trow['interval_minutes']) ? (int)$trow['interval_minutes'] : 1;
				$lr = isset($trow['last_run']) ? trim((string)$trow['last_run']) : '';
				$st = isset($trow['last_status']) ? trim((string)$trow['last_status']) : '';
				$lbl = isset($trow['label']) ? (string)$trow['label'] : $tid;
				$desc = isset($trow['description']) ? (string)$trow['description'] : '';
			?>
				<tr>
					<td class="align-middle text-center">
						<input type="checkbox" name="cron_task[<?= htmlspecialchars($tid) ?>][enabled]" value="1" <?= $en ? 'checked' : '' ?> />
					</td>
					<td class="align-middle">
						<strong><?= htmlspecialchars($lbl) ?></strong>
						<div class="small text-muted"><code><?= htmlspecialchars($tid) ?></code><?php if ($desc !== '') { ?> — <?= htmlspecialchars($desc) ?><?php } ?></div>
					</td>
					<td class="align-middle" style="max-width:7rem;">
						<input type="number" class="form-control form-control-sm" name="cron_task[<?= htmlspecialchars($tid) ?>][interval_minutes]" min="1" max="43200" value="<?= (int)$iv ?>" />
					</td>
					<td class="align-middle small"><?php if ($lr !== '') { ?><code><?= htmlspecialchars($lr) ?></code><?php } else { ?><span class="text-muted">—</span><?php } ?></td>
					<td class="align-middle small"><?= $st !== '' ? htmlspecialchars($st) : '—' ?></td>
					<td class="align-middle">
						<button type="submit" formaction="/admin.php?m=settings&tab=cron" formmethod="post" name="cron_task_run_now" value="1" class="btn btn-outline-secondary btn-sm" onclick="this.form.cron_task_id.value='<?= htmlspecialchars($tid, ENT_QUOTES) ?>';">Run</button>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
	<input type="hidden" name="cron_task_id" value="" />
	<button type="submit" class="btn btn-primary btn-sm">Save schedule</button>
</form>

<form method="post" action="/admin.php?m=settings&tab=cron" class="form-inline flex-wrap align-items-center">
	<input type="hidden" name="cron_tick_run_now" value="1" />
	<label class="small mb-0 mr-2"><input type="checkbox" name="cron_tick_force" value="1" /> Force (ignore intervals)</label>
	<button type="submit" class="btn btn-outline-success btn-sm">Run tick now</button>
</form>
<?php } ?>
<?php } ?>
