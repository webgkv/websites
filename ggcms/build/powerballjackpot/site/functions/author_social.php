<?php
/**
 * Author social profiles (sameAs) + optional reference links for profile page.
 *
 * Fixed networks align with Schema.org Person sameAs best practice:
 * official profiles the author controls (LinkedIn, X, etc.) + website + Wikipedia/ORCID/Scholar when applicable.
 * reference_links are editorial citations (interviews, publications) — shown on profile, not sameAs.
 */

/**
 * @return array<int,array{key:string,label:string,placeholder:string,same_as:bool}>
 */
function author_social_network_defs() {
	return array(
		array('key' => 'website', 'label' => 'Personal website', 'placeholder' => 'https://example.com', 'same_as' => true),
		array('key' => 'linkedin', 'label' => 'LinkedIn', 'placeholder' => 'https://www.linkedin.com/in/username', 'same_as' => true),
		array('key' => 'x', 'label' => 'X (Twitter)', 'placeholder' => 'https://x.com/username', 'same_as' => true),
		array('key' => 'facebook', 'label' => 'Facebook', 'placeholder' => 'https://www.facebook.com/username', 'same_as' => true),
		array('key' => 'instagram', 'label' => 'Instagram', 'placeholder' => 'https://www.instagram.com/username', 'same_as' => true),
		array('key' => 'youtube', 'label' => 'YouTube', 'placeholder' => 'https://www.youtube.com/@channel', 'same_as' => true),
		array('key' => 'tiktok', 'label' => 'TikTok', 'placeholder' => 'https://www.tiktok.com/@username', 'same_as' => true),
		array('key' => 'threads', 'label' => 'Threads', 'placeholder' => 'https://www.threads.net/@username', 'same_as' => true),
		array('key' => 'wikipedia', 'label' => 'Wikipedia', 'placeholder' => 'https://en.wikipedia.org/wiki/Name', 'same_as' => true),
		array('key' => 'google_scholar', 'label' => 'Google Scholar', 'placeholder' => 'https://scholar.google.com/citations?user=…', 'same_as' => true),
		array('key' => 'orcid', 'label' => 'ORCID', 'placeholder' => 'https://orcid.org/0000-0000-0000-0000', 'same_as' => true),
	);
}

/**
 * @return array<string,string>
 */
function author_social_profiles_decode($raw) {
	if (is_array($raw)) {
		$dec = $raw;
	} else {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return array();
		}
		$dec = json_decode($raw, true);
		if (!is_array($dec)) {
			return array();
		}
	}
	$out = array();
	foreach ($dec as $k => $v) {
		$url = author_social_normalize_url((string)$v);
		if ($url !== '') {
			$out[(string)$k] = $url;
		}
	}
	return $out;
}

/**
 * @return array<int,array{label:string,url:string}>
 */
function author_reference_links_decode($raw) {
	if (is_array($raw)) {
		$dec = $raw;
	} else {
		$raw = trim((string)$raw);
		if ($raw === '') {
			return array();
		}
		$dec = json_decode($raw, true);
		if (!is_array($dec)) {
			return array();
		}
	}
	$out = array();
	foreach ($dec as $row) {
		if (!is_array($row)) {
			continue;
		}
		$label = trim((string)($row['label'] ?? ''));
		$url = author_social_normalize_url((string)($row['url'] ?? ''));
		if ($url === '') {
			continue;
		}
		$out[] = array('label' => $label !== '' ? $label : $url, 'url' => $url);
	}
	return $out;
}

function author_social_normalize_url($url) {
	$url = trim((string)$url);
	if ($url === '') {
		return '';
	}
	if (!preg_match('#^https?://#i', $url)) {
		$url = 'https://' . ltrim($url, '/');
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return '';
	}
	return $url;
}

/**
 * Merge social_profiles JSON, legacy social_links, and reference_links from author row.
 *
 * @param array<string,mixed> $author
 * @return array{profiles:array<string,string>,references:array<int,array{label:string,url:string}>,same_as:string[]}
 */
