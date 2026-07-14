<?php
require_once ROOT_DIR . 'functions/author_profiles.php';
$__blog_author = author_for_abc($abc);
$__blog_author_schema = author_schema_person($__blog_author, $abc, true);
?>
<script type="application/ld+json">{
    "@context": "https://schema.org/",
    "@graph": [
        {
            "@type": "NewsArticle",
            "mainEntityOfPage": { "@type": "Webpage", "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>" },
            "headline": "<?=$q['name']?>",
            "description": "<?=$q['description']?>",
            "articleBody": "<?=$q['name_2']?>",
            "dateCreated": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "dateModified": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "datePublished": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "image": {
                "@type": "ImageObject",
                "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$q['gimg']?imgstr('gallery',$q['gimg'],$abc['gallery'][$q['gimg']]['img'],'800x512'):imgstr('blog',$q['id'],$q['img'],'800x512')?>",
                "width": "800px",
                "height": "512px",
                "caption": "<?=$q['gimg']?$abc['gallery'][$q['gimg']]['alt'.$abc['langid']]:$q['img_alt']?>",
                "thumbnail": "https://<?=$_SERVER['HTTP_HOST']?><?=$q['gimg']?imgstr('gallery',$q['gimg'],$abc['gallery'][$q['gimg']]['img'],'416x300'):imgstr('blog',$q['id'],$q['img'],'416x300')?>"
            },
            "author": <?= json_encode($__blog_author_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            "publisher": {
                "@type": "Organization",
                "name": "<?= htmlspecialchars(function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road', ENT_QUOTES, 'UTF-8') ?>",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://<?=$_SERVER['HTTP_HOST']?>/assets/images/logo.webp"
                }
            },
            "speakable": { "@type": "SpeakableSpecification", "xPath": ["/html/head/title", "/html/head/meta[@name='description']/@content"], "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>" }
        },
        { "@type": "WebSite", "name": "<?= htmlspecialchars(function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road', ENT_QUOTES, 'UTF-8') ?>", "url": "https://<?=$_SERVER['HTTP_HOST']?>/", "potentialAction": { "@type": "SearchAction", "target": "https://<?=$_SERVER['HTTP_HOST']?>/search/?q={search_term_string}", "query-input": "required name=search_term_string" }, "sameAs": [] }
    ]
}</script>

    <?=html_render('common/breadcrumb',$abc['breadcrumb'])?>

    <section>
      <div class='container'>
        <div class="row">
          <div class="col-12">
            <?php if (function_exists('site_render_author_byline')) echo site_render_author_byline($abc, array('date' => $q['date'] ?? '')); ?>
            <div class='text page-content-from-db about_content'>
              <?php
              require_once(ROOT_DIR . 'functions/cta_inject.php');
              require_once(ROOT_DIR . 'functions/blog_promo_guard.php');
              require_once(ROOT_DIR . 'functions/content_exclude_tags.php');
              $offer_path = isset($abc['ad_offer_path']) ? (string)$abc['ad_offer_path'] : '';
              $full_text = (string)($q['text1'] ?? '') . (string)($q['text2'] ?? '');
              $cta_btns = site_cta_buttons_html($offer_path);

              if (blog_promo_should_autoinsert_images($q)) {
              // Random promo images + buttons inside one post (at least 2 different images).
              require_once(ROOT_DIR . 'functions/blog_promo.php');
              
              // Best-effort extraction of <img src="..."> from promo html.
              // Allows both quote types: src="..." and src='...'
              $extract_img_src = function(string $img_html): string {
              	if (preg_match('/<img[^>]+\\s+src=[\\\'"]([^\\\'"]+)[\\\'"]/iu', $img_html, $m)) return (string)($m[1] ?? '');
              	return '';
              };

              $fallback_btns = site_cta_buttons_html($offer_path);

              $promo1 = (!empty($q['blog_promo']) && is_array($q['blog_promo'])) ? $q['blog_promo'] : blog_promo_random();
              $promo2 = blog_promo_random();
              $promo3 = blog_promo_random();

              $src1 = $extract_img_src((string)($promo1['image'] ?? ''));
              $src2 = $extract_img_src((string)($promo2['image'] ?? ''));
              $src3 = $extract_img_src((string)($promo3['image'] ?? ''));

              // Ensure promo2 image differs from promo1 (best-effort).
              $tries = 0;
              while ($src1 !== '' && $src2 !== '' && $src1 === $src2 && $tries < 8) {
              	$promo2 = blog_promo_random();
              	$src2 = $extract_img_src((string)($promo2['image'] ?? ''));
              	$tries++;
              }

              // Ensure promo3 image differs from promo1 and promo2 (best-effort).
              $tries = 0;
              while ($tries < 10) {
              	$src3 = $extract_img_src((string)($promo3['image'] ?? ''));
              	$bad = false;
              	if ($src1 !== '' && $src3 !== '' && $src1 === $src3) $bad = true;
              	if ($src2 !== '' && $src3 !== '' && $src2 === $src3) $bad = true;
              	if (!$bad) break;
              	$promo3 = blog_promo_random();
              	$tries++;
              }

              $promo_html = function($promo) use ($fallback_btns): string {
              	$img_html = (string)($promo['image'] ?? '');
              	$btn_html = (string)($promo['buttons_html'] ?? $promo['buttons'] ?? '');
              	if (trim($btn_html) === '') $btn_html = $fallback_btns;
              	return $img_html . $btn_html;
              };

              $promo1_html = $promo_html($promo1);
              $promo2_html = $promo_html($promo2);
              $promo3_html = $promo_html($promo3);

              $cta_positions = site_cta_even_paragraph_positions(
              	site_count_content_paragraphs($full_text),
              	3
              );
              if (count($cta_positions) >= 1) {
              	$full_text = site_insert_cta_after_paragraphs($full_text, $promo1_html, array($cta_positions[0]));
              }
              if (count($cta_positions) >= 2) {
              	$full_text = site_insert_cta_after_paragraphs($full_text, $promo2_html, array($cta_positions[1]));
              }
              if (count($cta_positions) >= 3) {
              	$full_text = site_insert_cta_after_paragraphs($full_text, $promo3_html, array($cta_positions[2]));
              }

              } elseif ($cta_btns !== '') {
              // skip_random_images: CTA buttons only (noinc still respected).
              $full_text = site_insert_cta_evenly_in_content($full_text, $cta_btns, 3);
              }

              $full_text = content_normalize_noads_links($full_text);
              $full_text = content_unwrap_exclude_tags($full_text);

              echo function_exists('site_seo_clean_content') ? site_seo_clean_content($full_text) : $full_text;
              ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php
    $bi = isset($q['blog_internal']) && is_array($q['blog_internal']) ? $q['blog_internal'] : null;
    if ($bi && (!empty($bi['prev']) || !empty($bi['next']) || !empty($bi['related']))):
    ?>
    <section class="blog-internal-links-wrap">
      <div class="container">
        <?php if (!empty($bi['prev']) || !empty($bi['next'])): ?>
        <nav class="blog-internal-nav" aria-label="Blog">
          <div class="blog-internal-nav-row">
            <?php if (!empty($bi['prev'])): ?>
            <div class="blog-internal-nav-item blog-internal-nav-prev">
              <span class="blog-internal-nav-label"><?= htmlspecialchars(i18n('common|blog_prev_post'), ENT_QUOTES, 'UTF-8') ?></span>
              <a class="blog-internal-nav-title-link" rel="prev" href="<?= htmlspecialchars($bi['prev']['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($bi['prev']['title'], ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <?php endif; ?>
            <?php if (!empty($bi['next'])): ?>
            <div class="blog-internal-nav-item blog-internal-nav-next">
              <span class="blog-internal-nav-label"><?= htmlspecialchars(i18n('common|blog_next_post'), ENT_QUOTES, 'UTF-8') ?></span>
              <a class="blog-internal-nav-title-link" rel="next" href="<?= htmlspecialchars($bi['next']['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($bi['next']['title'], ENT_QUOTES, 'UTF-8') ?></a>
            </div>
            <?php endif; ?>
          </div>
        </nav>
        <?php endif; ?>

        <?php if (!empty($bi['related'])): ?>
        <div class="blog-related">
          <h2 class="blog-related-heading"><?= htmlspecialchars(i18n('common|related_articles'), ENT_QUOTES, 'UTF-8') ?></h2>
          <ul class="blog-related-list">
            <?php foreach ($bi['related'] as $rel): ?>
            <li><a href="<?= htmlspecialchars($rel['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>
