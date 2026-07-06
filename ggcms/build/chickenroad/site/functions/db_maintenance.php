<?php
/**
 * DB size analyzer and retention cleanup (chunked DELETE).
 *
 * Safe default targets: system_logs, admin_jobs, site_telemetry_events,
 * translation_orders (+ translation_order_candidates for removed orders).
 *
 * Does not touch content tables (pages, guides, games, etc.) except blog wipe:
 *   php run_db_maintenance.php clean --target=blog --wipe-all [--dry-run] [--optimize]
 */

if (!function_exists('db_maintenance_table_exists')) {
	function db_maintenance_table_exists($table) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		if ($table === '') {
			return false;
		}
		return @mysql_select("SHOW TABLES LIKE '" . mysql_res($table) . "'", 'num_rows') > 0;
	}
}

if (!function_exists('db_maintenance_column_exists')) {
	function db_maintenance_column_exists($table, $column) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		$column = preg_replace('/[^a-z0-9_]/i', '', (string)$column);
		if ($table === '' || $column === '') {
			return false;
		}
		return @mysql_select("SHOW COLUMNS FROM `" . $table . "` LIKE '" . mysql_res($column) . "'", 'num_rows') > 0;
	}
}

if (!function_exists('db_maintenance_format_bytes')) {
	function db_maintenance_format_bytes($bytes) {
		$bytes = (float)$bytes;
		if ($bytes < 1024) {
			return round($bytes) . ' B';
		}
		if ($bytes < 1024 * 1024) {
			return round($bytes / 1024, 1) . ' KB';
		}
		if ($bytes < 1024 * 1024 * 1024) {
			return round($bytes / (1024 * 1024), 2) . ' MB';
		}
		return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
	}
}

if (!function_exists('db_maintenance_default_options')) {
	function db_maintenance_default_options() {
		return array(
			'days' => array(
				'system_logs' => 30,
				'admin_jobs' => 30,
				'site_telemetry_events' => 14,
				'translation_orders' => 90,
				'translation_vector_items' => 180,
			),
			'chunk' => 500,
			'max_chunks' => 200,
			'pause_ms' => 100,
			'optimize' => false,
			'dry_run' => false,
			'wipe_all' => false,
		);
	}
}

if (!function_exists('db_maintenance_merge_options')) {
	function db_maintenance_merge_options(array $overrides = array()) {
		$opts = db_maintenance_default_options();
		foreach ($overrides as $k => $v) {
			if ($k === 'days' && is_array($v)) {
				$opts['days'] = array_merge($opts['days'], $v);
			} elseif (array_key_exists($k, $opts)) {
				$opts[$k] = $v;
			}
		}
		$opts['chunk'] = max(50, min(5000, (int)$opts['chunk']));
		$opts['max_chunks'] = max(1, min(5000, (int)$opts['max_chunks']));
		$opts['pause_ms'] = max(0, min(5000, (int)$opts['pause_ms']));
		return $opts;
	}
}

if (!function_exists('db_maintenance_safe_targets')) {
	/**
	 * @return array<string,array{label:string,group:string,risk:string}>
	 */
	function db_maintenance_safe_targets() {
		return array(
			'system_logs' => array(
				'label' => 'System logs',
				'group' => 'logs',
				'risk' => 'low',
			),
			'admin_jobs' => array(
				'label' => 'Admin background jobs (terminal)',
				'group' => 'logs',
				'risk' => 'low',
			),
			'site_telemetry_events' => array(
				'label' => 'Site telemetry events',
				'group' => 'logs',
				'risk' => 'low',
			),
			'translation_orders' => array(
				'label' => 'Translation monitor orders (completed/cancelled)',
				'group' => 'translations',
				'risk' => 'medium',
			),
		);
	}
}

if (!function_exists('db_maintenance_optional_targets')) {
	function db_maintenance_optional_targets() {
		return array(
			'translation_vector_items' => array(
				'label' => 'Translation vector memory (retention or --wipe-all)',
				'group' => 'translations',
				'risk' => 'high',
			),
			'blog' => array(
				'label' => 'Blog posts + all language versions (--wipe-all only)',
				'group' => 'content',
				'risk' => 'high',
			),
		);
	}
}

if (!function_exists('db_maintenance_wipe_all_targets')) {
	/**
	 * Targets that support --wipe-all (full table / entity purge, not retention).
	 *
	 * @return string[]
	 */
	function db_maintenance_wipe_all_targets() {
		return array('translation_vector_items', 'blog');
	}
}

