<?php
/**
 * Build static language-split sitemaps (body from legacy cron_sitemap_build.php).
 */
if (!defined('SM_URLS_PER_FILE')) {
	define('SM_URLS_PER_FILE', 10000);
}
@set_time_limit(0);
if (function_exists('ini_set')) {
	@ini_set('memory_limit', '512M');
}
require_once ROOT_DIR . 'functions/lang_func.php';
require_once ROOT_DIR . 'functions/site_seo.php';
if (php_sapi_name() !== 'cli') {
	header('Content-Type: text/plain; charset=utf-8');
}
$base = rtrim((string)($config['http_domain'] ?? ''), '/');
// When running from CLI, $_SERVER['HTTP_HOST'] may be empty, producing base like "https:" (invalid).
// Prefer configured canonical_base (SEO → Structured) as an authoritative host for sitemaps.
if ($base === '' || preg_match('#^https?:$#i', $base) || preg_match('#^https?://$#i', $base)) {
	$canonical_base = '';
	if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
		$row_seo = mysql_select("SELECT value FROM `variables` WHERE `key`='seo_structured' LIMIT 1", 'row');
		if ($row_seo && isset($row_seo['value']) && (string)$row_seo['value'] !== '') {
			$dec = @json_decode((string)$row_seo['value'], true);
			if (is_array($dec) && !empty($dec['canonical_base'])) {
				$canonical_base = trim((string)$dec['canonical_base']);
			}
		}
	}
	if ($canonical_base !== '') {
		$base = rtrim($canonical_base, '/');
	} elseif (!empty($config['domain_main'])) {
		$base = ((int)($config['https'] ?? 0) === 1 ? 'https' : 'http') . '://' . trim((string)$config['domain_main']);
	} else {
		// Last resort: keep previous $base; sitemap will be invalid if host is missing.
	}
}
$apiDir = ROOT_DIR . 'api/sitemap/';
if (!is_dir($apiDir)) {
	mkdir($apiDir, 0775, true);
}

// Section toggles (SEO → Sitemap)
$include = array(
	'pages'   => 1,
	'blog'    => 1,
	'guides'  => 1,
	'games'   => 1,
	'casinos' => 1,
	'authors' => 1,
);
if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') > 0) {
	$row_inc = mysql_select("SELECT value FROM `variables` WHERE `key` = 'sitemap_include' LIMIT 1", 'row');
	if ($row_inc && $row_inc['value'] !== '') {
		$dec = json_decode($row_inc['value'], true);
		if (is_array($dec)) {
			foreach (array_keys($include) as $k) {
				if (isset($dec[$k])) {
					$include[$k] = (int)(bool)$dec[$k];
				}
			}
		}
	}
}
// SEO → Index rules: drop blocked sections from sitemap.
if (function_exists('site_seo_sitemap_apply_index_rules_to_include')) {
	$include = site_seo_sitemap_apply_index_rules_to_include($include);
}

$languages = function_exists('site_seo_sitemap_language_rows')
	? site_seo_sitemap_language_rows()
	: (mysql_select("SELECT id, url FROM languages WHERE display=1 ORDER BY rank DESC", 'rows') ?: array(array('id' => 0, 'url' => '')));

$default_lang = function_exists('lang') ? lang() : null;
$default_lang_id = $default_lang ? (int)$default_lang['id'] : null;
if ($default_lang_id === null && !empty($languages)) {
	$default_lang_id = (int)$languages[0]['id'];
}

/** Slug for filenames: sitemap_{slug}_001.xml */
function sm_file_lang_slug($lang) {
	$u = trim((string)$lang['url']);
	$u = strtolower(preg_replace('/[^a-z0-9]+/', '', $u));
	if ($u === '') {
		$u = 'lang' . (int)$lang['id'];
	}
	return $u;
}

function sm_slug($row, $langId, $default_lang_id) {
	if (!is_array($row)) {
		return '';
	}
	$suffix = ($default_lang_id !== null && $langId === $default_lang_id) ? '' : $langId;
	$field = 'url' . $suffix;
	if (isset($row[$field]) && trim((string)$row[$field]) !== '') {
		return trim((string)$row[$field], '/');
	}
	foreach (array('url', 'url1', 'url2', 'url3') as $f) {
		if (isset($row[$f]) && trim((string)$row[$f]) !== '') {
			return trim((string)$row[$f], '/');
		}
	}
	return '';
}

function sm_blog_article_slug($row, $langId) {
	if (!is_array($row) || empty($row['id'])) {
		return '';
	}
	$id = (int)$row['id'];
	$try = array('url' . $langId, 'url', 'url1', 'url2', 'url3');
	foreach ($try as $f) {
		if (isset($row[$f]) && trim((string)$row[$f]) !== '') {
			return trim((string)$row[$f], '/');
		}
	}
	return 'post-' . $id;
}

