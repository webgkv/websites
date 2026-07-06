<?php
/**
 * Full debug page for /go/... when ?debug_ip_check_full=1.
 * No redirect is performed; shows complete request/response payloads.
 */
$d = isset($abc['debug_ip_check_full']) && is_array($abc['debug_ip_check_full']) ? $abc['debug_ip_check_full'] : array();
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Debug IP Check Full</title>
</head>
<body style="font-family:monospace;background:#101827;color:#d1d5db;padding:20px;margin:0;">
<h1 style="color:#60a5fa;margin-top:0;">DEBUG IP CHECK FULL — Redirect /go/</h1>
<p>Request had <code>?debug_ip_check_full=1</code>, so redirect was not performed.</p>
<?php if (!empty($d)): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">1) Parsed request</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(isset($d['request']) ? $d['request'] : array(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">2) IP/Country context</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(isset($d['ip_check']) ? $d['ip_check'] : array(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">3) Backend call (full)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(isset($d['backend']) ? $d['backend'] : array(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">4) Final redirect decision</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(array(
		'final_redirect_url' => isset($d['final_redirect_url']) ? $d['final_redirect_url'] : '',
		'would_redirect' => !empty($d['would_redirect']),
	), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php else: ?>
<p>No debug payload.</p>
<?php endif; ?>
</body>
</html>
