<?php
/**
 * Cron tick scheduler — one crontab line runs run.php tick; intervals live in variables.cron_schedule.
 */

if (!function_exists('cron_schedule_var_key')) {
	function cron_schedule_var_key() {
		return 'cron_schedule';
	}
}

if (!function_exists('cron_schedule_lock_path')) {
	function cron_schedule_lock_path() {
		$dir = ROOT_DIR . 'files';
		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}
		return $dir . '/cron_tick.lock';
	}
}

if (!function_exists('cron_schedule_default_tasks')) {
	function cron_schedule_default_tasks() {
		require_once ROOT_DIR . 'cron/tasks_registry.php';
		$registry = cron_tasks_registry();
		$out = array();
		foreach ($registry as $id => $meta) {
			$out[$id] = array(
				'enabled' => !empty($meta['default_enabled']),
				'interval_minutes' => max(1, (int)$meta['default_interval_minutes']),
				'last_run' => '',
				'last_status' => '',
			);
		}
		return $out;
	}
}

if (!function_exists('cron_schedule_load_raw')) {
	function cron_schedule_load_raw() {
		$key = cron_schedule_var_key();
		$row = @mysql_select("SELECT value FROM variables WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
		if (!$row || !isset($row['value']) || trim((string)$row['value']) === '') {
			return null;
		}
		$dec = @json_decode((string)$row['value'], true);
		return is_array($dec) ? $dec : null;
	}
}

if (!function_exists('cron_schedule_save_raw')) {
	function cron_schedule_save_raw(array $data) {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') < 1) {
			return false;
		}
		$key = cron_schedule_var_key();
		$json = json_encode($data, JSON_UNESCAPED_UNICODE);
		$exists = mysql_select("SELECT id FROM variables WHERE `key` = '" . mysql_res($key) . "' LIMIT 1", 'row');
		if ($exists && !empty($exists['id'])) {
			mysql_fn('update', 'variables', array('value' => $json), " AND `key` = '" . mysql_res($key) . "' ");
		} else {
			mysql_fn('insert', 'variables', array('key' => $key, 'value' => $json));
		}
		return true;
	}
}

if (!function_exists('cron_schedule_ensure_defaults')) {
	/**
	 * Merge registry defaults into DB; add missing tasks without overwriting existing rows.
	 */
	function cron_schedule_ensure_defaults() {
		if (@mysql_select("SHOW TABLES LIKE 'variables'", 'num_rows') < 1) {
			return array(
				'version' => 1,
				'tick_last_run' => '',
				'tasks' => cron_schedule_default_tasks(),
			);
		}
		$raw = cron_schedule_load_raw();
		$defaults = cron_schedule_default_tasks();
		if ($raw === null) {
			$data = array(
				'version' => 1,
				'tick_last_run' => '',
				'tasks' => $defaults,
			);
			cron_schedule_save_raw($data);
			return $data;
		}
		if (!isset($raw['tasks']) || !is_array($raw['tasks'])) {
			$raw['tasks'] = array();
		}
		$changed = false;
		foreach ($defaults as $id => $def) {
			if (!isset($raw['tasks'][$id]) || !is_array($raw['tasks'][$id])) {
				$raw['tasks'][$id] = $def;
				$changed = true;
				continue;
			}
			foreach (array('enabled', 'interval_minutes', 'last_run', 'last_status') as $fk) {
				if (!array_key_exists($fk, $raw['tasks'][$id])) {
					$raw['tasks'][$id][$fk] = $def[$fk];
					$changed = true;
				}
			}
		}
		if (!isset($raw['version'])) {
			$raw['version'] = 1;
			$changed = true;
		}
		if (!array_key_exists('tick_last_run', $raw)) {
			$raw['tick_last_run'] = '';
			$changed = true;
		}
		if ($changed) {
			cron_schedule_save_raw($raw);
		}
		return $raw;
	}
}

if (!function_exists('cron_schedule_get')) {
	function cron_schedule_get() {
		require_once ROOT_DIR . 'cron/tasks_registry.php';
		$raw = cron_schedule_ensure_defaults();
		$registry = cron_tasks_registry();
		$tasks = array();
		foreach ($registry as $id => $meta) {
			$row = isset($raw['tasks'][$id]) && is_array($raw['tasks'][$id]) ? $raw['tasks'][$id] : array();
			$tasks[$id] = array(
				'id' => $id,
				'label' => $meta['label'],
				'description' => $meta['description'],
				'file' => $meta['file'],
				'enabled' => !empty($row['enabled']),
				'interval_minutes' => max(1, min(43200, (int)(isset($row['interval_minutes']) ? $row['interval_minutes'] : $meta['default_interval_minutes']))),
				'last_run' => isset($row['last_run']) ? (string)$row['last_run'] : '',
				'last_status' => isset($row['last_status']) ? (string)$row['last_status'] : '',
			);
		}
		return array(
			'version' => isset($raw['version']) ? (int)$raw['version'] : 1,
			'tick_last_run' => isset($raw['tick_last_run']) ? (string)$raw['tick_last_run'] : '',
			'tasks' => $tasks,
			'crontab_line' => cron_schedule_crontab_line(),
		);
	}
}

