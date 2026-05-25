<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\LiveLocationRepository;

final class LiveLocationsController extends BaseController
{
    private LiveLocationRepository $locations;

    public function __construct()
    {
        $this->locations = LiveLocationRepository::default();
    }

    /** GET /api/live-locations.php — admin-only, polled by the live map. */
    public function index(): never
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'], $_SESSION['role']) || (int) $_SESSION['role'] !== 1) {
            http_response_code(403);
            $this->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $drivers = array_map(static fn($loc): array => [
            'driver_id'   => $loc->driverId,
            'trip_id'     => $loc->tripId,
            'latitude'    => $loc->latitude,
            'longitude'   => $loc->longitude,
            'speed'       => $loc->speed,
            'heading'     => $loc->heading,
            'last_update' => $loc->lastUpdate,
            'name'        => $loc->driverName,
        ], $this->locations->allDrivers());

        $this->json(['success' => true, 'drivers' => $drivers]);
    }
}
