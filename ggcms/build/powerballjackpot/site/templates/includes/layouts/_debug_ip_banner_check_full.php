<?php
/**
 * Full debug page when ?debug_ip_banner_check_full=1 on a normal layout page (banner API loaded).
 * Shows banner request URLs, raw responses, and why banner vs placeholder was chosen.
 */
$d = isset($abc['debug_ip_banner_check_full']) && is_array($abc['debug_ip_banner_check_full']) ? $abc['debug_ip_banner_check_full'] : array();
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Debug Banner API Full</title>
</head>
<body style="font-family:monospace;background:#101827;color:#d1d5db;padding:20px;margin:0;">
<h1 style="color:#60a5fa;margin-top:0;">DEBUG BANNER API FULL</h1>
<p>Request had <code>?debug_ip_banner_check_full=1</code>. Normal page render was skipped.</p>
<p style="max-width:720px;">Use this on pages that load the site layout with the partner banner (e.g. <code>/en/</code>). On <code>/go/...</code> links, only redirect debug applies — open this URL on the homepage instead.</p>
<?php if (!empty($d)): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">Summary</h2>
	<p style="margin:0 0 8px;white-space:pre-wrap;"><?= htmlspecialchars(isset($d['human_summary']) ? (string)$d['human_summary'] : '', ENT_QUOTES, 'UTF-8') ?></p>
	<pre style="white-space:pre-wrap;word-break:break-all;margin:0;"><?= htmlspecialchars(json_encode(array(
		'request_uri' => isset($d['request_uri']) ? $d['request_uri'] : '',
		'lang_current' => isset($d['lang_current']) ? $d['lang_current'] : '',
	), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">1) IP / country (same helpers as redirect debug)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(isset($d['ip_country']) ? $d['ip_country'] : array(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">2) Banner API calls (URLs token-masked; full response per try)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(isset($d['banner_api']) ? $d['banner_api'] : array(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">3) Parsed partner + render decision</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode(array(
		'ad_partner' => isset($d['ad_partner']) ? $d['ad_partner'] : null,
		'render_decision' => isset($d['render_decision']) ? $d['render_decision'] : array(),
	), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php if (!empty($d['merged_debug_ip_check'])): ?>
<section style="margin-top:16px;padding:12px;background:#1f2937;border:1px solid #374151;">
	<h2 style="color:#93c5fd;">4) Merged <code>debug_ip_check</code> (if ?debug_ip_check=1)</h2>
	<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(json_encode($d['merged_debug_ip_check'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php endif; ?>
<?php else: ?>
<p>No debug payload.</p>
<?php endif; ?>
</body>
</html>
