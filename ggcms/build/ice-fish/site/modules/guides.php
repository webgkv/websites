<?php
/**
 * Guides: landing (categories), category list, or single guide.
 * URL: /guides/, /guides/category/, /guides/category/slug/
 */

require_once(ROOT_DIR . 'functions/guides_categories_func.php');
$guide_categories = guides_categories_get_map(isset($langid) ? $langid : '', true);
if (empty($guide_categories)) {
	$guide_categories = array(
		'analysis'       => 'Analysis',
		'bonus'          => 'Bonus',
		'how-to-win'     => 'How to Win',
		'signals'        => 'Signals',
		'crash-gambling' => 'Crash Gambling',
	);
}

// Prefer translated category labels from i18n (so FR/EN don't depend on DB seed language columns).
$guide_cat_i18n_keys = array(
	'analysis'       => 'common|guides_cat_analysis',
	'bonus'          => 'common|guides_cat_bonus',
	'how-to-win'     => 'common|guides_cat_how-to-win',
	'signals'        => 'common|guides_cat_signals',
	'crash-gambling' => 'common|guides_cat_crash-gambling',
);
foreach ($guide_categories as $slug => $current_name) {
	if (isset($guide_cat_i18n_keys[$slug])) {
		$translated = i18n($guide_cat_i18n_keys[$slug]);
		if (trim((string)$translated) !== '') $guide_categories[$slug] = $translated;
	}
}

$guides_base = site_section_public_base('guides', $abc);

