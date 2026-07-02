<?php
/**
 * Jobs queue UI (admin_jobs).
 */
$jobs = isset($q['jobs']) ? $q['jobs'] : array();
$job_detail = isset($q['job_detail']) ? $q['job_detail'] : null;
$job_msg = isset($q['job_msg']) ? $q['job_msg'] : '';
$counts = isset($q['counts']) ? $q['counts'] : array();
$status_filter = isset($q['status_filter']) ? $q['status_filter'] : '';
$module_filter = isset($q['module_filter']) ? $q['module_filter'] : '';
$module_opts = isset($q['module_opts']) ? $q['module_opts'] : array();
$page = isset($q['page']) ? (int)$q['page'] : 1;
$total_pages = isset($q['total_pages']) ? (int)$q['total_pages'] : 1;
$base_url = isset($q['base_url']) ? $q['base_url'] : '/admin.php?m=jobs';
$log_cleanup_url = isset($q['log_cleanup_url']) ? $q['log_cleanup_url'] : '/admin.php?m=jobs&u=log_cleanup';
$logs_table_ok = !empty($q['logs_table_ok']);

function _jobs_status_badge($status) {
	$class = 'secondary';
	if ($status === 'pending') $class = 'warning';
	elseif ($status === 'running') $class = 'info';
	elseif ($status === 'done') $class = 'success';
	elseif ($status === 'failed') $class = 'danger';
	elseif ($status === 'cancelled') $class = 'dark';
	return '<span class="badge badge-' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>

<?php if ($job_msg !== '') { ?>
<div class="alert alert-info alert-dismissible fade show"><?= htmlspecialchars($job_msg) ?> <a href="<?= htmlspecialchars($base_url) ?>" class="alert-link">Back to list</a> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php } ?>
<?php if (!empty($q['log_cleanup']) && isset($q['log_cleanup_deleted']) && (int)$q['log_cleanup_deleted'] >= 0) { ?>
<div class="alert alert-success alert-dismissible fade show">Log cleanup: removed <?= (int)$q['log_cleanup_deleted'] ?> old entries (older than <?= (int)($q['log_cleanup_days'] ?? 30) ?> days). Same retention as <a href="/admin.php?m=logs">Logs</a>. <button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php } ?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
	<ul class="nav nav-tabs">
		<li class="nav-item">
			<a class="nav-link<?= $status_filter === '' ? ' active' : '' ?>" href="/admin.php?m=jobs<?= $module_filter !== '' ? '&module=' . urlencode($module_filter) : '' ?>">
				All <span class="badge badge-light"><?= (int)@$counts['all'] ?></span>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= $status_filter === 'pending' ? ' active' : '' ?>" href="/admin.php?m=jobs&status=pending<?= $module_filter !== '' ? '&module=' . urlencode($module_filter) : '' ?>">
				Pending <span class="badge badge-warning"><?= (int)@$counts['pending'] ?></span>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= $status_filter === 'running' ? ' active' : '' ?>" href="/admin.php?m=jobs&status=running<?= $module_filter !== '' ? '&module=' . urlencode($module_filter) : '' ?>">
				Running <span class="badge badge-info"><?= (int)@$counts['running'] ?></span>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= $status_filter === 'done' ? ' active' : '' ?>" href="/admin.php?m=jobs&status=done<?= $module_filter !== '' ? '&module=' . urlencode($module_filter) : '' ?>">
				Done <span class="badge badge-success"><?= (int)@$counts['done'] ?></span>
			</a>
		</li>
		<li class="nav-item">
			<a class="nav-link<?= $status_filter === 'failed' ? ' active' : '' ?>" href="/admin.php?m=jobs&status=failed<?= $module_filter !== '' ? '&module=' . urlencode($module_filter) : '' ?>">
				Failed <span class="badge badge-danger"><?= (int)@$counts['failed'] ?></span>
			</a>
		</li>
	</ul>

	<form method="get" class="form-inline mt-2">
		<input type="hidden" name="m" value="jobs">
		<?php if ($status_filter !== '') { ?><input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>"><?php } ?>
		<label class="mr-2 small text-muted">Module</label>
		<select name="module" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
			<option value="">All</option>
			<?php foreach ($module_opts as $m) { ?>
			<option value="<?= htmlspecialchars($m) ?>"<?= $m === $module_filter ? ' selected' : '' ?>><?= htmlspecialchars($m) ?></option>
			<?php } ?>
		</select>
		<noscript><button class="btn btn-sm btn-primary" type="submit">Filter</button></noscript>
	</form>
</div>

<?php if ($job_detail) { ?>
<div class="card mb-4 border-primary">
	<div class="card-body">
		<h5 class="card-title">Job #<?= (int)$job_detail['id'] ?> — Raw</h5>
		<?php if ($job_detail['status'] === 'failed' && !empty($job_detail['message'])) { ?>
		<div class="alert alert-danger mb-2"><strong>Error reason:</strong> <?= nl2br(htmlspecialchars($job_detail['message'])) ?></div>
		<?php } ?>
		<pre class="bg-light p-3 small mb-2" style="max-height:300px; overflow:auto"><?= htmlspecialchars(json_encode($job_detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
		<div class="btn-group mt-2 flex-wrap">
			<?php if ($job_detail['status'] === 'pending') { ?>
			<a href="/admin.php?m=jobs&job_do=run&id=<?= (int)$job_detail['id'] ?>" class="btn btn-primary btn-sm">Run now</a>
			<?php } ?>
			<?php if (in_array($job_detail['status'], array('pending','running'), true)) { ?>
			<a href="/admin.php?m=jobs&job_do=cancel&id=<?= (int)$job_detail['id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('Cancel this job?');">Cancel</a>
			<?php } ?>
			<a href="/admin.php?m=jobs&job_do=delete&id=<?= (int)$job_detail['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove job from queue?');">Delete</a>
			<a href="<?= htmlspecialchars($base_url) ?>" class="btn btn-secondary btn-sm">Close</a>
		</div>
	</div>
</div>
<?php } ?>

<div class="card mb-4">
	<div class="card-body">
		<h5 class="card-title d-flex justify-content-between align-items-center flex-wrap">
			<span>Queue (admin_jobs)</span>
			<span class="d-flex flex-wrap gap-2 align-items-center">
				<?php if ($logs_table_ok) { ?>
				<a href="<?= htmlspecialchars($log_cleanup_url) ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Delete system log entries older than retention?');">Clean old logs</a>
				<?php } ?>
				<a href="/cron/web_jobs.php" target="_blank" class="btn btn-outline-secondary btn-sm">Run one job now (cron)</a>
			</span>
		</h5>
		<form method="post" action="<?= htmlspecialchars((string)$base_url) . ((int)$page > 1 ? '&page=' . (int)$page : '') ?>">
		<div class="d-flex align-items-center justify-content-end gap-2 mb-2">
			<button type="submit" name="bulk_jobs_action" value="cancel_selected" class="btn btn-warning btn-sm" onclick="return confirm('Cancel selected jobs?');">Cancel selected</button>
			<button type="submit" name="bulk_jobs_action" value="delete_selected" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected jobs?');">Delete selected</button>
		</div>
		<div class="table-responsive">
			<table class="table table-sm table-hover table-responsive-stack">
				<thead>
					<tr>
						<th style="width:40px;"><input type="checkbox" id="jobsSelAll"></th>
						<th>ID</th>
						<th>Module</th>
						<th>Action</th>
						<th>Status</th>
						<th>Scheduled</th>
						<th>Priority</th>
						<th>Created</th>
						<th>Started</th>
						<th>Finished</th>
						<th>Message / Error</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($jobs as $j) {
						$jid = (int)$j['id'];
						$detail_url = '/admin.php?m=jobs&id=' . $jid . ($status_filter !== '' ? '&status=' . urlencode($status_filter) : '') . ($module_filter !== '' ? '&module=' . urlencode($module_filter) : '') . ($page > 1 ? '&page=' . $page : '');
						$scheduled = isset($j['scheduled_at']) && $j['scheduled_at'] !== '' ? $j['scheduled_at'] : (isset($j['created_at']) ? $j['created_at'] : '—');
						$msg_short = (isset($j['message']) && $j['message'] !== '' && $j['message'] !== null) ? (mb_strlen($j['message']) > 50 ? mb_substr($j['message'], 0, 47) . '…' : $j['message']) : '';
					?>
					<tr class="<?= $job_detail && (int)$job_detail['id'] === $jid ? 'table-primary' : '' ?>">
						<td><input type="checkbox" class="jobs_cb" name="job_ids[]" value="<?= $jid ?>"></td>
						<td><?= $jid ?></td>
						<td><code><?= htmlspecialchars($j['module']) ?></code></td>
						<td><?= htmlspecialchars($j['action']) ?></td>
						<td><?= _jobs_status_badge($j['status']) ?></td>
						<td class="small"><?= htmlspecialchars($scheduled) ?></td>
						<td class="small"><?= isset($j['priority']) ? (int)$j['priority'] : 0 ?></td>
						<td class="small"><?= htmlspecialchars($j['created_at']) ?></td>
						<td class="small"><?= htmlspecialchars(isset($j['started_at']) && $j['started_at'] !== '' ? $j['started_at'] : '—') ?></td>
						<td class="small"><?= htmlspecialchars(isset($j['finished_at']) && $j['finished_at'] !== '' ? $j['finished_at'] : '—') ?></td>
						<td class="small text-truncate" style="max-width: 200px" title="<?= $msg_short !== '' ? htmlspecialchars($j['message']) : '' ?>">
							<?php if ($j['status'] === 'failed' && $msg_short !== '') { ?>
							<span class="text-danger font-weight-bold"><?= htmlspecialchars($msg_short) ?></span>
							<?php } elseif ($msg_short !== '') { ?>
							<?= htmlspecialchars($msg_short) ?>
							<?php } else { ?>
							<span class="text-muted">—</span>
							<?php } ?>
						</td>
						<td class="small text-nowrap">
							<a href="<?= htmlspecialchars($detail_url) ?>" class="btn btn-sm btn-outline-secondary">View</a>
							<?php if ($j['status'] === 'pending') { ?>
							<a href="/admin.php?m=jobs&job_do=run&id=<?= $jid ?>" class="btn btn-sm btn-outline-primary">Run</a>
							<?php } ?>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<?php if (empty($jobs)) { ?>
		<p class="text-muted mb-0">No jobs<?= $status_filter !== '' ? ' with status «' . htmlspecialchars($status_filter) . '»' : '' ?>.</p>
		<?php } ?>

		<?php
		// Pagination (required when jobs > per_page)
		$tp = isset($total_pages) ? (int)$total_pages : 1;
		$cp = isset($page) ? (int)$page : 1;
		?>
		<?php if (!empty($tp) && $tp > 1) { ?>
			<?php
			$pages = array();
			if ($tp <= 7) {
				for ($i = 1; $i <= $tp; $i++) $pages[] = $i;
			} else {
				$pages[] = 1;
				$start = max(2, $cp - 1);
				$end = min($tp - 1, $cp + 1);
				if ($start > 2) $pages[] = 0; // ellipsis
				for ($i = $start; $i <= $end; $i++) $pages[] = $i;
				if ($end < $tp - 1) $pages[] = 0; // ellipsis
				$pages[] = $tp;
			}
			?>
			<div class="pagination pagination-bottom mt-3">
				<nav aria-label="Pagination">
					<ul class="pagination pagination-sm pagination-rounded mb-0">
						<?php foreach ($pages as $p) { ?>
							<?php if ((int)$p === 0) { ?>
								<li class="page-item"><span class="page-link">…</span></li>
							<?php } else { ?>
								<?php $isActive = ((int)$p === (int)$cp); ?>
								<?php if ($isActive) { ?>
									<li class="page-item active"><span class="page-link"><?= (int)$p ?></span></li>
								<?php } else { ?>
									<li class="page-item"><a class="page-link" href="<?= htmlspecialchars((string)$base_url) ?>&page=<?= (int)$p ?>"><?= (int)$p ?></a></li>
								<?php } ?>
							<?php } ?>
						<?php } ?>
					</ul>
				</nav>
			</div>
		<?php } ?>
		</form>
		<script>
		(function(){
			var master = document.getElementById('jobsSelAll');
			if (!master) return;
			master.addEventListener('change', function(){
				document.querySelectorAll('input.jobs_cb').forEach(function(cb){ cb.checked = master.checked; });
			});
		})();
		</script>
	</div>
</div>