if (!function_exists('db_maintenance_all_targets')) {
	function db_maintenance_all_targets($include_optional = false) {
		$all = db_maintenance_safe_targets();
		if ($include_optional) {
			$all = array_merge($all, db_maintenance_optional_targets());
		}
		return $all;
	}
}

if (!function_exists('db_maintenance_database_name')) {
	function db_maintenance_database_name() {
		global $config;
		return isset($config['mysql_database']) ? (string)$config['mysql_database'] : '';
	}
}

if (!function_exists('db_maintenance_analyze_database')) {
	/**
	 * @return array<string,mixed>
	 */
	function db_maintenance_analyze_database() {
		$db = db_maintenance_database_name();
		if ($db === '') {
			return array('ok' => false, 'message' => 'Database name missing in config.');
		}

		$summary = mysql_select("
			SELECT
				COUNT(*) AS tables_count,
				COALESCE(SUM(data_length), 0) AS data_bytes,
				COALESCE(SUM(index_length), 0) AS index_bytes,
				COALESCE(SUM(data_free), 0) AS free_bytes
			FROM information_schema.TABLES
			WHERE table_schema = '" . mysql_res($db) . "'
		", 'row');

		$tables = mysql_select("
			SELECT
				table_name AS name,
				engine,
				table_rows AS est_rows,
				data_length AS data_bytes,
				index_length AS index_bytes,
				data_free AS free_bytes,
				CREATE_TIME AS created_at,
				UPDATE_TIME AS updated_at
			FROM information_schema.TABLES
			WHERE table_schema = '" . mysql_res($db) . "'
			ORDER BY (data_length + index_length) DESC, table_name ASC
		", 'rows') ?: array();

		$out_tables = array();
		foreach ($tables as $t) {
			$name = isset($t['name']) ? (string)$t['name'] : '';
			if ($name === '') {
				continue;
			}
			$data = (int)($t['data_bytes'] ?? 0);
			$idx = (int)($t['index_bytes'] ?? 0);
			$free = (int)($t['free_bytes'] ?? 0);
			$row = array(
				'name' => $name,
				'engine' => isset($t['engine']) ? (string)$t['engine'] : '',
				'est_rows' => (int)($t['est_rows'] ?? 0),
				'data_bytes' => $data,
				'index_bytes' => $idx,
				'total_bytes' => $data + $idx,
				'free_bytes' => $free,
				'data_human' => db_maintenance_format_bytes($data),
				'total_human' => db_maintenance_format_bytes($data + $idx),
				'created_at' => isset($t['created_at']) ? (string)$t['created_at'] : '',
				'updated_at' => isset($t['updated_at']) ? (string)$t['updated_at'] : '',
			);
			$row['exact_rows'] = db_maintenance_exact_row_count($name);
			$row['time_stats'] = db_maintenance_table_time_stats($name);
			$out_tables[] = $row;
		}

		$data_bytes = (int)($summary['data_bytes'] ?? 0);
		$index_bytes = (int)($summary['index_bytes'] ?? 0);
		$free_bytes = (int)($summary['free_bytes'] ?? 0);

		return array(
			'ok' => true,
			'database' => $db,
			'analyzed_at' => date('c'),
			'summary' => array(
				'tables_count' => (int)($summary['tables_count'] ?? 0),
				'data_bytes' => $data_bytes,
				'index_bytes' => $index_bytes,
				'total_bytes' => $data_bytes + $index_bytes,
				'free_bytes' => $free_bytes,
				'data_human' => db_maintenance_format_bytes($data_bytes),
				'total_human' => db_maintenance_format_bytes($data_bytes + $index_bytes),
				'free_human' => db_maintenance_format_bytes($free_bytes),
			),
			'tables' => $out_tables,
			'content_heavy_tables' => db_maintenance_content_heavy_tables($out_tables),
		);
	}
}

if (!function_exists('db_maintenance_exact_row_count')) {
	function db_maintenance_exact_row_count($table) {
		if (!db_maintenance_table_exists($table)) {
			return null;
		}
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . preg_replace('/[^a-z0-9_]/i', '', $table) . "`", 'row');
		return $r && isset($r['c']) ? (int)$r['c'] : null;
	}
}

if (!function_exists('db_maintenance_table_time_stats')) {
	function db_maintenance_table_time_stats($table) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		if ($table === '' || !db_maintenance_table_exists($table)) {
			return null;
		}
		$col = null;
		foreach (array('created_at', 'updated_at', 'finished_at', 'started_at') as $candidate) {
			if (db_maintenance_column_exists($table, $candidate)) {
				$col = $candidate;
				break;
			}
		}
		if ($col === null) {
			return null;
		}
		$r = mysql_select("
			SELECT MIN(`" . $col . "`) AS min_ts, MAX(`" . $col . "`) AS max_ts
			FROM `" . $table . "`
			WHERE `" . $col . "` IS NOT NULL AND `" . $col . "` <> '0000-00-00 00:00:00'
		", 'row');
		if (!$r) {
			return null;
		}
		return array(
			'column' => $col,
			'min' => isset($r['min_ts']) ? (string)$r['min_ts'] : '',
			'max' => isset($r['max_ts']) ? (string)$r['max_ts'] : '',
		);
	}
}

if (!function_exists('db_maintenance_content_heavy_tables')) {
	function db_maintenance_content_heavy_tables(array $tables) {
		$needles = array('content_i18n', 'pages', 'blog', 'guides', 'games', 'casino_articles', 'casinos');
		$out = array();
		foreach ($tables as $t) {
			$name = isset($t['name']) ? (string)$t['name'] : '';
			foreach ($needles as $n) {
				if ($name === $n) {
					$note = 'Site content — not auto-deleted by maintenance script.';
					if ($name === 'blog') {
						$note = 'Use: php run_db_maintenance.php clean --target=blog --wipe-all';
					}
					$out[] = array(
						'name' => $name,
						'total_human' => isset($t['total_human']) ? $t['total_human'] : '',
						'exact_rows' => isset($t['exact_rows']) ? $t['exact_rows'] : null,
						'note' => $note,
					);
					break;
				}
			}
		}
		return $out;
	}
}

if (!function_exists('db_maintenance_cutoff_sql')) {
	function db_maintenance_cutoff_sql($days) {
		$days = max(1, (int)$days);
		return date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
	}
}

if (!function_exists('db_maintenance_count_where')) {
	function db_maintenance_count_where($table, $where_sql) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		if ($table === '' || !db_maintenance_table_exists($table)) {
			return 0;
		}
		$r = mysql_select("SELECT COUNT(*) AS c FROM `" . $table . "` WHERE " . $where_sql, 'row');
		return $r && isset($r['c']) ? (int)$r['c'] : 0;
	}
}

