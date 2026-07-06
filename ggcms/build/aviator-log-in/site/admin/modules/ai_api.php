<?php
/**
 * AI layer - API: provider keys (OpenRouter, Google Gemini, NVIDIA NIM).
 */
$page_name = 'AI: API keys';

$get = array_merge(array('u' => '', 'id' => ''), (array)$get);

// AJAX: test key
if (isset($get['u']) && $get['u'] === 'test_key') {
	require_once(ROOT_DIR . 'functions/ai_gateway.php');
	$provider = '';
	$api_key = '';
	$model = null;
	if ((int)$get['id'] > 0) {
		$row = mysql_select("SELECT id, provider, api_key, model_default FROM ai_provider_keys WHERE id = " . (int)$get['id'], 'row');
		if ($row) {
			$provider = trim((string)$row['provider']);
			$api_key = trim((string)$row['api_key']);
			$model = isset($row['model_default']) ? trim((string)$row['model_default']) : null;
		}
	} else {
		$provider = isset($_POST['provider']) ? trim((string)$_POST['provider']) : (isset($_GET['provider']) ? trim((string)$_GET['provider']) : '');
		$api_key = isset($_POST['api_key']) ? trim((string)$_POST['api_key']) : (isset($_GET['api_key']) ? trim((string)$_GET['api_key']) : '');
		$model = isset($_POST['model_default']) ? trim((string)$_POST['model_default']) : (isset($_GET['model_default']) ? trim((string)$_GET['model_default']) : null);
	}
	header('Content-Type: application/json; charset=utf-8');
	if ($provider === '' || $api_key === '') {
		echo json_encode(array('ok' => false, 'message' => 'Provider and API key required', 'full_response' => array()));
		exit;
	}
	echo json_encode(ai_gateway_test($provider, $api_key, $model));
	exit;
}

$table_ok = @mysql_select("SHOW TABLES LIKE 'ai_provider_keys'", 'num_rows') > 0;
if (!$table_ok) {
	$content = '<div class="alert alert-warning"><strong>Table ai_provider_keys not found.</strong> Run migration: <a href="/scripts/run_migrate_BD.php?run=1" target="_blank">run_migrate_BD.php</a>.</div>';
	require_once(ROOT_DIR . $config['style'] . '/includes/layouts/_template.php');
	exit;
}

// Save key
if (!empty($_POST['ai_api_save']) && !empty($_POST['provider']) && trim((string)$_POST['api_key']) !== '') {
	$provider = trim((string)$_POST['provider']);
	$name = isset($_POST['name']) ? trim((string)$_POST['name']) : $provider;
	$api_key = trim((string)$_POST['api_key']);
	$model_default = isset($_POST['model_default']) ? trim((string)$_POST['model_default']) : null;
	$enabled = isset($_POST['enabled']) ? 1 : 1;
	if (in_array($provider, array('openrouter', 'google_gemini', 'nvidia'), true)) {
		mysql_fn('insert', 'ai_provider_keys', array(
			'provider' => $provider,
			'name' => $name,
			'api_key' => $api_key,
			'model_default' => $model_default !== '' ? $model_default : null,
			'enabled' => $enabled,
			'created_at' => date('Y-m-d H:i:s'),
			'updated_at' => date('Y-m-d H:i:s'),
		));
		header('Location: /admin.php?m=ai_api&added=1');
		exit;
	}
}

// Delete key
if (!empty($get['u']) && $get['u'] === 'delete_key' && (int)$get['id'] > 0) {
	mysql_fn('delete', 'ai_provider_keys', array('id' => (int)$get['id']));
	header('Location: /admin.php?m=ai_api&deleted=1');
	exit;
}

require_once ROOT_DIR . 'functions/ai_prompt_templates.php';

// Save LLM prompt templates (translation jobs)
if (!empty($_POST['ai_prompt_templates_save'])) {
	$partial = array();
	foreach (ai_prompt_templates_allowed_keys() as $k) {
		if (isset($_POST['tpl_' . $k])) {
			$partial[$k] = (string)$_POST['tpl_' . $k];
		}
	}
	$save = ai_prompt_templates_save_partial($partial);
	header('Location: /admin.php?m=ai_api&prompt_saved=' . (!empty($save['ok']) ? '1' : '0'));
	exit;
}

if (!empty($_POST['ai_prompt_templates_reset'])) {
	ai_prompt_templates_reset_all();
	header('Location: /admin.php?m=ai_api&prompt_reset=1');
	exit;
}

$keys = mysql_select("SELECT id, provider, name, api_key, model_default, enabled, created_at FROM ai_provider_keys ORDER BY provider, id", 'rows') ?: array();
$provider_labels = array('openrouter' => 'OpenRouter', 'google_gemini' => 'Google Gemini', 'nvidia' => 'NVIDIA NIM');

$content = '<div class="admin-module-page">';
$content .= '<h5 class="mb-3">AI API keys</h5>';
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Add provider key</strong></div><div class="card-body">';
$content .= '<form method="post">';
$content .= '<input type="hidden" name="ai_api_save" value="1">';
$content .= '<div class="form-row">';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="ai_provider">Provider</label><select name="provider" id="ai_provider" class="form-control">';
foreach ($provider_labels as $k => $label) {
	$content .= '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($label) . '</option>';
}
$content .= '</select></div>';
$content .= '<div class="form-group col-md-3"><label class="form-label" for="ai_name">Name</label><input class="form-control" id="ai_name" name="name" placeholder="Optional"></div>';
$content .= '<div class="form-group col-md-4"><label class="form-label" for="ai_key">API key</label><input class="form-control" id="ai_key" name="api_key" placeholder="Paste key"></div>';
$content .= '<div class="form-group col-md-2"><label class="form-label" for="ai_model">Model</label><input class="form-control" id="ai_model" name="model_default" placeholder="Optional"></div>';
$content .= '</div>';
$content .= '<button class="btn btn-primary btn-sm" type="submit">Save</button>';
$content .= '</form>';
$content .= '</div></div>';

