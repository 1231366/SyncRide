<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\BaseController;
use App\Repositories\CompanyRepository;

final class DashboardController extends BaseController
{
    private CompanyRepository $companies;

    public function __construct()
    {
        $this->companies = CompanyRepository::default();
    }

    public function index(): void
    {
        $stats = $this->companies->stats();

        $totalCompanies = count($stats);
        $totalRides     = array_sum(array_column($stats, 'total_rides'));
        $totalDrivers   = array_sum(array_column($stats, 'drivers'));
        $totalPartners  = array_sum(array_column($stats, 'partners'));
        $ridesThisWeek  = array_sum(array_column($stats, 'rides_today'));

        $this->view('superadmin.dashboard.index', compact(
            'stats', 'totalCompanies', 'totalRides',
            'totalDrivers', 'totalPartners', 'ridesThisWeek'
        ));
    }
}
