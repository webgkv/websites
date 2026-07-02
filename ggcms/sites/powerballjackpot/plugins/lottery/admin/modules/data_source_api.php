<?php
/**
 * Data Source — API keys for homepage lottery feeds (Lottery Results Feed, etc.).
 */
$page_name = 'Data Source: API keys';

$get = array_merge(array('u' => '', 'id' => ''), (array) $get);

if (isset($get['u']) && $get['u'] === 'test_key') {
	require_once ROOT_DIR . 'functions/lottery_data_sources.php';
	$provider = '';
	$api_key = '';
	$api_secret = '';
	$has_secret_col = @mysql_select("SHOW COLUMNS FROM `data_source_keys` LIKE 'api_secret'", 'num_rows') > 0;
	if ((int) $get['id'] > 0) {
		$cols = 'id, provider, api_key' . ($has_secret_col ? ', api_secret' : '');
		$row = mysql_select(
			'SELECT ' . $cols . ' FROM data_source_keys WHERE id = ' . (int) $get['id'],
			'row'
		);
		if ($row) {
			$provider = trim((string) $row['provider']);
			$api_key = trim((string) $row['api_key']);
			$api_secret = $has_secret_col ? trim((string) ($row['api_secret'] ?? '')) : '';
		}
	} else {
		$provider = isset($_POST['provider']) ? trim((string) $_POST['provider']) : (isset($_GET['provider']) ? trim((string) $_GET['provider']) : '');
		$api_key = isset($_POST['api_key']) ? trim((string) $_POST['api_key']) : (isset($_GET['api_key']) ? trim((string) $_GET['api_key']) : '');
		$api_secret = isset($_POST['api_secret']) ? trim((string) $_POST['api_secret']) : (isset($_GET['api_secret']) ? trim((string) $_GET['api_secret']) : '');
	}
	header('Content-Type: application/json; charset=utf-8');
	if ($provider === '') {
		echo json_encode(array('ok' => false, 'message' => 'Provider required', 'full_response' => array()));
		exit;
	}
	echo json_encode(lottery_data_source_test($provider, $api_key, $api_secret));
	exit;
}

if (isset($get['u']) && $get['u'] === 'fetch_data') {
	require_once ROOT_DIR . 'functions/lottery_sync.php';
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(lottery_sync_run());
	exit;
}

$table_ok = @mysql_select("SHOW TABLES LIKE 'data_source_keys'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table data_source_keys not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once ROOT_DIR . $config['style'] . '/includes/layouts/_template.php';
	exit;
}

require_once ROOT_DIR . 'functions/lottery_data_sources.php';
$provider_catalog = lottery_data_source_providers();
$allowed_providers = lottery_data_source_provider_ids();
$has_secret_col = @mysql_select("SHOW COLUMNS FROM `data_source_keys` LIKE 'api_secret'", 'num_rows') > 0;

if (!empty($_POST['data_source_api_save']) && !empty($_POST['provider'])) {
	$provider = trim((string) $_POST['provider']);
	$name = isset($_POST['name']) ? trim((string) $_POST['name']) : $provider;
	$api_key = isset($_POST['api_key']) ? trim((string) $_POST['api_key']) : '';
	$api_secret = isset($_POST['api_secret']) ? trim((string) $_POST['api_secret']) : '';
	$notes = isset($_POST['notes']) ? trim((string) $_POST['notes']) : null;
	$enabled = isset($_POST['enabled']) ? 1 : 1;
	$key_required = !empty($provider_catalog[$provider]['key_required']);
	$secret_supported = !empty($provider_catalog[$provider]['secret_supported']);
	$can_save = in_array($provider, $allowed_providers, true)
		&& ($api_key !== '' || !$key_required || ($secret_supported && $api_secret !== ''));
	if ($can_save) {
		$row = array(
			'provider' => $provider,
			'name' => $name !== '' ? $name : lottery_data_source_provider_label($provider),
			'api_key' => $api_key,
			'notes' => $notes !== '' ? $notes : null,
			'enabled' => $enabled,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		);
		if ($has_secret_col) {
			$row['api_secret'] = $secret_supported ? $api_secret : '';
		}
		mysql_fn('insert', 'data_source_keys', $row);
		header('Location: /admin.php?m=data_source_api&added=1');
		exit;
	}
}

if (!empty($get['u']) && $get['u'] === 'delete_key' && (int) $get['id'] > 0) {
	mysql_fn('delete', 'data_source_keys', array('id' => (int) $get['id']));
	header('Location: /admin.php?m=data_source_api&deleted=1');
	exit;
}

$keys = mysql_select(
	'SELECT id, provider, name, api_key' . ($has_secret_col ? ', api_secret' : '') . ', notes, enabled, created_at FROM data_source_keys ORDER BY provider, id',
	'rows'
) ?: array();

function data_source_api_mask_credential($value)
{
	$value = trim((string) $value);
	if ($value === '') {
		return '—';
	}
	if (strlen($value) > 8) {
		return substr($value, 0, 4) . '…' . substr($value, -4);
	}
	return $value;
}

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">Data Source — API keys</h5>';

