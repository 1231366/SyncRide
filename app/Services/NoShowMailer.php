<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Partner rides  → TO: partner email + CC: $ccAlways
 * Normal rides   → TO: $agencyEmail  + CC: $ccAlways + $ccList + $myCopyEmail
 *
 * $ccAlways is the "always" CC list (e.g. the operator's own inbox) sent on every
 * alert; $ccList is the agency-only CC list (e.g. the agency's internal recipients).
 */
final class NoShowMailer
{
    /**
     * @param string[] $ccList    Agency-only CC list from settings.
     * @param string[] $ccAlways  CC list applied to both partner and agency rides.
     */
    public function send(
        int     $tripId,
        array   $tripData,
        string  $serverPath,
        ?string $lat,
        ?string $lng,
        string  $agencyEmail  = '',
        array   $ccList       = [],
        string  $myCopyEmail  = '',
        ?string $reportPath   = null,
        array   $ccAlways     = []
    ): void {
        date_default_timezone_set('Europe/Lisbon');
        $timestamp   = date('d/m/Y H:i');
        $companyName = (string) ($tripData['company_name'] ?? '');

        $locationHtml = '';
        if ($lat && $lng) {
            $mapLink      = "https://www.google.com/maps?q={$lat},{$lng}";
            $locationHtml = "
                <div style='margin-top:20px;padding:15px;background:#fff5f5;border-radius:10px;border:1px solid #feb2b2;'>
                    <div style='font-size:10px;color:#c53030;text-transform:uppercase;font-weight:bold;'>GPS Location of Report</div>
                    <a href='{$mapLink}' target='_blank' style='color:#2d3748;text-decoration:none;font-size:13px;font-weight:bold;'>
                        📍 Open in Google Maps ({$lat}, {$lng})
                    </a>
                </div>";
        }

        $clientName = strtoupper((string) ($tripData['NomeCliente']       ?? 'N/A'));
        $pickup     = htmlspecialchars((string) ($tripData['serviceStartPoint'] ?? ''));
        $dropoff    = htmlspecialchars((string) ($tripData['serviceTargetPoint'] ?? ''));

        $mail = Mailer::make();

        // The "always" CC list goes out on every alert (partner and agency alike).
        $addAlwaysCc = static function () use ($mail, $ccAlways): void {
            foreach ($ccAlways as $cc) {
                if (filter_var($cc, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc);
                }
            }
        };

        $partnerEmail = (string) ($tripData['partner_email'] ?? '');
        $partnerName  = (string) ($tripData['partner_name']  ?? '');
        if (!empty($tripData['partner_id']) && $partnerEmail !== '') {
            // Partner ride → partner (TO) + always-CC only (no agency/MTS recipients)
            $mail->addAddress($partnerEmail, $partnerName);
            $addAlwaysCc();
        } elseif ($agencyEmail !== '') {
            // Agency ride → agency (TO) + always-CC + agency-only CC + my copy
            $mail->addAddress($agencyEmail);
            $addAlwaysCc();
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

        $mail->addEmbeddedImage($serverPath, 'foto_noshow');
        if ($reportPath !== null && file_exists($reportPath)) {
            $reportName = 'NoShow-Report-' . $tripId . '.pdf';
            $mail->addAttachment($reportPath, $reportName);
        }
        $mail->isHTML(true);
        $subjectCompany = $companyName !== '' ? "[{$companyName}] " : '';
        $mail->Subject = "{$subjectCompany}No-Show Reported: Ride #{$tripId}";
        $companyBadge  = $companyName !== ''
            ? "<div style='font-size:11px;text-transform:uppercase;opacity:0.85;margin-top:4px;letter-spacing:1px;'>{$companyName}</div>"
            : '';
        $mail->Body    = "
        <div style='background-color:#f0f4f8;padding:50px 20px;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;'>
            <div style='max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.1);border:1px solid #e2e8f0;'>
                <div style='background-color:#2563eb;padding:30px;color:#ffffff;'>
                    <div style='font-size:24px;font-weight:800;letter-spacing:-1px;display:inline-block;'>SyncRide<span style='color:rgba(255,255,255,0.7);font-weight:400;'> OS</span></div>
                    {$companyBadge}
                    <div style='float:right;text-align:right;'>
                        <div style='font-size:10px;text-transform:uppercase;opacity:0.9;'>Status</div>
                        <div style='font-size:14px;font-weight:bold;background:#dc2626;display:inline-block;padding:2px 10px;border-radius:6px;margin-top:2px;'>NO-SHOW</div>
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
                    <div style='background-color:#f7fafc;border-radius:15px;padding:25px;margin-bottom:30px;border:1px dashed #cbd5e0;'>
                        <div style='display:flex;align-items:center;justify-content:space-between;'>
                            <div><div style='font-size:24px;font-weight:900;color:#1a202c;'>PICK</div><div style='font-size:12px;color:#718096;max-width:150px;'>{$pickup}</div></div>
                            <div style='font-size:24px;color:#e53e3e;'>✕</div>
                            <div style='text-align:right;'><div style='font-size:24px;font-weight:900;color:#1a202c;'>DROP</div><div style='font-size:12px;color:#718096;max-width:150px;'>{$dropoff}</div></div>
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
        </div>";

        $mail->send();
    }
}
