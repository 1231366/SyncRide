<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class RidesController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
    }

    /** GET /partner/api-rides.php?status=pendente|aprovado|rejeitado */
    public function data(): never
    {
        $partnerId = (int) ($_SESSION['user_id'] ?? 0);
        $status    = in_array($_GET['status'] ?? '', ['pendente', 'aprovado', 'rejeitado'], true)
            ? $_GET['status']
            : 'pendente';

        $rows = $this->services->partnerRidesByStatus($partnerId, $status);
        $data = array_map(static fn(array $r): array => [
            'data_hora' => date('d/m/Y H:i', strtotime($r['serviceDate'] . ' ' . $r['serviceStartTime'])),
            'cliente'   => htmlspecialchars((string) ($r['NomeCliente'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'has_key'   => (int) ($r['has_key'] ?? 0),
            'rota'      => '<div class="d-flex flex-column text-start small">'
                . '<span class="text-truncate" style="max-width:150px"><i class="bi bi-geo-alt-fill text-success me-1"></i>' . htmlspecialchars((string) ($r['serviceStartPoint'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>'
                . '<span class="text-truncate" style="max-width:150px"><i class="bi bi-pin-map-fill text-danger me-1"></i>' . htmlspecialchars((string) ($r['serviceTargetPoint'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span></div>',
            'voo'       => !empty($r['FlightNumber'])
                ? '<span class="badge bg-light text-dark border">' . htmlspecialchars((string) $r['FlightNumber'], ENT_QUOTES, 'UTF-8') . '</span>'
                : '-',
            'pax'       => '<i class="bi bi-people-fill text-muted me-1"></i> ' . ((int)($r['paxADT'] ?? 0) + (int)($r['paxCHD'] ?? 0)),
            'status'    => ucfirst((string) ($r['status_pedido'] ?? '')),
        ], $rows);

        $this->json(['data' => $data]);
    }

    /** POST /partner/api-update-ride.php */
    public function update(): never
    {
        $partnerId = (int) ($_SESSION['user_id'] ?? 0);
        $rideId    = (int) ($_POST['ride_id'] ?? 0);

        if ($rideId <= 0) {
            $this->json(['success' => false, 'error' => 'Missing ride id.'], 422);
        }

        $date = trim((string) ($_POST['date'] ?? ''));
        $time = trim((string) ($_POST['time'] ?? ''));
        if ($date === '' || $time === '') {
            $this->json(['success' => false, 'error' => 'Date and time are required.'], 422);
        }

        $ok = $this->services->updateForPartner($partnerId, $rideId, $_POST);
        if (!$ok) {
            $this->json(['success' => false, 'error' => 'Update not allowed: ride already in progress or not yours.'], 403);
        }

        $this->json(['success' => true]);
    }

    /** POST /partner/api-create-ride.php */
    public function store(): never
    {
        $partnerId = (int) ($_SESSION['user_id'] ?? 0);

        $date   = trim((string) ($_POST['date']        ?? ''));
        $time   = trim((string) ($_POST['time']        ?? ''));
        $client = trim((string) ($_POST['client_name'] ?? ''));

        if ($date === '' || $time === '' || $client === '') {
            $this->json(['success' => false, 'error' => 'Fill in the required fields.'], 422);
        }

        $this->services->createForPartner($partnerId, $_POST);
        $this->json(['success' => true]);
    }
}
