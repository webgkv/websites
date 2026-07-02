<?php
require_once ROOT_DIR . 'functions/author_profiles.php';

$authors_base = author_public_base($abc);
$list_title = author_list_title();
$read_more = author_read_more_label();
?>
<?= html_render('common/breadcrumb', $abc['breadcrumb']) ?>
<section class="py-5">
    <div class="container">
        <?php if (!empty($abc['author_single'])) : ?>
            <?php
            $author = $abc['author_single'];
            $origin = function_exists('site_seo_public_origin') ? site_seo_public_origin() : ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $profile_schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                'mainEntity' => author_schema_person($author, $abc),
                'url' => rtrim($origin, '/') . author_profile_url($author, $abc),
            );
            ?>
            <script type="application/ld+json"><?= htmlspecialchars(json_encode($profile_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_NOQUOTES, 'UTF-8') ?></script>
            <?php
            $photo = author_photo_url($author);
            $photo_alt = trim((string)($author['photo_alt'] ?? ''));
            if ($photo_alt === '') {
                $photo_alt = (string)($author['name'] ?? '');
            }
            $social = author_social_data($author);
            $profiles = $social['profiles'];
            $references = $social['references'];
            $refs_title = (string)(function_exists('i18n') ? i18n('common|author_references_title') : 'References');
            if ($refs_title === '' || $refs_title === 'common|author_references_title') {
                $refs_title = 'References';
            }
            ?>
            <article class="author-profile">
                <div class="row align-items-start mb-4">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                        <div class="author-photo-wrapper d-inline-block rounded-circle overflow-hidden" style="width: 160px; height: 160px; border: 2px solid #43a047; background: #222;">
                            <?php if ($photo !== '') : ?>
                                <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo_alt, ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else : ?>
                                <i class="fa-solid fa-user-tie" style="font-size: 90px; line-height: 160px; color: #fff;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h1 class="mb-2"><?= htmlspecialchars((string)$author['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <?php if (!empty($author['job_title'])) : ?>
                            <p class="text-uppercase mb-3" style="color: #43a047; font-weight: 600; letter-spacing: 0.5px;"><?= htmlspecialchars((string)$author['job_title'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($profiles) : ?>
                            <ul class="list-inline author-social mb-0">
                                <?php foreach ($profiles as $key => $url) : ?>
                                    <li class="list-inline-item mr-3">
                                        <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" rel="noopener noreferrer me" target="_blank"><?= htmlspecialchars(author_social_label($key), ENT_QUOTES, 'UTF-8') ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="author-profile-bio text page-content-from-db about_content">
                    <?php
                    $bio = trim((string)($author['bio'] ?? ''));
                    if ($bio !== '' && strpos($bio, '<') !== false) {
                        echo function_exists('aviator_seo_clean_content') ? aviator_seo_clean_content($bio) : $bio;
                    } elseif ($bio !== '') {
                        echo nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'));
                    }
                    ?>
                </div>
                <?php if ($references) : ?>
                    <div class="author-references mt-4 pt-3 border-top">
                        <h2 class="h5 mb-3"><?= htmlspecialchars($refs_title, ENT_QUOTES, 'UTF-8') ?></h2>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($references as $ref) : ?>
                                <li class="mb-2">
                                    <a href="<?= htmlspecialchars($ref['url'], ENT_QUOTES, 'UTF-8') ?>" rel="noopener noreferrer" target="_blank"><?= htmlspecialchars($ref['label'], ENT_QUOTES, 'UTF-8') ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </article>
        <?php else : ?>
            <h1 class="mb-4"><?= htmlspecialchars($list_title, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if (empty($abc['authors_list'])) : ?>
                <p class="text-muted">No authors yet.</p>
            <?php else : ?>
                <div class="row">
                    <?php foreach ($abc['authors_list'] as $author) :
                        $slug = author_public_slug($author);
                        $link = $authors_base . $slug . '/';
                        $title = (string)($author['name'] ?? '');
                        $desc = author_excerpt($author);
                        $photo = author_photo_url($author);
                        $photo_alt = trim((string)($author['photo_alt'] ?? ''));
                        if ($photo_alt === '') {
                            $photo_alt = $title;
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" class="guide-card author-list-card card h-100 text-decoration-none">
                            <div class="card-body">
                                <div class="author-list-card-avatar-wrap">
                                    <?php if ($photo !== '') : ?>
                                        <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" class="author-list-card-avatar" alt="<?= htmlspecialchars($photo_alt, ENT_QUOTES, 'UTF-8') ?>" width="88" height="88" loading="lazy" decoding="async">
                                    <?php else : ?>
                                        <span class="author-list-card-avatar author-list-card-avatar--placeholder" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($title !== '') : ?>
                                    <h5 class="card-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h5>
                                <?php endif; ?>
                                <?php if (!empty($author['job_title'])) : ?>
                                    <p class="card-text small text-muted mb-2"><?= htmlspecialchars((string)$author['job_title'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <?php if ($desc !== '') : ?>
                                    <p class="card-text guide-card-desc"><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                                <span class="guide-card-link">&rarr; <?= htmlspecialchars($read_more, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
