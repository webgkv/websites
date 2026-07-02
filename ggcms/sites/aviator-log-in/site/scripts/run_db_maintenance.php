#!/usr/bin/env php
<?php
/**
 * DB analyzer + chunked retention cleanup.
 *
 * CLI:
 *   php run_db_maintenance.php analyze
 *   php run_db_maintenance.php analyze --json > /tmp/db-report.json
 *   php run_db_maintenance.php clean --dry-run
 *   php run_db_maintenance.php clean --target=system_logs --days=30
 *   php run_db_maintenance.php clean --all-safe --days=30 --chunk=500 --max-chunks=200
 *   php run_db_maintenance.php clean --all-safe --include-optional --optimize
 *   php run_db_maintenance.php clean --target=translation_vector_items --wipe-all --optimize
 *   php run_db_maintenance.php clean --target=translation_vector_items --wipe-all --dry-run
 *
 * Web (protect with server auth / IP allowlist):
 *   /scripts/run_db_maintenance.php?run=1&mode=analyze
 *   /scripts/run_db_maintenance.php?run=1&mode=clean&dry_run=1&all_safe=1
 */
$is_cli = (php_sapi_name() === 'cli');

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

$config_file = ROOT_DIR . 'config/config.php';
if (!is_file($config_file)) {
	$msg = "Error: config/config.php not found.\n";
	if ($is_cli) {
		fwrite(STDERR, $msg);
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('error' => 'Config not found'));
	exit(1);
}

if ($is_cli) {
	if (!isset($_SERVER['HTTP_HOST'])) {
		$_SERVER['HTTP_HOST'] = 'localhost';
	}
	if (!isset($_SERVER['REMOTE_ADDR'])) {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
	}
	if (!isset($_SERVER['SERVER_ADDR'])) {
		$_SERVER['SERVER_ADDR'] = '127.0.0.1';
	}
}

require_once ROOT_DIR . 'config/config.php';
require_once ROOT_DIR . 'functions/mysql_func.php';
require_once ROOT_DIR . 'functions/db_maintenance.php';

@set_time_limit(0);
if (function_exists('ini_set')) {
	@ini_set('memory_limit', '256M');
}

function db_maint_parse_args($argv_or_get) {
	$out = array(
		'mode' => '',
		'target' => '',
		'all_safe' => false,
		'include_optional' => false,
		'dry_run' => false,
		'optimize' => false,
		'wipe_all' => false,
		'json' => false,
		'days' => array(),
		'chunk' => 0,
		'max_chunks' => 0,
		'pause_ms' => -1,
		'out' => '',
		'top' => 25,
	);
	$args = is_array($argv_or_get) ? $argv_or_get : array();
	foreach ($args as $arg) {
		if (!is_string($arg)) {
			continue;
		}
		if ($arg === 'analyze' || $arg === 'clean') {
			$out['mode'] = $arg;
		} elseif ($arg === '--dry-run' || $arg === '-n') {
			$out['dry_run'] = true;
		} elseif ($arg === '--optimize') {
			$out['optimize'] = true;
		} elseif ($arg === '--wipe-all') {
			$out['wipe_all'] = true;
		} elseif ($arg === '--json') {
			$out['json'] = true;
		} elseif ($arg === '--all-safe') {
			$out['all_safe'] = true;
		} elseif ($arg === '--include-optional') {
			$out['include_optional'] = true;
		} elseif (preg_match('/^--target=(.+)$/', $arg, $m)) {
			$out['target'] = trim($m[1]);
		} elseif (preg_match('/^--days=(\d+)$/', $arg, $m)) {
			$out['days']['_default'] = (int)$m[1];
		} elseif (preg_match('/^--days-([a-z0-9_]+)=(\d+)$/', $arg, $m)) {
			$out['days'][$m[1]] = (int)$m[2];
		} elseif (preg_match('/^--chunk=(\d+)$/', $arg, $m)) {
			$out['chunk'] = (int)$m[1];
		} elseif (preg_match('/^--max-chunks=(\d+)$/', $arg, $m)) {
			$out['max_chunks'] = (int)$m[1];
		} elseif (preg_match('/^--pause-ms=(\d+)$/', $arg, $m)) {
			$out['pause_ms'] = (int)$m[1];
		} elseif (preg_match('/^--out=(.+)$/', $arg, $m)) {
			$out['out'] = $m[1];
		} elseif (preg_match('/^--top=(\d+)$/', $arg, $m)) {
			$out['top'] = max(5, (int)$m[1]);
		}
	}
	return $out;
}

