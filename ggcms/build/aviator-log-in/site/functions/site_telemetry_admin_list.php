<?php
/**
 * On-demand admin list diagnostics (sort SQL vs DB rows). Same auth as telemetry_snapshot.
 *
 * GET /api/telemetry_admin_list?token=...&section=content_casinos
 * Optional: o, s, n, c, search, search_id, category (guides/games), sample_limit (max 50)
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

/**
 * @param array<string,mixed> $params section, optional GET passthrough
 * @return array<string,mixed>
 */
function site_telemetry_admin_list_collect(array $params) {
	$out = array(
		'ok' => false,
		'error' => '',
		'section' => isset($params['section']) ? (string) $params['section'] : '',
	);

	if (!function_exists('mysql_select') || !function_exists('admin_table_list_sort_meta')) {
		require_once ROOT_DIR . 'functions/mysql_func.php';
		require_once ROOT_DIR . 'functions/admin_func.php';
	}

	$section = preg_replace('/[^a-z0-9_]/', '', $out['section']);
	$profiles = array(
		'content_casinos' => array(
			'file' => ROOT_DIR . 'admin/modules/casino_articles.php',
			'get_base' => array(
				'm' => 'content',
				'tab' => 'casinos',
				'stab' => '',
				'u' => '',
				'id' => '',
			),
		),
		'content_blog' => array(
			'file' => ROOT_DIR . 'admin/modules/blog.php',
			'get_base' => array(
				'm' => 'content',
				'tab' => 'blog',
				'stab' => 'blog',
				'u' => '',
				'id' => '',
			),
		),
		'content_guides' => array(
			'file' => ROOT_DIR . 'admin/modules/guides.php',
			'get_base' => array(
				'm' => 'content',
				'tab' => 'guides',
				'stab' => '',
				'u' => '',
				'id' => '',
			),
		),
		'content_games' => array(
			'file' => ROOT_DIR . 'admin/modules/games.php',
			'get_base' => array(
				'm' => 'content',
				'tab' => 'games',
				'stab' => '',
				'u' => '',
				'id' => '',
			),
		),
	);

	if (!isset($profiles[$section])) {
		$out['error'] = 'unknown_section';
		$out['allowed_sections'] = array_keys($profiles);
		return $out;
	}

	$pass = array('o', 's', 'n', 'c', 'search', 'search_id', 'category');
	$inject = array();
	foreach ($pass as $p) {
		if (!array_key_exists($p, $params)) {
			continue;
		}
		$v = $params[$p];
		if (is_array($v)) {
			continue;
		}
		$inject[$p] = is_numeric($v) ? (0 + $v) : (string) $v;
	}

	$sample_limit = isset($params['sample_limit']) ? (int) $params['sample_limit'] : 20;
	$per_c = array(20, 50, 100);
	if (isset($get['c']) && in_array((int) $get['c'], $per_c, true)) {
		$sample_limit = (int) $get['c'];
	}
	$sample_limit = max(5, min(50, $sample_limit));

	$saved_get = isset($_GET) && is_array($_GET) ? $_GET : array();
	$saved_request = isset($_REQUEST) && is_array($_REQUEST) ? $_REQUEST : array();

	$get = array_merge($profiles[$section]['get_base'], $inject);
	$_GET = $get;
	$_REQUEST = array_merge($_REQUEST, $get);

	$table = array();
	$query = '';
	$filter = array();

	require $profiles[$section]['file'];

	if (empty($table) || !isset($table['id'])) {
		$out['error'] = 'no_table_config';
		$_GET = $saved_get;
		$_REQUEST = $saved_request;
		return $out;
	}

	$sort_meta = admin_table_list_sort_meta($table, $_GET);
	$sql_body = trim(preg_replace('/\s+/', ' ', (string) $query));
	$sql_full = $sql_body . $sort_meta['order_by_fragment'];

	$count_sql = preg_replace('/\s+ORDER BY.*$/is', '', $sql_full);
	$count_sql = preg_replace('/SELECT\s+\*\s+FROM/is', 'SELECT COUNT(*) FROM', $count_sql);
	$total = (int) mysql_select($count_sql, 'string');

	$n = isset($get['n']) ? max(1, (int) $get['n']) : 1;
	$offset = ($n - 1) * $sample_limit;

	$rows_first = mysql_select($sql_full . ' LIMIT ' . (int) $sample_limit . ' OFFSET ' . (int) $offset, 'rows');
	if ($rows_first === false) {
		$rows_first = array();
	}

	$ids_first = array();
	$name_first = array();
	foreach ($rows_first as $row) {
		$ids_first[] = isset($row['id']) ? (int) $row['id'] : 0;
		$name_first[] = isset($row['name']) ? substr((string) $row['name'], 0, 60) : '';
	}

	$last_start = $total > 0 ? (max(0, (int) ceil($total / $sample_limit) - 1) * $sample_limit) : 0;
	$rows_last = array();
	if ($total > $sample_limit) {
		$rows_last = mysql_select($sql_full . ' LIMIT ' . (int) $sample_limit . ' OFFSET ' . (int) $last_start, 'rows');
		if ($rows_last === false) {
			$rows_last = array();
		}
	}
	$ids_last = array();
	foreach ($rows_last as $row) {
		$ids_last[] = isset($row['id']) ? (int) $row['id'] : 0;
	}

	$db_table = '';
	if (preg_match('/\bFROM\s+`?([a-z0-9_]+)`?\b/i', $sql_body, $mm)) {
		$db_table = $mm[1];
	}
	$mm_row = $db_table !== '' ? mysql_select('SELECT MIN(id) AS mn, MAX(id) AS mx FROM `' . mysql_res($db_table) . '`', 'row') : null;

	$af = ROOT_DIR . 'functions/admin_func.php';
	$admin_func_sha12 = is_readable($af) ? substr((string) @hash_file('sha256', $af), 0, 12) : null;

	$_GET = $saved_get;
	$_REQUEST = $saved_request;

	$out['ok'] = true;
	$out['error'] = '';
	$out['request_merged'] = $get;
	$out['table_id_sort_spec'] = $table['id'];
	$out['sort_resolution'] = $sort_meta;
	$out['sql_list_normalized'] = $sql_full;
	$out['total_rows'] = $total;
	$out['sample_limit'] = $sample_limit;
	$out['page_n'] = $n;
	$out['offset'] = $offset;
	$out['first_page_ids'] = $ids_first;
	$out['first_page_names_trimmed'] = $name_first;
	$out['last_page_offset'] = $last_start;
	$out['last_page_ids'] = $ids_last;
	$out['db_table'] = $db_table;
	$out['db_id_min_max'] = $mm_row ? array(
		'min_id' => isset($mm_row['mn']) ? (int) $mm_row['mn'] : null,
		'max_id' => isset($mm_row['mx']) ? (int) $mm_row['mx'] : null,
	) : null;
	$out['admin_func_sha256_12'] = $admin_func_sha12;

	return $out;
}
