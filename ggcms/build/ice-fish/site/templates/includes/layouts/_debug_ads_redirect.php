<?php
/**
 * Shown when ?debug_ads=1 and user hits /go/CODE/ or /go/CODE1BANNER1/ — full redirect debug, then link to offer.
 */
$d = isset($abc['debug_ads_redirect']) ? $abc['debug_ads_redirect'] : array();
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Ads redirect debug</title></head>
<body style="font-family:monospace;background:#1e1e1e;color:#d4d4d4;padding:20px;margin:0;">
<h1 style="color:#0e639c;">DEBUG ADS — Redirect /go/</h1>
<p>Request had <code>?debug_ads=1</code>, so redirect was not performed. Below is what would have happened.</p>
<?php if (!empty($d)): ?>
<section style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #444;">
<h2 style="color:#0e639c;">1. Request parsed</h2>
<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(print_r(isset($d['request']) ? $d['request'] : array(), true), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #444;">
<h2 style="color:#0e639c;">2. API URL called (token masked)</h2>
<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(isset($d['api_url_called']) ? $d['api_url_called'] : '—', ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #444;">
<h2 style="color:#0e639c;">3. API response (raw, first 2000 chars)</h2>
<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(isset($d['api_response_raw']) ? $d['api_response_raw'] : '—', ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #444;">
<h2 style="color:#0e639c;">4. API response (parsed)</h2>
<pre style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars(print_r(isset($d['api_response_parsed']) ? $d['api_response_parsed'] : array(), true), ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<section style="margin-top:16px;padding:12px;background:#2d2d2d;border:1px solid #444;">
<h2 style="color:#0e639c;">5. Result</h2>
<pre style="white-space:pre-wrap;word-break:break-all;">would_redirect: <?= !empty($d['would_redirect']) ? 'yes' : 'no' ?>

redirect_to_offer: <?= htmlspecialchars(isset($d['redirect_to_offer']) ? $d['redirect_to_offer'] : '—', ENT_QUOTES, 'UTF-8') ?></pre>
</section>
<?php if (!empty($d['offer_url_for_link'])): ?>
<p style="margin-top:24px;"><a href="<?= htmlspecialchars($d['offer_url_for_link'], ENT_QUOTES, 'UTF-8') ?>" style="color:#0e639c;">→ Go to offer</a></p>
<?php endif; ?>
<?php else: ?>
<p>No redirect debug data.</p>
<?php endif; ?>
<p style="margin-top:24px;"><a href="?debug_ads=1" style="color:#888;">Back to site with ?debug_ads=1</a></p>
</body></html>
