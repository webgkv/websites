<?php
/**
 * Daily homepage lottery data sync (jackpots + latest draws).
 */
require_once ROOT_DIR . 'functions/lottery_sync.php';

$result = lottery_sync_run();
$line = date('c') . ' lottery_sync: ' . ($result['message'] ?? 'done');
if (!empty($result['errors'])) {
	$line .= ' | errors: ' . implode('; ', $result['errors']);
}
echo $line . "\n";
exit(!empty($result['ok']) ? 0 : 1);
