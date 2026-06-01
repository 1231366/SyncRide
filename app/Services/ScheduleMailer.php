<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
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
     * @param string[] $recipients   Configured via TenantSettings (schedule_recipient).
     * @param string   $myCopyEmail Admin self-copy (schedule_my_copy). Empty = disabled.
     * @param int|null $companyId   Scope services to this company; null = all companies.
     */
    public function sendForTomorrow(array $recipients = [], string $myCopyEmail = '', ?int $companyId = null): bool
    {
        $tomorrow    = (new \DateTimeImmutable('+1 day'))->format('Y-m-d');
        $displayDate = (new \DateTimeImmutable('+1 day'))->format('d/m/Y');
        $services    = $this->servicesForDate($tomorrow, $companyId);
        $subject     = "Planeamento SyncRide: {$displayDate} (" . count($services) . ' Servicos)';
        $body        = $this->renderHtml($displayDate, $services);

        $toList = $recipients !== [] ? $recipients : [];
        if ($myCopyEmail !== '' && filter_var($myCopyEmail, FILTER_VALIDATE_EMAIL) && !in_array($myCopyEmail, $toList, true)) {
            $toList[] = $myCopyEmail;
        }

        if ($toList === []) {
            return false;
        }

        try {
            $mail = Mailer::make();
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            foreach ($toList as $to) {
                if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($to);
                }
            }

            return $mail->send();
        } catch (\Throwable $e) {
            error_log('ScheduleMailer failed: ' . $e->getMessage());
            return false;
        }
    }

    /** @return array<array<string,mixed>> */
    private function servicesForDate(string $date, ?int $companyId = null): array
    {
        $companyClause = $companyId !== null ? 'AND s.company_id = :cid' : '';
        $stmt = $this->db->prepare("
            SELECT s.ID, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint,
                   s.NomeCliente, s.ClientNumber, s.paxADT, s.paxCHD, s.FlightNumber,
                   s.serviceType, u.name AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u           ON sr.UserID = u.id
            WHERE s.serviceDate = :d {$companyClause}
            ORDER BY s.serviceStartTime ASC
        ");
        $params = ['d' => $date];
        if ($companyId !== null) {
            $params['cid'] = $companyId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<array<string,mixed>> $services */
    private function renderHtml(string $displayDate, array $services): string
    {
        $total = count($services);
        $ts    = date('d/m/Y H:i');

        $cards = '';
        foreach ($services as $svc) {
            $hora      = substr((string) $svc['serviceStartTime'], 0, 5);
            $totalPax  = (int) $svc['paxADT'] + (int) $svc['paxCHD'];
            $paxLabel  = $totalPax . ' <small>(' . (int)$svc['paxADT'] . 'A+' . (int)$svc['paxCHD'] . 'C)</small>';
            $name      = mb_strtoupper($this->safe($svc['NomeCliente']));
            $rideId    = (int) $svc['ID'];
            $from      = $this->safe($svc['serviceStartPoint']);
            $to        = $this->safe($svc['serviceTargetPoint']);

            $type = (int) ($svc['serviceType'] ?? 0);
            if ($type === 1) {
                $tipoLabel = 'Chegada'; $tipoColor = '#3182ce';
            } elseif ($type === 2) {
                $tipoLabel = 'Partida'; $tipoColor = '#d69e2e';
            } else {
                $tipoLabel = 'Servico'; $tipoColor = '#6c757d';
            }

            $metaLine = 'ID #' . $rideId . ' &bull; &#128101; ' . $paxLabel;
            if (!empty($svc['FlightNumber'])) {
                $metaLine .= ' &bull; &#9992; ' . $this->safe($svc['FlightNumber']);
            }

            if ($svc['driverName'] !== null) {
                $driverHtml = '<span style="color:#48bb78;font-weight:bold;">&#10003; ' . $this->safe($svc['driverName']) . '</span>';
            } else {
                $driverHtml = '<span style="background:#e53e3e;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;">POR ATRIBUIR</span>';
            }

            $cards .= '
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:10px;padding:15px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60" style="vertical-align:middle;border-right:2px solid #edf2f7;padding-right:12px;">
                    <div style="font-size:18px;font-weight:800;color:#1a202c;">' . $hora . '</div>
                    <div style="font-size:9px;color:' . $tipoColor . ';text-transform:uppercase;font-weight:700;margin-top:2px;">' . $tipoLabel . '</div>
                </td>
                <td style="padding-left:15px;vertical-align:middle;">
                    <div style="font-size:13px;font-weight:700;color:#2d3748;">' . $name . '</div>
                    <div style="font-size:11px;color:#718096;margin-top:3px;">' . $metaLine . '</div>
                </td>
                <td width="120" style="text-align:right;vertical-align:middle;">
                    <div style="font-size:11px;">' . $driverHtml . '</div>
                </td>
            </tr>
        </table>
        <div style="margin-top:10px;font-size:11px;color:#4a5568;background:#f7fafc;padding:8px;border-radius:6px;">
            <b>DE:</b> ' . $from . '<br>
            <b>PARA:</b> ' . $to . '
        </div>
    </div>';
        }

        if ($cards === '') {
            $cards = '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;text-align:center;color:#a0aec0;font-size:13px;">Sem servicos agendados para esta data.</div>';
        }

        return '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>SyncRide &mdash; Planeamento</title></head>
<body style="margin:0;padding:0;background-color:#f4f7f9;font-family:Arial,sans-serif;">
<div style="max-width:600px;margin:0 auto;padding:30px 10px;">

    <div style="background:#1a202c;border-radius:16px 16px 0 0;padding:20px;text-align:center;">
        <span style="color:#fff;font-size:20px;font-weight:800;">SyncRide<span style="color:#48bb78;">.</span></span>
        <div style="color:#a0aec0;font-size:10px;text-transform:uppercase;letter-spacing:2px;margin-top:5px;">Planeamento ' . $displayDate . '</div>
    </div>

    <div style="background:#fff;padding:15px;border-bottom:1px solid #edf2f7;text-align:center;">
        <div style="font-size:13px;color:#4a5568;">
            Total de <strong>' . $total . '</strong> servicos agendados
        </div>
    </div>

    <div style="padding:15px 0;">
        ' . $cards . '
    </div>

    <div style="text-align:center;padding:10px;font-size:10px;color:#cbd5e0;">
        Gerado em ' . $ts . '<br>SYNCRIDE PORTUGAL ECOSYSTEM
    </div>

</div>
</body>
</html>';
    }

    private function safe(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
