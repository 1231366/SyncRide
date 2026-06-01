<?php

declare(strict_types=1);

namespace Cron\Jobs;

use App\Repositories\TenantSettingsRepository;
use App\Services\ScheduleMailer;
use App\Support\Database;
use Cron\CronJob;
use PDO;

/**
 * Sends the next-day operations schedule by email to every enabled tenant.
 * Runs without an HTTP session — company IDs are resolved directly from
 * TenantSettings rather than from $_SESSION.
 */
final class DailyReportJob implements CronJob
{
    public function name(): string
    {
        return 'daily-report';
    }

    public function description(): string
    {
        return 'Emails the next-day service schedule to all tenants with schedule_enabled = 1.';
    }

    public function run(): string
    {
        $db = Database::connection();

        // Find every company that has schedule emails enabled.
        $stmt = $db->query(
            "SELECT company_id FROM TenantSettings WHERE `key` = 'schedule_enabled' AND `value` = '1'"
        );
        $companies = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($companies === []) {
            return 'daily-report: no tenants have schedule email enabled — nothing sent';
        }

        $mailer = new ScheduleMailer($db);
        $sent   = 0;
        $failed = 0;

        foreach ($companies as $companyId) {
            $companyId = (int) $companyId;
            $settings  = new TenantSettingsRepository($db, $companyId);
            $recipients = $settings->scheduleRecipients();
            $myCopy     = $settings->scheduleMyCopy();

            $ok = $mailer->sendForTomorrow($recipients, $myCopy, $companyId);
            $ok ? $sent++ : $failed++;
        }

        return "daily-report: sent={$sent} failed={$failed}";
    }
}