function author_social_bundle(array $author) {
	$profiles = array();
	if (!empty($author['social_profiles'])) {
		$profiles = author_social_profiles_decode($author['social_profiles']);
	}
	if (empty($profiles) && !empty($author['social_links'])) {
		$legacy = author_parse_social_links_legacy($author['social_links']);
		foreach ($legacy as $k => $url) {
			$profiles[$k] = $url;
		}
	}
	$references = array();
	if (!empty($author['reference_links'])) {
		$references = author_reference_links_decode($author['reference_links']);
	}
	$same_as_keys = array();
	foreach (author_social_network_defs() as $def) {
		if (!empty($def['same_as'])) {
			$same_as_keys[$def['key']] = true;
		}
	}
	$same_as = array();
	foreach ($profiles as $k => $url) {
		if (isset($same_as_keys[$k]) || $k === 'twitter') {
			$same_as[] = $url;
		}
	}
	$same_as = array_values(array_unique($same_as));
	return array(
		'profiles' => $profiles,
		'references' => $references,
		'same_as' => $same_as,
	);
}

/**
 * Legacy parser (moved from author_profiles for migration).
 *
 * @return array<string,string>
 */
function author_parse_social_links_legacy($raw) {
	$raw = trim((string)$raw);
	if ($raw === '') {
		return array();
	}
	$dec = json_decode($raw, true);
	if (is_array($dec)) {
		$out = array();
		foreach ($dec as $k => $v) {
			$url = author_social_normalize_url((string)$v);
			if ($url !== '') {
				$out[(string)$k] = $url;
			}
		}
		return $out;
	}
	$out = array();
	foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		if (preg_match('#^([a-z0-9_-]+)\s*:\s*(https?://.+)$#i', $line, $m)) {
			$key = strtolower($m[1]);
			if ($key === 'twitter') {
				$key = 'x';
			}
			$out[$key] = author_social_normalize_url(trim($m[2]));
		} elseif (preg_match('#^https?://#i', $line)) {
			$host = parse_url($line, PHP_URL_HOST);
			$key = $host ? preg_replace('/^www\./', '', (string)$host) : 'website';
			if (strpos($key, 'linkedin') !== false) {
				$key = 'linkedin';
			} elseif (strpos($key, 'facebook') !== false) {
				$key = 'facebook';
			} elseif ($key === 'twitter.com' || $key === 'x.com') {
				$key = 'x';
			} elseif (strpos($key, 'instagram') !== false) {
				$key = 'instagram';
			} elseif (strpos($key, 'youtube') !== false) {
				$key = 'youtube';
			} elseif (strpos($key, 'tiktok') !== false) {
				$key = 'tiktok';
			} else {
				$key = 'website';
			}
			$out[$key] = author_social_normalize_url($line);
		}
	}
	return $out;
}

/**
 * @return array{social_profiles:string,reference_links:string,social_links:string}
 */
