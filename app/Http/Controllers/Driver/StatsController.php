<?php

declare(strict_types=1);

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class StatsController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::forDriverContext();
    }

    /** GET /driver/stats.php */
    public function index(): void
    {
        $driverId     = (int) ($_SESSION['user_id'] ?? 0);
        $selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

        $totalCount     = $this->services->driverCountAllTime($driverId);
        $lastMonthCount = $this->services->driverCountLastMonth($driverId);
        $availableYears = $this->services->driverAvailableYears($driverId);
        $monthly        = $this->services->driverMonthlyByYear($driverId, $selectedYear);

        $yearTotal  = array_sum($monthly);
        $maxRides   = max($monthly);
        $bestMonth  = $maxRides > 0
            ? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][
                  (int) array_search($maxRides, $monthly)
              ]
            : '—';

        $this->view('driver.stats.index', compact(
            'totalCount', 'lastMonthCount', 'yearTotal', 'bestMonth',
            'monthly', 'availableYears', 'selectedYear'
        ));
    }
}
