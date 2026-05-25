<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\TenantSettingsRepository;
use PHPMailer\PHPMailer\PHPMailer;

final class TripReportController extends BaseController
{
    private ServiceRepository        $services;
    private LogRepository            $logs;
    private TenantSettingsRepository $tenantSettings;

    public function __construct()
    {
        $this->services        = ServiceRepository::default();
        $this->logs            = LogRepository::default();
        $this->tenantSettings  = TenantSettingsRepository::default();
    }

    /** GET /api/final-trip-report.php?ride_id=N */
    public function send(): never
    {
        header('Content-Type: application/json');
        ini_set('display_errors', '0');

        if (!$this->tenantSettings->tripReportEnabled()) {
            $this->json(['success' => false, 'message' => 'Trip reports are disabled for this tenant.']);
        }

        $rideId = (int) ($_GET['ride_id'] ?? 0);
        if ($rideId === 0) {
            $this->json(['success' => false, 'message' => 'ride_id missing']);
        }

        $ride = $this->services->findWithPartner($rideId);
        if ($ride === null) {
            $this->json(['success' => false, 'message' => 'Ride not found']);
        }

        $rideLogRows = $this->logs->forRide($rideId);
        $logsHtml    = $this->buildLogsHtml($rideLogRows, $rideId);

        try {
            $this->sendEmail($rideId, $ride, $logsHtml);
            $this->json(['success' => true, 'message' => 'Report sent.']);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /** @param array<array<string,mixed>> $logRows */
    private function buildLogsHtml(array $logRows, int $rideId): string
    {
        if ($logRows === []) {
            return "<p style='text-align:center;color:#a0aec0;font-size:12px;'>Detailed history unavailable.</p>";
        }
        $html = '';
        foreach ($logRows as $l) {
            $time   = date('H:i:s', strtotime((string) $l['date']));
            $action = str_replace("Service ID #{$rideId}: ", '', (string) $l['Action']);
            $html  .= "<div style='display:flex;margin-bottom:10px;border-left:2px solid #e0e0e0;padding-left:15px;'>"
                    . "<div style='min-width:70px;font-weight:bold;color:#1a202c;font-size:12px;'>{$time}</div>"
                    . "<div style='color:#4a5568;font-size:12px;'>{$action}</div></div>";
        }
        return $html;
    }

    private function sendEmail(int $rideId, array $ride, string $logsHtml): void
    {
        $baseDir = dirname(__DIR__, 4) . '/vendor/phpmailer/PHPMailer/';
        require_once $baseDir . 'Exception.php';
        require_once $baseDir . 'PHPMailer.php';
        require_once $baseDir . 'SMTP.php';

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

        if (!empty($ride['partner_id']) && !empty($ride['partner_email'])) {
            $mail->addAddress((string) $ride['partner_email'], (string) $ride['partner_name']);
        } else {
            $mail->addAddress('transfers.pt@mtsglobe.com');
        }

        foreach ($this->tenantSettings->tripReportRecipients() as $ccEmail) {
            if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($ccEmail);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = "Trip #{$rideId} Completed";
        $mail->Body    = $this->buildEmailBody($rideId, $ride, $logsHtml);
        $mail->send();
    }

    private function buildEmailBody(int $rideId, array $ride, string $logsHtml): string
    {
        $client  = mb_strtoupper((string) ($ride['NomeCliente'] ?? ''));
        $dateStr = date('d M Y', strtotime((string) ($ride['serviceDate'] ?? '')));
        $pickup  = htmlspecialchars((string) ($ride['serviceStartPoint'] ?? ''), ENT_QUOTES, 'UTF-8');
        $dropoff = htmlspecialchars((string) ($ride['serviceTargetPoint'] ?? ''), ENT_QUOTES, 'UTF-8');
        $ts      = date('H:i:s d/m/Y');

        return <<<HTML
<div style="background:#f0f4f8;padding:50px 20px;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
  <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,.1);border:1px solid #e2e8f0;">
    <div style="background:#000;padding:30px;color:#fff;display:flex;justify-content:space-between;align-items:center;">
      <div style="font-size:24px;font-weight:800;letter-spacing:-1px;">SyncRide<span style="color:#48bb78;">.</span></div>
      <div style="text-align:right;"><div style="font-size:10px;text-transform:uppercase;opacity:.6;">Status</div><div style="font-size:14px;font-weight:bold;color:#48bb78;">COMPLETED</div></div>
    </div>
    <div style="padding:40px;">
      <table style="width:100%;margin-bottom:40px;">
        <tr>
          <td style="width:50%;vertical-align:top;"><div style="font-size:10px;color:#a0aec0;text-transform:uppercase;margin-bottom:5px;font-weight:700;">Passenger</div><div style="font-size:18px;font-weight:bold;color:#2d3748;">{$client}</div></td>
          <td style="width:50%;vertical-align:top;text-align:right;"><div style="font-size:10px;color:#a0aec0;text-transform:uppercase;margin-bottom:5px;font-weight:700;">Service Date</div><div style="font-size:18px;font-weight:bold;color:#2d3748;">{$dateStr}</div></td>
        </tr>
      </table>
      <div style="background:#f7fafc;border-radius:15px;padding:25px;display:flex;align-items:center;justify-content:space-between;margin-bottom:40px;border:1px dashed #cbd5e0;">
        <div><div style="font-size:24px;font-weight:900;color:#1a202c;">PICK</div><div style="font-size:12px;color:#718096;max-width:150px;">{$pickup}</div></div>
        <div style="font-size:24px;color:#cbd5e0;">✈</div>
        <div style="text-align:right;"><div style="font-size:24px;font-weight:900;color:#1a202c;">DROP</div><div style="font-size:12px;color:#718096;max-width:150px;">{$dropoff}</div></div>
      </div>
      <div style="margin-top:20px;">
        <h3 style="font-size:14px;text-transform:uppercase;letter-spacing:1px;color:#a0aec0;margin-bottom:20px;">Activity Timeline</h3>
        {$logsHtml}
      </div>
    </div>
    <div style="background:#f8fafc;padding:20px;text-align:center;border-top:1px solid #edf2f7;">
      <div style="font-size:24px;color:#1a202c;margin-bottom:10px;font-weight:bold;">#{$rideId}</div>
      <div style="font-size:10px;color:#a0aec0;letter-spacing:2px;">SYNCRIDE PORTUGAL ECOSYSTEM</div>
    </div>
  </div>
  <div style="text-align:center;margin-top:20px;color:#a0aec0;font-size:11px;">Generated at {$ts}</div>
</div>
HTML;
    }
}
