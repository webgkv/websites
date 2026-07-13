<?php
/**
 * Layout helpers: hreflang, language switcher, per-lang link maps.
 */

	function site_template_links_for_lang($abc, $lang_url_raw) {
		$links = isset($abc['links']) && is_array($abc['links']) ? $abc['links'] : array();
		$candidates = array((string)$lang_url_raw, trim((string)$lang_url_raw, '/'));
		$seen = array();
		foreach ($candidates as $k) {
			if ($k === '' || isset($seen[$k])) {
				continue;
			}
			$seen[$k] = true;
			if (isset($links[$k]) && is_array($links[$k]) && count($links[$k]) > 0) {
				return $links[$k];
			}
		}
		return null;
	}

	function site_hreflang_code($abc, $lang_url_segment) {
		$u = strtolower(trim((string)$lang_url_segment, '/'));
		if (!isset($abc['languages']) || !is_array($abc['languages'])) {
			return ($u === 'ua') ? 'uk' : $u;
		}
		foreach ($abc['languages'] as $row) {
			if (!is_array($row)) {
				continue;
			}
			$row_u = strtolower(trim((string)($row['url'] ?? ''), '/'));
			if ($row_u !== $u) {
				continue;
			}
			$loc = isset($row['localization']) ? trim((string)$row['localization']) : '';
			$loc = str_replace('_', '-', strtolower($loc));
			if ($loc !== '' && preg_match('/^[a-z]{2,3}(-[a-z0-9]+)*$/i', $loc)) {
				// BCP47: Ukrainian is `uk`; DB sometimes stores `ua` (country / legacy).
				return ($loc === 'ua') ? 'uk' : $loc;
			}
			break;
		}
		if ($u === 'ua') {
			return 'uk';
		}
		return $u;
	}

	function site_lang_flag_country_code($url_seg) {
		$u = strtolower(trim((string)$url_seg, '/'));
		$map = array(
			'en' => 'gb',
			'fr' => 'fr',
			'de' => 'de',
			'es' => 'es',
			'it' => 'it',
			'pl' => 'pl',
			'pt' => 'br',
			'hi' => 'in',
			'bn' => 'bd',
			'ar' => 'sa',
			'vi' => 'vn',
			'az' => 'az',
			'ru' => 'ru',
			'nl' => 'nl',
			'ua' => 'ua',
			'tr' => 'tr',
			'ja' => 'jp',
			'zh' => 'cn',
			'ko' => 'kr',
			'cs' => 'cz',
			'sv' => 'se',
			'da' => 'dk',
			'fi' => 'fi',
			'no' => 'no',
			'nb' => 'no',
			'nn' => 'no',
			'ro' => 'ro',
			'el' => 'gr',
			'he' => 'il',
			'th' => 'th',
			'id' => 'id',
			'ms' => 'my',
			'tl' => 'ph',
			'fa' => 'ir',
			'ur' => 'pk',
			'sk' => 'sk',
			'hu' => 'hu',
			'bg' => 'bg',
			'hr' => 'hr',
			'sr' => 'rs',
			'sl' => 'si',
			'et' => 'ee',
			'lv' => 'lv',
			'lt' => 'lt',
			'sq' => 'al',
			'bs' => 'ba',
			'mk' => 'mk',
			'is' => 'is',
			'ga' => 'ie',
			'cy' => 'gb',
			'eu' => 'es',
			'ca' => 'es',
			'gl' => 'es',
			'lb' => 'lu',
			'mt' => 'mt',
			'hy' => 'am',
			'ka' => 'ge',
			'kk' => 'kz',
			'uz' => 'uz',
			'tg' => 'tj',
			'ky' => 'kg',
			'mn' => 'mn',
			'ne' => 'np',
			'si' => 'lk',
			'my' => 'mm',
			'km' => 'kh',
			'lo' => 'la',
			'fil' => 'ph',
		);
		if (isset($map[$u])) {
			return $map[$u];
		}
		if (preg_match('/^[a-z]{2}$/', $u)) {
			return $u;
		}
		return null;
	}

	function site_lang_switcher_label($url_seg, $db_name = '') {
		$d = trim((string)$db_name);
		if ($d !== '') {
			return $d;
		}
		$u = strtolower(trim((string)$url_seg, '/'));
		$labels = array(
			'en' => 'English',
			'fr' => 'Français',
			'de' => 'Deutsch',
			'es' => 'Español',
			'it' => 'Italiano',
			'pl' => 'Polski',
			'pt' => 'Português',
			'hi' => 'हिन्दी',
			'bn' => 'বাংলা',
			'ar' => 'العربية',
			'vi' => 'Tiếng Việt',
			'az' => 'Azərbaycan',
			'ru' => 'Русский',
			'nl' => 'Nederlands',
			'ua' => 'Українська',
			'tr' => 'Türkçe',
			'ja' => '日本語',
			'zh' => '中文',
			'ko' => '한국어',
			'cs' => 'Čeština',
			'sv' => 'Svenska',
			'da' => 'Dansk',
			'fi' => 'Suomi',
			'no' => 'Norsk',
			'nb' => 'Norsk',
			'nn' => 'Norsk',
			'ro' => 'Română',
			'el' => 'Ελληνικά',
			'he' => 'עברית',
			'th' => 'ไทย',
			'id' => 'Bahasa Indonesia',
			'ms' => 'Bahasa Melayu',
			'fa' => 'فارسی',
			'ur' => 'اردو',
			'sk' => 'Slovenčina',
			'hu' => 'Magyar',
			'bg' => 'Български',
			'hr' => 'Hrvatski',
			'sr' => 'Српски',
			'sl' => 'Slovenščina',
			'et' => 'Eesti',
			'lv' => 'Latviešu',
			'lt' => 'Lietuvių',
		);
		if (isset($labels[$u])) {
			return $labels[$u];
		}
		return strtoupper($u);
	}