function sm_norm_text($v) {
	$s = (string)$v;
	$s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
	$s = preg_replace('/\s+/u', ' ', $s);
	return trim($s);
}

function sm_add_url_xml(SimpleXMLElement $xml, $loc, $lastmod = null) {
	$u = $xml->addChild('url');
	$u->addChild('loc', htmlspecialchars($loc, ENT_QUOTES, 'UTF-8'));
	if ($lastmod && $lastmod !== '0000-00-00' && $lastmod !== '0000-00-00 00:00:00') {
		$u->addChild('lastmod', date('Y-m-d', strtotime($lastmod)));
	}
}

/**
 * @param array $entries list of array('loc' => string, 'lastmod' => string|null)
 */
function sm_write_urlset_file($apiDir, $entries, $basename) {
	$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" />');
	foreach ($entries as $e) {
		sm_add_url_xml($xml, $e['loc'], $e['lastmod']);
	}
	$tmp = $apiDir . '.' . $basename . '.tmp';
	$final = $apiDir . $basename;
	file_put_contents($tmp, $xml->asXML());
	rename($tmp, $final);
}

/** WHERE fragment: pages row matches slug in any existing url/urlN column. */
function sm_pages_slug_where($slug) {
	static $url_cols = null;
	if ($url_cols === null) {
		$url_cols = array('url');
		$col_rows = mysql_select("SHOW COLUMNS FROM pages LIKE 'url%'", 'rows') ?: array();
		foreach ($col_rows as $c) {
			$f = isset($c['Field']) ? (string) $c['Field'] : '';
			if ($f !== '' && $f !== 'url' && preg_match('/^url\d+$/', $f)) {
				$url_cols[] = $f;
			}
		}
	}
	$esc = mysql_res(trim((string) $slug, '/'));
	$parts = array();
	foreach ($url_cols as $f) {
		$parts[] = '`' . str_replace('`', '``', $f) . "`='" . $esc . "'";
	}
	return '(' . implode(' OR ', $parts) . ')';
}

$blog_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='blog' LIMIT 1", 'row');
$authors_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='authors' LIMIT 1", 'row');
$guides_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND " . sm_pages_slug_where('guides') . " LIMIT 1", 'row');
$games_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND " . sm_pages_slug_where('games') . " LIMIT 1", 'row');
$casinos_page = mysql_select("SELECT * FROM pages WHERE display=1 AND module='pages' AND " . sm_pages_slug_where('casinos') . " LIMIT 1", 'row');

$pages = null;
if (!empty($include['pages'])) {
	$pages = mysql_select("SELECT * FROM pages WHERE display=1 ORDER BY left_key", 'rows');
}

$writtenFiles = array();
$report = array(); // per-lang summary for CLI/HTML
$blog_total_in_db = null;
$has_content_i18n = @mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0;
$blog_date_cap = date('Y-m-d H:i:s');

