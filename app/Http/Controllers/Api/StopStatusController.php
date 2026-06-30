<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;
use App\Support\Session;

final class StopStatusController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = Session::role() === 2
            ? ServiceRepository::forDriverContext()
            : ServiceRepository::default();
    }

    /** POST /api/stop-status.php */
    public function update(): never
    {
        $this->cors();

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $json     = $this->shieldedBody();
        $masterId = (int) ($json['master_ride_id'] ?? 0);
        $stopId   = (int) ($json['stop_id']        ?? 0);
        $action   = (string) ($json['action']      ?? '');

        if ($masterId === 0 || $stopId === 0 || !in_array($action, ['arrived', 'departed'], true)) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        if (Session::role() === 2 && $this->services->assignedDriver($masterId) !== Session::userId()) {
            $this->json(['success' => false, 'error' => 'Not your ride'], 403);
        }

        $field = $action === 'arrived' ? 'ts_arrived' : 'ts_departed';
        $ok    = $this->services->updateStopTimestamp($stopId, $masterId, $field);

        $this->json(['success' => $ok]);
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
