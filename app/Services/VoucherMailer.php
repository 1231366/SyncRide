<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use PDO;

final class VoucherMailer
{
    /**
     * Partner rides  → TO: partner email only (no CC)
     * Normal rides   → TO: $agencyEmail + CC: $ccList + $myCopyEmail
     *
     * @param string[] $ccList
     */
    public function send(
        int     $tripId,
        string  $driverName,
        string  $serverPath,
        string  $fileName,
        ?string $lat,
        ?string $lng,
        string  $agencyEmail  = '',
        array   $ccList       = [],
        string  $myCopyEmail  = ''
    ): void {
        date_default_timezone_set('Europe/Lisbon');
        $timestamp = date('d/m/Y H:i');

        $trip    = $this->fetchTrip($tripId);
        $locHtml = $lat && $lng
            ? "<p>📍 <b>Location:</b> <a href='https://maps.google.com/maps?q={$lat},{$lng}' target='_blank'>View on Google Maps</a> <small>({$lat}, {$lng})</small></p>"
            : "<p>⚠️ Location not captured.</p>";

        $clientName  = htmlspecialchars((string) ($trip['NomeCliente']       ?? 'N/A'));
        $clientPhone = htmlspecialchars((string) ($trip['ClientNumber']      ?? 'N/A'));
        $serviceDate = htmlspecialchars((string) ($trip['serviceDate']       ?? 'N/A'));
        $serviceTime = htmlspecialchars((string) ($trip['serviceStartTime']  ?? 'N/A'));
        $origin      = htmlspecialchars((string) ($trip['serviceStartPoint'] ?? 'N/A'));
        $destination = htmlspecialchars((string) ($trip['serviceTargetPoint']?? 'N/A'));
        $safeDriver  = htmlspecialchars($driverName);

        $mail = Mailer::make();

        // Route primary TO; CCs only on agency rides (never sent to partner)
        $partnerEmail = (string) ($trip['partner_email'] ?? '');
        $partnerName  = (string) ($trip['partner_name']  ?? '');
        if ($partnerEmail !== '') {
            $mail->addAddress($partnerEmail, $partnerName);
        } elseif ($agencyEmail !== '') {
            $mail->addAddress($agencyEmail);
            foreach ($ccList as $cc) {
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc);
                }
            }
            if ($myCopyEmail !== '' && filter_var($myCopyEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($myCopyEmail);
            }
        } else {
            return; // nowhere to send
        }

        $mail->isHTML(true);
        $mail->Subject = "Voucher — Ride #{$tripId} — {$clientName}";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;color:#333'>
                <h2 style='color:#28a745'>Voucher Confirmation</h2>
                <hr>
                <p><b>Driver:</b> {$safeDriver}</p>
                <p><b>Recorded at:</b> {$timestamp}</p>
                <h3>Ride #{$tripId}</h3>
                <ul>
                    <li><b>Client:</b> {$clientName}</li>
                    <li><b>Contact:</b> {$clientPhone}</li>
                    <li><b>Date:</b> {$serviceDate} at {$serviceTime}</li>
                    <li><b>Route:</b> {$origin} &rarr; {$destination}</li>
                </ul>
                {$locHtml}
                <p>Photo attached.</p>
                <br><small style='color:#777'>SyncRide — Driver Module</small>
            </div>
        ";
        $mail->AltBody = "Voucher Ride #{$tripId}. Client: {$clientName}. Driver: {$safeDriver}.";
        $mail->addAttachment($serverPath, $fileName);
        $mail->send();
    }

    private function fetchTrip(int $id): array
    {
        $stmt = Database::connection()->prepare('
            SELECT s.serviceDate, s.serviceStartTime, s.NomeCliente, s.ClientNumber,
                   s.serviceStartPoint, s.serviceTargetPoint,
                   s.partner_id, u.email AS partner_email, u.name AS partner_name
            FROM Services s
            LEFT JOIN Users u ON s.partner_id = u.ID
            WHERE s.ID = :id
        ');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