$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Saved keys</strong></div><div class="card-body">';
$content .= '<div id="ai-api-test-result" class="mb-2"></div>';
$content .= '<div class="table-responsive"><table class="table table-sm">';
$content .= '<thead><tr><th>ID</th><th>Provider</th><th>Name</th><th>Model</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
foreach ($keys as $r) {
	$testUrl = '/admin.php?m=ai_api&u=test_key&id=' . (int)$r['id'];
	$delUrl = '/admin.php?m=ai_api&u=delete_key&id=' . (int)$r['id'];
	$content .= '<tr>';
	$content .= '<td>' . (int)$r['id'] . '</td>';
	$content .= '<td>' . htmlspecialchars(isset($provider_labels[$r['provider']]) ? $provider_labels[$r['provider']] : $r['provider']) . '</td>';
	$content .= '<td>' . htmlspecialchars((string)$r['name']) . '</td>';
	$content .= '<td>' . htmlspecialchars((string)$r['model_default']) . '</td>';
	$content .= '<td class="text-muted small">' . htmlspecialchars((string)$r['created_at']) . '</td>';
	$content .= '<td><a class="btn btn-outline-secondary btn-sm js-ai-test-key" href="' . htmlspecialchars($testUrl) . '">Test</a> ';
	$content .= '<a class="btn btn-outline-danger btn-sm" href="' . htmlspecialchars($delUrl) . '" onclick="return confirm(\'Delete this key?\')">Delete</a></td>';
	$content .= '</tr>';
}
if (empty($keys)) {
	$content .= '<tr><td colspan="6" class="text-muted">No keys saved yet.</td></tr>';
}
$content .= '</tbody></table></div>';
$content .= '</div></div></div>';

$tpl_merged = ai_prompt_templates_merged();
$tpl_custom = ai_prompt_templates_custom_keys();
$content .= '<div class="card mb-4"><div class="card-header bg-light"><strong>Translation LLM prompts</strong></div><div class="card-body">';
if (!empty($_GET['prompt_saved'])) {
	$content .= '<div class="alert alert-' . ((string)$_GET['prompt_saved'] === '1' ? 'success' : 'danger') . '">Prompts saved.</div>';
}
if (!empty($_GET['prompt_reset'])) {
	$content .= '<div class="alert alert-info">Prompts reset to built-in defaults.</div>';
}
$content .= '<p class="small text-muted">These templates apply to translation/repair jobs. Use placeholders: <code>{src_lang_name}</code>, <code>{dst_lang_name}</code>, <code>{examples_prompt}</code>, <code>{structure_counts}</code>. Override via Control API: <code>ai_prompt_templates_get</code> / <code>ai_prompt_templates_set</code> / <code>ai_prompt_templates_reset</code>.</p>';
$content .= '<form method="post" class="mb-3">';
$content .= '<input type="hidden" name="ai_prompt_templates_save" value="1">';
foreach (ai_prompt_templates_allowed_keys() as $k) {
	$label = $k;
	if (in_array($k, $tpl_custom, true)) {
		$label .= ' <span class="badge badge-warning text-dark">custom</span>';
	}
	$val = isset($tpl_merged[$k]) ? (string)$tpl_merged[$k] : '';
	$content .= '<div class="form-group"><label class="font-weight-bold" for="tpl_' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '">' . $label . '</label>';
	$content .= '<textarea class="form-control font-monospace small" name="tpl_' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" id="tpl_' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" rows="8">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</textarea></div>';
}
$content .= '<button type="submit" class="btn btn-primary btn-sm">Save prompt templates</button>';
$content .= '</form>';
$content .= '<form method="post" onsubmit="return confirm(\'Reset all translation prompts to defaults?\');">';
$content .= '<input type="hidden" name="ai_prompt_templates_reset" value="1">';
$content .= '<button type="submit" class="btn btn-outline-danger btn-sm">Reset to defaults</button>';
$content .= '</form>';
$content .= '</div></div>';

// Inline JS: test key via AJAX and show Bootstrap alerts
$content .= '<script>
document.addEventListener("DOMContentLoaded", function () {
	var container = document.getElementById("ai-api-test-result");
	function showAlert(type, text) {
		if (!container) return;
		container.innerHTML = \'<div class="alert alert-\' + type + \' alert-dismissible fade show" role="alert">\'
			+ text.replace(/</g, "&lt;").replace(/>/g, "&gt;")
			+ \'<button type="button" class="close" data-dismiss="alert" aria-label="Close">\
<span aria-hidden="true">&times;</span></button></div>\';
	}
	var links = document.querySelectorAll(".js-ai-test-key");
	links.forEach(function (link) {
		link.addEventListener("click", function (e) {
			e.preventDefault();
			var url = this.getAttribute("href");
			if (!url) return;
			showAlert("info", "Testing API key...");
			fetch(url, { credentials: "same-origin" })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					if (resp && resp.ok) {
						var msg = resp.message || "OK";
						if (resp.full_response && resp.full_response.http_code) {
							msg += " (HTTP " + resp.full_response.http_code + ")";
						}
						showAlert("success", msg);
					} else {
						var m = (resp && resp.message) ? resp.message : "Unknown error";
						showAlert("danger", "Error: " + m);
					}
				})
				.catch(function (err) {
					showAlert("danger", "Request failed: " + err);
				});
		});
	});
});
</script>';