if (!function_exists('db_maintenance_delete_chunked')) {
	/**
	 * Chunked DELETE … ORDER BY id ASC LIMIT N.
	 *
	 * @return array{deleted:int,chunks:int,remaining:int}
	 */
	function db_maintenance_delete_chunked($table, $where_sql, array $opts = array()) {
		$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
		$opts = db_maintenance_merge_options($opts);
		if ($table === '' || !db_maintenance_table_exists($table)) {
			return array('deleted' => 0, 'chunks' => 0, 'remaining' => 0);
		}
		if (!db_maintenance_column_exists($table, 'id')) {
			return array('deleted' => 0, 'chunks' => 0, 'remaining' => 0, 'error' => 'Table has no id column');
		}

		$remaining_before = db_maintenance_count_where($table, $where_sql);
		if (!empty($opts['dry_run'])) {
			return array('deleted' => $remaining_before, 'chunks' => 0, 'remaining' => $remaining_before, 'dry_run' => true);
		}

		$deleted = 0;
		$chunks = 0;
		$chunk = (int)$opts['chunk'];
		$max_chunks = (int)$opts['max_chunks'];
		$pause_ms = (int)$opts['pause_ms'];

		for ($i = 0; $i < $max_chunks; $i++) {
			$ids_rows = mysql_select(
				"SELECT id FROM `" . $table . "` WHERE " . $where_sql . " ORDER BY `id` ASC LIMIT " . (int)$chunk,
				'rows'
			) ?: array();
			if (empty($ids_rows)) {
				break;
			}
			$ids = array();
			foreach ($ids_rows as $r) {
				if (!empty($r['id'])) {
					$ids[] = (int)$r['id'];
				}
			}
			if (empty($ids)) {
				break;
			}
			$count_before = db_maintenance_count_where($table, $where_sql);
			$sql = "DELETE FROM `" . $table . "` WHERE id IN (" . implode(',', $ids) . ")";
			mysql_fn('query', $sql);
			$count_after = db_maintenance_count_where($table, $where_sql);
			$n = $count_before - $count_after;
			if ($n <= 0 && function_exists('mysql_query_affected_rows')) {
				$n = mysql_query_affected_rows($sql);
			}
			if ($n <= 0) {
				break;
			}
			$deleted += $n;
			$chunks++;
			if ($n < count($ids)) {
				break;
			}
			if ($pause_ms > 0) {
				usleep($pause_ms * 1000);
			}
		}

		$remaining = db_maintenance_count_where($table, $where_sql);
		return array(
			'deleted' => $deleted,
			'chunks' => $chunks,
			'remaining' => $remaining,
			'remaining_before' => $remaining_before,
		);
	}
}

