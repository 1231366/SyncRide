<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LiveLocationRepository;

final class TrackingController extends BaseController
{
    private LiveLocationRepository $locations;

    public function __construct()
    {
        $this->locations = LiveLocationRepository::default();
    }

    /**
     * GET /api/tracking-get.php
     * - ?ride_id=N → single ride tracking (public, for client track page)
     * - no ride_id  → all active rides (admin live-map)
     */
    public function get(): never
    {
        header('Content-Type: application/json');
        ini_set('display_errors', '0');

        $rideId = isset($_GET['ride_id']) ? (int) $_GET['ride_id'] : null;

        if ($rideId !== null) {
            $data = $this->locations->trackingFor($rideId);
            $this->json(['success' => true, 'data' => $data]);
        }

        $this->json(['success' => true, 'data' => $this->locations->allActiveRides()]);
    }

    /**
     * POST /api/tracking-stop.php
     * Called by the driver app (Capacitor or web) when a ride ends.
     * driver_id may come from session OR JSON payload.
     */
    public function stop(): never
    {
        $this->cors();

        $payload  = $this->jsonBody();
        $rideId   = (int) ($payload['ride_id'] ?? 0);
        $driverId = (int) ($_SESSION['user_id'] ?? $payload['driver_id'] ?? 0);

        if ($driverId === 0) {
            $this->json(['success' => false, 'error' => 'Unauthorized: driver_id missing'], 401);
        }

        if ($rideId === 0) {
            $this->json(['success' => false, 'error' => 'ride_id missing'], 422);
        }

        $this->locations->stopRide($rideId, $driverId);
        $this->json(['success' => true, 'ride_id' => $rideId]);
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
