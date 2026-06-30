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

        // Rides starting in [now+29min, now+31min] with an assigned driver
        $stmt = $pdo->prepare("
            SELECT s.ID AS ride_id, sr.UserID AS driver_id,
                   s.serviceDate, s.serviceStartTime, s.serviceStartPoint
              FROM Services s
              JOIN Services_Rides sr ON sr.RideID = s.ID
             WHERE s.status_id IN (0, NULL)
               AND TIMESTAMP(s.serviceDate, s.serviceStartTime) BETWEEN
                   DATE_ADD(NOW(), INTERVAL 28 MINUTE) AND
                   DATE_ADD(NOW(), INTERVAL 33 MINUTE)
        ");
        $stmt->execute();
        $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sent = 0;
        foreach ($rides as $row) {
            $driverId = (int) $row['driver_id'];
            $rideId   = (int) $row['ride_id'];
            $time     = substr((string) $row['serviceStartTime'], 0, 5);
            $pickup   = (string) $row['serviceStartPoint'];

            FCMSender::sendToUser(
                $driverId,
                'Serviço em 30 minutos',
                "Pickup: {$pickup} às {$time}",
                ['ride_id' => (string) $rideId]
            );
            $sent++;
        }

        return "Sent {$sent} reminder(s).";
    }
}