$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Add API key</strong></div><div class="card-body">';
$content .= '<form method="post">';
$content .= '<input type="hidden" name="data_source_api_save" value="1">';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="ds_provider">Provider</label><select name="provider" id="ds_provider" class="form-control">';
foreach ($provider_catalog as $pid => $meta) {
	$content .= '<option value="' . htmlspecialchars($pid) . '">' . htmlspecialchars((string) $meta['label']) . '</option>';
}
$content .= '</select></div>';
$content .= '<div class="form-group col-md-4"><label class="form-label" for="ds_key">Key</label><input class="form-control" id="ds_key" name="api_key" autocomplete="off"></div>';
$content .= '<div class="form-group col-md-4 d-none" id="ds_secret_group"><label class="form-label" for="ds_secret">Secret</label><input class="form-control" id="ds_secret" name="api_secret" autocomplete="off"></div>';
$content .= '</div>';
$content .= '<button class="btn btn-primary btn-sm" type="submit">Save</button>';
$content .= '</form>';
$content .= '</div></div>';

$content .= '<div class="card mb-4"><div class="card-header bg-light d-flex justify-content-between align-items-center"><strong>Saved keys</strong>';
$content .= '<button type="button" class="btn btn-outline-primary btn-sm" id="js-data-source-fetch">Fetch data</button></div><div class="card-body">';
$content .= '<div id="data-source-test-result" class="mb-2"></div>';
$content .= '<div class="table-responsive"><table class="table table-sm">';
$content .= '<thead><tr><th>Provider</th><th>Key</th>';
if ($has_secret_col) {
	$content .= '<th>Secret</th>';
}
$content .= '<th></th></tr></thead><tbody>';
foreach ($keys as $r) {
	$pid = (string) $r['provider'];
	$testUrl = '/admin.php?m=data_source_api&u=test_key&id=' . (int) $r['id'];
	$delUrl = '/admin.php?m=data_source_api&u=delete_key&id=' . (int) $r['id'];
	$key_mask = data_source_api_mask_credential($r['api_key']);
	$secret_mask = $has_secret_col ? data_source_api_mask_credential($r['api_secret'] ?? '') : '—';
	$content .= '<tr>';
	$content .= '<td>' . htmlspecialchars(lottery_data_source_provider_label($pid)) . '</td>';
	$content .= '<td class="font-monospace small">' . htmlspecialchars($key_mask) . '</td>';
	if ($has_secret_col) {
		$content .= '<td class="font-monospace small">' . htmlspecialchars($secret_mask) . '</td>';
	}
	$content .= '<td class="text-nowrap"><a class="btn btn-outline-secondary btn-sm js-data-source-test-key" href="' . htmlspecialchars($testUrl) . '">Test</a> ';
	$content .= '<a class="btn btn-outline-danger btn-sm" href="' . htmlspecialchars($delUrl) . '" onclick="return confirm(\'Delete this key?\')">Delete</a></td>';
	$content .= '</tr>';
}
if (empty($keys)) {
	$empty_cols = $has_secret_col ? 4 : 3;
	$content .= '<tr><td colspan="' . $empty_cols . '" class="text-muted">—</td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '</div></div></div>';

$content .= '<script>
document.addEventListener("DOMContentLoaded", function () {
	var container = document.getElementById("data-source-test-result");
	var providerSelect = document.getElementById("ds_provider");
	var secretGroup = document.getElementById("ds_secret_group");
	var providerMeta = ' . json_encode(array_map(function ($m) {
		return array('secret_supported' => !empty($m['secret_supported']));
	}, $provider_catalog), JSON_UNESCAPED_UNICODE) . ';
	function updateProviderFields() {
		if (!providerSelect || !secretGroup) return;
		var meta = providerMeta[providerSelect.value] || {};
		secretGroup.classList.toggle("d-none", !meta.secret_supported);
	}
	if (providerSelect) {
		providerSelect.addEventListener("change", updateProviderFields);
		updateProviderFields();
	}
	function showAlert(type, text) {
		if (!container) return;
		container.innerHTML = \'<div class="alert alert-\' + type + \' alert-dismissible fade show" role="alert">\'
			+ text.replace(/</g, "&lt;").replace(/>/g, "&gt;")
			+ \'<button type="button" class="close" data-dismiss="alert" aria-label="Close">\
<span aria-hidden="true">&times;</span></button></div>\';
	}
	document.querySelectorAll(".js-data-source-test-key").forEach(function (link) {
		link.addEventListener("click", function (e) {
			e.preventDefault();
			var url = this.getAttribute("href");
			if (!url) return;
			showAlert("info", "Testing...");
			fetch(url, { credentials: "same-origin" })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					if (resp && resp.ok) {
						showAlert("success", resp.message || "OK");
					} else {
						showAlert("danger", (resp && resp.message) ? resp.message : "Error");
					}
				})
				.catch(function (err) {
					showAlert("danger", "Request failed: " + err);
				});
		});
	});
	var fetchBtn = document.getElementById("js-data-source-fetch");
	if (fetchBtn) {
		fetchBtn.addEventListener("click", function () {
			showAlert("info", "Fetching...");
			fetchBtn.disabled = true;
			fetch("/admin.php?m=data_source_api&u=fetch_data", { credentials: "same-origin" })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					fetchBtn.disabled = false;
					if (resp && resp.ok) {
						showAlert("success", resp.message || "OK");
					} else {
						showAlert("danger", (resp && resp.message) ? resp.message : "Error");
					}
				})
				.catch(function (err) {
					fetchBtn.disabled = false;
					showAlert("danger", "Request failed: " + err);
				});
		});
	}
});
</script>';