if (!function_exists('db_maintenance_optimize_tables')) {
	function db_maintenance_optimize_tables(array $tables) {
		$done = array();
		foreach ($tables as $table) {
			$table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
			if ($table === '' || !db_maintenance_table_exists($table)) {
				continue;
			}
			mysql_fn('query', "OPTIMIZE TABLE `" . $table . "`");
			$done[] = $table;
		}
		return $done;
	}
}

if (!function_exists('db_maintenance_blog_admin_jobs_where')) {
	function db_maintenance_blog_admin_jobs_where() {
		return "payload LIKE '%\"entity\":\"blog\"%'";
	}
}

if (!function_exists('db_maintenance_blog_wipe_counts')) {
	/**
	 * Row counts included in a full blog wipe.
	 *
	 * @return array<string,int>
	 */
	function db_maintenance_blog_wipe_counts() {
		$counts = array();
		foreach (array('blog', 'blog_category', 'blog_tags') as $table) {
			if (db_maintenance_table_exists($table)) {
				$counts[$table] = (int)db_maintenance_exact_row_count($table);
			}
		}
		if (db_maintenance_table_exists('content_i18n')) {
			$counts['content_i18n'] = db_maintenance_count_where('content_i18n', "entity='blog'");
		}
		if (db_maintenance_table_exists('translation_cluster_state')) {
			$counts['translation_cluster_state'] = db_maintenance_count_where('translation_cluster_state', "entity='blog'");
		}
		if (db_maintenance_table_exists('translation_order_candidates')) {
			$counts['translation_order_candidates'] = db_maintenance_count_where('translation_order_candidates', "entity='blog'");
		}
		if (db_maintenance_table_exists('translation_orders')) {
			$counts['translation_orders'] = db_maintenance_count_where('translation_orders', "entity='blog'");
		}
		if (db_maintenance_table_exists('admin_jobs')) {
			$counts['admin_jobs'] = db_maintenance_count_where('admin_jobs', db_maintenance_blog_admin_jobs_where());
		}
		return $counts;
	}
}

