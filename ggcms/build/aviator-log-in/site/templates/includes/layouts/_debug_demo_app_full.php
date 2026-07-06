<?php
/**
 * Full debug page for /{lang}/demo/app/ when ?debug_ip_check=1 (admin session required).
 * No game iframe is loaded; shows server-side probes and JSON payloads.
 */
$d = isset($abc['debug_demo_app']) && is_array($abc['debug_demo_app']) ? $abc['debug_demo_app'] : array();
$json = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

if (isset($_GET['format']) && (string) $_GET['format'] === 'json') {
	header('Content-Type: application/json; charset=utf-8');
	echo $json !== false ? $json : '{}';
	exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Debug Demo App — Session.CreateDemo</title>
</head>
<body style="font-family:ui-monospace,monospace;background:#101827;color:#d1d5db;padding:20px;margin:0;line-height:1.45;">
<h1 style="color:#60a5fa;margin-top:0;">DEBUG DEMO APP — /demo/app/</h1>
<p>Request had <code>?debug_ip_check=1</code> with an <strong>admin session</strong>, so the game iframe was <strong>not</strong> loaded.</p>
<p>Add <code>&amp;format=json</code> for raw JSON only.</p>

<?php if (!empty($d['launch_decision']) && is_array($d['launch_decision'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;margin-top:0;">Summary</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['launch_decision'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['request'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">1) Request</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['request'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['ip_country'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">2) IP / Country context</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['ip_country'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['resolved_params'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">3) Resolved demo parameters</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['resolved_params'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['production_flow'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">4) Production client flow (what JS does on normal page load)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['production_flow'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['mirror_api_probe_server'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">5) Mirror API probe (server-side cURL, Session.CreateDemo)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode($d['mirror_api_probe_server'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<?php if (!empty($d['mirror_launch_url_probe']) || !empty($d['spribe_fallback_probe'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">6) Launch URL reachability (HEAD)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode(array(
		'mirror_launch_url' => isset($d['mirror_launch_url_probe']) ? $d['mirror_launch_url_probe'] : null,
		'spribe_fallback' => isset($d['spribe_fallback_probe']) ? $d['spribe_fallback_probe'] : null,
	), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>

<section style="margin-top:16px;padding:12px;background:#111827;border:1px solid #374151;">
	<h2 style="color:#fbbf24;margin-top:0;">Full JSON payload</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars($json !== false ? $json : '{}', ENT_QUOTES, 'UTF-8') ?></pre>
</section>

<?php if (empty($d)): ?>
<p>No debug payload.</p>
<?php endif; ?>
</body>
</html>
