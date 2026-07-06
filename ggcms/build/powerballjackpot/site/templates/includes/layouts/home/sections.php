<?php
require_once ROOT_DIR . 'functions/site_lottery_simulator.php';
$pbj_slides = site_home_lottery_slides();
$pbj_games_cfg = site_home_lottery_games_load();
$pbj_games = site_home_lottery_enabled_games();
$pbj_games_defaults = $pbj_games_cfg['defaults'];
$pbj_results = site_home_lottery_recent_results();
$pbj_counters = site_home_lottery_counters();
$pbj_steps = site_home_lottery_how_steps();
$pbj_why = site_home_lottery_why_items();
$pbj_arrow = site_home_lottery_arrow_icon();
$pbj_brand = function_exists('site_brand_name') ? site_brand_name() : 'PowerBall Jackpot';
$pbj_hero_img_alt = site_home_lottery_esc($pbj_brand . ' ' . site_home_lottery_i18n('home_hero_img_alt', '— lottery jackpots'));
?>
<script type="application/json" id="pbj-home-i18n"><?= site_home_lottery_js_i18n_json() ?></script>
    <section class="banner-section index dark-ui" id="index">
        <div class="overlay">
            <div class="banner-content position-relative">
                <div class="shape-area">
                    <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-1.png')) ?>" class="shape-1" alt="" width="120" height="120" loading="lazy">
                    <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-2.png')) ?>" class="shape-2" alt="" width="100" height="100" loading="lazy">
                    <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-3.png')) ?>" class="shape-3" alt="" width="110" height="110" loading="lazy">
                    <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-4.png')) ?>" class="shape-4" alt="" width="90" height="90" loading="lazy">
                </div>
                <div class="container">
                    <div class="row justify-content-between align-items-center g-4">
                        <div class="col-lg-6 col-md-7">
                            <div class="main-content">
                                <div class="top-area section-text">
                                    <p class="sub-title"><?= site_home_lottery_esc(site_home_lottery_i18n('hero_subtitle', "Now's your chance to win jackpot!")) ?></p>
                                    <h1 class="title"><?= site_home_lottery_hero_h1_html() ?></h1>
                                    <p class="xltxt"><?= site_home_lottery_esc(site_home_lottery_i18n('hero_lead', "Play the world's largest lotteries from home to win jackpots.")) ?></p>
                                    <div class="btn-area d-flex flex-wrap align-items-center gap-3 mt-30">
                                        <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn">
                                            <?= site_home_lottery_esc(site_home_lottery_i18n('hero_cta', 'Play Lottery')) ?><?= $pbj_arrow ?>
                                        </a>
                                        <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn alt">
                                            <?= site_home_lottery_esc(site_home_lottery_i18n('hero_explore', 'Explore more')) ?><?= $pbj_arrow ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-5">
                            <div class="sec-img">
                                <img src="<?= site_home_lottery_esc(site_home_lottery_img('index-illus.png')) ?>" class="max-un" alt="<?= $pbj_hero_img_alt ?>" width="640" height="520" loading="eager" decoding="async" fetchpriority="high">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lottery-slider dark-ui">
        <div class="overlay pb-120">
            <div class="container">
                <div class="row wow fadeInUp">
                    <div class="col-12 lottery-carousel">
<?php foreach ($pbj_slides as $slide): ?>
                        <div class="single">
                            <div class="single-slide">
                                <div class="icon-area">
                                    <img src="<?= site_home_lottery_esc(site_home_lottery_img($slide['icon'])) ?>" alt="">
                                </div>
                                <h5><?= site_home_lottery_esc($slide['name']) ?></h5>
                                <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_slider_estimated_prize', 'Estimated Prize :')) ?> <span><?= site_home_lottery_esc($slide['prize']) ?></span></p>
                                <p class="lgtxt"><?= site_home_lottery_esc($slide['days']) ?></p>
                                <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn"><?= site_home_lottery_esc(site_home_lottery_i18n('play_now', 'Play Now')) ?></a>
                            </div>
                        </div>
