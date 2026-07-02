<?php

/**
 * Brand profile: Chicken Road (chickenroad.run)
 * Loaded by shared/brand/loader.php when $config['site_id'] === 'chickenroad'.
 */
return array(
	'site_id' => 'chickenroad',
	'name' => 'Chicken Road',
	'domain' => 'chickenroad.run',
	'legacy_canonical_hosts' => array(
		'aviator-log-in.com',
		'www.aviator-log-in.com',
	),
	'legacy_hostname_map' => array(
		'aviator-log-in.com' => 'chickenroad.run',
		'www.aviator-log-in.com' => 'chickenroad.run',
	),
	'default_apk' => 'chickenroad.apk',
	'default_hero_image' => '/assets/images/chickenroad-hero.webp',
	'default_favicon' => '/assets/images/favicon.png',
	'default_store_google' => '/assets/images/chickenroad-store-googleplay.svg',
	'default_store_appstore' => '/assets/images/chickenroad-store-appstore.svg',
	'demo_preview_steps' => array(
		'/assets/images/chickenroad-step-1.webp',
		'/assets/images/chickenroad-step-2.webp',
		'/assets/images/chickenroad-step-3.webp',
	),
	'asset_legacy_map' => array(
		'chickenroad-hero.png' => '/assets/images/chickenroad-hero.webp',
		'aviator-app-and-mobile-version.png' => '/assets/images/chickenroad-hero.webp',
		'aviator-app-and-mobile-version.webp' => '/assets/images/chickenroad-hero.webp',
		'aviator-store-googleplay.svg' => '/assets/images/chickenroad-store-googleplay.svg',
		'aviator-store-appstore.svg' => '/assets/images/chickenroad-store-appstore.svg',
		'aviatorplay.webp' => '/assets/images/chickenroad-gameplay.webp',
		'bet-1.jpg' => '/assets/images/chickenroad-step-1.webp',
		'bet-2.jpg' => '/assets/images/chickenroad-step-2.webp',
		'bet-2.webp' => '/assets/images/chickenroad-step-2.webp',
		'bet-3.jpg' => '/assets/images/chickenroad-step-3.webp',
		'bet-3.png' => '/assets/images/chickenroad-step-3.webp',
		'app-game-header.webp' => '/assets/images/chickenroad-mobile.webp',
	),
	'rebrand_from_brands' => array('Aviator'),
	'rebrand_from_hosts' => array('aviator-log-in.com', 'www.aviator-log-in.com'),
	'plugins' => array(),
	'static_redirects' => array(
		'/assets/images/chickenroad-hero.png' => '/assets/images/chickenroad-hero.webp',
	),
);