function author_social_pack_from_post(array $post) {
	$profiles = array();
	$posted = isset($post['author_social']) && is_array($post['author_social']) ? $post['author_social'] : array();
	foreach (author_social_network_defs() as $def) {
		$key = $def['key'];
		$url = author_social_normalize_url((string)($posted[$key] ?? ''));
		if ($url !== '') {
			$profiles[$key] = $url;
		}
	}
	$references = array();
	$labels = isset($post['author_ref_label']) && is_array($post['author_ref_label']) ? $post['author_ref_label'] : array();
	$urls = isset($post['author_ref_url']) && is_array($post['author_ref_url']) ? $post['author_ref_url'] : array();
	$n = max(count($labels), count($urls));
	for ($i = 0; $i < $n; $i++) {
		$url = author_social_normalize_url((string)($urls[$i] ?? ''));
		if ($url === '') {
			continue;
		}
		$label = trim((string)($labels[$i] ?? ''));
		$references[] = array('label' => $label !== '' ? $label : $url, 'url' => $url);
	}
	return array(
		'social_profiles' => json_encode($profiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'reference_links' => json_encode($references, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		'social_links' => json_encode($profiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
	);
}

/**
 * Admin form block (tab Social & links).
 *
 * @param array<string,mixed> $row
 * @return string
 */
function admin_author_links_form_block(array $row) {
	$bundle = author_social_bundle($row);
	$profiles = $bundle['profiles'];
	$references = $bundle['references'];
	$h = '<div class="col-12"><div class="card shadow-sm border-light mb-4">';
	$h .= '<div class="card-header bg-light py-3"><h5 class="m-0 font-weight-bold text-primary">Social profiles (sameAs)</h5>';
	$h .= '<small class="text-muted">Official profiles for Schema.org Person. Not translated.</small></div>';
	$h .= '<div class="card-body"><div class="row g-3">';
	foreach (author_social_network_defs() as $def) {
		$key = $def['key'];
		$val = isset($profiles[$key]) ? (string)$profiles[$key] : '';
		$h .= '<div class="col-md-6">';
		$h .= '<label class="form-label font-weight-bold text-secondary">' . htmlspecialchars($def['label']) . '</label>';
		$h .= '<input type="url" class="form-control" name="author_social[' . htmlspecialchars($key, ENT_QUOTES) . ']" value="' . htmlspecialchars($val, ENT_QUOTES) . '" placeholder="' . htmlspecialchars($def['placeholder'], ENT_QUOTES) . '">';
		$h .= '</div>';
	}
	$h .= '</div></div></div>';

	$h .= '<div class="card shadow-sm border-light mb-4">';
	$h .= '<div class="card-header bg-light py-3"><h5 class="m-0 font-weight-bold text-primary">Reference links</h5>';
	$h .= '<small class="text-muted">Interviews, publications, talks — shown on author profile. Not added to sameAs.</small></div>';
	$h .= '<div class="card-body"><div id="author-ref-rows">';
	if (empty($references)) {
		$references = array(array('label' => '', 'url' => ''));
	}
	foreach ($references as $i => $ref) {
		$h .= admin_author_reference_row_html($i, (string)($ref['label'] ?? ''), (string)($ref['url'] ?? ''));
	}
	$h .= '</div>';
	$h .= '<button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="author-ref-add">+ Add reference link</button>';
	$h .= '</div></div></div>';

	$h .= '<template id="author-ref-row-tpl">' . admin_author_reference_row_html('__IDX__', '', '') . '</template>';
	$h .= '<script>(function(){var wrap=document.getElementById("author-ref-rows");var btn=document.getElementById("author-ref-add");var tpl=document.getElementById("author-ref-row-tpl");if(!wrap||!btn||!tpl)return;btn.addEventListener("click",function(){var i=wrap.querySelectorAll(".author-ref-row").length;var html=tpl.innerHTML.replace(/__IDX__/g,String(i));var div=document.createElement("div");div.innerHTML=html;while(div.firstChild)wrap.appendChild(div.firstChild);});wrap.addEventListener("click",function(e){var t=e.target;if(t&&t.classList.contains("author-ref-remove")){var row=t.closest(".author-ref-row");if(row)row.remove();}});})();</script>';

	return $h;
}

function admin_author_reference_row_html($idx, $label, $url) {
	return '<div class="row g-2 mb-2 author-ref-row">'
		. '<div class="col-md-5"><input type="text" class="form-control" name="author_ref_label[' . htmlspecialchars((string)$idx, ENT_QUOTES) . ']" value="' . htmlspecialchars($label, ENT_QUOTES) . '" placeholder="Label (e.g. Forbes interview)"></div>'
		. '<div class="col-md-6"><input type="url" class="form-control" name="author_ref_url[' . htmlspecialchars((string)$idx, ENT_QUOTES) . ']" value="' . htmlspecialchars($url, ENT_QUOTES) . '" placeholder="https://…"></div>'
		. '<div class="col-md-1 d-flex align-items-center"><button type="button" class="btn btn-sm btn-outline-danger author-ref-remove" title="Remove">&times;</button></div>'
		. '</div>';
}
