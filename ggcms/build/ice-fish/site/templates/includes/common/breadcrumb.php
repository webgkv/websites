<?php if ($i==1) { ?>
        <section>
            <div class="container breadcrumbs">
                <div class="row">
                    <div class="col-12">
<?php } ?>
<?php if ($i<$num_rows) { ?>
                        <a href='<?=$q['url']?>'><?=$q['name']?></a>&nbsp;/
<?php } else {?>
                        <span><?=$q['name']?></span>
                    </div>
                </div>
            </div>
        </section>
<?php } ?>