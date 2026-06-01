<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
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

        $json   = $this->jsonBody();
        $rideId = (int) ($json['ride_id'] ?? $_POST['ride_id'] ?? 0);
        $status = isset($json['status'])  ? (int) $json['status']  : (int) ($_POST['status'] ?? -1);

        if ($rideId === 0 || $status === -1) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        // A driver may only change the status of rides assigned to them
        if (Session::role() === 2 && $this->services->assignedDriver($rideId) !== Session::userId()) {
            $this->json(['success' => false, 'error' => 'Not your ride'], 403);
        }

        $this->services->updateStatus($rideId, $status);

        $labels = [1 => 'On the way', 2 => 'At pickup', 5 => 'With client', 3 => 'Trip started', 4 => 'Completed'];
        $label  = $labels[$status] ?? "Status {$status}";
        $this->logs->record("Service ID #{$rideId}: status changed to {$label}");

        $this->json(['success' => true, 'status' => $status]);
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