if (!function_exists('db_maintenance_blog_wipe_all')) {
	/**
	 * Remove all blog posts, categories, tags, translations (content_i18n), cluster state,
	 * translation monitor rows, and queued admin jobs for entity=blog.
	 * Does not remove pages.module=blog (blog section URL in menu).
	 *
	 * @return array{ok:bool,deleted:int,remaining:int,remaining_before:int,chunks:int,details:array,dry_run?:bool,messages?:string[]}
	 */
	function db_maintenance_blog_wipe_all(array $opts = array()) {
		$opts = db_maintenance_merge_options($opts);
		$dry_run = !empty($opts['dry_run']);
		$counts_before = db_maintenance_blog_wipe_counts();
		$remaining_before = 0;
		foreach ($counts_before as $n) {
			$remaining_before += (int)$n;
		}

		if ($dry_run) {
			return array(
				'ok' => true,
				'deleted' => $remaining_before,
				'remaining' => $remaining_before,
				'remaining_before' => $remaining_before,
				'chunks' => 0,
				'details' => $counts_before,
				'dry_run' => true,
			);
		}

		$details = array();
		$deleted = 0;
		$chunks = 0;

		if (db_maintenance_table_exists('translation_order_candidates')) {
			$n = db_maintenance_count_where('translation_order_candidates', "entity='blog'");
			if ($n > 0) {
				mysql_fn('query', "DELETE FROM `translation_order_candidates` WHERE entity='blog'");
			}
			$details['translation_order_candidates'] = $n;
			$deleted += $n;
		}

		if (db_maintenance_table_exists('translation_orders')) {
			$n = db_maintenance_count_where('translation_orders', "entity='blog'");
			if ($n > 0) {
				mysql_fn('query', "DELETE FROM `translation_orders` WHERE entity='blog'");
			}
			$details['translation_orders'] = $n;
			$deleted += $n;
		}

		if (db_maintenance_table_exists('admin_jobs')) {
			$job_where = db_maintenance_blog_admin_jobs_where();
			$del = db_maintenance_delete_chunked('admin_jobs', $job_where, $opts);
			$details['admin_jobs'] = (int)($del['deleted'] ?? 0);
			$deleted += (int)($del['deleted'] ?? 0);
			$chunks += (int)($del['chunks'] ?? 0);
			if (!empty($del['remaining'])) {
				return array(
					'ok' => false,
					'deleted' => $deleted,
					'remaining' => (int)$del['remaining'],
					'remaining_before' => $remaining_before,
					'chunks' => $chunks,
					'details' => $details,
					'messages' => array('admin_jobs: not fully deleted; re-run or raise --max-chunks.'),
				);
			}
		}

		if (db_maintenance_table_exists('content_i18n')) {
			$del = db_maintenance_delete_chunked('content_i18n', "entity='blog'", $opts);
			$details['content_i18n'] = (int)($del['deleted'] ?? 0);
			$deleted += (int)($del['deleted'] ?? 0);
			$chunks += (int)($del['chunks'] ?? 0);
			if (!empty($del['remaining'])) {
				return array(
					'ok' => false,
					'deleted' => $deleted,
					'remaining' => (int)$del['remaining'],
					'remaining_before' => $remaining_before,
					'chunks' => $chunks,
					'details' => $details,
					'messages' => array('content_i18n (blog): not fully deleted; re-run or raise --max-chunks.'),
				);
			}
		}

		if (db_maintenance_table_exists('translation_cluster_state')) {
			$del = db_maintenance_delete_chunked('translation_cluster_state', "entity='blog'", $opts);
			$details['translation_cluster_state'] = (int)($del['deleted'] ?? 0);
			$deleted += (int)($del['deleted'] ?? 0);
			$chunks += (int)($del['chunks'] ?? 0);
			if (!empty($del['remaining'])) {
				return array(
					'ok' => false,
					'deleted' => $deleted,
					'remaining' => (int)$del['remaining'],
					'remaining_before' => $remaining_before,
					'chunks' => $chunks,
					'details' => $details,
					'messages' => array('translation_cluster_state (blog): not fully deleted; re-run or raise --max-chunks.'),
				);
			}
		}

		foreach (array('blog_tags', 'blog', 'blog_category') as $table) {
			if (!db_maintenance_table_exists($table)) {
				continue;
			}
			$n = (int)db_maintenance_exact_row_count($table);
			if ($n > 0) {
				mysql_fn('query', 'TRUNCATE TABLE `' . preg_replace('/[^a-z0-9_]/i', '', $table) . '`');
			}
			$details[$table] = $n;
			$deleted += $n;
		}

		$counts_after = db_maintenance_blog_wipe_counts();
		$remaining = 0;
		foreach ($counts_after as $n) {
			$remaining += (int)$n;
		}

		return array(
			'ok' => ($remaining === 0),
			'deleted' => $deleted,
			'remaining' => $remaining,
			'remaining_before' => $remaining_before,
			'chunks' => $chunks,
			'details' => $details,
			'messages' => $remaining > 0 ? array('Some blog-related rows remain; re-run the same command.') : array(),
		);
	}
}

