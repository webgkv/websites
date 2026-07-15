<?php
/**
 * Blog promo: random one image and two buttons from main, games, predictor, download pages.
 * Used on blog article view only.
 */
function blog_promo_random(int $instance = 1) {
	global $abc, $langid;
	if (empty($langid)) $langid = isset($abc['langid']) ? $abc['langid'] : '';
	$langid = $langid ?: '';

	$index_url = get_url('index');
	$games_url = isset($abc['modules']['games']) ? get_url('page', $abc['modules']['games']) : $index_url;
	$download_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND (url='download' OR url2='download' OR url3='download') LIMIT 1", 'row');
	$predictor_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND (url='predictor' OR url2='predictor' OR url3='predictor') LIMIT 1", 'row');
	$download_url = $download_page ? get_url('page', $download_page) : $index_url;
	$predictor_url = $predictor_page ? get_url('page', $predictor_page) : $index_url;

	$offer_path = isset($abc['ad_offer_path']) && $abc['ad_offer_path'] !== '' ? $abc['ad_offer_path'] : $index_url . '#demo';

	$cta_play_label = i18n('common|cta_play_now');
	$cta_try_label = i18n('common|cta_try_bonus');
	$brand = function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road';
	$hero = function_exists('site_brand_hero_image_path') ? site_brand_hero_image_path() : '/assets/images/chickenroad-hero.webp';

	$images = array(
		array('src' => $hero, 'alt' => $brand . ' game preview'),
		array('src' => $hero, 'alt' => $brand . ' games'),
		array('src' => '/assets/images/chickenroad-gameplay.webp', 'alt' => $brand . ' gameplay'),
		array('src' => '/assets/images/chickenroad-app-desktop-mobile.webp', 'alt' => $brand . ' app download'),
	);

	$buttons = array(
		array('text' => $cta_play_label, 'href' => $offer_path),
		array('text' => 'Play Games', 'href' => $games_url),
		array('text' => $cta_try_label, 'href' => $predictor_url),
		array('text' => 'Download App', 'href' => $download_url),
	);

	$img = $images[array_rand($images)];
	$keys = array_rand($buttons, 2);
	if (!is_array($keys)) $keys = array($keys, array_rand($buttons));
	$btn1 = $buttons[$keys[0]];
	$btn2 = $buttons[$keys[1]];

	require_once ROOT_DIR . 'functions/site_cta_analytics.php';
	$page_key = site_cta_resolve_page_key($abc);

	$image_html = '<figure class="blog-promo-img my-4"><img src="' . htmlspecialchars($img['src']) . '" alt="' . htmlspecialchars($img['alt']) . '" width="500" height="auto"></figure>';
	$buttons_html = '<div class="blog-promo-btns mt-4">'
		. site_cta_promo_button_html((string) $btn1['href'], (string) $btn1['text'], $page_key, $instance)
		. ' '
		. site_cta_promo_button_html((string) $btn2['href'], (string) $btn2['text'], $page_key, $instance)
		. '</div>'
		. '<br>';

	return array('image' => $image_html, 'buttons' => $buttons_html);
}
