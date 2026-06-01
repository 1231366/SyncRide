<?php

declare(strict_types=1);

namespace Cron\Jobs;

use App\Support\Database;
use Cron\CronJob;
use PDO;

/**
 * Sends each driver tomorrow's service agenda via WhatsApp (Whapi gateway).
 *
 * Hardcoded to company_id = 1 — the only tenant using this automation.
 */
final class WhatsappAgendaJob implements CronJob
{
    private const COMPANY_ID = 1;

    // Driver IDs sent first regardless of ride count.
    private const VIP_DRIVER_IDS = [28, 37, 49, 39, 42];

    public function name(): string
    {
        return 'whatsapp-agenda';
    }

    public function description(): string
    {
        return "Notifies drivers of tomorrow's agenda via WhatsApp (company 1 only).";
    }

    public function run(): string
    {
        $apiToken = (string) (getenv('WHATSAPP_API_TOKEN') ?: '');
        $apiUrl   = (string) (getenv('WHATSAPP_API_URL') ?: 'https://gate.whapi.cloud/messages/text');

        if ($apiToken === '') {
            return 'whatsapp-agenda: WHATSAPP_API_TOKEN missing — skipped';
        }

        $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');
        $dataPt   = (new \DateTimeImmutable('tomorrow'))->format('d/m/Y');

        $rides = $this->fetchRides($tomorrow);

        if ($rides === []) {
            return 'whatsapp-agenda: no rides for tomorrow — nothing sent';
        }

        $byDriver = $this->groupByPhone($rides);
        $byDriver = $this->sortVipsFirst($byDriver);

        $sent   = 0;
        $failed = 0;

        foreach ($byDriver as $phone => $driverRides) {
            $msg = $this->buildMessage($driverRides, $dataPt);
            $ok  = $this->sendWhatsapp($phone, $msg, $apiToken, $apiUrl);
            $ok ? $sent++ : $failed++;

            if ($sent + $failed < count($byDriver)) {
                usleep(1_500_000); // 1.5 s between messages — respect Whapi rate limits
            }
        }

        return "whatsapp-agenda: sent={$sent} failed={$failed}";
    }

    /** @return array<array<string,mixed>> */
    private function fetchRides(string $date): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                u.id   AS UserID,
                u.phone,
                u.name AS NomeCondutor,
                s.ID   AS ServiceID,
                s.serviceStartTime,
                s.serviceStartPoint,
                s.serviceTargetPoint,
                s.NomeCliente,
                s.FlightNumber
            FROM Services s
            JOIN Services_Rides sr ON s.ID = sr.RideID
            JOIN Users u           ON sr.UserID = u.id
            WHERE s.serviceDate  = :d
              AND s.company_id   = :cid
              AND u.role         = 2
              AND u.phone IS NOT NULL
            ORDER BY s.serviceStartTime ASC
        ");
        $stmt->execute(['d' => $date, 'cid' => self::COMPANY_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Group rides by normalised PT phone number (strip leading 351).
     *
     * @param  array<array<string,mixed>> $rides
     * @return array<string, array<array<string,mixed>>>
     */
    private function groupByPhone(array $rides): array
    {
        $grouped = [];
        foreach ($rides as $ride) {
            $raw = preg_replace('/[^0-9]/', '', (string) $ride['phone']);
            if (str_starts_with($raw, '351') && strlen($raw) > 9) {
                $raw = substr($raw, 3);
            }
            $grouped[$raw][] = $ride;
        }
        return $grouped;
    }

    /**
     * Move VIP drivers to the front, then sort remaining by ride count desc.
     *
     * @param  array<string, array<array<string,mixed>>> $byDriver
     * @return array<string, array<array<string,mixed>>>
     */
    private function sortVipsFirst(array $byDriver): array
    {
        uasort($byDriver, function (array $a, array $b): int {
            $aVip = !empty(array_intersect(array_column($a, 'UserID'), self::VIP_DRIVER_IDS));
            $bVip = !empty(array_intersect(array_column($b, 'UserID'), self::VIP_DRIVER_IDS));

            if ($aVip !== $bVip) {
                return $aVip ? -1 : 1;
            }
            return count($b) <=> count($a);
        });
        return $byDriver;
    }

    /** @param array<array<string,mixed>> $rides */
    private function buildMessage(array $rides, string $dataPt): string
    {
        $firstName = explode(' ', (string) $rides[0]['NomeCondutor'])[0];

        $msg  = "Olá, *{$firstName}*! 👋\n";
        $msg .= "Aqui tens a tua agenda de serviços para amanhã, *{$dataPt}*:\n";
        $msg .= "------------------------------------------\n\n";

        foreach ($rides as $r) {
            $hora = substr((string) $r['serviceStartTime'], 0, 5);
            $voo  = !empty($r['FlightNumber']) ? ' (✈️ ' . $r['FlightNumber'] . ')' : '';

            $msg .= "⏰ *{$hora}* | Viagem #{$r['ServiceID']}\n";
            $msg .= '👤 ' . mb_strtoupper((string) $r['NomeCliente']) . "{$voo}\n";
            $msg .= "📍 *De:* {$r['serviceStartPoint']}\n";
            $msg .= "🏁 *Para:* {$r['serviceTargetPoint']}\n";
            $msg .= "------------------------------------------\n\n";
        }

        $msg .= 'Bom trabalho e conduz com cuidado! 🚀';
        return $msg;
    }

    private function sendWhatsapp(string $phone, string $body, string $token, string $url): bool
    {
        $payload = [
            'typing_time' => 0,
            'to'          => '351' . $phone . '@s.whatsapp.net',
            'body'        => $body,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response !== false && $httpCode >= 200 && $httpCode < 300;
    }
}
