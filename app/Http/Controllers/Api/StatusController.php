<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LiveLocationRepository;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\TenantSettingsRepository;
use App\Services\FCMSender;
use App\Support\Database;
use App\Support\Session;

final class StatusController extends BaseController
{
    private ServiceRepository $services;
    private LogRepository     $logs;

    public function __construct()
    {
        // Drivers act on rides assigned to them (across every company they belong to);
        // admins act on rides scoped to their own company.
        $this->services = Session::role() === 2
            ? ServiceRepository::forDriverContext()
            : ServiceRepository::default();
        $this->logs     = LogRepository::default();
    }

    /** POST /api/status-update.php — supports JSON body or FormData. */
    public function update(): never
    {
        $this->cors();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $json   = $this->shieldedBody();
        $rideId = (int) ($json['ride_id'] ?? $_POST['ride_id'] ?? 0);
        $status = isset($json['status'])  ? (int) $json['status']  : (int) ($_POST['status'] ?? -1);

        if ($rideId === 0 || $status === -1) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        // A driver may only change the status of rides assigned to them
        if (Session::role() === 2 && $this->services->assignedDriver($rideId) !== Session::userId()) {
            $this->json(['success' => false, 'error' => 'Not your ride'], 403);
        }

        // Optional note the driver leaves (typically just before completing).
        // Sent base64 (note_b64) so free text never trips the prod WAF.
        $note = null;
        if (isset($_POST['note_b64'])) {
            $note = base64_decode((string) $_POST['note_b64'], true) ?: '';
        } elseif (isset($json['note']) || isset($_POST['note'])) {
            $note = (string) ($json['note'] ?? $_POST['note']);
        }
        if ($note !== null && Session::role() === 2) {
            $this->services->setDriverNote($rideId, $note);
        }

        $this->services->updateStatus($rideId, $status);

        // Where the driver was when this status was set — shown per step in the
        // trip report. Prefer the coords the app sent; otherwise fall back to the
        // ride's most recent live-tracking fix. Both may be null (nothing shown).
        [$lat, $lng] = $this->statusLocation($json, $rideId);

        $labels = [1 => 'On the way', 2 => 'At pickup', 5 => 'With client', 3 => 'Trip started', 4 => 'Completed'];
        $label  = $labels[$status] ?? "Status {$status}";
        $this->logs->record("Service ID #{$rideId}: status changed to {$label}", $lat, $lng);

        if ($status === 1 || $status === 3 || $status === 4) {
            $ride = $this->services->find($rideId);
            if ($ride !== null) {
                if ($status === 1 && $ride->clientPhone !== null) {
                    $settings = TenantSettingsRepository::default();
                    if ($settings->wppTrackEnabled()) {
                        $this->sendTrackingWhatsApp($rideId, $ride->clientPhone);
                    }
                }
                if ($status === 3) {
                    $this->sendPartnerDepartureWhatsApp($rideId);
                }
                if ($status === 4 && $ride->companyId !== null) {
                    $stmt = Database::connection()->prepare(
                        'SELECT u.name AS driver_name
                           FROM Services_Rides sr
                           JOIN Users u ON u.id = sr.UserID
                          WHERE sr.RideID = ? LIMIT 1'
                    );
                    $stmt->execute([$rideId]);
                    $driverName = (string) ($stmt->fetchColumn() ?: 'Condutor');

                    $d = \DateTime::createFromFormat('Y-m-d', $ride->date);
                    $t = \DateTime::createFromFormat('H:i:s', $ride->startTime)
                      ?: \DateTime::createFromFormat('H:i', $ride->startTime);
                    $dateStr  = $d ? $d->format('d/m') : $ride->date;
                    $timeStr  = $t ? $t->format('H:i') : $ride->startTime;
                    $client   = $ride->clientName ?? 'Cliente';
                    $origin   = $this->shortAddress($ride->pickupAddress);
                    $dest     = $this->shortAddress($ride->dropoffAddress);

                    FCMSender::sendToAdmins(
                        $ride->companyId,
                        '✅ Viagem concluída',
                        "{$driverName} · {$client}\n{$origin} → {$dest} · {$dateStr} {$timeStr}",
                        ['ride_id' => (string) $rideId]
                    );
                }
            }
        }

        $this->json(['success' => true, 'status' => $status]);
    }