function db_maint_build_opts(array $args) {
	$opts = db_maintenance_default_options();
	if (!empty($args['dry_run'])) {
		$opts['dry_run'] = true;
	}
	if (!empty($args['optimize'])) {
		$opts['optimize'] = true;
	}
	if (!empty($args['wipe_all'])) {
		$opts['wipe_all'] = true;
	}
	if (!empty($args['chunk'])) {
		$opts['chunk'] = (int)$args['chunk'];
	}
	if (!empty($args['max_chunks'])) {
		$opts['max_chunks'] = (int)$args['max_chunks'];
	}
	if ($args['pause_ms'] >= 0) {
		$opts['pause_ms'] = (int)$args['pause_ms'];
	}
	if (!empty($args['days']['_default'])) {
		$d = (int)$args['days']['_default'];
		foreach ($opts['days'] as $k => $v) {
			$opts['days'][$k] = $d;
		}
	}
	foreach ($args['days'] as $k => $v) {
		if ($k === '_default') {
			continue;
		}
		$opts['days'][$k] = max(1, (int)$v);
	}
	return $opts;
}

function db_maint_resolve_targets(array $args) {
	if (!empty($args['target'])) {
		return array(trim($args['target']));
	}
	$targets = array_keys(db_maintenance_safe_targets());
	if (!empty($args['include_optional'])) {
		$targets = array_merge($targets, array_keys(db_maintenance_optional_targets()));
	}
	return $targets;
}

function db_maint_print_human_report(array $report, $top = 25) {
	$db = isset($report['database']) ? $report['database'] : array();
	if (empty($db['ok'])) {
		echo "Database analyze failed.\n";
		return;
	}
	$sum = $db['summary'];
	echo "Database: " . $db['database'] . "\n";
	echo "Total size: " . $sum['total_human'] . " (data " . $sum['data_human'] . ", index " . db_maintenance_format_bytes($sum['index_bytes']) . ")\n";
	echo "Tables: " . (int)$sum['tables_count'] . ", reclaimable free: " . $sum['free_human'] . "\n\n";

	echo "Top tables by size:\n";
	$i = 0;
	foreach ($db['tables'] as $t) {
		$i++;
		if ($i > $top) {
			break;
		}
		$rows = isset($t['exact_rows']) ? $t['exact_rows'] : $t['est_rows'];
		$ts = '';
		if (!empty($t['time_stats']['column'])) {
			$ts = ' | ' . $t['time_stats']['column'] . ' ' . $t['time_stats']['min'] . ' .. ' . $t['time_stats']['max'];
		}
		echo sprintf(
			"  %-28s %8s rows %10s%s\n",
			$t['name'],
			is_null($rows) ? '?' : number_format((int)$rows),
			$t['total_human'],
			$ts
		);
	}

	if (!empty($db['content_heavy_tables'])) {
		echo "\nContent tables (not auto-cleaned):\n";
		foreach ($db['content_heavy_tables'] as $c) {
			echo '  ' . $c['name'] . ' — ' . $c['total_human'];
			if (isset($c['exact_rows'])) {
				echo ' (' . number_format((int)$c['exact_rows']) . ' rows)';
			}
			echo "\n";
		}
	}

	if (!empty($report['retention']['estimates'])) {
		echo "\nRetention estimates:\n";
		foreach ($report['retention']['estimates'] as $est) {
			echo '  ' . $est['target'] . ': ' . number_format((int)$est['deletable']) . ' row(s), cutoff ' . $est['cutoff'] . ' (' . (int)$est['days'] . " d)\n";
			if (!empty($est['details'])) {
				foreach ($est['details'] as $k => $v) {
					echo '    ' . $k . ': ' . number_format((int)$v) . "\n";
				}
			}
		}
		echo '  TOTAL deletable (selected targets): ' . number_format((int)$report['retention']['total_deletable']) . "\n";
	}
}

function db_maint_print_cleanup(array $result) {
	echo ($result['dry_run'] ? '[dry-run] ' : '') . 'Cleanup finished. Deleted ' . number_format((int)$result['total_deleted']) . " row(s).\n";
	foreach ($result['results'] as $res) {
		echo '  - ' . (isset($res['message']) ? $res['message'] : $res['target']) . "\n";
		if (!empty($res['details'])) {
			foreach ($res['details'] as $k => $v) {
				echo '      ' . $k . ': ' . number_format((int)$v) . "\n";
			}
		}
		if (!empty($res['optimized'])) {
			echo '      optimized: ' . implode(', ', $res['optimized']) . "\n";
		}
	}
}