if (!function_exists('cron_schedule_crontab_line')) {
	function cron_schedule_crontab_line() {
		$run = ROOT_DIR . 'cron/run.php';
		$path = is_file($run) ? (realpath($run) ?: $run) : $run;
		$php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';
		return '* * * * * ' . $php . ' ' . $path . ' tick';
	}
}

if (!function_exists('cron_schedule_update_tasks')) {
	/**
	 * @param array<string, array{enabled?: bool, interval_minutes?: int}> $posted
	 */
	function cron_schedule_update_tasks(array $posted) {
		$raw = cron_schedule_ensure_defaults();
		require_once ROOT_DIR . 'cron/tasks_registry.php';
		$registry = cron_tasks_registry();
		foreach ($registry as $id => $meta) {
			if (!isset($posted[$id]) || !is_array($posted[$id])) {
				continue;
			}
			$p = $posted[$id];
			$raw['tasks'][$id]['enabled'] = !empty($p['enabled']);
			if (isset($p['interval_minutes'])) {
				$raw['tasks'][$id]['interval_minutes'] = max(1, min(43200, (int)$p['interval_minutes']));
			}
		}
		cron_schedule_save_raw($raw);
	}
}

if (!function_exists('cron_schedule_task_is_due')) {
	function cron_schedule_task_is_due(array $task_row, $force = false) {
		if ($force) {
			return true;
		}
		if (empty($task_row['enabled'])) {
			return false;
		}
		$last = isset($task_row['last_run']) ? trim((string)$task_row['last_run']) : '';
		if ($last === '') {
			return true;
		}
		$last_ts = strtotime($last);
		if (!$last_ts) {
			return true;
		}
		$interval = max(1, (int)$task_row['interval_minutes']);
		return (time() - $last_ts) >= ($interval * 60);
	}
}

if (!function_exists('cron_schedule_mark_task_ran')) {
	function cron_schedule_mark_task_ran($task_id, $status = 'ok') {
		$raw = cron_schedule_ensure_defaults();
		if (!isset($raw['tasks'][$task_id])) {
			return;
		}
		$raw['tasks'][$task_id]['last_run'] = date('Y-m-d H:i:s');
		$raw['tasks'][$task_id]['last_status'] = substr((string)$status, 0, 500);
		cron_schedule_save_raw($raw);
	}
}

if (!function_exists('cron_schedule_run_task')) {
	function cron_schedule_run_task($task_id) {
		require_once ROOT_DIR . 'cron/tasks_registry.php';
		$registry = cron_tasks_registry();
		if (!isset($registry[$task_id])) {
			return array('ok' => false, 'message' => 'Unknown task: ' . $task_id);
		}
		$file = ROOT_DIR . 'cron/tasks/' . $registry[$task_id]['file'];
		if (!is_file($file)) {
			return array('ok' => false, 'message' => 'Task file missing: ' . $registry[$task_id]['file']);
		}
		try {
			if (!defined('CRON_SCHEDULE_TICK')) {
				define('CRON_SCHEDULE_TICK', true);
			}
			require $file;
			cron_schedule_mark_task_ran($task_id, 'ok');
			return array('ok' => true, 'message' => 'Ran ' . $task_id);
		} catch (Throwable $e) {
			cron_schedule_mark_task_ran($task_id, 'error: ' . $e->getMessage());
			return array('ok' => false, 'message' => $task_id . ': ' . $e->getMessage());
		}
	}
}

if (!function_exists('cron_schedule_run_tick')) {
	/**
	 * @param array{force?: bool} $options
	 * @return int exit code
	 */
	function cron_schedule_run_tick(array $options = array()) {
		$force = !empty($options['force']);
		$lock_fp = @fopen(cron_schedule_lock_path(), 'c');
		if (!$lock_fp || !flock($lock_fp, LOCK_EX | LOCK_NB)) {
			echo date('c') . " cron tick skipped: lock held\n";
			return 0;
		}

		$schedule = cron_schedule_get();
		$ran = 0;
		$skipped = 0;
		$lines = array();

		foreach ($schedule['tasks'] as $id => $task_row) {
			if (empty($task_row['enabled'])) {
				$skipped++;
				continue;
			}
			if (!cron_schedule_task_is_due($task_row, $force)) {
				$skipped++;
				continue;
			}
			$res = cron_schedule_run_task($id);
			$lines[] = date('c') . ' ' . $id . ': ' . (isset($res['message']) ? $res['message'] : ($res['ok'] ? 'ok' : 'fail'));
			if (!empty($res['ok'])) {
				$ran++;
			}
		}

		$raw = cron_schedule_load_raw();
		if (is_array($raw)) {
			$raw['tick_last_run'] = date('Y-m-d H:i:s');
			cron_schedule_save_raw($raw);
		}

		echo date('c') . " cron tick done ran={$ran} skipped={$skipped} force=" . ($force ? '1' : '0') . "\n";
		foreach ($lines as $ln) {
			echo $ln . "\n";
		}

		flock($lock_fp, LOCK_UN);
		fclose($lock_fp);
		return 0;
	}
}
