<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;
use App\Services\XmlVoucherImporter;

/**
 * Admin home page: KPI cards, performance sparkline, next-ride feed,
 * XML voucher import drop-target.
 */
final class DashboardController extends BaseController
{
    public function index(): void
    {
        $imported = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['xmlFile']) && $_FILES['xmlFile']['error'] === UPLOAD_ERR_OK) {
            $contents = (string) file_get_contents($_FILES['xmlFile']['tmp_name']);
            $imported = XmlVoucherImporter::default()->importFromString($contents);
        }

        // All figures go through the repository, which scopes every query to the
        // admin's company (super-admin sees all). Direct $db queries here leaked
        // every company's data into a single company's dashboard.
        $services = ServiceRepository::default();

        $stats = [
            'all_time' => $services->countAllTime(),
            'today'    => $services->countToday(),
            'week'     => $services->countThisWeek(),
        ];

        $nextRides    = $services->upcoming(3);
        $monthlyChart = $services->monthlyThisYear();

        $this->view('admin.dashboard', [
            'stats'        => $stats,
            'nextRides'    => $nextRides,
            'monthlyChart' => $monthlyChart,
            'imported'     => $imported,
        ]);
    }
}