// --- Parse input ---
if ($is_cli) {
	$args = db_maint_parse_args(array_slice($_SERVER['argv'] ?? array(), 1));
	if ($args['mode'] === '') {
		echo "Usage:\n";
		echo "  php run_db_maintenance.php analyze [--json] [--top=25] [--out=file.json]\n";
		echo "  php run_db_maintenance.php clean [--dry-run] [--all-safe] [--include-optional]\n";
		echo "      [--target=system_logs] [--days=30] [--days-system_logs=14]\n";
		echo "      [--chunk=500] [--max-chunks=200] [--pause-ms=100] [--optimize]\n";
		echo "      [--target=translation_vector_items --wipe-all]  # delete ALL vector rows (not retention)\n";
		exit(0);
	}
} else {
	if (empty($_GET['run'])) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array(
			'hint' => 'Add ?run=1&mode=analyze or ?run=1&mode=clean&dry_run=1&all_safe=1',
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		exit(0);
	}
	$web_args = array();
	if (!empty($_GET['mode'])) {
		$web_args[] = (string)$_GET['mode'];
	}
	if (!empty($_GET['dry_run'])) {
		$web_args[] = '--dry-run';
	}
	if (!empty($_GET['all_safe'])) {
		$web_args[] = '--all-safe';
	}
	if (!empty($_GET['include_optional'])) {
		$web_args[] = '--include-optional';
	}
	if (!empty($_GET['optimize'])) {
		$web_args[] = '--optimize';
	}
	if (!empty($_GET['wipe_all'])) {
		$web_args[] = '--wipe-all';
	}
	if (!empty($_GET['target'])) {
		$web_args[] = '--target=' . preg_replace('/[^a-z0-9_]/', '', (string)$_GET['target']);
	}
	if (isset($_GET['days'])) {
		$web_args[] = '--days=' . (int)$_GET['days'];
	}
	if (isset($_GET['chunk'])) {
		$web_args[] = '--chunk=' . (int)$_GET['chunk'];
	}
	if (isset($_GET['max_chunks'])) {
		$web_args[] = '--max-chunks=' . (int)$_GET['max_chunks'];
	}
	$args = db_maint_parse_args($web_args);
	$args['json'] = true;
}

if (!mysql_connect_db()) {
	$msg = 'Cannot connect to database.';
	if ($is_cli) {
		fwrite(STDERR, $msg . "\n");
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('ok' => false, 'message' => $msg));
	exit(1);
}

$opts = db_maint_build_opts($args);
$targets = db_maint_resolve_targets($args);
if (!$args['all_safe'] && $args['target'] === '' && $args['mode'] === 'clean') {
	$targets = array_keys(db_maintenance_safe_targets());
}

if (!empty($args['wipe_all'])) {
	$wipe_ok = (count($targets) === 1 && isset($targets[0]) && $targets[0] === 'translation_vector_items');
	if (!$wipe_ok) {
		$msg = '--wipe-all requires --target=translation_vector_items (deletes every row in the table).';
		if ($is_cli) {
			fwrite(STDERR, $msg . "\n");
			exit(1);
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('ok' => false, 'message' => $msg));
		exit(1);
	}
}

$payload = null;
if ($args['mode'] === 'analyze') {
	$payload = db_maintenance_full_report($targets, $opts, !empty($args['include_optional']));
} elseif ($args['mode'] === 'clean') {
	$payload = db_maintenance_run_cleanup($targets, $opts);
	$payload['analyze_after'] = db_maintenance_analyze_database();
} else {
	$msg = 'Unknown mode. Use analyze or clean.';
	if ($is_cli) {
		fwrite(STDERR, $msg . "\n");
		exit(1);
	}
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('ok' => false, 'message' => $msg));
	exit(1);
}

if (!empty($args['out'])) {
	@file_put_contents($args['out'], json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
}

if ($is_cli && empty($args['json'])) {
	if ($args['mode'] === 'analyze') {
		db_maint_print_human_report($payload, (int)$args['top']);
	} else {
		db_maint_print_cleanup($payload);
		$sum = $payload['analyze_after']['summary'] ?? array();
		if (!empty($sum['total_human'])) {
			echo "\nDB size after: " . $sum['total_human'] . "\n";
		}
	}
	if (!empty($args['out'])) {
		echo "Report saved: " . $args['out'] . "\n";
	}
	exit(0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit(0);
