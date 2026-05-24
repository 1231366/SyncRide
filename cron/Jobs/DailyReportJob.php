<?php

declare(strict_types=1);

namespace Cron\Jobs;

use Cron\CronJob;

/**
 * Sends the next-day operations report to every driver via email.
 *
 * Wraps the legacy procedural script while we incrementally extract
 * its responsibilities into proper services (ServiceQuery, MailRenderer,
 * MailSender). The wrapper guarantees the new runner orchestrates jobs
 * uniformly without freezing legacy behaviour mid-rewrite.
 */
final class DailyReportJob implements CronJob
{
    public function name(): string
    {
        return 'daily-report';
    }

    public function description(): string
    {
        return 'Emails the next-day service report to all drivers.';
    }

    public function run(): string
    {
        ob_start();
        require __DIR__ . '/../Legacy/daily_report_legacy.php';
        $output = (string) ob_get_clean();

        return 'daily report dispatched (legacy bridge, ' . strlen($output) . ' bytes)';
    }
}
