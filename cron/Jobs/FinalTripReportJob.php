<?php

declare(strict_types=1);

namespace Cron\Jobs;

use Cron\CronJob;

/**
 * Emails the final trip report (boarding-pass style) after a service
 * completes, with branching logic for direct vs. partner-routed trips.
 */
final class FinalTripReportJob implements CronJob
{
    public function name(): string
    {
        return 'final-trip';
    }

    public function description(): string
    {
        return 'Emails the final post-trip report to the client/partner.';
    }

    public function run(): string
    {
        ob_start();
        require __DIR__ . '/../Legacy/final_trip_report_legacy.php';
        $output = (string) ob_get_clean();

        return 'final trip report dispatched (legacy bridge, ' . strlen($output) . ' bytes)';
    }
}
