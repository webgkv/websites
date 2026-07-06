<?php

/**
 * Render-time {year} placeholder (same idea as footer_copyright in i18n dictionaries).
 */

if (!function_exists('content_year_macro')) {
	/**
	 * @param mixed $text
	 * @return mixed
	 */
	function content_year_macro($text) {
		if (!is_string($text) || $text === '' || strpos($text, '{year}') === false) {
			return $text;
		}
		return str_replace('{year}', date('Y'), $text);
	}
}

if (!function_exists('content_year_macro_apply_row')) {
	/**
	 * Apply {year} to blog listing / article display fields (not body HTML).
	 *
	 * @param array<string,mixed> $row
	 * @param string $langid Suffix for localized columns (empty for canonical lang_id=1).
	 */
	function content_year_macro_apply_row(array &$row, $langid = '') {
		$langid = (string) $langid;
		$fields = array('name', 'title', 'description', 'name_2');
		foreach ($fields as $field) {
			$key = $field . $langid;
			if (isset($row[$key]) && is_string($row[$key])) {
				$row[$key] = content_year_macro($row[$key]);
			}
			if ($langid !== '' && isset($row[$field]) && is_string($row[$field])) {
				$row[$field] = content_year_macro($row[$field]);
			}
		}
	}
}

if (!function_exists('content_year_macro_apply_blog_internal')) {
	/**
	 * @param array<string,mixed> $internal blog_internal nav block
	 */
	function content_year_macro_apply_blog_internal(array &$internal) {
		foreach (array('prev', 'next') as $nav) {
			if (!empty($internal[$nav]['title']) && is_string($internal[$nav]['title'])) {
				$internal[$nav]['title'] = content_year_macro($internal[$nav]['title']);
			}
		}
		if (!empty($internal['related']) && is_array($internal['related'])) {
			foreach ($internal['related'] as $i => $rel) {
				if (!empty($rel['title']) && is_string($rel['title'])) {
					$internal['related'][$i]['title'] = content_year_macro($rel['title']);
				}
			}
		}
	}
}
