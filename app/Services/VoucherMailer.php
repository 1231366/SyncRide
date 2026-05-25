<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends a voucher confirmation email with the photo taken by the driver.
 */
final class VoucherMailer
{
    public function send(
        int    $tripId,
        string $driverName,
        string $serverPath,
        string $fileName,
        ?string $lat,
        ?string $lng
    ): void {
        date_default_timezone_set('Europe/Lisbon');
        $timestamp = date('d/m/Y H:i');

        $trip     = $this->fetchTrip($tripId);
        $locHtml  = $lat && $lng
            ? "<p>📍 <b>Location:</b> <a href='https://maps.google.com/maps?q={$lat},{$lng}' target='_blank'>View on Google Maps</a> <small>({$lat}, {$lng})</small></p>"
            : "<p>⚠️ Location not captured.</p>";

        $clientName   = htmlspecialchars((string) ($trip['NomeCliente']        ?? 'N/A'));
        $clientPhone  = htmlspecialchars((string) ($trip['ClientNumber']       ?? 'N/A'));
        $serviceDate  = htmlspecialchars((string) ($trip['serviceDate']        ?? 'N/A'));
        $serviceTime  = htmlspecialchars((string) ($trip['serviceStartTime']   ?? 'N/A'));
        $origin       = htmlspecialchars((string) ($trip['serviceStartPoint']  ?? 'N/A'));
        $destination  = htmlspecialchars((string) ($trip['serviceTargetPoint'] ?? 'N/A'));
        $safeDriver   = htmlspecialchars($driverName);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'cloud865.thundercloud.uk';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@syncride.wmservers.pt';
        $mail->Password   = (string) (getenv('MAIL_PASSWORD') ?: '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('no-reply@syncride.wmservers.pt', 'SyncRide Vouchers');
        $mail->addAddress('flexewar@gmail.com');

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
        $stmt = Database::connection()->prepare(
            'SELECT serviceDate, serviceStartTime, NomeCliente, ClientNumber,
                    serviceStartPoint, serviceTargetPoint
             FROM Services WHERE ID = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
