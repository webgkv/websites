<?php
/**
 * Games: landing (all games + category filter) or single game.
 * URL: /games/, /games/slug/
 */

require_once ROOT_DIR . 'functions/games_categories_func.php';
$game_categories = games_categories_get_map_or_fallback(isset($langid) ? $langid : '', true);
// Prefer dictionary labels when present (games_cat_{slug} in common.php per locale).
foreach ($game_categories as $slug => $current_name) {
	$dict_key = 'common|games_cat_' . $slug;
	$translated = i18n($dict_key);
	if (trim((string)$translated) !== '' && $translated !== $dict_key && $translated !== 'games_cat_' . $slug) {
		$game_categories[$slug] = $translated;
	}
}

$current_lang_id = isset($abc['lang']['id']) ? (int)$abc['lang']['id'] : 1;
$content_i18n_ok = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;

/**
 * Load game i18n rows map by entity_id for current language.
 */
function games_i18n_map($ids, $lang_id) {
	$ids = array_values(array_filter(array_map('intval', (array)$ids)));
	$lang_id = (int)$lang_id;
	if (empty($ids) || $lang_id <= 0) return array();
	$rows = mysql_select("
		SELECT entity_id, url, name, title, description, content
		FROM content_i18n
		WHERE entity='games'
		  AND lang_id=" . $lang_id . "
		  AND entity_id IN (" . implode(',', $ids) . ")
		ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
	", 'rows') ?: array();
	$map = array();
	foreach ($rows as $r) {
		$eid = (int)$r['entity_id'];
		if (!isset($map[$eid])) $map[$eid] = $r; // first row after ORDER BY has best status
	}
	return $map;
}

// Normalize translated URL stored in content_i18n into a route slug (no slashes).
// This prevents broken links like /de/games/<wrong/url/path>/.
function games_i18n_slug($u) {
	$u = trim((string)$u);
	if ($u === '') return '';
	$u = trim($u, '/');
	if ($u === '') return '';
	if (strpos($u, '/') !== false) {
		$parts = explode('/', $u);
		$u = (string)end($parts);
	}
	return trim($u);
}

if (!empty($u[3])) {
    $error++;
} elseif (!empty($u[2])) {
    // Single game: /games/slug/
    $slug = $u[2];
    $game = mysql_select("
        SELECT * FROM games
        WHERE display = 1 AND url = '" . mysql_res($slug) . "'
        LIMIT 1
    ", 'row');
    // For translated URLs: allow matching by content_i18n.url for current language.
    if (!$game && $content_i18n_ok && $current_lang_id > 1) {
        $game = mysql_select("
            SELECT g.*
            FROM games g
            INNER JOIN content_i18n ci
                ON ci.entity='games'
               AND ci.entity_id=g.id
               AND ci.lang_id=" . $current_lang_id . "
               AND ci.url='" . mysql_res($slug) . "'
            WHERE g.display=1
            ORDER BY FIELD(ci.status,'published','review','draft','missing') ASC, ci.id DESC
            LIMIT 1
        ", 'row');
    }
    if ($game) {
        // Canonical slug from `games.url` (before per-request i18n overlay) — used for hreflang / language switcher fallbacks.
        $game_canonical_slug = trim((string)$game['url'], '/');
        $game_id_for_links = (int)$game['id'];
        $landing_page_row = $abc['page'];

        // Apply translated fields when available.
        if ($content_i18n_ok && $current_lang_id > 1) {
            $gi = mysql_select("
                SELECT url, name, title, description, content
                FROM content_i18n
                WHERE entity='games'
                  AND entity_id=" . (int)$game['id'] . "
                  AND lang_id=" . $current_lang_id . "
                ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
                LIMIT 1
            ", 'row');
            if ($gi) {
				if (isset($gi['url']) && trim((string)$gi['url']) !== '') {
					$slug2 = games_i18n_slug($gi['url']);
					if ($slug2 !== '') $game['url'] = $slug2;
				}
                if (isset($gi['name']) && trim((string)$gi['name']) !== '') $game['name'] = (string)$gi['name'];
                if (isset($gi['title']) && trim((string)$gi['title']) !== '') $game['title'] = (string)$gi['title'];
                if (isset($gi['description']) && trim((string)$gi['description']) !== '') {
					$game['name_2'] = (string)$gi['description'];
					$game['description'] = (string)$gi['description'];
				}
                if (isset($gi['content']) && trim((string)$gi['content']) !== '') $game['text'] = (string)$gi['content'];
            }
        }
        $games_base = get_url('page', $abc['page']);
        // Section crumb already added in index.php (page tree or i18n games_title); do not append name/name_EN again.
        $abc['breadcrumb'][] = array('name' => $game['name'], 'url' => preg_replace('#/+#', '/', $games_base . trim((string)$game['url'], '/') . '/'));

        // Language switcher + hreflang: same game per locale as /{lang}/{games-section}/{game-slug}/ (matches router after short-URL injection).
        if (function_exists('page_i18n_slug') && !empty($abc['languages']) && is_array($abc['languages']) && $game_id_for_links > 0 && $game_canonical_slug !== '') {
            foreach ($abc['languages'] as $i => $ldata) {
                $langUrl = isset($ldata['url']) ? trim((string)$ldata['url'], '/') : '';
                $targetLid = isset($ldata['id']) ? (int)$ldata['id'] : 0;
                if ($langUrl === '' || $targetLid <= 0) {
                    continue;
                }
                $sec = page_i18n_slug((int)$landing_page_row['id'], $targetLid);
                if ($sec === null) {
                    $legacy = isset($landing_page_row['url' . ($i > 1 ? $i : '')]) ? trim((string)$landing_page_row['url' . ($i > 1 ? $i : '')], '/') : '';
                    $sec = ($legacy !== '') ? $legacy : null;
                }
                if ($sec === null || $sec === '') {
                    $sec = trim((string)($landing_page_row['url'] ?? ''), '/');
                }
                if ($sec === '') {
                    $sec = 'games';
                }
                $gslug = $game_canonical_slug;
                if ($content_i18n_ok && $targetLid > 1) {
                    $gi2 = mysql_select("
                        SELECT url
                        FROM content_i18n
                        WHERE entity='games'
                          AND entity_id=" . $game_id_for_links . "
                          AND lang_id=" . $targetLid . "
                        ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
                        LIMIT 1
                    ", 'row');
                    if ($gi2 && isset($gi2['url']) && trim((string)$gi2['url']) !== '') {
                        $gu = games_i18n_slug($gi2['url']);
                        if ($gu !== '') {
                            $gslug = $gu;
                        }
                    }
                }
                if ($gslug === '') {
                    $abc['links'][$langUrl] = array($langUrl);
                } else {
                    $abc['links'][$langUrl] = array($langUrl, $sec, $gslug);
                }
            }
        }

        $abc['page'] = array_merge($abc['page'], $game);
        $abc['game_single'] = $game;
        $game_text = $game['text'];
        // Images: /images/games/... — wrap in centering container and limit height on mobile
        $game_text = preg_replace_callback('/<img(\s[^>]*)\/?>/i', function ($m) {
            return '<div class="guide-img-center game-img-center">' . $m[0] . '</div>';
        }, $game_text);
        $abc['game_single']['text'] = $game_text;
    } else {
        $error++;
    }
} else {
    // Landing: all games + category filters
    $abc['game_categories'] = $game_categories;
    $abc['game_landing'] = true;
    $cat_filter = isset($_GET['category']) && isset($game_categories[$_GET['category']]) ? $_GET['category'] : '';
    $abc['game_category_filter'] = $cat_filter;
    $where = $cat_filter ? " AND category = '" . mysql_res($cat_filter) . "'" : '';
    $abc['game_list'] = mysql_select("
        SELECT * FROM games
        WHERE display = 1 $where
        ORDER BY position ASC, id ASC
    ", 'rows');
		// Apply translated card fields from content_i18n (name/description/url) for current language.
		// IMPORTANT: Do not hide *all* cards if we fail to find any translations in content_i18n,
		// otherwise the whole section becomes visually empty (looks like broken page).
		if (!empty($abc['game_list']) && $content_i18n_ok && $current_lang_id > 1) {
        $ids = array();
        foreach ($abc['game_list'] as $g) $ids[] = (int)$g['id'];
        $i18n_map = games_i18n_map($ids, $current_lang_id);
			$translated = array();
			$translated_count = 0;

			// First pass: override fields for translated rows; collect translated cards only when content exists.
			foreach ($abc['game_list'] as &$g) {
				$gid = (int)$g['id'];
				if (!isset($i18n_map[$gid])) continue;
				$gi = $i18n_map[$gid];

				// If we have translated content, show only then.
				$has_content = !empty($gi['content']) && trim((string)$gi['content']) !== '';
				if ($has_content) $translated_count++;

				if (isset($gi['url']) && trim((string)$gi['url']) !== '') {
					$slug2 = games_i18n_slug($gi['url']);
					if ($slug2 !== '') $g['url'] = $slug2;
				}
				if (isset($gi['name']) && trim((string)$gi['name']) !== '') $g['name'] = (string)$gi['name'];
				if (isset($gi['description']) && trim((string)$gi['description']) !== '') $g['name_2'] = (string)$gi['description'];

				if ($has_content) $translated[] = $g;
			}
			unset($g);

			// Second pass: only replace list if we found at least one real translation.
			if ($translated_count > 0) {
				$abc['game_list'] = $translated;
			}
    }
    foreach ($abc['game_list'] as &$g) {
        if (!empty($g['img']) && function_exists('content_img_disk_path')) {
            $p = content_img_disk_path('games', $g, 'img');
            $g['img_v'] = ($p && is_file($p)) ? filemtime($p) : '';
        }
    }
    unset($g);
}
