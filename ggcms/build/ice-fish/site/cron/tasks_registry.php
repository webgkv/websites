<?php
/**
 * Registered cron tasks (CLI: run.php <task> | run.php tick).
 *
 * @return array<string, array{file: string, label: string, description: string, default_interval_minutes: int, default_enabled: bool}>
 */
function cron_tasks_registry() {
	return array(
		'jobs' => array(
			'file' => 'admin_jobs.php',
			'label' => 'Admin jobs',
			'description' => 'Runs one pending row from admin_jobs (SEO recalc, imports, etc.).',
			'default_interval_minutes' => 1,
			'default_enabled' => true,
		),
		'translation_autopilot' => array(
			'file' => 'translation_autopilot.php',
			'label' => 'Translation autopilot',
			'description' => 'Autopilot tick + translation queue jobs (capped per run).',
			'default_interval_minutes' => 1,
			'default_enabled' => true,
		),
		'system_logs_cleanup' => array(
			'file' => 'system_logs_cleanup.php',
			'label' => 'Logs & jobs cleanup',
			'description' => 'Retention for system_logs and old admin_jobs (respects min interval in Settings → Variables).',
			'default_interval_minutes' => 60,
			'default_enabled' => true,
		),
		'sitemap' => array(
			'file' => 'sitemap.php',
			'label' => 'Sitemap cache',
			'description' => 'Rebuilds common sitemap cache (api/sitemap/common.xml.php).',
			'default_interval_minutes' => 360,
			'default_enabled' => true,
		),
		'sitemap_build' => array(
			'file' => 'sitemap_build.php',
			'label' => 'Sitemap static files',
			'description' => 'Builds language-split static sitemap XML files (heavy).',
			'default_interval_minutes' => 1440,
			'default_enabled' => true,
		),
	);
}