foreach ($languages as $lang) {
	$langId = (int)$lang['id'];
	$langSlug = trim((string)$lang['url']);
	$fileLangSlug = sm_file_lang_slug($lang);
	$entries = array();
	$cnt_pages = $cnt_blog = $cnt_guides = $cnt_games = $cnt_casinos = $cnt_authors = 0;
	$blog_rows_with_slug = 0;
	$blog_rows_empty_slug = 0;
	$blog_rows_same_as_source = 0;

	// 1) Pages
	if (!empty($include['pages']) && $pages) {
		foreach ($pages as $p) {
			if (function_exists('site_seo_sitemap_entity_allowed') && !site_seo_sitemap_entity_allowed('blog') && isset($p['module']) && (string)$p['module'] === 'blog') {
				continue;
			}
			$slug = '';
			if (@mysql_select("SHOW TABLES LIKE 'content_i18n'", 'num_rows') > 0 && !empty($p['id']) && $langId > 0) {
				$t = mysql_select("
					SELECT url
					FROM content_i18n
					WHERE entity='pages'
					  AND entity_id=" . (int)$p['id'] . "
					  AND lang_id=" . (int)$langId . "
					  AND status='published'
					LIMIT 1
				", 'row');
				if ($t && trim((string)$t['url']) !== '') {
					$slug = trim((string)$t['url'], '/');
				}
			}
			if ($slug === '') {
				$slug = sm_slug($p, $langId, $default_lang_id);
			}
			$path = '';
			if ($langSlug !== '') {
				$path .= '/' . $langSlug;
			}
			$path .= $slug !== '' ? '/' . $slug . '/' : '/';
			$entries[] = array(
				'loc' => $base . $path,
				'lastmod' => isset($p['updated_at']) ? $p['updated_at'] : null,
			);
			$cnt_pages++;
		}
	}

	// 2) Blog
	if (!empty($include['blog']) && @mysql_select("SHOW TABLES LIKE 'blog'", 'num_rows') > 0) {
		$blog_total_in_db = (int)@mysql_select("SELECT COUNT(*) FROM blog WHERE display=1", 'string');
		$gcats = mysql_select("SELECT * FROM blog_category", 'rows_id');
		if (!$gcats) {
			$gcats = array();
		}
		$blog_slug = '';
		if ($blog_page) {
			$blog_slug = sm_slug($blog_page, $langId, $default_lang_id);
		}
		if ($blog_slug === '') {
			$blog_slug = 'blog';
		}
		$prefix = ($langSlug !== '' ? '/' . $langSlug : '') . '/' . $blog_slug . '/';
		foreach ($gcats as $cat) {
			$cat_url = sm_slug($cat, $langId, $default_lang_id);
			if ($cat_url === '') {
				continue;
			}
			$entries[] = array(
				'loc' => $base . $prefix . $cat_url . '/',
				'lastmod' => isset($cat['updated_at']) ? $cat['updated_at'] : null,
			);
			$cnt_blog++;
		}
		// Non-default languages: only posts with published translation in content_i18n (same idea as public blog).
		$use_i18n_blog = $has_content_i18n && (int)$langId !== (int)$default_lang_id;

		$chunk_size = 2000;
		$offset = 0;
		do {
			if ($use_i18n_blog) {
				$blog_rows = mysql_select("
					SELECT b.*, ci.url AS _sitemap_ci_url, ci.updated_at AS _sitemap_ci_updated, ci.content AS _sitemap_ci_content
					FROM blog b
					INNER JOIN content_i18n ci
						ON ci.entity='blog' AND ci.entity_id=b.id AND ci.lang_id=" . (int)$langId . "
					WHERE b.display=1
					  AND b.date <= '" . mysql_res($blog_date_cap) . "'
					  AND ci.status='published'
					  AND TRIM(ci.url) <> ''
					  AND TRIM(ci.content) <> ''
					ORDER BY b.id ASC
					LIMIT " . (int)$chunk_size . " OFFSET " . (int)$offset, 'rows');
			} else {
				$blog_rows = mysql_select("
					SELECT * FROM blog
					WHERE display=1 AND date <= '" . mysql_res($blog_date_cap) . "'
					ORDER BY id ASC
					LIMIT " . (int)$chunk_size . " OFFSET " . (int)$offset, 'rows');
			}
			if (!is_array($blog_rows) || count($blog_rows) === 0) {
				break;
			}
			foreach ($blog_rows as $b) {
				if ($use_i18n_blog) {
					$art_url = isset($b['_sitemap_ci_url']) ? trim((string)$b['_sitemap_ci_url'], '/') : '';
				} else {
					$art_url = sm_blog_article_slug($b, $langId);
				}
				if ($art_url === '') {
					$blog_rows_empty_slug++;
					continue;
				}
				// For non-default languages only: skip "translations" that are identical to the source text.
				// Otherwise sitemaps end up with non-translated copies for every language.
				if ($use_i18n_blog) {
					$src_content = isset($b['text']) ? (string)$b['text'] : (isset($b['content']) ? (string)$b['content'] : '');
					$dst_content = isset($b['_sitemap_ci_content']) ? (string)$b['_sitemap_ci_content'] : '';
					if ($dst_content !== '' && $src_content !== '' && sm_norm_text($dst_content) === sm_norm_text($src_content)) {
						$blog_rows_same_as_source++;
						continue;
					}
				}
				$blog_rows_with_slug++;
				$path = $prefix . $art_url . '/';
				if ($use_i18n_blog && !empty($b['_sitemap_ci_updated']) && $b['_sitemap_ci_updated'] !== '0000-00-00 00:00:00') {
					$dt = $b['_sitemap_ci_updated'];
				} else {
					$dt = !empty($b['updated_at']) && $b['updated_at'] !== '0000-00-00 00:00:00' ? $b['updated_at'] : (isset($b['date']) ? $b['date'] : null);
				}
				$entries[] = array('loc' => $base . $path, 'lastmod' => $dt);
				$cnt_blog++;
			}
			$offset += $chunk_size;
		} while (count($blog_rows) === $chunk_size);
	}

	// 3) Guides
	if (!empty($include['guides']) && @mysql_select("SHOW TABLES LIKE 'guides'", 'num_rows') > 0) {
		$guide_categories = array('analysis', 'bonus', 'how-to-win', 'signals', 'crash-gambling');
		$guide_cats_from_db = @mysql_select("SELECT DISTINCT category FROM guides WHERE display=1 AND category IS NOT NULL AND category != ''", 'rows');
		if ($guide_cats_from_db) {
			foreach ($guide_cats_from_db as $r) {
				$c = trim((string)$r['category']);
				if ($c !== '' && !in_array($c, $guide_categories, true)) {
					$guide_categories[] = $c;
				}
			}
		}
		$guides_slug = $guides_page ? sm_slug($guides_page, $langId, $default_lang_id) : '';
		if ($guides_slug === '') {
			$guides_slug = 'guides';
		}
		$prefix = ($langSlug !== '' ? '/' . $langSlug : '') . '/' . $guides_slug . '/';
		$entries[] = array('loc' => $base . $prefix, 'lastmod' => null);
		$cnt_guides++;
		foreach ($guide_categories as $cat) {
			$entries[] = array('loc' => $base . $prefix . $cat . '/', 'lastmod' => null);
			$cnt_guides++;
			$rows = mysql_select("SELECT * FROM guides WHERE display=1 AND category = '" . mysql_res($cat) . "' ORDER BY position ASC, date DESC", 'rows');
			if ($rows) {
				foreach ($rows as $g) {
					$slug = sm_slug($g, $langId, $default_lang_id);
					if ($slug === '') {
						foreach (array('url', 'url1', 'url2', 'url3') as $uf) {
							if (isset($g[$uf]) && trim((string)$g[$uf]) !== '') {
								$slug = trim((string)$g[$uf], '/');
								break;
							}
						}
						if ($slug === '' && isset($g['id'])) {
							$slug = 'guide-' . (int)$g['id'];
						}
					}
					if ($slug === '') {
						continue;
					}
					$dt = !empty($g['updated_at']) && $g['updated_at'] !== '0000-00-00 00:00:00' ? $g['updated_at'] : (isset($g['date']) ? $g['date'] : null);
					$entries[] = array('loc' => $base . $prefix . $cat . '/' . $slug . '/', 'lastmod' => $dt);
					$cnt_guides++;
				}
			}
		}
	}

	// 4) Games
	if (!empty($include['games']) && @mysql_select("SHOW TABLES LIKE 'games'", 'num_rows') > 0) {
		$games_slug = $games_page ? sm_slug($games_page, $langId, $default_lang_id) : '';
		if ($games_slug === '') {
			$games_slug = 'games';
		}
		$prefix = ($langSlug !== '' ? '/' . $langSlug : '') . '/' . $games_slug . '/';
		$entries[] = array('loc' => $base . $prefix, 'lastmod' => null);
		$cnt_games++;
		$rows = mysql_select("SELECT * FROM games WHERE display=1 ORDER BY position ASC, id ASC", 'rows');
		if ($rows) {
			foreach ($rows as $g) {
				$slug = sm_slug($g, $langId, $default_lang_id);
				if ($slug === '') {
					foreach (array('url', 'url1', 'url2', 'url3') as $uf) {
						if (isset($g[$uf]) && trim((string)$g[$uf]) !== '') {
							$slug = trim((string)$g[$uf], '/');
							break;
						}
					}
					if ($slug === '' && isset($g['id'])) {
						$slug = 'game-' . (int)$g['id'];
					}
				}
				if ($slug === '') {
					continue;
				}
				$dt = !empty($g['updated_at']) && $g['updated_at'] !== '0000-00-00 00:00:00' ? $g['updated_at'] : null;
				$entries[] = array('loc' => $base . $prefix . $slug . '/', 'lastmod' => $dt);
				$cnt_games++;
			}
		}
	}

	// 5) Casinos
	if (!empty($include['casinos']) && @mysql_select("SHOW TABLES LIKE 'casino_articles'", 'num_rows') > 0) {
		$casinos_slug = $casinos_page ? sm_slug($casinos_page, $langId, $default_lang_id) : '';
		if ($casinos_slug === '') {
			$casinos_slug = 'casinos';
		}
		$prefix = ($langSlug !== '' ? '/' . $langSlug : '') . '/' . $casinos_slug . '/';
		$entries[] = array('loc' => $base . $prefix, 'lastmod' => null);
		$cnt_casinos++;
		$rows = mysql_select("SELECT * FROM casino_articles WHERE display=1 ORDER BY position DESC, date DESC, id DESC", 'rows');
		if ($rows) {
			foreach ($rows as $a) {
				$slug = sm_slug($a, $langId, $default_lang_id);
				if ($slug === '') {
					foreach (array('url', 'url1', 'url2', 'url3') as $uf) {
						if (isset($a[$uf]) && trim((string)$a[$uf]) !== '') {
							$slug = trim((string)$a[$uf], '/');
							break;
						}
					}
					if ($slug === '' && isset($a['id'])) {
						$slug = 'casino-' . (int)$a['id'];
					}
				}
				if ($slug === '') {
					continue;
				}
				$dt = !empty($a['updated_at']) && $a['updated_at'] !== '0000-00-00 00:00:00' ? $a['updated_at'] : (isset($a['date']) ? $a['date'] : null);
				$entries[] = array('loc' => $base . $prefix . $slug . '/', 'lastmod' => $dt);
				$cnt_casinos++;
			}
		}
	}

	// 6) Authors
	if (!empty($include['authors']) && @mysql_select("SHOW TABLES LIKE 'site_authors'", 'num_rows') > 0) {
		require_once ROOT_DIR . 'functions/author_profiles.php';
		$authors_slug = $authors_page ? sm_slug($authors_page, $langId, $default_lang_id) : '';
		if ($authors_slug === '') {
			$authors_slug = 'authors';
		}
		$prefix = ($langSlug !== '' ? '/' . $langSlug : '') . '/' . $authors_slug . '/';
		$entries[] = array('loc' => $base . $prefix, 'lastmod' => null);
		$cnt_authors++;
		$rows = mysql_select("SELECT * FROM site_authors WHERE display=1 ORDER BY name ASC", 'rows');
		if ($rows) {
			foreach ($rows as $a) {
				$loc_author = author_apply_locale($a, $langId);
				$slug = author_public_slug($loc_author, $langId);
				if ($slug === '') {
					continue;
				}
				$dt = !empty($a['updated_at']) && $a['updated_at'] !== '0000-00-00 00:00:00' ? $a['updated_at'] : null;
				$entries[] = array('loc' => $base . $prefix . $slug . '/', 'lastmod' => $dt);
				$cnt_authors++;
			}
		}
	}

	$partNo = 1;
	while (count($entries) >= SM_URLS_PER_FILE) {
		$chunk = array_splice($entries, 0, SM_URLS_PER_FILE);
		$name = sprintf('sitemap_%s_%03d.xml', $fileLangSlug, $partNo);
		sm_write_urlset_file($apiDir, $chunk, $name);
		$writtenFiles[] = $name;
		$partNo++;
	}
	if (count($entries) > 0) {
		$name = sprintf('sitemap_%s_%03d.xml', $fileLangSlug, $partNo);
		sm_write_urlset_file($apiDir, $entries, $name);
		$writtenFiles[] = $name;
	}

	$report[] = array(
		'lang' => $fileLangSlug,
		'urls' => $cnt_pages + $cnt_blog + $cnt_guides + $cnt_games + $cnt_casinos + $cnt_authors,
		'parts' => count(array_filter($writtenFiles, function ($f) use ($fileLangSlug) {
			return strpos($f, 'sitemap_' . $fileLangSlug . '_') === 0;
		})),
		'pages' => $cnt_pages,
		'blog' => $cnt_blog,
		'blog_same_as_source' => $blog_rows_same_as_source,
		'guides' => $cnt_guides,
		'games' => $cnt_games,
		'casinos' => $cnt_casinos,
		'authors' => $cnt_authors,
		'blog_db' => $blog_total_in_db,
		'blog_slug_ok' => $blog_rows_with_slug,
		'blog_slug_empty' => $blog_rows_empty_slug,
	);
}

// Remove stale part files (same naming convention only)
$writtenSet = array_flip($writtenFiles);
foreach (glob($apiDir . 'sitemap_*_*.xml') ?: array() as $abs) {
	$bn = basename($abs);
	if (!isset($writtenSet[$bn])) {
		@unlink($abs);
	}
}
@unlink($apiDir . 'sitemap_full.xml');

if (php_sapi_name() !== 'cli') {
	echo "Sitemap build (per-language, " . SM_URLS_PER_FILE . " URLs max per file)\n";
	echo "Written: " . implode(', ', $writtenFiles) . "\n\n";
	foreach ($report as $r) {
		echo $r['lang'] . ": urls=" . $r['urls'] . " parts=" . $r['parts']
			. " (pages=" . $r['pages'] . " blog=" . $r['blog'] . " guides=" . $r['guides']
			. " games=" . $r['games'] . " casinos=" . $r['casinos'] . ")\n";
	}
}
