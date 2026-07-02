<?php

/**
 * Brand profile: PowerBall Jackpot (powerballjackpot.run)
 */
return array(
	'site_id' => 'powerballjackpot',
	'name' => 'PowerBall Jackpot',
	'domain' => 'powerballjackpot.run',
	'legacy_canonical_hosts' => array(
		'aviator-log-in.com',
		'www.aviator-log-in.com',
		'chickenroad.run',
		'www.chickenroad.run',
	),
	'legacy_hostname_map' => array(
		'aviator-log-in.com' => 'powerballjackpot.run',
		'www.aviator-log-in.com' => 'powerballjackpot.run',
		'chickenroad.run' => 'powerballjackpot.run',
		'www.chickenroad.run' => 'powerballjackpot.run',
	),
	'default_apk' => 'powerballjackpot.apk',
	'default_hero_image' => '/assets/images/powerball-hero.webp',
	'default_favicon' => '/assets/images/favicon.png',
	'default_store_google' => '/assets/images/powerball-store-googleplay.svg',
	'default_store_appstore' => '/assets/images/powerball-store-appstore.svg',
	'demo_preview_steps' => array(
		'/assets/images/chickenroad-step-1.webp',
		'/assets/images/chickenroad-step-2.webp',
		'/assets/images/chickenroad-step-3.webp',
	),
	'asset_legacy_map' => array(
		'chickenroad-hero.png' => '/assets/images/powerball-hero.webp',
		'aviator-app-and-mobile-version.png' => '/assets/images/powerball-hero.webp',
		'aviator-app-and-mobile-version.webp' => '/assets/images/powerball-hero.webp',
	),
	'rebrand_from_brands' => array('Aviator', 'Chicken Road'),
	'rebrand_from_hosts' => array(
		'aviator-log-in.com',
		'www.aviator-log-in.com',
		'chickenroad.run',
		'www.chickenroad.run',
	),
	'plugins' => array('lottery'),
	'static_redirects' => array(
		'/assets/images/chickenroad-hero.png' => '/assets/images/chickenroad-hero.webp',
	),
	'section_slugs' => array(
		'blog' => 'articles',
		'guides' => 'odds',
		'casinos' => 'lotteries',
	),
	'section_admin_labels' => array(
		'blog' => 'Articles',
		'guides' => 'Odds',
		'casinos' => 'Lotteries',
	),
);
