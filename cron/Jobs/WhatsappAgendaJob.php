<?php

declare(strict_types=1);

namespace Cron\Jobs;

use Cron\CronJob;

/**
 * Sends each driver tomorrow's service agenda via WhatsApp (Whapi gateway).
 *
 * Bridges to the legacy script while the inner logic is being extracted
 * into discrete services (AgendaQuery, MessageFormatter, WhapiClient).
 */
final class WhatsappAgendaJob implements CronJob
{
    public function name(): string
    {
        return 'whatsapp-agenda';
    }

    public function description(): string
    {
        return "Notifies drivers of tomorrow's agenda via WhatsApp.";
    }

    public function run(): string
    {
        ob_start();
        require __DIR__ . '/../Legacy/whatsapp_agenda_legacy.php';
        $output = (string) ob_get_clean();

        return 'whatsapp agenda dispatched (legacy bridge, ' . strlen($output) . ' bytes)';
    }
}
