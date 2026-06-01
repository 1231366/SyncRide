<?php

declare(strict_types=1);

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class DashboardController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::forDriverContext();
    }

    /** GET /driver/ or GET /driver/index.php */
    public function index(): void
    {
        $driverId = (int) ($_SESSION['user_id'] ?? 0);

        if (isset($_GET['api']) && $_GET['api'] === 'refresh') {
            $serviceType = isset($_GET['serviceType']) ? (int) $_GET['serviceType'] : null;
            $this->json($this->services->driverDashboardRides($driverId, $serviceType));
        }

        $rides      = $this->services->driverDashboardRides($driverId);
        $todayCount = $this->services->driverCountToday($driverId);
        $weekCount  = $this->services->driverCountWeek($driverId);
        $userName   = (string) ($_SESSION['name'] ?? 'Driver');

        $this->view('driver.dashboard.index', compact(
            'rides', 'todayCount', 'weekCount', 'driverId', 'userName'
        ));
    }
}
