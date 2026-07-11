<?php

declare(strict_types=1);

namespace Cron\Jobs;

use App\Services\FCMSender;
use App\Support\Database;
use Cron\CronJob;
use PDO;

/**
 * Sends a 30-minute-before push notification to the assigned driver
 * for every ride starting in the next 30–31 minutes.
 * Run every minute via crontab: * * * * * php cron/run.php ride-reminder "$CRON_SECRET"
 */
final class RideReminderJob implements CronJob
{
    public function name(): string
    {
        return 'ride-reminder';
    }

    public function description(): string
    {
        return 'Pushes a 30-min reminder to drivers for upcoming rides.';
    }

    public function run(): string
    {
        $pdo = Database::connection();

        // Rides starting in the next 30–31 min, not yet started, still without a
        // reminder. The 1-minute window + reminder_sent_at guard guarantees each
        // driver is pinged exactly once even though this job runs every minute.
        $stmt = $pdo->prepare("
            SELECT s.ID AS ride_id, sr.UserID AS driver_id,
                   s.serviceStartTime, s.serviceStartPoint, s.NomeCliente
              FROM Services s
              JOIN Services_Rides sr ON sr.RideID = s.ID
             WHERE (s.status_id = 0 OR s.status_id IS NULL)
               AND s.reminder_sent_at IS NULL
               AND TIMESTAMP(s.serviceDate, s.serviceStartTime) >= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
               AND TIMESTAMP(s.serviceDate, s.serviceStartTime) <  DATE_ADD(NOW(), INTERVAL 31 MINUTE)
        ");
        $stmt->execute();
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $markSent = $pdo->prepare('UPDATE Services SET reminder_sent_at = NOW() WHERE ID = :id');

        $sent = 0;
        foreach ($rides as $row) {
            $driverId = (int) $row['driver_id'];
            $rideId   = (int) $row['ride_id'];
            $time     = substr((string) $row['serviceStartTime'], 0, 5);
            $pickup   = (string) $row['serviceStartPoint'];
            $client   = trim((string) ($row['NomeCliente'] ?? ''));

            // Claim the ride first — if the push fails we still don't re-spam it
            // on the next tick; a missed reminder is better than a burst of them.
            $markSent->execute(['id' => $rideId]);

            $body = "Recolha: {$pickup} às {$time}";
            if ($client !== '') {
                $body = "{$client} · {$body}";
            }

            // Never let one driver's push failure starve the rest of the batch —
            // the ride is already claimed above, so we simply move on.
            try {
                FCMSender::sendToUser(
                    $driverId,
                    'Serviço em 30 minutos',
                    $body,
                    ['ride_id' => (string) $rideId]
                );
                $sent++;
            } catch (\Throwable $e) {
                error_log("[RideReminderJob] ride #{$rideId}: " . $e->getMessage());
            }
        }

        return "Sent {$sent} reminder(s).";
    }
}