if (!empty($u[4])) {
    $error++;
} elseif (!empty($u[3])) {
    // Single guide: /{section}/category/slug/
    $cat = isset($guide_categories[$u[2]]) ? $u[2] : '';
    if (!$cat) {
        $error++;
    } else {
        $guide = mysql_select("
            SELECT * FROM guides
            WHERE display = 1 AND category = '" . mysql_res($cat) . "' AND url = '" . mysql_res($u[3]) . "'
            LIMIT 1
        ", 'row');
        if ($guide) {
            $guide_id = (int)$guide['id'];

            // Prefer translated content from content_i18n (saved in admin Translations tab).
            // For canonical language (langid==''), index.php intentionally blanks langid when ==1.
            // So we treat empty as 1 to still use i18n content.
            $effective_langid = ($langid === '' || $langid === null) ? 1 : (int)$langid;
            if ($effective_langid > 0 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
                $ti = mysql_select("
                    SELECT content, name, url, title, description
                    FROM content_i18n
                    WHERE entity='guides'
                      AND entity_id=" . (int)$guide_id . "
                      AND lang_id=" . (int)$effective_langid . "
                    ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
                    LIMIT 1
                ", 'row');
                if ($ti && trim((string)($ti['content'] ?? '')) !== '') {
                    $guide['text'] = (string)$ti['content'];
                    if (!empty($ti['name'])) $guide['name'] = (string)$ti['name'];
                    if (!empty($ti['url'])) $guide['url'] = (string)$ti['url'];
                    if (!empty($ti['title'])) $guide['title'] = (string)$ti['title'];
                    if (!empty($ti['description'])) $guide['description'] = (string)$ti['description'];
                }
            }

            // Replace canonical placeholders even for translated content.
            $guide['text'] = str_replace(array('{{GUIDE_ID}}', '{{ID}}'), (string)$guide_id, (string)$guide['text']);
            $abc['breadcrumb'][] = array('name' => $guide_categories[$cat], 'url' => $guides_base . $cat . '/');
            $abc['breadcrumb'][] = array('name' => $guide['name'], 'url' => $guides_base . $cat . '/' . $guide['url'] . '/');
            $abc['page'] = array_merge($abc['page'], $guide);
            $abc['guide_single'] = $guide;
            $guide_text = function_exists('template_img') ? template_img('guides', $guide, 'imgs', 'text') : (string)$guide['text'];
            // Cache-bust guide images: add ?v=filemtime so updated files load
            $guide_text = preg_replace_callback(
                '#(src=["\'])/files/guides/' . $guide_id . '/img/([^"\']+)(["\'])#',
                function ($m) use ($guide_id) {
                    $path = ROOT_DIR . 'files/guides/' . $guide_id . '/img/' . $m[2];
                    $v = file_exists($path) ? filemtime($path) : time();
                    return $m[1] . '/files/guides/' . $guide_id . '/img/' . $m[2] . '?v=' . $v . $m[3];
                },
                $guide_text
            );
            // Wrap every img in a centering container so images are always centered
            $guide_text = preg_replace_callback('/<img(\s[^>]*)\/?>/i', function ($m) {
                return '<div class="guide-img-center">' . $m[0] . '</div>';
            }, $guide_text);
            $abc['guide_single']['text'] = $guide_text;
            if (!empty($abc['ad_offer_path']) && function_exists('aviator_ad_replace_content_links')) {
                $abc['guide_single']['text'] = aviator_ad_replace_content_links($abc['guide_single']['text'], $abc['ad_offer_path']);
            }
            $abc['guide_category_name'] = $guide_categories[$cat];

            // Language switcher for a single guide:
            // If we have a translated version, point directly to it:
            // /{lang}/guides/{category}/{guide_url}/
            // If translation content is missing -> keep the default /{lang}/guides/ link from index.php.
            if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
                foreach ($abc['languages'] as $ldata) {
                    $langUrl = isset($ldata['url']) ? trim((string)$ldata['url']) : '';
                    $targetLid = isset($ldata['id']) ? (int)$ldata['id'] : 0;
                    if ($langUrl === '' || $targetLid <= 0) continue;

                    $tr = mysql_select("
                        SELECT url, content
                        FROM content_i18n
                        WHERE entity='guides'
                          AND entity_id=" . (int)$guide_id . "
                          AND lang_id=" . (int)$targetLid . "
                        ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
                        LIMIT 1
                    ", 'row');

                    $trUrl = $tr && isset($tr['url']) ? trim((string)$tr['url'], '/') : '';
                    $trContent = $tr && isset($tr['content']) ? trim((string)$tr['content']) : '';

                    if ($trUrl !== '' && $trContent !== '') {
                        $abc['links'][$langUrl] = array($langUrl, site_section_link_segment('guides'), $cat, $trUrl);
                    } elseif ($targetLid === 1) {
                        // Canonical fallback: even if content_i18n is missing for EN,
                        // we still know the guide url from the base `guides` row.
                        $fallbackUrl = isset($guide['url']) ? trim((string)$guide['url'], '/') : '';
                        if ($fallbackUrl !== '') $abc['links'][$langUrl] = array($langUrl, site_section_link_segment('guides'), $cat, $fallbackUrl);
                    }
                }
            }
        } else {
            $error++;
        }
    }
} elseif (!empty($u[2])) {
    // Category list: /{section}/category/
    $cat = isset($guide_categories[$u[2]]) ? $u[2] : '';
    if (!$cat) {
        $error++;
    } else {
		$effective_langid = ($langid === '' || $langid === null) ? 1 : (int)$langid;

		$canon_list = mysql_select("
			SELECT * FROM guides
			WHERE display = 1 AND category = '" . mysql_res($cat) . "'
			ORDER BY position ASC, date DESC
		", 'rows');

		$abc['guide_list'] = array();
		if (is_array($canon_list)) {
			if ($effective_langid > 1 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
				$ids = array();
				foreach ($canon_list as $g) {
					if (isset($g['id'])) $ids[] = (int)$g['id'];
				}
				$ids = array_values(array_filter(array_unique($ids)));

				$i18n_map = array(); // guide_id => row
				if (!empty($ids)) {
					$i18n_rows = mysql_select("
						SELECT entity_id, url, name, title, description, content, status
						FROM content_i18n
						WHERE entity='guides'
						  AND lang_id=" . (int)$effective_langid . "
						  AND entity_id IN (" . implode(',', $ids) . ")
						ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
					", 'rows') ?: array();
					if (is_array($i18n_rows)) {
						foreach ($i18n_rows as $r) {
							$gid = isset($r['entity_id']) ? (int)$r['entity_id'] : 0;
							if ($gid <= 0) continue;
							// Keep first (best status) per guide id.
							if (!isset($i18n_map[$gid])) $i18n_map[$gid] = $r;
						}
					}
				}

				foreach ($canon_list as $g) {
					$gid = isset($g['id']) ? (int)$g['id'] : 0;
					if ($gid <= 0) continue;
					if (empty($i18n_map[$gid])) continue; // hide guides without translation
					$tr = $i18n_map[$gid];
					if (empty($tr['content'])) continue;

					// Use translated url/name/short snippet.
					if (!empty($tr['url'])) $g['url'] = trim((string)$tr['url'], '/');

					// Mini-card title uses $g['name'], but translations may store it in different columns.
					// Prefer content_i18n.name; if empty -> use content_i18n.title; if still empty -> use content_i18n.description (plain text).
					$cardTitle = '';
					if (!empty($tr['name'])) $cardTitle = (string)$tr['name'];
					elseif (!empty($tr['title'])) $cardTitle = (string)$tr['title'];
					elseif (!empty($tr['description'])) $cardTitle = trim(strip_tags((string)$tr['description']));
					if ($cardTitle !== '') $g['name'] = $cardTitle;

					// Mini-card short text uses $g['name_2'].
					if (!empty($tr['description'])) $g['name_2'] = (string)$tr['description'];
					elseif (!empty($tr['title'])) $g['name_2'] = (string)$tr['title'];

					if (!empty($g['img'])) {
						$p = ROOT_DIR . 'files/guides/' . (int)$g['id'] . '/img/' . $g['img'];
						$g['img_v'] = file_exists($p) ? filemtime($p) : '';
					}
					$abc['guide_list'][] = $g;
				}
			} else {
				// EN or no lang: show all canonical guides.
				foreach ($canon_list as &$g) {
					if (!empty($g['img'])) {
						$p = ROOT_DIR . 'files/guides/' . (int)$g['id'] . '/img/' . $g['img'];
						$g['img_v'] = file_exists($p) ? filemtime($p) : '';
					}
				}
				unset($g);
				$abc['guide_list'] = $canon_list ?: array();
			}
		}

        $abc['guide_category'] = $cat;
        $abc['guide_category_name'] = $guide_categories[$cat];
        $abc['guide_categories'] = $guide_categories;
        $abc['breadcrumb'][] = array('name' => $guide_categories[$cat], 'url' => $guides_base . $cat . '/');
    }
} else {
    // Landing: all guides + category filters
    $abc['guide_categories'] = $guide_categories;
    $abc['guide_landing'] = true;
	$effective_langid = ($langid === '' || $langid === null) ? 1 : (int)$langid;

	$canon_list = mysql_select("
		SELECT * FROM guides
		WHERE display = 1
		ORDER BY position ASC, date DESC
	", 'rows');

	$abc['guide_list'] = array();
	if (is_array($canon_list)) {
		if ($effective_langid > 1 && @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0) {
			$ids = array();
			foreach ($canon_list as $g) {
				if (isset($g['id'])) $ids[] = (int)$g['id'];
			}
			$ids = array_values(array_filter(array_unique($ids)));

			$i18n_map = array();
			if (!empty($ids)) {
				$i18n_rows = mysql_select("
					SELECT entity_id, url, name, description, content, status
					FROM content_i18n
					WHERE entity='guides'
					  AND lang_id=" . (int)$effective_langid . "
					  AND entity_id IN (" . implode(',', $ids) . ")
					ORDER BY FIELD(status,'published','review','draft','missing') ASC, id DESC
				", 'rows') ?: array();
				if (is_array($i18n_rows)) {
					foreach ($i18n_rows as $r) {
						$gid = isset($r['entity_id']) ? (int)$r['entity_id'] : 0;
						if ($gid <= 0) continue;
						if (!isset($i18n_map[$gid])) $i18n_map[$gid] = $r;
					}
				}
			}

			foreach ($canon_list as $g) {
				$gid = isset($g['id']) ? (int)$g['id'] : 0;
				if ($gid <= 0) continue;
				if (empty($i18n_map[$gid])) continue;
				$tr = $i18n_map[$gid];
				if (empty($tr['content'])) continue;

				if (!empty($tr['url'])) $g['url'] = trim((string)$tr['url'], '/');

				$cardTitle = '';
				if (!empty($tr['name'])) $cardTitle = (string)$tr['name'];
				elseif (!empty($tr['title'])) $cardTitle = (string)$tr['title'];
				elseif (!empty($tr['description'])) $cardTitle = trim(strip_tags((string)$tr['description']));
				if ($cardTitle !== '') $g['name'] = $cardTitle;

				if (!empty($tr['description'])) $g['name_2'] = (string)$tr['description'];
				elseif (!empty($tr['title'])) $g['name_2'] = (string)$tr['title'];

				if (!empty($g['img'])) {
					$p = ROOT_DIR . 'files/guides/' . (int)$g['id'] . '/img/' . $g['img'];
					$g['img_v'] = file_exists($p) ? filemtime($p) : '';
				}
				$abc['guide_list'][] = $g;
			}
		} else {
			// EN or no i18n: show all canonical.
			foreach ($canon_list as &$g) {
				if (!empty($g['img'])) {
					$p = ROOT_DIR . 'files/guides/' . (int)$g['id'] . '/img/' . $g['img'];
					$g['img_v'] = file_exists($p) ? filemtime($p) : '';
				}
			}
			unset($g);
			$abc['guide_list'] = $canon_list ?: array();
		}
	}
}
