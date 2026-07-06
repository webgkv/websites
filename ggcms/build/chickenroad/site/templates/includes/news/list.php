<?php if($i==1) { ?>
<section>
  <div class='container about_content'>
    <div class="row">
      <div class="col-12">
        <ul>
<?php } ?>
          <li>
            <i class="fa-brands fa-hive"></i>
            <div>
              <a href='<?=get_url('news')?><?=$q['url'.$abc['langid']]?>/'>
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

