<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ServiceRepository;
use App\Services\XmlVoucherImporter;
use PDO;

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

        $db = $this->db();
        $services = ServiceRepository::default();

        $stats = [
            'all_time' => (int) $db->query('SELECT COUNT(*) FROM Services')->fetchColumn(),
            'today'    => (int) $db->query('SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE()')->fetchColumn(),
            'week'     => (int) $db->query('SELECT COUNT(*) FROM Services WHERE WEEK(serviceDate, 1) = WEEK(CURDATE(), 1)')->fetchColumn(),
        ];

        $nextRides = $db->query("
            SELECT * FROM Services
            WHERE (serviceDate > CURDATE())
               OR (serviceDate = CURDATE() AND serviceStartTime >= CURTIME())
            ORDER BY serviceDate ASC, serviceStartTime ASC
            LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);

        $monthlyChart = array_fill(0, 12, 0);
        $rows = $db->query("
            SELECT MONTH(serviceDate) AS m, COUNT(*) AS c
            FROM Services
            WHERE YEAR(serviceDate) = YEAR(CURDATE())
            GROUP BY m
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $monthlyChart[(int) $row['m'] - 1] = (int) $row['c'];
        }

        $this->view('admin.dashboard', [
            'stats'        => $stats,
            'nextRides'    => $nextRides,
            'monthlyChart' => $monthlyChart,
            'imported'     => $imported,
        ]);
    }
}