<?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lucky-number section-bg dark-ui">
        <div class="overlay pb-120">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-7">
                        <div class="section-header text-center">
                            <h2 class="title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_picker_title', 'Choose Lucky Numbers')) ?></h2>
                            <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_picker_lead', 'Pick your numbers for the next draw — switch lotteries to compare jackpots and schedules.')) ?></p>
                            <div class="btn-area mt-3">
                                <a href="<?= site_home_lottery_esc(site_lottery_sim_demo_url($abc)) ?>" class="cmn-btn alt">
                                    <?= site_home_lottery_esc(site_lottery_sim_i18n('sim_try_simulator', 'Try free simulator')) ?><?= $pbj_arrow ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-lg-10 col-md-12">
                        <div class="menu-area">
                            <div class="box position-relative d-flex align-items-center">
                                <select class="tab-to-select">
                                    <option value=""><?= site_home_lottery_esc(site_home_lottery_i18n('home_select_lottery', 'Select lottery')) ?></option>
<?php foreach ($pbj_slides as $i => $slide): ?>
                                    <option value="<?= site_home_lottery_esc($slide['id']) ?>"<?= $i === 0 ? ' selected' : '' ?>><?= site_home_lottery_esc($slide['name']) ?></option>
<?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-md-block d-none">
                                <ul class="nav justify-content-center" role="tablist">
<?php foreach ($pbj_slides as $i => $slide): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link<?= $i === 0 ? ' active' : '' ?>" id="<?= site_home_lottery_esc($slide['id']) ?>-tab" data-bs-toggle="tab" data-bs-target="#<?= site_home_lottery_esc($slide['id']) ?>" type="button" role="tab" aria-controls="<?= site_home_lottery_esc($slide['id']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                            <span class="single-item d-flex gap-3 text-start align-items-center">
                                                <span class="icon-area">
                                                    <img src="<?= site_home_lottery_esc(site_home_lottery_img($slide['icon'])) ?>" alt="">
                                                </span>
                                                <span class="text-area align-items-center">
                                                    <span class="lgtxt heading"><?= site_home_lottery_esc($slide['name']) ?></span>
                                                    <span class="price"><?= site_home_lottery_esc(site_home_lottery_i18n('home_slider_estimated_prize', 'Estimated Prize :')) ?> <span><?= site_home_lottery_esc($slide['prize']) ?></span></span>
                                                </span>
                                            </span>
                                        </button>
                                    </li>
<?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8">
                        <div class="tab-content">
<?php foreach ($pbj_games as $i => $game): ?>
                            <div class="tab-pane fade<?= $i === 0 ? ' show active' : '' ?> pbj-lucky-tab" id="<?= site_home_lottery_esc($game['id']) ?>" role="tabpanel" aria-labelledby="<?= site_home_lottery_esc($game['id']) ?>-tab" data-pbj-game="<?= site_home_lottery_esc($game['id']) ?>">
                                <div class="row gap-4 gap-md-0">
                                    <div class="col-md-6">
                                        <?= site_home_lottery_picker_area($game, $pbj_games_defaults) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= site_home_lottery_ticket_panel($pbj_cta_url, $pbj_arrow, $game, $pbj_games_defaults) ?>
                                    </div>
                                </div>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="application/json" id="pbj-lucky-config"><?= site_home_lottery_games_json_for_js() ?></script>
    </section>

    <section class="about-us dark-ui">
        <div class="overlay position-relative pt-120 pb-120">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="section-text">
                            <h5 class="sub-title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_promo_subtitle', 'Play with us')) ?></h5>
                            <h2 class="title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_promo_title', 'Try your luck — major jackpots within reach')) ?></h2>
                            <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_promo_lead', 'With PowerBall Jackpot you can try PowerBall, Mega Millions, EuroMillions and regional draws — guides, results and estimated prizes in one hub.')) ?></p>
                        </div>
                        <div class="btn-area">
                            <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn">
                                <?= site_home_lottery_esc(site_home_lottery_i18n('play_now', 'Play Now')) ?><?= $pbj_arrow ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="recent-lottery-results section-bg dark-ui">
        <div class="overlay pt-120 pb-120">
            <div class="container">
                <div class="row gap-3 gap-sm-0 section-text align-items-center">
                    <div class="col-lg-6 col-sm-8">
                        <div class="section-title">
                            <h2 class="title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_results_title', 'Recent Lottery Results')) ?></h2>
                            <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_results_lead', 'Latest winning numbers and payout highlights from major draws — updated after each published result.')) ?></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-sm-4 d-flex justify-content-start justify-content-sm-end">
                        <div class="btn-area">
                            <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn alt">
                                <?= site_home_lottery_esc(site_home_lottery_i18n('home_view_all', 'View All')) ?><?= $pbj_arrow ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="row cus-mar align-items-center">
