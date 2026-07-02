<?php
/**
 * Set homepage hero copy (Lotterypro-style, lottery-generic) in common.php dictionaries.
 * CLI: php scripts/apply_hero_lottery_dict.php
 */
if (php_sapi_name() !== 'cli') {
	die("CLI only\n");
}

$root = dirname(__DIR__) . '/';
define('ROOT_DIR', $root);
require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'admin/modules/_i18n.php';

$hero_keys = array(
	'hero_subtitle',
	'hero_h1_prefix',
	'hero_h1_accent_1',
	'hero_h1_mid',
	'hero_h1_accent_2',
	'hero_h1_tail',
	'hero_lead',
	'hero_cta',
	'hero_explore',
);

$by_lang = array(
	1 => array(
		'hero_subtitle' => "Now's your chance to win jackpot!",
		'hero_h1_prefix' => 'Get',
		'hero_h1_accent_1' => 'Involved',
		'hero_h1_mid' => 'For a',
		'hero_h1_accent_2' => 'quick win',
		'hero_h1_tail' => 'the jackpots',
		'hero_lead' => "Play the world's largest lotteries from home to win jackpots.",
		'hero_cta' => 'Play Lottery',
		'hero_explore' => 'Explore more',
	),
	3 => array(
		'hero_subtitle' => 'C’est le moment de tenter le jackpot !',
		'hero_h1_prefix' => 'Participez',
		'hero_h1_accent_1' => 'maintenant',
		'hero_h1_mid' => 'pour une',
		'hero_h1_accent_2' => 'gagne rapide',
		'hero_h1_tail' => 'aux jackpots',
		'hero_lead' => 'Jouez aux plus grandes loteries du monde depuis chez vous.',
		'hero_cta' => 'Jouer à la loterie',
		'hero_explore' => 'En savoir plus',
	),
	4 => array(
		'hero_subtitle' => 'Jetzt ist Ihre Chance auf den Jackpot!',
		'hero_h1_prefix' => 'Machen Sie',
		'hero_h1_accent_1' => 'mit',
		'hero_h1_mid' => 'für einen',
		'hero_h1_accent_2' => 'schnellen Gewinn',
		'hero_h1_tail' => 'bei Jackpots',
		'hero_lead' => 'Spielen Sie die größten Lotterien der Welt von zu Hause aus.',
		'hero_cta' => 'Lotterie spielen',
		'hero_explore' => 'Mehr erfahren',
	),
	6 => array(
		'hero_subtitle' => '¡Ahora es tu oportunidad de ganar el jackpot!',
		'hero_h1_prefix' => 'Participa',
		'hero_h1_accent_1' => 'ya',
		'hero_h1_mid' => 'para un',
		'hero_h1_accent_2' => 'premio rápido',
		'hero_h1_tail' => 'en los jackpots',
		'hero_lead' => 'Juega las loterías más grandes del mundo desde casa.',
		'hero_cta' => 'Jugar lotería',
		'hero_explore' => 'Explorar más',
	),
	9 => array(
		'hero_subtitle' => 'Сейчас ваш шанс выиграть джекпот!',
		'hero_h1_prefix' => 'Участвуйте',
		'hero_h1_accent_1' => 'в игре',
		'hero_h1_mid' => 'ради',
		'hero_h1_accent_2' => 'быстрого выигрыша',
		'hero_h1_tail' => 'в джекпотах',
		'hero_lead' => 'Играйте в крупнейшие лотереи мира из дома и выигрывайте джекпоты.',
		'hero_cta' => 'Играть в лотерею',
		'hero_explore' => 'Подробнее',
	),
	18 => array(
		'hero_subtitle' => 'Зараз ваш шанс виграти джекпот!',
		'hero_h1_prefix' => 'Беріть',
		'hero_h1_accent_1' => 'участь',
		'hero_h1_mid' => 'за',
		'hero_h1_accent_2' => 'швидкий виграш',
		'hero_h1_tail' => 'у джекпотах',
		'hero_lead' => 'Грайте в найбільші лотереї світу вдома та вигравайте джекпоти.',
		'hero_cta' => 'Грати в лотерею',
		'hero_explore' => 'Дізнатися більше',
	),
);

$default = $by_lang[1];
$paths = glob(ROOT_DIR . 'files/languages/*/dictionary/common.php') ?: array();
$updated = 0;

foreach ($paths as $path) {
	$lang_id = (int) basename(dirname(dirname($path)));
	$dict = admin_load_common_dict($lang_id);
	if (!$dict) {
		echo "SKIP lang {$lang_id}\n";
		continue;
	}
	$patch = isset($by_lang[$lang_id]) ? $by_lang[$lang_id] : $default;
	foreach ($hero_keys as $key) {
		if (isset($patch[$key])) {
			$dict[$key] = $patch[$key];
		}
	}
	$res = admin_save_common_dict($lang_id, $dict);
	if (empty($res['ok'])) {
		echo "FAIL lang {$lang_id}: " . ($res['message'] ?? 'unknown') . "\n";
		exit(1);
	}
	$updated++;
	echo "OK lang {$lang_id}\n";
}

echo "Done. Updated {$updated} dictionaries.\n";
