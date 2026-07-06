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
              <a href='<?= (trim((string)($abc['lang']['url'] ?? ''), '/') !== '' ? '/' . trim((string)($abc['lang']['url'] ?? ''), '/') . '/blog/' : '/blog/') . trim((string)($abc['gcats'][$q['category']]['url'.$abc['langid']] ?? ''), '/') . '/' . trim((string)$q['url'.$abc['langid']], '/') . '/' ?>'>
                <?=$q['name'.$abc['langid']]?>
              </a><br>
              <div style='color:#999;font-size:17px;font-weight:400'><?=$q['name_2'.$abc['langid']]?></div>
            </div>
          </li>
<?php if($i==$num_rows) { ?>
        </ul>
<?=html_render('pagination/default_front', $abc['blog']);?>
      </div>
    </div>
  </div>
</section>
<?php } ?>
