<?php
/**
 * Seed: FanSport free spins promo landing (chickenroad).
 * Included from migrate_BD_run.php — idempotent on url slug (insert + content refresh).
 */

if (!function_exists('promo_seed_fansport_html')) {
	function promo_seed_fansport_html($lang_prefix = '/en/') {
		$lang_prefix = rtrim((string)$lang_prefix, '/') . '/';
		$go = 'https://tcdu1.live/t.php?o=5GyyB';
		$demo = $lang_prefix . 'demo/app/';
		return <<<HTML
<section class="promo-land promo-land--fansport">
	<div class="promo-land-hero">
		<div class="promo-land-hero__visual" aria-hidden="true">
			<img src="/files/media/2026/07/chicken-fansport-fs.webp" alt="" width="640" height="360" loading="eager" class="promo-land-hero__img">
			<div class="promo-land-hero__glow"></div>
		</div>
		<p class="promo-land-hero__eyebrow">FanSport × Chicken Road · Free Spins</p>
		<h1 class="promo-land-hero__headline">Your free spins are ready</h1>
		<p class="promo-land-hero__lead">A new free spins gift has been added for you.</p>
		<p class="promo-land-hero__note">The bonus will be credited within 24 hours.</p>
		<div class="main_btn promo-land-hero__cta">
			<noads><a href="{$go}">Get bonus</a></noads>
		</div>
	</div>
	<div class="promo-land-body about_content">
		<p>Open the site, go to your account, and check the Bonuses or Gifts section — the offer should already be waiting there.</p>
		<div class="promo-land-steps">
			<div class="promo-land-step">
				<h2>Already have an account?</h2>
				<p>Just log in and claim your spins.</p>
			</div>
			<div class="promo-land-step">
				<h2>New here?</h2>
				<p>Register through this offer, and your welcome bonus will be added automatically after sign-up.</p>
			</div>
		</div>
		<p class="promo-land-closing">Use the spins while the offer is active, check the bonus terms, and enjoy a few extra rounds on us.</p>
		<h2>Where to find your bonus</h2>
		<ul>
			<li><strong>Web:</strong> Personal account → Bonuses and gifts</li>
			<li><strong>Mobile:</strong> Menu → Bonuses</li>
		</ul>
		<p class="promo-land-foot-cta">
			<noads><a href="{$go}" class="promo-land-btn-secondary">Go to FanSport</a></noads>
			<a href="{$demo}" class="promo-land-btn-ghost">Chicken Road demo</a>
		</p>
	</div>
</section>
HTML;
	}
}

if (@mysql_select("SHOW TABLES LIKE 'promo'", 'num_rows') > 0) {
	$seed_slug = 'fansport-free-spins';
	$exists = mysql_select("SELECT id FROM promo WHERE url IN ('" . mysql_res($seed_slug) . "','fansport-15-free-spins') ORDER BY id ASC LIMIT 1", 'row');
	$now = date('Y-m-d H:i:s');
	$seed_row = array(
		'name' => 'Free Spins — FanSport × Chicken Road',
		'name_2' => 'Your free spins are ready — log in or register to claim at FanSport.',
		'url' => $seed_slug,
		'text' => promo_seed_fansport_html(),
		'title' => 'Your Free Spins Are Ready | FanSport × Chicken Road',
		'description' => 'A new free spins gift is waiting in your FanSport account. Log in or register to claim it and enjoy extra Chicken Road rounds.',
		'updated_at' => $now,
	);
	if (!$exists) {
		$seed_row['img'] = '';
		$seed_row['category'] = 'active';
		$seed_row['promo_unlimited'] = 1;
		$seed_row['date_end'] = null;
		$seed_row['display'] = 1;
		$seed_row['position'] = 100;
		$seed_row['date'] = $now;
		$seed_row['author_id'] = 1;
		$seed_row['created_at'] = $now;
		mysql_fn('insert', 'promo', $seed_row);
		$done[] = 'promo seed fansport-free-spins';
	} else {
		$seed_row['id'] = (int)$exists['id'];
		mysql_fn('update', 'promo', $seed_row);
		$done[] = 'promo seed fansport-free-spins (updated id=' . (int)$exists['id'] . ')';
	}
}