<?php foreach ($pbj_results as $row): ?>
                    <div class="col-lg-6">
                        <div class="single-box">
                            <div class="img-area">
                                <img src="<?= site_home_lottery_esc(site_home_lottery_img($row['img'])) ?>" alt="">
                            </div>
                            <div class="text-area">
                                <p class="lgtxt"><span class="lgtxt"><?= site_home_lottery_esc($row['winner']) ?></span> · <?= site_home_lottery_esc($row['ago']) ?></p>
                                <span class="price"><?= site_home_lottery_esc(site_home_lottery_i18n('home_results_amount', 'Amount :')) ?> <span><?= site_home_lottery_esc($row['amount']) ?></span></span>
                                <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_results_draw_date', 'Draw took place on :')) ?> <span><?= site_home_lottery_esc($row['date']) ?></span></p>
                                <div class="lucky-number">
                                    <div class="tab-content m-0">
                                        <ul class="justify-content-start">
                                            <?= site_home_lottery_result_balls($row['nums'], $row['active']) ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="ticket-checker dark-ui">
        <div class="overlay position-relative pt-120 pb-120">
            <div class="shape-area">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-2.png')) ?>" class="shape-1" alt="">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-2.png')) ?>" class="shape-2" alt="">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('coin.png')) ?>" class="shape-5" alt="">
            </div>
            <div class="container">
                <div class="row justify-content-end">
                    <div class="col-lg-6">
                        <div class="main-content">
                            <?= site_home_lottery_checker_panel($pbj_slides) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="counter-section dark-ui">
        <div class="overlay pt-120 pb-120">
            <div class="container">
                <div class="row cus-mar justify-content-start align-items-center">
<?php foreach ($pbj_counters as $box): ?>
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="single-box">
                            <div class="icon-area">
                                <img src="<?= site_home_lottery_esc(site_home_lottery_icon($box['icon'])) ?>" alt="">
                            </div>
                            <div class="text-area<?= !empty($box['even']) ? ' even' : '' ?>">
                                <h3><span class="counter"><?= site_home_lottery_esc($box['value']) ?></span><?= site_home_lottery_esc($box['suffix']) ?></h3>
                                <p><?= site_home_lottery_esc($box['label']) ?></p>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="how-works section-bg dark-ui" id="pbj-how-works">
        <div class="overlay position-relative pt-120 pb-120">
            <div class="shape-area">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-1.png')) ?>" class="shape-1" alt="">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-2.png')) ?>" class="shape-2" alt="">
                <img src="<?= site_home_lottery_esc(site_home_lottery_img('ball-bg-icon-4.png')) ?>" class="shape-3" alt="">
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-xl-7 col-xxl-6">
                        <div class="section-header text-center">
                            <h5 class="sub-title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_how_subtitle', 'How it works')) ?></h5>
                            <h2 class="title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_how_title', 'How it works')) ?></h2>
                            <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_how_lead', 'From comparing jackpots to checking your numbers — four simple steps.')) ?></p>
                        </div>
                    </div>
                </div>
                <div class="row overflow-hidden">
                    <div class="col-lg-12">
                        <div class="scroll-line">
