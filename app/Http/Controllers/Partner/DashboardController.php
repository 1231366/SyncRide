<?php

declare(strict_types=1);

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;

final class DashboardController extends BaseController
{
    private ServiceRepository $services;

    public function __construct()
    {
        $this->services = ServiceRepository::default();
    }

    /** GET /partner/ or GET /partner/index.php */
    public function index(): void
    {
        $partnerId = (int) ($_SESSION['user_id'] ?? 0);
        $counts    = $this->services->partnerCounts($partnerId);
        $userName  = (string) ($_SESSION['name'] ?? 'Partner');

        $this->view('partner.dashboard.index', compact('counts', 'userName'));
    }
}
