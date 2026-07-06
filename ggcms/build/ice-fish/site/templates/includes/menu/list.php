<?php if($i==1) { ?>
                        <ul class="navbar-nav align-items-center">
<?php } ?>
                            <li class="nav-item<?= !empty($q['_submenu']) ? ' dropdown' : '' ?>">
                                <?php if (!empty($q['_submenu'])) : ?>
                                <a class="nav-link dropdown-toggle<?= !empty($q['_active']) ? ' active' : '' ?>" href="<?= str_replace('//','/', $q['_url']) ?>" id="menu-<?= $i ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars($q['name']) ?></a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menu-<?= $i ?>">
                                    <li><a class="dropdown-item" href="<?= str_replace('//','/', $q['_url']) ?>"><?= htmlspecialchars($q['name']) ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($q['_submenu'] as $sub) : ?>
                                    <li><a class="dropdown-item" href="<?= str_replace('//','/', $sub['_url'] ?: $sub['url']) ?>"><?= htmlspecialchars($sub['name']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php else : ?>
                                <a class="nav-link<?= !empty($q['_active']) ? ' active' : '' ?>" href="<?= str_replace('//','/', $q['_url']) ?>"><?= htmlspecialchars($q['name']) ?></a>
                                <?php endif; ?>
                            </li>
<?php if($i==$num_rows) { ?>
                        </ul>
<?php } ?>