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
            "mainEntityOfPage": {
                "@type": "Webpage",
                "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"
            },
            "headline": "<?=$q['name']?>",
            "description": "<?=$q['description']?>",
            "articleBody": "<?=$q['name_2']?>",
            "dateCreated": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "dateModified": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "datePublished": "<?=$q['date']?date('Y-m-d H:i:s',strtotime($q['date'])):date('Y-m-d H:i:s')?>",
            "image": {
                "@type": "ImageObject",
                "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$q['gimg']?imgstr('gallery',$q['gimg'],$abc['gallery'][$q['gimg']]['img'],'800x512'):imgstr('news',$q['id'],$q['img'],'800x512')?>",
                "width": "800px",
                "height": "512px",
                "caption": "<?=$q['gimg']?$abc['gallery'][$q['gimg']]['alt'.$abc['langid']]:$q['img_alt']?>",
                "thumbnail": "https://<?=$_SERVER['HTTP_HOST']?><?=$q['gimg']?imgstr('gallery',$q['gimg'],$abc['gallery'][$q['gimg']]['img'],'416x300'):imgstr('news',$q['id'],$q['img'],'416x300')?>"
            },
            "publisher": {
                "@type": "Organization",
                "name": "<?= htmlspecialchars(function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road', ENT_QUOTES, 'UTF-8') ?>",
                "logo": {
                    "@type": "ImageObject",
                    "url": "https://<?=$_SERVER['HTTP_HOST']?>/assets/images/logo.webp"
                }
            },
            "author": <?= json_encode($__blog_author_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
            "speakable": {
                "@type": "SpeakableSpecification",
                "xPath": [
                    "/html/head/title",
                    "/html/head/meta[@name='description']/@content"
                ],
                "url": "https://<?=$_SERVER['HTTP_HOST']?><?=$_SERVER['REQUEST_URI']?>"
            }
        },
        {
            "@type": "WebSite",
            "name": "<?= htmlspecialchars(function_exists('site_brand_name') ? site_brand_name() : 'Chicken Road', ENT_QUOTES, 'UTF-8') ?>",
            "url": "https://<?=$_SERVER['HTTP_HOST']?>/",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://<?=$_SERVER['HTTP_HOST']?>/search/?q={search_term_string}",
                "query-input": "required name=search_term_string"
            },
            "sameAs": []
        }
    ]
}</script>

    <?=html_render('common/breadcrumb',$abc['breadcrumb'])?>

    <section>
      <div class='container'>
        <div class="row">
          <div class="col-12">
            <?php if (function_exists('site_render_author_byline')) echo site_render_author_byline($abc, array('date' => $q['date'] ?? '')); ?>
            <!--text-->
            <div class='text'>
              <div class="text page-content-from-db">
                <?= function_exists('site_seo_clean_content') ? site_seo_clean_content($q['text1'] . $q['text2']) : ($q['text1'] . $q['text2']) ?>
              </div>
            </div>
            <!--text-->

<?php /*
            <div class='class-77'>
              <div>
                <div class='class-78'><?=i18n('common|share')?></div>
                <div class='class-79'>
                  <div><a class='social copylink' target='_blank' href='#'><img class='aspect1 w-100' src='/assets/link.svg' alt='Copy link' title='Copy link'></a></div>
                  <div><a class='social' target='_blank' href='https://www.facebook.com/share.php?u=<?=urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])?>&title=<?=urlencode($q['name'.$abc['langid']])?>' rel='nofollow'><img class='aspect1 w-100' src='/assets/facebook.svg' alt='Facebook' title='Facebook'></a></div>
                  <div><a class='social' target='_blank' href='https://twitter.com/intent/tweet?text=<?=urlencode($q['name'.$abc['langid']])?>&url=<?=urlencode('https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])?>' rel='nofollow'><img class='aspect1 w-100' src='/assets/x.svg' alt='X (Twitter)' title='X (Twitter)'></a></div>
                </div>
              </div>
            </div>
*/ ?>

          </div>
        </div>
      </div>
    </section>