if (!function_exists('db_maintenance_estimate_target')) {
	function db_maintenance_estimate_target($target, array $opts = array()) {
		$opts = db_maintenance_merge_options($opts);
		$days = isset($opts['days'][$target]) ? (int)$opts['days'][$target] : 30;
		$cut = db_maintenance_cutoff_sql($days);
		$est = array(
			'target' => $target,
			'days' => $days,
			'cutoff' => $cut,
			'deletable' => 0,
			'details' => array(),
		);

		switch ($target) {
			case 'system_logs':
				if (db_maintenance_table_exists('system_logs')) {
					$est['deletable'] = db_maintenance_count_where('system_logs', "created_at < '" . mysql_res($cut) . "'");
				}
				break;

			case 'admin_jobs':
				if (db_maintenance_table_exists('admin_jobs')) {
					$est['deletable'] = db_maintenance_count_where(
						'admin_jobs',
						"status IN ('done','failed','cancelled') AND ((finished_at IS NOT NULL AND finished_at < '" . mysql_res($cut) . "') OR (finished_at IS NULL AND created_at < '" . mysql_res($cut) . "'))"
					);
				}
				break;

			case 'site_telemetry_events':
				if (db_maintenance_table_exists('site_telemetry_events')) {
					$est['deletable'] = db_maintenance_count_where('site_telemetry_events', "created_at < '" . mysql_res($cut) . "'");
				}
				break;

			case 'translation_orders':
				if (db_maintenance_table_exists('translation_orders')) {
					$where = "status IN ('completed','cancelled') AND COALESCE(updated_at, created_at) < '" . mysql_res($cut) . "'";
					$est['deletable'] = db_maintenance_count_where('translation_orders', $where);
					if (db_maintenance_table_exists('translation_order_candidates')) {
						$est['details']['candidates_for_old_orders'] = (int)mysql_select("
							SELECT COUNT(*) AS c
							FROM translation_order_candidates toc
							INNER JOIN translation_orders tor ON tor.id = toc.order_id
							WHERE tor.status IN ('completed','cancelled')
							  AND COALESCE(tor.updated_at, tor.created_at) < '" . mysql_res($cut) . "'
						", 'string');
					}
				}
				break;

			case 'translation_vector_items':
				if (db_maintenance_table_exists('translation_vector_items')) {
					$est['deletable'] = db_maintenance_count_where(
						'translation_vector_items',
						"quality_status = 'auto' AND (last_used_at IS NULL OR last_used_at < '" . mysql_res($cut) . "') AND created_at < '" . mysql_res($cut) . "'"
					);
				}
				break;

			case 'blog':
				if (!empty($opts['wipe_all'])) {
					$counts = db_maintenance_blog_wipe_counts();
					$est['deletable'] = array_sum($counts);
					$est['details'] = $counts;
					$est['cutoff'] = '';
					$est['days'] = 0;
				}
				break;
		}

		return $est;
	}
}

if (!function_exists('db_maintenance_clean_target')) {
	function db_maintenance_clean_target($target, array $opts = array()) {
		$opts = db_maintenance_merge_options($opts);
		$days = isset($opts['days'][$target]) ? (int)$opts['days'][$target] : 30;
		$cut = db_maintenance_cutoff_sql($days);
		$result = array(
			'target' => $target,
			'days' => $days,
			'cutoff' => $cut,
			'ok' => true,
			'deleted' => 0,
			'chunks' => 0,
			'remaining' => 0,
			'optimized' => array(),
			'messages' => array(),
		);

		switch ($target) {
			case 'system_logs':
				if (!db_maintenance_table_exists('system_logs')) {
					$result['ok'] = false;
					$result['messages'][] = 'Table system_logs not found.';
					break;
				}
				$del = db_maintenance_delete_chunked('system_logs', "created_at < '" . mysql_res($cut) . "'", $opts);
				$result = array_merge($result, $del);
				if (!empty($opts['optimize'])) {
					$result['optimized'] = db_maintenance_optimize_tables(array('system_logs'));
				}
				break;

			case 'admin_jobs':
				if (!db_maintenance_table_exists('admin_jobs')) {
					$result['ok'] = false;
					$result['messages'][] = 'Table admin_jobs not found.';
					break;
				}
				$where = "status IN ('done','failed','cancelled') AND ((finished_at IS NOT NULL AND finished_at < '" . mysql_res($cut) . "') OR (finished_at IS NULL AND created_at < '" . mysql_res($cut) . "'))";
				$del = db_maintenance_delete_chunked('admin_jobs', $where, $opts);
				$result = array_merge($result, $del);
				if (!empty($opts['optimize'])) {
					$result['optimized'] = db_maintenance_optimize_tables(array('admin_jobs'));
				}
				break;

			case 'site_telemetry_events':
				if (!db_maintenance_table_exists('site_telemetry_events')) {
					$result['ok'] = false;
					$result['messages'][] = 'Table site_telemetry_events not found.';
					break;
				}
				$del = db_maintenance_delete_chunked('site_telemetry_events', "created_at < '" . mysql_res($cut) . "'", $opts);
				$result = array_merge($result, $del);
				if (!empty($opts['optimize'])) {
					$result['optimized'] = db_maintenance_optimize_tables(array('site_telemetry_events'));
				}
				break;

			case 'translation_orders':
				if (!db_maintenance_table_exists('translation_orders')) {
					$result['ok'] = false;
					$result['messages'][] = 'Table translation_orders not found.';
					break;
				}
				$order_where = "status IN ('completed','cancelled') AND COALESCE(updated_at, created_at) < '" . mysql_res($cut) . "'";
				$cand_deleted = 0;
				$orders_deleted = 0;
				$chunks = 0;
				$chunk = (int)$opts['chunk'];
				$max_chunks = (int)$opts['max_chunks'];
				$pause_ms = (int)$opts['pause_ms'];

				if (!empty($opts['dry_run'])) {
					$orders_deleted = db_maintenance_count_where('translation_orders', $order_where);
					if (db_maintenance_table_exists('translation_order_candidates')) {
						$cand_deleted = (int)mysql_select("
							SELECT COUNT(*) AS c
							FROM translation_order_candidates toc
							INNER JOIN translation_orders tor ON tor.id = toc.order_id
							WHERE " . $order_where . "
						", 'string');
					}
					$result['deleted'] = $orders_deleted;
					$result['remaining'] = $orders_deleted;
					$result['details'] = array('candidates_deleted' => $cand_deleted);
					break;
				}

				for ($i = 0; $i < $max_chunks; $i++) {
					$ids = mysql_select("
						SELECT id FROM translation_orders
						WHERE " . $order_where . "
						ORDER BY id ASC
						LIMIT " . $chunk . "
					", 'rows') ?: array();
					if (empty($ids)) {
						break;
					}
					$id_list = array();
					foreach ($ids as $r) {
						if (!empty($r['id'])) {
							$id_list[] = (int)$r['id'];
						}
					}
					if (empty($id_list)) {
						break;
					}
					$in = implode(',', $id_list);
					if (db_maintenance_table_exists('translation_order_candidates')) {
						$cand_deleted += function_exists('mysql_query_affected_rows')
							? mysql_query_affected_rows("DELETE FROM translation_order_candidates WHERE order_id IN (" . $in . ")")
							: 0;
					}
					$n = function_exists('mysql_query_affected_rows')
						? mysql_query_affected_rows("DELETE FROM translation_orders WHERE id IN (" . $in . ")")
						: 0;
					$orders_deleted += $n;
					$chunks++;
					if ($n < count($id_list)) {
						// Some ids may have been removed concurrently; continue until SELECT returns empty.
					}
					if ($pause_ms > 0) {
						usleep($pause_ms * 1000);
					}
				}

				$result['deleted'] = $orders_deleted;
				$result['chunks'] = $chunks;
				$result['remaining'] = db_maintenance_count_where('translation_orders', $order_where);
				$result['details'] = array('candidates_deleted' => $cand_deleted);
				if (!empty($opts['optimize'])) {
					$result['optimized'] = db_maintenance_optimize_tables(array('translation_orders', 'translation_order_candidates'));
				}
				break;

			case 'translation_vector_items':
				if (!db_maintenance_table_exists('translation_vector_items')) {
					$result['ok'] = false;
					$result['messages'][] = 'Table translation_vector_items not found.';
					break;
				}
				if (!empty($opts['wipe_all'])) {
					if (!function_exists('translation_vector_clear_all')) {
						require_once ROOT_DIR . 'functions/translation_cluster.php';
					}
					$clr = translation_vector_clear_all(array(
						'chunk' => (int)$opts['chunk'],
						'max_chunks' => (int)$opts['max_chunks'],
						'pause_ms' => (int)$opts['pause_ms'],
						'dry_run' => !empty($opts['dry_run']),
					));
					$result['wipe_all'] = true;
					$result['deleted'] = (int)($clr['deleted'] ?? 0);
					$result['chunks'] = (int)($clr['chunks'] ?? 0);
					$result['remaining'] = (int)($clr['remaining'] ?? 0);
					$result['remaining_before'] = (int)($clr['remaining_before'] ?? 0);
					if ($result['deleted'] <= 0 && $result['remaining_before'] > $result['remaining']) {
						$result['deleted'] = $result['remaining_before'] - $result['remaining'];
					}
					$result['ok'] = !empty($clr['ok']);
					if ($result['remaining'] > 0 && empty($opts['dry_run'])) {
						$result['ok'] = false;
						$result['messages'][] = 'Not fully empty after this run; re-run the same command or raise --max-chunks.';
					}
					if (!empty($opts['optimize']) && $result['remaining'] === 0) {
						$result['optimized'] = db_maintenance_optimize_tables(array('translation_vector_items'));
					} elseif (!empty($opts['optimize']) && $result['remaining'] > 0) {
						$result['optimized'] = db_maintenance_optimize_tables(array('translation_vector_items'));
					}
					break;
				}
				$where = "quality_status = 'auto' AND (last_used_at IS NULL OR last_used_at < '" . mysql_res($cut) . "') AND created_at < '" . mysql_res($cut) . "'";
				$del = db_maintenance_delete_chunked('translation_vector_items', $where, $opts);
				$result = array_merge($result, $del);
				if (!empty($opts['optimize'])) {
					$result['optimized'] = db_maintenance_optimize_tables(array('translation_vector_items'));
				}
				break;

			case 'blog':
				if (empty($opts['wipe_all'])) {
					$result['ok'] = false;
					$result['messages'][] = 'Blog requires --wipe-all (removes all posts and translations).';
					break;
				}
				$clr = db_maintenance_blog_wipe_all($opts);
				$result['wipe_all'] = true;
				$result['deleted'] = (int)($clr['deleted'] ?? 0);
				$result['chunks'] = (int)($clr['chunks'] ?? 0);
				$result['remaining'] = (int)($clr['remaining'] ?? 0);
				$result['remaining_before'] = (int)($clr['remaining_before'] ?? 0);
				if ($result['deleted'] <= 0 && $result['remaining_before'] > $result['remaining']) {
					$result['deleted'] = $result['remaining_before'] - $result['remaining'];
				}
				$result['ok'] = !empty($clr['ok']);
				$result['details'] = isset($clr['details']) && is_array($clr['details']) ? $clr['details'] : array();
				if (!empty($clr['messages'])) {
					$result['messages'] = array_merge($result['messages'], (array)$clr['messages']);
				}
				if ($result['remaining'] > 0 && empty($opts['dry_run'])) {
					$result['ok'] = false;
					if (empty($result['messages'])) {
						$result['messages'][] = 'Not fully empty after this run; re-run the same command or raise --max-chunks.';
					}
				}
				if (!empty($opts['optimize'])) {
					$tables = array();
					foreach (array('blog', 'blog_category', 'blog_tags', 'content_i18n', 'translation_cluster_state', 'translation_orders', 'translation_order_candidates', 'admin_jobs') as $t) {
						if (db_maintenance_table_exists($t)) {
							$tables[] = $t;
						}
					}
					$result['optimized'] = db_maintenance_optimize_tables($tables);
				}
				break;

			default:
				$result['ok'] = false;
				$result['messages'][] = 'Unknown target: ' . $target;
		}

		if (!isset($result['deleted'])) {
			$result['deleted'] = 0;
		}
		$wipe_targets = db_maintenance_wipe_all_targets();
		$is_wipe = !empty($opts['wipe_all']) && in_array($target, $wipe_targets, true);
		$mode = $is_wipe ? ' (wipe all)' : '';
		$remaining_label = $is_wipe ? ', rows left ' : ', remaining ';
		$result['message'] = $target . $mode . ': ' . ($opts['dry_run'] ? 'would delete ' : 'deleted ')
			. (int)$result['deleted'] . ' row(s)'
			. (isset($result['remaining']) ? ($remaining_label . (int)$result['remaining']) : '');
		if (!empty($result['remaining_before']) && $is_wipe) {
			$result['message'] .= ' (was ' . (int)$result['remaining_before'] . ' before)';
		}
		return $result;
	}
}

if (!function_exists('db_maintenance_analyze_retention')) {
	function db_maintenance_analyze_retention(array $targets, array $opts = array()) {
		$opts = db_maintenance_merge_options($opts);
		$estimates = array();
		$total = 0;
		foreach ($targets as $target) {
			$est = db_maintenance_estimate_target($target, $opts);
			$estimates[] = $est;
			$total += (int)$est['deletable'];
		}
		return array(
			'estimates' => $estimates,
			'total_deletable' => $total,
			'options' => $opts,
		);
	}
}

if (!function_exists('db_maintenance_run_cleanup')) {
	function db_maintenance_run_cleanup(array $targets, array $opts = array()) {
		$opts = db_maintenance_merge_options($opts);
		$results = array();
		$total_deleted = 0;
		foreach ($targets as $target) {
			$res = db_maintenance_clean_target($target, $opts);
			$results[] = $res;
			$total_deleted += (int)($res['deleted'] ?? 0);
		}
		return array(
			'ok' => true,
			'dry_run' => !empty($opts['dry_run']),
			'total_deleted' => $total_deleted,
			'results' => $results,
			'options' => $opts,
		);
	}
}

if (!function_exists('db_maintenance_full_report')) {
	function db_maintenance_full_report(array $targets, array $opts = array(), $include_optional = false) {
		$db = db_maintenance_analyze_database();
		$retention = db_maintenance_analyze_retention($targets, $opts);
		return array(
			'database' => $db,
			'retention' => $retention,
			'targets' => db_maintenance_all_targets($include_optional),
			'generated_at' => date('c'),
		);
	}
}
