<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LiveLocationRepository;
use App\Repositories\ServiceRepository;

final class LocationController extends BaseController
{
    private LiveLocationRepository $locations;
    private ServiceRepository $services;

    public function __construct()
    {
        $this->locations = LiveLocationRepository::default();
        $this->services  = ServiceRepository::default();
    }

    /**
     * POST /api/location-update.php
     * Called every ~5 s by the driver app.
     * No session required — driver_id comes in the JSON payload.
     */
    public function update(): never
    {
        $this->cors();

        $payload  = $this->jsonBody();
        $rideId   = (int) ($payload['ride_id'] ?? 0);
        // driver_id must come from the authenticated session — never trust the payload.
        $driverId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        $lat      = (float) ($payload['lat']   ?? 0);
        $lng      = (float) ($payload['lng']   ?? 0);

        if ($rideId === 0 || $lat === 0.0 || $lng === 0.0) {
            $this->json(['success' => false, 'error' => 'Incomplete data'], 422);
        }

        $service = $this->services->find($rideId);
        if ($service !== null && $service->isCompleted()) {
            $this->json(['success' => true, 'skipped' => 'ride_completed']);
        }

        $this->locations->trackRide(
            $rideId, $driverId, $lat, $lng,
            (float) ($payload['speed']   ?? 0),
            (float) ($payload['heading'] ?? 0)
        );

        $this->json(['success' => true]);
    }

    /**
     * POST /api/location-upload.php
     * Alternative GPS upload path used by Capacitor background plugin.
     * Logic is identical to update().
     */
    public function upload(): never
    {
        $this->update();
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
