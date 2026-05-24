<?php

declare(strict_types=1);

/**
 * CLI entry point for scheduled jobs.
 *
 * Usage (crontab):
 *   * * * * * /usr/bin/php /var/www/syncride/cron/run.php <job-name> $CRON_SECRET
 *
 * Examples:
 *   php cron/run.php daily-report     "$CRON_SECRET"
 *   php cron/run.php whatsapp-agenda  "$CRON_SECRET"
 */

require __DIR__ . '/../bootstrap.php';

use Cron\CronRunner;

$jobName = $argv[1] ?? null;
$secret  = $argv[2] ?? null;

if ($jobName === null) {
    fwrite(STDERR, "usage: php cron/run.php <job-name> <secret>\n");
    exit(64);
}

$registry = require __DIR__ . '/../config/cron.php';

try {
    $summary = (new CronRunner($registry['jobs']))->run($jobName, $secret);
    fwrite(STDOUT, $summary . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
