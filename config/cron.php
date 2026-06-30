<?php

declare(strict_types=1);

use App\Support\Env;

return [
    'enabled' => (bool) Env::get('CRON_ENABLED', false),
    'secret'  => Env::get('CRON_SECRET'),

    'jobs' => [
        'daily-report'    => Cron\Jobs\DailyReportJob::class,
        'whatsapp-agenda' => Cron\Jobs\WhatsappAgendaJob::class,
        'final-trip'      => Cron\Jobs\FinalTripReportJob::class,
        'sync-ai'         => Cron\Jobs\SyncAiEngineJob::class,
        'ride-reminder'   => Cron\Jobs\RideReminderJob::class,
    ],
];
