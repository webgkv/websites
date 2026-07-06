<?php
/**
 * Logs UI (system_logs) — matches prn_cross Drop Monitor → Log UX.
 */
$log_entries = isset($q['log_entries']) ? $q['log_entries'] : array();
$log_detail = isset($q['log_detail']) ? $q['log_detail'] : null;
$log_table_exists = !empty($q['log_table_exists']);
?>

<div class="card">
	<div class="card-body">
		<h5 class="card-title">Log</h5>
		<?php if (!empty($q['log_cleanup']) && isset($q['log_cleanup_deleted']) && (int)$q['log_cleanup_deleted'] >= 0) { ?>
		<div class="alert alert-success alert-dismissible fade show">Log cleanup: removed <?= (int)$q['log_cleanup_deleted'] ?> old entries (older than <?= (int)($q['log_cleanup_days'] ?? 30) ?> days). <button type="button" class="close" data-dismiss="alert">&times;</button></div>
		<?php } ?>
		<?php if ($log_table_exists) { ?>
		<p class="mb-2">
			<a href="/admin.php?m=logs&u=export_log_csv" class="btn btn-sm btn-outline-secondary">Download CSV</a> (last 1000)
			<a href="/admin.php?m=logs&u=export_log_csv_full" class="btn btn-sm btn-outline-secondary ml-1">Download full CSV</a> (up to 50k, may be slow)
			<a href="/admin.php?m=logs&u=log_cleanup" class="btn btn-sm btn-outline-secondary ml-1" onclick="return confirm('Delete log entries older than retention?');">Clean old (by retention)</a>
			<label class="ml-3 align-middle"><input type="checkbox" id="logLiveCheck" /> Live (every 5 sec)</label>
			<button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="logLoadBtn">Load logs</button>
		</p>
		<p class="small text-info mb-2" id="logLiveStatus" style="display:none"></p>
		<?php } ?>

		<div class="table-responsive" id="logTableWrap">
			<table class="table table-sm table-hover table-responsive-stack">
				<thead>
					<tr>
						<th>Time</th>
						<th>Service</th>
						<th>HTTP</th>
						<th>Duration (ms)</th>
						<th>Request</th>
						<th>Response (preview)</th>
						<th></th>
					</tr>
				</thead>
				<tbody id="logTableBody">
					<?php foreach ($log_entries as $e) {
						$req = isset($e['context']) ? (string)$e['context'] : '';
						$req_short = mb_substr($req, 0, 50);
						if (mb_strlen($req) > 50) $req_short .= '…';
						$resp = isset($e['message']) ? (string)$e['message'] : '';
						$resp_short = mb_substr(strip_tags($resp), 0, 60);
						if (mb_strlen($resp) > 60) $resp_short .= '…';
						$log_view_url = '/admin.php?m=logs&log_id=' . (int)$e['id'];
					?>
					<tr style="cursor:pointer" data-log-href="<?= htmlspecialchars($log_view_url) ?>" title="Click row or View to open full raw data">
						<td class="small"><?= htmlspecialchars($e['created_at']) ?></td>
						<td><span class="badge badge-secondary"><?= htmlspecialchars($e['channel']) ?></span></td>
						<td></td>
						<td></td>
						<td class="small" style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= htmlspecialchars($req) ?>"><?= htmlspecialchars($req_short) ?></td>
						<td class="small" style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="<?= htmlspecialchars($resp_short) ?>"><?= htmlspecialchars($resp_short) ?></td>
						<td class="text-nowrap"><a href="<?= htmlspecialchars($log_view_url) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>

		<script>
		(function(){
			var wrap = document.getElementById('logTableWrap');
			if (wrap) wrap.addEventListener('click', function(e) {
				var tr = e.target.closest('tr[data-log-href]');
				if (tr && !e.target.closest('a')) { window.location.href = tr.getAttribute('data-log-href'); }
			});

			var liveCheck = document.getElementById('logLiveCheck');
			var liveStatus = document.getElementById('logLiveStatus');
			var logBody = document.getElementById('logTableBody');
			var liveInterval = null;
			var logJsonUrl = (window.location.pathname || '/admin.php') + '?m=logs&u=log_entries_json&limit=50';

			function escapeHtml(s) {
				if (!s) return '';
				var d = document.createElement('div');
				d.textContent = s;
				return d.innerHTML;
			}
			function renderLogRows(entries) {
				if (!logBody) return;
				if (!entries || !entries.length) { logBody.innerHTML = ''; return; }
				var html = '';
				entries.forEach(function(e) {
					var viewUrl = '/admin.php?m=logs&log_id=' + e.id;
					html += '<tr style="cursor:pointer" data-log-href="' + escapeHtml(viewUrl) + '" title="Click row or View to open full raw data">' +
						'<td class="small">' + escapeHtml(e.created_at) + '</td>' +
						'<td><span class="badge badge-secondary">' + escapeHtml(e.service) + '</span></td>' +
						'<td></td><td></td>' +
						'<td class="small" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + escapeHtml(e.request_text || '') + '">' + escapeHtml(e.request_short || '') + '</td>' +
						'<td class="small" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + escapeHtml(e.response_short || '') + '">' + escapeHtml(e.response_short || '') + '</td>' +
						'<td class="text-nowrap"><a href="' + escapeHtml(viewUrl) + '" class="btn btn-sm btn-outline-primary">View</a></td></tr>';
				});
				logBody.innerHTML = html;
			}
			function updateLiveStatus(last) {
				if (!liveStatus) return;
				if (!last) { liveStatus.style.display = 'none'; return; }
				liveStatus.style.display = 'block';
				liveStatus.textContent = 'Last: ' + (last.service || '') + ' — ' + (last.created_at || '');
			}
			function fetchLogs() {
				var xhr = new XMLHttpRequest();
				xhr.open('GET', logJsonUrl, true);
				xhr.onload = function() {
					if (xhr.status !== 200) {
						if (liveStatus) { liveStatus.style.display = 'block'; liveStatus.textContent = 'Load error: HTTP ' + xhr.status; liveStatus.className = 'small text-danger mb-2'; }
						return;
					}
					try {
						var data = JSON.parse(xhr.responseText);
						if (data.entries) {
							renderLogRows(data.entries);
							updateLiveStatus(data.entries.length ? data.entries[0] : null);
							if (liveStatus) liveStatus.className = 'small text-info mb-2';
						}
					} catch (e) {
						if (liveStatus) { liveStatus.style.display = 'block'; liveStatus.textContent = 'Error: response is not JSON'; liveStatus.className = 'small text-danger mb-2'; }
					}
				};
				xhr.onerror = function() {
					updateLiveStatus(null);
					if (liveStatus) { liveStatus.style.display = 'block'; liveStatus.textContent = 'Network error loading logs'; liveStatus.className = 'small text-danger mb-2'; }
				};
				xhr.send();
			}
			if (liveCheck) {
				liveCheck.addEventListener('change', function() {
					if (liveCheck.checked) {
						fetchLogs();
						liveInterval = setInterval(fetchLogs, 5000);
					} else {
						if (liveInterval) { clearInterval(liveInterval); liveInterval = null; }
						updateLiveStatus(null);
					}
				});
			}
			var logLoadBtn = document.getElementById('logLoadBtn');
			if (logLoadBtn) logLoadBtn.addEventListener('click', function() { fetchLogs(); });
		})();
		</script>

		<?php if (empty($log_entries)) { ?>
		<p class="text-muted small mb-0">No log entries yet.</p>
		<?php } ?>

		<?php if (!empty($q['pagination_html'])) { ?>
		<div class="pagination pagination-bottom mt-3"><?= $q['pagination_html'] ?></div>
		<?php } ?>

		<?php if ($log_detail) { ?>
		<div class="modal fade show" id="logRawModal" tabindex="-1" role="dialog" style="display:block; background: rgba(0,0,0,0.2);">
			<div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h6 class="modal-title">Log #<?= (int)$log_detail['id'] ?> — <?= htmlspecialchars($log_detail['created_at']) ?> — <?= htmlspecialchars($log_detail['channel']) ?> (<?= htmlspecialchars($log_detail['level']) ?>)</h6>
						<a href="/admin.php?m=logs" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></a>
					</div>
					<div class="modal-body small">
						<p class="font-weight-bold mb-1">Context</p>
						<pre class="bg-light p-2 rounded border overflow-auto" style="max-height:300px"><?= htmlspecialchars((string)$log_detail['context']) ?></pre>
						<p class="font-weight-bold mb-1 mt-3">Message</p>
						<pre class="bg-light p-2 rounded border overflow-auto" style="max-height:400px; white-space: pre-wrap; word-break: break-all"><?= htmlspecialchars((string)$log_detail['message']) ?></pre>
					</div>
					<div class="modal-footer">
						<a class="btn btn-secondary btn-sm" href="/admin.php?m=logs">Close</a>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
</div>

