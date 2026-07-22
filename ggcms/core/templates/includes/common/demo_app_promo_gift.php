<?php
/**
 * Demo /demo/app/ bar: gift icon → promo hub + unseen badge (localStorage).
 */
global $abc;
require_once ROOT_DIR . 'functions/promo_front.php';
$_promo_gift_label = trim((string) i18n('common|demo_app_promo_gift'));
if ($_promo_gift_label === '' || strpos($_promo_gift_label, 'common|') === 0) {
	$_promo_gift_label = 'Promo & bonuses';
}
$_promo_badge = function_exists('promo_front_demo_badge_data') ? promo_front_demo_badge_data($abc) : array('hub' => '', 'items' => array());
$_promo_seen_key = function_exists('promo_front_seen_storage_key') ? promo_front_seen_storage_key() : 'promo_seen_v1';
if (!empty($_promo_badge['items'])) :
	$_promo_gift_href = (string)$_promo_badge['hub'];
?>
		<a class="demo-app-icon-btn demo-app-promo-gift" id="demoAppPromoGift"
			href="<?= htmlspecialchars($_promo_gift_href, ENT_QUOTES, 'UTF-8') ?>"
			data-promo-items="<?= htmlspecialchars(json_encode($_promo_badge['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
			data-promo-seen-key="<?= htmlspecialchars($_promo_seen_key, ENT_QUOTES, 'UTF-8') ?>"
			title="<?= htmlspecialchars($_promo_gift_label, ENT_QUOTES, 'UTF-8') ?>"
			aria-label="<?= htmlspecialchars($_promo_gift_label, ENT_QUOTES, 'UTF-8') ?>">
			<i class="fa-solid fa-gift" aria-hidden="true"></i>
		</a>
<?php endif; ?>
