<?php

declare(strict_types=1);

namespace Cron\Jobs;

use Cron\CronJob;

/**
 * Refreshes the operational context fed to the SyncRide AI assistant.
 *
 * Pulls today's services, performance snapshots, and fleet status to
 * keep the assistant's grounding data up-to-date.
 */
final class SyncAiEngineJob implements CronJob
{
    public function name(): string
    {
        return 'sync-ai';
    }

    public function description(): string
    {
        return 'Refreshes the AI assistant grounding context.';
    }

    public function run(): string
    {
        ob_start();
        require __DIR__ . '/../Legacy/sync_ai_engine_legacy.php';
        $output = (string) ob_get_clean();

        return 'AI grounding refreshed (legacy bridge, ' . strlen($output) . ' bytes)';
    }
}
