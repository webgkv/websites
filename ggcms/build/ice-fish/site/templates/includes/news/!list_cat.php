<?php if($i==1) { ?>

<?php /*
<script type="application/ld+json">{
    "@context": "https://schema.org/",
    "@type": "WebSite",
    "name": "Ice Fish",
    "url": "https://<?=$_SERVER['HTTP_HOST']?>/",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "https://<?=$_SERVER['HTTP_HOST']?>/search/?q={search_term_string}",
        "query-input": "required name=search_term_string"
    },
    "sameAs": []
}</script>
*/ ?>

<section>
  <div class='container about_content'>
    <div class="row">
      <div class="col-12">
        <ul>
<?php } ?>
          <li>
            <i class="fa-brands fa-hive"></i>
            <div>
              <a href='<?=get_url('news')?><?=$abc['gcats'][$q['category']]['url'.$abc['langid']]?>/<?=$q['url'.$abc['langid']]?>/'>
                <?=$q['name'.$abc['langid']]?>
              </a><br>
              <div style='color:#999;font-size:17px;font-weight:400'><?=$q['name_2'.$abc['langid']]?></div>
            </div>
          </li>
<?php if($i==$num_rows) { ?>
        </ul>

<?=html_render('pagination/default_front', $abc['news']);?>

      </div>
    </div>
  </div>
</section>
<?php } ?>

