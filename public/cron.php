<?php

declare(strict_types=1);

/**
 * HTTP cron trigger — for hosts that schedule via wget/curl (e.g. cPanel).
 *
 * Usage (cPanel cron, every day at 20:00):
 *   wget -q -O /dev/null "https://DOMAIN/cron.php?job=whatsapp-agenda&key=SECRET"
 *
 * Security: the CronRunner enforces CRON_ENABLED=true and a matching CRON_SECRET,
 * so an open URL alone cannot trigger anything without the correct key.
 */

require __DIR__ . '/../bootstrap.php';

use Cron\CronRunner;

header('Content-Type: text/plain; charset=utf-8');

$jobName = (string) ($_GET['job'] ?? '');
$secret  = (string) ($_GET['key'] ?? '');

if ($jobName === '') {
    http_response_code(400);
    echo "ERROR: missing ?job=";
    exit;
}

$registry = require __DIR__ . '/../config/cron.php';

try {
    $summary = (new CronRunner($registry['jobs']))->run($jobName, $secret);
    echo 'OK: ' . $summary;
} catch (\Throwable $e) {
    http_response_code(403);
    echo 'ERROR: ' . $e->getMessage();
}
