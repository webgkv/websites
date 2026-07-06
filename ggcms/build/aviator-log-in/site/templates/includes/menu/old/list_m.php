<?php if ($i==1) { ?>
      <div id='menu_m' class='rounded hidden'>
<div style='position:relative;max-height:100%;overflow-x:hidden;overflow-y:auto'>

        <div style='display:grid;grid-template-columns:1fr;gap:16px;align-items:center;padding-right:20px'>
<?php } ?>
<?php if ($q['_active']==100) {?>
          <div class='tmenu_link'><span><?=$q['name']?><?=($q['_submenu'])?"&nbsp;<img src='/assets/chevron-down-sm.png' alt='more' alt='title'>":''?></span>
<?php } else {?>
          <div class='tmenu_link phide_m h'>
            <div style='display:grid;grid-template-columns:auto 20px;gap:16px;align-items:center'>
              <div><a<?=$q['_url']?' href="'.$q['_url'].'"':''?> title="<?=htmlspecialchars($q['name'])?>" data-menuid="<?=$i?>" class="tmenu_a<?=$q['_submenu']?' expand':''?>"><?=$q['name']?><?=($q['_submenu'])?"</div><div><a href='#' class='dohide_m'></a>":''?></a></div>
            </div>
<?php } ?>
<?php if ($q['_submenu']) { ?>
            <div class='submenu'>
<?php foreach ($q['_submenu'] as $k=>$v) {?>
                <div><a class="submenu_a" href="<?=$v['_url']?>" title="<?=htmlspecialchars($v['name'])?>"><?=$v['name']?></a></div>
<?php } ?>
            </div>
<?php } ?>
          </div>
<?php if ($i==$num_rows) { ?>
        </div>
</div>
      </div>
<?php } ?>