<?php foreach ($pbj_steps as $step): ?>
<?php if ($step['side'] === 'left'): ?>
                            <div class="row align-items-center">
                                <div class="col-xl-5 col-md-7 col-sm-10">
                                    <div class="single-box">
                                        <div class="text-area text-end">
                                            <h4><?= site_home_lottery_esc($step['title']) ?></h4>
                                            <p><?= site_home_lottery_esc($step['text']) ?></p>
                                        </div>
                                        <div class="icon-area">
                                            <img src="<?= site_home_lottery_esc(site_home_lottery_icon($step['icon'])) ?>" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-2 d-none d-sm-block d-flex justify-content-center">
                                    <div class="point-area"><span><?= (int) $step['num'] ?></span></div>
                                </div>
                            </div>
<?php else: ?>
                            <div class="row align-items-center justify-content-end">
                                <div class="col-sm-2 d-none d-sm-block d-flex justify-content-center">
                                    <div class="point-area"><span><?= (int) $step['num'] ?></span></div>
                                </div>
                                <div class="col-xl-5 col-md-7 col-sm-10">
                                    <div class="single-box">
                                        <div class="icon-area">
                                            <img src="<?= site_home_lottery_esc(site_home_lottery_icon($step['icon'])) ?>" alt="">
                                        </div>
                                        <div class="text-area">
                                            <h4><?= site_home_lottery_esc($step['title']) ?></h4>
                                            <p><?= site_home_lottery_esc($step['text']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
<?php endif; ?>
<?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-best section-bg dark-ui">
        <div class="overlay pt-120 pb-120">
            <div class="container">
                <div class="row gap-4 gap-lg-0 align-items-center">
                    <div class="col-lg-6">
                        <div class="section-text">
                            <h5 class="sub-title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_why_subtitle', 'Why PowerBall Jackpot')) ?></h5>
                            <h2 class="title"><?= site_home_lottery_esc(site_home_lottery_i18n('home_why_title', 'Your lottery results hub')) ?></h2>
                            <p><?= site_home_lottery_esc(site_home_lottery_i18n('home_why_lead', 'Jackpots, recent draws and number tools — everything you need before you visit an official operator.')) ?></p>
                        </div>
                        <div class="btn-area">
                            <a href="<?= site_home_lottery_esc($pbj_cta_url) ?>" class="cmn-btn alt">
                                <?= site_home_lottery_esc(site_home_lottery_i18n('play_now', 'Play Now')) ?><?= $pbj_arrow ?>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row cus-mar">
                            <div class="col-sm-6">
<?php foreach ([$pbj_why[0], $pbj_why[2]] as $item): ?>
                                <div class="single-box">
                                    <div class="icon-area">
                                        <img src="<?= site_home_lottery_esc(site_home_lottery_icon($item['icon'])) ?>" alt="">
                                    </div>
                                    <div class="text-area">
                                        <h5><?= site_home_lottery_esc($item['title']) ?></h5>
                                        <p class="ndtxt"><?= site_home_lottery_esc($item['text']) ?></p>
                                    </div>
                                </div>
<?php endforeach; ?>
                            </div>
                            <div class="col-sm-6 mt-0 mt-sm-4">
<?php foreach ([$pbj_why[1], $pbj_why[3]] as $item): ?>
                                <div class="single-box">
                                    <div class="icon-area">
                                        <img src="<?= site_home_lottery_esc(site_home_lottery_icon($item['icon'])) ?>" alt="">
                                    </div>
                                    <div class="text-area">
                                        <h5><?= site_home_lottery_esc($item['title']) ?></h5>
                                        <p class="ndtxt"><?= site_home_lottery_esc($item['text']) ?></p>
                                    </div>
                                </div>
<?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