    /** POST /api/driver-note.php — driver saves their note without changing status. */
    public function saveNote(): never
    {
        $this->cors();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $rideId = (int) ($_POST['ride_id'] ?? 0);
        if ($rideId === 0) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        // Drivers only — and only on rides assigned to them.
        if (Session::role() !== 2 || $this->services->assignedDriver($rideId) !== Session::userId()) {
            $this->json(['success' => false, 'error' => 'Not your ride'], 403);
        }

        $note = isset($_POST['note_b64']) ? (base64_decode((string) $_POST['note_b64'], true) ?: '') : '';
        $this->services->setDriverNote($rideId, $note);

        $this->json(['success' => true]);
    }

    /**
     * Resolve the GPS fix to attach to this status change.
     * @param array<string,mixed> $json decoded request body
     * @return array{0:?float,1:?float} [lat, lng]
     */
    private function statusLocation(array $json, int $rideId): array
    {
        // 1) Coords the app attached to the status update (most accurate — the
        //    exact spot at the moment of the tap).
        if (isset($json['lat'], $json['lng'])) {
            $lat = (float) $json['lat'];
            $lng = (float) $json['lng'];
            if ($lat !== 0.0 && $lng !== 0.0) {
                return [$lat, $lng];
            }
        }

        // 2) Fallback: the ride's latest live-tracking position.
        $tracking = LiveLocationRepository::default()->trackingFor($rideId);
        if ($tracking !== null && $tracking['latitude'] !== 0.0 && $tracking['longitude'] !== 0.0) {
            return [$tracking['latitude'], $tracking['longitude']];
        }

        return [null, null];
    }

    private function sendPartnerDepartureWhatsApp(int $rideId): void
    {
        $stmt = Database::connection()->prepare("
            SELECT s.NomeCliente, s.serviceTargetPoint, p.phone AS partner_phone
            FROM Services s
            LEFT JOIN Users p ON p.id = s.partner_id AND p.role = 3
            WHERE s.ID = ? AND s.partner_id IS NOT NULL AND p.phone IS NOT NULL
        ");
        $stmt->execute([$rideId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || empty($row['partner_phone'])) return;

        $client = mb_strtoupper((string) $row['NomeCliente']);
        $dest   = (string) $row['serviceTargetPoint'];

        $message  = "A sair do aeroporto 🛬\n";
        $message .= "Cliente: {$client}\n";
        $message .= "Destino: {$dest}";

        $this->wppSend($row['partner_phone'], $message);
    }

    private function sendTrackingWhatsApp(int $rideId, string $rawPhone): void
    {
        $trackUrl = 'https://syncride.wmservers.pt/SRMT/public/track.php?id=' . $rideId;
        $message  = "🇵🇹 Olá! O seu motorista está a caminho.\n";
        $message .= "Acompanhe a localização em tempo real:\n";
        $message .= $trackUrl . "\n\n";
        $message .= "🇬🇧 Hi! Your driver is on the way.\n";
        $message .= "Track their live location here:\n";
        $message .= $trackUrl;

        $this->wppSend($rawPhone, $message);
    }

    private function wppSend(string $rawPhone, string $message): void
    {
        $phone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (str_starts_with($phone, '351') && strlen($phone) > 9) {
            $phone = substr($phone, 3);
        }
        if ($phone === '') return;

        $port    = (string) (getenv('WPP_PORT') ?: '3001');
        $payload = json_encode([
            'to'      => '351' . $phone . '@s.whatsapp.net',
            'message' => $message,
        ]);

        $ch = curl_init("http://127.0.0.1:{$port}/send");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    private function shortAddress(string $addr): string
    {
        // Keep only the first meaningful segment (before comma or parenthesis)
        $short = preg_split('/[,(]/', $addr)[0] ?? $addr;
        $short = trim($short);
        return mb_strlen($short) > 28 ? mb_substr($short, 0, 26) . '…' : $short;
    }

    private function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json');
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(200); exit; }
    }
}
