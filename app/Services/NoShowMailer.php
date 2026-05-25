<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends the no-show incident email to the relevant parties.
 *
 * For partner rides: the partner + internal address.
 * For agency rides:  MTS Globe contacts + internal address.
 */
final class NoShowMailer
{
    public function send(
        int    $tripId,
        array  $tripData,
        string $serverPath,
        ?string $lat,
        ?string $lng
    ): void {
        date_default_timezone_set('Europe/Lisbon');
        $timestamp = date('d/m/Y H:i');

        $locationHtml = '';
        if ($lat && $lng) {
            $mapLink = "https://www.google.com/maps?q={$lat},{$lng}";
            $locationHtml = "
                <div style='margin-top:20px;padding:15px;background:#fff5f5;border-radius:10px;border:1px solid #feb2b2;'>
                    <div style='font-size:10px;color:#c53030;text-transform:uppercase;font-weight:bold;'>GPS Location of Report</div>
                    <a href='{$mapLink}' target='_blank' style='color:#2d3748;text-decoration:none;font-size:13px;font-weight:bold;'>
                        📍 Open in Google Maps ({$lat}, {$lng})
                    </a>
                </div>";
        }

        $clientName = strtoupper((string) ($tripData['NomeCliente'] ?? 'N/A'));
        $pickup     = htmlspecialchars((string) ($tripData['serviceStartPoint'] ?? ''));
        $dropoff    = htmlspecialchars((string) ($tripData['serviceTargetPoint'] ?? ''));

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'cloud865.thundercloud.uk';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@syncride.wmservers.pt';
        $mail->Password   = (string) (getenv('MAIL_PASSWORD') ?: '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('no-reply@syncride.wmservers.pt', 'SyncRide Alerts');

        if (!empty($tripData['partner_id']) && !empty($tripData['partner_email'])) {
            $mail->addAddress((string) $tripData['partner_email'], (string) $tripData['partner_name']);
            $mail->addAddress('flexewar@gmail.com');
        } else {
            $mail->addAddress('transfers.pt@mtsglobe.com');
            $mail->addAddress('joao.teixeira@mtsglobe.com');
            $mail->addAddress('complaints.pt@mtsglobe.com');
            $mail->addAddress('flexewar@gmail.com');
        }

        $mail->addEmbeddedImage($serverPath, 'foto_noshow');
        $mail->isHTML(true);
        $mail->Subject = "No-Show Reported: Ride #{$tripId}";
        $mail->Body    = "
        <div style='background-color:#f0f4f8;padding:50px 20px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;'>
            <div style='max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.1);border:1px solid #e2e8f0;'>
                <div style='background-color:#e53e3e;padding:30px;color:#ffffff;'>
                    <div style='font-size:24px;font-weight:800;letter-spacing:-1px;display:inline-block;'>SyncRide<span style='color:#ffffff;'>.</span></div>
                    <div style='float:right;text-align:right;'>
                        <div style='font-size:10px;text-transform:uppercase;opacity:0.9;'>Status</div>
                        <div style='font-size:14px;font-weight:bold;'>NO-SHOW REPORTED</div>
                    </div>
                </div>
                <div style='padding:40px;'>
                    <table style='width:100%;margin-bottom:40px;'>
                        <tr>
                            <td style='width:50%;vertical-align:top;'>
                                <div style='font-size:10px;color:#a0aec0;text-transform:uppercase;margin-bottom:5px;font-weight:700;'>Passenger</div>
                                <div style='font-size:18px;font-weight:bold;color:#2d3748;'>{$clientName}</div>
                            </td>
                            <td style='width:50%;vertical-align:top;text-align:right;'>
                                <div style='font-size:10px;color:#a0aec0;text-transform:uppercase;margin-bottom:5px;font-weight:700;'>Incident Time</div>
                                <div style='font-size:18px;font-weight:bold;color:#2d3748;'>{$timestamp}</div>
                            </td>
                        </tr>
                    </table>
                    <div style='background-color:#f7fafc;border-radius:15px;padding:25px;display:flex;align-items:center;justify-content:space-between;margin-bottom:30px;border:1px dashed #cbd5e0;'>
                        <div style='text-align:left;'>
                            <div style='font-size:24px;font-weight:900;color:#1a202c;'>PICK</div>
                            <div style='font-size:12px;color:#718096;max-width:150px;'>{$pickup}</div>
                        </div>
                        <div style='font-size:24px;color:#e53e3e;'>✕</div>
                        <div style='text-align:right;'>
                            <div style='font-size:24px;font-weight:900;color:#1a202c;'>DROP</div>
                            <div style='font-size:12px;color:#718096;max-width:150px;'>{$dropoff}</div>
                        </div>
                    </div>
                    <div style='text-align:center;margin-bottom:25px;'>
                        <div style='font-size:10px;color:#a0aec0;text-transform:uppercase;margin-bottom:10px;font-weight:700;'>Photo Evidence</div>
                        <img src='cid:foto_noshow' style='width:100%;max-width:520px;border-radius:15px;border:4px solid #ffffff;box-shadow:0 4px 12px rgba(0,0,0,0.1);'>
                    </div>
                    {$locationHtml}
                </div>
                <div style='background-color:#f8fafc;padding:20px;text-align:center;border-top:1px solid #edf2f7;'>
                    <div style='font-size:24px;color:#1a202c;margin-bottom:10px;font-weight:bold;'>Ride ID #{$tripId}</div>
                    <div style='font-size:10px;color:#a0aec0;letter-spacing:2px;'>SYNCRIDE PORTUGAL ECOSYSTEM</div>
                </div>
            </div>
            <div style='text-align:center;margin-top:20px;color:#a0aec0;font-size:11px;'>
                Sent automatically by the SyncRide security system.
            </div>
        </div>";

        $mail->send();
    }
}
