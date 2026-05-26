<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use App\Support\Env;
use PDO;

/**
 * Emails tomorrow's operations schedule (driver assignments) to the
 * operations inbox. Triggered manually by an admin from the schedule
 * action; the cron variant lives in Cron\Jobs\DailyReportJob.
 */
final class ScheduleMailer
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /**
     * @param string[] $recipients  Configured via TenantSettings (schedule_recipient).
     *                              Falls back to MAIL_FROM_ADDRESS env var if empty.
     */
    public function sendForTomorrow(array $recipients = []): bool
    {
        $tomorrow    = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');
        $displayDate = (new \DateTimeImmutable('+1 day'))->format('d/m/Y');
        $services    = $this->servicesForDate($tomorrow);
        $subject     = "SyncRide schedule — {$displayDate}";
        $body        = $this->renderHtml($displayDate, $services);

        $fallback    = (string) (Env::get('MAIL_FROM_ADDRESS') ?: '');
        $toList      = $recipients !== [] ? $recipients : ($fallback !== '' ? [$fallback] : []);

        if ($toList === []) {
            return false;
        }

        $fromAddress = (string) (Env::get('MAIL_FROM_ADDRESS') ?: 'no-reply@syncride.wmservers.pt');
        $headers  = "From: {$fromAddress}\r\n";
        $headers .= "Reply-To: {$fromAddress}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $allOk = true;
        foreach ($toList as $to) {
            if (!mail($to, $subject, $body, $headers)) {
                $allOk = false;
            }
        }
        return $allOk;
    }

    /** @return array<array<string,mixed>> */
    private function servicesForDate(string $date): array
    {
        $stmt = $this->db->prepare('
            SELECT s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint,
                   s.NomeCliente, s.ClientNumber, s.paxADT, s.paxCHD, s.FlightNumber,
                   u.name AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u           ON sr.UserID = u.id
            WHERE s.serviceDate = :d
            ORDER BY s.serviceStartTime ASC
        ');
        $stmt->execute(['d' => $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<array<string,mixed>> $services */
    private function renderHtml(string $displayDate, array $services): string
    {
        $rows = '';
        if ($services === []) {
            $rows = '<p>No rides scheduled for this date.</p>';
        } else {
            $rows = '<table style="border-collapse:collapse;width:100%;margin-top:15px;">'
                  . '<thead><tr>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">Time</th>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">Route</th>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">Client</th>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">PAX</th>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">Flight</th>'
                  . '<th style="background:#007bff;color:#fff;padding:8px;border:1px solid #ddd;text-align:left;">Driver</th>'
                  . '</tr></thead><tbody>';

            foreach ($services as $service) {
                $time     = date('H:i', strtotime((string) $service['serviceStartTime']));
                $route    = $this->safe($service['serviceStartPoint']) . ' → ' . $this->safe($service['serviceTargetPoint']);
                $client   = $this->safe($service['NomeCliente']) . ' (' . $this->safe($service['ClientNumber']) . ')';
                $pax      = (int) $service['paxADT'] . '/' . (int) $service['paxCHD'];
                $flight   = $service['FlightNumber'] !== null && $service['FlightNumber'] !== '' ? $this->safe($service['FlightNumber']) : '—';
                $driver   = $service['driverName'] !== null ? $this->safe($service['driverName']) : '<span style="color:red;">UNASSIGNED</span>';

                $rows .= "<tr>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$time}</td>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$route}</td>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$client}</td>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$pax}</td>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$flight}</td>"
                       . "<td style='padding:8px;border:1px solid #ddd;'>{$driver}</td>"
                       . "</tr>";
            }
            $rows .= '</tbody></table>';
        }

        return <<<HTML
            <!DOCTYPE html>
            <html><head><meta charset="utf-8"><title>SyncRide schedule</title></head>
            <body style="font-family:Arial,sans-serif;color:#333;">
                <h3>📅 Service schedule: {$displayDate}</h3>
                {$rows}
            </body></html>
        HTML;
    }

    private function safe(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
