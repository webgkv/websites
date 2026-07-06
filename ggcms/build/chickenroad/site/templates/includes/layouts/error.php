<?php
$error_title = isset($abc['page']['title' . (isset($abc['langid']) ? $abc['langid'] : '')]) ? $abc['page']['title' . (isset($abc['langid']) ? $abc['langid'] : '')] : 'Page not found';
$error_message = isset($abc['page']['description' . (isset($abc['langid']) ? $abc['langid'] : '')]) ? $abc['page']['description' . (isset($abc['langid']) ? $abc['langid'] : '')] : 'The page you are looking for could not be found.';
?>
        <section class="container py-5">
            <div class="row">
                <div class="col-12 text-center py-5">
                    <h1 class="mb-3"><?= htmlspecialchars($error_title) ?></h1>
                    <p class="lead"><?= htmlspecialchars($error_message) ?></p>
                    <a href="/<?= isset($abc['lang']['url']) ? $abc['lang']['url'] : '' ?>/" class="btn btn-primary mt-3"><?=htmlspecialchars(i18n('common|back_to_home'))?></a>
                </div>
            </div>
        </section>
